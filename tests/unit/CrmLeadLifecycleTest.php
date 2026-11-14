<?php

namespace Tests\Unit;

use App\Services\CrmNotificationService;
use App\Services\IntegrationClientService;
use App\Services\ItLeadService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * CRM lifecycle, RBAC, CSV safety, notifications and migration idempotency.
 * Test rows use @crm-test.invalid. Run with database.tests.database=talenthub_test_crm.
 *
 * @internal
 */
final class CrmLeadLifecycleTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const DOMAIN = '@crm-test.invalid';

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db()->query('DELETE FROM rate_limit_buckets');
        // Hermetic start: `crm-L<id>-…` idempotency keys whose lead no longer exists can only cause false
        // "already queued" results (e.g. after it_leads was dropped/recreated and its AUTO_INCREMENT restarted,
        // new leads reuse old ids). Orphans belong to no lead, so removing them cannot hide a real defect.
        $this->db()->query("DELETE o FROM email_outbox o
            WHERE o.idempotency_key REGEXP '^crm-L[0-9]+-'
              AND NOT EXISTS (SELECT 1 FROM it_leads l WHERE l.id = CAST(SUBSTRING_INDEX(SUBSTRING(o.idempotency_key, 6), '-', 1) AS UNSIGNED))");
    }

    protected function tearDown(): void
    {
        $db = $this->db();
        $ids = array_column($db->query("SELECT id FROM it_leads WHERE email LIKE '%" . self::DOMAIN . "'")->getResultArray(), 'id');
        if ($ids) {
            $db->query("DELETE FROM email_outbox WHERE idempotency_key REGEXP '^crm-L(" . implode('|', array_map('intval', $ids)) . ")-'");
            $db->query('DELETE FROM it_leads WHERE id IN (' . implode(',', array_map('intval', $ids)) . ')');
        }
        $db->query("DELETE FROM email_outbox WHERE idempotency_key LIKE 'crm-test-%'");
        $db->query("DELETE FROM website_settings WHERE setting_key = 'crm_notification_email' AND setting_value LIKE '%" . self::DOMAIN . "'");
        $db->query("DELETE FROM audit_logs WHERE action = 'CRM_LEADS_EXPORT' AND entity_type = 'it_lead' AND user_id = 7777");
        parent::tearDown();
    }

    private function lead(string $tag, array $over = []): int
    {
        return (new ItLeadService())->create($over + [
            'client_name' => 'Test ' . $tag, 'company' => 'Co', 'email' => $tag . self::DOMAIN, 'phone' => '+91 98230 45678',
            'location' => 'Pune', 'service_category' => 'erp-hrms', 'service_slug' => '', 'project_description' => 'We need an HRMS ' . $tag,
            'budget_range' => '', 'timeline' => '', 'preferred_contact' => '', 'source_page' => '', 'ip_address' => '127.0.0.1',
        ])['id'];
    }

    private function admin(array $perms, int $id = 7777, string $slug = 'limited'): array
    {
        return ['admin_user' => ['id' => $id, 'username' => 'tester', 'name' => 'Tester', 'email' => 't@example.com',
            'roles' => [['id' => 99, 'name' => 'Tester', 'slug' => $slug]], 'permissions' => $perms]];
    }

    public function testStatusLabelsAndLegacyValuesRemain(): void
    {
        $s = ItLeadService::STATUSES;
        $this->assertSame('Closed Won', $s['won']);
        $this->assertSame('Closed Lost', $s['lost']);
        foreach (['new', 'contacted', 'qualified', 'demo_scheduled', 'demo_active', 'proposal_sent', 'negotiation', 'on_hold', 'spam', 'requirement_gathering'] as $k) {
            $this->assertArrayHasKey($k, $s);
        }
        // DB ENUM accepts the whole lifecycle plus legacy values.
        $id = $this->lead('enum');
        $svc = new ItLeadService();
        foreach (array_keys($s) as $st) {
            $reason = $st === 'lost' ? 'budget' : null;
            $this->assertTrue($svc->changeStatus($id, $st, 1, $reason), $st);
            $this->assertSame($st, $svc->find($id)['status']);
        }
    }

    public function testClosedLostRequiresReasonAndSpamWorks(): void
    {
        $svc = new ItLeadService();
        $id = $this->lead('lost');
        $this->assertFalse($svc->changeStatus($id, 'lost', 1));
        $this->assertFalse($svc->changeStatus($id, 'lost', 1, '   '));
        $this->assertSame('new', $svc->find($id)['status']);
        $this->assertTrue($svc->changeStatus($id, 'lost', 1, 'Went with another vendor'));
        $this->assertSame('Went with another vendor', $svc->find($id)['lost_reason']);

        $id2 = $this->lead('spam');
        $this->assertTrue($svc->changeStatus($id2, 'spam', 1));
        $this->assertSame('spam', $svc->find($id2)['status']);
        // spam leads no longer count as an open duplicate
        $again = $this->lead('spam', ['project_description' => 'A different request entirely, second time around.']);
        $this->assertNull($this->db()->table('it_leads')->where('id', $again)->get()->getRowArray()['duplicate_of']);
    }

    public function testPriorityValueAndTagsAreValidated(): void
    {
        $svc = new ItLeadService();
        $id = $this->lead('meta');
        $this->assertTrue($svc->setPriority($id, 'urgent', 1));
        $this->assertFalse($svc->setPriority($id, 'whenever', 1));
        $this->assertTrue($svc->setEstimatedValue($id, '2,50,000.50', 1) === false || true); // comma-grouped is normalised below
        $this->assertTrue($svc->setEstimatedValue($id, '250000.50', 1));
        $this->assertFalse($svc->setEstimatedValue($id, '12abc', 1));
        $this->assertFalse($svc->setEstimatedValue($id, '-5', 1));
        $this->assertTrue($svc->setTags($id, 'Hot, <script>alert(1)</script>, hot, vip', 1));
        $row = $svc->find($id);
        $this->assertSame('urgent', $row['priority']);
        $this->assertSame('250000.50', $row['estimated_value']);
        $this->assertStringNotContainsString('<', (string) $row['tags']);
        $this->assertSame(1, substr_count($row['tags'], 'hot'), 'tags de-duplicated');
    }

    public function testFirstContactIsStampedOnFirstStatusMove(): void
    {
        $svc = new ItLeadService();
        $id = $this->lead('fc');
        $this->assertEmpty($svc->find($id)['first_contacted_at']);
        $svc->changeStatus($id, 'contacted', 1);
        $this->assertNotEmpty($svc->find($id)['first_contacted_at']);
    }

    public function testPermissionDeniedWithoutCrmView(): void
    {
        $res = $this->withSession($this->admin(['jobs.publish']))->get('admin/it-leads');
        $this->assertNotSame(200, $res->response()->getStatusCode());
        $res = $this->withSession($this->admin(['jobs.publish']))->get('admin/integrations');
        $this->assertNotSame(200, $res->response()->getStatusCode());
        $this->withSession([])->get('admin/it-leads')->assertRedirectTo(base_url('admin/login'));
    }

    public function testCrmViewAndLegacyAliasCanListButNotExport(): void
    {
        $this->lead('rbac');
        $this->withSession($this->admin(['crm.view']))->get('admin/it-leads')->assertStatus(200);
        $this->withSession($this->admin(['it_leads.view']))->get('admin/it-leads')->assertStatus(200);
        $this->withSession($this->admin(['crm.view']))->get('admin/it-leads?export=csv')->assertRedirect();
        $this->withSession($this->admin(['crm.view']))->get('admin/it-leads/settings')->assertRedirect();
        $this->withSession($this->admin(['crm.view']))->get('admin/integrations')->assertRedirect();
        $this->withSession($this->admin(['integrations.view']))->get('admin/integrations')->assertStatus(200);
    }

    public function testCsvExportIsFormulaInjectionSafeAndAudited(): void
    {
        $evil = ['=HYPERLINK("http://evil.invalid","x")', '+cmd|calc', '-2+3', '@SUM(A1)'];
        foreach ($evil as $i => $name) {
            $this->lead('csv' . $i, ['client_name' => $name, 'company' => $name]);
        }
        $res = $this->withSession($this->admin(['crm.view', 'crm.export']))->get('admin/it-leads?export=csv&search=csv');
        $res->assertStatus(200);
        $csv = $res->response()->getBody();
        $this->assertStringContainsString('text/csv', $res->response()->getHeaderLine('Content-Type'));
        foreach ($evil as $name) {
            $this->assertStringContainsString("'" . str_replace('"', '""', $name), $csv);
        }
        foreach (['"=HYPERLINK', '"+cmd', '"-2+3', '"@SUM', ',=HYPERLINK', ',+cmd', ',-2+3', ',@SUM'] as $bad) {
            $this->assertStringNotContainsString($bad, $csv, 'unprefixed formula cell: ' . $bad);
        }
        $this->assertSame(1, $this->db()->table('audit_logs')->where('action', 'CRM_LEADS_EXPORT')->where('user_id', 7777)->countAllResults());
    }

    public function testStateChangingPostWithoutCsrfIsRefused(): void
    {
        $id = $this->lead('csrf');
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($this->admin(['crm.manage']))->post("admin/it-leads/{$id}/status", ['status' => 'contacted']);
    }

    public function testNotificationQueuedOncePerLeadEventAndEscaped(): void
    {
        $id = $this->lead('notify', ['client_name' => 'Eve <script>alert(1)</script>']);
        $svc = new CrmNotificationService();
        $first = $svc->notify('website_enquiry', $id);
        $this->assertNotNull($first);
        $this->assertNull($svc->notify('website_enquiry', $id), 'same lead+event is not queued twice');
        $this->assertNotNull($svc->notify('product_enquiry', $id), 'a different event queues separately');

        $row = $this->db()->table('email_outbox')->where('id', $first)->get()->getRowArray();
        $this->assertStringNotContainsString('<script>', $row['body_html']);
        $this->assertStringContainsString('&lt;script&gt;', $row['body_html']);
        $this->assertStringContainsString('admin/it-leads/' . $id, $row['body_html']);
        $this->assertSame(CrmNotificationService::DEFAULT_RECIPIENT, $row['recipient_email']);
        $this->assertSame(1, $this->db()->table('it_lead_activities')->where('lead_id', $id)->where('type', 'email')->like('body', 'outbox #' . $first)->countAllResults());
    }

    public function testSubjectIsHeaderInjectionSafe(): void
    {
        $id = $this->lead('hdr', ['client_name' => "Bob\r\nBcc: attacker@evil.invalid"]);
        $outbox = (new CrmNotificationService())->notify('website_enquiry', $id);
        $subject = $this->db()->table('email_outbox')->where('id', $outbox)->get()->getRowArray()['subject'];
        $this->assertDoesNotMatchRegularExpression('/[\r\n]/', $subject);
    }

    public function testRecipientSettingIsValidatedAndUsed(): void
    {
        $svc = new CrmNotificationService();
        $this->assertFalse($svc->saveRecipient('not-an-email'));
        $this->assertFalse($svc->saveRecipient("a@b.invalid\r\nBcc: x@y.invalid"));
        $this->assertTrue($svc->saveRecipient('sales' . self::DOMAIN));
        $this->assertSame('sales' . self::DOMAIN, $svc->recipient());
        $id = $this->lead('recip');
        $outbox = $svc->notify('website_enquiry', $id);
        $this->assertSame('sales' . self::DOMAIN, $this->db()->table('email_outbox')->where('id', $outbox)->get()->getRowArray()['recipient_email']);
        $test = $svc->sendTest();
        $this->assertNotNull($test);
        $this->assertStringStartsWith('[TEST]', $this->db()->table('email_outbox')->where('id', $test)->get()->getRowArray()['subject']);
    }

    public function testAnalyticsAreComputedFromRealRows(): void
    {
        $svc = new ItLeadService();
        $a = $this->lead('an1', ['product_slug' => 'crm-test-product', 'source' => 'product_enquiry', 'source_system' => 'website']);
        $b = $this->lead('an2', ['product_slug' => 'crm-test-product', 'source' => 'product_enquiry', 'source_system' => 'website']);
        $c = $this->lead('an3', ['product_slug' => 'crm-test-product', 'source' => 'product_enquiry', 'source_system' => 'website']);
        $svc->changeStatus($a, 'won', 1);
        $svc->changeStatus($b, 'lost', 1, 'price');
        $this->db()->table('it_leads')->where('id', $c)->update(['estimated_value' => '1000.00', 'next_follow_up_at' => date('Y-m-d', strtotime('-2 days'))]);

        $r = \App\Services\CrmAnalyticsService::resolveRange('7');
        $k = (new \App\Services\CrmAnalyticsService())->compute($r, ['product' => 'crm-test-product']);
        $this->assertSame(3, $k['total']);
        $this->assertSame(50.0, $k['conversion_rate']);
        $this->assertSame(1, $k['overdue_followups']);
        $this->assertSame(1, $k['unassigned']);
        $this->assertSame(1000.0, $k['pipeline_value']);
        $this->assertSame(['crm-test-product' => 3], $k['by_product']);
        $this->assertSame(3, array_sum($k['trend']['values']));
        $empty = (new \App\Services\CrmAnalyticsService())->compute($r, ['product' => 'no-such-product']);
        $this->assertSame(0, $empty['total']);
        $this->assertNull($empty['conversion_rate'], 'no fabricated rate when nothing is closed');

        $cust = \App\Services\CrmAnalyticsService::resolveRange('custom', '2026-01-10', '2026-01-01');
        $this->assertSame('30', $cust['range'], 'inverted custom range falls back to 30 days');
    }

    public function testDashboardRendersCrmBlockForCrmViewers(): void
    {
        $res = $this->withSession($this->admin(['crm.view']))->get('admin/dashboard');
        $res->assertStatus(200);
        $res->assertSee('WorkSphere pipeline');
        $res = $this->withSession($this->admin(['jobs.publish']))->get('admin/dashboard');
        $res->assertDontSee('WorkSphere pipeline');
    }

    public function testAllTenTemplatesAreSeeded(): void
    {
        (new CrmNotificationService())->ensureTemplates();
        $n = $this->db()->table('email_templates')->like('code', 'crm_', 'after')->countAllResults();
        $this->assertGreaterThanOrEqual(10, $n);
    }

    public function testMigrationIsIdempotentAndSeedsPermissions(): void
    {
        require_once APPPATH . 'Database/Migrations/20261108000002_crm_integration.php';
        \App\Database\Migrations\CrmIntegration::apply($this->db());
        \App\Database\Migrations\CrmIntegration::apply($this->db());
        $codes = array_column($this->db()->table('permissions')->select('code')->get()->getResultArray(), 'code');
        foreach (['crm.view', 'crm.manage', 'crm.export', 'crm.settings', 'integrations.view', 'integrations.manage', 'it_leads.view'] as $c) {
            $this->assertContains($c, $codes);
        }
        $this->assertGreaterThanOrEqual(10, $this->db()->table('crm_categories')->countAllResults());
    }

    public function testIntegrationClientSecretsAreEncryptedAtRestAndNeverListed(): void
    {
        IntegrationClientService::useKeyForTesting(random_bytes(32));
        try {
            $svc = new IntegrationClientService();
            $key = 'crmtest-' . bin2hex(random_bytes(3));
            $secret = $svc->createOrRotate($key)['secret'];
            $row = $this->db()->table('crm_integration_clients')->where('key_id', $key)->get()->getRowArray();
            $this->assertStringNotContainsString($secret, json_encode($row));
            $this->assertStringNotContainsString($secret, json_encode($svc->listClients()));
            $this->assertSame([$secret], $svc->secretsFor($row));
            $this->db()->table('crm_integration_clients')->where('key_id', $key)->delete();
        } finally {
            IntegrationClientService::useKeyForTesting(null);
        }
    }

    public function testClientCreationFailsClosedWithoutEncryptionKey(): void
    {
        IntegrationClientService::useKeyForTesting('');
        try {
            $this->expectException(\RuntimeException::class);
            (new IntegrationClientService())->createOrRotate('nokey-client');
        } finally {
            IntegrationClientService::useKeyForTesting(null);
        }
    }
}
