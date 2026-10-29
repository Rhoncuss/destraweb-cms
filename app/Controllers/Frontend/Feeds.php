<?php

namespace App\Controllers\Frontend;

/**
 * XML sitemaps, robots.txt and RSS feeds. Locale aware so /id/sitemap.xml and
 * /id/feed resolve to translated content.
 */
class Feeds extends BaseFrontendController
{
    public function sitemap(?string $locale = null)
    {
        if (! service('settings')->bool('seo.sitemap_enabled', true)) {
            return $this->response->setStatusCode(404)->setBody('Sitemap disabled.');
        }

        if ($locale !== null) {
            if (! service('localization')->exists($locale)) {
                return $this->response->setStatusCode(404)->setBody('Unknown language.');
            }
            $this->setLocale($locale);
        }

        return $this->response
            ->setContentType('application/xml; charset=UTF-8')
            ->setBody(service('seo')->sitemap($locale));
    }

    public function robots()
    {
        return $this->response
            ->setContentType('text/plain; charset=UTF-8')
            ->setBody(service('seo')->robots());
    }

    public function feed(?string $locale = null)
    {
        if (! service('settings')->bool('seo.rss_enabled', true)) {
            return $this->response->setStatusCode(404)->setBody('Feed disabled.');
        }

        if ($locale !== null) {
            if (! service('localization')->exists($locale)) {
                return $this->response->setStatusCode(404)->setBody('Unknown language.');
            }
            $this->setLocale($locale);
        }

        return $this->response
            ->setContentType('application/rss+xml; charset=UTF-8')
            ->setBody(service('seo')->feed($locale ?? $this->locale));
    }
}
