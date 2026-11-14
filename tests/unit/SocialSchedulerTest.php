<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Database\Migrations\CreateSocialMarketingTables;
use App\Services\Social\SocialSchedulerService;
use App\Services\Social\SocialPublishingEngine;
use App\Services\Social\SocialSecurityService;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\InstagramApiService;

require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';

class SocialSchedulerTest extends CIUnitTestCase
{
    protected $db;
    protected SocialSchedulerService $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        CreateSocialMarketingTables::apply($this->db);

        SocialSecurityService::useKeyForTesting('hex2bin:848a6062f68dd99f8295b93d052a65a3d76e336b95ee385c2923769931fc8017');

        $linkedIn = new LinkedInApiService($this->db);
        $linkedIn->setMockAccessToken('mock_token');

        $instagram = new InstagramApiService($this->db);
        $instagram->setMockAccessToken('mock_token');

        $engine = new SocialPublishingEngine($this->db, $linkedIn, $instagram);
        $this->scheduler = new SocialSchedulerService($this->db, $engine);

        // Ensure mock account exists
        $li = $this->db->table('social_accounts')->where('platform', 'linkedin')->get()->getRowArray();
        if (!$li) {
            $linkedIn->connectAccount(['access_token' => 'mock_token', 'expires_in' => 5184000], '12345678', 'TalentHub Company', 1);
        }
    }

    protected function tearDown(): void
    {
        SocialSecurityService::useKeyForTesting(null);
        parent::tearDown();
    }

    public function testDuePostsAreEnqueuedAndPublishedByScheduler(): void
    {
        // Insert scheduled post past due
        $pastDate = date('Y-m-d H:i:s', time() - 300); // 5 mins ago
        $this->db->table('social_posts')->insert([
            'title'            => 'Scheduled Due Post',
            'primary_platform' => 'linkedin',
            'base_content'     => 'This is scheduled content.',
            'status'           => 'scheduled',
            'scheduled_at'     => $pastDate,
            'created_at'       => $pastDate,
        ]);
        $postId = (int) $this->db->insertID();

        // Run scheduler
        $results = $this->scheduler->processQueue(5);

        $this->assertGreaterThanOrEqual(1, $results['processed']);
        $this->assertGreaterThanOrEqual(1, $results['succeeded']);

        // Verify post updated to published
        $post = $this->db->table('social_posts')->where('id', $postId)->get()->getRowArray();
        $this->assertEquals('published', $post['status']);
        $this->assertNotNull($post['published_at']);
    }

    public function testFutureScheduledPostsAreNotPublishedPrematurely(): void
    {
        $futureDate = date('Y-m-d H:i:s', time() + 3600); // 1 hour in future
        $this->db->table('social_posts')->insert([
            'title'            => 'Future Post',
            'primary_platform' => 'linkedin',
            'base_content'     => 'Future content.',
            'status'           => 'scheduled',
            'scheduled_at'     => $futureDate,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        $postId = (int) $this->db->insertID();

        $enqueued = $this->scheduler->enqueueDuePosts();
        $post = $this->db->table('social_posts')->where('id', $postId)->get()->getRowArray();

        // Must still be scheduled
        $this->assertEquals('scheduled', $post['status']);
        $this->assertNull($post['published_at']);
    }
}

