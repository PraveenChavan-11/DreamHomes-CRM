<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DynamicSeoAndChannelsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testCanonicalAndOgUrlNeverContainIndexPhpOrPhp(): void
    {
        $urlsToTest = ['/', '/jobs', '/employers', '/candidates', '/contact', '/career-enquiry', '/about'];

        foreach ($urlsToTest as $path) {
            $result = $this->call('get', $path);
            $result->assertStatus(200);
            $body = $result->getBody();

            // Extract canonical tag
            preg_match('/<link\s+rel="canonical"\s+href="([^"]+)"/i', $body, $canonicalMatches);
            $this->assertNotEmpty($canonicalMatches, "Canonical tag must exist on {$path}");
            $canonicalUrl = $canonicalMatches[1];
            $this->assertStringNotContainsString('index.php', $canonicalUrl, "Canonical URL on {$path} must not contain index.php");
            $this->assertStringNotContainsString('.php', $canonicalUrl, "Canonical URL on {$path} must not contain .php");

            // Extract og:url tag
            preg_match('/<meta\s+property="og:url"\s+content="([^"]+)"/i', $body, $ogMatches);
            $this->assertNotEmpty($ogMatches, "og:url tag must exist on {$path}");
            $ogUrl = $ogMatches[1];
            $this->assertStringNotContainsString('index.php', $ogUrl, "og:url on {$path} must not contain index.php");
            $this->assertStringNotContainsString('.php', $ogUrl, "og:url on {$path} must not contain .php");
        }
    }

    public function testGoogleSiteVerificationTokenRemoved(): void
    {
        $result = $this->call('get', '/');
        $result->assertStatus(200);
        $body = $result->getBody();

        $this->assertStringNotContainsString('google-site-verification', $body);
    }

    public function testDynamicSchemaOrgStructureAndMultiContactPoints(): void
    {
        $result = $this->call('get', '/');
        $result->assertStatus(200);
        $body = $result->getBody();

        preg_match('/<script\s+type="application\/ld\+json">([\s\S]*?)<\/script>/i', $body, $jsonMatches);
        $this->assertNotEmpty($jsonMatches, 'Schema.org JSON-LD script must exist');

        $schemaData = json_decode($jsonMatches[1], true);
        $this->assertIsArray($schemaData, 'Schema.org JSON must be valid JSON');
        $this->assertEquals('https://schema.org', $schemaData['@context']);
        $this->assertArrayHasKey('@graph', $schemaData);

        $orgNode = $schemaData['@graph'][0];
        $this->assertContains('EmploymentAgency', $orgNode['@type']);

        // Check dynamic sameAs
        $this->assertArrayHasKey('sameAs', $orgNode);
        $this->assertContains('https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R', $orgNode['sameAs']);

        // Check multi-contactPoint (Customer Service vs Recruitment / Candidate Applications)
        $this->assertArrayHasKey('contactPoint', $orgNode);
        $contactPoints = $orgNode['contactPoint'];
        $this->assertCount(2, $contactPoints);

        $customerService = $contactPoints[0];
        $this->assertEquals('customer service and corporate recruitment', $customerService['contactType']);
        $this->assertEquals('hr@talenthubconsultant.com', $customerService['email']);

        $recruitmentDesk = $contactPoints[1];
        $this->assertEquals('candidate careers and job applications', $recruitmentDesk['contactType']);
        $this->assertEquals('hiring@talenthubconsultant.com', $recruitmentDesk['email']);
    }

    public function testWhatsAppChannelLinkAndCtaAcrossPublicPages(): void
    {
        $channelUrl = 'https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R';

        // 1. Home Page
        $resultHome = $this->call('get', '/');
        $resultHome->assertStatus(200);
        $bodyHome = $resultHome->getBody();
        $this->assertStringContainsString($channelUrl, $bodyHome);
        $this->assertStringContainsString('Join Our WhatsApp Channel', $bodyHome);

        // 2. Jobs Page
        $resultJobs = $this->call('get', '/jobs');
        $resultJobs->assertStatus(200);
        $bodyJobs = $resultJobs->getBody();
        $this->assertStringContainsString($channelUrl, $bodyJobs);
        $this->assertStringContainsString('Join Our WhatsApp Channel', $bodyJobs);

        // 3. Candidates Page
        $resultCand = $this->call('get', '/candidates');
        $resultCand->assertStatus(200);
        $bodyCand = $resultCand->getBody();
        $this->assertStringContainsString($channelUrl, $bodyCand);
        $this->assertStringContainsString('Join Our WhatsApp Channel', $bodyCand);

        // 4. Career Enquiry Page
        $resultCareer = $this->call('get', '/career-enquiry');
        $resultCareer->assertStatus(200);
        $bodyCareer = $resultCareer->getBody();
        $this->assertStringContainsString($channelUrl, $bodyCareer);

        // 5. Contact Page
        $resultContact = $this->call('get', '/contact');
        $resultContact->assertStatus(200);
        $bodyContact = $resultContact->getBody();
        $this->assertStringContainsString($channelUrl, $bodyContact);
        $this->assertStringContainsString('Join Our WhatsApp Channel', $bodyContact);

        // 6. Footer (Global Layout - WhatsApp number present, channel icon removed from footer per requirement)
        $this->assertStringContainsString('aria-label="WhatsApp"', $bodyHome);
        $this->assertStringNotContainsString('aria-label="TalentHub WhatsApp Channel"', $bodyHome);
        $this->assertStringContainsString('btn-whatsapp-channel', $bodyHome);
    }

    public function testEmailSeparationDisplayOnPublicPages(): void
    {
        // Contact page must display both clearly separated
        $result = $this->call('get', '/contact');
        $result->assertStatus(200);
        $body = $result->getBody();

        $this->assertStringContainsString('Customer &amp; Corporate Enquiries', $body);
        $this->assertStringContainsString('hr@talenthubconsultant.com', $body);

        $this->assertStringContainsString('Candidate Careers &amp; Resumes', $body);
        $this->assertStringContainsString('hiring@talenthubconsultant.com', $body);

        // Footer must display both
        $this->assertStringContainsString('Customer &amp; B2B Enquiries', $body);
        $this->assertStringContainsString('Candidate Job Applications', $body);
    }

    public function testDefaultSocialSharingImageUsesFavicon(): void
    {
        $urlsToTest = ['/', '/about', '/contact', '/services'];

        foreach ($urlsToTest as $path) {
            $result = $this->call('get', $path);
            $result->assertStatus(200);
            $body = $result->getBody();

            // Extract og:image tag
            preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $body, $ogMatches);
            $this->assertNotEmpty($ogMatches, "og:image must exist on {$path}");
            $ogImage = $ogMatches[1];
            $this->assertStringContainsString('favicon.png', $ogImage, "og:image on {$path} must use favicon.png for professional link previews");
            $this->assertStringNotContainsString('logo.png', $ogImage, "og:image on {$path} must not be the wide logo.png banner");

            // Extract twitter:card tag (must be summary for square favicon)
            preg_match('/<meta\s+name="twitter:card"\s+content="([^"]+)"/i', $body, $twCardMatches);
            $this->assertNotEmpty($twCardMatches, "twitter:card must exist on {$path}");
            $this->assertEquals('summary', $twCardMatches[1], "twitter:card on {$path} must be 'summary' for square favicon");

            // Extract twitter:image tag
            preg_match('/<meta\s+name="twitter:image"\s+content="([^"]+)"/i', $body, $twImgMatches);
            $this->assertNotEmpty($twImgMatches, "twitter:image must exist on {$path}");
            $this->assertStringContainsString('favicon.png', $twImgMatches[1]);

            // Check og:image dimension tags
            $this->assertStringContainsString('<meta property="og:image:width" content="192">', $body);
            $this->assertStringContainsString('<meta property="og:image:height" content="192">', $body);
        }
    }
}
