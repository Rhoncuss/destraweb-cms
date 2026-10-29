<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RequestInterface;
use Throwable;

class Auth
{
    private ?array $user = null;

    private bool $resolved = false;

    /** @var list<string>|null */
    private ?array $permissions = null;

    private ?string $failure = null;

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;

        if (is_cli()) {
            return null;
        }

        try {
            $session = session();
        } catch (Throwable) {
            return null;
        }

        $id = (int) $session->get('admin_user_id');

        if ($id <= 0) {
            $this->loginFromRememberCookie($session);
            $id = (int) $session->get('admin_user_id');
        }

        if ($id <= 0) {
            return null;
        }

        if (! $this->validateFingerprint($session)) {
            $this->failure = 'Session security check failed. Please sign in again.';
            $this->logout(false);
            session()->setFlashdata('error', $this->failure);

            return null;
        }

        $timeout = (int) service('settings')->get('security.session_timeout', 120);
        $last    = (int) $session->get('admin_last_seen');
        if ($timeout > 0 && $last > 0 && time() - $last > $timeout * 60) {
            $this->failure = 'Your session expired after a period of inactivity.';
            $this->logout(false);
            session()->setFlashdata('error', $this->failure);

            return null;
        }

        $session->set('admin_last_seen', time());

        try {
            $user = db_connect()->table('users u')
                ->select('u.*, r.name AS role_name, r.slug AS role_slug')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.id', $id)
                ->where('u.deleted_at', null)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return null;
        }

