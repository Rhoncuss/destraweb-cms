# Security model

DESTRA CMS is built for small teams that host their own publishing platform.
This document describes the controls that are implemented in the code, how they
interact, and what the site owner still has to do.

> Security is a process. The defaults below are deliberately strict, but no CMS
> can replace patching PHP, MySQL and the operating system, enforcing HTTPS and
> keeping off-site backups.

---

## 1. Passwords

- Hashing: **Argon2id** via `password_hash(PASSWORD_ARGON2ID, …)`.
- Parameters are centralised in `App\Libraries\Auth::argonOptions()`.
- Hashes are transparently re-hashed on successful login when the cost changes
  (`password_needs_rehash`).
- Password input is trimmed, length-limited and never logged.
- Login attempts are throttled per IP/account; failures are written to the
  audit log.

Password reset tokens are random 32-byte values, stored only as an
HMAC-SHA256 hash with an expiry, and invalidated after use.

---

## 2. Secrets at rest (AES-256-GCM)

OAuth client secrets, SMTP passwords and plugin secrets are encrypted with
**AES-256-GCM** envelope encryption (`App\Libraries\Crypto`):

```
payload = "dst1:" . base64(nonce[12] . tag[16] . ciphertext)
```

- The 32-byte key is derived with `hash_hkdf('sha256', …, 'destra-aes-256-gcm')`
  from `destra.encryptionKey` in `.env`.
- The nonce is random per encryption; the GCM tag is verified on decrypt, so
  tampering fails closed (`decrypt()` returns `null`).
- Additional authenticated data (AAD) binds a secret to its purpose, e.g.
  `oauth:google`, so a ciphertext cannot be moved between providers.
- API tokens and newsletter/reset tokens are stored as **HMAC-SHA256** hashes,
  never in plaintext. IP addresses are stored as HMAC hashes.
- Database backups therefore do not expose usable credentials by themselves.

**Do not change `destra.encryptionKey` after storing secrets** — encrypted values
become unreadable. If you must rotate it, re-enter every secret in the admin
afterwards.

---

## 3. CSRF

- Protection is **session-based** (`Config\Security::$csrfProtection = 'session'`),
  so the token is never exposed as a readable cookie.
- Token name: `destra_csrf`; header: `X-CSRF-TOKEN`.
- Every form rendered by the admin, installer, auth screens and the frontend
  includes `csrf_field()` (or the `destra_csrf_meta()` meta tag for AJAX).
- AJAX helpers in `public/assets/*/js` read the meta tag and send the header
  automatically.
- Regeneration on submit is disabled to keep multi-tab admin sessions working;
  tokens are still bound to the session and expire with it.
- API routes are stateless and do not use CSRF; they require a bearer token.

---

## 4. Input handling and output encoding

### Input

A global filter (`InputGuard`) runs before every request:

- rejects null bytes, `%00`, `..`, encoded traversal and backslashes in the URI,
- blocks `/.env`, `/.git`, `/.htaccess`, `/app/`, `/writable/`, `/vendor/`,
  `/sdk/`, `/spark`, `/composer.*`, `/phpunit*`, `/themes/`, `/plugins/`,
- rejects bodies larger than 50 MB,
- sanitises every `$_GET`, `$_POST` and `$_REQUEST` value recursively
  (`Sanitizer::cleanArray`, depth limit 8).

The `Sanitizer` service provides typed helpers used by controllers:

| Method | Use |
| --- | --- |
| `text()`, `textarea()` | Strip all tags, normalise whitespace, enforce max length |
| `html()` | HTMLPurifier with a conservative allow-list (the only path for rich text) |
| `css()`, `js()` | Strip dangerous constructs (`expression(`, `javascript:`, `@import url`, `</script`) |
| `email()`, `username()`, `slug()`, `url()`, `filename()` | Validate and normalise |
| `int()`, `bool()`, `date()`, `color()`, `icon()` | Coerce to a safe type |

Rich fields are declared in `Config\Destra::$richKeys`. They are purified with
HTMLPurifier, never trusted raw. Script injection through content is blocked by
both purification and the Content-Security-Policy.

### Output

- Views escape with CodeIgniter's `esc()` (context-aware `html`/`attr`).
- URLs are validated and same-origin checked before redirects
  (`safe_redirect_url`), preventing open redirects.
- JSON responses use `setJSON()`, which sets the correct MIME type and prevents
  JSON hijacking via `X-Content-Type-Options: nosniff`.

---

## 5. Uploads and file handling

- Uploads are validated by **MIME sniffing** (`fileinfo`) plus extension, size
  and image dimension checks, not by the client-supplied type.
- Only allow-listed extensions are accepted (`Config\Destra`), and PHP-like
  extensions are never executable: uploads live in `writable/uploads/` outside
  the web root and are streamed through a controller.
