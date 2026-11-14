<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Database\Migrations\CreateSocialMarketingTables;
use App\Services\Social\SocialSecurityService;
use App\Services\Social\SocialSettingsService;

require_once APPPATH . 'Database/Migrations/20261110000001_create_social_marketing_tables.php';

class SocialSecurityAndEncryptionTest extends CIUnitTestCase
{
    protected $db;
    protected SocialSecurityService $security;
    protected SocialSettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect();
        CreateSocialMarketingTables::apply($this->db);

        // Configure deterministic raw key for test suite
        SocialSecurityService::useKeyForTesting('hex2bin:848a6062f68dd99f8295b93d052a65a3d76e336b95ee385c2923769931fc8017');
        $this->security = new SocialSecurityService($this->db);
        $this->settings = new SocialSettingsService($this->db, $this->security);
    }

    protected function tearDown(): void
    {
        SocialSecurityService::useKeyForTesting(null);
        parent::tearDown();
    }

    public function testEncryptionAndDecryptionAtRest(): void
    {
        $plain = 'sk_live_super_secret_oauth_token_12345';
        $encrypted = $this->security->encryptSecret($plain);

        $this->assertNotEquals($plain, $encrypted);
        $this->assertTrue(base64_decode($encrypted, true) !== false);

        $decrypted = $this->security->decryptSecret($encrypted);
        $this->assertEquals($plain, $decrypted);
    }

    public function testSecretMasking(): void
    {
        $secret = 'AQD0123456789abcdefghijklmnopqrstuvwxyz';
        $masked = $this->security->maskSecret($secret, 4);

        $this->assertStringContainsString('••••••••', $masked);
        $this->assertStringEndsWith('wxyz', $masked);
        $this->assertStringNotContainsString('abcdefghij', $masked);
    }

    public function testOAuthStateGenerationAndValidation(): void
    {
        $userId = 42;
        $redirect = 'https://talenthubconsultant.com/admin/social/oauth/callback/linkedin';

        $state = $this->security->generateOAuthState('linkedin', $userId, $redirect);
        $this->assertNotEmpty($state);
        $this->assertEquals(64, strlen($state)); // 32 bytes hex = 64 chars

        // First validation must pass
        $val1 = $this->security->validateOAuthState($state, 'linkedin', $userId);
        $this->assertTrue($val1['valid']);
        $this->assertEquals($userId, $val1['user_id']);

        // Second validation must fail due to replay defense
        $val2 = $this->security->validateOAuthState($state, 'linkedin', $userId);
        $this->assertFalse($val2['valid']);
        $this->assertEquals('INVALID_OR_EXPIRED_STATE', $val2['code']);
    }

    public function testPayloadSanitizationStripsSensitiveTokens(): void
    {
        $payload = [
            'client_id'     => 'test_client_id',
            'client_secret' => 'super_secret_password',
            'access_token'  => 'AQD_token_string_here',
            'details'       => [
                'auth_token' => 'nested_token',
                'public_id'  => 12345,
            ]
        ];

        $sanitized = $this->security->sanitizePayload($payload);
        $this->assertEquals('[REDACTED]', $sanitized['client_secret']);
        $this->assertEquals('[REDACTED]', $sanitized['access_token']);
        $this->assertEquals('[REDACTED]', $sanitized['details']['auth_token']);
        $this->assertEquals(12345, $sanitized['details']['public_id']);
    }

    public function testMediaValidationRejectsNonWhitelistedExtensions(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_php');
        file_put_contents($tempFile, '<?php echo "hello"; ?>');

        $result = $this->security->validateMediaFile($tempFile, 'malicious.php');
        unlink($tempFile);

        $this->assertFalse($result['valid']);
        $this->assertEquals('UNSUPPORTED_EXTENSION', $result['code']);
    }

    public function testHealthCheckerRunsWithoutExceptions(): void
    {
        $health = $this->settings->runHealthCheck();
        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('encryption_key', $health['checks']);
        $this->assertEquals('pass', $health['checks']['encryption_key']['status']);
    }
}

