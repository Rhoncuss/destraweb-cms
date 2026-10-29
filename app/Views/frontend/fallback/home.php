<?php
/**
 * @var list<array<string, mixed>> $latest
 * @var list<array<string, mixed>> $sticky
 * @var list<array<string, mixed>> $popular
 * @var list<array<string, mixed>> $blocks
 * @var list<array<string, mixed>> $categories
 * @var list<array<string, mixed>> $tags
 * @var list<array<string, mixed>> $slider
 */
$latest    = $latest ?? [];
$sticky    = $sticky ?? [];
$popular   = $popular ?? [];
$blocks    = $blocks ?? [];
$categories = $categories ?? [];
$tags      = $tags ?? [];
$slider    = $slider ?? [];

$featured  = $sticky[0] ?? ($latest[0] ?? null);
$rail      = array_slice($latest, $featured !== null && ($latest[0]['id'] ?? 0) === ($featured['id'] ?? 0) ? 1 : 0, 3);
$intro     = (string) setting('general.description', '');

$sections = front_sections();
$primarySection = null;
foreach ($sections as $section) {
    if (($section['type'] ?? '') === 'post') {
        $primarySection = $section;
        break;
    }
}
$archiveUrl = $primarySection !== null ? (string) $primarySection['url'] : search_url('', $locale);
?>

