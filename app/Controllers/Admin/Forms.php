<?php

namespace App\Controllers\Admin;

use Throwable;

class Forms extends BaseAdminController
{
    public const FIELD_TYPES = [
        'text'     => 'Text',
        'email'    => 'Email',
        'tel'      => 'Phone',
        'number'   => 'Number',
        'url'      => 'URL',
        'date'     => 'Date',
        'textarea' => 'Textarea',
        'select'   => 'Select',
        'radio'    => 'Radio group',
        'checkbox' => 'Checkbox',
    ];

    public function index(): string
    {
        $db = db_connect();
        $forms = $db->table('forms')->orderBy('name', 'ASC')->get()->getResultArray();

        $unread = [];
        try {
            foreach ($db->table('form_submissions')->select('form_id, COUNT(*) AS total')->where('is_read', 0)->groupBy('form_id')->get()->getResultArray() as $row) {
                $unread[(int) $row['form_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/forms/index', [
            'pageTitle'    => 'Forms',
            'pageSubtitle' => 'Build contact forms, quote requests and surveys without code.',
            'navActive'    => 'forms',
            'breadcrumbs'  => [['label' => 'Forms']],
            'forms'        => $forms,
            'unread'       => $unread,
            'fieldTypes'   => self::FIELD_TYPES,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $form = $this->find($id);
        if ($form === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($form);
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
        $db->table('form_submissions')->where('form_id', $id)->delete();
        $db->table('forms')->where('id', $id)->delete();

        $this->audit('form.delete', 'Deleted form #' . $id, 'forms', 'form', $id);
        $this->flushCache();

        return redirect()->to(admin_url('forms'))->with('success', 'Form deleted.');
    }

    public function toggle(int $id)
    {
        $form = $this->find($id);
        if ($form === null) {
            return $this->fail('Form not found.', 404);
        }

        $active = empty($form['is_active']) ? 1 : 0;
        db_connect()->table('forms')->where('id', $id)->update(['is_active' => $active, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        return $this->ok(['is_active' => $active], $active ? 'Form enabled.' : 'Form disabled.');
    }

    private function form(?array $form): string
    {
        $fields = [];
        if ($form !== null) {
            $decoded = json_decode((string) ($form['fields'] ?? ''), true);
            $fields  = is_array($decoded) ? $decoded : [];
        }

        return $this->render('admin/forms/form', [
            'pageTitle'    => $form ? 'Edit form' : 'New form',
            'pageSubtitle' => 'Define the fields visitors fill in, then embed the form on any page.',
            'navActive'    => 'forms',
            'breadcrumbs'  => [
                ['label' => 'Forms', 'url' => admin_url('forms')],
                ['label' => $form ? 'Edit form' : 'New form'],
            ],
            'form'       => $form,
            'fields'     => $fields,
            'fieldTypes' => self::FIELD_TYPES,
        ]);
    }

    private function save(?int $id)
    {
        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');

        $name = $san->text($post['name'] ?? '', 120);
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'A form name is required.');
        }

        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('forms'))->with('error', 'Form not found.');
        }

        $fields = $this->normaliseFields((array) ($post['fields'] ?? []));
        if ($fields === []) {
            return redirect()->back()->withInput()->with('error', 'Add at least one field.');
        }

        $row = [
            'name'            => $name,
            'slug'            => $this->uniqueSlug($san->slug($post['slug'] ?? '' ?: $name, 120), $id),
            'description'     => $san->text($post['description'] ?? null, 500) ?: null,
            'fields'          => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'success_message' => $san->text($post['success_message'] ?? null, 500) ?: 'Thank you — your message has been received.',
            'email_to'        => filter_var((string) ($post['email_to'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null,
            'email_subject'   => $san->text($post['email_subject'] ?? null, 255) ?: null,
            'captcha'         => isset($post['captcha']) ? 1 : 0,
            'is_active'       => isset($post['is_active']) ? 1 : 0,
            'updated_at'      => $now,
        ];

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('forms')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('forms')->where('id', $id)->update($row);
        }

        $this->audit($existing ? 'form.update' : 'form.create', ($existing ? 'Updated' : 'Created') . ' form "' . $name . '"', 'forms', 'form', $id);
        $this->flushCache();

        return redirect()->to(admin_url('forms'))->with('success', 'Form saved.');
    }

    /**
     * @param array<mixed> $raw
     *
     * @return list<array<string, mixed>>
     */
    private function normaliseFields(array $raw): array
    {
        $san    = service('sanitizer');
        $fields = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $san->text($item['label'] ?? null, 190);
            if ($label === '') {
                continue;
            }

            $key  = $san->slug($item['key'] ?? '' ?: $label, 60);
            $key  = str_replace('-', '_', $key) ?: 'field_' . (count($fields) + 1);
            $type = (string) ($item['type'] ?? 'text');
            $type = array_key_exists($type, self::FIELD_TYPES) ? $type : 'text';

            $options = [];
            if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $rawOptions = trim((string) ($item['options'] ?? ''));
                $decoded    = $rawOptions !== '' && str_starts_with($rawOptions, '[') ? json_decode($rawOptions, true) : null;
                $list       = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $rawOptions);
                foreach ((array) $list as $option) {
                    $option = $san->text($option, 120);
                    if ($option !== '') {
                        $options[] = $option;
                    }
                }
            }

            $fields[] = [
                'key'         => $key,
                'label'       => $label,
                'type'        => $type,
                'required'    => ! empty($item['required']),
                'placeholder' => $san->text($item['placeholder'] ?? null, 190),
                'options'     => $options,
                'width'       => ($item['width'] ?? 'full') === 'half' ? 'half' : 'full',
            ];
        }

        return array_slice($fields, 0, 30);
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $db   = db_connect();
        $base = $slug !== '' ? $slug : 'form';
        $slug = $base;
        $i    = 1;
        while ((int) $db->table('forms')->where('slug', $slug)->where('id !=', $ignoreId ?? 0)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('forms')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        return $row ?: null;
    }
}
