<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
|--------------------------------------------------------------------------
| Installer
|--------------------------------------------------------------------------
| Available only while writable/installed.lock is missing.
*/
$routes->group('install', ['filter' => 'installguard'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Install::index');
    $routes->post('database', 'Install::database');
    $routes->get('site', 'Install::site');
    $routes->post('finish', 'Install::finish');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->post('logout', 'Auth::logout');
$routes->get('forgot-password', 'Auth::forgot');
$routes->post('forgot-password', 'Auth::sendReset');
$routes->get('reset-password/(:segment)', 'Auth::reset/$1');
$routes->post('reset-password', 'Auth::updatePassword');
$routes->get('/register', 'Auth::register');
$routes->post('register', 'Auth::createAccount');

$routes->get('auth/(:segment)', 'Auth::social/$1');
$routes->get('auth/(:segment)/callback', 'Auth::socialCallback/$1');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
$admin = trim((string) config(\Config\Destra::class)->adminPrefix, '/') ?: 'admin';

$routes->get($admin . '/login', 'Auth::login', ['filter' => 'adminguest']);
$routes->post($admin . '/login', 'Auth::attempt', ['filter' => 'adminguest']);

$routes->group($admin, ['filter' => 'adminauth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('search', 'Admin\Dashboard::search');

    $routes->post('ai/generate', 'Admin\Ai::generate');

    $routes->group('notifications', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Notifications::index');
        $routes->post('read-all', 'Admin\Notifications::readAll');
        $routes->post('clear-read', 'Admin\Notifications::clearRead');
        $routes->post('(:num)/open', 'Admin\Notifications::open/$1');
        $routes->post('(:num)/read', 'Admin\Notifications::read/$1');
        $routes->post('(:num)/delete', 'Admin\Notifications::delete/$1');
    });

    $routes->get('profile', 'Admin\Profile::index');
    $routes->post('profile', 'Admin\Profile::update');
    $routes->post('profile/password', 'Admin\Profile::password');
    $routes->post('profile/preferences', 'Admin\Profile::preferences');

    $routes->group('content', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Content::index');
        $routes->get('create', 'Admin\Content::create');
        $routes->post('store', 'Admin\Content::store');
        $routes->post('bulk', 'Admin\Content::bulk');
        $routes->get('(:num)/edit', 'Admin\Content::edit/$1');
        $routes->post('(:num)/update', 'Admin\Content::update/$1');
        $routes->post('(:num)/delete', 'Admin\Content::delete/$1');
        $routes->post('(:num)/restore', 'Admin\Content::restore/$1');
        $routes->post('(:num)/destroy', 'Admin\Content::destroy/$1');
    });

    $routes->group('media', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Media::index');
        $routes->get('picker', 'Admin\Media::picker');
        $routes->post('upload', 'Admin\Media::upload');
        $routes->post('folders', 'Admin\Media::folders');
        $routes->post('(:num)/update', 'Admin\Media::update/$1');
        $routes->post('(:num)/delete', 'Admin\Media::delete/$1');
    });

    $routes->group('sections', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Sections::index');
        $routes->get('create', 'Admin\Sections::create');
        $routes->post('store', 'Admin\Sections::store');
        $routes->get('(:num)/edit', 'Admin\Sections::edit/$1');
        $routes->post('(:num)/update', 'Admin\Sections::update/$1');
        $routes->post('(:num)/delete', 'Admin\Sections::delete/$1');
    });

    $routes->group('categories', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Category::index');
        $routes->get('create', 'Admin\Category::create');
        $routes->post('store', 'Admin\Category::store');
        $routes->get('(:num)/edit', 'Admin\Category::edit/$1');
        $routes->post('(:num)/update', 'Admin\Category::update/$1');
        $routes->post('(:num)/delete', 'Admin\Category::delete/$1');
        $routes->post('(:num)/toggle', 'Admin\Category::toggle/$1');
    });

    $routes->group('tags', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Tag::index');
        $routes->get('create', 'Admin\Tag::create');
        $routes->post('store', 'Admin\Tag::store');
        $routes->get('(:num)/edit', 'Admin\Tag::edit/$1');
        $routes->post('(:num)/update', 'Admin\Tag::update/$1');
        $routes->post('(:num)/delete', 'Admin\Tag::delete/$1');
    });

    $routes->group('menus', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Menu::index');
        $routes->post('store', 'Admin\Menu::store');
        $routes->get('(:num)/edit', 'Admin\Menu::edit/$1');
        $routes->post('(:num)/update', 'Admin\Menu::update/$1');
        $routes->post('(:num)/delete', 'Admin\Menu::delete/$1');
        $routes->post('(:num)/reorder', 'Admin\Menu::reorder/$1');
        $routes->get('(:num)/items/create', 'Admin\Menu::createItem/$1');
        $routes->post('(:num)/items/store', 'Admin\Menu::storeItem/$1');
        $routes->get('(:num)/items/(:num)/edit', 'Admin\Menu::editItem/$1/$2');
        $routes->post('(:num)/items/(:num)/update', 'Admin\Menu::updateItem/$1/$2');
        $routes->post('(:num)/items/(:num)/delete', 'Admin\Menu::deleteItem/$1/$2');
    });

    $routes->group('comments', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Comments::index');
        $routes->post('moderate', 'Admin\Comments::moderate');
        $routes->post('(:num)/status', 'Admin\Comments::status/$1');
        $routes->post('(:num)/reply', 'Admin\Comments::reply/$1');
        $routes->post('(:num)/delete', 'Admin\Comments::delete/$1');
    });

    $routes->group('banners', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Banners::index');
        $routes->get('create', 'Admin\Banners::create');
        $routes->post('store', 'Admin\Banners::store');
        $routes->get('(:num)/edit', 'Admin\Banners::edit/$1');
        $routes->post('(:num)/update', 'Admin\Banners::update/$1');
        $routes->post('(:num)/delete', 'Admin\Banners::delete/$1');
        $routes->post('(:num)/toggle', 'Admin\Banners::toggle/$1');
    });

    $routes->group('popups', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Popups::index');
        $routes->get('create', 'Admin\Popups::create');
        $routes->post('store', 'Admin\Popups::store');
        $routes->get('(:num)/edit', 'Admin\Popups::edit/$1');
        $routes->post('(:num)/update', 'Admin\Popups::update/$1');
        $routes->post('(:num)/delete', 'Admin\Popups::delete/$1');
        $routes->post('(:num)/toggle', 'Admin\Popups::toggle/$1');
    });

    $routes->group('forms', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Forms::index');
        $routes->get('create', 'Admin\Forms::create');
        $routes->post('store', 'Admin\Forms::store');
        $routes->get('(:num)/edit', 'Admin\Forms::edit/$1');
        $routes->post('(:num)/update', 'Admin\Forms::update/$1');
        $routes->post('(:num)/delete', 'Admin\Forms::delete/$1');
        $routes->post('(:num)/toggle', 'Admin\Forms::toggle/$1');
    });

    $routes->group('inbox', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Inbox::index');
        $routes->post('bulk', 'Admin\Inbox::bulk');
        $routes->get('(:num)', 'Admin\Inbox::show/$1');
        $routes->post('(:num)/status', 'Admin\Inbox::status/$1');
        $routes->post('(:num)/delete', 'Admin\Inbox::delete/$1');
    });

    $routes->group('orders', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Orders::index');
        $routes->get('export', 'Admin\Orders::export');
        $routes->get('(:num)', 'Admin\Orders::show/$1');
        $routes->post('(:num)/status', 'Admin\Orders::status/$1');
        $routes->post('(:num)/delete', 'Admin\Orders::delete/$1');
    });

    $routes->group('newsletter', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Newsletter::index');
        $routes->get('export', 'Admin\Newsletter::export');
        $routes->post('store', 'Admin\Newsletter::store');
        $routes->post('(:num)/status', 'Admin\Newsletter::status/$1');
        $routes->post('(:num)/delete', 'Admin\Newsletter::delete/$1');
    });

    $routes->group('users', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Users::index');
        $routes->get('create', 'Admin\Users::create');
        $routes->post('store', 'Admin\Users::store');
        $routes->get('(:num)/edit', 'Admin\Users::edit/$1');
        $routes->post('(:num)/update', 'Admin\Users::update/$1');
        $routes->post('(:num)/toggle', 'Admin\Users::toggle/$1');
        $routes->post('(:num)/delete', 'Admin\Users::delete/$1');
    });

    $routes->group('roles', static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Roles::index');
        $routes->post('create', 'Admin\Roles::create');
        $routes->get('(:num)/edit', 'Admin\Roles::edit/$1');
        $routes->post('(:num)/update', 'Admin\Roles::update/$1');
        $routes->post('(:num)/delete', 'Admin\Roles::delete/$1');
    });

    $routes->group('settings', ['filter' => 'permission:settings.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Settings::index');
        $routes->post('update/(:segment)', 'Admin\Settings::update/$1');
        $routes->post('test-mail', 'Admin\Settings::testMail');
    });

    $routes->group('social-login', ['filter' => 'permission:settings.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\SocialLogin::index');
        $routes->post('(:segment)/save', 'Admin\SocialLogin::save/$1');
    });

    $routes->group('languages', ['filter' => 'permission:languages.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Languages::index');
        $routes->get('create', 'Admin\Languages::create');
        $routes->post('store', 'Admin\Languages::store');
        $routes->get('(:num)/edit', 'Admin\Languages::edit/$1');
        $routes->post('(:num)/update', 'Admin\Languages::update/$1');
        $routes->post('(:num)/delete', 'Admin\Languages::delete/$1');
        $routes->post('(:num)/toggle', 'Admin\Languages::toggle/$1');
        $routes->post('(:num)/default', 'Admin\Languages::makeDefault/$1');
        $routes->post('(:num)/seed', 'Admin\Languages::seedKeys/$1');
        $routes->get('(:num)/translations', 'Admin\Languages::translations/$1');
        $routes->post('(:num)/translations', 'Admin\Languages::saveTranslations/$1');
        $routes->post('(:num)/translations/add', 'Admin\Languages::addKey/$1');
        $routes->post('(:num)/translations/(:num)/delete', 'Admin\Languages::deleteKey/$1/$2');
    });
    $routes->group('redirects', ['filter' => 'permission:seo.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Redirects::index');
        $routes->get('create', 'Admin\Redirects::create');
        $routes->post('store', 'Admin\Redirects::store');
        $routes->get('(:num)/edit', 'Admin\Redirects::edit/$1');
        $routes->post('(:num)/update', 'Admin\Redirects::update/$1');
        $routes->post('(:num)/toggle', 'Admin\Redirects::toggle/$1');
        $routes->post('(:num)/delete', 'Admin\Redirects::delete/$1');
    });

    $routes->group('seo', ['filter' => 'permission:seo.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Seo::index');
        $routes->post('rebuild', 'Admin\Seo::rebuild');
    });

    $routes->group('themes', ['filter' => 'permission:appearance.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Themes::index');
        $routes->post('activate/(:segment)', 'Admin\Themes::activate/$1');
        $routes->post('preview/stop', 'Admin\Themes::stopPreview');
        $routes->post('preview/(:segment)', 'Admin\Themes::preview/$1');
    });

    $routes->group('plugins', ['filter' => 'permission:plugins.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Plugins::index');
        $routes->post('install', 'Admin\Plugins::install');
        $routes->get('(:segment)/settings', 'Admin\Plugins::settings/$1');
        $routes->post('(:segment)/settings', 'Admin\Plugins::saveSettings/$1');
        $routes->post('(:segment)/toggle', 'Admin\Plugins::toggle/$1');
        $routes->post('(:segment)/delete', 'Admin\Plugins::delete/$1');
    });

    $routes->group('tools', ['filter' => 'permission:tools.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Tools::index');
        $routes->post('cache/clear', 'Admin\Tools::clearCache');
        $routes->post('maintenance', 'Admin\Tools::maintenance');
        $routes->post('audit/prune', 'Admin\Tools::pruneAudit');
        $routes->get('audit/export', 'Admin\Tools::exportAudit');
    });

    $routes->group('calendar', ['filter' => 'permission:content.view'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Calendar::index');
        $routes->get('events', 'Admin\Calendar::events');
    });

    $routes->group('reports', ['filter' => 'permission:reports.view'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\Reports::index');
        $routes->get('export', 'Admin\Reports::export');
    });

    $routes->group('api-tokens', ['filter' => 'permission:api.manage'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Admin\ApiTokens::index');
        $routes->post('/', 'Admin\ApiTokens::store');
        $routes->post('(:num)/revoke', 'Admin\ApiTokens::revoke/$1');
        $routes->post('(:num)/delete', 'Admin\ApiTokens::delete/$1');
    });
});
$routes->get('theme-assets/(:segment)/(.*)', 'ThemeAsset::show/$1/$2');
$routes->get('plugin-assets/(:segment)/(.*)', 'PluginAsset::show/$1/$2');
$routes->get('media/(:num)/(:segment)', 'MediaServe::show/$1/$2');

