<?php

namespace App\Libraries;

use Throwable;

/**
 * The single cache facade used by the CMS. Every write that changes public
 * output calls flush(), which keeps the "auto clear on save" guarantee.
 */
class CacheManager
{
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $key = $this->key($key);
        $hit = cache($key);
        if ($hit !== null) {
            return $hit;
        }

        $value = $callback();
        cache()->save($key, $value, $ttl);

        return $value;
    }

    public function forget(string $key): void
    {
        cache()->delete($this->key($key));
    }

    public function flush(): void
    {
        try {
            cache()->clean();
        } catch (Throwable $e) {
            log_message('error', 'Cache flush failed: ' . $e->getMessage());
        }

        try {
            service('themeManager')->clearCache();
        } catch (Throwable) {
            // Theme manager is optional during installation.
        }
    }

    public function forgetEverything(): void
    {
        $this->flush();
    }

    public function stat(string $key, int $ttl = 300): int
    {
        return (int) $this->remember($key, $ttl, static fn () => 0);
    }

    private function key(string $key): string
    {
        return 'destra_' . preg_replace('/[^a-z0-9_.-]/i', '_', $key);
    }
}
