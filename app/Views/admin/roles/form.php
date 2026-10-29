<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('roles') ?>" class="btn btn-soft">Back to roles</a>
    <button form="roleForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save role</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="roleForm" method="post" action="<?= admin_url('roles/' . (int) $role['id'] . '/update') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Role details</h2>
                <?php if (! empty($role['is_system'])): ?><span class="dst-chip">Built-in</span><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control" id="name" name="name" maxlength="100" required value="<?= esc($role['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" maxlength="60" value="<?= esc($role['slug']) ?>" <?= ! empty($role['is_system']) ? 'disabled' : '' ?>>
                        <?php if (! empty($role['is_system'])): ?><input type="hidden" name="slug" value="<?= esc($role['slug'], 'attr') ?>"><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <input class="form-control" id="description" name="description" maxlength="255" value="<?= esc((string) ($role['description'] ?? '')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Permissions</h2>
                <?php if ($role['slug'] === 'super-admin'): ?>
                    <span class="dst-chip">Super admins always have full access</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($role['slug'] === 'super-admin'): ?>
                    <p class="text-muted-2 mb-0">This role bypasses permission checks. Changes here are ignored for super administrators.</p>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($grouped as $group => $permissions): ?>
                            <div class="dst-card" style="box-shadow:none;background:var(--dst-canvas);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-650"><?= esc($group) ?></div>
                                        <label class="text-muted-2 small d-flex align-items-center gap-2" style="cursor:pointer;">
                                            <input class="form-check-input m-0" type="checkbox" data-group-toggle="<?= esc($group, 'attr') ?>"> All
                                        </label>
                                    </div>
                                    <div class="row g-2">
                                        <?php foreach ($permissions as $perm): ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= (int) $perm['id'] ?>"
                                                           id="perm-<?= (int) $perm['id'] ?>" data-group="<?= esc($group, 'attr') ?>"
                                                           <?= in_array((int) $perm['id'], $granted, true) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="perm-<?= (int) $perm['id'] ?>">
                                                        <span class="fw-650"><?= esc($perm['name']) ?></span>
                                                        <span class="text-muted-2 d-block" style="font-size:11px;"><?= esc($perm['slug']) ?></span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Summary</h2></div>
            <div class="card-body">
                <div class="d-flex justify-content-between small mb-2"><span class="text-muted-2">Users in role</span><span class="fw-650"><?= $userCount ?></span></div>
                <div class="d-flex justify-content-between small mb-3"><span class="text-muted-2">Permissions</span><span class="fw-650"><?= $role['slug'] === 'super-admin' ? 'All' : count($granted) ?></span></div>
                <button class="btn btn-brand w-100" type="submit">Save role</button>
            </div>
        </div>
    </aside>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-group-toggle]').forEach(function (master) {
        master.addEventListener('change', function () {
            var group = master.getAttribute('data-group-toggle');
            document.querySelectorAll('[data-group]').forEach(function (box) {
                if (box.getAttribute('data-group') === group) { box.checked = master.checked; }
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
