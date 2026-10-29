# Installing DESTRA CMS on cPanel / shared hosting

DESTRA CMS is designed to run without Composer or Node.js on the server. Every
PHP dependency is bundled in `vendor/` and every frontend library in
`public/vendor/`.

The only hard requirements are PHP 8.2+ and MySQL/MariaDB.

---

## 1. Choose a document-root strategy

### A. Point the domain at `public/` (recommended)

If your host lets you change the document root:

1. cPanel → **Domains** → manage the domain → set **Document Root** to
   `/home/USER/destra/public`.
2. Done. Requests never reach `app/`, `.env` or `vendor/`.

### B. Keep the document root at `public_html` (fallback)

The package includes a root `.htaccess` that:

- blocks direct access to `.env`, `app/`, `vendor/`, `writable/`, `themes/`, `plugins/`, `composer.*` and similar,
- silently rewrites every request into `public/`.

Upload the whole package into `public_html` and keep that `.htaccess`. This
works on any Apache host with `mod_rewrite` and `AllowOverride All` (the cPanel
default). Do **not** delete it.

> On LiteSpeed hosts (most cPanel servers) `.htaccess` is honoured as well.

---

## 2. Upload the files

1. cPanel → **File Manager** → open the target directory (e.g. `public_html`).
2. Upload `destra-cms-1.0.0.zip`.
3. Select the archive → **Extract**.
4. If the archive extracted into a nested folder, move its contents up so that
   `app/`, `public/`, `vendor/` sit directly inside `public_html`.

Keep `writable/` as a top-level folder, not inside `public/`.

---

## 3. PHP version and extensions

1. cPanel → **MultiPHP Manager** → select your domain → choose **PHP 8.2** (or 8.3).
2. cPanel → **MultiPHP INI Editor** → add or verify:

```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 24M
max_execution_time = 120
```

3. In **Select PHP Version → Extensions**, enable:
   `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `openssl`, `fileinfo`, `gd`, `zip`, `curl`, `json`.

The installer checks these and refuses to continue if a required one is missing.

---

## 4. Create the database

cPanel → **MySQL® Databases**:

1. Create a database, e.g. `myuser_destra`.
2. Create a user with a strong password.
3. Add the user to the database with **All Privileges**.

Note the full names (cPanel prefixes them with your account name).

---

## 5. Permissions

In **File Manager** (enable “Show Hidden Files”):

| Path | Permission |
| --- | --- |
| `writable/` and all subfolders | `775` |
| `writable/uploads/` | `775` |
| `themes/` | `775` |
| `plugins/` | `775` |
| `.env` (after installation) | `600` |
| everything else | `644` files / `755` folders |

Do not set `777`; it is unnecessary and often blocked.

---

## 6. Run the installer

Open:

```
https://your-domain.tld/install
```

If you used strategy A (document root = `public/`), the URL is simply
`https://your-domain.tld/install`.

Follow the three steps:

1. **Requirements** — PHP version, extensions, writable folders.
2. **Database** — host `localhost`, the database name and user from step 4,
   prefix `cms_`. The installer writes `.env` and imports the schema.
3. **Site & administrator** — site name, base URL (`https://your-domain.tld/`),
   timezone, first Super Admin, optional demo content.

When it finishes, the installer creates `writable/installed.lock` and the
installer routes disappear automatically.

---

## 7. Post-install checklist

1. Set the `.env` permission to `600`.
2. Force HTTPS: cPanel → **Domains** → enable **Force HTTPS Redirect**, or use
   the AutoSSL toggle. Then set `app.forceGlobalSecureRequests = true` in `.env`.
3. Log in at `/admin` and open **Settings → General** to confirm the URL, logo and timezone.
4. Configure **Settings → Mail** (SMTP). Shared hosts often block PHP `mail()`;
   using the host's SMTP hostname with port 587 is the reliable option.
5. Configure **Settings → Integrations** for captcha, analytics and maps.
6. Remove the demo content you do not need (**Content → trash**).
7. Set up cPanel backups (files + database) before going live.

### Re-running the installer

Delete `writable/installed.lock` and open `/install` again. The installer is
idempotent and will not duplicate the schema, but it will overwrite `.env`.
Back up `.env` first if you have custom values.

---

## 8. Nginx (optional)

If your host uses Nginx instead of Apache, add inside the `server` block, with
the document root pointing at `public/`:

```nginx
root /home/USER/destra/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php$is_args$args;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

location ~* /(app|vendor|writable|themes|plugins|resources|sdk|tests|scripts)/ {
    deny all;
}

location ~ /\.(env|git) {
    deny all;
}
```

---

## 9. Troubleshooting

| Symptom | Fix |
| --- | --- |
| “403 Forbidden” on the home page | `public/index.php` permissions, or `Options -Indexes` conflict — check the error log |
| Installer shows a red requirement | Enable the PHP extension in **Select PHP Version** |
| “Database connection failed” | Check the cPanel-prefixed DB/user names and that the user is attached to the DB |
| 500 error after install | Read `writable/logs/log-YYYY-MM-DD.php`; temporarily set `CI_ENVIRONMENT = development` in `.env` |
| CSS/JS missing | `app.baseURL` must be `https://your-domain.tld/` with a trailing slash |
| Uploads rejected | Raise `upload_max_filesize`/`post_max_size`; the app limit is set in **Settings → Media** |
| Emails not arriving | Use SMTP, verify SPF/DKIM in cPanel → **Email Deliverability** |
| Login redirect loop | Clear cookies for the domain; make sure `cookie.domain` in `.env` matches the host |
