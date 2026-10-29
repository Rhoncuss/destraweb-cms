<?php

namespace Config;

/**
 * Canonical default settings for DESTRA CMS.
 *
 * Each entry: key => [default value, group, type]. "type" is used by the
 * settings UI (text, textarea, bool, int, color, select, secret).
 */
class SiteDefaults
{
    /**
     * @return array<string, array{0: mixed, 1: string, 2: string}>
     */
    public static function settings(): array
    {
        return [
            // General ------------------------------------------------------
            'general.site_name'           => ['DESTRA CMS', 'general', 'text'],
            'general.tagline'             => ['Ideas, crafted beautifully.', 'general', 'text'],
            'general.description'         => ['A multilingual publishing platform built for modern teams.', 'general', 'textarea'],
            'general.admin_email'         => ['', 'general', 'text'],
            'general.timezone'            => ['Asia/Jakarta', 'general', 'text'],
            'general.date_format'         => ['d M Y', 'general', 'text'],
            'general.logo'                => ['0', 'general', 'media'],
            'general.logo_dark'           => ['0', 'general', 'media'],
            'general.favicon'             => ['0', 'general', 'media'],
            'general.registration_enabled'=> ['0', 'general', 'bool'],
            'general.registration_role'   => ['4', 'general', 'int'],
            'general.preloader'           => ['1', 'general', 'bool'],
            'general.footer_style'        => ['1', 'general', 'int'],
            'general.copyright'           => ['© {year} {site}. All rights reserved.', 'general', 'text'],
            'general.footer_text'         => ['Crafted with care for curious minds.', 'general', 'text'],
            'general.language_switcher'   => ['1', 'general', 'bool'],
            'general.visitor_counter'     => ['1', 'general', 'bool'],
            'general.search_enabled'      => ['1', 'general', 'bool'],
            'general.maintenance_mode'    => ['0', 'general', 'bool'],
            'general.maintenance_message' => ['We are making things a little better. Please check back soon.', 'general', 'textarea'],
            'general.maintenance_ips'     => ['', 'general', 'text'],
            'general.debug_mode'          => ['0', 'general', 'bool'],
            'general.custom_head_code'    => ['', 'general', 'textarea'],
            'general.custom_body_code'    => ['', 'general', 'textarea'],
            'general.custom_css'          => ['', 'general', 'textarea'],
            'general.custom_js'           => ['', 'general', 'textarea'],
            'general.editor'              => ['tinymce', 'general', 'select'],

            // Reading ------------------------------------------------------
            'reading.home_mode'           => ['latest', 'reading', 'text'],
            'reading.home_page_id'        => ['0', 'reading', 'int'],
            'reading.posts_per_page'      => ['12', 'reading', 'int'],
            'reading.excerpt_length'      => ['26', 'reading', 'int'],
            'reading.show_author'         => ['1', 'reading', 'bool'],
            'reading.show_date'           => ['1', 'reading', 'bool'],
            'reading.show_reading_time'   => ['1', 'reading', 'bool'],
            'reading.show_views'          => ['1', 'reading', 'bool'],
            'reading.rss_items'           => ['20', 'reading', 'int'],
            'reading.home_sections'       => ['', 'reading', 'text'],

            // Comments -----------------------------------------------------
            'comments.enabled'            => ['1', 'comments', 'bool'],
            'comments.auto_approve'       => ['0', 'comments', 'bool'],
            'comments.require_login'      => ['0', 'comments', 'bool'],
            'comments.allow_guests'       => ['1', 'comments', 'bool'],
            'comments.notify_admin'       => ['1', 'comments', 'bool'],
            'comments.max_length'         => ['3000', 'comments', 'int'],

            // Appearance ---------------------------------------------------
            'appearance.theme'            => ['modern', 'appearance', 'text'],
            'appearance.primary'          => ['#4f46e5', 'appearance', 'color'],
            'appearance.secondary'        => ['#0ea5e9', 'appearance', 'color'],
            'appearance.accent'           => ['#f59e0b', 'appearance', 'color'],
            'appearance.radius'           => ['18', 'appearance', 'int'],
            'appearance.font'             => ['plus-jakarta', 'appearance', 'text'],
            'appearance.admin_scheme'     => ['midnight', 'appearance', 'text'],
            'appearance.admin_dark'       => ['0', 'appearance', 'bool'],
            'appearance.ads'              => ['1', 'appearance', 'bool'],
            'appearance.trending'         => ['1', 'appearance', 'bool'],

            // SEO ----------------------------------------------------------
            'seo.title_template'          => ['{title} — {site}', 'seo', 'text'],
            'seo.description'             => ['DESTRA CMS — a multilingual publishing platform for modern teams.', 'seo', 'textarea'],
            'seo.keywords'                => ['cms, publishing, blog, documentation', 'seo', 'text'],
            'seo.robots'                  => ['index,follow', 'seo', 'text'],
            'seo.friendly_urls'           => ['1', 'seo', 'bool'],
            'seo.canonical'               => ['1', 'seo', 'bool'],
            'seo.hreflang'                => ['1', 'seo', 'bool'],
            'seo.og_default'              => ['0', 'seo', 'media'],
            'seo.twitter_handle'          => ['', 'seo', 'text'],
            'seo.google_verification'     => ['', 'seo', 'text'],
            'seo.bing_verification'       => ['', 'seo', 'text'],
            'seo.organization_type'       => ['Organization', 'seo', 'text'],
            'seo.sitemap_enabled'         => ['1', 'seo', 'bool'],
            'seo.analytics_id'            => ['', 'seo', 'text'],
            'seo.gtm_id'                  => ['', 'seo', 'text'],
            'seo.rss_enabled'             => ['1', 'seo', 'bool'],

            // Mail ---------------------------------------------------------
            'mail.enabled'                => ['0', 'mail', 'bool'],
            'mail.protocol'               => ['smtp', 'mail', 'text'],
            'mail.host'                   => ['', 'mail', 'text'],
            'mail.port'                   => ['587', 'mail', 'int'],
            'mail.username'               => ['', 'mail', 'text'],
            'mail.password'               => ['', 'mail', 'secret'],
            'mail.encryption'             => ['tls', 'mail', 'text'],
            'mail.from_email'             => ['', 'mail', 'text'],
            'mail.from_name'              => ['DESTRA CMS', 'mail', 'text'],
            'mail.reply_to'               => ['', 'mail', 'text'],
            'mail.notify_email'           => ['', 'mail', 'text'],

            // Newsletter / marketing --------------------------------------
            'newsletter.enabled'          => ['1', 'marketing', 'bool'],
            'newsletter.provider'         => ['internal', 'marketing', 'text'],
            'newsletter.double_optin'     => ['1', 'marketing', 'bool'],
            'newsletter.message'          => ['Join 12,400+ readers. One thoughtful email, once a week.', 'marketing', 'textarea'],
            'newsletter.mailchimp_key'    => ['', 'marketing', 'secret'],
            'newsletter.mailchimp_list'   => ['', 'marketing', 'text'],
            'newsletter.mailcoach_url'    => ['', 'marketing', 'text'],
            'newsletter.mailcoach_token'  => ['', 'marketing', 'secret'],
            'newsletter.mailcoach_list'   => ['', 'marketing', 'text'],
            'social.facebook'             => ['', 'marketing', 'text'],
            'social.x'                    => ['', 'marketing', 'text'],
            'social.instagram'            => ['', 'marketing', 'text'],
            'social.linkedin'             => ['', 'marketing', 'text'],
            'social.youtube'              => ['', 'marketing', 'text'],
            'social.tiktok'               => ['', 'marketing', 'text'],
            'social.github'               => ['', 'marketing', 'text'],
            'social.pinterest'            => ['', 'marketing', 'text'],
            'social.telegram'             => ['', 'marketing', 'text'],
            'social.whatsapp'             => ['', 'marketing', 'text'],
            'social.share'                => ['1', 'marketing', 'bool'],
            'social.share_networks'       => ['facebook,x,linkedin,pinterest,whatsapp,email', 'marketing', 'text'],
            'social.login_enabled'        => ['0', 'marketing', 'bool'],
            'social.social_registration'  => ['0', 'marketing', 'bool'],

            // Captcha ------------------------------------------------------
            'captcha.enabled'             => ['0', 'captcha', 'bool'],
            'captcha.provider'            => ['recaptcha_v2', 'captcha', 'text'],
            'captcha.site_key'            => ['', 'captcha', 'text'],
            'captcha.secret_key'          => ['', 'captcha', 'secret'],
            'captcha.threshold'           => ['0.5', 'captcha', 'text'],
            'captcha.on_login'            => ['0', 'captcha', 'bool'],
            'captcha.on_comments'         => ['1', 'captcha', 'bool'],
            'captcha.on_forms'            => ['1', 'captcha', 'bool'],
            'captcha.on_newsletter'       => ['1', 'captcha', 'bool'],

            // Integrations -------------------------------------------------
            'integrations.maps_key'       => ['', 'integrations', 'secret'],
            'integrations.ai_enabled'     => ['0', 'integrations', 'bool'],
            'integrations.ai_provider'    => ['openai', 'integrations', 'text'],
            'integrations.ai_api_key'     => ['', 'integrations', 'secret'],
            'integrations.ai_base_url'    => ['https://api.openai.com/v1', 'integrations', 'text'],
            'integrations.ai_model'       => ['gpt-4o-mini', 'integrations', 'text'],
            'integrations.ai_image_model' => ['gpt-image-1', 'integrations', 'text'],
            'integrations.ai_max_tokens'  => ['1200', 'integrations', 'int'],
            'integrations.ai_temperature' => ['0.7', 'integrations', 'text'],

            // Security -----------------------------------------------------
            'security.login_attempts'     => ['5', 'security', 'int'],
            'security.lockout_minutes'    => ['15', 'security', 'int'],
            'security.session_timeout'    => ['120', 'security', 'int'],
            'security.force_https'        => ['0', 'security', 'bool'],
            'security.csp'                => ['1', 'security', 'bool'],
            'security.api_rate_limit'     => ['120', 'security', 'int'],
            'security.max_upload_mb'      => ['20', 'security', 'int'],
            'security.audit_retention'    => ['180', 'security', 'int'],
            'security.password_min'       => ['10', 'security', 'int'],
            'security.pwned_check'        => ['0', 'security', 'bool'],
            'security.login_captcha'      => ['0', 'security', 'bool'],

            // API ----------------------------------------------------------
            'api.enabled'                 => ['1', 'api', 'bool'],
            'api.rate_limit'              => ['120', 'api', 'int'],
            'api.allow_origins'           => ['', 'api', 'text'],
            'api.docs_public'             => ['0', 'api', 'bool'],

            // Cache --------------------------------------------------------
            'cache.auto_clear'            => ['1', 'cache', 'bool'],
            'cache.sitemap_ttl'           => ['21600', 'cache', 'int'],

            // Orders -------------------------------------------------------
            'orders.enabled'              => ['1', 'orders', 'bool'],
            'orders.notify_email'         => ['', 'orders', 'text'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function encryptedKeys(): array
    {
        return [
            'mail.password', 'newsletter.mailchimp_key', 'newsletter.mailcoach_token',
            'captcha.secret_key', 'integrations.maps_key', 'integrations.ai_api_key',
        ];
    }
}
