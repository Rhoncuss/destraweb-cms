<?php

namespace App\Controllers\Admin;

use App\Libraries\Auth;
use Throwable;

class Users extends BaseAdminController
{
    public function index(): string
    {
        $db     = db_connect();
        $query  = trim((string) ($this->request->getGet('q') ?? ''));
        $roleId = (int) ($this->request->getGet('role') ?? 0);
        $status = (string) ($this->request->getGet('status') ?? '');
        $status = in_array($status, ['active', 'suspended'], true) ? $status : '';

        try {
            $roles = $db->table('roles')->orderBy('name', 'ASC')->get()->getResultArray();
        } catch (Throwable) {
            $roles = [];
        }

        $builder = $db->table('users u')
            ->select('u.id, u.name, u.username, u.email, u.avatar, u.status, u.last_login_at, u.created_at, r.name AS role_name, r.slug AS role_slug')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('u.deleted_at', null);

        if ($query !== '') {
            $builder->groupStart()->like('u.name', $query)->orLike('u.email', $query)->orLike('u.username', $query)->groupEnd();
        }
        if ($roleId > 0) {
            $builder->where('u.role_id', $roleId);
        }
        if ($status !== '') {
            $builder->where('u.status', $status);
        }

        $items = $builder->orderBy('u.name', 'ASC')->get()->getResultArray();

        $counts = [];
        try {
            foreach ($db->table('contents')->select('author_id, COUNT(*) AS total')->where('deleted_at', null)->groupBy('author_id')->get()->getResultArray() as $row) {
                $counts[(int) $row['author_id']] = (int) $row['total'];
            }
        } catch (Throwable) {
        }

        return $this->render('admin/users/index', [
            'pageTitle'    => 'Users',
            'pageSubtitle' => 'Your team, their roles and access to the admin.',
            'navActive'    => 'users',
            'breadcrumbs'  => [['label' => 'Users']],
            'items'        => $items,
            'roles'        => $roles,
            'query'        => $query,
            'roleId'       => $roleId,
            'status'       => $status,
            'counts'       => $counts,
            'currentId'    => (int) ($this->admin['id'] ?? 0),
        ]);
    }

    public function create(): string
    {
        return $this->form(null);
    }

