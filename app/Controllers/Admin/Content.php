<?php

namespace App\Controllers\Admin;

use Throwable;

class Content extends BaseAdminController
{
    public function index(): string
    {
        $filters = [
            'preview'    => true,
            'page'       => max(1, (int) ($this->request->getGet('page') ?? 1)),
            'per_page'   => 15,
            'type'       => (string) ($this->request->getGet('type') ?? ''),
            'status'     => (string) ($this->request->getGet('status') ?? ''),
            'section_id' => (int) ($this->request->getGet('section') ?? 0),
            'q'          => (string) ($this->request->getGet('q') ?? ''),
            'order'      => (string) ($this->request->getGet('order') ?? 'latest'),
        ];

        if ($filters['status'] === 'trash') {
            $filters['preview'] = false;
        }

        $result = service('contentService')->query($filters);
        $counts = $this->counts();

        if ($filters['status'] === 'trash') {
            $result['items'] = $this->trashedItems($filters['page'], 15);
            $result['total'] = $counts['trash'];
            $result['pages'] = max(1, (int) ceil($counts['trash'] / 15));
        }

        return $this->render('admin/content/index', [
            'pageTitle'    => 'Content',
            'pageSubtitle' => 'Posts, pages and documentation in one place.',
            'navActive'    => $filters['type'] !== '' ? 'content-' . $filters['type'] : 'content',
            'breadcrumbs'  => [['label' => 'Content']],
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'pages'        => $result['pages'],
            'filters'      => $filters,
            'counts'       => $counts,
            'sections'     => $this->sections(),
            'isTrash'      => $filters['status'] === 'trash',
        ]);
    }

    public function create(): string
    {
        return $this->renderForm(null);
    }

