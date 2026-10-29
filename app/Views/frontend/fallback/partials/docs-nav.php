<?php
/**
 * @var list<array<string, mixed>> $docsTree
 * @var array<string, mixed>|null  $activeDoc
 */
$docsTree  = $docsTree ?? [];
$active    = (int) ($activeDoc['id'] ?? 0);
$renderNode = function (array $node) use (&$renderNode, $active): void {
    $isActive = (int) $node['id'] === $active;
    ?>
    <li class="<?= ! empty($node['children']) ? 'has-children' : '' ?>">
        <a href="<?= esc((string) $node['url']) ?>" class="<?= $isActive ? 'is-active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <?= esc((string) $node['title']) ?>
        </a>
        <?php if (! empty($node['children'])): ?>
            <ul><?php foreach ($node['children'] as $child): ?><?php $renderNode($child); ?><?php endforeach; ?></ul>
        <?php endif; ?>
    </li>
    <?php
};
?>
<nav class="dst-docsnav" aria-label="<?= esc(ui('documentation', 'Documentation')) ?>">
    <div class="dst-docsnav__head">
        <span class="dst-kicker"><?= esc(ui('documentation', 'Documentation')) ?></span>
        <a href="<?= esc(loc_url('docs', current_locale())) ?>" class="dst-docsnav__home"><?= esc(ui('all_docs', 'All guides')) ?></a>
    </div>
    <ul class="dst-docsnav__list">
        <?php foreach ($docsTree as $node): ?>
            <?php $renderNode($node); ?>
        <?php endforeach; ?>
    </ul>
</nav>
