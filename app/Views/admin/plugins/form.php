<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('plugins') ?>" class="btn btn-soft">Back to plugins</a>
    <?php if ($schema !== []): ?>
        <button form="pluginSettingsForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i>Save settings</button>
    <?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Plugin settings</h2>
                <span class="dst-badge <?= $active ? 'active' : 'inactive' ?>"><?= $active ? 'Active' : 'Inactive' ?></span>
            </div>
            <div class="card-body">
                <?php if ($schema === []): ?>
                    <p class="text-muted-2 mb-0">This plugin does not expose any configuration options. It runs with sensible defaults.</p>
                <?php else: ?>
                    <form id="pluginSettingsForm" method="post" action="<?= admin_url('plugins/' . esc($slug, 'attr') . '/settings') ?>">
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <?php foreach ($schema as $key => $field):
                                $type  = (string) ($field['type'] ?? 'text');
                                $id    = 'plg-' . preg_replace('/[^a-z0-9-]/i', '-', $key);
                                $value = $values[$key] ?? ($field['default'] ?? '');
                                $help  = (string) ($field['help'] ?? '');
                                $label = (string) ($field['label'] ?? ucfirst($key));
                            ?>
                                <?php if ($type === 'bool'): ?>
                                    <div class="col-12">
                                        <div class="dst-setting-row">
                                            <div>
                                                <label class="form-label mb-0" for="<?= $id ?>"><?= esc($label) ?></label>
                                                <?php if ($help !== ''): ?><div class="text-muted-2 small"><?= esc($help) ?></div><?php endif; ?>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input type="hidden" name="settings[<?= esc($key, 'attr') ?>]" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" value="1" <?= (string) $value === '1' ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($type === 'textarea'): ?>
                                    <div class="col-12">
                                        <label class="form-label" for="<?= $id ?>"><?= esc($label) ?></label>
                                        <textarea class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" rows="3"><?= esc((string) $value) ?></textarea>
                                        <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                    </div>
                                <?php elseif ($type === 'select'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="<?= $id ?>"><?= esc($label) ?></label>
                                        <select class="form-select" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]">
                                            <?php foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel): ?>
                                                <option value="<?= esc((string) $optValue, 'attr') ?>" <?= (string) $value === (string) $optValue ? 'selected' : '' ?>><?= esc((string) $optLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                    </div>
                                <?php elseif ($type === 'int'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="<?= $id ?>"><?= esc($label) ?></label>
                                        <input type="number" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                        <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                    </div>
                                <?php elseif ($type === 'secret'): ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="<?= $id ?>"><?= esc($label) ?></label>
                                        <input type="password" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" autocomplete="new-password"
                                               placeholder="<?= (string) $value !== '' ? 'Saved — leave blank to keep' : 'Not set' ?>">
                                        <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="col-md-6">
                                        <label class="form-label" for="<?= $id ?>"><?= esc($label) ?></label>
                                        <input type="text" class="form-control" id="<?= $id ?>" name="settings[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                        <?php if ($help !== ''): ?><div class="text-muted-2 small mt-1"><?= esc($help) ?></div><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button class="btn btn-brand" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Save settings</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">About this plugin</h2></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="dst-icon-chip"><i class="fa-solid fa-plug"></i></div>
                    <div class="min-w-0">
                        <div class="fw-650 truncate"><?= esc((string) ($meta['name'] ?? $slug)) ?></div>
                        <div class="text-muted-2 small">v<?= esc((string) ($meta['version'] ?? '1.0.0')) ?> · <?= esc((string) ($meta['author'] ?? 'DESTRA')) ?></div>
                    </div>
                </div>
                <p class="text-muted-2 small"><?= esc((string) ($meta['description'] ?? '')) ?></p>
                <div class="d-flex justify-content-between"><span class="text-muted-2 small">Slug</span><code class="dst-trans-key"><?= esc($slug) ?></code></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
