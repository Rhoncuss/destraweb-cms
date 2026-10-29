<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('redirects/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>Add redirect</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="dst-stat">
            <span class="stat-label">Redirects</span>
            <span class="stat-value"><?= number_format($total) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-signs-post me-1"></i><?= (int) $activeCount ?> active</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-ocean">
            <span class="stat-label">Times followed</span>
            <span class="stat-value"><?= number_format($hitCount) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i>total hits</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-sunrise">
            <span class="stat-label">Search engine safety</span>
            <span class="stat-value" style="font-size:22px;">301</span>
            <span class="stat-foot"><i class="fa-solid fa-shield-halved me-1"></i>permanent by default</span>
        </div>
    </div>
</div>

<div class="dst-card">
    <div class="card-header">
        <h2 class="card-title">URL redirects</h2>
        <form method="get" action="<?= admin_url('redirects') ?>" class="d-flex gap-2">
            <input type="search" name="q" value="<?= esc($q, 'attr') ?>" class="form-control form-control-sm" placeholder="Search paths…" style="width:220px;">
            <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <?php if ($items === []): ?>
        <div class="dst-empty">
            <i class="fa-solid fa-signs-post"></i>
            <h3>No redirects yet</h3>
            <p>When you rename a slug or retire a page, point the old address here so links and search rankings survive.</p>
            <a class="btn btn-brand" href="<?= admin_url('redirects/create') ?>">Create a redirect</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>From</th><th>To</th><th>Type</th><th class="text-end">Hits</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): $isActive = (int) $item['is_active'] === 1; ?>
                    <tr>
                        <td><code class="dst-trans-key"><?= esc((string) $item['from_path']) ?></code></td>
                        <td class="min-w-0"><div class="truncate" style="max-width:360px;"><?= esc((string) $item['to_path']) ?></div></td>
                        <td><span class="dst-chip"><?= (int) $item['status_code'] ?></span></td>
                        <td class="text-end dst-meta"><?= number_format((int) $item['hits']) ?></td>
                        <td><span class="dst-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Paused' ?></span></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('redirects/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <button type="button" class="btn btn-soft btn-icon" title="<?= $isActive ? 'Pause' : 'Activate' ?>"
                                        data-redirect-post="<?= admin_url('redirects/' . (int) $item['id'] . '/toggle') ?>">
                                    <i class="fa-solid <?= $isActive ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                </button>
                                <button type="button" class="btn btn-danger-soft btn-icon" title="Delete"
                                        data-redirect-post="<?= admin_url('redirects/' . (int) $item['id'] . '/delete') ?>"
                                        data-redirect-confirm="Delete the redirect for <?= esc((string) $item['from_path'], 'attr') ?>?">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="mt-3" aria-label="Redirect pagination">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= admin_url('redirects?' . http_build_query(array_filter(['q' => $q, 'page' => $i]))) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-redirect-post]').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-redirect-post');
            var message = button.getAttribute('data-redirect-confirm');
            var run = function () {
                Destra.postForm(url, {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
                    if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 350); }
                }).catch(function () { Destra.toast('error', 'Network error — please retry.'); });
            };
            if (!message) { run(); return; }
            Swal.fire({
                title: 'Are you sure?', text: message, icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) { if (result.isConfirmed) { run(); } });
        });
    });
})();
</script>
<?= $this->endSection() ?>
