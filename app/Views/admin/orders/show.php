<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('orders') ?>" class="btn btn-soft">Back to orders</a>
    <a href="mailto:<?= esc((string) $order['email'], 'attr') ?>" class="btn btn-soft"><i class="fa-regular fa-envelope me-2"></i>Email customer</a>
    <form method="post" action="<?= admin_url('orders/' . (int) $order['id'] . '/delete') ?>" data-confirm="Delete this order permanently?">
        <?= csrf_field() ?>
        <button class="btn btn-danger-soft"><i class="fa-regular fa-trash-can me-2"></i>Delete</button>
    </form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Message</h2></div>
            <div class="card-body">
                <div class="dst-quote mb-3"><?= esc((string) $order['message']) ?></div>
                <dl class="row mb-0 small">
                    <dt class="col-4 text-muted-2 fw-normal">Customer</dt><dd class="col-8"><?= esc($order['name']) ?></dd>
                    <dt class="col-4 text-muted-2 fw-normal">Email</dt><dd class="col-8"><a href="mailto:<?= esc((string) $order['email'], 'attr') ?>"><?= esc($order['email']) ?></a></dd>
                    <dt class="col-4 text-muted-2 fw-normal">Phone</dt><dd class="col-8"><?= esc((string) ($order['phone'] ?: '—')) ?></dd>
                    <dt class="col-4 text-muted-2 fw-normal">Related page</dt>
                    <dd class="col-8">
                        <a href="<?= esc(content_url(['id' => (int) $order['content_id']], ['slug' => (string) ($order['content_slug'] ?? '')], default_locale()), 'attr') ?>" target="_blank" rel="noopener">
                            <?= esc($order['content_title'] ?: 'Content #' . (int) $order['content_id']) ?>
                        </a>
                    </dd>
                </dl>

                <?php if ($payload !== []): ?>
                    <hr>
                    <div class="d-grid gap-2 small">
                        <?php foreach ($payload as $key => $value): ?>
                            <div class="d-flex gap-2">
                                <span class="text-muted-2" style="min-width:140px;"><?= esc((string) $key) ?></span>
                                <span class="fw-650"><?= esc(is_scalar($value) ? (string) $value : json_encode($value)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Status</h2></div>
            <div class="card-body">
                <form method="post" action="<?= admin_url('orders/' . (int) $order['id'] . '/status') ?>">
                    <?= csrf_field() ?>
                    <select class="form-select mb-3" name="status">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $order['status'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-brand w-100" type="submit">Update order</button>
                </form>
                <hr>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted-2 fw-normal">Received</dt>
                    <dd class="col-6"><?= esc(date('M j, Y H:i', strtotime((string) $order['created_at']))) ?></dd>
                    <dt class="col-6 text-muted-2 fw-normal">Last update</dt>
                    <dd class="col-6"><?= esc($order['updated_at'] ? date('M j, Y H:i', strtotime((string) $order['updated_at'])) : '—') ?></dd>
                    <dt class="col-6 text-muted-2 fw-normal">IP fingerprint</dt>
                    <dd class="col-6"><code><?= esc(mb_strimwidth((string) ($order['ip_hash'] ?? ''), 0, 16, '…')) ?></code></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
