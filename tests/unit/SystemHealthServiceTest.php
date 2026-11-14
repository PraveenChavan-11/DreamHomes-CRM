<?php

namespace Tests\Unit;

use App\Services\SystemHealthService as H;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * @internal
 */
final class SystemHealthServiceTest extends CIUnitTestCase
{
    use RollsBackDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginRollbackTx();
        $db = $this->dbc();
        foreach (['scheduled_job_runs', 'social_scheduled_jobs', 'crm_integration_events', 'crm_integration_clients', 'social_credentials', 'social_accounts'] as $t) {
            $db->query("DELETE FROM `$t`");
        }
        $db->query("DELETE FROM email_outbox WHERE 1=1");
    }

    protected function tearDown(): void
    {
        putenv('SMTP_PASS');
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function heartbeat(string $job, string $status, int $minutesAgo, ?string $err = null): void
    {
        $t = date('Y-m-d H:i:s', time() - $minutesAgo * 60);
        $this->dbc()->table('scheduled_job_runs')->insert([
            'job_name' => $job, 'status' => $status, 'items_processed' => 1, 'error_details' => $err,
            'duration_ms' => 10, 'started_at' => $t, 'completed_at' => $status === 'RUNNING' ? null : $t,
        ]);
    }

    private function job(string $name): array
    {
        return (new H())->schedulerJobs()[$name];
    }

    // ── scheduler / heartbeat states ─────────────────────────────────────────
    public function testMissingHeartbeatIsNotVerifiedNeverHealthy(): void
    {
        $j = $this->job('social:publish');
        $this->assertSame(H::NOT_VERIFIED, $j['status']);
        $this->assertNull($j['last_success']);
        $this->assertStringContainsString('No heartbeat', $j['summary']);
        $sched = (new H())->checkScheduler();
        $this->assertNotSame(H::HEALTHY, $sched['status'], 'configured cron without evidence must never be healthy');
    }

    public function testRecentSuccessfulRunIsHealthy(): void
    {
        $this->heartbeat('social:publish', 'SUCCESS', 2);
        $j = $this->job('social:publish');
        $this->assertSame(H::HEALTHY, $j['status']);
        $this->assertNotNull($j['last_success']);
        $this->assertNotNull($j['next_expected']);
    }

    public function testSchedulerNotHealthyUntilEveryRequiredJobReports(): void
    {
        $this->heartbeat('social:publish', 'SUCCESS', 1);
        $this->assertSame(H::NOT_VERIFIED, (new H())->checkScheduler()['status'], 'engagement:run has no heartbeat yet');
        $this->heartbeat('engagement:run', 'SUCCESS', 1);
        $this->assertSame(H::HEALTHY, (new H())->checkScheduler()['status']);
    }

    public function testOptionalJobsDoNotAffectSchedulerStatus(): void
    {
        $this->heartbeat('social:publish', 'SUCCESS', 1);
        $this->heartbeat('engagement:run', 'SUCCESS', 1);
        $this->assertFalse($this->job('mail:sync')['required']);
        $this->assertSame(H::NOT_VERIFIED, $this->job('mail:sync')['status']);
        $this->assertSame(H::HEALTHY, (new H())->checkScheduler()['status']);
    }

    public function testMissedRunsWarnThenFail(): void
    {
        $this->heartbeat('social:publish', 'SUCCESS', 5 * 3);   // 3 intervals ago
        $this->assertSame(H::WARNING, $this->job('social:publish')['status']);
        $this->dbc()->query('DELETE FROM scheduled_job_runs');
        $this->heartbeat('social:publish', 'SUCCESS', 5 * 8);   // 8 intervals ago
        $this->assertSame(H::FAILED, $this->job('social:publish')['status']);
    }

    public function testMostRecentFailureWarnsAndIsCountedAndRedacted(): void
    {
        $this->heartbeat('social:publish', 'SUCCESS', 7);
        $this->heartbeat('social:publish', 'FAILED', 1, 'RuntimeException: api_key=ABCDEF1234567890ABCDEF1234567890XYZ failed at C:\\xampp\\htdocs\\secret.php');
        $j = $this->job('social:publish');
        $this->assertSame(H::WARNING, $j['status']);
        $this->assertSame('FAILED', $j['last_status']);
        $this->assertSame(1, $j['failures_24h']);
        $this->assertStringNotContainsString('ABCDEF1234567890', (string) $j['last_error']);
        $this->assertStringNotContainsString('C:\\xampp', (string) $j['last_error']);
    }

    public function testRunThatNeverFinishedIsFailed(): void
    {
        $this->heartbeat('social:publish', 'RUNNING', 45);
        $this->assertSame(H::FAILED, $this->job('social:publish')['status']);
    }

    // ── database ─────────────────────────────────────────────────────────────
    public function testDatabaseFailureIsReportedWithoutLeakingDetails(): void
    {
        $bad = \Config\Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1', 'username' => 'nobody_x', 'password' => 'leak-me-not-123', 'database' => 'no_such_db_zz',
            'DBDriver' => 'MySQLi', 'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true, 'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci', 'port' => 3306,
        ], false);
        $r = (new H($bad))->checkDatabase();
        $this->assertSame(H::FAILED, $r['status']);
        $blob = json_encode($r);
        $this->assertStringNotContainsString('leak-me-not-123', $blob);
        $this->assertStringNotContainsString('nobody_x', $blob);
        $this->assertStringNotContainsString('no_such_db_zz', $blob);
    }

    public function testDatabaseHealthyReportsResponseTime(): void
    {
        $r = (new H())->checkDatabase();
        $this->assertContains($r['status'], [H::HEALTHY, H::WARNING]);
        $this->assertArrayHasKey('Response time', $r['details']);
    }

    // ── background jobs ──────────────────────────────────────────────────────
    private function socialJob(string $status, ?int $lockedMinutesAgo = null): int
    {
        $this->dbc()->table('social_scheduled_jobs')->insert([
            'post_id' => 1, 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => $status,
            'locked_at' => $lockedMinutesAgo === null ? null : date('Y-m-d H:i:s', time() - $lockedMinutesAgo * 60),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->dbc()->insertID();
    }

    public function testNoFailedOrStuckJobsIsHealthy(): void
    {
        $this->socialJob('completed');
        $this->assertSame(H::HEALTHY, (new H())->checkBackgroundJobs()['status']);
    }

    public function testFailedJobWarnsAndStuckJobFails(): void
    {
        $this->socialJob('failed');
        $this->assertSame(H::WARNING, (new H())->checkBackgroundJobs()['status']);
        $this->socialJob('processing', 40);
        $r = (new H())->checkBackgroundJobs();
        $this->assertSame(H::FAILED, $r['status']);
        $c = (new H())->jobCounts();
        $this->assertSame(1, $c['stuck']);
        $this->assertSame(1, $c['failed']);
    }

    public function testRecentlyClaimedJobIsNotStuck(): void
    {
        $this->socialJob('processing', 2);
        $this->assertSame(0, (new H())->jobCounts()['stuck']);
    }

    // ── integrations ─────────────────────────────────────────────────────────
    private function client(): void
    {
        $this->dbc()->table('crm_integration_clients')->insert(['key_id' => 'ws-test', 'label' => 'x', 'secret_encrypted' => 'enc', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function event(string $result, string $type = 'lead.created'): void
    {
        $this->dbc()->table('crm_integration_events')->insert(['event_id' => bin2hex(random_bytes(6)), 'key_id' => 'ws-test', 'event_type' => $type, 'result' => $result, 'received_at' => date('Y-m-d H:i:s')]);
    }

    public function testIntegrationStatesNeverHealthyOnConfigurationAlone(): void
    {
        $this->assertSame(H::NOT_CONFIGURED, (new H())->checkIntegrations()['status']);
        $this->client();
        $this->assertSame(H::NOT_VERIFIED, (new H())->checkIntegrations()['status'], 'a registered client with zero events is unverified, not healthy');
        $this->event('accepted');
        $this->assertSame(H::HEALTHY, (new H())->checkIntegrations()['status']);
        $this->event('failed');
        $this->assertSame(H::WARNING, (new H())->checkIntegrations()['status']);
    }

    public function testIntegrationProblemsDoNotDragOverallStatus(): void
    {
        $this->client();
        $this->event('failed');
        $this->assertFalse((new H())->checkIntegrations()['affects_overall']);
    }

    // ── social ───────────────────────────────────────────────────────────────
    private function account(?string $expires, bool $token = true, ?string $verified = '2026-01-01 00:00:00', string $status = 'active'): void
    {
        $db = $this->dbc();
        $db->table('social_accounts')->insert(['platform' => 'linkedin', 'account_name' => 'Acme', 'external_id' => 'ext-' . random_int(1, 99999), 'status' => $status, 'last_verified_at' => $verified, 'created_at' => date('Y-m-d H:i:s')]);
        $id = (int) $db->insertID();
        $db->table('social_credentials')->insert(['account_id' => $id, 'platform' => 'linkedin', 'access_token_encrypted' => $token ? 'ciphertext' : '', 'token_expires_at' => $expires, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function testSocialTokenStates(): void
    {
        $this->assertSame(H::NOT_CONFIGURED, (new H())->checkSocial()['status']);

        $this->account(date('Y-m-d H:i:s', time() - 3600));
        $this->assertSame(H::FAILED, (new H())->checkSocial()['status'], 'expired token');

        $db = $this->dbc(); $db->query('DELETE FROM social_credentials'); $db->query('DELETE FROM social_accounts');
        $this->account(date('Y-m-d H:i:s', time() + 3 * 86400));
        $this->assertSame(H::WARNING, (new H())->checkSocial()['status'], 'expires within 7 days');

        $db->query('DELETE FROM social_credentials'); $db->query('DELETE FROM social_accounts');
        $this->account(null, false);
        $this->assertSame(H::WARNING, (new H())->checkSocial()['status'], 'no token stored → needs attention, not healthy');
        $this->assertSame(H::NOT_CONFIGURED, (new H())->socialAccounts()[0]['status']);

        $db->query('DELETE FROM social_credentials'); $db->query('DELETE FROM social_accounts');
        $this->account(date('Y-m-d H:i:s', time() + 90 * 86400), true, null);
        $this->assertSame(H::NOT_VERIFIED, (new H())->socialAccounts()[0]['status'], 'token present but never verified');

        $db->query('DELETE FROM social_credentials'); $db->query('DELETE FROM social_accounts');
        $this->account(date('Y-m-d H:i:s', time() + 90 * 86400));
        $this->assertSame(H::HEALTHY, (new H())->checkSocial()['status']);
    }

    public function testSocialAccountStatusesMapToOperationalStatuses(): void
    {
        $db = $this->dbc();
        foreach (['expired' => H::PERMISSION_REQUIRED, 'disconnected' => H::NOT_CONFIGURED, 'error' => H::FAILED] as $dbStatus => $expected) {
            $db->query('DELETE FROM social_credentials');
            $db->query('DELETE FROM social_accounts');
            $this->account(date('Y-m-d H:i:s', time() + 90 * 86400), true, '2026-01-01 00:00:00', $dbStatus);
            $this->assertSame($expected, (new H())->socialAccounts()[0]['status'], $dbStatus);
        }
    }

    // ── email ────────────────────────────────────────────────────────────────
    public function testEmailNotConfiguredWithoutSmtpCredentials(): void
    {
        putenv('SMTP_PASS');
        $this->dbc()->query("DELETE FROM website_settings WHERE setting_key = 'smtp_pass'");
        $this->assertSame(H::NOT_CONFIGURED, (new H())->checkEmail()['status']);
    }

    public function testEmailWithCredentialsButNoDeliveryIsNotVerified(): void
    {
        putenv('SMTP_PASS=test-only-not-a-real-password');
        $r = (new H())->checkEmail();
        $this->assertSame(H::NOT_VERIFIED, $r['status'], 'having an SMTP password is not proof that mail is delivered');
    }

    // ── secrets / overall ────────────────────────────────────────────────────
    public function testReportNeverContainsSecretsOrServerPaths(): void
    {
        putenv('SMTP_PASS=TOP-SECRET-SMTP-VALUE-9917');
        $this->account(date('Y-m-d H:i:s', time() + 90 * 86400));
        $this->dbc()->query("UPDATE social_credentials SET access_token_encrypted = 'ACCESS-TOKEN-CIPHERTEXT-5521', client_secret_encrypted = 'CLIENT-SECRET-CIPHERTEXT-7730'");
        $blob = json_encode((new H())->run());
        foreach (['TOP-SECRET-SMTP-VALUE-9917', 'ACCESS-TOKEN-CIPHERTEXT-5521', 'CLIENT-SECRET-CIPHERTEXT-7730', 'encryption.key'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob);
        }
        $this->assertStringNotContainsString(str_replace('\\', '\\\\', ROOTPATH), $blob, 'no absolute server path');
        $this->assertStringNotContainsString(rtrim(str_replace('\\', '/', ROOTPATH), '/'), str_replace('\\/', '/', $blob));
    }

    public function testOverallIsWorstOfCoreChecksAndNeverHealthyWithoutHeartbeats(): void
    {
        $report = (new H())->run();
        $this->assertContains($report['overall'], [H::NOT_VERIFIED, H::WARNING, H::FAILED]);
        $this->assertNotSame(H::HEALTHY, $report['overall']);
        $this->assertSame(['application', 'database', 'storage', 'scheduler', 'jobs', 'email', 'social', 'integrations', 'admin_access'], array_keys($report['checks']));
    }

    public function testMakesNoOutboundNetworkOrShellCalls(): void
    {
        $src = file_get_contents(APPPATH . 'Services/SystemHealthService.php') . file_get_contents(APPPATH . 'Services/BackgroundJobService.php');
        foreach (['curl_', 'file_get_contents(\'http', 'fsockopen', 'shell_exec', 'exec(', 'system(', 'passthru', 'proc_open', 'popen', 'getenv()', 'phpinfo', '$_ENV'] as $banned) {
            $this->assertStringNotContainsString($banned, $src, "health/job services must not use {$banned}");
        }
    }

    // ── admin access (lockout safety net) ────────────────────────────────────
    public function testAdminAccessFailsWhenNoActiveSuperAdminExists(): void
    {
        $db = $this->dbc();
        $db->query("DELETE ur FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.slug = 'super_admin'");
        $r = (new H())->checkAdminAccess();
        $this->assertSame(H::FAILED, $r['status']);
        $this->assertSame(0, $r['details']['Active Super Admins']);
    }

    public function testAdminAccessWarnsAboutAccountsThatReliedOnTheRemovedShortcut(): void
    {
        $db = $this->dbc();
        $super = (int) $db->query("SELECT id FROM roles WHERE slug='super_admin'")->getRowArray()['id'];
        // a second real Super Admin exists; the account literally named "admin" does not hold the role
        $db->query("DELETE FROM user_roles WHERE role_id = $super");
        $db->query("DELETE FROM admin_users WHERE username IN ('acl_real_super','admin') AND id <> 1");
        $db->table('admin_users')->insert(['username' => 'acl_real_super', 'email' => 'acl_real_super@example.invalid', 'password_hash' => 'x', 'first_name' => 'R', 'last_name' => 'S', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        $realId = (int) $db->insertID();
        $db->table('user_roles')->insert(['user_id' => $realId, 'role_id' => $super]);
        $r = (new H())->checkAdminAccess();
        $this->assertSame(H::WARNING, $r['status']);
        $this->assertStringContainsString('no Super Admin role', $r['summary']);
        $this->assertSame(1, $r['details']['Active Super Admins']);
        // give account id 1 the role → healthy again
        $db->table('user_roles')->insert(['user_id' => 1, 'role_id' => $super]);
        $this->assertSame(H::HEALTHY, (new H())->checkAdminAccess()['status']);
    }
}
