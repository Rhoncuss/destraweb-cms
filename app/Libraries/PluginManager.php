<?php

namespace App\Libraries;

use Config\Destra;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class PluginManager
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $available = null;

    /** @var list<object> */
    private array $instances = [];

    private bool $booted = false;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function available(): array
    {
        if ($this->available !== null) {
            return $this->available;
        }

        $this->available = [];
        foreach (glob(config(Destra::class)->pluginsPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $manifest = $dir . '/plugin.json';
            if (! is_file($manifest)) {
                continue;
            }

            $data = json_decode((string) file_get_contents($manifest), true);
            if (! is_array($data) || empty($data['name']) || empty($data['main'])) {
                continue;
            }

            $slug = basename($dir);
            if (! preg_match('/^[a-z0-9][a-z0-9-]{1,60}$/', $slug)) {
                continue;
            }

            $data['slug'] = $slug;
            $data['path'] = $dir;
            $data['main_path'] = $dir . '/' . ltrim((string) $data['main'], '/');
            $this->available[$slug] = $data;
        }

        ksort($this->available);

        return $this->available;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function records(): array
    {
        if (! is_file(config(Destra::class)->installLock)) {
            return [];
        }

        try {
            $rows = db_connect()->table('plugins')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $row['settings'] = json_decode((string) ($row['settings'] ?? ''), true) ?: [];
            $result[$row['slug']] = $row;
        }

        return $result;
    }

    public function isActive(string $slug): bool
    {
        $records = $this->records();

        return ! empty($records[$slug]['is_active']);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        try {
            foreach ($this->records() as $slug => $row) {
                if ((int) ($row['is_active'] ?? 0) !== 1) {
                    continue;
                }

                $plugin = $this->instantiate($slug, $row['settings'] ?? []);
                if ($plugin !== null) {
                    $this->instances[] = $plugin;
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Plugin boot failed: ' . $e->getMessage());
        }
    }

    /**
     * Load a single plugin instance. Returns null when its files are missing
     * or the entry class cannot be resolved.
     */
    public function instantiate(string $slug, array $settings = []): ?object
    {
        $plugins = $this->available();
        if (! isset($plugins[$slug])) {
            return null;
        }

        $meta = $plugins[$slug];
        $main = $meta['main_path'];
        if (! is_file($main)) {
            return null;
        }

        $namespace = trim((string) ($meta['namespace'] ?? ''), '\\');
        if ($namespace !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]+$/', $namespace)) {
            $src = rtrim($meta['path'], '/\\') . '/src';
            if (is_dir($src)) {
                service('autoloader')->addNamespace($namespace, $src);
            }
            service('autoloader')->addNamespace($namespace, rtrim($meta['path'], '/\\'));
        }

        require_once $main;

        $class = (string) ($meta['class'] ?? '');
        if ($class === '' || ! class_exists($class)) {
            log_message('error', 'Plugin {slug}: class not found ({class}).', ['slug' => $slug, 'class' => $class]);

            return null;
        }

        $instance = new $class();
        if (! is_object($instance)) {
            return null;
        }

        $settings = $this->decryptSecrets($slug, $meta, $settings);

        if (method_exists($instance, 'setContext')) {
            $instance->setContext($meta, $settings);
        }

        if (method_exists($instance, 'register')) {
            $instance->register();
        }

        return $instance;
    }

    /**
     * @return list<object>
     */
    public function instances(): array
    {
        return $this->instances;
    }

    public function installFromZip(string $zipPath): array
    {
        if (! class_exists(ZipArchive::class)) {
            return ['ok' => false, 'message' => 'The PHP zip extension is required.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'message' => 'The uploaded file is not a valid ZIP archive.'];
        }

        if ($zip->numFiles > 2000) {
            $zip->close();

            return ['ok' => false, 'message' => 'The archive contains too many files.'];
        }

        $temp = WRITEPATH . 'cache/plugin-' . bin2hex(random_bytes(8));
        @mkdir($temp, 0755, true);

        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }

            $name = str_replace('\\', '/', (string) $stat['name']);
            $total += (int) $stat['size'];

            if ($total > 52_428_800 || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                $zip->close();
                $this->deleteDir($temp);

                return ['ok' => false, 'message' => 'The archive contains an unsafe path or exceeds 50 MB.'];
            }

            $mode = (int) ($stat['external_attributes'] ?? 0);
            if (($mode & 0xF000) === 0xA000) {
                $zip->close();
                $this->deleteDir($temp);

                return ['ok' => false, 'message' => 'Symbolic links are not allowed in plugin archives.'];
            }

            $target = $temp . '/' . $name;
            if (str_ends_with($name, '/')) {
                @mkdir($target, 0755, true);
                continue;
            }

            @mkdir(dirname($target), 0755, true);
            if (! $zip->extractTo($temp, $name)) {
                $zip->close();
                $this->deleteDir($temp);

                return ['ok' => false, 'message' => 'Unable to extract the archive.'];
            }
        }
        $zip->close();

        $root = $this->findPluginRoot($temp);
        if ($root === null) {
            $this->deleteDir($temp);

            return ['ok' => false, 'message' => 'plugin.json was not found in the archive root.'];
        }

        $manifest = json_decode((string) file_get_contents($root . '/plugin.json'), true);
        if (! is_array($manifest) || empty($manifest['name']) || empty($manifest['main'])) {
            $this->deleteDir($temp);

            return ['ok' => false, 'message' => 'plugin.json is invalid (name and main are required).'];
        }

        $slug = strtolower(trim((string) ($manifest['slug'] ?? basename($root))));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug) ?? '';
        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,60}$/', $slug)) {
            $this->deleteDir($temp);

            return ['ok' => false, 'message' => 'Invalid plugin slug.'];
        }

        $dest = rtrim(config(Destra::class)->pluginsPath, '/\\') . '/' . $slug;
        if (is_dir($dest)) {
            $this->deleteDir($temp);

            return ['ok' => false, 'message' => 'A plugin with this slug is already installed.'];
        }

        if (! @rename($root, $dest)) {
            $this->copyDir($root, $dest);
            $this->deleteDir($temp);
        } else {
            $this->deleteDir($temp);
        }

        try {
            db_connect()->table('plugins')->insert([
                'slug'        => $slug,
                'name'        => mb_substr((string) $manifest['name'], 0, 120),
                'version'     => mb_substr((string) ($manifest['version'] ?? '1.0.0'), 0, 30),
                'description' => mb_substr((string) ($manifest['description'] ?? ''), 0, 500),
                'author'      => mb_substr((string) ($manifest['author'] ?? ''), 0, 120),
                'is_active'   => 0,
                'settings'    => '{}',
                'installed_at'=> date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Plugin files were copied but the database record failed: ' . $e->getMessage()];
        }

        $this->available = null;

        return ['ok' => true, 'message' => 'Plugin uploaded. Review it, then activate it.', 'slug' => $slug];
    }

    public function remove(string $slug): array
    {
        if (! preg_match('/^[a-z0-9-]+$/', $slug)) {
            return ['ok' => false, 'message' => 'Invalid plugin.'];
        }

        $base = realpath(config(Destra::class)->pluginsPath);
        $path = realpath(config(Destra::class)->pluginsPath . '/' . $slug);
        if ($path === false || $base === false || ! str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $base) . '/')) {
            return ['ok' => false, 'message' => 'Plugin folder not found.'];
        }

        $this->deleteDir($path);
        try {
            db_connect()->table('plugins')->where('slug', $slug)->delete();
        } catch (Throwable) {
        }
        $this->available = null;

        return ['ok' => true, 'message' => 'Plugin removed.'];
    }

    public function setActive(string $slug, bool $active): bool
    {
        try {
            $update = ['is_active' => $active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')];
            db_connect()->table('plugins')->where('slug', $slug)->update($update);
            service('cache_manager')->flush();
        } catch (Throwable $e) {
            log_message('error', 'Plugin state update failed: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    public function saveSettings(string $slug, array $settings, array $schema = []): void
    {
        $existing = (array) ($this->records()[$slug]['settings'] ?? []);
        $crypto   = service('crypto');
        $clean    = [];

        foreach ($settings as $key => $value) {
            if (! is_string($key) || ! preg_match('/^[a-z0-9_.-]+$/i', $key)) {
                continue;
            }

            if ($this->fieldType($schema, $key) === 'secret') {
                $value = is_string($value) ? trim($value) : '';
                $clean[$key] = $value === ''
                    ? (string) ($existing[$key] ?? '')
                    : $crypto->encrypt($value, 'plugin:' . $slug . ':' . $key);

                continue;
            }

            $clean[$key] = is_scalar($value) ? mb_substr((string) $value, 0, 5000) : '';
        }

        db_connect()->table('plugins')->where('slug', $slug)->update([
            'settings'   => json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        service('cache_manager')->flush();
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function decryptSecrets(string $slug, array $meta, array $settings): array
    {
        $schema = $meta['settings'] ?? [];
        if (! is_array($schema) || $schema === []) {
            return $settings;
        }

        $crypto = service('crypto');
        foreach ($settings as $key => $value) {
            if ($this->fieldType($schema, (string) $key) !== 'secret' || ! is_string($value) || ! str_starts_with($value, 'dst1:')) {
                continue;
            }

            $plain = $crypto->decrypt($value, 'plugin:' . $slug . ':' . $key);
            if ($plain !== null) {
                $settings[$key] = $plain;
            }
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function fieldType(array $schema, string $key): string
    {
        if (isset($schema[$key]) && is_array($schema[$key])) {
            return (string) ($schema[$key]['type'] ?? 'text');
        }

        foreach ($schema as $field) {
            if (is_array($field) && (string) ($field['key'] ?? '') === $key) {
                return (string) ($field['type'] ?? 'text');
            }
        }

        return '';
    }

    private function findPluginRoot(string $dir): ?string
    {
        if (is_file($dir . '/plugin.json')) {
            return $dir;
        }

        $dirs = glob($dir . '/*', GLOB_ONLYDIR) ?: [];
        if (count($dirs) === 1 && is_file($dirs[0] . '/plugin.json')) {
            return $dirs[0];
        }

        return null;
    }

    private function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    private function copyDir(string $from, string $to): void
    {
        @mkdir($to, 0755, true);
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($items as $item) {
            $target = $to . DIRECTORY_SEPARATOR . $items->getSubPathname();
            if ($item->isDir()) {
                @mkdir($target, 0755, true);
            } else {
                @copy($item->getPathname(), $target);
            }
        }
    }
}
