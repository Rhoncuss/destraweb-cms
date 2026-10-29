<?= $this->extend('auth/layout') ?>

<?= $this->section('form') ?>
<h1>Create your account</h1>
<p class="sub">Join <?= esc(service('settings')->get('general.site_name', 'DESTRA CMS')) ?>. You can change your profile details later.</p>

<?php if (! empty($error)): ?>
    <div class="alert alert-error"><span>!</span><span><?= esc($error) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= site_url('register') ?>" novalidate>
    <?= csrf_field() ?>

    <label class="field" for="name">Full name</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>
        </span>
        <input type="text" id="name" name="name" value="<?= esc(old('name')) ?>" placeholder="Alex Morgan" required>
    </div>

    <div class="grid-two">
        <div>
            <label class="field" for="username">Username</label>
            <div class="control">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                </span>
                <input type="text" id="username" name="username" value="<?= esc(old('username')) ?>" placeholder="alexm" required>
            </div>
        </div>
        <div>
            <label class="field" for="email">Email</label>
            <div class="control">
                <span class="icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                </span>
                <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@company.com" required>
            </div>
        </div>
    </div>

    <label class="field" for="password">Password</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        </span>
        <input type="password" id="password" name="password" minlength="10" required data-strength="#strength-bar">
        <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.6"/></svg>
        </button>
    </div>
    <div class="strength"><span id="strength-bar"></span></div>

    <label class="field" for="password_confirm">Confirm password</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 6 9 17l-5-5"/></svg>
        </span>
        <input type="password" id="password_confirm" name="password_confirm" minlength="10" required>
    </div>

    <?php if (! empty($captcha)): ?>
        <div class="captcha"><?= $captcha ?></div>
    <?php endif; ?>

    <button type="submit" class="btn">Create account <span aria-hidden="true">→</span></button>
</form>

<?php if (! empty($socialProviders)): ?>
    <div class="social-login">
        <span class="social-login__label">or sign up with</span>
        <div class="social-login__row">
            <?php foreach ($socialProviders as $provider): ?>
                <a class="social-login__btn" href="<?= esc($provider['url'], 'attr') ?>" rel="nofollow">
                    <i class="<?= esc($provider['icon'], 'attr') ?>" aria-hidden="true"></i>
                    <span><?= esc($provider['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="foot">Already have an account? <a href="<?= site_url('login') ?>">Sign in</a></div>
<?= $this->endSection() ?>
