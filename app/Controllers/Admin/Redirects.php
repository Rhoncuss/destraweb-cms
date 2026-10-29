<?php

namespace App\Controllers\Admin;

use Throwable;

class Redirects extends BaseAdminController
{
    private const CODES = [301 => '301 — Permanent', 302 => '302 — Temporary', 307 => '307 — Temporary (method preserved)', 308 => '308 — Permanent (method preserved)'];

    public function index(): string
    {
        $db      = db_connect();
        $q       = trim((string) $this->request->getGet('q'));
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 25;

        $builder = $db->table('redirects');
        if ($q !== '') {
            $builder->groupStart()->like('from_path', $q)->orLike('to_path', $q)->groupEnd();
        }

        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy('id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        $active = (int) $db->table('redirects')->where('is_active', 1)->countAllResults();
        $hits   = (int) ($db->table('redirects')->selectSum('hits')->get()->getRow()->hits ?? 0);

        return $this->render('admin/redirects/index', [
            'pageTitle'    => 'Redirects',
            'pageSubtitle' => 'Keep old links alive with precise, permanent or temporary redirects.',
            'navActive'    => 'redirects',
            'breadcrumbs'  => [['label' => 'Redirects']],
            'items'        => $items,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'q'            => $q,
            'activeCount'  => $active,
            'hitCount'     => $hits,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $item = $this->find($id);
        if ($item === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($item);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function toggle(int $id)
    {
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Redirect not found.', 404);
        }

        db_connect()->table('redirects')->where('id', $id)->update([
            'is_active'  => (int) $row['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->flushCache();
        $this->audit('redirect.toggle', 'Toggled redirect ' . $row['from_path'], 'redirects', 'redirect', $id);

        return $this->ok(['active' => (int) $row['is_active'] === 1 ? 0 : 1], 'Redirect updated.');
    }

    public function delete(int $id)
    {
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Redirect not found.', 404);
        }

        db_connect()->table('redirects')->where('id', $id)->delete();
        $this->flushCache();
        $this->audit('redirect.delete', 'Deleted redirect ' . $row['from_path'], 'redirects', 'redirect', $id);

        return $this->ok([], 'Redirect deleted.');
    }

    private function form(?array $item): string
    {
        return $this->render('admin/redirects/form', [
            'pageTitle'    => $item ? 'Edit redirect' : 'Add redirect',
            'pageSubtitle' => $item ? 'Adjust where ' . $item['from_path'] . ' points.' : 'Send a retired URL to its replacement.',
            'navActive'    => 'redirects',
            'breadcrumbs'  => [['label' => 'Redirects', 'url' => admin_url('redirects')], ['label' => $item ? 'Edit' : 'New redirect']],
            'item'         => $item,
            'codes'        => self::CODES,
        ]);
    }

    private function save(?int $id)
    {
        $db  = db_connect();
        $san = service('sanitizer');

        $from = $this->normalisePath($san->url($this->request->getPost('from_path')));
        $to   = $san->url($this->request->getPost('to_path'));
        $code = (int) $this->request->getPost('status_code');

        if ($from === '' || $to === '') {
            return redirect()->back()->withInput()->with('error', 'Both the source path and the destination are required.');
        }

        if ($from === $to) {
            return redirect()->back()->withInput()->with('error', 'A redirect cannot point to itself.');
        }

        if (! isset(self::CODES[$code])) {
            $code = 301;
        }

        $duplicate = $db->table('redirects')->where('from_path', $from)->where('id !=', (int) $id)->countAllResults();
        if ($duplicate > 0) {
            return redirect()->back()->withInput()->with('error', 'A redirect for ' . $from . ' already exists.');
        }

        $data = [
            'from_path'   => mb_substr($from, 0, 500),
            'to_path'     => mb_substr($to, 0, 500),
            'status_code' => $code,
            'is_active'   => $this->request->getPost('is_active') ? 1 : 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['hits']       = 0;
            $db->table('redirects')->insert($data);
            $id = (int) $db->insertID();
            $this->audit('redirect.create', 'Created redirect ' . $from . ' → ' . $to, 'redirects', 'redirect', $id);
            $message = 'Redirect created.';
        } else {
            $db->table('redirects')->where('id', $id)->update($data);
            $this->audit('redirect.update', 'Updated redirect ' . $from . ' → ' . $to, 'redirects', 'redirect', $id);
            $message = 'Redirect updated.';
        }

        $this->flushCache();

        return redirect()->to(admin_url('redirects'))->with('success', $message);
    }

    private function normalisePath(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $parts = parse_url($value);
        if ($parts === false) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '') {
            return '';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return preg_replace('#/+#', '/', $path) ?: '';
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('redirects')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }
}
