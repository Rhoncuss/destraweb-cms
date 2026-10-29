<?php
/**
 * Schema driven public form.
 *
 * @var array<string, mixed> $form
 */
$fields = $form['fields'] ?? [];
$uid    = 'f' . (int) $form['id'];
?>
<form class="dst-form" data-ajax-form action="<?= esc(site_url('forms/' . (int) $form['id'])) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="locale" value="<?= esc(current_locale()) ?>">
    <input type="text" name="website" class="dst-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

    <div class="dst-form__grid">
        <?php foreach ($fields as $field): ?>
            <?php
            $key         = (string) ($field['key'] ?? $field['name'] ?? '');
            $label       = (string) ($field['label'] ?? $key);
            $type        = (string) ($field['type'] ?? 'text');
            $required    = ! empty($field['required']);
            $placeholder = (string) ($field['placeholder'] ?? '');
            $options     = (array) ($field['options'] ?? []);
            $width       = ($field['width'] ?? 'full') === 'half' ? 'half' : 'full';
            $id          = $uid . '-' . preg_replace('/[^a-z0-9_-]/i', '', $key);
            $inputName   = 'fields[' . $key . ']';
            ?>
            <div class="dst-field dst-field--<?= esc($width) ?>">
                <?php if ($type === 'checkbox'): ?>
                    <span class="dst-field__legend"><?= esc($label) ?><?= $required ? ' *' : '' ?></span>
                    <div class="dst-field__choices">
                        <?php foreach ($options as $option): ?>
                            <label class="dst-check">
                                <input type="checkbox" name="<?= esc($inputName) ?>[]" value="<?= esc((string) $option) ?>">
                                <span><?= esc((string) $option) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($type === 'radio'): ?>
                    <span class="dst-field__legend"><?= esc($label) ?><?= $required ? ' *' : '' ?></span>
                    <div class="dst-field__choices">
                        <?php foreach ($options as $option): ?>
                            <label class="dst-check">
                                <input type="radio" name="<?= esc($inputName) ?>" value="<?= esc((string) $option) ?>" <?= $required ? 'required' : '' ?>>
                                <span><?= esc((string) $option) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($type === 'select'): ?>
                    <label for="<?= esc($id) ?>"><?= esc($label) ?><?= $required ? ' *' : '' ?></label>
                    <select id="<?= esc($id) ?>" name="<?= esc($inputName) ?>" <?= $required ? 'required' : '' ?>>
                        <option value=""><?= esc(ui('choose', 'Choose…')) ?></option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= esc((string) $option) ?>"><?= esc((string) $option) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'textarea'): ?>
                    <label for="<?= esc($id) ?>"><?= esc($label) ?><?= $required ? ' *' : '' ?></label>
                    <textarea id="<?= esc($id) ?>" name="<?= esc($inputName) ?>" rows="5" maxlength="5000"
                              placeholder="<?= esc($placeholder) ?>" <?= $required ? 'required' : '' ?>></textarea>
                <?php else: ?>
                    <label for="<?= esc($id) ?>"><?= esc($label) ?><?= $required ? ' *' : '' ?></label>
                    <?php
                    $inputType = in_array($type, ['email', 'tel', 'date', 'number', 'url'], true) ? $type : 'text';
                    ?>
                    <input id="<?= esc($id) ?>" type="<?= esc($inputType) ?>"
                           name="<?= esc($inputName) ?>" placeholder="<?= esc($placeholder) ?>"
                           maxlength="1000" <?= $required ? 'required' : '' ?>
                           <?= $inputType === 'email' ? 'autocomplete="email"' : '' ?>>
                <?php endif; ?>
                <small class="dst-field__error" data-error-for="<?= esc($key) ?>"></small>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ((int) ($form['captcha'] ?? 0) === 1): ?>
        <?= service('captcha')->widget('forms') ?>
    <?php endif; ?>

    <div class="dst-form__actions">
        <button type="submit" class="dst-btn dst-btn--primary dst-btn--lg">
            <span><?= esc(ui('send_message', 'Send message')) ?></span>
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        </button>
        <p class="dst-form__msg" data-form-msg role="status" aria-live="polite"></p>
    </div>
</form>
