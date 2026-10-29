<?php
$action  = $popup ? admin_url('popups/' . (int) $popup['id'] . '/update') : admin_url('popups/store');
$image   = ! empty($popup['image_id']) ? service('media')->find((int) $popup['image_id']) : null;
$imageUrl = $image ? service('media')->url($image, 'thumb') : '';
$dt = static fn (?string $value): string => $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('popups') ?>" class="btn btn-soft">Back to popups</a>
    <button form="popupForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save popup</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="popupForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Content</h2></div>
            <div class="card-body">
                <label class="form-label" for="title">Headline</label>
                <input class="form-control mb-3" id="title" name="title" maxlength="190" value="<?= esc((string) ($popup['title'] ?? '')) ?>" placeholder="Join our newsletter">
                <label class="form-label" for="content">Body</label>
                <textarea class="form-control" id="content" name="content" rows="8" data-editor="tinymce" placeholder="Write the message readers will see…"><?= esc((string) ($popup['content'] ?? '')) ?></textarea>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Artwork</h2></div>
            <div class="card-body">
                <input type="hidden" name="image_id" id="popupImageId" value="<?= (int) ($popup['image_id'] ?? 0) ?>">
                <div class="dst-placeholder p-0 overflow-hidden" id="popupImagePreview">
                    <?php if ($imageUrl): ?>
                        <img src="<?= esc($imageUrl, 'attr') ?>" alt="" style="width:100%;max-height:220px;object-fit:cover;display:block;">
                    <?php else: ?>
                        <div class="py-4"><i class="fa-regular fa-image d-block mb-2" style="font-size:22px;"></i>No image selected</div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-soft flex-grow-1" data-media-picker="#popupImageId" data-media-preview="#popupImagePreview">Choose image</button>
                    <button type="button" class="btn btn-soft" data-media-clear="#popupImageId" data-media-preview="#popupImagePreview"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Publish</h2></div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= ! $popup || ! empty($popup['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <label class="form-label" for="name">Internal name</label>
                <input class="form-control mb-3" id="name" name="name" maxlength="120" required value="<?= esc((string) ($popup['name'] ?? '')) ?>" placeholder="Newsletter invite">
                <button class="btn btn-brand w-100" type="submit">Save popup</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Behaviour</h2></div>
            <div class="card-body">
                <label class="form-label" for="trigger_type">Trigger</label>
                <select class="form-select mb-3" id="trigger_type" name="trigger_type">
                    <?php foreach ($triggers as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($popup['trigger_type'] ?? 'delay') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="trigger_value">Trigger value</label>
                <input type="number" class="form-control mb-3" id="trigger_value" name="trigger_value" min="0" max="100000" value="<?= (int) ($popup['trigger_value'] ?? 3) ?>">
                <div class="form-text mb-3">Seconds for delays, percentage for scroll depth.</div>

                <label class="form-label" for="frequency">Show</label>
                <select class="form-select mb-3" id="frequency" name="frequency">
                    <?php foreach ($frequencies as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($popup['frequency'] ?? 'session') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="pages">Pages</label>
                <select class="form-select mb-3" id="pages" name="pages">
                    <?php foreach ($pages as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($popup['pages'] ?? 'all') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="content_ids">Content IDs</label>
                <input class="form-control" id="content_ids" name="content_ids" maxlength="500" value="<?= esc((string) ($popup['content_ids'] ?? '')) ?>" placeholder="12, 34, 56">
                <div class="form-text">Only used when &ldquo;Specific content&rdquo; is selected.</div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Schedule</h2></div>
            <div class="card-body">
                <label class="form-label" for="starts_at">Starts</label>
                <input type="datetime-local" class="form-control mb-3" id="starts_at" name="starts_at" value="<?= esc($dt($popup['starts_at'] ?? null), 'attr') ?>">
                <label class="form-label" for="ends_at">Ends</label>
                <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="<?= esc($dt($popup['ends_at'] ?? null), 'attr') ?>">
            </div>
        </div>
    </aside>
</form>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>
