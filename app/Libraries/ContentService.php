<?php

namespace App\Libraries;

use Throwable;

/**
 * Read-side content repository with locale fallback, eager loading of
 * translations/categories/tags/media and cursor-safe pagination.
 *
 * All public queries are parameterised through the query builder and only
 * hydrate arrays; templates never see raw SQL.
 */
class ContentService
{
    /** @var array<int, array<string, mixed>> */
    private array $mediaCache = [];

    /**
     * @param array{
     *   page?: int, per_page?: int, type?: string|list<string>, section_id?: int,
     *   section_slug?: string, author_id?: int, author?: string, category_id?: int,
     *   tag_id?: int, q?: string, sticky?: bool, exclude?: int|list<int>, ids?: list<int>,
     *   status?: string, include_future?: bool, preview?: bool
     * } $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function query(array $filters = []): array
    {
        $perPage = max(1, min(60, (int) ($filters['per_page'] ?? setting_int('reading.posts_per_page', 12))));
        $page    = max(1, (int) ($filters['page'] ?? 1));
        $locale  = $filters['locale'] ?? current_locale();

        $countBuilder = $this->builder($filters);
        $total        = (int) $countBuilder->countAllResults();

        $order = (string) ($filters['order'] ?? 'published');
        $rows  = $this->builder($filters, true)
            ->orderBy('c.sticky', 'DESC')
            ->orderBy(
                $order === 'popular' ? 'c.views' : ($order === 'oldest' ? 'c.published_at' : 'c.published_at'),
                $order === 'oldest' ? 'ASC' : 'DESC'
            )
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'items'    => $this->hydrate($rows, (string) $locale, ! empty($filters['with_body'])),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function latest(int $limit = 6, array $options = []): array
    {
        return $this->query(array_merge([
            'per_page' => $limit,
            'order'    => 'latest',
        ], $options))['items'];
    }

    public function popular(int $limit = 5, array $options = []): array
    {
        return $this->query(array_merge([
            'per_page' => $limit,
            'order'    => 'popular',
        ], $options))['items'];
    }

    public function findBySlug(string $slug, ?string $lang = null, ?string $type = null, bool $preview = false): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $filters = [
            'slug'     => $slug,
            'type'     => $type,
            'per_page' => 1,
            'preview'  => $preview,
            'with_body' => true,
        ];
        if ($lang !== null) {
            $filters['locale'] = $lang;
        }

        $items = $this->query($filters)['items'];

        return $items[0] ?? null;
    }

    public function findById(int $id, ?string $lang = null, bool $preview = false): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $filters = [
            'ids'       => [$id],
            'per_page'  => 1,
            'preview'   => $preview,
            'with_body' => true,
        ];
        if ($lang !== null) {
            $filters['locale'] = $lang;
        }

        $items = $this->query($filters)['items'];

        return $items[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function related(int $contentId, int $limit = 3, ?array $content = null): array
    {
        $limit = max(1, min(12, $limit));
        if ($contentId <= 0) {
            return [];
        }

        $ids = [];
        try {
            $rows = db_connect()->table('content_related')->where('content_id', $contentId)->orderBy('sort_order', 'ASC')->limit($limit)->get()->getResultArray();
            $ids  = array_map(static fn (array $r): int => (int) $r['related_id'], $rows);
        } catch (Throwable) {
            $ids = [];
        }

        $items = $ids !== [] ? $this->findMany($ids) : [];

        if (count($items) < $limit && $content !== null && ! empty($content['section_id'])) {
            $extra = $this->query([
                'section_id' => (int) $content['section_id'],
                'exclude'    => $contentId,
                'per_page'   => $limit - count($items) + 3,
            ])['items'];

            $seen = array_column($items, 'id');
            foreach ($extra as $item) {
                if (in_array((int) $item['id'], $seen, true)) {
                    continue;
                }
                $items[] = $item;
                if (count($items) >= $limit) {
                    break;
                }
            }
        }

        return array_slice($items, 0, $limit);
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function findMany(array $ids, ?string $locale = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $filters = ['ids' => $ids, 'per_page' => max(1, count($ids)), 'preview' => false];
        if ($locale !== null) {
            $filters['locale'] = $locale;
        }

        $items = $this->query($filters)['items'];
        $order = array_flip($ids);

        usort($items, static fn (array $a, array $b): int => ($order[(int) $a['id']] ?? 0) <=> ($order[(int) $b['id']] ?? 0));

        return $items;
    }

    public function byCategory(string $slug, array $options = []): array
    {
        $category = $this->categoryBySlug($slug);
        if ($category === null) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 0, 'pages' => 1, 'category' => null];
        }

        return array_merge(
            $this->query(array_merge(['category_id' => (int) $category['id']], $options)),
            ['category' => $category]
        );
    }

    public function byTag(string $slug, array $options = []): array
    {
        $tag = $this->tagBySlug($slug);
        if ($tag === null) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 0, 'pages' => 1, 'tag' => null];
        }

        return array_merge(
            $this->query(array_merge(['tag_id' => (int) $tag['id']], $options)),
            ['tag' => $tag]
        );
    }

    public function search(string $query, array $options = []): array
    {
        $query = trim($query);

        return $this->query(array_merge(['q' => $query], $options));
    }

    public function categoryBySlug(string $slug, ?string $locale = null): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        try {
            $db       = db_connect();
            $locale   = $locale ?? current_locale();
            $default  = default_locale();
            $row = $db->table('category_translations')
                ->select('category_id, language_code, name, slug, description, seo_title, seo_description')
                ->where('slug', $slug)
                ->whereIn('language_code', array_values(array_unique([$locale, $default])))
                ->get()
                ->getResultArray();

            if ($row === []) {
                return null;
            }

            $translation = null;
            foreach ($row as $candidate) {
                if ($candidate['language_code'] === $locale) {
                    $translation = $candidate;
                    break;
                }
            }
            $translation ??= $row[0];

            $category = $db->table('categories')->where('id', (int) $translation['category_id'])->where('deleted_at', null)->get()->getRowArray();
            if (! $category) {
                return null;
            }

            $category['name']        = $translation['name'];
            $category['slug']        = $translation['slug'];
            $category['description'] = $translation['description'] ?? '';
            $category['url']         = category_url($category, (string) $translation['slug'], (string) $translation['language_code']);

            return $category;
        } catch (Throwable) {
            return null;
        }
    }

    public function tagBySlug(string $slug, ?string $locale = null): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        try {
            $db      = db_connect();
            $locale  = $locale ?? current_locale();
            $default = default_locale();

            $rows = $db->table('tag_translations')
                ->where('slug', $slug)
                ->whereIn('language_code', array_values(array_unique([$locale, $default])))
                ->get()
                ->getResultArray();
            if ($rows === []) {
                return null;
            }

            $translation = $rows[0];
            foreach ($rows as $row) {
                if ($row['language_code'] === $locale) {
                    $translation = $row;
                    break;
                }
            }

            $tag = $db->table('tags')->where('id', (int) $translation['tag_id'])->get()->getRowArray();
            if (! $tag) {
                return null;
            }

            $tag['name'] = $translation['name'];
            $tag['slug'] = $translation['slug'];
            $tag['url']  = tag_url((string) $translation['slug'], (string) $translation['language_code']);

            return $tag;
        } catch (Throwable) {
            return null;
        }
    }

    public function sectionBySlug(string $slug): ?array
    {
        try {
            $section = db_connect()->table('sections')
                ->where('slug', $slug)
                ->where('is_active', 1)
                ->where('visibility', 'public')
                ->get()
                ->getRowArray();

            if (! $section) {
                return null;
            }

            $section['url'] = section_url($section);

            return $section;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(?int $sectionId = null, bool $withCounts = true): array
    {
        try {
            $db      = db_connect();
            $locale  = current_locale();
            $default = default_locale();

            $builder = $db->table('categories c')
                ->select('c.id, c.parent_id, c.icon, c.color, c.image_id, c.sort_order')
                ->where('c.is_active', 1)
                ->where('c.deleted_at', null)
                ->orderBy('c.sort_order', 'ASC')
                ->orderBy('c.id', 'ASC');
            if ($sectionId !== null && $sectionId > 0) {
                $builder->where('c.section_id', $sectionId);
            }
            $categories = $builder->get()->getResultArray();
            if ($categories === []) {
                return [];
            }

            $ids          = array_column($categories, 'id');
            $translations = $db->table('category_translations')
                ->whereIn('category_id', $ids)
                ->whereIn('language_code', array_values(array_unique([$locale, $default])))
                ->get()
                ->getResultArray();

            $map = [];
            foreach ($translations as $translation) {
                $map[(int) $translation['category_id']][(string) $translation['language_code']] = $translation;
            }

            $counts = [];
            if ($withCounts) {
                $rows = $db->table('content_categories cc')
                    ->select('cc.category_id, COUNT(*) AS total')
                    ->join('contents c', 'c.id = cc.content_id')
                    ->where('c.status', 'published')
                    ->where('c.deleted_at', null)
                    ->whereIn('cc.category_id', $ids)
                    ->groupBy('cc.category_id')
                    ->get()
                    ->getResultArray();
                $counts = array_column($rows, 'total', 'category_id');
            }

            $result = [];
            foreach ($categories as $category) {
                $id = (int) $category['id'];
                $translation = $map[$id][$locale] ?? ($map[$id][$default] ?? null);
                if ($translation === null) {
                    continue;
                }
                $category['name']        = $translation['name'];
                $category['slug']        = $translation['slug'];
                $category['description'] = $translation['description'] ?? '';
                $category['url']         = category_url($category, (string) $translation['slug'], (string) $translation['language_code']);
                $category['count']       = (int) ($counts[$id] ?? 0);
                $category['image_url']   = ! empty($category['image_id']) ? media_url((int) $category['image_id'], 'thumb') : '';
                $result[] = $category;
            }

            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tags(int $limit = 30): array
    {
        try {
            $db      = db_connect();
            $locale  = current_locale();
            $default = default_locale();

            $rows = $db->table('tag_translations tt')
                ->select('t.id, t.color, tt.name, tt.slug, tt.language_code, COUNT(ct.content_id) AS total')
                ->join('tags t', 't.id = tt.tag_id')
                ->join('content_tags ct', 'ct.tag_id = t.id', 'left')
                ->join('contents c', 'c.id = ct.content_id AND c.status = ' . $db->escape('published') . ' AND c.deleted_at IS NULL', 'left')
                ->where('tt.language_code', $locale)
                ->groupBy('t.id, t.color, tt.name, tt.slug, tt.language_code')
                ->orderBy('total', 'DESC')
                ->limit(max(1, min(100, $limit)))
                ->get()
                ->getResultArray();

            if ($rows === []) {
                $rows = $db->table('tag_translations tt')
                    ->select('t.id, t.color, tt.name, tt.slug, tt.language_code, 0 AS total')
                    ->join('tags t', 't.id = tt.tag_id')
                    ->where('tt.language_code', $default)
                    ->limit(max(1, min(100, $limit)))
                    ->get()
                    ->getResultArray();
            }

            foreach ($rows as &$row) {
                $row['url'] = tag_url((string) $row['slug'], (string) $row['language_code']);
            }
            unset($row);

            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string> code => absolute URL
     */
    public function alternates(array $content): array
    {
        if (empty($content['id'])) {
            return [];
        }

        $translations = $this->translationMap((int) $content['id']);
        $active       = service('localization')->codes();
        $sectionSlug  = (string) ($content['section_slug'] ?? '');
        $type         = (string) ($content['type'] ?? 'post');
        $urls         = [];

        foreach ($translations as $code => $translation) {
            if (! in_array($code, $active, true)) {
                continue;
            }
            $urls[$code] = content_url([
                'id'           => (int) $content['id'],
                'type'         => $type,
                'section_slug' => $sectionSlug,
            ], $translation, $code);
        }

        $default = default_locale();
        if (isset($urls[$default])) {
            $urls['x-default'] = $urls[$default];
        }

        return $urls;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function translationMap(int $contentId): array
    {
        if ($contentId <= 0) {
            return [];
        }

        try {
            $rows = db_connect()->table('content_translations')->where('content_id', $contentId)->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['language_code']] = $row;
        }

        return $map;
    }

