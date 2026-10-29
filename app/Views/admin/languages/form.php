<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('languages') ?>" class="btn btn-soft">Back to languages</a>
    <button form="languageForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save language</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="languageForm" method="post" action="<?= $item ? admin_url('languages/' . (int) $item['id'] . '/update') : admin_url('languages/store') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Language details</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="code">Language code</label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="10" required
                                   value="<?= esc((string) ($item['code'] ?? '')) ?>" placeholder="en, id, ar, pt-BR"
                                   pattern="[A-Za-z]{2,3}(-[A-Za-z]{2,4})?">
                            <div class="text-muted-2 small mt-1">ISO 639-1, optionally with a region.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="name">English name</label>
                            <input type="text" class="form-control" id="name" name="name" maxlength="80" required value="<?= esc((string) ($item['name'] ?? '')) ?>" placeholder="Indonesian">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="native_name">Native name</label>
                            <input type="text" class="form-control" id="native_name" name="native_name" maxlength="80" required value="<?= esc((string) ($item['native_name'] ?? '')) ?>" placeholder="Bahasa Indonesia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="direction">Text direction</label>
                            <select class="form-select" id="direction" name="direction">
                                <option value="ltr" <?= ($item['direction'] ?? 'ltr') !== 'rtl' ? 'selected' : '' ?>>Left to right (LTR)</option>
                                <option value="rtl" <?= ($item['direction'] ?? 'ltr') === 'rtl' ? 'selected' : '' ?>>Right to left (RTL)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="flag">Flag code</label>
                            <input type="text" class="form-control" id="flag" name="flag" maxlength="30" value="<?= esc((string) ($item['flag'] ?? '')) ?>" placeholder="id">
                            <div class="text-muted-2 small mt-1">Optional ISO country code, e.g. id, us, sa.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="sort_order">Sort order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" max="9999" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">Availability</h2></div>
                <div class="card-body">
                    <div class="dst-setting-row mb-2">
                        <div>
                            <label class="form-label mb-0" for="is_active">Active</label>
                            <div class="text-muted-2 small">Inactive languages stay in the database but are hidden from visitors.</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                                   <?= ! $item || (int) $item['is_active'] === 1 ? 'checked' : '' ?>
                                   <?= $item && (int) $item['is_default'] === 1 ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    <div class="dst-setting-row">
                        <div>
                            <label class="form-label mb-0" for="is_default">Default language</label>
                            <div class="text-muted-2 small">The default language is served at the site root without a URL prefix.</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <?php if ($item && (int) $item['is_default'] === 1): ?>
                                <input class="form-check-input" type="checkbox" role="switch" checked disabled>
                            <?php else: ?>
                                <input class="form-check-input" type="checkbox" role="switch" id="is_default" name="is_default" value="1">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Preview</h2></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="dst-lang-chip" id="previewCode"><?= esc(strtoupper((string) ($item['code'] ?? 'XX'))) ?></span>
                        <div class="min-w-0">
                            <div class="fw-650 truncate" id="previewNative"><?= esc((string) ($item['native_name'] ?? 'Native name')) ?></div>
                            <div class="text-muted-2 small" id="previewName"><?= esc((string) ($item['name'] ?? 'English name')) ?></div>
                        </div>
                    </div>
                    <p class="text-muted-2 small mb-0" id="previewDirection">
                        <?= ($item['direction'] ?? 'ltr') === 'rtl' ? 'This language renders right-to-left, laying out menus and content from the right edge.' : 'This language renders left-to-right with standard alignment.' ?>
                    </p>
                </div>
            </div>

            <?php if ($item): ?>
                <div class="dst-card">
                    <div class="card-header"><h2 class="card-title">Translations</h2></div>
                    <div class="card-body d-grid gap-2">
                        <a class="btn btn-soft" href="<?= admin_url('languages/' . (int) $item['id'] . '/translations') ?>"><i class="fa-solid fa-language me-2"></i>Edit interface strings</a>
                        <p class="text-muted-2 small mb-0">Content translations are edited per entry from the content editor.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var code = document.getElementById('code');
    var name = document.getElementById('name');
    var native = document.getElementById('native_name');
    var direction = document.getElementById('direction');
    var bind = function (input, target) {
        if (!input || !target) { return; }
        input.addEventListener('input', function () {
            target.textContent = input.value.trim() || target.getAttribute('data-empty') || '—';
        });
    };
    bind(code, document.getElementById('previewCode'));
    bind(name, document.getElementById('previewName'));
    bind(native, document.getElementById('previewNative'));
    if (code) {
        code.addEventListener('input', function () {
            var el = document.getElementById('previewCode');
            if (el) { el.textContent = code.value.trim().toUpperCase() || 'XX'; }
        });
    }
    if (direction) {
        direction.addEventListener('change', function () {
            var el = document.getElementById('previewDirection');
            if (el) {
                el.textContent = direction.value === 'rtl'
                    ? 'This language renders right-to-left, laying out menus and content from the right edge.'
                    : 'This language renders left-to-right with standard alignment.';
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
