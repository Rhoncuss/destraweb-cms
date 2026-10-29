<?php

namespace App\Controllers\Admin;

use Throwable;

class Notifications extends BaseAdminController
{
    private const ICONS = [
        'order'      => 'fa-solid fa-bag-shopping',
        'comment'    => 'fa-regular fa-comment',
        'user'       => 'fa-solid fa-user-plus',
        'newsletter' => 'fa-regular fa-envelope',
        'form'       => 'fa-regular fa-rectangle-list',
        'security'   => 'fa-solid fa-shield-halved',
        'plugin'     => 'fa-solid fa-plug',
        'system'     => 'fa-solid fa-gear',
        'info'       => 'fa-solid fa-circle-info',
    ];

    public function index(): string
    {
        $db     = db_connect();
        $userId = (int) ($this->admin['id'] ?? 0);
        $filter = (string) ($this->request->getGet('filter') ?? 'all');
        if (! in_array($filter, ['all', 'unread', 'read'], true)) {
            $filter = 'all';
        }

        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 25;

        $builder = $db->table('notifications')->where('user_id', $userId)->orderBy('id', 'DESC');
        if ($filter === 'unread') {
            $builder->where('is_read', 0);
        } elseif ($filter === 'read') {
            $builder->where('is_read', 1);
        }

        $total = (int) $builder->countAllResults(false);
        $items = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        $unread = (int) $db->table('notifications')->where('user_id', $userId)->where('is_read', 0)->countAllResults();
        $all    = (int) $db->table('notifications')->where('user_id', $userId)->countAllResults();

        foreach ($items as &$item) {
            $item['icon_class'] = $this->iconFor($item);
        }
        unset($item);

        return $this->render('admin/notifications/index', [
            'pageTitle'    => 'Notifications',
            'pageSubtitle' => 'Everything that needs your attention, in one inbox.',
            'navActive'    => 'notifications',
            'breadcrumbs'  => [['label' => 'Notifications']],
            'items'        => $items,
            'filter'       => $filter,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'unreadCount'  => $unread,
            'allCount'     => $all,
        ]);
    }

    public function open(int $id)
    {
        $row = $this->findOwned($id);
        if ($row === null) {
            return $this->fail('Notification not found.', 404);
        }

        db_connect()->table('notifications')->where('id', $id)->update(['is_read' => 1]);

        $url = trim((string) ($row['url'] ?? ''));

        if ($url === '') {
            $url = admin_url('notifications');
        } elseif (! preg_match('#^https?://#i', $url)) {
            $url = base_url(ltrim($url, '/'));
        }

        return $this->ok(['url' => $url]);
    }

    public function read(int $id)
    {
        $row = $this->findOwned($id);
        if ($row === null) {
            return $this->fail('Notification not found.', 404);
        }

        db_connect()->table('notifications')->where('id', $id)->update(['is_read' => 1]);

        return $this->ok([], 'Marked as read.');
    }

    public function readAll()
    {
        $userId = (int) ($this->admin['id'] ?? 0);
        db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 0)->update(['is_read' => 1]);

        return $this->ok([], 'All notifications marked as read.');
    }

    public function delete(int $id)
    {
        $row = $this->findOwned($id);
        if ($row === null) {
            return $this->fail('Notification not found.', 404);
        }

        db_connect()->table('notifications')->where('id', $id)->delete();

        return $this->ok([], 'Notification deleted.');
    }

    public function clearRead()
    {
        $userId = (int) ($this->admin['id'] ?? 0);
        $deleted = db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 1)->delete();

        return $this->ok(['deleted' => (int) $deleted], 'Cleared ' . (int) $deleted . ' read notifications.');
    }

    private function iconFor(array $row): string
    {
        $icon = trim((string) ($row['icon'] ?? ''));
        if ($icon !== '' && str_starts_with($icon, 'fa-')) {
            return $icon;
        }

        return self::ICONS[(string) ($row['type'] ?? 'info')] ?? self::ICONS['info'];
    }

    private function findOwned(int $id): ?array
    {
        try {
            $row = db_connect()->table('notifications')
                ->where('id', $id)
                ->where('user_id', (int) ($this->admin['id'] ?? 0))
                ->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }
}
