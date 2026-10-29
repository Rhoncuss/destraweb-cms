<?php

namespace App\Controllers\Admin;

use Throwable;

class Dashboard extends BaseAdminController
{
    public function index(): string
    {
        $stats = service('stats');

        $hour     = (int) date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $latest = [];
        try {
            $latest = service('contentService')->query([
                'preview'  => true,
                'per_page' => 6,
            ])['items'];
        } catch (Throwable) {
        }

        return $this->render('admin/dashboard', [
            'pageTitle'    => $greeting . ', ' . explode(' ', (string) ($this->admin['name'] ?? 'Admin'))[0],
            'pageSubtitle' => 'Here is what is happening across ' . setting('general.site_name', 'your site') . ' today.',
            'navActive'    => 'dashboard',
            'overview'     => $stats->overview(),
            'daily'        => $stats->dailyViews(14),
            'popular'      => $stats->popularContents(5),
            'activity'     => $stats->recentActivity(7),
            'latest'       => $latest,
            'health'       => $this->systemHealth(),
        ]);
    }

    public function search(): string
    {
        $query = trim((string) $this->request->getGet('q'));
        $contents = [];
        $users = [];
        $media = [];

        if ($query !== '' && mb_strlen($query) >= 2) {
            try {
                $contents = service('contentService')->query([
                    'q'        => $query,
                    'preview'  => true,
                    'per_page' => 12,
                ])['items'];
            } catch (Throwable) {
            }

            try {
                $users = db_connect()->table('users')
                    ->groupStart()->like('name', $query)->orLike('email', $query)->orLike('username', $query)->groupEnd()
                    ->where('deleted_at', null)
                    ->limit(8)
                    ->get()
                    ->getResultArray();
            } catch (Throwable) {
            }

            try {
                $media = db_connect()->table('media')
                    ->like('original_name', $query)
                    ->where('deleted_at', null)
                    ->limit(12)
                    ->get()
                    ->getResultArray();
            } catch (Throwable) {
            }
        }

        return $this->render('admin/search', [
            'pageTitle' => 'Search results',
            'navActive' => '',
            'query'     => $query,
            'contents'  => $contents,
            'users'     => $users,
            'media'     => $media,
        ]);
    }

    /**
     * @return list<array{label: string, value: string, ok: bool}>
     */
    private function systemHealth(): array
    {
        $dbVersion = 'Unknown';
        try {
            $dbVersion = 'MariaDB/MySQL ' . db_connect()->getVersion();
        } catch (Throwable) {
        }

        $cacheDriver = 'File';
        try {
            $cacheDriver = (string) config('Cache')->handler;
        } catch (Throwable) {
        }

        $uploadWritable = is_writable(config(\Config\Destra::class)->uploadsPath);
        $lockInstalled  = is_file(config(\Config\Destra::class)->installLock);

        return [
            ['label' => 'PHP', 'value' => PHP_VERSION, 'ok' => PHP_VERSION_ID >= 80100],
            ['label' => 'Database', 'value' => $dbVersion, 'ok' => $dbVersion !== 'Unknown'],
            ['label' => 'Cache', 'value' => $cacheDriver, 'ok' => true],
            ['label' => 'Uploads', 'value' => $uploadWritable ? 'Writable' : 'Not writable', 'ok' => $uploadWritable],
            ['label' => 'Installer lock', 'value' => $lockInstalled ? 'Locked' : 'Open', 'ok' => $lockInstalled],
            ['label' => 'Environment', 'value' => ENVIRONMENT, 'ok' => ENVIRONMENT === 'production'],
        ];
    }
}
