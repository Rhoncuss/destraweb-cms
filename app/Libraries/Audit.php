<?php

namespace App\Libraries;

use Throwable;

class Audit
{
    public function log(
        string $action,
        ?string $description = null,
        string $module = 'general',
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $meta = [],
    ): void {
        if (! is_file(config(\Config\Destra::class)->installLock)) {
            return;
        }

        try {
            $request = service('request');
            $ip      = $request->getIPAddress();
            $userId  = (int) session('admin_user_id');

            $sanitizer = service('sanitizer');
            db_connect()->table('audit_logs')->insert([
                'user_id'      => $userId > 0 ? $userId : null,
                'action'       => mb_substr($sanitizer->text($action), 0, 80),
                'module'       => mb_substr($sanitizer->text($module), 0, 60),
                'description'  => $description !== null ? mb_substr($sanitizer->text($description), 0, 500) : null,
                'subject_type' => $subjectType !== null ? mb_substr($sanitizer->text($subjectType), 0, 80) : null,
                'subject_id'   => $subjectId,
                'ip'           => mb_substr($ip, 0, 64),
                'user_agent'   => mb_substr($request->getUserAgent()->getAgentString(), 0, 255),
                'meta'         => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
        }
    }

    public function notify(int $userId, string $type, string $title, string $message = '', string $url = '', string $icon = 'fa-solid fa-bell'): void
    {
        try {
            db_connect()->table('notifications')->insert([
                'user_id'    => $userId,
                'type'       => mb_substr($type, 0, 30),
                'title'      => mb_substr($title, 0, 190),
                'message'    => $message,
                'url'        => $url !== '' ? $url : null,
                'icon'       => $icon,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }
}
