<?php
$heading = $heading ?? 'We could not find that page';
$message = $message ?? 'The link may be broken or the page may have moved.';
?>
<div class="dst-container dst-error">
    <div class="dst-error__card" data-reveal>
        <span class="dst-error__code">404</span>
        <h1 class="dst-display dst-display--sm"><?= esc($heading) ?></h1>
        <p class="dst-lede"><?= esc($message) ?></p>

        <form class="dst-error__search" method="get" action="<?= esc(search_url('', $locale)) ?>" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" maxlength="120" placeholder="<?= esc(ui('search_placeholder', 'Search the site…')) ?>" aria-label="<?= esc(ui('search', 'Search')) ?>">
            <button class="dst-btn dst-btn--primary" type="submit"><?= esc(ui('search', 'Search')) ?></button>
        </form>

        <div class="dst-error__actions">
            <a class="dst-btn dst-btn--ghost" href="<?= esc(loc_url('', $locale)) ?>"><?= esc(ui('back_home', 'Back home')) ?></a>
        </div>
    </div>
</div>
