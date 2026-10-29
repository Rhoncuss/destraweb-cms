<?php

namespace App\Controllers\Admin;

use Throwable;

class ApiTokens extends BaseAdminController
{
    public const SCOPES = [
        'content:read'    => 'Read posts, pages and taxonomy',
        'content:write'   => 'Create and update content',
        'content:publish' => 'Publish or schedule content',
        'media:read'      => 'Browse the media library',
        'media:write'     => 'Upload and delete media',
        'comments:read'   => 'Read comments',
        'comments:write'  => 'Moderate comments',
        'orders:read'     => 'Read orders and submissions',
        'newsletter:read' => 'Read newsletter subscribers',
        'stats:read'      => 'Read traffic and analytics figures',
    ];

    public function index(): string
    {
        $db   = db_connect();
        $user = $this->admin;
        $isSuper = (int) ($user['role_id'] ?? 0) === 1;

        $builder = $db->table('api_tokens t')
            ->select('t.*, u.name AS user_name, u.email AS user_email')
            ->join('users u', 'u.id = t.user_id', 'left')
            ->orderBy('t.id', 'DESC');

        if (! $isSuper) {
            $builder->where('t.user_id', (int) ($user['id'] ?? 0));
        }

        $items = $builder->get()->getResultArray();

        foreach ($items as &$item) {
            $item['abilities_list'] = $this->decodeAbilities((string) ($item['abilities'] ?? ''));
        }
        unset($item);

        $active  = 0;
        $revoked = 0;
        $expired = 0;
        $now     = time();

        foreach ($items as $item) {
            if ($item['revoked_at'] !== null && $item['revoked_at'] !== '') {
                $revoked++;
            } elseif ($item['expires_at'] !== null && $item['expires_at'] !== '' && strtotime((string) $item['expires_at']) < $now) {
                $expired++;
            } else {
                $active++;
            }
        }

        return $this->render('admin/api_tokens/index', [
            'pageTitle'    => 'API tokens',
            'pageSubtitle' => 'Issue personal access tokens for the DESTRA REST API.',
            'navActive'    => 'api',
            'breadcrumbs'  => [['label' => 'API tokens']],
            'items'        => $items,
            'scopes'       => self::SCOPES,
            'activeCount'  => $active,
            'revokedCount' => $revoked,
            'expiredCount' => $expired,
            'isSuper'      => $isSuper,
            'apiBase'      => base_url('api/v1'),
        ]);
    }

    public function store()
    {
        $db  = db_connect();
        $san = service('sanitizer');

        $name = mb_substr($san->text($this->request->getPost('name')), 0, 100);
        if ($name === '') {
            return $this->fail('Give the token a name so you can recognise it later.');
        }

        $requested = (array) ($this->request->getPost('abilities') ?? []);
        $abilities = [];
        foreach ($requested as $scope) {
            $scope = (string) $scope;
            if (isset(self::SCOPES[$scope])) {
                $abilities[] = $scope;
            }
        }
        if ($abilities === []) {
            return $this->fail('Select at least one permission for this token.');
        }

        $days = (int) $this->request->getPost('expires_in');
        $expiresAt = in_array($days, [30, 90, 365], true)
            ? date('Y-m-d H:i:s', time() + $days * 86400)
            : null;

        $plain = 'dst_' . bin2hex(random_bytes(24));

        $db->table('api_tokens')->insert([
            'user_id'    => (int) ($this->admin['id'] ?? 0),
            'name'       => $name,
            'token_hash' => service('crypto')->hashToken($plain),
            'prefix'     => substr($plain, 0, 12),
            'abilities'  => json_encode($abilities, JSON_UNESCAPED_SLASHES),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $id = (int) $db->insertID();
        $this->audit('api.token.create', 'Created API token "' . $name . '"', 'api', 'api_token', $id, ['abilities' => $abilities]);

        return $this->ok([
            'id'        => $id,
            'name'      => $name,
            'token'     => $plain,
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ], 'Token created. Copy it now — it will not be shown again.');
    }

    public function revoke(int $id)
    {
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Token not found.', 404);
        }
        if (! $this->canManage($row)) {
            return $this->fail('You can only manage your own tokens.', 403);
        }

        if ($row['revoked_at'] !== null && $row['revoked_at'] !== '') {
            return $this->fail('This token is already revoked.');
        }

        db_connect()->table('api_tokens')->where('id', $id)->update([
            'revoked_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit('api.token.revoke', 'Revoked API token "' . $row['name'] . '"', 'api', 'api_token', $id);

        return $this->ok([], 'Token revoked.');
    }

    public function delete(int $id)
    {
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Token not found.', 404);
        }
        if (! $this->canManage($row)) {
            return $this->fail('You can only manage your own tokens.', 403);
        }

        db_connect()->table('api_tokens')->where('id', $id)->delete();
        $this->audit('api.token.delete', 'Deleted API token "' . $row['name'] . '"', 'api', 'api_token', $id);

        return $this->ok([], 'Token deleted.');
    }

    /**
     * @return list<string>
     */
    private function decodeAbilities(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), static fn (string $s): bool => isset(self::SCOPES[$s])));
        }

        $parts = array_map('trim', explode(',', $raw));

        return array_values(array_filter($parts, static fn (string $s): bool => $s !== '' && isset(self::SCOPES[$s])));
    }

    private function canManage(array $row): bool
    {
        return (int) ($this->admin['role_id'] ?? 0) === 1
            || (int) $row['user_id'] === (int) ($this->admin['id'] ?? 0);
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('api_tokens')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }
}
