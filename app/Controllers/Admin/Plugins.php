<?php

namespace App\Controllers\Admin;

use Config\Destra;
use Throwable;

class Plugins extends BaseAdminController
{
    public function index(): string
    {
        $manager   = service('pluginManager');
        $available = $manager->available();
        $records   = $manager->records();

        $cards = [];
        foreach ($records as $slug => $record) {
            $meta = $available[$slug] ?? [];
            $cards[] = $this->card((string) $slug, $meta, $record);
        }
        foreach ($available as $slug => $meta) {
            if (! isset($records[$slug])) {
                $cards[] = $this->card((string) $slug, $meta, null);
            }
        }

        $activeCount = count(array_filter($cards, static fn (array $c): bool => $c['active']));

        return $this->render('admin/plugins/index', [
            'pageTitle'    => 'Plugins',
            'pageSubtitle' => 'Extend DESTRA with self-contained modules that ship separately.',
            'navActive'    => 'plugins',
            'breadcrumbs'  => [['label' => 'Plugins']],
            'cards'        => $cards,
            'activeCount'  => $activeCount,
            'pluginsPath'  => config(Destra::class)->pluginsPath,
        ]);
    }

    public function install()
    {
        $file = $this->request->getFile('plugin');
        if ($file === null || ! $file->isValid()) {
            return $this->respond(false, 'Choose a plugin ZIP archive to upload.');
        }

        if (strtolower($file->getClientExtension() ?: $file->getExtension()) !== 'zip') {
            return $this->respond(false, 'Only .zip archives are accepted.');
        }

        if ($file->getSize() > ((int) config(Destra::class)->maxUploadMB) * 1024 * 1024) {
            return $this->respond(false, 'That archive is larger than the configured upload limit.');
        }

        $tmp = WRITEPATH . 'uploads/plugin-' . bin2hex(random_bytes(6)) . '.zip';
        @mkdir(dirname($tmp), 0755, true);
        $file->move(dirname($tmp), basename($tmp));

        try {
            $result = service('pluginManager')->installFromZip($tmp);
        } catch (Throwable $e) {
            @unlink($tmp);

            return $this->respond(false, 'Install failed: ' . $e->getMessage());
        }

        @unlink($tmp);

        if (! ($result['ok'] ?? false)) {
            return $this->respond(false, (string) ($result['message'] ?? 'Install failed.'));
        }

        $this->flushCache();
        $this->audit('plugin.install', 'Installed plugin ' . (string) ($result['slug'] ?? ''), 'plugins');

        return $this->respond(true, (string) ($result['message'] ?? 'Plugin installed.'));
    }

