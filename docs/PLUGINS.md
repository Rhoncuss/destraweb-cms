# Plugin development guide

DESTRA plugins are self-contained folders in `plugins/`. They are activated per
site from **Appearance → Plugins**, can expose a settings screen, and hook into
the frontend without editing core files. ZIP upload and manual FTP installation
are both supported.

```
plugins/
└── my-plugin/
    ├── plugin.json          required
    ├── src/
    │   └── MyPlugin.php     the entry class
    ├── Views/               optional PHP views
    └── Assets/              optional css/js/images
```

The bundled `author-box` plugin is a complete, working reference. Read
`plugins/author-box/plugin.json` and `plugins/author-box/src/AuthorBox.php`
before you start.

---

## 1. Manifest (`plugin.json`)

```json
{
    "name": "Reading Progress",
    "slug": "reading-progress",
    "version": "1.0.0",
    "author": "Your Name",
    "description": "Adds a slim reading progress bar to every article.",
    "namespace": "Destra\\Plugins\\ReadingProgress",
    "class": "Destra\\Plugins\\ReadingProgress\\ReadingProgress",
    "main": "src/ReadingProgress.php",
    "settings": {
        "color": {
            "label": "Bar colour",
            "type": "text",
            "default": "#4f46e5"
        },
        "show_on_pages": {
            "label": "Also show on static pages",
            "type": "bool",
            "default": "0"
        }
    }
}
```

| Field | Required | Notes |
| --- | --- | --- |
| `name` | yes | Shown in the admin. |
| `main` | yes | Entry PHP file, relative to the plugin folder. |
| `slug` | recommended | Lowercase, digits and hyphens. Defaults to the folder name on manual install. |
| `version` | recommended | Free-form string. |
| `author`, `description` | recommended | Plain text, shown on the plugin card. |
| `namespace` | optional | PSR-4 namespace mapped to `src/` automatically. |
| `class` | recommended | Fully-qualified entry class. |
| `settings` | optional | Settings schema rendered in the admin (see below). |

---

## 2. The entry class

The recommended base class is `Destra\SDK\Plugin`:

```php
<?php

namespace Destra\Plugins\ReadingProgress;

use Destra\SDK\Plugin;

class ReadingProgress extends Plugin
{
    public function register(): void
    {
        $this->addFilter('content.render', [$this, 'injectBar'], 20);
        $this->addAction('frontend.body_end', [$this, 'scripts'], 20);
    }

    public function injectBar(string $html, array $content = []): string
    {
        return '<div class="rp-bar" data-progress></div>' . $html;
    }

    public function scripts(): void
    {
        $color = esc((string) $this->setting('color', '#4f46e5'), 'attr');

        echo '<style>.rp-bar{position:fixed;top:0;left:0;height:3px;background:' . $color . ';z-index:9999;width:0}</style>';
        echo '<script>(function(){var b=document.querySelector("[data-progress]");if(!b)return;'
            . 'addEventListener("scroll",function(){var h=document.documentElement;'
            . 'b.style.width=(h.scrollTop/(h.scrollHeight-h.clientHeight)*100)+"%";},{passive:true});})();</script>';
    }
}
```

Lifecycle methods:

| Method | When it runs |
| --- | --- |
| `register()` | On every request while the plugin is active. Register hooks here. |
| `activate()` | Once, when the admin activates the plugin. |
| `deactivate()` | Once, when the admin deactivates it. |

The SDK also provides `addAction()`, `addFilter()`, `meta()`, `setting()` and an
optional `settingsSchema()` override.

A class does not have to extend the SDK: any class with a public `register()`
method and an optional `setContext(array $meta, array $settings)` method works,
which is exactly what the bundled `AuthorBox` demonstrates.

---

## 3. Hooks

Actions are fire-and-forget; filters transform a value.

| Hook | Type | Signature |
| --- | --- | --- |
| `frontend.head_end` | action | `()` — output markup before `</head>` |
| `frontend.body_end` | action | `()` — output markup before `</body>` |
| `content.render` | filter | `(string $html, array $content): string` — article body, after purification/shortcodes |
| `shortcode.output` | filter | `(string $html): string` — final shortcode output |
| `admin.menu` | filter | `(array $items): array` — add admin navigation entries |

