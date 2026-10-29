<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * OAuth 2.0 login for Google, Facebook, GitHub, LinkedIn, X and Bitbucket.
 * Uses the authorization-code flow with state validation (and PKCE for X)
 * implemented directly against the providers' documented endpoints.
 */
class OAuthService
{
    /** @var array<string, array<string, mixed>> */
    private const PROVIDERS = [
        'google' => [
            'name'         => 'Google',
            'icon'         => 'fa-brands fa-google',
            'authorize'    => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token'        => 'https://oauth2.googleapis.com/token',
            'userinfo'     => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scope'        => 'openid email profile',
            'pkce'         => false,
        ],
        'facebook' => [
            'name'         => 'Facebook',
            'icon'         => 'fa-brands fa-facebook-f',
            'authorize'    => 'https://www.facebook.com/v19.0/dialog/oauth',
            'token'        => 'https://graph.facebook.com/v19.0/oauth/access_token',
            'userinfo'     => 'https://graph.facebook.com/me?fields=id,name,email,picture.type(large)',
            'scope'        => 'email public_profile',
            'pkce'         => false,
        ],
        'github' => [
            'name'         => 'GitHub',
            'icon'         => 'fa-brands fa-github',
            'authorize'    => 'https://github.com/login/oauth/authorize',
            'token'        => 'https://github.com/login/oauth/access_token',
            'userinfo'     => 'https://api.github.com/user',
            'emails'       => 'https://api.github.com/user/emails',
            'scope'        => 'read:user user:email',
            'pkce'         => false,
        ],
        'linkedin' => [
            'name'         => 'LinkedIn',
            'icon'         => 'fa-brands fa-linkedin-in',
            'authorize'    => 'https://www.linkedin.com/oauth/v2/authorization',
            'token'        => 'https://www.linkedin.com/oauth/v2/accessToken',
            'userinfo'     => 'https://api.linkedin.com/v2/userinfo',
            'scope'        => 'openid profile email',
            'pkce'         => false,
        ],
        'x' => [
            'name'         => 'X',
            'icon'         => 'fa-brands fa-x-twitter',
            'authorize'    => 'https://twitter.com/i/oauth2/authorize',
            'token'        => 'https://api.twitter.com/2/oauth2/token',
            'userinfo'     => 'https://api.twitter.com/2/users/me?user.fields=profile_image_url,name,username',
            'scope'        => 'users.read tweet.read offline.access',
            'pkce'         => true,
        ],
        'bitbucket' => [
            'name'         => 'Bitbucket',
            'icon'         => 'fa-brands fa-bitbucket',
            'authorize'    => 'https://bitbucket.org/site/oauth2/authorize',
            'token'        => 'https://bitbucket.org/site/oauth2/access_token',
            'userinfo'     => 'https://api.bitbucket.org/2.0/user',
            'emails'       => 'https://api.bitbucket.org/2.0/user/emails',
            'scope'        => 'account email',
            'pkce'         => false,
        ],
    ];

    public function providerNames(): array
    {
        return array_map(static fn (array $p): string => (string) $p['name'], self::PROVIDERS);
    }

    public function meta(string $provider): ?array
    {
        return self::PROVIDERS[$provider] ?? null;
    }

    /**
     * @return list<array{slug: string, name: string, icon: string, url: string}>
     */
    public function enabledProviders(): array
    {
        $list = [];
        if (! service('settings')->bool('social.login_enabled', false)) {
            return $list;
        }

        foreach ($this->records() as $provider => $record) {
            if (empty($record['is_active'])) {
                continue;
            }
            $meta = self::PROVIDERS[$provider] ?? null;
            if ($meta === null || empty($record['client_id']) || empty($record['client_secret'])) {
                continue;
            }
            $list[] = [
                'slug' => $provider,
                'name' => (string) $meta['name'],
                'icon' => (string) $meta['icon'],
                'url'  => $this->authorizeUrl($provider),
            ];
        }

        return $list;
    }

