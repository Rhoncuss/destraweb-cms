<?php

namespace App\Libraries;

use Config\Destra;
use Config\SiteDefaults;
use Throwable;

/**
 * Database backed settings with transparent AES-256-GCM encryption for
 * secrets. Values are loaded once per request (single query) and defaults are
 * available even before the installer has run.
 */
class Settings
{
    /** @var array<string, mixed> */
    private array $items = [];

    private bool $loaded = false;

    /** @var array<string, array{0: mixed, 1: string, 2: string}> */
    private array $defaults;

    public function __construct()
    {
        $this->defaults = SiteDefaults::settings();
        foreach ($this->defaults as $key => [$value]) {
            $this->items[$key] = $value;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        return array_key_exists($key, $this->items) && $this->items[$key] !== null
            ? $this->items[$key]
            : ($default ?? ($this->defaults[$key][0] ?? null));
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function list(string $key, string $separator = ','): array
    {
        $value = (string) $this->get($key, '');
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode($separator, $value))));
    }

    public function group(string $group): array
    {
        $this->load();
        $result = [];

        foreach ($this->defaults as $key => [$default, $defaultGroup]) {
            if ($defaultGroup === $group) {
                $result[$key] = $this->items[$key] ?? $default;
            }
        }

        return $result;
    }

    public function all(): array
    {
        $this->load();

        return $this->items;
    }

    public function set(string $key, mixed $value, ?string $group = null, ?string $type = null): bool
    {
        if (! is_file(config(Destra::class)->installLock)) {
            $this->items[$key] = $value;

            return true;
        }

        $this->load();

        [$default, $defaultGroup, $defaultType] = $this->defaults[$key] ?? [null, 'general', 'text'];
        $group ??= $defaultGroup;
        $type  ??= $defaultType;
        $stored = is_array($value) ? implode(',', $value) : (string) $value;

        if (in_array($key, SiteDefaults::encryptedKeys(), true) && $stored !== '') {
            $stored = service('crypto')->encrypt($stored, 'setting:' . $key);
        }

        $db = db_connect();
        try {
            $existing = $db->table('settings')->where('setting_key', $key)->countAllResults() > 0;
            $data     = [
                'setting_group'   => $group,
                'setting_key'     => $key,
                'setting_value'   => $stored,
                'setting_type'    => $type,
                'is_encrypted'    => in_array($key, SiteDefaults::encryptedKeys(), true) ? 1 : 0,
                'updated_at'      => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $db->table('settings')->where('setting_key', $key)->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('settings')->insert($data);
            }
        } catch (Throwable $e) {
            log_message('error', 'Unable to persist setting {key}: {msg}', ['key' => $key, 'msg' => $e->getMessage()]);

            return false;
        }

        $this->items[$key] = $value;

        if (service('settings')->get('cache.auto_clear') !== '0') {
            service('cache_manager')->forgetEverything();
        }

        return true;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setMany(array $values): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->set($key, $value) && $ok;
        }

        return $ok;
    }

    public function defaultMeta(string $key): ?array
    {
        return $this->defaults[$key] ?? null;
    }

    public function forgetCache(): void
    {
        $this->loaded = false;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (! is_file(config(Destra::class)->installLock)) {
            return;
        }

        try {
            $rows = db_connect()->table('settings')->get()->getResultArray();
        } catch (Throwable $e) {
            log_message('error', 'Settings unavailable: ' . $e->getMessage());

            return;
        }

        foreach ($rows as $row) {
            $key   = (string) $row['setting_key'];
            $value = $row['setting_value'];
            if ((int) $row['is_encrypted'] === 1 && is_string($value) && $value !== '') {
                $decoded = service('crypto')->decrypt($value, 'setting:' . $key);
                if ($decoded === null) {
                    $value = '';
                } else {
                    $value = $decoded;
                }
            }
            $this->items[$key] = $value;
        }
    }
}
