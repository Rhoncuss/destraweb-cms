<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('settings?group=appearance') ?>" class="btn btn-soft"><i class="fa-solid fa-palette me-2"></i>Colour &amp; typography</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($preview !== ''): ?>
    <div class="dst-card mb-3" style="border-color:color-mix(in srgb, var(--dst-brand) 35%, transparent);">
        <div class="card-body d-flex flex-wrap align-items-center gap-2 py-3">
            <span class="dst-badge pending"><i class="fa-regular fa-eye me-1"></i>Preview mode</span>
            <span class="text-muted-2 small me-auto">You are previewing <strong><?= esc($preview) ?></strong> on the public site. Visitors still see the active theme.</span>
            <form method="post" action="<?= admin_url('themes/preview/stop') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-soft btn-sm">End preview</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($cards === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-solid fa-palette"></i>
            <h3>No themes installed</h3>
            <p>Themes live in <code><?= esc($themesPath) ?></code>. Each theme is a folder with a <code>theme.json</code> manifest, its own views and assets.</p>
            <p class="text-muted-2 small">Install the bundled themes from the package, or build your own using the theme development guide.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($cards as $card): ?>
            <div class="col-md-6 col-xl-4">
                <div class="dst-card theme-card h-100">
                    <div class="theme-shot">
                        <img src="<?= esc($card['screenshot'], 'attr') ?>" alt="<?= esc($card['name'], 'attr') ?> preview" loading="lazy">
                        <?php if ($card['active']): ?><span class="dst-badge published theme-flag"><i class="fa-solid fa-circle-check me-1"></i>Active</span><?php endif; ?>
                        <?php if ($card['is_preview']): ?><span class="dst-badge pending theme-flag">Previewing</span><?php endif; ?>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="card-title mb-0"><?= esc($card['name']) ?></h2>
                            <span class="dst-chip">v<?= esc($card['version']) ?></span>
                        </div>
                        <div class="text-muted-2 small mb-2">by <?= esc($card['author']) ?></div>
                        <p class="text-muted-2 small flex-grow-1"><?= esc($card['description']) ?></p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (! $card['active']): ?>
                                <form method="post" action="<?= admin_url('themes/activate/' . $card['slug']) ?>" class="flex-grow-1">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-brand w-100 btn-sm">Activate</button>
                                </form>
                            <?php else: ?>
                                <a class="btn btn-soft btn-sm flex-grow-1" href="<?= base_url('/') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View site</a>
                            <?php endif; ?>
                            <form method="post" action="<?= admin_url('themes/preview/' . $card['slug']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-soft btn-sm" title="Preview on the public site"><i class="fa-regular fa-eye"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dst-card mt-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <div class="dst-icon-chip"><i class="fa-solid fa-code"></i></div>
            <div class="min-w-0 flex-grow-1">
                <div class="fw-650">Build your own theme</div>
                <div class="text-muted-2 small">Drop a folder with <code>theme.json</code>, <code>Views/</code> and <code>assets/</code> into <code><?= esc($themesPath) ?></code> — it appears here instantly.</div>
            </div>
            <span class="dst-chip">hot-swappable</span>
        </div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
