# Theme development guide

A DESTRA theme is a folder inside `themes/` containing a `theme.json` manifest
and, optionally, view overrides and assets. Themes are discovered at runtime —
no rebuild or cache clear is needed to see a new folder, **Appearance → Themes**
lists it immediately.

```
themes/
└── mytheme/
    ├── theme.json          required
    ├── screenshot.svg      recommended (also .png/.jpg/.webp)
    ├── Views/              optional overrides
    │   ├── layout.php
    │   ├── home.php
    │   ├── content.php
    │   └── partials/
    │       └── card.php
    └── Assets/             optional static files
        ├── css/theme.css
        └── js/theme.js
```

If a view is missing, DESTRA falls back to `app/Views/frontend/fallback/…`
file by file. A theme can therefore override a single partial (for example only
`partials/card.php`) and inherit everything else.

---

## 1. Manifest (`theme.json`)

```json
{
    "name": "Studio",
    "version": "1.0.0",
    "description": "A warm editorial theme with oversized serif headlines.",
    "author": "Your Name",
    "screenshot": "screenshot.png",
    "license": "Commercial",
    "supports": {
        "dark_mode": true,
        "rtl": true,
        "layout": "fallback"
    },
    "colors": {
        "primary": "#4f46e5",
        "secondary": "#0ea5e9",
        "accent": "#f59e0b"
    }
}
```

| Field | Required | Notes |
| --- | --- | --- |
| `name` | yes | Shown in the admin. The folder name becomes the theme slug. |
| `version` | recommended | Free-form string. |
| `description` | recommended | Plain text, shown on the theme card. |
| `author` | recommended | Plain text. |
| `screenshot` | optional | File name relative to the theme root. Used on the card and preview. |
| `license` | optional | Shown as metadata only. |
| `supports.dark_mode` | optional | Set `true` if your theme ships dark-mode styles. |
| `supports.rtl` | optional | Set `true` if the layout works in right-to-left locales. |
| `supports.layout` | optional | Keep `fallback` unless you replace the whole layout contract. |
| `colors` | optional | Used by the admin preview swatches. |

Folder-name rules: lowercase letters, digits and hyphens only, starting with a
letter or digit (`^[a-z0-9][a-z0-9-]{1,40}$`). Anything else is ignored.

---

## 2. Views you can override

Mirror the fallback tree under `themes/mytheme/Views/`:

| View | Used for |
| --- | --- |
| `layout.php` | Global shell: `<head>`, header, menus, footer, popups, body class |
| `home.php` | Home page (hero + sections, depends on Reading settings) |
| `archive.php` | Category, tag, author, search and blog index |
| `content.php` | Single post |
| `page.php` | Single page |
| `docs.php` | Documentation article (with the docs tree) |
| `404.php` | Not-found page |
| `maintenance.php` | Maintenance mode |
| `message.php` | Confirmation / unsubscribe / system messages |
| `generic.php` | Absolute fallback |
| `partials/card.php` | The content card used everywhere (`grid`, `list`, `hero`, `wide`, `compact`) |
| `partials/sidebar.php` | Sidebar widgets |
| `partials/comments.php` | Comment list and form |
| `partials/newsletter.php` | Newsletter block |
| `partials/form.php` | Custom form renderer |
| `partials/order-form.php` | Order / quote form |
| `partials/pagination.php` | Pager |
| `partials/breadcrumbs.php` | Breadcrumbs |
| `partials/docs-nav.php` | Documentation tree |
| `partials/share.php` | Social share buttons |
| `partials/ad.php` | Ad slot renderer |
| `partials/popups.php` | Banners and popups |

A `layout.php` gets `$body` (the rendered inner view) plus the shared shell
variables: `$locale`, `$language`, `$theme`, `$themeSlug`, `$languages`,
`$isRtl`, `$seo`, `$menu`, `$siteName`, `$settings` helpers and so on. The
easiest way to start is to copy `app/Views/frontend/fallback/layout.php` into
your theme and edit it.

---

## 3. Helpers available in every theme view

```php
<?= esc($content['title']) ?>
<?= content_url($content) ?>                 // locale-aware canonical link
<?= esc(human_date($content['published_at'])) ?>
<?= esc(ui('read_more', 'Read more')) ?>     // translatable UI string
<?= setting('general.site_name', 'DESTRA') ?>
<?= setting_bool('reading.show_views', false) ? 'yes' : 'no' ?>
<?= theme_asset('assets/css/theme.css') ?>   // → /theme-assets/mytheme/assets/css/theme.css
<?= render_content($content['body']) ?>      // purified HTML + shortcodes
<?= share_links($content) ?>                 // social share buttons
```

Full list: `app/Helpers/destra_helper.php`.

Rules:

- Always escape dynamic text with `esc()` (or `esc($v, 'attr')` inside attributes).
- Use `content_url()`, `category_url()`, `tag_url()`, `author_url()`,
  `search_url()` and `loc_url()` instead of hard-coding paths — they handle
  `/en/`, `/id/`, `/ar/` prefixes.
- Do not call models directly. Use `service('contentService')`, `service('settings')`
  and the prepared `$content` / `$items` arrays.
- Keep `<html dir="<?= dir_attr() ?>">` and `lang="<?= current_locale() ?>"`.

---

## 4. Assets

Put files in `themes/mytheme/Assets/` and reference them with
`theme_asset('assets/css/theme.css')`.

- The URL is `/theme-assets/{slug}/…` and is served by a whitelisting controller.
- PHP files are never executed or served.
- Allowed extensions: css, js, map, json, txt, svg, png, jpg/jpeg, gif, webp,
  avif, ico, woff/woff2/ttf/otf/eot, mp4, webm.
- `theme_asset()` appends a `?v=` cache-buster based on the file mtime.
- Files are served with a long-lived cache header, so version your changes by
  editing the file (mtime changes) or by adding `?v=2` manually.

If you prefer a build step, compile your CSS locally and ship the compiled file;
the server never runs Node.js.

---

## 5. Activate, preview and test

1. Copy the folder into `themes/`.
2. Admin → **Appearance → Themes**. Your card appears with its screenshot.
3. **Preview** shows the theme on the public site for your admin session only.
4. **Activate** stores `appearance.theme` in settings and flushes the cache.
5. Test: home, blog index, single post, page, docs, category, tag, search,
   contact, 404, mobile width 360 px, dark mode (if supported) and Arabic
   (`/ar/`) for RTL.

The active theme is also honoured in the admin preview and by
`theme_view()` inside plugins.

---

## 6. Distributing a theme

Zip the theme folder so that `theme.json` is at the archive root:

```
mytheme.zip
└── mytheme/
    ├── theme.json
    └── Views/…
```

extract it into `themes/`. Themes are filesystem-based by design — they
never touch the database, so activating and rolling back is instant.

For DESTRAWEB, include a `README.md` inside the theme folder with installation
steps, a feature list and support contact details. The manifest `license` field
should match your Envato license.
