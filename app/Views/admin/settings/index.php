<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <button form="settingsForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-tabs-scroll mb-3">
    <ul class="nav nav-pills flex-nowrap">
        <?php foreach ($tabs as $key => $meta): ?>
            <li class="nav-item">
                <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" href="<?= admin_url('settings?group=' . $key) ?>">
                    <i class="<?= esc($meta['icon'], 'attr') ?> me-2"></i><?= esc($meta['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<form id="settingsForm" method="post" action="<?= admin_url('settings/update/' . $tab) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dst-card">
                <div class="card-header">
                    <h2 class="card-title"><?= esc($tabs[$tab]['label']) ?> settings</h2>
                    <span class="dst-chip"><?= count($fields) ?> options</span>
                </div>
                <div class="card-body">
                    <?php if ($fields === []): ?>
                        <p class="text-muted-2 mb-0">No options in this group yet.</p>
                    <?php endif; ?>
                    <div class="row g-3">
                        <?php foreach ($fields as $key => $field):
                            $type  = (string) ($field['type'] ?? 'text');
                            $id    = 'set-' . preg_replace('/[^a-z0-9-]/i', '-', $key);
                            $value = $values[$key] ?? '';
                            $help  = (string) ($field['help'] ?? '');
                            $wide  = in_array($type, ['textarea', 'html', 'css', 'js'], true);
                        ?>
                            <?php if ($type === 'bool'): ?>
                                <div class="col-12">
                                    <div class="dst-setting-row">
                                        <div class="min-w-0">
                                            <label class="form-label mb-0" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                            <?php if ($help !== ''): ?><div class="text-muted-2 small"><?= esc($help) ?></div><?php endif; ?>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input type="hidden" name="settings[<?= esc($key, 'attr') ?>]" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="<?= $id ?>"
                                                   name="settings[<?= esc($key, 'attr') ?>]" value="1"
                                                   <?= (string) $value === '1' ? 'checked' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($type === 'media'): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <input type="hidden" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" value="<?= (int) $value ?>">
                                    <div class="d-flex gap-2">
                                        <div class="dst-media-preview" id="<?= $id ?>-preview"
                                             data-media-preview="#<?= $id ?>-preview" style="width:96px;height:72px;flex:none;border-radius:12px;overflow:hidden;border:1px solid var(--dst-line);background:var(--dst-canvas);display:grid;place-items:center;">
                                            <?php if ((int) $value > 0): ?>
                                                <img src="<?= esc(media_url((int) $value, 'thumb'), 'attr') ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                <i class="fa-regular fa-image text-muted-2"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-grid gap-1 flex-grow-1 align-content-center">
                                            <button type="button" class="btn btn-soft btn-sm" data-media-picker="#<?= $id ?>" data-media-preview="#<?= $id ?>-preview">Choose image</button>
                                            <button type="button" class="btn btn-danger-soft btn-sm" data-media-clear="#<?= $id ?>" data-media-preview="#<?= $id ?>-preview">Remove</button>
                                        </div>
                                    </div>
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'color'): ?>
                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" value="<?= esc((string) ($value ?: '#4f46e5'), 'attr') ?>"
                                               oninput="document.getElementById('<?= $id ?>-hex').value = this.value">
                                        <input type="text" class="form-control" id="<?= $id ?>-hex" name="settings[<?= esc($key, 'attr') ?>]"
                                               value="<?= esc((string) $value, 'attr') ?>" pattern="#[0-9a-fA-F]{6}" maxlength="7">
                                    </div>
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'select'): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <select class="form-select" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]">
                                        <?php foreach (($field['options'] ?? []) as $optValue => $optLabel): ?>
                                            <option value="<?= esc((string) $optValue, 'attr') ?>" <?= (string) $value === (string) $optValue ? 'selected' : '' ?>><?= esc((string) $optLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'textarea' || $type === 'html' || $type === 'css' || $type === 'js'): ?>
                                <div class="col-12">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <textarea class="form-control <?= $type !== 'textarea' ? 'font-monospace' : '' ?>" id="<?= $id ?>" rows="<?= $type === 'textarea' ? 3 : 6 ?>"
                                              name="settings[<?= esc($key, 'attr') ?>]" spellcheck="false"
                                              placeholder="<?= $type === 'html' ? '<script>…</script>' : '' ?>"><?= esc((string) $value) ?></textarea>
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'secret'): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <input type="password" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]"
                                           autocomplete="new-password" placeholder="<?= (string) $value !== '' ? '•••••••• saved — leave blank to keep' : 'Not set' ?>">
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php elseif ($type === 'int'): ?>
                                <div class="col-md-6 col-xl-4">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <input type="number" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]"
                                           value="<?= esc((string) $value, 'attr') ?>" min="<?= (int) ($field['min'] ?? 0) ?>" max="<?= (int) ($field['max'] ?? 1000000) ?>">
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
                                    <input type="text" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                    <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($fields !== []): ?>
                    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                        <span class="text-muted-2 small">Changes apply instantly across the site.</span>
                        <button class="btn btn-brand" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($tab === 'mail'): ?>
                <div class="dst-card mt-3">
                    <div class="card-header"><h2 class="card-title">Send a test email</h2></div>
                    <div class="card-body">
                        <p class="text-muted-2 small">Save your SMTP details first, then send yourself a message to confirm delivery.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="email" class="form-control" style="max-width:320px;" id="testMailTo" value="<?= esc((string) service('settings')->get('general.admin_email')) ?>" placeholder="you@example.com">
                            <button type="button" class="btn btn-soft" id="testMailBtn"><i class="fa-regular fa-paper-plane me-2"></i>Send test</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Quick actions</h2></div>
                <div class="card-body d-grid gap-2">
                    <a class="btn btn-soft text-start" href="<?= admin_url('settings?group=seo') ?>"><i class="fa-solid fa-magnifying-glass-chart me-2"></i>SEO defaults</a>
                    <a class="btn btn-soft text-start" href="<?= admin_url('settings?group=mail') ?>"><i class="fa-regular fa-envelope me-2"></i>Email delivery</a>
                    <a class="btn btn-soft text-start" href="<?= admin_url('settings?group=security') ?>"><i class="fa-solid fa-lock me-2"></i>Security hardening</a>
                    <a class="btn btn-soft text-start" href="<?= admin_url('tools') ?>"><i class="fa-solid fa-screwdriver-wrench me-2"></i>Tools &amp; cache</a>
                </div>
            </div>

            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">Good to know</h2></div>
                <div class="card-body text-muted-2 small">
                    <?php if ($tab === 'general'): ?>
                        Custom head/footer code is injected verbatim — only paste code you trust. Custom CSS is limited to stylesheets, JavaScript runs as written.
                    <?php elseif ($tab === 'seo'): ?>
                        Titles under 60 characters and descriptions under 160 characters perform best in search results. Canonical and hreflang tags are generated automatically for every content page.
                    <?php elseif ($tab === 'security'): ?>
                        Passwords are hashed with Argon2id, secrets are encrypted with AES-256-GCM, and every admin action is written to the audit log.
                    <?php elseif ($tab === 'mail'): ?>
                        Use port 587 with TLS for most hosts. Gmail requires an app password. Secrets are encrypted at rest and never shown again.
                    <?php elseif ($tab === 'media'): ?>
                        Uploads are re-encoded, stripped of metadata and stored outside the web root.
                    <?php else: ?>
                        Every option saves with CSRF protection and is recorded in the audit log. Sensitive values are encrypted with AES-256-GCM.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($tab === 'mail'): ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var button = document.getElementById('testMailBtn');
    if (!button) { return; }
    button.addEventListener('click', function () {
        var to = document.getElementById('testMailTo').value.trim();
        if (!to) { Destra.toast('error', 'Enter a recipient address.'); return; }
        button.disabled = true;
        var body = new FormData();
        body.append('to', to);
        body.append(Destra.csrfName, Destra.csrfToken());
        fetch('<?= admin_url('settings/test-mail') ?>', {
            method: 'POST', body: body, credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (json) {
            Destra.toast(json.success ? 'success' : 'error', json.message || json.error || 'Done.');
        }).catch(function () {
            Destra.toast('error', 'Request failed.');
        }).then(function () { button.disabled = false; });
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
