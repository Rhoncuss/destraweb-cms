<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('newsletter/export') ?>" class="btn btn-soft"><i class="fa-solid fa-file-csv me-2"></i>Export CSV</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#subscriberModal"><i class="fa-solid fa-plus me-2"></i>Add subscriber</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = static function (array $overrides = []) use ($status, $query): string {
    $params = array_filter(array_merge(['status' => $status, 'q' => $query], $overrides), static fn ($v) => $v !== '' && $v !== null);

    return admin_url('newsletter' . ($params ? '?' . http_build_query($params) : ''));
};
$badges = ['confirmed' => 'published', 'pending' => 'pending', 'unsubscribed' => 'inactive'];
$labels = ['confirmed' => 'Confirmed', 'pending' => 'Pending', 'unsubscribed' => 'Unsubscribed'];
?>
<div class="row g-3 mb-3">
    <?php foreach (['all' => 'Total audience', 'confirmed' => 'Confirmed', 'pending' => 'Pending', 'unsubscribed' => 'Unsubscribed'] as $key => $label): ?>
        <div class="col-6 col-lg-3">
            <a class="dst-card text-reset text-decoration-none d-block" href="<?= $qs(['status' => $key === 'all' ? '' : $key]) ?>">
                <div class="card-body">
                    <div class="text-muted-2 small"><?= esc($label) ?></div>
                    <div class="stat-value" style="font-size:26px;font-weight:750;"><?= number_format((int) $counts[$key]) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="dst-card mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= admin_url('newsletter') ?>" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">All statuses</option>
                <?php foreach ($labels as $key => $label): ?><option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-sm" placeholder="Search email or name…" style="width:220px;">
            <button class="btn btn-soft btn-sm" type="submit">Filter</button>
            <span class="text-muted-2 small ms-auto"><?= number_format($total) ?> subscriber(s)</span>
        </form>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-envelope-open"></i>
            <h3>No subscribers here</h3>
            <p>Add people manually or point the front-end newsletter widget at this list.</p>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Locale</th><th>Since</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="fw-650"><?= esc($item['email']) ?></td>
                        <td class="dst-meta"><?= esc((string) ($item['name'] ?: '—')) ?></td>
                        <td><span class="dst-badge <?= esc($badges[$item['status']] ?? 'inactive', 'attr') ?>"><?= esc($labels[$item['status']] ?? ucfirst((string) $item['status'])) ?></span></td>
                        <td class="dst-meta"><?= esc(strtoupper((string) ($item['locale'] ?: '—'))) ?></td>
                        <td class="dst-meta"><?= esc(date('M j, Y', strtotime((string) $item['created_at']))) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <?php if ($item['status'] !== 'confirmed'): ?>
                                    <form method="post" action="<?= admin_url('newsletter/' . (int) $item['id'] . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn btn-success-soft btn-icon" title="Confirm"><i class="fa-solid fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($item['status'] !== 'unsubscribed'): ?>
                                    <form method="post" action="<?= admin_url('newsletter/' . (int) $item['id'] . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="unsubscribed">
                                        <button class="btn btn-soft btn-icon" title="Unsubscribe"><i class="fa-solid fa-ban"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= admin_url('newsletter/' . (int) $item['id'] . '/delete') ?>" data-confirm="Remove this subscriber permanently?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger-soft btn-icon" title="Remove"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="mt-3"><ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul></nav>
    <?php endif; ?>
<?php endif; ?>

<div class="modal fade" id="subscriberModal" tabindex="-1" aria-labelledby="subscriberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('newsletter/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title" id="subscriberModalLabel">Add subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="subscriberEmail">Email</label>
                <input type="email" class="form-control mb-3" id="subscriberEmail" name="email" required maxlength="190">
                <label class="form-label" for="subscriberName">Name (optional)</label>
                <input class="form-control" id="subscriberName" name="name" maxlength="120">
                <div class="form-text">Manual additions are confirmed immediately.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand">Add subscriber</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