        if (! $user || $user['status'] !== 'active') {
            $this->logout(false);

            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    public function id(): int
    {
        return (int) ($this->user()['id'] ?? 0);
    }

    public function isSuperAdmin(): bool
    {
        return ($this->user()['role_slug'] ?? '') === 'super-admin';
    }

    public function can(string $permission): bool
    {
        if (! $this->check()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->permissions === null) {
            $this->permissions = [];
            try {
                $rows = db_connect()->table('role_permissions rp')
                    ->select('p.slug')
                    ->join('permissions p', 'p.id = rp.permission_id')
                    ->where('rp.role_id', (int) $this->user['role_id'])
                    ->get()
                    ->getResultArray();
                $this->permissions = array_column($rows, 'slug');
            } catch (Throwable) {
                $this->permissions = [];
            }
        }

        return in_array($permission, $this->permissions, true);
    }

    /**
     * @param list<string> $permissions
     */
    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public function attempt(string $email, string $password, bool $remember = false, ?RequestInterface $request = null): array
    {
        $email = strtolower(trim($email));
        $request ??= service('request');
        $ip = $request->getIPAddress();

        if (! service('sanitizer')->email($email) || $password === '') {
            return ['ok' => false, 'message' => 'Please enter a valid email and password.'];
        }

        $maxAttempts = (int) service('settings')->get('security.login_attempts', 5);
        $lockMinutes = (int) service('settings')->get('security.lockout_minutes', 15);
        $window      = date('Y-m-d H:i:s', time() - $lockMinutes * 60);

        try {
            $failures = db_connect()->table('login_attempts')
                ->where('email', $email)
                ->where('ip', $ip)
                ->where('success', 0)
                ->where('created_at >=', $window)
                ->countAllResults();

            if ($failures >= $maxAttempts) {
                return ['ok' => false, 'message' => "Too many attempts. Please try again in {$lockMinutes} minutes."];
            }
        } catch (Throwable) {
        }

        try {
            $user = db_connect()->table('users')
                ->where('email', $email)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            $user = null;
        }

        if (! $user || ! password_verify($password, (string) $user['password_hash'])) {
            $this->logAttempt($email, $ip, $request->getUserAgent()->getAgentString(), false);

            return ['ok' => false, 'message' => 'The credentials you entered are incorrect.'];
        }

        if ($user['status'] !== 'active') {
            $this->logAttempt($email, $ip, $request->getUserAgent()->getAgentString(), false);

            return ['ok' => false, 'message' => 'This account is not active. Please contact the administrator.'];
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_ARGON2ID, self::argonOptions())) {
            db_connect()->table('users')->where('id', $user['id'])->update([
                'password_hash' => self::hashPassword($password),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $this->logAttempt($email, $ip, $request->getUserAgent()->getAgentString(), true);
        $this->establishSession((int) $user['id'], (string) $user['email'], $remember, $request);

        try {
            db_connect()->table('users')->where('id', $user['id'])->update([
                'last_login_at' => date('Y-m-d H:i:s'),
                'last_login_ip' => service('crypto')->hashIp($ip),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }

        service('audit')->log('auth.login', 'Signed in to the dashboard', 'auth', 'user', (int) $user['id']);

        return ['ok' => true, 'message' => 'Welcome back, ' . $user['name'] . '.', 'user_id' => (int) $user['id']];
    }

    public function loginById(int $userId, bool $remember = false, ?RequestInterface $request = null): bool
    {
        $request ??= service('request');

        try {
            $user = db_connect()->table('users')->where('id', $userId)->where('deleted_at', null)->get()->getRowArray();
        } catch (Throwable) {
            return false;
        }

        if (! $user || $user['status'] !== 'active') {
            return false;
        }

        $this->establishSession($userId, (string) $user['email'], $remember, $request);
        service('audit')->log('auth.login', 'Signed in via external provider', 'auth', 'user', $userId);

        return true;
    }

    public function logout(bool $clearRemember = true): void
    {
        $userId = (int) session('admin_user_id');

        if ($clearRemember && $userId > 0) {
            try {
                db_connect()->table('users')->where('id', $userId)->update([
                    'remember_token'      => null,
                    'remember_expires_at' => null,
                ]);
            } catch (Throwable) {
            }
        }

        try {
            delete_cookie('remember');
        } catch (Throwable) {
        }

        if (session_status() === PHP_SESSION_ACTIVE || is_file(WRITEPATH . 'session/' . session_id())) {
            session()->destroy();
        }

        $this->user        = null;
        $this->permissions = null;
        $this->resolved    = true;
    }

    public function lastFailure(): ?string
    {
        return $this->failure;
    }

    public function updateSessionActivity(): void
    {
        if ($this->check()) {
            session()->set('admin_last_seen', time());
        }
    }

    public static function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID, self::argonOptions());
        if (! is_string($hash)) {
            throw new \RuntimeException('Password hashing is unavailable on this server.');
        }

        return $hash;
    }

    public static function argonOptions(): array
    {
        return [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 2,
        ];
    }

    public static function validatePasswordStrength(string $password): ?string
    {
        $min = max(8, (int) service('settings')->get('security.password_min', 10));

        if (strlen($password) < $min) {
            return "Password must be at least {$min} characters.";
        }

        if (! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
            return 'Password must include at least one letter and one number.';
        }

        if (preg_match('/^(password|qwerty|letmein|admin|welcome|123456)/i', $password)) {
            return 'This password is too common. Please choose a stronger one.';
        }

        return null;
    }

    public function fingerprint(): string
    {
        $ua = service('request')->getUserAgent()->getAgentString();

        return hash('sha256', 'dst|' . $ua . '|' . (string) env('destra.fingerprintSalt', 'destra'));
    }

    private function establishSession(int $userId, string $email, bool $remember, RequestInterface $request): void
    {
        $session = session();
        $session->regenerate(true);
        $session->set([
            'admin_user_id'    => $userId,
            'admin_email'      => $email,
            'admin_fingerprint'=> $this->fingerprint(),
            'admin_last_seen'  => time(),
        ]);

        $this->resolved    = false;
        $this->user        = null;
        $this->permissions = null;

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 30 * DAY;
            db_connect()->table('users')->where('id', $userId)->update([
                'remember_token'      => service('crypto')->hashToken($token),
                'remember_expires_at' => date('Y-m-d H:i:s', $expires),
            ]);

            set_cookie([
                'name'     => 'remember',
                'value'    => $userId . ':' . $token,
                'expire'   => 30 * DAY,
                'httponly' => true,
                'secure'   => (bool) config('Cookie')->secure,
                'samesite' => 'Lax',
            ]);
        } else {
            delete_cookie('remember');
        }
    }

    private function loginFromRememberCookie($session): void
    {
        $raw = get_cookie('remember');
        if (! is_string($raw) || ! str_contains($raw, ':')) {
            return;
        }

        [$id, $token] = explode(':', $raw, 2);
        $id = (int) $id;
        if ($id <= 0 || $token === '') {
            delete_cookie('remember');

            return;
        }

        try {
            $user = db_connect()->table('users')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        } catch (Throwable) {
            return;
        }

        if (! $user || $user['status'] !== 'active' || empty($user['remember_token']) || empty($user['remember_expires_at'])) {
            delete_cookie('remember');

            return;
        }

        if (strtotime((string) $user['remember_expires_at']) < time() || ! hash_equals((string) $user['remember_token'], service('crypto')->hashToken($token))) {
            delete_cookie('remember');

            return;
        }

        $session->set([
            'admin_user_id'    => $id,
            'admin_email'      => $user['email'],
            'admin_fingerprint'=> $this->fingerprint(),
            'admin_last_seen'  => time(),
        ]);

        // Rotate the token on every use.
        $newToken = bin2hex(random_bytes(32));
        db_connect()->table('users')->where('id', $id)->update([
            'remember_token'      => service('crypto')->hashToken($newToken),
            'remember_expires_at' => date('Y-m-d H:i:s', time() + 30 * DAY),
        ]);
        set_cookie([
            'name'     => 'remember',
            'value'    => $id . ':' . $newToken,
            'expire'   => 30 * DAY,
            'httponly' => true,
            'secure'   => (bool) config('Cookie')->secure,
            'samesite' => 'Lax',
        ]);
    }

    private function validateFingerprint($session): bool
    {
        $stored = (string) $session->get('admin_fingerprint');

        return $stored !== '' && hash_equals($stored, $this->fingerprint());
    }

    private function logAttempt(string $email, string $ip, string $ua, bool $success): void
    {
        try {
            db_connect()->table('login_attempts')->insert([
                'email'      => mb_substr($email, 0, 190),
                'ip'         => mb_substr($ip, 0, 64),
                'user_agent' => mb_substr($ua, 0, 255),
                'success'    => $success ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }
    }
}
