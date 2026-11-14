<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'slug',
        'name',
        'description',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get all roles along with assigned user counts and permission counts
     */
    public function getRolesWithCounts(): array
    {
        $db = \Config\Database::connect();
        $roles = $this->orderBy('id', 'ASC')->findAll();

        foreach ($roles as &$role) {
            $userCount = $db->table('user_roles')
                ->where('role_id', $role['id'])
                ->countAllResults();

            $permCount = $db->table('role_permissions')
                ->where('role_id', $role['id'])
                ->countAllResults();

            $role['user_count']        = $userCount;
            $role['users_count']       = $userCount;
            $role['permission_count']  = $permCount;
            $role['permissions_count'] = $permCount;

            $permCodes = $db->table('role_permissions')
                ->select('permissions.code')
                ->join('permissions', 'permissions.id = role_permissions.permission_id')
                ->where('role_permissions.role_id', $role['id'])
                ->get()
                ->getResultArray();

            $role['permission_codes'] = array_column($permCodes, 'code');
        }

        return $roles;
    }

    /**
     * Get role with assigned permission IDs
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        $db = \Config\Database::connect();
        $assigned = $db->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        $permIds = array_map('intval', array_column($assigned, 'permission_id'));
        $role['permission_ids'] = $permIds;
        $role['permissions']    = $permIds;
        return $role;
    }

    /**
     * Synchronize permissions for a role
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();
        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        if (!empty($permissionIds)) {
            $rows = [];
            foreach ($permissionIds as $permId) {
                $pId = (int) $permId;
                if ($pId > 0) {
                    $rows[] = [
                        'role_id'       => $roleId,
                        'permission_id' => $pId,
                    ];
                }
            }

            if (!empty($rows)) {
                $db->table('role_permissions')->insertBatch($rows);
            }
        }

        return true;
    }
}
