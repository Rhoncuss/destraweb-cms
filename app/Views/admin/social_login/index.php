<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a class="btn btn-soft" href="<?= admin_url('settings?group=marketing') ?>"><i class="fa-solid fa-sliders me-2"></i>Global options</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="dst-stat dst-stat--mini">
            <span class="stat-label">Providers</span>
            <span class="stat-value"><?= number_format(count($providers)) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-plug me-1"></i>available integrations</span>
        </div>
    </div>
    <div class="col-4">
        <div class="dst-stat stat-ocean dst-stat--mini">
            <span class="stat-label">Active</span>
            <span class="stat-value"><?= number_format($activeCount) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-bolt me-1"></i>shown on the login screen</span>
        </div>
    </div>
    <div class="col-4">
        <div class="dst-stat stat-sunrise dst-stat--mini">
            <span class="stat-label">Social sign-in</span>
            <span class="stat-value"><?= $enabled ? 'On' : 'Off' ?></span>
            <span class="stat-foot"><i class="fa-solid <?= $enabled ? 'fa-circle-check' : 'fa-circle-pause' ?> me-1"></i>master switch</span>
        </div>
    </div>
</div>

<div class="dst-card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <div class="flex-grow-1 min-w-0">
            <h2 class="card-title mb-1">How it works</h2>
            <p class="text-muted-2 small mb-0">Create an OAuth application in each provider's developer console, paste the credentials below, then switch the provider on. Visitors will see a matching button on the sign-in and registration pages.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="dst-chip"><i class="fa-solid fa-lock me-1"></i>Secrets encrypted</span>
            <span class="dst-chip"><i class="fa-solid fa-shuffle me-1"></i>State validated</span>
            <?php if ($registration): ?>
                <span class="dst-chip"><i class="fa-solid fa-user-plus me-1"></i>Auto registration on</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (! $enabled): ?>
    <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center" role="alert">
        <strong><i class="fa-solid fa-triangle-exclamation me-2"></i>Social sign-in is turned off globally.</strong>
        <span class="small">Providers can be configured now, but buttons stay hidden until you enable social login in <a href="<?= admin_url('settings?group=marketing') ?>" class="alert-link">Settings &rsaquo; Marketing</a>.</span>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($providers as $slug => $meta):
        $record       = $records[$slug] ?? [];
        $clientId     = (string) ($record['client_id'] ?? '');
        $hasSecret    = ! empty($record['has_secret']);
        $isActive     = ! empty($record['is_active']) && $clientId !== '' && $hasSecret;
        $state        = $isActive ? 'active' : (($clientId !== '' || $hasSecret) ? 'configured' : 'empty');
        $stateLabel   = $isActive ? 'Active' : ($state === 'configured' ? 'Configured' : 'Not configured');
        $stateClass   = $isActive ? 'stat-ocean' : ($state === 'configured' ? 'stat-sunrise' : '');
        $defaultUri   = $callbackBase . '/' . $slug . '/callback';
        $savedUri     = trim((string) ($record['redirect_uri'] ?? ''));
        $scopes       = (string) ($record['scopes'] ?? '');
        if ($scopes === '') {
            $scopes = (string) ($meta['scope'] ?? '');
        }
        $formId = 'social-' . $slug . '-form';
    ?>
        <div class="col-xl-6">
            <div class="dst-card h-100">
                <div class="card-header">
                    <h2 class="card-title d-flex align-items-center gap-2 min-w-0">
                        <span class="dst-icon-chip"><i class="<?= esc((string) $meta['icon'], 'attr') ?>"></i></span>
                        <span class="truncate"><?= esc((string) $meta['name']) ?></span>
                    </h2>
                    <span class="dst-chip <?= $isActive ? 'bg-success-subtle text-success-emphasis' : '' ?>">
                        <i class="fa-solid <?= $isActive ? 'fa-circle-check' : ($state === 'configured' ? 'fa-circle-half-stroke' : 'fa-circle') ?> me-1"></i><?= esc($stateLabel) ?>
                    </span>
                </div>
                <div class="card-body">
                    <form id="<?= $formId ?>" method="post" action="<?= admin_url('social-login/' . $slug . '/save') ?>" data-social-form>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($slug, 'attr') ?>-client-id">Client ID</label>
                            <input type="text" class="form-control font-monospace" id="<?= esc($slug, 'attr') ?>-client-id"
                                   name="client_id" maxlength="255" autocomplete="off" value="<?= esc($clientId, 'attr') ?>"
                                   placeholder="<?= esc((string) $meta['name'], 'attr') ?> client ID">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($slug, 'attr') ?>-client-secret">Client secret</label>
                            <input type="password" class="form-control font-monospace" id="<?= esc($slug, 'attr') ?>-client-secret"
                                   name="client_secret" maxlength="500" autocomplete="new-password"
                                   placeholder="<?= $hasSecret ? '•••••••• saved — leave blank to keep' : 'Client secret' ?>">
                            <div class="form-text"><?= $hasSecret ? 'Stored encrypted with AES-256-GCM. Enter a new value only to rotate it.' : 'Encrypted at rest with your application key.' ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($slug, 'attr') ?>-redirect">Redirect URI</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" id="<?= esc($slug, 'attr') ?>-redirect"
                                       name="redirect_uri" maxlength="255" autocomplete="off"
                                       value="<?= esc($savedUri, 'attr') ?>" placeholder="<?= esc($defaultUri, 'attr') ?>">
                                <button class="btn btn-soft" type="button" data-copy="<?= esc($defaultUri, 'attr') ?>" title="Copy default callback URL"><i class="fa-regular fa-copy"></i></button>
                            </div>
                            <div class="form-text">Leave blank to use <code><?= esc($defaultUri) ?></code>. Register that exact URL in the provider console.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($slug, 'attr') ?>-scopes">Scopes</label>
                            <input type="text" class="form-control font-monospace" id="<?= esc($slug, 'attr') ?>-scopes"
                                   name="scopes" maxlength="500" autocomplete="off" value="<?= esc($scopes, 'attr') ?>">
                        </div>
                        <div class="dst-setting-row mb-3">
                            <div class="min-w-0">
                                <label class="form-label mb-0" for="<?= esc($slug, 'attr') ?>-active">Show on sign-in page</label>
                                <div class="text-muted-2 small">Requires a client ID and secret.</div>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="<?= esc($slug, 'attr') ?>-active"
                                       name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-brand" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save <?= esc((string) $meta['name']) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = button.getAttribute('data-copy');
            var done = function () { Destra.toast('success', 'Callback URL copied.'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(done).catch(done);
            } else {
                var input = button.closest('.input-group').querySelector('input');
                input.value = value;
                input.select();
                document.execCommand('copy');
                done();
            }
        });
    });

    document.querySelectorAll('[data-social-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submit = form.querySelector('button[type="submit"]');
            submit.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (response) { return response.json(); }).then(function (json) {
                submit.disabled = false;
                if (json.success === false) {
                    Destra.toast('error', json.error || 'The provider could not be saved.');
                    return;
                }
                Destra.toast('success', json.message || 'Saved.');
                setTimeout(function () { window.location.reload(); }, 500);
            }).catch(function () {
                submit.disabled = false;
                Destra.toast('error', 'Network error — please retry.');
            });
        });
    });
})();
</script>
<?= $this->endSection() ?>
