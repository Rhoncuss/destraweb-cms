<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('roles') ?>" class="btn btn-soft"><i class="fa-solid fa-shield-halved me-2"></i>Roles</a>
    <a href="<?= admin_url('users/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New user</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= admin_url('users') ?>" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-sm" placeholder="Name, email or username…" style="width:230px;">
            <select name="role" class="form-select form-select-sm" style="width:auto;">
                <option value="">All roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= $roleId === (int) $role['id'] ? 'selected' : '' ?>><?= esc($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">Any status</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
            <button class="btn btn-soft btn-sm" type="submit">Filter</button>
            <span class="text-muted-2 small ms-auto"><?= count($items) ?> user(s)</span>
        </form>
    </div>
</div>

<div class="dst-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>User</th><th>Role</th><th>Status</th><th class="text-end">Content</th><th>Last sign-in</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td style="min-width:220px;">
                        <div class="d-flex align-items-center gap-2">
                            <?php if (! empty($item['avatar'])): ?>
                                <img src="<?= esc($item['avatar'], 'attr') ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <span class="dst-avatar" style="width:36px;height:36px;font-size:12px;"><?= esc(mb_strtoupper(mb_substr((string) $item['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <div class="fw-650 truncate"><?= esc($item['name']) ?><?= (int) $item['id'] === $currentId ? ' <span class="dst-chip ms-1" style="font-size:9px;">you</span>' : '' ?></div>
                                <div class="text-muted-2 small truncate"><?= esc($item['email']) ?> · @<?= esc($item['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dst-chip"><?= esc($item['role_name'] ?? 'No role') ?></span></td>
                    <td><span class="dst-badge <?= $item['status'] === 'active' ? 'published' : 'rejected' ?>"><?= esc(ucfirst((string) $item['status'])) ?></span></td>
                    <td class="text-end dst-meta"><?= number_format((int) ($counts[(int) $item['id']] ?? 0)) ?></td>
                    <td class="dst-meta small"><?= esc($item['last_login_at'] ? date('M j, Y H:i', strtotime((string) $item['last_login_at'])) : 'Never') ?></td>
                    <td class="text-end">
                        <div class="table-actions">
                            <?php if ((int) $item['id'] !== $currentId): ?>
                                <div class="form-check form-switch me-1">
                                    <input class="form-check-input" type="checkbox" data-dst-toggle="<?= admin_url('users/' . (int) $item['id'] . '/toggle') ?>" <?= $item['status'] === 'active' ? 'checked' : '' ?> title="Active">
                                </div>
                            <?php endif; ?>
                            <a class="btn btn-soft btn-icon" href="<?= admin_url('users/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <?php if ((int) $item['id'] !== $currentId): ?>
                                <form method="post" action="<?= admin_url('users/' . (int) $item['id'] . '/delete') ?>" data-confirm="Remove this user? Their content stays published.">
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
<?= $this->endSection() ?>