    /**
     * Front page blocks: latest items for each configured section.
     *
     * @return list<array{section: array<string, mixed>, items: list<array<string, mixed>>}>
     */
    public function homeSections(int $blocks = 3, int $perBlock = 4): array
    {
        $ids = service('settings')->list('reading.home_sections');
        try {
            $builder = db_connect()->table('sections')->where('is_active', 1)->where('visibility', 'public');
            if ($ids !== []) {
                $builder->whereIn('id', array_map('intval', $ids));
            }
            $sections = $builder->orderBy('sort_order', 'ASC')->limit(max(1, min(6, $blocks)))->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }

        $result = [];
        foreach ($sections as $section) {
            $items = $this->query([
                'section_id' => (int) $section['id'],
                'per_page'   => max(1, min(12, $perBlock)),
            ])['items'];
            if ($items === []) {
                continue;
            }
            $section['url']   = section_url($section);
            $result[] = ['section' => $section, 'items' => $items];
        }

        return $result;
    }

    /**
     * Build the filtered query. $forFetch adds translation joins used by
     * hydration-friendly selects; count uses the same joins for correctness.
     */
    private function builder(array $filters, bool $forFetch = false)
    {
        $db      = db_connect();
        $locale  = (string) ($filters['locale'] ?? current_locale());
        $default = default_locale();

        $builder = $db->table('contents c')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->join('users u', 'u.id = c.author_id', 'left')
            ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape($locale), 'left')
            ->join('content_translations td', 'td.content_id = c.id AND td.language_code = ' . $db->escape($default), 'left');

