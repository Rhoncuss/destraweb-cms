<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#pluginUploadModal"><i class="fa-solid fa-file-zipper me-2"></i>Upload plugin</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="dst-stat">
            <span class="stat-label">Plugins</span>
            <span class="stat-value"><?= count($cards) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-plug me-1"></i>detected modules</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-ocean">
            <span class="stat-label">Active</span>
            <span class="stat-value"><?= (int) $activeCount ?></span>
            <span class="stat-foot"><i class="fa-solid fa-bolt me-1"></i>running on every request</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-sunrise">
            <span class="stat-label">Isolation</span>
            <span class="stat-value" style="font-size:22px;">Sandboxed</span>
            <span class="stat-foot"><i class="fa-solid fa-shield-halved me-1"></i>own views &amp; assets</span>
        </div>
    </div>
</div>

<?php if ($cards === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-solid fa-plug"></i>
            <h3>No plugins installed</h3>
            <p>Drop a plugin folder into <code><?= esc($pluginsPath) ?></code> or upload a ZIP archive. Each plugin has a <code>plugin.json</code> manifest and its own classes, views and assets.</p>
            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#pluginUploadModal">Upload a plugin</button>
        </div>
    </div>
<?php else: ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Plugin</th><th>Version</th><th>Author</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($cards as $card): ?>
                    <tr>
                        <td class="min-w-0">
                            <div class="d-flex align-items-center gap-2">
                                <div class="dst-icon-chip"><i class="fa-solid fa-plug"></i></div>
                                <div class="min-w-0">
                                    <div class="fw-650 truncate"><?= esc($card['name']) ?></div>
                                    <div class="text-muted-2 small truncate" style="max-width:520px;"><?= esc($card['description']) ?></div>
                                    <div class="text-muted-2 small"><code class="dst-trans-key"><?= esc($card['slug']) ?></code></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="dst-chip">v<?= esc($card['version']) ?></span></td>
                        <td class="dst-meta"><?= esc($card['author'] !== '' ? $card['author'] : '—') ?></td>
                        <td>
                            <?php if ($card['active']): ?>
                                <span class="dst-badge active">Active</span>
                            <?php elseif ($card['installed']): ?>
                                <span class="dst-badge inactive">Inactive</span>
                            <?php else: ?>
                                <span class="dst-badge draft">Not installed</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <button type="button" class="btn <?= $card['active'] ? 'btn-soft' : 'btn-success-soft' ?> btn-sm"
                                        data-plugin-toggle="<?= admin_url('plugins/' . $card['slug'] . '/toggle') ?>"
                                        data-plugin-active="<?= $card['active'] ? '1' : '0' ?>">
                                    <i class="fa-solid <?= $card['active'] ? 'fa-pause' : 'fa-play' ?> me-1"></i><?= $card['active'] ? 'Deactivate' : ($card['installed'] ? 'Activate' : 'Install &amp; activate') ?>
                                </button>
                                <?php if ($card['has_settings']): ?>
                                    <a class="btn btn-soft btn-icon" href="<?= admin_url('plugins/' . $card['slug'] . '/settings') ?>" title="Settings"><i class="fa-solid fa-gear"></i></a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger-soft btn-icon" title="Remove"
                                        data-plugin-delete="<?= admin_url('plugins/' . $card['slug'] . '/delete') ?>"
                                        data-plugin-name="<?= esc($card['name'], 'attr') ?>">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="dst-card mt-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <div class="dst-icon-chip"><i class="fa-solid fa-box-open"></i></div>
        <div class="min-w-0 flex-grow-1">
            <div class="fw-650">Plugins are portable</div>
            <div class="text-muted-2 small">Hooks, shortcodes, routes and settings live inside the plugin folder. Removing a plugin never touches your content.</div>
        </div>
        <span class="dst-chip">plugin.json</span>
    </div>
</div>

<div class="modal fade" id="pluginUploadModal" tabindex="-1" aria-labelledby="pluginUploadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('plugins/install') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" id="pluginUploadLabel">Upload a plugin</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted-2 small">Select a ZIP archive containing a single plugin folder with a valid <code>plugin.json</code> manifest.</p>
                <input type="file" class="form-control" name="plugin" accept=".zip,application/zip" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand" type="submit"><i class="fa-solid fa-upload me-2"></i>Upload</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-plugin-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            button.disabled = true;
            Destra.postForm(button.getAttribute('data-plugin-toggle'), {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
                if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 350); }
                else { button.disabled = false; }
            }).catch(function () {
                button.disabled = false;
                Destra.toast('error', 'Network error — please retry.');
            });
        });
    });

    document.querySelectorAll('[data-plugin-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            var name = button.getAttribute('data-plugin-name') || 'this plugin';
            Swal.fire({
                title: 'Remove ' + name + '?',
                text: 'The plugin folder and its settings are deleted. Your content stays untouched.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                Destra.postForm(button.getAttribute('data-plugin-delete'), {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Removed');
                    if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 350); }
                });
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
