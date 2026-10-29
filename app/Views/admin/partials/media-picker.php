<div class="modal fade" id="mediaPickerModal" tabindex="-1" aria-labelledby="mediaPickerTitle" aria-hidden="true"
     data-picker-url="<?= esc(admin_url('media/picker'), 'attr') ?>"
     data-picker-upload="<?= esc(admin_url('media/upload'), 'attr') ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header flex-wrap gap-2">
                <div class="min-w-0">
                    <h2 class="modal-title h5 mb-0" id="mediaPickerTitle">Media library</h2>
                    <p class="text-muted-2 small mb-0">Pick an image from your library or upload a new one.</p>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    <label class="btn btn-soft btn-sm mb-0">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload
                        <input type="file" class="d-none" accept="image/*" multiple data-picker-upload-input>
                    </label>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-transparent"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control" placeholder="Search images by file name…" data-picker-search autocomplete="off">
                </div>
                <div class="media-grid" data-picker-grid aria-live="polite"></div>
                <div class="dst-empty" data-picker-empty hidden>
                    <i class="fa-regular fa-images"></i>
                    <h3>No images found</h3>
                    <p>Upload your first image to start building the library.</p>
                </div>
                <div class="dst-empty" data-picker-loading hidden>
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                    <h3>Loading library…</h3>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <span class="text-muted-2 small">Files are re-encoded and stored safely outside the web root.</span>
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
