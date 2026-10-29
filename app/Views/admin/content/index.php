<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <div class="d-inline-flex gap-2 flex-wrap">
        <a href="<?= admin_url('content/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>Add new</a>
        <a href="<?= admin_url('media') ?>" class="btn btn-soft"><i class="fa-regular fa-images me-2"></i>Media</a>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusLabels = ['' => 'All', 'published' => 'Published', 'draft' => 'Draft', 'private' => 'Private', 'trash' => 'Trash'];
$statusKey = (string) ($filters['status'] ?? '');
$qs = static function (array $overrides) use ($filters): string {
    $params = array_filter(array_merge([
        'type'    => $filters['type'],
        'status'  => $filters['status'],
        'section' => $filters['section_id'] ?: '',
        'q'       => $filters['q'],
        'order'   => $filters['order'],
    ], $overrides), static fn ($v) => $v !== '' && $v !== 0 && $v !== null);

    return admin_url('content' . ($params ? '?' . http_build_query($params) : ''));
};
?>

<div class="dst-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex flex-wrap gap-1 me-auto">
                <?php foreach ($statusLabels as $key => $label): ?>
                    <a href="<?= $qs(['status' => $key, 'page' => '']) ?>"
                       class="btn btn-sm <?= $statusKey === $key ? 'btn-brand' : 'btn-soft' ?>">
                        <?= esc($label) ?>
                        <span class="ms-1" style="opacity:.65;"><?= (int) ($counts[$key === '' ? 'all' : $key] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="<?= admin_url('content') ?>" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="status" value="<?= esc($statusKey, 'attr') ?>">
                <select name="type" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All types</option>
                    <?php foreach (['post' => 'Posts', 'page' => 'Pages', 'doc' => 'Docs'] as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="section" class="form-select form-select-sm" style="width:auto;">
                    <option value="">All sections</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?= (int) $section['id'] ?>" <?= (int) $filters['section_id'] === (int) $section['id'] ? 'selected' : '' ?>><?= esc($section['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="search" name="q" value="<?= esc($filters['q']) ?>" class="form-control form-control-sm" placeholder="Search title…" style="width:180px;">
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</div>

<form method="post" action="<?= admin_url('content/bulk') ?>" id="bulkForm">
    <?= csrf_field() ?>
    <div class="dst-card">
        <div class="table-responsive">
            <table class="table align-middle dst-table-content">
                <thead>
                    <tr>
                        <th style="width:38px;"><input class="form-check-input" type="checkbox" data-check-all></th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Author</th>
                        <th class="text-end">Views</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="dst-empty">
                                <i class="fa-regular fa-file-lines"></i>
                                <h3>No content here yet</h3>
                                <p>Create your first post, page or documentation entry. Everything supports revisions, translations and custom fields.</p>
                                <a class="btn btn-brand" href="<?= admin_url('content/create') ?>">Create content</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" data-check-item></td>
                            <td>
                                <div class="dst-title"><?= esc($item['title'] ?? 'Untitled') ?><?= ! empty($item['sticky']) ? ' <i class="fa-solid fa-thumbtack ms-1" style="font-size:11px;color:var(--dst-brand);"></i>' : '' ?></div>
                                <div class="dst-meta">/<?= esc((string) ($item['slug'] ?? '')) ?></div>
                            </td>
                            <td class="dst-meta"><?= esc(ucfirst((string) ($item['type'] ?? 'post'))) ?><?= ! empty($item['section_name']) ? ' · ' . esc($item['section_name']) : '' ?></td>
                            <td><span class="dst-badge <?= esc((string) ($item['status'] ?? 'draft'), 'attr') ?>"><?= esc(ucfirst((string) ($item['status'] ?? 'draft'))) ?></span></td>
                            <td class="dst-meta"><?= esc(is_array($item['author'] ?? null) ? ($item['author']['name'] ?? '—') : ($item['author'] ?? '—')) ?></td>
                            <td class="text-end dst-meta"><?= number_format((int) ($item['views'] ?? 0)) ?></td>
                            <td class="dst-meta">
                                <?php if (! empty($item['published_at'])): ?>
                                    <?= esc(date('d M Y', strtotime((string) $item['published_at']))) ?>
                                <?php else: ?>
                                    <?= esc(date('d M Y', strtotime((string) ($item['updated_at'] ?? 'now')))) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <?php if ($isTrash): ?>
                                        <form method="post" action="<?= admin_url('content/' . (int) $item['id'] . '/restore') ?>">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-soft btn-icon" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>
                                        </form>
                                        <form method="post" action="<?= admin_url('content/' . (int) $item['id'] . '/destroy') ?>" data-confirm="This permanently deletes the item and every translation." data-confirm-button="Delete forever">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger-soft btn-icon" title="Delete forever"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <a class="btn btn-soft btn-icon" href="<?= admin_url('content/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <?php if (($item['status'] ?? '') === 'published' && ! empty($item['url'])): ?>
                                            <a class="btn btn-soft btn-icon d-none d-md-inline-flex" href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener" title="View"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                        <?php endif; ?>
                                        <form method="post" action="<?= admin_url('content/' . (int) $item['id'] . '/delete') ?>" data-confirm="Move this item to trash?">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-soft btn-icon" title="Trash"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (! empty($items)): ?>
            <div class="card-header border-top" style="border-bottom:0;">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select name="action" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Bulk actions</option>
                        <?php if ($isTrash): ?>
                            <option value="restore">Restore selected</option>
                        <?php else: ?>
                            <option value="publish">Publish selected</option>
                            <option value="draft">Move to draft</option>
                            <option value="trash">Move to trash</option>
                        <?php endif; ?>
                    </select>
                    <button class="btn btn-soft btn-sm" type="submit">Apply</button>
                    <span class="text-muted-2 small ms-auto"><?= number_format((int) $total) ?> item(s)</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if (($pages ?? 1) > 1): ?>
    <nav class="mt-3" aria-label="Content pagination">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= (int) $pages; $i++): ?>
                <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var all = document.querySelector('[data-check-all]');
    var items = document.querySelectorAll('[data-check-item]');
    if (all) {
        all.addEventListener('change', function () {
            items.forEach(function (item) { item.checked = all.checked; });
        });
    }
    var form = document.getElementById('bulkForm');
    if (form) {
        form.addEventListener('submit', function (event) {
            var checked = document.querySelectorAll('[data-check-item]:checked').length;
            if (checked === 0) {
                event.preventDefault();
                if (window.Destra) { Destra.toast('error', 'Select at least one item first.'); }
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
