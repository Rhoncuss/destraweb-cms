<?php

namespace App\Controllers\Admin;

use Throwable;

class SocialLogin extends BaseAdminController
{
    public function index(): string
    {
        $oauth     = service('oauth');
        $providers = [];

        foreach (array_keys($oauth->providerNames()) as $slug) {
            $meta = $oauth->meta($slug);
            if ($meta === null) {
                continue;
            }
            $providers[$slug] = $meta;
        }

        $records     = [];
        $activeCount = 0;
        try {
            foreach (db_connect()->table('oauth_providers')->get()->getResultArray() as $row) {
                $row['has_secret'] = (string) ($row['client_secret'] ?? '') !== '';
                unset($row['client_secret']);
                if (! empty($row['is_active']) && (string) ($row['client_id'] ?? '') !== '' && $row['has_secret']) {
                    $activeCount++;
                }
                $records[(string) $row['provider']] = $row;
            }
        } catch (Throwable) {
        }

        return $this->render('admin/social_login/index', [
            'pageTitle'    => 'Social sign-in',
            'pageSubtitle' => 'Let visitors sign in with Google, GitHub, Facebook and more. Configure each provider you want to enable.',
            'navActive'    => 'social-login',
            'breadcrumbs'  => [['label' => 'Social sign-in']],
            'providers'    => $providers,
            'records'      => $records,
            'activeCount'  => $activeCount,
            'enabled'      => service('settings')->bool('social.login_enabled', false),
            'registration' => service('settings')->bool('social.social_registration', false),
            'callbackBase' => site_url('auth'),
        ]);
    }

    public function save(string $provider)
    {
        $oauth = service('oauth');
        $meta  = $oauth->meta($provider);
        if ($meta === null) {
            return $this->fail('Unknown provider.', 404);
        }

        $san = service('sanitizer');
        $db  = db_connect();

        $clientId     = $san->text($this->request->getPost('client_id'), 255);
        $clientSecret = trim((string) $this->request->getPost('client_secret'));
        $redirect     = $san->url($this->request->getPost('redirect_uri'));
        $scopes       = $san->text($this->request->getPost('scopes'), 500);
        $active       = $this->request->getPost('is_active') ? 1 : 0;

        try {
            $existing          = $db->table('oauth_providers')->where('provider', $provider)->get()->getRowArray();
            $hasStoredSecret   = $existing && (string) ($existing['client_secret'] ?? '') !== '';

            if ($active === 1 && $clientId === '') {
                return $this->fail('Add a client ID before enabling this provider.');
            }

            if ($active === 1 && $clientSecret === '' && ! $hasStoredSecret) {
                return $this->fail('Add a client secret before enabling this provider.');
            }

            $data = [
                'provider'     => $provider,
                'client_id'    => $clientId,
                'redirect_uri' => $redirect,
                'scopes'       => $scopes,
                'is_active'    => $active,
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($clientSecret !== '') {
                $data['client_secret'] = service('crypto')->encrypt($clientSecret, 'oauth:' . $provider);
            } elseif (! $existing) {
                $data['client_secret'] = '';
            }

            if ($existing) {
                $db->table('oauth_providers')->where('id', (int) $existing['id'])->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('oauth_providers')->insert($data);
            }

            $this->audit('social.update', 'Updated ' . $provider . ' sign-in settings', 'settings', 'oauth_provider', $existing ? (int) $existing['id'] : null, ['provider' => $provider]);
            $this->flushCache();

            return $this->ok([], ucfirst($provider) . ' sign-in saved.');
        } catch (Throwable $e) {
            log_message('error', 'OAuth settings save failed: ' . $e->getMessage());

            return $this->fail('The provider could not be saved.');
        }
    }
}