    public function toggle(string $slug)
    {
        $slug    = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $manager = service('pluginManager');
        $plugins = $manager->available();

        if ($slug === '' || ! isset($plugins[$slug])) {
            return $this->fail('Plugin not found.', 404);
        }

        $records = $manager->records();
        $active  = ! empty($records[$slug]['is_active']);
        $meta    = $plugins[$slug];

        if (! isset($records[$slug])) {
            db_connect()->table('plugins')->insert([
                'slug'         => $slug,
                'name'         => mb_substr((string) ($meta['name'] ?? $slug), 0, 120),
                'version'      => mb_substr((string) ($meta['version'] ?? '1.0.0'), 0, 30),
                'description'  => mb_substr((string) ($meta['description'] ?? ''), 0, 500),
                'author'       => mb_substr((string) ($meta['author'] ?? ''), 0, 120),
                'is_active'    => 0,
                'settings'     => '{}',
                'installed_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        $ok = $manager->setActive($slug, ! $active);
        if (! $ok) {
            return $this->fail('Could not update the plugin.');
        }

        $this->flushCache();
        $this->audit('plugin.toggle', (!$active ? 'Activated' : 'Deactivated') . ' plugin ' . $slug, 'plugins', 'plugin', null, ['slug' => $slug]);

        return $this->ok(['active' => ! $active], ($active ? 'Plugin deactivated.' : 'Plugin activated.'));
    }

    public function settings(string $slug): string
    {
        $slug    = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $plugins = service('pluginManager')->available();
        if ($slug === '' || ! isset($plugins[$slug])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $meta   = $plugins[$slug];
        $record = service('pluginManager')->records()[$slug] ?? null;
        $values = (array) ($record['settings'] ?? []);
        $schema = $this->schema($meta);

        return $this->render('admin/plugins/form', [
            'pageTitle'    => (string) ($meta['name'] ?? $slug) . ' settings',
            'pageSubtitle' => (string) ($meta['description'] ?? ''),
            'navActive'    => 'plugins',
            'breadcrumbs'  => [['label' => 'Plugins', 'url' => admin_url('plugins')], ['label' => (string) ($meta['name'] ?? $slug)]],
            'slug'         => $slug,
            'meta'         => $meta,
            'schema'       => $schema,
            'values'       => $values,
            'active'       => ! empty($record['is_active']),
        ]);
    }

    public function saveSettings(string $slug)
    {
        $slug    = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $plugins = service('pluginManager')->available();
        if ($slug === '' || ! isset($plugins[$slug])) {
            return $this->fail('Plugin not found.', 404);
        }

        $schema = $this->schema($plugins[$slug]);
        $raw    = (array) ($this->request->getPost('settings') ?? []);
        $san    = service('sanitizer');
        $clean  = [];

        foreach ($schema as $key => $field) {
            $value = $raw[$key] ?? null;
            $type  = (string) ($field['type'] ?? 'text');

            $clean[$key] = match ($type) {
                'bool'     => ! empty($value) && (string) $value !== '0' ? '1' : '0',
                'int'      => (string) $san->int($value, 0, 1000000),
                'textarea' => $san->textarea($value),
                'secret'   => is_string($value) ? trim($value) : '',
                'email'    => $san->email($value),
                'url'      => $san->url($value),
                default    => $san->text($value, 5000),
            };
        }

        service('pluginManager')->saveSettings($slug, $clean, $schema);
        $this->flushCache();
        $this->audit('plugin.settings', 'Updated settings for plugin ' . $slug, 'plugins', 'plugin', null, ['slug' => $slug]);

        return redirect()->to(admin_url('plugins/' . $slug . '/settings'))->with('success', 'Plugin settings saved.');
    }

    public function delete(string $slug)
    {
        $slug   = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $result = service('pluginManager')->remove($slug);

        if (! ($result['ok'] ?? false)) {
            return $this->fail((string) ($result['message'] ?? 'Could not remove the plugin.'));
        }

        $this->flushCache();
        $this->audit('plugin.delete', 'Removed plugin ' . $slug, 'plugins', 'plugin', null, ['slug' => $slug]);

        return $this->ok([], (string) ($result['message'] ?? 'Plugin removed.'));
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed>|null $record
     */
    private function card(string $slug, array $meta, ?array $record): array
    {
        return [
            'slug'        => $slug,
            'name'        => (string) ($record['name'] ?? $meta['name'] ?? $slug),
            'version'     => (string) ($record['version'] ?? $meta['version'] ?? '1.0.0'),
            'author'      => (string) ($record['author'] ?? $meta['author'] ?? ''),
            'description' => (string) ($record['description'] ?? $meta['description'] ?? ''),
            'active'      => ! empty($record['is_active']),
            'installed'   => $record !== null,
            'has_settings'=> $this->schema($meta) !== [],
            'installed_at'=> $record['installed_at'] ?? null,
        ];
    }

    /**
     * Normalise a plugin manifest "settings" definition into key => field.
     *
     * @param array<string, mixed> $meta
     * @return array<string, array<string, mixed>>
     */
    private function schema(array $meta): array
    {
        $schema = $meta['settings'] ?? [];
        if (! is_array($schema)) {
            return [];
        }

        $normalised = [];
        foreach ($schema as $key => $field) {
            if (is_int($key) && is_array($field)) {
                $fieldKey = (string) ($field['key'] ?? '');
                if ($fieldKey === '') {
                    continue;
                }
                $normalised[$fieldKey] = $field;
                continue;
            }
            if (! is_string($key) || ! preg_match('/^[a-z0-9_.-]+$/i', $key)) {
                continue;
            }
            $normalised[$key] = is_array($field) ? $field : ['label' => ucfirst($key), 'type' => 'text'];
            $normalised[$key]['label'] ??= ucfirst(str_replace(['_', '.'], ' ', $key));
        }

        return $normalised;
    }

    private function respond(bool $ok, string $message)
    {
        if ($this->wantsJson()) {
            return $ok ? $this->ok([], $message) : $this->fail($message);
        }

        return redirect()->to(admin_url('plugins'))->with($ok ? 'success' : 'error', $message);
    }
}
