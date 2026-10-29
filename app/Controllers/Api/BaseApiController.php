<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\ApiContext;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseApiController extends BaseController
{
    /** @var array<string, mixed> */
    protected array $payload = [];

    protected int $perPage = 20;

    protected int $page = 1;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        helper(['url', 'destra', 'text']);

        $this->applyCors();

        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && str_contains(strtolower($request->getHeaderLine('Content-Type')), 'json')) {
            try {
                $json = $request->getJSON(true);
            } catch (\Throwable) {
                $json = null;
            }
            $this->payload = is_array($json) ? $json : (array) $request->getPost();
        }

        $this->page    = max(1, (int) ($request->getGet('page') ?? 1));
        $this->perPage = min(100, max(1, (int) ($request->getGet('per_page') ?? 20)));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function ok(array $data = [], array $meta = [], int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
        ]);
    }

    protected function created(array $data = [], array $meta = []): ResponseInterface
    {
        return $this->ok($data, $meta, 201);
    }

    /**
     * @param array<string, mixed> $details
     */
    protected function fail(string $message, int $status = 400, string $code = 'bad_request', array $details = []): ResponseInterface
    {
        $body = [
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ];
        if ($details !== []) {
            $body['error']['details'] = $details;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    protected function guard(string $ability): ?ResponseInterface
    {
        if (ApiContext::can($ability)) {
            return null;
        }

        return $this->fail('This token is missing the "' . $ability . '" permission.', 403, 'forbidden');
    }

    /**
     * @return array{page: int, per_page: int, total: int, pages: int}
     */
    protected function pagination(int $total): array
    {
        return [
            'page'     => $this->page,
            'per_page' => $this->perPage,
            'total'    => $total,
            'pages'    => (int) ceil($total / $this->perPage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $this->request->getPost($key) ?? $this->request->getGet($key) ?? $default;
    }

    protected function applyCors(): void
    {
        $allowed = trim((string) service('settings')->get('api.allow_origins', ''));
        $origin  = $this->request->getHeaderLine('Origin');

        if ($allowed === '' || $allowed === '*') {
            $this->response->setHeader('Access-Control-Allow-Origin', '*');
        } else {
            $origins = array_filter(array_map('trim', explode(',', $allowed)));
            if ($origin !== '' && in_array($origin, $origins, true)) {
                $this->response->setHeader('Access-Control-Allow-Origin', $origin);
                $this->response->setHeader('Vary', 'Origin');
            } else {
                $this->response->setHeader('Access-Control-Allow-Origin', (string) ($origins[0] ?? 'null'));
            }
        }

        $this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-API-Key, Accept');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Max-Age', '86400');
        $this->response->setHeader('X-API-Version', '1');
    }
}
