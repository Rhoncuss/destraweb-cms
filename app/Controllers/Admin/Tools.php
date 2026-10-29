<?php

namespace App\Controllers\Admin;

use Throwable;

class Tools extends BaseAdminController
{
    public function index(): string
    {
        $tab = (string) ($this->request->getGet('tab') ?? 'overview');
        if (! in_array($tab, ['overview', 'audit', 'security'], true)) {
            $tab = 'overview';
        }

        $db      = db_connect();
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 30;

        $data = [
            'pageTitle'    => 'Tools & cache',
            'pageSubtitle' => 'Housekeeping, diagnostics and a complete audit trail.',
            'navActive'    => 'tools',
            'breadcrumbs'  => [['label' => 'Tools & cache']],
            'tab'          => $tab,
            'system'       => $this->systemInfo($db),
            'cache'        => $this->cacheInfo(),
            'maintenance'  => service('settings')->bool('general.maintenance_mode', false),
            'maintenanceIps' => (string) service('settings')->get('general.maintenance_ips', ''),
            'audit'        => ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1],
            'modules'      => [],
            'users'        => [],
            'filters'      => ['q' => '', 'module' => '', 'user' => '', 'from' => '', 'to' => ''],
            'loginAttempts' => [],
            'failed24h'    => 0,
        ];

