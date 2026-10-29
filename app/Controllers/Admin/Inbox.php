<?php

namespace App\Controllers\Admin;

use Throwable;

class Inbox extends BaseAdminController
{
    public function index(): string
    {
        $db     = db_connect();
        $formId = (int) ($this->request->getGet('form') ?? 0);
        $unread = (string) ($this->request->getGet('unread') ?? '') === '1';
        $query  = trim((string) ($this->request->getGet('q') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;

        try {
            $forms = $db->table('forms')->select('id, name, submissions_count')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            $forms = [];
        }

        $apply = static function ($builder) use ($formId, $unread, $query): void {
            if ($formId > 0) {
                $builder->where('s.form_id', $formId);
            }
            if ($unread) {
                $builder->where('s.is_read', 0);
            }
            if ($query !== '') {
                $builder->like('s.data', $query);
            }
        };

        $base = static fn () => $db->table('form_submissions s')
            ->join('forms f', 'f.id = s.form_id', 'left');

        $countBuilder = $base();
        $apply($countBuilder);
        $total = (int) $countBuilder->countAllResults();

        $builder = $base()->select('s.*, f.name AS form_name, f.slug AS form_slug');
        $apply($builder);
        $items = $builder->orderBy('s.created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        foreach ($items as &$item) {
            $decoded = json_decode((string) ($item['data'] ?? ''), true);
            $item['payload'] = is_array($decoded) ? $decoded : [];
            $item['preview'] = $this->preview($item['payload']);
        }
        unset($item);

        $pages = max(1, (int) ceil($total / $perPage));

        return $this->render('admin/inbox/index', [
            'pageTitle'    => 'Inbox',
            'pageSubtitle' => 'Every form submission in one place, with read state and quick actions.',
            'navActive'    => 'inbox',
            'breadcrumbs'  => [['label' => 'Inbox']],
            'items'        => $items,
            'forms'        => $forms,
            'formId'       => $formId,
            'unreadOnly'   => $unread,
            'query'        => $query,
            'total'        => $total,
            'page'         => $page,
            'pages'        => $pages,
        ]);
    }

    public function show(int $id): string
    {
        $row = db_connect()->table('form_submissions s')
            ->select('s.*, f.name AS form_name, f.fields AS form_fields, f.email_to')
            ->join('forms f', 'f.id = s.form_id', 'left')
            ->where('s.id', $id)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (empty($row['is_read'])) {
            db_connect()->table('form_submissions')->where('id', $id)->update(['is_read' => 1, 'status' => 'read']);
        }

        $payload = json_decode((string) ($row['data'] ?? ''), true);
        $schema  = json_decode((string) ($row['form_fields'] ?? ''), true);
        $labels  = [];
        foreach ((array) $schema as $field) {
            if (is_array($field)) {
                $labels[(string) ($field['key'] ?? '')] = (string) ($field['label'] ?? '');
            }
        }

        return $this->render('admin/inbox/show', [
            'pageTitle'    => 'Submission #' . $id,
            'pageSubtitle' => 'Received ' . date('M j, Y \a\t H:i', strtotime((string) $row['created_at'])) . '.',
            'navActive'    => 'inbox',
            'breadcrumbs'  => [
                ['label' => 'Inbox', 'url' => admin_url('inbox')],
                ['label' => 'Submission #' . $id],
            ],
            'submission' => $row,
            'payload'    => is_array($payload) ? $payload : [],
            'labels'     => $labels,
        ]);
    }

    public function status(int $id)
    {
        $value = (string) $this->request->getPost('status');
        $read  = in_array($value, ['read', 'unread'], true) ? $value === 'read' : true;

        db_connect()->table('form_submissions')->where('id', $id)->update(['is_read' => $read ? 1 : 0, 'status' => $read ? 'read' : 'new']);

        if ($this->wantsJson()) {
            return $this->ok(['is_read' => $read], $read ? 'Marked as read.' : 'Marked as unread.');
        }

        return redirect()->back()->with('success', $read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function bulk()
    {
        $ids    = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        $action = (string) $this->request->getPost('action');
        if ($ids === []) {
            return redirect()->back()->with('error', 'Select at least one submission.');
        }

        $db = db_connect();
        if (in_array($action, ['read', 'unread'], true)) {
            $db->table('form_submissions')->whereIn('id', $ids)->update([
                'is_read' => $action === 'read' ? 1 : 0,
                'status'  => $action === 'read' ? 'read' : 'new',
            ]);
        } elseif ($action === 'delete') {
            $db->table('form_submissions')->whereIn('id', $ids)->delete();
        } else {
            return redirect()->back()->with('error', 'Unknown bulk action.');
        }

        $this->audit('inbox.bulk', 'Inbox action "' . $action . '" on ' . count($ids) . ' submission(s)', 'forms', 'submission', $ids[0]);
        $this->flushCache();

        return redirect()->back()->with('success', 'Submissions updated.');
    }

    public function delete(int $id)
    {
        db_connect()->table('form_submissions')->where('id', $id)->delete();

        $this->audit('inbox.delete', 'Deleted submission #' . $id, 'forms', 'submission', $id);
        $this->flushCache();

        return redirect()->to(admin_url('inbox'))->with('success', 'Submission deleted.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function preview(array $payload): string
    {
        $parts = [];
        foreach ($payload as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $parts[] = trim((string) $value);
            }
            if (count($parts) >= 2) {
                break;
            }
        }

        return implode(' · ', $parts);
    }
}
