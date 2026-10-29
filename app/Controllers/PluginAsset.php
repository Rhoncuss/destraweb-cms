<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Destra;

/**
 * Serves whitelisted static files from /plugins/{slug}/assets (falling back to
 * the plugin root). PHP files are never executed or returned.
 */
class PluginAsset extends BaseController
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
    ];

    public function show(string $slug, string $path = '')
    {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
        $path = $this->relativePath($slug);
        if ($slug === '' || $path === '' || str_contains($path, '\\') || str_contains($path, "\0")) {
            throw PageNotFoundException::forPageNotFound();
        }

        $root = config(Destra::class)->pluginsPath . DIRECTORY_SEPARATOR . $slug;
        $base = $root . DIRECTORY_SEPARATOR . 'assets';
        if (! is_dir($base)) {
            $base = $root;
        }

        $baseReal = realpath($base);
        $file     = realpath($base . DIRECTORY_SEPARATOR . $path);

        if ($baseReal === false || $file === false || ! is_file($file)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $baseReal = str_replace('\\', '/', $baseReal);
        $file     = str_replace('\\', '/', $file);
        if (! str_starts_with($file, $baseReal . '/')) {
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

    private function relativePath(string $slug): string
    {
        $uri = trim(urldecode((string) $this->request->getPath()), '/');
        $needle = 'plugin-assets/' . $slug . '/';

        return str_starts_with($uri, $needle) ? trim(substr($uri, strlen($needle)), '/') : '';
    }
}
