<?php

namespace Tests\Unit;

use App\Services\ItLeadService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * IT Services CRM: service logic, public submission, admin access control.
 * Test rows use the reserved domain @itlead-test.invalid and are removed in tearDown.
 *
 * @internal
 */
final class ItLeadsCrmTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const DOMAIN = '@itlead-test.invalid';

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Local test DB only: reset throttle buckets so repeated runs are deterministic.
        if ($this->db()->tableExists('rate_limit_buckets')) {
            $this->db()->query('DELETE FROM rate_limit_buckets');
        }
    }

    protected function tearDown(): void
    {
        $db = $this->db();
        if ($db->tableExists('it_leads')) {
            $db->query("DELETE FROM it_leads WHERE email LIKE ?", ['%' . self::DOMAIN]);
        }
        parent::tearDown();
    }

    private function payload(string $tag, array $over = []): array
    {
        return $over + [
            'client_name' => 'Test Client', 'company' => 'Example Co', 'email' => $tag . self::DOMAIN,
            'phone' => '+91 98230 45678', 'location' => 'Pune', 'service_category' => 'erp-hrms', 'service_slug' => '',
            'project_description' => 'We need an HRMS with payroll and leave tracking.', 'budget_range' => '', 'timeline' => '',
            'preferred_contact' => '', 'source_page' => '', 'ip_address' => '127.0.0.1',
        ];
    }

    private function admin(array $perms, int $id = 2, string $slug = 'limited'): array
    {
        return ['admin_user' => [
            'id' => $id, 'username' => 'tester', 'name' => 'Tester', 'email' => 't@example.com',
            'roles' => [['id' => 99, 'name' => 'Tester', 'slug' => $slug]], 'permissions' => $perms,
        ]];
    }

    public function testLeadLifecycleWritesActivityTrail(): void
    {
        $svc = new ItLeadService();
        $r = $svc->create($this->payload('life'));
        $this->assertFalse($r['idempotent']);
        $id = $r['id'];

        $this->assertTrue($svc->changeStatus($id, 'contacted', 1));
        $this->assertFalse($svc->changeStatus($id, 'bogus', 1), 'unknown status rejected');
        $this->assertTrue($svc->addNote($id, 'Called, wants a proposal', 1));
        $this->assertFalse($svc->addNote($id, '   ', 1), 'empty note rejected');
        $this->assertTrue($svc->setFollowUp($id, '2030-01-15', 1));
        $this->assertFalse($svc->setFollowUp($id, '15/01/2030', 1), 'bad date rejected');
        $this->assertFalse($svc->assign($id, 999999, 1), 'unknown user rejected');

        $this->assertSame('contacted', $svc->find($id)['status']);
        $this->assertSame('2030-01-15', $svc->find($id)['next_follow_up_at']);

        $types = array_column($this->db()->table('it_lead_activities')->where('lead_id', $id)->get()->getResultArray(), 'type');
        foreach (['created', 'status_change', 'note', 'follow_up'] as $t) {
            $this->assertContains($t, $types);
        }

        $this->assertTrue($svc->softDelete($id, 1));
        $this->assertNull($svc->find($id), 'soft-deleted lead hidden');
        $this->assertNotNull($this->db()->table('it_leads')->where('id', $id)->get()->getRowArray(), 'row retained, not hard-deleted');
    }

    public function testIdempotentAndDuplicateDetection(): void
    {
        $svc = new ItLeadService();
        $a = $svc->create($this->payload('dup'));
        $again = $svc->create($this->payload('dup'));
        $this->assertTrue($again['idempotent']);
        $this->assertSame($a['id'], $again['id']);

        $b = $svc->create($this->payload('dup', ['project_description' => 'A completely different second project request.']));
        $this->assertFalse($b['idempotent']);
        $this->assertSame($a['id'], $b['duplicate_of'], 'same email flagged as duplicate');
    }

    public function testPublicFormRendersAndRefusesMissingCsrf(): void
    {
        $page = $this->get('project-enquiry?service=custom-software-development');
        $page->assertStatus(200);
        $page->assertSee('Discuss Your Project');

        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('project-enquiry/submit', ['client_name' => 'x']);
    }

    private function signedPost(array $over = []): array
    {
        $ts = (new \App\Services\FormSecurityService())->generateSignedTimestamp();
        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        return [$token, $over + [
            csrf_token() => $token, 'client_name' => 'Web Tester', 'company' => 'Example Co',
            'email' => 'web' . bin2hex(random_bytes(3)) . self::DOMAIN, 'phone' => '+91 98230 45678',
            'service_category' => 'website-development', 'project_description' => 'Build a company website with a blog.',
            'consent' => '1', 'th_hp_check' => '', 'th_hp_website' => '',
            'th_form_ts' => $ts['th_form_ts'], 'th_form_sig' => $ts['th_form_sig'],
            'utm_source' => 'google', 'utm_campaign' => 'spring', 'cf-turnstile-response' => 'test_token_valid_' . bin2hex(random_bytes(6)),
        ]];
    }

    public function testPublicSubmissionPersistsLeadWithUtmAndConsent(): void
    {
        [$token, $data] = $this->signedPost();
        $res = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => $token])
            ->post('project-enquiry/submit', $data);
        $this->assertSame(201, $res->response()->getStatusCode(), $res->getJSON() ?: '');
        $json = json_decode($res->getJSON(), true);
        $this->assertTrue($json['success']);

        $row = $this->db()->table('it_leads')->where('email', $data['email'])->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('new', $row['status']);
        $this->assertSame('website-development', $row['service_category']);
        $this->assertSame('google', $row['utm_source']);
        $this->assertNotEmpty($row['consent_at']);
    }

    public function testPublicSubmissionValidationAndConsent(): void
    {
        [$token, $data] = $this->signedPost(['consent' => '']);
        $h = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => $token];
        $this->withHeaders($h)->post('project-enquiry/submit', $data)->assertStatus(422);

        [$token, $data] = $this->signedPost(['email' => 'not-an-email', 'project_description' => 'short']);
        $res = $this->withHeaders(['X-CSRF-TOKEN' => $token] + $h)->post('project-enquiry/submit', $data);
        $res->assertStatus(422);
        $this->assertNull($this->db()->table('it_leads')->where('email', 'not-an-email')->get()->getRowArray());

        [$token, $data] = $this->signedPost(['th_hp_check' => 'bot']);
        $this->withHeaders(['X-CSRF-TOKEN' => $token] + $h)->post('project-enquiry/submit', $data)->assertStatus(422);
    }

    public function testAdminLeadAccessControl(): void
    {
        // Unauthenticated -> login redirect
        $this->withSession([])->get('admin/it-leads')->assertRedirectTo(base_url('admin/login'));

        // Authenticated without permission -> bounced, never rendered
        $res = $this->withSession($this->admin(['jobs.view']))->get('admin/it-leads');
        $this->assertNotSame(200, $res->response()->getStatusCode());

        // View-only can list but cannot change status or export
        $svc = new ItLeadService();
        $id = $svc->create($this->payload('rbac'))['id'];
        $viewer = $this->admin(['it_leads.view']);
        $this->withSession($viewer)->get('admin/it-leads')->assertStatus(200);
        $this->withSession($viewer)->get('admin/it-leads/' . $id)->assertStatus(200);
        $this->withSession($viewer)->get('admin/it-leads?export=csv')->assertRedirect();
        $this->assertSame('new', $svc->find($id)['status']);

        // Super admin can list and open
        $super = $this->admin(['*'], 1, 'super_admin');
        $this->withSession($super)->get('admin/it-leads')->assertStatus(200);
        $this->withSession($super)->get('admin/it-leads/' . $id)->assertStatus(200);
    }
}
