<?php

namespace Tests\Crm;

use Crm\Services\AccessScope;
use Crm\Services\AuditService;
use Crm\Services\AuthException;
use Crm\Services\AuthService;
use Crm\Services\Crm;
use Crm\Services\Money;
use Crm\Services\NumberSequenceService;
use Crm\Services\Settings;
use Crm\Services\UserService;

final class CoreServicesTest extends CrmTestCase
{
    public function testMoneyRoundsHalfUpWithoutFloatDrift(): void
    {
        $this->assertSame('1.01', Money::round('1.005'));
        $this->assertSame('0.30', Money::round(Money::add('0.1', '0.2')));
        $this->assertSame('-1.01', Money::round('-1.005'));
        $this->assertSame('18.00', Money::pct('100', '18'));
        $this->assertSame('0', Money::norm('abc'));
    }

    public function testNumbersAreSequentialAndUnique(): void
    {
        $a = NumberSequenceService::next('quotation');
        $b = NumberSequenceService::next('quotation');
        $this->assertMatchesRegularExpression('/^QT-\d{4,}$/', $a);
        $this->assertNotSame($a, $b);
        $this->assertSame((int) substr($a, 3) + 1, (int) substr($b, 3));
    }

    public function testAuditScrubsSecretsAndIsAppendOnly(): void
    {
        $id = AuditService::audit('test.event', 'user', 1, ['new' => ['password' => 'x', 'nested' => ['api_token' => 'y'], 'ok' => 'v']]);
        $row = Crm::db()->table('crm_audit_events')->where('id', $id)->get()->getRowArray();
        $this->assertStringNotContainsString('"x"', $row['new_state']);
        $this->assertStringNotContainsString('"y"', $row['new_state']);
        $this->assertStringContainsString('"ok":"v"', $row['new_state']);
        $this->assertSame(32, strlen($row['correlation_id']));
    }

    public function testLoginSuccessFailureAndHistory(): void
    {
        $uid = $this->user('sales_executive', ['email' => 'exec1@demo.invalid', 'username' => 'exec1']);
        $s   = AuthService::attempt('exec1@demo.invalid', 'Correct-Horse-9', '127.0.0.1');
        $this->assertSame($uid, $s['id']);
        $this->assertContains('sales_executive', $s['roles']);
        $this->assertContains('enquiries.create', $s['permissions']);
        $this->assertNotContains('users.manage', $s['permissions']);

        $this->expectException(AuthException::class);
        try {
            AuthService::attempt('exec1@demo.invalid', 'wrong', '127.0.0.1');
        } finally {
            $ev = array_column(Crm::db()->table('crm_login_history')->where('user_id', $uid)->get()->getResultArray(), 'event');
            $this->assertContains('login', $ev);
            $this->assertContains('failed', $ev);
        }
    }

    public function testDeactivatedAndUnknownUsersCannotLogin(): void
    {
        $uid = $this->user('sales_executive', ['email' => 'gone@demo.invalid', 'username' => 'gone']);
        UserService::setActive($uid, false, 'left company');
        try {
            AuthService::attempt('gone@demo.invalid', 'Correct-Horse-9');
            $this->fail('deactivated login succeeded');
        } catch (AuthException $e) {
            $this->assertStringContainsString('deactivated', $e->getMessage());
        }
        $this->expectException(AuthException::class);
        AuthService::attempt('nobody@demo.invalid', 'Correct-Horse-9');
    }

    public function testAccountLocksAfterRepeatedFailures(): void
    {
        $this->user('sales_executive', ['email' => 'lock@demo.invalid', 'username' => 'lockme']);
        Settings::set('auth.max_failed_logins', '3');
        for ($i = 0; $i < 3; $i++) {
            try {
                AuthService::attempt('lock@demo.invalid', 'bad');
            } catch (AuthException) {
            }
        }
        try {
            AuthService::attempt('lock@demo.invalid', 'Correct-Horse-9');
            $this->fail('locked account accepted correct password');
        } catch (AuthException $e) {
            $this->assertStringContainsString('locked', $e->getMessage());
        }
    }

    public function testPasswordResetTokenIsSingleUseAndHashedAtRest(): void
    {
        $this->user('sales_executive', ['email' => 'reset@demo.invalid', 'username' => 'reset1']);
        $raw = AuthService::createResetToken('reset@demo.invalid');
        $this->assertNotNull($raw);
        $this->assertSame(0, Crm::db()->table('password_resets')->where('token_hash', $raw)->countAllResults());
        AuthService::resetPassword($raw, 'Brand-New-Pass-1');
        $this->assertSame('exec', substr(AuthService::attempt('reset@demo.invalid', 'Brand-New-Pass-1')['roles'][0], 6, 4));
        $this->expectException(AuthException::class);
        AuthService::resetPassword($raw, 'Another-Pass-22');
    }

    public function testUnknownEmailResetReturnsNullWithoutLeak(): void
    {
        $this->assertNull(AuthService::createResetToken('ghost@demo.invalid'));
    }

    public function testWeakPasswordsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->user('sales_executive', ['password' => 'short']);
    }

    public function testRoleScopesAndPermissionMatrix(): void
    {
        $exec = $this->sessionUser($this->user('sales_executive'));
        $mgr  = $this->sessionUser($this->user('sales_manager'));
        $adm  = $this->sessionUser($this->user('admin'));
        $mgt  = $this->sessionUser($this->user('management'));

        $this->assertSame('own', AccessScope::level($exec));
        $this->assertSame('team', AccessScope::level($mgr));
        $this->assertSame('all', AccessScope::level($adm));
        $this->assertSame('all', AccessScope::level($mgt));

        $this->assertFalse(AccessScope::can($exec, 'discounts.approve_manager'));
        $this->assertTrue(AccessScope::can($mgr, 'discounts.approve_manager'));
        $this->assertFalse(AccessScope::can($mgr, 'discounts.approve_admin'));
        $this->assertTrue(AccessScope::can($adm, 'users.manage'));
        // management is read-only: no create/edit/approve/manage permissions at all
        foreach ($mgt['permissions'] as $p) {
            $this->assertDoesNotMatchRegularExpression('/\.(create|edit|assign|reassign|close|reopen|manage|send|approve_.*|deactivate|reset)$/', $p, "management has write permission {$p}");
        }
    }

    public function testManagerSeesOnlyOwnTeamMembers(): void
    {
        $mgrId  = $this->user('sales_manager');
        $teamId = UserService::createTeam('T' . bin2hex(random_bytes(3)), $mgrId);
        $in     = $this->user('sales_executive');
        $out    = $this->user('sales_executive');
        UserService::addToTeam($teamId, $in);
        $ids = AccessScope::visibleUserIds($this->sessionUser($mgrId));
        $this->assertContains($in, $ids);
        $this->assertContains($mgrId, $ids);
        $this->assertNotContains($out, $ids);
    }

    public function testSessionTimeout(): void
    {
        $u = ['last_activity' => time() - 3600];
        $this->assertTrue(AuthService::isExpired($u));
        $this->assertFalse(AuthService::isExpired(['last_activity' => time()]));
    }
}
