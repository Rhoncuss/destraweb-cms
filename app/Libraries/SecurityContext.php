<?php

namespace App\Libraries;

/**
 * Per-request security context (CSP nonce etc.).
 */
class SecurityContext
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }

        return self::$nonce;
    }

    public static function reset(): void
    {
        self::$nonce = null;
    }
}
