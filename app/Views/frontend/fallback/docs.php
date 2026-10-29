<?php
/**
 * @var array<string, mixed> $section
 * @var list<array<string, mixed>> $docs
 * @var list<array<string, mixed>> $docsTree
 * @var array<string, mixed>|null $activeDoc
 */
$section   = $section ?? [];
$docs      = $docs ?? [];
$docsTree  = $docsTree ?? [];
$activeDoc = $activeDoc ?? ($docs[0] ?? null);
?>
<div class="dst-container dst-docs">
    <?= view('frontend/fallback/partials/breadcrumbs', ['crumbs' => [['label' => (string) ($section['name'] ?? 'Docs')]]]) ?>

    <header class="dst-docs__head" data-reveal>
        <span class="dst-kicker"><?= esc(ui('documentation', 'Documentation')) ?></span>
        <h1 class="dst-display"><?= esc((string) ($section['name'] ?? ui('documentation', 'Documentation'))) ?></h1>
        <?php if (! empty($section['description'])): ?>
            <p class="dst-lede"><?= esc((string) $section['description']) ?></p>
        <?php endif; ?>
        <p class="dst-docs__count"><?= count($docs) ?> <?= esc(ui('guides', 'guides')) ?></p>
    </header>

    <div class="dst-layout dst-layout--docs">
        <aside class="dst-docs__nav">
            <?= view('frontend/fallback/partials/docs-nav', ['docsTree' => $docsTree, 'activeDoc' => $activeDoc]) ?>
        </aside>

        <div class="dst-docs__main">
            <?php if ($docs === []): ?>
                <div class="dst-empty">
                    <span class="dst-empty__icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
                    <h2><?= esc(ui('docs_empty', 'No guides published yet')) ?></h2>
                    <p><?= esc(ui('docs_empty_hint', 'Documentation pages will appear here as they are published.')) ?></p>
                </div>
            <?php else: ?>
                <div class="dst-docgrid">
                    <?php foreach ($docs as $doc): ?>
                        <?php $isActive = $activeDoc !== null && (int) $activeDoc['id'] === (int) $doc['id']; ?>
                        <a class="dst-doccard <?= $isActive ? 'is-active' : '' ?>" href="<?= esc((string) $doc['url']) ?>">
                            <span class="dst-doccard__icon"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span>
                            <span class="dst-doccard__body">
                                <strong><?= esc((string) $doc['title']) ?></strong>
                                <?php if (! empty($doc['excerpt'])): ?>
                                    <span><?= esc((string) $doc['excerpt']) ?></span>
                                <?php endif; ?>
                            </span>
                            <i class="fa-solid fa-arrow-right dst-doccard__arrow" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
