<?php
/** @var list<array{network:string,label:string,icon:string,url:string}> $share */
$share = $share ?? [];
if ($share === []) {
    return;
}
?>
<div class="dst-share">
    <span class="dst-share__label"><?= esc(ui('share', 'Share')) ?></span>
    <?php foreach ($share as $link): ?>
        <a class="dst-share__item" href="<?= esc((string) $link['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"
           aria-label="<?= esc('Share on ' . (string) $link['label']) ?>" data-share="<?= esc((string) $link['network']) ?>">
            <?= icon_html((string) $link['icon']) ?>
        </a>
    <?php endforeach; ?>
    <button type="button" class="dst-share__item dst-share__copy" data-copy-link aria-label="<?= esc(ui('copy_link', 'Copy link')) ?>">
        <i class="fa-solid fa-link" aria-hidden="true"></i>
    </button>
</div>
