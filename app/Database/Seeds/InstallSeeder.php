<?php

namespace App\Database\Seeds;

use App\Libraries\Auth;
use CodeIgniter\Database\Seeder;

/**
 * Installs the default DESTRA data set: roles, permissions, languages,
 * settings, sections, taxonomy, menus, forms, OAuth providers and a polished
 * multilingual demo site.
 */
class InstallSeeder extends Seeder
{
    private string $now;

    private int $adminId = 0;

    /** @var array<string, int> */
    private array $roleIds = [];

    /** @var array<string, int> */
    private array $permissionIds = [];

    /** @var array<string, int> */
    private array $sectionIds = [];

    /** @var array<string, int> */
    private array $categoryIds = [];

    /** @var array<string, int> */
    private array $tagIds = [];

    /** @var array<string, int> */
    private array $contentIds = [];

    /** @var array<string, int> */
    private array $mediaIds = [];

    public function run(): void
    {
        $this->runInstall([
            'site_name'      => 'DESTRA CMS',
            'tagline'        => 'Ideas, crafted beautifully.',
            'admin_name'     => 'Site Administrator',
            'admin_email'    => 'admin@example.com',
            'admin_password' => 'ChangeMe-Now-2026!',
        ]);
    }

    /**
     * @param array{site_name: string, tagline?: string, admin_name: string, admin_email: string, admin_password: string, locale?: string} $options
     */
    public function runInstall(array $options): int
    {
        $this->now = date('Y-m-d H:i:s');

        $this->seedRoles();
        $this->seedPermissions();
        $this->seedAdmin($options);
        $this->seedSettings($options);
        $this->seedLanguages();
        $this->seedMedia();
        $this->seedSections();
        $this->seedCategories();
        $this->seedTags();
        $this->seedContent();
        $this->seedBanners();
        $this->seedMenus();
        $this->seedForms($options);
        $this->seedOAuthProviders();
        $this->seedNotifications();

        return $this->adminId;
    }

    private function seedRoles(): void
    {
        $roles = [
            'super-admin' => ['Super Admin', 'Full unrestricted access.', 1],
            'admin'       => ['Administrator', 'Manages the site and its team.', 1],
            'editor'      => ['Editor', 'Curates content, media and taxonomy.', 1],
            'author'      => ['Author', 'Writes and publishes their own content.', 1],
            'contributor' => ['Contributor', 'Writes drafts for editorial review.', 1],
        ];

        foreach ($roles as $slug => [$name, $description, $system]) {
            $row = $this->db->table('roles')->where('slug', $slug)->get()->getRowArray();
            if ($row) {
                $this->roleIds[$slug] = (int) $row['id'];
                continue;
            }

            $this->db->table('roles')->insert([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'is_system'   => $system,
                'created_at'  => $this->now,
                'updated_at'  => $this->now,
            ]);
            $this->roleIds[$slug] = (int) $this->db->insertID();
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'dashboard.view'    => ['View dashboard', 'General'],
            'content.view'      => ['View content', 'Content'],
            'content.create'    => ['Create content', 'Content'],
            'content.edit'      => ['Edit content', 'Content'],
            'content.delete'    => ['Delete content', 'Content'],
            'content.publish'   => ['Publish content', 'Content'],
            'sections.manage'   => ['Manage sections', 'Structure'],
            'categories.manage' => ['Manage categories', 'Structure'],
            'tags.manage'       => ['Manage tags', 'Structure'],
            'menus.manage'      => ['Manage menus', 'Structure'],
            'media.manage'      => ['Manage media library', 'Media'],
            'appearance.manage' => ['Manage appearance, themes and banners', 'Appearance'],
            'forms.manage'      => ['Manage forms, inbox and orders', 'Engagement'],
            'comments.moderate' => ['Moderate comments', 'Engagement'],
            'orders.manage'     => ['Manage orders', 'Engagement'],
            'seo.manage'        => ['Manage SEO and redirects', 'Growth'],
            'marketing.manage'  => ['Manage newsletter and marketing', 'Growth'],
            'reports.view'      => ['View reports', 'Growth'],
            'users.manage'      => ['Manage users', 'System'],
            'roles.manage'      => ['Manage roles and permissions', 'System'],
            'languages.manage'  => ['Manage languages and translations', 'System'],
            'plugins.manage'    => ['Manage plugins', 'System'],
            'settings.manage'   => ['Manage settings', 'System'],
            'tools.manage'      => ['Use system tools', 'System'],
            'api.manage'        => ['Manage API tokens', 'System'],
            'audit.view'        => ['View audit trail', 'System'],
        ];

        foreach ($permissions as $slug => [$name, $group]) {
            $row = $this->db->table('permissions')->where('slug', $slug)->get()->getRowArray();
            if ($row) {
                $this->permissionIds[$slug] = (int) $row['id'];
                continue;
            }

            $this->db->table('permissions')->insert([
                'name'       => $name,
                'slug'       => $slug,
                'group_name' => $group,
            ]);
            $this->permissionIds[$slug] = (int) $this->db->insertID();
        }

        $editor = [
            'dashboard.view', 'content.view', 'content.create', 'content.edit', 'content.delete', 'content.publish',
            'sections.manage', 'categories.manage', 'tags.manage', 'menus.manage', 'media.manage',
            'forms.manage', 'comments.moderate', 'orders.manage', 'seo.manage', 'marketing.manage', 'reports.view',
        ];
        $author = ['dashboard.view', 'content.view', 'content.create', 'content.edit', 'media.manage'];
        $contributor = ['dashboard.view', 'content.view', 'content.create'];

        $this->assignPermissions('super-admin', array_keys($this->permissionIds));
        $this->assignPermissions('admin', array_keys($this->permissionIds));
        $this->assignPermissions('editor', $editor);
        $this->assignPermissions('author', $author);
        $this->assignPermissions('contributor', $contributor);
    }

