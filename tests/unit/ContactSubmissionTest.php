<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ContactSubmissionTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testContactSubmissionWithOptionalEmptyMessage(): void
    {
        $sec = new \App\Services\FormSecurityService();
        $tsSig = $sec->generateSignedTimestamp();
        $token = csrf_hash();
        $cookieName = config('Security')->cookieName;
        $_COOKIE[$cookieName] = $token;

        $uniqueEmail = 'contact_test_' . bin2hex(random_bytes(4)) . '@talenthubconsultant.com';
        $postData = [
            csrf_token()            => $token,
            'first_name'            => 'Praveen',
            'last_name'             => 'Chavan',
            'email'                 => $uniqueEmail,
            'phone'                 => '+91 98230 45678',
            'subject'               => 'Corporate Staffing Mandate',
            'message'               => '', // STRICTLY EMPTY OPTIONAL MESSAGE
            'consent'               => '1',
            'th_hp_check'           => '',
            'th_hp_website'         => '',
            'th_form_ts'            => $tsSig['th_form_ts'],
            'th_form_sig'           => $tsSig['th_form_sig'],
            'cf-turnstile-response' => 'test_token_valid_' . bin2hex(random_bytes(6)),
        ];

        $result = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
            'X-CSRF-TOKEN'     => $token,
        ])->post('contact/submit', $postData);

        $result->assertStatus(200);

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertStringContainsString('Thank you', $json['message']);
        $this->assertNotEmpty($json['csrf_token']);
        $this->assertNotEmpty($json['csrf_hash']);
    }

    public function testContactSubmissionValidationErrorIncludesCsrfTokens(): void
    {
        $sec = new \App\Services\FormSecurityService();
        $tsSig = $sec->generateSignedTimestamp();
        $token = csrf_hash();
        $cookieName = config('Security')->cookieName;
        $_COOKIE[$cookieName] = $token;

        $postData = [
            csrf_token()            => $token,
            'first_name'            => 'Praveen',
            'last_name'             => 'Chavan',
            'email'                 => 'invalid_email_format', // Will trigger 422
            'phone'                 => '+91 98230 45678',
            'subject'               => 'Corporate Staffing Mandate',
            'message'               => '',
            'consent'               => '1',
            'th_hp_check'           => '',
            'th_hp_website'         => '',
            'th_form_ts'            => $tsSig['th_form_ts'],
            'th_form_sig'           => $tsSig['th_form_sig'],
            'cf-turnstile-response' => 'test_token_valid_' . bin2hex(random_bytes(6)),
        ];

        $result = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
            'X-CSRF-TOKEN'     => $token,
        ])->post('contact/submit', $postData);

        $result->assertStatus(422);

        $json = json_decode($result->getJSON(), true);
        $this->assertFalse($json['success']);
        $this->assertSame('VALIDATION_FAILED', $json['code']);
        // Crucial test: Ensure csrf_token and csrf_hash are always returned on 422 to prevent desynchronization
        $this->assertNotEmpty($json['csrf_token']);
        $this->assertNotEmpty($json['csrf_hash']);
    }
}
