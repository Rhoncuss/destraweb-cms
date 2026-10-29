<?php

namespace App\Controllers\Admin;

use Throwable;

class Languages extends BaseAdminController
{
    /**
     * Starter UI strings offered by the visual translation editor.
     *
     * @var array<string, array<string, string>>
     */
    private const STARTER_KEYS = [
        'general' => [
            'read_more'        => 'Read more',
            'read_less'        => 'Show less',
            'share'            => 'Share',
            'search'           => 'Search',
            'search_placeholder' => 'Search articles, guides and pages…',
            'all'              => 'All',
            'home'             => 'Home',
            'latest'           => 'Latest',
            'trending'         => 'Trending',
            'featured'         => 'Featured',
            'categories'       => 'Categories',
            'tags'             => 'Tags',
            'views'            => 'views',
            'min_read'         => 'min read',
            'published'        => 'Published',
            'updated'          => 'Updated',
            'related'          => 'Related reading',
            'table_of_contents' => 'On this page',
            'no_results'       => 'Nothing matched your search.',
            'loading'          => 'Loading…',
            'back_to_top'      => 'Back to top',
        ],
        'comments' => [
            'comments'          => 'Comments',
            'leave_comment'     => 'Leave a comment',
            'comment_placeholder' => 'Share your thoughts…',
            'post_comment'      => 'Post comment',
            'reply'             => 'Reply',
            'name'              => 'Name',
            'email'             => 'Email',
            'website'           => 'Website',
            'awaiting_review'   => 'Your comment is awaiting review.',
            'be_first'          => 'Be the first to comment.',
        ],
        'newsletter' => [
            'newsletter_title'    => 'Stay in the loop',
            'newsletter_subtitle' => 'One thoughtful email, once a week.',
            'email_placeholder'   => 'you@example.com',
            'subscribe'           => 'Subscribe',
            'subscribed'          => 'You are on the list. Welcome aboard!',
            'unsubscribe'         => 'Unsubscribe',
        ],
        'forms' => [
            'send_message' => 'Send message',
            'sending'      => 'Sending…',
            'thank_you'    => 'Thank you — we will be in touch shortly.',
            'required'     => 'This field is required.',
            'invalid_email' => 'Enter a valid email address.',
        ],
        'footer' => [
            'footer_about'   => 'About',
            'footer_links'   => 'Explore',
            'footer_legal'   => 'Legal',
            'privacy'        => 'Privacy policy',
            'terms'          => 'Terms of service',
            'contact'        => 'Contact',
            'copyright'      => 'All rights reserved.',
            'powered_by'     => 'Powered by DESTRA CMS',
        ],
    ];

