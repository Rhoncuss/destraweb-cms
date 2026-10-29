<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('redirects') ?>" class="btn btn-soft">Back to redirects</a>
    <button form="redirectForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save redirect</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="redirectForm" method="post" action="<?= $item ? admin_url('redirects/' . (int) $item['id'] . '/update') : admin_url('redirects/store') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">Redirect rule</h2></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="from_path">From path</label>
                        <input type="text" class="form-control font-monospace" id="from_path" name="from_path" maxlength="500" required
                               value="<?= esc((string) ($item['from_path'] ?? '')) ?>" placeholder="/old-article-url">
                        <div class="text-muted-2 small mt-1">The path visitors request. Query strings are matched by path only.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="to_path">Destination</label>
                        <input type="text" class="form-control font-monospace" id="to_path" name="to_path" maxlength="500" required
                               value="<?= esc((string) ($item['to_path'] ?? '')) ?>" placeholder="/new-article-url or https://example.com/page">
                        <div class="text-muted-2 small mt-1">A site path such as <code>/about</code>, or a full https:// URL.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="status_code">Redirect type</label>
                            <select class="form-select" id="status_code" name="status_code">
                                <?php foreach ($codes as $code => $label): ?>
                                    <option value="<?= (int) $code ?>" <?= (int) ($item['status_code'] ?? 301) === (int) $code ? 'selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="dst-setting-row w-100">
                                <label class="form-label mb-0" for="is_active">Active</label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= ! $item || (int) $item['is_active'] === 1 ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Best practice</h2></div>
                <div class="card-body text-muted-2 small">
                    <ul class="mb-0 ps-3">
                        <li class="mb-2">Use <strong>301</strong> when a page has moved for good — search engines transfer ranking signals.</li>
                        <li class="mb-2">Use <strong>302</strong> for campaigns or temporary maintenance so the original URL stays indexed.</li>
                        <li>Avoid chains: point the old URL straight at the final destination.</li>
                    </ul>
                </div>
            </div>
            <?php if ($item): ?>
                <div class="dst-card">
                    <div class="card-header"><h2 class="card-title">Stats</h2></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted-2 small">Hits</span><span class="fw-650"><?= number_format((int) $item['hits']) ?></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted-2 small">Created</span><span class="fw-650"><?= esc(human_date((string) $item['created_at'])) ?></span></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>
<?= $this->endSection() ?>
