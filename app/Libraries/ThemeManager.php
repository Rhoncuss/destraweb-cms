<?php

namespace App\Libraries;

use Config\Destra;
use Throwable;

class ThemeManager
{
    private ?string $active = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $themes = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function available(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $this->themes = [];
        $base = config(Destra::class)->themesPath;

        foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $manifest = $dir . '/theme.json';
            if (! is_file($manifest)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($manifest), true);
            if (! is_array($data) || empty($data['name'])) {
                continue;
            }

            $slug = basename($dir);
            if (! preg_match('/^[a-z0-9][a-z0-9-]{1,40}$/', $slug)) {
                continue;
            }

            $data['slug']   = $slug;
            $data['path']   = $dir;
            $data['screenshot'] = isset($data['screenshot']) && is_file($dir . '/' . $data['screenshot'])
                ? 'themes/' . $slug . '/' . $data['screenshot']
                : null;
            $this->themes[$slug] = $data;
        }

        ksort($this->themes);

        return $this->themes;
    }

    public function activeSlug(): string
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $slug = (string) service('settings')->get('appearance.theme', 'modern');

        $preview = session('theme_preview');
        if (is_string($preview) && $preview !== '' && service('auth')->check() && service('auth')->can('appearance.manage')) {
            $slug = $preview;
        }

        $themes = $this->available();
        if (! isset($themes[$slug])) {
            $slug = isset($themes['modern']) ? 'modern' : (string) array_key_first($themes);
        }

        return $this->active = ($slug !== '' ? $slug : 'modern');
    }

    public function active(): array
    {
        $themes = $this->available();
        $slug   = $this->activeSlug();

        return $themes[$slug] ?? [
            'name' => 'DESTRA', 'slug' => $slug, 'version' => '1.0.0',
            'description' => '', 'author' => 'DESTRA', 'path' => config(Destra::class)->themesPath . '/' . $slug,
            'screenshot' => null,
        ];
    }

    public function path(?string $slug = null): string
    {
        $themes = $this->available();
        $slug ??= $this->activeSlug();
        $path = $themes[$slug]['path'] ?? config(Destra::class)->themesPath . '/' . preg_replace('/[^a-z0-9-]/', '', $slug);

        return rtrim((string) $path, '/\\');
    }

    public function viewExists(string $view, ?string $slug = null): bool
    {
        return is_file($this->path($slug) . '/Views/' . ltrim($view, '/') . '.php');
    }

    public function isValid(string $slug): bool
    {
        return isset($this->available()[$slug]);
    }

    /**
     * Registers the active theme's view namespace with the autoloader.
     */
    public function boot(): void
    {
        try {
            $path = $this->path();
            if (! is_dir($path)) {
                return;
            }

            service('autoloader')->addNamespace('Theme', $path);
        } catch (Throwable $e) {
            log_message('error', 'Theme boot failed: ' . $e->getMessage());
        }
    }

    public function screenshotUrl(?string $slug = null): string
    {
        $themes = $this->available();
        $slug ??= $this->activeSlug();
        $shot = $themes[$slug]['screenshot'] ?? null;

        return $shot
            ? base_url('theme-assets/' . rawurlencode($slug) . '/' . rawurlencode(basename((string) $shot)))
            : base_url('assets/admin/img/theme-placeholder.svg');
    }

    public function asset(string $path, ?string $slug = null): string
    {
        $slug = $slug ?? $this->activeSlug();
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        return base_url('theme-assets/' . $slug . '/' . ltrim($path, '/'));
    }

    public function clearCache(): void
    {
        $this->themes = null;
        $this->active = null;
    }
}
