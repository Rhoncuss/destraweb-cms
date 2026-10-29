<?php

namespace App\Controllers\Admin;

use App\Libraries\Auth;
use Throwable;

class Profile extends BaseAdminController
{
    public function index(): string
    {
        $db   = db_connect();
        $id   = (int) ($this->admin['id'] ?? 0);
        $meta = $this->meta();

        $stats = [
            'content' => 0,
            'published' => 0,
            'media'   => 0,
            'comments' => 0,
        ];

        try {
            $stats['content']   = (int) $db->table('contents')->where('author_id', $id)->where('deleted_at', null)->countAllResults();
            $stats['published'] = (int) $db->table('contents')->where('author_id', $id)->where('status', 'published')->where('deleted_at', null)->countAllResults();
            $stats['media']     = (int) $db->table('media')->where('user_id', $id)->where('deleted_at', null)->countAllResults();
            $stats['comments']  = (int) $db->table('comments')->where('user_id', $id)->countAllResults();
        } catch (Throwable) {
        }

        $logins = [];
        $activity = [];
        try {
            $logins = $db->table('login_attempts')
                ->where('email', (string) ($this->admin['email'] ?? ''))
                ->orderBy('id', 'DESC')->limit(8)->get()->getResultArray();
            $activity = $db->table('audit_logs')
                ->where('user_id', $id)->orderBy('id', 'DESC')->limit(8)->get()->getResultArray();
        } catch (Throwable) {
        }

        return $this->render('admin/profile/index', [
            'pageTitle'    => 'My profile',
            'pageSubtitle' => 'Your account details, password and workspace appearance.',
            'navActive'    => 'profile',
            'breadcrumbs'  => [['label' => 'My profile']],
            'user'         => $this->admin,
            'stats'        => $stats,
            'logins'       => $logins,
            'activity'     => $activity,
            'languages'    => languages(true),
            'timezones'    => timezone_identifiers_list(),
            'schemes'      => admin_schemes(),
            'prefs'        => [
                'admin_scheme' => (string) ($meta['admin_scheme'] ?? ''),
            ],
        ]);
    }

    public function update()
    {
        $db  = db_connect();
        $san = service('sanitizer');
        $id  = (int) ($this->admin['id'] ?? 0);
        $row = $this->fresh();

        if ($row === null) {
            return redirect()->back()->with('error', 'Your account could not be loaded.');
        }

        $post = (array) $this->request->getPost();

        $name = mb_substr($san->text($post['name'] ?? ''), 0, 120);
        $username = $san->username($post['username'] ?? '');
        $email = $san->email($post['email'] ?? '');
        $locale = (string) ($post['locale'] ?? default_locale());
        $timezone = (string) ($post['timezone'] ?? '');
        $bio = mb_substr($san->textarea($post['bio'] ?? ''), 0, 1000);
        $avatar = (int) $san->int($post['avatar'] ?? 0, 0);

        if ($name === '' || $username === '' || $email === '') {
            return redirect()->back()->withInput()->with('error', 'Name, username and a valid email address are required.');
        }

        $dupe = $db->table('users')->groupStart()->where('email', $email)->orWhere('username', $username)->groupEnd()
            ->where('id !=', $id)->countAllResults();
        if ($dupe > 0) {
            return redirect()->back()->withInput()->with('error', 'That email or username is already used by another account.');
        }

        $emailChanged = strcasecmp((string) $row['email'], $email) !== 0;
        if ($emailChanged) {
            $current = (string) ($post['current_password'] ?? '');
            if ($current === '' || ! password_verify($current, (string) $row['password_hash'])) {
                return redirect()->back()->withInput()->with('error', 'Enter your current password to change the sign-in email.');
            }
        }

        $localeCodes = array_column(languages(true), 'code');
        if (! in_array($locale, $localeCodes, true)) {
            $locale = (string) $row['locale'];
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = (string) ($row['timezone'] ?? '');
        }

        $avatarValue = null;
        if ($avatar > 0 && $db->table('media')->where('id', $avatar)->where('deleted_at', null)->countAllResults() > 0) {
            $avatarValue = (string) $avatar;
        }

        $db->table('users')->where('id', $id)->update([
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'locale'     => $locale,
            'timezone'   => $timezone !== '' ? $timezone : null,
            'bio'        => $bio !== '' ? $bio : null,
            'avatar'     => $avatarValue,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set('admin_name', $name);
        session()->set('admin_email', $email);

        $this->audit('profile.update', 'Updated own profile', 'users', 'user', $id);

        return redirect()->to(admin_url('profile'))->with('success', 'Profile updated.');
    }

    public function password()
    {
        $db  = db_connect();
        $id  = (int) ($this->admin['id'] ?? 0);
        $row = $this->fresh();

        if ($row === null) {
            return redirect()->back()->with('error', 'Your account could not be loaded.');
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');

        if ($current === '' || ! password_verify($current, (string) $row['password_hash'])) {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        if ($new !== $confirm) {
            return redirect()->back()->with('error', 'The new password and confirmation do not match.');
        }

        if ($new === $current) {
            return redirect()->back()->with('error', 'Choose a password you have not used on this account before.');
        }

        if ($error = Auth::validatePasswordStrength($new)) {
            return redirect()->back()->with('error', $error);
        }

        $db->table('users')->where('id', $id)->update([
            'password_hash'      => Auth::hashPassword($new),
            'remember_token'     => null,
            'remember_expires_at' => null,
            'reset_token'        => null,
            'reset_expires_at'   => null,
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->audit('profile.password', 'Changed own password', 'users', 'user', $id);

        return redirect()->to(admin_url('profile'))->with('success', 'Password changed. Other remembered devices have been signed out.');
    }

    public function preferences()
    {
        $scheme = (string) $this->request->getPost('admin_scheme');
        if (! isset(admin_schemes()[$scheme])) {
            return $this->fail('Unknown colour scheme.');
        }

        $meta = $this->meta();
        $meta['admin_scheme'] = $scheme;

        db_connect()->table('users')->where('id', (int) ($this->admin['id'] ?? 0))->update([
            'meta'       => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        session()->set('admin_scheme', $scheme);

        return $this->ok(['scheme' => $scheme], 'Workspace colour updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(): array
    {
        $raw = $this->admin['meta'] ?? null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function fresh(): ?array
    {
        try {
            $row = db_connect()->table('users')
                ->where('id', (int) ($this->admin['id'] ?? 0))
                ->where('deleted_at', null)
                ->get()->getRowArray();
        } catch (Throwable) {
            return null;
        }

        return $row ?: null;
    }
}
