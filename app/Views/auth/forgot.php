<?= $this->extend('auth/layout') ?>

<?= $this->section('form') ?>
<h1>Forgot your password?</h1>
<p class="sub">Enter the email tied to your account and we'll send a secure, single-use reset link that expires in 60 minutes.</p>

<?php if (! empty($error)): ?>
    <div class="alert alert-error"><span>!</span><span><?= esc($error) ?></span></div>
<?php endif; ?>
<?php if (! empty($success)): ?>
    <div class="alert alert-success"><span>✓</span><span><?= esc($success) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= site_url('forgot-password') ?>" novalidate>
    <?= csrf_field() ?>

    <label class="field" for="email">Email address</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
        </span>
        <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@company.com" required autofocus>
    </div>

    <button type="submit" class="btn">Send reset link <span aria-hidden="true">→</span></button>
</form>

<div class="foot"><a href="<?= site_url('login') ?>">← Back to sign in</a></div>
<?= $this->endSection() ?>
