<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Database\Migrations\CreateSocialMarketingTables;
use App\Services\Social\SocialContentAssistantService;
use App\Services\Social\SocialEnquiryService;
use App\Services\Social\SocialAnalyticsService;
use App\Services\Social\SocialSecurityService;

require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';

class SocialEnquiryAndAnalyticsTest extends CIUnitTestCase
{
    protected $db;
    protected SocialContentAssistantService $assistant;
    protected SocialEnquiryService $enquiryService;
    protected SocialAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        CreateSocialMarketingTables::apply($this->db);

        SocialSecurityService::useKeyForTesting('hex2bin:848a6062f68dd99f8295b93d052a65a3d76e336b95ee385c2923769931fc8017');

        $this->assistant        = new SocialContentAssistantService($this->db);
        $this->enquiryService   = new SocialEnquiryService($this->db);
        $this->analyticsService = new SocialAnalyticsService($this->db);
    }

    protected function tearDown(): void
    {
        SocialSecurityService::useKeyForTesting(null);
        parent::tearDown();
    }

    public function testContentAssistantGeneratesValidPlatformCaptions(): void
    {
        $res = $this->assistant->generateSuggestions('HRMS / WorkSphere', 'Corporate', 'Automated Attendance');

        $this->assertArrayHasKey('headline', $res);
        $this->assertArrayHasKey('linkedin', $res);
        $this->assertArrayHasKey('instagram', $res);
        $this->assertNotEmpty($res['linkedin']['caption']);
        $this->assertNotEmpty($res['instagram']['caption']);
        $this->assertLessThanOrEqual(3000, $res['linkedin']['char_count']);
        $this->assertLessThanOrEqual(2200, $res['instagram']['char_count']);
    }

    public function testSocialEnquiryCaptureAndDeduplication(): void
    {
        $unique = bin2hex(random_bytes(4));
        $payload1 = [
            'contact_name'    => 'Aarav Sharma ' . $unique,
            'email'           => 'aarav.' . $unique . '@example.com',
            'phone'           => '+91 98' . substr(str_shuffle('0123456789'), 0, 8),
            'company'         => 'TechCorp India',
            'enquiry_type'    => 'recruitment',
            'message'         => 'Interested in hiring 5 senior React developers.',
            'source_platform' => 'linkedin',
        ];

        $enq1 = $this->enquiryService->captureEnquiry($payload1, 1);
        $this->assertGreaterThan(0, $enq1['id']);
        $this->assertFalse($enq1['is_duplicate']);

        // Duplicate submission with same email
        $enq2 = $this->enquiryService->captureEnquiry($payload1, 1);
        $this->assertTrue($enq2['is_duplicate']);
        $this->assertEquals($enq1['id'], $enq2['duplicate_id']);
    }

    public function testEnquiryStatusTransition(): void
    {
        $unique = bin2hex(random_bytes(4));
        $payload = [
            'contact_name'    => 'Priya Patel ' . $unique,
            'email'           => 'priya.' . $unique . '@example.com',
            'phone'           => '+91 97' . substr(str_shuffle('0123456789'), 0, 8),
            'company'         => 'GrowthX Ltd',
            'enquiry_type'    => 'product',
            'message'         => 'Want demo of WorkSphere HRMS.',
            'source_platform' => 'instagram',
        ];

        $enq = $this->enquiryService->captureEnquiry($payload, 1);
        $id = $enq['id'];

        $updated = $this->enquiryService->updateStatus($id, 'qualified', 'Candidate verified budget & timeline.', 1, 1);
        $this->assertTrue($updated);

        $row = $this->db->table('social_enquiries')->where('id', $id)->get()->getRowArray();
        $this->assertEquals('qualified', $row['lead_status']);
        $this->assertEquals('Candidate verified budget & timeline.', $row['notes']);
    }

    public function testOverviewKpisCalculation(): void
    {
        $kpis = $this->analyticsService->getOverviewKpis('30_days', 'all');

        $this->assertArrayHasKey('total_published', $kpis);
        $this->assertArrayHasKey('scheduled_posts', $kpis);
        $this->assertArrayHasKey('engagement_rate', $kpis);
        $this->assertArrayHasKey('total_enquiries', $kpis);
    }

    public function testEnquiryCsvExport(): void
    {
        $csv = $this->analyticsService->exportEnquiriesCsv();
        $this->assertStringContainsString('Contact Name', $csv);
        $this->assertStringContainsString('Aarav Sharma', $csv);
    }
}
