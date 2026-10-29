<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $permission = is_array($arguments) ? (string) ($arguments[0] ?? '') : (string) $arguments;

        if ($permission === '') {
            return null;
        }

        if (! service('auth')->can($permission)) {
            if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
                return service('response')->setStatusCode(403)->setJSON([
                    'success' => false,
                    'error'   => 'You do not have permission to perform this action.',
                ]);
            }

            return service('response')->setStatusCode(403)->setBody(view('errors/html/error_general', [
                'code'    => 403,
                'message' => 'You do not have permission to access this page.',
            ]));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
