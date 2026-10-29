<?php

namespace App\Controllers\Admin;

use Throwable;

class Popups extends BaseAdminController
{
    public const TRIGGERS = [
        'delay'  => 'After a delay',
        'scroll' => 'On scroll depth',
        'exit'   => 'On exit intent',
        'click'  => 'On click',
    ];

    public const FREQUENCIES = [
        'always'  => 'Every page load',
        'session' => 'Once per session',
        'day'     => 'Once per day',
        'week'    => 'Once per week',
    ];

    public const PAGES = [
        'all'      => 'Every page',
        'home'     => 'Home page only',
        'specific' => 'Specific content',
    ];

    public function index(): string
    {
        $items = db_connect()->table('popups')->orderBy('id', 'DESC')->get()->getResultArray();

        return $this->render('admin/popups/index', [
            'pageTitle'    => 'Popups',
            'pageSubtitle' => 'Timed, scroll and exit-intent overlays for announcements and lead capture.',
            'navActive'    => 'popups',
            'breadcrumbs'  => [['label' => 'Popups']],
            'items'        => $items,
            'triggers'     => self::TRIGGERS,
            'frequencies'  => self::FREQUENCIES,
            'pages'        => self::PAGES,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $popup = $this->find($id);
        if ($popup === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($popup);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function delete(int $id)
    {
        db_connect()->table('popups')->where('id', $id)->delete();

        $this->audit('popup.delete', 'Deleted popup #' . $id, 'appearance', 'popup', $id);
        $this->flushCache();

        return redirect()->to(admin_url('popups'))->with('success', 'Popup deleted.');
    }

    public function toggle(int $id)
    {
        $popup = $this->find($id);
        if ($popup === null) {
            return $this->fail('Popup not found.', 404);
        }

        $active = empty($popup['is_active']) ? 1 : 0;
        db_connect()->table('popups')->where('id', $id)->update(['is_active' => $active, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        return $this->ok(['is_active' => $active], $active ? 'Popup enabled.' : 'Popup disabled.');
    }

    private function form(?array $popup): string
    {
        return $this->render('admin/popups/form', [
            'pageTitle'    => $popup ? 'Edit popup' : 'New popup',
            'pageSubtitle' => 'Design a focused overlay and decide exactly when it appears.',
            'navActive'    => 'popups',
            'breadcrumbs'  => [
                ['label' => 'Popups', 'url' => admin_url('popups')],
                ['label' => $popup ? 'Edit popup' : 'New popup'],
            ],
            'popup'        => $popup,
            'triggers'     => self::TRIGGERS,
            'frequencies'  => self::FREQUENCIES,
            'pages'        => self::PAGES,
        ]);
    }

    private function save(?int $id)
    {
        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');

        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'An internal name is required.');
        }

        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('popups'))->with('error', 'Popup not found.');
        }

        $trigger   = (string) ($post['trigger_type'] ?? 'delay');
        $trigger   = array_key_exists($trigger, self::TRIGGERS) ? $trigger : 'delay';
        $frequency = (string) ($post['frequency'] ?? 'session');
        $frequency = array_key_exists($frequency, self::FREQUENCIES) ? $frequency : 'session';
        $pages     = (string) ($post['pages'] ?? 'all');
        $pages     = array_key_exists($pages, self::PAGES) ? $pages : 'all';

        $ids = array_values(array_filter(array_map(
            static fn ($v) => (int) trim((string) $v),
            explode(',', (string) ($post['content_ids'] ?? '')),
        )));

        $row = [
            'name'          => $name,
            'title'         => $san->text($post['title'] ?? null, 190) ?: null,
            'content'       => $san->html($post['content'] ?? ''),
            'image_id'      => (int) ($post['image_id'] ?? 0) ?: null,
            'trigger_type'  => $trigger,
            'trigger_value' => $san->int($post['trigger_value'] ?? 3, 0, 100000),
            'pages'         => $pages,
            'content_ids'   => $pages === 'specific' ? implode(',', array_slice($ids, 0, 50)) : null,
            'frequency'     => $frequency,
            'starts_at'     => $san->date($post['starts_at'] ?? null, true),
            'ends_at'       => $san->date($post['ends_at'] ?? null, true),
            'is_active'     => isset($post['is_active']) ? 1 : 0,
            'updated_at'    => $now,
        ];

        if ($row['content'] === '') {
            return redirect()->back()->withInput()->with('error', 'Add some popup content before saving.');
        }

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('popups')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('popups')->where('id', $id)->update($row);
        }

        $this->audit($existing ? 'popup.update' : 'popup.create', ($existing ? 'Updated' : 'Created') . ' popup "' . $name . '"', 'appearance', 'popup', $id);
        $this->flushCache();

        return redirect()->to(admin_url('popups'))->with('success', 'Popup saved.');
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('popups')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        return $row ?: null;
    }
}
