<?php

namespace Tests\Unit;

use App\Services\CaptchaService;
use App\Services\DocumentVaultService;
use App\Services\FormSecurityService;
use App\Services\RateLimiterService;
use App\Services\TotpService;
use App\Models\EmailOutboxModel;
use CodeIgniter\Test\CIUnitTestCase;

class HardeningTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('phone');
    }

    /**
     * Test Phone Normalization Helper
     */
    public function testPhoneNormalizationIndianAndInternational(): void
    {
        // 10-digit Indian standard
        $this->assertSame('+919876543210', normalize_phone_number('9876543210'));
        $this->assertSame('+919876543210', normalize_phone_number('+91 98765 43210'));
        $this->assertSame('+919876543210', normalize_phone_number('+91-9876543210'));
        $this->assertSame('+919876543210', normalize_phone_number('09876543210'));
        $this->assertSame('+919876543210', normalize_phone_number('919876543210'));
        $this->assertSame('+918765432109', normalize_phone_number('8765432109'));
        $this->assertSame('+917890123456', normalize_phone_number('7890123456'));
        $this->assertSame('+916789012345', normalize_phone_number('6789012345'));

        // International E.164
        $this->assertSame('+14155552671', normalize_phone_number('+1 415 555 2671'));
        $this->assertSame('+442079460958', normalize_phone_number('+44 20 7946 0958'));
        $this->assertSame('+971501234567', normalize_phone_number('+971 50 123 4567'));

        // Invalid numbers
        $this->assertNull(normalize_phone_number('12345'));
        $this->assertNull(normalize_phone_number('abcdefghij'));
        $this->assertNull(normalize_phone_number('0000000000'));
        $this->assertNull(normalize_phone_number('+91123456789')); // Only 9 digits after +91
        $this->assertNull(normalize_phone_number(''));
    }

    /**
     * Test Experience Validation Helper
     */
    public function testExperienceBoundsValidation(): void
    {
        // Valid experience values
        $this->assertNotFalse(validate_experience_years(0));
        $this->assertSame(0.0, validate_experience_years(0));
        $this->assertSame(0.0, validate_experience_years('0'));
        $this->assertSame(5.0, validate_experience_years(5));
        $this->assertSame(5.5, validate_experience_years('5.5'));
        $this->assertSame(12.5, validate_experience_years(12.5));
        $this->assertSame(50.0, validate_experience_years(50));
        $this->assertSame(50.0, validate_experience_years('50.0'));

        // Negative experience rejected
        $this->assertFalse(validate_experience_years(-1));
        $this->assertFalse(validate_experience_years('-0.5'));
        $this->assertFalse(validate_experience_years('-10'));

        // Over 50 years rejected
        $this->assertFalse(validate_experience_years(51));
        $this->assertFalse(validate_experience_years('50.5'));
        $this->assertFalse(validate_experience_years(100));

        // Invalid non-numeric strings rejected
        $this->assertFalse(validate_experience_years('ten'));
        $this->assertFalse(validate_experience_years('five years'));
    }

    /**
     * Test TOTP Service (RFC 6238 & Single-Use Backup Codes)
     */
    public function testTotpServiceGenerationAndVerification(): void
    {
        $totp = new TotpService();
        $secret = $totp->generateSecret();
        $this->assertNotEmpty($secret);
        $this->assertGreaterThanOrEqual(26, strlen($secret));

        // Generate backup codes
        $backupCodes = $totp->generateBackupCodes(5);
        $this->assertCount(5, $backupCodes);

        $jsonCodes = json_encode($backupCodes);
        $testCode = $backupCodes[0];

        // Consume valid code
        $result = $totp->verifyAndConsumeBackupCode($testCode, $jsonCodes);
        $this->assertTrue($result['valid']);

        $remaining = json_decode($result['remaining_codes_json'], true);
        $this->assertCount(4, $remaining);
        $this->assertNotContains($testCode, $remaining);

        // Consume the same code again (must fail - single-use)
        $replayResult = $totp->verifyAndConsumeBackupCode($testCode, $result['remaining_codes_json']);
        $this->assertFalse($replayResult['valid']);

        // Test invalid code
        $badResult = $totp->verifyAndConsumeBackupCode('INVALID-CODE', $jsonCodes);
        $this->assertFalse($badResult['valid']);
    }

    /**
     * Test Form Security Service (Honeypot & Signed Timestamps)
     */
    public function testFormSecurityHoneypotAndTimestamp(): void
    {
        $security = new FormSecurityService();

        // Honeypot detection
        $emptyPost = ['first_name' => 'John', 'th_hp_email' => ''];
        $this->assertFalse($security->isHoneypotTriggered($emptyPost));

        $botPost = ['first_name' => 'Spambot', 'th_hp_email' => 'spam@bot.com'];
        $this->assertTrue($security->isHoneypotTriggered($botPost));

        $legacyBotPost = ['first_name' => 'Spambot', 'website_hp' => 'http://spam.ru'];
        $this->assertTrue($security->isHoneypotTriggered($legacyBotPost));

        // Signed Form Timestamp
        $timestampData = $security->generateSignedTimestamp();
        $this->assertArrayHasKey('th_form_ts', $timestampData);
        $this->assertArrayHasKey('th_form_sig', $timestampData);

        // Valid timestamp verification (with min 0 seconds for unit test)
        $validCheck = $security->verifySignedTimestamp($timestampData['th_form_ts'], $timestampData['th_form_sig'], 0, 7200);
        $this->assertTrue($validCheck['valid']);

        // Tampered timestamp
        $tamperedCheck = $security->verifySignedTimestamp($timestampData['th_form_ts'] + 10, $timestampData['th_form_sig'], 0, 7200);
        $this->assertFalse($tamperedCheck['valid']);
        $this->assertSame('TAMPERED_TIMESTAMP', $tamperedCheck['reason']);

        // Expired timestamp
        $expiredTs = time() - 8000;
        $signingKey = env('app.appKey', env('encryption.key', 'th_secure_signing_salt_2026_pune'));
        $expiredSig = hash_hmac('sha256', (string) $expiredTs, $signingKey);
        $expiredCheck = $security->verifySignedTimestamp($expiredTs, $expiredSig, 0, 7200);
        $this->assertFalse($expiredCheck['valid']);
        $this->assertSame('FORM_EXPIRED', $expiredCheck['reason']);
    }

    /**
     * Test Rate Limiter Service
     */
    public function testRateLimiterPersistentBuckets(): void
    {
        $limiter = new RateLimiterService();
        $testKey = 'test_unit_' . bin2hex(random_bytes(4));

        // Initial hit
        $res1 = $limiter->hit($testKey, 3, 60);
        $this->assertTrue($res1['allowed']);
        $this->assertSame(1, $res1['current']);
        $this->assertSame(2, $res1['remaining']);

        // Second hit
        $res2 = $limiter->hit($testKey, 3, 60);
        $this->assertTrue($res2['allowed']);
        $this->assertSame(2, $res2['current']);

        // Third hit (at limit)
        $res3 = $limiter->hit($testKey, 3, 60);
        $this->assertTrue($res3['allowed']);
        $this->assertSame(3, $res3['current']);
        $this->assertSame(0, $res3['remaining']);

        // Fourth hit (throttled)
        $res4 = $limiter->hit($testKey, 3, 60);
        $this->assertFalse($res4['allowed']);
        $this->assertGreaterThan(0, $res4['retry_after']);
    }

    /**
     * Test Captcha Service Turnstile Verification
     */
    public function testCaptchaTurnstileValidation(): void
    {
        $captcha = new CaptchaService();

        // Empty token must fail
        $emptyRes = $captcha->verify('', 'unit_test', '127.0.0.1');
        $this->assertFalse($emptyRes['success']);
        $this->assertSame('MISSING_TOKEN', $emptyRes['code']);

        // Automated test token (always pass)
        $testToken = 'test_token_valid_' . bin2hex(random_bytes(6));
        $passRes = $captcha->verify($testToken, 'unit_test', '127.0.0.1');
        $this->assertTrue($passRes['success']);

        // Replaying the exact same token must fail replay protection
        $replayRes = $captcha->verify($testToken, 'unit_test', '127.0.0.1');
        $this->assertFalse($replayRes['success']);
        $this->assertSame('TOKEN_REPLAYED', $replayRes['code']);
    }

    /**
     * Test Document Vault Service File Validation
     */
    public function testDocumentVaultFileValidation(): void
    {
        $vault = new DocumentVaultService();

        // 1. Double extension rejection
        $doubleExtCheck = $vault->validateFileProperties('resume.php.pdf', 'application/pdf', 1024);
        $this->assertFalse($doubleExtCheck['valid']);
        $this->assertSame('DOUBLE_EXTENSION_DETECTED', $doubleExtCheck['code']);

        // 2. Traversal rejection
        $traversalCheck = $vault->validateFileProperties('../../etc/passwd.pdf', 'application/pdf', 1024);
        $this->assertFalse($traversalCheck['valid']);
        $this->assertSame('INVALID_FILENAME_CHARACTERS', $traversalCheck['code']);

        // 3. Disallowed extension
        $badExtCheck = $vault->validateFileProperties('exploit.exe', 'application/octet-stream', 1024);
        $this->assertFalse($badExtCheck['valid']);
        $this->assertSame('DISALLOWED_FILE_EXTENSION', $badExtCheck['code']);

        // 4. Oversized file (> 5MB)
        $oversizedCheck = $vault->validateFileProperties('huge.pdf', 'application/pdf', 6 * 1024 * 1024);
        $this->assertFalse($oversizedCheck['valid']);
        $this->assertSame('FILE_EXCEEDS_MAX_SIZE', $oversizedCheck['code']);

        // 5. Valid file properties
        $validCheck = $vault->validateFileProperties('Praveen_Resume_2026.pdf', 'application/pdf', 2 * 1024 * 1024);
        $this->assertTrue($validCheck['valid']);

        // 6. Deep Content Inspection - Fake PDF rejection
        $tempFakePdf = tempnam(sys_get_temp_dir(), 'fake_') . '.pdf';
        file_put_contents($tempFakePdf, '<?php echo "evil script"; ?>');
        $deepFake = $vault->inspectFileContent($tempFakePdf, 'pdf');
        @unlink($tempFakePdf);
        $this->assertFalse($deepFake['valid']);
        $this->assertSame('INVALID_PDF_HEADER', $deepFake['code']);

        // 7. Deep Content Inspection - Valid PDF header
        $tempRealPdf = tempnam(sys_get_temp_dir(), 'real_') . '.pdf';
        file_put_contents($tempRealPdf, "%PDF-1.7\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
        $deepReal = $vault->inspectFileContent($tempRealPdf, 'pdf');
        @unlink($tempRealPdf);
        $this->assertTrue($deepReal['valid']);
    }

    /**
     * Test Email Outbox Atomic Locking & Deduplication
     */
    public function testEmailOutboxAtomicClaiming(): void
    {
        $outboxModel = new EmailOutboxModel();
        $idempotencyKey = 'unit_outbox_' . bin2hex(random_bytes(6));

        // Enqueue an email
        $id1 = $outboxModel->enqueue(
            'test@talenthubconsultant.com',
            'Test Candidate',
            'Unit Test Subject',
            '<p>Unit Test HTML</p>',
            'Unit Test Text',
            'unit_test',
            $idempotencyKey
        );
        $this->assertNotNull($id1);
        $this->assertGreaterThan(0, $id1);

        // Duplicate enqueue with same idempotency key must return the exact same ID
        $id2 = $outboxModel->enqueue(
            'test@talenthubconsultant.com',
            'Test Candidate',
            'Unit Test Subject',
            '<p>Unit Test HTML</p>',
            'Unit Test Text',
            'unit_test',
            $idempotencyKey
        );
        $this->assertSame($id1, $id2);

        // Atomic lock test: exactly 1 worker can transition from PENDING to PROCESSING
        $db = \Config\Database::connect();
        $db->query("UPDATE email_outbox SET status = 'PROCESSING' WHERE id = ? AND status = 'PENDING'", [$id1]);
        $this->assertSame(1, $db->affectedRows());

        $item = $outboxModel->find($id1);
        $this->assertSame('PROCESSING', $item['status']);

        // Concurrent second worker attempting the exact same row gets 0 affected rows
        $db->query("UPDATE email_outbox SET status = 'PROCESSING' WHERE id = ? AND status = 'PENDING'", [$id1]);
        $this->assertSame(0, $db->affectedRows());

        // Clean up test outbox record
        $outboxModel->delete($id1);
    }
}
