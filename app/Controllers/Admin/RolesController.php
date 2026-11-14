<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;

class RolesController extends AdminBaseController
{
    private RoleModel $roleModel;
    private PermissionModel $permissionModel;
    private AuditLogModel $auditModel;

    public function __construct()
    {
        $this->roleModel       = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->auditModel      = new AuditLogModel();
    }

    public function index()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $roles = $this->roleModel->getRolesWithCounts();

        return $this->renderAdminView('admin/roles/index', [
            'roles' => $roles,
            'title' => 'Roles & Granular Permissions — TalentHub Admin',
        ], 'roles');
    }

    public function new()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $groupedPermissions = $this->permissionModel->getAllGroupedByCategory();

        return $this->renderAdminView('admin/roles/form', [
            'role'               => null,
            'groupedPermissions' => $groupedPermissions,
            'title'              => 'Create New Role & Assign Permissions — TalentHub Admin',
        ], 'roles');
    }

    public function create()
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'slug' => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[roles.slug]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $slug = url_title(strtolower((string) $this->request->getPost('slug')), '_');
        $name = trim((string) $this->request->getPost('name'));
        $desc = trim((string) $this->request->getPost('description'));
        $permissionIds = $this->request->getPost('permissions') ?: [];

        $data = [
            'slug'        => $slug,
            'name'        => $name,
            'description' => $desc,
        ];

        try {
            $roleId = (int) $this->roleModel->insert($data);
            $this->roleModel->syncPermissions($roleId, $permissionIds);

            $this->auditModel->log('ROLE_CREATE', 'roles', $roleId, null, [
                'slug'        => $slug,
                'name'        => $name,
                'permissions' => $permissionIds,
            ]);

            session()->setFlashdata('success', "Role '{$name}' created with " . count($permissionIds) . " permissions.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to create role: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/roles'));
    }

    public function edit(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $role = $this->roleModel->getRoleWithPermissions($id);
        if (!$role) {
            session()->setFlashdata('error', 'Role not found.');
            return redirect()->to(base_url('admin/roles'));
        }

        $groupedPermissions = $this->permissionModel->getAllGroupedByCategory();

        return $this->renderAdminView('admin/roles/form', [
            'role'               => $role,
            'groupedPermissions' => $groupedPermissions,
            'title'              => "Edit Role: {$role['name']} — TalentHub Admin",
        ], 'roles');
    }

    public function update(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $role = $this->roleModel->find($id);
        if (!$role) {
            session()->setFlashdata('error', 'Role not found.');
            return redirect()->to(base_url('admin/roles'));
        }

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $name = trim((string) $this->request->getPost('name'));
        $desc = trim((string) $this->request->getPost('description'));
        $permissionIds = $this->request->getPost('permissions') ?: [];

        $data = [
            'name'        => $name,
            'description' => $desc,
        ];

        // Slug is immutable for super_admin
        if ($role['slug'] !== 'super_admin' && $this->request->getPost('slug')) {
            $data['slug'] = url_title(strtolower((string) $this->request->getPost('slug')), '_');
        }

        try {
            $this->roleModel->update($id, $data);
            $this->roleModel->syncPermissions($id, $permissionIds);

            $this->auditModel->log('ROLE_UPDATE', 'roles', $id, $role, [
                'name'        => $name,
                'permissions' => $permissionIds,
            ]);

            session()->setFlashdata('success', "Role '{$name}' updated successfully.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to update role: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/roles'));
    }

    public function delete(int $id)
    {
        if ($redirect = $this->requirePermission('users.manage')) {
            return $redirect;
        }

        $role = $this->roleModel->find($id);
        if (!$role) {
            session()->setFlashdata('error', 'Role not found.');
            return redirect()->to(base_url('admin/roles'));
        }

        if ($role['slug'] === 'super_admin') {
            session()->setFlashdata('error', 'The Super Administrator role is protected and cannot be deleted.');
            return redirect()->to(base_url('admin/roles'));
        }

        $db = \Config\Database::connect();
        $assignedUsers = $db->table('user_roles')->where('role_id', $id)->countAllResults();
        if ($assignedUsers > 0) {
            session()->setFlashdata('error', "Cannot delete role '{$role['name']}' because it is currently assigned to {$assignedUsers} staff member(s). Reassign them first.");
            return redirect()->to(base_url('admin/roles'));
        }

        $db->table('role_permissions')->where('role_id', $id)->delete();
        $this->roleModel->delete($id, true);

        $this->auditModel->log('ROLE_DELETE', 'roles', $id, $role, null);
        session()->setFlashdata('success', "Role '{$role['name']}' deleted successfully.");

        return redirect()->to(base_url('admin/roles'));
    }
}

