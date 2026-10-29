<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminGuest implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! is_file(config(\Config\Destra::class)->installLock)) {
            return redirect()->to(site_url('install'));
        }

        if (service('auth')->check()) {
            return redirect()->to(site_url('admin'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
