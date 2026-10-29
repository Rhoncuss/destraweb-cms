<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <?php if ($unreadCount > 0): ?>
        <button class="btn btn-soft" id="notificationsReadAll"><i class="fa-solid fa-check-double me-2"></i>Mark all read</button>
    <?php endif; ?>
    <?php if ($allCount - $unreadCount > 0): ?>
        <button class="btn btn-danger-soft" id="notificationsClearRead"><i class="fa-regular fa-trash-can me-2"></i>Clear read</button>
    <?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <div class="card-header flex-wrap gap-2">
        <h2 class="card-title">Inbox <span class="dst-chip ms-2"><?= number_format($unreadCount) ?> unread</span></h2>
        <div class="dst-tabs-scroll">
            <?php foreach (['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $key => $label): ?>
                <a class="dst-chip <?= $filter === $key ? 'is-active' : '' ?>" href="<?= admin_url('notifications' . ($key === 'all' ? '' : '?filter=' . $key)) ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="dst-empty">
            <i class="fa-regular fa-bell"></i>
            <h3><?= $filter === 'all' ? 'You are all caught up' : 'Nothing here' ?></h3>
            <p><?= $filter === 'unread' ? 'No unread notifications — nice work.' : 'Notifications about comments, orders, sign-ins and background jobs land here.' ?></p>
        </div>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($items as $item): $isUnread = (int) $item['is_read'] === 0; $hasUrl = trim((string) ($item['url'] ?? '')) !== ''; ?>
                <div class="notif-row <?= $isUnread ? 'is-unread' : '' ?>" data-notification-row>
                    <div class="notif-icon"><i class="<?= esc($item['icon_class'], 'attr') ?>"></i></div>
                    <div class="notif-body min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-650"><?= esc((string) $item['title']) ?></span>
                            <span class="dst-chip"><?= esc(ucfirst((string) $item['type'])) ?></span>
                            <?php if ($isUnread): ?><span class="dst-badge new">New</span><?php endif; ?>
                        </div>
                        <?php if (trim((string) ($item['message'] ?? '')) !== ''): ?>
                            <p class="notif-message mb-1"><?= esc((string) $item['message']) ?></p>
                        <?php endif; ?>
                        <div class="dst-meta"><?= esc(human_date($item['created_at'], 'M j, Y H:i')) ?></div>
                    </div>
                    <div class="notif-actions table-actions">
                        <?php if ($hasUrl): ?>
                            <button type="button" class="btn btn-brand btn-sm" data-notification-open="<?= admin_url('notifications/' . (int) $item['id'] . '/open') ?>"><i class="fa-solid fa-arrow-right me-1"></i>Open</button>
                        <?php elseif ($isUnread): ?>
                            <button type="button" class="btn btn-soft btn-sm" data-notification-read="<?= admin_url('notifications/' . (int) $item['id'] . '/read') ?>"><i class="fa-solid fa-check me-1"></i>Read</button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger-soft btn-icon" title="Delete"
                                data-notification-delete="<?= admin_url('notifications/' . (int) $item['id'] . '/delete') ?>">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3" aria-label="Notification pagination">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= admin_url('notifications?' . http_build_query(array_filter(['filter' => $filter === 'all' ? '' : $filter, 'page' => $i]))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    function reloadSoon() { setTimeout(function () { window.location.reload(); }, 300); }

    document.querySelectorAll('[data-notification-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            Destra.postForm(button.getAttribute('data-notification-open'), {}).then(function (json) {
                if (json.success === false) { Destra.toast('error', json.error || 'Could not open.'); return; }
                window.location.href = json.url;
            }).catch(function () { Destra.toast('error', 'Network error — please retry.'); });
        });
    });

    document.querySelectorAll('[data-notification-read]').forEach(function (button) {
        button.addEventListener('click', function () {
            Destra.postForm(button.getAttribute('data-notification-read'), {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
                if (json.success !== false) { reloadSoon(); }
            });
        });
    });

    document.querySelectorAll('[data-notification-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            Swal.fire({
                title: 'Delete this notification?', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                Destra.postForm(button.getAttribute('data-notification-delete'), {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Deleted');
                    if (json.success !== false) { reloadSoon(); }
                });
            });
        });
    });

    var readAll = document.getElementById('notificationsReadAll');
    if (readAll) {
        readAll.addEventListener('click', function () {
            readAll.disabled = true;
            Destra.postForm('<?= admin_url('notifications/read-all') ?>', {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Done');
                if (json.success !== false) { reloadSoon(); } else { readAll.disabled = false; }
            });
        });
    }

    var clearRead = document.getElementById('notificationsClearRead');
    if (clearRead) {
        clearRead.addEventListener('click', function () {
            Swal.fire({
                title: 'Delete every read notification?', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Clear', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                Destra.postForm('<?= admin_url('notifications/clear-read') ?>', {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Cleared');
                    if (json.success !== false) { reloadSoon(); }
                });
            });
        });
    }
})();
</script>
<?= $this->endSection() ?>
