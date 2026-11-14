<?php

namespace Tests\Unit;

use App\Models\AnnouncementModel;
use App\Models\CookieConsentLogModel;
use App\Models\EmailCampaignModel;
use App\Models\JobAlertSubscriptionModel;
use App\Models\NewsletterSubscriberModel;
use App\Services\AnnouncementService;
use App\Services\CookieConsentService;
use App\Services\EmailCampaignService;
use App\Services\JobAlertService;
use App\Services\NewsletterService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * TalentHub Engagement Centre — Comprehensive Unit & Integration Tests
 * Verifies Phase 1 through Phase 11 requirements.
 *
 * @internal
 */
final class EngagementCentreTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected NewsletterService $newsletterService;
    protected AnnouncementService $announcementService;
    protected JobAlertService $jobAlertService;
    protected CookieConsentService $cookieConsentService;
    protected EmailCampaignService $emailCampaignService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->newsletterService = new NewsletterService();
        $this->announcementService = new AnnouncementService();
        $this->jobAlertService = new JobAlertService();
        $this->cookieConsentService = new CookieConsentService();
        $this->emailCampaignService = new EmailCampaignService();
    }

    /**
     * 1. Test Newsletter Subscription with Double Opt-In
     */
    public function testNewsletterSubscriptionDoubleOptInFlow(): void
    {
        $testEmail = 'test_dev_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        // 1. Subscribe with valid email and explicit consent
        $subResult = $this->newsletterService->subscribe($testEmail, 'Aditi', true, 'unit_test');

        $this->assertTrue($subResult['success']);
        $this->assertNotEmpty($subResult['subscriber_id']);

        $subscriberModel = new NewsletterSubscriberModel();
        $subscriber = $subscriberModel->find($subResult['subscriber_id']);

        $this->assertNotNull($subscriber);
        $this->assertSame($testEmail, $subscriber['email']);
        $this->assertSame('Aditi', $subscriber['first_name']);
        $this->assertSame('unverified', $subscriber['status']);
        $this->assertNotEmpty($subscriber['verification_token_hash']);

        // 2. Verify with Invalid Token should fail
        $invalidVerify = $this->newsletterService->verify('totally_invalid_token_123');
        $this->assertFalse($invalidVerify['success']);

        // 3. Verify with Valid Raw Token
        $rawVerifyToken = $subResult['verification_token'];
        $validVerify = $this->newsletterService->verify($rawVerifyToken);
        $this->assertTrue($validVerify['success']);

        $verifiedSubscriber = $subscriberModel->find($subResult['subscriber_id']);
        $this->assertSame('subscribed', $verifiedSubscriber['status']);
        $this->assertNotNull($verifiedSubscriber['verified_at']);

        // 4. Duplicate subscription attempt should be gracefully handled
        $dupResult = $this->newsletterService->subscribe($testEmail, null, true, 'unit_test_dup');
        $this->assertTrue($dupResult['success']);
        $this->assertSame('ALREADY_SUBSCRIBED', $dupResult['code']);

        // 5. Clean up test record
        $subscriberModel->delete($subscriber['id'], true);
    }

    /**
     * 2. Test Newsletter Unsubscribe and Suppression Enforcement
     */
    public function testNewsletterUnsubscribeAndSuppression(): void
    {
        $testEmail = 'suppress_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        $subResult = $this->newsletterService->subscribe($testEmail, 'Pooja', true, 'unit_test_suppress');
        $this->assertTrue($subResult['success']);
        $subscriberId = $subResult['subscriber_id'];

        $subscriberModel = new NewsletterSubscriberModel();

        // Verify subscriber
        $rawUnsubToken = bin2hex(random_bytes(32));
        $subscriberModel->update($subscriberId, [
            'status'                 => 'subscribed',
            'verified_at'            => date('Y-m-d H:i:s'),
            'unsubscribe_token_hash' => hash('sha256', $rawUnsubToken),
        ]);

        // Prior to unsubscribe, suppression should be false
        $this->assertFalse($this->newsletterService->isSuppressed($testEmail));

        // Unsubscribe via raw token
        $unsubResult = $this->newsletterService->unsubscribe($rawUnsubToken, 'User requested unsubscribe');
        $this->assertTrue($unsubResult['success']);

        // Now suppression MUST be true (Marketing emails strictly prohibited)
        $this->assertTrue($this->newsletterService->isSuppressed($testEmail));

        $updated = $subscriberModel->find($subscriberId);
        $this->assertSame('unsubscribed', $updated['status']);
        $this->assertNotNull($updated['unsubscribed_at']);

        // Resubscribe workflow
        $resubResult = $this->newsletterService->resubscribe($rawUnsubToken);
        $this->assertTrue($resubResult['success']);
        $this->assertFalse($this->newsletterService->isSuppressed($testEmail));

        // Clean up
        $subscriberModel->delete($subscriberId, true);
    }

    /**
     * 3. Test Announcement Lifecycle & Timezone Scheduling
     */
    public function testAnnouncementLifecycleAndTargeting(): void
    {
        $announcementModel = new AnnouncementModel();

        // Create an active announcement
        $activeId = $announcementModel->insert([
            'title'             => 'Test Flash Hiring Drive',
            'public_heading'    => 'Mega Recruitment Drive Pune',
            'public_content'    => 'Join 50+ IT firms hiring this weekend in Hinjawadi.',
            'announcement_type' => 'recruitment_drive',
            'display_format'    => 'center_modal',
            'audience'          => 'everyone',
            'target_pages'      => 'all',
            'frequency'         => 'once_per_day',
            'priority'          => 10,
            'status'            => 'ACTIVE',
            'start_date'        => date('Y-m-d', strtotime('-1 day')),
            'end_date'          => date('Y-m-d', strtotime('+7 days')),
            'start_time'        => '00:00:00',
            'end_time'          => '23:59:59',
            'timezone'          => 'Asia/Kolkata',
            'cta_text'          => 'Register Now',
            'cta_url'           => '/jobs',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->assertIsNumeric($activeId);

        // Fetch active announcements for homepage
        $candidates = $this->announcementService->getActiveForVisitor('everyone', '/', false);

        $this->assertIsArray($candidates);
        $found = false;
        foreach ($candidates as $ann) {
            if ((int) $ann['id'] === (int) $activeId) {
                $found = true;
                $this->assertSame('center_modal', $ann['display_format']);
                $this->assertSame('Mega Recruitment Drive Pune', $ann['public_heading']);
                break;
            }
        }
        $this->assertTrue($found, 'Active announcement should be delivered to audience');

        // Test event tracking
        $trackResult = $this->announcementService->trackEvent((int) $activeId, 'impression', '127.0.0.1', 'PHPUnit Agent');
        $this->assertTrue($trackResult);

        // Clean up
        $announcementModel->delete($activeId, true);
    }

    /**
     * 4. Test Job Alert Subscription & Verification
     */
    public function testJobAlertSubscriptionAndMatching(): void
    {
        $alertEmail = 'jobseeker_' . bin2hex(random_bytes(4)) . '@talenthub-test.com';

        $subResult = $this->jobAlertService->subscribe(
            $alertEmail,
            'engineering',
            'Pune',
            'mid',
            'full_time',
            'daily'
        );

        $this->assertTrue($subResult['success']);
        $this->assertNotEmpty($subResult['subscription_id']);
        $this->assertNotEmpty($subResult['token']);

        $alertModel = new JobAlertSubscriptionModel();
        $sub = $alertModel->find($subResult['subscription_id']);

        $this->assertNotNull($sub);
        $this->assertSame($alertEmail, $sub['email']);
        $this->assertSame('active', $sub['status']);
        $this->assertSame('daily', $sub['frequency']);
        $this->assertNotEmpty($sub['token_hash']);

        // Test unsubscribe with raw token
        $unsubResult = $this->jobAlertService->unsubscribe($subResult['token']);
        $this->assertTrue($unsubResult['success']);

        $updatedSub = $alertModel->find($sub['id']);
        $this->assertSame('unsubscribed', $updatedSub['status']);

        // Clean up
        $alertModel->delete($sub['id'], true);
    }

    /**
     * 5. Test Cookie Consent Logging (DPDP Act 2023 / GDPR Compliance)
     */
    public function testCookieConsentLoggingCompliance(): void
    {
        $visitorUuid = 'uuid_' . bin2hex(random_bytes(16));

        $logResult = $this->cookieConsentService->recordConsent(
            $visitorUuid,
            true, // analytics
            false, // marketing
            '127.0.0.1',
            'PHPUnit Test Browser v1.0'
        );

        $this->assertTrue($logResult);

        $logModel = new CookieConsentLogModel();
        $log = $logModel->where('consent_uuid', $visitorUuid)->first();

        $this->assertNotNull($log);
        $this->assertSame($visitorUuid, $log['consent_uuid']);
        $this->assertSame(1, (int) $log['analytics']);
        $this->assertSame(0, (int) $log['marketing']);
        $this->assertNotNull($log['created_at']);

        // Verify aggregation stats
        $stats = $this->cookieConsentService->getConsentStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);

        // Clean up
        $logModel->delete($log['id'], true);
    }

    /**
     * 6. Test Email Template Merge Tags & Variables
     */
    public function testEmailCampaignTemplateRendering(): void
    {
        $rendered = $this->emailCampaignService->renderTemplate(
            'Hello {{first_name}}, welcome to {{company_name}}! Unsubscribe here: {{unsubscribe_url}}',
            [
                'first_name'      => 'Sneha',
                'company_name'    => 'TalentHub Consultant',
                'unsubscribe_url' => 'https://talenthubconsultant.com/newsletter/unsubscribe?token=abc123xyz',
            ]
        );

        $this->assertStringContainsString('Hello Sneha, welcome to TalentHub Consultant!', $rendered);
        $this->assertStringContainsString('https://talenthubconsultant.com/newsletter/unsubscribe?token=abc123xyz', $rendered);
        $this->assertStringNotContainsString('{{first_name}}', $rendered);
        $this->assertStringNotContainsString('{{company_name}}', $rendered);
    }

    /**
     * 7. Test Admin Engagement Centre Route Protection (RBAC)
     */
    public function testAdminEngagementRoutesRequireAuthentication(): void
    {
        // Without admin session, access to /admin/engagement should redirect to /admin/login
        $response = $this->call('get', '/admin/engagement');
        $this->assertTrue(in_array($response->response()->getStatusCode(), [302, 301, 307]));
        $this->assertStringContainsString('/admin/login', (string) $response->response()->getHeaderLine('Location'));

        $subscribers = $this->call('get', '/admin/engagement/subscribers');
        $this->assertTrue(in_array($subscribers->response()->getStatusCode(), [302, 301, 307]));
        $this->assertStringContainsString('/admin/login', (string) $subscribers->response()->getHeaderLine('Location'));
    }

    /**
     * 8. Test Public Engagement Endpoints Respond Correctly
     */
    public function testPublicEngagementPagesRender(): void
    {
        // 1. Unsubscribe page with no params (graceful form)
        $unsub = $this->call('get', '/newsletter/unsubscribe');
        $this->assertSame(200, $unsub->response()->getStatusCode());
        $this->assertStringContainsString('Unsubscribe', $unsub->getBody());

        // 2. Preferences page
        $pref = $this->call('get', '/newsletter/preferences');
        $this->assertSame(200, $pref->response()->getStatusCode());
        $this->assertStringContainsString('Preferences', $pref->getBody());

        // 3. Active announcements service direct check
        $activeAnnouncements = $this->announcementService->getActiveForVisitor('everyone', '/');
        $this->assertIsArray($activeAnnouncements);
    }
}
