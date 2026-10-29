<?php

namespace App\Controllers\Admin;

use Throwable;

class Comments extends BaseAdminController
{
    private const STATUSES = ['pending', 'approved', 'spam', 'trash'];

    public function index(): string
    {
        $db      = db_connect();
        $status  = (string) ($this->request->getGet('status') ?? 'pending');
        $status  = in_array($status, [...self::STATUSES, 'all'], true) ? $status : 'pending';
        $query   = trim((string) ($this->request->getGet('q') ?? ''));
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;

        $counts = ['pending' => 0, 'approved' => 0, 'spam' => 0, 'trash' => 0, 'all' => 0];
        try {
            foreach ($db->table('comments')->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray() as $row) {
                $key = (string) $row['status'];
                if (array_key_exists($key, $counts)) {
                    $counts[$key] = (int) $row['total'];
                }
                $counts['all'] += (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $apply = static function ($builder) use ($status, $query): void {
            if ($status === 'trash') {
                $builder->where('c.deleted_at IS NOT NULL', null, false);
            } else {
                $builder->where('c.deleted_at', null);
            }
            if ($status !== 'all' && $status !== 'trash') {
                $builder->where('c.status', $status);
            }
            if ($query !== '') {
                $builder->groupStart()
                    ->like('c.author_name', $query)
                    ->orLike('c.author_email', $query)
                    ->orLike('c.body', $query)
                    ->groupEnd();
            }
        };

        $base = static fn () => $db->table('comments c')
            ->join('content_translations t', 't.content_id = c.content_id AND t.language_code = ' . $db->escape(default_locale()), 'left');

        $countBuilder = $base();
        $apply($countBuilder);
        $total = (int) $countBuilder->countAllResults();

        $builder = $base()->select('c.*, t.title AS content_title, t.slug AS content_slug');
        $apply($builder);
        $items = $builder->orderBy('c.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $pages = max(1, (int) ceil($total / $perPage));

        return $this->render('admin/comments/index', [
            'pageTitle'    => 'Comments',
            'pageSubtitle' => 'Review, approve and reply to reader discussions.',
            'navActive'    => 'comments',
            'breadcrumbs'  => [['label' => 'Comments']],
            'items'        => $items,
            'status'       => $status,
            'query'        => $query,
            'counts'       => $counts,
            'total'        => $total,
            'page'         => $page,
            'pages'        => $pages,
        ]);
    }

    public function moderate()
    {
        $ids    = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        $action = (string) $this->request->getPost('action');

        if ($ids === []) {
            return $this->wantsJson()
                ? $this->fail('Select at least one comment.')
                : redirect()->back()->with('error', 'Select at least one comment.');
        }

        $affected = $this->applyAction($ids, $action);
        if ($affected === 0) {
            return $this->wantsJson()
                ? $this->fail('That action is not recognised.')
                : redirect()->back()->with('error', 'That action is not recognised.');
        }

        $this->audit('comment.moderate', 'Comments "' . $action . '" applied to ' . $affected . ' item(s)', 'engagement', 'comment', $ids[0]);
        $this->flushCache();

        $message = $affected . ' comment(s) updated.';

        return $this->wantsJson()
            ? $this->ok([], $message)
            : redirect()->back()->with('success', $message);
    }

    public function status(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if ($this->applyAction([$id], $status) === 0) {
            return $this->fail('Unknown status.');
        }

        $this->flushCache();

        return $this->ok([], 'Comment marked as ' . $status . '.');
    }

    public function reply(int $id)
    {
        $comment = db_connect()->table('comments')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if ($comment === null) {
            return redirect()->back()->with('error', 'Comment not found.');
        }

        $body = service('sanitizer')->textarea($this->request->getPost('body') ?? '', 4000);
        if ($body === '') {
            return redirect()->back()->with('error', 'Write a reply before sending.');
        }

        $now = date('Y-m-d H:i:s');
        db_connect()->table('comments')->insert([
            'content_id'    => (int) $comment['content_id'],
            'parent_id'     => $id,
            'user_id'       => (int) ($this->admin['id'] ?? 0) ?: null,
            'author_name'   => (string) ($this->admin['name'] ?? 'Administrator'),
            'author_email'  => (string) ($this->admin['email'] ?? ''),
            'body'          => $body,
            'status'        => 'approved',
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $this->audit('comment.reply', 'Replied to comment #' . $id, 'engagement', 'comment', $id);
        $this->flushCache();

        return redirect()->back()->with('success', 'Reply published.');
    }

    public function delete(int $id)
    {
        $db = db_connect();
        $db->table('comments')->where('parent_id', $id)->update(['parent_id' => null]);
        $db->table('comments')->where('id', $id)->delete();

        $this->audit('comment.delete', 'Deleted comment #' . $id, 'engagement', 'comment', $id);
        $this->flushCache();

        return redirect()->back()->with('success', 'Comment deleted permanently.');
    }

    /**
     * @param list<int> $ids
     */
    private function applyAction(array $ids, string $action): int
    {
        if ($ids === []) {
            return 0;
        }

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        switch ($action) {
            case 'approve':
            case 'approved':
                $db->table('comments')->whereIn('id', $ids)->update(['status' => 'approved', 'deleted_at' => null, 'updated_at' => $now]);

                return $db->affectedRows();

            case 'pending':
                $db->table('comments')->whereIn('id', $ids)->update(['status' => 'pending', 'deleted_at' => null, 'updated_at' => $now]);

                return $db->affectedRows();

            case 'spam':
                $db->table('comments')->whereIn('id', $ids)->update(['status' => 'spam', 'deleted_at' => null, 'updated_at' => $now]);

                return $db->affectedRows();

            case 'trash':
                $db->table('comments')->whereIn('id', $ids)->update(['status' => 'trash', 'deleted_at' => $now, 'updated_at' => $now]);

                return $db->affectedRows();

            case 'restore':
                $db->table('comments')->whereIn('id', $ids)->update(['status' => 'pending', 'deleted_at' => null, 'updated_at' => $now]);

                return $db->affectedRows();

            case 'delete':
                $db->table('comments')->whereIn('parent_id', $ids)->update(['parent_id' => null]);
                $db->table('comments')->whereIn('id', $ids)->delete();

                return $db->affectedRows();
        }

        return 0;
    }
}