/*
|--------------------------------------------------------------------------
| REST API v1
|--------------------------------------------------------------------------
| Tokens are issued in the admin (API tokens). CORS preflight is answered
| without auth; every other endpoint requires a bearer token with scopes.
*/
$routes->options('api/v1', 'Api\V1::preflight');
$routes->options('api/v1/(:any)', 'Api\V1::preflight');
$routes->options('api/v1/(:any)/(:any)', 'Api\V1::preflight');
$routes->options('api/v1/(:any)/(:any)/(:any)', 'Api\V1::preflight');

$routes->get('api/v1/openapi.json', 'Api\V1::openapi', ['filter' => 'apithrottle']);

$routes->group('api/v1', ['namespace' => 'App\Controllers\Api', 'filter' => ['apithrottle', 'apiauth']], static function (RouteCollection $routes): void {
    $routes->get('/', 'V1::index');

    $routes->get('content', 'ContentApi::index');
    $routes->post('content', 'ContentApi::store');
    $routes->get('content/(:segment)', 'ContentApi::show/$1');
    $routes->match(['put', 'patch'], 'content/(:num)', 'ContentApi::update/$1');
    $routes->delete('content/(:num)', 'ContentApi::destroy/$1');

    $routes->get('posts', 'ContentApi::posts');
    $routes->get('pages', 'ContentApi::pages');
    $routes->get('docs', 'ContentApi::docs');

    $routes->get('categories', 'TaxonomyApi::categories');
    $routes->get('tags', 'TaxonomyApi::tags');
    $routes->get('sections', 'TaxonomyApi::sections');
    $routes->get('languages', 'TaxonomyApi::languages');

    $routes->get('media', 'MediaApi::index');
    $routes->post('media', 'MediaApi::store');

    $routes->get('comments', 'InsightsApi::comments');
    $routes->post('comments/(:num)/moderate', 'InsightsApi::moderate/$1');

    $routes->get('orders', 'InsightsApi::orders');
    $routes->get('subscribers', 'InsightsApi::subscribers');
    $routes->get('stats', 'InsightsApi::stats');
});

