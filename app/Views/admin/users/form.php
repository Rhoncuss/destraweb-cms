<?php
$action = $user ? admin_url('users/' . (int) $user['id'] . '/update') : admin_url('users/store');
$isSelf = $user && (int) $user['id'] === (int) ($adminUser['id'] ?? 0);
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('users') ?>" class="btn btn-soft">Back to users</a>
    <button form="userForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save user</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="userForm" method="post" action="<?= esc($action, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Profile</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name</label>
                        <input class="form-control" id="name" name="name" maxlength="120" required value="<?= esc((string) ($user['name'] ?? '')) ?>" data-slug-source="#username">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" maxlength="60" required value="<?= esc((string) ($user['username'] ?? '')) ?>" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="190" required value="<?= esc((string) ($user['email'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="avatar">Avatar URL</label>
                        <input type="url" class="form-control" id="avatar" name="avatar" maxlength="255" value="<?= esc((string) ($user['avatar'] ?? '')) ?>" placeholder="https://…">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">Short bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" maxlength="2000"><?= esc((string) ($user['bio'] ?? '')) ?></textarea>
                        <div class="form-text">Shown on author bylines when enabled in settings.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title"><?= $user ? 'Change password' : 'Initial password' ?></h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" <?= $user ? '' : 'required' ?>>
                        <div class="form-text"><?= $user ? 'Leave blank to keep the current password.' : 'At least 10 characters with letters and numbers.' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirm password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Access</h2></div>
            <div class="card-body">
                <label class="form-label" for="role_id">Role</label>
                <select class="form-select mb-3" id="role_id" name="role_id" <?= $isSelf ? 'disabled' : '' ?>>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= esc($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isSelf): ?>
                    <input type="hidden" name="role_id" value="<?= (int) ($user['role_id'] ?? 0) ?>">
                    <div class="form-text mb-3">You cannot change your own role.</div>
                <?php endif; ?>

                <label class="form-label" for="status">Status</label>
                <select class="form-select mb-3" id="status" name="status" <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
                <?php if ($isSelf): ?><input type="hidden" name="status" value="active"><?php endif; ?>

                <button class="btn btn-brand w-100" type="submit">Save user</button>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Localisation</h2></div>
            <div class="card-body">
                <label class="form-label" for="locale">Admin language</label>
                <select class="form-select mb-3" id="locale" name="locale">
                    <?php foreach ($languages as $language): ?>
                        <option value="<?= esc((string) $language['code'], 'attr') ?>" <?= ($user['locale'] ?? default_locale()) === $language['code'] ? 'selected' : '' ?>><?= esc($language['native_name'] ?: $language['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="timezone">Timezone</label>
                <select class="form-select" id="timezone" name="timezone">
                    <option value="">Use site default</option>
                    <?php foreach ($timezones as $timezone): ?>
                        <option value="<?= esc($timezone, 'attr') ?>" <?= ($user['timezone'] ?? '') === $timezone ? 'selected' : '' ?>><?= esc($timezone) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </aside>
</form>
<?= $this->endSection() ?>
