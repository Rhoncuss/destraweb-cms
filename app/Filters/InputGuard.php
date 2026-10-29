<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InputGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = (string) $request->getUri();
        $path = $request->getUri()->getPath();

        if (
            preg_match('/[\x00-\x1F]/', $uri)
            || preg_match('/%00|%2e%2e|\.\.\/|\.\.\\\\|%5c/i', $uri)
            || str_contains($path, '\\')
        ) {
            return $this->reject(400, 'The request contains an invalid path.');
        }

        foreach ([
            '/.env', '/.git', '/.htaccess', '/app/', '/writable/', '/vendor/', '/sdk/',
            '/composer.json', '/composer.lock', '/spark', '/phpunit', '/themes/', '/plugins/',
        ] as $blocked) {
            if (str_starts_with(strtolower($path), $blocked)) {
                return $this->reject(404, 'Not found.');
            }
        }

        $length = (int) ($request->getHeaderLine('Content-Length') ?: 0);
        if ($length > 52_428_800) {
            return $this->reject(413, 'The request is too large.');
        }

        $sanitizer = service('sanitizer');

        if (! empty($_GET)) {
            $_GET = $sanitizer->cleanArray($_GET);
        }
        if (! empty($_POST)) {
            $_POST = $sanitizer->cleanArray($_POST);
        }

        if (! empty($_POST) || ! empty($_GET)) {
            $_REQUEST = array_merge($_GET, $_POST);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function reject(int $status, string $message): ResponseInterface
    {
        if (str_starts_with(trim(service('request')->getPath(), '/'), 'api/')) {
            return service('response')->setStatusCode($status)->setJSON([
                'success' => false,
                'error'   => $message,
            ]);
        }

        return service('response')->setStatusCode($status)->setBody(view('errors/html/error_general', [
            'code'    => $status,
            'message' => $message,
        ]));
    }
}
