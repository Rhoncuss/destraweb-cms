<?php

namespace App\Controllers\Admin;

class Themes extends BaseAdminController
{
    public function index(): string
    {
        $manager = service('themeManager');
        $themes  = $manager->available();
        $active  = $manager->activeSlug();

        $cards = [];
        foreach ($themes as $slug => $theme) {
            $cards[] = [
                'slug'        => (string) $slug,
                'name'        => (string) ($theme['name'] ?? $slug),
                'version'     => (string) ($theme['version'] ?? '1.0.0'),
                'author'      => (string) ($theme['author'] ?? 'DESTRA'),
                'description' => (string) ($theme['description'] ?? ''),
                'screenshot'  => $manager->screenshotUrl((string) $slug),
                'active'      => (string) $slug === $active,
                'is_preview'  => (string) session('theme_preview') === (string) $slug,
            ];
        }

        return $this->render('admin/themes/index', [
            'pageTitle'    => 'Themes',
            'pageSubtitle' => 'Swap the public look of the site without touching a line of code.',
            'navActive'    => 'themes',
            'breadcrumbs'  => [['label' => 'Themes']],
            'cards'        => $cards,
            'active'       => $active,
            'preview'      => (string) (session('theme_preview') ?? ''),
            'themesPath'   => config(\Config\Destra::class)->themesPath,
        ]);
    }

    public function activate(string $slug)
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        if (! service('themeManager')->isValid($slug)) {
            return redirect()->to(admin_url('themes'))->with('error', 'That theme is not installed.');
        }

        service('settings')->set('appearance.theme', $slug);
        session()->remove('theme_preview');
        $this->flushCache();
        $this->audit('theme.activate', 'Activated theme ' . $slug, 'themes', 'theme', null, ['slug' => $slug]);

        return redirect()->to(admin_url('themes'))->with('success', 'Theme activated. Visit the site to see it live.');
    }

    public function preview(string $slug)
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        if (! service('themeManager')->isValid($slug)) {
            return redirect()->to(admin_url('themes'))->with('error', 'That theme is not installed.');
        }

        session()->set('theme_preview', $slug);

        return redirect()->to(base_url('/'));
    }

    public function stopPreview()
    {
        session()->remove('theme_preview');

        return redirect()->to(admin_url('themes'))->with('success', 'Theme preview ended.');
    }
}