Register with priorities (lower runs first):

```php
Hook::add('frontend.body_end', [$this, 'scripts'], 20);
Hook::filter('content.render', [$this, 'append'], 20);
```

The namespaced helpers are equivalent:

```php
$this->addAction('frontend.body_end', [$this, 'scripts'], 20);
$this->addFilter('content.render', [$this, 'append'], 20);
```

Inside an action, echo escaped HTML. Inside a filter, return the modified string.
Never echo unescaped user data; wrap every dynamic value with `esc()`.

---

## 4. Shortcodes

Shortcodes are rendered inside content after HTML purification, so they can
output safe markup built from escaped attributes:

```php
public function register(): void
{
    service('shortcodes')->add('cta', function (array $attrs, string $body): string {
        $url   = esc((string) ($attrs['url'] ?? '#'), 'attr');
        $label = esc($body !== '' ? $body : 'Learn more');

        return '<a class="dst-btn dst-btn--primary" href="' . $url . '">' . $label . '</a>';
    });
}
```

Editors then write `[cta url="https://example.com"]Read the guide[/cta]`.

---

## 5. Settings screen

The `settings` object in `plugin.json` becomes a real form in the admin.
Supported field types:

| `type` | Renders |
| --- | --- |
| `text` (default) | Single-line input |
| `textarea` | Multi-line text |
| `select` | Dropdown using `options` (`{"value": "Label"}`) |
| `int` | Number input |
| `bool` | Switch (stored as `"1"` / `"0"`) |
| `secret` | Password input; the stored value is encrypted at rest and never echoed back |

```json
"settings": {
    "endpoint": { "label": "Endpoint URL", "type": "text", "help": "Must start with https://" },
    "api_key":  { "label": "API key", "type": "secret" },
    "limit":    { "label": "Items per page", "type": "int", "default": "10" }
}
```

Read values with `$this->setting('api_key', '')`. Secrets are decrypted for you
by the plugin manager — never log them.

---

## 6. Views and assets

Plugins can ship their own views. Because the plugin namespace is registered
with the autoloader, place views in `plugins/my-plugin/Views/` and load them
with the namespace:

```php
echo view('Destra\\Plugins\\MyPlugin\\Views\\widget', ['items' => $items]);
```

For CSS/JS/images, keep files under `Assets/` and reference them through the
plugin asset route:

```php
<link rel="stylesheet" href="<?= plugin_asset('my-plugin', 'assets/widget.css') ?>">
```

`plugin_asset()` resolves to `/plugin-assets/{slug}/…`, which serves whitelisted
extensions only (PHP is never executed).

Rules:

- Escape every value in views (`esc()` / `esc($v, 'attr')`).
- Do not write to `app/` or core tables. Use your own tables via migrations or
  the plugin settings store.
- Wrap database work in `try/catch (Throwable)` so a plugin failure cannot take
  the site down.

---

## 7. Install, activate and distribute

### From the admin

**Appearance → Plugins → Upload plugin** accepts a ZIP. Requirements:

- `plugin.json` at the archive root (or one folder deep),
- ≤ 50 MB and ≤ 2,000 files,
- no absolute paths, `..` segments or symlinks.

After upload the plugin appears **inactive**. Review its metadata, then
activate. Settings can be edited from the plugin card.

### Manually

Extract the folder into `plugins/`. The slug is the folder name. Then activate
it in the admin.

### Distributing on CodeCanyon

```
my-plugin.zip
└── my-plugin/
    ├── plugin.json
    ├── src/…
    ├── Views/…
    └── Assets/…
```

Include a `README.md` with installation instructions, a changelog and support
contact details. Declare a version in `plugin.json`; updates are installed by
deactivating, replacing the folder (or removing it in the admin) and re-uploading.
Remember that removing a plugin deletes its settings but not its own database
tables — clean those up in `deactivate()` if you created any.
