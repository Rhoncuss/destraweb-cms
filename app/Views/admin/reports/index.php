<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a class="btn btn-soft" href="<?= admin_url('reports/export?range=' . (int) $range) ?>"><i class="fa-solid fa-file-csv me-2"></i>Export CSV</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <div class="dst-tabs-scroll">
        <?php foreach ($ranges as $value => $label): ?>
            <a class="dst-chip <?= (int) $range === $value ? 'is-active' : '' ?>" href="<?= admin_url('reports?range=' . $value) ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </div>
    <span class="dst-meta ms-auto">Comparing with the previous <?= (int) $range ?> days</span>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Page views</span>
            <span class="stat-value"><?= number_format($rangeViews) ?></span>
            <span class="stat-foot <?= $viewsTrend >= 0 ? 'text-success' : 'text-danger' ?>">
                <i class="fa-solid <?= $viewsTrend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?> me-1"></i><?= $viewsTrend >= 0 ? '+' : '' ?><?= $viewsTrend ?>% vs previous
            </span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat stat-ocean">
            <span class="stat-label">Unique visitors</span>
            <span class="stat-value"><?= number_format($rangeVisitors) ?></span>
            <span class="stat-foot <?= $visitorsTrend >= 0 ? 'text-success' : 'text-danger' ?>">
                <i class="fa-solid <?= $visitorsTrend >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?> me-1"></i><?= $visitorsTrend >= 0 ? '+' : '' ?><?= $visitorsTrend ?>% vs previous
            </span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Views per day</span>
            <span class="stat-value"><?= number_format($avgPerDay) ?></span>
            <span class="stat-foot"><i class="fa-regular fa-calendar me-1"></i>average over <?= (int) $range ?> days</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat stat-sunrise">
            <span class="stat-label">New content</span>
            <span class="stat-value"><?= number_format($totals['content']) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-pen-nib me-1"></i>posts &amp; pages created</span>
        </div>
    </div>
</div>

