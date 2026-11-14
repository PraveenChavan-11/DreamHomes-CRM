<?php

namespace App\Controllers\Admin;

use App\Models\AdminUserModel;

class ProfileController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $userModel = new AdminUserModel();
        $user = $userModel->find($this->adminUser['id'] ?? 0);

        return $this->renderAdminView('admin/settings/profile', [
            'user'  => $user ?: $this->adminUser,
            'title' => 'My Profile & Security Settings — TalentHub Admin',
        ], 'profile');
    }

    public function update()
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $userModel = new AdminUserModel();
        $userId = $this->adminUser['id'] ?? 0;
        $user = $userModel->find($userId);

        if (!$user) {
            session()->setFlashdata('error', 'Administrator record not found.');
            return redirect()->to(base_url('admin/profile'));
        }

        $name = trim($this->request->getPost('name') ?? '');
        $email = trim($this->request->getPost('email') ?? '');
        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        $data = [
            'name'  => $name,
            'email' => $email,
        ];

        // Check password change if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword) || !password_verify($currentPassword, $user['password_hash'])) {
                session()->setFlashdata('error', 'Current password entered is incorrect.');
                return redirect()->back()->withInput();
            }

            if (strlen($newPassword) < 8) {
                session()->setFlashdata('error', 'New password must be at least 8 characters long.');
                return redirect()->back()->withInput();
            }

            if ($newPassword !== $confirmPassword) {
                session()->setFlashdata('error', 'New password and confirmation do not match.');
                return redirect()->back()->withInput();
            }

            $data['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        $userModel->update($userId, $data);

        // Update session
        $this->adminUser['name'] = $name;
        $this->adminUser['email'] = $email;
        session()->set('admin_user', $this->adminUser);

        session()->setFlashdata('success', 'Profile and security credentials updated successfully.');
        return redirect()->to(base_url('admin/profile'));
    }
}

