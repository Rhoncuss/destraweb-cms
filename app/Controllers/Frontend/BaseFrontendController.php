<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class BaseFrontendController extends BaseController
{
    protected string $locale = 'en';

    /** @var array<string, mixed> */
    protected array $theme = [];

    protected string $themeSlug = 'modern';

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        helper(['form', 'url', 'destra', 'text', 'inflector']);

        service('themeManager')->boot();
        $this->themeSlug = service('themeManager')->activeSlug();
        $this->theme     = service('themeManager')->active();
        $this->locale    = service('localization')->resolve();
        service('localization')->setCurrent($this->locale);
    }

    protected function setLocale(string $code): void
    {
        if (! service('localization')->exists($code)) {
            $code = service('localization')->defaultCode();
        }

        $this->locale = $code;
        service('localization')->setCurrent($code);
    }

    protected function wantsJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json')
            || str_contains(strtolower($this->request->getHeaderLine('X-Requested-With')), 'xmlhttprequest');
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function jsonOk(array $data = [], string $message = 'Done.', int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge([
            'success' => true,
            'message' => $message,
        ], $data));
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function jsonFail(string $message, int $status = 422, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra));
    }

    protected function requestLocale(): string
    {
        $candidate = (string) ($this->request->getPost('locale') ?? '');

        return $candidate !== '' && service('localization')->exists($candidate) ? $candidate : $this->locale;
    }

    protected function notifyAdmins(string $type, string $title, string $message, string $url, string $icon = 'fa-solid fa-bell'): void
    {
        try {
            $db   = db_connect();
            $userIds = $db->table('users')
                ->select('id')
                ->where('role_id', 1)
                ->where('status', 'active')
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            foreach ($userIds as $row) {
                $db->table('notifications')->insert([
                    'user_id'    => (int) $row['id'],
                    'type'       => mb_substr($type, 0, 30),
                    'title'      => mb_substr($title, 0, 190),
                    'message'    => mb_substr($message, 0, 1000),
                    'url'        => $url,
                    'icon'       => $icon,
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
            log_message('debug', 'Admin notification skipped: ' . $e->getMessage());
        }
    }

    /**
     * Renders a public page inside the active theme's layout.
     *
     * @param array<string, mixed> $data
     */
    protected function renderPage(string $view, array $data = []): string
    {
        $data['locale']       = $this->locale;
        $data['language']     = current_language();
        $data['theme']        = $this->theme;
        $data['themeSlug']    = $this->themeSlug;
        $data['languages']    = languages();
        $data['isRtl']        = is_rtl();
        $data += ['seo' => '', 'bodyClass' => '', 'crumbs' => []];

        $data += $this->shellData();

        $view = ltrim($view, '/');
        $body = $this->renderThemeView($view, $data);

        $layout = 'frontend/fallback/layout';
        if (service('themeManager')->viewExists('layout')) {
            $layout = 'Theme\\Views\\layout';
        }

        return view($layout, array_merge($data, ['body' => $body]));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderThemeView(string $view, array $data): string
    {
        if (service('themeManager')->viewExists($view)) {
            return view('Theme\\Views\\' . $view, $data);
        }

        $fallback = APPPATH . 'Views/frontend/fallback/' . $view . '.php';

        return is_file($fallback)
            ? view('frontend/fallback/' . $view, $data)
            : view('frontend/fallback/generic', $data);
    }

    /**
     * Shared data every public layout needs.
     *
     * @return array<string, mixed>
     */
    protected function shellData(): array
    {
        $settings = service('settings');
        $siteName = (string) $settings->get('general.site_name', 'DESTRA CMS');
        $year     = date('Y');

        $copyright = str_replace(
            ['{year}', '{site}'],
            [$year, $siteName],
            (string) $settings->get('general.copyright', '© {year} {site}')
        );

        $social = [];
        foreach (['facebook', 'x', 'instagram', 'linkedin', 'youtube', 'tiktok', 'pinterest', 'github', 'telegram', 'whatsapp'] as $network) {
            $url = service('sanitizer')->url((string) $settings->get('social.' . $network, ''));
            if ($url !== '') {
                $social[$network] = $url;
            }
        }

        return [
            'siteName'      => $siteName,
            'tagline'       => (string) $settings->get('general.tagline', ''),
            'logoId'        => (int) $settings->get('general.logo', 0),
            'faviconId'     => (int) $settings->get('general.favicon', 0),
            'preloader'     => $settings->bool('general.preloader', false),
            'searchEnabled' => $settings->bool('general.search_enabled', true),
            'langSwitcher'  => $settings->bool('general.language_switcher', true),
            'visitorCounter' => $settings->bool('general.visitor_counter', false),
            'visitorCount'  => $settings->bool('general.visitor_counter', false) ? $this->visitorCount() : 0,
            'footerText'    => (string) $settings->get('general.footer_text', ''),
            'copyright'     => $copyright,
            'customCss'     => (string) $settings->get('general.custom_css', ''),
            'customHead'    => (string) $settings->get('general.custom_head_code', ''),
            'customBody'    => (string) $settings->get('general.custom_body_code', ''),
            'customJs'      => (string) $settings->get('general.custom_js', ''),
            'primaryMenu'   => menu_tree('primary'),
            'footerMenu'    => menu_tree('footer'),
            'social'        => $social,
            'newsletterEnabled' => $settings->bool('newsletter.enabled', true),
            'newsletterMessage' => (string) $settings->get('newsletter.message', ''),
            'commentsEnabled' => $settings->bool('comments.enabled', true),
            'gtmId'         => (string) $settings->get('seo.gtm_id', ''),
            'analyticsId'   => (string) $settings->get('seo.analytics_id', ''),
            'popups'        => $this->activePopups(),
            'headerBanner'  => $this->ads('header'),
            'footerBanner'  => $this->ads('footer'),
            'fontStack'     => $this->fontStack((string) $settings->get('appearance.font', 'plus-jakarta')),
            'themeVars'     => [
                'primary'   => (string) $settings->get('appearance.primary', '#4f46e5'),
                'secondary' => (string) $settings->get('appearance.secondary', '#0ea5e9'),
                'accent'    => (string) $settings->get('appearance.accent', '#f59e0b'),
                'radius'    => (int) $settings->get('appearance.radius', 18),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function ads(string $placement): array
    {
        $key = 'ads_' . preg_replace('/[^a-z0-9_]/i', '', $placement) . '_' . $this->locale;

        $items = service('cache_manager')->remember($key, 600, function () use ($placement): array {
            try {
                $now = date('Y-m-d H:i:s');
                $rows = db_connect()->table('banners b')
                    ->select('b.*, t.title, t.subtitle, t.alt, t.cta_label AS button_label')
                    ->join('banner_translations t', 't.banner_id = b.id AND t.language_code = ' . db_connect()->escape($this->locale), 'left')
                    ->where('b.placement', $placement)
                    ->where('b.is_active', 1)
                    ->groupStart()->where('b.starts_at', null)->orWhere('b.starts_at <=', $now)->groupEnd()
                    ->groupStart()->where('b.ends_at', null)->orWhere('b.ends_at >', $now)->groupEnd()
                    ->orderBy('b.sort_order', 'ASC')
                    ->orderBy('b.id', 'DESC')
                    ->get()->getResultArray();

                // Banners without a translation in the current language fall back
                // to the site's default locale so placements never render blank.
                $default = service('localization')->defaultCode();
                if ($default !== $this->locale && $rows !== []) {
                    $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
                    $fallback = db_connect()->table('banner_translations')
                        ->select('banner_id, title, subtitle, alt, cta_label')
                        ->where('language_code', $default)
                        ->whereIn('banner_id', $ids)
                        ->get()->getResultArray();
                    $byBanner = [];
                    foreach ($fallback as $translation) {
                        $byBanner[(int) $translation['banner_id']] = $translation;
                    }
                    foreach ($rows as &$row) {
                        $source = $byBanner[(int) $row['id']] ?? [];
                        foreach (['title', 'subtitle', 'alt', 'button_label'] as $field) {
                            if (($row[$field] ?? null) === null || $row[$field] === '') {
                                $row[$field] = $field === 'button_label'
                                    ? ($source['cta_label'] ?? '')
                                    : ($source[$field] ?? '');
                            }
                        }
                    }
                    unset($row);
                }

                foreach ($rows as &$row) {
                    $row['image_url'] = ! empty($row['image_id']) ? media_url((int) $row['image_id']) : '';
                    $row['target']    = ($row['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                    $row['href']      = ! empty($row['link_url']) ? base_url('ads/' . (int) $row['id'] . '/click') : '';
                }
                unset($row);

                return $rows;
            } catch (Throwable) {
                return [];
            }
        });

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activePopups(): array
    {
        $page = $this->popupPageContext();

        $items = service('cache_manager')->remember('popups_' . $this->locale . '_' . $page, 600, function () use ($page): array {
            try {
                $now = date('Y-m-d H:i:s');
                $rows = db_connect()->table('popups')
                    ->where('is_active', 1)
                    ->whereIn('pages', ['all', $page])
                    ->groupStart()->where('starts_at', null)->orWhere('starts_at <=', $now)->groupEnd()
                    ->groupStart()->where('ends_at', null)->orWhere('ends_at >', $now)->groupEnd()
                    ->orderBy('id', 'DESC')
                    ->get()->getResultArray();

                foreach ($rows as &$row) {
                    $row['image_url'] = ! empty($row['image_id']) ? media_url((int) $row['image_id']) : '';
                }
                unset($row);

                return $rows;
            } catch (Throwable) {
                return [];
            }
        });

        return $items;
    }

    protected function popupPageContext(): string
    {
        $path = trim($this->request->getPath(), '/');

        return $path === '' ? 'home' : 'all';
    }

    protected function visitorCount(): int
    {
        return (int) service('cache_manager')->remember('visitor_total', 300, static function (): int {
            try {
                $row = db_connect()->table('visits')->selectSum('views')->get()->getRowArray();

                return (int) ($row['views'] ?? 0);
            } catch (Throwable) {
                return 0;
            }
        });
    }

    protected function fontStack(string $key): string
    {
        return match ($key) {
            'inter'   => "'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif",
            'manrope' => "'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif",
            'system'  => "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif",
            default   => "'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif",
        };
    }

    protected function isDownForMaintenance(): bool
    {
        if (! service('settings')->bool('general.maintenance_mode', false)) {
            return false;
        }

        try {
            if (auth_check() && user_can('settings.manage')) {
                return false;
            }
        } catch (Throwable) {
        }

        $ips = array_filter(array_map('trim', explode(',', (string) service('settings')->get('general.maintenance_ips', ''))));

        return ! in_array($this->request->getIPAddress(), $ips, true);
    }

    protected function maintenanceResponse(): string
    {
        $this->response->setStatusCode(503)->setHeader('Retry-After', '3600');

        return view('frontend/fallback/maintenance', $this->shellData() + [
            'message' => (string) service('settings')->get('general.maintenance_message', 'We are making things better. Please check back soon.'),
            'siteName' => (string) service('settings')->get('general.site_name', 'DESTRA CMS'),
            'themeSlug' => $this->themeSlug,
        ]);
    }

    /**
     * Applies a matching URL redirect, if one exists for the request path.
     */
    protected function handleRedirect(string $path): ?ResponseInterface
    {
        if ($path === '' || str_starts_with($path, 'admin') || str_starts_with($path, 'api')) {
            return null;
        }

        $from = '/' . ltrim($path, '/');

        $map = service('cache_manager')->remember('redirect_map', 300, static function (): array {
            try {
                $rows = db_connect()->table('redirects')->where('is_active', 1)->get()->getResultArray();
            } catch (Throwable) {
                return [];
            }

            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row['from_path']] = $row;
            }

            return $map;
        });

        if (! isset($map[$from])) {
            return null;
        }

        $row = $map[$from];
        try {
            db_connect()->table('redirects')->where('id', (int) $row['id'])->set('hits', 'hits + 1', false)->update();
        } catch (Throwable) {
        }

        $to = (string) $row['to_path'];
        if (! preg_match('#^https?://#i', $to)) {
            $to = base_url(ltrim($to, '/'));
        }

        return redirect()->to($to, (int) $row['status_code'] ?? 301, 'GET');
    }
}