    public function index(): string
    {
        $db        = db_connect();
        $languages = service('localization')->languages(false);

        $counts = [];
        try {
            foreach ($db->table('translations')->select('language_code, COUNT(*) AS total')->groupBy('language_code')->get()->getResultArray() as $row) {
                $counts[(string) $row['language_code']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        $contentCounts = [];
        try {
            foreach ($db->table('content_translations')->select('language_code, COUNT(*) AS total')->groupBy('language_code')->get()->getResultArray() as $row) {
                $contentCounts[(string) $row['language_code']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/languages/index', [
            'pageTitle'     => 'Languages',
            'pageSubtitle'  => 'Publish in any language, left-to-right or right-to-left.',
            'navActive'     => 'languages',
            'breadcrumbs'   => [['label' => 'Languages']],
            'items'         => $languages,
            'counts'        => $counts,
            'contentCounts' => $contentCounts,
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

    public function toggle(int $id)
    {
        $db  = db_connect();
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Language not found.', 404);
        }

        if ((int) $row['is_default'] === 1 && (int) $row['is_active'] === 1) {
            return $this->fail('The default language must stay active.');
        }

        $db->table('languages')->where('id', $id)->update([
            'is_active'  => (int) $row['is_active'] === 1 ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        service('localization')->forget();
        $this->flushCache();
        $this->audit('language.toggle', 'Toggled language ' . $row['code'], 'languages', 'language', $id);

        return $this->ok(['active' => (int) $row['is_active'] === 1 ? 0 : 1], 'Language updated.');
    }

    public function makeDefault(int $id)
    {
        $db  = db_connect();
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Language not found.', 404);
        }

        $db->transStart();
        $db->table('languages')->update(['is_default' => 0]);
        $db->table('languages')->where('id', $id)->update([
            'is_default' => 1,
            'is_active'  => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        service('localization')->forget();
        $this->flushCache();
        $this->audit('language.default', 'Set ' . $row['code'] . ' as the default language', 'languages', 'language', $id);

        return $this->ok([], 'Default language updated.');
    }

    public function delete(int $id)
    {
        $db  = db_connect();
        $row = $this->find($id);
        if ($row === null) {
            return $this->fail('Language not found.', 404);
        }

        if ((int) $row['is_default'] === 1) {
            return $this->fail('Choose another default language before deleting this one.');
        }

        $used = (int) $db->table('content_translations')->where('language_code', $row['code'])->countAllResults();
        if ($used > 0) {
            return $this->fail('This language has ' . $used . ' translated entries. Remove them first or deactivate the language instead.');
        }

        $db->table('translations')->where('language_code', $row['code'])->delete();
        $db->table('languages')->where('id', $id)->delete();

        service('localization')->forget();
        $this->flushCache();
        $this->audit('language.delete', 'Deleted language ' . $row['code'], 'languages', 'language', $id);

        return $this->ok([], 'Language deleted.');
    }

    public function translations(int $id): string
    {
        $db       = db_connect();
        $language = $this->find($id);
        if ($language === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $code    = (string) $language['code'];
        $q       = trim((string) $this->request->getGet('q'));
        $group   = (string) $this->request->getGet('group');
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 40;

        $groups = [];
        try {
            foreach ($db->table('translations')->select('group_name')->where('language_code', $code)->distinct()->orderBy('group_name', 'ASC')->get()->getResultArray() as $row) {
                $groups[(string) $row['group_name']] = (string) $row['group_name'];
            }
        } catch (Throwable) {
        }

        $builder = $db->table('translations')->where('language_code', $code);
        if ($group !== '' && isset($groups[$group])) {
            $builder->where('group_name', $group);
        }
        if ($q !== '') {
            $builder->groupStart()->like('trans_key', $q)->orLike('trans_value', $q)->groupEnd();
        }

        $total = (int) $builder->countAllResults(false);
        $rows  = $builder->orderBy('group_name', 'ASC')->orderBy('trans_key', 'ASC')
            ->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->render('admin/languages/translations', [
            'pageTitle'    => 'Translations — ' . $language['name'],
            'pageSubtitle' => 'Override every interface string without touching the theme code.',
            'navActive'    => 'languages',
            'breadcrumbs'  => [['label' => 'Languages', 'url' => admin_url('languages')], ['label' => (string) $language['native_name']]],
            'language'     => $language,
            'items'        => $rows,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'groups'       => $groups,
            'group'        => $group,
            'q'            => $q,
            'suggested'    => $this->suggestedKeys($code),
        ]);
    }

    public function saveTranslations(int $id)
    {
        $db       = db_connect();
        $language = $this->find($id);
        if ($language === null) {
            return $this->fail('Language not found.', 404);
        }

        $code   = (string) $language['code'];
        $values = (array) ($this->request->getPost('values') ?? []);
        $san    = service('sanitizer');
        $saved  = 0;

        foreach ($values as $rowId => $value) {
            $rowId = (int) $rowId;
            if ($rowId <= 0) {
                continue;
            }
            $exists = $db->table('translations')->where('id', $rowId)->where('language_code', $code)->countAllResults();
            if ($exists === 0) {
                continue;
            }
            $db->table('translations')->where('id', $rowId)->update([
                'trans_value' => $san->textarea($value, 20000),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
            $saved++;
        }

        $this->flushCache();
        $this->audit('translation.update', 'Updated ' . $saved . ' translations for ' . $code, 'languages', 'language', $id);

        return redirect()->to(admin_url('languages/' . $id . '/translations'))->with('success', $saved . ' translations saved.');
    }

    public function addKey(int $id)
    {
        $db       = db_connect();
        $language = $this->find($id);
        if ($language === null) {
            return $this->fail('Language not found.', 404);
        }

        $san   = service('sanitizer');
        $key   = strtolower(trim((string) $this->request->getPost('trans_key')));
        $key   = preg_replace('/[^a-z0-9._-]/', '', $key) ?? '';
        $group = strtolower(trim((string) ($this->request->getPost('group_name') ?: 'general')));
        $group = preg_replace('/[^a-z0-9_-]/', '', $group) ?: 'general';
        $value = $san->textarea($this->request->getPost('trans_value'), 20000);

        if ($key === '' || strlen($key) > 190) {
            return $this->fail('Enter a valid translation key (letters, numbers, dots, dashes or underscores).');
        }

        $code = (string) $language['code'];
        $db->table('translations')->where('language_code', $code)->where('group_name', $group)->where('trans_key', $key)->delete();
        $db->table('translations')->insert([
            'language_code' => $code,
            'group_name'    => $group,
            'trans_key'     => $key,
            'trans_value'   => $value,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->flushCache();
        $this->audit('translation.create', 'Added translation key ' . $group . '.' . $key . ' for ' . $code, 'languages', 'language', $id);

        return redirect()->to(admin_url('languages/' . $id . '/translations?group=' . rawurlencode($group)))->with('success', 'Translation key added.');
    }

    public function deleteKey(int $id, int $translationId)
    {
        $db       = db_connect();
        $language = $this->find($id);
        if ($language === null) {
            return $this->fail('Language not found.', 404);
        }

        $db->table('translations')->where('id', $translationId)->where('language_code', $language['code'])->delete();
        $this->flushCache();
        $this->audit('translation.delete', 'Deleted translation #' . $translationId . ' for ' . $language['code'], 'languages', 'language', $id);

        return $this->ok([], 'Translation deleted.');
    }

    public function seedKeys(int $id)
    {
        $db       = db_connect();
        $language = $this->find($id);
        if ($language === null) {
            return $this->fail('Language not found.', 404);
        }

        $code    = (string) $language['code'];
        $created = 0;

        foreach (self::STARTER_KEYS as $group => $keys) {
            foreach ($keys as $key => $value) {
                $exists = $db->table('translations')->where('language_code', $code)->where('group_name', $group)->where('trans_key', $key)->countAllResults();
                if ($exists > 0) {
                    continue;
                }
                $db->table('translations')->insert([
                    'language_code' => $code,
                    'group_name'    => $group,
                    'trans_key'     => $key,
                    'trans_value'   => $code === default_locale() ? $value : '',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
                $created++;
            }
        }

        $this->flushCache();
        $this->audit('translation.seed', 'Seeded ' . $created . ' starter keys for ' . $code, 'languages', 'language', $id);

        return redirect()->to(admin_url('languages/' . $id . '/translations'))->with('success', $created . ' starter keys added.');
    }

    private function form(?array $item): string
    {
        return $this->render('admin/languages/form', [
            'pageTitle'    => $item ? 'Edit language' : 'Add language',
            'pageSubtitle' => $item ? 'Update the details of ' . $item['name'] . '.' : 'Add a locale and start translating.',
            'navActive'    => 'languages',
            'breadcrumbs'  => [['label' => 'Languages', 'url' => admin_url('languages')], ['label' => $item ? (string) $item['name'] : 'New language']],
            'item'         => $item,
        ]);
    }

    private function save(?int $id)
    {
        $db  = db_connect();
        $san = service('sanitizer');

        $code = strtolower($san->text($this->request->getPost('code'), 10));
        $code = str_replace('_', '-', $code);

        if (! preg_match('/^[a-z]{2,3}(-[a-z]{2,4})?$/', $code)) {
            return redirect()->back()->withInput()->with('error', 'Use a valid language code such as en, id, ar or pt-BR.');
        }

        $data = [
            'code'        => $code,
            'name'        => $san->text($this->request->getPost('name'), 80),
            'native_name' => $san->text($this->request->getPost('native_name'), 80),
            'direction'   => $this->request->getPost('direction') === 'rtl' ? 'rtl' : 'ltr',
            'flag'        => preg_replace('/[^a-zA-Z-]/', '', (string) $this->request->getPost('flag')) ?: null,
            'is_active'   => $this->request->getPost('is_active') ? 1 : 0,
            'sort_order'  => $san->int($this->request->getPost('sort_order'), 0, 9999),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($data['name'] === '' || $data['native_name'] === '') {
            return redirect()->back()->withInput()->with('error', 'A language needs both an English name and a native name.');
        }

        $duplicate = $db->table('languages')->where('code', $code)->where('id !=', (int) $id)->countAllResults();
        if ($duplicate > 0) {
            return redirect()->back()->withInput()->with('error', 'That language code already exists.');
        }

        $wantsDefault = (bool) $this->request->getPost('is_default');

        if ($id === null) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['is_default'] = 0;
            $db->table('languages')->insert($data);
            $id = (int) $db->insertID();

            if ($wantsDefault) {
                $this->switchDefault($db, $id);
            }

            $this->audit('language.create', 'Created language ' . $code, 'languages', 'language', $id);
            $message = 'Language added.';
        } else {
            $existing = $this->find($id);
            if ($existing === null) {
                return redirect()->to(admin_url('languages'))->with('error', 'Language not found.');
            }

            if ((int) $existing['is_default'] === 1) {
                $data['is_active'] = 1;
            }

            $db->table('languages')->where('id', $id)->update($data);

            if ($wantsDefault && (int) $existing['is_default'] !== 1) {
                $this->switchDefault($db, $id);
            }

            $this->audit('language.update', 'Updated language ' . $code, 'languages', 'language', $id);
            $message = 'Language updated.';
        }

        service('localization')->forget();
        $this->flushCache();

        return redirect()->to(admin_url('languages'))->with('success', $message);
    }

    private function switchDefault($db, int $id): void
    {
        $db->transStart();
        $db->table('languages')->update(['is_default' => 0]);
        $db->table('languages')->where('id', $id)->update(['is_default' => 1, 'is_active' => 1]);
        $db->transComplete();
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('languages')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        if (! $row) {
            return null;
        }

        $row['is_default'] = (int) $row['is_default'];
        $row['is_active']  = (int) $row['is_active'];

        return $row;
    }

    /**
     * @return list<array{group: string, key: string, value: string}>
     */
    private function suggestedKeys(string $code): array
    {
        $existing = [];
        try {
            foreach (db_connect()->table('translations')->select('group_name, trans_key')->where('language_code', $code)->get()->getResultArray() as $row) {
                $existing[$row['group_name'] . '.' . $row['trans_key']] = true;
            }
        } catch (Throwable) {
        }

        $suggested = [];
        foreach (self::STARTER_KEYS as $group => $keys) {
            foreach ($keys as $key => $value) {
                if (isset($existing[$group . '.' . $key])) {
                    continue;
                }
                $suggested[] = ['group' => $group, 'key' => $key, 'value' => $value];
            }
        }

        return $suggested;
    }
}
