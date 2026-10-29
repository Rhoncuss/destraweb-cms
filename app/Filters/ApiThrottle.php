<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class ApiThrottle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->getHeaderLine('Authorization');
        $key  = $auth !== '' ? substr(hash('sha256', $auth), 0, 24) : 'ip_' . str_replace([':', '.'], '_', $request->getIPAddress());
        $cacheKey = 'destra_api_rl_' . $key;

        try {
            $limit = max(10, (int) service('settings')->get('api.rate_limit', 120));
        } catch (Throwable) {
            $limit = 120;
        }

        $count = (int) (cache($cacheKey) ?: 0) + 1;
        cache()->save($cacheKey, $count, MINUTE);

        $remaining = max(0, $limit - $count);
        $response  = service('response');
        $response->setHeader('X-RateLimit-Limit', (string) $limit);
        $response->setHeader('X-RateLimit-Remaining', (string) $remaining);

        if ($count > $limit) {
            $response->setHeader('Retry-After', '60');

            return $response->setStatusCode(429)->setJSON([
                'success' => false,
                'error'   => [
                    'code'    => 'rate_limited',
                    'message' => 'Too many requests. Please slow down.',
                ],
            ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
