<?php
$menuId    = (int) $menu['id'];
$itemId    = (int) ($item['id'] ?? 0);
$type      = (string) ($item['type'] ?? 'custom');
$type      = $type === 'page' ? 'content' : $type;
$action    = $item ? admin_url('menus/' . $menuId . '/items/' . $itemId . '/update') : admin_url('menus/' . $menuId . '/items/store');
$primary   = $translations[$defaultLocale] ?? [];
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('menus/' . $menuId . '/edit') ?>" class="btn btn-soft">Back to items</a>
    <button form="menuItemForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save item</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="menuItemForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Labels</h2>
                <span class="dst-chip"><?= esc($menu['name']) ?></span>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <?php foreach ($languages as $language): ?>
                        <?php $code = (string) $language['code']; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $code === $defaultLocale ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#item-<?= esc($code, 'attr') ?>" type="button" role="tab">
                                <?= esc($language['native_name'] ?: $language['name']) ?>
                                <?php if ($code === $defaultLocale): ?><span class="badge text-bg-light ms-1" style="font-size:9px;">default</span><?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content">
                    <?php foreach ($languages as $language): ?>
                        <?php
                        $code      = (string) $language['code'];
                        $tr        = $translations[$code] ?? [];
                        $isDefault = $code === $defaultLocale;
                        ?>
                        <div class="tab-pane fade <?= $isDefault ? 'show active' : '' ?>" id="item-<?= esc($code, 'attr') ?>" role="tabpanel">
                            <label class="form-label" for="label-<?= esc($code, 'attr') ?>">Navigation label</label>
                            <input type="text" class="form-control" id="label-<?= esc($code, 'attr') ?>"
                                   name="<?= $isDefault ? 'label' : 'translations[' . esc($code, 'attr') . '][label]' ?>"
                                   maxlength="190" value="<?= esc((string) ($tr['label'] ?? '')) ?>" <?= $isDefault ? 'required' : 'placeholder="Optional translation"' ?>>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Destination</h2></div>
            <div class="card-body">
                <label class="form-label" for="type">Link type</label>
                <select class="form-select mb-3" id="type" name="type" data-menu-type>
                    <?php foreach (['custom' => 'Custom URL', 'content' => 'Content page', 'category' => 'Category', 'tag' => 'Tag', 'section' => 'Section'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $type === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <div data-type-panel="custom">
                    <label class="form-label" for="url">URL</label>
                    <input type="text" class="form-control" id="url" name="url" maxlength="500"
                           value="<?= esc((string) ($item['url'] ?? '')) ?>" placeholder="/about or https://example.com">
                    <div class="form-text">Relative paths and absolute URLs are both accepted.</div>
                </div>

                <?php
                $panels = [
                    'content'  => ['label' => 'Content page', 'options' => $contents, 'placeholder' => 'Select a content page'],
                    'category' => ['label' => 'Category', 'options' => $categories, 'placeholder' => 'Select a category'],
                    'tag'      => ['label' => 'Tag', 'options' => $tags, 'placeholder' => 'Select a tag'],
                    'section'  => ['label' => 'Section', 'options' => $sections, 'placeholder' => 'Select a section'],
                ];
                ?>
                <?php foreach ($panels as $panelType => $panel): ?>
                    <div data-type-panel="<?= $panelType ?>" hidden>
                        <label class="form-label" for="target-<?= $panelType ?>"><?= esc($panel['label']) ?></label>
                        <select class="form-select" id="target-<?= $panelType ?>" name="target_id" <?= $type === $panelType ? '' : 'disabled' ?>>
                            <option value=""><?= esc($panel['placeholder']) ?></option>
                            <?php foreach ($panel['options'] as $option): ?>
                                <?php $optionLabel = $option['name'] ?? $option['label'] ?? $option['title'] ?? 'Untitled'; ?>
                                <option value="<?= (int) $option['id'] ?>" <?= (int) ($item['target_id'] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>><?= esc($optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($panel['options'])): ?>
                            <div class="form-text">Nothing available yet.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Publish</h2></div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $item || ! empty($item['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Visible in the menu</label>
                </div>
                <label class="form-label" for="sort_order">Sort order</label>
                <input type="number" class="form-control mb-3" id="sort_order" name="sort_order" min="0" max="9999" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
                <button class="btn btn-brand w-100" type="submit">Save item</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Behaviour</h2></div>
            <div class="card-body">
                <label class="form-label" for="parent_id">Parent item</label>
                <select class="form-select mb-3" id="parent_id" name="parent_id">
                    <option value="">— Top level —</option>
                    <?php foreach ($parents as $parent): ?>
                        <option value="<?= (int) $parent['id'] ?>" <?= (int) ($item['parent_id'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>><?= esc($parent['label'] ?: 'Item #' . (int) $parent['id']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="target">Open in</label>
                <select class="form-select mb-3" id="target" name="target">
                    <option value="_self" <?= ($item['target'] ?? '_self') === '_self' ? 'selected' : '' ?>>Same tab</option>
                    <option value="_blank" <?= ($item['target'] ?? '') === '_blank' ? 'selected' : '' ?>>New tab</option>
                </select>

                <label class="form-label" for="icon">Icon class</label>
                <input type="text" class="form-control mb-3" id="icon" name="icon" maxlength="60" placeholder="fa-solid fa-house" value="<?= esc((string) ($item['icon'] ?? '')) ?>">

                <label class="form-label" for="css_class">CSS class</label>
                <input type="text" class="form-control" id="css_class" name="css_class" maxlength="120" placeholder="nav-highlight" value="<?= esc((string) ($item['css_class'] ?? '')) ?>">
            </div>
        </div>
    </aside>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var select = document.querySelector('[data-menu-type]');
    if (!select) { return; }
    var panels = document.querySelectorAll('[data-type-panel]');

    function sync() {
        panels.forEach(function (panel) {
            var match = panel.getAttribute('data-type-panel') === select.value;
            panel.hidden = !match;
            var field = panel.querySelector('select[name="target_id"]');
            if (field) { field.disabled = !match; }
        });
    }

    select.addEventListener('change', sync);
    sync();
})();
</script>
<?= $this->endSection() ?>
