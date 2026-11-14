<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';
use App\Database\Migrations\CreateSocialMarketingTables;

class SocialMarketingMigrationTest extends CIUnitTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        CreateSocialMarketingTables::apply($this->db);
    }

    public function testAllSeventeenTablesCreated(): void
    {
        $expectedTables = [
            'social_accounts',
            'social_credentials',
            'social_oauth_states',
            'social_campaigns',
            'social_posts',
            'social_post_platforms',
            'social_media_library',
            'social_post_media',
            'social_scheduled_jobs',
            'social_publish_attempts',
            'social_content_templates',
            'social_campaign_posts',
            'social_analytics_snapshots',
            'social_enquiries',
            'social_comments',
            'social_notifications',
            'social_activity_logs',
        ];

        $tables = $this->db->listTables();
        foreach ($expectedTables as $table) {
            $this->assertContains($table, $tables, "Expected table {$table} to exist in database.");
        }
    }

    public function testSocialPermissionsSeeded(): void
    {
        if ($this->db->tableExists('permissions')) {
            $perms = $this->db->table('permissions')->where('category', 'Social Media')->get()->getResultArray();
            $this->assertGreaterThanOrEqual(7, count($perms));
            $codes = array_column($perms, 'code');
            $this->assertContains('social.view', $codes);
            $this->assertContains('social.create', $codes);
            $this->assertContains('social.publish', $codes);
            $this->assertContains('social.manage', $codes);
            $this->assertContains('social.settings', $codes);
        }
    }

    public function testDefaultTemplatesSeeded(): void
    {
        $count = $this->db->table('social_content_templates')->countAllResults();
        $this->assertGreaterThanOrEqual(5, $count);
    }
}
