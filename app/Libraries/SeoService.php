<?php

namespace App\Libraries;

use Throwable;

/**
 * Builds the public SEO surface: meta/OG/Twitter tags, JSON-LD, XML sitemaps,
 * robots.txt and the RSS feed. Everything is cached and flushed with the rest
 * of the public cache whenever content changes.
 */
class SeoService
{
    public function siteName(): string
    {
        return (string) service('settings')->get('general.site_name', 'DESTRA CMS');
    }

    public function tagline(): string
    {
        return (string) service('settings')->get('general.tagline', '');
    }

    /**
     * Render the <head> SEO block for a page.
     *
     * Supported keys: title, description, keywords, image, type (website|article),
     * url, canonical, published_at, modified_at, author, robots, noindex,
     * alternates (code => absolute url), jsonld (list<array>).
     */
    public function render(array $context = []): string
    {
        $settings = service('settings');
        $site     = $this->siteName();
        $title    = trim((string) ($context['title'] ?? ''));
        $template = (string) $settings->get('seo.title_template', '{title} — {site}');

        $fullTitle = $title !== ''
            ? str_replace(['{title}', '{site}', '{tagline}'], [$title, $site, $this->tagline()], $template)
            : $site . ($this->tagline() !== '' ? ' — ' . $this->tagline() : '');

        $description = trim((string) ($context['description'] ?? ''));
        if ($description === '') {
            $description = (string) $settings->get('seo.description', (string) $settings->get('general.description', ''));
        }
        $description = mb_substr(preg_replace('/\s+/u', ' ', strip_tags($description)) ?? '', 0, 300);

        $keywords = trim((string) ($context['keywords'] ?? $settings->get('seo.keywords', '')));

        $robots = (string) ($context['robots'] ?? $settings->get('seo.robots', 'index,follow'));
        if (! empty($context['noindex'])) {
            $robots = 'noindex,nofollow';
        }
        $robots = preg_match('/^[a-z,\- ]+$/i', $robots) ? $robots : 'index,follow';

        $url  = (string) ($context['url'] ?? current_url());
        $type = ($context['type'] ?? 'website') === 'article' ? 'article' : 'website';

        $canonical = '';
        if ($settings->bool('seo.canonical', true)) {
            $canonical = trim((string) ($context['canonical'] ?? ''));
            if ($canonical === '') {
                $canonical = $url;
            }
        }
        $canonical = service('sanitizer')->url($canonical);

        $image = $this->resolveImage($context['image'] ?? $settings->get('seo.og_default', 0));

        $html = [];
        $html[] = '<title>' . esc($fullTitle) . '</title>';
        if ($description !== '') {
            $html[] = '<meta name="description" content="' . esc($description) . '">';
        }
        if ($keywords !== '') {
            $html[] = '<meta name="keywords" content="' . esc($keywords) . '">';
        }
        $html[] = '<meta name="robots" content="' . esc($robots) . '">';
        if ($canonical !== '') {
            $html[] = '<link rel="canonical" href="' . esc($canonical) . '">';
        }

        $verification = [
            'google-site-verification' => (string) $settings->get('seo.google_verification', ''),
            'msvalidate.01'            => (string) $settings->get('seo.bing_verification', ''),
        ];
        foreach ($verification as $name => $value) {
            if ($value !== '') {
                $html[] = '<meta name="' . $name . '" content="' . esc($value) . '">';
            }
        }

        if ($settings->bool('seo.hreflang', true) && ! empty($context['alternates'])) {
            foreach ((array) $context['alternates'] as $code => $alternateUrl) {
                $alternateUrl = service('sanitizer')->url((string) $alternateUrl);
                if ($alternateUrl !== '') {
                    $html[] = '<link rel="alternate" hreflang="' . esc((string) $code) . '" href="' . esc($alternateUrl) . '">';
                }
            }
        }

        $html[] = '<meta property="og:site_name" content="' . esc($site) . '">';
        $html[] = '<meta property="og:locale" content="' . esc(str_replace('-', '_', current_locale())) . '">';
        $html[] = '<meta property="og:type" content="' . $type . '">';
        $html[] = '<meta property="og:title" content="' . esc($title !== '' ? $title : $fullTitle) . '">';
        if ($description !== '') {
            $html[] = '<meta property="og:description" content="' . esc($description) . '">';
        }
        $html[] = '<meta property="og:url" content="' . esc($url) . '">';
        if ($image !== '') {
            $html[] = '<meta property="og:image" content="' . esc($image) . '">';
        }

        $twitter = ltrim((string) $settings->get('seo.twitter_handle', ''), '@');
        $html[] = '<meta name="twitter:card" content="' . ($image !== '' ? 'summary_large_image' : 'summary') . '">';
        $html[] = '<meta name="twitter:title" content="' . esc($title !== '' ? $title : $fullTitle) . '">';
        if ($description !== '') {
            $html[] = '<meta name="twitter:description" content="' . esc($description) . '">';
        }
        if ($image !== '') {
            $html[] = '<meta name="twitter:image" content="' . esc($image) . '">';
        }
        if ($twitter !== '') {
            $html[] = '<meta name="twitter:site" content="@' . esc($twitter) . '">';
        }

        if ($type === 'article') {
            if (! empty($context['published_at'])) {
                $html[] = '<meta property="article:published_time" content="' . esc((string) $context['published_at']) . '">';
            }
            if (! empty($context['modified_at'])) {
                $html[] = '<meta property="article:modified_time" content="' . esc((string) $context['modified_at']) . '">';
            }
            if (! empty($context['author'])) {
                $html[] = '<meta property="article:author" content="' . esc((string) $context['author']) . '">';
            }
        }

        $jsonLd = $this->defaultJsonLd($context);
        foreach ((array) ($context['jsonld'] ?? []) as $node) {
            if (is_array($node) && $node !== []) {
                $jsonLd[] = $node;
            }
        }
        foreach ($jsonLd as $node) {
            $encoded = json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            if ($encoded !== false) {
                $html[] = '<script type="application/ld+json">' . $encoded . '</script>';
            }
        }

        return implode("\n", $html);
    }

