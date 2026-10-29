<?php

namespace App\Controllers;

use App\Libraries\Auth;
use App\Libraries\Installer;
use Config\Destra;

class Install extends BaseController
{
    private Installer $installer;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! is_file(config(Destra::class)->installLock)) {
            $detected = $this->detectedBaseUrl();
            if ($detected !== '') {
                config('App')->baseURL = $detected;
            }
        }

        $this->installer = new Installer();
        helper(['form', 'url', 'destra']);
    }

    public function index(): string
    {
        return view('installer/requirements', [
            'title'        => 'Server Requirements',
            'step'         => $this->installer->requirementsMet() ? 2 : 1,
            'checks'       => $this->installer->requirements(),
            'ready'        => $this->installer->requirementsMet(),
            'defaults'     => [
                'hostname' => (string) (env('database.default.hostname') ?: 'localhost'),
                'port'     => (string) (env('database.default.port') ?: '3306'),
                'database' => (string) (env('database.default.database') ?: 'destra_cms'),
                'username' => (string) (env('database.default.username') ?: 'root'),
                'password' => (string) (env('database.default.password') ?: ''),
                'prefix'   => (string) (env('database.default.DBPrefix') ?: 'cms_'),
            ],
            'errors'       => session()->getFlashdata('install_errors') ?? [],
            'old'          => session()->getFlashdata('install_old') ?? [],
            'base'         => $this->baseUrl(),
        ]);
    }

    public function database()
    {
        helper(['form']);

        $rules = [
            'hostname' => [
                'label' => 'Database host',
                'rules' => 'required|max_length[120]|regex_match[/^[a-zA-Z0-9_.:\-]+$/]',
                'errors' => ['regex_match' => 'The database host contains invalid characters.'],
            ],
            'port' => [
                'label' => 'Port',
                'rules' => 'required|integer|greater_than[0]|less_than[65536]',
            ],
            'database' => [
                'label' => 'Database name',
                'rules' => 'required|max_length[64]|regex_match[/^[a-zA-Z0-9_]+$/]',
                'errors' => ['regex_match' => 'Use letters, numbers and underscores only.'],
            ],
            'username' => [
                'label' => 'Database username',
                'rules' => 'required|max_length[64]',
            ],
            'password' => [
                'label' => 'Database password',
                'rules' => 'permit_empty|max_length[255]',
            ],
            'prefix' => [
                'label' => 'Table prefix',
                'rules' => 'permit_empty|max_length[20]|regex_match[/^[a-zA-Z0-9_]*$/]',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('install_errors', $this->validator->getErrors());
        }

        $db = [
            'hostname'  => (string) $this->request->getPost('hostname'),
            'port'      => (int) $this->request->getPost('port'),
            'database'  => (string) $this->request->getPost('database'),
            'username'  => (string) $this->request->getPost('username'),
            'password'  => (string) $this->request->getPost('password'),
            'db_prefix' => (string) ($this->request->getPost('prefix') ?: 'cms_'),
        ];

        $test = $this->installer->testDatabase($db);
        if (! $test['ok']) {
            return redirect()->back()->withInput()->with('install_errors', ['database' => $test['message']]);
        }

        $written = $this->installer->writeEnvironment([
            'environment' => 'production',
            'base_url'    => $this->baseUrl(),
            'timezone'    => (string) (env('app.appTimezone') ?: 'Asia/Jakarta'),
            'hostname'    => $db['hostname'],
            'port'        => $db['port'],
            'database'    => $db['database'],
            'username'    => $db['username'],
            'password'    => $db['password'],
            'db_prefix'   => $db['db_prefix'],
            'admin_prefix' => 'admin',
        ]);

        if (! $written['ok']) {
            return redirect()->back()->withInput()->with('install_errors', ['database' => $written['message']]);
        }

        return redirect()->to($this->baseUrl() . 'install/site');
    }

    public function site(): string
    {
        if (! is_file(ROOTPATH . '.env') || is_file(config(Destra::class)->installLock)) {
            return redirect()->to($this->baseUrl() . 'install')->with('install_errors', ['database' => 'Configure the database connection first.']);
        }

        return view('installer/site', [
            'title'    => 'Site & Administrator',
            'step'     => 3,
            'defaults' => [
                'site_name'  => 'DESTRA CMS',
                'tagline'    => 'Ideas, crafted beautifully.',
                'admin_name' => 'Site Administrator',
                'admin_email' => '',
                'timezone'   => (string) (env('app.appTimezone') ?: 'Asia/Jakarta'),
                'locale'     => 'en',
            ],
            'errors'   => session()->getFlashdata('install_errors') ?? [],
            'old'      => session()->getFlashdata('install_old') ?? [],
            'base'     => $this->baseUrl(),
            'timezones' => $this->timezones(),
        ]);
    }

    public function finish()
    {
        helper(['form']);

        if (is_file(config(Destra::class)->installLock)) {
            return redirect()->to($this->baseUrl() . 'install')->with('install_errors', ['general' => 'DESTRA is already installed.']);
        }

        $rules = [
            'site_name' => [
                'label' => 'Site name',
                'rules' => 'required|max_length[120]',
            ],
            'tagline' => [
                'label' => 'Tagline',
                'rules' => 'permit_empty|max_length[200]',
            ],
            'admin_name' => [
                'label' => 'Full name',
                'rules' => 'required|max_length[120]',
            ],
            'admin_email' => [
                'label' => 'Email address',
                'rules' => 'required|valid_email|max_length[180]',
            ],
            'admin_password' => [
                'label' => 'Password',
                'rules' => 'required|max_length[255]',
            ],
            'password_confirm' => [
                'label' => 'Password confirmation',
                'rules' => 'required|matches[admin_password]',
                'errors' => ['matches' => 'The two passwords do not match.'],
            ],
            'timezone' => [
                'label' => 'Timezone',
                'rules' => 'required|max_length[64]',
            ],
            'locale' => [
                'label' => 'Default language',
                'rules' => 'required|in_list[en,id,ar]',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('install_errors', $this->validator->getErrors());
        }

        $password = (string) $this->request->getPost('admin_password');
        if ($problem = Auth::validatePasswordStrength($password)) {
            return redirect()->back()->withInput()->with('install_errors', ['admin_password' => $problem]);
        }

        $result = $this->installer->finish([
            'site_name'      => (string) $this->request->getPost('site_name'),
            'tagline'        => (string) $this->request->getPost('tagline'),
            'admin_name'     => (string) $this->request->getPost('admin_name'),
            'admin_email'    => (string) $this->request->getPost('admin_email'),
            'admin_password' => $password,
            'locale'         => (string) $this->request->getPost('locale'),
        ]);

        if (! $result['ok']) {
            return view('installer/site', [
                'title'    => 'Site & Administrator',
                'step'     => 3,
                'defaults' => [
                    'site_name'   => (string) $this->request->getPost('site_name'),
                    'tagline'     => (string) $this->request->getPost('tagline'),
                    'admin_name'  => (string) $this->request->getPost('admin_name'),
                    'admin_email' => (string) $this->request->getPost('admin_email'),
                    'timezone'    => (string) $this->request->getPost('timezone'),
                    'locale'      => (string) $this->request->getPost('locale'),
                ],
                'errors'    => ['general' => $result['message']],
                'old'       => [],
                'base'      => $this->baseUrl(),
                'timezones' => $this->timezones(),
            ]);
        }

        try {
            service('auth')->loginById((int) $result['admin_id'], false, $this->request);
        } catch (\Throwable $e) {
            log_message('error', 'Installer auto-login failed: ' . $e->getMessage());
        }

        return view('installer/complete', [
            'title'      => 'Installation complete',
            'step'       => 4,
            'adminUrl'   => $this->baseUrl() . config(Destra::class)->adminPrefix,
            'homeUrl'    => $this->baseUrl(),
            'adminEmail' => (string) $this->request->getPost('admin_email'),
        ]);
    }

    private function baseUrl(): string
    {
        return $this->detectedBaseUrl() ?: base_url('/');
    }

    /**
     * Builds the site base URL from the current request (scheme, host, port and
     * subfolder) so installer forms and redirects stay on the same origin as the
     * browser, even before the correct app.baseURL has been written to .env.
     */
    private function detectedBaseUrl(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '' || ! preg_match('/^(?:\[[0-9A-Fa-f:.]+\]|[A-Za-z0-9.\-]+)(?::[0-9]{1,5})?$/', $host)) {
            return '';
        }

        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $https     = (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
            || $forwarded === 'https';
        $scheme = $https ? 'https' : 'http';

        $path = (string) parse_url('http://localhost' . ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');

        if (preg_match('~/(?:index\.php|install)(?:/|$)~', $path, $match, PREG_OFFSET_CAPTURE)) {
            $path = substr($path, 0, $match[0][1]) ?: '/';
        }

        $basePath = '/' . trim($path, '/');

        return $scheme . '://' . $host . ($basePath === '/' ? '/' : $basePath . '/');
    }

    /**
     * @return array<string, string>
     */
    private function timezones(): array
    {
        $zones = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'Asia/Singapore', 'Asia/Kuala_Lumpur', 'Asia/Dubai', 'Asia/Riyadh', 'Europe/London', 'Europe/Berlin', 'Europe/Paris', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Australia/Sydney', 'UTC'];
        $out   = [];
        foreach ($zones as $zone) {
            $out[$zone] = $zone;
        }

        return $out;
    }
}
