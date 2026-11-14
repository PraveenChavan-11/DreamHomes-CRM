<?php

namespace App\Controllers\Admin;

use App\Models\AdminUserModel;
use App\Models\AuditLogModel;
use App\Models\RoleModel;

class UsersController extends AdminBaseController
{
    private AdminUserModel $userModel;
    private RoleModel $roleModel;
    private AuditLogModel $auditModel;

    public function __construct()
    {
        $this->userModel  = new AdminUserModel();
        $this->roleModel  = new RoleModel();
        $this->auditModel = new AuditLogModel();
    }

    public function index()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $users = $this->userModel->getUsersWithRoles();

        return $this->renderAdminView('admin/users/index', [
            'users' => $users,
            'title' => 'Admin & Staff User Management — TalentHub Admin',
        ], 'users');
    }

    public function new()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $roles = $this->roleModel->orderBy('name', 'ASC')->findAll();

        return $this->renderAdminView('admin/users/form', [
            'user'  => null,
            'roles' => $roles,
            'title' => 'Add New Staff User — TalentHub Admin',
        ], 'users');
    }

    public function create()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $rules = [
            'username'   => 'required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[admin_users.username]',
            'email'      => 'required|valid_email|max_length[190]|is_unique[admin_users.email]',
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[1]|max_length[100]',
            'password'   => 'required|min_length[8]',
            'roles'      => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $password = (string) $this->request->getPost('password');
        $roleIds = $this->request->getPost('roles') ?: [];

        $data = [
            'username'      => trim((string) $this->request->getPost('username')),
            'email'         => trim(strtolower((string) $this->request->getPost('email'))),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'first_name'    => trim((string) $this->request->getPost('first_name')),
            'last_name'     => trim((string) $this->request->getPost('last_name')),
            'phone'         => trim((string) $this->request->getPost('phone')),
            'is_active'         => $this->request->getPost('is_active') ? 1 : 0,
            'email_otp_enabled' => $this->request->getPost('email_otp_enabled') ? 1 : 0,
        ];

        try {
            $userId = (int) $this->userModel->insert($data);
            $this->userModel->assignRoles($userId, $roleIds);

            $this->auditModel->log('USER_CREATE', 'admin_users', $userId, null, [
                'username' => $data['username'],
                'email'    => $data['email'],
                'roles'    => $roleIds,
            ]);

            session()->setFlashdata('success', "Staff user '{$data['username']}' created successfully.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to create user: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/users'));
    }

    public function edit(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $user = $this->userModel->getUserWithRoles($id);
        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            return redirect()->to(base_url('admin/users'));
        }

        $roles = $this->roleModel->orderBy('name', 'ASC')->findAll();

        return $this->renderAdminView('admin/users/form', [
            'user'  => $user,
            'roles' => $roles,
            'title' => "Edit User: {$user['username']} — TalentHub Admin",
        ], 'users');
    }

    public function update(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            return redirect()->to(base_url('admin/users'));
        }

        $rules = [
            'username'   => "required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[admin_users.username,id,{$id}]",
            'email'      => "required|valid_email|max_length[190]|is_unique[admin_users.email,id,{$id}]",
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name'  => 'required|min_length[1]|max_length[100]',
            'roles'      => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $data = [
            'username'   => trim((string) $this->request->getPost('username')),
            'email'      => trim(strtolower((string) $this->request->getPost('email'))),
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'last_name'  => trim((string) $this->request->getPost('last_name')),
            'phone'      => trim((string) $this->request->getPost('phone')),
            'is_active'         => $this->request->getPost('is_active') ? 1 : 0,
            'email_otp_enabled' => $this->request->getPost('email_otp_enabled') ? 1 : 0,
        ];

        $password = (string) $this->request->getPost('password');
        if (!empty($password)) {
            if (strlen($password) < 8) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
            }
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $roleIds = $this->request->getPost('roles') ?: [];

        try {
            $this->userModel->update($id, $data);
            $this->userModel->assignRoles($id, $roleIds);

            $this->auditModel->log('USER_UPDATE', 'admin_users', $id, $user, [
                'username' => $data['username'],
                'email'    => $data['email'],
                'roles'    => $roleIds,
            ]);

            session()->setFlashdata('success', "Staff user '{$data['username']}' updated successfully.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to update user: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/users'));
    }

    public function toggleStatus(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        // Cannot deactivate self
        if (!empty($this->adminUser['id']) && (int) $this->adminUser['id'] === $id) {
            session()->setFlashdata('error', 'You cannot deactivate your own account.');
            return redirect()->to(base_url('admin/users'));
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            return redirect()->to(base_url('admin/users'));
        }

        $newActive = $user['is_active'] ? 0 : 1;
        $this->userModel->update($id, ['is_active' => $newActive]);

        $statusStr = $newActive ? 'activated' : 'deactivated';
        session()->setFlashdata('success', "User '{$user['username']}' has been {$statusStr}.");

        return redirect()->to(base_url('admin/users'));
    }

    public function delete(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        if (!empty($this->adminUser['id']) && (int) $this->adminUser['id'] === $id) {
            session()->setFlashdata('error', 'You cannot delete your own account.');
            return redirect()->to(base_url('admin/users'));
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            return redirect()->to(base_url('admin/users'));
        }

        $db = \Config\Database::connect();
        $db->table('user_roles')->where('user_id', $id)->delete();
        $this->userModel->delete($id, true);

        $this->auditModel->log('USER_DELETE', 'admin_users', $id, $user, null);
        session()->setFlashdata('success', "User '{$user['username']}' deleted successfully.");

        return redirect()->to(base_url('admin/users'));
    }
}

