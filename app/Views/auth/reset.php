<?= $this->extend('auth/layout') ?>

<?= $this->section('form') ?>
<h1>Choose a new password</h1>
<p class="sub">Resetting the password for <strong><?= esc($email) ?></strong>. Use at least 10 characters with a mix of letters and numbers.</p>

<?php if (! empty($error)): ?>
    <div class="alert alert-error"><span>!</span><span><?= esc($error) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= site_url('reset-password') ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= esc($token) ?>">

    <label class="field" for="password">New password</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        </span>
        <input type="password" id="password" name="password" minlength="10" required autofocus data-strength="#strength-bar">
        <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.6"/></svg>
        </button>
    </div>
    <div class="strength"><span id="strength-bar"></span></div>

    <label class="field" for="password_confirm">Confirm new password</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <input type="password" id="password_confirm" name="password_confirm" minlength="10" required>
    </div>

    <button type="submit" class="btn">Update password <span aria-hidden="true">→</span></button>
</form>
<?= $this->endSection() ?>
