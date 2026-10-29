<?= $this->extend('installer/layout') ?>

<?= $this->section('content') ?>
<div class="card-head">
    <div class="eyebrow">Step 2 · Identity</div>
    <h1>Name your site, create the owner account</h1>
    <p class="lead">
        The account you create here receives the <strong>Super Admin</strong> role. Passwords are hashed with
        Argon2id and every form on the platform is CSRF protected.
    </p>
</div>
<div class="card-body">
    <?php if (! empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Almost there — please review:</strong>
            <ul>
                <?php foreach ($errors as $message): ?>
                    <li><?= esc($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= esc(($base ?? '/') . 'install/finish') ?>" autocomplete="off">
        <?= csrf_field() ?>

        <div class="grid">
            <div>
                <label class="field" for="site_name">Site name</label>
                <input type="text" id="site_name" name="site_name" maxlength="120" value="<?= esc($old['site_name'] ?? $defaults['site_name']) ?>" required>
            </div>
            <div>
                <label class="field" for="tagline">Tagline</label>
                <input type="text" id="tagline" name="tagline" maxlength="200" value="<?= esc($old['tagline'] ?? $defaults['tagline']) ?>">
            </div>
            <div>
                <label class="field" for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    <?php foreach ($timezones as $zone): ?>
                        <option value="<?= esc($zone) ?>" <?= ($old['timezone'] ?? $defaults['timezone']) === $zone ? 'selected' : '' ?>><?= esc($zone) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="field" for="locale">Default language</label>
                <select id="locale" name="locale">
                    <option value="en" <?= ($old['locale'] ?? $defaults['locale']) === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="id" <?= ($old['locale'] ?? $defaults['locale']) === 'id' ? 'selected' : '' ?>>Bahasa Indonesia</option>
                    <option value="ar" <?= ($old['locale'] ?? $defaults['locale']) === 'ar' ? 'selected' : '' ?>>العربية (RTL)</option>
                </select>
            </div>

            <div class="span-2" style="margin-top:6px;border-top:1px solid var(--line);padding-top:20px;">
                <label class="field" style="font-size:13.5px;color:var(--ink);">Super Admin account</label>
            </div>

            <div>
                <label class="field" for="admin_name">Full name</label>
                <input type="text" id="admin_name" name="admin_name" maxlength="120" value="<?= esc($old['admin_name'] ?? $defaults['admin_name']) ?>" required>
            </div>
            <div>
                <label class="field" for="admin_email">Email address</label>
                <input type="email" id="admin_email" name="admin_email" maxlength="180" value="<?= esc($old['admin_email'] ?? $defaults['admin_email']) ?>" required>
            </div>
            <div>
                <label class="field" for="admin_password">Password <span class="hint">· 10+ chars, letters &amp; numbers</span></label>
                <input type="password" id="admin_password" name="admin_password" minlength="10" autocomplete="new-password" required>
            </div>
            <div>
                <label class="field" for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="10" autocomplete="new-password" required>
            </div>
        </div>

        <div class="actions">
            <a href="<?= esc(($base ?? '/') . 'install') ?>" class="btn btn-ghost">← Back to database</a>
            <button type="submit" class="btn">
                Install DESTRA
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
