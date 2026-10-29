<?php
/** Fallback used when a theme references a view that does not exist. */
$heading = $heading ?? $pageTitle ?? '';
?>
<div class="dst-container dst-generic">
    <?php if ($heading !== ''): ?>
        <h1 class="dst-display dst-display--sm"><?= esc((string) $heading) ?></h1>
    <?php endif; ?>
    <p class="dst-lede"><?= esc(ui('content_unavailable', 'This section is being prepared. Please check back shortly.')) ?></p>
    <a class="dst-btn dst-btn--primary" href="<?= esc(loc_url('', current_locale())) ?>"><?= esc(ui('back_home', 'Back home')) ?></a>
</div>
