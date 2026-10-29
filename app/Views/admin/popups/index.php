<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('popups/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New popup</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-window-restore"></i>
            <h3>No popups yet</h3>
            <p>Announce launches, collect emails or highlight offers with a well-timed overlay.</p>
            <a class="btn btn-brand" href="<?= admin_url('popups/create') ?>">Create a popup</a>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Popup</th><th>Trigger</th><th>Pages</th><th>Frequency</th><th class="text-end">Views</th><th>Live</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="min-width:220px;">
                            <div class="fw-650 truncate"><?= esc($item['title'] ?: $item['name']) ?></div>
                            <div class="text-muted-2 small truncate"><?= esc((string) $item['name']) ?></div>
                        </td>
                        <td class="dst-meta small">
                            <?= esc($triggers[$item['trigger_type']] ?? $item['trigger_type']) ?>
                            <?php if ($item['trigger_type'] === 'delay'): ?><span class="text-muted-2">(<?= (int) $item['trigger_value'] ?>s)</span><?php elseif ($item['trigger_type'] === 'scroll'): ?><span class="text-muted-2">(<?= (int) $item['trigger_value'] ?>%)</span><?php endif; ?>
                        </td>
                        <td><span class="dst-chip"><?= esc($pages[$item['pages']] ?? $item['pages']) ?></span></td>
                        <td class="dst-meta small"><?= esc($frequencies[$item['frequency']] ?? $item['frequency']) ?></td>
                        <td class="text-end dst-meta"><?= number_format((int) $item['views']) ?></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" data-dst-toggle="<?= admin_url('popups/' . (int) $item['id'] . '/toggle') ?>" <?= ! empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('popups/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('popups/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this popup?">
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
