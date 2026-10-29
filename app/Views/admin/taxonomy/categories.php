<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('categories/create') ?>" class="btn btn-brand"><i class="fa-solid fa-plus me-2"></i>New category</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card">
    <?php if (empty($items)): ?>
        <div class="dst-empty">
            <i class="fa-regular fa-folder-open"></i>
            <h3>No categories yet</h3>
            <p>Categories organise posts and docs into browsable topics. Give each one a friendly URL and optional artwork.</p>
            <a class="btn btn-brand" href="<?= admin_url('categories/create') ?>">Create the first category</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Section</th>
                        <th class="text-end">Content</th>
                        <th>Languages</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:10px;height:10px;border-radius:50%;background:<?= esc($item['color'] ?: '#6d5efc', 'attr') ?>;"></span>
                                <?php if (! empty($item['icon'])): ?><i class="<?= esc($item['icon'], 'attr') ?> text-muted-2"></i><?php endif; ?>
                                <div class="dst-title text-truncate"><?= esc($item['name'] ?: 'Untitled') ?></div>
                            </div>
                        </td>
                        <td class="dst-meta">/<?= esc((string) ($item['slug'] ?? '')) ?></td>
                        <td class="dst-meta"><?= esc($item['section_name'] ?? '—') ?></td>
                        <td class="text-end dst-meta"><?= (int) ($counts[(int) $item['id']] ?? 0) ?></td>
                        <td>
                            <?php $codes = array_keys($translations[(int) $item['id']] ?? []); ?>
                            <?php if ($codes === []): ?>
                                <span class="text-muted-2 small">—</span>
                            <?php else: ?>
                                <?php foreach ($codes as $code): ?>
                                    <span class="dst-chip" style="font-size:10px;"><?= esc(strtoupper($code)) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" data-dst-toggle="<?= admin_url('categories/' . (int) $item['id'] . '/toggle') ?>" <?= ! empty($item['is_active']) ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="table-actions">
                                <a class="btn btn-soft btn-icon" href="<?= admin_url('categories/' . (int) $item['id'] . '/edit') ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= admin_url('categories/' . (int) $item['id'] . '/delete') ?>" data-confirm="Remove this category? Content stays but loses the label.">
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
