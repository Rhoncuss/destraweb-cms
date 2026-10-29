<?php

namespace App\Libraries;

class ApiContext
{
    private static ?array $token = null;

    private static ?array $user = null;

    public static function set(?array $token, ?array $user): void
    {
        self::$token = $token;
        self::$user  = $user;
    }

    public static function token(): ?array
    {
        return self::$token;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function userId(): int
    {
        return (int) (self::$user['id'] ?? 0);
    }

    public static function can(string $ability): bool
    {
        $abilities = self::$token['abilities_array'] ?? ['*'];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public static function reset(): void
    {
        self::$token = null;
        self::$user  = null;
    }
}
