<?php

namespace App\Controllers\Admin;

use Throwable;

class Tag extends BaseAdminController
{
    public function index(): string
    {
        $db      = db_connect();
        $default = default_locale();

        $items = $db->table('tags t')
            ->select('t.*, tt.name, tt.slug')
            ->join('tag_translations tt', 'tt.tag_id = t.id AND tt.language_code = ' . $db->escape($default), 'left')
            ->orderBy('tt.name', 'ASC')
            ->get()
            ->getResultArray();

        $counts = [];
        try {
            foreach ($db->table('content_tags')->select('tag_id, COUNT(*) AS total')->groupBy('tag_id')->get()->getResultArray() as $row) {
                $counts[(int) $row['tag_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $translations = [];
        try {
            foreach ($db->table('tag_translations')->get()->getResultArray() as $row) {
                $translations[(int) $row['tag_id']][(string) $row['language_code']] = $row;
            }
        } catch (Throwable) {
        }

        return $this->render('admin/taxonomy/tags', [
            'pageTitle'    => 'Tags',
            'pageSubtitle' => 'Lightweight labels for cross-cutting topics.',
            'navActive'    => 'tags',
            'breadcrumbs'  => [['label' => 'Tags']],
            'items'        => $items,
            'counts'       => $counts,
            'translations' => $translations,
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
        $db = db_connect();
        $db->table('tag_translations')->where('tag_id', $id)->delete();
        $db->table('content_tags')->where('tag_id', $id)->delete();
        $db->table('tags')->where('id', $id)->delete();

        $this->audit('tag.delete', 'Deleted tag #' . $id, 'content', 'tag', $id);
        $this->flushCache();

        return redirect()->to(admin_url('tags'))->with('success', 'Tag deleted.');
    }

    private function form(?array $item): string
    {
        $translations = [];
        if ($item !== null) {
            foreach (db_connect()->table('tag_translations')->where('tag_id', (int) $item['id'])->get()->getResultArray() as $row) {
                $translations[(string) $row['language_code']] = $row;
            }
        }

        return $this->render('admin/taxonomy/tag-form', [
            'pageTitle'    => $item ? 'Edit tag' : 'New tag',
            'pageSubtitle' => 'Tags are multilingual — translate the label for each active language.',
            'navActive'    => 'tags',
            'breadcrumbs'  => [['label' => 'Tags', 'url' => admin_url('tags')], ['label' => $item ? 'Edit' : 'Create']],
            'item'         => $item,
            'translations' => $translations,
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
        $default = default_locale();

        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'A tag name is required.');
        }

        if ($id === null) {
            $db->table('tags')->insert([
                'color'      => $san->color($post['color'] ?? null, '#6d5efc'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $id = (int) $db->insertID();
        } else {
            $db->table('tags')->where('id', $id)->update([
                'color'      => $san->color($post['color'] ?? null, '#6d5efc'),
                'updated_at' => $now,
            ]);
        }

        $this->saveTranslation($id, $default, $name, (string) ($post['slug'] ?? ''));
        foreach ((array) ($post['translations'] ?? []) as $code => $translation) {
            $code = (string) $code;
            if ($code === $default || ! is_array($translation)) {
                continue;
            }
            $trName = trim((string) ($translation['name'] ?? ''));
            if ($trName === '') {
                $db->table('tag_translations')->where('tag_id', $id)->where('language_code', $code)->delete();
                continue;
            }
            $this->saveTranslation($id, $code, $trName, (string) ($translation['slug'] ?? ''));
        }

        $this->audit($id ? 'tag.save' : 'tag.create', 'Saved tag "' . $name . '"', 'content', 'tag', $id);
        $this->flushCache();

        return redirect()->to(admin_url('tags'))->with('success', 'Tag saved.');
    }

    private function saveTranslation(int $tagId, string $code, string $name, string $slug): void
    {
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');
        $san = service('sanitizer');

        $name = $san->text($name, 120);
        if ($name === '') {
            return;
        }

        $slug = $san->slug($slug !== '' ? $slug : $name, 190);
        $base = $slug;
        $i    = 1;
        while ($db->table('tag_translations')->where('language_code', $code)->where('slug', $slug)->where('tag_id !=', $tagId)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        $existing = $db->table('tag_translations')->where('tag_id', $tagId)->where('language_code', $code)->get()->getRowArray();
        $row = ['name' => $name, 'slug' => $slug, 'updated_at' => $now];
        if ($existing) {
            $db->table('tag_translations')->where('id', (int) $existing['id'])->update($row);
        } else {
            $row += ['tag_id' => $tagId, 'language_code' => $code, 'created_at' => $now];
            $db->table('tag_translations')->insert($row);
        }
    }

    private function find(int $id): ?array
    {
        $row = db_connect()->table('tags t')
            ->select('t.*, tt.name, tt.slug')
            ->join('tag_translations tt', 'tt.tag_id = t.id AND tt.language_code = ' . db_connect()->escape(default_locale()), 'left')
            ->where('t.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}
