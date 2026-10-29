<?php
/** @var list<array{label:string, url?:string}> $crumbs */
$crumbs = $crumbs ?? [];
if ($crumbs === []) {
    return;
}
?>
<nav class="dst-breadcrumbs" aria-label="<?= esc(ui('breadcrumbs', 'Breadcrumbs')) ?>">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= esc(loc_url('', current_locale())) ?>" itemprop="item"><span itemprop="name"><?= esc(ui('home', 'Home')) ?></span></a>
            <meta itemprop="position" content="1">
        </li>
        <?php $position = 1; ?>
        <?php foreach ($crumbs as $crumb): ?>
            <?php $position++; ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if (! empty($crumb['url'])): ?>
                    <a href="<?= esc((string) $crumb['url']) ?>" itemprop="item"><span itemprop="name"><?= esc((string) $crumb['label']) ?></span></a>
                <?php else: ?>
                    <span itemprop="name" aria-current="page"><?= esc((string) $crumb['label']) ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= $position ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
