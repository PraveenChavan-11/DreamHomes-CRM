<?php

namespace Crm\Services;

/** User + team administration (shared admin_users identity table). */
final class UserService
{
    /**
     * @param array{username:string,email:string,first_name:string,last_name:string,phone?:?string,role:string,password:string,team_id?:?int,max_discount_pct?:string,employee_code?:?string} $d
     */
    public static function create(array $d): int
    {
        if ($p = AuthService::passwordProblems($d['password'])) {
            throw new \InvalidArgumentException(implode(' ', $p));
        }

        return (int) Crm::tx(static function ($db) use ($d) {
            $role = $db->table('roles')->where('slug', $d['role'])->get()->getRowArray();
            if (! $role || ! isset(ReferenceData::ROLES[$d['role']])) {
                throw new \InvalidArgumentException('Unknown role.');
            }
            if ($db->table('admin_users')->groupStart()->where('email', $d['email'])->orWhere('username', $d['username'])->groupEnd()->countAllResults() > 0) {
                throw new \InvalidArgumentException('A user with that email or username already exists.');
            }
            $db->table('admin_users')->insert([
                'username' => $d['username'], 'email' => strtolower($d['email']), 'password_hash' => AuthService::hash($d['password']),
                'first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'phone' => $d['phone'] ?? null, 'is_active' => 1,
                'employee_code' => $d['employee_code'] ?? null, 'team_id' => $d['team_id'] ?? null,
                'max_discount_pct' => $d['max_discount_pct'] ?? self::defaultLimit($d['role']),
            ]);
            $uid = (int) $db->insertID();
            $db->table('user_roles')->insert(['user_id' => $uid, 'role_id' => $role['id']]);
            if (! empty($d['team_id'])) {
                $db->query('INSERT IGNORE INTO crm_team_members (team_id, user_id) VALUES (?,?)', [$d['team_id'], $uid]);
            }
            AuditService::audit('user.created', 'user', $uid, ['new' => ['username' => $d['username'], 'email' => $d['email'], 'role' => $d['role'], 'team_id' => $d['team_id'] ?? null]]);

            return $uid;
        });
    }

    public static function defaultLimit(string $role): string
    {
        return match ($role) {
            'sales_executive' => Settings::get('discount.limit.executive_pct'),
            'sales_manager'   => Settings::get('discount.limit.manager_pct'),
            'admin', 'super_admin' => Settings::get('discount.limit.admin_pct'),
            default => '0',
        };
    }

    public static function setActive(int $uid, bool $active, ?string $reason = null): void
    {
        $db = Crm::db();
        if (! $active && $db->table('admin_users')->where('id', $uid)->get()->getRow() === null) {
            throw new \InvalidArgumentException('No such user.');
        }
        $db->table('admin_users')->where('id', $uid)->update(['is_active' => $active ? 1 : 0, 'deactivated_at' => $active ? null : Crm::now()]);
        AuditService::audit($active ? 'user.activated' : 'user.deactivated', 'user', $uid, ['reason' => $reason]);
    }

    public static function adminResetPassword(int $uid, string $newPassword): void
    {
        if ($p = AuthService::passwordProblems($newPassword)) {
            throw new \InvalidArgumentException(implode(' ', $p));
        }
        Crm::db()->table('admin_users')->where('id', $uid)->update(['password_hash' => AuthService::hash($newPassword), 'failed_login_count' => 0, 'locked_until' => null, 'password_changed_at' => Crm::now()]);
        AuditService::audit('user.password_reset_by_admin', 'user', $uid, []);
    }

    public static function createTeam(string $name, ?int $managerId): int
    {
        $db = Crm::db();
        $db->table('crm_teams')->insert(['name' => $name, 'manager_id' => $managerId]);
        $id = (int) $db->insertID();
        if ($managerId) {
            $db->query('INSERT IGNORE INTO crm_team_members (team_id, user_id) VALUES (?,?)', [$id, $managerId]);
        }
        AuditService::audit('team.created', 'team', $id, ['new' => ['name' => $name, 'manager_id' => $managerId]]);

        return $id;
    }

    public static function addToTeam(int $teamId, int $userId): void
    {
        $db = Crm::db();
        $db->query('INSERT INTO crm_team_members (team_id, user_id, is_active) VALUES (?,?,1) ON DUPLICATE KEY UPDATE is_active = 1', [$teamId, $userId]);
        $db->table('admin_users')->where('id', $userId)->update(['team_id' => $teamId]);
        AuditService::audit('team.member_added', 'team', $teamId, ['new' => ['user_id' => $userId]]);
    }
}
