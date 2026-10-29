<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class InsightsApi extends BaseApiController
{
    private const COMMENT_STATUSES = ['pending', 'approved', 'spam', 'trash'];

    public function comments(): ResponseInterface
    {
        if (($guard = $this->guard('comments:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $locale = $this->localeParam();
        $status = (string) ($this->request->getGet('status') ?: 'all');
        if ($status !== 'all' && ! in_array($status, self::COMMENT_STATUSES, true)) {
            return $this->fail('Invalid comment status filter.', 422, 'validation_failed');
        }

        $builder = $db->table('comments cm')
            ->select('cm.*, ct.title AS content_title, ct.slug AS content_slug')
            ->join('content_translations ct', 'ct.content_id = cm.content_id AND ct.language_code = ' . $db->escape($locale), 'left')
            ->where('cm.deleted_at', null);

        if ($status !== 'all') {
            $builder->where('cm.status', $status);
        }

        $contentId = (int) $this->request->getGet('content_id');
        if ($contentId > 0) {
            $builder->where('cm.content_id', $contentId);
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('cm.id', 'DESC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = array_map(static fn (array $row): array => [
            'id'            => (int) $row['id'],
            'content_id'    => (int) $row['content_id'],
            'content_title' => (string) ($row['content_title'] ?? ''),
            'content_slug'  => (string) ($row['content_slug'] ?? ''),
            'author_name'   => (string) $row['author_name'],
            'author_email'  => (string) $row['author_email'],
            'body'          => (string) $row['body'],
            'status'        => (string) $row['status'],
            'parent_id'     => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            'created_at'    => $row['created_at'],
        ], $rows);

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function moderate(int $id): ResponseInterface
    {
        if (($guard = $this->guard('comments:write')) !== null) {
            return $guard;
        }

        $status = (string) $this->input('status', '');
        if (! in_array($status, self::COMMENT_STATUSES, true)) {
            return $this->fail('Status must be one of: ' . implode(', ', self::COMMENT_STATUSES) . '.', 422, 'validation_failed');
        }

        $db  = db_connect();
        $row = $db->table('comments')->where('id', $id)->get()->getRowArray();
        if (! $row) {
            return $this->fail('Comment not found.', 404, 'not_found');
        }

        $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'trash') {
            $update['deleted_at'] = date('Y-m-d H:i:s');
        } else {
            $update['deleted_at'] = null;
        }

        $db->table('comments')->where('id', $id)->update($update);
        $this->audit('comments.moderate', 'REST API set comment #' . $id . ' to ' . $status, 'comments', 'comment', $id);

        return $this->ok(['id' => $id, 'status' => $status]);
    }

    public function orders(): ResponseInterface
    {
        if (($guard = $this->guard('orders:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $locale = $this->localeParam();
        $status = (string) ($this->request->getGet('status') ?: 'all');

        $builder = $db->table('orders o')
            ->select('o.*, ct.title AS content_title')
            ->join('content_translations ct', 'ct.content_id = o.content_id AND ct.language_code = ' . $db->escape($locale), 'left');

        if ($status !== 'all') {
            $builder->where('o.status', preg_replace('/[^a-z_]/', '', $status));
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('o.id', 'DESC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = array_map(static function (array $row): array {
            $data = json_decode((string) ($row['data'] ?? ''), true);

            return [
                'id'           => (int) $row['id'],
                'content_id'   => (int) $row['content_id'],
                'content_title'=> (string) ($row['content_title'] ?? ''),
                'name'         => (string) $row['name'],
                'email'        => (string) $row['email'],
                'phone'        => $row['phone'] !== null ? (string) $row['phone'] : null,
                'message'      => $row['message'] !== null ? (string) $row['message'] : null,
                'fields'       => is_array($data) ? $data : [],
                'status'       => (string) $row['status'],
                'created_at'   => $row['created_at'],
            ];
        }, $rows);

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function subscribers(): ResponseInterface
    {
        if (($guard = $this->guard('newsletter:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $status = (string) ($this->request->getGet('status') ?: 'all');
        $builder = $db->table('newsletter_subscribers');

        if ($status !== 'all') {
            $builder->where('status', preg_replace('/[^a-z_]/', '', $status));
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('id', 'DESC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = array_map(static fn (array $row): array => [
            'id'            => (int) $row['id'],
            'email'         => (string) $row['email'],
            'name'          => $row['name'] !== null ? (string) $row['name'] : null,
            'status'        => (string) $row['status'],
            'locale'        => $row['locale'] !== null ? (string) $row['locale'] : null,
            'confirmed_at'  => $row['confirmed_at'],
            'created_at'    => $row['created_at'],
        ], $rows);

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function stats(): ResponseInterface
    {
        if (($guard = $this->guard('stats:read')) !== null) {
            return $guard;
        }

        $overview = service('stats')->overview();
        $days     = max(7, min(90, (int) ($this->request->getGet('days') ?? 30)));

        $data = [
            'overview' => $overview,
            'daily'    => service('stats')->dailyViews($days),
            'popular'  => service('stats')->popularContents(10),
            'locale'   => default_locale(),
            'generated_at' => date('c'),
        ];

        return $this->ok($data);
    }

    private function localeParam(): string
    {
        $locale = (string) ($this->request->getGet('lang') ?? '');
        $locale = preg_replace('/[^a-z0-9_-]/i', '', $locale);
        if ($locale !== '' && service('localization')->exists($locale)) {
            return $locale;
        }

        return default_locale();
    }

    private function audit(string $action, string $description, string $module, string $entityType, int $entityId): void
    {
        try {
            db_connect()->table('audit_logs')->insert([
                'user_id'     => \App\Libraries\ApiContext::userId(),
                'action'      => $action,
                'description' => $description,
                'module'      => $module,
                'subject_type'=> $entityType,
                'subject_id'  => $entityId,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }
}
