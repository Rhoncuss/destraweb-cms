<?php
/**
 * Public layout shared by every theme unless a theme ships its own layout.
 *
 * @var string $body
 * @var string $seo
 * @var string $siteName
 * @var string $tagline
 * @var string $locale
 * @var array<string, mixed> $themeVars
 * @var list<array<string, mixed>> $primaryMenu
 * @var list<array<string, mixed>> $footerMenu
 * @var array<string, string> $social
 * @var list<array<string, mixed>> $languages
 * @var list<array<string, mixed>> $popups
 */
$themeVars   = $themeVars ?? ['primary' => '#4f46e5', 'secondary' => '#0ea5e9', 'accent' => '#f59e0b', 'radius' => 18];
$primaryMenu = $primaryMenu ?? [];
$footerMenu  = $footerMenu ?? [];
$social      = $social ?? [];
$languages   = $languages ?? [];
$popups      = $popups ?? [];
$bodyClass   = $bodyClass ?? '';
$logoId      = (int) ($logoId ?? 0);
$faviconId   = (int) ($faviconId ?? 0);
$currentCode = $locale ?? current_locale();
$currentLang = $language ?? current_language();

$fontFamilies = [
    'plus-jakarta' => 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
    'inter'        => 'Inter:wght@400;500;600;700;800',
    'manrope'      => 'Manrope:wght@400;500;600;700;800',
];
$fontKey = (string) setting('appearance.font', 'plus-jakarta');
$fontUrl = isset($fontFamilies[$fontKey])
    ? 'https://fonts.googleapis.com/css2?family=' . $fontFamilies[$fontKey] . '&display=swap'
    : '';

$navSections = $primaryMenu === [] ? front_sections() : [];
$footerPages = $footerMenu === [] ? front_pages(6) : [];
$logoUrl     = $logoId > 0 ? media_url($logoId) : '';
$faviconUrl  = $faviconId > 0 ? media_url($faviconId, 'thumb') : '';
?>
<!doctype html>
<html class="no-js" lang="<?= esc($currentCode) ?>" dir="<?= ! empty($isRtl) ? 'rtl' : 'ltr' ?>" data-theme="<?= esc($themeSlug ?? 'modern') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= esc((string) $themeVars['primary']) ?>">
    <?= destra_csrf_meta() ?>
    <?= $seo ?? '' ?>

    <?php if ($faviconUrl !== ''): ?>
        <link rel="icon" href="<?= esc($faviconUrl) ?>">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($fontUrl !== ''): ?>
        <link rel="stylesheet" href="<?= esc($fontUrl) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/swiper/swiper-bundle.min.css') ?>">
    <link rel="stylesheet" href="<?= front_asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= theme_asset('assets/theme.css') ?>">
    <?php if (setting_bool('seo.rss_enabled', true)): ?>
        <link rel="alternate" type="application/rss+xml" title="<?= esc($siteName) ?> RSS" href="<?= esc(loc_url('feed', $currentCode)) ?>">
    <?php endif; ?>

    <style>
        :root {
            --dst-primary: <?= esc((string) $themeVars['primary']) ?>;
            --dst-secondary: <?= esc((string) $themeVars['secondary']) ?>;
            --dst-accent: <?= esc((string) $themeVars['accent']) ?>;
            --dst-radius: <?= (int) $themeVars['radius'] ?>px;
            --dst-font: <?= $fontStack ?? "'Plus Jakarta Sans', system-ui, sans-serif" ?>;
        }
    </style>

    <?php if (! empty($customCss)): ?>
        <style id="dst-custom-css"><?= $customCss ?></style>
    <?php endif; ?>

    <?php if (! empty($gtmId)): ?>
        <script nonce="<?= esc(csp_nonce()) ?>">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;j.setAttribute('nonce','<?= esc(csp_nonce()) ?>');f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= esc($gtmId, 'js') ?>');</script>
    <?php elseif (! empty($analyticsId)): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($analyticsId) ?>" nonce="<?= esc(csp_nonce()) ?>"></script>
        <script nonce="<?= esc(csp_nonce()) ?>">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= esc($analyticsId, 'js') ?>');</script>
    <?php endif; ?>

    <?= $customHead ?? '' ?>
    <?= \App\Libraries\Hook::apply('frontend.head_end', '') ?>
</head>
<body class="dst-body theme-<?= esc($themeSlug ?? 'modern') ?> <?= esc($bodyClass) ?>" data-locale="<?= esc($currentCode) ?>" data-base="<?= esc(base_url()) ?>">

