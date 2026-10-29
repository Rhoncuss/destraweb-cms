<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! is_file(config(\Config\Destra::class)->installLock)) {
            return redirect()->to(site_url('install'));
        }

        $auth = service('auth');

        if (! $auth->check()) {
            if ($this->wantsJson($request)) {
                return service('response')->setStatusCode(401)->setJSON([
                    'success' => false,
                    'error'   => 'Your session has ended. Please sign in again.',
                ]);
            }

            if ($request->getMethod() === 'GET') {
                try {
                    session()->set('redirect_url', current_url());
                } catch (\Throwable) {
                }
            }

            return redirect()->to(site_url('login'));
        }

        $user = $auth->user();
        if ($user && ! empty($user['locale']) && service('localization')->exists((string) $user['locale'])) {
            service('localization')->setCurrent((string) $user['locale']);
        }

        $auth->updateSessionActivity();

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function wantsJson(RequestInterface $request): bool
    {
        return $request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json');
    }
}
