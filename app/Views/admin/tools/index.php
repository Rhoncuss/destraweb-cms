<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <button type="button" class="btn btn-soft" data-tools-clear><i class="fa-solid fa-broom me-2"></i>Clear cache</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-tabs-scroll mb-3">
    <ul class="nav nav-pills flex-nowrap">
        <li class="nav-item"><a class="nav-link <?= $tab === 'overview' ? 'active' : '' ?>" href="<?= admin_url('tools') ?>"><i class="fa-solid fa-gauge-high me-2"></i>Overview</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'audit' ? 'active' : '' ?>" href="<?= admin_url('tools?tab=audit') ?>"><i class="fa-solid fa-clipboard-list me-2"></i>Audit log</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'security' ? 'active' : '' ?>" href="<?= admin_url('tools?tab=security') ?>"><i class="fa-solid fa-shield-halved me-2"></i>Sign-in security</a></li>
    </ul>
</div>

<?php if ($tab === 'overview'): ?>
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat">
                <span class="stat-label">Cache entries</span>
                <span class="stat-value"><?= number_format($cache['files']) ?></span>
                <span class="stat-foot"><i class="fa-solid fa-database me-1"></i><?= esc(format_bytes((int) $cache['size'])) ?> on disk</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat stat-ocean">
                <span class="stat-label">PHP</span>
                <span class="stat-value" style="font-size:22px;"><?= esc($system['php']) ?></span>
                <span class="stat-foot"><i class="fa-brands fa-php me-1"></i><?= esc($system['server']) ?></span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat stat-sunrise">
                <span class="stat-label">Database</span>
                <span class="stat-value" style="font-size:18px;"><?= esc($system['db'] !== '' ? $system['db'] : 'Connected') ?></span>
                <span class="stat-foot"><i class="fa-solid fa-server me-1"></i><?= esc($system['os']) ?></span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat">
                <span class="stat-label">Disk space</span>
                <span class="stat-value" style="font-size:20px;"><?= $system['free'] !== null ? esc(format_bytes((int) $system['free'])) : '—' ?></span>
                <span class="stat-foot"><i class="fa-regular fa-hard-drive me-1"></i>free of <?= $system['total'] !== null ? esc(format_bytes((int) $system['total'])) : '—' ?></span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="dst-card mb-3">
                <div class="card-header">
                    <h2 class="card-title">Maintenance</h2>
                    <span class="dst-badge <?= $maintenance ? 'pending' : 'active' ?>"><?= $maintenance ? 'Site offline' : 'Site online' ?></span>
                </div>
                <div class="card-body">
                    <div class="dst-setting-row mb-3">
                        <div>
                            <label class="form-label mb-0" for="maintenanceToggle">Maintenance mode</label>
                            <div class="text-muted-2 small">Visitors see the maintenance page. Signed-in administrators keep full access.</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="maintenanceToggle" data-maintenance-toggle <?= $maintenance ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted-2 small">Allowed IPs: <code><?= esc($maintenanceIps !== '' ? $maintenanceIps : 'none') ?></code></span>
                        <a class="btn btn-soft btn-sm ms-auto" href="<?= admin_url('settings?group=general') ?>"><i class="fa-solid fa-pen me-1"></i>Edit whitelist</a>
                    </div>
                </div>
            </div>

            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">Housekeeping</h2></div>
                <div class="card-body d-grid gap-2">
                    <div class="dst-setting-row">
                        <div>
                            <div class="fw-650">Application cache</div>
                            <div class="text-muted-2 small">Menus, sitemaps, feeds, widgets and settings fragments.</div>
                        </div>
                        <button class="btn btn-soft btn-sm" type="button" data-tools-clear>Flush</button>
                    </div>
                    <div class="dst-setting-row">
                        <div>
                            <div class="fw-650">Sitemap &amp; feed</div>
                            <div class="text-muted-2 small">Rebuild crawler XML after bulk changes.</div>
                        </div>
                        <button class="btn btn-soft btn-sm" type="button" data-tools-seo>Rebuild</button>
                    </div>
                    <div class="dst-setting-row">
                        <div>
                            <div class="fw-650">Audit log retention</div>
                            <div class="text-muted-2 small">Remove entries older than <?= (int) service('settings')->get('security.audit_retention', 180) ?> days.</div>
                        </div>
                        <button class="btn btn-danger-soft btn-sm" type="button" data-tools-prune>Prune</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="dst-card mb-3">
                <div class="card-header"><h2 class="card-title">Environment</h2></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2 small">CodeIgniter</span><span class="fw-650"><?= esc($system['ci']) ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2 small">Timezone</span><span class="fw-650"><?= esc($system['timezone']) ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2 small">Memory limit</span><span class="fw-650"><?= esc($system['memory']) ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2 small">Upload limit</span><span class="fw-650"><?= esc($system['upload']) ?> / post <?= esc($system['post']) ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted-2 small">Writable folders</span>
                        <span class="fw-650">
                            <?php foreach ($system['writable'] as $label => $ok): ?>
                                <span class="dst-chip me-1" style="<?= $ok ? '' : 'color:#b42318;background:#fef3f2;' ?>"><?= esc($label) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="dst-card">
                <div class="card-header"><h2 class="card-title">PHP extensions</h2></div>
                <div class="card-body">
                    <?php foreach ($system['extensions'] as $ext): ?>
                        <span class="dst-chip me-1 mb-1"><i class="fa-solid fa-check me-1 text-success"></i><?= esc($ext) ?></span>
                    <?php endforeach; ?>
                    <?php foreach ($system['missing'] as $ext): ?>
                        <span class="dst-chip me-1 mb-1" style="color:#b42318;background:#fef3f2;"><i class="fa-solid fa-xmark me-1"></i><?= esc($ext) ?></span>
                    <?php endforeach; ?>
                    <?php if ($system['extensions'] === [] && $system['missing'] === []): ?>
                        <span class="text-muted-2 small">No extension data available.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'audit'): ?>
    <div class="dst-card mb-3">
        <div class="card-body py-3">
            <form method="get" action="<?= admin_url('tools') ?>" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="tab" value="audit">
                <input type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" class="form-control form-control-sm" placeholder="Search actions or descriptions…" style="width:240px;">
                <select name="module" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All modules</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= esc($module, 'attr') ?>" <?= $filters['module'] === $module ? 'selected' : '' ?>><?= esc($module) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="user" class="form-select form-select-sm" style="width:auto;">
                    <option value="0">Everyone</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= (int) $id ?>" <?= $filters['user'] === (int) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="from" value="<?= esc($filters['from'], 'attr') ?>" class="form-control form-control-sm" style="width:auto;">
                <input type="date" name="to" value="<?= esc($filters['to'], 'attr') ?>" class="form-control form-control-sm" style="width:auto;">
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                <?php $exportQs = http_build_query(array_filter($filters)); ?>
                <a class="btn btn-soft btn-sm ms-auto" href="<?= admin_url('tools/audit/export' . ($exportQs !== '' ? '?' . $exportQs : '')) ?>"><i class="fa-solid fa-download me-1"></i>Export CSV</a>
            </form>
        </div>
    </div>

    <div class="dst-card">
        <div class="card-header">
            <h2 class="card-title">Audit trail</h2>
            <span class="text-muted-2 small"><?= number_format($audit['total']) ?> entries</span>
        </div>
        <?php if ($audit['items'] === []): ?>
            <div class="dst-empty">
                <i class="fa-solid fa-clipboard-list"></i>
                <h3>No activity matches</h3>
                <p>Every save, delete and settings change is recorded here with the user, IP address and timestamp.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($audit['items'] as $row): ?>
                        <tr>
                            <td class="dst-meta" style="white-space:nowrap;"><?= esc(date('d M Y H:i', strtotime((string) $row['created_at']) ?: time())) ?></td>
                            <td class="min-w-0">
                                <div class="fw-650 truncate"><?= esc((string) ($row['user_name'] ?? 'System')) ?></div>
                                <?php if (! empty($row['user_email'])): ?><div class="text-muted-2 small truncate"><?= esc((string) $row['user_email']) ?></div><?php endif; ?>
                            </td>
                            <td><code class="dst-trans-key"><?= esc((string) $row['action']) ?></code></td>
                            <td><span class="dst-chip"><?= esc((string) $row['module']) ?></span></td>
                            <td class="min-w-0"><div class="truncate" style="max-width:380px;" title="<?= esc((string) ($row['meta'] ?? ''), 'attr') ?>"><?= esc((string) ($row['description'] ?? '')) ?></div></td>
                            <td class="dst-meta"><?= esc((string) ($row['ip'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($audit['pages'] > 1): ?>
        <nav class="mt-3" aria-label="Audit pagination">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $audit['pages']; $i++): ?>
                    <?php $link = admin_url('tools?' . http_build_query(array_filter(array_merge($filters, ['tab' => 'audit', 'page' => $i])))); ?>
                    <li class="page-item <?= $i === $audit['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= esc($link, 'attr') ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php if ($tab === 'security'): ?>
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat stat-sunrise">
                <span class="stat-label">Failed attempts (24h)</span>
                <span class="stat-value"><?= number_format($failed24h) ?></span>
                <span class="stat-foot"><i class="fa-solid fa-lock me-1"></i>blocked by throttling</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat">
                <span class="stat-label">Password hashing</span>
                <span class="stat-value" style="font-size:20px;">Argon2id</span>
                <span class="stat-foot"><i class="fa-solid fa-shield-halved me-1"></i>memory-hard</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat stat-ocean">
                <span class="stat-label">Session timeout</span>
                <span class="stat-value" style="font-size:20px;"><?= (int) service('settings')->get('security.session_timeout', 120) ?> min</span>
                <span class="stat-foot"><i class="fa-regular fa-clock me-1"></i>idle lock</span>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="dst-stat">
                <span class="stat-label">Lockout</span>
                <span class="stat-value" style="font-size:20px;"><?= (int) service('settings')->get('security.login_attempts', 5) ?> tries</span>
                <span class="stat-foot"><i class="fa-solid fa-user-lock me-1"></i>per <?= (int) service('settings')->get('security.lockout_minutes', 15) ?> min</span>
            </div>
        </div>
    </div>

    <div class="dst-card">
        <div class="card-header">
            <h2 class="card-title">Recent sign-in attempts</h2>
            <a class="btn btn-soft btn-sm" href="<?= admin_url('settings?group=security') ?>"><i class="fa-solid fa-sliders me-1"></i>Security settings</a>
        </div>
        <?php if ($loginAttempts === []): ?>
            <div class="dst-empty">
                <i class="fa-solid fa-shield-halved"></i>
                <h3>No attempts recorded</h3>
                <p>Sign-in attempts appear here as soon as anyone tries the admin login form.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>When</th><th>Email</th><th>IP address</th><th>Browser</th><th>Result</th></tr></thead>
                    <tbody>
                    <?php foreach ($loginAttempts as $row): ?>
                        <tr>
                            <td class="dst-meta" style="white-space:nowrap;"><?= esc(date('d M Y H:i', strtotime((string) $row['created_at']) ?: time())) ?></td>
                            <td class="fw-650"><?= esc((string) $row['email']) ?></td>
                            <td class="dst-meta"><?= esc((string) $row['ip']) ?></td>
                            <td class="min-w-0"><div class="truncate text-muted-2 small" style="max-width:320px;"><?= esc((string) ($row['user_agent'] ?? '')) ?></div></td>
                            <td>
                                <?php if ((int) $row['success'] === 1): ?>
                                    <span class="dst-badge published">Success</span>
                                <?php else: ?>
                                    <span class="dst-badge spam">Failed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    document.querySelectorAll('[data-tools-clear]').forEach(function (button) {
        button.addEventListener('click', function () {
            Destra.postForm('<?= admin_url('tools/cache/clear') ?>', {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Done');
                if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 500); }
            });
        });
    });

    var seo = document.querySelector('[data-tools-seo]');
    if (seo) {
        seo.addEventListener('click', function () {
            Destra.postForm('<?= admin_url('seo/rebuild') ?>', {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Done');
            });
        });
    }

    var prune = document.querySelector('[data-tools-prune]');
    if (prune) {
        prune.addEventListener('click', function () {
            Swal.fire({
                title: 'Prune audit log?', text: 'Entries older than the retention window are permanently deleted.',
                icon: 'warning', showCancelButton: true, confirmButtonText: 'Prune', cancelButtonText: 'Cancel',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
                buttonsStyling: false
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                Destra.postForm('<?= admin_url('tools/audit/prune') ?>', {}).then(function (json) {
                    Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Done');
                });
            });
        });
    }

    var maintenance = document.querySelector('[data-maintenance-toggle]');
    if (maintenance) {
        maintenance.addEventListener('change', function () {
            Destra.postForm('<?= admin_url('tools/maintenance') ?>', { value: maintenance.checked ? 1 : 0 }).then(function (json) {
                if (json.success === false) { maintenance.checked = !maintenance.checked; }
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Updated');
            }).catch(function () {
                maintenance.checked = !maintenance.checked;
                Destra.toast('error', 'Network error — please retry.');
            });
        });
    }
})();
</script>
<?= $this->endSection() ?>
