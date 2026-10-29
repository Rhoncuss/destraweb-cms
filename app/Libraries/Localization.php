<?php

namespace App\Libraries;

use Config\Destra;
use Throwable;

class Localization
{
    /** @var list<array<string, mixed>>|null */
    private ?array $languages = null;

    private ?string $current = null;

    /** @var array<string, array<string, string>> */
    private array $translations = [];

    /**
     * @return list<array<string, mixed>>
     */
    public function languages(bool $activeOnly = true): array
    {
        if ($this->languages === null) {
            $this->languages = [];
            if (is_file(config(Destra::class)->installLock)) {
                try {
                    $this->languages = db_connect()->table('languages')->orderBy('sort_order', 'ASC')->get()->getResultArray();
                } catch (Throwable) {
                    $this->languages = [];
                }
            }

            if ($this->languages === []) {
                $this->languages = [
                    ['id' => 1, 'code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'flag' => 'us', 'is_default' => 1, 'is_active' => 1, 'sort_order' => 1],
                    ['id' => 2, 'code' => 'id', 'name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'direction' => 'ltr', 'flag' => 'id', 'is_default' => 0, 'is_active' => 1, 'sort_order' => 2],
                ];
            }

            foreach ($this->languages as &$language) {
                $language['is_default'] = (int) $language['is_default'];
                $language['is_active']  = (int) $language['is_active'];
            }
            unset($language);
        }

        if (! $activeOnly) {
            return $this->languages;
        }

        return array_values(array_filter($this->languages, static fn (array $l): bool => $l['is_active'] === 1));
    }

    public function codes(bool $activeOnly = true): array
    {
        return array_column($this->languages($activeOnly), 'code');
    }

    public function defaultCode(): string
    {
        foreach ($this->languages() as $language) {
            if ($language['is_default'] === 1) {
                return (string) $language['code'];
            }
        }

        return 'en';
    }

    public function exists(string $code, bool $activeOnly = true): bool
    {
        return in_array($code, $this->codes($activeOnly), true);
    }

    public function get(string $code): ?array
    {
        foreach ($this->languages(false) as $language) {
            if ($language['code'] === $code) {
                return $language;
            }
        }

        return null;
    }

    public function resolve(?string $candidate = null): string
    {
        if ($candidate !== null && $this->exists($candidate)) {
            return $candidate;
        }

        $session = null;
        try {
            if (! is_cli()) {
                $session = session();
            }
        } catch (Throwable) {
        }

        if ($session !== null) {
            $fromSession = (string) $session->get('locale', '');
            if ($fromSession !== '' && $this->exists($fromSession)) {
                return $fromSession;
            }
        }

        return $this->defaultCode();
    }

    public function setCurrent(string $code): void
    {
        if (! $this->exists($code)) {
            $code = $this->defaultCode();
        }

        $this->current = $code;
        try {
            service('language')->setLocale($code);
            service('request')->setLocale($code);
        } catch (Throwable) {
        }
    }

    public function current(): string
    {
        return $this->current ?? $this->resolve();
    }

    public function currentLanguage(): array
    {
        return $this->get($this->current()) ?? $this->languages()[0];
    }

    public function isRtl(?string $code = null): bool
    {
        $language = $this->get($code ?? $this->current());

        return ($language['direction'] ?? 'ltr') === 'rtl';
    }

    public function prefix(?string $code = null): string
    {
        $code ??= $this->current();

        return $code === $this->defaultCode() ? '' : '/' . $code;
    }

    /**
     * Translate a UI string, allowing visual overrides in the translations table.
     */
    public function translate(string $key, string $fallback = '', ?string $locale = null): string
    {
        $locale ??= $this->current();

        if (! isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
            if (is_file(config(Destra::class)->installLock)) {
                try {
                    $rows = db_connect()->table('translations')
                        ->select('trans_key, trans_value')
                        ->where('language_code', $locale)
                        ->get()
                        ->getResultArray();
                    foreach ($rows as $row) {
                        $this->translations[$locale][$row['trans_key']] = (string) $row['trans_value'];
                    }
                } catch (Throwable) {
                }
            }
        }

        $value = $this->translations[$locale][$key] ?? '';

        return $value !== '' ? $value : $fallback;
    }

    public function forget(): void
    {
        $this->languages    = null;
        $this->translations = [];
        $this->current      = null;
    }
}
