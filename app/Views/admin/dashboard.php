<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <div class="d-inline-flex gap-2 flex-wrap">
        <a href="<?= admin_url('content/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New content</a>
        <a href="<?= admin_url('media') ?>" class="btn btn-soft"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload media</a>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="dst-stat">
            <div class="stat-top">
                <span class="stat-label">Content</span>
                <span class="stat-icon"><i class="fa-regular fa-file-lines"></i></span>
            </div>
            <div>
                <div class="stat-value"><?= number_format((int) $overview['contents']) ?></div>
                <div class="stat-foot">
                    <span class="dst-chip brand"><?= (int) $overview['published'] ?> published</span>
                    <span class="dst-chip"><?= (int) $overview['drafts'] ?> drafts</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dst-stat stat-ocean">
            <div class="stat-top">
                <span class="stat-label">Views today</span>
                <span class="stat-icon"><i class="fa-solid fa-chart-simple"></i></span>
            </div>
            <div>
                <div class="stat-value"><?= number_format((int) $overview['views_today']) ?></div>
                <div class="stat-foot">
                    <span class="dst-chip accent"><?= number_format((int) $overview['visitors_today']) ?> visitors</span>
                    <span class="text-muted-2"><?= number_format((int) $overview['views_total']) ?> all time</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dst-stat stat-sunrise">
            <div class="stat-top">
                <span class="stat-label">Comments pending</span>
                <span class="stat-icon"><i class="fa-regular fa-comments"></i></span>
            </div>
            <div>
                <div class="stat-value"><?= number_format((int) $overview['comments_pending']) ?></div>
                <div class="stat-foot"><a href="<?= admin_url('comments') ?>" class="dst-chip">Review moderation <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="dst-stat stat-coral">
            <div class="stat-top">
                <span class="stat-label">Subscribers</span>
                <span class="stat-icon"><i class="fa-regular fa-paper-plane"></i></span>
            </div>
            <div>
                <div class="stat-value"><?= number_format((int) $overview['subscribers']) ?></div>
                <div class="stat-foot">
                    <span class="dst-chip"><?= (int) $overview['messages_unread'] ?> unread messages</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="dst-card h-100">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Audience overview</h2>
                    <div class="text-muted-2 small mt-1">Views and unique visitors over the last 14 days</div>
                </div>
                <a href="<?= admin_url('reports') ?>" class="btn btn-soft btn-sm">Full report</a>
            </div>
            <div class="card-body">
                <div style="height:300px;"><canvas id="viewsChart" aria-label="Views and visitors chart" role="img"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="dst-card h-100">
            <div class="card-header">
                <h2 class="card-title">Most viewed</h2>
                <span class="dst-chip">Top 5</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($popular)): ?>
                    <div class="dst-empty py-5"><i class="fa-regular fa-eye"></i><p>No views recorded yet.</p></div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular as $index => $item): ?>
                            <div class="list-group-item d-flex align-items-center gap-3 border-0 px-3 py-3" style="background:transparent;">
                                <span class="dst-avatar" style="width:32px;height:32px;font-size:12px;background:linear-gradient(135deg,#eef0ff,#dfe3ff);color:#4b3df0;"><?= $index + 1 ?></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold truncate" style="font-size:13px;"><?= esc($item['title'] ?? 'Untitled') ?></div>
                                    <div class="text-muted-2" style="font-size:11.5px;"><?= esc(ucfirst((string) ($item['type'] ?? 'post'))) ?> · <?= number_format((int) ($item['views'] ?? 0)) ?> views</div>
                                </div>
                                <i class="fa-solid fa-chevron-right text-muted-2" style="font-size:10px;"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="dst-card h-100">
            <div class="card-header">
                <h2 class="card-title">Latest content</h2>
                <a href="<?= admin_url('content') ?>" class="btn btn-soft btn-sm">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($latest)): ?>
                        <tr><td colspan="4"><div class="dst-empty py-4"><p class="mb-0">Nothing published yet. Create your first piece of content.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($latest as $item): ?>
                            <tr>
                                <td>
                                    <div class="dst-title"><?= esc($item['title'] ?? 'Untitled') ?></div>
                                    <div class="dst-meta"><?= esc(ucfirst((string) ($item['type'] ?? 'post'))) ?><?= ! empty($item['section_name']) ? ' · ' . esc($item['section_name']) : '' ?></div>
                                </td>
                                <td><span class="dst-badge <?= esc((string) ($item['status'] ?? 'draft'), 'attr') ?>"><?= esc(ucfirst((string) ($item['status'] ?? 'draft'))) ?></span></td>
                                <td class="dst-meta"><?= esc(date('d M Y', strtotime((string) ($item['updated_at'] ?? 'now')))) ?></td>
                                <td class="text-end">
                                    <a href="<?= admin_url('content/' . (int) $item['id'] . '/edit') ?>" class="btn btn-soft btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    <?php if (($item['status'] ?? '') === 'published'): ?>
                                        <a href="<?= esc($item['url'] ?? '#', 'attr') ?>" target="_blank" rel="noopener" class="btn btn-soft btn-icon" title="View"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="dst-card h-100">
            <div class="card-header">
                <h2 class="card-title">Activity</h2>
                <a href="<?= admin_url('tools/audit') ?>" class="btn btn-soft btn-sm">Audit log</a>
            </div>
            <div class="card-body">
                <?php if (empty($activity)): ?>
                    <div class="dst-empty py-4"><i class="fa-regular fa-clock"></i><p>Activity will appear here as your team works.</p></div>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($activity as $log): ?>
                            <div class="d-flex gap-3">
                                <span class="dst-avatar" style="width:34px;height:34px;font-size:12px;">
                                    <?= esc(mb_strtoupper(mb_substr((string) ($log['user_name'] ?? 'S'), 0, 1))) ?>
                                </span>
                                <div class="min-w-0">
                                    <div style="font-size:13px;">
                                        <strong><?= esc($log['user_name'] ?? 'System') ?></strong>
                                        <?= esc($log['description'] ?? $log['action'] ?? '') ?>
                                    </div>
                                    <div class="text-muted-2" style="font-size:11.5px;">
                                        <?= esc(date('d M · H:i', strtotime((string) ($log['created_at'] ?? 'now')))) ?>
                                        <?php if (! empty($log['module'])): ?> · <?= esc(ucfirst((string) $log['module'])) ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Quick actions</h2></div>
            <div class="card-body">
                <div class="row g-2">
                    <?php
                    $quick = [
                        ['Write a post', 'fa-regular fa-pen-to-square', 'content/create?type=post'],
                        ['Upload media', 'fa-regular fa-images', 'media'],
                        ['Create a page', 'fa-regular fa-file', 'content/create?type=page'],
                        ['Invite a teammate', 'fa-solid fa-user-plus', 'users/create'],
                        ['Review comments', 'fa-regular fa-comments', 'comments'],
                        ['Site settings', 'fa-solid fa-sliders', 'settings'],
                    ];
                    foreach ($quick as [$label, $icon, $path]): ?>
                        <div class="col-sm-6 col-lg-4">
                            <a href="<?= admin_url($path) ?>" class="d-flex align-items-center gap-2 p-3 rounded-3 text-reset" style="background:var(--dst-canvas);border:1px solid var(--dst-line);">
                                <i class="<?= esc($icon, 'attr') ?>" style="color:var(--dst-brand);"></i>
                                <span style="font-size:13px;font-weight:600;"><?= esc($label) ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">System health</h2></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php foreach ($health as $check): ?>
                        <div class="d-flex align-items-center justify-content-between gap-3" style="font-size:13px;">
                            <span class="text-muted-2"><?= esc($check['label']) ?></span>
                            <span class="dst-chip <?= $check['ok'] ? 'accent' : '' ?>" style="<?= $check['ok'] ? '' : 'background:#fef3f2;color:#b42318;' ?>">
                                <i class="fa-solid <?= $check['ok'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                                <?= esc($check['value']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var canvas = document.getElementById('viewsChart');
    if (!canvas || !window.Chart) { return; }
    var series = <?= json_encode($daily, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var labels = series.map(function (row) { return new Date(row.date + 'T00:00:00').toLocaleDateString(undefined, { day: '2-digit', month: 'short' }); });
    var ctx = canvas.getContext('2d');
    var gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(109, 94, 252, .35)');
    gradient.addColorStop(1, 'rgba(109, 94, 252, 0)');
    var gradient2 = ctx.createLinearGradient(0, 0, 0, 300);
    gradient2.addColorStop(0, 'rgba(0, 194, 168, .28)');
    gradient2.addColorStop(1, 'rgba(0, 194, 168, 0)');
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Views', data: series.map(function (r) { return r.views; }), borderColor: '#6d5efc', backgroundColor: gradient, fill: true, tension: .38, borderWidth: 2.4, pointRadius: 0, pointHoverRadius: 4 },
                { label: 'Visitors', data: series.map(function (r) { return r.visitors; }), borderColor: '#00c2a8', backgroundColor: gradient2, fill: true, tension: .38, borderWidth: 2.2, pointRadius: 0, pointHoverRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, .16)' }, ticks: { font: { size: 10 }, precision: 0 } }
            }
        }
    });
})();
</script>
<?= $this->endSection() ?>
