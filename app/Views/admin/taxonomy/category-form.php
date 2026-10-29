<?php
$primary = $translations[$defaultLocale] ?? [];
$action = $item ? admin_url('categories/' . (int) $item['id'] . '/update') : admin_url('categories/store');
$image = ! empty($item['image_id']) ? service('media')->find((int) $item['image_id']) : null;
$imageUrl = $image ? service('media')->url($image, 'thumb') : '';
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('categories') ?>" class="btn btn-soft">Back to categories</a>
    <button form="categoryForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save category</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="categoryForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <?php foreach ($languages as $language): ?>
                        <?php $code = (string) $language['code']; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $code === $defaultLocale ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#cat-<?= esc($code, 'attr') ?>" type="button" role="tab">
                                <?= esc($language['native_name'] ?: $language['name']) ?>
                                <?php if ($code === $defaultLocale): ?><span class="badge text-bg-light ms-1" style="font-size:9px;">default</span><?php endif; ?>
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
                        <div class="tab-pane fade <?= $isDefault ? 'show active' : '' ?>" id="cat-<?= esc($code, 'attr') ?>" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label" for="name-<?= esc($code, 'attr') ?>">Name</label>
                                    <input type="text" class="form-control" id="name-<?= esc($code, 'attr') ?>"
                                           name="<?= $isDefault ? 'name' : 'translations[' . esc($code, 'attr') . '][name]' ?>" maxlength="190" required
                                           value="<?= esc((string) ($tr['name'] ?? '')) ?>"
                                           <?= $isDefault ? 'data-slug-source="#slug"' : '' ?>>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="slug-<?= esc($code, 'attr') ?>">Slug</label>
                                    <input type="text" class="form-control" id="slug-<?= esc($code, 'attr') ?>"
                                           name="<?= $isDefault ? 'slug' : 'translations[' . esc($code, 'attr') . '][slug]' ?>" maxlength="190"
                                           value="<?= esc((string) ($tr['slug'] ?? '')) ?>" placeholder="auto-generated">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="desc-<?= esc($code, 'attr') ?>">Description</label>
                                    <textarea class="form-control" rows="4" id="desc-<?= esc($code, 'attr') ?>"
                                              name="<?= $isDefault ? 'description' : 'translations[' . esc($code, 'attr') . '][description]' ?>"><?= esc((string) ($tr['description'] ?? '')) ?></textarea>
                                </div>
                                <?php if ($isDefault): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="seo_title">SEO title</label>
                                        <input type="text" class="form-control" id="seo_title" name="seo_title" maxlength="255" value="<?= esc((string) ($primary['seo_title'] ?? '')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="seo_description">Meta description</label>
                                        <input type="text" class="form-control" id="seo_description" name="seo_description" maxlength="500" value="<?= esc((string) ($primary['seo_description'] ?? '')) ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Publish</h2></div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $item || ! empty($item['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Visible on the website</label>
                </div>
                <label class="form-label" for="sort_order">Sort order</label>
                <input type="number" class="form-control mb-3" id="sort_order" name="sort_order" min="0" max="9999" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
                <button class="btn btn-brand w-100" type="submit">Save category</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Organisation</h2></div>
            <div class="card-body">
                <label class="form-label" for="section_id">Section</label>
                <select class="form-select mb-3" id="section_id" name="section_id">
                    <option value="">— None —</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= (int) $section['id'] ?>" <?= (int) ($item['section_id'] ?? 0) === (int) $section['id'] ? 'selected' : '' ?>><?= esc($section['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="parent_id">Parent category</label>
                <select class="form-select mb-3" id="parent_id" name="parent_id">
                    <option value="">— Top level —</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (int) ($item['parent_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= esc($category['name'] ?: 'Untitled') ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label" for="icon">Icon</label>
                        <input type="text" class="form-control" id="icon" name="icon" maxlength="60" placeholder="fa-regular fa-folder" value="<?= esc((string) ($item['icon'] ?? '')) ?>">
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="color">Colour</label>
                        <input type="color" class="form-control form-control-color w-100" id="color" name="color" value="<?= esc((string) ($item['color'] ?? '#6d5efc'), 'attr') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Artwork</h2></div>
            <div class="card-body">
                <input type="hidden" name="image_id" id="categoryImageId" value="<?= (int) ($item['image_id'] ?? 0) ?>">
                <div class="dst-placeholder p-0 overflow-hidden" id="categoryPreview">
                    <?php if ($imageUrl): ?>
                        <img src="<?= esc($imageUrl, 'attr') ?>" alt="" style="width:100%;height:120px;object-fit:cover;display:block;">
                    <?php else: ?>
                        <div class="py-3"><i class="fa-regular fa-image d-block mb-2" style="font-size:22px;"></i>No image</div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-soft btn-sm flex-grow-1" data-media-picker="#categoryImageId" data-media-preview="#categoryPreview">Choose</button>
                    <button type="button" class="btn btn-soft btn-sm" data-media-clear="#categoryImageId" data-media-preview="#categoryPreview"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>
    </aside>
</form>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>
