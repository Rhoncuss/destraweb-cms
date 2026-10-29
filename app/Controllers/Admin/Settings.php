<?php

namespace App\Controllers\Admin;

use Throwable;

class Settings extends BaseAdminController
{
    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function tabs(): array
    {
        return [
            'general'      => ['label' => 'General', 'icon' => 'fa-solid fa-sliders'],
            'reading'      => ['label' => 'Reading', 'icon' => 'fa-solid fa-book-open-reader'],
            'appearance'   => ['label' => 'Appearance', 'icon' => 'fa-solid fa-palette'],
            'comments'     => ['label' => 'Comments', 'icon' => 'fa-regular fa-comments'],
            'seo'          => ['label' => 'SEO', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
            'mail'         => ['label' => 'Email', 'icon' => 'fa-regular fa-envelope'],
            'marketing'    => ['label' => 'Marketing', 'icon' => 'fa-solid fa-bullhorn'],
            'captcha'      => ['label' => 'Captcha', 'icon' => 'fa-solid fa-shield-halved'],
            'integrations' => ['label' => 'Integrations', 'icon' => 'fa-solid fa-plug'],
            'security'     => ['label' => 'Security', 'icon' => 'fa-solid fa-lock'],
            'api'          => ['label' => 'API', 'icon' => 'fa-solid fa-code'],
            'orders'       => ['label' => 'Orders', 'icon' => 'fa-solid fa-bag-shopping'],
        ];
    }

    public function index(): string
    {
        $tabs = self::tabs();
        $tab  = (string) ($this->request->getGet('group') ?? 'general');
        if (! isset($tabs[$tab])) {
            $tab = 'general';
        }

        $settings = service('settings');
        $fields   = $this->fields($tab);
        $values   = [];
        foreach (array_keys($fields) as $key) {
            $values[$key] = $settings->get($key);
        }

        return $this->render('admin/settings/index', [
            'pageTitle'    => 'Settings',
            'pageSubtitle' => 'Configure how DESTRA CMS looks, behaves and talks to the world.',
            'navActive'    => 'settings',
            'breadcrumbs'  => [['label' => 'Settings'], ['label' => $tabs[$tab]['label']]],
            'tabs'         => $tabs,
            'tab'          => $tab,
            'fields'       => $fields,
            'values'       => $values,
            'isVerified'   => $tab === 'mail' && (string) $settings->get('mail.enabled') === '1',
        ]);
    }

    public function update(string $group)
    {
        $tabs = self::tabs();
        if (! isset($tabs[$group])) {
            return $this->fail('Unknown settings group.', 404);
        }

        $fields = $this->fields($group);
        $post   = (array) $this->request->getPost();
        $raw    = (array) ($post['settings'] ?? []);
        $san    = service('sanitizer');
        $store  = service('settings');
        $saved  = 0;

        foreach ($fields as $key => $meta) {
            $type = $meta['type'];

            if ($type === 'bool') {
                $store->set($key, ! empty($raw[$key]) && (string) $raw[$key] !== '0' ? '1' : '0');
                $saved++;
                continue;
            }

            if (! array_key_exists($key, $raw)) {
                continue;
            }

            $input = $raw[$key];

            $value = match ($type) {
                'int'    => (string) $san->int($input, (int) ($meta['min'] ?? 0), (int) ($meta['max'] ?? 1000000)),
                'color'  => $san->color($input, (string) $store->get($key)),
                'media'  => (string) $san->int($input, 0),
                'secret' => is_string($input) ? trim($input) : '',
                'email'  => $san->email($input),
                'url'    => $san->url($input),
                'html'   => $san->html($input),
                'css'    => $san->css($input),
                'js'     => $san->js($input),
                'textarea' => $san->textarea($input),
                'select' => isset($meta['options'][(string) $input]) ? (string) $input : (string) $store->get($key),
                default  => $san->text($input),
            };

            if ($type === 'secret' && $value === '') {
                continue;
            }

            $store->set($key, $value);
            $saved++;
        }

        $this->flushCache();
        $this->audit('settings.update', 'Updated ' . $group . ' settings (' . $saved . ' values)', 'settings', 'group', null);

        if ($this->wantsJson()) {
            return $this->ok(['saved' => $saved], 'Settings saved.');
        }

        return redirect()->to(admin_url('settings?group=' . $group))->with('success', 'Settings saved.');
    }

    public function testMail()
    {
        $to = service('sanitizer')->email($this->request->getPost('to'))
            ?: (string) service('settings')->get('general.admin_email');

        if ($to === '') {
            return $this->fail('Enter a recipient address first.');
        }

        try {
            $sent = service('mailer')->send(
                $to,
                'DESTRA CMS test message',
                '<p>This is a test message from your DESTRA CMS installation.</p><p>If you can read this, your mail settings work.</p>',
            );
        } catch (Throwable $e) {
            return $this->fail('Mail error: ' . $e->getMessage());
        }

        $this->audit('settings.test_mail', 'Sent a test email to ' . $to, 'settings');

        return $sent
            ? $this->ok([], 'Test email sent to ' . $to . '.')
            : $this->fail('The mailer rejected the message. Check the SMTP host, port and credentials.');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(string $group): array
    {
        $timezones = [];
        foreach (timezone_identifiers_list() as $zone) {
            $timezones[$zone] = $zone;
        }

        $themes = [];
        try {
            foreach (service('themeManager')->available() as $slug => $theme) {
                $themes[(string) $slug] = (string) ($theme['name'] ?? $slug);
            }
        } catch (Throwable) {
        }
        if ($themes === []) {
            $themes = ['modern' => 'Modern'];
        }

        $schemes = [];
        foreach (admin_schemes() as $slug => $scheme) {
            $schemes[(string) $slug] = (string) ($scheme['label'] ?? $slug);
        }

        $roles = [];
        try {
            foreach (db_connect()->table('roles')->orderBy('id', 'ASC')->get()->getResultArray() as $row) {
                $roles[(string) $row['id']] = (string) $row['name'];
            }
        } catch (Throwable) {
            $roles = ['4' => 'Author'];
        }

        $pages = ['0' => '— Select a page —'];
        try {
            foreach (db_connect()->table('content_translations t')
                ->select('t.content_id, t.title')
                ->join('contents c', 'c.id = t.content_id')
                ->where('c.type', 'page')
                ->where('c.status', 'published')
                ->where('c.deleted_at', null)
                ->where('t.language_code', default_locale())
                ->orderBy('t.title', 'ASC')
                ->get()->getResultArray() as $row) {
                $pages[(string) $row['content_id']] = (string) $row['title'];
            }
        } catch (Throwable) {
        }

        $bool = static fn (string $label, string $help = ''): array => ['label' => $label, 'type' => 'bool', 'help' => $help];
        $text = static fn (string $label, string $help = ''): array => ['label' => $label, 'type' => 'text', 'help' => $help];
        $area = static fn (string $label, string $help = ''): array => ['label' => $label, 'type' => 'textarea', 'help' => $help];
        $select = static fn (string $label, array $options, string $help = ''): array => ['label' => $label, 'type' => 'select', 'options' => $options, 'help' => $help];
        $int = static fn (string $label, int $min, int $max, string $help = ''): array => ['label' => $label, 'type' => 'int', 'min' => $min, 'max' => $max, 'help' => $help];

        $map = [
            'general' => [
                'general.site_name'            => $text('Site name'),
                'general.tagline'              => $text('Tagline'),
                'general.description'          => $area('Site description', 'Used as the default meta description.'),
                'general.admin_email'          => $text('Administrator email', 'Receives system notifications and password resets.'),
                'general.timezone'             => $select('Timezone', $timezones, 'All dates are stored in UTC and rendered in this zone.'),
                'general.date_format'          => $text('Date format', 'PHP date() syntax, e.g. d M Y.'),
                'general.editor'               => $select('Rich text editor', ['tinymce' => 'TinyMCE', 'summernote' => 'Summernote', 'ckeditor' => 'CKEditor 5']),
                'general.logo'                 => ['label' => 'Logo (light)', 'type' => 'media'],
                'general.logo_dark'            => ['label' => 'Logo (dark)', 'type' => 'media'],
                'general.favicon'              => ['label' => 'Favicon', 'type' => 'media'],
                'general.registration_enabled' => $bool('Allow public registration', 'Visitors can create reader accounts.'),
                'general.registration_role'    => $select('Default role for new accounts', $roles),
                'general.preloader'            => $bool('Show the site preloader'),
                'general.footer_style'         => $select('Footer style', ['1' => 'Columns', '2' => 'Minimal', '3' => 'Newsletter focus']),
                'general.copyright'            => $text('Copyright line', 'Use {year} and {site} as placeholders.'),
                'general.footer_text'          => $text('Footer note'),
                'general.language_switcher'    => $bool('Show the language switcher'),
                'general.visitor_counter'      => $bool('Show the visitor counter'),
                'general.search_enabled'       => $bool('Enable site search'),
                'general.maintenance_mode'     => $bool('Maintenance mode', 'Only administrators keep access while enabled.'),
                'general.maintenance_message'  => $area('Maintenance message'),
                'general.maintenance_ips'      => $text('Allowed IPs', 'Comma separated addresses that always see the site.'),
                'general.debug_mode'           => $bool('Debug mode', 'Shows detailed errors. Never enable this on a live site.'),
                'general.custom_head_code'     => ['label' => 'Custom head code', 'type' => 'html', 'help' => 'Injected before </head>. Analytics, verification tags.'],
                'general.custom_body_code'     => ['label' => 'Custom footer code', 'type' => 'html', 'help' => 'Injected before </body>. Chat widgets, trackers.'],
                'general.custom_css'           => ['label' => 'Custom CSS', 'type' => 'css'],
                'general.custom_js'            => ['label' => 'Custom JavaScript', 'type' => 'js'],
            ],
            'reading' => [
                'reading.home_mode'          => $select('Homepage shows', [
                    'latest'   => 'Latest content',
                    'section'  => 'Section feed',
                    'page'     => 'A static page',
                ]),
                'reading.home_page_id'       => $select('Homepage page', $pages),
                'reading.posts_per_page'     => $int('Posts per page', 1, 100),
                'reading.excerpt_length'     => $int('Excerpt length (words)', 5, 120),
                'reading.show_author'        => $bool('Show author byline'),
                'reading.show_date'          => $bool('Show publish date'),
                'reading.show_reading_time'  => $bool('Show reading time'),
                'reading.show_views'         => $bool('Show view count'),
                'reading.rss_items'          => $int('RSS items', 1, 200),
                'reading.home_sections'      => $text('Homepage sections', 'Comma separated section slugs, in display order.'),
            ],
            'appearance' => [
                'appearance.theme'         => $select('Active theme', $themes, 'Themes live in /themes and are hot-swappable.'),
                'appearance.primary'       => ['label' => 'Primary colour', 'type' => 'color'],
                'appearance.secondary'     => ['label' => 'Secondary colour', 'type' => 'color'],
                'appearance.accent'        => ['label' => 'Accent colour', 'type' => 'color'],
                'appearance.radius'        => $int('Corner radius (px)', 0, 40),
                'appearance.font'          => $select('Display font', [
                    'plus-jakarta' => 'Plus Jakarta Sans',
                    'inter'        => 'Inter',
                    'manrope'      => 'Manrope',
                    'system'       => 'System UI',
                ]),
                'appearance.admin_scheme'  => $select('Admin colour scheme', $schemes),
                'appearance.admin_dark'    => $bool('Dark admin by default'),
                'appearance.ads'           => $bool('Enable ad slots'),
                'appearance.trending'      => $bool('Show trending strip'),
            ],
            'comments' => [
                'comments.enabled'       => $bool('Enable comments'),
                'comments.auto_approve'  => $bool('Auto-approve comments', 'Off means every comment waits for moderation.'),
                'comments.require_login' => $bool('Require an account to comment'),
                'comments.allow_guests'  => $bool('Allow guest comments'),
                'comments.notify_admin'  => $bool('Email the administrator about new comments'),
                'comments.max_length'    => $int('Maximum comment length', 200, 20000),
            ],
            'seo' => [
                'seo.title_template'       => $text('Title template', 'Available: {title}, {site}, {tagline}.'),
                'seo.description'          => $area('Default meta description'),
                'seo.keywords'             => $text('Default keywords'),
                'seo.robots'               => $select('Default robots directive', [
                    'index,follow'        => 'index, follow',
                    'index,nofollow'      => 'index, nofollow',
                    'noindex,follow'      => 'noindex, follow',
                    'noindex,nofollow'    => 'noindex, nofollow',
                ]),
                'seo.friendly_urls'        => $bool('Friendly URLs'),
                'seo.canonical'            => $bool('Emit canonical URLs'),
                'seo.hreflang'             => $bool('Emit hreflang alternatives'),
                'seo.sitemap_enabled'      => $bool('Serve /sitemap.xml'),
                'seo.rss_enabled'          => $bool('Serve the RSS feed'),
                'seo.og_default'           => ['label' => 'Default social image', 'type' => 'media'],
                'seo.twitter_handle'       => $text('X / Twitter handle'),
                'seo.organization_type'    => $select('Organization type', [
                    'Organization' => 'Organization',
                    'NewsMediaOrganization' => 'News media',
                    'Corporation'  => 'Corporation',
                    'NGO'          => 'NGO',
                ]),
                'seo.google_verification'  => $text('Google site verification'),
                'seo.bing_verification'    => $text('Bing site verification'),
                'seo.analytics_id'         => $text('Google Analytics ID', 'e.g. G-XXXXXXX.'),
                'seo.gtm_id'               => $text('Google Tag Manager ID', 'e.g. GTM-XXXXXX.'),
            ],
            'mail' => [
                'mail.enabled'    => $bool('Enable outgoing email', 'Disable while testing to queue nothing.'),
                'mail.protocol'   => $select('Protocol', ['smtp' => 'SMTP', 'mail' => 'PHP mail()', 'sendmail' => 'sendmail']),
                'mail.host'       => $text('SMTP host'),
                'mail.port'       => $int('SMTP port', 1, 65535),
                'mail.username'   => $text('SMTP username'),
                'mail.password'   => ['label' => 'SMTP password', 'type' => 'secret', 'help' => 'Stored AES-256-GCM encrypted. Leave blank to keep the current value.'],
                'mail.encryption' => $select('Encryption', ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']),
                'mail.from_email' => $text('From address'),
                'mail.from_name'  => $text('From name'),
                'mail.reply_to'   => $text('Reply-to address'),
                'mail.notify_email' => $text('Notification address', 'Defaults to the administrator email when blank.'),
            ],
            'marketing' => [
                'newsletter.enabled'         => $bool('Enable the newsletter'),
                'newsletter.provider'        => $select('Provider', ['internal' => 'Internal list', 'mailchimp' => 'Mailchimp', 'mailcoach' => 'Mailcoach']),
                'newsletter.double_optin'    => $bool('Double opt-in', 'New subscribers must confirm by email.'),
                'newsletter.message'         => $area('Signup pitch'),
                'newsletter.mailchimp_key'   => ['label' => 'Mailchimp API key', 'type' => 'secret'],
                'newsletter.mailchimp_list'  => $text('Mailchimp list ID'),
                'newsletter.mailcoach_url'   => $text('Mailcoach URL'),
                'newsletter.mailcoach_token' => ['label' => 'Mailcoach token', 'type' => 'secret'],
                'newsletter.mailcoach_list'  => $text('Mailcoach list UUID'),
                'social.share'               => $bool('Show share buttons'),
                'social.share_networks'      => $text('Share networks', 'Comma separated: facebook, x, linkedin, pinterest, whatsapp, email.'),
                'social.login_enabled'       => $bool('Enable social login'),
                'social.social_registration' => $bool('Create accounts on social sign-in'),
                'social.facebook'            => $text('Facebook URL'),
                'social.x'                   => $text('X URL'),
                'social.instagram'           => $text('Instagram URL'),
                'social.linkedin'            => $text('LinkedIn URL'),
                'social.youtube'             => $text('YouTube URL'),
                'social.tiktok'              => $text('TikTok URL'),
                'social.github'              => $text('GitHub URL'),
                'social.pinterest'           => $text('Pinterest URL'),
                'social.telegram'            => $text('Telegram URL'),
                'social.whatsapp'            => $text('WhatsApp URL'),
            ],
            'captcha' => [
                'captcha.enabled'      => $bool('Enable captcha'),
                'captcha.provider'     => $select('Provider', [
                    'recaptcha_v2'   => 'reCAPTCHA v2',
                    'recaptcha_v3'   => 'reCAPTCHA v3',
                    'turnstile'      => 'Cloudflare Turnstile',
                    'hcaptcha'       => 'hCaptcha',
                ]),
                'captcha.site_key'     => $text('Site key'),
                'captcha.secret_key'   => ['label' => 'Secret key', 'type' => 'secret', 'help' => 'Stored encrypted. Leave blank to keep the current value.'],
                'captcha.threshold'    => $text('v3 score threshold', 'Between 0 and 1. Higher is stricter.'),
                'captcha.on_login'     => $bool('Protect the sign-in form'),
                'captcha.on_comments'  => $bool('Protect comments'),
                'captcha.on_forms'     => $bool('Protect forms'),
                'captcha.on_newsletter'=> $bool('Protect newsletter signup'),
            ],
            'integrations' => [
                'integrations.maps_key'       => ['label' => 'Google Maps API key', 'type' => 'secret'],
                'integrations.ai_enabled'     => $bool('Enable AI assistant'),
                'integrations.ai_provider'    => $select('AI provider', ['openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'openrouter' => 'OpenRouter', 'custom' => 'Custom (OpenAI compatible)']),
                'integrations.ai_api_key'     => ['label' => 'AI API key', 'type' => 'secret'],
                'integrations.ai_base_url'    => $text('API base URL'),
                'integrations.ai_model'       => $text('Text model'),
                'integrations.ai_image_model' => $text('Image model'),
                'integrations.ai_max_tokens'  => $int('Max tokens', 64, 32000),
                'integrations.ai_temperature' => $text('Temperature', '0 is deterministic, 1 is creative.'),
            ],
            'security' => [
                'security.login_attempts'  => $int('Failed logins before lockout', 1, 50),
                'security.lockout_minutes' => $int('Lockout window (minutes)', 1, 1440),
                'security.session_timeout' => $int('Session timeout (minutes)', 5, 1440),
                'security.password_min'    => $int('Minimum password length', 8, 128),
                'security.force_https'     => $bool('Force HTTPS'),
                'security.csp'             => $bool('Send a Content-Security-Policy header'),
                'security.pwned_check'     => $bool('Reject passwords found in breaches'),
                'security.login_captcha'   => $bool('Require captcha on sign-in'),
                'security.api_rate_limit'  => $int('API requests per minute', 10, 10000),
                'security.max_upload_mb'   => $int('Maximum upload size (MB)', 1, 512),
                'security.audit_retention' => $int('Keep audit logs (days)', 7, 3650),
            ],
            'api' => [
                'api.enabled'       => $bool('Enable the REST API'),
                'api.rate_limit'    => $int('Requests per minute', 10, 10000),
                'api.allow_origins' => $text('Allowed CORS origins', 'Comma separated. Leave blank to disable cross-origin calls.'),
                'api.docs_public'   => $bool('Make the API reference public'),
            ],
            'orders' => [
                'orders.enabled'      => $bool('Enable order requests'),
                'orders.notify_email' => $text('Order notification address'),
            ],
        ];

        return $map[$group] ?? [];
    }
}
