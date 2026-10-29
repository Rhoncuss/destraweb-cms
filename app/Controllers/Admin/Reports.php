<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\Orders as OrdersAdmin;
use Throwable;

class Reports extends BaseAdminController
{
    private const RANGES = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public function index(): string
    {
        $range = (int) ($this->request->getGet('range') ?? 30);
        if (! isset(self::RANGES[$range])) {
            $range = 30;
        }

        $db    = db_connect();
        $stats = service('stats');

        $traffic = $stats->dailyViews($range);
        $labels  = array_map(static fn (array $row): string => date('M j', strtotime($row['date'])), $traffic);
        $views   = array_map(static fn (array $row): int => (int) $row['views'], $traffic);
        $unique  = array_map(static fn (array $row): int => (int) $row['visitors'], $traffic);

        $rangeViews    = array_sum($views);
        $rangeVisitors = array_sum($unique);

        $previous = array_slice($stats->dailyViews($range * 2), 0, $range);
        $prevViews = 0;
        $prevVisitors = 0;
        foreach ($previous as $row) {
            $prevViews    += (int) $row['views'];
            $prevVisitors += (int) $row['visitors'];
        }

        $contentSeries      = $this->dailyCounts($db, 'contents', 'created_at', $range);
        $subscriberSeries   = $this->dailyCounts($db, 'newsletter_subscribers', 'created_at', $range);
        $orderSeries        = $this->dailyCounts($db, 'orders', 'created_at', $range);

        $contentByType   = $this->groupCounts($db, 'contents', 'type', ['deleted_at' => null]);
        $contentByStatus = $this->groupCounts($db, 'contents', 'status', ['deleted_at' => null]);
        $commentsByStatus = $this->groupCounts($db, 'comments', 'status', ['deleted_at' => null]);
        $ordersByStatus  = $this->groupCounts($db, 'orders', 'status');
        $subscribersByStatus = $this->groupCounts($db, 'newsletter_subscribers', 'status');

        $languages = [];
        try {
            $languages = $db->table('content_translations t')
                ->select('t.language_code, COUNT(*) AS total')
                ->join('contents c', 'c.id = t.content_id')
                ->where('c.deleted_at', null)
                ->groupBy('t.language_code')
                ->orderBy('total', 'DESC')
                ->get()->getResultArray();
        } catch (Throwable) {
        }

        $topContent = $stats->popularContents(8);
        $topPages   = $stats->topPages(8);

        return $this->render('admin/reports/index', [
            'pageTitle'    => 'Reports',
            'pageSubtitle' => 'Traffic, publishing and audience trends at a glance.',
            'navActive'    => 'reports',
            'breadcrumbs'  => [['label' => 'Reports']],
            'range'        => $range,
            'ranges'       => self::RANGES,
            'labels'       => $labels,
            'views'        => $views,
            'visitors'     => $unique,
            'rangeViews'   => $rangeViews,
            'rangeVisitors' => $rangeVisitors,
            'viewsTrend'   => $this->trend($rangeViews, $prevViews),
            'visitorsTrend' => $this->trend($rangeVisitors, $prevVisitors),
            'avgPerDay'    => $range > 0 ? (int) round($rangeViews / $range) : 0,
            'contentSeries' => array_values($contentSeries),
            'subscriberSeries' => array_values($subscriberSeries),
            'orderSeries'  => array_values($orderSeries),
            'contentByType' => $contentByType,
            'contentByStatus' => $contentByStatus,
            'commentsByStatus' => $commentsByStatus,
            'ordersByStatus' => $ordersByStatus,
            'subscribersByStatus' => $subscribersByStatus,
            'languages'    => $languages,
            'topContent'   => $topContent,
            'topPages'     => $topPages,
            'orderStatusLabels' => OrdersAdmin::STATUSES,
            'totals' => [
                'content'     => array_sum($contentSeries),
                'subscribers' => array_sum($subscriberSeries),
                'orders'      => array_sum($orderSeries),
                'views'       => $rangeViews,
            ],
        ]);
    }

    public function export()
    {
        $range = (int) ($this->request->getGet('range') ?? 30);
        if (! isset(self::RANGES[$range])) {
            $range = 30;
        }

        $rows = service('stats')->dailyViews($range);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Date', 'Views', 'Unique visitors']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['date'], (int) $row['views'], (int) $row['visitors']]);
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        $this->audit('reports.export', 'Exported the traffic report for the last ' . $range . ' days', 'reports');

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="destra-traffic-' . $range . 'd-' . date('Ymd') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    /**
     * @return array<string, int>
     */
    private function dailyCounts($db, string $table, string $column, int $range): array
    {
        $series = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $series[date('Y-m-d', strtotime('-' . $i . ' days'))] = 0;
        }

        try {
            $rows = $db->table($table)
                ->select('DATE(' . $column . ') AS day, COUNT(*) AS total')
                ->where($column . ' >=', date('Y-m-d 00:00:00', strtotime('-' . ($range - 1) . ' days')))
                ->groupBy('DATE(' . $column . ')')
                ->get()->getResultArray();

            foreach ($rows as $row) {
                $day = (string) $row['day'];
                if (isset($series[$day])) {
                    $series[$day] = (int) $row['total'];
                }
            }
        } catch (Throwable) {
        }

        return $series;
    }

    /**
     * @param array<string, mixed> $where
     * @return array<string, int>
     */
    private function groupCounts($db, string $table, string $column, array $where = []): array
    {
        $counts = [];

        try {
            $builder = $db->table($table)->select($column . ' AS label, COUNT(*) AS total')->groupBy($column);
            foreach ($where as $key => $value) {
                $value === null ? $builder->where($key . ' IS NULL') : $builder->where($key, $value);
            }

            foreach ($builder->get()->getResultArray() as $row) {
                $counts[(string) $row['label']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $counts;
    }

    private function trend(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
