<?php

namespace Crm\Services;

/** Login/logout/lockout/reset/password-change with login history. Uses shared admin_users identity table. */
final class AuthService
{
    public static function cost(): int
    {
        return ENVIRONMENT === 'testing' ? 4 : 12;
    }

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => self::cost()]);
    }

    /** @return list<string> problems */
    public static function passwordProblems(string $p): array
    {
        $e = [];
        if (mb_strlen($p) < 10) {
            $e[] = 'Password must be at least 10 characters.';
        }
        if (! preg_match('/[A-Z]/', $p) || ! preg_match('/[a-z]/', $p) || ! preg_match('/\d/', $p)) {
            $e[] = 'Password must include upper-case, lower-case and a digit.';
        }

        return $e;
    }

    private static function history(?int $uid, ?string $email, string $event, ?string $ip, ?string $ua): void
    {
        Crm::db()->table('crm_login_history')->insert([
            'user_id' => $uid, 'email' => $email ? mb_substr($email, 0, 190) : null, 'event' => $event,
            'ip_address' => $ip, 'user_agent' => $ua ? mb_substr($ua, 0, 255) : null, 'created_at' => Crm::now(),
        ]);
    }

    /** @return array<string,mixed> session user */
    public static function attempt(string $identity, string $password, ?string $ip = null, ?string $ua = null): array
    {
        $db   = Crm::db();
        $user = $db->table('admin_users')->groupStart()->where('email', $identity)->orWhere('username', $identity)->groupEnd()->get()->getRowArray();
        $generic = 'Invalid credentials.';

        if (! $user) {
            self::history(null, $identity, 'failed', $ip, $ua);
            password_verify($password, '$2y$04$abcdefghijklmnopqrstuuJ0m8s7Qy0o8p3sT5l0k9bXq6nNn2m1y'); // timing equaliser
            throw new AuthException($generic);
        }
        $uid = (int) $user['id'];
        if (! empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            self::history($uid, $user['email'], 'locked', $ip, $ua);
            throw new AuthException('Account temporarily locked. Try again later.');
        }
        if (! password_verify($password, (string) $user['password_hash'])) {
            $fails = (int) $user['failed_login_count'] + 1;
            $upd   = ['failed_login_count' => $fails];
            if ($fails >= (int) Settings::get('auth.max_failed_logins')) {
                $upd['locked_until']       = date('Y-m-d H:i:s', time() + 60 * (int) Settings::get('auth.lock_minutes'));
                $upd['failed_login_count'] = 0;
            }
            $db->table('admin_users')->where('id', $uid)->update($upd);
            self::history($uid, $user['email'], 'failed', $ip, $ua);
            throw new AuthException($generic);
        }
        if ((int) $user['is_active'] !== 1) {
            self::history($uid, $user['email'], 'failed', $ip, $ua);
            throw new AuthException('This account is deactivated. Contact an administrator.');
        }
        $db->table('admin_users')->where('id', $uid)->update(['failed_login_count' => 0, 'locked_until' => null, 'last_login_at' => Crm::now()]);
        self::history($uid, $user['email'], 'login', $ip, $ua);
        Crm::setActor($uid);
        AuditService::audit('auth.login', 'user', $uid, ['user_id' => $uid, 'ip' => $ip]);

        return self::sessionUser($uid);
    }

    /** @return array<string,mixed> */
    public static function sessionUser(int $uid): array
    {
        $db    = Crm::db();
        $u     = $db->table('admin_users')->where('id', $uid)->get()->getRowArray();
        $roles = array_column($db->query('SELECT r.slug FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?', [$uid])->getResultArray(), 'slug');
        $perms = array_column($db->query(
            'SELECT DISTINCT p.code FROM user_roles ur JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id WHERE ur.user_id = ?',
            [$uid]
        )->getResultArray(), 'code');

        return [
            'id' => $uid, 'name' => trim($u['first_name'] . ' ' . $u['last_name']), 'email' => $u['email'], 'roles' => $roles, 'permissions' => $perms,
            'max_discount_pct' => $u['max_discount_pct'] ?? '0', 'last_activity' => time(),
        ];
    }

    public static function logout(int $uid, ?string $ip = null, ?string $ua = null, string $event = 'logout'): void
    {
        self::history($uid, null, $event, $ip, $ua);
        AuditService::audit('auth.' . $event, 'user', $uid, ['user_id' => $uid, 'ip' => $ip]);
    }

    public static function isExpired(array $sessionUser): bool
    {
        return (time() - (int) ($sessionUser['last_activity'] ?? 0)) > 60 * (int) Settings::get('session.timeout_minutes');
    }

    public static function changePassword(int $uid, string $current, string $new, ?string $ip = null): void
    {
        $db = Crm::db();
        $u  = $db->table('admin_users')->where('id', $uid)->get()->getRowArray();
        if (! $u || ! password_verify($current, (string) $u['password_hash'])) {
            throw new AuthException('Current password is incorrect.');
        }
        if ($p = self::passwordProblems($new)) {
            throw new AuthException(implode(' ', $p));
        }
        $db->table('admin_users')->where('id', $uid)->update(['password_hash' => self::hash($new), 'password_changed_at' => Crm::now()]);
        self::history($uid, $u['email'], 'password_change', $ip, null);
        AuditService::audit('auth.password_changed', 'user', $uid, ['user_id' => $uid]);
    }

    /** Returns raw token (to be emailed) or null when no such active user (caller must not reveal which). */
    public static function createResetToken(string $email, ?string $ip = null): ?string
    {
        $db = Crm::db();
        $u  = $db->table('admin_users')->where('email', $email)->where('is_active', 1)->get()->getRowArray();
        if (! $u) {
            return null;
        }
        $raw = bin2hex(random_bytes(32));
        $db->table('password_resets')->insert([
            'email' => $email, 'token_hash' => hash('sha256', $raw), 'ip_address' => $ip,
            'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'created_at' => Crm::now(),
        ]);
        AuditService::audit('auth.reset_requested', 'user', (int) $u['id'], ['user_id' => (int) $u['id'], 'ip' => $ip]);

        return $raw;
    }

    public static function resetPassword(string $rawToken, string $new, ?string $ip = null): void
    {
        $db  = Crm::db();
        $row = $db->table('password_resets')->where('token_hash', hash('sha256', $rawToken))->where('used_at', null)->where('expires_at >', Crm::now())->get()->getRowArray();
        if (! $row) {
            throw new AuthException('This reset link is invalid or has expired.');
        }
        if ($p = self::passwordProblems($new)) {
            throw new AuthException(implode(' ', $p));
        }
        Crm::tx(static function ($db) use ($row, $new, $ip) {
            $u = $db->table('admin_users')->where('email', $row['email'])->get()->getRowArray();
            $db->table('admin_users')->where('id', $u['id'])->update(['password_hash' => self::hash($new), 'failed_login_count' => 0, 'locked_until' => null, 'password_changed_at' => Crm::now()]);
            $db->table('password_resets')->where('id', $row['id'])->update(['used_at' => Crm::now()]);
            self::history((int) $u['id'], $u['email'], 'password_reset', $ip, null);
            AuditService::audit('auth.password_reset', 'user', (int) $u['id'], ['user_id' => (int) $u['id']]);
        });
    }
}
