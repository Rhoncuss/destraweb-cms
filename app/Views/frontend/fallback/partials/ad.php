<?php
/** @var array<string, mixed> $ad */
$type = (string) ($ad['type'] ?? 'image');
$href = (string) ($ad['href'] ?? '');
$target = ($ad['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener noreferrer sponsored"' : ' rel="sponsored"';
?>
<?php if ($type === 'html' && ! empty($ad['html'])): ?>
    <div class="dst-ad__html"><?= service('sanitizer')->html((string) $ad['html']) ?></div>
<?php elseif (! empty($ad['image_url'])): ?>
    <a class="dst-ad__card" href="<?= esc($href !== '' ? $href : '#') ?>"<?= $target ?>>
        <span class="dst-ad__label"><?= esc(ui('sponsored', 'Sponsored')) ?></span>
        <img src="<?= esc((string) $ad['image_url']) ?>"
             alt="<?= esc((string) ($ad['alt'] ?? $ad['title'] ?? $ad['name'] ?? 'Advertisement')) ?>"
             loading="lazy" decoding="async">
        <?php if (! empty($ad['title'])): ?>
            <span class="dst-ad__title"><?= esc((string) $ad['title']) ?></span>
        <?php endif; ?>
        <?php if (! empty($ad['button_label'])): ?>
            <span class="dst-ad__cta"><?= esc((string) $ad['button_label']) ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>
