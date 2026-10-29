<?php
/**
 * @var array<string, mixed> $content
 * @var list<array<string, mixed>> $related
 * @var list<array<string, mixed>> $comments
 * @var array<string, mixed> $sidebar
 * @var list<array{label:string,url?:string}> $crumbs
 */
$content    = $content ?? [];
$related    = $related ?? [];
$comments   = $comments ?? [];
$sidebar    = $sidebar ?? [];
$crumbs     = $crumbs ?? [];
$share      = $share ?? [];
$inlineAds  = $inlineAds ?? [];
$isDoc      = ! empty($isDoc);
$isPage     = ! empty($isPage);
$author     = $content['author'] ?? [];
$category   = $content['primary_category'] ?? null;
$image      = (string) ($content['image_url'] ?? '');
$tags       = $content['tags'] ?? [];
$date       = (string) ($content['published_at'] ?? '');
$updated    = (string) ($content['updated_at'] ?? '');
$reading    = (int) ($content['reading_time'] ?? 0);
$views      = (int) ($content['views'] ?? 0);
$docsTree   = $docsTree ?? [];
$docsNav    = view('frontend/fallback/partials/docs-nav', ['docsTree' => $docsTree, 'activeDoc' => $content]);
?>
<div class="dst-container dst-single">
    <?= view('frontend/fallback/partials/breadcrumbs', ['crumbs' => $crumbs]) ?>

    <header class="dst-article-head" data-reveal>
        <?php if ($category !== null): ?>
            <a class="dst-kicker dst-kicker--link" href="<?= esc((string) $category['url']) ?>"><?= esc((string) $category['name']) ?></a>
        <?php elseif ($isDoc): ?>
            <span class="dst-kicker"><?= esc(ui('documentation', 'Documentation')) ?></span>
        <?php elseif (! empty($content['section_name'])): ?>
            <span class="dst-kicker"><?= esc((string) $content['section_name']) ?></span>
        <?php endif; ?>

        <h1 class="dst-display"><?= esc((string) $content['title']) ?></h1>

        <?php if (! empty($content['excerpt'])): ?>
            <p class="dst-lede"><?= esc((string) $content['excerpt']) ?></p>
        <?php endif; ?>

        <div class="dst-article-head__meta">
            <?php if (! empty($author['name'])): ?>
                <a class="dst-author-chip" href="<?= esc((string) ($content['author_url'] ?? '#')) ?>">
                    <?php $avatar = avatar_url($author); ?>
                    <?php if ($avatar !== ''): ?>
                        <img class="dst-avatar" src="<?= esc($avatar) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="dst-avatar dst-avatar--text"><?= esc(initials((string) $author['name'])) ?></span>
                    <?php endif; ?>
                    <span>
                        <strong><?= esc((string) $author['name']) ?></strong>
                        <small><?= esc(ui('author', 'Author')) ?></small>
                    </span>
                </a>
            <?php endif; ?>

            <div class="dst-article-head__stats">
                <?php if ($date !== ''): ?>
                    <span><i class="fa-regular fa-calendar" aria-hidden="true"></i> <time datetime="<?= esc($date) ?>"><?= esc(human_date($date)) ?></time></span>
                <?php endif; ?>
                <?php if ($reading > 0): ?>
                    <span><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= $reading ?> <?= esc(ui('min_read', 'min read')) ?></span>
                <?php endif; ?>
                <?php if (setting_bool('reading.show_views', false) && $views > 0): ?>
                    <span><i class="fa-regular fa-eye" aria-hidden="true"></i> <?= esc(number_format($views)) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($share !== []): ?>
                <?= view('frontend/fallback/partials/share', ['share' => $share]) ?>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($image !== '' && ! $isDoc): ?>
        <figure class="dst-article-cover" data-reveal>
            <img src="<?= esc($image) ?>" alt="<?= esc((string) $content['title']) ?>" loading="eager" decoding="async">
        </figure>
    <?php endif; ?>

    <div class="dst-layout dst-layout--article">
        <article class="dst-article">
            <div class="dst-prose" data-article-body>
                <?= \App\Libraries\Hook::apply('content.render', render_content((string) $content['body']), $content) ?>
            </div>

            <?php foreach ($inlineAds as $ad): ?>
                <div class="dst-ad dst-ad--inline"><?= view('frontend/fallback/partials/ad', ['ad' => $ad]) ?></div>
            <?php endforeach; ?>

            <?php if (! empty($orderForm)): ?>
                <section class="dst-article__order">
                    <?= view('frontend/fallback/partials/order-form', ['content' => $content]) ?>
                </section>
            <?php endif; ?>

            <?php if ($tags !== []): ?>
                <div class="dst-article__tags">
                    <?php foreach ($tags as $tag): ?>
                        <a class="dst-chip" href="<?= esc((string) $tag['url']) ?>">#<?= esc((string) $tag['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($share !== []): ?>
                <div class="dst-article__share">
                    <?= view('frontend/fallback/partials/share', ['share' => $share]) ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($author['name'])): ?>
                <section class="dst-authorbox">
                    <?php if (avatar_url($author) !== ''): ?>
                        <img class="dst-authorbox__avatar" src="<?= esc((string) avatar_url($author)) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span class="dst-authorbox__avatar dst-authorbox__avatar--text"><?= esc(initials((string) $author['name'])) ?></span>
                    <?php endif; ?>
                    <div>
                        <span class="dst-kicker"><?= esc(ui('written_by', 'Written by')) ?></span>
                        <h3><?= esc((string) $author['name']) ?></h3>
                        <?php if (! empty($author['bio'])): ?>
                            <p><?= esc((string) $author['bio']) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($content['author_url'])): ?>
                            <a class="dst-link-more" href="<?= esc((string) $content['author_url']) ?>"><?= esc(ui('more_from', 'More from this author')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($isDoc): ?>
                <?php $prevDoc = $prevDoc ?? null; $nextDoc = $nextDoc ?? null; ?>
                <?php if ($prevDoc !== null || $nextDoc !== null): ?>
                    <nav class="dst-docpager" aria-label="<?= esc(ui('documentation', 'Documentation')) ?>">
                        <?php if ($prevDoc !== null): ?>
                            <a class="dst-docpager__item" href="<?= esc((string) $prevDoc['url']) ?>">
                                <span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= esc(ui('previous', 'Previous')) ?></span>
                                <strong><?= esc((string) $prevDoc['title']) ?></strong>
                            </a>
                        <?php endif; ?>
                        <?php if ($nextDoc !== null): ?>
                            <a class="dst-docpager__item dst-docpager__item--next" href="<?= esc((string) $nextDoc['url']) ?>">
                                <span><?= esc(ui('next', 'Next')) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                                <strong><?= esc((string) $nextDoc['title']) ?></strong>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (! $isDoc && ! $isPage && ! empty($commentsOpen)): ?>
                <?= view('frontend/fallback/partials/comments', [
                    'comments'             => $comments,
                    'commentCount'         => $commentCount ?? 0,
                    'commentUser'          => $commentUser ?? null,
                    'commentsOpen'         => $commentsOpen,
                    'commentRequiresLogin' => $commentRequiresLogin ?? false,
                    'commentGuestsAllowed' => $commentGuestsAllowed ?? true,
                    'commentMaxLength'     => $commentMaxLength ?? 3000,
                    'commentCaptcha'       => $commentCaptcha ?? '',
                    'content'              => $content,
                ]) ?>
            <?php endif; ?>
        </article>

        <aside class="dst-article-aside">
            <?php if ($isDoc): ?>
                <div class="dst-docsnav-wrap"><?= $docsNav ?></div>
            <?php else: ?>
                <nav class="dst-toc" data-toc hidden aria-label="<?= esc(ui('on_this_page', 'On this page')) ?>">
                    <span class="dst-toc__label"><?= esc(ui('on_this_page', 'On this page')) ?></span>
                    <ul data-toc-list></ul>
                </nav>

                <?php if (! empty($updated) && $updated !== $date): ?>
                    <p class="dst-aside-note"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= esc(ui('updated', 'Updated')) ?> <?= esc(human_date($updated)) ?></p>
                <?php endif; ?>

                <?= view('frontend/fallback/partials/sidebar', ['sidebar' => $sidebar]) ?>
            <?php endif; ?>
        </aside>
    </div>

    <?php if (! $isDoc && $related !== []): ?>
        <section class="dst-section dst-section--related">
            <header class="dst-section__head">
                <div>
                    <span class="dst-kicker"><?= esc(ui('keep_reading', 'Keep reading')) ?></span>
                    <h2 class="dst-section-title"><?= esc(ui('related_stories', 'Related stories')) ?></h2>
                </div>
            </header>
            <div class="dst-grid dst-grid--3">
                <?php foreach ($related as $item): ?>
                    <?= view('frontend/fallback/partials/card', ['item' => $item, 'variant' => 'grid']) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
