<?php
$action = $banner ? admin_url('banners/' . (int) $banner['id'] . '/update') : admin_url('banners/store');
$type   = (string) ($banner['type'] ?? 'image');
$image  = ! empty($banner['image_id']) ? service('media')->find((int) $banner['image_id']) : null;
$imageUrl = $image ? service('media')->url($image, 'thumb') : '';
$dt = static fn (?string $value): string => $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('banners') ?>" class="btn btn-soft">Back to banners</a>
    <button form="bannerForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save banner</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="bannerForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Creative</h2></div>
            <div class="card-body">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link <?= $type === 'image' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#bannerTypeImage" type="button" role="tab"><i class="fa-regular fa-image me-2"></i>Image</button></li>
                    <li class="nav-item"><button class="nav-link <?= $type === 'html' ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#bannerTypeHtml" type="button" role="tab"><i class="fa-solid fa-code me-2"></i>HTML / ads code</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade <?= $type === 'image' ? 'show active' : '' ?>" id="bannerTypeImage">
                        <input type="hidden" name="image_id" id="bannerImageId" value="<?= (int) ($banner['image_id'] ?? 0) ?>">
                        <div class="dst-placeholder p-0 overflow-hidden" id="bannerImagePreview">
                            <?php if ($imageUrl): ?>
                                <img src="<?= esc($imageUrl, 'attr') ?>" alt="" style="width:100%;max-height:260px;object-fit:cover;display:block;">
                            <?php else: ?>
                                <div class="py-5"><i class="fa-regular fa-image d-block mb-2" style="font-size:26px;"></i>No image selected</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-soft flex-grow-1" data-media-picker="#bannerImageId" data-media-preview="#bannerImagePreview">Choose image</button>
                            <button type="button" class="btn btn-soft" data-media-clear="#bannerImageId" data-media-preview="#bannerImagePreview"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div class="form-text">Recommended width: 1600px for hero slides. Text overlays are optional per language.</div>
                    </div>
                    <div class="tab-pane fade <?= $type === 'html' ? 'show active' : '' ?>" id="bannerTypeHtml">
                        <label class="form-label" for="html">HTML / advertising code</label>
                        <textarea class="form-control font-monospace" id="html" name="html" rows="8" placeholder="&lt;a href=&quot;…&quot;&gt;&lt;img src=&quot;…&quot; alt=&quot;&quot;&gt;&lt;/a&gt;"><?= esc((string) ($banner['html'] ?? '')) ?></textarea>
                        <div class="form-text">Scripts are stripped by the sanitiser. Use image or iframe embeds for ad networks.</div>
                    </div>
                </div>

                <input type="hidden" name="type" id="bannerType" value="<?= esc($type, 'attr') ?>">
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Copy</h2></div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <?php foreach ($languages as $language): ?>
                        <?php $code = (string) $language['code']; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $code === $defaultLocale ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#banner-<?= esc($code, 'attr') ?>" type="button" role="tab">
                                <?= esc($language['native_name'] ?: $language['name']) ?>
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
                        $prefix    = $isDefault ? '' : 'translations[' . $code . ']';
                        ?>
                        <div class="tab-pane fade <?= $isDefault ? 'show active' : '' ?>" id="banner-<?= esc($code, 'attr') ?>" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Headline</label>
                                    <input type="text" class="form-control" name="<?= $isDefault ? 'title' : $prefix . '[title]' ?>" maxlength="255" value="<?= esc((string) ($tr['title'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Subtitle</label>
                                    <input type="text" class="form-control" name="<?= $isDefault ? 'subtitle' : $prefix . '[subtitle]' ?>" maxlength="500" value="<?= esc((string) ($tr['subtitle'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Button label</label>
                                    <input type="text" class="form-control" name="<?= $isDefault ? 'cta_label' : $prefix . '[cta_label]' ?>" maxlength="120" placeholder="Learn more" value="<?= esc((string) ($tr['cta_label'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Image alt text</label>
                                    <input type="text" class="form-control" name="<?= $isDefault ? 'alt' : $prefix . '[alt]' ?>" maxlength="255" value="<?= esc((string) ($tr['alt'] ?? '')) ?>">
                                </div>
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
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $banner || ! empty($banner['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <label class="form-label" for="sort_order">Sort order</label>
                <input type="number" class="form-control mb-3" id="sort_order" name="sort_order" min="0" max="9999" value="<?= (int) ($banner['sort_order'] ?? 0) ?>">
                <button class="btn btn-brand w-100" type="submit">Save banner</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Placement</h2></div>
            <div class="card-body">
                <label class="form-label" for="name">Internal name</label>
                <input class="form-control mb-3" id="name" name="name" maxlength="120" required value="<?= esc((string) ($banner['name'] ?? '')) ?>" placeholder="Spring campaign">
                <label class="form-label" for="placement">Placement</label>
                <select class="form-select mb-3" id="placement" name="placement">
                    <?php foreach ($placements as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($banner['placement'] ?? 'home_slider') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="link_url">Link URL</label>
                <input class="form-control mb-3" id="link_url" name="link_url" maxlength="500" value="<?= esc((string) ($banner['link_url'] ?? '')) ?>" placeholder="https://example.com/offer">
                <label class="form-label" for="link_target">Open in</label>
                <select class="form-select" id="link_target" name="link_target">
                    <option value="_self" <?= ($banner['link_target'] ?? '_self') === '_self' ? 'selected' : '' ?>>Same tab</option>
                    <option value="_blank" <?= ($banner['link_target'] ?? '') === '_blank' ? 'selected' : '' ?>>New tab</option>
                </select>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Schedule</h2></div>
            <div class="card-body">
                <label class="form-label" for="starts_at">Starts</label>
                <input type="datetime-local" class="form-control mb-3" id="starts_at" name="starts_at" value="<?= esc($dt($banner['starts_at'] ?? null), 'attr') ?>">
                <label class="form-label" for="ends_at">Ends</label>
                <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="<?= esc($dt($banner['ends_at'] ?? null), 'attr') ?>">
                <div class="form-text">Leave empty to run indefinitely.</div>
            </div>
        </div>
    </aside>
</form>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var typeInput = document.getElementById('bannerType');
    var pills = document.querySelectorAll('[data-bs-target="#bannerTypeImage"], [data-bs-target="#bannerTypeHtml"]');
    pills.forEach(function (pill) {
        pill.addEventListener('shown.bs.tab', function () {
            typeInput.value = pill.getAttribute('data-bs-target') === '#bannerTypeHtml' ? 'html' : 'image';
        });
    });
})();
</script>
<?= $this->endSection() ?>
