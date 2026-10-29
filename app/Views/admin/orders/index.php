<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('orders/export') ?>" class="btn btn-soft"><i class="fa-solid fa-file-csv me-2"></i>Export CSV</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = static function (array $overrides = []) use ($status, $query): string {
    $params = array_filter(array_merge(['status' => $status, 'q' => $query], $overrides), static fn ($v) => $v !== '' && $v !== null);

    return admin_url('orders' . ($params ? '?' . http_build_query($params) : ''));
};
?>
<div class="dst-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex flex-wrap gap-1 me-auto">
                <a href="<?= $qs(['status' => '']) ?>" class="btn btn-sm <?= $status === '' ? 'btn-brand' : 'btn-soft' ?>">All <span class="ms-1" style="opacity:.65;"><?= (int) $counts['all'] ?></span></a>
                <?php foreach ($statuses as $key => $label): ?>
                    <a href="<?= $qs(['status' => $key]) ?>" class="btn btn-sm <?= $status === $key ? 'btn-brand' : 'btn-soft' ?>"><?= esc($label) ?> <span class="ms-1" style="opacity:.65;"><?= (int) ($counts[$key] ?? 0) ?></span></a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= admin_url('orders') ?>" class="d-flex gap-2">
                <input type="hidden" name="status" value="<?= esc($status, 'attr') ?>">
                <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-sm" placeholder="Name, email or text…" style="width:220px;">
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-bag-shopping"></i>
            <h3>No orders yet</h3>
            <p>Enquiries submitted from product pages will appear here with their status.</p>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Customer</th><th>Subject</th><th>Status</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="min-width:180px;">
                            <div class="fw-650"><?= esc($item['name']) ?></div>
                            <div class="text-muted-2 small"><?= esc($item['email']) ?><?= ! empty($item['phone']) ? ' · ' . esc($item['phone']) : '' ?></div>
                        </td>
                        <td class="small" style="max-width:360px;">
                            <div class="truncate"><?= esc($item['content_title'] ?: 'Content #' . (int) $item['content_id']) ?></div>
                            <div class="text-muted-2 truncate"><?= esc(mb_strimwidth((string) $item['message'], 0, 110, '…')) ?></div>
                        </td>
                        <td><span class="dst-badge <?= in_array($item['status'], ['won', 'contacted'], true) ? 'published' : ($item['status'] === 'lost' ? 'rejected' : 'pending') ?>"><?= esc($statuses[$item['status']] ?? ucfirst((string) $item['status'])) ?></span></td>
                        <td class="dst-meta"><?= esc(date('M j, Y', strtotime((string) $item['created_at']))) ?></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('orders/' . (int) $item['id']) ?>" title="Open"><i class="fa-solid fa-arrow-right"></i></a>
                                <form method="post" action="<?= admin_url('orders/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this order?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger-soft btn-icon" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
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
<?= $this->endSection() ?>
