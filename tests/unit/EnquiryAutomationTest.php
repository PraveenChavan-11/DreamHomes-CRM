<?php

namespace Tests\Unit;

use App\Models\ApplicationModel;
use App\Models\CandidateModel;
use App\Models\ContactEnquiryModel;
use App\Models\EmployerEnquiryModel;
use App\Models\JobModel;
use App\Services\EnquiryAutomationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TalentHub Enquiry & Application Automation Unit Tests
 * Verifies automated emails on status transitions (Rejected, Shortlisted, Closed),
 * follow-up datelines, and reopening workflows.
 *
 * @internal
 */
final class EnquiryAutomationTest extends CIUnitTestCase
{
    private EnquiryAutomationService $automationService;
    private ApplicationModel $applicationModel;
    private CandidateModel $candidateModel;
    private JobModel $jobModel;
    private EmployerEnquiryModel $employerModel;
    private ContactEnquiryModel $contactModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->automationService = new EnquiryAutomationService();
        $this->applicationModel  = new ApplicationModel();
        $this->candidateModel    = new CandidateModel();
        $this->jobModel          = new JobModel();
        $this->employerModel     = new EmployerEnquiryModel();
        $this->contactModel      = new ContactEnquiryModel();
    }

    /**
     * 1. Test Candidate Rejection: Automatic Polite Rejection Email & Talent Pool Retention
     */
    public function testCandidateRejectionAutomailAndDateline(): void
    {
        $testEmail = 'cand_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        // 1. Fetch valid existing job
        $job = $this->jobModel->first();
        $this->assertNotNull($job, 'Database must have at least one job record');
        $jobId = (int) $job['id'];

        // 2. Create test candidate
        $candidateId = (int) $this->candidateModel->insert([
            'full_name'              => 'Vikram Deshmukh',
            'email'                  => $testEmail,
            'phone'                  => '+919822011223',
            'total_experience_years' => 5.5,
            'skills'                 => 'PHP, CodeIgniter, MySQL',
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        // 3. Create active application with existing document_id = 1
        $appId = (int) $this->applicationModel->insert([
            'candidate_id'    => $candidateId,
            'job_id'          => $jobId,
            'document_id'     => (int) (\Config\Database::connect()->table('candidate_documents')->select('id')->get()->getRowArray()['id'] ?? 13),
            'status'          => 'SCREENING',
            'recruiter_notes' => 'Candidate passed screening, reviewing technical fit',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->assertGreaterThan(0, $appId);

        // 4. Update status to REJECTED with recruiter note and follow-up dateline
        $result = $this->automationService->updateApplicationStatus(
            $appId,
            'REJECTED',
            'Position requires 7+ years of microservices experience. Profile retained in talent pool.',
            date('Y-m-d H:i:s', strtotime('+30 days')),
            1
        );

        $this->assertTrue($result['success']);
        $this->assertSame('REJECTED', $result['new_status']);
        $this->assertTrue($result['email_dispatched']);

        // Verify database updates
        $updatedApp = $this->applicationModel->find($appId);
        $this->assertSame('REJECTED', $updatedApp['status']);
        $this->assertNotNull($updatedApp['closed_at']);
        $this->assertNotNull($updatedApp['follow_up_date']);

        // Verify outbox entry
        $db = \Config\Database::connect();
        $outbox = $db->table('email_outbox')
                     ->where('recipient_email', $testEmail)
                     ->where('template_code', 'app_candidate_rejected')
                     ->orderBy('id', 'DESC')
                     ->get()
                     ->getRowArray();

        $this->assertNotNull($outbox, 'Automated rejection email must be queued in outbox');
        $this->assertStringContainsString('Vikram Deshmukh', $outbox['body_html']);
        $this->assertStringContainsString('Executive Talent Repository', $outbox['body_html']);

        // Clean up
        $this->applicationModel->delete($appId, true);
        $this->candidateModel->delete($candidateId, true);
        if ($outbox) {
            $db->table('email_outbox')->delete(['id' => $outbox['id']]);
        }
    }

    /**
     * 2. Test Application Reopening Workflow
     */
    public function testApplicationReopeningWithDateline(): void
    {
        $job = $this->jobModel->first();
        $this->assertNotNull($job);
        $jobId = (int) $job['id'];

        $candidateId = (int) $this->candidateModel->insert([
            'full_name'              => 'Snehal Kulkarni',
            'email'                  => 'snehal_' . bin2hex(random_bytes(4)) . '@talenthub-test.com',
            'phone'                  => '+919822099887',
            'total_experience_years' => 4.0,
            'created_at'             => date('Y-m-d H:i:s'),
        ]);

        $appId = (int) $this->applicationModel->insert([
            'candidate_id'    => $candidateId,
            'job_id'          => $jobId,
            'document_id'     => (int) (\Config\Database::connect()->table('candidate_documents')->select('id')->get()->getRowArray()['id'] ?? 13),
            'status'          => 'REJECTED',
            'closed_at'       => date('Y-m-d H:i:s', strtotime('-10 days')),
            'recruiter_notes' => 'Initially rejected due to budget constraints',
            'created_at'      => date('Y-m-d H:i:s', strtotime('-15 days')),
        ]);

        // Reopen application to SCREENING with new follow-up dateline
        $reopenResult = $this->automationService->updateApplicationStatus(
            $appId,
            'SCREENING',
            'Reopening mandate: client expanded compensation budget',
            date('Y-m-d H:i:s', strtotime('+3 days')),
            1
        );

        $this->assertTrue($reopenResult['success']);
        $this->assertTrue($reopenResult['is_reopened']);
        $this->assertSame('SCREENING', $reopenResult['new_status']);

        $updatedApp = $this->applicationModel->find($appId);
        $this->assertNotNull($updatedApp['reopened_at']);
        $this->assertNull($updatedApp['closed_at']);
        $this->assertSame('SCREENING', $updatedApp['status']);

        // Clean up
        $this->applicationModel->delete($appId, true);
        $this->candidateModel->delete($candidateId, true);
    }

    /**
     * 3. Test Employer Mandate Status Change to CLOSED_LOST & Automail
     */
    public function testEmployerMandateClosureAutomail(): void
    {
        $testEmail = 'hiring_' . bin2hex(random_bytes(4)) . '@talenthub-client.com';

        $empId = (int) $this->employerModel->insert([
            'company_name'   => 'Apex Cloud Solutions Pune',
            'contact_person' => 'Amitabh Saxena',
            'email'          => $testEmail,
            'phone'          => '+912027400000',
            'required_roles' => 'DevOps Architect',
            'status'         => 'IN_DISCUSSION',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $result = $this->automationService->updateEmployerStatus(
            $empId,
            'CLOSED_LOST',
            'Client decided to put hiring on hold until Q2',
            date('Y-m-d H:i:s', strtotime('+60 days')),
            1
        );

        $this->assertTrue($result['success']);
        $this->assertSame('CLOSED_LOST', $result['new_status']);
        $this->assertTrue($result['email_dispatched']);

        $updated = $this->employerModel->find($empId);
        $this->assertNotNull($updated['closed_at']);

        // Verify outbox
        $db = \Config\Database::connect();
        $outbox = $db->table('email_outbox')
                     ->where('recipient_email', $testEmail)
                     ->where('template_code', 'employer_mandate_closed')
                     ->orderBy('id', 'DESC')
                     ->get()
                     ->getRowArray();

        $this->assertNotNull($outbox);
        $this->assertStringContainsString('Amitabh Saxena', $outbox['body_html']);

        // Clean up
        $this->employerModel->delete($empId, true);
        if ($outbox) {
            $db->table('email_outbox')->delete(['id' => $outbox['id']]);
        }
    }

    /**
     * 4. Test Overdue Follow-ups Monitoring
     */
    public function testOverdueFollowUpsRetrieval(): void
    {
        // Insert an overdue enquiry
        $empId = (int) $this->employerModel->insert([
            'company_name'    => 'Quick Mandate Test',
            'contact_person'  => 'Pooja',
            'email'           => 'pooja@test.com',
            'status'          => 'NEW',
            'follow_up_date'  => date('Y-m-d H:i:s', strtotime('-2 days')), // Overdue
            'follow_up_notes' => 'Call hiring manager to confirm role description',
            'created_at'      => date('Y-m-d H:i:s', strtotime('-5 days')),
        ]);

        $overdue = $this->automationService->getOverdueFollowUps();
        $this->assertIsArray($overdue);
        $this->assertGreaterThanOrEqual(1, $overdue['total']);

        // Clean up
        $this->employerModel->delete($empId, true);
    }

    /**
     * 5. Test Job Alert Broadcast to Subscribers
     */
    public function testJobAlertBroadcastToSubscribers(): void
    {
        $db = \Config\Database::connect();
        $testEmail = 'sub_job_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        $db->table('newsletter_subscribers')->insert([
            'email'                  => $testEmail,
            'first_name'             => 'Pooja Jobseeker',
            'status'                 => 'subscribed',
            'verified_at'            => date('Y-m-d H:i:s'),
            'consent_given'          => 1,
            'unsubscribe_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'created_at'             => date('Y-m-d H:i:s'),
        ]);
        $subId = (int) $db->insertID();

        $job = $this->jobModel->first();
        $this->assertNotNull($job);

        $result = $this->automationService->broadcastNewJobAlert($job);
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['queued']);

        $outbox = $db->table('email_outbox')
            ->where('recipient_email', $testEmail)
            ->where('idempotency_key', "job_alert_{$job['id']}_{$subId}")
            ->get()
            ->getRowArray();

        $this->assertNotNull($outbox);
        $this->assertStringContainsString($job['title'], $outbox['subject']);

        // Clean up
        $db->table('newsletter_subscribers')->delete(['id' => $subId]);
        if ($outbox) {
            $db->table('email_outbox')->delete(['id' => $outbox['id']]);
        }
    }

    /**
     * 6. Test 1-Click Direct Notice Broadcast
     */
    public function testDirectNoticeBroadcast(): void
    {
        $db = \Config\Database::connect();
        $testEmail = 'sub_notice_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        $db->table('newsletter_subscribers')->insert([
            'email'                  => $testEmail,
            'first_name'             => 'Gaurav Member',
            'status'                 => 'subscribed',
            'verified_at'            => date('Y-m-d H:i:s'),
            'consent_given'          => 1,
            'unsubscribe_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'created_at'             => date('Y-m-d H:i:s'),
        ]);
        $subId = (int) $db->insertID();

        $result = $this->automationService->sendDirectNotice([
            'subject'        => 'Special Test Broadcast: Pune Drive',
            'headline'       => 'Urgent Pune Engineering Recruitment Notice',
            'content'        => 'This is a test notification message broadcast to all subscribed members.',
            'priority_badge' => 'Urgent Announcement',
            'cta_text'       => 'View Opportunities',
            'cta_url'        => 'https://talenthubconsultant.com/jobs',
            'audience'       => 'all',
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['queued']);

        $outbox = $db->table('email_outbox')
            ->where('recipient_email', $testEmail)
            ->where('subject', 'Special Test Broadcast: Pune Drive')
            ->get()
            ->getRowArray();

        $this->assertNotNull($outbox);
        $this->assertStringContainsString('Urgent Pune Engineering Recruitment Notice', $outbox['body_html']);

        // Clean up
        $db->table('newsletter_subscribers')->delete(['id' => $subId]);
        if ($outbox) {
            $db->table('email_outbox')->delete(['id' => $outbox['id']]);
        }
    }

    /**
     * 7. Test Candidate Interview Reminder
     */
    public function testCandidateInterviewReminder(): void
    {
        $job = $this->jobModel->first();
        $candidateId = (int) $this->candidateModel->insert([
            'full_name'  => 'Sunita Patil',
            'email'      => 'sunita_' . bin2hex(random_bytes(3)) . '@talenthub-test.com',
            'phone'      => '+919876543210',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $appId = (int) $this->applicationModel->insert([
            'candidate_id' => $candidateId,
            'job_id'       => $job['id'],
            'document_id'  => (int) (\Config\Database::connect()->table('candidate_documents')->select('id')->get()->getRowArray()['id'] ?? 13),
            'status'       => 'SHORTLISTED',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $result = $this->automationService->sendCandidateInterviewReminder($appId, [
            'company_name'       => 'Tata Technologies',
            'interview_date'     => 'Tomorrow at 11:00 AM IST',
            'interview_mode'     => 'Microsoft Teams',
            'interview_location' => 'https://teams.microsoft.com/meet/123',
            'notes'              => 'Focus on React and CI4 REST APIs',
        ]);

        $this->assertTrue($result['success']);

        $db = \Config\Database::connect();
        $outbox = $db->table('email_outbox')
            ->where('recipient_email', 'sunita_' . substr($this->candidateModel->find($candidateId)['email'], 7))
            ->like('idempotency_key', "interview_reminder_{$appId}_")
            ->get()
            ->getRowArray();

        // Clean up
        $this->applicationModel->delete($appId, true);
        $this->candidateModel->delete($candidateId, true);
        if ($outbox) {
            $db->table('email_outbox')->delete(['id' => $outbox['id']]);
        }
    }

    /**
     * 8. Test Unsubscribe and Resubscribe by Direct Email Address
     */
    public function testUnsubscribeAndResubscribeByEmail(): void
    {
        $newsletterService = new \App\Services\NewsletterService();
        $testEmail = 'unsub_test_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        // 1. Subscribe user
        $subResult = $newsletterService->subscribe($testEmail, 'Suresh', true, 'test_suite');
        $this->assertTrue($subResult['success']);

        // 2. Unsubscribe via direct email with reason
        $unsubResult = $newsletterService->unsubscribe(null, 'Content no longer relevant', $testEmail);
        $this->assertTrue($unsubResult['success']);
        $this->assertSame('UNSUBSCRIBED', $unsubResult['code']);

        // Verify status
        $subModel = new \App\Models\NewsletterSubscriberModel();
        $subscriber = $subModel->findByEmail($testEmail);
        $this->assertSame('unsubscribed', $subscriber['status']);
        $this->assertNotNull($subscriber['unsubscribed_at']);

        // 3. Resubscribe via email
        $resubResult = $newsletterService->resubscribe(null, $testEmail);
        $this->assertTrue($resubResult['success']);
        $this->assertSame('RESUBSCRIBED', $resubResult['code']);

        $subscriberAfter = $subModel->findByEmail($testEmail);
        $this->assertSame('subscribed', $subscriberAfter['status']);

        // Clean up
        $subModel->delete($subscriber['id'], true);
    }
}
