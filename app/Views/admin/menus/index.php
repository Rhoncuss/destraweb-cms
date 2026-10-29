<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#menuModal"><i class="fa-solid fa-plus me-2"></i>New menu</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <?php if (empty($menus)): ?>
        <div class="dst-empty">
            <i class="fa-solid fa-bars-staggered"></i>
            <h3>No menus yet</h3>
            <p>A menu holds the links shown in your header, footer or sidebar. Create one, then add items.</p>
            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#menuModal">Create a menu</button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Menu</th><th>Slug</th><th>Location</th><th class="text-end">Items</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($menus as $menu): ?>
                    <tr>
                        <td class="dst-title"><?= esc($menu['name']) ?></td>
                        <td class="dst-meta"><?= esc($menu['slug']) ?></td>
                        <td><span class="dst-chip"><?= esc(ucfirst($menu['location'])) ?></span></td>
                        <td class="text-end dst-meta"><?= (int) ($counts[(int) $menu['id']] ?? 0) ?></td>
                        <td><span class="dst-badge <?= ! empty($menu['is_active']) ? 'published' : 'draft' ?>"><?= ! empty($menu['is_active']) ? 'Active' : 'Inactive' ?></span></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('menus/' . (int) $menu['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('menus/' . (int) $menu['id'] . '/delete') ?>" data-confirm="Delete this menu and all of its items?">
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
    <?php endif; ?>
</div>

<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('menus/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">New menu</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="menuName">Name</label>
                    <input class="form-control" id="menuName" name="name" maxlength="120" required placeholder="Primary navigation">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="menuLocation">Location</label>
                    <select class="form-select" id="menuLocation" name="location">
                        <option value="primary">Primary (header)</option>
                        <option value="footer">Footer</option>
                        <option value="sidebar">Sidebar</option>
                        <option value="mobile">Mobile</option>
                        <option value="topbar">Top bar</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="menuSlug">Slug</label>
                    <input class="form-control" id="menuSlug" name="slug" maxlength="120" placeholder="auto-generated">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand" type="submit">Create menu</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
