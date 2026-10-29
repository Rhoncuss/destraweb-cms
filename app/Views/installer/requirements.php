<?= $this->extend('installer/layout') ?>

<?= $this->section('content') ?>
<div class="card-head">
    <div class="eyebrow">Step 1 · Environment &amp; database</div>
    <h1>Let's make sure your server is ready</h1>
    <p class="lead">
        DESTRA verifies your PHP runtime and folder permissions, then connects to MySQL and writes a hardened
        <code>.env</code> file. Nothing is seeded until the final step, so you can safely retry.
    </p>
</div>
<div class="card-body">
    <?php if (! empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $message): ?>
                    <li><?= esc($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checklist">
        <?php foreach ($checks as $check): ?>
            <?php
            $state = $check['ok'] ? 'ok' : ($check['required'] ? 'bad' : 'warn');
            $icon  = $check['ok'] ? '✓' : ($check['required'] ? '✕' : '!');
            ?>
            <div class="check <?= $state ?>">
                <span class="dot"><?= $icon ?></span>
                <span class="name"><?= esc($check['label']) ?></span>
                <span class="detail" title="<?= esc($check['detail']) ?>"><?= esc($check['detail']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (! $ready): ?>
        <div class="alert alert-info">
            Fix the failed requirements above, then reload this page. The <strong>themes</strong> and
            <strong>plugins</strong> folders are recommended but optional.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= esc(($base ?? '/') . 'install/database') ?>" autocomplete="off" novalidate>
        <?= csrf_field() ?>

        <div class="grid">
            <div>
                <label class="field" for="hostname">Database host</label>
                <input type="text" id="hostname" name="hostname" value="<?= esc($old['hostname'] ?? $defaults['hostname']) ?>" required>
            </div>
            <div>
                <label class="field" for="port">Port</label>
                <input type="number" id="port" name="port" min="1" max="65535" value="<?= esc($old['port'] ?? $defaults['port']) ?>" required>
            </div>
            <div>
                <label class="field" for="database">Database name <span class="hint">· created if missing</span></label>
                <input type="text" id="database" name="database" value="<?= esc($old['database'] ?? $defaults['database']) ?>" required>
            </div>
            <div>
                <label class="field" for="prefix">Table prefix <span class="hint">· recommended</span></label>
                <input type="text" id="prefix" name="prefix" value="<?= esc($old['prefix'] ?? $defaults['prefix']) ?>">
            </div>
            <div>
                <label class="field" for="username">Database username</label>
                <input type="text" id="username" name="username" value="<?= esc($old['username'] ?? $defaults['username']) ?>" required>
            </div>
            <div>
                <label class="field" for="password">Database password <span class="hint">· blank on default XAMPP</span></label>
                <input type="password" id="password" name="password" value="<?= esc($old['password'] ?? $defaults['password']) ?>" autocomplete="new-password">
            </div>
        </div>

        <div class="actions">
            <span class="hint">Tables are created in the next step. Existing <code>.env</code> is backed up automatically.</span>
            <button type="submit" class="btn" <?= $ready ? '' : 'disabled' ?>>
                Test connection &amp; continue
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
