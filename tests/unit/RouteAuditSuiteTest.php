<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class RouteAuditSuiteTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testAuditAllPublicRoutesRenderSuccessfully(): void
    {
        $routes = [
            '/'                     => ['WorkSphere', 'tokens.css', 'theme.css'],
            '/jobs'                 => ['Browse Jobs', 'tokens.css'],
            '/services'             => ['Services', 'tokens.css'],
            '/services/recruitment' => ['Recruitment', 'tokens.css'],
            '/services/it-services' => ['IT Services', 'tokens.css'],
            '/products'             => ['Products', 'tokens.css'],
            '/products/worksphere'  => ['WorkSphere', 'HRMS'],
            '/case-studies'         => ['Case Studies', 'tokens.css'],
            '/project-enquiry'      => ['Discuss', 'tokens.css'],
            '/about'                => ['About', 'tokens.css'],
            '/solutions'            => ['Solutions', 'tokens.css'],
            '/employers'            => ['Employers', 'tokens.css'],
            '/candidates'           => ['Candidates', 'tokens.css'],
            '/career-enquiry'       => ['Career', 'tokens.css'],
            '/insights'             => ['Insights', 'tokens.css'],
            '/interviews'           => ['Interview', 'tokens.css'],
            '/contact'              => ['Contact', 'tokens.css'],
            '/thank-you'            => ['Thank You', 'tokens.css'],
            '/faq'                  => ['FAQ', 'tokens.css'],
            '/privacy'              => ['Privacy', 'tokens.css'],
            '/terms'                => ['Terms', 'tokens.css'],
            '/robots.txt'           => ['User-agent:'],
            '/llms.txt'             => ['TalentHub'],
            '/sitemap.xml'          => ['sitemapindex'],
            '/sitemap-pages.xml'    => ['urlset'],
        ];

        foreach ($routes as $uri => $expectedStrings) {
            $res = $this->call('get', $uri);
            $res->assertStatus(200);
            $body = $res->getBody();
            foreach ($expectedStrings as $needle) {
                $this->assertStringContainsString($needle, $body, "Route {$uri} body missing: {$needle}");
            }
        }
    }
}
