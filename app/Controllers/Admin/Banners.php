<?php

namespace App\Controllers\Admin;

use Throwable;

class Banners extends BaseAdminController
{
    public const PLACEMENTS = [
        'home_slider' => 'Home slider',
        'header'      => 'Header banner',
        'sidebar'     => 'Sidebar',
        'inline'      => 'Inline (between content)',
        'footer'      => 'Footer',
    ];

    public function index(): string
    {
        $db        = db_connect();
        $placement = (string) ($this->request->getGet('placement') ?? '');
        $placement = array_key_exists($placement, self::PLACEMENTS) ? $placement : '';

        $builder = $db->table('banners b')
            ->select('b.*, t.title AS title, t.alt AS alt')
            ->join('banner_translations t', 't.banner_id = b.id AND t.language_code = ' . $db->escape(default_locale()), 'left');
        if ($placement !== '') {
            $builder->where('b.placement', $placement);
        }
        $items = $builder->orderBy('b.sort_order', 'ASC')->orderBy('b.id', 'DESC')->get()->getResultArray();

        return $this->render('admin/banners/index', [
            'pageTitle'    => 'Banners & ads',
            'pageSubtitle' => 'Manage hero slides, promotional strips and advertising placements.',
            'navActive'    => 'banners',
            'breadcrumbs'  => [['label' => 'Banners & ads']],
            'items'        => $items,
            'placement'    => $placement,
            'placements'   => self::PLACEMENTS,
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $banner = $this->find($id);
        if ($banner === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($banner);
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
        $db->table('banner_translations')->where('banner_id', $id)->delete();
        $db->table('banners')->where('id', $id)->delete();

        $this->audit('banner.delete', 'Deleted banner #' . $id, 'appearance', 'banner', $id);
        $this->flushCache();

        return redirect()->to(admin_url('banners'))->with('success', 'Banner deleted.');
    }

    public function toggle(int $id)
    {
        $db     = db_connect();
        $banner = $this->find($id);
        if ($banner === null) {
            return $this->fail('Banner not found.', 404);
        }

        $active = empty($banner['is_active']) ? 1 : 0;
        $db->table('banners')->where('id', $id)->update(['is_active' => $active, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flushCache();

        return $this->ok(['is_active' => $active], $active ? 'Banner is now live.' : 'Banner hidden.');
    }

    private function form(?array $banner): string
    {
        $translations = [];
        if ($banner !== null) {
            foreach (db_connect()->table('banner_translations')->where('banner_id', (int) $banner['id'])->get()->getResultArray() as $row) {
                $translations[(string) $row['language_code']] = $row;
            }
        }

        return $this->render('admin/banners/form', [
            'pageTitle'    => $banner ? 'Edit banner' : 'New banner',
            'pageSubtitle' => 'Banners can be an image with text overlays or raw HTML/ads code.',
            'navActive'    => 'banners',
            'breadcrumbs'  => [
                ['label' => 'Banners & ads', 'url' => admin_url('banners')],
                ['label' => $banner ? 'Edit banner' : 'New banner'],
            ],
            'banner'       => $banner,
            'translations' => $translations,
            'languages'    => service('localization')->languages(),
            'defaultLocale' => default_locale(),
            'placements'   => self::PLACEMENTS,
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
            return redirect()->back()->withInput()->with('error', 'A banner name is required.');
        }

        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('banners'))->with('error', 'Banner not found.');
        }

        $placement = (string) ($post['placement'] ?? 'home_slider');
        $placement = array_key_exists($placement, self::PLACEMENTS) ? $placement : 'home_slider';
        $type      = in_array($post['type'] ?? 'image', ['image', 'html'], true) ? $post['type'] : 'image';

        $row = [
            'name'        => $name,
            'placement'   => $placement,
            'type'        => $type,
            'image_id'    => (int) ($post['image_id'] ?? 0) ?: null,
            'html'        => $type === 'html' ? $san->html($post['html'] ?? '') : null,
            'link_url'    => $san->url($post['link_url'] ?? '') ?: null,
            'link_target' => ($post['link_target'] ?? '_self') === '_blank' ? '_blank' : '_self',
            'starts_at'   => $san->date($post['starts_at'] ?? null, true),
            'ends_at'     => $san->date($post['ends_at'] ?? null, true),
            'sort_order'  => $san->int($post['sort_order'] ?? 0, 0, 9999),
            'is_active'   => isset($post['is_active']) ? 1 : 0,
            'updated_at'  => $now,
        ];

        if ($type === 'image' && ! $row['image_id']) {
            return redirect()->back()->withInput()->with('error', 'Choose an image for this banner.');
        }

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('banners')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('banners')->where('id', $id)->update($row);
        }

        $default      = default_locale();
        $translations = (array) ($post['translations'] ?? []);
        $this->saveTranslation($id, $default, [
            'title'     => $post['title'] ?? null,
            'subtitle'  => $post['subtitle'] ?? null,
            'cta_label' => $post['cta_label'] ?? null,
            'alt'       => $post['alt'] ?? null,
        ]);

        foreach ($translations as $code => $translation) {
            $code = (string) $code;
            if ($code === $default || ! is_array($translation)) {
                continue;
            }
            if (trim((string) ($translation['title'] ?? '')) === '') {
                $db->table('banner_translations')->where('banner_id', $id)->where('language_code', $code)->delete();
                continue;
            }
            $this->saveTranslation($id, $code, $translation);
        }

        $this->audit($existing ? 'banner.update' : 'banner.create', ($existing ? 'Updated' : 'Created') . ' banner "' . $name . '"', 'appearance', 'banner', $id);
        $this->flushCache();

        return redirect()->to(admin_url('banners'))->with('success', 'Banner saved.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveTranslation(int $bannerId, string $code, array $data): void
    {
        $san  = service('sanitizer');
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');
        $row  = [
            'title'      => $san->text($data['title'] ?? null, 255) ?: null,
            'subtitle'   => $san->text($data['subtitle'] ?? null, 500) ?: null,
            'cta_label'  => $san->text($data['cta_label'] ?? null, 120) ?: null,
            'alt'        => $san->text($data['alt'] ?? null, 255) ?: null,
            'updated_at' => $now,
        ];

        $existing = $db->table('banner_translations')->where('banner_id', $bannerId)->where('language_code', $code)->get()->getRowArray();
        if ($existing) {
            $db->table('banner_translations')->where('id', (int) $existing['id'])->update($row);
        } elseif (array_filter($row, static fn ($v) => $v !== null) !== []) {
            $db->table('banner_translations')->insert($row + [
                'banner_id'     => $bannerId,
                'language_code' => $code,
                'created_at'    => $now,
            ]);
        }
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('banners')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        return $row ?: null;
    }
}