    public function isEnabled(string $provider): bool
    {
        $records = $this->records();
        $record  = $records[$provider] ?? null;

        return is_array($record)
            && ! empty($record['is_active'])
            && ! empty($record['client_id'])
            && ! empty($record['client_secret'])
            && isset(self::PROVIDERS[$provider]);
    }

    public function authorizeUrl(string $provider): string
    {
        if (! $this->isEnabled($provider)) {
            return '';
        }

        $meta   = self::PROVIDERS[$provider];
        $record = $this->records()[$provider];
        $state  = service('crypto')->randomToken(24);
        $pkce   = (bool) $meta['pkce'];

        $sessionData = ['state' => $state, 'time' => time()];
        if ($pkce) {
            $verifier = service('crypto')->randomToken(48);
            $sessionData['verifier'] = $verifier;
        }
        session()->set('oauth_' . $provider, $sessionData);

        $params = [
            'response_type' => 'code',
            'client_id'     => (string) $record['client_id'],
            'redirect_uri'  => $this->redirectUri($provider, $record),
            'scope'         => (string) ($record['scopes'] ?: $meta['scope']),
            'state'         => $state,
        ];

        if ($provider === 'google') {
            $params['access_type'] = 'online';
            $params['prompt']      = 'select_account';
        }

        if ($pkce) {
            $params['code_challenge']        = rtrim(strtr(base64_encode(hash('sha256', $sessionData['verifier'], true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }

        return $meta['authorize'] . '?' . http_build_query($params);
    }

    /**
     * @return array{ok: bool, message: string, user_id?: int}
     */
    public function callback(string $provider, string $code, string $state): array
    {
        if (! isset(self::PROVIDERS[$provider]) || ! $this->isEnabled($provider)) {
            return ['ok' => false, 'message' => 'This sign-in provider is not available.'];
        }

        $stored = session('oauth_' . $provider);
        session()->remove('oauth_' . $provider);

        if (! is_array($stored) || empty($stored['state']) || ! hash_equals((string) $stored['state'], $state)) {
            return ['ok' => false, 'message' => 'The sign-in session expired. Please try again.'];
        }

        if (time() - (int) ($stored['time'] ?? 0) > 900) {
            return ['ok' => false, 'message' => 'The sign-in request timed out. Please try again.'];
        }

        if ($code === '' || strlen($code) > 2000) {
            return ['ok' => false, 'message' => 'The provider did not return a valid authorization code.'];
        }

        try {
            $record = $this->records()[$provider];
            $token  = $this->exchangeToken($provider, $record, $code, (string) ($stored['verifier'] ?? ''));
            if ($token === '') {
                return ['ok' => false, 'message' => 'Could not verify your account with ' . self::PROVIDERS[$provider]['name'] . '.'];
            }

            $profile = $this->fetchProfile($provider, $token);
            if ($profile === null) {
                return ['ok' => false, 'message' => 'Could not read your profile from ' . self::PROVIDERS[$provider]['name'] . '.'];
            }

            return $this->loginOrRegister($provider, $profile);
        } catch (Throwable $e) {
            log_message('error', 'OAuth callback failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Sign in failed. Please try again later.'];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function records(): array
    {
        $records = [];
        if (! is_installed()) {
            return $records;
        }

        try {
            $rows = db_connect()->table('oauth_providers')->get()->getResultArray();
            foreach ($rows as $row) {
                $provider = (string) $row['provider'];
                $row['is_active']    = (int) $row['is_active'];
                $row['client_id']    = (string) ($row['client_id'] ?? '');
                $secret              = (string) ($row['client_secret'] ?? '');
                if ($secret !== '' && service('crypto')->isEncrypted($secret)) {
                    $secret = (string) service('crypto')->decrypt($secret, 'oauth:' . $provider);
                }
                $row['client_secret'] = $secret;
                $records[$provider]   = $row;
            }
        } catch (Throwable) {
            return [];
        }

        return $records;
    }

    private function redirectUri(string $provider, array $record): string
    {
        $configured = trim((string) ($record['redirect_uri'] ?? ''));

        return $configured !== '' ? $configured : site_url('auth/' . $provider . '/callback');
    }

    private function exchangeToken(string $provider, array $record, string $code, string $verifier): string
    {
        $meta = self::PROVIDERS[$provider];
        $form = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'client_id'    => (string) $record['client_id'],
            'client_secret' => (string) $record['client_secret'],
            'redirect_uri' => $this->redirectUri($provider, $record),
        ];

        if ($provider === 'x' && $verifier !== '') {
            $form['code_verifier'] = $verifier;
        }

        $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false], null, null, false);
        $result = $client->post($meta['token'], [
            'form_params' => $form,
            'headers'     => ['Accept' => 'application/json'],
            'auth'        => $provider === 'bitbucket' ? [(string) $record['client_id'], (string) $record['client_secret']] : null,
        ]);

        $body = (string) $result->getBody();
        $data = json_decode($body, true);
        if (! is_array($data)) {
            parse_str($body, $data);
        }

        return is_array($data) && ! empty($data['access_token']) ? (string) $data['access_token'] : '';
    }

    private function fetchProfile(string $provider, string $token): ?array
    {
        $meta   = self::PROVIDERS[$provider];
        $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false], null, null, false);

        $headers = [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $token,
            'User-Agent'    => (string) setting('general.site_name', 'DESTRA CMS') . ' OAuth',
        ];

        $response = $client->get($meta['userinfo'], ['headers' => $headers]);
        $data     = json_decode((string) $response->getBody(), true);
        if (! is_array($data)) {
            return null;
        }

        $profile = [
            'id'       => '',
            'email'    => '',
            'name'     => '',
            'username' => '',
            'avatar'   => '',
        ];

        switch ($provider) {
            case 'google':
            case 'linkedin':
                $profile['id']       = (string) ($data['sub'] ?? '');
                $profile['email']    = (string) ($data['email'] ?? '');
                $profile['name']     = (string) ($data['name'] ?? '');
                $profile['avatar']   = (string) ($data['picture'] ?? '');
                $profile['username'] = (string) ($data['preferred_username'] ?? '');
                break;

            case 'facebook':
                $profile['id']     = (string) ($data['id'] ?? '');
                $profile['email']  = (string) ($data['email'] ?? '');
                $profile['name']   = (string) ($data['name'] ?? '');
                $profile['avatar'] = (string) ($data['picture']['data']['url'] ?? '');
                break;

            case 'github':
                $profile['id']       = (string) ($data['id'] ?? '');
                $profile['email']    = (string) ($data['email'] ?? '');
                $profile['name']     = (string) ($data['name'] ?? $data['login'] ?? '');
                $profile['username'] = (string) ($data['login'] ?? '');
                $profile['avatar']   = (string) ($data['avatar_url'] ?? '');

                if ($profile['email'] === '' && ! empty($meta['emails'])) {
                    $mailResponse = $client->get($meta['emails'], ['headers' => $headers]);
                    $emails       = json_decode((string) $mailResponse->getBody(), true);
                    if (is_array($emails)) {
                        foreach ($emails as $mail) {
                            if (! empty($mail['primary']) && ! empty($mail['verified'])) {
                                $profile['email'] = (string) $mail['email'];
                                break;
                            }
                        }
                    }
                }
                break;

            case 'x':
                $user = (array) ($data['data'] ?? []);
                $profile['id']       = (string) ($user['id'] ?? '');
                $profile['name']     = (string) ($user['name'] ?? '');
                $profile['username'] = (string) ($user['username'] ?? '');
                $profile['avatar']   = (string) ($user['profile_image_url'] ?? '');
                break;

            case 'bitbucket':
                $profile['id']     = (string) ($data['uuid'] ?? '');
                $profile['name']   = (string) ($data['display_name'] ?? '');
                $profile['avatar'] = (string) ($data['links']['avatar']['href'] ?? '');
                $profile['username'] = (string) ($data['username'] ?? '');

                if (! empty($meta['emails'])) {
                    $mailResponse = $client->get($meta['emails'], ['headers' => $headers]);
                    $emails       = json_decode((string) $mailResponse->getBody(), true);
                    foreach ((array) ($emails['values'] ?? []) as $mail) {
                        if (! empty($mail['is_primary']) && ! empty($mail['is_confirmed'])) {
                            $profile['email'] = (string) $mail['email'];
                            break;
                        }
                    }
                }
                break;
        }

        if ($profile['id'] === '') {
            return null;
        }

        return $profile;
    }

    /**
     * @return array{ok: bool, message: string, user_id?: int}
     */
    private function loginOrRegister(string $provider, array $profile): array
    {
        $db    = db_connect();
        $email = service('sanitizer')->email($profile['email']);
        $link  = null;
        $user  = null;

        $link = $db->table('social_logins')->where('provider', $provider)->where('provider_user_id', $profile['id'])->get()->getRowArray();
        if ($link) {
            $user = $db->table('users')->where('id', (int) $link['user_id'])->get()->getRowArray();
        } elseif ($email !== '') {
            $user = $db->table('users')->where('email', $email)->get()->getRowArray();
        } else {
            $user = null;
        }

        if (! $user) {
            if (! service('settings')->bool('social.social_registration', false)) {
                return ['ok' => false, 'message' => 'No account is linked to this profile. Please register first.'];
            }

            if ($email === '') {
                $host  = preg_replace('/[^a-z0-9.-]/i', '', (string) (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'));
                $email = $provider . '-' . preg_replace('/[^a-z0-9]/i', '', $profile['id']) . '@social.' . $host;
            }

            $username = $this->uniqueUsername($profile['username'] !== '' ? $profile['username'] : ($profile['name'] !== '' ? $profile['name'] : $provider . $profile['id']));

            $now  = date('Y-m-d H:i:s');
            $role = max(1, (int) setting('general.registration_role', 4));
            $db->table('users')->insert([
                'name'              => mb_substr($profile['name'] !== '' ? $profile['name'] : $username, 0, 120),
                'username'          => $username,
                'email'             => $email,
                'password_hash'     => Auth::hashPassword(service('crypto')->randomToken(32)),
                'role_id'           => $role,
                'avatar'            => service('sanitizer')->url($profile['avatar']),
                'locale'            => current_locale(),
                'status'            => 'active',
                'email_verified_at' => $profile['email'] !== '' ? $now : null,
                'meta'              => json_encode(['social' => $provider, 'social_only' => true]),
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $userId = (int) $db->insertID();
            $user   = $db->table('users')->where('id', $userId)->get()->getRowArray();
        }

        if (! $user) {
            return ['ok' => false, 'message' => 'Could not create your account.'];
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return ['ok' => false, 'message' => 'This account has been suspended.'];
        }

        if (! $link) {
            $db->table('social_logins')->insert([
                'user_id'          => (int) $user['id'],
                'provider'         => $provider,
                'provider_user_id' => $profile['id'],
                'email'            => $email !== '' ? $email : null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        service('auth')->loginById((int) $user['id'], false, service('request'));

        try {
            service('audit')->log('auth.social', 'Login via ' . self::PROVIDERS[$provider]['name'], 'auth', 'user', (int) $user['id']);
        } catch (Throwable) {
            // Audit is best effort.
        }

        return ['ok' => true, 'message' => 'Signed in with ' . self::PROVIDERS[$provider]['name'] . '.', 'user_id' => (int) $user['id']];
    }

    private function uniqueUsername(string $candidate): string
    {
        $base = service('sanitizer')->slug($candidate);
        $base = $base !== '' ? mb_substr($base, 0, 40) : 'member';
        $name = $base;
        $i    = 1;
        $db   = db_connect();

        while ($db->table('users')->where('username', $name)->countAllResults() > 0) {
            $name = $base . '-' . $i++;
            if ($i > 9999) {
                $name = $base . '-' . service('crypto')->randomToken(4);
                break;
            }
        }

        return $name;
    }
}
