<?php

use App\Libraries\SecurityContext;
use Config\Destra;

if (! function_exists('is_installed')) {
    function is_installed(): bool
    {
        return is_file(config(Destra::class)->installLock);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return service('settings')->get($key, $default);
    }
}

if (! function_exists('setting_bool')) {
    function setting_bool(string $key, bool $default = false): bool
    {
        return service('settings')->bool($key, $default);
    }
}

if (! function_exists('setting_int')) {
    function setting_int(string $key, int $default = 0): int
    {
        return (int) service('settings')->get($key, $default);
    }
}

if (! function_exists('ui')) {
    function ui(string $key, string $fallback = ''): string
    {
        $fallback = $fallback !== '' ? $fallback : (string) lang($key);

        return service('localization')->translate($key, $fallback);
    }
}

if (! function_exists('languages')) {
    function languages(bool $activeOnly = true): array
    {
        return service('localization')->languages($activeOnly);
    }
}

if (! function_exists('current_language')) {
    function current_language(): array
    {
        return service('localization')->currentLanguage();
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return service('localization')->current();
    }
}

if (! function_exists('default_locale')) {
    function default_locale(): string
    {
        return service('localization')->defaultCode();
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(?string $code = null): bool
    {
        return service('localization')->isRtl($code);
    }
}

if (! function_exists('dir_attr')) {
    function dir_attr(?string $code = null): string
    {
        return is_rtl($code) ? 'rtl' : 'ltr';
    }
}

if (! function_exists('lang_prefix')) {
    function lang_prefix(?string $code = null): string
    {
        return service('localization')->prefix($code);
    }
}

if (! function_exists('loc_url')) {
    function loc_url(string $path = '', ?string $code = null): string
    {
        $prefix = lang_prefix($code);
        $path   = trim($path, '/');

        return base_url(trim($prefix . ($path !== '' ? '/' . $path : ''), '/'));
    }
}

if (! function_exists('content_url')) {
    function content_url(array $content, ?array $translation = null, ?string $lang = null): string
    {
        $id = (int) ($content['id'] ?? 0);
        if ($id <= 0) {
            return base_url('/');
        }

        if (! setting_bool('seo.friendly_urls', true)) {
            return site_url('p/' . $id);
        }

        $lang ??= current_locale();
        $slug = (string) ($translation['slug'] ?? 'item-' . $id);
        $type = (string) ($content['type'] ?? 'post');
        $sectionSlug = (string) ($content['section_slug'] ?? '');

        if ($type === 'page') {
            return loc_url($slug, $lang);
        }

        if ($type === 'doc' || $sectionSlug === 'docs') {
            return loc_url('docs/' . $slug, $lang);
        }

        if ($sectionSlug !== '') {
            return loc_url($sectionSlug . '/' . $slug, $lang);
        }

        return loc_url(($type !== '' ? $type : 'post') . '/' . $slug, $lang);
    }
}

if (! function_exists('section_url')) {
    function section_url(array $section, ?string $lang = null): string
    {
        if (! setting_bool('seo.friendly_urls', true)) {
            return site_url('s/' . (int) ($section['id'] ?? 0));
        }

        $slug = (string) ($section['slug'] ?? '');

        return $slug !== '' ? loc_url($slug, $lang) : base_url('/');
    }
}

if (! function_exists('category_url')) {
    function category_url(array $category, string $slug, ?string $lang = null): string
    {
        if (! setting_bool('seo.friendly_urls', true)) {
            return site_url('category/' . (int) ($category['id'] ?? 0));
        }

        return loc_url('category/' . rawurlencode($slug), $lang);
    }
}

if (! function_exists('tag_url')) {
    function tag_url(string $slug, ?string $lang = null): string
    {
        return loc_url('tag/' . rawurlencode($slug), $lang);
    }
}

if (! function_exists('author_url')) {
    function author_url(array $user, ?string $lang = null): string
    {
        return loc_url('author/' . rawurlencode((string) ($user['username'] ?? '')), $lang);
    }
}

if (! function_exists('search_url')) {
    function search_url(string $q = '', ?string $lang = null): string
    {
        $url = loc_url('search', $lang);

        return $q !== '' ? $url . '?q=' . rawurlencode($q) : $url;
    }
}

if (! function_exists('switch_language_url')) {
    function switch_language_url(string $code): string
    {
        $request = service('request');
        $segments = array_values(array_filter(explode('/', trim($request->getPath(), '/')), static fn ($s) => $s !== ''));
        $codes    = service('localization')->codes();

        if (isset($segments[0]) && in_array($segments[0], $codes, true)) {
            array_shift($segments);
        }

        if ($code !== default_locale()) {
            array_unshift($segments, $code);
        }

        $path  = implode('/', $segments);
        $query = (string) $request->getUri()->getQuery();

        return base_url($path) . ($query !== '' ? '?' . $query : '');
    }
}

if (! function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return service('auth')->user();
    }
}

