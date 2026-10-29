<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Destra;

/**
 * Serves whitelisted static files from /themes/{slug}. PHP files are never
 * executed or returned, and paths are resolved inside the theme directory.
 */
class ThemeAsset extends BaseController
{
    private const MIME = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'text/javascript; charset=utf-8',
        'map'   => 'application/json',
        'json'  => 'application/json',
        'txt'   => 'text/plain; charset=utf-8',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'avif'  => 'image/avif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'eot'   => 'application/vnd.ms-fontobject',
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
    ];

    public function show(string $slug, string $path = '')
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $path = $this->relativePath($slug, 'theme-assets');

        if ($slug === '' || $path === '' || str_contains($path, '\\') || str_contains($path, "\0")) {
            throw PageNotFoundException::forPageNotFound();
        }

        $base = realpath(config(Destra::class)->themesPath . DIRECTORY_SEPARATOR . $slug);
        $file = realpath(config(Destra::class)->themesPath . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . $path);

        if ($base === false || $file === false || ! is_file($file)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $base = str_replace('\\', '/', $base);
        $file = str_replace('\\', '/', $file);
        if (! str_starts_with($file, $base . '/')) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (! isset(self::MIME[$ext])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', self::MIME[$ext])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=604800')
            ->setBody((string) file_get_contents($file));
    }

    /**
     * Route placeholders stop at the first slash, so the asset path is rebuilt
     * from the request URI after the public route prefix.
     */
    private function relativePath(string $slug, string $prefix): string
    {
        $uri = trim(urldecode((string) $this->request->getPath()), '/');
        $needle = $prefix . '/' . $slug . '/';

        return str_starts_with($uri, $needle) ? trim(substr($uri, strlen($needle)), '/') : '';
    }
}
