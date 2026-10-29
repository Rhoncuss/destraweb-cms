<?php
/** @var string $variant */
$variant = $variant ?? 'footer';
$captcha = service('captcha')->widget('newsletter');
?>
<form class="dst-newsletter dst-newsletter--<?= esc($variant) ?>" data-newsletter novalidate>
    <input type="hidden" name="locale" value="<?= esc(current_locale()) ?>">
    <div class="dst-newsletter__row">
        <label class="dst-sr" for="nlemail-<?= esc($variant) ?>"><?= esc(ui('email', 'Email address')) ?></label>
        <input id="nlemail-<?= esc($variant) ?>" type="email" name="email" required autocomplete="email"
               placeholder="<?= esc(ui('email_placeholder', 'you@example.com')) ?>" maxlength="190">
        <button type="submit" class="dst-btn dst-btn--primary">
            <span><?= esc(ui('subscribe', 'Subscribe')) ?></span>
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        </button>
    </div>
    <label class="dst-check">
        <input type="checkbox" name="privacy" value="1" required>
        <span><?= esc(ui('newsletter_privacy', 'I agree to receive emails and accept the privacy policy.')) ?></span>
    </label>
    <?= $captcha ?>
    <input type="text" name="website" class="dst-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <p class="dst-newsletter__msg" data-newsletter-msg role="status" aria-live="polite"></p>
</form>
