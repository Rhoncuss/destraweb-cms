<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a class="btn btn-soft" href="<?= admin_url('notifications') ?>"><i class="fa-regular fa-bell me-2"></i>Notifications</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$avatarId = ctype_digit((string) ($user['avatar'] ?? '')) ? (int) $user['avatar'] : 0;
$avatarImage = avatar_url($user);
$initials = initials((string) ($user['name'] ?? 'A'));
$memberSince = ! empty($user['created_at']) ? date('F Y', strtotime((string) $user['created_at'])) : '—';
$lastLogin = ! empty($user['last_login_at']) ? human_date((string) $user['last_login_at'], 'M j, Y H:i') : 'Never';
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="dst-card">
            <div class="profile-hero">
                <div class="profile-avatar-lg">
                    <?php if ($avatarImage !== ''): ?>
                        <img src="<?= esc($avatarImage, 'attr') ?>" alt="<?= esc((string) $user['name'], 'attr') ?>">
                    <?php else: ?>
                        <span><?= esc($initials) ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <h2 class="h4 mb-1 truncate"><?= esc((string) $user['name']) ?></h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="dst-badge active"><?= esc((string) ($user['role_name'] ?? 'Member')) ?></span>
                        <span class="dst-chip"><i class="fa-solid fa-at me-1"></i><?= esc((string) $user['username']) ?></span>
                        <span class="dst-chip"><i class="fa-regular fa-calendar me-1"></i>Joined <?= esc($memberSince) ?></span>
                    </div>
                </div>
            </div>

            <form method="post" action="<?= admin_url('profile') ?>" class="card-body pt-0">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="profileName">Full name</label>
                        <input type="text" class="form-control" id="profileName" name="name" maxlength="120" required value="<?= esc((string) $user['name'], 'attr') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profileUsername">Username</label>
                        <input type="text" class="form-control" id="profileUsername" name="username" maxlength="60" required value="<?= esc((string) $user['username'], 'attr') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profileEmail">Email address</label>
                        <input type="email" class="form-control" id="profileEmail" name="email" maxlength="190" required value="<?= esc((string) $user['email'], 'attr') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profileCurrentPassword">Current password</label>
                        <input type="password" class="form-control" id="profileCurrentPassword" name="current_password" autocomplete="current-password" placeholder="Only needed to change email">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profileLocale">Interface language</label>
                        <select class="form-select" id="profileLocale" name="locale">
                            <?php foreach ($languages as $language): ?>
                                <option value="<?= esc((string) $language['code'], 'attr') ?>" <?= (string) $user['locale'] === (string) $language['code'] ? 'selected' : '' ?>>
                                    <?= esc((string) $language['name']) ?><?= ! empty($language['native_name']) ? ' — ' . esc((string) $language['native_name']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profileTimezone">Timezone</label>
                        <select class="form-select" id="profileTimezone" name="timezone">
                            <option value="">Use site default</option>
                            <?php foreach ($timezones as $tz): ?>
                                <option value="<?= esc($tz, 'attr') ?>" <?= (string) ($user['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= esc($tz) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="profileBio">Short bio</label>
                        <textarea class="form-control" id="profileBio" name="bio" rows="3" maxlength="1000" placeholder="Shown on your author archive and article bylines."><?= esc((string) ($user['bio'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label d-block">Avatar</label>
                        <input type="hidden" name="avatar" id="profileAvatarId" value="<?= $avatarId ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="dst-avatar-lg" id="profileAvatarPreview">
                                <?php if ($avatarImage !== ''): ?>
                                    <img src="<?= esc($avatarImage, 'attr') ?>" alt="">
                                <?php else: ?>
                                    <span><?= esc($initials) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <button type="button" class="btn btn-soft btn-sm" data-media-picker="#profileAvatarId" data-media-preview="#profileAvatarPreview"><i class="fa-regular fa-image me-1"></i>Choose avatar</button>
                                <button type="button" class="btn btn-soft btn-sm" data-media-clear="#profileAvatarId" data-media-preview="#profileAvatarPreview"><i class="fa-solid fa-xmark me-1"></i>Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-brand" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save changes</button>
                </div>
            </form>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header">
                <h2 class="card-title">Password</h2>
                <span class="dst-chip"><i class="fa-solid fa-shield-halved me-1"></i>Argon2id</span>
            </div>
            <form method="post" action="<?= admin_url('profile/password') ?>" class="card-body">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="currentPassword">Current password</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="newPassword">New password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="confirmPassword">Confirm new password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <span class="text-muted-2 small">At least <?= (int) setting('security.password_min', 10) ?> characters with letters and numbers. Changing it signs out other remembered devices.</span>
                    <button class="btn btn-brand" type="submit"><i class="fa-solid fa-key me-2"></i>Update password</button>
                </div>
            </form>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header">
                <h2 class="card-title">Workspace appearance</h2>
                <span class="dst-chip"><?= esc(count($schemes)) ?> colour schemes</span>
            </div>
            <form method="post" action="<?= admin_url('profile/preferences') ?>" class="card-body" id="profileSchemeForm">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <?php foreach ($schemes as $key => $scheme): $active = ($prefs['admin_scheme'] ?: '') === $key; ?>
                        <div class="col-6 col-md-3">
                            <label class="scheme-option <?= $active ? 'is-active' : '' ?>">
                                <input type="radio" name="admin_scheme" value="<?= esc($key, 'attr') ?>" <?= $active ? 'checked' : '' ?>>
                                <span class="scheme-swatch" style="background:linear-gradient(135deg, <?= esc($scheme['sidebar'], 'attr') ?>, <?= esc($scheme['primary'], 'attr') ?>);"></span>
                                <span class="scheme-name"><?= esc($scheme['name']) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <span class="text-muted-2 small">Applies to your account only. Leave all unselected to follow the site-wide scheme.</span>
                    <button class="btn btn-soft" type="submit"><i class="fa-solid fa-palette me-2"></i>Apply scheme</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">At a glance</h2></div>
            <div class="card-body d-grid gap-2">
                <div class="profile-stat"><span>Content</span><strong><?= number_format($stats['content']) ?></strong></div>
                <div class="profile-stat"><span>Published</span><strong><?= number_format($stats['published']) ?></strong></div>
                <div class="profile-stat"><span>Media uploads</span><strong><?= number_format($stats['media']) ?></strong></div>
                <div class="profile-stat"><span>Comments</span><strong><?= number_format($stats['comments']) ?></strong></div>
            </div>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header"><h2 class="card-title">Account</h2></div>
            <div class="card-body">
                <div class="dst-link-row"><i class="fa-solid fa-id-badge text-muted-2"></i><div class="min-w-0 flex-grow-1"><div class="small text-muted-2">Role</div><div class="fw-650"><?= esc((string) ($user['role_name'] ?? 'Member')) ?></div></div></div>
                <div class="dst-link-row"><i class="fa-solid fa-right-to-bracket text-muted-2"></i><div class="min-w-0 flex-grow-1"><div class="small text-muted-2">Last sign-in</div><div class="fw-650"><?= esc($lastLogin) ?></div><div class="text-muted-2 small"><?= esc((string) ($user['last_login_ip'] ?? '')) ?></div></div></div>
                <div class="dst-link-row"><i class="fa-regular fa-envelope text-muted-2"></i><div class="min-w-0 flex-grow-1"><div class="small text-muted-2">Email status</div><div class="fw-650"><?= ! empty($user['email_verified_at']) ? 'Verified' : 'Not verified' ?></div></div></div>
                <div class="dst-link-row"><i class="fa-solid fa-key text-muted-2"></i><div class="min-w-0 flex-grow-1"><div class="small text-muted-2">API tokens</div><a class="fw-650" href="<?= admin_url('api-tokens') ?>">Manage tokens</a></div></div>
            </div>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header"><h2 class="card-title">Recent sign-ins</h2></div>
            <div class="card-body p-0">
                <?php if ($logins === []): ?>
                    <div class="p-3 text-muted-2 small">No sign-in history yet.</div>
                <?php else: ?>
                    <div class="px-3 py-2">
                        <?php foreach ($logins as $login): ?>
                            <div class="profile-login">
                                <i class="fa-solid <?= (int) $login['success'] === 1 ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger' ?>"></i>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="small fw-650"><?= esc(human_date($login['created_at'], 'M j, Y H:i')) ?></div>
                                    <div class="text-muted-2 small truncate"><?= esc((string) $login['ip']) ?> · <?= esc((string) $login['user_agent']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dst-card mt-3">
            <div class="card-header"><h2 class="card-title">Your activity</h2></div>
            <div class="card-body p-0">
                <?php if ($activity === []): ?>
                    <div class="p-3 text-muted-2 small">No activity recorded yet.</div>
                <?php else: ?>
                    <div class="px-3 py-2">
                        <?php foreach ($activity as $entry): ?>
                            <div class="profile-login">
                                <i class="fa-solid fa-clock-rotate-left text-muted-2"></i>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="small fw-650 truncate"><?= esc((string) $entry['description']) ?></div>
                                    <div class="text-muted-2 small"><?= esc(human_date($entry['created_at'], 'M j, Y H:i')) ?> · <?= esc((string) $entry['module']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var schemeForm = document.getElementById('profileSchemeForm');
    if (!schemeForm) { return; }

    schemeForm.querySelectorAll('input[name="admin_scheme"]').forEach(function (input) {
        input.addEventListener('change', function () {
            schemeForm.querySelectorAll('.scheme-option').forEach(function (option) {
                option.classList.toggle('is-active', option.querySelector('input').checked);
            });
        });
    });

    schemeForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var chosen = schemeForm.querySelector('input[name="admin_scheme"]:checked');
        var button = schemeForm.querySelector('button[type="submit"]');
        if (!chosen) { Destra.toast('error', 'Choose a colour scheme first.'); return; }
        button.disabled = true;
        Destra.postForm(schemeForm.action, { admin_scheme: chosen.value }).then(function (json) {
            Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Saved');
            if (json.success !== false) { setTimeout(function () { window.location.reload(); }, 500); }
            else { button.disabled = false; }
        }).catch(function () {
            button.disabled = false;
            Destra.toast('error', 'Network error — please retry.');
        });
    });
})();
</script>
<?= $this->endSection() ?>
