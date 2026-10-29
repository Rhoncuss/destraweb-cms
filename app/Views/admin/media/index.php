<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <div class="d-inline-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-soft" data-bs-toggle="modal" data-bs-target="#folderModal">
            <i class="fa-regular fa-folder-open me-2"></i>New folder
        </button>
        <button type="button" class="btn btn-brand" data-media-upload-trigger>
            <i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload files
        </button>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = static function (array $overrides) use ($q, $folder, $type): string {
    $params = array_filter(array_merge([
        'q'      => $q,
        'folder' => $folder ?: '',
        'kind'   => $type,
    ], $overrides), static fn ($v) => $v !== '' && $v !== 0 && $v !== null);

    return admin_url('media' . ($params ? '?' . http_build_query($params) : ''));
};
?>

<div class="dst-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex flex-wrap gap-1 me-auto">
                <?php foreach (['' => 'All files', 'image' => 'Images', 'document' => 'Documents'] as $key => $label): ?>
                    <a href="<?= $qs(['kind' => $key, 'page' => '']) ?>" class="btn btn-sm <?= $type === $key ? 'btn-brand' : 'btn-soft' ?>"><?= esc($label) ?></a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= admin_url('media') ?>" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="kind" value="<?= esc($type, 'attr') ?>">
                <select name="folder" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All folders</option>
                    <?php foreach ($folders as $f): ?>
                        <option value="<?= (int) $f['id'] ?>" <?= (int) $folder === (int) $f['id'] ? 'selected' : '' ?>><?= esc($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="q" value="<?= esc($q) ?>" class="form-control form-control-sm" placeholder="Search file name…" style="width:190px;">
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</div>

<div class="dst-card mb-3 dst-dropzone" data-media-dropzone>
    <div class="card-body text-center py-4">
        <input type="file" class="d-none" multiple data-media-upload-input>
        <i class="fa-solid fa-cloud-arrow-up d-block mb-2" style="font-size:30px;color:var(--dst-brand);"></i>
        <div class="fw-650">Drag &amp; drop files here</div>
        <div class="text-muted-2 small mb-2">Images (JPG, PNG, WebP, GIF, SVG) and documents up to 12 MB each. Images are re-encoded automatically.</div>
        <button type="button" class="btn btn-soft btn-sm" data-media-upload-trigger>Browse files</button>
        <div class="small mt-2" data-media-upload-status></div>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-images"></i>
            <h3>Your library is empty</h3>
            <p>Upload images for posts, banners and your media library. Every file is validated, resized and stored outside the public web root.</p>
            <button type="button" class="btn btn-brand" data-media-upload-trigger>Upload your first file</button>
        </div>
    </div>
<?php else: ?>
    <div class="media-grid mb-4">
        <?php foreach ($items as $item): ?>
            <?php $isImage = str_starts_with((string) $item['mime_type'], 'image/'); ?>
            <button type="button" class="media-tile"
                    data-media-open
                    data-id="<?= (int) $item['id'] ?>"
                    data-name="<?= esc((string) $item['original_name'], 'attr') ?>"
                    data-url="<?= esc($item['full_url'], 'attr') ?>"
                    data-preview="<?= esc($item['thumb_url'], 'attr') ?>"
                    data-mime="<?= esc((string) $item['mime_type'], 'attr') ?>"
                    data-size="<?= esc(number_format(((int) $item['size']) / 1024, 1) . ' KB', 'attr') ?>"
                    data-date="<?= esc(date('d M Y H:i', strtotime((string) $item['created_at'])), 'attr') ?>"
                    data-alt="<?= esc((string) ($item['alt'] ?? ''), 'attr') ?>"
                    data-caption="<?= esc((string) ($item['caption'] ?? ''), 'attr') ?>"
                    data-width="<?= esc((string) ($item['width'] ?? ''), 'attr') ?>"
                    data-height="<?= esc((string) ($item['height'] ?? ''), 'attr') ?>">
                <?php if ($isImage): ?>
                    <img src="<?= esc($item['thumb_url'], 'attr') ?>" alt="<?= esc($item['alt'] ?: $item['original_name'], 'attr') ?>" loading="lazy">
                <?php else: ?>
                    <span class="d-flex align-items-center justify-content-center h-100" style="font-size:34px;color:#8b93ab;"><i class="fa-regular fa-file"></i></span>
                <?php endif; ?>
                <span class="tile-ext"><?= esc(strtoupper((string) $item['extension'])) ?></span>
                <span class="tile-meta"><?= esc($item['original_name']) ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div class="text-muted-2 small"><?= number_format($total) ?> file<?= $total === 1 ? '' : 's' ?> · page <?= (int) $page ?> of <?= (int) $pages ?></div>
        <?php if ($pages > 1): ?>
            <nav aria-label="Media pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => max(1, $page - 1)]) ?>">Previous</a></li>
                    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $qs(['page' => min($pages, $page + 1)]) ?>">Next</a></li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="folderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= admin_url('media/folders') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0">New folder</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="folderName">Folder name</label>
                <input class="form-control" id="folderName" name="name" maxlength="120" required placeholder="e.g. Campaign 2026">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand" type="submit">Create folder</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="mediaDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0 truncate" data-details-title>File details</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="dst-placeholder p-0 overflow-hidden" data-details-preview style="aspect-ratio:1/1;"></div>
                        <div class="text-muted-2 small mt-2" data-details-meta></div>
                        <button type="button" class="btn btn-soft btn-sm w-100 mt-2" data-details-copy><i class="fa-regular fa-copy me-1"></i>Copy file URL</button>
                    </div>
                    <div class="col-md-7">
                        <form method="post" class="mb-3" data-details-form>
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label" for="mediaAlt">Alternative text</label>
                                <input class="form-control" id="mediaAlt" name="alt" maxlength="255" placeholder="Describe the image for screen readers">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="mediaCaption">Caption</label>
                                <input class="form-control" id="mediaCaption" name="caption" maxlength="500" placeholder="Optional caption shown under the image">
                            </div>
                            <button class="btn btn-brand" type="submit">Save details</button>
                        </form>
                        <form method="post" data-details-delete data-confirm="Delete this file? It is removed from the library and any content using it will show a broken reference." data-confirm-button="Delete file">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger-soft" type="submit"><i class="fa-regular fa-trash-can me-1"></i>Delete file</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var dropzone = document.querySelector('[data-media-dropzone]');
    var inputs = document.querySelectorAll('[data-media-upload-input]');
    var status = document.querySelector('[data-media-upload-status]');
    var uploadUrl = <?= json_encode(admin_url('media/upload')) ?>;

    function setStatus(text, isError) {
        if (!status) { return; }
        status.textContent = text;
        status.style.color = isError ? '#dc3545' : 'var(--dst-muted)';
    }

    function upload(files) {
        if (!files || !files.length) { return; }
        var body = new FormData();
        Array.prototype.forEach.call(files, function (file) { body.append('files[]', file); });
        body.append(Destra.csrfName, Destra.csrfToken);
        setStatus('Uploading ' + files.length + ' file(s)…');
        fetch(uploadUrl, {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) { return response.json(); }).then(function (json) {
            if (json.success === false) {
                setStatus(json.error || 'Upload failed.', true);
                Destra.toast('error', json.error || 'Upload failed.');
                return;
            }
            Destra.toast('success', json.message || 'Uploaded.');
            window.location.reload();
        }).catch(function () {
            setStatus('Upload failed — please retry.', true);
        });
    }

    document.querySelectorAll('[data-media-upload-trigger]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = inputs[0];
            if (input) { input.click(); }
        });
    });

    inputs.forEach(function (input) {
        input.addEventListener('change', function () {
            upload(input.files);
            input.value = '';
        });
    });

    if (dropzone) {
        ['dragenter', 'dragover'].forEach(function (type) {
            dropzone.addEventListener(type, function (event) {
                event.preventDefault();
                dropzone.classList.add('dst-dragging');
            });
        });
        ['dragleave', 'drop'].forEach(function (type) {
            dropzone.addEventListener(type, function (event) {
                event.preventDefault();
                dropzone.classList.remove('dst-dragging');
            });
        });
        dropzone.addEventListener('drop', function (event) {
            if (event.dataTransfer && event.dataTransfer.files) { upload(event.dataTransfer.files); }
        });
    }

    var detailsModalEl = document.getElementById('mediaDetailsModal');
    if (detailsModalEl && window.bootstrap) {
        var detailsModal = new bootstrap.Modal(detailsModalEl);
        var title = detailsModalEl.querySelector('[data-details-title]');
        var preview = detailsModalEl.querySelector('[data-details-preview]');
        var meta = detailsModalEl.querySelector('[data-details-meta]');
        var form = detailsModalEl.querySelector('[data-details-form]');
        var del = detailsModalEl.querySelector('[data-details-delete]');
        var copy = detailsModalEl.querySelector('[data-details-copy]');
        var currentUrl = '';

        document.querySelectorAll('[data-media-open]').forEach(function (tile) {
            tile.addEventListener('click', function () {
                var data = tile.dataset;
                var base = document.documentElement.getAttribute('data-base') || '/';
                currentUrl = data.url;
                title.textContent = data.name;
                preview.innerHTML = '';
                if (data.mime.indexOf('image/') === 0) {
                    var img = document.createElement('img');
                    img.src = data.preview;
                    img.alt = data.alt || data.name;
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block;';
                    preview.appendChild(img);
                } else {
                    var icon = document.createElement('div');
                    icon.className = 'py-5 text-center';
                    icon.innerHTML = '<i class="fa-regular fa-file" style="font-size:38px;"></i>';
                    preview.appendChild(icon);
                }
                meta.textContent = data.mime + ' · ' + data.size + (data.width ? ' · ' + data.width + '×' + data.height : '') + ' · ' + data.date;
                detailsModalEl.querySelector('#mediaAlt').value = data.alt || '';
                detailsModalEl.querySelector('#mediaCaption').value = data.caption || '';
                form.action = base + <?= json_encode((trim(config(\Config\Destra::class)->adminPrefix, '/') ?: 'admin') . '/media/') ?> + data.id + '/update';
                del.action = base + <?= json_encode((trim(config(\Config\Destra::class)->adminPrefix, '/') ?: 'admin') . '/media/') ?> + data.id + '/delete';
                detailsModal.show();
            });
        });

        if (copy) {
            copy.addEventListener('click', function () {
                navigator.clipboard.writeText(currentUrl).then(function () { Destra.toast('success', 'URL copied'); });
            });
        }
    }
})();
</script>
<?= $this->endSection() ?>
