<?php

namespace App\Controllers\Admin;

use Throwable;

class Orders extends BaseAdminController
{
    public const STATUSES = [
        'new'        => 'New',
        'contacted'  => 'Contacted',
        'won'        => 'Won',
        'lost'       => 'Lost',
    ];

    public function index(): string
    {
        $db     = db_connect();
        $status = (string) ($this->request->getGet('status') ?? '');
        $status = array_key_exists($status, self::STATUSES) ? $status : '';
        $query  = trim((string) ($this->request->getGet('q') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;

        $counts = ['all' => 0] + array_fill_keys(array_keys(self::STATUSES), 0);
        try {
            foreach ($db->table('orders')->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray() as $row) {
                $counts[(string) $row['status']] = (int) $row['total'];
                $counts['all'] += (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $apply = static function ($builder) use ($status, $query): void {
            if ($status !== '') {
                $builder->where('o.status', $status);
            }
            if ($query !== '') {
                $builder->groupStart()
                    ->like('o.name', $query)
                    ->orLike('o.email', $query)
                    ->orLike('o.phone', $query)
                    ->orLike('o.message', $query)
                    ->groupEnd();
            }
        };

        $base = static fn () => $db->table('orders o')
            ->join('content_translations t', 't.content_id = o.content_id AND t.language_code = ' . $db->escape(default_locale()), 'left');

        $countBuilder = $base();
        $apply($countBuilder);
        $total = (int) $countBuilder->countAllResults();

        $builder = $base()->select('o.*, t.title AS content_title');
        $apply($builder);
        $items = $builder->orderBy('o.created_at', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->render('admin/orders/index', [
            'pageTitle'    => 'Orders & enquiries',
            'pageSubtitle' => 'Requests submitted through product pages and buy buttons.',
            'navActive'    => 'orders',
            'breadcrumbs'  => [['label' => 'Orders']],
            'items'        => $items,
            'status'       => $status,
            'statuses'     => self::STATUSES,
            'counts'       => $counts,
            'query'        => $query,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function show(int $id): string
    {
        $db  = db_connect();
        $row = $db->table('orders o')
            ->select('o.*, t.title AS content_title, t.slug AS content_slug')
            ->join('content_translations t', 't.content_id = o.content_id AND t.language_code = ' . $db->escape(default_locale()), 'left')
            ->where('o.id', $id)
            ->get()
            ->getRowArray();

        if ($row === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = json_decode((string) ($row['data'] ?? ''), true);

        return $this->render('admin/orders/show', [
            'pageTitle'    => 'Order #' . $id,
            'pageSubtitle' => 'Submitted ' . date('M j, Y \a\t H:i', strtotime((string) $row['created_at'])) . '.',
            'navActive'    => 'orders',
            'breadcrumbs'  => [
                ['label' => 'Orders', 'url' => admin_url('orders')],
                ['label' => 'Order #' . $id],
            ],
            'order'    => $row,
            'payload'  => is_array($data) ? $data : [],
            'statuses' => self::STATUSES,
        ]);
    }

    public function status(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if (! array_key_exists($status, self::STATUSES)) {
            return $this->wantsJson()
                ? $this->fail('Unknown status.')
                : redirect()->back()->with('error', 'Unknown status.');
        }

        db_connect()->table('orders')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        if ($this->wantsJson()) {
            return $this->ok([], 'Order updated.');
        }

        return redirect()->back()->with('success', 'Order updated.');
    }

    public function delete(int $id)
    {
        db_connect()->table('orders')->where('id', $id)->delete();

        $this->audit('order.delete', 'Deleted order #' . $id, 'forms', 'order', $id);

        return redirect()->to(admin_url('orders'))->with('success', 'Order deleted.');
    }

    public function export()
    {
        $rows = db_connect()->table('orders')->orderBy('created_at', 'DESC')->get()->getResultArray();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Status', 'Content', 'Message', 'Created']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['id'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['status'],
                'content #' . $row['content_id'],
                $row['message'],
                $row['created_at'],
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="orders-' . date('Ymd-His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }
}
