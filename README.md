# DESTRA CMS

**A complete, self-hosted publishing platform built on CodeIgniter 4 — posts, pages, documentation, media, multilingual content, plugins and themes in one elegant admin.**

DESTRA CMS is a commercial-grade alternative to a general-purpose blog engine. It ships with an editorial admin (AdminLTE 4 + Bootstrap 5), a hand-crafted frontend theme system (Tailwind CSS), a guided installer, a token-authenticated REST API and a WordPress-style extension layer. No build tools are required on the buyer's server.

- **Version:** 1.0.0 “Aurora”
- **Framework:** CodeIgniter 4.7.x (PHP 8.2+)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** Tailwind CSS 3, responsive down to 360 px, app-style mobile navigation, dark mode, RTL
- **Admin:** AdminLTE 4, four color schemes, command palette, keyboard shortcuts, mobile bottom bar

---

## Feature highlights

### Content
- Posts, pages and a nested documentation tree with their own layout
- Sections, categories and tags with SEO fields and friendly URLs
- Revisions-friendly editor with TinyMCE, CKEditor 5 or Summernote (switchable per site)
- Custom fields (text, textarea, select, number, date, bool) on any content type
- Featured images, galleries, attachments, reading time, view counter
- Scheduling, expiry dates, sticky posts, trash and restore
- Bulk actions and bulk upload workflows
- Full multilingual content: English, Indonesian and Arabic seeded; add any language
- Arabic RTL typography and bidi-safe dates

### Engagement
- Threaded comments with honeypot, optional captcha, rate limits and moderation
- AJAX contact forms with a form builder and a searchable inbox
- Newsletter with double opt-in, confirmation and one-click unsubscribe
- Orders / request-a-quote flow (AJAX, stored in the admin)
- Banners, popups, ad placements and a home-page slider
- Related posts, social sharing, most-viewed lists

### Appearance
- Theme system with manifests, screenshots, previews and per-theme assets
- 4 admin color schemes, collapsible sidebar, dark admin theme
- Menu builder (header, footer, mobile), widget/sidebar areas
- Custom CSS and custom head/footer code (CSP-aware, sanitized)
- Preloader and page transition toggles

### Security
- **Argon2id** password hashing
- **AES-256-GCM** envelope encryption for stored secrets (OAuth, SMTP, API keys)
- CSRF tokens in every form, with automatic AJAX header injection
- Strict input sanitizer: blocklists, depth/length limits, HTML purification
- Output escaping everywhere, Content-Security-Policy with nonces
- Path traversal / LFI guards, upload MIME sniffing, executable-upload blocking
- Login throttling, session hardening, audit log, optional 2FA-ready structure
- Granular role and permission system (Super Admin, Admin, Editor, Author, plus custom roles)

### SEO & feeds
- Per-content SEO title, description, keywords, canonical and Open Graph image
- XML sitemaps (multilingual, index + per-type), robots.txt, RSS/Atom feeds
- Redirect manager with 301/302 rules and hit counts
- Clean URL structure: `/blog/{slug}`, `/page/{slug}`, `/docs/{slug}`, `/category/{slug}`, `/tag/{slug}`, locale-prefixed variants
- Schema.org JSON-LD, breadcrumbs, hreflang alternates

### Integrations
- REST API v1 with scoped tokens (`content:read`, `content:write`, `media:write`, …)
- OAuth social sign-in (Google, Facebook, GitHub, LinkedIn, X, Apple-ready table)
- reCAPTCHA / hCaptcha / Turnstile abstraction
- Google Analytics / Tag Manager, Google Maps embeds, Mailchimp and Mailcoach adapters
- SMTP mail with branded transactional templates
- Webmail-style inbox for form submissions, graphical reports

### Operations
- Guided browser installer (requirements check, DB creation, `.env` generation, migrations, demo seed)
- File-based cache with one-click flush, maintenance mode, debug toggle
- Audit log, notifications, dashboard search, calendar
- Works on XAMPP out of the box; cPanel shared hosting supported without Composer
- All frontend libraries are bundled locally — no CDN dependency

---

## Requirements

| Item | Minimum |
| --- | --- |
| PHP | 8.2 (8.3 recommended) |
| Extensions | `intl`, `mbstring`, `mysqli`, `openssl`, `fileinfo`, `gd`, `zip`, `json`, `curl` |
| Database | MySQL 5.7+ or MariaDB 10.3+ |
| Web server | Apache with `mod_rewrite` (Nginx config notes included) |
| Disk | ~60 MB extracted (vendor libraries bundled) |

The bundled installer checks every requirement before it writes anything.

---

## Installation

Two guides are included:

- [docs/INSTALL-XAMPP.md](docs/INSTALL-XAMPP.md) — local Windows/macOS development with XAMPP
- [docs/INSTALL-CPANEL.md](docs/INSTALL-CPANEL.md) — shared hosting / cPanel, with or without a custom document root

Short version:

1. Upload the package and point the document root at `public/` (or use the included root `.htaccess` fallback).
2. Make `writable/`, `themes/` and `plugins/` writable (755/775).
3. Create an empty MySQL database (the installer can create it too).
4. Open `https://your-domain.tld/install` and follow the three steps.
5. Delete `writable/installed.lock` if you ever need to re-run the installer.

The installer creates your first Super Admin account — there are no default credentials.

---

## Directory structure

```
app/                Application code (controllers, models, views, libraries)
  Config/           Routes, filters, Destra product config
  Controllers/      Admin, Api, Auth, Frontend, Install
  Database/         Migrations and the installer seeder
  Libraries/        Crypto, Sanitizer, ThemeManager, PluginManager, SEO, Mail…
  Views/            admin/, frontend/fallback/, errors/, install/
plugins/            Drop-in plugins (author-box ships as a reference)
public/             Web root — index.php, assets, bundled vendor libraries
resources/          Tailwind source (resources/css/frontend.css)
sdk/                Plugin base class for extensions
themes/             Theme packages (classic, modern, velocity)
writable/           Cache, logs, sessions, uploads, installer lock
docs/               Installation, extension and API guides
scripts/            Packaging helper
```

---

## Extending DESTRA

- **Themes** — [docs/THEMES.md](docs/THEMES.md): manifest fields, view overrides, asset pipeline, preview and activation.
- **Plugins** — [docs/PLUGINS.md](docs/PLUGINS.md): `plugin.json`, the `Destra\SDK\Plugin` base class, hooks, settings screens and packaging.
- **REST API** — [docs/API.md](docs/API.md): token scopes, endpoints, examples and the OpenAPI document.
- **Security model** — [docs/SECURITY.md](docs/SECURITY.md): hashing, encryption, CSRF, sanitization, uploads and hardening checklist.

---

## Updating the CSS (developers only)

The compiled Tailwind file is already included. If you edit `resources/css/frontend.css`:

```bash
npm install
npm run build:frontend
```

Production servers do not need Node.js.

---

## License

DESTRA CMS is free software that can be used for both commercial and non-commercial purposes. Please refer to the LICENSE for full terms and conditions. The CodeIgniter 4 framework, along with the included open-source libraries, remains subject to their respective licenses (MIT and others); attribution files can be found within the `vendor/` and `public/vendor/` directories.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
"# destraweb-cms" 
