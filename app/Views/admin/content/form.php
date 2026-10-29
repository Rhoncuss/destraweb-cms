<?php
$primary = $translations[$defaultLocale] ?? [];
$old = static function (string $key, $fallback = '') {
    $value = old($key);
    return $value === null || $value === '' ? $fallback : $value;
};
$slugValue = (string) ($primary['slug'] ?? '');
$titleValue = (string) ($primary['title'] ?? '');
$editorEngine = (string) setting('general.editor', 'tinymce');
$editorEngine = in_array($editorEngine, ['tinymce', 'ckeditor', 'summernote', 'textarea'], true) ? $editorEngine : 'tinymce';
$editorAttr = $editorEngine === 'textarea' ? '' : ' data-editor="' . $editorEngine . '"';
$status = (string) ($content['status'] ?? 'draft');
$publishedAt = ! empty($content['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $content['published_at'])) : '';
$expiresAt = ! empty($content['expires_at']) ? date('Y-m-d\TH:i', strtotime((string) $content['expires_at'])) : '';
$featuredUrl = $featured ? service('media')->url($featured, 'thumb') : '';
$actionUrl = $isEdit ? admin_url('content/' . (int) $content['id'] . '/update') : admin_url('content/store');
?>
<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <div class="d-inline-flex gap-2 flex-wrap">
        <?php if ($isEdit && $status === 'published'): ?>
            <a href="<?= content_url($content ?? [], $primary) ?>" target="_blank" rel="noopener" class="btn btn-soft">
                <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Preview
            </a>
        <?php endif; ?>
        <a href="<?= admin_url('content') ?>" class="btn btn-soft">Back to list</a>
        <button form="contentForm" type="submit" class="btn btn-brand"><i class="fa-solid fa-floppy-disk me-2"></i><?= $isEdit ? 'Save changes' : 'Publish content' ?></button>
    </div>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form id="contentForm" method="post" action="<?= esc($actionUrl, 'attr') ?>" class="editor-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= esc($type, 'attr') ?>">

    <div class="d-grid gap-3">
        <div class="dst-card">
            <div class="card-body">
                <label class="form-label" for="title">Title</label>
                <input type="text" class="form-control form-control-lg mb-2" id="title" name="title" maxlength="255"
                       value="<?= esc($old('title', $titleValue)) ?>" placeholder="A clear, specific headline" required
                       data-slug-source="#slug" autocomplete="off" style="font-size:20px;font-weight:600;">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="text-muted-2 small"><?= base_url('/') ?></span>
                    <input type="text" class="form-control form-control-sm" name="slug" id="slug" maxlength="190"
                           value="<?= esc($old('slug', $slugValue)) ?>" placeholder="auto-generated-from-title" style="max-width:340px;">
                    <button type="button" class="btn btn-soft btn-sm" data-regenerate-slug><i class="fa-solid fa-rotate me-1"></i>Generate</button>
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <?php foreach ($languages as $index => $language): ?>
                        <?php
                        $code = (string) $language['code'];
                        $hasTranslation = isset($translations[$code]) && trim((string) $translations[$code]['title']) !== '';
                        ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $code === $defaultLocale ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#lang-<?= esc($code, 'attr') ?>" type="button" role="tab">
                                <?= esc($language['native_name'] ?: $language['name']) ?>
                                <?php if ($code === $defaultLocale): ?><span class="badge text-bg-light ms-1" style="font-size:9px;">default</span><?php endif; ?>
                                <?php if ($code !== $defaultLocale && $hasTranslation): ?><i class="fa-solid fa-circle-check ms-1" style="font-size:10px;color:var(--dst-accent);"></i><?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content">
                    <?php foreach ($languages as $language): ?>
                        <?php
                        $code = (string) $language['code'];
                        $tr = $translations[$code] ?? [];
                        $isDefault = $code === $defaultLocale;
                        ?>
                        <div class="tab-pane fade <?= $isDefault ? 'show active' : '' ?>" id="lang-<?= esc($code, 'attr') ?>" role="tabpanel">
                            <?php if (! $isDefault): ?>
                                <div class="dst-placeholder mb-3" style="padding:12px;text-align:start;">
                                    <i class="fa-solid fa-language me-2"></i>
                                    Translate this content into <?= esc($language['name']) ?>. Leave the title empty to keep it unpublished in this language.
                                </div>
                                <label class="form-label" for="tr-<?= esc($code, 'attr') ?>-title">Title (<?= esc($code) ?>)</label>
                                <input type="text" class="form-control mb-2" id="tr-<?= esc($code, 'attr') ?>-title"
                                       name="translations[<?= esc($code, 'attr') ?>][title]" maxlength="255"
                                       value="<?= esc((string) ($tr['title'] ?? '')) ?>">
                                <label class="form-label" for="tr-<?= esc($code, 'attr') ?>-slug">Slug</label>
                                <input type="text" class="form-control mb-2" id="tr-<?= esc($code, 'attr') ?>-slug"
                                       name="translations[<?= esc($code, 'attr') ?>][slug]" maxlength="190"
                                       value="<?= esc((string) ($tr['slug'] ?? '')) ?>" placeholder="kosongkan untuk otomatis">
                                <label class="form-label" for="tr-<?= esc($code, 'attr') ?>-excerpt">Excerpt</label>
                                <textarea class="form-control mb-2" rows="2" id="tr-<?= esc($code, 'attr') ?>-excerpt"
                                          name="translations[<?= esc($code, 'attr') ?>][excerpt]"><?= esc((string) ($tr['excerpt'] ?? '')) ?></textarea>
                                <label class="form-label" for="tr-<?= esc($code, 'attr') ?>-body">Body</label>
                                <textarea class="form-control" rows="14" id="tr-<?= esc($code, 'attr') ?>-body"
                                          name="translations[<?= esc($code, 'attr') ?>][body]"<?= $editorAttr ?>><?= esc((string) ($tr['body'] ?? '')) ?></textarea>
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <label class="form-label mb-0" for="excerpt">Excerpt</label>
                                    <?php if (! empty($aiEnabled)): ?>
                                        <button type="button" class="btn btn-sm btn-light" data-ai-action="summary" data-ai-target="#excerpt">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Summarise
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <textarea class="form-control mb-3 mt-2" rows="3" id="excerpt" name="excerpt" maxlength="1000"
                                          placeholder="A short summary shown in listings and social cards."><?= esc($old('excerpt', (string) ($primary['excerpt'] ?? ''))) ?></textarea>

                                <label class="form-label" for="body">Body</label>
                                <textarea class="form-control" rows="20" id="body" name="body"<?= $editorAttr ?>><?= esc($old('body', (string) ($primary['body'] ?? ''))) ?></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Custom fields</h2>
                <span class="dst-chip" data-section-label>
                    <?php
                    $activeSectionName = '';
                    foreach ($sections as $sectionRow) {
                        if ((int) $sectionRow['id'] === (int) $sectionId) {
                            $activeSectionName = (string) $sectionRow['name'];
                        }
                    }
                    echo esc($activeSectionName !== '' ? $activeSectionName : 'No section selected');
                    ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($fieldGroups)): ?>
                    <p class="text-muted-2 mb-0">No custom fields defined yet. Add fields under <a href="<?= admin_url('sections') ?>">Sections</a>.</p>
                <?php else: ?>
                    <?php foreach ($fieldGroups as $groupId => $groupFields): ?>
                        <fieldset data-section-group="<?= (int) $groupId ?>" <?= (int) $groupId === (int) $sectionId ? '' : 'disabled hidden' ?>>
                            <?php if (empty($groupFields)): ?>
                                <p class="text-muted-2 mb-0">This section has no custom fields.</p>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($groupFields as $field): ?>
                                        <?php
                                        $key = (string) $field['field_key'];
                                        $value = old('custom.' . $key, $customValues[$key] ?? $field['default_value']);
                                        $options = json_decode((string) ($field['options'] ?? ''), true);
                                        ?>
                                        <div class="col-md-6">
                                            <label class="form-label" for="cf-<?= esc($key, 'attr') ?>"><?= esc($field['name']) ?><?= ! empty($field['is_required']) ? ' *' : '' ?></label>
                                            <?php if ($field['type'] === 'textarea'): ?>
                                                <textarea class="form-control" rows="3" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]"><?= esc((string) $value) ?></textarea>
                                            <?php elseif ($field['type'] === 'editor' || $field['type'] === 'rich'): ?>
                                                <textarea class="form-control" rows="5" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]"<?= $editorAttr ?>><?= esc((string) $value) ?></textarea>
                                            <?php elseif ($field['type'] === 'select' && is_array($options)): ?>
                                                <select class="form-select" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]">
                                                    <option value="">—</option>
                                                    <?php foreach ($options as $option): ?>
                                                        <option value="<?= esc((string) $option, 'attr') ?>" <?= (string) $value === (string) $option ? 'selected' : '' ?>><?= esc(ucfirst((string) $option)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                                <div class="form-check form-switch mt-2">
                                                    <input type="hidden" name="custom[<?= esc($key, 'attr') ?>]" value="0">
                                                    <input class="form-check-input" type="checkbox" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="1" <?= (string) $value === '1' ? 'checked' : '' ?>>
                                                </div>
                                            <?php elseif ($field['type'] === 'date'): ?>
                                                <input type="date" class="form-control" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                            <?php elseif ($field['type'] === 'number'): ?>
                                                <input type="number" class="form-control" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                            <?php elseif ($field['type'] === 'url'): ?>
                                                <input type="url" class="form-control" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                            <?php elseif ($field['type'] === 'color'): ?>
                                                <input type="color" class="form-control form-control-color" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value ?: '#6d5efc', 'attr') ?>">
                                            <?php else: ?>
                                                <input type="text" class="form-control" id="cf-<?= esc($key, 'attr') ?>" name="custom[<?= esc($key, 'attr') ?>]" value="<?= esc((string) $value, 'attr') ?>">
                                            <?php endif; ?>
                                            <?php if (! empty($field['description'])): ?><div class="form-text"><?= esc($field['description']) ?></div><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </fieldset>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header">
                <h2 class="card-title">Search engine preview</h2>
                <?php if (! empty($aiEnabled)): ?>
                    <button type="button" class="btn btn-sm btn-light" data-ai-action="meta">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generate with AI
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="seo_title">SEO title <span class="text-muted-2">· <span data-counter="#seoTitleCount">0</span></span></label>
                        <input type="text" class="form-control" id="seo_title" name="seo_title" maxlength="255" data-counter="#seoTitleCount"
                               value="<?= esc($old('seo_title', (string) ($primary['seo_title'] ?? ''))) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="seo_description">Meta description <span class="text-muted-2">· <span data-counter="#seoDescCount">0</span></span></label>
                        <textarea class="form-control" rows="2" id="seo_description" name="seo_description" maxlength="500" data-counter="#seoDescCount"><?= esc($old('seo_description', (string) ($primary['seo_description'] ?? ''))) ?></textarea>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <label class="form-label mb-0" for="seo_keywords">Keywords</label>
                            <?php if (! empty($aiEnabled)): ?>
                                <button type="button" class="btn btn-sm btn-light" data-ai-action="tags" data-ai-target="#seo_keywords">
                                    <i class="fa-solid fa-tags me-1"></i>Suggest
                                </button>
                            <?php endif; ?>
                        </div>
                        <input type="text" class="form-control mt-2" id="seo_keywords" name="seo_keywords" maxlength="500"
                               value="<?= esc($old('seo_keywords', (string) ($primary['seo_keywords'] ?? ''))) ?>" placeholder="comma, separated, keywords">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="canonical_url">Canonical URL</label>
                        <input type="url" class="form-control" id="canonical_url" name="canonical_url" maxlength="500"
                               value="<?= esc($old('canonical_url', (string) ($primary['canonical_url'] ?? ''))) ?>" placeholder="https://…">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <aside class="editor-side">
        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Publish</h2></div>
            <div class="card-body">
                <label class="form-label" for="status">Status</label>
                <select class="form-select mb-3" id="status" name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="private" <?= $status === 'private' ? 'selected' : '' ?>>Private</option>
                </select>

                <label class="form-label" for="published_at">Publish date</label>
                <input type="datetime-local" class="form-control mb-1" id="published_at" name="published_at" value="<?= esc($publishedAt, 'attr') ?>">
                <div class="form-text mb-3">Leave empty to publish immediately. A future date schedules the post.</div>

                <label class="form-label" for="expires_at">Expires</label>
                <input type="datetime-local" class="form-control mb-3" id="expires_at" name="expires_at" value="<?= esc($expiresAt, 'attr') ?>">

                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="sticky" value="0">
                    <input class="form-check-input" type="checkbox" id="sticky" name="sticky" value="1" <?= ! empty($content['sticky']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sticky">Pin to the top</label>
                </div>
                <div class="form-check form-switch">
                    <input type="hidden" name="allow_comments" value="0">
                    <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" value="1" <?= ! $isEdit || ! empty($content['allow_comments']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="allow_comments">Allow comments</label>
                </div>

                <div class="dst-divider"></div>

                <div class="d-grid gap-2">
                    <button class="btn btn-brand" type="submit"><?= $isEdit ? 'Save changes' : 'Create content' ?></button>
                    <button class="btn btn-soft" type="submit" name="redirect_to" value="list">Save &amp; close</button>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Organization</h2></div>
            <div class="card-body">
                <label class="form-label" for="section_id">Section</label>
                <select class="form-select mb-3" id="section_id" name="section_id">
                    <option value="">— None —</option>
                    <?php foreach ($sections as $sectionRow): ?>
                        <option value="<?= (int) $sectionRow['id'] ?>" <?= (int) $sectionId === (int) $sectionRow['id'] ? 'selected' : '' ?>><?= esc($sectionRow['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label" for="template">Template</label>
                <input type="text" class="form-control mb-3" id="template" name="template" maxlength="60"
                       value="<?= esc((string) ($content['template'] ?? '')) ?>" placeholder="default">

                <?php if (service('auth')->isSuperAdmin() || service('auth')->can('content.publish')): ?>
                    <label class="form-label" for="author_id">Author</label>
                    <select class="form-select" id="author_id" name="author_id">
                        <?php foreach ($authors as $author): ?>
                            <option value="<?= (int) $author['id'] ?>" <?= (int) ($content['author_id'] ?? $adminUser['id']) === (int) $author['id'] ? 'selected' : '' ?>><?= esc($author['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Featured image</h2></div>
            <div class="card-body">
                <input type="hidden" name="featured_image_id" id="featuredImageId" value="<?= (int) ($content['featured_image_id'] ?? 0) ?>">
                <div class="dst-placeholder p-0 overflow-hidden" id="featuredPreview" style="<?= $featuredUrl ? 'border:0;' : '' ?>">
                    <?php if ($featuredUrl): ?>
                        <img src="<?= esc($featuredUrl, 'attr') ?>" alt="" style="width:100%;height:150px;object-fit:cover;display:block;">
                    <?php else: ?>
                        <div class="py-4"><i class="fa-regular fa-image d-block mb-2" style="font-size:26px;"></i>No image selected</div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-soft btn-sm flex-grow-1" data-media-picker="#featuredImageId" data-media-preview="#featuredPreview"><i class="fa-regular fa-image me-1"></i>Choose</button>
                    <button type="button" class="btn btn-soft btn-sm" data-media-clear="#featuredImageId" data-media-preview="#featuredPreview"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Categories</h2></div>
            <div class="card-body" style="max-height:240px;overflow:auto;">
                <?php if (empty($categories)): ?>
                    <p class="text-muted-2 small mb-0">No categories yet.</p>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>" id="cat-<?= (int) $category['id'] ?>"
                                   <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat-<?= (int) $category['id'] ?>" style="font-size:13px;"><?= esc($category['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dst-card">
            <div class="card-header"><h2 class="card-title">Tags</h2></div>
            <div class="card-body">
                <input type="text" class="form-control" name="tags" value="<?= esc($tagList) ?>" placeholder="design, workflow, security">
                <div class="form-text">Separate tags with commas. New tags are created automatically.</div>
            </div>
        </div>
    </aside>
</form>

<?= $this->include('admin/partials/media-picker') ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= admin_asset('editor.js') ?>"></script>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var title = document.getElementById('title');
    var slug = document.getElementById('slug');
    var regenerate = document.querySelector('[data-regenerate-slug]');
    if (regenerate && title && slug) {
        regenerate.addEventListener('click', function () {
            slug.value = Destra.slugify(title.value);
        });
    }

    var sectionSelect = document.getElementById('section_id');
    var groups = document.querySelectorAll('[data-section-group]');
    function syncSections() {
        var current = sectionSelect ? sectionSelect.value : '';
        groups.forEach(function (group) {
            var match = group.getAttribute('data-section-group') === current;
            group.disabled = !match;
            group.hidden = !match;
        });
        var label = document.querySelector('[data-section-label]');
        if (label && sectionSelect) {
            var option = sectionSelect.options[sectionSelect.selectedIndex];
            label.textContent = option && option.value ? option.text : 'No section selected';
        }
    }
    if (sectionSelect) {
        sectionSelect.addEventListener('change', syncSections);
        syncSections();
    }

    var aiButtons = document.querySelectorAll('[data-ai-action]');
    if (aiButtons.length) {
        var aiUrl = <?= json_encode(admin_url('ai/generate')) ?>;
        var titleField = document.getElementById('title');
        var bodyField = document.getElementById('body');

        function bodyHtml() {
            if (window.tinymce && window.tinymce.get('body')) {
                return window.tinymce.get('body').getContent();
            }
            return bodyField ? bodyField.value : '';
        }

        function fill(target, value) {
            if (!target) { return; }
            target.value = value;
            target.dispatchEvent(new Event('input', { bubbles: true }));
        }

        aiButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-ai-action');
                var original = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Working…';

                Destra.postForm(aiUrl, {
                    action: action,
                    title: titleField ? titleField.value : '',
                    body: bodyHtml()
                }).then(function (json) {
                    if (!json.success) {
                        Destra.toast('error', json.error || 'The AI request failed.');
                        return;
                    }

                    if (action === 'meta') {
                        fill(document.getElementById('seo_title'), json.title || '');
                        fill(document.getElementById('seo_description'), json.description || '');
                        fill(document.getElementById('seo_keywords'), json.keywords || '');
                    } else if (action === 'tags') {
                        fill(document.getElementById('seo_keywords'), json.text || '');
                        var tags = document.querySelector('input[name="tags"]');
                        if (tags && tags.value.trim() === '') {
                            fill(tags, json.text || '');
                        }
                    } else {
                        var target = document.querySelector(button.getAttribute('data-ai-target'));
                        fill(target, json.text || '');
                    }

                    Destra.toast('success', json.message || 'Done.');
                }).catch(function () {
                    Destra.toast('error', 'Network error — please retry.');
                }).then(function () {
                    button.disabled = false;
                    button.innerHTML = original;
                });
            });
        });
    }
})();
</script>
<?= $this->endSection() ?>
