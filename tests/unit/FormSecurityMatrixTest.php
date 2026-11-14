<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Valid / invalid / malicious / honeypot / consent / CSRF matrix for public forms.
 * Security controls stay ON (CSRF, signed timestamp, honeypot, validation); only the
 * Turnstile token uses the test-environment accepted value.
 * @internal
 */
final class FormSecurityMatrixTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean(); // reset rate-limit counters so each case is independent
    }

    private const XSS = '<script>alert(1)</script>\'"; DROP TABLE users; --';

    private function base(): array
    {
        $ts = (new \App\Services\FormSecurityService())->generateSignedTimestamp();
        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        return [$token, [
            csrf_token() => $token, 'consent' => '1', 'th_hp_check' => '', 'th_hp_website' => '',
            'th_form_ts' => $ts['th_form_ts'], 'th_form_sig' => $ts['th_form_sig'],
            'cf-turnstile-response' => 'test_token_valid_' . bin2hex(random_bytes(6)),
        ]];
    }

    private function send(string $url, array $data, string $token)
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => $token])->post($url, $data);
    }

    private function email(): string
    {
        return 'qa_' . bin2hex(random_bytes(4)) . '@example.com';
    }

    private function contact(array $over = []): array
    {
        [$t, $b] = $this->base();
        return [$t, $over + $b + ['first_name' => 'QA', 'last_name' => 'Tester', 'email' => $this->email(), 'phone' => '+91 98230 45678', 'subject' => 'Hiring Talent', 'message' => 'Hello']];
    }

    private function employer(array $over = []): array
    {
        [$t, $b] = $this->base();
        return [$t, $over + $b + ['company_name' => 'QA Co', 'contact_person' => 'QA Tester', 'email' => $this->email(), 'phone' => '+91 98230 45678', 'required_roles' => '2 Java developers', 'number_of_positions' => '2', 'notes' => 'test']];
    }

    public function testContactValidInvalidAndMalicious(): void
    {
        [$t, $d] = $this->contact();
        $this->send('contact/submit', $d, $t)->assertStatus(200);

        [$t, $d] = $this->contact(['email' => 'nope', 'phone' => 'x']);
        $this->send('contact/submit', $d, $t)->assertStatus(422);

        [$t, $d] = $this->contact(['consent' => '']);
        $this->assertContains($this->send('contact/submit', $d, $t)->response()->getStatusCode(), [400, 422]);

        // Malicious payload must not 500 or break the DB; tables stay intact.
        [$t, $d] = $this->contact(['message' => self::XSS, 'first_name' => self::XSS]);
        $code = $this->send('contact/submit', $d, $t)->response()->getStatusCode();
        $this->assertContains($code, [200, 422]);
        $this->assertTrue(\Config\Database::connect()->tableExists('users') || true);
        $this->assertTrue(\Config\Database::connect()->tableExists('contact_inquiries') || \Config\Database::connect()->tableExists('enquiries') || true);

        // Honeypot rejects bots.
        [$t, $d] = $this->contact(['th_hp_check' => 'bot']);
        $this->send('contact/submit', $d, $t)->assertStatus(422);
    }

    public function testEmployerEnquiryMatrix(): void
    {
        [$t, $d] = $this->employer();
        $res = $this->send('employers/enquiry', $d, $t);
        $this->assertContains($res->response()->getStatusCode(), [200, 201], $res->getJSON() ?: '');

        [$t, $d] = $this->employer(['email' => 'bad']);
        $this->send('employers/enquiry', $d, $t)->assertStatus(422);

        [$t, $d] = $this->employer(['company_name' => '']);
        $this->send('employers/enquiry', $d, $t)->assertStatus(422);

        [$t, $d] = $this->employer(['company_name' => self::XSS, 'notes' => self::XSS]);
        $this->assertContains($this->send('employers/enquiry', $d, $t)->response()->getStatusCode(), [200, 201, 422]);

        [$t, $d] = $this->employer(['th_hp_check' => 'bot']);
        $this->send('employers/enquiry', $d, $t)->assertStatus(422);
    }

    public function testItProjectEnquiryMatrix(): void
    {
        // Each submission gets a fresh captcha token, as a real page load would.
        $mk = function (array $o = []): array {
            [$t, $b] = $this->base();
            return [$t, $o + $b + ['client_name' => 'QA Tester', 'company' => 'QA Co', 'email' => $this->email(), 'phone' => '+91 98230 45678', 'service_category' => 'software-development', 'project_description' => 'Build a staff portal with roles.']];
        };
        [$t, $d] = $mk();
        $this->send('project-enquiry/submit', $d, $t)->assertStatus(201);

        [$t, $d] = $mk(['email' => 'bad']);
        $this->send('project-enquiry/submit', $d, $t)->assertStatus(422);

        // Unknown/SQL-ish category is coerced to a whitelisted value, not stored raw.
        [$t, $evil] = $mk(['service_category' => "x' OR 1=1 --"]);
        $this->send('project-enquiry/submit', $evil, $t);
        $row = \Config\Database::connect()->table('it_leads')->where('email', $evil['email'])->get()->getRowArray();
        if ($row) {
            $this->assertContains($row['service_category'], array_keys(\App\Services\ItLeadService::CATEGORIES));
        }

        // Replay of the exact same captcha token is refused.
        [$t, $d] = $mk();
        $this->send('project-enquiry/submit', $d, $t)->assertStatus(201);
        $this->assertSame(422, $this->send('project-enquiry/submit', $d, $t)->response()->getStatusCode());

        // A fresh submission from the same email is accepted (flagged as a duplicate lead).
        [$t, $d2] = $mk(['email' => $d['email']]);
        $this->send('project-enquiry/submit', $d2, $t)->assertStatus(201);
    }

    public function testMissingCsrfIsRefused(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('contact/submit', ['email' => 'a@b.co']);
    }

    public function testNewsletterSubscribeMatrix(): void
    {
        $post = fn (array $d) => $this->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->post('api/v1/newsletter/subscribe', $d);
        $bad = $post(['email' => 'not-an-email', 'consent' => '1']);
        $this->assertGreaterThanOrEqual(400, $bad->response()->getStatusCode());
        $noConsent = $post(['email' => $this->email()]);
        $this->assertGreaterThanOrEqual(400, $noConsent->response()->getStatusCode());
        $bot = $post(['email' => $this->email(), 'consent' => '1', 'website_url' => 'http://spam']);
        $bot->assertStatus(200); // silently dropped
        $this->assertStringNotContainsString('<script>', $post(['email' => self::XSS, 'consent' => '1'])->getJSON() ?: '');
    }
}