        if ($tab === 'audit') {
            $filters = [
                'q'      => trim((string) $this->request->getGet('q')),
                'module' => trim((string) $this->request->getGet('module')),
                'user'   => (int) ($this->request->getGet('user') ?? 0),
                'from'   => trim((string) $this->request->getGet('from')),
                'to'     => trim((string) $this->request->getGet('to')),
            ];

            $builder = $db->table('audit_logs a')
                ->select('a.*, u.name AS user_name, u.email AS user_email')
                ->join('users u', 'u.id = a.user_id', 'left');

            $this->applyAuditFilters($builder, $filters);

            $total = (int) $builder->countAllResults(false);
            $items = $builder->orderBy('a.id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

            $modules = [];
            try {
                foreach ($db->table('audit_logs')->select('module')->distinct()->orderBy('module', 'ASC')->get()->getResultArray() as $row) {
                    $modules[] = (string) $row['module'];
                }
            } catch (Throwable) {
            }

            $users = [];
            try {
                foreach ($db->table('users')->select('id, name')->where('deleted_at', null)->orderBy('name', 'ASC')->get()->getResultArray() as $row) {
                    $users[(int) $row['id']] = (string) $row['name'];
                }
            } catch (Throwable) {
            }

            $data['audit']   = ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage))];
            $data['modules'] = $modules;
            $data['users']   = $users;
            $data['filters'] = $filters;
        }

        if ($tab === 'security') {
            try {
                $data['loginAttempts'] = $db->table('login_attempts')->orderBy('id', 'DESC')->limit(40)->get()->getResultArray();
                $data['failed24h'] = (int) $db->table('login_attempts')
                    ->where('success', 0)
                    ->where('created_at >=', date('Y-m-d H:i:s', time() - 86400))
                    ->countAllResults();
            } catch (Throwable) {
            }
        }

        return $this->render('admin/tools/index', $data);
    }

    public function clearCache()
    {
        $this->flushCache();
        $this->audit('tools.clear_cache', 'Flushed every application cache', 'tools');

        return $this->ok(['cache' => $this->cacheInfo()], 'Cache cleared.');
    }

    public function maintenance()
    {
        $enabled = (string) $this->request->getPost('value') === '1';
        service('settings')->set('general.maintenance_mode', $enabled ? '1' : '0');
        $this->flushCache();
        $this->audit('tools.maintenance', ($enabled ? 'Enabled' : 'Disabled') . ' maintenance mode', 'tools');

        return $this->ok(['enabled' => $enabled], $enabled ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.');
    }

    public function pruneAudit()
    {
        $days = max(1, (int) service('settings')->get('security.audit_retention', 180));
        $cut  = date('Y-m-d H:i:s', time() - $days * 86400);

        $deleted = db_connect()->table('audit_logs')->where('created_at <', $cut)->delete();
        $this->audit('tools.prune_audit', 'Pruned audit entries older than ' . $days . ' days', 'tools');

        return $this->ok(['deleted' => (int) $deleted], 'Removed ' . (int) $deleted . ' old audit entries.');
    }

    public function exportAudit()
    {
        $filters = [
            'q'      => trim((string) $this->request->getGet('q')),
            'module' => trim((string) $this->request->getGet('module')),
            'user'   => (int) ($this->request->getGet('user') ?? 0),
            'from'   => trim((string) $this->request->getGet('from')),
            'to'     => trim((string) $this->request->getGet('to')),
        ];

        $db      = db_connect();
        $builder = $db->table('audit_logs a')
            ->select('a.id, a.created_at, a.action, a.module, a.description, a.ip, u.email AS user_email')
            ->join('users u', 'u.id = a.user_id', 'left');
        $this->applyAuditFilters($builder, $filters);

        $rows = $builder->orderBy('a.id', 'DESC')->limit(10000)->get()->getResultArray();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Timestamp', 'User', 'Action', 'Module', 'Description', 'IP']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'], $row['created_at'], $row['user_email'] ?? '',
                $row['action'], $row['module'], $row['description'] ?? '', $row['ip'] ?? '',
            ]);
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="destra-audit-' . date('Ymd-His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function applyAuditFilters($builder, array $filters): void
    {
        if ($filters['q'] !== '') {
            $builder->groupStart()
                ->like('a.description', $filters['q'])
                ->orLike('a.action', $filters['q'])
                ->groupEnd();
        }
        if ($filters['module'] !== '') {
            $builder->where('a.module', $filters['module']);
        }
        if ($filters['user'] > 0) {
            $builder->where('a.user_id', $filters['user']);
        }
        if ($filters['from'] !== '') {
            $builder->where('a.created_at >=', $filters['from'] . ' 00:00:00');
        }
        if ($filters['to'] !== '') {
            $builder->where('a.created_at <=', $filters['to'] . ' 23:59:59');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function systemInfo($db): array
    {
        $dbVersion = '';
        try {
            $dbVersion = (string) $db->getVersion();
        } catch (Throwable) {
        }

        $free = @disk_free_space(ROOTPATH);
        $total = @disk_total_space(ROOTPATH);

        $writable = [
            'writable'          => is_writable(WRITEPATH),
            'uploads'           => is_writable(config(\Config\Destra::class)->uploadsPath),
            'app/Config'        => is_writable(APPPATH . 'Config'),
        ];

        return [
            'php'        => PHP_VERSION,
            'ci'         => \CodeIgniter\CodeIgniter::CI_VERSION,
            'db'         => $dbVersion,
            'server'     => $_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI,
            'os'         => PHP_OS_FAMILY,
            'timezone'   => date_default_timezone_get(),
            'memory'     => ini_get('memory_limit'),
            'upload'     => ini_get('upload_max_filesize'),
            'post'       => ini_get('post_max_size'),
            'extensions' => array_values(array_intersect(
                ['mbstring', 'intl', 'gd', 'zip', 'curl', 'openssl', 'fileinfo', 'dom', 'xml', 'exif'],
                get_loaded_extensions(),
            )),
            'free'       => $free !== false ? (int) $free : null,
            'total'      => $total !== false ? (int) $total : null,
            'writable'   => $writable,
            'missing'    => array_diff(['mbstring', 'intl', 'gd', 'zip', 'curl', 'openssl', 'fileinfo'], get_loaded_extensions()),
        ];
    }

    /**
     * @return array{files: int, size: int}
     */
    private function cacheInfo(): array
    {
        $files = 0;
        $size  = 0;

        try {
            $info = cache()->getCacheInfo();
            if (is_array($info)) {
                $files = count($info);
                foreach ($info as $item) {
                    if (is_array($item) && isset($item['size'])) {
                        $size += (int) $item['size'];
                    }
                }
            }
        } catch (Throwable) {
        }

        if ($files === 0) {
            foreach (glob(WRITEPATH . 'cache/*') ?: [] as $file) {
                if (is_file($file) && basename($file) !== 'index.html') {
                    $files++;
                    $size += (int) @filesize($file);
                }
            }
        }

        return ['files' => $files, 'size' => $size];
    }
}