<div class="dst-card mb-3">
    <div class="card-header">
        <h2 class="card-title">Traffic</h2>
        <span class="dst-chip"><i class="fa-solid fa-chart-line me-1"></i>views &amp; visitors</span>
    </div>
    <div class="card-body">
        <div class="report-chart" style="height:320px;"><canvas id="reportTraffic"></canvas></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Publishing cadence</h2><span class="dst-chip">new content / day</span></div>
            <div class="card-body"><div class="report-chart" style="height:240px;"><canvas id="reportContent"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Content mix</h2></div>
            <div class="card-body"><div class="report-chart" style="height:240px;"><canvas id="reportTypes"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Audience growth</h2><span class="dst-chip"><?= number_format($totals['subscribers']) ?> new</span></div>
            <div class="card-body"><div class="report-chart" style="height:220px;"><canvas id="reportSubscribers"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Comments</h2></div>
            <div class="card-body"><div class="report-chart" style="height:220px;"><canvas id="reportComments"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Orders pipeline</h2><span class="dst-chip"><?= number_format($totals['orders']) ?> new</span></div>
            <div class="card-body"><div class="report-chart" style="height:220px;"><canvas id="reportOrders"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Most viewed content</h2></div>
            <?php if ($topContent === []): ?>
                <div class="dst-empty"><i class="fa-regular fa-file-lines"></i><h3>No published content yet</h3><p>Publish a few articles and their traffic will appear here.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Title</th><th>Type</th><th class="text-end">Views</th></tr></thead>
                        <tbody>
                        <?php foreach ($topContent as $item): ?>
                            <tr>
                                <td class="min-w-0"><div class="fw-650 truncate" style="max-width:420px;"><?= esc((string) ($item['title'] ?? 'Untitled')) ?></div></td>
                                <td><span class="dst-chip"><?= esc(ucfirst((string) $item['type'])) ?></span></td>
                                <td class="text-end dst-meta"><?= number_format((int) $item['views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Top pages (30 days)</h2></div>
            <?php if ($topPages === []): ?>
                <div class="dst-empty"><i class="fa-solid fa-chart-simple"></i><h3>No visit data yet</h3><p>View counts are captured automatically on the public site.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Path</th><th class="text-end">Views</th></tr></thead>
                        <tbody>
                        <?php foreach ($topPages as $page): ?>
                            <tr>
                                <td class="min-w-0"><div class="truncate" style="max-width:260px;"><code class="dst-trans-key"><?= esc((string) $page['path']) ?></code></div></td>
                                <td class="text-end dst-meta"><?= number_format((int) $page['total_views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dst-card mt-3">
    <div class="card-header"><h2 class="card-title">Translations by language</h2><span class="dst-chip"><?= count($languages) ?> languages in use</span></div>
    <div class="card-body">
        <?php if ($languages === []): ?>
            <p class="text-muted-2 small mb-0">No translated content yet.</p>
        <?php else: ?>
            <?php $maxLang = max(array_map(static fn (array $row): int => (int) $row['total'], $languages)); ?>
            <div class="d-grid gap-2">
                <?php foreach ($languages as $language): $count = (int) $language['total']; ?>
                    <div class="report-lang">
                        <span class="dst-lang-chip"><?= esc(strtoupper((string) $language['language_code'])) ?></span>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between small mb-1"><span><?= esc(strtoupper((string) $language['language_code'])) ?></span><span class="text-muted-2"><?= number_format($count) ?></span></div>
                            <div class="report-bar"><span style="width:<?= $maxLang > 0 ? max(2, (int) round($count / $maxLang * 100)) : 0 ?>%"></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var labels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var views = <?= json_encode($views) ?>;
    var visitors = <?= json_encode($visitors) ?>;
    var contentSeries = <?= json_encode($contentSeries) ?>;
    var subscriberSeries = <?= json_encode($subscriberSeries) ?>;
    var orderSeries = <?= json_encode($orderSeries) ?>;
    var contentByType = <?= json_encode($contentByType ?: []) ?>;
    var commentsByStatus = <?= json_encode($commentsByStatus ?: []) ?>;
    var ordersByStatus = <?= json_encode($ordersByStatus ?: []) ?>;

    var palette = ['#6366f1', '#22d3ee', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#0ea5e9', '#84cc16'];
    var muted = getComputedStyle(document.documentElement).getPropertyValue('--dst-muted') || '#94a3b8';
    var grid = 'rgba(148,163,184,.18)';

    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.color = muted.trim() || '#94a3b8';

    var lineBase = {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8 } } },
        scales: {
            x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
            y: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } }
        }
    };

    var traffic = JSON.parse(JSON.stringify(lineBase));
    new Chart(document.getElementById('reportTraffic'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Page views', data: views, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.14)', fill: true, tension: .35, borderWidth: 2.5, pointRadius: 0, pointHitRadius: 12 },
                { label: 'Unique visitors', data: visitors, borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.10)', fill: true, tension: .35, borderWidth: 2.5, pointRadius: 0, pointHitRadius: 12 }
            ]
        },
        options: traffic
    });

    new Chart(document.getElementById('reportContent'), {
        type: 'bar',
        data: { labels: labels, datasets: [{ label: 'Content created', data: contentSeries, backgroundColor: 'rgba(16,185,129,.65)', borderRadius: 6, maxBarThickness: 22 }] },
        options: JSON.parse(JSON.stringify(lineBase))
    });

    new Chart(document.getElementById('reportSubscribers'), {
        type: 'line',
        data: { labels: labels, datasets: [{ label: 'New subscribers', data: subscriberSeries, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.16)', fill: true, tension: .35, borderWidth: 2.5, pointRadius: 0 }] },
        options: JSON.parse(JSON.stringify(lineBase))
    });

    function doughnut(id, data, colors) {
        new Chart(document.getElementById(id), {
            type: 'doughnut',
            data: {
                labels: Object.keys(data).map(function (key) { return key.charAt(0).toUpperCase() + key.slice(1); }),
                datasets: [{ data: Object.values(data), backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } } }
        });
    }

    doughnut('reportTypes', contentByType, palette);
    doughnut('reportComments', commentsByStatus, ['#f59e0b', '#10b981', '#f43f5e', '#94a3b8']);

    new Chart(document.getElementById('reportOrders'), {
        type: 'bar',
        data: {
            labels: Object.keys(ordersByStatus).map(function (key) { return key.charAt(0).toUpperCase() + key.slice(1); }),
            datasets: [{ label: 'Orders', data: Object.values(ordersByStatus), backgroundColor: ['#6366f1', '#22d3ee', '#10b981', '#f43f5e'], borderRadius: 8, maxBarThickness: 38 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } } } }
    });
})();
</script>
<?= $this->endSection() ?>
