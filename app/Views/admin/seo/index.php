<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('redirects') ?>" class="btn btn-soft"><i class="fa-solid fa-signs-post me-2"></i>Redirects</a>
    <a href="<?= admin_url('settings?group=seo') ?>" class="btn btn-brand"><i class="fa-solid fa-sliders me-2"></i>SEO settings</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Published entries</span>
            <span class="stat-value"><?= number_format($published) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-check me-1"></i>indexable content</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat stat-sunrise">
            <span class="stat-label">Missing descriptions</span>
            <span class="stat-value"><?= number_format($missingDescription) ?></span>
            <span class="stat-foot"><i class="fa-solid fa-align-left me-1"></i>meta descriptions</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat stat-ocean">
            <span class="stat-label">Missing social image</span>
            <span class="stat-value"><?= number_format($missingImage) ?></span>
            <span class="stat-foot"><i class="fa-regular fa-image me-1"></i>Open Graph covers</span>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dst-stat">
            <span class="stat-label">Active redirects</span>
            <span class="stat-value"><?= number_format($redirects) ?></span>
            <span class="stat-foot"><a class="dst-chip" href="<?= admin_url('redirects') ?>">Manage <i class="fa-solid fa-arrow-right ms-1"></i></a></span>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="dst-card h-100">
            <div class="card-header">
                <h2 class="card-title">Crawler files</h2>
                <button type="button" class="btn btn-soft btn-sm" id="rebuildSeo"><i class="fa-solid fa-arrows-rotate me-1"></i>Rebuild cache</button>
            </div>
            <div class="card-body">
                <div class="dst-link-row">
                    <div class="dst-icon-chip"><i class="fa-solid fa-sitemap"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-650">Multilingual sitemap</div>
                        <a class="text-muted-2 small truncate d-block" href="<?= esc($sitemapUrl, 'attr') ?>" target="_blank" rel="noopener"><?= esc($sitemapUrl) ?></a>
                    </div>
                    <span class="dst-badge <?= $sitemapEnabled ? 'published' : 'draft' ?>"><?= $sitemapEnabled ? 'Enabled' : 'Disabled' ?></span>
                </div>
                <div class="dst-link-row">
                    <div class="dst-icon-chip"><i class="fa-solid fa-robot"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-650">robots.txt</div>
                        <a class="text-muted-2 small truncate d-block" href="<?= esc($robotsUrl, 'attr') ?>" target="_blank" rel="noopener"><?= esc($robotsUrl) ?></a>
                    </div>
                    <span class="dst-chip">auto</span>
                </div>
                <div class="dst-link-row">
                    <div class="dst-icon-chip"><i class="fa-solid fa-rss"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-650">RSS feed</div>
                        <a class="text-muted-2 small truncate d-block" href="<?= esc($feedUrl, 'attr') ?>" target="_blank" rel="noopener"><?= esc($feedUrl) ?></a>
                    </div>
                    <span class="dst-badge <?= $rssEnabled ? 'published' : 'draft' ?>"><?= $rssEnabled ? 'Enabled' : 'Disabled' ?></span>
                </div>
                <div class="dst-link-row">
                    <div class="dst-icon-chip"><i class="fa-solid fa-link"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="fw-650">Canonical &amp; hreflang</div>
                        <div class="text-muted-2 small">Canonical <?= $canonical ? 'on' : 'off' ?> · hreflang <?= $hreflang ? 'on' : 'off' ?></div>
                    </div>
                    <button class="btn btn-soft btn-sm" type="button" data-dst-copy="<?= esc($sitemapUrl, 'attr') ?>"><i class="fa-regular fa-copy"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dst-card h-100">
            <div class="card-header"><h2 class="card-title">Search appearance</h2></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted-2 small mb-1">Title template</div>
                    <code class="dst-trans-key"><?= esc($titleTemplate) ?></code>
                </div>
                <div class="mb-3">
                    <div class="text-muted-2 small mb-1">Example result</div>
                    <div class="dst-seo-preview">
                        <div class="seo-url"><?= esc(parse_url(base_url(), PHP_URL_HOST) ?: 'example.com') ?> › article</div>
                        <div class="seo-title"><?= esc(str_replace(['{title}', '{site}'], ['How we ship faster', setting('general.site_name', 'DESTRA')], $titleTemplate)) ?></div>
                        <div class="seo-desc"><?= esc(mb_substr((string) setting('seo.description', ''), 0, 155)) ?></div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2 small">Analytics ID</span><span class="fw-650"><?= $analyticsId !== '' ? esc($analyticsId) : '—' ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted-2 small">Tag Manager</span><span class="fw-650"><?= $gtmId !== '' ? esc($gtmId) : '—' ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted-2 small">Missing SEO titles</span><span class="fw-650"><?= number_format($missingTitle) ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="dst-card">
    <div class="card-header">
        <h2 class="card-title">Metadata coverage</h2>
        <span class="text-muted-2 small">Latest 20 published entries in <?= esc(strtoupper($default = default_locale())) ?></span>
    </div>
    <?php if ($rows === []): ?>
        <div class="dst-empty">
            <i class="fa-solid fa-magnifying-glass-chart"></i>
            <h3>No published content yet</h3>
            <p>Publish your first article and DESTRA will start tracking metadata coverage here.</p>
            <a class="btn btn-brand" href="<?= admin_url('content/create') ?>">Write something</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Title</th><th class="text-center">SEO title</th><th class="text-center">Description</th><th class="text-center">Social image</th><th class="text-center">Canonical</th><th>Updated</th><th class="text-end"></th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="min-w-0">
                            <div class="fw-650 truncate" style="max-width:320px;"><?= esc((string) $row['title']) ?></div>
                            <div class="text-muted-2 small truncate"><?= esc(content_url(['id' => (int) $row['id'], 'type' => (string) $row['type'], 'section_slug' => (string) ($row['section_slug'] ?? '')], ['slug' => (string) $row['slug']])) ?></div>
                        </td>
                        <td class="text-center"><?= trim((string) $row['seo_title']) !== '' ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-regular fa-circle text-muted-2"></i>' ?></td>
                        <td class="text-center"><?= trim((string) $row['seo_description']) !== '' ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-regular fa-circle text-muted-2"></i>' ?></td>
                        <td class="text-center"><?= (int) ($row['og_image_id'] ?? 0) > 0 ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-regular fa-circle text-muted-2"></i>' ?></td>
                        <td class="text-center"><?= trim((string) $row['canonical_url']) !== '' ? '<span class="dst-chip">custom</span>' : '<span class="dst-chip">auto</span>' ?></td>
                        <td class="dst-meta"><?= esc(human_date((string) ($row['translation_updated'] ?: $row['content_updated']))) ?></td>
                        <td class="text-end"><a class="btn btn-soft btn-sm" href="<?= admin_url('content/' . (int) $row['id'] . '/edit') ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var button = document.getElementById('rebuildSeo');
    if (!button) { return; }
    button.addEventListener('click', function () {
        button.disabled = true;
        Destra.postForm('<?= admin_url('seo/rebuild') ?>', {}).then(function (json) {
            Destra.toast(json.success === false ? 'error' : 'success', json.message || json.error || 'Rebuilt.');
        }).catch(function () {
            Destra.toast('error', 'Network error — please retry.');
        }).then(function () { button.disabled = false; });
    });
})();
</script>
<?= $this->endSection() ?>
