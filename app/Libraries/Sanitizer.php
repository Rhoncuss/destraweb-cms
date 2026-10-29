<?php

namespace App\Libraries;

use Config\Destra;
use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Central input sanitisation. Every request passes through cleanArray() in the
 * global InputGuard filter; controllers and the API apply the same rules again
 * for JSON payloads before persistence.
 */
class Sanitizer
{
    private ?HTMLPurifier $purifier = null;

    public const RICH_KEYS = [
        'body', 'content', 'html', 'description_html', 'excerpt_html',
    ];

    public const TRUSTED_CODE_KEYS = [
        'custom_css', 'custom_js', 'head_code', 'footer_code',
        'custom_head_code', 'custom_body_code', 'css', 'js',
    ];

    public const PASSWORD_KEYS = [
        'password', 'password_confirm', 'current_password', 'new_password',
        'confirm_password', 'secret', 'api_key', 'client_secret', 'token',
    ];

    public function text(mixed $value, ?int $max = null): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = (string) $value;
        $value = $this->validUtf8($value);
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value);

        return $max !== null ? mb_substr($value, 0, $max) : $value;
    }

    public function textarea(mixed $value, ?int $max = null): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = $this->validUtf8((string) $value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace("\0", '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = strip_tags($value);
        $value = trim($value);

        return $max !== null ? mb_substr($value, 0, $max) : $value;
    }

    public function password(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        // Passwords are intentionally not trimmed or tag-stripped. Only
        // invalid encodings and NUL bytes are removed so arbitrary Unicode
        // passphrases keep working.
        return str_replace("\0", '', $this->validUtf8($value));
    }

    public function html(mixed $value, int $max = 500000): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $value = $this->validUtf8($value);
        $value = str_replace("\0", '', $value);
        $value = mb_substr($value, 0, $max);

        return $this->purifier()->purify($value);
    }

    public function css(mixed $value, int $max = 100000): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = mb_substr(str_replace("\0", '', $this->validUtf8($value)), 0, $max);
        $patterns = [
            '/<\/style/i', '/@import\s+url/i', '/expression\s*\(/i', '/javascript\s*:/i',
            '/behavior\s*:/i', '/-moz-binding\s*:/i', '/<\/script/i',
        ];

        return preg_replace($patterns, '', $value) ?? '';
    }

    public function js(mixed $value, int $max = 200000): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = mb_substr(str_replace("\0", '', $this->validUtf8($value)), 0, $max);

        return preg_replace('/<\/script/i', '<\\/script', $value) ?? '';
    }

    public function email(mixed $value): string
    {
        $value = strtolower(trim((string) $this->text($value, 190)));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    public function slug(mixed $value, int $max = 160): string
    {
        $value = (string) $value;
        $value = $this->validUtf8($value);
        $value = strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{Nd}\s_-]+/u', '', $value) ?? '';
        $value = preg_replace('/[\s_-]+/u', '-', $value) ?? '';
        $value = trim($value, '-');

        if (function_exists('transliterator_transliterate')) {
            $trans = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
            if (is_string($trans) && $trans !== '') {
                $value = preg_replace('/[^a-z0-9-]+/', '-', strtolower($trans)) ?? $value;
                $value = trim($value, '-');
            }
        }

        $value = mb_substr($value, 0, $max);

        return $value !== '' ? $value : 'item-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    public function username(mixed $value): string
    {
        $value = strtolower($this->text($value, 60));
        $value = preg_replace('/[^a-z0-9._-]/', '', $value) ?? '';

        return trim($value, '.-_');
    }

    public function url(mixed $value, bool $allowRelative = true): string
    {
        $value = trim(str_replace(["\0", "\r", "\n", "\t"], '', (string) $value));
        if ($value === '' || strlen($value) > 2000) {
            return '';
        }

        if (str_starts_with($value, '//')) {
            $value = 'https:' . $value;
        }

        if ($allowRelative && (str_starts_with($value, '/') || str_starts_with($value, '#'))) {
            if (str_contains($value, '..') || str_contains($value, '\\') || preg_match('/[\x00-\x1F]/', $value)) {
                return '';
            }

            return $value;
        }

        if (! preg_match('~^[a-z][a-z0-9+.-]*:~i', $value)) {
            return $allowRelative ? '/' . ltrim($value, '/') : '';
        }

        $parts = parse_url($value);
        if ($parts === false || empty($parts['scheme'])) {
            return '';
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return '';
        }

        if (in_array($scheme, ['mailto', 'tel'], true)) {
            return filter_var($value, FILTER_SANITIZE_EMAIL) !== '' ? $value : '';
        }

        if (empty($parts['host'])) {
            return '';
        }

        $host = strtolower($parts['host']);
        if ($this->isSameOriginHost($host)) {
            return $value;
        }

        if ($host === 'localhost' || preg_match('/^127\.|^0\.|^10\.|^192\.168\.|^169\.254\.|^172\.(1[6-9]|2\d|3[01])\./', $host)) {
            return '';
        }

        return $value;
    }

    /**
     * Absolute URLs pointing at the site itself (canonical, hreflang, social
     * previews) must survive the private-host block used for user input.
     */
    private function isSameOriginHost(string $host): bool
    {
        $baseHost = parse_url((string) base_url(), PHP_URL_HOST);

        return is_string($baseHost) && $baseHost !== '' && strtolower($baseHost) === $host;
    }

    public function int(mixed $value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) {
            return max(0, $min);
        }

        return max($min, min($max, (int) $value));
    }

    public function bool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    public function color(mixed $value, string $fallback = '#4f46e5'): string
    {
        $value = trim((string) $value);

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) ? strtolower($value) : $fallback;
    }

    public function icon(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^(?:fa[srbltd]?|fa-(?:solid|regular|light|thin|duotone|brands|sharp(?:-solid|-regular|-light|-thin)?))\s+fa-[a-z0-9-]+$/', $value)) {
            return $value;
        }

        return preg_match('/^bi bi-[a-z0-9-]+$/', $value) ? $value : '';
    }

    public function date(mixed $value, bool $withTime = false): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $format = $withTime ? 'Y-m-d H:i:s' : 'Y-m-d';
        $dt     = \DateTime::createFromFormat($format, $value) ?: \DateTime::createFromFormat('Y-m-d\TH:i', $value);

        return $dt ? $dt->format($format) : null;
    }

    public function filename(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? 'file';
        $name = trim($name, '.-');
        $name = mb_substr($name, 0, 140);

        return $name !== '' ? $name : 'file';
    }

    /**
     * @param array<string|int, mixed> $data
     *
     * @return array<string|int, mixed>
     */
    public function cleanArray(array $data, int $depth = 0): array
    {
        $config  = config(Destra::class);
        $maxDepth = $config->maxInputDepth;

        if ($depth > $maxDepth) {
            return [];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $safeKey = is_string($key) ? preg_replace('/[^\w.\-\[\]]/', '', $key) ?? '' : $key;
            if ($safeKey === '') {
                continue;
            }

            if (is_array($value)) {
                $clean[$safeKey] = $this->cleanArray($value, $depth + 1);
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $lower = is_string($safeKey) ? strtolower($safeKey) : '';

            if ($lower === 'destra_csrf' || $lower === (string) config('Security')->tokenName) {
                $clean[$safeKey] = $value;
                continue;
            }

            if (in_array($lower, self::PASSWORD_KEYS, true)) {
                $clean[$safeKey] = $this->password($value);
                continue;
            }

            if (in_array($lower, self::TRUSTED_CODE_KEYS, true)) {
                $string = is_string($value) ? $value : (string) $value;
                $clean[$safeKey] = str_contains($lower, 'css') ? $this->css($string) : $this->js($string);
                continue;
            }

            if (in_array($lower, self::RICH_KEYS, true)) {
                $clean[$safeKey] = $this->html($value);
                continue;
            }

            if (is_string($value)) {
                $clean[$safeKey] = $this->text($value, $config->maxInputLength);
                continue;
            }

            $clean[$safeKey] = $value;
        }

        return $clean;
    }

    public function stripTags(mixed $value): string
    {
        return $this->text($value);
    }

    public function excerpt(string $html, int $words = 26): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return word_limiter(trim($text), max(1, $words));
    }

    public function purifier(): HTMLPurifier
    {
        if ($this->purifier instanceof HTMLPurifier) {
            return $this->purifier;
        }

        $cacheDir = WRITEPATH . 'cache/htmlpurifier';
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cacheDir);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.DefinitionID', 'destra-cms-content');
        $config->set('HTML.DefinitionRev', 3);
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins', 'mark', 'sup', 'sub',
            'ul', 'ol', 'li', 'a[href|title|rel|target]',
            'img[src|alt|title|width|height|loading|class]',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'pre', 'code', 'hr', 'figure', 'figcaption',
            'table[class]', 'thead', 'tbody', 'tr', 'th[colspan|rowspan]', 'td[colspan|rowspan]',
            'span[class]', 'div[class]',
            'iframe[src|width|height|allowfullscreen|loading|title|referrerpolicy]',
            'video[src|controls|poster|width|height|preload]',
            'audio[src|controls|preload]', 'source[src|type]',
        ]));
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/|www\.google\.com/maps/embed|maps\.google\.com/maps|www\.google\.com/recaptcha/)%');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('CSS.AllowTricky', false);

        // HTMLPurifier targets HTML 4.01; register the HTML5 elements that
        // modern editors emit so they survive purification instead of warning.
        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addElement('mark', 'Inline', 'Inline', 'Common');
            $definition->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
            $definition->addElement('figcaption', 'Block', 'Flow', 'Common');
            $definition->addElement('video', 'Block', 'Optional: (source, Flow) | Flow', 'Common', [
                'src'      => 'URI',
                'controls' => 'Bool',
                'poster'   => 'URI',
                'width'    => 'Length',
                'height'   => 'Length',
                'preload'  => 'Enum#auto,metadata,none',
            ]);
            $definition->addElement('audio', 'Block', 'Optional: (source, Flow) | Flow', 'Common', [
                'src'      => 'URI',
                'controls' => 'Bool',
                'preload'  => 'Enum#auto,metadata,none',
            ]);
            $definition->addElement('source', 'Inline', 'Empty', 'Common', [
                'src'  => 'URI',
                'type' => 'Text',
            ]);
            $definition->addAttribute('img', 'loading', 'Enum#lazy,eager,auto');
            $definition->addAttribute('iframe', 'allowfullscreen', 'Bool');
            $definition->addAttribute('iframe', 'loading', 'Enum#lazy,eager');
            $definition->addAttribute('iframe', 'referrerpolicy', 'Text');
        }

        return $this->purifier = new HTMLPurifier($config);
    }

    private function validUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
