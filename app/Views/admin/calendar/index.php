<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a class="btn btn-brand" href="<?= esc($newUrl, 'attr') ?>"><i class="fa-solid fa-plus me-2"></i>New content</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-xl-9">
        <div class="dst-card">
            <div class="card-header flex-wrap gap-2">
                <h2 class="card-title">Editorial calendar</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="dst-chip"><i class="fa-solid fa-circle me-1" style="color:#10b981;font-size:8px;"></i>Published</span>
                    <span class="dst-chip"><i class="fa-solid fa-circle me-1" style="color:#f59e0b;font-size:8px;"></i>Scheduled</span>
                    <span class="dst-chip"><i class="fa-solid fa-circle me-1" style="color:#6366f1;font-size:8px;"></i>Draft</span>
                    <span class="dst-chip"><i class="fa-solid fa-circle me-1" style="color:#94a3b8;font-size:8px;"></i>Expired</span>
                </div>
            </div>
            <div class="card-body">
                <div id="dstCalendar"></div>
                <div class="text-muted-2 small mt-3"><i class="fa-regular fa-hand-pointer me-1"></i>Select an event to edit the content. Bars are placed on the publish date.</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="row g-3 mb-0">
            <div class="col-6 col-xl-12">
                <div class="dst-stat">
                    <span class="stat-label">Scheduled</span>
                    <span class="stat-value"><?= (int) $counts['scheduled'] ?></span>
                    <span class="stat-foot"><i class="fa-regular fa-clock me-1"></i>waiting to go live</span>
                </div>
            </div>
            <div class="col-6 col-xl-12">
                <div class="dst-stat stat-ocean">
                    <span class="stat-label">Published this month</span>
                    <span class="stat-value"><?= (int) $counts['published'] ?></span>
                    <span class="stat-foot"><i class="fa-regular fa-calendar-check me-1"></i><?= esc(date('F Y')) ?></span>
                </div>
            </div>
            <div class="col-6 col-xl-12">
                <div class="dst-stat stat-sunrise">
                    <span class="stat-label">Drafts</span>
                    <span class="stat-value"><?= (int) $counts['drafts'] ?></span>
                    <span class="stat-foot"><i class="fa-regular fa-pen-to-square me-1"></i>in progress</span>
                </div>
            </div>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header"><h2 class="card-title">Coming up</h2></div>
            <div class="card-body p-0">
                <?php if ($upcoming === []): ?>
                    <div class="p-3 text-muted-2 small">Nothing scheduled. Set a future publish date on any content item.</div>
                <?php else: ?>
                    <div class="px-3 py-2">
                        <?php foreach ($upcoming as $item): ?>
                            <a class="cal-item" href="<?= admin_url('content/' . (int) $item['id'] . '/edit') ?>">
                                <span class="cal-date"><?= esc(date('d', strtotime((string) $item['published_at']))) ?><small><?= esc(date('M', strtotime((string) $item['published_at']))) ?></small></span>
                                <span class="min-w-0 flex-grow-1">
                                    <span class="d-block fw-650 truncate"><?= esc((string) ($item['title'] ?? 'Untitled')) ?></span>
                                    <span class="d-block text-muted-2 small"><?= esc(date('H:i', strtotime((string) $item['published_at']))) ?> · <?= esc(ucfirst((string) $item['type'])) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header"><h2 class="card-title">Recently edited drafts</h2></div>
            <div class="card-body p-0">
                <?php if ($recentDrafts === []): ?>
                    <div class="p-3 text-muted-2 small">No drafts yet — you are all caught up.</div>
                <?php else: ?>
                    <div class="px-3 py-2">
                        <?php foreach ($recentDrafts as $item): ?>
                            <a class="cal-item" href="<?= admin_url('content/' . (int) $item['id'] . '/edit') ?>">
                                <span class="cal-date cal-date-muted"><i class="fa-regular fa-file-lines"></i></span>
                                <span class="min-w-0 flex-grow-1">
                                    <span class="d-block fw-650 truncate"><?= esc((string) ($item['title'] ?? 'Untitled')) ?></span>
                                    <span class="d-block text-muted-2 small"><?= esc(human_date((string) $item['updated_at'], 'M j, H:i')) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('vendor/fullcalendar/index.global.min.js') ?>"></script>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var element = document.getElementById('dstCalendar');
    if (!element || typeof FullCalendar === 'undefined') { return; }

    var calendar = new FullCalendar.Calendar(element, {
        initialView: 'dayGridMonth',
        height: 'auto',
        firstDay: 1,
        nowIndicator: true,
        fixedWeekCount: false,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
        events: {
            url: <?= json_encode($eventsUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            method: 'GET',
            failure: function () { Destra.toast('error', 'Could not load calendar events.'); }
        },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventDisplay: 'block',
        eventDidMount: function (info) {
            var props = info.event.extendedProps;
            var parts = [props.type];
            if (props.section) { parts.push(props.section); }
            parts.push(props.status);
            info.el.title = info.event.title + ' — ' + parts.join(' · ');
        }
    });

    calendar.render();

    document.addEventListener('dst:theme', function () {
        calendar.render();
    });
})();
</script>
<?= $this->endSection() ?>