    /**
     * @param list<string> $slugs
     */
    private function assignPermissions(string $roleSlug, array $slugs): void
    {
        $roleId = $this->roleIds[$roleSlug] ?? 0;
        if ($roleId <= 0) {
            return;
        }

        foreach ($slugs as $slug) {
            $permissionId = $this->permissionIds[$slug] ?? 0;
            if ($permissionId <= 0) {
                continue;
            }
            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    private function seedAdmin(array $options): void
    {
        $email    = strtolower(trim((string) $options['admin_email']));
        $name     = trim((string) $options['admin_name']);
        $password = (string) $options['admin_password'];
        $roleId   = $this->roleIds['super-admin'] ?? 1;

        $existing = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if ($existing) {
            $this->adminId = (int) $existing['id'];
            $this->db->table('users')->where('id', $this->adminId)->update([
                'name'          => $name !== '' ? $name : $existing['name'],
                'password_hash' => Auth::hashPassword($password),
                'role_id'       => $roleId,
                'status'        => 'active',
                'updated_at'    => $this->now,
            ]);

            return;
        }

        $username = $this->uniqueUsername('admin');

        $this->db->table('users')->insert([
            'name'              => $name !== '' ? $name : 'Site Administrator',
            'username'          => $username,
            'email'             => $email,
            'password_hash'     => Auth::hashPassword($password),
            'role_id'           => $roleId,
            'locale'            => (string) ($options['locale'] ?? 'en'),
            'timezone'          => 'Asia/Jakarta',
            'bio'               => 'Founder and editor of this publication.',
            'status'            => 'active',
            'email_verified_at' => $this->now,
            'meta'              => json_encode(['admin_scheme' => 'midnight']),
            'created_at'        => $this->now,
            'updated_at'        => $this->now,
        ]);
        $this->adminId = (int) $this->db->insertID();
    }

    private function seedSettings(array $options): void
    {
        $defaults = \Config\SiteDefaults::settings();
        $values   = [];
        foreach ($defaults as $key => [$value]) {
            if (is_array($value)) {
                $value = implode(',', $value);
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $values[$key] = (string) $value;
        }

        $siteName = trim((string) ($options['site_name'] ?? 'DESTRA CMS'));
        $email    = strtolower(trim((string) $options['admin_email']));

        $values['general.site_name']   = $siteName !== '' ? $siteName : 'DESTRA CMS';
        $values['general.tagline']     = (string) ($options['tagline'] ?? 'Ideas, crafted beautifully.');
        $values['general.admin_email'] = $email;
        $values['general.registration_role'] = (string) ($this->roleIds['author'] ?? 4);
        $values['mail.from_email']     = $email;
        $values['mail.notify_email']   = $email;
        $values['mail.from_name']      = $values['general.site_name'];
        $values['orders.notify_email'] = $email;
        $values['seo.description']     = $values['general.site_name'] . ' — ' . $values['general.tagline'];
        $values['appearance.admin_scheme'] = 'midnight';

        foreach ($defaults as $key => [$default, $group, $type]) {
            $exists = $this->db->table('settings')->where('setting_key', $key)->countAllResults();
            $data = [
                'setting_group' => $group,
                'setting_key'   => $key,
                'setting_value' => $values[$key] ?? '',
                'setting_type'  => $type,
                'is_encrypted'  => 0,
                'updated_at'    => $this->now,
            ];

            if ($exists > 0) {
                $this->db->table('settings')->where('setting_key', $key)->update($data);
            } else {
                $data['created_at'] = $this->now;
                $this->db->table('settings')->insert($data);
            }
        }
    }

    private function seedLanguages(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native' => 'English', 'direction' => 'ltr', 'flag' => 'us', 'default' => 1, 'sort' => 1],
            ['code' => 'id', 'name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'direction' => 'ltr', 'flag' => 'id', 'default' => 0, 'sort' => 2],
            ['code' => 'ar', 'name' => 'Arabic', 'native' => 'العربية', 'direction' => 'rtl', 'flag' => 'sa', 'default' => 0, 'sort' => 3],
        ];

        foreach ($languages as $language) {
            $exists = $this->db->table('languages')->where('code', $language['code'])->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $this->db->table('languages')->insert([
                'code'        => $language['code'],
                'name'        => $language['name'],
                'native_name' => $language['native'],
                'direction'   => $language['direction'],
                'flag'        => $language['flag'],
                'is_default'  => $language['default'],
                'is_active'   => 1,
                'sort_order'  => $language['sort'],
                'created_at'  => $this->now,
                'updated_at'  => $this->now,
            ]);
        }
    }

    private function seedMedia(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        $demo = [
            'hero'     => ['destra-hero-studio.webp', 'Team collaborating in a bright studio'],
            'security' => ['destra-security-ops.webp', 'Security operations dashboard on a desk'],
            'toolkit'  => ['destra-toolkit-flatlay.webp', 'Publishing toolkit arranged on a desk'],
        ];
        $dir = rtrim(ROOTPATH, '/\\') . '/public/assets/demo';

        foreach ($demo as $key => [$file, $alt]) {
            $original = pathinfo($file, PATHINFO_FILENAME) . '.webp';
            $existing = $this->db->table('media')->where('original_name', $original)->get()->getRowArray();
            if ($existing) {
                $this->mediaIds[$key] = (int) $existing['id'];

                continue;
            }

            $path = $dir . '/' . $file;
            if (! is_file($path)) {
                continue;
            }

            $binary = @file_get_contents($path);
            if ($binary === false) {
                continue;
            }

            try {
                $result = service('media')->storeImageBinary($binary, pathinfo($file, PATHINFO_FILENAME), $this->adminId);
            } catch (\Throwable) {
                continue;
            }

            $id = (int) ($result['media']['id'] ?? 0);
            if (empty($result['ok']) || $id <= 0) {
                continue;
            }

            $this->mediaIds[$key] = $id;
            $this->db->table('media')->where('id', $id)->update(['alt' => $alt]);
        }
    }

    private function seedBanners(): void
    {
        $banners = [
            [
                'name'      => 'Launch hero',
                'placement' => 'home_slider',
                'media'     => 'hero',
                'link'      => null,
                'target'    => '_self',
                'sort'      => 1,
                'translations' => [
                    'en' => ['Build something remarkable', 'The fastest way to launch your next content platform.', 'Get started', 'Team collaborating in a bright studio'],
                    'id' => ['Bangun sesuatu yang luar biasa', 'Cara tercepat meluncurkan platform konten berikutnya.', 'Mulai sekarang', 'Tim berkolaborasi di studio yang terang'],
                ],
            ],
            [
                'name'      => 'Header sponsor',
                'placement' => 'header',
                'media'     => 'hero',
                'link'      => 'https://example.com/sponsor',
                'target'    => '_blank',
                'sort'      => 1,
                'translations' => [
                    'en' => ['Ship faster with DESTRA Cloud', 'Managed hosting for growing publishers.', 'Explore plans', 'DESTRA Cloud banner'],
                    'id' => ['Rilis lebih cepat dengan DESTRA Cloud', 'Hosting terkelola untuk penerbit yang berkembang.', 'Lihat paket', 'Banner DESTRA Cloud'],
                ],
            ],
            [
                'name'      => 'Footer sponsor',
                'placement' => 'footer',
                'media'     => 'toolkit',
                'link'      => 'https://example.com/tools',
                'target'    => '_blank',
                'sort'      => 1,
                'translations' => [
                    'en' => ['The 2026 publishing toolkit', 'Templates, checklists and launch guides.', 'Download free', 'Publishing toolkit banner'],
                    'id' => ['Perangkat penerbitan 2026', 'Template, checklist, dan panduan peluncuran.', 'Unduh gratis', 'Banner perangkat penerbitan'],
                ],
            ],
        ];

        foreach ($banners as $banner) {
            $exists = $this->db->table('banners')
                ->where('name', $banner['name'])
                ->where('placement', $banner['placement'])
                ->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $this->db->table('banners')->insert([
                'name'       => $banner['name'],
                'placement'  => $banner['placement'],
                'type'       => 'image',
                'image_id'   => $this->mediaIds[$banner['media']] ?? null,
                'link_url'   => $banner['link'],
                'link_target'=> $banner['target'],
                'sort_order' => $banner['sort'],
                'is_active'  => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $bannerId = (int) $this->db->insertID();

            foreach ($banner['translations'] as $language => [$title, $subtitle, $cta, $alt]) {
                $this->db->table('banner_translations')->insert([
                    'banner_id'     => $bannerId,
                    'language_code' => $language,
                    'title'         => $title,
                    'subtitle'      => $subtitle,
                    'cta_label'     => $cta,
                    'alt'           => $alt,
                    'created_at'    => $this->now,
                    'updated_at'    => $this->now,
                ]);
            }
        }
    }

    private function seedSections(): void
    {
        $sections = [
            ['blog', 'Blog', 'fa-solid fa-newspaper', 'Stories, essays and product notes.', 'post', 'blog', 'public', 1],
            ['docs', 'Documentation', 'fa-solid fa-book-open', 'Guides and reference material.', 'doc', 'docs', 'public', 2],
            ['pages', 'Pages', 'fa-solid fa-file-lines', 'Standalone pages such as about and privacy.', 'page', 'default', 'hidden', 3],
        ];

        foreach ($sections as [$slug, $name, $icon, $description, $type, $template, $visibility, $sort]) {
            $row = $this->db->table('sections')->where('slug', $slug)->get()->getRowArray();
            if ($row) {
                $this->sectionIds[$slug] = (int) $row['id'];
                continue;
            }
            $this->db->table('sections')->insert([
                'name'        => $name,
                'slug'        => $slug,
                'icon'        => $icon,
                'description' => $description,
                'type'        => $type,
                'template'    => $template,
                'visibility'  => $visibility,
                'is_active'   => 1,
                'sort_order'  => $sort,
                'settings'    => json_encode(['layout' => $slug === 'docs' ? 'sidebar' : 'grid']),
                'created_at'  => $this->now,
                'updated_at'  => $this->now,
            ]);
            $this->sectionIds[$slug] = (int) $this->db->insertID();
        }

        $fields = [
            ['blog', 'Subtitle', 'subtitle', 'text', null, false, 1],
            ['blog', 'Hero layout', 'hero_layout', 'select', json_encode(['split', 'wide', 'minimal']), false, 2],
            ['docs', 'Difficulty', 'difficulty', 'select', json_encode(['Beginner', 'Intermediate', 'Advanced']), false, 1],
        ];

        foreach ($fields as [$section, $name, $key, $type, $options, $required, $sort]) {
            $sectionId = $this->sectionIds[$section] ?? 0;
            if ($sectionId <= 0) {
                continue;
            }
            $exists = $this->db->table('section_fields')->where('section_id', $sectionId)->where('field_key', $key)->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $this->db->table('section_fields')->insert([
                'section_id'     => $sectionId,
                'name'           => $name,
                'field_key'      => $key,
                'type'           => $type,
                'options'        => $options,
                'default_value'  => null,
                'is_required'    => $required ? 1 : 0,
                'sort_order'     => $sort,
                'created_at'     => $this->now,
                'updated_at'     => $this->now,
            ]);
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['news', 'News', 'Berita', 'Updates from the studio.', 'fa-solid fa-bolt', '#6366f1'],
            ['guides', 'Guides', 'Panduan', 'Practical how-to articles.', 'fa-solid fa-compass', '#0ea5e9'],
            ['product', 'Product', 'Produk', 'Releases and feature deep-dives.', 'fa-solid fa-cube', '#f59e0b'],
            ['security', 'Security', 'Keamanan', 'Hardening, privacy and trust.', 'fa-solid fa-shield-halved', '#10b981'],
        ];

        foreach ($categories as $index => [$slug, $name, $nameId, $description, $icon, $color]) {
            $translation = $this->db->table('category_translations')->where('language_code', 'en')->where('slug', $slug)->get()->getRowArray();
            if ($translation) {
                $this->categoryIds[$slug] = (int) $translation['category_id'];
                continue;
            }

            $this->db->table('categories')->insert([
                'section_id' => $this->sectionIds['blog'] ?? null,
                'parent_id'  => null,
                'icon'       => $icon,
                'color'      => $color,
                'sort_order' => $index + 1,
                'is_active'  => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $categoryId = (int) $this->db->insertID();
            $this->categoryIds[$slug] = $categoryId;

            foreach ([['en', $name, $description], ['id', $nameId, $description]] as [$lang, $title, $desc]) {
                $this->db->table('category_translations')->insert([
                    'category_id'     => $categoryId,
                    'language_code'   => $lang,
                    'name'            => $title,
                    'slug'            => $slug,
                    'description'     => $desc,
                    'seo_title'       => $title,
                    'seo_description' => $desc,
                    'created_at'      => $this->now,
                    'updated_at'      => $this->now,
                ]);
            }
        }
    }

    private function seedTags(): void
    {
        $tags = [
            ['cms', 'CMS', 'CMS', '#6366f1'],
            ['security', 'Security', 'Keamanan', '#10b981'],
            ['design', 'Design', 'Desain', '#ec4899'],
            ['performance', 'Performance', 'Performa', '#f59e0b'],
            ['workflow', 'Workflow', 'Alur kerja', '#0ea5e9'],
        ];

        foreach ($tags as [$slug, $name, $nameId, $color]) {
            $translation = $this->db->table('tag_translations')->where('language_code', 'en')->where('slug', $slug)->get()->getRowArray();
            if ($translation) {
                $this->tagIds[$slug] = (int) $translation['tag_id'];
                continue;
            }

            $this->db->table('tags')->insert([
                'color'      => $color,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
            $tagId = (int) $this->db->insertID();
            $this->tagIds[$slug] = $tagId;

            foreach ([['en', $name], ['id', $nameId]] as [$lang, $label]) {
                $this->db->table('tag_translations')->insert([
                    'tag_id'        => $tagId,
                    'language_code' => $lang,
                    'name'          => $label,
                    'slug'          => $slug,
                    'created_at'    => $this->now,
                    'updated_at'    => $this->now,
                ]);
            }
        }
    }

    private function seedContent(): void
    {
        $welcomeBody = '<p>DESTRA is a multilingual publishing platform designed for teams who care about craft. It combines a fast public site, a calm editorial workspace and a clean REST API in one package.</p>'
            . '<h2>Built for the way you publish</h2>'
            . '<p>Structure your content with sections, categories and tags. Every article, documentation page and landing page shares the same secure core while keeping its own template and custom fields.</p>'
            . '<ul><li>Drag-and-drop menus and modular sections</li><li>Three frontend themes with a shared component system</li><li>Roles, permissions and a complete audit trail</li><li>SEO titles, canonical URLs, hreflang and structured data</li></ul>'
            . '<blockquote>Great publishing software should feel invisible. You write, the platform handles the rest.</blockquote>'
            . '[button url="/docs/getting-started" style="primary"]Read the documentation[/button]'
            . '[alert type="info" title="Did you know?"]Every module can be switched on or off from the admin settings — no code changes required.[/alert]';

        $welcomeIdBody = '<p>DESTRA adalah platform penerbitan multibahasa yang dirancang untuk tim yang peduli pada kualitas. Situs publik yang cepat, ruang kerja editorial yang tenang dan API REST yang rapi dalam satu paket.</p>'
            . '<h2>Dibangun untuk cara Anda menerbitkan</h2>'
            . '<p>Kelola konten dengan section, kategori dan tag. Setiap artikel, halaman dokumentasi dan halaman mandiri berbagi inti keamanan yang sama.</p>'
            . '[button url="/id/docs/getting-started" style="primary"]Baca dokumentasi[/button]';

        $this->createContent([
            'section'      => 'blog',
            'type'         => 'post',
            'slug'         => 'welcome-to-destra-cms',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'sticky'       => 1,
            'image'        => 'hero',
            'translations' => [
                'en' => [
                    'title'   => 'Welcome to DESTRA CMS',
                    'excerpt' => 'A calm, multilingual publishing platform that brings content, design and security together.',
                    'body'    => $welcomeBody,
                    'seo'     => ['title' => 'Welcome to DESTRA CMS — a modern multilingual platform', 'description' => 'Meet DESTRA CMS: sections, themes, plugins, roles, SEO and a clean REST API in one elegant platform.', 'keywords' => 'destra cms, multilingual cms, publishing platform'],
                    'fields'  => ['subtitle' => 'One workspace for writers, designers and engineers.', 'hero_layout' => 'wide'],
                ],
                'id' => [
                    'title'   => 'Selamat datang di DESTRA CMS',
                    'slug'    => 'selamat-datang-di-destra-cms',
                    'excerpt' => 'Platform penerbitan multibahasa yang tenang, menyatukan konten, desain dan keamanan.',
                    'body'    => $welcomeIdBody,
                    'seo'     => ['title' => 'Selamat datang di DESTRA CMS', 'description' => 'DESTRA CMS: section, tema, plugin, peran, SEO dan API REST dalam satu platform elegan.', 'keywords' => 'destra cms, cms multibahasa'],
                    'fields'  => ['subtitle' => 'Satu ruang kerja untuk penulis, desainer dan insinyur.', 'hero_layout' => 'wide'],
                ],
            ],
            'categories'   => ['news', 'guides'],
            'tags'         => ['cms', 'design'],
            'comments'     => 1,
        ], 'welcome');

        $this->createContent([
            'section'      => 'blog',
            'type'         => 'post',
            'slug'         => 'designing-a-calm-content-workflow',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'image'        => 'toolkit',
            'translations' => [
                'en' => [
                    'title'   => 'Designing a calm content workflow',
                    'excerpt' => 'How a small editorial team can ship thoughtful work without drowning in tabs.',
                    'body'    => '<p>Calm is a design constraint, not an accident. A content workflow should reduce the number of decisions between an idea and a published page.</p>'
                        . '<h2>Start with the shape of the page</h2><p>Give every section one job. A blog index should invite reading; a documentation home should answer questions. When the layout communicates the task, the editor can focus on the words.</p>'
                        . '<h2>Write in the open</h2><p>Drafts, review notes and publishing checklists belong next to the article. DESTRA keeps status, schedule and SEO metadata in a single editing surface.</p>'
                        . '<p>Finally, measure lightly. A small dashboard with the few numbers that matter beats a wall of vanity metrics.</p>',
                    'seo'     => ['title' => 'Designing a calm content workflow', 'description' => 'A practical approach to editorial systems that reduce friction and help small teams publish consistently.', 'keywords' => 'editorial workflow, content design, cms'],
                ],
                'id' => [
                    'title'   => 'Merancang alur kerja konten yang tenang',
                    'slug'    => 'merancang-alur-kerja-konten-yang-tenang',
                    'excerpt' => 'Cara tim editorial kecil merilis karya yang matang tanpa tenggelam dalam banyak tab.',
                    'body'    => '<p>Tenang adalah batasan desain, bukan kebetulan. Alur kerja konten harus mengurangi keputusan antara ide dan halaman yang terbit.</p><h2>Mulai dari bentuk halaman</h2><p>Beri setiap section satu tugas. Halaman blog mengundang membaca; beranda dokumentasi menjawab pertanyaan.</p>',
                    'seo'     => ['title' => 'Merancang alur kerja konten yang tenang', 'description' => 'Pendekatan praktis untuk sistem editorial yang mengurangi gesekan.', 'keywords' => 'alur kerja editorial, desain konten'],
                ],
            ],
            'categories'   => ['guides'],
            'tags'         => ['design', 'workflow'],
        ], 'calm');

        $this->createContent([
            'section'      => 'blog',
            'type'         => 'post',
            'slug'         => 'security-hardening-checklist',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'image'        => 'security',
            'translations' => [
                'en' => [
                    'title'   => 'A security hardening checklist for small teams',
                    'excerpt' => 'The high-leverage controls that keep a CMS safe without a dedicated security department.',
                    'body'    => '<p>Most incidents start with a handful of avoidable mistakes. This checklist focuses on controls that are cheap to adopt and expensive to skip.</p>'
                        . '<h2>Identity</h2><ul><li>Argon2id password hashing and a minimum length of ten characters.</li><li>Rate limiting on login, registration and password resets.</li><li>Session rotation after privilege changes.</li></ul>'
                        . '<h2>Input and output</h2><ul><li>Allow-list rich HTML and strip everything else.</li><li>Never concatenate user input into SQL.</li><li>Serve uploads outside the document root.</li></ul>'
                        . '<h2>Operations</h2><ul><li>Keep an audit trail of administrative actions.</li><li>Encrypt secrets at rest with a key outside the database.</li><li>Back up nightly and test restoring.</li></ul>'
                        . '<blockquote>Security is a property of the whole system, not a plugin you install at the end.</blockquote>',
                    'seo'     => ['title' => 'Security hardening checklist for small teams', 'description' => 'Practical CMS security controls: Argon2id, CSRF, CSP, upload isolation, audit trails and encrypted secrets.', 'keywords' => 'cms security, hardening, argon2id, csrf, csp'],
                ],
            ],
            'categories'   => ['security'],
            'tags'         => ['security', 'cms'],
        ], 'security');

        $this->createContent([
            'section'      => 'blog',
            'type'         => 'post',
            'slug'         => 'multilingual-sites-without-chaos',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'image'        => 'hero',
            'translations' => [
                'en' => [
                    'title'   => 'Building multilingual sites without the chaos',
                    'excerpt' => 'A translation model that keeps URLs, SEO and editorial intent in sync.',
                    'body'    => '<p>Multilingual publishing fails when translations are treated as copies. The better model is a single piece of content with per-language expressions.</p>'
                        . '<h2>One identity, many voices</h2><p>Keep a stable content identity and store titles, slugs and bodies per language. Links, hreflang and sitemaps then follow naturally.</p>'
                        . '<h2>URLs that make sense</h2><p>Use the default language at the root and prefix every other language. It is predictable for readers and search engines alike.</p>'
                        . '<p>Finally, allow editors to see which languages are missing. A small completeness indicator prevents silent gaps.</p>',
                    'seo'     => ['title' => 'Building multilingual sites without the chaos', 'description' => 'How DESTRA models translations, friendly URLs, hreflang and sitemaps for multilingual publishing.', 'keywords' => 'multilingual cms, hreflang, seo'],
                ],
            ],
            'categories'   => ['guides', 'product'],
            'tags'         => ['cms', 'design'],
        ], 'multilingual');

        $this->createContent([
            'section'      => 'docs',
            'type'         => 'doc',
            'slug'         => 'getting-started',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'translations' => [
                'en' => [
                    'title'   => 'Getting started with DESTRA CMS',
                    'excerpt' => 'Install, sign in and publish your first article in under ten minutes.',
                    'body'    => '<p>This guide walks through the first ten minutes with DESTRA. The installer creates the database schema, a super administrator and a polished demo site.</p>'
                        . '<h2>1. Run the installer</h2><p>Open <code>/install</code> in your browser. The wizard checks your server, validates the database connection and writes a hardened <code>.env</code> file.</p>'
                        . '<h2>2. Sign in</h2><p>Use the administrator account you created. Every new session is bound to a browser fingerprint and expires after inactivity.</p>'
                        . '<h2>3. Publish</h2><p>Create content from <strong>Content → New</strong>. Choose a section, write in one or more languages and set the SEO fields. Drafts stay private until you publish.</p>'
                        . '[alert type="warning" title="Before going live"]Change the demo email, connect SMTP and enable HTTPS in your hosting panel.[/alert]',
                    'seo'     => ['title' => 'Getting started with DESTRA CMS', 'description' => 'Install DESTRA CMS, sign in and publish your first multilingual article.', 'keywords' => 'destra install, getting started'],
                    'fields'  => ['difficulty' => 'Beginner'],
                ],
            ],
            'tags'         => ['cms'],
        ], 'getting-started');

        $this->createContent([
            'section'      => 'docs',
            'type'         => 'doc',
            'slug'         => 'theme-development',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            'translations' => [
                'en' => [
                    'title'   => 'Theme development guide',
                    'excerpt' => 'Create a standalone theme with its own views, assets and manifest.',
                    'body'    => '<p>A DESTRA theme lives in <code>themes/{slug}</code> and is described by <code>theme.json</code>. Views use the <code>Theme\Views</code> namespace and can fall back to the shared layout.</p>'
                        . '<h2>Manifest</h2><pre><code>{ "name": "My theme", "version": "1.0.0", "author": "You", "screenshot": "screenshot.jpg" }</code></pre>'
                        . '<h2>Assets</h2><p>Place CSS, JavaScript and images in <code>assets/</code>. They are served from <code>/theme-assets/{slug}/</code> with cache-busting.</p>'
                        . '<h2>Views</h2><p>Required views are <code>layout</code>, <code>home</code>, <code>content</code>, <code>section</code>, <code>category</code>, <code>tag</code>, <code>search</code>, <code>author</code>, <code>page</code> and <code>error</code>.</p>'
                        . '<p>Use the helper functions such as <code>theme_colors()</code>, <code>render_menu()</code> and <code>content_url()</code> to stay compatible with the core.</p>',
                    'seo'     => ['title' => 'Theme development guide', 'description' => 'Build a standalone DESTRA theme with views, assets and a manifest.', 'keywords' => 'destra theme, theme development'],
                    'fields'  => ['difficulty' => 'Intermediate'],
                ],
            ],
            'tags'         => ['design', 'cms'],
        ], 'theme-development');

        $this->createContent([
            'section'      => 'pages',
            'type'         => 'page',
            'slug'         => 'about',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'image'        => 'hero',
            'translations' => [
                'en' => [
                    'title'   => 'About DESTRA',
                    'excerpt' => 'A small studio building calm, dependable publishing tools.',
                    'body'    => '<p>DESTRA began as a simple question: why does publishing software feel so noisy? We build tools that respect attention — yours and your readers\'.</p>'
                        . '<h2>What we value</h2><ul><li>Clarity over cleverness.</li><li>Security as a default, not an upgrade.</li><li>Design that disappears while you work.</li></ul>'
                        . '<p>This demo site is the default DESTRA installation. Replace it with your own story, colours and content.</p>',
                    'seo'     => ['title' => 'About DESTRA', 'description' => 'The story and values behind the DESTRA publishing platform.', 'keywords' => 'about destra'],
                ],
                'id' => [
                    'title'   => 'Tentang DESTRA',
                    'slug'    => 'tentang-destra',
                    'excerpt' => 'Studio kecil yang membangun alat penerbitan yang tenang dan andal.',
                    'body'    => '<p>DESTRA lahir dari pertanyaan sederhana: mengapa perangkat lunak penerbitan terasa begitu bising? Kami membangun alat yang menghormati perhatian Anda.</p>',
                    'seo'     => ['title' => 'Tentang DESTRA', 'description' => 'Kisah dan nilai di balik platform penerbitan DESTRA.', 'keywords' => 'tentang destra'],
                ],
            ],
        ], 'about');

        $this->createContent([
            'section'      => 'pages',
            'type'         => 'page',
            'slug'         => 'privacy-policy',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-9 days')),
            'translations' => [
                'en' => [
                    'title'   => 'Privacy policy',
                    'excerpt' => 'What we collect, why we collect it and how to remove it.',
                    'body'    => '<p>We collect the minimum data required to operate this website: account details for editors, anonymous visit counts and messages you choose to send us.</p>'
                        . '<h2>Cookies</h2><p>We use a session cookie for signed-in editors and a CSRF token cookie. Analytics, when enabled, is configured to respect visitor privacy.</p>'
                        . '<h2>Your rights</h2><p>Write to the address on the contact page to request an export or deletion of your data. We respond within thirty days.</p>',
                    'seo'     => ['title' => 'Privacy policy', 'description' => 'How DESTRA handles data, cookies and privacy requests.', 'keywords' => 'privacy, cookies'],
                ],
            ],
        ], 'privacy');

        $this->createContent([
            'section'      => 'pages',
            'type'         => 'page',
            'slug'         => 'contact',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-9 days')),
            'translations' => [
                'en' => [
                    'title'   => 'Contact',
                    'excerpt' => 'Questions, partnership ideas or a bug report — we read every message.',
                    'body'    => '<p>We would love to hear from you. Use the form below or write to <strong>hello@example.com</strong>.</p>'
                        . '<p>We usually reply within two business days.</p>',
                    'seo'     => ['title' => 'Contact', 'description' => 'Contact the DESTRA team.', 'keywords' => 'contact'],
                ],
            ],
        ], 'contact');

        $this->createContent([
            'section'      => 'pages',
            'type'         => 'page',
            'slug'         => 'request-a-quote',
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            'image'        => 'toolkit',
            'order_form'   => 1,
            'translations' => [
                'en' => [
                    'title'   => 'Request a quote',
                    'excerpt' => 'Tell us about your project and we will send a tailored proposal.',
                    'body'    => '<p>We help teams plan, build and launch multilingual content platforms. Share a few details and our team replies within one business day.</p>',
                    'seo'     => ['title' => 'Request a quote', 'description' => 'Request a tailored proposal for your next DESTRA CMS project.', 'keywords' => 'quote, project proposal'],
                ],
                'id' => [
                    'title'   => 'Minta penawaran',
                    'slug'    => 'minta-penawaran',
                    'excerpt' => 'Ceritakan proyek Anda dan kami akan mengirim proposal yang sesuai.',
                    'body'    => '<p>Kami membantu tim merencanakan, membangun, dan meluncurkan platform konten multibahasa. Bagikan sedikit detail dan tim kami membalas dalam satu hari kerja.</p>',
                    'seo'     => ['title' => 'Minta penawaran', 'description' => 'Minta proposal khusus untuk proyek DESTRA CMS Anda berikutnya.', 'keywords' => 'penawaran, proposal proyek'],
                ],
            ],
        ], 'quote');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createContent(array $data, string $key): void
    {
        $sectionId = $this->sectionIds[$data['section']] ?? null;
        if ($sectionId === null) {
            return;
        }

        $first = reset($data['translations']);
        $slug  = (string) $data['slug'];

        $existing = $this->db->table('content_translations')->where('language_code', 'en')->where('slug', $slug)->get()->getRowArray();
        if ($existing) {
            $this->contentIds[$key] = (int) $existing['content_id'];

            return;
        }

        $this->db->table('contents')->insert([
            'section_id'       => $sectionId,
            'type'             => (string) $data['type'],
            'author_id'        => $this->adminId,
            'status'           => (string) $data['status'],
            'template'         => $data['type'] === 'page' ? 'page' : null,
            'format'           => 'standard',
            'featured_image_id'=> isset($data['image']) ? ($this->mediaIds[(string) $data['image']] ?? null) : null,
            'sticky'           => (int) ($data['sticky'] ?? 0),
            'allow_comments'   => (int) ($data['comments'] ?? 1),
            'order_form'       => (int) ($data['order_form'] ?? 0),
            'published_at'     => $data['published_at'],
            'expires_at'       => null,
            'views'            => 0,
            'sort_order'       => 0,
            'created_at'       => $this->now,
            'updated_at'       => $this->now,
        ]);
        $contentId = (int) $this->db->insertID();
        $this->contentIds[$key] = $contentId;

        foreach ($data['translations'] as $language => $translation) {
            $body = (string) $translation['body'];
            $words = str_word_count(strip_tags($body));
            $this->db->table('content_translations')->insert([
                'content_id'      => $contentId,
                'language_code'   => $language,
                'title'           => (string) $translation['title'],
                'slug'            => $language === 'en' ? $slug : (string) ($translation['slug'] ?? $slug . '-' . $language),
                'excerpt'         => (string) $translation['excerpt'],
                'body'            => $body,
                'seo_title'       => (string) ($translation['seo']['title'] ?? $translation['title']),
                'seo_description' => (string) ($translation['seo']['description'] ?? ''),
                'seo_keywords'    => (string) ($translation['seo']['keywords'] ?? ''),
                'canonical_url'   => null,
                'og_image_id'     => null,
                'custom_fields'   => json_encode($translation['fields'] ?? []),
                'reading_time'    => max(1, (int) ceil($words / 200)),
                'created_at'      => $this->now,
                'updated_at'      => $this->now,
            ]);
        }

        foreach ((array) ($data['categories'] ?? []) as $category) {
            $categoryId = $this->categoryIds[$category] ?? 0;
            if ($categoryId > 0) {
                $this->db->table('content_categories')->insert(['content_id' => $contentId, 'category_id' => $categoryId]);
            }
        }

        foreach ((array) ($data['tags'] ?? []) as $tag) {
            $tagId = $this->tagIds[$tag] ?? 0;
            if ($tagId > 0) {
                $this->db->table('content_tags')->insert(['content_id' => $contentId, 'tag_id' => $tagId]);
            }
        }

        if (isset($first['fields']) && is_array($first['fields'])) {
            // Custom field defaults are stored per translation in content_translations.
        }
    }

    private function seedMenus(): void
    {
        $primaryId = $this->ensureMenu('Main navigation', 'primary', 'primary');
        $footerId  = $this->ensureMenu('Footer navigation', 'footer', 'footer');

        if ($primaryId > 0) {
            $this->ensureMenuItem($primaryId, 'custom', null, '/', 'fa-solid fa-house', 1, ['en' => 'Home', 'id' => 'Beranda', 'ar' => 'الرئيسية']);
            $this->ensureMenuItem($primaryId, 'section', $this->sectionIds['blog'] ?? null, null, 'fa-solid fa-newspaper', 2, ['en' => 'Blog', 'id' => 'Blog', 'ar' => 'المدونة']);
            $this->ensureMenuItem($primaryId, 'section', $this->sectionIds['docs'] ?? null, null, 'fa-solid fa-book-open', 3, ['en' => 'Docs', 'id' => 'Dokumentasi', 'ar' => 'التوثيق']);
            $this->ensureMenuItem($primaryId, 'page', $this->contentIds['about'] ?? null, null, 'fa-solid fa-circle-info', 4, ['en' => 'About', 'id' => 'Tentang', 'ar' => 'حول']);
        }

        if ($footerId > 0) {
            $this->ensureMenuItem($footerId, 'page', $this->contentIds['about'] ?? null, null, null, 1, ['en' => 'About', 'id' => 'Tentang']);
            $this->ensureMenuItem($footerId, 'page', $this->contentIds['privacy'] ?? null, null, null, 2, ['en' => 'Privacy', 'id' => 'Privasi']);
            $this->ensureMenuItem($footerId, 'page', $this->contentIds['contact'] ?? null, null, null, 3, ['en' => 'Contact', 'id' => 'Kontak']);
            $this->ensureMenuItem($footerId, 'custom', null, '/feed', 'fa-solid fa-rss', 4, ['en' => 'RSS feed', 'id' => 'Umpan RSS']);
        }
    }

    private function ensureMenu(string $name, string $slug, string $location): int
    {
        $row = $this->db->table('menus')->where('slug', $slug)->get()->getRowArray();
        if ($row) {
            return (int) $row['id'];
        }

        $this->db->table('menus')->insert([
            'name'       => $name,
            'slug'       => $slug,
            'location'   => $location,
            'is_active'  => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, string> $labels
     */
    private function ensureMenuItem(int $menuId, string $type, ?int $targetId, ?string $url, ?string $icon, int $sort, array $labels): void
    {
        $exists = $this->db->table('menu_items')
            ->where('menu_id', $menuId)
            ->where('sort_order', $sort)
            ->countAllResults();
        if ($exists > 0) {
            return;
        }

        $this->db->table('menu_items')->insert([
            'menu_id'   => $menuId,
            'parent_id' => null,
            'type'      => $type,
            'target_id' => $targetId,
            'url'       => $url,
            'icon'      => $icon,
            'target'    => '_self',
            'sort_order'=> $sort,
            'is_active' => 1,
            'created_at'=> $this->now,
            'updated_at'=> $this->now,
        ]);
        $itemId = (int) $this->db->insertID();

        foreach ($labels as $language => $label) {
            $this->db->table('menu_item_translations')->insert([
                'item_id'       => $itemId,
                'language_code' => $language,
                'label'         => $label,
                'created_at'    => $this->now,
                'updated_at'    => $this->now,
            ]);
        }
    }

    private function seedForms(array $options): void
    {
        $exists = $this->db->table('forms')->where('slug', 'contact')->countAllResults();
        if ($exists > 0) {
            return;
        }

        $fields = [
            ['key' => 'name', 'label' => 'Your name', 'type' => 'text', 'required' => true, 'placeholder' => 'Jane Cooper'],
            ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'placeholder' => 'jane@example.com'],
            ['key' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => false, 'placeholder' => 'How can we help?'],
            ['key' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Tell us a little more…'],
        ];

        $this->db->table('forms')->insert([
            'name'            => 'Contact form',
            'slug'            => 'contact',
            'description'     => 'The main contact form used on the contact page.',
            'fields'          => json_encode($fields),
            'success_message' => 'Thank you! Your message has been sent and we will reply shortly.',
            'email_to'        => (string) $options['admin_email'],
            'email_subject'   => 'New message from the contact form',
            'captcha'         => 1,
            'is_active'       => 1,
            'created_at'      => $this->now,
            'updated_at'      => $this->now,
        ]);
    }

    private function seedOAuthProviders(): void
    {
        $providers = [
            'google'    => 'openid email profile',
            'facebook'  => 'email public_profile',
            'github'    => 'read:user user:email',
            'linkedin'  => 'openid profile email',
            'x'         => 'users.read tweet.read offline.access',
            'bitbucket' => 'account email',
        ];

        foreach ($providers as $provider => $scopes) {
            $exists = $this->db->table('oauth_providers')->where('provider', $provider)->countAllResults();
            if ($exists > 0) {
                continue;
            }
            $this->db->table('oauth_providers')->insert([
                'provider'      => $provider,
                'client_id'     => null,
                'client_secret' => null,
                'redirect_uri'  => null,
                'scopes'        => $scopes,
                'is_active'     => 0,
                'sort_order'    => array_search($provider, array_keys($providers), true) + 1,
                'created_at'    => $this->now,
                'updated_at'    => $this->now,
            ]);
        }
    }

    private function seedNotifications(): void
    {
        if ($this->adminId <= 0) {
            return;
        }

        $exists = $this->db->table('notifications')->where('user_id', $this->adminId)->countAllResults();
        if ($exists > 0) {
            return;
        }

        $this->db->table('notifications')->insert([
            'user_id'    => $this->adminId,
            'type'       => 'success',
            'title'      => 'Welcome to DESTRA CMS',
            'message'    => 'Your site is installed. Start by reviewing Settings → General, then publish your first article.',
            'url'        => '/' . trim((string) config(\Config\Destra::class)->adminPrefix, '/') . '/settings',
            'icon'       => 'fa-solid fa-sparkles',
            'is_read'    => 0,
            'created_at' => $this->now,
        ]);
    }

    private function uniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-z0-9._-]/i', '', strtolower($base)) ?: 'admin';
        $name = $base;
        $i    = 1;

        while ($this->db->table('users')->where('username', $name)->countAllResults() > 0) {
            $name = $base . $i++;
        }

        return $name;
    }
}