<?php if (! empty($preloader)): ?>
    <div class="dst-preloader" data-preloader aria-hidden="true">
        <span class="dst-preloader__ring"></span>
        <span class="dst-preloader__brand"><?= esc($siteName) ?></span>
    </div>
<?php endif; ?>

<?php if (! empty($gtmId)): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc($gtmId) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<a class="dst-skip" href="#main"><?= esc(ui('skip_to_content', 'Skip to content')) ?></a>

<div class="dst-progress" data-reading-progress aria-hidden="true"><span></span></div>

<header class="dst-header" data-header>
    <div class="dst-container dst-header__inner">
        <a class="dst-brand" href="<?= esc(loc_url('', $currentCode)) ?>" aria-label="<?= esc($siteName) ?>">
            <?php if ($logoUrl !== ''): ?>
                <img src="<?= esc($logoUrl) ?>" alt="<?= esc($siteName) ?>" class="dst-brand__logo">
            <?php else: ?>
                <span class="dst-brand__mark" aria-hidden="true"><?= esc(mb_substr($siteName, 0, 1)) ?></span>
                <span class="dst-brand__text">
                    <strong><?= esc($siteName) ?></strong>
                    <?php if (! empty($tagline)): ?><small dir="auto"><?= esc($tagline) ?></small><?php endif; ?>
                </span>
            <?php endif; ?>
        </a>

        <nav class="dst-nav" aria-label="<?= esc(ui('primary_navigation', 'Primary navigation')) ?>">
            <?php if ($primaryMenu !== []): ?>
                <?= render_menu('primary', 'dst-menu') ?>
            <?php else: ?>
                <ul class="dst-menu">
                    <li><a href="<?= esc(loc_url('', $currentCode)) ?>"><span><?= esc(ui('home', 'Home')) ?></span></a></li>
                    <?php foreach ($navSections as $section): ?>
                        <li><a href="<?= esc((string) $section['url']) ?>"><span><?= esc((string) $section['name']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </nav>

        <div class="dst-header__actions">
            <?php if (! empty($searchEnabled)): ?>
                <button type="button" class="dst-icon-btn" data-search-open aria-label="<?= esc(ui('search', 'Search')) ?>">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            <?php endif; ?>

            <?php if (! empty($langSwitcher) && count($languages) > 1): ?>
                <details class="dst-lang">
                    <summary aria-label="<?= esc(ui('language', 'Language')) ?>">
                        <span class="dst-lang__code"><?= esc(strtoupper($currentCode)) ?></span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </summary>
                    <ul class="dst-lang__list">
                        <?php foreach ($languages as $languageItem): ?>
                            <li>
                                <a href="<?= esc(switch_language_url((string) $languageItem['code'])) ?>"
                                   class="<?= $languageItem['code'] === $currentCode ? 'is-active' : '' ?>"
                                   hreflang="<?= esc((string) $languageItem['code']) ?>">
                                    <span><?= esc((string) ($languageItem['native_name'] ?: $languageItem['name'])) ?></span>
                                    <?php if ($languageItem['code'] === $currentCode): ?><i class="fa-solid fa-check" aria-hidden="true"></i><?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>

            <?php if (! empty($newsletterEnabled)): ?>
                <a class="dst-btn dst-btn--primary dst-header__cta" href="#newsletter"><?= esc(ui('subscribe', 'Subscribe')) ?></a>
            <?php endif; ?>

            <?php if (auth_check()): ?>
                <a class="dst-icon-btn dst-header__account" href="<?= esc(admin_url('')) ?>" aria-label="<?= esc(ui('dashboard', 'Dashboard')) ?>">
                    <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <a class="dst-icon-btn dst-header__account" href="<?= esc(site_url('login')) ?>" aria-label="<?= esc(ui('sign_in', 'Sign in')) ?>">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                </a>
            <?php endif; ?>

            <button type="button" class="dst-burger" data-menu-open aria-label="<?= esc(ui('open_menu', 'Open menu')) ?>" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <?php if (! empty($headerBanner)): ?>
        <div class="dst-container dst-ad dst-ad--header">
            <?php foreach ($headerBanner as $ad): ?>
                <?= view('frontend/fallback/partials/ad', ['ad' => $ad]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>

<div class="dst-search" data-search-panel aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?= esc(ui('search', 'Search')) ?>">
    <div class="dst-search__inner">
        <button type="button" class="dst-search__close" data-search-close aria-label="<?= esc(ui('close', 'Close')) ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        <p class="dst-search__hint"><?= esc(ui('search_hint', 'Type to search stories, docs and pages')) ?></p>
        <form method="get" action="<?= esc(search_url('', $currentCode)) ?>" class="dst-search__form" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" data-search-input autocomplete="off" placeholder="<?= esc(ui('search_placeholder', 'Search the site…')) ?>" aria-label="<?= esc(ui('search', 'Search')) ?>" maxlength="120">
            <button type="submit" class="dst-btn dst-btn--primary"><?= esc(ui('search', 'Search')) ?></button>
        </form>
    </div>
</div>

<aside class="dst-drawer" data-drawer aria-hidden="true" aria-label="<?= esc(ui('menu', 'Menu')) ?>">
    <div class="dst-drawer__head">
        <span class="dst-drawer__title"><?= esc(ui('menu', 'Menu')) ?></span>
        <button type="button" class="dst-icon-btn" data-menu-close aria-label="<?= esc(ui('close', 'Close')) ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>

    <?php if (! empty($searchEnabled)): ?>
        <form method="get" action="<?= esc(search_url('', $currentCode)) ?>" class="dst-drawer__search" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" placeholder="<?= esc(ui('search_placeholder', 'Search the site…')) ?>" aria-label="<?= esc(ui('search', 'Search')) ?>" maxlength="120">
        </form>
    <?php endif; ?>

    <nav class="dst-drawer__nav" aria-label="<?= esc(ui('mobile_navigation', 'Mobile navigation')) ?>">
        <ul>
            <li><a href="<?= esc(loc_url('', $currentCode)) ?>"><i class="fa-solid fa-house" aria-hidden="true"></i><span><?= esc(ui('home', 'Home')) ?></span></a></li>
            <?php foreach ($navSections as $section): ?>
                <li><a href="<?= esc((string) $section['url']) ?>">
                    <?= icon_html((string) ($section['icon'] ?? ''), 'fa-solid fa-folder') ?>
                    <span><?= esc((string) $section['name']) ?></span>
                </a></li>
            <?php endforeach; ?>
            <?php foreach ($footerPages as $page): ?>
                <li><a href="<?= esc((string) $page['url']) ?>"><i class="fa-regular fa-file-lines" aria-hidden="true"></i><span><?= esc((string) $page['title']) ?></span></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if (count($languages) > 1): ?>
        <div class="dst-drawer__section">
            <span class="dst-drawer__label"><?= esc(ui('language', 'Language')) ?></span>
            <div class="dst-drawer__langs">
                <?php foreach ($languages as $languageItem): ?>
                    <a href="<?= esc(switch_language_url((string) $languageItem['code'])) ?>"
                       class="dst-chip <?= $languageItem['code'] === $currentCode ? 'is-active' : '' ?>"
                       hreflang="<?= esc((string) $languageItem['code']) ?>">
                        <?= esc((string) ($languageItem['native_name'] ?: $languageItem['name'])) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="dst-drawer__section dst-drawer__cta">
        <?php if (! empty($newsletterEnabled)): ?>
            <a class="dst-btn dst-btn--primary dst-btn--block" href="#newsletter" data-menu-close><?= esc(ui('subscribe', 'Subscribe')) ?></a>
        <?php endif; ?>
        <?php if (auth_check()): ?>
            <a class="dst-btn dst-btn--ghost dst-btn--block" href="<?= esc(admin_url('')) ?>"><?= esc(ui('dashboard', 'Dashboard')) ?></a>
        <?php else: ?>
            <a class="dst-btn dst-btn--ghost dst-btn--block" href="<?= esc(site_url('login')) ?>"><?= esc(ui('sign_in', 'Sign in')) ?></a>
        <?php endif; ?>
    </div>
</aside>
<div class="dst-backdrop" data-backdrop hidden></div>

<main id="main" class="dst-main">
    <?= $body ?>
</main>

<footer class="dst-footer">
    <div class="dst-container dst-footer__grid">
        <div class="dst-footer__brand">
            <a class="dst-brand dst-brand--footer" href="<?= esc(loc_url('', $currentCode)) ?>">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= esc($logoUrl) ?>" alt="<?= esc($siteName) ?>" class="dst-brand__logo">
                <?php else: ?>
                    <span class="dst-brand__mark" aria-hidden="true"><?= esc(mb_substr($siteName, 0, 1)) ?></span>
                    <span class="dst-brand__text"><strong><?= esc($siteName) ?></strong></span>
                <?php endif; ?>
            </a>
            <p class="dst-footer__tagline" dir="auto"><?= esc((string) ($footerText !== '' ? $footerText : $tagline)) ?></p>

            <?php if ($social !== []): ?>
                <ul class="dst-social" aria-label="<?= esc(ui('social_links', 'Social links')) ?>">
                    <?php foreach ($social as $network => $url): ?>
                        <li>
                            <a href="<?= esc($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= esc(ucfirst($network)) ?>">
                                <?= icon_html('fa-brands fa-' . ($network === 'x' ? 'x-twitter' : $network), 'fa-solid fa-link') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dst-footer__col">
            <h3><?= esc(ui('explore', 'Explore')) ?></h3>
            <ul>
                <?php foreach ($navSections as $section): ?>
                    <li><a href="<?= esc((string) $section['url']) ?>"><?= esc((string) $section['name']) ?></a></li>
                <?php endforeach; ?>
                <?php if ($navSections === []): ?>
                    <li><a href="<?= esc(loc_url('', $currentCode)) ?>"><?= esc(ui('home', 'Home')) ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dst-footer__col">
            <h3><?= esc(ui('company', 'Company')) ?></h3>
            <?php if ($footerMenu !== []): ?>
                <?= render_menu('footer', 'dst-footer__menu') ?>
            <?php else: ?>
                <ul>
                    <?php foreach ($footerPages as $page): ?>
                        <li><a href="<?= esc((string) $page['url']) ?>"><?= esc((string) $page['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dst-footer__col dst-footer__newsletter" id="newsletter">
            <h3><?= esc(ui('newsletter', 'Newsletter')) ?></h3>
            <p><?= esc($newsletterMessage !== '' ? $newsletterMessage : ui('newsletter_hint', 'One thoughtful email each week. No noise, ever.')) ?></p>
            <?php if (! empty($newsletterEnabled)): ?>
                <?= view('frontend/fallback/partials/newsletter', ['variant' => 'footer']) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (! empty($footerBanner)): ?>
        <div class="dst-container dst-ad dst-ad--footer">
            <?php foreach ($footerBanner as $ad): ?>
                <?= view('frontend/fallback/partials/ad', ['ad' => $ad]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="dst-container dst-footer__bottom">
        <p><?= esc($copyright ?? '') ?></p>
        <div class="dst-footer__meta">
            <?php if (! empty($visitorCounter)): ?>
                <span class="dst-footer__visits"><i class="fa-regular fa-eye" aria-hidden="true"></i> <?= esc(number_format((int) ($visitorCount ?? 0))) ?> <?= esc(ui('visits', 'visits')) ?></span>
            <?php endif; ?>
            <span><?= esc(ui('powered_by', 'Crafted with')) ?> <a href="https://codeigniter.com" target="_blank" rel="noopener noreferrer">DESTRA CMS</a></span>
        </div>
    </div>
</footer>

<nav class="dst-mobilebar" aria-label="<?= esc(ui('mobile_navigation', 'Mobile navigation')) ?>">
    <a href="<?= esc(loc_url('', $currentCode)) ?>" class="<?= ($bodyClass ?? '') === 'home' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-house" aria-hidden="true"></i><span><?= esc(ui('home', 'Home')) ?></span>
    </a>
    <button type="button" data-menu-open>
        <i class="fa-solid fa-compass" aria-hidden="true"></i><span><?= esc(ui('explore', 'Explore')) ?></span>
    </button>
    <?php if (! empty($searchEnabled)): ?>
        <button type="button" data-search-open>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span><?= esc(ui('search', 'Search')) ?></span>
        </button>
    <?php endif; ?>
    <a href="<?= esc(auth_check() ? admin_url('') : site_url('login')) ?>">
        <i class="fa-regular fa-user" aria-hidden="true"></i><span><?= esc(auth_check() ? ui('dashboard', 'Dashboard') : ui('account', 'Account')) ?></span>
    </a>
</nav>

<button type="button" class="dst-totop" data-totop aria-label="<?= esc(ui('back_to_top', 'Back to top')) ?>">
    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
</button>

<?php if ($popups !== []): ?>
    <?= view('frontend/fallback/partials/popups', ['popups' => $popups]) ?>
<?php endif; ?>

<script src="<?= base_url('vendor/swiper/swiper-bundle.min.js') ?>" nonce="<?= esc(csp_nonce()) ?>"></script>
<script src="<?= front_asset('js/app.js') ?>" nonce="<?= esc(csp_nonce()) ?>"></script>

<?= $customBody ?? '' ?>
<?= $customJs ?? '' ?>
<?= \App\Libraries\Hook::apply('frontend.body_end', '') ?>
</body>
</html>
