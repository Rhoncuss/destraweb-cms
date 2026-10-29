<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('forms/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New form</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($forms === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-rectangle-list"></i>
            <h3>No forms yet</h3>
            <p>Create a contact form or survey, then embed it on any page with a shortcode.</p>
            <a class="btn btn-brand" href="<?= admin_url('forms/create') ?>">Create your first form</a>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Form</th><th class="text-end">Fields</th><th class="text-end">Submissions</th><th class="text-end">Unread</th><th>Live</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($forms as $item): ?>
                    <?php
                    $decoded    = json_decode((string) ($item['fields'] ?? ''), true);
                    $fieldCount = is_array($decoded) ? count($decoded) : 0;
                    $pending    = (int) ($unread[(int) $item['id']] ?? 0);
                    ?>
                    <tr>
                        <td style="min-width:230px;">
                            <div class="fw-650"><?= esc($item['name']) ?></div>
                            <div class="text-muted-2 small">/forms/<?= esc((string) $item['slug']) ?><?= ! empty($item['description']) ? ' · ' . esc(mb_strimwidth((string) $item['description'], 0, 60, '…')) : '' ?></div>
                        </td>
                        <td class="text-end dst-meta"><?= $fieldCount ?></td>
                        <td class="text-end dst-meta"><?= number_format((int) $item['submissions_count']) ?></td>
                        <td class="text-end">
                            <?php if ($pending > 0): ?>
                                <a href="<?= admin_url('inbox?form=' . (int) $item['id'] . '&unread=1') ?>" class="dst-badge new text-decoration-none"><?= $pending ?> new</a>
                            <?php else: ?>
                                <span class="text-muted-2 small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" data-dst-toggle="<?= admin_url('forms/' . (int) $item['id'] . '/toggle') ?>" <?= ! empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('inbox?form=' . (int) $item['id']) ?>" title="Submissions"><i class="fa-solid fa-inbox"></i></a>
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('forms/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('forms/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this form and all of its submissions?">
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
