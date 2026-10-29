<?php

namespace Config;

use App\Filters\AdminAuth;
use App\Filters\AdminGuest;
use App\Filters\ApiAuth;
use App\Filters\ApiThrottle;
use App\Filters\InputGuard;
use App\Filters\InstalledGuard;
use App\Filters\InstallGuard;
use App\Filters\Locale;
use App\Filters\Maintenance;
use App\Filters\PermissionFilter;
use App\Filters\SecurityHeaders;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecurityHeaders::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'input'         => InputGuard::class,
        'installguard'  => InstallGuard::class,
        'installed'     => InstalledGuard::class,
        'locale'        => Locale::class,
        'adminauth'     => AdminAuth::class,
        'adminguest'    => AdminGuest::class,
        'permission'    => PermissionFilter::class,
        'maintenance'   => Maintenance::class,
        'apiauth'       => ApiAuth::class,
        'apithrottle'   => ApiThrottle::class,
    ];

    public array $required = [
        'before' => [
            'forcehttps',
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    public array $globals = [
        'before' => [
            'maintenance' => ['except' => ['admin', 'admin/*', 'login', 'login/*', 'install', 'install/*', 'theme-assets/*', 'plugin-assets/*', 'media/*', 'api/*']],
            'input'       => ['except' => ['media/*', 'theme-assets/*', 'plugin-assets/*']],
            'csrf'        => ['except' => ['api/*']],
        ],
        'after' => [
            'secureheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [];
}
