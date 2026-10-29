<?php

namespace App\Libraries;

/**
 * Lightweight WordPress-style action/filter hooks used by plugins.
 */
class Hook
{
    /** @var array<string, array<int, list<callable>>> */
    private static array $actions = [];

    /** @var array<string, array<int, list<callable>>> */
    private static array $filters = [];

    public static function add(string $name, callable $callback, int $priority = 10): void
    {
        self::$actions[$name][$priority][] = $callback;
    }

    public static function filter(string $name, callable $callback, int $priority = 10): void
    {
        self::$filters[$name][$priority][] = $callback;
    }

    public static function do(string $name, mixed ...$args): void
    {
        foreach (self::sorted(self::$actions, $name) as $callback) {
            $callback(...$args);
        }
    }

    public static function apply(string $name, mixed $value, mixed ...$args): mixed
    {
        foreach (self::sorted(self::$filters, $name) as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    public static function has(string $name): bool
    {
        return ! empty(self::$actions[$name]) || ! empty(self::$filters[$name]);
    }

    /**
     * @param array<string, array<int, list<callable>>> $registry
     *
     * @return list<callable>
     */
    private static function sorted(array $registry, string $name): array
    {
        if (empty($registry[$name])) {
            return [];
        }

        $bucket = $registry[$name];
        ksort($bucket);
        $callbacks = [];
        foreach ($bucket as $items) {
            foreach ($items as $item) {
                $callbacks[] = $item;
            }
        }

        return $callbacks;
    }
}
