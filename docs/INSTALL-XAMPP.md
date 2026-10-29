# Installing DESTRA CMS on XAMPP (Windows / macOS)

This guide covers a local installation for development, demos and screenshots.
For production hosting, use [INSTALL-CPANEL.md](INSTALL-CPANEL.md).

---

## 1. Prepare XAMPP

1. Download XAMPP with **PHP 8.2 or newer** from <https://www.apachefriends.org/>.
2. Install it (default `C:\xampp` on Windows, `/Applications/XAMPP` on macOS).
3. Start **Apache** and **MySQL** from the XAMPP control panel.
4. Optional: open <http://localhost/phpmyadmin> and confirm MySQL is reachable.

Verify the PHP extensions the installer needs are enabled in `php.ini`
(`C:\xampp\php\php.ini`). Remove the leading `;` from:

```
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=fileinfo
extension=gd
extension=zip
extension=curl
```

Restart Apache after editing `php.ini`.

---

## 2. Copy the package

Extract the CodeCanyon archive and copy the project folder into the web root:

```
C:\xampp\htdocs\destra
```

The folder must contain `app/`, `public/`, `vendor/`, `writable/`, `themes/` and `plugins/`.

You have two ways to open the site:

| Option | URL | Notes |
| --- | --- | --- |
| Point at `public/` (recommended) | `http://localhost/destra/public/` | Cleanest; works without extra configuration |
| Root `.htaccess` fallback | `http://localhost/destra/` | Requires `mod_rewrite` and `AllowOverride All` |

For the fallback to work in XAMPP, open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
or `httpd.conf` and make sure the `htdocs` directory allows overrides:

```apache
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

Then restart Apache.

---

## 3. Permissions (macOS / Linux)

On Windows, XAMPP usually has the required permissions already. On macOS/Linux:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/destra
chmod -R 775 writable themes plugins
```

`writable/` must be writable by the web server; `themes/` and `plugins/` are only
required for installing extensions from the admin panel.

---

## 4. Create the database

The installer can create the database automatically when the MySQL user has the
`CREATE` privilege (`root` on a default XAMPP install does). If you prefer to
create it yourself:

1. Open <http://localhost/phpmyadmin>.
2. **New** → database name `destra_cms` → collation `utf8mb4_unicode_ci` → **Create**.

Keep the name, user and password for the next step.

---

## 5. Run the installer

Open:

```
http://localhost/destra/public/install
```

(or `http://localhost/destra/install` if you used the root `.htaccess`).

### Step 1 — Requirements
The installer checks PHP version, extensions, and writable folders. Fix anything
marked red and reload. On macOS, `writable/` permissions are the usual culprit.

### Step 2 — Database
Enter:

| Field | Example |
| --- | --- |
| Host | `localhost` |
| Port | `3306` |
| Database | `destra_cms` |
| Username | `root` |
| Password | *(empty on a default XAMPP install)* |
| Table prefix | `cms_` |

The installer verifies the connection, creates the database if needed, writes
`.env` with a freshly generated AES-256 key, and runs all migrations.

### Step 3 — Site & administrator
Enter the site name, base URL, timezone, default language, and the first
Super Admin account (name, email, password). Choose whether to import the demo
content. Finish — you are redirected to the admin login.

> The base URL must include the subfolder and a trailing slash, e.g.
> `http://localhost/destra/public/`.

---

## 6. After installation

- Admin panel: `http://localhost/destra/public/admin`
- Frontend: `http://localhost/destra/public/`
- Log in with the Super Admin account you created in step 3.

Recommended first steps:

1. **Settings → General** — site name, tagline, logo, timezone, date format.
2. **Settings → Reading** — homepage layout, posts per page.
3. **Appearance → Themes** — activate *Modern*, *Classic* or *Velocity*.
4. **Content** — edit or delete the demo posts.
5. **Settings → Mail** — configure SMTP before enabling notifications.

---

## 7. Common issues

| Symptom | Fix |
| --- | --- |
| `404` on every page except the home page | Enable `mod_rewrite`, set `AllowOverride All`, restart Apache |
| “Missing PHP extension” | Enable the extension in `php.ini`, restart Apache |
| Installer is locked / redirects to home | Delete `writable/installed.lock` and reload `/install` |
| Blank page or 500 error | Open `writable/logs/` and read today's log; set `CI_ENVIRONMENT=development` in `.env` temporarily |
| CSS or JS not loading | The base URL in `.env` is wrong (must include the subfolder and trailing slash) |
| Uploads fail | Check `upload_max_filesize` and `post_max_size` in `php.ini` and `writable/uploads/` permissions |
| Arabic text looks mirrored in dates | Hard-refresh once; the bundled CSS isolates dates and numerals |

---

## 8. Changing the port

If port 80 is taken, change Apache to 8080 in XAMPP, then use
`http://localhost:8080/destra/public/` everywhere (including `app.baseURL` in
`.env` or **Settings → General**).
