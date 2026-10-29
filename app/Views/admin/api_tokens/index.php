<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#tokenCreateModal"><i class="fa-solid fa-key me-2"></i>New token</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="dst-stat">
            <span class="stat-label">Tokens</span>
            <span class="stat-value"><?= number_format(count($items)) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-key me-1"></i>personal access tokens</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-ocean">
            <span class="stat-label">Active</span>
            <span class="stat-value"><?= number_format($activeCount) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-bolt me-1"></i>can call the API</span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="dst-stat stat-sunrise">
            <span class="stat-label">Inactive</span>
            <span class="stat-value"><?= number_format($revokedCount + $expiredCount) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-ban me-1"></i><?= (int) $revokedCount ?> revoked · <?= (int) $expiredCount ?> expired</span>
        </div>
    </div>
</div>

<div class="dst-card">
    <div class="card-header">
        <h2 class="card-title">Access tokens</h2>
        <span class="dst-chip"><i class="fa-solid fa-lock me-1"></i>Hashed with SHA-256</span>
    </div>

    <?php if ($items === []): ?>
        <div class="dst-empty">
            <i class="fa-solid fa-key"></i>
            <h3>No API tokens yet</h3>
            <p>Create a token to connect an app, mobile client or automation to the DESTRA REST API. Tokens are shown once and stored only as a hash.</p>
            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#tokenCreateModal">Create your first token</button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <?php if ($isSuper): ?><th>Owner</th><?php endif; ?>
                        <th>Abilities</th>
                        <th>Last used</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item):
                    $now      = time();
                    $revoked  = $item['revoked_at'] !== null && $item['revoked_at'] !== '';
                    $expired  = ! $revoked && $item['expires_at'] !== null && $item['expires_at'] !== '' && strtotime((string) $item['expires_at']) < $now;
                    $state    = $revoked ? 'revoked' : ($expired ? 'expired' : 'active');
                    $abilities = $item['abilities_list'];
                ?>
                    <tr>
                        <td class="min-w-0">
                            <div class="fw-650 truncate"><?= esc((string) $item['name']) ?></div>
                            <div class="text-muted-2 small"><code class="dst-trans-key"><?= esc((string) $item['prefix']) ?>…</code></div>
                        </td>
                        <?php if ($isSuper): ?>
                            <td class="min-w-0">
                                <div class="truncate"><?= esc((string) ($item['user_name'] ?? '—')) ?></div>
                                <div class="text-muted-2 small truncate"><?= esc((string) ($item['user_email'] ?? '')) ?></div>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="d-flex flex-wrap gap-1" style="max-width:340px;">
                                <?php foreach (array_slice($abilities, 0, 3) as $scope): ?>
                                    <span class="dst-chip"><?= esc($scope) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($abilities) > 3): ?>
                                    <span class="dst-chip" title="<?= esc(implode(', ', array_slice($abilities, 3)), 'attr') ?>">+<?= count($abilities) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="dst-meta"><?= $item['last_used_at'] ? esc(date('M j, Y H:i', strtotime((string) $item['last_used_at']))) : 'Never' ?></td>
                        <td class="dst-meta"><?= $item['expires_at'] ? esc(date('M j, Y', strtotime((string) $item['expires_at']))) : 'Never' ?></td>
                        <td>
                            <span class="dst-badge <?= $state === 'active' ? 'active' : ($state === 'expired' ? 'pending' : 'inactive') ?>">
                                <?= ucfirst($state) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <?php if ($state === 'active'): ?>
                                    <button type="button" class="btn btn-soft btn-sm" data-token-revoke="<?= admin_url('api-tokens/' . (int) $item['id'] . '/revoke') ?>"><i class="fa-solid fa-ban me-1"></i>Revoke</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger-soft btn-icon" title="Delete"
                                        data-token-delete="<?= admin_url('api-tokens/' . (int) $item['id'] . '/delete') ?>"
                                        data-token-name="<?= esc((string) $item['name'], 'attr') ?>">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Quick start</h2></div>
            <div class="card-body">
                <p class="text-muted-2 small mb-2">Send the token as a bearer credential. Every request is scoped to the abilities you granted.</p>
                <pre class="p-3 mb-2" style="background:rgba(15,23,42,.06);border-radius:12px;font-size:.8rem;overflow-auto;"><code>curl <?= esc($apiBase) ?>/posts \
  -H "Authorization: Bearer dst_your_token_here" \
  -H "Accept: application/json"</code></pre>
                <div class="d-flex flex-wrap gap-2">
                    <span class="dst-chip"><i class="fa-solid fa-shield-halved me-1"></i>Bearer auth</span>
                    <span class="dst-chip"><i class="fa-solid fa-clock me-1"></i>Optional expiry</span>
                    <span class="dst-chip"><i class="fa-solid fa-fingerprint me-1"></i>Per-token abilities</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Staying safe</h2></div>
            <div class="card-body">
                <ul class="list-unstyled small mb-0 d-grid gap-2">
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Tokens are stored as one-way SHA-256 hashes.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Grant the smallest set of abilities that works.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Set an expiry for temporary integrations.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Revoke instantly — no password change required.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tokenCreateModal" tabindex="-1" aria-labelledby="tokenCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="tokenCreateForm" method="post" action="<?= admin_url('api-tokens') ?>">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" id="tokenCreateLabel">Create API token</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="tokenName">Token name</label>
                    <input type="text" class="form-control" id="tokenName" name="name" maxlength="100" required placeholder="e.g. Mobile app, Zapier, CI deploy">
                    <div class="form-text">A label so you know where this token is used.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Abilities</span>
                        <button type="button" class="btn btn-link btn-sm p-0" data-token-select-all>Select all</button>
                    </label>
                    <div class="row g-2">
                        <?php foreach ($scopes as $scope => $label): ?>
                            <div class="col-md-6">
                                <label class="d-flex gap-2 align-items-start p-2 rounded-3" style="border:1px solid rgba(148,163,184,.25);cursor:pointer;">
                                    <input class="form-check-input mt-1" type="checkbox" name="abilities[]" value="<?= esc($scope, 'attr') ?>">
                                    <span class="min-w-0">
                                        <span class="d-block fw-650 small"><?= esc($scope) ?></span>
                                        <span class="d-block text-muted-2" style="font-size:.75rem;"><?= esc($label) ?></span>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label" for="tokenExpiry">Expiry</label>
                    <select class="form-select" id="tokenExpiry" name="expires_in">
                        <option value="0">Never expires</option>
                        <option value="30">30 days</option>
                        <option value="90" selected>90 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-brand" type="submit"><i class="fa-solid fa-key me-2"></i>Generate token</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="tokenRevealModal" tabindex="-1" aria-labelledby="tokenRevealLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="tokenRevealLabel">Copy your token now</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>This is the only time the token is shown. Store it in a password manager or your app's secret settings.</div>
                <label class="form-label small text-muted-2">Token</label>
                <div class="input-group mb-2">
                    <input type="text" class="form-control font-monospace" id="newTokenValue" readonly>
                    <button class="btn btn-soft" type="button" id="newTokenCopy" title="Copy"><i class="fa-regular fa-copy"></i></button>
                </div>
                <div class="text-muted-2 small" id="newTokenMeta"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal" id="tokenDoneButton">I've saved it</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var createForm = document.getElementById('tokenCreateForm');
    var selectAll = document.querySelector('[data-token-select-all]');

    if (selectAll) {
        selectAll.addEventListener('click', function () {
            var boxes = createForm.querySelectorAll('input[name="abilities[]"]');
            var allChecked = Array.prototype.every.call(boxes, function (box) { return box.checked; });
            boxes.forEach(function (box) { box.checked = !allChecked; });
            selectAll.textContent = allChecked ? 'Select all' : 'Clear all';
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submit = createForm.querySelector('button[type="submit"]');
            var data = new FormData(createForm);
            submit.disabled = true;

            fetch(createForm.action, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (response) { return response.json(); }).then(function (json) {
                submit.disabled = false;
                if (json.success === false) {
                    Destra.toast('error', json.error || 'Could not create the token.');
                    return;
                }
                bootstrap.Modal.getInstance(document.getElementById('tokenCreateModal')).hide();
                document.getElementById('newTokenValue').value = json.token;
                document.getElementById('newTokenMeta').textContent =
                    json.name + ' · ' + json.abilities.join(', ') +
                    (json.expires_at ? ' · expires ' + json.expires_at.slice(0, 10) : ' · never expires');
                createForm.reset();
                if (selectAll) { selectAll.textContent = 'Select all'; }
                new bootstrap.Modal(document.getElementById('tokenRevealModal')).show();
            }).catch(function () {
                submit.disabled = false;
                Destra.toast('error', 'Network error — please retry.');
            });
        });
    }

    var copyButton = document.getElementById('newTokenCopy');
    if (copyButton) {
        copyButton.addEventListener('click', function () {
            var input = document.getElementById('newTokenValue');
            input.select();
            input.setSelectionRange(0, input.value.length);
            var done = function () { Destra.toast('success', 'Token copied to clipboard.'); };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value).then(done).catch(function () { document.execCommand('copy'); done(); });
            } else {
                document.execCommand('copy');
                done();
            }
        });
    }

    document.getElementById('tokenRevealModal').addEventListener('hidden.bs.modal', function () {
        window.location.reload();
    });

    function postWithConfirm(button, title, text, action) {
        Swal.fire({
            title: title, text: text, icon: 'warning',
            showCancelButton: true, confirmButtonText: action, cancelButtonText: 'Cancel',
            customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-soft ms-2' },
            buttonsStyling: false
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            Destra.postForm(button.getAttribute(button.hasAttribute('data-token-revoke') ? 'data-token-revoke' : 'data-token-delete'), {}).then(function (json) {
                Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Done');
                if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 350); }
            }).catch(function () { Destra.toast('error', 'Network error — please retry.'); });
        });
    }

    document.querySelectorAll('[data-token-revoke]').forEach(function (button) {
        button.addEventListener('click', function () {
            postWithConfirm(button, 'Revoke this token?', 'Any app using it will lose API access immediately.', 'Revoke');
        });
    });

    document.querySelectorAll('[data-token-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            postWithConfirm(button, 'Delete ' + (button.getAttribute('data-token-name') || 'this token') + '?', 'The record is removed permanently.', 'Delete');
        });
    });
})();
</script>
<?= $this->endSection() ?>