/*
|--------------------------------------------------------------------------
| Frontend
|--------------------------------------------------------------------------
| System endpoints first, then AJAX form handlers, then the locale aware
| catch-all dispatcher that resolves friendly content URLs.
*/
$routes->get('sitemap.xml', 'Frontend\Feeds::sitemap');
$routes->get('robots.txt', 'Frontend\Feeds::robots');
$routes->get('feed', 'Frontend\Feeds::feed');
$routes->get('feed/(:segment)', 'Frontend\Feeds::feed/$1');
$routes->get('rss.xml', 'Frontend\Feeds::feed');

$routes->post('newsletter', 'Frontend\Newsletter::subscribe');
$routes->get('newsletter/confirm', 'Frontend\Newsletter::confirm');
$routes->get('newsletter/confirm/(:segment)', 'Frontend\Newsletter::confirm/$1');
$routes->get('newsletter/unsubscribe', 'Frontend\Newsletter::unsubscribe');
$routes->get('newsletter/unsubscribe/(:segment)', 'Frontend\Newsletter::unsubscribe/$1');

$routes->post('comments', 'Frontend\Comments::store');
$routes->post('forms/(:num)', 'Frontend\Forms::submit/$1');
$routes->post('orders/(:num)', 'Frontend\Orders::store/$1');

$routes->get('ads/(:num)/click', 'Frontend\Ads::click/$1');

$routes->get('/', 'Frontend\Router::home');
$routes->get('(:any)', 'Frontend\Router::dispatch/$1');
