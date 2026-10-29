<?php
$action = $item ? admin_url('tags/' . (int) $item['id'] . '/update') : admin_url('tags/store');
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('tags') ?>" class="btn btn-soft">Back to tags</a>
    <button form="tagForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save tag</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="tagForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>
    <div class="dst-card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <?php foreach ($languages as $language): ?>
                    <?php $code = (string) $language['code']; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $code === $defaultLocale ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tag-<?= esc($code, 'attr') ?>" type="button" role="tab">
                            <?= esc($language['native_name'] ?: $language['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content">
                <?php foreach ($languages as $language): ?>
                    <?php
                    $code = (string) $language['code'];
                    $tr   = $translations[$code] ?? [];
                    $isDefault = $code === $defaultLocale;
                    ?>
                    <div class="tab-pane fade <?= $isDefault ? 'show active' : '' ?>" id="tag-<?= esc($code, 'attr') ?>" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="tag-name-<?= esc($code, 'attr') ?>">Name</label>
                                <input type="text" class="form-control" id="tag-name-<?= esc($code, 'attr') ?>"
                                       name="<?= $isDefault ? 'name' : 'translations[' . esc($code, 'attr') . '][name]' ?>" maxlength="120" required
                                       value="<?= esc((string) ($tr['name'] ?? '')) ?>" <?= $isDefault ? 'data-slug-source="#tag-slug"' : '' ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tag-slug-<?= esc($code, 'attr') ?>">Slug</label>
                                <input type="text" class="form-control" id="tag-slug-<?= esc($code, 'attr') ?>"
                                       name="<?= $isDefault ? 'slug' : 'translations[' . esc($code, 'attr') . '][slug]' ?>" maxlength="190"
                                       value="<?= esc((string) ($tr['slug'] ?? '')) ?>" placeholder="auto-generated">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Appearance</h2></div>
            <div class="card-body">
                <label class="form-label" for="color">Colour</label>
                <input type="color" class="form-control form-control-color w-100 mb-3" id="color" name="color" value="<?= esc((string) ($item['color'] ?? '#6d5efc'), 'attr') ?>">
                <button class="btn btn-brand w-100" type="submit">Save tag</button>
            </div>
        </div>
    </aside>
</form>
<?= $this->endSection() ?>
