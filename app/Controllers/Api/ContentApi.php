<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class ContentApi extends BaseApiController
{
    public function index(): ResponseInterface
    {
        return $this->listing();
    }

    public function posts(): ResponseInterface
    {
        return $this->listing('post');
    }

    public function pages(): ResponseInterface
    {
        return $this->listing('page');
    }

    public function docs(): ResponseInterface
    {
        return $this->listing('doc');
    }

    public function show(string $idOrSlug): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $locale = $this->localeParam();
        $db     = db_connect();

        $builder = $db->table('contents c')
            ->select('c.*, t.title, t.slug, t.excerpt, t.body, t.seo_title, t.seo_description, t.seo_keywords, t.custom_fields, t.reading_time, s.slug AS section_slug, s.name AS section_name')
            ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape($locale), 'left')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.deleted_at', null);

        if (ctype_digit($idOrSlug)) {
            $builder->where('c.id', (int) $idOrSlug);
        } else {
            $builder->where('t.slug', service('sanitizer')->slug($idOrSlug, 190));
        }

        $row = $builder->get()->getRowArray();
        if (! $row) {
            return $this->fail('Content not found.', 404, 'not_found');
        }

        if (! $this->canSeeStatus((string) $row['status'])) {
            return $this->fail('Content not found.', 404, 'not_found');
        }

        return $this->ok($this->present($row, true));
    }

    public function store(): ResponseInterface
    {
        if (($guard = $this->guard('content:write')) !== null) {
            return $guard;
        }

        $title = trim((string) $this->input('title', ''));
        if ($title === '') {
            return $this->fail('The title field is required.', 422, 'validation_failed', ['title' => 'Title is required.']);
        }

        $status = (string) $this->input('status', 'draft');
        if (! in_array($status, ['draft', 'published', 'private'], true)) {
            return $this->fail('Invalid status value.', 422, 'validation_failed');
        }
        if ($status === 'published' && ($guard = $this->guard('content:publish')) !== null) {
            return $guard;
        }

        $type = (string) $this->input('type', 'post');
        if (! in_array($type, ['post', 'page', 'doc'], true)) {
            return $this->fail('Invalid content type.', 422, 'validation_failed');
        }

        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $db->table('contents')->insert([
            'type'              => $type,
            'section_id'        => (int) $this->input('section_id', 0) ?: $this->defaultSectionId($type),
            'author_id'         => \App\Libraries\ApiContext::userId(),
            'status'            => $status,
            'template'          => $type === 'page' ? 'page' : null,
            'format'            => 'standard',
            'featured_image_id' => (int) $this->input('featured_image_id', 0) ?: null,
            'sticky'            => service('sanitizer')->bool($this->input('sticky', false)) ? 1 : 0,
            'allow_comments'    => service('sanitizer')->bool($this->input('allow_comments', true)) ? 1 : 0,
            'published_at'      => $status === 'published' ? $now : null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $id = (int) $db->insertID();
        if ($id <= 0) {
            return $this->fail('The content could not be saved.', 500, 'server_error');
        }

        $this->saveTranslations($id, $title, $status === 'published' ? $now : null);
        $this->syncTaxonomy($id, $this->localeParam());

        $this->audit($id, 'content.create');

        return $this->created($this->fetchById($id), ['url' => base_url('api/v1/content/' . $id)]);
    }

    public function update(int $id): ResponseInterface
    {
        if (($guard = $this->guard('content:write')) !== null) {
            return $guard;
        }

        $db  = db_connect();
        $row = $db->table('contents')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if (! $row) {
            return $this->fail('Content not found.', 404, 'not_found');
        }

        $data = [];
        if (array_key_exists('status', $this->payload) || $this->request->getPost('status') !== null) {
            $status = (string) $this->input('status');
            if (! in_array($status, ['draft', 'published', 'private'], true)) {
                return $this->fail('Invalid status value.', 422, 'validation_failed');
            }
            if ($status === 'published' && ($guard = $this->guard('content:publish')) !== null) {
                return $guard;
            }
            $data['status'] = $status;
            if ($status === 'published' && empty($row['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        }
        foreach (['section_id', 'featured_image_id'] as $field) {
            if (array_key_exists($field, $this->payload)) {
                $data[$field] = (int) $this->input($field) ?: null;
            }
        }
        foreach (['sticky', 'allow_comments'] as $field) {
            if (array_key_exists($field, $this->payload)) {
                $data[$field] = service('sanitizer')->bool($this->input($field)) ? 1 : 0;
            }
        }
        if ($data !== []) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $db->table('contents')->where('id', $id)->update($data);
        }

        if ($this->translationInput() !== []) {
            $title = trim((string) ($this->translationInput()['title'] ?? ''));
            if ($title === '') {
                $current = $db->table('content_translations')->where('content_id', $id)->where('language_code', $this->localeParam())->get()->getRowArray();
                $title = (string) ($current['title'] ?? 'Untitled');
            }
            $this->saveTranslations($id, $title, $row['published_at'] ?? null, true);
        }

        if ($this->payload !== [] && (array_key_exists('categories', $this->payload) || array_key_exists('tags', $this->payload))) {
            $this->syncTaxonomy($id, $this->localeParam());
        }

        $this->audit($id, 'content.update');

        return $this->ok($this->fetchById($id));
    }

    public function destroy(int $id): ResponseInterface
    {
        if (($guard = $this->guard('content:write')) !== null) {
            return $guard;
        }

        $db  = db_connect();
        $row = $db->table('contents')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        if (! $row) {
            return $this->fail('Content not found.', 404, 'not_found');
        }

        $db->table('contents')->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit($id, 'content.delete');

        return $this->ok(['id' => $id, 'deleted' => true]);
    }

    private function listing(?string $type = null): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $locale = $this->localeParam();
        $status = (string) $this->request->getGet('status') ?: 'published';

        if (! in_array($status, ['published', 'draft', 'private'], true)) {
            return $this->fail('Invalid status filter.', 422, 'validation_failed');
        }
        if ($status !== 'published' && ! \App\Libraries\ApiContext::can('content:write')) {
            return $this->fail('Reading unpublished content requires the "content:write" permission.', 403, 'forbidden');
        }

        $builder = $db->table('contents c')
            ->select('c.*, t.title, t.slug, t.excerpt, t.body, t.seo_title, t.seo_description, t.seo_keywords, t.custom_fields, t.reading_time, s.slug AS section_slug, s.name AS section_name')
            ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape($locale), 'left')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.status', $status)
            ->where('c.deleted_at', null);

        $type = $type ?? (string) $this->request->getGet('type');
        if (in_array($type, ['post', 'page', 'doc'], true)) {
            $builder->where('c.type', $type);
        }

        $section = trim((string) $this->request->getGet('section'));
        if ($section !== '') {
            $builder->where('s.slug', service('sanitizer')->slug($section, 190));
        }

        $category = trim((string) $this->request->getGet('category'));
        if ($category !== '') {
            $builder->where(
                'EXISTS (SELECT 1 FROM ' . $db->prefixTable('content_categories') . ' cc'
                . ' JOIN ' . $db->prefixTable('category_translations') . ' ct ON ct.category_id = cc.category_id'
                . ' WHERE cc.content_id = c.id AND ct.language_code = ' . $db->escape($locale)
                . ' AND ct.slug = ' . $db->escape(service('sanitizer')->slug($category, 190)) . ')',
                null,
                false
            );
        }

        $tag = trim((string) $this->request->getGet('tag'));
        if ($tag !== '') {
            $builder->where(
                'EXISTS (SELECT 1 FROM ' . $db->prefixTable('content_tags') . ' cgt'
                . ' JOIN ' . $db->prefixTable('tag_translations') . ' tt ON tt.tag_id = cgt.tag_id'
                . ' WHERE cgt.content_id = c.id AND tt.language_code = ' . $db->escape($locale)
                . ' AND tt.slug = ' . $db->escape(service('sanitizer')->slug($tag, 190)) . ')',
                null,
                false
            );
        }

        $query = trim((string) $this->request->getGet('q'));
        if ($query !== '') {
            $like = '%' . $db->escapeLikeString(mb_substr($query, 0, 120)) . '%';
            $builder->groupStart()
                ->like('t.title', $like)
                ->orLike('t.excerpt', $like)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults(false);

        $items = $builder->orderBy('c.published_at', 'DESC')
            ->orderBy('c.id', 'DESC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        return $this->ok(array_map(fn (array $row): array => $this->present($row), $items), $this->pagination((int) $total));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(array $row, bool $withTranslations = false): array
    {
        $locale = $this->localeParam();

        $data = [
            'id'               => (int) $row['id'],
            'type'             => (string) $row['type'],
            'status'           => (string) $row['status'],
            'title'            => (string) ($row['title'] ?? ''),
            'slug'             => (string) ($row['slug'] ?? ''),
            'excerpt'          => $row['excerpt'] !== null ? (string) $row['excerpt'] : null,
            'body'             => (string) ($row['body'] ?? ''),
            'language'         => $locale,
            'section'          => $row['section_slug'] !== null ? ['slug' => (string) $row['section_slug'], 'name' => (string) $row['section_name']] : null,
            'url'              => content_url($row, ['slug' => $row['slug'] ?? ''], $locale),
            'featured_image'   => ! empty($row['featured_image_id']) ? media_url((int) $row['featured_image_id']) : null,
            'featured_image_id'=> ! empty($row['featured_image_id']) ? (int) $row['featured_image_id'] : null,
            'sticky'           => (int) $row['sticky'] === 1,
            'allow_comments'   => (int) $row['allow_comments'] === 1,
            'views'            => (int) $row['views'],
            'reading_time'     => (int) ($row['reading_time'] ?? 0),
            'seo'              => [
                'title'       => $row['seo_title'] !== null ? (string) $row['seo_title'] : null,
                'description' => $row['seo_description'] !== null ? (string) $row['seo_description'] : null,
                'keywords'    => $row['seo_keywords'] !== null ? (string) $row['seo_keywords'] : null,
            ],
            'published_at'     => $row['published_at'],
            'updated_at'       => $row['updated_at'],
            'created_at'       => $row['created_at'] ?? null,
        ];

        $custom = json_decode((string) ($row['custom_fields'] ?? ''), true);
        $data['custom_fields'] = is_array($custom) ? $custom : [];

        $data['categories'] = $this->categoriesFor((int) $row['id'], $locale);
        $data['tags']       = $this->tagsFor((int) $row['id'], $locale);

        if ($withTranslations) {
            $translations = db_connect()->table('content_translations')
                ->select('language_code, title, slug, excerpt, seo_title, seo_description')
                ->where('content_id', (int) $row['id'])
                ->get()->getResultArray();
            $data['translations'] = [];
            foreach ($translations as $translation) {
                $data['translations'][(string) $translation['language_code']] = [
                    'title'   => (string) $translation['title'],
                    'slug'    => (string) $translation['slug'],
                    'excerpt' => (string) ($translation['excerpt'] ?? ''),
                ];
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchById(int $id): array
    {
        $db     = db_connect();
        $locale = $this->localeParam();
        $row    = $db->table('contents c')
            ->select('c.*, t.title, t.slug, t.excerpt, t.body, t.seo_title, t.seo_description, t.seo_keywords, t.custom_fields, t.reading_time, s.slug AS section_slug, s.name AS section_name')
            ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape($locale), 'left')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.id', $id)
            ->get()->getRowArray();

        return $row ? $this->present($row, true) : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function translationInput(): array
    {
        $locale = $this->localeParam();
        $translations = $this->payload['translations'] ?? null;
        if (is_array($translations) && isset($translations[$locale]) && is_array($translations[$locale])) {
            return $translations[$locale];
        }

        return array_intersect_key($this->payload, array_flip(['title', 'slug', 'excerpt', 'body', 'seo_title', 'seo_description', 'seo_keywords']));
    }

    private function saveTranslations(int $contentId, string $title, ?string $publishedAt, bool $onlyProvided = false): void
    {
        $db        = db_connect();
        $sanitizer = service('sanitizer');
        $default   = default_locale();
        $now       = date('Y-m-d H:i:s');

        $sources = [];
        $primary = $this->translationInput();
        if ($primary !== [] || ! $onlyProvided) {
            $sources[$this->localeParam()] = $primary !== [] ? $primary : ['title' => $title];
        }

        $translations = $this->payload['translations'] ?? null;
        if (is_array($translations)) {
            foreach ($translations as $code => $translation) {
                $code = preg_replace('/[^a-z0-9_-]/i', '', (string) $code);
                if ($code !== '' && is_array($translation) && trim((string) ($translation['title'] ?? '')) !== '') {
                    $sources[$code] = $translation;
                }
            }
        }

        foreach ($sources as $code => $data) {
            $existing = $db->table('content_translations')->where('content_id', $contentId)->where('language_code', $code)->get()->getRowArray();
            $previous = $existing ?: [];

            $pick = static function (string $key, mixed $fallback = null) use ($data, $previous): mixed {
                return array_key_exists($key, $data) ? $data[$key] : ($previous[$key] ?? $fallback);
            };

            $rowTitle = $sanitizer->text($pick('title', $title), 255);
            if ($rowTitle === '') {
                continue;
            }
            $slugSource = trim((string) ($pick('slug', '') ?: ($previous['slug'] ?? '') ?: $rowTitle));
            $slug = $sanitizer->slug($slugSource, 190);
            $slug = $this->uniqueSlug($slug, $code, $contentId);

            $body    = $sanitizer->html((string) $pick('body', ''));
            $excerpt = trim((string) $pick('excerpt', ''));
            $row = [
                'title'           => $rowTitle,
                'slug'            => $slug,
                'excerpt'         => $excerpt !== '' ? $sanitizer->textarea($excerpt, 1000) : null,
                'body'            => $body,
                'seo_title'       => $sanitizer->text($pick('seo_title'), 255) ?: null,
                'seo_description' => $sanitizer->text($pick('seo_description'), 500) ?: null,
                'seo_keywords'    => $sanitizer->text($pick('seo_keywords'), 500) ?: null,
                'reading_time'    => max(1, (int) ceil(str_word_count(strip_tags($body)) / 220)),
                'updated_at'      => $now,
            ];

            if ($existing) {
                $db->table('content_translations')->where('id', (int) $existing['id'])->update($row);
            } else {
                $row['content_id']    = $contentId;
                $row['language_code'] = $code;
                $row['created_at']    = $now;
                $db->table('content_translations')->insert($row);
            }

            if ($code === $default && (array_key_exists('featured_image_id', $this->payload) || $this->request->getPost('featured_image_id') !== null)) {
                $db->table('content_media')->where('content_id', $contentId)->where('role', 'featured')->delete();
                $mediaId = (int) ($this->input('featured_image_id', 0) ?: 0);
                if ($mediaId > 0) {
                    $db->table('content_media')->insert([
                        'content_id' => $contentId,
                        'media_id'   => $mediaId,
                        'role'       => 'featured',
                        'sort_order' => 0,
                    ]);
                }
            }
        }
    }

    private function uniqueSlug(string $slug, string $code, int $contentId): string
    {
        $db       = db_connect();
        $reserved = config(\Config\Destra::class)->reservedSlugs;
        $base     = $slug;
        $suffix   = 1;

        while (in_array(strtolower($slug), $reserved, true)) {
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

    private function syncTaxonomy(int $contentId, string $locale): void
    {
        $db = db_connect();

        if (array_key_exists('categories', $this->payload)) {
            $db->table('content_categories')->where('content_id', $contentId)->delete();
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->payload['categories']))));
            if ($ids !== []) {
                $valid = array_column(
                    $db->table('categories')->select('id')->whereIn('id', $ids)->where('deleted_at', null)->get()->getResultArray(),
                    'id'
                );
                foreach ($valid as $categoryId) {
                    $db->table('content_categories')->insert(['content_id' => $contentId, 'category_id' => (int) $categoryId]);
                }
            }
        }

        if (array_key_exists('tags', $this->payload)) {
            $db->table('content_tags')->where('content_id', $contentId)->delete();
            foreach ((array) $this->payload['tags'] as $name) {
                $name = service('sanitizer')->text($name, 120);
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
                if ($db->table('content_tags')->where('content_id', $contentId)->where('tag_id', $tagId)->countAllResults() === 0) {
                    $db->table('content_tags')->insert(['content_id' => $contentId, 'tag_id' => $tagId]);
                }
            }
        }
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function categoriesFor(int $contentId, string $locale): array
    {
        $rows = db_connect()->table('content_categories cc')
            ->select('cat.id, ct.name, ct.slug')
            ->join('categories cat', 'cat.id = cc.category_id', 'left')
            ->join('category_translations ct', 'ct.category_id = cc.category_id AND ct.language_code = ' . db_connect()->escape($locale), 'left')
            ->where('cc.content_id', $contentId)
            ->get()->getResultArray();

        return array_map(static fn (array $row): array => [
            'id'   => (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
        ], $rows);
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function tagsFor(int $contentId, string $locale): array
    {
        $rows = db_connect()->table('content_tags ctg')
            ->select('tag.id, tt.name, tt.slug')
            ->join('tags tag', 'tag.id = ctg.tag_id', 'left')
            ->join('tag_translations tt', 'tt.tag_id = ctg.tag_id AND tt.language_code = ' . db_connect()->escape($locale), 'left')
            ->where('ctg.content_id', $contentId)
            ->get()->getResultArray();

        return array_map(static fn (array $row): array => [
            'id'   => (int) $row['id'],
            'name' => (string) ($row['name'] ?? ''),
            'slug' => (string) ($row['slug'] ?? ''),
        ], $rows);
    }

    private function localeParam(): string
    {
        $locale = (string) ($this->request->getGet('lang') ?? '');
        $locale = preg_replace('/[^a-z0-9_-]/i', '', $locale);
        if ($locale !== '' && service('localization')->exists($locale)) {
            return $locale;
        }

        return default_locale();
    }

    private function canSeeStatus(string $status): bool
    {
        return $status === 'published' || \App\Libraries\ApiContext::can('content:write');
    }

    private function defaultSectionId(string $type): ?int
    {
        $slug = match ($type) {
            'page'  => 'pages',
            'doc'   => 'docs',
            default => 'blog',
        };
        $row = db_connect()->table('sections')->where('slug', $slug)->get()->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    private function audit(int $id, string $action): void
    {
        try {
            db_connect()->table('audit_logs')->insert([
                'user_id'     => \App\Libraries\ApiContext::userId(),
                'action'      => $action,
                'description' => 'REST API ' . str_replace('content.', '', $action) . ' for content #' . $id,
                'module'      => 'content',
                'subject_type'=> 'content',
                'subject_id'  => $id,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }
}