        if ($forFetch) {
            $builder->select('c.*, s.name AS section_name, s.slug AS section_slug, s.type AS section_type, s.template AS section_template, s.icon AS section_icon, '
                . 'u.name AS author_name, u.username AS author_username, u.avatar AS author_avatar, u.bio AS author_bio, '
                . "COALESCE(NULLIF(t.title, ''), td.title) AS tr_title, COALESCE(NULLIF(t.slug, ''), td.slug) AS tr_slug, "
                . 't.title AS locale_title, t.slug AS locale_slug, t.language_code AS translation_locale, td.title AS default_title, td.slug AS default_slug');
        }

        if (! empty($filters['with_body'])) {
            $builder->select("t.body, td.body AS default_body, t.excerpt, td.excerpt AS default_excerpt, t.custom_fields, td.custom_fields AS default_custom_fields, t.reading_time, td.reading_time AS default_reading_time");
        } else {
            $builder->select('t.excerpt, td.excerpt AS default_excerpt, t.reading_time, td.reading_time AS default_reading_time');
        }

        $preview = ! empty($filters['preview']);
        if (! $preview) {
            $now = date('Y-m-d H:i:s');
            $builder->where('c.status', (string) ($filters['status'] ?? 'published'))
                ->where('c.deleted_at', null);

            if (empty($filters['include_future'])) {
                $builder->where('c.published_at <=', $now);
            }
            $builder->groupStart()->where('c.expires_at', null)->orWhere('c.expires_at >', $now)->groupEnd();
        } elseif (! empty($filters['status'])) {
            $builder->where('c.status', (string) $filters['status']);
            $builder->where('c.deleted_at', null);
        }

