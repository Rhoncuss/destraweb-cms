<?php

namespace App\Controllers\Admin;

use Throwable;

class Roles extends BaseAdminController
{
    public function index(): string
    {
        $db = db_connect();

        $roles = $db->table('roles')->orderBy('is_system', 'DESC')->orderBy('name', 'ASC')->get()->getResultArray();

        $userCounts = [];
        $permCounts = [];
        try {
            foreach ($db->table('users')->select('role_id, COUNT(*) AS total')->where('deleted_at', null)->groupBy('role_id')->get()->getResultArray() as $row) {
                $userCounts[(int) $row['role_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }
        try {
            foreach ($db->table('role_permissions')->select('role_id, COUNT(*) AS total')->groupBy('role_id')->get()->getResultArray() as $row) {
                $permCounts[(int) $row['role_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/roles/index', [
            'pageTitle'    => 'Roles & permissions',
            'pageSubtitle' => 'Decide exactly what each role can see and do in the admin.',
            'navActive'    => 'roles',
            'breadcrumbs'  => [['label' => 'Roles']],
            'roles'        => $roles,
            'userCounts'   => $userCounts,
            'permCounts'   => $permCounts,
            'permissionTotal' => (int) $db->table('permissions')->countAllResults(),
        ]);
    }

    public function create()
    {
        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $name = $san->text($post['name'] ?? '', 100);
        if ($name === '') {
            return redirect()->to(admin_url('roles'))->with('error', 'A role name is required.');
        }

        $db   = db_connect();
        $slug = $san->slug($post['slug'] ?? '' ?: $name, 60) ?: 'role';
        $base = $slug;
        $i    = 1;
        while ($db->table('roles')->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . (++$i);
        }

        $db->table('roles')->insert([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $san->text($post['description'] ?? null, 255) ?: null,
            'is_system'   => 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        $this->audit('role.create', 'Created role "' . $name . '"', 'system', 'role', $id);
        $this->flushCache();

        return redirect()->to(admin_url('roles/' . $id . '/edit'))->with('success', 'Role created. Choose its permissions now.');
    }

    public function edit(int $id): string
    {
        $role = $this->find($id);
        if ($role === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db      = db_connect();
        $perms   = $db->table('permissions')->orderBy('group_name', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
        $granted = [];
        try {
            foreach ($db->table('role_permissions')->where('role_id', $id)->get()->getResultArray() as $row) {
                $granted[] = (int) $row['permission_id'];
            }
        } catch (Throwable) {
        }

        $grouped = [];
        foreach ($perms as $perm) {
            $grouped[(string) $perm['group_name']][] = $perm;
        }

        return $this->render('admin/roles/form', [
            'pageTitle'    => 'Edit role',
            'pageSubtitle' => 'Toggle the capabilities for “' . $role['name'] . '”.',
            'navActive'    => 'roles',
            'breadcrumbs'  => [
                ['label' => 'Roles', 'url' => admin_url('roles')],
                ['label' => $role['name']],
            ],
            'role'      => $role,
            'grouped'   => $grouped,
            'granted'   => $granted,
            'userCount' => (int) $db->table('users')->where('role_id', $id)->where('deleted_at', null)->countAllResults(),
        ]);
    }

    public function update(int $id)
    {
        $role = $this->find($id);
        if ($role === null) {
            return redirect()->to(admin_url('roles'))->with('error', 'Role not found.');
        }

        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $db   = db_connect();

        $name = $san->text($post['name'] ?? '', 100);
        if ($name === '') {
            return redirect()->back()->with('error', 'A role name is required.');
        }

        $data = [
            'name'        => $name,
            'description' => $san->text($post['description'] ?? null, 255) ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if (empty($role['is_system'])) {
            $slug = $san->slug($post['slug'] ?? '' ?: $name, 60) ?: $role['slug'];
            $base = $slug;
            $i    = 1;
            while ($db->table('roles')->where('slug', $slug)->where('id !=', $id)->countAllResults() > 0) {
                $slug = $base . '-' . (++$i);
            }
            $data['slug'] = $slug;
        }

        $db->table('roles')->where('id', $id)->update($data);

        if ($role['slug'] !== 'super-admin') {
            $ids = array_values(array_filter(array_map('intval', (array) ($post['permissions'] ?? []))));
            $valid = array_column($db->table('permissions')->select('id')->whereIn('id', $ids ?: [0])->get()->getResultArray(), 'id');

            $db->table('role_permissions')->where('role_id', $id)->delete();
            foreach ($valid as $permissionId) {
                $db->table('role_permissions')->insert(['role_id' => $id, 'permission_id' => (int) $permissionId]);
            }
        }

        $this->audit('role.update', 'Updated role "' . $name . '"', 'system', 'role', $id);
        $this->flushCache();

        return redirect()->to(admin_url('roles'))->with('success', 'Role saved.');
    }

    public function delete(int $id)
    {
        $role = $this->find($id);
        if ($role === null) {
            return redirect()->to(admin_url('roles'))->with('error', 'Role not found.');
        }
        if (! empty($role['is_system'])) {
            return redirect()->to(admin_url('roles'))->with('error', 'Built-in roles cannot be deleted.');
        }

        $inUse = (int) db_connect()->table('users')->where('role_id', $id)->where('deleted_at', null)->countAllResults();
        if ($inUse > 0) {
            return redirect()->to(admin_url('roles'))->with('error', 'Move users to another role before deleting this one.');
        }

        $db = db_connect();
        $db->table('role_permissions')->where('role_id', $id)->delete();
        $db->table('roles')->where('id', $id)->delete();

        $this->audit('role.delete', 'Deleted role "' . $role['name'] . '"', 'system', 'role', $id);
        $this->flushCache();

        return redirect()->to(admin_url('roles'))->with('success', 'Role deleted.');
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('roles')->where('id', $id)->get()->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        return $row ?: null;
    }
}
