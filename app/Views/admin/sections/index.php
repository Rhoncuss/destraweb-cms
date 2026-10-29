<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('sections/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New section</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <?php if (empty($sections)): ?>
        <div class="dst-empty">
            <i class="fa-regular fa-rectangle-list"></i>
            <h3>No sections yet</h3>
            <p>Sections drive the content editor: each one can have its own fields, template and visibility.</p>
            <a class="btn btn-brand" href="<?= admin_url('sections/create') ?>">Create a section</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Section</th><th>Slug</th><th>Type</th><th>Template</th><th class="text-end">Content</th><th class="text-end">Fields</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($sections as $section): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="dst-icon-chip"><i class="<?= esc($section['icon'] ?: 'fa-regular fa-rectangle-list', 'attr') ?>"></i></span>
                                <div>
                                    <div class="dst-title"><?= esc($section['name']) ?></div>
                                    <?php if (! empty($section['description'])): ?><div class="dst-meta"><?= esc($section['description']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="dst-meta">/<?= esc($section['slug']) ?></td>
                        <td><span class="dst-chip"><?= esc(ucfirst($section['type'])) ?></span></td>
                        <td class="dst-meta"><?= esc($section['template']) ?></td>
                        <td class="text-end dst-meta"><?= (int) ($contentCounts[(int) $section['id']] ?? 0) ?></td>
                        <td class="text-end dst-meta"><?= (int) ($fieldCounts[(int) $section['id']] ?? 0) ?></td>
                        <td><span class="dst-badge <?= ! empty($section['is_active']) ? 'published' : 'draft' ?>"><?= ! empty($section['is_active']) ? 'Active' : 'Hidden' ?></span></td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('sections/' . (int) $section['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('sections/' . (int) $section['id'] . '/delete') ?>" data-confirm="Delete this section and its field definitions?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger-soft btn-icon" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
