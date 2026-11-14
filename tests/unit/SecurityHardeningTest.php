<?php

namespace Tests\Unit;

use App\Services\SafeImageUpload;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * Regression tests for the Milestone-2 security review: uploads, rate-limit coverage, error disclosure,
 * the removed Super-Admin shortcut, outbound-request safety and the admin Content-Security-Policy.
 *
 * @internal
 */
final class SecurityHardeningTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    /** @var list<string> */
    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Services::resetSingle('renderer');
        $this->beginRollbackTx();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
        $this->tmp = [];
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function tmpFile(string $bytes): string
    {
        $p = tempnam(sys_get_temp_dir(), 'thsec');
        file_put_contents($p, $bytes);
        $this->tmp[] = $p;
        return $p;
    }

    /** Minimal valid PNG with the requested size in its IHDR (getimagesize reads only the header, so no GD is needed). */
    private function png(int $w = 4, int $h = 4): string
    {
        $chunk = static fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        $ihdr  = pack('N', $w) . pack('N', $h) . "\x08\x02\x00\x00\x00";
        $idat  = gzcompress(str_repeat("\x00", 1 + 3 * min($w, 4)) . '');
        return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', $ihdr) . $chunk('IDAT', $idat) . $chunk('IEND', '');
    }

    // ── uploads ──────────────────────────────────────────────────────────────
    public function testValidRasterImagesAreAcceptedWithExtensionFromTheRealType(): void
    {
        $png = $this->tmpFile($this->png());
        $r = SafeImageUpload::inspect($png, 'photo.jpg', filesize($png));   // lying name: still stored as png
        $this->assertTrue($r['ok']);
        $this->assertSame('png', $r['ext']);
        $this->assertSame('image/png', $r['mime']);
        $gif = $this->tmpFile(base64_decode('R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='));
        $this->assertSame('gif', SafeImageUpload::inspect($gif, 'a.gif', filesize($gif))['ext']);
    }

    public function testSvgScriptAndFakeImagesAreRefused(): void
    {
        $svg = $this->tmpFile('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.cookie)"><script>alert(1)</script></svg>');
        $this->assertFalse(SafeImageUpload::inspect($svg, 'logo.svg', filesize($svg))['ok'], 'SVG can carry script');
        $this->assertFalse(SafeImageUpload::inspect($svg, 'logo.png', filesize($svg))['ok'], 'SVG renamed to .png');

        $php = $this->tmpFile('<?php system($_GET["c"]); ?>');
        $this->assertFalse(SafeImageUpload::inspect($php, 'shell.jpg', filesize($php))['ok'], 'PHP renamed to .jpg');

        $html = $this->tmpFile('<html><script>alert(1)</script></html>');
        $this->assertFalse(SafeImageUpload::inspect($html, 'x.png', filesize($html))['ok']);

        $png = $this->tmpFile($this->png());
        foreach (['shell.php.png', 'a.phtml', 'a.phar', 'a.svg', 'x.html', 'x.js', 'x.exe', 'x.sh', 'img.PHP.jpg'] as $name) {
            $this->assertFalse(SafeImageUpload::inspect($png, $name, filesize($png))['ok'], "$name must be refused even with valid image bytes");
        }
    }

    public function testImagePolyglotsGetAnImageExtensionNotAScriptOne(): void
    {
        // A GIF header followed by PHP is "an image" to finfo; what protects the server is that the stored extension is forced
        // from the detected type (.gif) and uploads/.htaccess forbids script execution — assert both halves.
        $gif = $this->tmpFile("GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;<?php echo 'pwned'; ?>");
        $r = SafeImageUpload::inspect($gif, 'cat.php.gif', filesize($gif));
        $this->assertFalse($r['ok'], 'double extension is refused outright');
        $r = SafeImageUpload::inspect($gif, 'cat.gif', filesize($gif));
        if ($r['ok']) {
            $this->assertSame('gif', $r['ext']);
        }
        $ht = (string) file_get_contents(FCPATH . 'uploads/.htaccess');
        $this->assertStringContainsString('RemoveHandler', $ht);
        $this->assertStringContainsString('php', strtolower($ht));
        $this->assertStringContainsString('Deny from all', $ht);
    }

    public function testOversizedAndEmptyAndPixelBombFilesAreRefused(): void
    {
        $png = $this->tmpFile($this->png());
        $this->assertFalse(SafeImageUpload::inspect($png, 'a.png', SafeImageUpload::MAX_BYTES + 1)['ok']);
        $this->assertFalse(SafeImageUpload::inspect($png, 'a.png', 0)['ok']);
        $this->assertFalse(SafeImageUpload::inspect('/no/such/file', 'a.png', 10)['ok']);
        $big = $this->tmpFile($this->png(SafeImageUpload::MAX_DIMENSION + 1, 2));
        $this->assertFalse(SafeImageUpload::inspect($big, 'wide.png', filesize($big))['ok'], 'absurd dimensions (decompression bomb)');
    }

    public function testResumeVaultValidatesContentNotJustTheName(): void
    {
        $src = (string) file_get_contents(APPPATH . 'Services/DocumentVaultService.php');
        $this->assertStringContainsString('finfo_file', $src, 'candidate documents are checked by detected MIME');
        $this->assertStringContainsString('MAX_BYTES', $src);
        $this->assertStringContainsString('random', strtolower($src), 'stored under a random name');
        $this->assertStringContainsString("WRITEPATH", $src, 'vault lives outside the web root');
    }

    // ── rate limiting coverage ───────────────────────────────────────────────
    public function testEveryPublicApiWriteIsRateLimitedOrExplicitlyExempt(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();
        // Exempt = authenticated by something stronger than a rate limit, or not a public endpoint.
        $exemptPrefixes = ['api/v1/admin/', 'api/v1/integrations/'];     // admin API (session+RBAC), signed webhook + bearer agent API (own limits)
        $unprotected = [];
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $verb) {
            foreach (array_keys($routes->getRoutes($verb)) as $pattern) {
                if (!str_starts_with($pattern, 'api/')) {
                    continue;
                }
                foreach ($exemptPrefixes as $p) {
                    if (str_starts_with($pattern, $p)) {
                        continue 2;
                    }
                }
                $filters = (array) ($routes->getRoutesOptions($pattern, $verb)['filter'] ?? []);
                $has = false;
                foreach ($filters as $f) {
                    $has = $has || str_starts_with((string) $f, 'ratelimit');
                }
                if (!$has) {
                    $unprotected[] = "$verb $pattern";
                }
            }
        }
        $this->assertSame([], $unprotected, "public API writes without a rate limit:\n" . implode("\n", $unprotected));
    }

    public function testAdminPasswordResetApiIsThrottledLikeTheWebForm(): void
    {
        $routes = service('routes');
        $routes->loadRoutes();
        foreach (['api/v1/admin/forgot-password', 'api/v1/admin/reset-password', 'api/v1/admin/login'] as $p) {
            $f = (array) ($routes->getRoutesOptions($p, 'POST')['filter'] ?? []);
            $this->assertNotEmpty(array_filter($f, static fn ($x) => str_starts_with((string) $x, 'ratelimit')), "$p must be rate limited");
        }
    }

    // ── no internal error text reaches visitors ──────────────────────────────
    public function testNoControllerEchoesExceptionMessagesToClients(): void
    {
        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH . 'Controllers'));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach (file($file->getPathname()) as $n => $line) {
                if (preg_match('/(respondError|respondJson|setJSON|redirect\(\)|base_url\()[^;]*\$e->getMessage\(\)/', $line) && !str_contains($line, '$internal') && !str_contains($line, 'sanitize(')) {
                    $offenders[] = str_replace(APPPATH, '', $file->getPathname()) . ':' . ($n + 1);
                }
            }
        }
        $this->assertSame([], $offenders, "exception text is being sent to clients:\n" . implode("\n", $offenders));
    }

    // ── removed Super Admin shortcut ─────────────────────────────────────────
    public function testUserIdOneOrNamedAdminIsNoLongerSuperAdminWithoutTheRole(): void
    {
        $legacy = ['admin_user' => ['id' => 1, 'username' => 'admin', 'name' => 'Admin', 'email' => 'a@example.invalid',
            'roles' => [['id' => 2, 'name' => 'Recruiter', 'slug' => 'recruiter']], 'permissions' => ['jobs.publish']]];
        foreach (['admin/agents', 'admin/system/health', 'admin/social/settings', 'admin/users', 'admin/roles', 'admin/settings', 'admin/audit-logs', 'admin/integrations'] as $uri) {
            \Config\Services::resetSingle('renderer');
            $code = $this->withSession($legacy)->call('get', $uri)->response()->getStatusCode();
            $this->assertNotSame(200, $code, "id 1 / 'admin' without the Super Admin role must not open /$uri");
        }
        $real = $legacy;
        $real['admin_user']['roles'] = [['id' => 1, 'name' => 'Super Administrator', 'slug' => 'super_admin']];
        \Config\Services::resetSingle('renderer');
        $this->assertSame(200, $this->withSession($real)->call('get', 'admin/agents')->response()->getStatusCode(), 'the real role still works');
    }

    // ── outbound requests (SSRF) ─────────────────────────────────────────────
    public function testOutboundHttpClientsUseFixedBasesVerifyTlsAndNeverFollowRedirects(): void
    {
        foreach (['Social/LinkedInApiService', 'Social/InstagramApiService', 'HostingerMailService', 'CaptchaService'] as $svc) {
            $src = (string) file_get_contents(APPPATH . "Services/$svc.php");
            $this->assertStringNotContainsString('CURLOPT_FOLLOWLOCATION, true', $src, "$svc must not follow redirects");
            $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER, false', $src, "$svc must verify TLS");
            $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYHOST, 0', $src);
            $this->assertStringNotContainsString('verify\' => false', $src);
        }
        // the agent API and admin media never fetch a user-supplied URL
        foreach (['Agent/AgentSocialService', 'Agent/AgentDraftValidator', 'Agent/AgentApprovalService', 'SafeImageUpload'] as $svc) {
            $src = (string) file_get_contents(APPPATH . "Services/$svc.php");
            foreach (['curl_init', 'file_get_contents(\'http', 'fopen(', 'curlrequest', 'fsockopen', 'SoapClient', 'simplexml_load_file'] as $banned) {
                $this->assertStringNotContainsString($banned, $src, "$svc must not perform network I/O ($banned)");
            }
        }
    }

    // ── public pages stay safe ───────────────────────────────────────────────
    public function testProductionDoesNotDisplayErrors(): void
    {
        $boot = (string) file_get_contents(APPPATH . 'Config/Boot/production.php');
        $this->assertStringContainsString("ini_set('display_errors', '0')", $boot);
    }
}
