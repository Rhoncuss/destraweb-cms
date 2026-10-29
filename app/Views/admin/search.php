<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('content') ?>" class="btn btn-soft"><i class="fa-regular fa-file-lines me-2"></i>Content</a>
    <a href="<?= admin_url('media') ?>" class="btn btn-soft"><i class="fa-regular fa-images me-2"></i>Media</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dst-card mb-3">
    <div class="card-body">
        <form method="get" action="<?= admin_url('search') ?>" class="d-flex gap-2" role="search">
            <div class="input-group">
                <span class="input-group-text bg-transparent"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-lg" placeholder="Search content, media and people…" autofocus>
            </div>
            <button class="btn btn-brand px-4" type="submit">Search</button>
        </form>
        <?php if ($query === ''): ?>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="text-muted-2 small align-self-center">Try:</span>
                <?php foreach (['welcome', 'security', 'launch'] as $suggestion): ?>
                    <a href="<?= admin_url('search?q=' . urlencode($suggestion)) ?>" class="dst-chip"><?= esc($suggestion) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($query !== '' && mb_strlen($query) < 2): ?>
    <div class="dst-card"><div class="dst-empty"><i class="fa-solid fa-keyboard"></i><h3>Keep typing</h3><p>Enter at least two characters to search.</p></div></div>
<?php elseif ($query !== '' && empty($contents) && empty($users) && empty($media)): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>No matches for “<?= esc($query) ?>”</h3>
            <p>Try a different keyword, or search by slug and e-mail address.</p>
            <a class="btn btn-brand" href="<?= admin_url('content/create') ?>">Create content</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="dst-card">
                <div class="card-header">
                    <h2 class="card-title">Content</h2>
                    <span class="dst-chip"><?= count($contents) ?></span>
                </div>
                <?php if (empty($contents)): ?>
                    <div class="card-body text-muted-2 small">No content matched.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($contents as $item): ?>
                            <a class="list-group-item list-group-item-action bg-transparent" href="<?= admin_url('content/' . (int) $item['id'] . '/edit') ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="dst-avatar" style="width:36px;height:36px;font-size:13px;"><i class="fa-regular fa-file-lines"></i></span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-650 truncate"><?= esc($item['title']) ?></div>
                                        <div class="text-muted-2 small truncate"><?= esc($item['excerpt'] ?: '/' . $item['slug']) ?></div>
                                    </div>
                                    <span class="dst-badge <?= esc((string) $item['status'], 'attr') ?>"><?= esc(ucfirst((string) $item['status'])) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dst-card mt-3">
                <div class="card-header">
                    <h2 class="card-title">Media</h2>
                    <span class="dst-chip"><?= count($media) ?></span>
                </div>
                <?php if (empty($media)): ?>
                    <div class="card-body text-muted-2 small">No media matched.</div>
                <?php else: ?>
                    <div class="card-body">
                        <div class="media-grid">
                            <?php foreach ($media as $file): ?>
                                <a class="media-tile" href="<?= esc(service('media')->url($file), 'attr') ?>" target="_blank" rel="noopener" title="<?= esc($file['original_name'], 'attr') ?>">
                                    <?php if (str_starts_with((string) $file['mime_type'], 'image/')): ?>
                                        <img src="<?= esc(service('media')->url($file, 'thumb'), 'attr') ?>" alt="<?= esc($file['alt'] ?: $file['original_name'], 'attr') ?>" loading="lazy">
                                    <?php else: ?>
                                        <span class="d-flex align-items-center justify-content-center h-100" style="font-size:30px;color:#8b93ab;"><i class="fa-regular fa-file"></i></span>
                                    <?php endif; ?>
                                    <span class="tile-ext"><?= esc(strtoupper((string) $file['extension'])) ?></span>
                                    <span class="tile-meta"><?= esc($file['original_name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="dst-card">
                <div class="card-header">
                    <h2 class="card-title">People</h2>
                    <span class="dst-chip"><?= count($users) ?></span>
                </div>
                <?php if (empty($users)): ?>
                    <div class="card-body text-muted-2 small">No people matched.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($users as $person): ?>
                            <div class="list-group-item bg-transparent d-flex align-items-center gap-3">
                                <span class="dst-avatar"><?= esc(mb_strtoupper(mb_substr((string) $person['name'], 0, 1))) ?></span>
                                <div class="min-w-0">
                                    <div class="fw-650 truncate"><?= esc($person['name']) ?></div>
                                    <div class="text-muted-2 small truncate"><?= esc($person['email']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
