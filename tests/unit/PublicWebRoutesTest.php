<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PublicWebRoutesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testHomePageReturnsOk(): void
    {
        $result = $this->call('get', '/');
        $result->assertStatus(200);
        $result->assertSee('TalentHub');
        $result->assertSee('Discuss Your Requirements');
        $result->assertSee('Recruitment &amp; Talent Acquisition');
        $result->assertSee('IT Services &amp; Software Development');
    }

    public function testMasterHeroSectionContentAndVisualIntegrity(): void
    {
        $result = $this->call('get', '/');
        $result->assertStatus(200);
        $body = $result->getBody();
        foreach ([
            'Building Better Teams. Creating Better Technology.',
            'TalentHub connects businesses with the right people and delivers digital solutions',
            'Discuss Your Requirements',
            'Explore Our Services',
            'One Partner. Two Powerful Capabilities.',
            'A Practical Partner for Your Business Goals.',
            "Have a Business Challenge? Let's Build the Right Solution.",
            'th-hero',
        ] as $needle) {
            $this->assertStringContainsString($needle, $body, $needle);
        }
        $this->assertStringNotContainsString('SocietyHub', $body);
        // The product catalogue is public again (nav + footer link to /products); only the retired placeholders must stay gone.
        $this->assertStringNotContainsString('/products/societyhub', $body);
        $this->assertStringNotContainsString('/products/peoplehub', $body);
    }

    public function testTestimonialsRenderOnlyGenuineRecordsAndNeverFabricatedFallback(): void
    {
        // Empty -> partial renders nothing (no invented reviews).
        $this->assertSame('', trim(preg_replace('/<!--.*?-->/s', '', view('public/partials/testimonials_section', ['testimonials' => []]))));

        // Provided records -> featured grid + marquee with initials.
        $rows = [];
        foreach (['Test Alpha', 'Test Beta', 'Test Gamma', 'Test Delta', 'Test Epsilon'] as $n) {
            $rows[] = ['name' => $n, 'designation' => 'QA', 'company' => 'Example Co', 'content' => 'Sample feedback text.', 'rating' => 5];
        }
        $html = view('public/partials/testimonials_section', ['testimonials' => $rows]);
        $this->assertStringContainsString('Test Alpha', $html);
        $this->assertStringContainsString('testimonial-marquee-track', $html);
        $this->assertStringContainsString('TA', $html);
    }

    public function testJobsPageReturnsOk(): void
    {
        $result = $this->call('get', 'jobs');
        $result->assertStatus(200);
        $result->assertSee('Active Job Opportunities Across India');
    }

    public function testAboutPageReturnsOk(): void
    {
        $result = $this->call('get', 'about');
        $result->assertStatus(200);
        $result->assertSee('Ankit Joshi');
        $result->assertSee('Meghna Patil');
    }

    public function testSolutionsPageReturnsOk(): void
    {
        $result = $this->call('get', 'solutions');
        $result->assertStatus(200);
    }

    public function testEmployersPageReturnsOk(): void
    {
        $result = $this->call('get', 'employers');
        $result->assertStatus(200);
    }

    public function testCandidatesPageReturnsOk(): void
    {
        $result = $this->call('get', 'candidates');
        $result->assertStatus(200);
    }

    public function testCareerEnquiryPageReturnsOk(): void
    {
        $result = $this->call('get', 'career-enquiry');
        $result->assertStatus(200);
    }

    public function testContactDeskReturnsOk(): void
    {
        $result = $this->call('get', 'contact');
        $result->assertStatus(200);
    }

    public function testSitemapReturnsXml(): void
    {
        $result = $this->call('get', 'sitemap.xml');
        $result->assertStatus(200);
        $this->assertStringContainsString('sitemapindex', $result->getBody());
        $this->assertStringContainsString('sitemap-news.xml', $result->getBody());
    }

    public function testNewsSitemapReturnsValidGoogleNewsXml(): void
    {
        $result = $this->call('get', 'sitemap-news.xml');
        $result->assertStatus(200);
        $body = $result->response()->getBody();
        $this->assertStringContainsString('http://www.google.com/schemas/sitemap-news/0.9', $body);
        $this->assertStringContainsString('<news:publication>', $body);
        $this->assertStringContainsString('<news:title>', $body);
    }

    public function testJobDetailPageHasFullGoogleCompliantJobPostingSchema(): void
    {
        $result = $this->call('get', 'jobs/steel-foreman-pune');
        $result->assertStatus(200);
        $body = $result->getBody();
        $this->assertStringContainsString('"@type": "JobPosting"', $body);
        $this->assertStringContainsString('"datePosted":', $body);
        $this->assertStringContainsString('"validThrough":', $body);
        $this->assertStringContainsString('"streetAddress":', $body);
        $this->assertStringContainsString('"postalCode":', $body);
        $this->assertStringContainsString('"addressLocality": "Pune"', $body);
        $this->assertStringContainsString('"addressRegion": "Maharashtra"', $body);
        $this->assertStringContainsString('"addressCountry": "IN"', $body);
        $this->assertStringContainsString('"baseSalary":', $body);
        $this->assertStringContainsString('"currency": "INR"', $body);
        $this->assertStringContainsString('"directApply": true', $body);
    }

    public function testAdminLoginPageReturnsOk(): void
    {
        $result = $this->call('get', 'admin/login');
        $result->assertStatus(200);
        $result->assertSee('Sign In');
    }

    public function testAdminDashboardWithSessionReturnsOk(): void
    {
        $result = $this->withSession(['admin_user' => [
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin@talenthubconsultant.com',
            'username' => 'admin',
            'roles' => [['slug' => 'super_admin', 'name' => 'Super Administrator']],
            'permissions' => ['all'],
        ]])->call('get', 'admin/dashboard');

        $result->assertStatus(200);
        $result->assertSee('Executive Dashboard');
    }

    public function testAdminRootRouteWithSessionReturnsOk(): void
    {
        $result = $this->withSession(['admin_user' => [
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin@talenthubconsultant.com',
            'username' => 'admin',
            'roles' => [['slug' => 'super_admin', 'name' => 'Super Administrator']],
            'permissions' => ['all'],
        ]])->call('get', 'admin');

        $result->assertStatus(200);
        $result->assertSee('Executive Dashboard');
    }

    public function testAuditLogsRouteWithSessionReturnsOk(): void
    {
        $result = $this->withSession(['admin_user' => [
            'id' => 1,
            'name' => 'Super Administrator',
            'email' => 'admin@talenthubconsultant.com',
            'username' => 'admin',
            'roles' => [['slug' => 'super_admin', 'name' => 'Super Administrator']],
            'permissions' => ['all'],
        ]])->call('get', 'admin/audit-logs');

        $result->assertStatus(200);
        $result->assertSee('Audit Trail');
    }
    public function testThankYouPageRendersWithAllTypes(): void
    {
        // 1. Generic / Enquiry
        $res = $this->call('get', 'thank-you?type=enquiry&ref=ENQ-TEST-99');
        $res->assertStatus(200);
        $res->assertSee('Thank You for Contacting TalentHub');
        $res->assertSee('ENQ-TEST-99');

        // 2. Employer Mandate
        $res = $this->call('get', 'thank-you?type=employer&ref=EMP-TEST-88');
        $res->assertStatus(200);
        $res->assertSee('Corporate Mandate Successfully Registered');
        $res->assertSee('EMP-TEST-88');

        // 3. Career Resume Bank
        $res = $this->call('get', 'thank-you?type=career&ref=CAR-TEST-77');
        $res->assertStatus(200);
        $res->assertSee('Profile Catalogued in Private Talent Bank');

        // 4. Job Application
        $res = $this->call('get', 'thank-you?type=application&ref=APP-TEST-66&title=' . urlencode('Senior PHP Architect'));
        $res->assertStatus(200);
        $res->assertSee('Application Submitted Successfully');
        $res->assertSee('Senior PHP Architect');

        // 5. Newsletter
        $res = $this->call('get', 'thank-you?type=newsletter');
        $res->assertStatus(200);
        $res->assertSee('Thank You for Subscribing!');
        $res->assertSee('Verification Email Sent');
    }

    public function testSubmissionFailedPageRendersWithDirectContactHelp(): void
    {
        // A fixed reason CODE is turned into a server-written message.
        $res = $this->call('get', 'submission-failed?type=enquiry&reason=server_error');
        $res->assertStatus(200);
        $res->assertSee('We Couldn\'t Complete Your Submission');
        $res->assertSee('problem on our side');
        $res->assertSee('WhatsApp Desk');
        $res->assertSee('Call Katraj Desk');
    }

    public function testSubmissionFailedPageNeverEchoesAttackerSuppliedText(): void
    {
        // Content spoofing: anyone could craft a link that made the official domain display an arbitrary message.
        $spoof = 'Your account is suspended. Call +91 99999 00000 to verify your bank details';
        $res = $this->call('get', 'submission-failed?type=enquiry&reason=' . urlencode($spoof));
        $res->assertStatus(200);
        $this->assertStringNotContainsString('suspended', (string) $res->getBody());
        $this->assertStringNotContainsString('99999 00000', (string) $res->getBody());
        $res->assertSee('unexpected validation or network error');
        // return_url must stay on this site (no open redirect via the "try again" link)
        $res = $this->call('get', 'submission-failed?type=enquiry&reason=server_error&return_url=' . urlencode('https://evil.example/phish'));
        $this->assertStringNotContainsString('evil.example', (string) $res->getBody());
        $res = $this->call('get', 'submission-failed?type=enquiry&reason=server_error&return_url=' . urlencode('//evil.example/x'));
        $this->assertStringNotContainsString('evil.example', (string) $res->getBody());
    }

    public function testTwoServicePagesRenderAndLegacyUrlsRedirect(): void
    {
        foreach (['/services', '/services/recruitment', '/services/it-services'] as $path) {
            $this->assertSame(200, $this->call('get', $path)->response()->getStatusCode(), $path);
        }
        $body = $this->call('get', '/services')->getBody();
        $this->assertStringContainsString('People. Technology. Business Transformation.', $body);
        $this->assertStringNotContainsString('SocietyHub', $body);
        $this->assertStringNotContainsString('PeopleHub', $body);

        $redirects = [
            '/products/societyhub' => '/services/it-services',
            '/products/peoplehub' => '/services/it-services',
            '/services/erp-hrms' => '/services/it-services',
            '/services/qa-testing' => '/services/it-services',
            '/services/recruitment/it-recruitment' => '/services/recruitment',
            '/services/it-services/website-development' => '/services/it-services',
        ];
        foreach ($redirects as $from => $to) {
            $res = $this->call('get', $from)->response();
            $this->assertSame(301, $res->getStatusCode(), $from);
            $this->assertStringEndsWith($to, $res->getHeaderLine('Location'), $from);
        }
        // The product catalogue is a real section again: /products lists published products, no redirect.
        $this->assertSame(200, $this->call('get', '/products')->response()->getStatusCode());
        $this->assertSame(200, $this->call('get', '/products/worksphere')->response()->getStatusCode());
        try {
            $status = $this->call('get', '/services/nope')->response()->getStatusCode();
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $status = 404;
        }
        $this->assertSame(404, $status);
    }

    public function testSitemapListsOnlyPublishedProducts(): void
    {
        $xml = (new \App\Services\SitemapService())->generatePagesXml();
        $this->assertStringContainsString('/products/worksphere', $xml);
        $this->assertStringNotContainsString('/products/societyhub', $xml);
        $this->assertStringNotContainsString('/products/peoplehub', $xml);
        $this->assertStringContainsString('/services/recruitment', $xml);
        $this->assertStringContainsString('/services/it-services', $xml);
    }

    public function testFooterCompactNewsletterFormPresent(): void
    {
        $res = $this->call('get', '/');
        $res->assertStatus(200);
        $res->assertSee('data-source="footer_compact"');
        $res->assertSee('footer-compact-newsletter');
        $res->assertSee('Your email for insights');

        // Verify navbar CTA uses Hire & Build and footer excludes Cookie Preferences
        $res->assertSee('Hire &amp; Build');
        $res->assertDontSee('Cookie Preferences');
    }

    public function testAllPublicPagesDynamicSeoTitlesMatchDatabaseWebsitePages(): void
    {
        $expected = [
            '/'                           => 'TalentHub Consultant | Leading Job Consultancy in Pune',
            'about'                       => 'About Us | Top Recruitment Consultancy in Pune | TalentHub',
            'solutions'                   => 'Recruitment Solutions &amp; Staffing Tracks | TalentHub Pune',
            'employers'                   => 'Hire Top Talent in Pune | Corporate Recruitment | TalentHub',
            'candidates'                  => 'Pune Job Opportunities &amp; Career Advisory | TalentHub',
            'jobs'                        => 'Current Job Vacancies in Pune | IT &amp; Core Roles | TalentHub',
            'career-enquiry'              => 'Submit Resume | Confidential Pune Talent Vault | TalentHub',
            'insights'                    => 'Pune Hiring Market Trends &amp; Salary Insights | TalentHub',
            'interviews'                  => 'Interview Preparation &amp; Career Guidance | TalentHub Pune',
            'contact'                     => 'Contact TalentHub | Recruitment Office Katraj, Pune Desk',
            'faq'                         => 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune',
            'privacy'                     => 'Privacy Policy &amp; India DPDP Act Compliance | TalentHub',
            'terms'                       => 'Terms of Service &amp; Advisory Conditions | TalentHub Pune',
            'application/tracker'         => 'Track Job Application Status | Candidate Portal | TalentHub',
            'privacy/request-cv-deletion' => 'Request CV Deletion | DPDP Right to Erasure | TalentHub',
        ];

        foreach ($expected as $route => $expectedTitle) {
            $res = $this->call('get', $route);
            $res->assertStatus(200);
            $body = $res->getBody();
            $this->assertStringContainsString("<title>{$expectedTitle}</title>", $body, "Route '{$route}' did not output expected dynamic title '{$expectedTitle}'.");
        }
    }

    public function testJobDetailPageDynamicSeoTitlePreserved(): void
    {
        $res = $this->call('get', 'jobs/steel-foreman-pune');
        $res->assertStatus(200);
        $body = $res->getBody();
        // Job detail retains job title in <title> tag
        $this->assertStringContainsString('<title>Steel Foreman Jobs in Pune | Construction Hiring | TalentHub</title>', $body);
    }

    public function testLiveAdminSeoUpdateImmediatelyReflectedOnPublicSite(): void
    {
        $pageModel = new \App\Models\WebsitePageModel();
        $original = $pageModel->where('slug', 'terms')->first();
        $originalTitle = $original['meta_title'];

        try {
            // Simulate admin updating meta title in /admin/content/pages
            $pageModel->where('slug', 'terms')->set(['meta_title' => 'Custom Live Admin Title for Terms | TalentHub Test'])->update();

            $res = $this->call('get', 'terms');
            $res->assertStatus(200);
            $body = $res->getBody();
            $this->assertStringContainsString('<title>Custom Live Admin Title for Terms | TalentHub Test</title>', $body);
        } finally {
            // Restore original title
            $pageModel->where('slug', 'terms')->set(['meta_title' => $originalTitle])->update();
        }

        // Verify restored
        $resRestored = $this->call('get', 'terms');
        $this->assertStringContainsString('<title>Terms of Service &amp; Advisory Conditions | TalentHub Pune</title>', $resRestored->getBody());
    }

    public function testPwaManifestHasExactTalentHubName(): void
    {
        $adminManifest = json_decode(file_get_contents(FCPATH . 'manifest-admin.json'), true);
        $this->assertSame('TalentHub', $adminManifest['name']);
        $this->assertSame('TalentHub', $adminManifest['short_name']);

        $publicManifest = json_decode(file_get_contents(FCPATH . 'manifest.json'), true);
        $this->assertSame('TalentHub', $publicManifest['name']);
        $this->assertSame('TalentHub', $publicManifest['short_name']);
    }

    public function testFooterNewsletterSubmitButtonRightAligned(): void
    {
        $res = $this->call('get', '/');
        $body = $res->getBody();
        $this->assertStringContainsString('id="footer-compact-newsletter"', $body);
        $this->assertStringContainsString('right: 6px !important;', $body);
        $this->assertStringContainsString('left: auto !important;', $body);
    }
}
