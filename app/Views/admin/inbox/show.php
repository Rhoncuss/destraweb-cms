<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('inbox') ?>" class="btn btn-soft">Back to inbox</a>
    <form method="post" action="<?= admin_url('inbox/' . (int) $submission['id'] . '/status') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="status" value="unread">
        <button class="btn btn-soft"><i class="fa-regular fa-envelope me-2"></i>Mark unread</button>
    </form>
    <form method="post" action="<?= admin_url('inbox/' . (int) $submission['id'] . '/delete') ?>" data-confirm="Delete this submission permanently?">
        <?= csrf_field() ?>
        <button class="btn btn-danger-soft"><i class="fa-regular fa-trash-can me-2"></i>Delete</button>
    </form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title"><?= esc($submission['form_name'] ?: 'Submission') ?></h2>
                <span class="dst-chip">#<?= (int) $submission['id'] ?></span>
            </div>
            <div class="card-body">
                <?php if ($payload === []): ?>
                    <p class="text-muted-2 mb-0">This submission has no fields recorded.</p>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($payload as $key => $value): ?>
                            <div>
                                <div class="text-muted-2 small text-uppercase" style="letter-spacing:.06em;"><?= esc($labels[(string) $key] ?? (string) $key) ?></div>
                                <div class="fw-650" style="white-space:pre-wrap;">
                                    <?php if (is_array($value)): ?>
                                        <?= esc(implode(', ', array_map(static fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value))) ?>
                                    <?php elseif (filter_var((string) $value, FILTER_VALIDATE_EMAIL)): ?>
                                        <a href="mailto:<?= esc((string) $value, 'attr') ?>"><?= esc((string) $value) ?></a>
                                    <?php else: ?>
                                        <?= esc((string) $value) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Metadata</h2></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Received</dt>
                    <dd class="col-7"><?= esc(date('M j, Y H:i', strtotime((string) $submission['created_at']))) ?></dd>
                    <dt class="col-5 text-muted-2 fw-normal">Status</dt>
                    <dd class="col-7"><span class="dst-badge <?= ! empty($submission['is_read']) ? 'published' : 'new' ?>"><?= ! empty($submission['is_read']) ? 'Read' : 'Unread' ?></span></dd>
                    <dt class="col-5 text-muted-2 fw-normal">Locale</dt>
                    <dd class="col-7"><?= esc((string) ($submission['locale'] ?: '—')) ?></dd>
                    <dt class="col-5 text-muted-2 fw-normal">Page</dt>
                    <dd class="col-7 text-break"><?= esc((string) ($submission['user_agent'] ?: '—')) ?></dd>
                    <dt class="col-5 text-muted-2 fw-normal">IP fingerprint</dt>
                    <dd class="col-7 text-break"><code><?= esc(mb_strimwidth((string) ($submission['ip_hash'] ?? ''), 0, 24, '…')) ?></code></dd>
                </dl>
            </div>
        </div>

        <?php if (! empty($submission['email_to'])): ?>
            <div class="dst-card mt-3">
                <div class="card-body small">
                    <div class="text-muted-2 mb-1">Notification target</div>
                    <div class="fw-650"><?= esc((string) $submission['email_to']) ?></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
