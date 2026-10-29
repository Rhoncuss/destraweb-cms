<?php
$action = $form ? admin_url('forms/' . (int) $form['id'] . '/update') : admin_url('forms/store');
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('forms') ?>" class="btn btn-soft">Back to forms</a>
    <button form="formBuilder" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save form</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$optionsText = static function (array $field): string {
    $options = $field['options'] ?? [];
    if (is_array($options)) {
        $list = array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $options);
    } else {
        $list = preg_split('/[\r\n,]+/', (string) $options) ?: [];
    }

    return implode("\n", array_filter(array_map('trim', $list), static fn ($v) => $v !== ''));
};
?>
<form id="formBuilder" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Form details</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control" id="name" name="name" maxlength="120" required value="<?= esc((string) ($form['name'] ?? '')) ?>" data-slug-source="#slug">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="slug">Slug</label>
                        <input class="form-control" id="slug" name="slug" maxlength="120" value="<?= esc((string) ($form['slug'] ?? '')) ?>" placeholder="auto-generated">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <input class="form-control" id="description" name="description" maxlength="500" value="<?= esc((string) ($form['description'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="success_message">Success message</label>
                        <input class="form-control" id="success_message" name="success_message" maxlength="500" value="<?= esc((string) ($form['success_message'] ?? 'Thank you — your message has been received.')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Fields</h2>
                <button type="button" class="btn btn-soft btn-sm" data-add-field><i class="fa-solid fa-plus me-1"></i>Add field</button>
            </div>
            <div class="card-body">
                <p class="text-muted-2 small">Each field is validated server-side. Select, radio and checkbox need one option per line.</p>
                <div data-fields>
                    <?php $index = 0; foreach ($fields as $field): $index++; ?>
                        <div class="dst-sortable-item" data-field-row>
                            <div class="d-grid gap-2 w-100">
                                <div class="row g-2">
                                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][label]" value="<?= esc((string) ($field['label'] ?? ''), 'attr') ?>" placeholder="Label" required></div>
                                    <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][key]" value="<?= esc((string) ($field['key'] ?? ''), 'attr') ?>" placeholder="key"></div>
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" name="fields[<?= $index ?>][type]">
                                            <?php foreach ($fieldTypes as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= ($field['type'] ?? 'text') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex gap-1">
                                        <button type="button" class="btn btn-danger-soft btn-sm w-100" data-remove-field><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[<?= $index ?>][placeholder]" value="<?= esc((string) ($field['placeholder'] ?? ''), 'attr') ?>" placeholder="Placeholder"></div>
                                    <div class="col-md-5"><textarea class="form-control form-control-sm" rows="2" name="fields[<?= $index ?>][options]" placeholder="Options (one per line)"><?= esc($optionsText($field)) ?></textarea></div>
                                    <div class="col-md-3 d-flex flex-column gap-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="req-<?= $index ?>" name="fields[<?= $index ?>][required]" value="1" <?= ! empty($field['required']) ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="req-<?= $index ?>">Required</label>
                                        </div>
                                        <select class="form-select form-select-sm" name="fields[<?= $index ?>][width]">
                                            <option value="full" <?= ($field['width'] ?? 'full') === 'full' ? 'selected' : '' ?>>Full width</option>
                                            <option value="half" <?= ($field['width'] ?? '') === 'half' ? 'selected' : '' ?>>Half width</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-muted-2 small" data-no-fields <?= $fields !== [] ? 'hidden' : '' ?>>No fields yet — add your first one.</div>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Publish</h2></div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $form || ! empty($form['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Accept submissions</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="captcha" name="captcha" value="1" <?= ! $form || ! empty($form['captcha']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="captcha">Spam protection</label>
                </div>
                <button class="btn btn-brand w-100" type="submit">Save form</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Notifications</h2></div>
            <div class="card-body">
                <label class="form-label" for="email_to">Notify email</label>
                <input type="email" class="form-control mb-3" id="email_to" name="email_to" maxlength="255" value="<?= esc((string) ($form['email_to'] ?? '')) ?>" placeholder="sales@example.com">
                <label class="form-label" for="email_subject">Email subject</label>
                <input class="form-control" id="email_subject" name="email_subject" maxlength="255" value="<?= esc((string) ($form['email_subject'] ?? '')) ?>" placeholder="New submission">
                <div class="form-text">Leave empty to fall back to the site notification address.</div>
            </div>
        </div>
    </aside>
</form>

<template id="fieldRowTemplate">
    <div class="dst-sortable-item" data-field-row>
        <div class="d-grid gap-2 w-100">
            <div class="row g-2">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][label]" placeholder="Label" required></div>
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][key]" placeholder="key"></div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="fields[__INDEX__][type]">
                        <?php foreach ($fieldTypes as $value => $label): ?><option value="<?= $value ?>"><?= esc($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1"><button type="button" class="btn btn-danger-soft btn-sm w-100" data-remove-field><i class="fa-solid fa-xmark"></i></button></div>
            </div>
            <div class="row g-2">
                <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="fields[__INDEX__][placeholder]" placeholder="Placeholder"></div>
                <div class="col-md-5"><textarea class="form-control form-control-sm" rows="2" name="fields[__INDEX__][options]" placeholder="Options (one per line)"></textarea></div>
                <div class="col-md-3 d-flex flex-column gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="fields[__INDEX__][required]" value="1">
                        <span class="form-check-label small">Required</span>
                    </div>
                    <select class="form-select form-select-sm" name="fields[__INDEX__][width]">
                        <option value="full">Full width</option>
                        <option value="half">Half width</option>
                    </select>
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
