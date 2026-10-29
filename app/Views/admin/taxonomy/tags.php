<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('tags/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New tag</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <?php if (empty($items)): ?>
        <div class="dst-empty">
            <i class="fa-solid fa-tags"></i>
            <h3>No tags yet</h3>
            <p>Tags let editors connect related content without a strict hierarchy. Add your first one to get started.</p>
            <a class="btn btn-brand" href="<?= admin_url('tags/create') ?>">Create a tag</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Tag</th><th>Slug</th><th class="text-end">Content</th><th>Languages</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <span class="dst-chip" style="background:<?= esc($item['color'] ?: '#6d5efc', 'attr') ?>1f;color:<?= esc($item['color'] ?: '#6d5efc', 'attr') ?>;border-color:transparent;">
                                <i class="fa-solid fa-hashtag me-1" style="font-size:9px;"></i><?= esc($item['name'] ?: 'Untitled') ?>
                            </span>
                        </td>
                        <td class="dst-meta">/<?= esc((string) ($item['slug'] ?? '')) ?></td>
                        <td class="text-end dst-meta"><?= (int) ($counts[(int) $item['id']] ?? 0) ?></td>
                        <td>
                            <?php $codes = array_keys($translations[(int) $item['id']] ?? []); ?>
                            <?php foreach ($codes as $code): ?><span class="dst-chip" style="font-size:10px;"><?= esc(strtoupper($code)) ?></span><?php endforeach; ?>
                            <?php if ($codes === []): ?><span class="text-muted-2 small">—</span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('tags/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('tags/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this tag? It will be removed from all content.">
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
