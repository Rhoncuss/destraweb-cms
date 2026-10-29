<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Locale implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $localization = service('localization');
        $path         = trim($request->getPath(), '/');
        $segments     = $path === '' ? [] : explode('/', $path);
        $candidate    = null;

        if (isset($segments[0]) && $localization->exists($segments[0])) {
            $candidate = $segments[0];
            if ($candidate === $localization->defaultCode()) {
                // Prefixing the default language is allowed, but canonical
                // URLs never include it.
            }
        }

        if (str_starts_with($path, 'admin')) {
            $user = service('auth')->user();
            if ($user && ! empty($user['locale']) && $localization->exists((string) $user['locale'])) {
                $candidate = (string) $user['locale'];
            }
        }

        $code = $localization->resolve($candidate);
        $localization->setCurrent($code);

        try {
            if (! is_cli()) {
                session()->set('locale', $code);
            }
        } catch (\Throwable) {
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
