<?= $this->extend('installer/layout') ?>

<?= $this->section('content') ?>
<div class="card-head">
    <div class="eyebrow">Step 3 · Ready</div>
    <h1>DESTRA CMS is installed</h1>
</div>
<div class="card-body">
    <div class="success">
        <div class="success-badge">✓</div>
        <p class="lead" style="margin:0 auto 18px;">
            Tables, demo content and your Super Admin account were created successfully.
            The installer is now locked and cannot be run again.
        </p>
        <div class="credential">Signed in as <strong><?= esc($adminEmail) ?></strong></div>
        <div class="site-links">
            <a class="btn" href="<?= esc($adminUrl) ?>">Open the dashboard →</a>
            <a class="btn btn-ghost" href="<?= esc($homeUrl) ?>">View the website</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
