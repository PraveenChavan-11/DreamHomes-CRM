<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Database\Migrations\CreateSocialMarketingTables;
use App\Services\Social\SocialPublishingEngine;
use App\Services\Social\SocialSecurityService;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\InstagramApiService;

require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';

class SocialPublishingEngineTest extends CIUnitTestCase
{
    protected $db;
    protected SocialPublishingEngine $engine;
    protected LinkedInApiService $linkedIn;
    protected InstagramApiService $instagram;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        CreateSocialMarketingTables::apply($this->db);

        SocialSecurityService::useKeyForTesting('hex2bin:848a6062f68dd99f8295b93d052a65a3d76e336b95ee385c2923769931fc8017');

        $this->linkedIn = new LinkedInApiService($this->db);
        $this->linkedIn->setMockAccessToken('mock_linkedin_token_xyz');

        $this->instagram = new InstagramApiService($this->db);
        $this->instagram->setMockAccessToken('mock_instagram_token_xyz');

        $this->engine = new SocialPublishingEngine($this->db, $this->linkedIn, $this->instagram);

        // Seed mock accounts if not present
        $this->seedMockAccounts();
    }

    protected function tearDown(): void
    {
        SocialSecurityService::useKeyForTesting(null);
        parent::tearDown();
    }

    private function seedMockAccounts(): void
    {
        // 1. LinkedIn Organization
        $li = $this->db->table('social_accounts')->where('platform', 'linkedin')->get()->getRowArray();
        if (!$li) {
            $this->linkedIn->connectAccount([
                'access_token' => 'mock_linkedin_token_xyz',
                'expires_in'   => 5184000,
            ], '98765432', 'TalentHub Technologies Pvt Ltd', 1);
        }

        // 2. Instagram Business
        $ig = $this->db->table('social_accounts')->where('platform', 'instagram')->get()->getRowArray();
        if (!$ig) {
            $this->instagram->connectAccount([
                'ig_id'       => '17841400000000001',
                'username'    => 'talenthub_consultant',
                'name'        => 'TalentHub Official',
            ], 'mock_instagram_token_xyz', 5184000, 1);
        }
    }

    public function testImmediatePublishingLinkedInPost(): void
    {
        // Create post
        $this->db->table('social_posts')->insert([
            'title'            => 'Test LinkedIn Hiring Post',
            'primary_platform' => 'linkedin',
            'base_content'     => 'We are hiring elite backend engineers in Bangalore & Pune.',
            'base_hashtags'    => '#Hiring #TechJobs #TalentHub',
            'base_cta'         => 'Apply Now',
            'status'           => 'draft',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $postId = (int) $this->db->insertID();

        $result = $this->engine->publishPost($postId, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals('published', $result['status']);
        $this->assertArrayHasKey('linkedin', $result['results']);
        $this->assertTrue($result['results']['linkedin']['success']);
        $this->assertNotEmpty($result['results']['linkedin']['external_post_id']);

        // Verify post table state
        $updated = $this->db->table('social_posts')->where('id', $postId)->get()->getRowArray();
        $this->assertEquals('published', $updated['status']);
        $this->assertNotNull($updated['published_at']);

        // Verify attempt recorded
        $attempt = $this->db->table('social_publish_attempts')->where('post_id', $postId)->get()->getRowArray();
        $this->assertNotNull($attempt);
        $this->assertEquals('success', $attempt['status']);
    }

    public function testDuplicatePublishingPreventionIdempotency(): void
    {
        // Create already-published post
        $this->db->table('social_posts')->insert([
            'title'            => 'Already Published Post',
            'primary_platform' => 'linkedin',
            'base_content'     => 'Already published content.',
            'status'           => 'published',
            'published_at'     => date('Y-m-d H:i:s'),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $postId = (int) $this->db->insertID();

        $result = $this->engine->publishPost($postId);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('already been published', $result['message']);
    }

    public function testCharacterLimitValidationRejectsExcessiveContent(): void
    {
        $excessiveText = str_repeat('A', 3050);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum limit of 3,000 characters');

        $liAccount = $this->db->table('social_accounts')->where('platform', 'linkedin')->get()->getRowArray();
        $this->linkedIn->publishPost((int) $liAccount['id'], $excessiveText);
    }
}

