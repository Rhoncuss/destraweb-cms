<?php
/**
 * @var array<string, mixed> $sidebar
 */
$popular    = $sidebar['popular'] ?? [];
$categories = $sidebar['categories'] ?? [];
$tags       = $sidebar['tags'] ?? [];
$ads        = $sidebar['ads'] ?? [];
?>
<aside class="dst-sidebar" aria-label="<?= esc(ui('sidebar', 'Sidebar')) ?>">
    <?php foreach ($ads as $ad): ?>
        <div class="dst-sidebar__block dst-ad dst-ad--sidebar"><?= view('frontend/fallback/partials/ad', ['ad' => $ad]) ?></div>
    <?php endforeach; ?>

    <?php if (! empty($newsletterEnabled)): ?>
        <div class="dst-sidebar__block dst-sidebar__newsletter">
            <span class="dst-kicker"><?= esc(ui('newsletter', 'Newsletter')) ?></span>
            <h3><?= esc(ui('newsletter_sidebar_title', 'Stories worth your inbox')) ?></h3>
            <p><?= esc($newsletterMessage !== '' ? $newsletterMessage : ui('newsletter_hint', 'One thoughtful email each week. No noise, ever.')) ?></p>
            <?= view('frontend/fallback/partials/newsletter', ['variant' => 'sidebar']) ?>
        </div>
    <?php endif; ?>

    <?php if ($popular !== []): ?>
        <div class="dst-sidebar__block">
            <h3 class="dst-sidebar__title"><?= esc(ui('most_read', 'Most read')) ?></h3>
            <ol class="dst-rank-list">
                <?php foreach ($popular as $index => $item): ?>
                    <li>
                        <span class="dst-rank-list__index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div>
                            <a href="<?= esc((string) $item['url']) ?>" class="dst-rank-list__title"><?= esc((string) $item['title']) ?></a>
                            <span class="dst-rank-list__meta"><?= esc(human_date((string) ($item['published_at'] ?? ''))) ?><?= setting_bool('reading.show_views', false) ? ' · ' . number_format((int) $item['views']) . ' views' : '' ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>

    <?php if ($categories !== []): ?>
        <div class="dst-sidebar__block">
            <h3 class="dst-sidebar__title"><?= esc(ui('categories', 'Categories')) ?></h3>
            <ul class="dst-side-list">
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="<?= esc((string) $category['url']) ?>"><?= esc((string) $category['name']) ?></a>
                        <span class="dst-count"><?= (int) ($category['count'] ?? 0) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($tags !== []): ?>
        <div class="dst-sidebar__block">
            <h3 class="dst-sidebar__title"><?= esc(ui('tags', 'Tags')) ?></h3>
            <div class="dst-tagcloud">
                <?php foreach ($tags as $tag): ?>
                    <a class="dst-chip" href="<?= esc((string) $tag['url']) ?>">#<?= esc((string) $tag['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</aside>
