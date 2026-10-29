<?php

namespace App\Controllers\Admin;

use Throwable;

class Calendar extends BaseAdminController
{
    public function index(): string
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $counts = ['scheduled' => 0, 'drafts' => 0, 'published' => 0];
        $upcoming = [];
        $recentDrafts = [];

        try {
            $counts['scheduled'] = (int) $db->table('contents')
                ->where('deleted_at', null)->where('status', 'published')->where('published_at >', $now)->countAllResults();
            $counts['drafts'] = (int) $db->table('contents')
                ->where('deleted_at', null)->where('status', 'draft')->countAllResults();
            $counts['published'] = (int) $db->table('contents')
                ->where('deleted_at', null)->where('status', 'published')
                ->where('published_at <=', $now)->where('published_at >=', date('Y-m-01 00:00:00'))->countAllResults();

            $upcoming = $db->table('contents c')
                ->select('c.id, c.type, c.status, c.published_at, t.title')
                ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape(default_locale()), 'left')
                ->where('c.deleted_at', null)
                ->where('c.status', 'published')
                ->where('c.published_at >', $now)
                ->orderBy('c.published_at', 'ASC')
                ->limit(6)
                ->get()->getResultArray();

            $recentDrafts = $db->table('contents c')
                ->select('c.id, c.type, c.status, c.updated_at, t.title')
                ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape(default_locale()), 'left')
                ->where('c.deleted_at', null)
                ->where('c.status', 'draft')
                ->orderBy('c.updated_at', 'DESC')
                ->limit(6)
                ->get()->getResultArray();
        } catch (Throwable) {
        }

        return $this->render('admin/calendar/index', [
            'pageTitle'    => 'Calendar',
            'pageSubtitle' => 'Plan your editorial rhythm across every section.',
            'navActive'    => 'calendar',
            'breadcrumbs'  => [['label' => 'Calendar']],
            'eventsUrl'    => admin_url('calendar/events'),
            'newUrl'       => admin_url('content/create'),
            'counts'       => $counts,
            'upcoming'     => $upcoming,
            'recentDrafts' => $recentDrafts,
        ]);
    }

    public function events()
    {
        $start = (string) ($this->request->getGet('start') ?? '');
        $end   = (string) ($this->request->getGet('end') ?? '');

        $startDate = $start !== '' ? date('Y-m-d H:i:s', strtotime($start)) : date('Y-m-01 00:00:00');
        $endDate   = $end !== '' ? date('Y-m-d H:i:s', strtotime($end)) : date('Y-m-t 23:59:59');

        $events = [];

        try {
            $db = db_connect();
            $rows = $db->table('contents c')
                ->select('c.id, c.type, c.status, c.published_at, c.expires_at, t.title, s.name AS section_name')
                ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape(default_locale()), 'left')
                ->join('sections s', 's.id = c.section_id', 'left')
                ->where('c.deleted_at', null)
                ->where('c.published_at IS NOT NULL')
                ->where('c.published_at >=', $startDate)
                ->where('c.published_at <=', $endDate)
                ->orderBy('c.published_at', 'ASC')
                ->get()->getResultArray();

            $now = time();
            foreach ($rows as $row) {
                $publishedAt = (string) $row['published_at'];
                $scheduled   = strtotime($publishedAt) > $now && $row['status'] === 'published';
                $expired     = ! empty($row['expires_at']) && strtotime((string) $row['expires_at']) < $now;

                if ($expired) {
                    $color = '#94a3b8';
                } elseif ($scheduled) {
                    $color = '#f59e0b';
                } elseif ($row['status'] === 'draft') {
                    $color = '#6366f1';
                } else {
                    $color = '#10b981';
                }

                $events[] = [
                    'id'    => (string) $row['id'],
                    'title' => (string) ($row['title'] ?? 'Untitled'),
                    'start' => date('c', strtotime($publishedAt)),
                    'url'   => admin_url('content/' . (int) $row['id'] . '/edit'),
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => '#ffffff',
                    'classNames'      => $expired ? ['dst-cal-expired'] : [],
                    'extendedProps'   => [
                        'type'      => ucfirst((string) $row['type']),
                        'status'    => $scheduled ? 'Scheduled' : ucfirst((string) $row['status']),
                        'section'   => (string) ($row['section_name'] ?? ''),
                        'scheduled' => $scheduled,
                    ],
                ];
            }
        } catch (Throwable) {
        }

        return $this->response->setJSON($events);
    }
}
