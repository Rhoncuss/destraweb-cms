<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Destra;
use RuntimeException;
use Throwable;

/**
 * Secure media pipeline.
 *
 * Uploaded files are stored outside the document root (writable/uploads),
 * served only through a controller, renamed randomly and images are fully
 * re-encoded through GD which strips payloads and EXIF metadata.
 */
class MediaService
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const DOC_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'mp3', 'mp4', 'webm', 'wav', 'ogg'];

    private const MIME_MAP = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'txt'  => ['text/plain'],
        'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'mp3'  => ['audio/mpeg', 'audio/mp3'],
        'mp4'  => ['video/mp4'],
        'webm' => ['video/webm', 'audio/webm'],
        'wav'  => ['audio/wav', 'audio/x-wav'],
        'ogg'  => ['audio/ogg', 'video/ogg', 'application/ogg'],
    ];

    /** @var array<int, array<string, mixed>|null> */
    private array $cache = [];

    public function find(int $id): ?array
    {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        if ($id <= 0 || ! is_installed()) {
            return $this->cache[$id] = null;
        }

        try {
            $row = db_connect()->table('media')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        if ($row) {
            $row['variants_array'] = json_decode((string) ($row['variants'] ?? ''), true) ?: [];
        }

        return $this->cache[$id] = $row ?: null;
    }

    public function url(mixed $idOrMedia, string $variant = ''): string
    {
        if (is_numeric($idOrMedia)) {
            $media = $this->find((int) $idOrMedia);
        } elseif (is_array($idOrMedia)) {
            $media = $idOrMedia;
        } else {
            $media = null;
        }

        if (! $media) {
            return '';
        }

        $variants = $media['variants_array'] ?? (json_decode((string) ($media['variants'] ?? ''), true) ?: []);
        if ($variant !== '' && empty($variants[$variant])) {
            $variant = '';
        }

        return media_service_url((int) $media['id'], (string) $media['filename'], $variant);
    }

    public function alt(mixed $idOrMedia): string
    {
        $media = is_numeric($idOrMedia) ? $this->find((int) $idOrMedia) : (is_array($idOrMedia) ? $idOrMedia : null);

        return (string) ($media['alt'] ?? '');
    }

    public function path(array $media, string $variant = ''): ?string
    {
        $variants = $media['variants_array'] ?? (json_decode((string) ($media['variants'] ?? ''), true) ?: []);
        $relative = $variant !== '' && ! empty($variants[$variant]) ? (string) $variants[$variant] : (string) ($media['path'] ?? '');
        if ($relative === '') {
            return null;
        }

        $base = realpath(config(Destra::class)->uploadsPath);
        $path = realpath(rtrim(config(Destra::class)->uploadsPath, '/\\') . '/' . str_replace('\\', '/', $relative));
        if ($base === false || $path === false || ! str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $base) . '/')) {
            return null;
        }

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{ok: bool, message: string, media?: array<string, mixed>}
     */
    public function upload(UploadedFile $file, int $userId, ?int $folderId = null, ?int $maxMb = null): array
    {
        if (! $file->isValid() || $file->hasMoved()) {
            return ['ok' => false, 'message' => 'The upload failed: ' . $file->getErrorString()];
        }

        $maxMb ??= max(1, setting_int('security.max_upload_mb', config(Destra::class)->maxUploadMB));
        if ($file->getSize() > $maxMb * 1024 * 1024) {
            return ['ok' => false, 'message' => "Files may not exceed {$maxMb} MB."];
        }

        $original = service('sanitizer')->filename((string) $file->getClientName());
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (! in_array($ext, self::IMAGE_EXT, true) && ! in_array($ext, self::DOC_EXT, true)) {
            return ['ok' => false, 'message' => 'This file type is not allowed.'];
        }

        $tmp = $file->getTempName();
        if (! is_file($tmp)) {
            return ['ok' => false, 'message' => 'The uploaded file could not be read.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmp);
        $allowedMimes = self::MIME_MAP[$ext] ?? [];
        if (! in_array($mime, $allowedMimes, true) && ! ($ext === 'jpg' && $mime === 'image/jpg')) {
            return ['ok' => false, 'message' => 'The file content does not match its extension.'];
        }

        $isImage = in_array($ext, self::IMAGE_EXT, true);
        $base    = service('sanitizer')->slug(pathinfo($original, PATHINFO_FILENAME), 60);
        $random  = bin2hex(random_bytes(5));
        $subdir  = date('Y/m');
        $dir     = rtrim(config(Destra::class)->uploadsPath, '/\\') . '/' . $subdir;

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return ['ok' => false, 'message' => 'The upload directory is not writable.'];
        }

        $this->protectUploadRoot();

        $filename = $base . '-' . $random . ($isImage ? '.webp' : '.' . $ext);
        $relative = $subdir . '/' . $filename;
        $absolute = $dir . '/' . $filename;
        $variants = [];
        $width    = null;
        $height   = null;

        if ($isImage) {
            $result = $this->writeImageVariants($tmp, $dir, $base . '-' . $random);
            if (! $result['ok']) {
                return ['ok' => false, 'message' => $result['message']];
            }

            $filename = $result['filename'];
            $relative = $subdir . '/' . $filename;
            $variants = $result['variants'];
            $width    = $result['width'];
            $height   = $result['height'];
            $absolute = $dir . '/' . $filename;
            $mime     = 'image/webp';
            $ext      = 'webp';
        } else {
            if (! @move_uploaded_file($tmp, $absolute)) {
                return ['ok' => false, 'message' => 'Unable to store the uploaded file.'];
            }
            @chmod($absolute, 0644);
        }

        $hash = @hash_file('sha256', $absolute) ?: '';

        try {
            $db  = db_connect();
            $db->table('media')->insert([
                'user_id'       => $userId,
                'folder_id'     => $folderId,
                'filename'      => $filename,
                'original_name' => mb_substr($original, 0, 255),
                'mime_type'     => $mime,
                'extension'     => $ext,
                'size'          => (int) (@filesize($absolute) ?: $file->getSize()),
                'width'         => $width,
                'height'        => $height,
                'alt'           => service('sanitizer')->text(pathinfo($original, PATHINFO_FILENAME), 255),
                'caption'       => null,
                'hash'          => $hash,
                'path'          => $relative,
                'variants'      => json_encode($variants, JSON_UNESCAPED_SLASHES),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $id = (int) $db->insertID();
        } catch (Throwable $e) {
            @unlink($absolute);
            log_message('error', 'Media insert failed: ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Unable to save the media record.'];
        }

        service('cache_manager')->flush();

        return ['ok' => true, 'message' => 'File uploaded.', 'media' => $this->find($id) ?? []];
    }

    /**
     * Store binary image content (e.g. AI-generated images).
     */
    public function storeImageBinary(string $binary, string $suggestedName, int $userId): array
    {
        if (strlen($binary) > 25 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'Image is too large.'];
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false) {
            return ['ok' => false, 'message' => 'Generated content is not a valid image.'];
        }

        $subdir = date('Y/m');
        $dir    = rtrim(config(Destra::class)->uploadsPath, '/\\') . '/' . $subdir;
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return ['ok' => false, 'message' => 'The upload directory is not writable.'];
        }
        $this->protectUploadRoot();

        $base = service('sanitizer')->slug($suggestedName, 60);
        $result = $this->writeImageSource($binary, $dir, $base . '-' . bin2hex(random_bytes(5)));
        if (! $result['ok']) {
            return $result;
        }

        $absolute = $dir . '/' . $result['filename'];
        try {
            db_connect()->table('media')->insert([
                'user_id'       => $userId,
                'folder_id'     => null,
                'filename'      => $result['filename'],
                'original_name' => mb_substr($base . '.webp', 0, 255),
                'mime_type'     => 'image/webp',
                'extension'     => 'webp',
                'size'          => (int) (@filesize($absolute) ?: 0),
                'width'         => $result['width'],
                'height'        => $result['height'],
                'alt'           => $base,
                'hash'          => @hash_file('sha256', $absolute) ?: '',
                'path'          => $subdir . '/' . $result['filename'],
                'variants'      => json_encode($result['variants'], JSON_UNESCAPED_SLASHES),
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
            $id = (int) db_connect()->insertID();
        } catch (Throwable $e) {
            @unlink($absolute);

            return ['ok' => false, 'message' => 'Unable to save the generated image.'];
        }

        return ['ok' => true, 'message' => 'Image created.', 'media' => $this->find($id) ?? []];
    }

    public function softDelete(int $id): bool
    {
        try {
            db_connect()->table('media')->where('id', $id)->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function writeImageVariants(string $tmp, string $dir, string $base): array
    {
        $binary = @file_get_contents($tmp);

        return $binary !== false ? $this->writeImageSource($binary, $dir, $base) : ['ok' => false, 'message' => 'Unable to read the image.'];
    }

    private function writeImageSource(string $binary, string $dir, string $base): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return ['ok' => false, 'message' => 'GD is required to process images.'];
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return ['ok' => false, 'message' => 'The image could not be processed.'];
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW <= 0 || $srcH <= 0 || $srcW * $srcH > 80_000_000) {
            imagedestroy($source);

            return ['ok' => false, 'message' => 'Invalid or excessively large image dimensions.'];
        }

        $mainMax = 2400;
        $main = $this->resize($source, $srcW, $srcH, $mainMax);
        $mainName = $base . '.webp';
        $mainPath = $dir . '/' . $mainName;
        $this->saveWebp($main, $mainPath);
        $mainW = imagesx($main);
        $mainH = imagesy($main);

        $thumb = $this->resize($source, $srcW, $srcH, 640);
        $thumbName = $base . '-thumb.webp';
        $thumbPath = $dir . '/' . $thumbName;
        $this->saveWebp($thumb, $thumbPath);

        imagedestroy($source);
        imagedestroy($main);
        imagedestroy($thumb);

        if (! is_file($mainPath)) {
            return ['ok' => false, 'message' => 'Unable to write the processed image.'];
        }

        return [
            'ok'       => true,
            'message'  => 'ok',
            'filename' => $mainName,
            'width'    => $mainW,
            'height'   => $mainH,
            'variants' => ['thumb' => ltrim(str_replace(config(Destra::class)->uploadsPath, '', $dir . '/' . $thumbName), '/\\')],
        ];
    }

    /**
     * @return \GdImage
     */
    private function resize(\GdImage $source, int $srcW, int $srcH, int $max)
    {
        $ratio = min(1, $max / max($srcW, $srcH));
        $newW  = max(1, (int) round($srcW * $ratio));
        $newH  = max(1, (int) round($srcH * $ratio));

        $canvas = imagecreatetruecolor($newW, $newH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
        imagealphablending($canvas, true);
        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($source)) {
            imagepalettetotruecolor($source);
        }
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $canvas;
    }

    private function saveWebp(\GdImage $image, string $path): void
    {
        @imagewebp($image, $path, 82);
        @chmod($path, 0644);
    }

    private function protectUploadRoot(): void
    {
        $root = rtrim(config(Destra::class)->uploadsPath, '/\\');
        if (! is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        $htaccess = $root . '/.htaccess';
        if (! is_file($htaccess)) {
            @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
        }

        if (! is_file($root . '/index.html')) {
            @file_put_contents($root . '/index.html', '');
        }
    }
}
