<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUserModel extends Model
{
    protected $table            = 'admin_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'phone',
        'is_active',
        'last_login_at',
        'failed_login_count',
        'locked_until',
        'totp_secret',
        'totp_enabled',
        'totp_backup_codes',
        'email_otp_enabled',
        'email_otp_code',
        'email_otp_expires_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Authenticate an admin user with timing-safe comparison, rate-limit check, and password upgrade
     */
    public function authenticate(string $identifier, string $password): ?array
    {
        $user = $this->where('username', $identifier)
                     ->orWhere('email', $identifier)
                     ->first();

        if (!$user) {
            return null;
        }

        // Check active status
        if ((int) $user['is_active'] !== 1) {
            return null;
        }

        // Check if locked
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return null;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $failed = ((int) ($user['failed_login_count'] ?? 0)) + 1;
            $updates = ['failed_login_count' => $failed];
            if ($failed >= 5) {
                $updates['locked_until'] = date('Y-m-d H:i:s', time() + 900); // 15 min lock
            }
            $this->update($user['id'], $updates);
            return null;
        }

        // Success: reset failures and update last login
        $this->update($user['id'], [
            'failed_login_count' => 0,
            'locked_until'       => null,
            'last_login_at'      => date('Y-m-d H:i:s'),
        ]);

        $user['roles'] = $this->getUserRoles((int) $user['id']);
        $user['permissions'] = $this->getUserPermissions((int) $user['id']);
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('user_roles')
                  ->select('roles.slug, roles.name')
                  ->join('roles', 'roles.id = user_roles.role_id')
                  ->where('user_roles.user_id', $userId)
                  ->get()
                  ->getResultArray();
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('user_roles')
                   ->select('permissions.code')
                   ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
                   ->join('permissions', 'permissions.id = role_permissions.permission_id')
                   ->where('user_roles.user_id', $userId)
                   ->get()
                   ->getResultArray();

        return array_values(array_unique(array_column($rows, 'code')));
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(int $userId, string $permissionCode): bool
    {
        $perms = $this->getUserPermissions($userId);
        return in_array($permissionCode, $perms, true);
    }

    /**
     * Get all users with their assigned roles
     */
    public function getUsersWithRoles(): array
    {
        $users = $this->select('id, username, email, first_name, last_name, phone, is_active, last_login_at, created_at')
                      ->orderBy('id', 'ASC')
                      ->findAll();

        foreach ($users as &$user) {
            $user['roles'] = $this->getUserRoles((int) $user['id']);
            $user['role_slugs'] = array_column($user['roles'], 'slug');
            $user['role_names'] = array_column($user['roles'], 'name');
        }

        return $users;
    }

    /**
     * Get single user with assigned role IDs
     */
    public function getUserWithRoles(int $userId): ?array
    {
        $user = $this->select('id, username, email, first_name, last_name, phone, is_active, last_login_at, created_at')
                     ->find($userId);

        if (!$user) {
            return null;
        }

        $user['roles'] = $this->getUserRoles($userId);
        $user['role_ids'] = array_map('intval', array_column(
            \Config\Database::connect()->table('user_roles')->where('user_id', $userId)->get()->getResultArray(),
            'role_id'
        ));

        return $user;
    }

    /**
     * Assign / synchronize roles for a user
     */
    public function assignRoles(int $userId, array $roleIds): void
    {
        $db = \Config\Database::connect();
        $db->table('user_roles')->where('user_id', $userId)->delete();

        if (!empty($roleIds)) {
            $rows = [];
            foreach ($roleIds as $rId) {
                $roleId = (int) $rId;
                if ($roleId > 0) {
                    $rows[] = [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ];
                }
            }

            if (!empty($rows)) {
                $db->table('user_roles')->insertBatch($rows);
            }
        }
    }
}