        if (! empty($filters['ids'])) {
            $builder->whereIn('c.id', array_map('intval', (array) $filters['ids']));
        }

        if (! empty($filters['slug'])) {
            $slug = (string) $filters['slug'];
            $builder->groupStart()->where('t.slug', $slug)->orWhere('td.slug', $slug)->groupEnd();
        }

        if (! empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $builder->whereIn('c.type', $filters['type']);
            } else {
                $builder->where('c.type', (string) $filters['type']);
            }
        }

        if (! empty($filters['section_id'])) {
            $builder->where('c.section_id', (int) $filters['section_id']);
        }

        if (! empty($filters['section_slug'])) {
            $builder->where('s.slug', (string) $filters['section_slug']);
        }

        if (! empty($filters['author_id'])) {
            $builder->where('c.author_id', (int) $filters['author_id']);
        }

        if (! empty($filters['author'])) {
            $builder->where('u.username', (string) $filters['author']);
        }

        if (! empty($filters['sticky'])) {
            $builder->where('c.sticky', 1);
        }

        if (! empty($filters['exclude'])) {
            $builder->whereNotIn('c.id', array_map('intval', (array) $filters['exclude']));
        }

        // Relation filters run as EXISTS subqueries: executing a nested builder
        // here would call resetSelect() and wipe the connection-wide alias map
        // ($db->aliasedTables), which makes the outer `c.*` compile as `cms_c.*`.
        if (! empty($filters['category_id'])) {
            $builder->where(
                'EXISTS (SELECT 1 FROM ' . $db->prefixTable('content_categories') . ' cc'
                . ' WHERE cc.content_id = c.id AND cc.category_id = ' . (int) $filters['category_id'] . ')',
                null,
                false
            );
        }

        if (! empty($filters['tag_id'])) {
            $builder->where(
                'EXISTS (SELECT 1 FROM ' . $db->prefixTable('content_tags') . ' ct'
                . ' WHERE ct.content_id = c.id AND ct.tag_id = ' . (int) $filters['tag_id'] . ')',
                null,
                false
            );
        }

        if (! empty($filters['q'])) {
            $builder->groupStart()
                ->like('t.title', (string) $filters['q'])
                ->orLike('td.title', (string) $filters['q'])
                ->orLike('t.excerpt', (string) $filters['q'])
                ->orLike('td.excerpt', (string) $filters['q'])
                ->orLike('t.body', (string) $filters['q'])
                ->orLike('td.body', (string) $filters['q'])
                ->groupEnd();
        }

        if (! empty($filters['created_from'])) {
            $builder->where('c.created_at >=', (string) $filters['created_from']);
        }
        if (! empty($filters['created_to'])) {
            $builder->where('c.created_at <=', (string) $filters['created_to'] . ' 23:59:59');
        }

        return $builder;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function hydrate(array $rows, string $locale, bool $withBody = false): array
    {
        if ($rows === []) {
            return [];
        }

        $ids     = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $default = default_locale();
        $db      = db_connect();

        $translationBuilder = $db->table('content_translations')->whereIn('content_id', $ids);
        if (! $withBody) {
            $translationBuilder->select('id, content_id, language_code, title, slug, excerpt, seo_title, seo_description, seo_keywords, canonical_url, og_image_id, reading_time, updated_at');
        }
        $translationRows = $translationBuilder->get()->getResultArray();
        $translations    = [];
        foreach ($translationRows as $translation) {
            $translations[(int) $translation['content_id']][(string) $translation['language_code']] = $translation;
        }

        $categoryLinks = $db->table('content_categories')->whereIn('content_id', $ids)->get()->getResultArray();
        $categoryIds   = array_values(array_unique(array_map(static fn (array $r): int => (int) $r['category_id'], $categoryLinks)));
        $categoryMap   = [];
        if ($categoryIds !== []) {
            $cats = $db->table('categories')->whereIn('id', $categoryIds)->where('deleted_at', null)->orderBy('sort_order', 'ASC')->get()->getResultArray();
            $catTrans = $db->table('category_translations')->whereIn('category_id', $categoryIds)->get()->getResultArray();
            $trMap = [];
            foreach ($catTrans as $tr) {
                $trMap[(int) $tr['category_id']][(string) $tr['language_code']] = $tr;
            }
            foreach ($cats as $cat) {
                $id = (int) $cat['id'];
                $tr = $trMap[$id][$locale] ?? ($trMap[$id][$default] ?? null);
                if ($tr === null) {
                    continue;
                }
                $cat['name'] = $tr['name'];
                $cat['slug'] = $tr['slug'];
                $cat['url']  = category_url($cat, (string) $tr['slug'], (string) $tr['language_code']);
                $categoryMap[$id] = $cat;
            }
        }

        $tagLinks = $db->table('content_tags')->whereIn('content_id', $ids)->get()->getResultArray();
        $tagIds   = array_values(array_unique(array_map(static fn (array $r): int => (int) $r['tag_id'], $tagLinks)));
        $tagMap   = [];
        if ($tagIds !== []) {
            $tagTrans = $db->table('tag_translations')->whereIn('tag_id', $tagIds)->get()->getResultArray();
            $trMap = [];
            foreach ($tagTrans as $tr) {
                $trMap[(int) $tr['tag_id']][(string) $tr['language_code']] = $tr;
            }
            foreach ($tagIds as $tagId) {
                $tr = $trMap[$tagId][$locale] ?? ($trMap[$tagId][$default] ?? null);
                if ($tr === null) {
                    continue;
                }
                $tagMap[$tagId] = [
                    'id'   => $tagId,
                    'name' => $tr['name'],
                    'slug' => $tr['slug'],
                    'url'  => tag_url((string) $tr['slug'], (string) $tr['language_code']),
                ];
            }
        }

        $byContent = [];
        foreach ($categoryLinks as $link) {
            if (isset($categoryMap[(int) $link['category_id']])) {
                $byContent[(int) $link['content_id']]['categories'][] = $categoryMap[(int) $link['category_id']];
            }
        }
        foreach ($tagLinks as $link) {
            if (isset($tagMap[(int) $link['tag_id']])) {
                $byContent[(int) $link['content_id']]['tags'][] = $tagMap[(int) $link['tag_id']];
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $id         = (int) $row['id'];
            $current    = $translations[$id][$locale] ?? null;
            $fallback   = $translations[$id][$default] ?? null;
            $translation = $current ?? $fallback;
            $activeLang = $current !== null ? $locale : $default;

            $content = [
                'id'               => $id,
                'section_id'       => $row['section_id'] !== null ? (int) $row['section_id'] : null,
                'type'             => (string) $row['type'],
                'author_id'        => (int) $row['author_id'],
                'parent_id'        => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'status'           => (string) $row['status'],
                'template'         => (string) ($row['template'] ?? ''),
                'format'           => (string) ($row['format'] ?? 'standard'),
                'featured_image_id'=> $row['featured_image_id'] !== null ? (int) $row['featured_image_id'] : 0,
                'icon'             => (string) ($row['icon'] ?? ''),
                'sticky'           => (int) $row['sticky'],
                'allow_comments'   => (int) $row['allow_comments'],
                'order_form'       => (int) ($row['order_form'] ?? 0),
                'published_at'     => $row['published_at'],
                'expires_at'       => $row['expires_at'] ?? null,
                'views'            => (int) $row['views'],
                'sort_order'       => (int) $row['sort_order'],
                'created_at'       => $row['created_at'],
                'updated_at'       => $row['updated_at'],
                'section_name'     => (string) ($row['section_name'] ?? ''),
                'section_slug'     => (string) ($row['section_slug'] ?? ''),
                'section_type'     => (string) ($row['section_type'] ?? ''),
                'section_template' => (string) ($row['section_template'] ?? ''),
                'section_icon'     => (string) ($row['section_icon'] ?? ''),
            ];

            $content['author'] = [
                'id'       => (int) $row['author_id'],
                'name'     => (string) ($row['author_name'] ?? 'Editorial team'),
                'username' => (string) ($row['author_username'] ?? ''),
                'avatar'   => (string) ($row['author_avatar'] ?? ''),
                'bio'      => (string) ($row['author_bio'] ?? ''),
            ];
            $content['author_url'] = $content['author']['username'] !== '' ? author_url($content['author'], $activeLang) : '';

            $body     = '';
            $excerpt  = '';
            $seoTitle = '';
            $seoDesc  = '';
            $keywords = '';
            $canonical = '';
            $ogImage  = 0;
            $custom   = [];
            $reading  = 0;

            if ($translation !== null) {
                $body      = (string) ($translation['body'] ?? '');
                $excerpt   = (string) ($translation['excerpt'] ?? '');
                $seoTitle  = (string) ($translation['seo_title'] ?? '');
                $seoDesc   = (string) ($translation['seo_description'] ?? '');
                $keywords  = (string) ($translation['seo_keywords'] ?? '');
                $canonical = (string) ($translation['canonical_url'] ?? '');
                $ogImage   = (int) ($translation['og_image_id'] ?? 0);
                $custom    = json_decode((string) ($translation['custom_fields'] ?? ''), true) ?: [];
                $reading   = (int) ($translation['reading_time'] ?? 0);
            }

            if ($excerpt === '' && $body !== '') {
                $excerpt = service('sanitizer')->excerpt($body, setting_int('reading.excerpt_length', 26));
            }

            $title = (string) ($translation['title'] ?? 'Untitled');
            $slug  = (string) ($translation['slug'] ?? '');

            $content['translation']   = $translation ?? [];
            $content['locale']        = $activeLang;
            $content['is_fallback']   = $current === null && $fallback !== null;
            $content['title']         = $title;
            $content['slug']          = $slug;
            $content['excerpt']       = $excerpt;
            $content['body']          = $body;
            $content['reading_time']  = $reading > 0 ? $reading : max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));
            $content['seo_title']     = $seoTitle;
            $content['seo_description'] = $seoDesc;
            $content['seo_keywords']  = $keywords;
            $content['canonical_url'] = $canonical;
            $content['og_image_id']   = $ogImage;
            $content['custom_fields'] = $custom;
            $content['categories']    = $byContent[$id]['categories'] ?? [];
            $content['tags']          = $byContent[$id]['tags'] ?? [];
            $content['primary_category'] = $content['categories'][0] ?? null;
            $content['url']           = $slug !== '' && $translation !== null
                ? content_url($content, $translation, $activeLang)
                : base_url('/');

            $imageId = $content['featured_image_id'] > 0 ? $content['featured_image_id'] : $ogImage;
            $content['image']     = $imageId > 0 ? $this->media($imageId) : null;
            $content['image_url'] = $imageId > 0 ? media_url($imageId) : '';
            $content['thumb_url'] = $imageId > 0 ? (media_url($imageId, 'thumb') ?: media_url($imageId)) : '';

            $items[] = $content;
        }

        return $items;
    }

    private function media(int $id): ?array
    {
        if (array_key_exists($id, $this->mediaCache)) {
            return $this->mediaCache[$id];
        }

        return $this->mediaCache[$id] = service('media')->find($id);
    }
}
