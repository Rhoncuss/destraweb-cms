<?php

namespace App\Libraries;

use Config\Destra;
use RuntimeException;

/**
 * Authenticated symmetric encryption (AES-256-GCM) and token helpers.
 *
 * Envelope format: "dst1:" . base64(nonce[12] . tag[16] . ciphertext)
 * The 256-bit key is derived with HKDF-SHA256 from the installer generated
 * key, so the raw key is never used for more than one purpose.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'dst1:';
    private const NONCE_LEN = 12;
    private const TAG_LEN = 16;

    private string $key;

    public function __construct(?string $encodedKey = null)
    {
        $encodedKey ??= (string) config(Destra::class)->encryptionKey;
        $raw = $encodedKey !== '' ? base64_decode($encodedKey, true) : false;

        if ($raw === false || strlen($raw) < 32) {
            // Legacy/edge deployments: derive from CI's encryption key if present.
            $fallback = (string) env('encryption.key', '');
            $fallback = str_replace(['hex2bin:', 'base64:'], '', $fallback);
            $raw      = hash_hkdf('sha256', $fallback !== '' ? $fallback : 'destra-unconfigured', 32, 'destra-root');

            if (ENVIRONMENT === 'production') {
                log_message('warning', 'DESTRA encryption key is missing or invalid. Run the installer or set destra.encryptionKey.');
            }
        }

        $this->key = hash_hkdf('sha256', substr($raw, 0, 32), 32, 'destra-aes-256-gcm');
    }

    public function encrypt(string $plaintext, string $aad = ''): string
    {
        $nonce = random_bytes(self::NONCE_LEN);
        $tag   = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, self::TAG_LEN);

        if ($cipher === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return self::PREFIX . base64_encode($nonce . $tag . $cipher);
    }

    public function decrypt(string $payload, string $aad = ''): ?string
    {
        if (! str_starts_with($payload, self::PREFIX)) {
            return null;
        }

        $binary = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        if ($binary === false || strlen($binary) <= self::NONCE_LEN + self::TAG_LEN) {
            return null;
        }

        $nonce  = substr($binary, 0, self::NONCE_LEN);
        $tag    = substr($binary, self::NONCE_LEN, self::TAG_LEN);
        $cipher = substr($binary, self::NONCE_LEN + self::TAG_LEN);
        $plain  = openssl_decrypt($cipher, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);

        return $plain === false ? null : $plain;
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    public function randomToken(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, $this->key);
    }

    public function hashIp(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, $this->key . '|ip');
    }

    public function sign(string $data): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $data, $this->key . '|sign', true)), '+/', '-_'), '=');
    }

    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), $signature);
    }

    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }
}
