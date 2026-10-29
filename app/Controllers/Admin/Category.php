<?php

namespace App\Controllers\Admin;

use Throwable;

class Category extends BaseAdminController
{
    public function index(): string
    {
        $db      = db_connect();
        $default = default_locale();

        $items = $db->table('categories c')
            ->select('c.*, t.name, t.slug, s.name AS section_name')
            ->join('category_translations t', 't.category_id = c.id AND t.language_code = ' . $db->escape($default), 'left')
            ->join('sections s', 's.id = c.section_id', 'left')
            ->where('c.deleted_at', null)
            ->orderBy('c.sort_order', 'ASC')
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getResultArray();

        $counts = [];
        try {
            $usage = $db->table('content_categories')->select('category_id, COUNT(*) AS total')->groupBy('category_id')->get()->getResultArray();
            foreach ($usage as $row) {
                $counts[(int) $row['category_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $translations = [];
        try {
            foreach ($db->table('category_translations')->get()->getResultArray() as $row) {
                $translations[(int) $row['category_id']][(string) $row['language_code']] = $row;
            }
        } catch (Throwable) {
        }

        return $this->render('admin/taxonomy/categories', [
            'pageTitle'    => 'Categories',
            'pageSubtitle' => 'Group content into navigable topics with SEO-friendly URLs.',
            'navActive'    => 'categories',
            'breadcrumbs'  => [['label' => 'Categories']],
            'items'        => $items,
            'counts'       => $counts,
            'translations' => $translations,
            'sections'     => $this->sections(),
            'languages'    => service('localization')->languages(),
            'defaultLocale' => $default,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $item = $this->find($id);
        if ($item === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($item);
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
        $item = $this->find($id);
        if ($item === null) {
            return redirect()->to(admin_url('categories'))->with('error', 'Category not found.');
        }

        db_connect()->table('categories')->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit('category.delete', 'Deleted category "' . $item['name'] . '"', 'content', 'category', $id);
        $this->flushCache();

        return redirect()->to(admin_url('categories'))->with('success', 'Category removed.');
    }

    public function toggle(int $id)
    {
        $item = $this->find($id);
        if ($item === null) {
            return $this->fail('Category not found.', 404);
        }

        $active = (int) $this->request->getPost('value') === 1 ? 1 : 0;
        db_connect()->table('categories')->where('id', $id)->update(['is_active' => $active, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        return $this->ok(['value' => $active], $active ? 'Category published.' : 'Category hidden.');
    }

    private function form(?array $item): string
    {
        $translations = [];
        if ($item !== null) {
            try {
                foreach (db_connect()->table('category_translations')->where('category_id', (int) $item['id'])->get()->getResultArray() as $row) {
                    $translations[(string) $row['language_code']] = $row;
                }
            } catch (Throwable) {
            }
        }

        return $this->render('admin/taxonomy/category-form', [
            'pageTitle'    => $item ? 'Edit category' : 'New category',
            'pageSubtitle' => 'Names, slugs and descriptions can be translated per language.',
            'navActive'    => 'categories',
            'breadcrumbs'  => [['label' => 'Categories', 'url' => admin_url('categories')], ['label' => $item ? 'Edit' : 'Create']],
            'item'         => $item,
            'translations' => $translations,
            'sections'     => $this->sections(),
            'categories'   => $this->findAll($item['id'] ?? null),
            'languages'    => service('localization')->languages(),
            'defaultLocale' => default_locale(),
        ]);
    }

    private function save(?int $id)
    {
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $post = $this->request->getPost();
        $san  = service('sanitizer');

        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('categories'))->with('error', 'Category not found.');
        }

        $name = $san->text($post['name'] ?? '', 190);
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'A category name is required.');
        }

        $row = [
            'section_id' => (int) ($post['section_id'] ?? 0) ?: null,
            'parent_id'  => (int) ($post['parent_id'] ?? 0) ?: null,
            'icon'       => $san->icon($post['icon'] ?? null) ?: null,
            'color'      => $san->color($post['color'] ?? null, '#6d5efc'),
            'image_id'   => (int) ($post['image_id'] ?? 0) ?: null,
            'sort_order' => $san->int($post['sort_order'] ?? 0, 0, 9999),
            'is_active'  => isset($post['is_active']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('categories')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('categories')->where('id', $id)->update($row);
        }

        $default = default_locale();
        $translations = (array) ($post['translations'] ?? []);
        $this->saveTranslation($id, $default, [
            'name'            => $name,
            'slug'            => (string) ($post['slug'] ?? ''),
            'description'     => (string) ($post['description'] ?? ''),
            'seo_title'       => $post['seo_title'] ?? null,
            'seo_description' => $post['seo_description'] ?? null,
        ]);

        foreach ($translations as $code => $translation) {
            $code = (string) $code;
            if ($code === $default || ! is_array($translation)) {
                continue;
            }
            $trName = trim((string) ($translation['name'] ?? ''));
            if ($trName === '') {
                $db->table('category_translations')->where('category_id', $id)->where('language_code', $code)->delete();
                continue;
            }
            $this->saveTranslation($id, $code, $translation);
        }

        $this->audit($existing ? 'category.update' : 'category.create', ($existing ? 'Updated' : 'Created') . ' category "' . $name . '"', 'content', 'category', $id);
        $this->flushCache();

        return redirect()->to(admin_url('categories'))->with('success', 'Category saved.');
    }

    private function saveTranslation(int $categoryId, string $code, array $data): void
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $san = service('sanitizer');

        $name = $san->text($data['name'] ?? '', 190);
        if ($name === '') {
            return;
        }

        $slug = $san->slug($data['slug'] ?? '' ?: $name, 190);
        $base = $slug;
        $i    = 1;
        while ($db->table('category_translations')->where('language_code', $code)->where('slug', $slug)->where('category_id !=', $categoryId)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        $row = [
            'name'            => $name,
            'slug'            => $slug,
            'description'     => $san->textarea($data['description'] ?? null, 5000) ?: null,
            'updated_at'      => $now,
        ];

        if (array_key_exists('seo_title', $data)) {
            $row['seo_title'] = $san->text($data['seo_title'], 255) ?: null;
        }
        if (array_key_exists('seo_description', $data)) {
            $row['seo_description'] = $san->text($data['seo_description'], 500) ?: null;
        }

        $existing = $db->table('category_translations')->where('category_id', $categoryId)->where('language_code', $code)->get()->getRowArray();
        if ($existing) {
            $db->table('category_translations')->where('id', (int) $existing['id'])->update($row);
        } else {
            $row['category_id']   = $categoryId;
            $row['language_code'] = $code;
            $row['created_at']    = $now;
            $db->table('category_translations')->insert($row);
        }
    }

    private function find(int $id): ?array
    {
        $db      = db_connect();
        $default = default_locale();
        $row = $db->table('categories c')
            ->select('c.*, t.name, t.slug, t.description')
            ->join('category_translations t', 't.category_id = c.id AND t.language_code = ' . $db->escape($default), 'left')
            ->where('c.id', $id)
            ->where('c.deleted_at', null)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function findAll(?int $exclude): array
    {
        $db      = db_connect();
        $builder = $db->table('categories c')
            ->select('c.id, c.parent_id, t.name')
            ->join('category_translations t', 't.category_id = c.id AND t.language_code = ' . $db->escape(default_locale()), 'left')
            ->where('c.deleted_at', null)
            ->orderBy('t.name', 'ASC');
        if ($exclude !== null) {
            $builder->where('c.id !=', $exclude);
        }

        return $builder->get()->getResultArray();
    }

    private function sections(): array
    {
        try {
            return db_connect()->table('sections')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }
}
