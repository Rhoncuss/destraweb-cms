<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Maintenance implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! is_file(config(\Config\Destra::class)->installLock)) {
            return null;
        }

        if (! service('settings')->bool('general.maintenance_mode')) {
            return null;
        }

        if (service('auth')->check()) {
            return null;
        }

        $ip = $request->getIPAddress();
        foreach (service('settings')->list('general.maintenance_ips') as $allowed) {
            if ($allowed === $ip || (str_ends_with($allowed, '*') && str_starts_with($ip, rtrim($allowed, '*')))) {
                return null;
            }
        }

        if (str_starts_with(trim($request->getPath(), '/'), 'api/')) {
            return service('response')->setStatusCode(503)->setJSON([
                'success' => false,
                'error'   => 'The site is under maintenance.',
            ]);
        }

        $response = service('response');
        $response->setStatusCode(503);
        $response->setHeader('Retry-After', '3600');

        return $response->setBody(view('frontend/fallback/maintenance', [
            'message'  => (string) service('settings')->get('general.maintenance_message', 'We are making things better. Please check back soon.'),
            'siteName' => (string) service('settings')->get('general.site_name', 'DESTRA CMS'),
        ]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
