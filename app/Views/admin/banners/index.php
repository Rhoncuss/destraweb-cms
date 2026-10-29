<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('banners/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New banner</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card mb-3">
    <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
        <div class="d-flex flex-wrap gap-1 me-auto">
            <a href="<?= admin_url('banners') ?>" class="btn btn-sm <?= $placement === '' ? 'btn-brand' : 'btn-soft' ?>">All placements</a>
            <?php foreach ($placements as $key => $label): ?>
                <a href="<?= admin_url('banners?placement=' . urlencode($key)) ?>" class="btn btn-sm <?= $placement === $key ? 'btn-brand' : 'btn-soft' ?>"><?= esc($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-rectangle-ad"></i>
            <h3>No banners in this placement</h3>
            <p>Add a hero slide, a promotional strip or an advertising unit to any placement.</p>
            <a class="btn btn-brand" href="<?= admin_url('banners/create') ?>">Create a banner</a>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Banner</th><th>Placement</th><th>Type</th><th class="text-end">Impressions</th><th class="text-end">Clicks</th><th>Schedule</th><th>Live</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php
                    $image = ! empty($item['image_id']) ? service('media')->find((int) $item['image_id']) : null;
                    $thumb = $image ? service('media')->url($image, 'thumb') : '';
                    $rate  = (int) $item['impressions'] > 0 ? round(((int) $item['clicks'] / (int) $item['impressions']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td style="min-width:200px;">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($thumb): ?>
                                    <img src="<?= esc($thumb, 'attr') ?>" alt="" style="width:56px;height:38px;object-fit:cover;border-radius:8px;">
                                <?php else: ?>
                                    <span class="dst-icon-chip"><i class="fa-solid fa-code"></i></span>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <div class="fw-650 truncate"><?= esc($item['title'] ?: $item['name']) ?></div>
                                    <div class="text-muted-2 small truncate"><?= esc((string) $item['name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="dst-chip"><?= esc($placements[$item['placement']] ?? $item['placement']) ?></span></td>
                        <td class="dst-meta"><?= esc(ucfirst((string) $item['type'])) ?></td>
                        <td class="text-end dst-meta"><?= number_format((int) $item['impressions']) ?></td>
                        <td class="text-end dst-meta"><?= number_format((int) $item['clicks']) ?> <span class="small">(<?= esc((string) $rate) ?>%)</span></td>
                        <td class="dst-meta small">
                            <?php if (empty($item['starts_at']) && empty($item['ends_at'])): ?>
                                Always
                            <?php else: ?>
                                <?= esc($item['starts_at'] ? date('M j, Y', strtotime((string) $item['starts_at'])) : 'Now') ?>
                                –
                                <?= esc($item['ends_at'] ? date('M j, Y', strtotime((string) $item['ends_at'])) : 'Ongoing') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" data-dst-toggle="<?= admin_url('banners/' . (int) $item['id'] . '/toggle') ?>" <?= ! empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('banners/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('banners/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this banner?">
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
<?php endif; ?>
<?= $this->endSection() ?>
