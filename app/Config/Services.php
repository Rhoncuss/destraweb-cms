<?php

namespace Config;

use App\Libraries\AiService;
use App\Libraries\Audit;
use App\Libraries\Auth;
use App\Libraries\CacheManager;
use App\Libraries\Captcha;
use App\Libraries\ContentService;
use App\Libraries\Crypto;
use App\Libraries\Localization;
use App\Libraries\MailService;
use App\Libraries\MediaService;
use App\Libraries\NewsletterService;
use App\Libraries\OAuthService;
use App\Libraries\PluginManager;
use App\Libraries\Sanitizer;
use App\Libraries\SeoService;
use App\Libraries\Settings;
use App\Libraries\Shortcodes;
use App\Libraries\Stats;
use App\Libraries\ThemeManager;
use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function settings(bool $getShared = true): Settings
    {
        return $getShared ? static::getSharedInstance('settings') : new Settings();
    }

    public static function crypto(bool $getShared = true): Crypto
    {
        return $getShared ? static::getSharedInstance('crypto') : new Crypto();
    }

    public static function sanitizer(bool $getShared = true): Sanitizer
    {
        return $getShared ? static::getSharedInstance('sanitizer') : new Sanitizer();
    }

    public static function auth(bool $getShared = true): Auth
    {
        return $getShared ? static::getSharedInstance('auth') : new Auth();
    }

    public static function audit(bool $getShared = true): Audit
    {
        return $getShared ? static::getSharedInstance('audit') : new Audit();
    }

    public static function localization(bool $getShared = true): Localization
    {
        return $getShared ? static::getSharedInstance('localization') : new Localization();
    }

    public static function themeManager(bool $getShared = true): ThemeManager
    {
        return $getShared ? static::getSharedInstance('themeManager') : new ThemeManager();
    }

    public static function pluginManager(bool $getShared = true): PluginManager
    {
        return $getShared ? static::getSharedInstance('pluginManager') : new PluginManager();
    }

    public static function cache_manager(bool $getShared = true): CacheManager
    {
        return $getShared ? static::getSharedInstance('cache_manager') : new CacheManager();
    }

    public static function media(bool $getShared = true): MediaService
    {
        return $getShared ? static::getSharedInstance('media') : new MediaService();
    }

    public static function seo(bool $getShared = true): SeoService
    {
        return $getShared ? static::getSharedInstance('seo') : new SeoService();
    }

    public static function stats(bool $getShared = true): Stats
    {
        return $getShared ? static::getSharedInstance('stats') : new Stats();
    }

    public static function mailer(bool $getShared = true): MailService
    {
        return $getShared ? static::getSharedInstance('mailer') : new MailService();
    }

    public static function captcha(bool $getShared = true): Captcha
    {
        return $getShared ? static::getSharedInstance('captcha') : new Captcha();
    }

    public static function newsletter(bool $getShared = true): NewsletterService
    {
        return $getShared ? static::getSharedInstance('newsletter') : new NewsletterService();
    }

    public static function oauth(bool $getShared = true): OAuthService
    {
        return $getShared ? static::getSharedInstance('oauth') : new OAuthService();
    }

    public static function shortcodes(bool $getShared = true): Shortcodes
    {
        return $getShared ? static::getSharedInstance('shortcodes') : new Shortcodes();
    }

    public static function ai(bool $getShared = true): AiService
    {
        return $getShared ? static::getSharedInstance('ai') : new AiService();
    }

    public static function contentService(bool $getShared = true): ContentService
    {
        return $getShared ? static::getSharedInstance('contentService') : new ContentService();
    }
}
