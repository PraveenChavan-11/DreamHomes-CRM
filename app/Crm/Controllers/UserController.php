<?php

namespace Crm\Controllers;

use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ReferenceData;
use Crm\Services\UserService;

/** Users, teams and the read-only role/permission matrix. Account rules (password policy, uniqueness, audit) live in UserService. */
class UserController extends CrmController
{
    public function index(): string
    {
        $db  = Crm::db();
        $tab = (string) $this->request->getGet('tab');
        $can = ['users' => crm_can('users.manage'), 'teams' => crm_can('teams.manage')];
        if (! in_array($tab, ['users', 'teams', 'roles', 'logins'], true) || ($tab === 'users' && ! $can['users']) || ($tab === 'logins' && ! $can['users'])) {
            $tab = $can['users'] ? 'users' : 'teams';
        }
        $d = ['tab' => $tab, 'can' => $can];

        $users = $db->query(
            "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.phone, u.employee_code, u.is_active, u.last_login_at, u.failed_login_count, u.locked_until, u.max_discount_pct,
                    GROUP_CONCAT(DISTINCT r.slug) roles, (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM crm_team_members tm JOIN crm_teams t ON t.id = tm.team_id WHERE tm.user_id = u.id AND tm.is_active = 1) teams
             FROM admin_users u LEFT JOIN user_roles ur ON ur.user_id = u.id LEFT JOIN roles r ON r.id = ur.role_id
             WHERE EXISTS (SELECT 1 FROM user_roles x JOIN roles y ON y.id = x.role_id WHERE x.user_id = u.id AND y.slug IN ('super_admin','admin','sales_manager','sales_executive','management'))
             GROUP BY u.id ORDER BY u.is_active DESC, u.first_name"
        )->getResultArray();
        $d['users'] = $users;
        $d['teams'] = $db->query(
            "SELECT t.id, t.name, t.is_active, t.manager_id, CONCAT(m.first_name,' ',m.last_name) manager,
                    (SELECT COUNT(*) FROM crm_team_members tm WHERE tm.team_id = t.id AND tm.is_active = 1) members
             FROM crm_teams t LEFT JOIN admin_users m ON m.id = t.manager_id ORDER BY t.name"
        )->getResultArray();
        foreach ($d['teams'] as &$t) {
            $t['member_rows'] = $db->query("SELECT u.id, CONCAT(u.first_name,' ',u.last_name) name FROM crm_team_members tm JOIN admin_users u ON u.id = tm.user_id WHERE tm.team_id = ? AND tm.is_active = 1 ORDER BY u.first_name", [$t['id']])->getResultArray();
        }
        unset($t);
        $d['assignable'] = $db->query("SELECT u.id, CONCAT(u.first_name,' ',u.last_name) name FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.is_active = 1 AND r.slug IN ('sales_manager','sales_executive') GROUP BY u.id ORDER BY u.first_name")->getResultArray();
        $d['roles']   = $db->table('roles')->orderBy('id')->get()->getResultArray();
        $d['perms']   = $db->table('permissions')->orderBy('category')->orderBy('code')->get()->getResultArray();
        $grant        = [];
        foreach ($db->query('SELECT r.slug, p.code FROM role_permissions rp JOIN roles r ON r.id = rp.role_id JOIN permissions p ON p.id = rp.permission_id')->getResultArray() as $g) {
            $grant[$g['slug']][$g['code']] = true;
        }
        $d['grant'] = $grant;
        $uid        = (int) $this->request->getGet('uid');
        $lb         = $db->table('crm_login_history h')->select("h.*, CONCAT(u.first_name,' ',u.last_name) uname", false)->join('admin_users u', 'u.id = h.user_id', 'left')->orderBy('h.id', 'DESC')->limit(40);
        if ($uid) {
            $lb->where('h.user_id', $uid);
        }
        $d['logins'] = $tab === 'logins' ? $lb->get()->getResultArray() : [];
        $d['uid']    = $uid;
        $d['assignableRoles'] = array_filter(array_keys(ReferenceData::ROLES), fn ($s) => $s !== 'super_admin' || AccessScope::isSuper($this->user()));

        return $this->render('users/index', $d, 'Users & Teams', 'users');
    }

