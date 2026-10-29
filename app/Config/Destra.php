<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * DESTRA CMS application configuration.
 *
 * Values can be overridden through the .env file using the "destra." prefix.
 */
class Destra extends BaseConfig
{
    public string $productName = 'DESTRA CMS';
    public string $version     = '1.0.0';
    public string $codename    = 'Aurora';

    /**
     * Base64 encoded 32-byte key used for AES-256-GCM envelope encryption.
     * Generated automatically by the installer.
     */
    public string $encryptionKey = '';

    public int $maxInputDepth  = 8;
    public int $maxInputLength = 200000;
    public int $maxUploadMB    = 20;

    public array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    public array $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'mp3', 'mp4', 'webm', 'wav', 'ogg'];

    /**
     * Input keys that may contain trusted rich HTML. These are never
     * tag-stripped at the input layer; they are purified with HTMLPurifier
     * before they are persisted.
     */
    public array $richKeys = [
        'body', 'content', 'html', 'description_html', 'custom_css', 'custom_js',
        'head_code', 'footer_code', 'custom_head_code', 'custom_body_code',
    ];

    /**
     * Slugs that can never be used by content, sections or categories because
     * they shadow system routes.
     */
    public array $reservedSlugs = [
        'admin', 'api', 'login', 'logout', 'register', 'forgot-password', 'reset-password',
        'install', 'setup', 'assets', 'media', 'theme-assets', 'plugin-assets', 'uploads',
        'sitemap', 'sitemap.xml', 'robots.txt', 'feed', 'rss', 'search', 'contact',
        'category', 'tag', 'author', 'docs', 'p', 's', 'page', 'pages', 'preview',
        'auth', 'captcha', 'notifications', 'newsletter', 'unsubscribe', 'order',
        'maintenance', 'wp-admin', 'wp-login.php', 'xmlrpc.php', 'vendor', 'writable',
        'app', 'public', 'index.php', 'favicon.ico',
    ];

    public string $themesPath  = ROOTPATH . 'themes';
    public string $pluginsPath = ROOTPATH . 'plugins';
    public string $uploadsPath = WRITEPATH . 'uploads';

    public string $installLock = WRITEPATH . 'installed.lock';

    public string $adminPrefix = 'admin';

    public int $sitemapTtl = 21600;
    public int $settingsTtl = 3600;
    public int $menuTtl     = 3600;

    public bool $demo = false;
}
