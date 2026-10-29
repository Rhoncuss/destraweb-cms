<?php
/** @var list<array<string, mixed>> $popups */
foreach ($popups as $popup):
    $image = (string) ($popup['image_url'] ?? '');
    ?>
    <div class="dst-popup" data-popup
         data-popup-id="<?= (int) $popup['id'] ?>"
         data-trigger="<?= esc((string) ($popup['trigger_type'] ?? 'delay')) ?>"
         data-value="<?= (int) ($popup['trigger_value'] ?? 3) ?>"
         data-frequency="<?= esc((string) ($popup['frequency'] ?? 'session')) ?>"
         aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?= esc((string) ($popup['title'] ?: $popup['name'])) ?>">
        <div class="dst-popup__backdrop" data-popup-close></div>
        <div class="dst-popup__card">
            <button type="button" class="dst-popup__close" data-popup-close aria-label="<?= esc(ui('close', 'Close')) ?>">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <?php if ($image !== ''): ?>
                <div class="dst-popup__media"><img src="<?= esc($image) ?>" alt="" loading="lazy"></div>
            <?php endif; ?>
            <div class="dst-popup__body">
                <?php if (! empty($popup['title'])): ?>
                    <h2 class="dst-popup__title"><?= esc((string) $popup['title']) ?></h2>
                <?php endif; ?>
                <?php if (! empty($popup['content'])): ?>
                    <div class="dst-popup__content"><?= service('sanitizer')->html((string) $popup['content']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
