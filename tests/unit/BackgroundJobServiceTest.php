<?php

namespace Tests\Unit;

use App\Services\BackgroundJobService as B;
use App\Services\JobRunRecorder;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * @internal
 */
final class BackgroundJobServiceTest extends CIUnitTestCase
{
    use RollsBackDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beginRollbackTx();
        $db = $this->dbc();
        foreach (['social_publish_attempts', 'social_scheduled_jobs', 'scheduled_job_runs'] as $t) {
            $db->query("DELETE FROM `$t`");
        }
        $db->query('DELETE FROM email_outbox');
    }

    protected function tearDown(): void
    {
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function post(string $status = 'failed'): int
    {
        $db = $this->dbc();
        $db->table('social_posts')->insert(['title' => 'job-test', 'base_content' => 'x', 'status' => $status, 'primary_platform' => 'linkedin', 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $db->insertID();
    }

    private function sjob(string $status, ?int $postId = null, ?string $err = null): int
    {
        $db = $this->dbc();
        $db->table('social_scheduled_jobs')->insert([
            'post_id' => $postId ?? $this->post(), 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => $status,
            'attempts' => 3, 'max_attempts' => 3, 'last_error' => $err, 'locked_at' => $status === 'processing' ? date('Y-m-d H:i:s') : null,
            'locked_by' => $status === 'processing' ? 'w1' : null, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $db->insertID();
    }

    private function email(string $status): int
    {
        $db = $this->dbc();
        $db->table('email_outbox')->insert(['template_code' => 't', 'recipient_email' => 'a@b.invalid', 'subject' => 's', 'body_html' => '<p>SECRET BODY</p>', 'body_text' => 'SECRET BODY', 'status' => $status, 'attempts' => 4, 'last_error' => 'smtp password=hunter2hunter2 refused', 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $db->insertID();
    }

    public function testListNormalisesStatusesAndFilters(): void
    {
        $this->sjob('queued'); $this->sjob('failed'); $this->sjob('completed'); $this->email('FAILED'); $this->email('SENT');
        $svc = new B();
        $this->assertSame(5, $svc->list(null, null)['total']);
        $this->assertSame(2, $svc->list(null, 'failed')['total']);
        $this->assertSame(1, $svc->list(null, 'pending')['total']);
        $this->assertSame(3, $svc->list('social', null)['total']);
        $this->assertSame(2, $svc->list('email', null)['total']);
        $this->assertSame(5, $svc->list(null, 'not-a-status')['total'], 'unknown status is ignored, not injected');
    }

    public function testPaginationIsServerSide(): void
    {
        for ($i = 0; $i < 7; $i++) { $this->sjob('completed'); }
        $r = (new B())->list(null, null, 2, 5);
        $this->assertCount(2, $r['rows']);
        $this->assertSame(7, $r['total']);
        $this->assertSame(2, $r['page']);
    }

    public function testSqlInjectionInFiltersIsHarmless(): void
    {
        $this->sjob('failed');
        $svc = new B();
        $this->assertSame(0, $svc->list("social' OR '1'='1", null)['total'], 'a malicious queue name matches no queue');
        $this->assertSame(1, $svc->list(null, "failed' OR '1'='1")['total'], 'a malicious status is ignored, never concatenated');
        $this->assertSame(1, $svc->list('social', 'failed')['total']);
        $this->assertTrue($this->dbc()->tableExists('social_scheduled_jobs'));
    }

    public function testRetryOnlyWorksOnceAndOnlyForFailedJobs(): void
    {
        $postId = $this->post('failed');
        $id = $this->sjob('failed', $postId);
        $svc = new B();
        $this->assertTrue($svc->retry('social', $id));
        $row = $this->dbc()->table('social_scheduled_jobs')->where('id', $id)->get()->getRowArray();
        $this->assertSame('queued', $row['status']);
        $this->assertSame('0', (string) $row['attempts']);
        $this->assertNull($row['locked_by']);
        $this->assertSame('scheduled', $this->dbc()->table('social_posts')->where('id', $postId)->get()->getRowArray()['status']);
        $this->assertFalse($svc->retry('social', $id), 'second retry must be rejected (already queued) — no duplicate execution');
    }

    public function testRetryRejectedForCompletedProcessingAndMissingJobs(): void
    {
        $svc = new B();
        $this->assertFalse($svc->retry('social', $this->sjob('completed')));
        $this->assertFalse($svc->retry('social', $this->sjob('processing')));
        $this->assertFalse($svc->retry('social', 99999999));
        $this->assertFalse($svc->retry('bogus', 1));
    }

    public function testRetryDoesNotRevivePublishedPost(): void
    {
        $postId = $this->post('published');
        $id = $this->sjob('failed', $postId);
        (new B())->retry('social', $id);
        $this->assertSame('published', $this->dbc()->table('social_posts')->where('id', $postId)->get()->getRowArray()['status'], 'only a failed post is moved back to scheduled');
    }

    public function testCancelOnlyWaitingSocialJobs(): void
    {
        $svc = new B();
        $queued = $this->sjob('queued');
        $this->assertTrue($svc->cancel('social', $queued));
        $this->assertSame('cancelled', $this->dbc()->table('social_scheduled_jobs')->where('id', $queued)->get()->getRowArray()['status']);
        $this->assertFalse($svc->cancel('social', $queued), 'already cancelled');
        $this->assertFalse($svc->cancel('social', $this->sjob('processing')), 'a job a worker already claimed cannot be cancelled');
        $this->assertFalse($svc->cancel('social', $this->sjob('completed')));
        $this->assertFalse($svc->cancel('email', $this->email('PENDING')), 'email cannot be cancelled without deleting data');
    }

    public function testEmailRetryRequeuesFailedOnce(): void
    {
        $id = $this->email('FAILED');
        $svc = new B();
        $this->assertTrue($svc->retry('email', $id));
        $this->assertSame('PENDING', $this->dbc()->table('email_outbox')->where('id', $id)->get()->getRowArray()['status']);
        $this->assertFalse($svc->retry('email', $id));
        $this->assertFalse($svc->retry('email', $this->email('SENT')));
    }

    public function testActionEligibilityHelpers(): void
    {
        $this->assertTrue(B::canRetry(['status' => 'failed', 'queue' => 'email']));
        $this->assertFalse(B::canRetry(['status' => 'pending', 'queue' => 'social']));
        $this->assertTrue(B::canCancel(['status' => 'pending', 'queue' => 'social']));
        $this->assertFalse(B::canCancel(['status' => 'pending', 'queue' => 'email']));
        $this->assertFalse(B::canCancel(['status' => 'processing', 'queue' => 'social']));
    }

    public function testErrorsAreSanitisedAndBodiesNeverReturned(): void
    {
        $this->sjob('failed', null, 'Graph API rejected: access_token=EAAB1234567890abcdefghijklmnopqrstuvwxyz and Bearer abcdefghijklmnopqrstuvwxyz0123456789 at C:\\xampp\\htdocs\\app\\x.php');
        $this->email('FAILED');
        $blob = json_encode((new B())->list(null, null)['rows']);
        foreach (['EAAB1234567890', 'abcdefghijklmnopqrstuvwxyz0123456789', 'C:\\\\xampp', 'hunter2hunter2', 'SECRET BODY'] as $needle) {
            $this->assertStringNotContainsString($needle, $blob);
        }
        $this->assertStringContainsString('[redacted]', $blob);
    }

    public function testDetailLookupAndAttemptSummariesAreSanitised(): void
    {
        $id = $this->sjob('failed');
        $this->dbc()->table('social_publish_attempts')->insert(['job_id' => $id, 'post_id' => 1, 'platform' => 'linkedin', 'attempt_number' => 1, 'started_at' => date('Y-m-d H:i:s'), 'status' => 'failed', 'error_message' => 'client_secret=ABCDEFGHIJKLMNOPQRSTUVWXYZ012345 bad', 'request_payload_sanitized' => '{"leak":"PAYLOAD-SENTINEL"}', 'created_at' => date('Y-m-d H:i:s')]);
        $svc = new B();
        $this->assertSame($id, (int) $svc->find('social', $id)['id']);
        $this->assertNull($svc->find('social', 99999999));
        $this->assertNull($svc->find('nope', 1));
        $blob = json_encode($svc->attempts($id));
        $this->assertStringNotContainsString('ABCDEFGHIJKLMNOPQRSTUVWXYZ012345', $blob);
        $this->assertStringNotContainsString('PAYLOAD-SENTINEL', $blob, 'raw payloads are never exposed');
    }

    // ── JobRunRecorder ───────────────────────────────────────────────────────
    public function testRecorderRecordsSuccessAndFailureAndRethrows(): void
    {
        $rec = new JobRunRecorder();
        $rec->run('unit:ok', static fn () => 7);
        $ok = $this->dbc()->table('scheduled_job_runs')->where('job_name', 'unit:ok')->get()->getRowArray();
        $this->assertSame('SUCCESS', $ok['status']);
        $this->assertSame('7', (string) $ok['items_processed']);
        $this->assertNotNull($ok['completed_at']);

        try {
            $rec->run('unit:bad', static function () { throw new \RuntimeException('boom password=hunter2hunter2'); });
            $this->fail('exception must propagate');
        } catch (\RuntimeException) {
            // expected
        }
        $bad = $this->dbc()->table('scheduled_job_runs')->where('job_name', 'unit:bad')->get()->getRowArray();
        $this->assertSame('FAILED', $bad['status']);
        $this->assertStringNotContainsString('hunter2hunter2', (string) $bad['error_details']);
    }

    public function testRecorderLeavesRunningRowWhenProcessDiesAndSanitizeCapsLength(): void
    {
        $id = (new JobRunRecorder())->start('unit:crash');
        $row = $this->dbc()->table('scheduled_job_runs')->where('id', $id)->get()->getRowArray();
        $this->assertSame('RUNNING', $row['status']);
        $this->assertNull($row['completed_at']);
        $this->assertLessThanOrEqual(500, strlen(JobRunRecorder::sanitize(str_repeat('x ', 1000))));
        $this->assertStringNotContainsString("\n", JobRunRecorder::sanitize("a\nb\r\nc"));
    }
}
