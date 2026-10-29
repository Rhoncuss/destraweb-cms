<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Streams library files from writable/uploads without exposing the folder.
 */
class MediaServe extends BaseController
{
    public function show(int $id, string $filename = ''): ResponseInterface
    {
        $media = service('media')->find($id);
        if ($media === null || ! empty($media['deleted_at'])) {
            return $this->notFound();
        }

        if ($filename !== '' && ! hash_equals((string) $media['filename'], $filename)) {
            return $this->notFound();
        }

        $variant = (string) ($this->request->getGet('v') ?? '');
        $variants = $media['variants_array'] ?? (json_decode((string) ($media['variants'] ?? ''), true) ?: []);
        if ($variant !== '' && ! isset($variants[$variant])) {
            $variant = '';
        }

        $path = service('media')->path($media, $variant);
        if ($path === null) {
            return $this->notFound();
        }

        $mime = $variant !== ''
            ? 'image/webp'
            : ((string) $media['mime_type'] ?: 'application/octet-stream');
        if (! preg_match('~^[a-z0-9.+-]+/[a-z0-9.+-]+$~i', $mime)) {
            $mime = 'application/octet-stream';
        }

        $etag = '"' . substr(hash_file('sha256', $path) ?: (string) $media['hash'], 0, 32) . '"';
        if (trim((string) $this->request->getHeaderLine('If-None-Match')) === $etag) {
            return $this->response->setStatusCode(304)->setHeader('ETag', $etag);
        }

        $isImage = str_starts_with($mime, 'image/');
        $disposition = $isImage ? 'inline' : 'attachment';
        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $media['original_name']) ?: 'file';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $safeName . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setHeader('ETag', $etag)
            ->setBody((string) file_get_contents($path));
    }

    private function notFound(): ResponseInterface
    {
        return $this->response->setStatusCode(404)->setBody('Not found.');
    }
}
