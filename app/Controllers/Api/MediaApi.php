<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class MediaApi extends BaseApiController
{
    public function index(): ResponseInterface
    {
        if (($guard = $this->guard('media:read')) !== null) {
            return $guard;
        }

        $db     = db_connect();
        $query  = trim((string) $this->request->getGet('q'));
        $folder = (int) $this->request->getGet('folder');
        $kind   = (string) $this->request->getGet('kind');

        $builder = $db->table('media')->where('deleted_at', null);
        if ($query !== '') {
            $builder->like('original_name', $db->escapeLikeString(mb_substr($query, 0, 120)));
        }
        if ($folder > 0) {
            $builder->where('folder_id', $folder);
        }
        if ($kind === 'image') {
            $builder->where('mime_type LIKE', 'image/%');
        } elseif ($kind === 'document') {
            $builder->where('mime_type NOT LIKE', 'image/%');
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->orderBy('id', 'DESC')
            ->limit($this->perPage, ($this->page - 1) * $this->perPage)
            ->get()->getResultArray();

        $items = array_map(function (array $row): array {
            return [
                'id'            => (int) $row['id'],
                'filename'      => (string) $row['filename'],
                'original_name' => (string) $row['original_name'],
                'mime_type'     => (string) $row['mime_type'],
                'extension'     => (string) $row['extension'],
                'size'          => (int) $row['size'],
                'width'         => $row['width'] !== null ? (int) $row['width'] : null,
                'height'        => $row['height'] !== null ? (int) $row['height'] : null,
                'alt'           => $row['alt'] !== null ? (string) $row['alt'] : null,
                'caption'       => $row['caption'] !== null ? (string) $row['caption'] : null,
                'url'           => media_url((int) $row['id']),
                'thumb'         => media_url((int) $row['id'], 'thumb'),
                'created_at'    => $row['created_at'],
            ];
        }, $rows);

        return $this->ok($items, $this->pagination((int) $total));
    }

    public function store(): ResponseInterface
    {
        if (($guard = $this->guard('media:write')) !== null) {
            return $guard;
        }

        $files = $this->request->getFileMultiple('files');
        if ($files === null) {
            $single = $this->request->getFile('file');
            $files  = $single !== null ? [$single] : [];
        }

        if ($files === []) {
            return $this->fail('Attach a file using the "file" or "files" multipart field.', 422, 'validation_failed');
        }

        $folderId = (int) ($this->request->getPost('folder_id') ?? 0) ?: null;
        $media    = service('media');
        $saved    = [];
        $errors   = [];

        foreach ($files as $file) {
            if (! $file->isValid()) {
                $errors[] = $file->getClientName() . ': ' . $file->getErrorString();
                continue;
            }

            $result = $media->upload($file, \App\Libraries\ApiContext::userId(), $folderId);
            if (! ($result['ok'] ?? false)) {
                $errors[] = $file->getClientName() . ': ' . ($result['message'] ?? 'Upload failed.');
                continue;
            }

            $row = (array) ($result['media'] ?? []);
            $saved[] = [
                'id'            => (int) ($row['id'] ?? 0),
                'original_name' => (string) ($row['original_name'] ?? ''),
                'mime_type'     => (string) ($row['mime_type'] ?? ''),
                'url'           => $media->url($row),
                'thumb'         => $media->url($row, 'thumb'),
                'width'         => isset($row['width']) ? (int) $row['width'] : null,
                'height'        => isset($row['height']) ? (int) $row['height'] : null,
            ];
        }

        if ($saved === []) {
            return $this->fail(implode(' ', $errors) ?: 'Upload failed.', 422, 'validation_failed');
        }

        try {
            db_connect()->table('audit_logs')->insert([
                'user_id'     => \App\Libraries\ApiContext::userId(),
                'action'      => 'media.upload',
                'description' => 'REST API uploaded ' . count($saved) . ' file(s)',
                'module'      => 'media',
                'subject_type'=> 'media',
                'subject_id'  => (int) ($saved[0]['id'] ?? 0),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
        }

        return $this->created($saved, ['errors' => $errors]);
    }
}