<div class="dst-container dst-home">

    <?php if ($slider !== []): ?>
        <section class="dst-hero-slider swiper" data-slider aria-label="<?= esc(ui('featured', 'Featured')) ?>">
            <div class="swiper-wrapper">
                <?php foreach ($slider as $ad): ?>
                    <?php $target = ($ad['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener noreferrer sponsored"' : ' rel="sponsored"'; ?>
                    <div class="swiper-slide">
                        <a class="dst-hero-slide" href="<?= esc((string) ($ad['href'] ?? '#')) ?>"<?= $target ?>>
                            <?php if (! empty($ad['image_url'])): ?>
                                <img src="<?= esc((string) $ad['image_url']) ?>" alt="<?= esc((string) ($ad['alt'] ?? $ad['title'] ?? '')) ?>" loading="eager" decoding="async">
                            <?php endif; ?>
                            <span class="dst-hero-slide__shade" aria-hidden="true"></span>
                            <span class="dst-hero-slide__content">
                                <span class="dst-kicker dst-kicker--light"><?= esc(ui('featured', 'Featured')) ?></span>
                                <?php if (! empty($ad['title'])): ?>
                                    <span class="dst-hero-slide__title"><?= esc((string) $ad['title']) ?></span>
                                <?php endif; ?>
                                <?php if (! empty($ad['button_label'])): ?>
                                    <span class="dst-btn dst-btn--light"><?= esc((string) $ad['button_label']) ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($slider) > 1): ?>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="dst-home-hero">
        <div class="dst-home-hero__intro" data-reveal>
            <span class="dst-kicker" dir="auto"><?= esc($tagline !== '' ? $tagline : ui('welcome', 'Welcome')) ?></span>
            <h1 class="dst-display" dir="auto"><?= esc($intro !== '' ? $intro : $siteName) ?></h1>
            <p class="dst-lede" dir="auto"><?= esc(ui('home_lede', 'Thoughtful stories, deep dives and documentation — designed to be read, not skimmed.')) ?></p>
            <div class="dst-home-hero__actions">
                <a class="dst-btn dst-btn--primary dst-btn--lg" href="<?= esc($archiveUrl) ?>">
                    <span><?= esc(ui('start_reading', 'Start reading')) ?></span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a class="dst-btn dst-btn--ghost dst-btn--lg" href="<?= esc(loc_url('docs', $locale)) ?>">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    <span><?= esc(ui('documentation', 'Documentation')) ?></span>
                </a>
            </div>
            <div class="dst-home-hero__facts">
                <span><i class="fa-solid fa-earth-americas" aria-hidden="true"></i> <?= count($languages ?? []) ?> <?= esc(ui('languages', 'languages')) ?></span>
                <span><i class="fa-solid fa-bolt" aria-hidden="true"></i> <?= esc(ui('fast_by_design', 'Fast by design')) ?></span>
                <?php if (! empty($visitorCounter)): ?>
                    <span><i class="fa-regular fa-eye" aria-hidden="true"></i> <?= esc(number_format((int) $visitorCount)) ?> <?= esc(ui('visits', 'visits')) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($featured !== null): ?>
            <div class="dst-home-hero__feature" data-reveal>
                <?= view('frontend/fallback/partials/card', ['item' => $featured, 'variant' => 'hero']) ?>
            </div>
        <?php endif; ?>

        <?php if ($rail !== []): ?>
            <div class="dst-home-hero__rail" data-reveal>
                <h2 class="dst-rail__title"><?= esc(ui('fresh_this_week', 'Fresh this week')) ?></h2>
                <?php foreach ($rail as $item): ?>
                    <a class="dst-rail-item" href="<?= esc((string) $item['url']) ?>">
                        <?php $image = (string) (($item['thumb_url'] ?? '') !== '' ? $item['thumb_url'] : ($item['image_url'] ?? '')); ?>
                        <?php if ($image !== ''): ?>
                            <img src="<?= esc($image) ?>" alt="" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <span class="dst-rail-item__body">
                            <span class="dst-kicker"><?= esc((string) ($item['primary_category']['name'] ?? $item['section_name'] ?? '')) ?></span>
                            <span class="dst-rail-item__title"><?= esc((string) $item['title']) ?></span>
                            <time datetime="<?= esc((string) ($item['published_at'] ?? '')) ?>"><?= esc(human_date((string) ($item['published_at'] ?? ''))) ?></time>
                        </span>
                    </a>
                <?php endforeach; ?>
                <a class="dst-rail__more" href="<?= esc($archiveUrl) ?>"><?= esc(ui('view_all', 'View all')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($sticky !== [] && count($sticky) > 1): ?>
        <section class="dst-section dst-section--tight">
            <header class="dst-section__head">
                <div>
                    <span class="dst-kicker"><?= esc(ui('editors_picks', "Editor's picks")) ?></span>
                    <h2 class="dst-section-title"><?= esc(ui('handpicked', 'Handpicked for you')) ?></h2>
                </div>
            </header>
            <div class="dst-grid dst-grid--3">
                <?php foreach (array_slice($sticky, 0, 3) as $item): ?>
                    <?= view('frontend/fallback/partials/card', ['item' => $item, 'variant' => 'grid']) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($latest !== []): ?>
        <section class="dst-section" id="latest">
            <header class="dst-section__head">
                <div>
                    <span class="dst-kicker"><?= esc(ui('latest', 'Latest')) ?></span>
                    <h2 class="dst-section-title"><?= esc(ui('latest_stories', 'Latest stories')) ?></h2>
                </div>
                <a class="dst-link-more" href="<?= esc($archiveUrl) ?>"><?= esc(ui('view_all', 'View all')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </header>
            <div class="dst-grid dst-grid--3">
                <?php foreach ($latest as $item): ?>
                    <?= view('frontend/fallback/partials/card', ['item' => $item, 'variant' => 'grid']) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php foreach ($blocks as $block): ?>
        <section class="dst-section dst-section--block">
            <header class="dst-section__head">
                <div class="dst-section__title-group">
                    <span class="dst-section__icon"><?= icon_html((string) ($block['section']['icon'] ?? ''), 'fa-solid fa-folder-open') ?></span>
                    <div>
                        <span class="dst-kicker"><?= esc(ui('section', 'Section')) ?></span>
                        <h2 class="dst-section-title"><?= esc((string) $block['section']['name']) ?></h2>
                    </div>
                </div>
                <a class="dst-link-more" href="<?= esc((string) $block['section']['url']) ?>"><?= esc(ui('view_all', 'View all')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </header>
            <div class="dst-grid dst-grid--4">
                <?php foreach ($block['items'] as $item): ?>
                    <?= view('frontend/fallback/partials/card', ['item' => $item, 'variant' => 'grid']) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="dst-band dst-band--split">
        <?php if ($popular !== []): ?>
            <div class="dst-band__panel">
                <span class="dst-kicker"><?= esc(ui('trending', 'Trending')) ?></span>
                <h2 class="dst-section-title"><?= esc(ui('most_read', 'Most read')) ?></h2>
                <ol class="dst-rank-list dst-rank-list--large">
                    <?php foreach ($popular as $index => $item): ?>
                        <li>
                            <span class="dst-rank-list__index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <div>
                                <a href="<?= esc((string) $item['url']) ?>" class="dst-rank-list__title"><?= esc((string) $item['title']) ?></a>
                                <span class="dst-rank-list__meta"><?= esc(human_date((string) ($item['published_at'] ?? ''))) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>

        <div class="dst-band__panel">
            <?php if ($categories !== []): ?>
                <span class="dst-kicker"><?= esc(ui('categories', 'Categories')) ?></span>
                <h2 class="dst-section-title"><?= esc(ui('browse_by_topic', 'Browse by topic')) ?></h2>
                <ul class="dst-catgrid">
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <a href="<?= esc((string) $category['url']) ?>">
                                <span class="dst-catgrid__name"><?= esc((string) $category['name']) ?></span>
                                <span class="dst-catgrid__count"><?= (int) ($category['count'] ?? 0) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($tags !== []): ?>
                <div class="dst-tagcloud dst-tagcloud--band">
                    <?php foreach ($tags as $tag): ?>
                        <a class="dst-chip" href="<?= esc((string) $tag['url']) ?>">#<?= esc((string) $tag['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (! empty($newsletterEnabled)): ?>
        <section class="dst-cta" data-reveal>
            <div class="dst-cta__copy">
                <span class="dst-kicker"><?= esc(ui('newsletter', 'Newsletter')) ?></span>
                <h2><?= esc(ui('newsletter_cta_title', 'Get the best stories, once a week')) ?></h2>
                <p><?= esc($newsletterMessage !== '' ? $newsletterMessage : ui('newsletter_hint', 'One thoughtful email each week. No noise, ever.')) ?></p>
            </div>
            <div class="dst-cta__form">
                <?= view('frontend/fallback/partials/newsletter', ['variant' => 'cta']) ?>
            </div>
        </section>
    <?php endif; ?>
</div>