    public function edit(int $id): string
    {
        $user = $this->find($id);
        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($user);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    public function toggle(int $id)
    {
        if ($id === (int) ($this->admin['id'] ?? 0)) {
            return $this->fail('You cannot suspend your own account.');
        }

        $user = $this->find($id);
        if ($user === null) {
            return $this->fail('User not found.', 404);
        }

        $status = $user['status'] === 'active' ? 'suspended' : 'active';
        db_connect()->table('users')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->audit('user.status', 'Set user #' . $id . ' to ' . $status, 'system', 'user', $id);

        return $this->ok(['status' => $status], 'User ' . ($status === 'active' ? 'reactivated' : 'suspended') . '.');
    }

    public function delete(int $id)
    {
        if ($id === (int) ($this->admin['id'] ?? 0)) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user = $this->find($id);
        if ($user === null) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $superAdmins = (int) db_connect()->table('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->where('r.slug', 'super-admin')
            ->where('u.deleted_at', null)
            ->where('u.id !=', $id)
            ->countAllResults();

        if ($user['role_slug'] === 'super-admin' && $superAdmins === 0) {
            return redirect()->back()->with('error', 'At least one super administrator must remain.');
        }

        db_connect()->table('users')->where('id', $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'status'     => 'suspended',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->audit('user.delete', 'Deleted user #' . $id, 'system', 'user', $id);

        return redirect()->to(admin_url('users'))->with('success', 'User removed.');
    }

    private function form(?array $user): string
    {
        return $this->render('admin/users/form', [
            'pageTitle'    => $user ? 'Edit user' : 'New user',
            'pageSubtitle' => $user ? 'Update the profile, role and access for this teammate.' : 'Invite a teammate and choose what they can do.',
            'navActive'    => 'users',
            'breadcrumbs'  => [
                ['label' => 'Users', 'url' => admin_url('users')],
                ['label' => $user ? 'Edit user' : 'New user'],
            ],
            'user'      => $user,
            'roles'     => db_connect()->table('roles')->orderBy('name', 'ASC')->get()->getResultArray(),
            'languages' => service('localization')->languages(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    private function save(?int $id)
    {
        $post = $this->request->getPost();
        $san  = service('sanitizer');
        $db   = db_connect();
        $now  = date('Y-m-d H:i:s');

        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && $existing === null) {
            return redirect()->to(admin_url('users'))->with('error', 'User not found.');
        }

        $name  = $san->text($post['name'] ?? '', 120);
        $email = filter_var(trim((string) ($post['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $username = $san->username($post['username'] ?? '' ?: $name);

        if ($name === '' || ! $email || $username === '') {
            return redirect()->back()->withInput()->with('error', 'A name, unique username and valid email are required.');
        }

        $roleId = (int) ($post['role_id'] ?? 0);
        if ($db->table('roles')->where('id', $roleId)->countAllResults() === 0) {
            return redirect()->back()->withInput()->with('error', 'Choose a valid role.');
        }

        $dupe = $db->table('users')->groupStart()->where('email', $email)->orWhere('username', $username)->groupEnd()
            ->where('id !=', $id ?? 0)->where('deleted_at', null)->get()->getRowArray();
        if ($dupe) {
            return redirect()->back()->withInput()->with('error', 'That email or username is already in use.');
        }

        $password = (string) ($post['password'] ?? '');
        if ($id === null && $password === '') {
            return redirect()->back()->withInput()->with('error', 'Set an initial password for the new user.');
        }
        if ($password !== '') {
            if ($password !== (string) ($post['password_confirm'] ?? '')) {
                return redirect()->back()->withInput()->with('error', 'The passwords do not match.');
            }
            if ($error = Auth::validatePasswordStrength($password)) {
                return redirect()->back()->withInput()->with('error', $error);
            }
        }

        $locale = (string) ($post['locale'] ?? 'en');
        if (! service('localization')->exists($locale)) {
            $locale = default_locale();
        }

        $timezone = (string) ($post['timezone'] ?? '');
        if ($timezone !== '' && ! in_array($timezone, timezone_identifiers_list(), true)) {
            $timezone = null;
        }

        $row = [
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'role_id'    => $roleId,
            'avatar'     => $san->url($post['avatar'] ?? '') ?: null,
            'locale'     => $locale,
            'timezone'   => $timezone ?: null,
            'bio'        => $san->textarea($post['bio'] ?? '', 2000) ?: null,
            'status'     => ($post['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active',
            'updated_at' => $now,
        ];

        if ($id === (int) ($this->admin['id'] ?? 0)) {
            $row['status'] = 'active';
            unset($row['role_id']);
        }

        if ($password !== '') {
            $row['password_hash'] = Auth::hashPassword($password);
            $row['remember_token'] = null;
        }

        if ($id === null) {
            $row['created_at'] = $now;
            $db->table('users')->insert($row);
            $id = (int) $db->insertID();
        } else {
            $db->table('users')->where('id', $id)->update($row);
        }

        $this->audit($existing ? 'user.update' : 'user.create', ($existing ? 'Updated' : 'Created') . ' user "' . $name . '"', 'system', 'user', $id);
        $this->flushCache();

        return redirect()->to(admin_url('users'))->with('success', 'User saved.');
    }

    private function find(int $id): ?array
    {
        try {
            $row = db_connect()->table('users u')
                ->select('u.*, r.slug AS role_slug, r.name AS role_name')
                ->join('roles r', 'r.id = u.role_id', 'left')
                ->where('u.id', $id)
                ->where('u.deleted_at', null)
                ->get()
                ->getRowArray();
        } catch (Throwable) {
            $row = null;
        }

        return $row ?: null;
    }
}
