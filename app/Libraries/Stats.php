<?php

namespace App\Libraries;

use Throwable;

/**
 * Aggregate reporting and lightweight (privacy friendly) visit tracking.
 * All queries fail soft so a fresh or partially migrated database never
 * breaks the dashboard.
 */
class Stats
{
    /**
     * @return array<string, int>
     */
    public function overview(): array
    {
        return (array) service('cache_manager')->remember('stats_overview', 120, function (): array {
            $counts = [
                'contents'         => 0,
                'published'        => 0,
                'drafts'           => 0,
                'pages'            => 0,
                'users'            => 0,
                'comments_pending' => 0,
                'subscribers'      => 0,
                'media'            => 0,
                'messages_unread'  => 0,
                'orders_new'       => 0,
                'views_total'      => 0,
                'views_today'      => 0,
                'visitors_today'   => 0,
                'plugins_active'   => 0,
            ];

            if (! is_installed()) {
                return $counts;
            }

            $db = db_connect();
            $queries = [
                'contents'         => static fn () => $db->table('contents')->where('deleted_at', null)->countAllResults(),
                'published'        => static fn () => $db->table('contents')->where('deleted_at', null)->where('status', 'published')->countAllResults(),
                'drafts'           => static fn () => $db->table('contents')->where('deleted_at', null)->where('status', 'draft')->countAllResults(),
                'pages'            => static fn () => $db->table('contents')->where('deleted_at', null)->where('type', 'page')->countAllResults(),
                'users'            => static fn () => $db->table('users')->where('deleted_at', null)->countAllResults(),
                'comments_pending' => static fn () => $db->table('comments')->where('deleted_at', null)->where('status', 'pending')->countAllResults(),
                'subscribers'      => static fn () => $db->table('newsletter_subscribers')->where('status', 'active')->countAllResults(),
                'media'            => static fn () => $db->table('media')->where('deleted_at', null)->countAllResults(),
                'messages_unread'  => static fn () => $db->table('form_submissions')->where('is_read', 0)->countAllResults(),
                'orders_new'       => static fn () => $db->table('orders')->where('status', 'new')->countAllResults(),
                'plugins_active'   => static fn () => $db->table('plugins')->where('is_active', 1)->countAllResults(),
            ];

            foreach ($queries as $key => $query) {
                try {
                    $counts[$key] = (int) $query();
                } catch (Throwable) {
                    $counts[$key] = 0;
                }
            }

            try {
                $sum = $db->table('visits')->selectSum('views')->get()->getRowArray();
                $counts['views_total'] = (int) ($sum['views'] ?? 0);
            } catch (Throwable) {
                $counts['views_total'] = 0;
            }

            try {
                $today = $db->table('visits')->select('SUM(views) AS views, SUM(visitors) AS visitors')
                    ->where('visit_date', date('Y-m-d'))
                    ->get()
                    ->getRowArray();
                $counts['views_today']    = (int) ($today['views'] ?? 0);
                $counts['visitors_today'] = (int) ($today['visitors'] ?? 0);
            } catch (Throwable) {
                // Keep zeroes.
            }

            try {
                $sum = $db->table('contents')->selectSum('views')->get()->getRowArray();
                $counts['views_total'] = max($counts['views_total'], (int) ($sum['views'] ?? 0));
            } catch (Throwable) {
                // Ignore.
            }

            return $counts;
        });
    }

    /**
     * Daily series used by the dashboard chart and reports.
     *
     * @return list<array{date: string, views: int, visitors: int}>
     */
    public function dailyViews(int $days = 30): array
    {
        $days = max(7, min(365, $days));
        $key  = 'stats_daily_' . $days;

        return (array) service('cache_manager')->remember($key, 300, function () use ($days): array {
            $series = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime('-' . $i . ' days'));
                $series[$date] = ['date' => $date, 'views' => 0, 'visitors' => 0];
            }

            if (! is_installed()) {
                return array_values($series);
            }

            try {
                $rows = db_connect()->table('visits')
                    ->select('visit_date, SUM(views) AS views, SUM(visitors) AS visitors')
                    ->where('visit_date >=', date('Y-m-d', strtotime('-' . ($days - 1) . ' days')))
                    ->groupBy('visit_date')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $date = (string) $row['visit_date'];
                    if (isset($series[$date])) {
                        $series[$date]['views']    = (int) $row['views'];
                        $series[$date]['visitors'] = (int) $row['visitors'];
                    }
                }
            } catch (Throwable) {
                // Return zero filled series.
            }

            return array_values($series);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function popularContents(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        return (array) service('cache_manager')->remember('stats_popular_' . $limit, 300, function () use ($limit): array {
            try {
                return db_connect()->table('contents c')
                    ->select('c.id, c.type, c.views, c.published_at, t.title, t.slug, s.slug AS section_slug')
                    ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . db_connect()->escape(default_locale()), 'left')
                    ->join('sections s', 's.id = c.section_id', 'left')
                    ->where('c.status', 'published')
                    ->where('c.deleted_at', null)
                    ->orderBy('c.views', 'DESC')
                    ->limit($limit)
                    ->get()
                    ->getResultArray();
            } catch (Throwable) {
                return [];
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentActivity(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));

        try {
            return db_connect()->table('audit_logs a')
                ->select('a.*, u.name AS user_name, u.username, u.avatar')
                ->join('users u', 'u.id = a.user_id', 'left')
                ->orderBy('a.id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topPages(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));

        try {
            return db_connect()->table('visits')
                ->select('path, SUM(views) AS total_views')
                ->where('visit_date >=', date('Y-m-d', strtotime('-30 days')))
                ->groupBy('path')
                ->orderBy('total_views', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    public function recordVisit(string $path, ?int $contentId = null, ?string $locale = null): void
    {
        if (! is_installed()) {
            return;
        }

        try {
            $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
            if (str_starts_with($path, '/admin') || str_starts_with($path, '/api') || str_starts_with($path, '/media')) {
                return;
            }

            $hash  = hash('sha256', $path);
            $today = date('Y-m-d');
            $db    = db_connect();

            $row = $db->table('visits')
                ->where('visit_date', $today)
                ->where('path_hash', $hash)
                ->get()
                ->getRowArray();

            if ($row) {
                $db->table('visits')->where('id', (int) $row['id'])
                    ->set('views', 'views + 1', false)
                    ->update();
                if ($contentId !== null && $contentId > 0) {
                    $db->table('contents')->where('id', $contentId)->set('views', 'views + 1', false)->update();
                }

                return;
            }

            $db->table('visits')->insert([
                'visit_date'    => $today,
                'path_hash'     => $hash,
                'path'          => mb_substr($path, 0, 500),
                'content_id'    => $contentId,
                'language_code' => $locale ?? current_locale(),
                'views'         => 1,
                'visitors'      => 1,
            ]);

            if ($contentId !== null && $contentId > 0) {
                $db->table('contents')->where('id', $contentId)->set('views', 'views + 1', false)->update();
            }
        } catch (Throwable $e) {
            log_message('debug', 'Visit tracking skipped: ' . $e->getMessage());
        }
    }
}
