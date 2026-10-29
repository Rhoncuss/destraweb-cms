<?php

namespace App\Controllers\Admin;

use Throwable;

class Sections extends BaseAdminController
{
    public function index(): string
    {
        $db = db_connect();
        $sections = $db->table('sections')->orderBy('sort_order', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();

        $contentCounts = [];
        $fieldCounts   = [];
        try {
            foreach ($db->table('contents')->select('section_id, COUNT(*) AS total')->where('deleted_at', null)->groupBy('section_id')->get()->getResultArray() as $row) {
                $contentCounts[(int) $row['section_id']] = (int) $row['total'];
            }
            foreach ($db->table('section_fields')->select('section_id, COUNT(*) AS total')->groupBy('section_id')->get()->getResultArray() as $row) {
                $fieldCounts[(int) $row['section_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/sections/index', [
            'pageTitle'    => 'Sections',
            'pageSubtitle' => 'Sections bundle a content type, a template and its own custom fields.',
            'navActive'    => 'sections',
            'breadcrumbs'  => [['label' => 'Sections']],
            'sections'     => $sections,
            'contentCounts' => $contentCounts,
            'fieldCounts'  => $fieldCounts,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $section = $this->find($id);
        if ($section === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($section);
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
        $used = (int) $db->table('contents')->where('section_id', $id)->where('deleted_at', null)->countAllResults();
        if ($used > 0) {
            return redirect()->to(admin_url('sections'))->with('error', 'This section still has ' . $used . ' content item(s). Move them first.');
        }

        $db->table('section_fields')->where('section_id', $id)->delete();
        $db->table('sections')->where('id', $id)->delete();

        $this->audit('section.delete', 'Deleted section #' . $id, 'content', 'section', $id);
        $this->flushCache();

        return redirect()->to(admin_url('sections'))->with('success', 'Section deleted.');
    }

    private function form(?array $section): string
    {
        $fields = [];
        if ($section !== null) {
            $fields = db_connect()->table('section_fields')->where('section_id', (int) $section['id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();
        }

        return $this->render('admin/sections/form', [
            'pageTitle'    => $section ? 'Edit section' : 'New section',
            'pageSubtitle' => 'Custom fields appear in the editor whenever this section is selected.',
            'navActive'    => 'sections',
            'breadcrumbs'  => [['label' => 'Sections', 'url' => admin_url('sections')], ['label' => $section ? 'Edit' : 'Create']],
            'section'      => $section,
            'fields'       => $fields,
        ]);
    }

    private function save(?int $id)
    {
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $post = $this->request->getPost();
        $san  = service('sanitizer');

        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'A section name is required.');
        }

        $slug = $san->slug($post['slug'] ?? '' ?: $name, 120);
        $base = $slug;
        $i    = 1;
        while ((int) $db->table('sections')->where('slug', $slug)->where('id !=', $id ?? 0)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        $row = [
            'name'        => $name,
            'slug'        => $slug,
            'icon'        => $san->icon($post['icon'] ?? null) ?: null,
            'description' => $san->text($post['description'] ?? null, 500) ?: null,
            'type'        => in_array($post['type'] ?? 'post', ['post', 'page', 'doc'], true) ? $post['type'] : 'post',
            'template'    => $san->text($post['template'] ?? 'default', 60) ?: 'default',
            'visibility'  => in_array($post['visibility'] ?? 'public', ['public', 'private', 'hidden'], true) ? $post['visibility'] : 'public',
            'is_active'   => isset($post['is_active']) ? 1 : 0,
            'sort_order'  => $san->int($post['sort_order'] ?? 0, 0, 9999),
            'updated_at'  => $now,
        ];

        $isNew = $id === null;
        if ($isNew) {
            $row['created_at'] = $now;
            $db->table('sections')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('sections')->where('id', $id)->update($row);
        }

        $this->syncFields($id, (array) ($post['fields'] ?? []));

        $this->audit($isNew ? 'section.create' : 'section.update', 'Saved section "' . $name . '"', 'content', 'section', $id);
        $this->flushCache();

        return redirect()->to(admin_url('sections'))->with('success', 'Section saved.');
    }

    private function syncFields(int $sectionId, array $fields): void
    {
        $db = db_connect();
        $keep = [];
        $sort = 0;

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = service('sanitizer')->text($field['name'] ?? '', 120);
            $key  = strtolower((string) preg_replace('/[^a-z0-9_\-]/i', '', (string) ($field['field_key'] ?? '')));
            if ($name === '' || $key === '') {
                continue;
            }

            $options = trim((string) ($field['options'] ?? ''));
            if ($options !== '') {
                $decoded = json_decode($options, true);
                $options = is_array($decoded)
                    ? json_encode(array_values(array_map('strval', $decoded)), JSON_UNESCAPED_UNICODE)
                    : null;
            } else {
                $options = null;
            }

            $data = [
                'section_id'    => $sectionId,
                'name'          => $name,
                'field_key'     => $key,
                'type'          => in_array($field['type'] ?? 'text', ['text', 'textarea', 'editor', 'rich', 'select', 'checkbox', 'date', 'number', 'url', 'color'], true) ? $field['type'] : 'text',
                'options'       => $options,
                'default_value' => service('sanitizer')->textarea($field['default_value'] ?? null, 2000) ?: null,
                'is_required'   => ! empty($field['is_required']) ? 1 : 0,
                'sort_order'    => $sort++,
                'updated_at'    => date('Y-m-d H:i:s'),
            ];

            $existingId = (int) ($field['id'] ?? 0);
            if ($existingId > 0) {
                $db->table('section_fields')->where('id', $existingId)->where('section_id', $sectionId)->update($data);
                $keep[] = $existingId;
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('section_fields')->insert($data);
                $keep[] = (int) $db->insertID();
            }
        }

        $builder = $db->table('section_fields')->where('section_id', $sectionId);
        if ($keep !== []) {
            $builder->whereNotIn('id', $keep);
        }
        $builder->delete();
    }

    private function find(int $id): ?array
    {
        $row = db_connect()->table('sections')->where('id', $id)->get()->getRowArray();

        return $row ?: null;
    }
}
