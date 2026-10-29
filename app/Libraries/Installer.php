<?php

namespace App\Libraries;

use Config\Destra;
use Config\Services;
use mysqli;
use Throwable;

/**
 * Guided installer: requirement checks, database validation, .env generation,
 * migrations and seeding. Every step is idempotent enough to retry safely.
 */
class Installer
{
    /**
     * @return list<array{label: string, ok: bool, detail: string, required: bool}>
     */
    public function requirements(): array
    {
        $checks = [];

        $checks[] = [
            'label'    => 'PHP 8.2 or newer',
            'ok'       => PHP_VERSION_ID >= 80200,
            'detail'   => PHP_VERSION,
            'required' => true,
        ];

        foreach (['intl', 'mbstring', 'mysqli', 'openssl', 'fileinfo', 'gd', 'zip', 'json', 'curl'] as $extension) {
            $checks[] = [
                'label'    => 'PHP extension: ' . $extension,
                'ok'       => extension_loaded($extension),
                'detail'   => extension_loaded($extension) ? 'Loaded' : 'Missing',
                'required' => true,
            ];
        }

        $checks[] = [
            'label'    => 'Root folder writable (.env)',
            'ok'       => is_writable(ROOTPATH),
            'detail'   => ROOTPATH,
            'required' => true,
        ];

        foreach (['writable', 'writable/cache', 'writable/logs', 'writable/session', 'writable/uploads'] as $dir) {
            $path = ROOTPATH . str_replace('/', DIRECTORY_SEPARATOR, $dir);
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $checks[] = [
                'label'    => 'Writable: ' . $dir,
                'ok'       => is_dir($path) && is_writable($path),
                'detail'   => $path,
                'required' => true,
            ];
        }

        foreach (['themes', 'plugins'] as $dir) {
            $path = ROOTPATH . $dir;
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $checks[] = [
                'label'    => 'Writable: ' . $dir . '/',
                'ok'       => is_dir($path) && is_writable($path),
                'detail'   => $path,
                'required' => false,
            ];
        }

        return $checks;
    }

