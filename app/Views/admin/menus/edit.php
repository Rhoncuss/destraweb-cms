<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('menus') ?>" class="btn btn-soft">All menus</a>
    <a href="<?= admin_url('menus/' . (int) $menu['id'] . '/items/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>Add item</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="dst-card mb-3">
            <div class="card-header">
                <h2 class="card-title">Menu items</h2>
                <span class="dst-chip"><?= count($items) ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="dst-empty py-4">
                        <i class="fa-solid fa-link"></i>
                        <h3>This menu is empty</h3>
                        <p>Add a link to a page, section, category, tag or a custom URL.</p>
                        <a class="btn btn-brand" href="<?= admin_url('menus/' . (int) $menu['id'] . '/items/create') ?>">Add the first item</a>
                    </div>
                <?php else: ?>
                    <div data-menu-items data-reorder-url="<?= admin_url('menus/' . (int) $menu['id'] . '/reorder') ?>">
                        <?php foreach ($items as $item): ?>
                            <div class="dst-sortable-item" data-menu-item data-id="<?= (int) $item['id'] ?>">
                                <span class="handle"><i class="fa-solid fa-grip-vertical"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-650 truncate">
                                        <?php if (! empty($item['icon'])): ?><i class="<?= esc($item['icon'], 'attr') ?> me-1 text-muted-2"></i><?php endif; ?>
                                        <?= esc($item['label']) ?>
                                        <?php if (! empty($item['parent_id'])): ?><span class="dst-chip ms-1" style="font-size:9px;">child</span><?php endif; ?>
                                    </div>
                                    <div class="text-muted-2 small truncate"><?= esc(ucfirst($item['type'])) ?> · <?= esc($item['resolved_url']) ?></div>
                                </div>
                                <?php if (empty($item['is_active'])): ?><span class="dst-badge draft">Hidden</span><?php endif; ?>
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('menus/' . (int) $menu['id'] . '/items/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('menus/' . (int) $menu['id'] . '/items/' . (int) $item['id'] . '/delete') ?>" data-confirm="Remove this item from the menu?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger-soft btn-icon" title="Remove"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-muted-2 small mt-2">Drag the handle to reorder. Order is saved automatically.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <form class="dst-card" method="post" action="<?= admin_url('menus/' . (int) $menu['id'] . '/update') ?>">
            <?= csrf_field() ?>
            <div class="card-header"><h2 class="card-title">Menu settings</h2></div>
            <div class="card-body">
                <label class="form-label" for="name">Name</label>
                <input class="form-control mb-3" id="name" name="name" maxlength="120" required value="<?= esc($menu['name']) ?>">

                <label class="form-label" for="slug">Slug</label>
                <input class="form-control mb-3" id="slug" name="slug" maxlength="120" value="<?= esc($menu['slug']) ?>">

                <label class="form-label" for="location">Location</label>
                <select class="form-select mb-3" id="location" name="location">
                    <?php foreach ($locations as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $menu['location'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! empty($menu['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button class="btn btn-brand w-100" type="submit">Save settings</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var list = document.querySelector('[data-menu-items]');
    if (!list || !window.Sortable) { return; }

    Sortable.create(list, {
        handle: '.handle',
        animation: 160,
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            var ids = Array.prototype.map.call(list.querySelectorAll('[data-menu-item]'), function (el) { return el.getAttribute('data-id'); });
            var body = new FormData();
            ids.forEach(function (id) { body.append('ids[]', id); });
            body.append(Destra.csrfName, Destra.csrfToken);
            fetch(list.getAttribute('data-reorder-url'), {
                method: 'POST',
                body: body,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Order saved');
            }).catch(function () { Destra.toast('error', 'Could not save the order.'); });
        }
    });
})();
</script>
<?= $this->endSection() ?>
