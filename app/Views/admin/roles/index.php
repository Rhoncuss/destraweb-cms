<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('users') ?>" class="btn btn-soft"><i class="fa-solid fa-user-group me-2"></i>Users</a>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#roleModal"><i class="fa-solid fa-plus me-2"></i>New role</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Role</th><th>Slug</th><th class="text-end">Users</th><th class="text-end">Permissions</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td style="min-width:220px;">
                        <div class="fw-650">
                            <?= esc($role['name']) ?>
                            <?php if (! empty($role['is_system'])): ?><span class="dst-chip ms-1" style="font-size:9px;">built-in</span><?php endif; ?>
                        </div>
                        <div class="text-muted-2 small"><?= esc((string) ($role['description'] ?: 'No description')) ?></div>
                    </td>
                    <td><code class="small"><?= esc($role['slug']) ?></code></td>
                    <td class="text-end dst-meta"><?= (int) ($userCounts[(int) $role['id']] ?? 0) ?></td>
                    <td class="text-end dst-meta">
                        <?php if ($role['slug'] === 'super-admin'): ?>
                            All access
                        <?php else: ?>
                            <?= (int) ($permCounts[(int) $role['id']] ?? 0) ?> / <?= (int) $permissionTotal ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="table-actions">
                            <a class="btn btn-soft btn-icon" href="<?= admin_url('roles/' . (int) $role['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <?php if (empty($role['is_system'])): ?>
                                <form method="post" action="<?= admin_url('roles/' . (int) $role['id'] . '/delete') ?>" data-confirm="Delete this role?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger-soft btn-icon" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="roleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('roles/create') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalLabel">New role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="roleName">Name</label>
                <input class="form-control mb-3" id="roleName" name="name" maxlength="100" required placeholder="Marketing manager">
                <label class="form-label" for="roleDescription">Description</label>
                <input class="form-control" id="roleDescription" name="description" maxlength="255" placeholder="Runs campaigns and reads reports">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand">Create role</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
