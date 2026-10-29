<?php

namespace App\Libraries;

use Config\Services;
use Throwable;

/**
 * Newsletter subscriptions with double opt-in, one-click unsubscribe and
 * optional Mailchimp / Mailcoach synchronisation. Local storage is always the
 * source of truth so the list survives provider outages.
 */
class NewsletterService
{
    /**
     * @return array{ok: bool, message: string, status?: string}
     */
    public function subscribe(string $email, string $name = '', ?string $locale = null, string $source = 'footer', bool $accepted = true): array
    {
        if (! service('settings')->bool('newsletter.enabled', true)) {
            return ['ok' => false, 'message' => 'Newsletter is currently closed.'];
        }

        if (! $accepted) {
            return ['ok' => false, 'message' => 'Please accept the privacy notice.'];
        }

        $email = service('sanitizer')->email($email);
        if ($email === '') {
            return ['ok' => false, 'message' => 'Please enter a valid email address.'];
        }

        $name  = mb_substr(trim(strip_tags($name)), 0, 120);
        $token = service('crypto')->randomToken(32);
        $hash  = service('crypto')->hashToken($token);

        try {
            $db  = db_connect();
            $row = $db->table('newsletter_subscribers')->where('email', $email)->get()->getRowArray();

            if ($row && $row['status'] === 'active') {
                return ['ok' => true, 'message' => 'You are already subscribed. Thank you!', 'status' => 'active'];
            }

            $doubleOptin = service('settings')->bool('newsletter.double_optin', true);
            $status      = $doubleOptin ? 'pending' : 'active';
            $data        = [
                'email'        => $email,
                'name'         => $name,
                'status'       => $status,
                'token_hash'   => $hash,
                'locale'       => $locale ?? current_locale(),
                'ip_hash'      => service('crypto')->hashIp(service('request')->getIPAddress()),
                'confirmed_at' => $doubleOptin ? null : date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($row) {
                $data['unsubscribed_at'] = null;
                $db->table('newsletter_subscribers')->where('id', (int) $row['id'])->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('newsletter_subscribers')->insert($data);
            }

            if ($doubleOptin) {
                $this->sendConfirmation($email, $name, $token, $locale);
            } else {
                $this->syncProvider($email, $name, 'subscribed');
            }

            $message = $doubleOptin
                ? 'Almost there! Please confirm your subscription via the email we just sent.'
                : 'You are subscribed. Welcome aboard!';

            return ['ok' => true, 'message' => $message, 'status' => $status];
        } catch (Throwable $e) {
            log_message('error', 'Newsletter subscribe failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'We could not complete your subscription. Please try again.'];
        }
    }

    public function confirm(string $token): bool
    {
        $token = trim($token);
        if ($token === '' || strlen($token) > 128) {
            return false;
        }

        try {
            $hash = service('crypto')->hashToken($token);
            $db   = db_connect();
            $row  = $db->table('newsletter_subscribers')->where('token_hash', $hash)->get()->getRowArray();
            if (! $row || $row['status'] === 'active') {
                return false;
            }

            // token_hash is kept so the same link can also unsubscribe later.
            $db->table('newsletter_subscribers')->where('id', (int) $row['id'])->update([
                'status'        => 'active',
                'confirmed_at'  => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->syncProvider((string) $row['email'], (string) ($row['name'] ?? ''), 'subscribed');

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Newsletter confirm failed: ' . $e->getMessage());

            return false;
        }
    }

    public function unsubscribe(string $tokenOrEmail): bool
    {
        $tokenOrEmail = trim($tokenOrEmail);
        if ($tokenOrEmail === '') {
            return false;
        }

        try {
            $db    = db_connect();
            $email = service('sanitizer')->email($tokenOrEmail);
            $row   = $email !== ''
                ? $db->table('newsletter_subscribers')->where('email', $email)->get()->getRowArray()
                : $db->table('newsletter_subscribers')->where('token_hash', service('crypto')->hashToken($tokenOrEmail))->get()->getRowArray();

            if (! $row) {
                return false;
            }

            $db->table('newsletter_subscribers')->where('id', (int) $row['id'])->update([
                'status'          => 'unsubscribed',
                'unsubscribed_at' => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            $this->syncProvider((string) $row['email'], (string) ($row['name'] ?? ''), 'unsubscribed');

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Newsletter unsubscribe failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return array{total: int, active: int, pending: int, unsubscribed: int}
     */
    public function stats(): array
    {
        $stats = ['total' => 0, 'active' => 0, 'pending' => 0, 'unsubscribed' => 0];

        try {
            $db = db_connect();
            $stats['total'] = (int) $db->table('newsletter_subscribers')->countAllResults();
            foreach (['active', 'pending', 'unsubscribed'] as $status) {
                $stats[$status] = (int) $db->table('newsletter_subscribers')->where('status', $status)->countAllResults();
            }
        } catch (Throwable) {
            // Keep zeroes.
        }

        return $stats;
    }

    /**
     * Push a subscriber to the configured external provider.
     */
    public function syncProvider(string $email, string $name = '', string $status = 'subscribed'): bool
    {
        $provider = (string) service('settings')->get('newsletter.provider', 'internal');
        if ($provider === 'internal') {
            return true;
        }

        if ($provider === 'mailchimp') {
            return $this->syncMailchimp($email, $name, $status);
        }

        if ($provider === 'mailcoach') {
            return $this->syncMailcoach($email, $name, $status);
        }

        return false;
    }

    private function sendConfirmation(string $email, string $name, string $token, ?string $locale): void
    {
        $url = base_url('newsletter/confirm?token=' . rawurlencode($token));
        $site = (string) setting('general.site_name', 'DESTRA CMS');

        $message = '<p style="margin:0 0 16px;">Hi ' . esc($name !== '' ? $name : 'there') . ',</p>'
            . '<p style="margin:0 0 20px;">Thanks for subscribing to <strong>' . esc($site) . '</strong>. Please confirm your email address to complete the subscription.</p>'
            . '<p style="margin:0 0 24px;"><a href="' . esc($url, 'attr') . '" style="display:inline-block;background:#4f46e5;color:#ffffff;padding:13px 24px;border-radius:12px;text-decoration:none;font-weight:600;">Confirm subscription</a></p>'
            . '<p style="margin:0;color:#64748b;font-size:13px;">If you did not request this, you can safely ignore this email. The link expires after 7 days.</p>';

        service('mailer')->sendTemplate($email, 'Please confirm your subscription', 'newsletter-confirm', [
            'message'   => $message,
            'preheader' => 'One click to confirm your subscription.',
        ]);
    }

    private function syncMailchimp(string $email, string $name, string $status): bool
    {
        $key  = (string) service('settings')->get('newsletter.mailchimp_key', '');
        $list = (string) service('settings')->get('newsletter.mailchimp_list', '');
        if ($key === '' || $list === '' || ! str_contains($key, '-')) {
            return false;
        }

        $dc = substr($key, strrpos($key, '-') + 1);
        if (! preg_match('/^[a-z0-9]+$/i', $dc)) {
            return false;
        }

        $endpoint = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . rawurlencode($list) . '/members/' . md5(strtolower($email));

        try {
            $client = Services::curlrequest(['timeout' => 12, 'http_errors' => false], null, null, false);
            $client->request('PUT', $endpoint, [
                'auth'        => ['user', $key],
                'json'        => [
                    'email_address' => $email,
                    'status_if_new' => $status === 'unsubscribed' ? 'unsubscribed' : 'subscribed',
                    'status'        => $status === 'unsubscribed' ? 'unsubscribed' : 'subscribed',
                    'merge_fields'  => ['FNAME' => $name],
                ],
                'headers'     => ['Accept' => 'application/json'],
            ]);

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Mailchimp sync failed: ' . $e->getMessage());

            return false;
        }
    }

    private function syncMailcoach(string $email, string $name, string $status): bool
    {
        $base  = rtrim((string) service('settings')->get('newsletter.mailcoach_url', ''), '/');
        $token = (string) service('settings')->get('newsletter.mailcoach_token', '');
        $list  = (string) service('settings')->get('newsletter.mailcoach_list', '');

        if ($base === '' || $token === '' || $list === '') {
            return false;
        }

        $url = $base . '/api/email-lists/' . rawurlencode($list) . '/subscribers';
        if (! preg_match('~^https?://~i', $url)) {
            return false;
        }

        $parts = ['first_name' => $name];

        try {
            $client = Services::curlrequest(['timeout' => 12, 'http_errors' => false], null, null, false);

            if ($status === 'unsubscribed') {
                $client->request('DELETE', $url . '/' . rawurlencode($email), [
                    'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                ]);

                return true;
            }

            $client->request('POST', $url, [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                'json'    => array_merge(['email' => $email], array_filter($parts)),
            ]);

            return true;
        } catch (Throwable $e) {
            log_message('error', 'Mailcoach sync failed: ' . $e->getMessage());

            return false;
        }
    }
}
