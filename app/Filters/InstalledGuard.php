<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InstalledGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! is_file(config(\Config\Destra::class)->installLock)) {
            if (str_starts_with(trim($request->getPath(), '/'), 'api/')) {
                return service('response')->setStatusCode(503)->setJSON([
                    'success' => false,
                    'error'   => 'DESTRA CMS is not installed yet.',
                ]);
            }

            return redirect()->to(site_url('install'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