if (! function_exists('auth_check')) {
    function auth_check(): bool
    {
        return service('auth')->check();
    }
}

if (! function_exists('user_can')) {
    function user_can(string $permission): bool
    {
        return service('auth')->can($permission);
    }
}

if (! function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        $prefix = trim(config(Destra::class)->adminPrefix, '/');

        return site_url(($prefix !== '' ? $prefix : 'admin') . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (! function_exists('admin_asset')) {
    function admin_asset(string $path): string
    {
        $full = FCPATH . 'assets/admin/' . ltrim($path, '/');
        $ver  = is_file($full) ? (string) filemtime($full) : config(Destra::class)->version;

        return base_url('assets/admin/' . ltrim($path, '/')) . '?v=' . $ver;
    }
}

if (! function_exists('front_asset')) {
    function front_asset(string $path): string
    {
        $full = FCPATH . 'assets/frontend/' . ltrim($path, '/');
        $ver  = is_file($full) ? (string) filemtime($full) : config(Destra::class)->version;

        return base_url('assets/frontend/' . ltrim($path, '/')) . '?v=' . $ver;
    }
}

if (! function_exists('vendor_asset')) {
    function vendor_asset(string $path): string
    {
        return base_url('assets/vendor/' . ltrim($path, '/'));
    }
}

if (! function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        return service('themeManager')->asset($path);
    }
}

if (! function_exists('plugin_asset')) {
    function plugin_asset(string $slug, string $path): string
    {
        return base_url('plugin-assets/' . rawurlencode($slug) . '/' . ltrim($path, '/'));
    }
}

if (! function_exists('media_url')) {
    function media_url(mixed $idOrMedia, string $variant = ''): string
    {
        return service('media')->url($idOrMedia, $variant);
    }
}

if (! function_exists('media_service_url')) {
    function media_service_url(int $id, string $filename = '', string $variant = ''): string
    {
        $url = base_url('media/' . $id . '/' . rawurlencode($filename !== '' ? $filename : 'file'));

        return $variant !== '' ? $url . '?v=' . rawurlencode($variant) : $url;
    }
}

if (! function_exists('theme_view')) {
    function theme_view(string $view, array $data = [], array $options = []): string
    {
        $view = ltrim($view, '/');

        try {
            if (service('themeManager')->viewExists($view)) {
                return view('Theme\\Views\\' . $view, $data, $options);
            }
        } catch (Throwable $e) {
            log_message('error', 'Theme view failed: ' . $e->getMessage());
        }

        if (is_file(APPPATH . 'Views/frontend/fallback/' . $view . '.php')) {
            return view('frontend/fallback/' . $view, $data, $options);
        }

        return view('frontend/fallback/generic', $data, $options);
    }
}

if (! function_exists('render_content')) {
    function render_content(?string $html): string
    {
        $html = service('sanitizer')->html((string) $html);

        return service('shortcodes')->render($html);
    }
}

if (! function_exists('excerpt_text')) {
    function excerpt_text(?string $html, ?int $words = null): string
    {
        return service('sanitizer')->excerpt((string) $html, $words ?? setting_int('reading.excerpt_length', 26));
    }
}

if (! function_exists('reading_time')) {
    function reading_time(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }
}

if (! function_exists('human_date')) {
    function human_date(?string $date, ?string $format = null): string
    {
        if (! $date) {
            return '';
        }

        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }

        return date($format ?? (string) setting('general.date_format', 'd M Y'), $ts);
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return SecurityContext::nonce();
    }
}

