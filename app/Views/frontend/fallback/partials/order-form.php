<?php
/**
 * @var array<string, mixed> $content
 */
$price = (string) (($content['custom_fields']['price'] ?? '') ?: '');
$options = $content['custom_fields']['options'] ?? [];
if (is_string($options)) {
    $options = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $options) ?: [])));
}
$options = is_array($options) ? $options : [];
?>
<form class="dst-form dst-order-form" data-ajax-form action="<?= esc(site_url('orders/' . (int) $content['id'])) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="locale" value="<?= esc(current_locale()) ?>">
    <input type="text" name="website" class="dst-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

    <div class="dst-order-form__head">
        <span class="dst-kicker"><?= esc(ui('order_request', 'Order request')) ?></span>
        <?php if ($price !== ''): ?>
            <span class="dst-order-form__price"><?= esc($price) ?></span>
        <?php endif; ?>
    </div>

    <div class="dst-form__grid">
        <div class="dst-field dst-field--half">
            <label for="order-name"><?= esc(ui('name', 'Name')) ?> *</label>
            <input id="order-name" type="text" name="name" required maxlength="120" autocomplete="name">
        </div>
        <div class="dst-field dst-field--half">
            <label for="order-email"><?= esc(ui('email', 'Email')) ?> *</label>
            <input id="order-email" type="email" name="email" required maxlength="190" autocomplete="email">
        </div>
        <div class="dst-field dst-field--half">
            <label for="order-phone"><?= esc(ui('phone', 'Phone')) ?></label>
            <input id="order-phone" type="tel" name="phone" maxlength="50" autocomplete="tel">
        </div>
        <div class="dst-field dst-field--half">
            <label for="order-qty"><?= esc(ui('quantity', 'Quantity')) ?></label>
            <input id="order-qty" type="number" name="quantity" min="1" max="999" value="1">
        </div>
        <?php if ($options !== []): ?>
            <div class="dst-field dst-field--full">
                <label for="order-option"><?= esc(ui('options', 'Option')) ?></label>
                <select id="order-option" name="option">
                    <option value=""><?= esc(ui('choose', 'Choose…')) ?></option>
                    <?php foreach ($options as $option): ?>
                        <option value="<?= esc((string) $option) ?>"><?= esc((string) $option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="dst-field dst-field--full">
            <label for="order-message"><?= esc(ui('notes', 'Notes')) ?></label>
            <textarea id="order-message" name="message" rows="4" maxlength="2000"
                      placeholder="<?= esc(ui('order_notes_placeholder', 'Anything we should know?')) ?>"></textarea>
        </div>
    </div>

    <div class="dst-form__actions">
        <button type="submit" class="dst-btn dst-btn--primary dst-btn--lg">
            <span><?= esc(ui('send_request', 'Send request')) ?></span>
            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
        </button>
        <p class="dst-form__msg" data-form-msg role="status" aria-live="polite"></p>
    </div>
</form>
