<?php

namespace App\Controllers\Admin;

use Throwable;

class Menu extends BaseAdminController
{
    public function index(): string
    {
        $db = db_connect();
        $menus = $db->table('menus')->orderBy('name', 'ASC')->get()->getResultArray();

        $counts = [];
        try {
            foreach ($db->table('menu_items')->select('menu_id, COUNT(*) AS total')->groupBy('menu_id')->get()->getResultArray() as $row) {
                $counts[(int) $row['menu_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/menus/index', [
            'pageTitle'    => 'Menus',
            'pageSubtitle' => 'Build navigation for the header, footer and any custom location.',
            'navActive'    => 'menus',
            'breadcrumbs'  => [['label' => 'Menus']],
            'menus'        => $menus,
            'counts'       => $counts,
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->to(admin_url('menus'))->with('error', 'A menu name is required.');
        }

        $slug = $this->uniqueSlug($san->slug($post['slug'] ?? '' ?: $name, 120), null);
        $db   = db_connect();
        $db->table('menus')->insert([
            'name'       => $name,
            'slug'       => $slug,
            'location'   => $san->slug($post['location'] ?? 'primary', 60),
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        $this->audit('menu.create', 'Created menu "' . $name . '"', 'appearance', 'menu', $id);
        $this->flushCache();

        return redirect()->to(admin_url('menus/' . $id . '/edit'))->with('success', 'Menu created. Add your first item.');
    }

    public function edit(int $id): string
    {
        $menu = $this->findMenu($id);
        if ($menu === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db      = db_connect();
        $default = default_locale();
        $items = $db->table('menu_items i')
            ->select('i.*, t.label, t.language_code')
            ->join('menu_item_translations t', 't.item_id = i.id AND t.language_code = ' . $db->escape($default), 'left')
            ->where('i.menu_id', $id)
            ->orderBy('i.sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $labels = [];
        foreach ($items as &$item) {
            $item['resolved_url'] = $this->resolveUrl($item);
            if (empty($item['label'])) {
                $item['label'] = 'Untitled item';
            }
        }
        unset($item);

        return $this->render('admin/menus/edit', [
            'pageTitle'    => 'Edit menu',
            'pageSubtitle' => 'Drag items to reorder. Nested items become dropdowns on the website.',
            'navActive'    => 'menus',
            'breadcrumbs'  => [['label' => 'Menus', 'url' => admin_url('menus')], ['label' => $menu['name']]],
            'menu'         => $menu,
            'items'        => $items,
            'locations'    => ['primary' => 'Primary (header)', 'footer' => 'Footer', 'sidebar' => 'Sidebar', 'mobile' => 'Mobile', 'topbar' => 'Top bar'],
        ]);
    }

    public function update(int $id)
    {
        $menu = $this->findMenu($id);
        if ($menu === null) {
            return redirect()->to(admin_url('menus'))->with('error', 'Menu not found.');
        }

        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->back()->with('error', 'A menu name is required.');
        }

        db_connect()->table('menus')->where('id', $id)->update([
            'name'       => $name,
            'slug'       => $this->uniqueSlug($san->slug($post['slug'] ?? '' ?: $name, 120), $id),
            'location'   => $san->slug($post['location'] ?? 'primary', 60),
            'is_active'  => isset($post['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->flushCache();

        return redirect()->to(admin_url('menus/' . $id . '/edit'))->with('success', 'Menu settings saved.');
    }

    public function delete(int $id)
    {
        $db = db_connect();
        $db->table('menu_item_translations')->whereIn('item_id', array_column($db->table('menu_items')->select('id')->where('menu_id', $id)->get()->getResultArray(), 'id') ?: [0])->delete();
        $db->table('menu_items')->where('menu_id', $id)->delete();
        $db->table('menus')->where('id', $id)->delete();

        $this->audit('menu.delete', 'Deleted menu #' . $id, 'appearance', 'menu', $id);
        $this->flushCache();

        return redirect()->to(admin_url('menus'))->with('success', 'Menu deleted.');
    }

    public function reorder(int $id)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        if ($ids === []) {
            return $this->fail('No items received.');
        }

        $db = db_connect();
        foreach ($ids as $order => $itemId) {
            $db->table('menu_items')->where('menu_id', $id)->where('id', $itemId)->update(['sort_order' => $order]);
        }
        $this->flushCache();

        return $this->ok([], 'Menu order saved.');
    }

    public function createItem(int $menuId): string
    {
        return $this->itemForm($menuId, null);
    }

    public function editItem(int $menuId, int $itemId): string
    {
        $item = $this->findItem($menuId, $itemId);
        if ($item === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->itemForm($menuId, $item);
    }

    public function storeItem(int $menuId)
    {
        return $this->saveItem($menuId, null);
    }

    public function updateItem(int $menuId, int $itemId)
    {
        return $this->saveItem($menuId, $itemId);
    }

    public function deleteItem(int $menuId, int $itemId)
    {
        $db = db_connect();
        $db->table('menu_item_translations')->where('item_id', $itemId)->delete();
        $db->table('menu_items')->where('id', $itemId)->where('menu_id', $menuId)->delete();
        $db->table('menu_items')->where('menu_id', $menuId)->where('parent_id', $itemId)->update(['parent_id' => null]);
        $this->flushCache();

        return redirect()->to(admin_url('menus/' . $menuId . '/edit'))->with('success', 'Menu item removed.');
    }

    private function itemForm(int $menuId, ?array $item): string
    {
        $menu = $this->findMenu($menuId);
        if ($menu === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $translations = [];
        if ($item !== null) {
            foreach (db_connect()->table('menu_item_translations')->where('item_id', (int) $item['id'])->get()->getResultArray() as $row) {
                $translations[(string) $row['language_code']] = $row;
            }
        }

        return $this->render('admin/menus/item-form', [
            'pageTitle'    => $item ? 'Edit menu item' : 'New menu item',
            'pageSubtitle' => 'Link to a page, section, category, tag or any custom URL.',
            'navActive'    => 'menus',
            'breadcrumbs'  => [
                ['label' => 'Menus', 'url' => admin_url('menus')],
                ['label' => $menu['name'], 'url' => admin_url('menus/' . $menuId . '/edit')],
                ['label' => $item ? 'Edit item' : 'New item'],
            ],
            'menu'         => $menu,
            'item'         => $item,
            'translations' => $translations,
            'languages'    => service('localization')->languages(),
            'defaultLocale' => default_locale(),
            'parents'      => db_connect()->table('menu_items i')
                ->select('i.id, t.label')
                ->join('menu_item_translations t', 't.item_id = i.id AND t.language_code = ' . db_connect()->escape(default_locale()), 'left')
                ->where('i.menu_id', $menuId)
                ->where('i.id !=', (int) ($item['id'] ?? 0))
                ->orderBy('i.sort_order', 'ASC')
                ->get()
                ->getResultArray(),
            'contents'     => $this->contentOptions(),
            'categories'   => $this->tableOptions('category_translations', 'category_id'),
            'tags'         => $this->tableOptions('tag_translations', 'tag_id'),
            'sections'     => $this->tableOptions('sections', 'id', 'name'),
        ]);
    }

    private function saveItem(int $menuId, ?int $itemId)
    {
        $menu = $this->findMenu($menuId);
        if ($menu === null) {
            return redirect()->to(admin_url('menus'))->with('error', 'Menu not found.');
        }

        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');

        $label = $san->text($post['label'] ?? '', 190);
        if ($label === '') {
            return redirect()->back()->withInput()->with('error', 'A label is required.');
        }

        $type = in_array($post['type'] ?? 'custom', ['custom', 'content', 'category', 'tag', 'section'], true) ? $post['type'] : 'custom';
        $row = [
            'menu_id'    => $menuId,
            'parent_id'  => (int) ($post['parent_id'] ?? 0) ?: null,
            'type'       => $type,
            'target_id'  => $type === 'custom' ? null : ((int) ($post['target_id'] ?? 0) ?: null),
            'url'        => $type === 'custom' ? ($san->url($post['url'] ?? '/') ?: '/') : null,
            'icon'       => $san->icon($post['icon'] ?? null) ?: null,
            'target'     => ($post['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
            'css_class'  => $san->text($post['css_class'] ?? null, 120) ?: null,
            'sort_order' => $san->int($post['sort_order'] ?? 0, 0, 9999),
            'is_active'  => isset($post['is_active']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($itemId === null) {
            $row['created_at'] = $now;
            $db->table('menu_items')->insert($row);
            $itemId = (int) $db->insertID();
        } else {
            $db->table('menu_items')->where('id', $itemId)->where('menu_id', $menuId)->update($row);
        }

        $default = default_locale();
        $this->saveItemTranslation($itemId, $default, $label);
        foreach ((array) ($post['translations'] ?? []) as $code => $translation) {
            $code = (string) $code;
            if ($code === $default || ! is_array($translation)) {
                continue;
            }
            $tr = trim((string) ($translation['label'] ?? ''));
            if ($tr === '') {
                $db->table('menu_item_translations')->where('item_id', $itemId)->where('language_code', $code)->delete();
                continue;
            }
            $this->saveItemTranslation($itemId, $code, $tr);
        }

        $this->audit('menu.item.save', 'Saved menu item "' . $label . '"', 'appearance', 'menu_item', $itemId);
        $this->flushCache();

        return redirect()->to(admin_url('menus/' . $menuId . '/edit'))->with('success', 'Menu item saved.');
    }

    private function saveItemTranslation(int $itemId, string $code, string $label): void
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $row = ['label' => service('sanitizer')->text($label, 190), 'updated_at' => $now];

        $existing = $db->table('menu_item_translations')->where('item_id', $itemId)->where('language_code', $code)->get()->getRowArray();
        if ($existing) {
            $db->table('menu_item_translations')->where('id', (int) $existing['id'])->update($row);
        } else {
            $row += ['item_id' => $itemId, 'language_code' => $code, 'created_at' => $now];
            $db->table('menu_item_translations')->insert($row);
        }
    }

    private function findMenu(int $id): ?array
    {
        $row = db_connect()->table('menus')->where('id', $id)->get()->getRowArray();

        return $row ?: null;
    }

    private function findItem(int $menuId, int $itemId): ?array
    {
        $row = db_connect()->table('menu_items i')
            ->select('i.*, t.label')
            ->join('menu_item_translations t', 't.item_id = i.id AND t.language_code = ' . db_connect()->escape(default_locale()), 'left')
            ->where('i.id', $itemId)
            ->where('i.menu_id', $menuId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    private function resolveUrl(array $item): string
    {
        $type = (string) $item['type'];
        $id   = (int) ($item['target_id'] ?? 0);

        try {
            if ($type === 'content' && $id > 0) {
                $content = service('contentService')->findById($id);

                return $content['url'] ?? admin_url('content/' . $id . '/edit');
            }
            if ($type === 'category' && $id > 0) {
                $row = db_connect()->table('category_translations')->where('category_id', $id)->where('language_code', default_locale())->get()->getRowArray();

                return $row ? category_url(['id' => $id], (string) $row['slug'], (string) $row['language_code']) : '#';
            }
            if ($type === 'tag' && $id > 0) {
                $row = db_connect()->table('tag_translations')->where('tag_id', $id)->where('language_code', default_locale())->get()->getRowArray();

                return $row ? tag_url((string) $row['slug'], (string) $row['language_code']) : '#';
            }
            if ($type === 'section' && $id > 0) {
                $row = db_connect()->table('sections')->where('id', $id)->get()->getRowArray();

                return $row ? section_url($row) : '#';
            }
        } catch (Throwable) {
        }

        return (string) ($item['url'] ?? '#');
    }

    private function contentOptions(): array
    {
        $db = db_connect();

        return $db->table('contents c')
            ->select('c.id, t.title')
            ->join('content_translations t', 't.content_id = c.id AND t.language_code = ' . $db->escape(default_locale()), 'left')
            ->where('c.deleted_at', null)
            ->orderBy('t.title', 'ASC')
            ->limit(200)
            ->get()
            ->getResultArray();
    }

    private function tableOptions(string $table, string $idColumn, string $labelColumn = 'name'): array
    {
        $db = db_connect();
        try {
            return $db->table($table)->select($idColumn . ' AS id, ' . $labelColumn . ' AS label')->orderBy($labelColumn, 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            return [];
        }
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $db   = db_connect();
        $base = $slug;
        $i    = 1;
        while ((int) $db->table('menus')->where('slug', $slug)->where('id !=', $ignoreId ?? 0)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