// CodeIgniter already declares a global csrf_meta() that exposes the header
// name; the public theme needs the field name as well so fetch() payloads and
// classic form posts share one token source.
if (! function_exists('destra_csrf_meta')) {
    function destra_csrf_meta(): string
    {
        $security = config('Security');

        return '<meta name="' . esc($security->tokenName) . '" content="' . esc(csrf_hash()) . '">'
            . '<meta name="csrf-header" content="' . esc($security->headerName) . '">';
    }
}

if (! function_exists('icon_html')) {
    function icon_html(?string $icon, string $fallback = 'fa-solid fa-circle'): string
    {
        $icon  = trim((string) $icon);
        $style = '(?:fa[srbltd]?|fa-(?:solid|regular|brands|light|thin|duotone))';
        $valid = preg_match('/^' . $style . ' fa-[a-z0-9-]+(?: fa-[a-z0-9-]+)*$/', $icon) === 1
            || preg_match('/^bi bi-[a-z0-9-]+(?: bi-[a-z0-9-]+)*$/', $icon) === 1;

        if (! $valid) {
            $icon = $fallback;
        }

        return '<i class="' . esc($icon) . '" aria-hidden="true"></i>';
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $last  = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}

if (! function_exists('avatar_url')) {
    function avatar_url(?array $user): string
    {
        if (! $user) {
            return '';
        }

        if (! empty($user['avatar'])) {
            if (ctype_digit((string) $user['avatar'])) {
                return media_url((int) $user['avatar'], 'thumb');
            }

            $url = service('sanitizer')->url((string) $user['avatar']);

            return $url !== '' ? $url : '';
        }

        return '';
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = $bytes > 0 ? (int) floor(log($bytes) / log(1024)) : 0;
        $pow   = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('safe_redirect_url')) {
    function safe_redirect_url(?string $url, string $fallback = ''): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $fallback !== '' ? $fallback : base_url('/');
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return base_url(ltrim($url, '/'));
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host && strcasecmp((string) $host, (string) parse_url(base_url(), PHP_URL_HOST)) === 0) {
            return $url;
        }

        $clean = service('sanitizer')->url($url);
        if ($clean === '') {
            return $fallback !== '' ? $fallback : base_url('/');
        }

        return $clean;
    }
}

