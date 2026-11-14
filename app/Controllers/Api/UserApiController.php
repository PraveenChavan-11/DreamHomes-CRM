<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;
use App\Models\AuditLogModel;
use CodeIgniter\HTTP\ResponseInterface;

class UserApiController extends BaseController
{
    private AdminUserModel $userModel;

    public function __construct()
    {
        $this->userModel = new AdminUserModel();
    }

    public function profile(): ResponseInterface
    {
        $sessionUser = session()->get('admin_user');
        $user = $this->userModel->find($sessionUser['id'] ?? 1);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        unset($user['password_hash']);
        $user['roles'] = $this->userModel->getUserRoles((int) $user['id']);
        $user['permissions'] = $this->userModel->getUserPermissions((int) $user['id']);

        return $this->respondJson($user);
    }

    public function updateProfile(): ResponseInterface
    {
        $sessionUser = session()->get('admin_user');
        $userId = (int) ($sessionUser['id'] ?? 1);

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $updates = [];
        if (!empty($input['first_name'])) $updates['first_name'] = trim((string) $input['first_name']);
        if (!empty($input['last_name'])) $updates['last_name'] = trim((string) $input['last_name']);
        if (!empty($input['phone'])) $updates['phone'] = trim((string) $input['phone']);

        $this->userModel->update($userId, $updates);

        // Update session
        $updatedUser = $this->userModel->find($userId);
        unset($updatedUser['password_hash']);
        $updatedUser['roles'] = $this->userModel->getUserRoles($userId);
        $updatedUser['permissions'] = $this->userModel->getUserPermissions($userId);
        session()->set('admin_user', $updatedUser);

        return $this->respondJson($updatedUser, 'Profile updated successfully');
    }

    public function changePassword(): ResponseInterface
    {
        $sessionUser = session()->get('admin_user');
        $userId = (int) ($sessionUser['id'] ?? 1);

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $current = (string) ($input['current_password'] ?? '');
        $new = (string) ($input['new_password'] ?? '');

        $user = $this->userModel->find($userId);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return $this->respondError('Current password verification failed', 400);
        }

        if (strlen($new) < 8) {
            return $this->respondError('New password must be at least 8 characters long', 422);
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->userModel->update($userId, ['password_hash' => $hash]);

        $audit = new AuditLogModel();
        $audit->log('PASSWORD_CHANGED', 'admin_users', $userId, null, null, $userId);

        return $this->respondJson([], 'Password changed successfully');
    }

    public function index(): ResponseInterface
    {
        $users = $this->userModel->findAll();
        foreach ($users as &$u) {
            unset($u['password_hash']);
            $u['roles'] = $this->userModel->getUserRoles((int) $u['id']);
        }
        return $this->respondJson($users);
    }

    public function roles(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $roles = $db->table('roles')->get()->getResultArray();
        return $this->respondJson($roles);
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'username'   => 'required|min_length[3]|is_unique[admin_users.username]',
            'email'      => 'required|valid_email|is_unique[admin_users.email]',
            'password'   => 'required|min_length[8]',
            'first_name' => 'required',
            'role_id'    => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validation failed', 422, 'VALIDATION_FAILED', $this->validator->getErrors());
        }

        $hash = password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT, ['cost' => 12]);
        $userId = $this->userModel->insert([
            'username'      => trim((string) $this->request->getPost('username')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => $hash,
            'first_name'    => trim((string) $this->request->getPost('first_name')),
            'last_name'     => trim((string) $this->request->getPost('last_name') ?? ''),
            'phone'         => trim((string) $this->request->getPost('phone') ?? ''),
            'is_active'     => 1,
        ]);

        $db = \Config\Database::connect();
        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => (int) $this->request->getPost('role_id'),
        ]);

        return $this->respondJson(['id' => $userId], 'Staff user created successfully', 201);
    }

    public function update($id): ResponseInterface
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('User not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $updates = [];
        if (isset($input['first_name'])) $updates['first_name'] = $input['first_name'];
        if (isset($input['last_name'])) $updates['last_name'] = $input['last_name'];
        if (isset($input['phone'])) $updates['phone'] = $input['phone'];
        if (isset($input['is_active'])) $updates['is_active'] = (int) $input['is_active'];

        $this->userModel->update($id, $updates);

        if (!empty($input['role_id'])) {
            $db = \Config\Database::connect();
            $db->table('user_roles')->where('user_id', $id)->delete();
            $db->table('user_roles')->insert([
                'user_id' => $id,
                'role_id' => (int) $input['role_id'],
            ]);
        }

        return $this->respondJson([], 'Staff user updated successfully');
    }
}

