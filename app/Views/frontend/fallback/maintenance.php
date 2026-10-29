<?php
$message  = $message ?? 'We are making things better. Please check back soon.';
$siteName = $siteName ?? 'DESTRA CMS';
$logoId   = (int) ($logoId ?? 0);
$logoUrl  = $logoId > 0 ? media_url($logoId) : '';
?>
<!doctype html>
<html lang="<?= esc(current_locale()) ?>" dir="<?= is_rtl() ? 'rtl' : 'ltr' ?>" data-theme="<?= esc($themeSlug ?? 'modern') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="<?= esc((string) ($themeVars['primary'] ?? '#4f46e5')) ?>">
    <title><?= esc($siteName) ?> — <?= esc(ui('maintenance', 'Maintenance')) ?></title>
    <link rel="stylesheet" href="<?= base_url('vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= front_asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= theme_asset('assets/theme.css') ?>">
</head>
<body class="dst-body dst-body--maintenance theme-<?= esc($themeSlug ?? 'modern') ?>">
    <main class="dst-maintenance">
        <div class="dst-maintenance__card">
            <span class="dst-maintenance__icon"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></span>
            <?php if ($logoUrl !== ''): ?>
                <img class="dst-maintenance__logo" src="<?= esc($logoUrl) ?>" alt="<?= esc($siteName) ?>">
            <?php else: ?>
                <span class="dst-brand__mark dst-brand__mark--lg" aria-hidden="true"><?= esc(mb_substr($siteName, 0, 1)) ?></span>
            <?php endif; ?>
            <h1><?= esc($siteName) ?></h1>
            <p><?= esc($message) ?></p>
            <span class="dst-maintenance__badge"><?= esc(ui('we_are_back_soon', 'Back soon')) ?></span>
        </div>
    </main>
</body>
</html>
