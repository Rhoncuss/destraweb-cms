<?php

namespace App\Filters;

use App\Libraries\ApiContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class ApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        if (! is_file(config(\Config\Destra::class)->installLock)) {
            return $this->unauthorized('DESTRA CMS is not installed yet.', 503);
        }

        if (! service('settings')->bool('api.enabled', true)) {
            return $this->unauthorized('The REST API is disabled.', 503);
        }

        $header = trim($request->getHeaderLine('Authorization'));
        $token  = '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = trim($matches[1]);
        } else {
            $token = trim($request->getHeaderLine('X-API-Key'));
        }

        if ($token === '' || ! preg_match('/^dst_[A-Za-z0-9_-]{20,}$/', $token)) {
            return $this->unauthorized('A valid API token is required.');
        }

        $hash = service('crypto')->hashToken($token);

        try {
            $row = db_connect()->table('api_tokens t')
                ->select('t.*, u.name AS user_name, u.email AS user_email, u.status AS user_status, u.deleted_at AS user_deleted, u.role_id AS user_role_id')
                ->join('users u', 'u.id = t.user_id')
                ->where('t.token_hash', $hash)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return $this->unauthorized('Unable to verify the API token.', 500);
        }

        if (! $row || $row['revoked_at'] !== null || ($row['expires_at'] !== null && strtotime($row['expires_at']) < time())) {
            return $this->unauthorized('The API token is invalid or expired.');
        }

        if ($row['user_status'] !== 'active' || $row['user_deleted'] !== null) {
            return $this->unauthorized('The token owner is not active.');
        }

        $row['abilities_array'] = $this->parseAbilities((string) ($row['abilities'] ?? ''));
        unset($row['token_hash']);

        ApiContext::set($row, [
            'id'     => (int) $row['user_id'],
            'name'   => $row['user_name'],
            'email'  => $row['user_email'],
            'role_id'=> (int) $row['user_role_id'],
        ]);

        if ($row['last_used_at'] === null || strtotime($row['last_used_at']) < time() - 300) {
            try {
                db_connect()->table('api_tokens')->where('id', $row['id'])->update(['last_used_at' => date('Y-m-d H:i:s')]);
            } catch (Throwable) {
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * @return list<string>
     */
    private function parseAbilities(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), static fn (string $s): bool => $s !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $s): bool => $s !== ''));
    }

    private function unauthorized(string $message, int $status = 401): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode($status)->setJSON([
            'success' => false,
            'error'   => [
                'code'    => $status === 401 ? 'unauthorized' : ($status === 403 ? 'forbidden' : 'unavailable'),
                'message' => $message,
            ],
        ]);

        if ($status === 401) {
            $response->setHeader('WWW-Authenticate', 'Bearer realm="DESTRA API"');
        }

        return $response;
    }
}
