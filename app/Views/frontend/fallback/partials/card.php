<?php
/**
 * Content card used across home, archives and sidebars.
 *
 * @var array<string, mixed> $item
 * @var string $variant grid|list|compact|hero|wide
 */
$variant   = $variant ?? 'grid';
$url       = (string) ($item['url'] ?? '#');
$title     = (string) ($item['title'] ?? '');
$excerpt   = (string) ($item['excerpt'] ?? '');
$image     = (string) (($item['thumb_url'] ?? '') !== '' ? $item['thumb_url'] : ($item['image_url'] ?? ''));
$category  = $item['primary_category'] ?? null;
$author    = $item['author'] ?? [];
$date      = (string) ($item['published_at'] ?? '');
$reading   = (int) ($item['reading_time'] ?? 0);
$views     = (int) ($item['views'] ?? 0);

$showAuthor  = setting_bool('reading.show_author', true);
$showDate    = setting_bool('reading.show_date', true);
$showReading = setting_bool('reading.show_reading_time', true);
$showViews   = setting_bool('reading.show_views', false);
?>
<article class="dst-card dst-card--<?= esc($variant) ?>" data-reveal>
    <?php if ($variant !== 'compact' && $image !== ''): ?>
        <a class="dst-card__media" href="<?= esc($url) ?>" aria-hidden="true" tabindex="-1">
            <img src="<?= esc($image) ?>" alt="<?= esc($title) ?>" loading="lazy" decoding="async">
            <?php if ($category !== null): ?>
                <span class="dst-chip dst-chip--overlay"><?= esc((string) $category['name']) ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <div class="dst-card__body">
        <?php if ($variant === 'compact'): ?>
            <div class="dst-card__kicker">
                <?php if ($category !== null): ?>
                    <a href="<?= esc((string) $category['url']) ?>" class="dst-kicker"><?= esc((string) $category['name']) ?></a>
                <?php else: ?>
                    <span class="dst-kicker"><?= esc((string) ($item['section_name'] ?? '')) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h3 class="dst-card__title"><a href="<?= esc($url) ?>"><?= esc($title) ?></a></h3>

        <?php if ($variant !== 'compact' && $excerpt !== ''): ?>
            <p class="dst-card__excerpt"><?= esc($excerpt) ?></p>
        <?php endif; ?>

        <div class="dst-card__meta">
            <?php if ($showAuthor && ! empty($author['name'])): ?>
                <span class="dst-card__author">
                    <?php $avatar = avatar_url($author); ?>
                    <?php if ($avatar !== ''): ?>
                        <img src="<?= esc($avatar) ?>" alt="" class="dst-avatar dst-avatar--xs" loading="lazy">
                    <?php endif; ?>
                    <?= esc((string) $author['name']) ?>
                </span>
            <?php endif; ?>
            <?php if ($showDate && $date !== ''): ?>
                <time datetime="<?= esc($date) ?>"><?= esc(human_date($date)) ?></time>
            <?php endif; ?>
            <?php if ($showReading && $reading > 0): ?>
                <span><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= esc((string) $reading) ?> <?= esc(ui('min_read', 'min read')) ?></span>
            <?php endif; ?>
            <?php if ($showViews && $views > 0): ?>
                <span><i class="fa-regular fa-eye" aria-hidden="true"></i> <?= esc(number_format($views)) ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
