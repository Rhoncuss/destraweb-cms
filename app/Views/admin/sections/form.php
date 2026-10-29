<?php
$action = $section ? admin_url('sections/' . (int) $section['id'] . '/update') : admin_url('sections/store');
$types = ['text' => 'Text', 'textarea' => 'Textarea', 'editor' => 'Rich text', 'select' => 'Select', 'checkbox' => 'Checkbox', 'date' => 'Date', 'number' => 'Number', 'url' => 'URL', 'color' => 'Colour'];
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('sections') ?>" class="btn btn-soft">Back to sections</a>
    <button form="sectionForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save section</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="sectionForm" method="post" action="<?= esc($action, 'attr') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Section details</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" maxlength="120" required value="<?= esc((string) ($section['name'] ?? '')) ?>" data-slug-source="#slug">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="slug">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" maxlength="120" value="<?= esc((string) ($section['slug'] ?? '')) ?>" placeholder="auto-generated">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="description">Description</label>
                            <input type="text" class="form-control" id="description" name="description" maxlength="500" value="<?= esc((string) ($section['description'] ?? '')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="icon">Icon</label>
                            <input type="text" class="form-control" id="icon" name="icon" maxlength="60" value="<?= esc((string) ($section['icon'] ?? '')) ?>" placeholder="fa-regular fa-newspaper">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="type">Content type</label>
                            <select class="form-select" id="type" name="type">
                                <?php foreach (['post' => 'Posts', 'page' => 'Pages', 'doc' => 'Documentation'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= ($section['type'] ?? 'post') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="template">Template</label>
                            <input type="text" class="form-control" id="template" name="template" maxlength="60" value="<?= esc((string) ($section['template'] ?? 'default')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="visibility">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <?php foreach (['public' => 'Public', 'private' => 'Members only', 'hidden' => 'Hidden from listings'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= ($section['visibility'] ?? 'public') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dst-card">
                <div class="card-header">
                    <h2 class="card-title">Custom fields</h2>
                    <button type="button" class="btn btn-soft btn-sm" data-add-field><i class="fa-solid fa-plus me-1"></i>Add field</button>
                </div>
                <div class="card-body">
                    <p class="text-muted-2 small">These fields are stored per translation and appear in the content editor under the selected section.</p>
                    <div data-fields>
                        <?php $index = 0; foreach ($fields as $field): $index++; ?>
                            <div class="dst-sortable-item field-row" data-field-row>
                                <div class="d-grid gap-2 w-100">
                                    <input type="hidden" name="fields[<?= $index ?>][id]" value="<?= (int) $field['id'] ?>">
                                    <div class="row g-2">
                                        <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][name]" value="<?= esc($field['name'], 'attr') ?>" placeholder="Field label" required></div>
                                        <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][field_key]" value="<?= esc($field['field_key'], 'attr') ?>" placeholder="key" required></div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-sm" name="fields[<?= $index ?>][type]">
                                                <?php foreach ($types as $value => $label): ?>
                                                    <option value="<?= $value ?>" <?= $field['type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex gap-1">
                                            <button type="button" class="btn btn-danger-soft btn-sm w-100" data-remove-field><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][default_value]" value="<?= esc((string) ($field['default_value'] ?? ''), 'attr') ?>" placeholder="Default value"></div>
                                        <div class="col-md-5"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][options]" value="<?= esc((string) ($field['options'] ?? ''), 'attr') ?>" placeholder='Select options JSON, e.g. ["low","high"]'></div>
                                        <div class="col-md-3 d-flex align-items-center">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="fields[<?= $index ?>][is_required]" value="1" id="req-<?= $index ?>" <?= ! empty($field['is_required']) ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="req-<?= $index ?>">Required</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-muted-2 small" data-no-fields <?= $fields !== [] ? 'hidden' : '' ?>>No custom fields yet.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">Publish</h2></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $section || ! empty($section['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <label class="form-label" for="sort_order">Sort order</label>
                    <input type="number" class="form-control mb-3" id="sort_order" name="sort_order" min="0" max="9999" value="<?= (int) ($section['sort_order'] ?? 0) ?>">
                    <button class="btn btn-brand w-100" type="submit">Save section</button>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="fieldRowTemplate">
    <div class="dst-sortable-item field-row" data-field-row>
        <div class="d-grid gap-2 w-100">
            <input type="hidden" name="fields[__INDEX__][id]" value="0">
            <div class="row g-2">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][name]" placeholder="Field label" required></div>
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][field_key]" placeholder="key" required></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="fields[__INDEX__][type]">
                        <?php foreach ($types as $value => $label): ?><option value="<?= $value ?>"><?= $label ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1"><button type="button" class="btn btn-danger-soft btn-sm w-100" data-remove-field><i class="fa-solid fa-xmark"></i></button></div>
            </div>
            <div class="row g-2">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][default_value]" placeholder="Default value"></div>
                <div class="col-md-5"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][options]" placeholder='Select options JSON, e.g. ["low","high"]'></div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="fields[__INDEX__][is_required]" value="1">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var counter = <?= $index ?? 0 ?>;
    var container = document.querySelector('[data-fields]');
    var template = document.getElementById('fieldRowTemplate');
    var empty = document.querySelector('[data-no-fields]');

    document.querySelector('[data-add-field]').addEventListener('click', function () {
        var html = template.innerHTML.replace(/__INDEX__/g, String(++counter));
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        container.appendChild(wrap.firstElementChild);
        if (empty) { empty.hidden = true; }
    });

    container.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-field]');
        if (!button) { return; }
        button.closest('[data-field-row]').remove();
        if (empty && !container.querySelector('[data-field-row]')) { empty.hidden = false; }
    });
})();
</script>
<?= $this->endSection() ?>