if (! function_exists('menu_tree')) {
    function menu_tree(string $location = 'primary', ?string $lang = null): array
    {
        $lang = $lang ?? current_locale();
        $key  = 'menu_' . preg_replace('/[^a-z0-9_-]/i', '', $location) . '_' . $lang;

        return service('cache_manager')->remember($key, 3600, static function () use ($location, $lang): array {
            try {
                $db = db_connect();
                $menu = $db->table('menus')->where('location', $location)->where('is_active', 1)->orderBy('id', 'ASC')->get()->getRowArray();
                if (! $menu) {
                    return [];
                }

                $items = $db->table('menu_items')
                    ->where('menu_id', (int) $menu['id'])
                    ->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->get()
                    ->getResultArray();

                if ($items === []) {
                    return [];
                }

                $ids = array_column($items, 'id');
                $translations = $db->table('menu_item_translations')
                    ->whereIn('item_id', $ids)
                    ->get()
                    ->getResultArray();

                $labels = [];
                foreach ($translations as $translation) {
                    $labels[(int) $translation['item_id']][(string) $translation['language_code']] = (string) $translation['label'];
                }

                $contentLabels = [];
                $categoryLabels = [];
                $tagLabels = [];
                $contentSlugs = [];
                $categorySlugs = [];
                $tagSlugs = [];
                $sectionSlugs = [];

                foreach ($items as $item) {
                    if ($item['target_id'] === null) {
                        continue;
                    }
                    $id = (int) $item['target_id'];
                    if ($item['type'] === 'content' || $item['type'] === 'page') {
                        $rows = $db->table('content_translations')->where('content_id', $id)->get()->getResultArray();
                        foreach ($rows as $row) {
                            $contentLabels[$id][(string) $row['language_code']] = (string) $row['title'];
                            $contentSlugs[$id][(string) $row['language_code']] = (string) $row['slug'];
                        }
                    } elseif ($item['type'] === 'category') {
                        $rows = $db->table('category_translations')->where('category_id', $id)->get()->getResultArray();
                        foreach ($rows as $row) {
                            $categoryLabels[$id][(string) $row['language_code']] = (string) $row['name'];
                            $categorySlugs[$id][(string) $row['language_code']] = (string) $row['slug'];
                        }
                    } elseif ($item['type'] === 'tag') {
                        $rows = $db->table('tag_translations')->where('tag_id', $id)->get()->getResultArray();
                        foreach ($rows as $row) {
                            $tagLabels[$id][(string) $row['language_code']] = (string) $row['name'];
                            $tagSlugs[$id][(string) $row['language_code']] = (string) $row['slug'];
                        }
                    } elseif ($item['type'] === 'section') {
                        $row = $db->table('sections')->where('id', $id)->get()->getRowArray();
                        if ($row) {
                            $sectionSlugs[$id] = (string) $row['slug'];
                        }
                    }
                }

                $default = service('localization')->defaultCode();
                $nodes   = [];
                foreach ($items as $item) {
                    $id    = (int) $item['id'];
                    $label = $labels[$id][$lang] ?? $labels[$id][$default] ?? '';
                    $url   = '#';
                    $tid   = (int) ($item['target_id'] ?? 0);

                    switch ($item['type']) {
                        case 'content':
                        case 'page':
                            $label = $label !== '' ? $label : ($contentLabels[$tid][$lang] ?? $contentLabels[$tid][$default] ?? '');
                            $slug  = $contentSlugs[$tid][$lang] ?? $contentSlugs[$tid][$default] ?? '';
                            if ($slug !== '') {
                                $type = $item['type'] === 'page' ? 'page' : 'post';
                                $url = $type === 'page' ? loc_url($slug, $lang) : content_url(['id' => $tid, 'type' => 'post'], ['slug' => $slug], $lang);
                            }
                            break;
                        case 'category':
                            $label = $label !== '' ? $label : ($categoryLabels[$tid][$lang] ?? $categoryLabels[$tid][$default] ?? '');
                            $slug  = $categorySlugs[$tid][$lang] ?? $categorySlugs[$tid][$default] ?? '';
                            if ($slug !== '') {
                                $url = category_url(['id' => $tid], $slug, $lang);
                            }
                            break;
                        case 'tag':
                            $label = $label !== '' ? $label : ($tagLabels[$tid][$lang] ?? $tagLabels[$tid][$default] ?? '');
                            $slug  = $tagSlugs[$tid][$lang] ?? $tagSlugs[$tid][$default] ?? '';
                            if ($slug !== '') {
                                $url = tag_url($slug, $lang);
                            }
                            break;
                        case 'section':
                            if (! empty($sectionSlugs[$tid])) {
                                $url = section_url(['id' => $tid, 'slug' => $sectionSlugs[$tid]], $lang);
                            }
                            break;
                        default:
                            $url = service('sanitizer')->url((string) ($item['url'] ?? ''), true);
                            $url = $url !== '' ? $url : '#';
                    }

                    $nodes[$id] = [
                        'id'     => $id,
                        'label'  => $label !== '' ? $label : 'Menu item',
                        'url'    => $url,
                        'icon'   => (string) ($item['icon'] ?? ''),
                        'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                        'class'  => (string) ($item['css_class'] ?? ''),
                        'parent' => (int) ($item['parent_id'] ?? 0),
                        'children' => [],
                    ];
                }

                $tree = [];
                foreach ($nodes as $id => &$node) {
                    if ($node['parent'] > 0 && isset($nodes[$node['parent']])) {
                        $nodes[$node['parent']]['children'][] = &$node;
                    } else {
                        $tree[] = &$node;
                    }
                }
                unset($node);

                return $tree;
            } catch (Throwable $e) {
                log_message('error', 'Menu load failed: ' . $e->getMessage());

                return [];
            }
        });
    }
}

if (! function_exists('render_menu')) {
    function render_menu(string $location = 'primary', string $class = 'menu', string $lang = ''): string
    {
        $tree = menu_tree($location, $lang !== '' ? $lang : null);
        if ($tree === []) {
            return '';
        }

        return _render_menu_items($tree, $class);
    }
}

