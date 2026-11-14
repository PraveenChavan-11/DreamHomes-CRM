<?php

namespace Tests\Unit;

use App\Services\CaseStudyService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class CaseStudiesModuleTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPublicCaseStudiesPageRendersProperly(): void
    {
        $res = $this->call('get', '/case-studies');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('Case Studies', $body);
        $this->assertStringContainsString('tokens.css', $body);
    }

    public function testCaseStudyServiceHasPublishedReturnsBoolean(): void
    {
        $has = CaseStudyService::hasPublished();
        $this->assertIsBool($has);
    }

    public function testAdminCaseStudiesRequiresAuthentication(): void
    {
        $res = $this->call('get', '/admin/case-studies');
        // Unauthenticated access redirects to login
        $this->assertTrue($res->isRedirect());
    }

    public function testAdminCaseStudiesAccessibleWhenAuthenticated(): void
    {
        $adminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'email'       => 'admin@talenthubconsultant.com',
                'first_name'  => 'Super',
                'last_name'   => 'Admin',
                'role'        => 'Super Admin',
                'role_id'     => 1,
                'permissions' => ['*'],
            ],
            'admin_logged_in' => true,
        ];

        $res = $this->withSession($adminSession)->call('get', '/admin/case-studies');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('Case Studies', $body);
    }
}

