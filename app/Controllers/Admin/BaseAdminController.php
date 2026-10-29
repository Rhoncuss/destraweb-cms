<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Destra;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class BaseAdminController extends BaseController
{
    protected array $admin = [];

    protected string $prefix = 'admin';

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        helper(['form', 'url', 'destra', 'text', 'inflector']);

        $this->admin  = (array) (service('auth')->user() ?? []);
        $this->prefix = trim((string) config(Destra::class)->adminPrefix, '/') ?: 'admin';
    }

    /**
     * Renders an admin page inside the shared layout.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): string
    {
        $unread = 0;
        try {
            $unread = db_connect()->table('notifications')
                ->where('user_id', (int) ($this->admin['id'] ?? 0))
                ->where('is_read', 0)
                ->countAllResults();
        } catch (Throwable) {
        }

        return view($view, array_merge([
            'adminUser'          => $this->admin,
            'adminPrefix'        => $this->prefix,
            'pageTitle'          => 'Dashboard',
            'pageSubtitle'       => '',
            'breadcrumbs'        => [],
            'navActive'          => '',
            'pageActions'        => '',
            'scheme'             => admin_scheme(),
            'unreadNotifications' => $unread,
            'siteName'           => (string) setting('general.site_name', 'DESTRA CMS'),
        ], $data));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function ok(array $data = [], string $message = 'Saved.'): ResponseInterface
    {
        return $this->response->setJSON(array_merge(['success' => true, 'message' => $message], $data));
    }

    protected function fail(string $message, int $status = 422, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(array_merge(['success' => false, 'error' => $message], $extra));
    }

    protected function wantsJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
    }

    protected function audit(string $action, string $description, string $module = 'general', ?string $subjectType = null, ?int $subjectId = null, array $meta = []): void
    {
        service('audit')->log($action, $description, $module, $subjectType, $subjectId, $meta);
    }

    protected function flushCache(): void
    {
        try {
            service('cache_manager')->flush();
        } catch (Throwable) {
        }
    }
}