- Image uploads are re-encoded (WebP) and thumbnails generated with GD, which
  neutralises polyglot/metadata payloads.
- Filenames are normalised; the stored name is random-hashed, the original name
  is kept only as metadata.
- ZIP uploads (plugins/themes) reject absolute paths, `..` segments, symlinks
  and archives larger than 50 MB / 2,000 files.
- Theme and plugin assets are served by whitelisting controllers, never by
  direct PHP execution. Only static extensions are returned.

---

## 6. Headers and transport

`SecurityHeaders` sets on every response:

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=(), usb=()
Cross-Origin-Opener-Policy: same-origin
X-Permitted-Cross-Domain-Policies: none
Content-Security-Policy: default-src 'self'; … nonce-based script-src …
Strict-Transport-Security: max-age=31536000; includeSubDomains   (HTTPS only)
```

- `X-Powered-By` is removed.
- `X-XSS-Protection: 0` is intentional: the legacy auditor is removed and CSP
  is the real control.
- CSP uses a per-request nonce. Template scripts use `nonce="<?= csp_nonce() ?>"`.
  If you add a third-party script, either add its origin in
  `app/Filters/SecurityHeaders.php` or implement it through a plugin that outputs
  a nonce-protected tag.
- The CSP can be disabled globally in **Settings → Security** for debugging.

---

## 7. Sessions and authentication

- Session cookie: `destra_session`, `HttpOnly`, `SameSite=Lax`, `Secure` when
  HTTPS is detected.
- Session IDs are regenerated on login and destroyed on logout; sessions carry a
  user-agent fingerprint that must keep matching.
- Admin and public sessions share the cookie but admin routes require both a
  valid session and an active user with the right permission.
- Permission checks are enforced by route filters (`permission:…`) **and** in
  controllers/services for defence in depth.
- Inactive, deleted or locked users lose access immediately; remember-me tokens
  are not used.
- OAuth sign-in is opt-in per provider and can be disabled globally in
  **Settings → Marketing** (`social.login_enabled`).

---

## 8. Abuse prevention

| Control | Default |
| --- | --- |
| Login throttle | Per IP + identifier, escalating delay |
| Comment rate limit | 1 per 60 s per IP, 12 per hour |
| Newsletter throttle | 4 s per session + captcha |
| Contact/order forms | Honeypot, captcha (if configured), IP rate limit |
| API | 120 requests/min per token (`api.rate_limit`) |
| Public forms | CSRF + honeypot; captcha adapter supports reCAPTCHA v3, hCaptcha and Turnstile |

Honeypot fields are visually hidden, `tabindex="-1"`, `autocomplete="off"` and
must stay empty.

---

## 9. Audit trail

Security-relevant actions are appended to `cms_audit_logs` with actor, IP hash,
user agent, action, entity and a JSON context:

- logins, failed logins, password changes, role changes,
- settings changes, plugin/theme activation,
- content publish/delete, media deletion, comment moderation, API token
  creation/revocation, OAuth configuration.

The log is read-only in the UI (no delete button) and is included in database
backups.

---

## 10. Installer and deployment hardening

The installer is reachable only while `writable/installed.lock` is missing and
creates that lock on completion. Do not leave a half-finished installation
public.

Checklist before going live:

- [ ] Serve the site over HTTPS and enable **Force HTTPS** (`app.forceGlobalSecureRequests = true`).
- [ ] Set `.env` permissions to `600`; it contains the database password and the encryption key.
- [ ] Set `CI_ENVIRONMENT = production` and disable the debug toolbar.
- [ ] Point the document root at `public/` (preferred) or keep the bundled root `.htaccess`.
- [ ] Remove write access to `app/`, `vendor/`, `public/` for the web user (only `writable/` needs writes).
- [ ] Change the default admin path (`destra.adminPrefix`) from `admin` to something unique.
- [ ] Use strong, unique passwords for the admin and the database user; do not use MySQL `root`.
- [ ] Restrict database user privileges to the CMS database only.
- [ ] Configure SMTP and verify SPF/DKIM so password-reset mail is not spoofed or lost.
- [ ] Schedule encrypted off-site backups of files **and** the database.
- [ ] Keep PHP, MySQL/MariaDB and the OS patched; subscribe to DESTRAWEB item updates.
- [ ] Review **System → Audit log** after launch and periodically thereafter.
- [ ] Test restoring a backup before you need it.

---

## 11. Reporting a vulnerability

If you find a security issue in DESTRA CMS, please report it privately through
the item's DESTRAWEB support tab, including reproduction steps and the affected
version. Do not open a public review with exploit details.

Please allow a reasonable window for a fix before disclosure. Confirmed issues
are fixed in the next release and credited in `CHANGELOG.md` if you wish.
