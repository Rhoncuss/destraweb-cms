<?= $this->extend('auth/layout') ?>

<?= $this->section('form') ?>
<h1>Welcome back</h1>
<p class="sub">Sign in to manage your content, media and settings.</p>

<?php if (! empty($error)): ?>
    <div class="alert alert-error"><span>!</span><span><?= esc($error) ?></span></div>
<?php endif; ?>
<?php if (! empty($success)): ?>
    <div class="alert alert-success"><span>✓</span><span><?= esc($success) ?></span></div>
<?php endif; ?>

<form method="post" action="<?= site_url('login') ?>" autocomplete="on" novalidate>
    <?= csrf_field() ?>

    <label class="field" for="email">Email address</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
        </span>
        <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@company.com" required autofocus>
    </div>

    <label class="field" for="password">Password</label>
    <div class="control">
        <span class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        </span>
        <input type="password" id="password" name="password" placeholder="••••••••••" required>
        <button type="button" class="toggle" data-toggle-password="password" aria-label="Show password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.6"/></svg>
        </button>
    </div>

    <div class="row">
        <label class="check"><input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>> Remember this device</label>
        <a class="link" href="<?= site_url('forgot-password') ?>">Forgot password?</a>
    </div>

    <?php if (! empty($captcha)): ?>
        <div class="captcha"><?= $captcha ?></div>
    <?php endif; ?>

    <button type="submit" class="btn">Sign in securely <span aria-hidden="true">→</span></button>
</form>

<?php if (! empty($socialProviders)): ?>
    <div class="social-login">
        <span class="social-login__label">or continue with</span>
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

<?php if (service('settings')->bool('general.registration_enabled')): ?>
    <div class="foot">New here? <a href="<?= site_url('register') ?>">Create an account</a></div>
<?php endif; ?>
<?= $this->endSection() ?>
