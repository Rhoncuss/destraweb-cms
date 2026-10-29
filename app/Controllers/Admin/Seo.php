<?php

namespace App\Controllers\Admin;

use Throwable;

class Seo extends BaseAdminController
{
    public function index(): string
    {
        $db      = db_connect();
        $default = default_locale();

        $published = (int) $db->table('contents')->where('status', 'published')->where('deleted_at', null)->countAllResults();

        $missingDescription = 0;
        $missingTitle       = 0;
        $missingImage       = 0;
        try {
            $missingDescription = (int) $db->table('content_translations ct')
                ->join('contents c', 'c.id = ct.content_id')
                ->where('c.status', 'published')->where('c.deleted_at', null)
                ->where('ct.language_code', $default)
                ->groupStart()->where('ct.seo_description', null)->orWhere('ct.seo_description', '')->groupEnd()
                ->countAllResults();

            $missingTitle = (int) $db->table('content_translations ct')
                ->join('contents c', 'c.id = ct.content_id')
                ->where('c.status', 'published')->where('c.deleted_at', null)
                ->where('ct.language_code', $default)
                ->groupStart()->where('ct.seo_title', null)->orWhere('ct.seo_title', '')->groupEnd()
                ->countAllResults();

            $missingImage = (int) $db->table('contents c')
                ->join('content_translations ct', 'ct.content_id = c.id AND ct.language_code = ' . $db->escape($default), 'left')
                ->where('c.status', 'published')->where('c.deleted_at', null)
                ->where('c.featured_image_id', null)
                ->groupStart()->where('ct.og_image_id', null)->orWhere('ct.og_image_id', 0)->groupEnd()
                ->countAllResults();
        } catch (Throwable) {
        }

        $rows = $db->table('contents c')
            ->select('c.id, c.type, c.updated_at AS content_updated, ct.title, ct.slug, ct.seo_title, ct.seo_description, ct.og_image_id, ct.canonical_url, ct.updated_at AS translation_updated, s.slug AS section_slug')
            ->join('content_translations ct', 'ct.content_id = c.id AND ct.language_code = ' . $db->escape($default))
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.status', 'published')
            ->where('c.deleted_at', null)
            ->orderBy('c.updated_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $redirects = (int) $db->table('redirects')->where('is_active', 1)->countAllResults();

        $settings = service('settings');

        return $this->render('admin/seo/index', [
            'pageTitle'     => 'SEO suite',
            'pageSubtitle'  => 'Technical health, metadata coverage and crawler files in one place.',
            'navActive'     => 'seo',
            'breadcrumbs'   => [['label' => 'SEO suite']],
            'published'     => $published,
            'missingDescription' => $missingDescription,
            'missingTitle'  => $missingTitle,
            'missingImage'  => $missingImage,
            'rows'          => $rows,
            'redirects'     => $redirects,
            'sitemapUrl'    => base_url('sitemap.xml'),
            'robotsUrl'     => base_url('robots.txt'),
            'feedUrl'       => base_url('feed'),
            'sitemapEnabled' => $settings->bool('seo.sitemap_enabled', true),
            'rssEnabled'    => $settings->bool('seo.rss_enabled', true),
            'canonical'     => $settings->bool('seo.canonical', true),
            'hreflang'      => $settings->bool('seo.hreflang', true),
            'analyticsId'   => (string) $settings->get('seo.analytics_id', ''),
            'gtmId'         => (string) $settings->get('seo.gtm_id', ''),
            'titleTemplate' => (string) $settings->get('seo.title_template', '{title} — {site}'),
        ]);
    }

    public function rebuild()
    {
        $this->flushCache();
        $this->audit('seo.rebuild', 'Rebuilt sitemap and feed caches', 'seo');

        return $this->ok([], 'Sitemap, feed and page caches rebuilt.');
    }
}