    /**
     * Build a canonical content context from a content row and translation.
     */
    public function contentContext(array $content, array $translation, array $author = [], array $alternates = []): array
    {
        $image = (int) ($translation['og_image_id'] ?? 0) ?: (int) ($content['featured_image_id'] ?? 0);
        $description = trim((string) ($translation['seo_description'] ?? ''));
        if ($description === '') {
            $description = service('sanitizer')->excerpt((string) ($translation['excerpt'] ?? $translation['body'] ?? ''), 30);
        }

        $url = content_url($content, $translation);
        $canonical = trim((string) ($translation['canonical_url'] ?? ''));

        return [
            'title'        => (string) ($translation['seo_title'] ?? '') !== '' ? (string) $translation['seo_title'] : (string) ($translation['title'] ?? ''),
            'description'  => $description,
            'keywords'     => (string) ($translation['seo_keywords'] ?? ''),
            'image'        => $image > 0 ? $image : null,
            'type'         => (($content['type'] ?? 'post') === 'page') ? 'website' : 'article',
            'url'          => $url,
            'canonical'    => $canonical !== '' ? $canonical : $url,
            'published_at' => (string) ($content['published_at'] ?? ''),
            'modified_at'  => (string) ($translation['updated_at'] ?? $content['updated_at'] ?? ''),
            'author'       => (string) ($author['name'] ?? ''),
            'alternates'   => $alternates,
            'jsonld'       => [
                [
                    '@context'      => 'https://schema.org',
                    '@type'         => ($content['type'] ?? 'post') === 'page' ? 'WebPage' : 'BlogPosting',
                    'headline'      => (string) ($translation['title'] ?? ''),
                    'description'   => $description,
                    'datePublished' => (string) ($content['published_at'] ?? ''),
                    'dateModified'  => (string) ($content['updated_at'] ?? ''),
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => content_url($content, $translation)],
                    'author'        => ['@type' => 'Person', 'name' => (string) ($author['name'] ?? $this->siteName())],
                    'publisher'     => ['@type' => 'Organization', 'name' => $this->siteName()],
                    'image'         => $image > 0 ? media_url($image) : '',
                ],
            ],
        ];
    }

    public function sitemap(?string $locale = null): string
    {
        $ttl = max(300, (int) service('settings')->get('cache.sitemap_ttl', 21600));
        $key = 'sitemap_' . ($locale ?? 'all');

        return (string) service('cache_manager')->remember($key, $ttl, function () use ($locale): string {
            $locales = service('localization')->languages();
            if ($locale !== null) {
                $locales = array_values(array_filter($locales, static fn (array $l): bool => $l['code'] === $locale));
            }
            if ($locales === []) {
                $locales = [['code' => default_locale()]];
            }

            $entries = []; // url => ['lastmod' => ..., 'alternates' => [code => url]]
            $now     = date('c');

            foreach ($locales as $language) {
                $entries[loc_url('', $language['code'])] ??= ['lastmod' => $now, 'alternates' => []];
            }

            try {
                $db = db_connect();
                $contents = $this->sitemapContents($db);
                if ($contents !== []) {
                    $ids = array_column($contents, 'id');
                    $translations = $db->table('content_translations')
                        ->select('content_id, language_code, slug, updated_at')
                        ->whereIn('content_id', $ids)
                        ->get()
                        ->getResultArray();

                    $byContent = [];
                    foreach ($translations as $translation) {
                        $byContent[(int) $translation['content_id']][(string) $translation['language_code']] = $translation;
                    }

                    foreach ($contents as $content) {
                        $available = $byContent[(int) $content['id']] ?? [];
                        $activeLocales = $locale !== null
                            ? [$locale]
                            : array_values(array_intersect(array_column($locales, 'code'), array_keys($available)));

                        $urls = [];
                        foreach ($activeLocales as $code) {
                            if (! isset($available[$code])) {
                                continue;
                            }
                            $urls[$code] = content_url(
                                [
                                    'id'           => (int) $content['id'],
                                    'type'         => (string) $content['type'],
                                    'section_slug' => (string) ($content['section_slug'] ?? ''),
                                ],
                                $available[$code],
                                $code
                            );
                        }

                        foreach ($urls as $code => $url) {
                            $entries[$url] = [
                                'lastmod'    => date('c', strtotime((string) ($available[$code]['updated_at'] ?? $content['updated_at'] ?? 'now')) ?: time()),
                                'alternates' => $urls,
                            ];
                        }
                    }
                }

                $sections = $db->table('sections')->where('is_active', 1)->where('visibility', 'public')->get()->getResultArray();
                foreach ($sections as $section) {
                    foreach ($locales as $language) {
                        $url = section_url($section, $language['code']);
                        $entries[$url] ??= ['lastmod' => $now, 'alternates' => []];
                    }
                }

                $categoryRows = $db->table('category_translations ct')
                    ->select('ct.category_id, ct.language_code, ct.slug, ct.updated_at')
                    ->join('categories c', 'c.id = ct.category_id')
                    ->where('c.is_active', 1)
                    ->where('c.deleted_at', null)
                    ->get()
                    ->getResultArray();
                foreach ($categoryRows as $category) {
                    if ($locale !== null && $category['language_code'] !== $locale) {
                        continue;
                    }
                    $entries[category_url(['id' => (int) $category['category_id']], (string) $category['slug'], (string) $category['language_code'])] = [
                        'lastmod'    => date('c', strtotime((string) $category['updated_at']) ?: time()),
                        'alternates' => [],
                    ];
                }

                $tagRows = $db->table('tag_translations')
                    ->select('tag_id, language_code, slug, updated_at')
                    ->get()
                    ->getResultArray();
                foreach ($tagRows as $tag) {
                    if ($locale !== null && $tag['language_code'] !== $locale) {
                        continue;
                    }
                    $entries[tag_url((string) $tag['slug'], (string) $tag['language_code'])] = [
                        'lastmod'    => date('c', strtotime((string) $tag['updated_at']) ?: time()),
                        'alternates' => [],
                    ];
                }
            } catch (Throwable $e) {
                log_message('error', 'Sitemap build failed: ' . $e->getMessage());
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
            foreach ($entries as $url => $data) {
                if (! is_string($url) || $url === '') {
                    continue;
                }
                $xml .= "  <url>\n";
                $xml .= '    <loc>' . $this->xml($url) . "</loc>\n";
                if (! empty($data['lastmod'])) {
                    $xml .= '    <lastmod>' . $this->xml((string) $data['lastmod']) . "</lastmod>\n";
                }
                foreach ((array) ($data['alternates'] ?? []) as $code => $alternate) {
                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . $this->xml((string) $code) . '" href="' . $this->xml((string) $alternate) . "\"/>\n";
                }
                $xml .= "  </url>\n";
            }
            $xml .= '</urlset>';

            return $xml;
        });
    }

    public function robots(): string
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /install',
            'Disallow: /install/',
            'Disallow: /search',
            'Disallow: /preview/',
            '',
        ];

        $extra = trim((string) service('settings')->get('seo.robots_extra', ''));
        if ($extra !== '') {
            foreach (preg_split('/\R/', $extra) ?: [] as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        if (service('settings')->bool('seo.sitemap_enabled', true)) {
            $lines[] = 'Sitemap: ' . base_url('sitemap.xml');
            foreach (service('localization')->languages() as $language) {
                $lines[] = 'Sitemap: ' . loc_url('sitemap.xml', (string) $language['code']);
            }
            $lines[] = '';
        }

        return implode("\n", array_unique($lines));
    }

    public function feed(?string $locale = null): string
    {
        $locale = $locale ?? current_locale();
        $key    = 'feed_' . $locale;

        return (string) service('cache_manager')->remember($key, 1800, function () use ($locale): string {
            $site = $this->siteName();
            $description = (string) service('settings')->get('seo.description', '');
            $limit = max(1, (int) service('settings')->get('reading.rss_items', 20));

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
            $xml .= "  <channel>\n";
            $xml .= '    <title>' . $this->xml($site) . "</title>\n";
            $xml .= '    <link>' . $this->xml(loc_url('', $locale)) . "</link>\n";
            $xml .= '    <description>' . $this->xml($description) . "</description>\n";
            $xml .= '    <language>' . $this->xml($locale) . "</language>\n";
            $xml .= '    <lastBuildDate>' . date(DATE_RSS) . "</lastBuildDate>\n";
            $xml .= '    <atom:link href="' . $this->xml(loc_url('feed', $locale)) . '" rel="self" type="application/rss+xml"/>' . "\n";

            try {
                $db = db_connect();
                $contents = $this->sitemapContents($db, $limit);
                if ($contents !== []) {
                    $ids = array_column($contents, 'id');
                    $translations = $db->table('content_translations')
                        ->whereIn('content_id', $ids)
                        ->where('language_code', $locale)
                        ->get()
                        ->getResultArray();
                    $map = [];
                    foreach ($translations as $translation) {
                        $map[(int) $translation['content_id']] = $translation;
                    }

                    foreach ($contents as $content) {
                        $translation = $map[(int) $content['id']] ?? null;
                        if ($translation === null) {
                            continue;
                        }
                        $url = content_url([
                            'id'           => (int) $content['id'],
                            'type'         => (string) $content['type'],
                            'section_slug' => (string) ($content['section_slug'] ?? ''),
                        ], $translation, $locale);

                        $xml .= "    <item>\n";
                        $xml .= '      <title>' . $this->xml((string) $translation['title']) . "</title>\n";
                        $xml .= '      <link>' . $this->xml($url) . "</link>\n";
                        $xml .= '      <guid isPermaLink="true">' . $this->xml($url) . "</guid>\n";
                        $xml .= '      <pubDate>' . date(DATE_RSS, strtotime((string) $content['published_at']) ?: time()) . "</pubDate>\n";
                        $summary = service('sanitizer')->excerpt((string) ($translation['excerpt'] ?? $translation['body'] ?? ''), 40);
                        $xml .= '      <description>' . $this->xml($summary) . "</description>\n";
                        $xml .= "    </item>\n";
                    }
                }
            } catch (Throwable $e) {
                log_message('error', 'Feed build failed: ' . $e->getMessage());
            }

            $xml .= "  </channel>\n</rss>";

            return $xml;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sitemapContents($db, int $limit = 5000): array
    {
        $now = date('Y-m-d H:i:s');

        return $db->table('contents c')
            ->select('c.id, c.type, c.updated_at, c.published_at, s.slug AS section_slug')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.status', 'published')
            ->where('c.deleted_at', null)
            ->where('c.published_at <=', $now)
            ->groupStart()
                ->where('c.expires_at', null)
                ->orWhere('c.expires_at >', $now)
            ->groupEnd()
            ->orderBy('c.published_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    private function resolveImage(mixed $image): string
    {
        if ($image === null || $image === '' || $image === 0 || $image === '0') {
            return '';
        }

        if (is_numeric($image) && (int) $image > 0) {
            return media_url((int) $image);
        }

        return service('sanitizer')->url((string) $image);
    }

    private function defaultJsonLd(array $context): array
    {
        $nodes = [];
        $site  = $this->siteName();
        $url   = (string) ($context['url'] ?? base_url('/'));

        $nodes[] = [
            '@context' => 'https://schema.org',
            '@type'    => (string) service('settings')->get('seo.organization_type', 'Organization'),
            'name'     => $site,
            'url'      => base_url('/'),
        ];

        $nodes[] = [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $site,
            'url'      => base_url('/'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => search_url() . '?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return $nodes;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