    public function requirementsMet(): bool
    {
        foreach ($this->requirements() as $check) {
            if ($check['required'] && ! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{hostname: string, port: int|string, database: string, username: string, password: string} $db
     * @return array{ok: bool, message: string, version?: string, created?: bool}
     */
    public function testDatabase(array $db): array
    {
        $host     = trim((string) ($db['hostname'] ?? 'localhost'));
        $port     = (int) ($db['port'] ?? 3306);
        $database = trim((string) ($db['database'] ?? ''));
        $username = (string) ($db['username'] ?? '');
        $password = (string) ($db['password'] ?? '');

        if ($host === '' || ! preg_match('/^[a-z0-9_.:\-]+$/i', $host)) {
            return ['ok' => false, 'message' => 'Enter a valid database host.'];
        }
        if ($port < 1 || $port > 65535) {
            return ['ok' => false, 'message' => 'Enter a valid database port.'];
        }
        if ($database === '' || ! preg_match('/^[a-z0-9_]+$/i', $database)) {
            return ['ok' => false, 'message' => 'The database name may only contain letters, numbers and underscores.'];
        }
        if ($username === '') {
            return ['ok' => false, 'message' => 'Enter the database username.'];
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            $connection = @new mysqli($host, $username, $password, '', $port);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Could not reach MySQL: ' . $e->getMessage()];
        }

        if ($connection->connect_errno) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $connection->connect_error];
        }

        $created = false;
        if (! $connection->select_db($database)) {
            if (! @$connection->query('CREATE DATABASE `' . $connection->real_escape_string($database) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')) {
                $message = 'The database does not exist and could not be created: ' . $connection->error;
                $connection->close();

                return ['ok' => false, 'message' => $message];
            }
            $created = true;
        }

        $version = (string) $connection->server_info;
        $connection->close();

        return ['ok' => true, 'message' => 'Connected successfully.', 'version' => $version, 'created' => $created];
    }

    /**
     * @param array<string, mixed> $config
     * @return array{ok: bool, message: string}
     */
    public function writeEnvironment(array $config): array
    {
        $destra = config(Destra::class);
        $env    = [
            'CI_ENVIRONMENT'                     => ($config['environment'] ?? 'production') === 'development' ? 'development' : 'production',
            'app.baseURL'                        => $this->baseUrl((string) ($config['base_url'] ?? '')),
            'app.indexPage'                      => '',
            'app.appTimezone'                    => (string) ($config['timezone'] ?? 'Asia/Jakarta'),
            'app.forceGlobalSecureRequests'      => 'false',
            'app.CSPEnabled'                     => 'false',
            'database.default.hostname'          => (string) $config['hostname'],
            'database.default.database'          => (string) $config['database'],
            'database.default.username'          => (string) $config['username'],
            'database.default.password'          => (string) $config['password'],
            'database.default.DBDriver'          => 'MySQLi',
            'database.default.DBPrefix'          => (string) ($config['db_prefix'] ?? 'cms_'),
            'database.default.port'              => (string) $config['port'],
            'database.default.charset'           => 'utf8mb4',
            'destra.encryptionKey'               => base64_encode(random_bytes(32)),
            'destra.adminPrefix'                 => (string) ($config['admin_prefix'] ?? 'admin'),
            'security.csrfProtection'            => 'session',
            'security.tokenRandomize'            => 'false',
            'security.regenerate'                => 'false',
            'security.redirect'                  => 'false',
            'security.headerName'                => 'X-CSRF-TOKEN',
            'session.expiration'                 => '7200',
            'session.matchIP'                    => 'false',
            'cookie.prefix'                      => 'destra_',
        ];

        $content = "#--------------------------------------------------------------------\n"
            . "# DESTRA CMS environment — generated by the installer\n"
            . '# ' . date('Y-m-d H:i:s') . "\n"
            . "# Keep this file outside version control and never share its keys.\n"
            . "#--------------------------------------------------------------------\n\n";

        foreach ($env as $key => $value) {
            $content .= $this->envLine($key, (string) $value) . "\n";
        }

        $path = ROOTPATH . '.env';
        $tmp  = $path . '.tmp';

        try {
            if (is_file($path)) {
                @copy($path, $path . '.backup-' . date('YmdHis'));
            }
            if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
                return ['ok' => false, 'message' => 'Could not write the .env file. Check folder permissions.'];
            }
            @chmod($tmp, 0600);
            if (! @rename($tmp, $path)) {
                @unlink($tmp);

                return ['ok' => false, 'message' => 'Could not move the .env file into place.'];
            }
            @chmod($path, 0600);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Environment setup failed: ' . $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Environment file written.'];
    }

    /**
     * @param array{site_name: string, tagline?: string, admin_name: string, admin_email: string, admin_password: string, locale?: string} $site
     * @return array{ok: bool, message: string, admin_id?: int}
     */
    public function finish(array $site): array
    {
        if (is_file(config(Destra::class)->installLock)) {
            return ['ok' => false, 'message' => 'DESTRA is already installed.'];
        }

        try {
            $runner = Services::migrations();
            $runner->setNamespace(null);
            $runner->latest('default');
        } catch (Throwable $e) {
            log_message('error', 'Installer migration failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Database migrations failed: ' . $e->getMessage()];
        }

        try {
            $seeder  = new \App\Database\Seeds\InstallSeeder(config(\Config\Database::class));
            $adminId = $seeder->runInstall($site);
        } catch (Throwable $e) {
            log_message('error', 'Installer seeding failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Seeding failed: ' . $e->getMessage()];
        }

        try {
            $lock = json_encode([
                'installed_at' => date('c'),
                'version'      => config(Destra::class)->version,
                'codename'     => config(Destra::class)->codename,
            ], JSON_PRETTY_PRINT);

            if (@file_put_contents(config(Destra::class)->installLock, $lock, LOCK_EX) === false) {
                return ['ok' => false, 'message' => 'The installation lock could not be written.'];
            }
            @chmod(config(Destra::class)->installLock, 0644);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Could not finalise the installation: ' . $e->getMessage()];
        }

        try {
            service('cache_manager')->flush();
        } catch (Throwable) {
            // Cache is optional.
        }

        return ['ok' => true, 'message' => 'Installation complete.', 'admin_id' => (int) $adminId];
    }

    private function baseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            $baseUrl = base_url('/');
        }
        if (! preg_match('~^https?://~i', $baseUrl)) {
            $baseUrl = 'http://' . $baseUrl;
        }

        return rtrim($baseUrl, '/') . '/';
    }

    private function envLine(string $key, string $value): string
    {
        $value = str_replace(["\r", "\n", "\0"], '', $value);
        if ($value === '' || preg_match('/[\s#"\']/', $value)) {
            $value = '"' . addcslashes($value, "\\\"") . '"';
        }

        return $key . ' = ' . $value;
    }
}
