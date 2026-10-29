<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

class TaxonomyApi extends BaseApiController
{
    public function categories(): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $locale = $this->localeParam();

        $builder = $db->table('categories c')
            ->select('c.id, c.section_id, c.parent_id, c.icon, c.color, c.image_id, c.sort_order, c.is_active, ct.name, ct.slug, ct.description')
            ->join('category_translations ct', 'ct.category_id = c.id AND ct.language_code = ' . $db->escape($locale), 'left')
            ->where('c.deleted_at', null);

        $section = trim((string) $this->request->getGet('section'));
        if ($section !== '') {
            $builder->join('sections s', 's.id = c.section_id', 'left')
                ->where('s.slug', service('sanitizer')->slug($section, 190));
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('c.sort_order', 'ASC')->orderBy('c.id', 'ASC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id'         => (int) $row['id'],
                'name'       => (string) ($row['name'] ?? ''),
                'slug'       => (string) ($row['slug'] ?? ''),
                'description'=> $row['description'] !== null ? (string) $row['description'] : null,
                'section_id' => $row['section_id'] !== null ? (int) $row['section_id'] : null,
                'parent_id'  => $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
                'color'      => $row['color'] !== null ? (string) $row['color'] : null,
                'icon'       => $row['icon'] !== null ? (string) $row['icon'] : null,
                'image'      => ! empty($row['image_id']) ? media_url((int) $row['image_id']) : null,
                'active'     => (int) $row['is_active'] === 1,
                'count'      => $this->categoryCount((int) $row['id']),
            ];
        }

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function tags(): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $locale = $this->localeParam();

        $builder = $db->table('tags tag')
            ->select('tag.id, tag.color, tt.name, tt.slug')
            ->join('tag_translations tt', 'tt.tag_id = tag.id AND tt.language_code = ' . $db->escape($locale), 'left');

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('tt.name', 'ASC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id'    => (int) $row['id'],
                'name'  => (string) ($row['name'] ?? ''),
                'slug'  => (string) ($row['slug'] ?? ''),
                'color' => $row['color'] !== null ? (string) $row['color'] : null,
                'count' => db_connect()->table('content_tags')->where('tag_id', (int) $row['id'])->countAllResults(),
            ];
        }

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function sections(): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $rows = db_connect()->table('sections')
            ->select('id, name, slug, icon, description, type, template, visibility, is_active, sort_order')
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();

        $items = array_map(static fn (array $row): array => [
            'id'          => (int) $row['id'],
            'name'        => (string) $row['name'],
            'slug'        => (string) $row['slug'],
            'icon'        => $row['icon'] !== null ? (string) $row['icon'] : null,
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'type'        => (string) $row['type'],
            'template'    => (string) $row['template'],
            'visibility'  => (string) $row['visibility'],
            'active'      => (int) $row['is_active'] === 1,
        ], $rows);

        return $this->ok($items, $this->pagination(count($items)));
    }

    public function languages(): ResponseInterface
    {
        if (($guard = $this->guard('content:read')) !== null) {
            return $guard;
        }

        $rows = db_connect()->table('languages')
            ->select('code, name, native_name, direction, is_default, is_active, sort_order')
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();

        $items = array_map(static fn (array $row): array => [
            'code'       => (string) $row['code'],
            'name'       => (string) $row['name'],
            'native_name'=> (string) ($row['native_name'] ?? $row['name']),
            'direction'  => (string) ($row['direction'] ?? 'ltr'),
            'default'    => (int) $row['is_default'] === 1,
            'active'     => (int) $row['is_active'] === 1,
        ], $rows);

        return $this->ok($items, $this->pagination(count($items)));
    }

    private function categoryCount(int $id): int
    {
        return db_connect()->table('content_categories')->where('category_id', $id)->countAllResults();
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
}
