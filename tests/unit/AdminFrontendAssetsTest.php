<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * The admin portal serves a pre-built Tailwind file instead of the runtime CDN compiler, loads Chart.js / TinyMCE only where used,
 * keeps the Tiny Cloud key in configuration, and has a stricter Content-Security-Policy than the public site.
 *
 * @internal
 */
final class AdminFrontendAssetsTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    private string $originalKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Services::resetSingle('renderer');
        $this->originalKey = config('Editor')->tinymceApiKey;
        $this->beginRollbackTx();
    }

    protected function tearDown(): void
    {
        config('Editor')->tinymceApiKey = $this->originalKey;
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function superAdmin(): array
    {
        return ['admin_user' => ['id' => 7771, 'username' => 'fe_tester', 'name' => 'FE Tester', 'email' => 'fe@example.invalid',
            'roles' => [['id' => 1, 'name' => 'Super Administrator', 'slug' => 'super_admin']], 'permissions' => []]];
    }

    private function page(string $uri)
    {
        \Config\Services::resetSingle('renderer');
        return $this->withSession($this->superAdmin())->call('get', $uri);
    }

    public function testAdminPagesUseTheBuiltStylesheetAndNoTailwindCdn(): void
    {
        foreach (['admin', 'admin/system/health', 'admin/jobs', 'admin/social/posts', 'admin/engagement/analytics/report'] as $uri) {
            $res  = $this->page($uri);
            $body = (string) $res->getBody();
            $this->assertSame(200, $res->response()->getStatusCode(), $uri);
            $this->assertStringNotContainsString('cdn.tailwindcss.com', $body, "$uri must not load the Tailwind CDN");
            $this->assertStringNotContainsString('tailwind.config', $body, "$uri must not configure the runtime compiler");
            $this->assertStringContainsString('assets/css/admin-tailwind.css?v=', $body, "$uri must link the built stylesheet");
        }
    }

    public function testBuiltStylesheetExistsAndContainsTheBrandAndCoreUtilities(): void
    {
        $css = FCPATH . 'assets/css/admin-tailwind.css';
        $this->assertFileExists($css);
        $this->assertLessThan(200 * 1024, filesize($css), 'a built stylesheet is far smaller than the ~400 KB runtime compiler');
        $txt = (string) file_get_contents($css);
        foreach (['.bg-brand-primary', '.text-brand-accent', '.md\\:static', '.hover\\:bg-white\\/5', '.-translate-x-full', '.rounded-xl', '.font-extrabold'] as $needle) {
            $this->assertStringContainsString($needle, $txt, "missing utility $needle");
        }
        $this->assertStringNotContainsString('sourceMappingURL', $txt);
    }

    public function testAdminCspIsStricterThanThePublicOne(): void
    {
        $admin = $this->page('admin')->response()->getHeaderLine('Content-Security-Policy');
        $this->assertStringNotContainsString("'unsafe-eval'", $admin, 'no runtime eval in the admin area');
        $this->assertStringNotContainsString('tailwindcss.com', $admin);
        $this->assertStringNotContainsString('tawk.to', $admin);
        $this->assertStringNotContainsString('googletagmanager', $admin);
        $this->assertStringContainsString("object-src 'none'", $admin);
        $this->assertStringContainsString("frame-ancestors 'self'", $admin);
        $this->assertStringContainsString('cdn.tiny.cloud', $admin);

        \Config\Services::resetSingle('renderer');
        $public = $this->call('get', '/')->response()->getHeaderLine('Content-Security-Policy');
        $this->assertStringNotContainsString('tailwindcss.com', $public, 'the CDN host is removed from the public policy too');
        $this->assertStringContainsString('tawk.to', $public, 'public integrations are unchanged');
    }

    public function testHeavyScriptsLoadOnlyWhereTheyAreUsed(): void
    {
        config('Editor')->tinymceApiKey = str_repeat('a', 48);
        $health = (string) $this->page('admin/system/health')->getBody();
        $this->assertStringNotContainsString('chart.umd', $health, 'no charts on the health page → no Chart.js');
        $this->assertStringNotContainsString('cdn.tiny.cloud', $health, 'no editor on the health page → no TinyMCE');

        $jobForm = (string) $this->page('admin/jobs/new')->getBody();
        $this->assertStringContainsString('cdn.tiny.cloud/1/' . str_repeat('a', 48) . '/tinymce/7/tinymce.min.js', $jobForm, 'the job form uses the editor');

        $dash = (string) $this->page('admin')->getBody();
        $this->assertStringContainsString('chart.umd', $dash, 'the dashboard draws charts');
    }

    public function testEditorIsSimplyAbsentWhenNoKeyIsConfigured(): void
    {
        config('Editor')->tinymceApiKey = '';
        $res  = $this->page('admin/jobs/new');
        $body = (string) $res->getBody();
        $this->assertSame(200, $res->response()->getStatusCode());
        $this->assertStringNotContainsString('cdn.tiny.cloud', $body);
        $this->assertStringContainsString("typeof tinymce !== 'undefined'", $body, 'the page guards its init, so the plain textarea keeps working');
        $this->assertStringContainsString('id="job-description"', $body);
    }

    public function testEditorKeyValidationAndNoHardCodedKeyInSource(): void
    {
        $c = new \Config\Editor();
        foreach (['', 'short', 'UPPERCASEKEY' . str_repeat('A', 30), str_repeat('a', 29), str_repeat('a', 65), str_repeat('a', 40) . '"><script>alert(1)</script>', 'a b c' . str_repeat('a', 40)] as $bad) {
            $c->tinymceApiKey = $bad;
            $this->assertFalse($c->isTinyMceEnabled(), 'rejected: ' . substr($bad, 0, 20));
        }
        $c->tinymceApiKey = str_repeat('a1', 24);
        $this->assertTrue($c->isTinyMceEnabled());
        $this->assertSame('https://cdn.tiny.cloud/1/' . str_repeat('a1', 24) . '/tinymce/7/tinymce.min.js', $c->tinymceScriptUrl());
        $c->tinymceVersion = '7"><x';
        $this->assertStringNotContainsString('"', $c->tinymceScriptUrl());

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH));
        foreach ($it as $f) {
            if (!in_array($f->getExtension(), ['php', 'js', 'html'], true)) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression('~cdn\.tiny\.cloud/1/[a-z0-9]{30,}/~', (string) file_get_contents($f->getPathname()), $f->getPathname() . ' contains a hard-coded Tiny Cloud key');
        }
    }

    public function testOfflinePageAndServiceWorkerNeedNoCdn(): void
    {
        $offline = (string) file_get_contents(FCPATH . 'offline.html');
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $offline, 'the offline page is shown when the network is down');
        $this->assertStringContainsString('/assets/css/admin-tailwind.css', $offline);
        foreach ([FCPATH . 'sw.js', ROOTPATH . 'sw.js'] as $sw) {
            $src = (string) file_get_contents($sw);
            $this->assertStringContainsString("'/assets/css/admin-tailwind.css'", $src, 'precached so the offline page can render');
            $this->assertStringContainsString("talenthub-cache-v5", $src, 'cache version bumped so clients fetch the new precache list');
        }
        $this->assertSame(file_get_contents(FCPATH . 'sw.js'), file_get_contents(ROOTPATH . 'sw.js'), 'the two service-worker copies stay identical');
    }

    public function testBuildToolingIsPinnedAndDocumented(): void
    {
        $pkg = json_decode((string) file_get_contents(ROOTPATH . 'tools/tailwind/package.json'), true);
        $this->assertSame('3.4.17', $pkg['devDependencies']['tailwindcss'], 'pinned to the version the CDN served, so output is identical');
        $this->assertFileExists(ROOTPATH . 'tools/tailwind/package-lock.json');
        $this->assertFileExists(ROOTPATH . 'tools/tailwind/README.md');
        $this->assertStringContainsString('node_modules/', (string) file_get_contents(ROOTPATH . '.gitignore'));
    }

    public function testSectionStylePagesRenderTheirContentExactlyOnceInsideTheBody(): void
    {
        // Regression: peeking at a section with renderSection() consumes it unless saveData=true, which once blanked every extend-style (social) page.
        $body = (string) $this->page('admin/social/posts')->getBody();
        $marker = 'Manage, review, reschedule';
        $this->assertSame(1, substr_count($body, $marker), 'the page content must appear exactly once');
        $this->assertGreaterThan(strpos($body, '<body'), strpos($body, $marker), 'and after <body>, not inside <head>');
        $head = substr($body, 0, (int) strpos($body, '<body'));
        $this->assertStringNotContainsString($marker, $head);
        $this->assertStringContainsString('chart.umd', (string) $this->page('admin/social/analytics')->getBody(), 'section-style pages that draw charts still get Chart.js');
    }
}
