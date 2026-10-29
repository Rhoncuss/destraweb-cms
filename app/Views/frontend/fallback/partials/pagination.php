<?php
/**
 * @var array{page:int, pages:int, total:int, query:array<string, mixed>} $pagination
 * @var string $baseUrl
 */
$page  = max(1, (int) ($pagination['page'] ?? 1));
$pages = max(1, (int) ($pagination['pages'] ?? 1));
$query = (array) ($pagination['query'] ?? []);

if ($pages <= 1) {
    return;
}

$link = static function (int $target) use ($baseUrl, $query): string {
    $params = $query;
    if ($target > 1) {
        $params['page'] = $target;
    }

    return $baseUrl . ($params !== [] ? '?' . http_build_query($params) : '');
};

$window = [];
$window[] = 1;
for ($i = max(2, $page - 1); $i <= min($pages - 1, $page + 1); $i++) {
    $window[] = $i;
}
if ($pages > 1) {
    $window[] = $pages;
}
$window = array_values(array_unique($window));
sort($window);
?>
<nav class="dst-pagination" aria-label="<?= esc(ui('pagination', 'Pagination')) ?>">
    <?php if ($page > 1): ?>
        <a class="dst-pagination__nav" href="<?= esc($link($page - 1)) ?>" rel="prev" aria-label="<?= esc(ui('previous', 'Previous')) ?>">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        </a>
    <?php endif; ?>

    <?php $previous = 0; ?>
    <?php foreach ($window as $number): ?>
        <?php if ($previous > 0 && $number - $previous > 1): ?>
            <span class="dst-pagination__ellipsis">…</span>
        <?php endif; ?>
        <?php if ($number === $page): ?>
            <span class="dst-pagination__page is-active" aria-current="page"><?= $number ?></span>
        <?php else: ?>
            <a class="dst-pagination__page" href="<?= esc($link($number)) ?>"><?= $number ?></a>
        <?php endif; ?>
        <?php $previous = $number; ?>
    <?php endforeach; ?>

    <?php if ($page < $pages): ?>
        <a class="dst-pagination__nav" href="<?= esc($link($page + 1)) ?>" rel="next" aria-label="<?= esc(ui('next', 'Next')) ?>">
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</nav>
