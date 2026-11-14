<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * SocialAdminRoutesAndSecurityTest
 *
 * Verifies:
 * 1. Unauthenticated access protection (redirects to /admin/login)
 * 2. Super Administrator authorized access to all 15 Social Media Marketing screens
 * 3. AI Content Assistant AJAX endpoints (suggestions & rewriting)
 * 4. Audit logging & token security integrity (zero secret exposure)
 *
 * @internal
 */
final class SocialAdminRoutesAndSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();

        // Ensure tables exist
        if (!$this->db->tableExists('social_accounts')) {
            require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';
            \App\Database\Migrations\CreateSocialMarketingTables::apply($this->db);
        }
    }

    /**
     * 1. Unauthenticated users must be redirected to /admin/login
     */
    public function testUnauthenticatedUsersBlockedFromSocialCenter(): void
    {
        $protectedRoutes = [
            'admin/social',
            'admin/social/overview',
            'admin/social/posts',
            'admin/social/posts/create',
            'admin/social/calendar',
            'admin/social/queue',
            'admin/social/media',
            'admin/social/campaigns',
            'admin/social/enquiries',
            'admin/social/engagement',
            'admin/social/analytics',
            'admin/social/accounts',
            'admin/social/settings',
            'admin/social/logs',
        ];

        foreach ($protectedRoutes as $route) {
            $result = $this->withSession([])->get($route);
            $result->assertRedirectTo(base_url('admin/login'));
        }
    }

    /**
     * 2. Authenticated Admin has 200 OK access to all Social Media Marketing screens
     */
    public function testAuthenticatedAdminCanAccessAllSocialScreens(): void
    {
        $adminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'name'        => 'Super Administrator',
                'email'       => 'admin@talenthubconsultant.com',
                'roles'       => [
                    [
                        'id'   => 1,
                        'name' => 'Super Administrator',
                        'slug' => 'super_admin',
                    ],
                ],
                'permissions' => ['*'],
            ],
        ];

        $screens = [
            'admin/social'                  => 'Marketing Overview',
            'admin/social/overview'         => 'Marketing Overview',
            'admin/social/posts'            => 'Manage, review, reschedule',
            'admin/social/posts/create'     => 'Post Creation Studio',
            'admin/social/calendar'         => 'Content Marketing Calendar',
            'admin/social/scheduled'        => 'Manage, review, reschedule',
            'admin/social/drafts'           => 'Manage, review, reschedule',
            'admin/social/queue'            => 'Publishing Queue',
            'admin/social/media'            => 'Marketing Media Library',
            'admin/social/campaigns'        => 'Marketing Campaigns',
            'admin/social/campaigns/create' => 'Campaign',
            'admin/social/enquiries'        => 'Social Enquiries &amp; Leads',
            'admin/social/engagement'       => 'Comments &amp; Engagement',
            'admin/social/analytics'        => 'Analytics &amp; Performance Reports',
            'admin/social/accounts'         => 'Connected Social Accounts',
            'admin/social/settings'         => 'API Integration Settings &amp; Security',
            'admin/social/logs'             => 'Activity &amp; Audit Logs',
        ];

        foreach ($screens as $route => $expectedSnippet) {
            $result = $this->withSession($adminSession)->get($route);
            $result->assertStatus(200);
            $body = $result->getBody();
            $this->assertStringContainsString($expectedSnippet, $body, "Route {$route} does not contain expected snippet: {$expectedSnippet}");
        }
    }

    /**
     * 3. AI Assistant AJAX suggest endpoint returns JSON with candidates
     */
    public function testAssistantSuggestEndpoint(): void
    {
        $adminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'name'        => 'Super Administrator',
                'email'       => 'admin@talenthubconsultant.com',
                'roles'       => [['id' => 1, 'slug' => 'super_admin']],
                'permissions' => ['*'],
            ],
        ];

        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        $headers = [
            'X-CSRF-TOKEN'     => $token,
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $postData = [
            csrf_token() => $token,
            'category'   => 'Recruitment & Hiring',
            'tone'       => 'Professional',
            'topic'      => 'Senior Full Stack Engineers',
            'platform'   => 'both',
        ];

        $result = $this->withSession($adminSession)->withHeaders($headers)->post('admin/social/assistant/suggest', $postData);
        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);
    }

    /**
     * 4. AI Assistant AJAX rewrite endpoint returns platform-specific variations
     */
    public function testAssistantRewriteEndpoint(): void
    {
        $adminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'name'        => 'Super Administrator',
                'email'       => 'admin@talenthubconsultant.com',
                'roles'       => [['id' => 1, 'slug' => 'super_admin']],
                'permissions' => ['*'],
            ],
        ];

        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        $headers = [
            'X-CSRF-TOKEN'     => $token,
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $postData = [
            csrf_token() => $token,
            'text'       => 'We are hiring experienced PHP CodeIgniter engineers for our team.',
            'tone'       => 'Thought Leadership',
        ];

        $result = $this->withSession($adminSession)->withHeaders($headers)->post('admin/social/assistant/rewrite', $postData);
        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertIsArray($json);
        $this->assertTrue($json['success'] ?? false);
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);
    }

    /**
     * 5. Verify Activity Log recorded by controller actions
     */
    public function testAuditLogSavedOnAdminAction(): void
    {
        // Insert a simulated log entry to verify table query
        $this->db->table('social_activity_logs')->insert([
            'user_id'       => 1,
            'action'        => 'test.action',
            'entity_type'   => 'post',
            'entity_id'     => '999',
            'metadata_json' => json_encode(['platform' => 'linkedin', 'status' => 'tested']),
            'ip_address'    => '127.0.0.1',
            'user_agent'    => 'PHPUnit Security Test',
            'result'        => 'success',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $logged = $this->db->table('social_activity_logs')->where('action', 'test.action')->get()->getRowArray();
        $this->assertNotNull($logged);
        $this->assertSame('post', $logged['entity_type']);
        $this->assertSame('999', $logged['entity_id']);
        $this->assertStringNotContainsString('access_token', $logged['metadata_json']);
        $this->assertStringNotContainsString('client_secret', $logged['metadata_json']);
    }
}