    public function edit(int $id): string
    {
        $content = $this->findContent($id);
        if ($content === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->renderForm($content);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function delete(int $id)
    {
        $content = $this->findContent($id, true);
        if ($content === null) {
            return $this->fail('Content not found.', 404);
        }

        db_connect()->table('contents')->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit('content.delete', 'Moved "' . ($content['title'] ?? $id) . '" to trash', 'content', 'content', $id);
        $this->flushCache();

        return redirect()->to(admin_url('content'))->with('success', 'Content moved to trash.');
    }

    public function restore(int $id)
    {
        db_connect()->table('contents')->where('id', $id)->update(['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        return redirect()->to(admin_url('content?status=trash'))->with('success', 'Content restored.');
    }

    public function destroy(int $id)
    {
        if (service('auth')->isSuperAdmin() === false && ! service('auth')->can('content.delete')) {
            return $this->fail('You do not have permission to permanently delete content.', 403);
        }

        $db = db_connect();
        $db->table('content_translations')->where('content_id', $id)->delete();
        $db->table('content_categories')->where('content_id', $id)->delete();
        $db->table('content_tags')->where('content_id', $id)->delete();
        $db->table('content_media')->where('content_id', $id)->delete();
        $db->table('content_related')->where('content_id', $id)->orWhere('related_id', $id)->delete();
        $db->table('contents')->where('id', $id)->delete();

        $this->audit('content.destroy', 'Permanently deleted content #' . $id, 'content', 'content', $id);
        $this->flushCache();

        return redirect()->to(admin_url('content?status=trash'))->with('success', 'Content permanently deleted.');
    }

    public function bulk()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to(admin_url('content'));
        }

        $ids    = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        $action = (string) $this->request->getPost('action');

        if ($ids === [] || ! in_array($action, ['publish', 'draft', 'trash', 'restore'], true)) {
            return redirect()->back()->with('error', 'Select at least one item and a valid action.');
        }

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        if ($action === 'trash') {
            $db->table('contents')->whereIn('id', $ids)->update(['deleted_at' => $now, 'updated_at' => $now]);
        } elseif ($action === 'restore') {
            $db->table('contents')->whereIn('id', $ids)->update(['deleted_at' => null, 'updated_at' => $now]);
        } else {
            $db->table('contents')->whereIn('id', $ids)->update(['status' => $action === 'publish' ? 'published' : 'draft', 'updated_at' => $now]);
            if ($action === 'publish') {
                $db->table('contents')->whereIn('id', $ids)->where('published_at', null)->update(['published_at' => $now]);
            }
        }

        $this->audit('content.bulk', 'Bulk action "' . $action . '" on ' . count($ids) . ' items', 'content', null, null, ['ids' => $ids]);
        $this->flushCache();

        return redirect()->back()->with('success', 'Bulk action applied to ' . count($ids) . ' item(s).');
    }

    private function renderForm(?array $content): string
    {
        $languages = service('localization')->languages();
        $default   = default_locale();
        $isEdit    = $content !== null;

        $translations = [];
        if ($isEdit) {
            try {
                $rows = db_connect()->table('content_translations')->where('content_id', (int) $content['id'])->get()->getResultArray();
                foreach ($rows as $row) {
                    $translations[$row['language_code']] = $row;
                }
            } catch (Throwable) {
            }
        }

        $categories = [];
        $tagList    = '';
        $selectedCategories = [];
        if ($isEdit) {
            $categories = service('contentService')->categories();
            try {
                $selectedCategories = array_map(
                    static fn (array $row): int => (int) $row['category_id'],
                    db_connect()->table('content_categories')->where('content_id', (int) $content['id'])->get()->getResultArray()
                );
                $tagRows = db_connect()->table('content_tags ct')
                    ->select('t.id, tt.name')
                    ->join('tags t', 't.id = ct.tag_id', 'left')
                    ->join('tag_translations tt', 'tt.tag_id = ct.tag_id AND tt.language_code = ' . db_connect()->escape($default), 'left')
                    ->where('ct.content_id', (int) $content['id'])
                    ->get()
                    ->getResultArray();
                $tagList = implode(', ', array_filter(array_column($tagRows, 'name')));
            } catch (Throwable) {
            }
        } else {
            $categories = service('contentService')->categories();
        }

        $sectionId = (int) ($content['section_id'] ?? ($this->request->getGet('section') ?? 0));
        $type      = (string) ($content['type'] ?? ($this->request->getGet('type') ?? 'post'));
        $type      = in_array($type, ['post', 'page', 'doc'], true) ? $type : 'post';

        $sectionList = $this->sections($type);
        $fieldGroups = [];
        foreach ($sectionList as $sectionRow) {
            $fieldGroups[(int) $sectionRow['id']] = $this->customFieldDefinitions((int) $sectionRow['id']);
        }

        $featured = null;
        if ($isEdit && ! empty($content['featured_image_id'])) {
            $featured = service('media')->find((int) $content['featured_image_id']);
        }

        return $this->render('admin/content/form', [
            'pageTitle'    => $isEdit ? 'Edit content' : 'Create content',
            'pageSubtitle' => $isEdit ? 'Update "' . ($translations[$default]['title'] ?? 'Untitled') . '".' : 'Draft a new piece of content in any language.',
            'navActive'    => 'content',
            'breadcrumbs'  => [['label' => 'Content', 'url' => admin_url('content')], ['label' => $isEdit ? 'Edit' : 'Create']],
            'content'      => $content,
            'isEdit'       => $isEdit,
            'languages'    => $languages,
            'defaultLocale' => $default,
            'translations' => $translations,
            'sections'     => $sectionList,
            'sectionId'    => $sectionId,
            'type'         => $type,
            'categories'   => $categories,
            'selectedCategories' => $selectedCategories,
            'tagList'      => $tagList,
            'customFields' => $this->customFieldDefinitions($sectionId),
            'fieldGroups'  => $fieldGroups,
            'customValues' => $this->customValues($isEdit ? (int) $content['id'] : 0, $default),
            'featured'     => $featured,
            'authors'      => $this->authors(),
            'aiEnabled'    => service('ai')->enabled(),
        ]);
    }

    private function save(?int $id)
    {
        $db      = db_connect();
        $default = default_locale();
        $input   = $this->request->getPost();

        $rules = [
            'title' => ['label' => 'Title', 'rules' => 'required|max_length[255]'],
            'type'  => ['label' => 'Type', 'rules' => 'required|in_list[post,page,doc]'],
            'status' => ['label' => 'Status', 'rules' => 'required|in_list[draft,published,private]'],
            'section_id' => ['label' => 'Section', 'rules' => 'permit_empty|integer'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $sanitizer = service('sanitizer');
        $now       = date('Y-m-d H:i:s');

        $existing = $id !== null ? $this->findContent($id, true) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('content'))->with('error', 'Content not found.');
        }

        $status      = (string) $input['status'];
        $publishedAt = $sanitizer->date((string) ($input['published_at'] ?? ''), true);
        if ($publishedAt === null) {
            $publishedAt = $status === 'published' ? $now : null;
        }

        $row = [
            'type'              => (string) $input['type'],
            'section_id'        => (int) ($input['section_id'] ?? 0) ?: null,
            'author_id'         => (int) ($input['author_id'] ?? 0) ?: (int) (($existing['author_id'] ?? 0) ?: $this->admin['id']),
            'status'            => $status,
            'template'          => $sanitizer->text($input['template'] ?? null, 60) ?: null,
            'format'            => 'standard',
            'featured_image_id' => (int) ($input['featured_image_id'] ?? 0) ?: null,
            'icon'              => $sanitizer->icon($input['icon'] ?? null) ?: null,
            'sticky'            => $sanitizer->bool($input['sticky'] ?? false) ? 1 : 0,
            'allow_comments'    => $sanitizer->bool($input['allow_comments'] ?? false) ? 1 : 0,
            'published_at'      => $publishedAt,
            'expires_at'        => $sanitizer->date((string) ($input['expires_at'] ?? ''), true),
            'updated_at'        => $now,
        ];

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('contents')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('contents')->where('id', $id)->update($row);
        }

        if ($id <= 0) {
            return redirect()->back()->withInput()->with('error', 'The content could not be saved.');
        }

        $primarySlug = $this->saveTranslation($id, $default, [
            'title'           => (string) $input['title'],
            'slug'            => (string) ($input['slug'] ?? ''),
            'excerpt'         => (string) ($input['excerpt'] ?? ''),
            'body'            => (string) ($input['body'] ?? ''),
            'seo_title'       => (string) ($input['seo_title'] ?? ''),
            'seo_description' => (string) ($input['seo_description'] ?? ''),
            'seo_keywords'    => (string) ($input['seo_keywords'] ?? ''),
            'canonical_url'   => (string) ($input['canonical_url'] ?? ''),
        ], (int) ($input['featured_image_id'] ?? 0), $input['custom'] ?? []);

        foreach ((array) ($input['translations'] ?? []) as $code => $translation) {
            $code = (string) $code;
            if ($code === $default || ! is_array($translation)) {
                continue;
            }

            $title = trim((string) ($translation['title'] ?? ''));
            if ($title === '') {
                $db->table('content_translations')->where('content_id', $id)->where('language_code', $code)->delete();
                continue;
            }

            $this->saveTranslation($id, $code, array_merge($translation, ['title' => $title]), (int) ($input['featured_image_id'] ?? 0), $input['custom'] ?? []);
        }

        $this->syncCategories($id, (array) ($input['categories'] ?? []));
        $this->syncTags($id, (string) ($input['tags'] ?? ''), $default);
        $this->syncFeaturedMedia($id, (int) ($input['featured_image_id'] ?? 0));

        $this->audit(
            $existing ? 'content.update' : 'content.create',
            ($existing ? 'Updated' : 'Created') . ' content "' . $input['title'] . '"',
            'content',
            'content',
            $id,
            ['slug' => $primarySlug]
        );
        $this->flushCache();

        $redirect = (string) ($input['redirect_to'] ?? '');
        if ($redirect === 'list') {
            return redirect()->to(admin_url('content'))->with('success', 'Content saved.');
        }

        return redirect()->to(admin_url('content/' . $id . '/edit'))->with('success', 'Content saved successfully.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveTranslation(int $contentId, string $code, array $data, int $featuredImageId, array $custom): string
    {
        $db        = db_connect();
        $sanitizer = service('sanitizer');
        $now       = date('Y-m-d H:i:s');

        $title = $sanitizer->text($data['title'] ?? '', 255);
        $slug  = $sanitizer->slug($data['slug'] ?? '' ?: $title, 190);
        $slug  = $this->uniqueSlug($slug, $code, $contentId);

        $customValues = [];
        foreach ($custom as $key => $value) {
            $key = preg_replace('/[^a-z0-9_\-]/i', '', (string) $key);
            if ($key === '') {
                continue;
            }
            $customValues[$key] = is_array($value)
                ? array_map(static fn ($v): string => service('sanitizer')->text($v, 500), $value)
                : $sanitizer->textarea($value, 5000);
        }

        $body      = $sanitizer->html($data['body'] ?? '');
        $excerpt   = trim((string) ($data['excerpt'] ?? ''));
        $reading   = max(1, (int) ceil(str_word_count(strip_tags($body)) / 220));

        $row = [
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => $excerpt !== '' ? $sanitizer->textarea($excerpt, 1000) : null,
            'body'            => $body,
            'seo_title'       => $sanitizer->text($data['seo_title'] ?? null, 255) ?: null,
            'seo_description' => $sanitizer->text($data['seo_description'] ?? null, 500) ?: null,
            'seo_keywords'    => $sanitizer->text($data['seo_keywords'] ?? null, 500) ?: null,
            'canonical_url'   => $sanitizer->url($data['canonical_url'] ?? null) ?: null,
            'og_image_id'     => $featuredImageId > 0 ? $featuredImageId : null,
            'custom_fields'   => $customValues !== [] ? json_encode($customValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'reading_time'    => $reading,
            'updated_at'      => $now,
        ];

        $existing = $db->table('content_translations')->where('content_id', $contentId)->where('language_code', $code)->get()->getRowArray();

        if ($existing) {
            $db->table('content_translations')->where('id', (int) $existing['id'])->update($row);
        } else {
            $row['content_id']    = $contentId;
            $row['language_code'] = $code;
            $row['created_at']    = $now;
            $db->table('content_translations')->insert($row);
        }

        return $slug;
    }

    private function uniqueSlug(string $slug, string $code, int $contentId): string
    {
        $db      = db_connect();
        $reserved = config(\Config\Destra::class)->reservedSlugs;
        $base    = $slug;
        $suffix  = 1;

        $reservedCheck = static function (string $candidate) use ($reserved): bool {
            return in_array(strtolower($candidate), $reserved, true);
        };

        while ($reservedCheck($slug)) {
            $slug = $base . '-' . (++$suffix);
        }

        while (true) {
            $clash = $db->table('content_translations')
                ->where('language_code', $code)
                ->where('slug', $slug)
                ->where('content_id !=', $contentId)
                ->countAllResults();
            if ($clash === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$suffix);
        }
    }

    private function syncCategories(int $contentId, array $categoryIds): void
    {
        $db = db_connect();
        $db->table('content_categories')->where('content_id', $contentId)->delete();

        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if ($ids === []) {
            return;
        }

        $valid = array_column(
            $db->table('categories')->select('id')->whereIn('id', $ids)->where('deleted_at', null)->get()->getResultArray(),
            'id'
        );

        foreach ($valid as $categoryId) {
            $db->table('content_categories')->insert(['content_id' => $contentId, 'category_id' => (int) $categoryId]);
        }
    }

    private function syncTags(int $contentId, string $tags, string $locale): void
    {
        $db = db_connect();
        $db->table('content_tags')->where('content_id', $contentId)->delete();

        $names = array_filter(array_unique(array_map('trim', explode(',', $tags))));
        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }

            $slug = service('sanitizer')->slug($name, 190);
            $tag  = $db->table('tag_translations')->where('language_code', $locale)->where('slug', $slug)->get()->getRowArray();

            if (! $tag) {
                $db->table('tags')->insert(['color' => '#6d5efc', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                $tagId = (int) $db->insertID();
                $db->table('tag_translations')->insert([
                    'tag_id'        => $tagId,
                    'language_code' => $locale,
                    'name'          => mb_substr($name, 0, 120),
                    'slug'          => $slug,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            } else {
                $tagId = (int) $tag['tag_id'];
            }

            $exists = $db->table('content_tags')->where('content_id', $contentId)->where('tag_id', $tagId)->countAllResults();
            if ($exists === 0) {
                $db->table('content_tags')->insert(['content_id' => $contentId, 'tag_id' => $tagId]);
            }
        }
    }

    private function syncFeaturedMedia(int $contentId, int $mediaId): void
    {
        $db = db_connect();
        $db->table('content_media')->where('content_id', $contentId)->where('role', 'featured')->delete();

        if ($mediaId > 0 && $db->table('media')->where('id', $mediaId)->where('deleted_at', null)->countAllResults() > 0) {
            $db->table('content_media')->insert([
                'content_id' => $contentId,
                'media_id'   => $mediaId,
                'role'       => 'featured',
                'sort_order' => 0,
            ]);
        }
    }

    private function findContent(int $id, bool $withTrash = false): ?array
    {
        $builder = db_connect()->table('contents c')
            ->select('c.*')
            ->where('c.id', $id);

        if (! $withTrash) {
            $builder->where('c.deleted_at', null);
        }

        $content = $builder->get()->getRowArray();
        if ($content && empty($content['deleted_at'])) {
            $content['deleted_at'] = null;
        }

        return $content ?: null;
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $db = db_connect();
        $counts = ['all' => 0, 'published' => 0, 'draft' => 0, 'private' => 0, 'trash' => 0];

        try {
            $counts['all']       = (int) $db->table('contents')->where('deleted_at', null)->countAllResults();
            $counts['published'] = (int) $db->table('contents')->where('deleted_at', null)->where('status', 'published')->countAllResults();
            $counts['draft']     = (int) $db->table('contents')->where('deleted_at', null)->where('status', 'draft')->countAllResults();
            $counts['private']   = (int) $db->table('contents')->where('deleted_at', null)->where('status', 'private')->countAllResults();
            $counts['trash']     = (int) $db->table('contents')->where('deleted_at !=', null)->countAllResults();
        } catch (Throwable) {
        }

        return $counts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trashedItems(int $page, int $perPage): array
    {
        try {
            return db_connect()->table('contents c')
                ->select('c.*, t.title, t.slug')
                ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . db_connect()->escape(default_locale()), 'left')
                ->where('c.deleted_at !=', null)
                ->orderBy('c.deleted_at', 'DESC')
                ->limit($perPage, max(0, ($page - 1) * $perPage))
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sections(string $type = ''): array
    {
        $builder = db_connect()->table('sections')->where('is_active', 1)->orderBy('sort_order', 'ASC');
        if ($type !== '') {
            $builder->where('type', $type);
        }

        try {
            return $builder->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function authors(): array
    {
        try {
            return db_connect()->table('users u')
                ->select('u.id, u.name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.deleted_at', null)
                ->whereIn('r.slug', ['super-admin', 'admin', 'editor', 'author'])
                ->orderBy('u.name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customFieldDefinitions(int $sectionId): array
    {
        if ($sectionId <= 0) {
            return [];
        }

        try {
            return db_connect()->table('section_fields')->where('section_id', $sectionId)->orderBy('sort_order', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function customValues(int $contentId, string $locale): array
    {
        if ($contentId <= 0) {
            return [];
        }

        try {
            $row = db_connect()->table('content_translations')
                ->select('custom_fields')
                ->where('content_id', $contentId)
                ->where('language_code', $locale)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return [];
        }

        $decoded = json_decode((string) ($row['custom_fields'] ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }
}
