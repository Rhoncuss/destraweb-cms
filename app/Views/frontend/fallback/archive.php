<?php
/**
 * @var list<array<string, mixed>> $items
 * @var array<string, mixed> $pagination
 * @var string $baseUrl
 * @var string $heading
 * @var string $subheading
 * @var list<array{label:string,url?:string}> $crumbs
 */
$items      = $items ?? [];
$heading    = $heading ?? '';
$subheading = $subheading ?? '';
$kicker     = $kicker ?? '';
$avatar     = $avatar ?? '';
$query      = $query ?? '';
$isSearch   = ($bodyClass ?? '') === 'search' || $query !== '';
?>
<div class="dst-container dst-archive">
    <?= view('frontend/fallback/partials/breadcrumbs', ['crumbs' => $crumbs ?? []]) ?>

    <header class="dst-archive__head" data-reveal>
        <div class="dst-archive__intro">
            <?php if ($avatar !== ''): ?>
                <img class="dst-archive__avatar" src="<?= esc($avatar) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div>
                <span class="dst-kicker"><?= esc($kicker !== '' ? $kicker : ui('archive', 'Archive')) ?></span>
                <h1 class="dst-display dst-display--sm"><?= esc($heading) ?></h1>
                <?php if ($subheading !== ''): ?>
                    <p class="dst-lede"><?= esc($subheading) ?></p>
                <?php endif; ?>
                <p class="dst-archive__count">
                    <?= esc(ui('stories_found', 'Stories')) ?>: <strong><?= (int) ($pagination['total'] ?? 0) ?></strong>
                    <?php if ((int) ($pagination['pages'] ?? 1) > 1): ?>
                        <span>· <?= esc(ui('page', 'Page')) ?> <?= (int) $pagination['page'] ?> / <?= (int) $pagination['pages'] ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($isSearch): ?>
            <form class="dst-archive__search" method="get" action="<?= esc(search_url('', $locale)) ?>" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" name="q" value="<?= esc($query) ?>" maxlength="120"
                       placeholder="<?= esc(ui('search_placeholder', 'Search the site…')) ?>" aria-label="<?= esc(ui('search', 'Search')) ?>">
                <button class="dst-btn dst-btn--primary" type="submit"><?= esc(ui('search', 'Search')) ?></button>
            </form>
        <?php endif; ?>
    </header>

    <div class="dst-layout dst-layout--sidebar">
        <div class="dst-layout__main">
            <?php if ($items === []): ?>
                <div class="dst-empty" data-reveal>
                    <span class="dst-empty__icon"><i class="fa-regular fa-folder-open" aria-hidden="true"></i></span>
                    <h2><?= esc(ui('nothing_found', 'Nothing here yet')) ?></h2>
                    <p><?= esc($isSearch
                        ? ui('search_nothing', 'No stories matched that search. Try a different keyword.')
                        : ui('archive_nothing', 'This archive does not have any published stories yet.')) ?></p>
                    <div class="dst-empty__actions">
                        <?php if ($isSearch): ?>
                            <a class="dst-btn dst-btn--primary" href="<?= esc(search_url('', $locale)) ?>"><?= esc(ui('new_search', 'New search')) ?></a>
                        <?php endif; ?>
                        <a class="dst-btn dst-btn--ghost" href="<?= esc(loc_url('', $locale)) ?>"><?= esc(ui('back_home', 'Back home')) ?></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="dst-grid dst-grid--2">
                    <?php foreach ($items as $item): ?>
                        <?= view('frontend/fallback/partials/card', ['item' => $item, 'variant' => 'list']) ?>
                    <?php endforeach; ?>
                </div>

                <?= view('frontend/fallback/partials/pagination', [
                    'pagination' => $pagination,
                    'baseUrl'    => $baseUrl,
                ]) ?>
            <?php endif; ?>
        </div>

        <?= view('frontend/fallback/partials/sidebar', ['sidebar' => $sidebar ?? []]) ?>
    </div>
</div>
