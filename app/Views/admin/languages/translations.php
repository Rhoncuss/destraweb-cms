<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('languages') ?>" class="btn btn-soft">Back to languages</a>
    <button form="translationsForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save translations</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="dst-lang-chip"><?= esc(strtoupper((string) $language['code'])) ?></span>
            <div class="me-auto min-w-0">
                <div class="fw-650"><?= esc((string) $language['native_name']) ?></div>
                <div class="text-muted-2 small"><?= esc((string) $language['name']) ?> · <?= $language['direction'] === 'rtl' ? 'Right to left' : 'Left to right' ?> · <?= number_format($total) ?> strings</div>
            </div>
            <form method="get" action="<?= admin_url('languages/' . (int) $language['id'] . '/translations') ?>" class="d-flex flex-wrap gap-2">
                <input type="search" name="q" value="<?= esc($q, 'attr') ?>" class="form-control form-control-sm" placeholder="Search keys or text…" style="width:200px;">
                <select name="group" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All groups</option>
                    <?php foreach ($groups as $groupName): ?>
                        <option value="<?= esc($groupName, 'attr') ?>" <?= $group === $groupName ? 'selected' : '' ?>><?= esc($groupName) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addKeyModal"><i class="fa-solid fa-plus me-1"></i>Add key</button>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-9">
        <?php if ($items === []): ?>
            <div class="dst-card">
                <div class="dst-empty">
                    <i class="fa-solid fa-language"></i>
                    <h3>No translations yet</h3>
                    <p><?= $q !== '' || $group !== '' ? 'Nothing matched this filter.' : 'Start with the starter string pack, then fine-tune every label.' ?></p>
                    <?php if ($q === '' && $group === ''): ?>
                        <form method="post" action="<?= admin_url('languages/' . (int) $language['id'] . '/seed') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-brand">Add starter strings</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
        <form id="translationsForm" method="post" action="<?= admin_url('languages/' . (int) $language['id'] . '/translations') ?>">
            <?= csrf_field() ?>
            <div class="dst-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr><th style="width:120px;">Group</th><th style="width:34%;">Key</th><th>Value</th><th style="width:52px;"></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $row): $rowId = (int) $row['id']; ?>
                            <tr>
                                <td><span class="dst-chip"><?= esc((string) $row['group_name']) ?></span></td>
                                <td><code class="dst-trans-key"><?= esc((string) $row['trans_key']) ?></code></td>
                                <td><textarea class="form-control form-control-sm" rows="2" name="values[<?= $rowId ?>]" placeholder="Leave blank to use the built-in English string"><?= esc((string) ($row['trans_value'] ?? '')) ?></textarea></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-danger-soft btn-icon" title="Delete key"
                                            data-delete-key="<?= admin_url('languages/' . (int) $language['id'] . '/translations/' . $rowId . '/delete') ?>">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted-2 small">Blank values fall back to the theme's English copy.</span>
                <button class="btn btn-brand" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save translations</button>
            </div>
        </form>
        <?php endif; ?>

        <?php if ($pages > 1): ?>
            <nav class="mt-3" aria-label="Translation pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <?php $link = admin_url('languages/' . (int) $language['id'] . '/translations?' . http_build_query(array_filter(['q' => $q, 'group' => $group, 'page' => $i]))); ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= esc($link, 'attr') ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <div class="col-lg-3">
        <div class="dst-card mb-3">
            <div class="card-header"><h2 class="card-title">Groups</h2></div>
            <div class="card-body d-grid gap-1">
                <a class="btn btn-soft btn-sm text-start <?= $group === '' ? 'active' : '' ?>" href="<?= admin_url('languages/' . (int) $language['id'] . '/translations') ?>">All groups</a>
                <?php foreach ($groups as $groupName): ?>
                    <a class="btn btn-soft btn-sm text-start <?= $group === $groupName ? 'active' : '' ?>"
                       href="<?= admin_url('languages/' . (int) $language['id'] . '/translations?group=' . rawurlencode($groupName)) ?>"><?= esc($groupName) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Starter pack</h2></div>
            <div class="card-body">
                <p class="text-muted-2 small mb-2">
                    <?= count($suggested) > 0
                        ? count($suggested) . ' of the built-in interface strings are not in this language yet.'
                        : 'Every starter string is already present.' ?>
                </p>
                <?php if ($suggested !== []): ?>
                    <form method="post" action="<?= admin_url('languages/' . (int) $language['id'] . '/seed') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-soft btn-sm w-100"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Add missing starter keys</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addKeyModal" tabindex="-1" aria-labelledby="addKeyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('languages/' . (int) $language['id'] . '/translations/add') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" id="addKeyLabel">Add a translation key</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="newGroup">Group</label>
                    <input type="text" class="form-control" id="newGroup" name="group_name" value="<?= esc($group !== '' ? $group : 'general', 'attr') ?>" maxlength="60" placeholder="general">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="newKey">Key</label>
                    <input type="text" class="form-control" id="newKey" name="trans_key" maxlength="190" required placeholder="read_more">
                    <div class="text-muted-2 small mt-1">Letters, numbers, dots, dashes and underscores.</div>
                </div>
                <div>
                    <label class="form-label" for="newValue">Value in <?= esc((string) $language['native_name']) ?></label>
                    <textarea class="form-control" id="newValue" name="trans_value" rows="3" maxlength="20000"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand" type="submit">Add key</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-delete-key]').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-delete-key');
            Swal.fire({
                title: 'Delete this key?', text: 'The translation is removed for this language.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                Destra.postForm(url, {}).then(function (json) {
                    if (json.success === false) {
                        Destra.toast('error', json.error || 'Could not delete.');
                        return;
                    }
                    button.closest('tr').remove();
                    Destra.toast('success', json.message || 'Deleted.');
                });
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
