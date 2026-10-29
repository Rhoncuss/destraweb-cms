<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('forms') ?>" class="btn btn-soft"><i class="fa-solid fa-list-check me-2"></i>Manage forms</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= admin_url('inbox') ?>" class="d-flex flex-wrap align-items-center gap-2">
            <select name="form" class="form-select form-select-sm" style="width:auto;">
                <option value="">All forms</option>
                <?php foreach ($forms as $formItem): ?>
                    <option value="<?= (int) $formItem['id'] ?>" <?= $formId === (int) $formItem['id'] ? 'selected' : '' ?>><?= esc($formItem['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-sm" placeholder="Search submissions…" style="width:220px;">
            <div class="form-check form-switch mb-0 ms-1">
                <input class="form-check-input" type="checkbox" id="unread" name="unread" value="1" <?= $unreadOnly ? 'checked' : '' ?>>
                <label class="form-check-label small" for="unread">Unread only</label>
            </div>
            <button class="btn btn-soft btn-sm" type="submit">Filter</button>
            <?php if ($formId > 0 || $unreadOnly || $query !== ''): ?>
                <a href="<?= admin_url('inbox') ?>" class="btn btn-soft btn-sm">Reset</a>
            <?php endif; ?>
            <span class="text-muted-2 small ms-auto"><?= number_format($total) ?> submission(s)</span>
        </form>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-inbox"></i>
            <h3>Nothing in the inbox</h3>
            <p>When someone submits a form, the message lands here.</p>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= admin_url('inbox/bulk') ?>" id="inboxBulkForm">
        <?= csrf_field() ?>
        <div class="dst-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:38px;"><input class="form-check-input" type="checkbox" data-check-all aria-label="Select all"></th>
                            <th>Form</th>
                            <th>Preview</th>
                            <th>Received</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" data-check-item aria-label="Select submission"></td>
                            <td style="min-width:150px;">
                                <a class="fw-650 text-reset text-decoration-none" href="<?= admin_url('inbox/' . (int) $item['id']) ?>">
                                    <?php if (empty($item['is_read'])): ?><span class="dst-dot" style="position:static;display:inline-block;margin-inline-end:6px;"></span><?php endif; ?>
                                    <?= esc($item['form_name'] ?: 'Unknown form') ?>
                                </a>
                                <div class="text-muted-2 small">#<?= (int) $item['id'] ?></div>
                            </td>
                            <td class="small" style="max-width:420px;"><?= esc($item['preview'] ?: '—') ?></td>
                            <td class="dst-meta"><?= esc(date('M j, Y', strtotime((string) $item['created_at']))) ?><br><span class="small"><?= esc(date('H:i', strtotime((string) $item['created_at']))) ?></span></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a class="btn btn-soft btn-icon" href="<?= admin_url('inbox/' . (int) $item['id']) ?>" title="Open"><i class="fa-solid fa-arrow-right"></i></a>
                                    <form method="post" action="<?= admin_url('inbox/' . (int) $item['id'] . '/status') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="status" value="<?= empty($item['is_read']) ? 'read' : 'unread' ?>">
                                        <button class="btn btn-soft btn-icon" title="<?= empty($item['is_read']) ? 'Mark read' : 'Mark unread' ?>"><i class="fa-regular <?= empty($item['is_read']) ? 'fa-envelope-open' : 'fa-envelope' ?>"></i></button>
                                    </form>
                                    <form method="post" action="<?= admin_url('inbox/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this submission?">
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
            <div class="card-body border-top d-flex flex-wrap align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Bulk actions</option>
                    <option value="read">Mark read</option>
                    <option value="unread">Mark unread</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-soft btn-sm" type="submit">Apply</button>
            </div>
        </div>
    </form>

    <?php if ($pages > 1): ?>
        <?php
        $qs = static function (int $p) use ($formId, $unreadOnly, $query): string {
            return admin_url('inbox?' . http_build_query(array_filter([
                'form'   => $formId ?: '',
                'unread' => $unreadOnly ? '1' : '',
                'q'      => $query,
                'page'   => $p,
            ], static fn ($v) => $v !== '' && $v !== null)));
        };
        ?>
        <nav class="mt-3" aria-label="Inbox pagination">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $qs($i) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
