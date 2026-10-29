<?php

namespace App\Controllers;

use App\Libraries\Auth as AuthService;
use Throwable;

class Auth extends BaseController
{
    private AuthService $auth;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = service('auth');
        helper(['form', 'url', 'destra']);
    }

    public function login()
    {
        if ($this->auth->check()) {
            return redirect()->to($this->homeForUser());
        }

        return view('auth/login', [
            'title'   => 'Sign in',
            'error'   => session()->getFlashdata('error'),
            'success' => session()->getFlashdata('success'),
            'captcha' => service('captcha')->required('login') ? service('captcha')->widget('login') : '',
            'socialProviders' => service('oauth')->enabledProviders(),
        ]);
    }

    public function social(string $provider)
    {
        if ($this->auth->check()) {
            return redirect()->to($this->homeForUser());
        }

        $url = service('oauth')->authorizeUrl($provider);
        if ($url === '') {
            return redirect()->to(site_url('login'))->with('error', 'That sign-in provider is not available.');
        }

        return redirect()->to($url);
    }

    public function socialCallback(string $provider)
    {
        $result = service('oauth')->callback(
            $provider,
            (string) ($this->request->getGet('code') ?? ''),
            (string) ($this->request->getGet('state') ?? '')
        );

        if (! ($result['ok'] ?? false)) {
            return redirect()->to(site_url('login'))->with('error', (string) ($result['message'] ?? 'Sign in failed.'));
        }

        return redirect()->to($this->homeForUser())->with('success', (string) ($result['message'] ?? 'Signed in.'));
    }

    public function attempt()
    {
        helper(['form']);

        $rules = [
            'email' => ['label' => 'Email', 'rules' => 'required|valid_email|max_length[180]'],
            'password' => ['label' => 'Password', 'rules' => 'required|max_length[255]'],
            'remember' => ['label' => 'Remember me', 'rules' => 'permit_empty|in_list[1]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if (service('captcha')->required('login') && ! service('captcha')->verifyRequest('login')) {
            return redirect()->back()->withInput()->with('error', 'Captcha verification failed. Please try again.');
        }

        $result = $this->auth->attempt(
            (string) $this->request->getPost('email'),
            (string) $this->request->getPost('password'),
            $this->request->getPost('remember') === '1',
            $this->request,
        );

        if (! $result['ok']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        $redirect = (string) session()->get('redirect_url');
        session()->remove('redirect_url');

        if ($redirect === '' || ! str_starts_with($redirect, base_url())) {
            $redirect = $this->homeForUser();
        }

        return redirect()->to($redirect)->with('success', $result['message']);
    }

    public function logout()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(site_url('login'));
        }

        $this->auth->logout();
        service('audit')->log('auth.logout', 'Signed out of the dashboard', 'auth');

        return redirect()->to(site_url('login'))->with('success', 'You have been signed out.');
    }

    public function forgot()
    {
        if ($this->auth->check()) {
            return redirect()->to($this->homeForUser());
        }

        return view('auth/forgot', [
            'title'   => 'Reset your password',
            'error'   => session()->getFlashdata('error'),
            'success' => session()->getFlashdata('success'),
        ]);
    }

    public function sendReset()
    {
        helper(['form']);

        if (! $this->validate([
            'email' => ['label' => 'Email', 'rules' => 'required|valid_email|max_length[180]'],
        ])) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid email address.');
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));

        try {
            $user = db_connect()->table('users')
                ->where('email', $email)
                ->where('deleted_at', null)
                ->where('status', 'active')
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            $user = null;
        }

        if ($user) {
            $token = service('crypto')->randomToken(32);
            db_connect()->table('users')->where('id', $user['id'])->update([
                'reset_token'      => service('crypto')->hashToken($token),
                'reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            $link = site_url('reset-password/' . $token);
            service('mailer')->sendTemplate(
                (string) $user['email'],
                'Reset your ' . service('settings')->get('general.site_name', 'DESTRA') . ' password',
                'reset-password',
                [
                    'name'      => $user['name'],
                    'link'      => $link,
                    'expires'   => '60 minutes',
                    'site_name' => service('settings')->get('general.site_name', 'DESTRA CMS'),
                ],
            );

            service('audit')->log('auth.reset_requested', 'Password reset requested for ' . $email, 'auth', 'user', (int) $user['id']);
        }

        return redirect()->to(site_url('login'))->with('success', 'If that email exists, a reset link is on its way.');
    }

    public function reset(string $token = '')
    {
        $user = $this->findByResetToken($token);

        if (! $user) {
            return redirect()->to(site_url('forgot-password'))->with('error', 'This reset link is invalid or has expired.');
        }

        return view('auth/reset', [
            'title' => 'Choose a new password',
            'token' => $token,
            'email' => (string) $user['email'],
            'error' => session()->getFlashdata('error'),
        ]);
    }

    public function updatePassword()
    {
        helper(['form']);

        $token = (string) $this->request->getPost('token');
        $user  = $this->findByResetToken($token);

        if (! $user) {
            return redirect()->to(site_url('forgot-password'))->with('error', 'This reset link is invalid or has expired.');
        }

        $rules = [
            'password' => ['label' => 'Password', 'rules' => 'required|max_length[255]'],
            'password_confirm' => ['label' => 'Confirmation', 'rules' => 'required|matches[password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()))->withInput();
        }

        $password = (string) $this->request->getPost('password');
        if ($problem = AuthService::validatePasswordStrength($password)) {
            return redirect()->back()->with('error', $problem);
        }

        db_connect()->table('users')->where('id', $user['id'])->update([
            'password_hash'       => AuthService::hashPassword($password),
            'reset_token'         => null,
            'reset_expires_at'    => null,
            'remember_token'      => null,
            'remember_expires_at' => null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        service('audit')->log('auth.password_reset', 'Password changed via reset link', 'auth', 'user', (int) $user['id']);

        return redirect()->to(site_url('login'))->with('success', 'Your password has been updated. Please sign in.');
    }

    public function register()
    {
        if (! service('settings')->bool('general.registration_enabled')) {
            return redirect()->to(site_url('login'))->with('error', 'Registration is currently closed.');
        }

        if ($this->auth->check()) {
            return redirect()->to($this->homeForUser());
        }

        return view('auth/register', [
            'title'   => 'Create your account',
            'error'   => session()->getFlashdata('error'),
            'captcha' => service('captcha')->required('forms') ? service('captcha')->widget('forms') : '',
            'socialProviders' => service('oauth')->enabledProviders(),
        ]);
    }

    public function createAccount()
    {
        helper(['form']);

        if (! service('settings')->bool('general.registration_enabled')) {
            return redirect()->to(site_url('login'))->with('error', 'Registration is currently closed.');
        }

        $rules = [
            'name' => ['label' => 'Full name', 'rules' => 'required|min_length[2]|max_length[120]'],
            'username' => ['label' => 'Username', 'rules' => 'required|alpha_dash|min_length[3]|max_length[40]|is_unique[users.username]'],
            'email' => ['label' => 'Email', 'rules' => 'required|valid_email|max_length[180]|is_unique[users.email]'],
            'password' => ['label' => 'Password', 'rules' => 'required|max_length[255]'],
            'password_confirm' => ['label' => 'Confirmation', 'rules' => 'required|matches[password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if (service('captcha')->required('forms') && ! service('captcha')->verifyRequest('forms')) {
            return redirect()->back()->withInput()->with('error', 'Captcha verification failed. Please try again.');
        }

        $password = (string) $this->request->getPost('password');
        if ($problem = AuthService::validatePasswordStrength($password)) {
            return redirect()->back()->withInput()->with('error', $problem);
        }

        $roleId = (int) service('settings')->get('general.registration_role', 4);

        $db = db_connect();
        $db->table('users')->insert([
            'name'          => (string) $this->request->getPost('name'),
            'username'      => strtolower((string) $this->request->getPost('username')),
            'email'         => strtolower((string) $this->request->getPost('email')),
            'password_hash' => AuthService::hashPassword($password),
            'role_id'       => $roleId > 0 ? $roleId : 4,
            'locale'        => current_locale(),
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $db->insertID();
        $this->auth->loginById($userId, false, $this->request);

        service('audit')->log('auth.register', 'New account registered', 'auth', 'user', $userId);
        service('audit')->notify(1, 'user', 'New member registered', 'A new account was created: ' . $this->request->getPost('email'), admin_url('users'), 'fa-solid fa-user-plus');

        return redirect()->to(base_url('/'))->with('success', 'Welcome aboard! Your account is ready.');
    }

    private function findByResetToken(string $token): ?array
    {
        if ($token === '' || strlen($token) > 190) {
            return null;
        }

        try {
            $user = db_connect()->table('users')
                ->where('reset_token', service('crypto')->hashToken($token))
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            return null;
        }

        if (! $user || empty($user['reset_expires_at'])) {
            return null;
        }

        if (strtotime((string) $user['reset_expires_at']) < time()) {
            return null;
        }

        return $user;
    }

    private function homeForUser(): string
    {
        return $this->auth->can('dashboard.view') ? admin_url() : base_url('/');
    }
}