    public function create()
    {
        $p    = $this->pick($this->request->getPost(), ['username', 'email', 'first_name', 'last_name', 'phone', 'role', 'team_id', 'employee_code', 'max_discount_pct']);
        $pw   = (string) $this->request->getPost('password');
        $user = $this->user();

        return $this->act(function () use ($p, $pw, $user) {
            $e = [];
            foreach (['username' => 'Username', 'first_name' => 'First name', 'last_name' => 'Last name'] as $k => $l) {
                if (($p[$k] ?? '') === '') {
                    $e[$k] = "{$l} is required.";
                }
            }
            if (! filter_var($p['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $e['email'] = 'Enter a valid email address.';
            }
            if (! empty($p['username']) && ! preg_match('/^[a-z0-9._-]{3,50}$/i', $p['username'])) {
                $e['username'] = 'Use 3-50 letters, digits, dot, dash or underscore.';
            }
            if ($e) {
                throw CrmException::validation($e);
            }
            if (($p['role'] ?? '') === 'super_admin' && ! AccessScope::isSuper($user)) {
                throw new CrmException('Only a Super Admin can create another Super Admin.');
            }
            $data = ['username' => $p['username'], 'email' => $p['email'], 'first_name' => $p['first_name'], 'last_name' => $p['last_name'], 'phone' => ($p['phone'] ?? '') ?: null,
                'role' => (string) ($p['role'] ?? ''), 'password' => $pw, 'team_id' => ((int) ($p['team_id'] ?? 0)) ?: null, 'employee_code' => ($p['employee_code'] ?? '') ?: null];
            if (($p['max_discount_pct'] ?? '') !== '' && is_numeric($p['max_discount_pct']) && $p['max_discount_pct'] >= 0 && $p['max_discount_pct'] <= 100) {
                $data['max_discount_pct'] = $p['max_discount_pct'];
            }
            UserService::create($data);
        }, 'User created. Share the temporary password through a secure channel; they can change it from their profile.', 'crm/users');
    }

    public function active(int $id)
    {
        $user = $this->user();
        $on   = (bool) $this->request->getPost('active');
        $why  = trim((string) $this->request->getPost('reason'));

        return $this->act(function () use ($id, $on, $why, $user) {
            if ($id === (int) $user['id']) {
                throw new CrmException('You cannot deactivate your own account.');
            }
            $this->assertManageable($id);
            UserService::setActive($id, $on, $why ?: null);
        }, $on ? 'User activated.' : 'User deactivated. Their history and ownership records remain intact.', 'crm/users');
    }

    public function password(int $id)
    {
        $pw = (string) $this->request->getPost('new_password');

        return $this->act(function () use ($id, $pw) {
            $this->assertManageable($id);
            UserService::adminResetPassword($id, $pw);
        }, 'Password reset. The user should change it after signing in.', 'crm/users');
    }

    public function createTeam()
    {
        $name = trim((string) $this->request->getPost('name'));
        $mgr  = (int) $this->request->getPost('manager_id') ?: null;

        return $this->act(function () use ($name, $mgr) {
            if ($name === '' || mb_strlen($name) > 100) {
                throw CrmException::validation(['name' => 'Team name is required (max 100 characters).']);
            }
            if (Crm::db()->table('crm_teams')->where('name', $name)->countAllResults()) {
                throw CrmException::validation(['name' => 'A team with that name already exists.']);
            }
            if ($mgr && ! Crm::db()->table('admin_users')->where(['id' => $mgr, 'is_active' => 1])->countAllResults()) {
                throw new CrmException('Choose an active manager.');
            }
            UserService::createTeam($name, $mgr);
        }, 'Team created.', 'crm/users?tab=teams');
    }

    public function addMember(int $teamId)
    {
        $uid = (int) $this->request->getPost('user_id');

        return $this->act(function () use ($teamId, $uid) {
            $db = Crm::db();
            if (! $db->table('crm_teams')->where('id', $teamId)->countAllResults()) {
                throw new CrmException('Team not found.');
            }
            if (! $db->table('admin_users')->where(['id' => $uid, 'is_active' => 1])->countAllResults()) {
                throw new CrmException('Choose an active user.');
            }
            UserService::addToTeam($teamId, $uid);
        }, 'Member added to team.', 'crm/users?tab=teams');
    }

    /** Non-super admins may not change the accounts of Super Admins. */
    private function assertManageable(int $targetId): void
    {
        $slugs = array_column(Crm::db()->query('SELECT r.slug FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?', [$targetId])->getResultArray(), 'slug');
        if (! $slugs && ! Crm::db()->table('admin_users')->where('id', $targetId)->countAllResults()) {
            throw new CrmException('User not found.');
        }
        if (in_array('super_admin', $slugs, true) && ! AccessScope::isSuper($this->user())) {
            throw new CrmException('Only a Super Admin can change a Super Admin account.');
        }
    }
}
