<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * @internal
 */
final class SystemControllerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // CI's shared view renderer keeps data between requests in one process (a test-only artefact: production
        // serves one request per process). Reset it so a previous page's content can't leak into the next layout.
        \Config\Services::resetSingle('renderer');
        $this->beginRollbackTx();
        $db = $this->dbc();
        foreach (['social_publish_attempts', 'social_scheduled_jobs', 'scheduled_job_runs'] as $t) {
            $db->query("DELETE FROM `$t`");
        }
        cache()->delete('system_health_v1');
    }

    protected function tearDown(): void
    {
        putenv('SMTP_PASS');
        cache()->delete('system_health_v1');
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function as(array $perms, bool $super = false): array
    {
        return ['admin_user' => [
            'id' => 7771, 'username' => 'tester', 'name' => 'Tester', 'email' => 't@example.invalid',
            'roles' => [['id' => 99, 'name' => $super ? 'Super Administrator' : 'Limited', 'slug' => $super ? 'super_admin' : 'limited']],
            'permissions' => $perms,
        ]];
    }

    private function failedJob(?string $err = null): int
    {
        $db = $this->dbc();
        $db->table('social_posts')->insert(['title' => 'ctl-test', 'base_content' => 'x', 'status' => 'failed', 'primary_platform' => 'linkedin', 'created_at' => date('Y-m-d H:i:s')]);
        $post = (int) $db->insertID();
        $db->table('social_scheduled_jobs')->insert(['post_id' => $post, 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => 'failed', 'attempts' => 3, 'max_attempts' => 3, 'last_error' => $err, 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $db->insertID();
    }

    private function post(string $uri, array $session, bool $csrf = true)
    {
        return $this->withSession($session)->call('post', $uri, $csrf ? [csrf_token() => csrf_hash()] : []);
    }

    private function jobStatus(int $id): string
    {
        return (string) $this->dbc()->table('social_scheduled_jobs')->where('id', $id)->get()->getRowArray()['status'];
    }

    // ── access control ───────────────────────────────────────────────────────
    public function testAnonymousUsersAreSentToLogin(): void
    {
        foreach (['admin/system/health', 'admin/system/jobs', 'admin/system/scheduler', 'admin/system/jobs/social/1'] as $uri) {
            $r = $this->call('get', $uri);
            $r->assertStatus(302);
            $this->assertStringContainsString('admin/login', $r->getRedirectUrl(), $uri);
        }
    }

    public function testUserWithoutSystemViewIsDeniedEverywhere(): void
    {
        $s = $this->as(['applications.view']);
        foreach (['admin/system/health', 'admin/system/jobs', 'admin/system/scheduler', 'admin/system/jobs/social/1'] as $uri) {
            $r = $this->withSession($s)->call('get', $uri);
            $r->assertStatus(302);
            $this->assertStringNotContainsString('system', (string) $r->getRedirectUrl(), $uri);
            $this->assertStringNotContainsString('System Health', (string) $r->getBody());
        }
        $this->assertStringContainsString('Access restricted', (string) session()->getFlashdata('error'));
    }

    public function testViewOnlyUserCanSeePagesButNotMutate(): void
    {
        $id = $this->failedJob();
        $s  = $this->as(['system.view']);
        $this->withSession($s)->call('get', 'admin/system/health')->assertStatus(200);
        $this->withSession($s)->call('get', 'admin/system/jobs')->assertStatus(200);
        $this->withSession($s)->call('get', 'admin/system/scheduler')->assertStatus(200);
        $detail = $this->withSession($s)->call('get', 'admin/system/jobs/social/' . $id);
        $detail->assertStatus(200);
        $this->assertStringNotContainsString('Retry this job', $detail->getBody(), 'no action buttons without system.manage');
        $this->assertStringContainsString('view-only access', $detail->getBody());

        $r = $this->post('admin/system/jobs/social/' . $id . '/retry', $s);
        $r->assertStatus(302);
        $this->assertSame('failed', $this->jobStatus($id), 'privilege check is server-side, not just hidden buttons');
        $this->assertSame(0, (int) $this->dbc()->table('audit_logs')->like('action', 'JOB_RETRY')->where('entity_id', 'social:' . $id)->countAllResults());
    }

    public function testSystemManageUserSeesButtonsAndCanRetryWithAuditTrail(): void
    {
        $id = $this->failedJob();
        $s  = $this->as(['system.view', 'system.manage']);
        $this->assertStringContainsString('Retry this job', $this->withSession($s)->call('get', 'admin/system/jobs/social/' . $id)->getBody());

        $r = $this->post('admin/system/jobs/social/' . $id . '/retry', $s);
        $r->assertStatus(302);
        $this->assertSame('queued', $this->jobStatus($id));
        $audit = $this->dbc()->table('audit_logs')->where('action', 'JOB_RETRY')->where('entity_id', 'social:' . $id)->get()->getRowArray();
        $this->assertNotNull($audit, 'retry is audit-logged');
        $this->assertSame('7771', (string) $audit['user_id']);
    }

    public function testDoubleRetryIsRejectedAndAuditedAsRejected(): void
    {
        $id = $this->failedJob();
        $s  = $this->as([], true);
        $this->post('admin/system/jobs/social/' . $id . '/retry', $s);
        $this->post('admin/system/jobs/social/' . $id . '/retry', $s);
        $this->assertSame('queued', $this->jobStatus($id));
        $this->assertSame(1, (int) $this->dbc()->table('audit_logs')->where('action', 'JOB_RETRY')->where('entity_id', 'social:' . $id)->countAllResults());
        $this->assertSame(1, (int) $this->dbc()->table('audit_logs')->where('action', 'JOB_RETRY_REJECTED')->where('entity_id', 'social:' . $id)->countAllResults());
        $this->assertStringContainsString('can no longer be retried', (string) session()->getFlashdata('error'));
    }

    public function testCancelWaitingJobButNotRunningJob(): void
    {
        $db = $this->dbc();
        $db->table('social_scheduled_jobs')->insert(['post_id' => 1, 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => 'queued', 'created_at' => date('Y-m-d H:i:s')]);
        $queued = (int) $db->insertID();
        $db->table('social_scheduled_jobs')->insert(['post_id' => 1, 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => 'processing', 'locked_at' => date('Y-m-d H:i:s'), 'locked_by' => 'w', 'created_at' => date('Y-m-d H:i:s')]);
        $running = (int) $db->insertID();
        $s = $this->as(['system.manage']);
        $this->post('admin/system/jobs/social/' . $queued . '/cancel', $s);
        $this->assertSame('cancelled', $this->jobStatus($queued));
        $this->post('admin/system/jobs/social/' . $running . '/cancel', $s);
        $this->assertSame('processing', $this->jobStatus($running));
        $this->assertSame(1, (int) $db->table('audit_logs')->where('action', 'JOB_CANCEL')->where('entity_id', 'social:' . $queued)->countAllResults());
        $this->assertSame(1, (int) $db->table('audit_logs')->where('action', 'JOB_CANCEL_REJECTED')->where('entity_id', 'social:' . $running)->countAllResults());
    }

    // ── CSRF / routing / validation ──────────────────────────────────────────
    public function testMutationsWithoutCsrfTokenAreRefused(): void
    {
        $id = $this->failedJob();
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        try {
            $this->post('admin/system/jobs/social/' . $id . '/retry', $this->as([], true), false);
        } finally {
            $this->assertSame('failed', $this->jobStatus($id));
        }
    }

    public function testGetCannotMutate(): void
    {
        $id = $this->failedJob();
        try {
            $this->withSession($this->as([], true))->call('get', 'admin/system/jobs/social/' . $id . '/retry');
        } catch (\Throwable) {
            // 404 / method not allowed are both acceptable
        }
        $this->assertSame('failed', $this->jobStatus($id));
    }

    public function testUnknownQueueAndIdsAreHandledSafely(): void
    {
        $s = $this->as([], true);
        try {
            $r = $this->withSession($s)->call('get', 'admin/system/jobs/evil/1');
            $this->assertSame(404, $r->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException) {
            $this->assertTrue(true);
        }
        $r = $this->withSession($s)->call('get', 'admin/system/jobs/social/99999999');
        $r->assertStatus(302);
        $this->assertStringContainsString('admin/system/jobs', $r->getRedirectUrl());
        $this->withSession($s)->call('get', 'admin/system/jobs', ['queue' => "x' OR 1=1 --", 'status' => '<script>alert(1)</script>', 'page' => '-5'])->assertStatus(200);
    }

    // ── no sensitive output ──────────────────────────────────────────────────
    public function testPagesNeverRenderSecretsPathsOrRawErrors(): void
    {
        putenv('SMTP_PASS=TOP-SECRET-SMTP-VALUE-9917');
        $id = $this->failedJob('Graph error access_token=EAAB1234567890abcdefghijklmnopqrstuvwxyz at C:\\xampp\\htdocs\\x.php <script>alert(1)</script>');
        $s  = $this->as([], true);
        $needles = ['TOP-SECRET-SMTP-VALUE-9917', 'EAAB1234567890', 'C:\\xampp', '<script>alert(1)</script>', 'encryption.key', 'DB_PASSWORD', 'Stack trace'];
        foreach (['admin/system/health', 'admin/system/jobs', 'admin/system/scheduler', 'admin/system/jobs/social/' . $id] as $uri) {
            $body = (string) $this->withSession($s)->call('get', $uri)->getBody();
            foreach ($needles as $n) {
                $this->assertStringNotContainsString($n, $body, "{$uri} leaked {$n}");
            }
            $this->assertStringNotContainsString(ROOTPATH, $body, "{$uri} leaked a server path");
        }
    }

    public function testHealthIsCachedAndRefreshIsPostOnly(): void
    {
        $s = $this->as(['system.view']);
        $this->withSession($s)->call('get', 'admin/system/health')->assertStatus(200);
        $this->assertIsArray(cache()->get('system_health_v1'), 'result cached so the page cannot hammer the DB');
        $this->post('admin/system/health/refresh', $s)->assertStatus(302);
        $this->assertNull(cache()->get('system_health_v1'));
    }

    // ── pages reflect real heartbeat data ────────────────────────────────────
    public function testSchedulerPageIsNotVerifiedUntilARealRunExists(): void
    {
        $s    = $this->as([], true);
        $body = (string) $this->withSession($s)->call('get', 'admin/system/scheduler')->getBody();
        $this->assertStringContainsString('Not Verified', $body);
        $this->assertStringNotContainsString('>Healthy<', $body);
        $this->assertStringContainsString('never recorded', $body);

        $now = date('Y-m-d H:i:s');
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'social:publish', 'status' => 'SUCCESS', 'items_processed' => 3, 'duration_ms' => 12, 'started_at' => $now, 'completed_at' => $now]);
        $body = (string) $this->withSession($s)->call('get', 'admin/system/scheduler')->getBody();
        $this->assertStringContainsString('completed successfully', $body);
    }

    public function testSocialQueueCronBadgeFollowsActualHeartbeat(): void
    {
        $s    = $this->as([], true);
        $get  = function () use ($s) {
            \Config\Services::resetSingle('renderer');
            return (string) $this->withSession($s)->call('get', 'admin/social/queue')->getBody();
        };
        $none = $get();
        $this->assertStringContainsString('Cron worker: not verified', $none);
        $this->assertStringNotContainsString('Cron Worker: Active', $none, 'the old hard-coded badge is gone');
        $this->assertStringNotContainsString(rtrim(str_replace('\\', '/', ROOTPATH), '/'), str_replace('\\', '/', $none), 'no server path in the cron instructions');

        $now = date('Y-m-d H:i:s');
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'social:publish', 'status' => 'SUCCESS', 'items_processed' => 0, 'duration_ms' => 5, 'started_at' => $now, 'completed_at' => $now]);
        $this->assertStringContainsString('Cron worker: running', $get());

        $this->dbc()->query('DELETE FROM scheduled_job_runs');
        $old = date('Y-m-d H:i:s', time() - 3 * 3600);
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'social:publish', 'status' => 'SUCCESS', 'items_processed' => 0, 'duration_ms' => 5, 'started_at' => $old, 'completed_at' => $old]);
        $this->assertStringContainsString('Cron worker: stopped', $get());
    }

    public function testHeaderMailSyncPillReflectsRealHeartbeatNotAFixedLabel(): void
    {
        $s   = $this->as([], true);
        $get = function () use ($s) {
            \Config\Services::resetSingle('renderer');
            return (string) $this->withSession($s)->call('get', 'admin/system/health')->getBody();
        };
        $none = $get();
        $this->assertStringContainsString('Mail sync: not verified', $none);
        $this->assertStringNotContainsString('Hostinger Mail Sync Active', $none, 'the old hard-coded pill is gone');

        $now = date('Y-m-d H:i:s');
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'mail:sync', 'status' => 'FAILED', 'error_details' => 'x', 'started_at' => $now, 'completed_at' => $now]);
        $this->assertStringContainsString('Mail sync: needs attention', $get());

        $this->dbc()->query('DELETE FROM scheduled_job_runs');
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'mail:sync', 'status' => 'SUCCESS', 'started_at' => $now, 'completed_at' => $now]);
        $this->assertStringContainsString('Mail sync: running', $get());

        $this->dbc()->query('DELETE FROM scheduled_job_runs');
        $old = date('Y-m-d H:i:s', time() - 30 * 3600);
        $this->dbc()->table('scheduled_job_runs')->insert(['job_name' => 'mail:sync', 'status' => 'SUCCESS', 'started_at' => $old, 'completed_at' => $old]);
        $this->assertStringContainsString('Mail sync: stopped', $get());
    }
}