if (! function_exists('_render_menu_items')) {
    function _render_menu_items(array $items, string $class = ''): string
    {
        $html = '<ul class="' . esc($class) . '">';
        foreach ($items as $item) {
            $hasChildren = ! empty($item['children']);
            $html .= '<li class="' . ($hasChildren ? 'has-children ' : '') . esc($item['class']) . '">';
            $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
            $url = $item['url'] === '#' ? '#' : esc($item['url']);
            $html .= '<a href="' . $url . '"' . $target . '>';
            if ($item['icon'] !== '') {
                $html .= icon_html($item['icon']) . ' ';
            }
            $html .= '<span>' . esc($item['label']) . '</span>';
            if ($hasChildren) {
                $html .= ' <i class="fa-solid fa-chevron-down menu-caret" aria-hidden="true"></i>';
            }
            $html .= '</a>';
            if ($hasChildren) {
                $html .= _render_menu_items($item['children'], 'submenu');
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

if (! function_exists('share_links')) {
    function share_links(array $content, array $translation): array
    {
        $url   = content_url($content, $translation);
        $title = (string) ($translation['title'] ?? '');
        $image = media_url((int) ($content['featured_image_id'] ?? ($translation['og_image_id'] ?? 0)));
        $networks = service('settings')->list('social.share_networks');
        $links = [];

        foreach ($networks as $network) {
            switch ($network) {
                case 'facebook':
                    $links[] = ['network' => 'facebook', 'label' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f', 'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url)];
                    break;
                case 'x':
                    $links[] = ['network' => 'x', 'label' => 'X', 'icon' => 'fa-brands fa-x-twitter', 'url' => 'https://twitter.com/intent/tweet?url=' . rawurlencode($url) . '&text=' . rawurlencode($title)];
                    break;
                case 'linkedin':
                    $links[] = ['network' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin-in', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($url)];
                    break;
                case 'pinterest':
                    $links[] = ['network' => 'pinterest', 'label' => 'Pinterest', 'icon' => 'fa-brands fa-pinterest-p', 'url' => 'https://pinterest.com/pin/create/button/?url=' . rawurlencode($url) . '&media=' . rawurlencode($image) . '&description=' . rawurlencode($title)];
                    break;
                case 'whatsapp':
                    $links[] = ['network' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'url' => 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url)];
                    break;
                case 'telegram':
                    $links[] = ['network' => 'telegram', 'label' => 'Telegram', 'icon' => 'fa-brands fa-telegram', 'url' => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title)];
                    break;
                case 'email':
                    $links[] = ['network' => 'email', 'label' => 'Email', 'icon' => 'fa-solid fa-envelope', 'url' => 'mailto:?subject=' . rawurlencode($title) . '&body=' . rawurlencode($url)];
                    break;
            }
        }

        return $links;
    }
}

if (! function_exists('admin_schemes')) {
    function admin_schemes(): array
    {
        return [
            'midnight' => [
                'name' => 'Midnight', 'primary' => '#6366f1', 'secondary' => '#8b5cf6', 'accent' => '#22d3ee',
                'sidebar' => '#0f172a', 'sidebar2' => '#1e1b4b',
            ],
            'emerald' => [
                'name' => 'Emerald', 'primary' => '#10b981', 'secondary' => '#0d9488', 'accent' => '#f59e0b',
                'sidebar' => '#052e2b', 'sidebar2' => '#064e3b',
            ],
            'sunset' => [
                'name' => 'Sunset', 'primary' => '#f97316', 'secondary' => '#ef4444', 'accent' => '#facc15',
                'sidebar' => '#2a0f0a', 'sidebar2' => '#431407',
            ],
            'royal' => [
                'name' => 'Royal', 'primary' => '#2563eb', 'secondary' => '#7c3aed', 'accent' => '#f59e0b',
                'sidebar' => '#0b1b3f', 'sidebar2' => '#172554',
            ],
        ];
    }
}

if (! function_exists('admin_scheme')) {
    function admin_scheme(): string
    {
        $schemes = admin_schemes();
        $user = service('auth')->user();
        $meta = [];
        if ($user && ! empty($user['meta'])) {
            $meta = json_decode((string) $user['meta'], true) ?: [];
        }

        $candidate = (string) ($meta['admin_scheme'] ?? session('admin_scheme') ?? setting('appearance.admin_scheme', 'midnight'));

        return isset($schemes[$candidate]) ? $candidate : 'midnight';
    }
}

if (! function_exists('admin_menu')) {
    function admin_menu(): array
    {
        $groups = [
            [
                'label' => 'Workspace',
                'items' => [
                    ['label' => 'Dashboard', 'icon' => 'fa-solid fa-grid-2', 'url' => admin_url(), 'permission' => 'dashboard.view', 'match' => 'admin'],
                    ['label' => 'Content', 'icon' => 'fa-solid fa-newspaper', 'url' => admin_url('content'), 'permission' => 'content.view', 'match' => 'admin/content*'],
                    ['label' => 'Media library', 'icon' => 'fa-solid fa-photo-film', 'url' => admin_url('media'), 'permission' => 'media.manage', 'match' => 'admin/media*'],
                    ['label' => 'Calendar', 'icon' => 'fa-solid fa-calendar-days', 'url' => admin_url('calendar'), 'permission' => 'content.view', 'match' => 'admin/calendar*'],
                ],
            ],
            [
                'label' => 'Structure',
                'items' => [
                    ['label' => 'Sections', 'icon' => 'fa-solid fa-layer-group', 'url' => admin_url('sections'), 'permission' => 'sections.manage', 'match' => 'admin/sections*'],
                    ['label' => 'Categories', 'icon' => 'fa-solid fa-folder-tree', 'url' => admin_url('categories'), 'permission' => 'categories.manage', 'match' => 'admin/categories*'],
                    ['label' => 'Tags', 'icon' => 'fa-solid fa-tags', 'url' => admin_url('tags'), 'permission' => 'tags.manage', 'match' => 'admin/tags*'],
                    ['label' => 'Menus', 'icon' => 'fa-solid fa-bars-staggered', 'url' => admin_url('menus'), 'permission' => 'menus.manage', 'match' => 'admin/menus*'],
                ],
            ],
            [
                'label' => 'Experience',
                'items' => [
                    ['label' => 'Themes', 'icon' => 'fa-solid fa-palette', 'url' => admin_url('appearance/themes'), 'permission' => 'appearance.manage', 'match' => 'admin/appearance*'],
                    ['label' => 'Banners & Ads', 'icon' => 'fa-solid fa-rectangle-ad', 'url' => admin_url('banners'), 'permission' => 'appearance.manage', 'match' => 'admin/banners*'],
                    ['label' => 'Popups', 'icon' => 'fa-solid fa-window-restore', 'url' => admin_url('popups'), 'permission' => 'appearance.manage', 'match' => 'admin/popups*'],
                    ['label' => 'Forms', 'icon' => 'fa-solid fa-list-check', 'url' => admin_url('forms'), 'permission' => 'forms.manage', 'match' => 'admin/forms*'],
                    ['label' => 'Inbox', 'icon' => 'fa-solid fa-inbox', 'url' => admin_url('inbox'), 'permission' => 'forms.manage', 'match' => 'admin/inbox*'],
                    ['label' => 'Comments', 'icon' => 'fa-solid fa-comments', 'url' => admin_url('comments'), 'permission' => 'comments.moderate', 'match' => 'admin/comments*'],
                    ['label' => 'Orders', 'icon' => 'fa-solid fa-bag-shopping', 'url' => admin_url('orders'), 'permission' => 'forms.manage', 'match' => 'admin/orders*'],
                ],
            ],
            [
                'label' => 'Growth',
                'items' => [
                    ['label' => 'SEO', 'icon' => 'fa-solid fa-magnifying-glass-chart', 'url' => admin_url('seo'), 'permission' => 'seo.manage', 'match' => 'admin/seo*'],
                    ['label' => 'Newsletter', 'icon' => 'fa-solid fa-envelope-open-text', 'url' => admin_url('newsletter'), 'permission' => 'marketing.manage', 'match' => 'admin/newsletter*'],
                    ['label' => 'Reports', 'icon' => 'fa-solid fa-chart-line', 'url' => admin_url('reports'), 'permission' => 'reports.view', 'match' => 'admin/reports*'],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['label' => 'Users', 'icon' => 'fa-solid fa-user-group', 'url' => admin_url('users'), 'permission' => 'users.manage', 'match' => 'admin/users*'],
                    ['label' => 'Roles', 'icon' => 'fa-solid fa-shield-halved', 'url' => admin_url('roles'), 'permission' => 'roles.manage', 'match' => 'admin/roles*'],
                    ['label' => 'Languages', 'icon' => 'fa-solid fa-language', 'url' => admin_url('languages'), 'permission' => 'languages.manage', 'match' => 'admin/languages*'],
                    ['label' => 'Plugins', 'icon' => 'fa-solid fa-plug', 'url' => admin_url('plugins'), 'permission' => 'plugins.manage', 'match' => 'admin/plugins*'],
                    ['label' => 'API tokens', 'icon' => 'fa-solid fa-code', 'url' => admin_url('api-tokens'), 'permission' => 'api.manage', 'match' => 'admin/api-tokens*'],
                    ['label' => 'Social sign-in', 'icon' => 'fa-solid fa-share-nodes', 'url' => admin_url('social-login'), 'permission' => 'settings.manage', 'match' => 'admin/social-login*'],
                    ['label' => 'Settings', 'icon' => 'fa-solid fa-sliders', 'url' => admin_url('settings'), 'permission' => 'settings.manage', 'match' => 'admin/settings*'],
                    ['label' => 'Tools', 'icon' => 'fa-solid fa-screwdriver-wrench', 'url' => admin_url('tools'), 'permission' => 'tools.manage', 'match' => 'admin/tools*'],
                ],
            ],
        ];

        $uri = trim(service('request')->getPath(), '/');

        foreach ($groups as $gIndex => &$group) {
            $group['items'] = array_values(array_filter($group['items'], static fn (array $item): bool => user_can($item['permission'])));
            foreach ($group['items'] as &$item) {
                $item['active'] = _admin_menu_match($uri, $item['match']);
            }
            unset($item);
            if ($group['items'] === []) {
                unset($groups[$gIndex]);
            }
        }
        unset($group);

        $groups = array_values($groups);

        return \App\Libraries\Hook::apply('admin.menu', $groups);
    }
}

if (! function_exists('_admin_menu_match')) {
    function _admin_menu_match(string $uri, string $pattern): bool
    {
        if ($pattern === 'admin') {
            return $uri === 'admin';
        }

        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';

        return (bool) preg_match($regex, $uri);
    }
}

if (! function_exists('notifications_unread')) {
    function notifications_unread(): int
    {
        if (! auth_check()) {
            return 0;
        }

        try {
            return db_connect()->table('notifications')->where('user_id', service('auth')->id())->where('is_read', 0)->countAllResults();
        } catch (Throwable) {
            return 0;
        }
    }
}

if (! function_exists('theme_colors')) {
    function theme_colors(): array
    {
        return [
            'primary'   => service('sanitizer')->color(setting('appearance.primary'), '#4f46e5'),
            'secondary' => service('sanitizer')->color(setting('appearance.secondary'), '#0ea5e9'),
            'accent'    => service('sanitizer')->color(setting('appearance.accent'), '#f59e0b'),
            'radius'    => max(0, min(40, setting_int('appearance.radius', 18))),
        ];
    }
}

if (! function_exists('redirect_login')) {
    function redirect_login(string $intended = ''): \CodeIgniter\HTTP\RedirectResponse
    {
        if ($intended !== '') {
            session()->set('redirect_url', $intended);
        }

        return redirect()->to(site_url('login'));
    }
}

if (! function_exists('str_slug_safe')) {
    function str_slug_safe(string $value): string
    {
        return service('sanitizer')->slug($value);
    }
}

if (! function_exists('front_sections')) {
    /**
     * Public, active sections used for navigation fallbacks.
     *
     * @return list<array<string, mixed>>
     */
    function front_sections(bool $includePages = false): array
    {
        $key = 'front_sections_' . ($includePages ? 'all' : 'content');

        return service('cache_manager')->remember($key, 1800, static function () use ($includePages): array {
            try {
                $builder = db_connect()->table('sections')
                    ->where('is_active', 1)
                    ->where('visibility', 'public')
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC');

                if (! $includePages) {
                    $builder->where('type !=', 'page');
                }

                $rows = $builder->get()->getResultArray();
            } catch (Throwable) {
                return [];
            }

            foreach ($rows as &$row) {
                $row['url'] = section_url($row);
            }
            unset($row);

            return $rows;
        });
    }
}

if (! function_exists('front_pages')) {
    /**
     * Published pages for footer / drawer navigation.
     *
     * @return list<array<string, mixed>>
     */
    function front_pages(int $limit = 8): array
    {
        try {
            $rows = service('contentService')->query([
                'type'     => 'page',
                'per_page' => max(1, min(20, $limit)),
                'locale'   => current_locale(),
            ])['items'];
        } catch (Throwable) {
            return [];
        }

        return $rows;
    }
}
