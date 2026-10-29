<?php

namespace App\Controllers\Admin;

use Throwable;

class Newsletter extends BaseAdminController
{
    public function index(): string
    {
        $db     = db_connect();
        $status = (string) ($this->request->getGet('status') ?? '');
        $status = in_array($status, ['confirmed', 'pending', 'unsubscribed'], true) ? $status : '';
        $query  = trim((string) ($this->request->getGet('q') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 25;

        $counts = ['all' => 0, 'confirmed' => 0, 'pending' => 0, 'unsubscribed' => 0];
        try {
            foreach ($db->table('newsletter_subscribers')->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray() as $row) {
                $counts[(string) $row['status']] = (int) $row['total'];
                $counts['all'] += (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $apply = static function ($builder) use ($status, $query): void {
            if ($status !== '') {
                $builder->where('status', $status);
            }
            if ($query !== '') {
                $builder->groupStart()->like('email', $query)->orLike('name', $query)->groupEnd();
            }
        };

        $countBuilder = $db->table('newsletter_subscribers');
        $apply($countBuilder);
        $total = (int) $countBuilder->countAllResults();

        $builder = $db->table('newsletter_subscribers');
        $apply($builder);
        $items = $builder->orderBy('created_at', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->render('admin/newsletter/index', [
            'pageTitle'    => 'Newsletter',
            'pageSubtitle' => 'Grow and segment your audience, then sync with Mailchimp or Mailcoach.',
            'navActive'    => 'newsletter',
            'breadcrumbs'  => [['label' => 'Newsletter']],
            'items'        => $items,
            'status'       => $status,
            'query'        => $query,
            'counts'       => $counts,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function store()
    {
        $post  = $this->request->getPost();
        $email = filter_var(trim((string) ($post['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        if (! $email) {
            return redirect()->to(admin_url('newsletter'))->with('error', 'Enter a valid email address.');
        }

        $db       = db_connect();
        $existing = $db->table('newsletter_subscribers')->where('email', $email)->get()->getRowArray();
        $now      = date('Y-m-d H:i:s');

        if ($existing) {
            $db->table('newsletter_subscribers')->where('id', (int) $existing['id'])->update([
                'status'          => 'confirmed',
                'name'            => service('sanitizer')->text($post['name'] ?? null, 120) ?: $existing['name'],
                'confirmed_at'    => $now,
                'unsubscribed_at' => null,
                'updated_at'      => $now,
            ]);
        } else {
            $db->table('newsletter_subscribers')->insert([
                'email'        => $email,
                'name'         => service('sanitizer')->text($post['name'] ?? null, 120) ?: null,
                'status'       => 'confirmed',
                'locale'       => current_locale(),
                'confirmed_at' => $now,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $this->audit('newsletter.add', 'Added subscriber ' . $email, 'marketing', 'subscriber', (int) $db->insertID());
        $this->flushCache();

        return redirect()->to(admin_url('newsletter'))->with('success', 'Subscriber saved.');
    }

    public function status(int $id)
    {
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, ['confirmed', 'pending', 'unsubscribed'], true)) {
            return $this->fail('Unknown status.');
        }

        $now  = date('Y-m-d H:i:s');
        $data = ['status' => $status, 'updated_at' => $now];
        if ($status === 'confirmed') {
            $data['confirmed_at']    = $now;
            $data['unsubscribed_at'] = null;
        } elseif ($status === 'unsubscribed') {
            $data['unsubscribed_at'] = $now;
        }

        db_connect()->table('newsletter_subscribers')->where('id', $id)->update($data);
        $this->flushCache();

        return $this->wantsJson()
            ? $this->ok(['status' => $status], 'Subscriber updated.')
            : redirect()->back()->with('success', 'Subscriber updated.');
    }

    public function delete(int $id)
    {
        db_connect()->table('newsletter_subscribers')->where('id', $id)->delete();
        $this->audit('newsletter.delete', 'Removed subscriber #' . $id, 'marketing', 'subscriber', $id);

        return redirect()->back()->with('success', 'Subscriber removed.');
    }

    public function export()
    {
        $rows = db_connect()->table('newsletter_subscribers')->orderBy('created_at', 'DESC')->get()->getResultArray();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Email', 'Name', 'Status', 'Locale', 'Confirmed', 'Subscribed']);

        foreach ($rows as $row) {
            fputcsv($handle, [$row['email'], $row['name'], $row['status'], $row['locale'], $row['confirmed_at'], $row['created_at']]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="subscribers-' . date('Ymd-His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }
}
