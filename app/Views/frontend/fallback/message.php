<?php
$heading = $heading ?? 'Something happened';
$message = $message ?? '';
$icon    = $icon ?? 'fa-solid fa-circle-info';
?>
<div class="dst-container dst-message">
    <div class="dst-message__card" data-reveal>
        <span class="dst-message__icon"><i class="<?= esc($icon) ?>" aria-hidden="true"></i></span>
        <h1 class="dst-display dst-display--sm"><?= esc($heading) ?></h1>
        <p class="dst-lede"><?= esc($message) ?></p>
        <div class="dst-message__actions">
            <a class="dst-btn dst-btn--primary" href="<?= esc(loc_url('', $locale)) ?>"><?= esc(ui('back_home', 'Back home')) ?></a>
            <a class="dst-btn dst-btn--ghost" href="<?= esc(search_url('', $locale)) ?>"><?= esc(ui('search', 'Search')) ?></a>
        </div>
    </div>
</div>
