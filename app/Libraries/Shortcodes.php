<?php

namespace App\Libraries;

/**
 * Safe shortcode engine. Handlers never receive raw output permissions: every
 * attribute is validated and HTML is built from escaped values only.
 */
class Shortcodes
{
    /** @var array<string, callable> */
    private array $handlers = [];

    private bool $booted = false;

    public function add(string $tag, callable $handler): void
    {
        $tag = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $tag) ?? '');
        if ($tag === '') {
            return;
        }

        $this->handlers[$tag] = $handler;
    }

    public function render(?string $html): string
    {
        $html = (string) $html;
        if ($html === '' || ! str_contains($html, '[')) {
            return $html;
        }

        $this->boot();

        $html = preg_replace_callback(
            '/\[([a-z][a-z0-9_-]*)((?:[^\]\[]|\[[^\]]*\])*?)\](.*?)\[\/\1\]/is',
            fn (array $match): string => $this->handle($match[1], $match[2], $match[3]),
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[([a-z][a-z0-9_-]*)((?:[^\]\[]|\[[^\]]*\])*?)\/?\]/is',
            fn (array $match): string => $this->handle($match[1], $match[2], ''),
            $html
        ) ?? $html;

        $result = Hook::apply('shortcode.output', $html);

        return is_string($result) ? $result : $html;
    }

    private function handle(string $tag, string $rawAttributes, string $body): string
    {
        $tag = strtolower($tag);
        if (! isset($this->handlers[$tag])) {
            return '[' . $tag . $rawAttributes . ']' . ($body !== '' ? $body . '[/' . $tag . ']' : '');
        }

        try {
            $result = ($this->handlers[$tag])($this->attributes($rawAttributes), $body);
        } catch (\Throwable $e) {
            log_message('error', 'Shortcode {tag} failed: {msg}', ['tag' => $tag, 'msg' => $e->getMessage()]);

            return '';
        }

        return is_string($result) ? $result : '';
    }

    /**
     * @return array<string, string>
     */
    private function attributes(string $raw): array
    {
        $attributes = [];
        if (preg_match_all('/([a-z0-9_-]+)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s\]]+))/i', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = $match[3] !== '' ? $match[3] : ($match[4] !== '' ? $match[4] : ($match[5] ?? ''));
                $attributes[strtolower($match[1])] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $attributes;
    }

    private function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        $this->add('year', static fn (): string => date('Y'));
        $this->add('site', static fn (): string => esc((string) setting('general.site_name', 'DESTRA CMS')));
        $this->add('sitename', static fn (): string => esc((string) setting('general.site_name', 'DESTRA CMS')));
        $this->add('tagline', static fn (): string => esc((string) setting('general.tagline', '')));

        $this->add('button', function (array $attrs, string $body): string {
            $url = service('sanitizer')->url($attrs['url'] ?? '#', true);
            if ($url === '') {
                $url = '#';
            }
            $style = in_array($attrs['style'] ?? 'primary', ['primary', 'secondary', 'accent', 'outline', 'ghost'], true) ? $attrs['style'] : 'primary';
            $target = ($attrs['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
            $label = $body !== '' ? $body : ($attrs['text'] ?? 'Learn more');

            return '<a class="dst-btn dst-btn--' . $style . '" href="' . esc($url, 'attr') . '"' . $target . '>' . esc(trim(strip_tags($label))) . '</a>';
        });

        $this->add('alert', function (array $attrs, string $body): string {
            $type = in_array($attrs['type'] ?? 'info', ['info', 'success', 'warning', 'danger', 'primary'], true) ? $attrs['type'] : 'info';
            $title = trim((string) ($attrs['title'] ?? ''));
            $html = '<div class="dst-alert dst-alert--' . $type . '" role="alert">';
            if ($title !== '') {
                $html .= '<strong class="dst-alert__title">' . esc($title) . '</strong>';
            }
            $html .= '<span class="dst-alert__body">' . esc(trim(strip_tags($body))) . '</span></div>';

            return $html;
        });

        $this->add('youtube', function (array $attrs): string {
            $id = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $attrs['id'] ?? ($attrs['url'] ?? ''));
            if ($id === '' || strlen($id) > 20) {
                return '';
            }

            return '<div class="dst-embed"><iframe src="https://www.youtube-nocookie.com/embed/' . esc($id, 'attr') . '" title="YouTube video" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
        });

        $this->add('vimeo', function (array $attrs): string {
            $id = (string) preg_replace('/[^0-9]/', '', $attrs['id'] ?? ($attrs['url'] ?? ''));
            if ($id === '') {
                return '';
            }

            return '<div class="dst-embed"><iframe src="https://player.vimeo.com/video/' . esc($id, 'attr') . '" title="Vimeo video" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
        });

        $this->add('map', function (array $attrs): string {
            $lat = isset($attrs['lat']) && is_numeric($attrs['lat']) ? (float) $attrs['lat'] : null;
            $lng = isset($attrs['lng']) && is_numeric($attrs['lng']) ? (float) $attrs['lng'] : null;
            $zoom = max(2, min(19, (int) ($attrs['zoom'] ?? 15)));
            if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                return '';
            }

            $delta = 360 / (2 ** $zoom);
            $bbox  = ($lng - $delta) . ',' . ($lat - $delta) . ',' . ($lng + $delta) . ',' . ($lat + $delta);
            $src   = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox) . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);

            return '<div class="dst-embed dst-embed--map"><iframe src="' . esc($src, 'attr') . '" title="' . esc($attrs['title'] ?? 'Map', 'attr') . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
        });

        $this->add('code', static fn (array $attrs, string $body): string => '<pre class="dst-code"><code>' . esc(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</code></pre>');

        $this->add('divider', static fn (): string => '<hr class="dst-divider">');

        $this->add('icon', function (array $attrs): string {
            $name = preg_match('/^(fa[srb]? fa-[a-z0-9-]+|bi bi-[a-z0-9-]+)$/', (string) ($attrs['name'] ?? '')) ? (string) $attrs['name'] : 'fa-solid fa-circle';

            return '<i class="' . esc($name, 'attr') . '" aria-hidden="true"></i>';
        });

        $this->add('gallery', function (array $attrs): string {
            $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($attrs['ids'] ?? '')))));
            if ($ids === [] || count($ids) > 24) {
                return '';
            }

            $html = '<div class="dst-gallery">';
            foreach ($ids as $id) {
                $url = media_url($id, 'thumb');
                if ($url === '') {
                    continue;
                }
                $alt = service('media')->alt($id);
                $html .= '<figure class="dst-gallery__item"><a href="' . esc(media_url($id), 'attr') . '" data-lightbox="gallery"><img src="' . esc($url, 'attr') . '" alt="' . esc($alt, 'attr') . '" loading="lazy"></a></figure>';
            }
            $html .= '</div>';

            return $html;
        });
    }
}
