<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('languages/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>Add language</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Languages</span>
            <span class="stat-value"><?= count($items) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-language me-1"></i><?= count(array_filter($items, static fn ($l) => (int) $l['is_active'] === 1)) ?> active</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">UI translations</span>
            <span class="stat-value"><?= number_format(array_sum($counts)) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-keyboard me-1"></i>interface strings</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Translated entries</span>
            <span class="stat-value"><?= number_format(array_sum($contentCounts)) ?></span>
            <span class="stat-foot"><i class="fa-regular fa-file-lines me-1"></i>content translations</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Default locale</span>
            <span class="stat-value" style="font-size:22px;"><?= esc(strtoupper((string) (service('localization')->defaultCode()))) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-earth-americas me-1"></i>served without a prefix</span>
        </div>
    </div>
</div>

<div class="dst-card">
    <div class="card-header">
        <h2 class="card-title">Site languages</h2>
        <span class="text-muted-2 small">Right-to-left layouts switch automatically.</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Language</th>
                    <th>Direction</th>
                    <th>Default</th>
                    <th class="text-end">UI strings</th>
                    <th class="text-end">Content</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php $isDefault = (int) $item['is_default'] === 1; $isActive = (int) $item['is_active'] === 1; ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="dst-lang-chip"><?= esc(strtoupper((string) $item['code'])) ?></span>
                            <div class="min-w-0">
                                <div class="fw-650 truncate"><?= esc((string) $item['native_name']) ?></div>
                                <div class="text-muted-2 small"><?= esc((string) $item['name']) ?> · <?= esc((string) $item['code']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dst-chip"><?= $item['direction'] === 'rtl' ? 'RTL' : 'LTR' ?></span></td>
                    <td>
                        <?php if ($isDefault): ?>
                            <span class="dst-badge published"><i class="fa-solid fa-star me-1"></i>Default</span>
                        <?php else: ?>
                            <button type="button" class="btn btn-soft btn-sm" data-lang-post="<?= admin_url('languages/' . (int) $item['id'] . '/default') ?>" data-lang-confirm="Make <?= esc((string) $item['name'], 'attr') ?> the default language?">Make default</button>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a class="dst-meta" href="<?= admin_url('languages/' . (int) $item['id'] . '/translations') ?>"><?= (int) ($counts[(string) $item['code']] ?? 0) ?></a>
                    </td>
                    <td class="text-end dst-meta"><?= (int) ($contentCounts[(string) $item['code']] ?? 0) ?></td>
                    <td>
                        <span class="dst-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="text-end">
                        <div class="table-actions">
                            <a class="btn btn-soft btn-icon" href="<?= admin_url('languages/' . (int) $item['id'] . '/translations') ?>" title="Translations"><i class="fa-solid fa-language"></i></a>
                            <a class="btn btn-soft btn-icon" href="<?= admin_url('languages/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <button type="button" class="btn btn-soft btn-icon" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>"
                                    data-lang-post="<?= admin_url('languages/' . (int) $item['id'] . '/toggle') ?>"
                                    <?= $isDefault && $isActive ? 'disabled' : '' ?>>
                                <i class="fa-solid <?= $isActive ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                            </button>
                            <button type="button" class="btn btn-danger-soft btn-icon" title="Delete"
                                    data-lang-post="<?= admin_url('languages/' . (int) $item['id'] . '/delete') ?>"
                                    data-lang-confirm="Delete <?= esc((string) $item['name'], 'attr') ?>? Interface strings for this language are removed too."
                                    <?= $isDefault ? 'disabled' : '' ?>>
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-transparent text-muted-2 small">
        Add a language, then visit its translation editor to localise every interface string. Content is translated per entry from the content editor.
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-lang-post]').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-lang-post');
            var confirmText = button.getAttribute('data-lang-confirm');
            var run = function () {
                button.disabled = true;
                Destra.postForm(url, {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
                    if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 400); }
                    else { button.disabled = false; }
                }).catch(function () {
                    Destra.toast('error', 'Network error — please retry.');
                    button.disabled = false;
                });
            };
            if (!confirmText) { run(); return; }
            Swal.fire({
                title: 'Are you sure?', text: confirmText, icon: 'warning',
                showCancelButton: true, confirmButtonText: 'Yes, continue', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) { if (result.isConfirmed) { run(); } });
        });
    });
})();
</script>
<?= $this->endSection() ?>
