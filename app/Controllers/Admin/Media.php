<?php

namespace App\Controllers\Admin;

use Throwable;

class Media extends BaseAdminController
{
    public function index(): string
    {
        $q      = trim((string) ($this->request->getGet('q') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $folder = (int) ($this->request->getGet('folder') ?? 0);
        $type   = (string) ($this->request->getGet('kind') ?? '');
        $perPage = 24;

        $builder = db_connect()->table('media')->where('deleted_at', null);
        if ($q !== '') {
            $builder->like('original_name', $q);
        }
        if ($folder > 0) {
            $builder->where('folder_id', $folder);
        }
        if ($type === 'image') {
            $builder->where('mime_type LIKE', 'image/%');
        } elseif ($type === 'document') {
            $builder->where('mime_type NOT LIKE', 'image/%');
        }

        $total = (int) $builder->countAllResults(false);
        $items = $builder->orderBy('id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        foreach ($items as &$item) {
            $item['thumb_url'] = service('media')->url($item, 'thumb');
            $item['full_url']  = service('media')->url($item);
        }
        unset($item);

        $folders = [];
        try {
            $folders = db_connect()->table('media_folders')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
        }

        return $this->render('admin/media/index', [
            'pageTitle'    => 'Media library',
            'pageSubtitle' => 'Upload images and documents. Files are stored outside the web root and re-encoded for safety.',
            'navActive'    => 'media',
            'breadcrumbs'  => [['label' => 'Media']],
            'items'        => $items,
            'folders'      => $folders,
            'total'        => $total,
            'page'         => $page,
            'pages'        => max(1, (int) ceil($total / $perPage)),
            'q'            => $q,
            'folder'       => $folder,
            'type'         => $type,
        ]);
    }

    public function picker()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));

        $builder = db_connect()->table('media')->where('deleted_at', null)->where('mime_type LIKE', 'image/%');
        if ($q !== '') {
            $builder->like('original_name', $q);
        }

        $rows = $builder->orderBy('id', 'DESC')->limit(60)->get()->getResultArray();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id'     => (int) $row['id'],
                'name'   => (string) $row['original_name'],
                'url'    => service('media')->url($row),
                'thumb'  => service('media')->url($row, 'thumb'),
                'width'  => $row['width'] !== null ? (int) $row['width'] : null,
                'height' => $row['height'] !== null ? (int) $row['height'] : null,
                'alt'    => (string) ($row['alt'] ?? ''),
                'date'   => date('d M Y', strtotime((string) $row['created_at'])),
            ];
        }

        return $this->response->setJSON(['success' => true, 'items' => $items]);
    }

    public function upload()
    {
        $files = $this->request->getFileMultiple('files');
        if ($files === null) {
            $single = $this->request->getFile('file');
            $files  = $single !== null ? [$single] : [];
        }

        if ($files === []) {
            return $this->fail('No files were received.');
        }

        $folderId = (int) ($this->request->getPost('folder_id') ?? 0) ?: null;
        $results  = [];
        $errors   = [];
        $media    = service('media');

        foreach ($files as $file) {
            if (! $file->isValid()) {
                $errors[] = $file->getClientName() . ': ' . $file->getErrorString();
                continue;
            }

            $result = $media->upload($file, (int) $this->admin['id'], $folderId);
            if (! ($result['ok'] ?? false)) {
                $errors[] = $file->getClientName() . ': ' . ($result['message'] ?? 'Upload failed.');
                continue;
            }

            $row = (array) ($result['media'] ?? []);
            $mediaId = (int) ($row['id'] ?? 0);
            if ($mediaId <= 0) {
                $errors[] = $file->getClientName() . ': The media record could not be read.';
                continue;
            }

            $results[] = [
                'id'    => $mediaId,
                'name'  => (string) ($row['original_name'] ?? ''),
                'url'   => $media->url($row),
                'thumb' => $media->url($row, 'thumb'),
            ];
        }

        if ($results !== []) {
            $this->audit('media.upload', 'Uploaded ' . count($results) . ' file(s)', 'media');
            $this->flushCache();
        }

        if ($results === []) {
            return $this->fail(implode(' ', $errors) ?: 'Upload failed.');
        }

        return $this->ok(['items' => $results, 'errors' => $errors], count($results) . ' file(s) uploaded.');
    }

    public function update(int $id)
    {
        $row = service('media')->find($id);
        if ($row === null) {
            return $this->fail('Media not found.', 404);
        }

        $sanitizer = service('sanitizer');
        db_connect()->table('media')->where('id', $id)->update([
            'alt'     => $sanitizer->text($this->request->getPost('alt'), 255) ?: null,
            'caption' => $sanitizer->text($this->request->getPost('caption'), 500) ?: null,
        ]);

        $this->audit('media.update', 'Updated media #' . $id . ' metadata', 'media', 'media', $id);
        $this->flushCache();

        if ($this->wantsJson()) {
            return $this->ok([], 'Media details updated.');
        }

        return redirect()->to(admin_url('media'))->with('success', 'Media details updated.');
    }

    public function delete(int $id)
    {
        service('media')->softDelete($id);
        $this->audit('media.delete', 'Moved media #' . $id . ' to trash', 'media', 'media', $id);
        $this->flushCache();

        if ($this->wantsJson()) {
            return $this->ok([], 'Media deleted.');
        }

        return redirect()->to(admin_url('media'))->with('success', 'Media deleted.');
    }

    public function folders()
    {
        $name = service('sanitizer')->text($this->request->getPost('name'), 120);
        if ($name === '') {
            return redirect()->to(admin_url('media'))->with('error', 'Folder name is required.');
        }

        db_connect()->table('media_folders')->insert([
            'name'       => $name,
            'slug'       => service('sanitizer')->slug($name, 120),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(admin_url('media'))->with('success', 'Folder created.');
    }
}
