<?php

namespace Tests\Unit;

use App\Services\ItLeadService;
use App\Services\ProductService;
use App\Services\SitemapService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Product catalogue: slugs, URL/video validation, upload validation, published-only visibility,
 * permissions, XSS escaping, enquiry -> CRM lead, and the seeded WorkSphere flagship.
 * Test products use the slug prefix "zz-test" and test leads the reserved domain @product-test.invalid;
 * both are removed in tearDown. Run against a local test database only (database.tests.database).
 *
 * @internal
 */
final class ProductCatalogueTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const DOMAIN = '@product-test.invalid';

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    protected function setUp(): void
    {
        parent::setUp();
        new ProductService(); // lazy-ensure schema
        if ($this->db()->tableExists('rate_limit_buckets')) {
            $this->db()->query('DELETE FROM rate_limit_buckets');
        }
    }

    protected function tearDown(): void
    {
        $db = $this->db();
        $db->query("DELETE FROM products WHERE slug LIKE 'zz-test%'");
        if ($db->tableExists('it_leads')) {
            $db->query('DELETE FROM it_leads WHERE email LIKE ?', ['%' . self::DOMAIN]);
        }
        $db->query("DELETE FROM audit_logs WHERE entity_type = 'product' AND new_values LIKE '%zz-test%'");
        $dir = FCPATH . 'uploads/products';
        foreach (glob($dir . '/zz-*') ?: [] as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function make(string $name, string $status = 'published', array $over = []): int
    {
        $svc = new ProductService();
        $r   = $svc->sanitize(['name' => $name, 'slug' => $over['slug'] ?? '', 'short_description' => 'Short text for ' . $name, 'status' => $status] + $over);
        $this->assertSame([], $r['errors'], json_encode($r['errors']));
        return $svc->save(null, $r['data'], [], 1);
    }

    private function admin(array $perms, int $id = 2, string $slug = 'limited'): array
    {
        return ['admin_user' => [
            'id' => $id, 'username' => 'tester', 'name' => 'Tester', 'email' => 't@example.com',
            'roles' => [['id' => 99, 'name' => 'Tester', 'slug' => $slug]], 'permissions' => $perms,
        ]];
    }

    /** Writes a minimal valid PNG (no GD needed) and returns its path. */
    private function tinyPng(int $w = 20, int $h = 20): string
    {
        $raw = '';
        for ($y = 0; $y < $h; $y++) {
            $raw .= "\x00" . str_repeat("\x1d\x6a\x34", $w);
        }
        $chunk = static fn (string $t, string $d) => pack('N', strlen($d)) . $t . $d . pack('N', crc32($t . $d));
        $png   = "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0)) . $chunk('IDAT', gzcompress($raw)) . $chunk('IEND', '');
        $path  = tempnam(sys_get_temp_dir(), 'zzpng');
        file_put_contents($path, $png);
        return $path;
    }

    // ---------------------------------------------------------------- schema + seed

    public function testWorkSphereFlagshipIsSeededAndPublished(): void
    {
        $svc = new ProductService();
        $p   = $svc->findBySlug('worksphere');
        $this->assertNotNull($p, 'WorkSphere must be seeded');
        $this->assertSame('published', $p['status']);
        $this->assertSame(1, (int) $p['is_flagship']);
        $this->assertMatchesRegularExpression('#^https://#', (string) $p['signup_url']);
        $full = $svc->withChildren($p);
        $this->assertNotEmpty($full['features']);
        $this->assertNotEmpty($full['modules']);
        $this->assertNotEmpty($full['faqs']);
        // No invented proof points anywhere in the seeded copy.
        $blob = strtolower(json_encode($full));
        foreach (['iso 27001', 'soc 2', '99.9', 'trusted by', 'customers worldwide', 'testimonial'] as $bad) {
            $this->assertStringNotContainsString($bad, $blob);
        }
    }

    public function testPermissionsAreRegisteredForSuperAdmin(): void
    {
        $codes = array_column($this->db()->table('permissions')->whereIn('code', ['products.view', 'products.manage'])->get()->getResultArray(), 'code');
        sort($codes);
        $this->assertSame(['products.manage', 'products.view'], $codes);
        $granted = $this->db()->query("SELECT COUNT(*) c FROM role_permissions rp JOIN roles r ON r.id = rp.role_id JOIN permissions p ON p.id = rp.permission_id
            WHERE r.slug = 'super_admin' AND p.code IN ('products.view','products.manage')")->getRow()->c;
        $this->assertSame(2, (int) $granted);
    }

    public function testLegacyMigrationNoLongerArchivesNewProducts(): void
    {
        require_once APPPATH . 'Database/Migrations/20261106000001_two_service_structure.php';
        \App\Database\Migrations\TwoServiceStructure::apply($this->db());
        $this->assertSame('published', (new ProductService())->findBySlug('worksphere')['status']);
    }

    // ---------------------------------------------------------------- slugs

    public function testSlugGenerationAndUniqueness(): void
    {
        $a = $this->make('Zz-Test Alpha Product');
        $b = $this->make('Zz-Test Alpha Product');
        $svc = new ProductService();
        $sa  = $svc->find($a)['slug'];
        $sb  = $svc->find($b)['slug'];
        $this->assertSame('zz-test-alpha-product', $sa);
        $this->assertNotSame($sa, $sb);
        $this->assertSame('zz-test-alpha-product-2', $sb);
        // Soft-deleted rows still hold their slug (DB unique index), so the next one must not collide.
        $svc->softDelete($b, 1);
        $this->assertSame('zz-test-alpha-product-3', $svc->uniqueSlug('Zz-Test Alpha Product'));
        // Editing a product keeps its own slug.
        $this->assertSame($sa, $svc->uniqueSlug($sa, $a));
        $this->assertSame('new-product', $svc->uniqueSlug('new'), 'reserved route words are avoided');
        $this->assertSame('product', ProductService::slugify('???'));
    }

    // ---------------------------------------------------------------- URL + video validation

    public function testUrlValidatorAllowsOnlyHttpAndHttps(): void
    {
        foreach (['https://example.com/signup', 'http://example.com', 'https://sub.example.co.in/a?b=1#c'] as $ok) {
            $this->assertTrue(ProductService::validateUrl($ok), $ok);
        }
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html;base64,PHNjcmlwdD4=', 'file:///etc/passwd', 'ftp://example.com',
                  '//evil.example.com', '/relative', 'https://', 'https://user:pass@example.com', "https://exa mple.com", 'https://example.com/"onmouseover=x',
                  'https://example.com/<script>', "https://example.com/a\x00b", '', str_repeat('a', 600)] as $bad) {
            $this->assertFalse(ProductService::validateUrl($bad), $bad);
        }
        $this->assertTrue(ProductService::validateUrl('/contact', true));
        $this->assertFalse(ProductService::validateUrl('//evil.example.com', true));
        $this->assertFalse(ProductService::validateUrl('javascript:alert(1)', true));
    }

    public function testVideoOnlyYouTubeAndVimeoBecomeEmbeds(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', ProductService::videoEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', ProductService::videoEmbedUrl('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertSame('https://player.vimeo.com/video/123456789', ProductService::videoEmbedUrl('https://vimeo.com/123456789'));
        foreach (['https://evil.example.com/watch?v=dQw4w9WgXcQ', 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ', 'https://example.com/video.mp4',
                  'javascript:alert(1)', 'https://www.youtube.com/watch?v=<script>', 'https://www.youtube.com/watch?v=short', 'https://vimeo.com/abc', 'https://www.dailymotion.com/video/x1'] as $bad) {
            $this->assertNull(ProductService::videoEmbedUrl($bad), $bad);
        }
        $r = (new ProductService())->sanitize(['name' => 'Zz-Test Video', 'hero_video_url' => 'https://evil.example.com/v']);
        $this->assertArrayHasKey('hero_video_url', $r['errors']);
    }

    public function testFormSanitizerRejectsBadLinksAndRequiresMatchingCtaData(): void
    {
        $svc = new ProductService();
        $r = $svc->sanitize(['name' => 'Zz-Test Links', 'signup_url' => 'javascript:alert(1)', 'docs_url' => 'data:text/html,x', 'cta_url' => 'file:///x', 'primary_cta_type' => 'demo']);
        foreach (['signup_url', 'docs_url', 'cta_url'] as $f) {
            $this->assertArrayHasKey($f, $r['errors'], $f);
        }
        $this->assertArrayHasKey('name', $svc->sanitize(['name' => 'x'])['errors']);
        $ok = $svc->sanitize(['name' => 'Zz-Test Ok', 'primary_cta_type' => 'external', 'cta_url' => '/contact', 'status' => 'bogus']);
        $this->assertSame([], $ok['errors']);
        $this->assertSame('draft', $ok['data']['status'], 'unknown status falls back to draft');
    }

    public function testMediaRowsCannotReferenceForeignOrArbitraryPaths(): void
    {
        $svc  = new ProductService();
        $rows = $svc->sanitizeMedia([
            ['kind' => 'screenshot', 'path_or_url' => '../../.env'],
            ['kind' => 'screenshot', 'path_or_url' => 'uploads/products/999/' . str_repeat('a', 32) . '.png'],
            ['kind' => 'screenshot', 'path_or_url' => 'uploads/products/5/' . str_repeat('a', 32) . '.png', 'caption' => 'Ok'],
            ['kind' => 'video', 'path_or_url' => 'https://evil.example.com/x'],
            ['kind' => 'video', 'path_or_url' => 'https://youtu.be/dQw4w9WgXcQ'],
        ], 5);
        $this->assertCount(2, $rows);
        $this->assertSame('Ok', $rows[0]['caption']);
        // Only files we generated are ever deleted.
        ProductService::deleteStoredFile('../../.env');
        ProductService::deleteStoredFile('assets/css/app.css');
        $this->assertFileExists(FCPATH . 'assets/css/app.css');
    }

    // ---------------------------------------------------------------- uploads

    public function testUploadRejectsPhpFileRenamedToJpg(): void
    {
        $f = tempnam(sys_get_temp_dir(), 'zzphp');
        file_put_contents($f, "<?php echo 'pwn'; ?>");
        $r = ProductService::validateImageFile($f, 'shell.jpg', filesize($f));
        $this->assertFalse($r['ok']);
        // Polyglot: JPEG magic bytes followed by PHP.
        file_put_contents($f, "\xFF\xD8\xFF\xE0" . "<?php system(\$_GET['c']); ?>");
        $this->assertFalse(ProductService::validateImageFile($f, 'poly.jpg', filesize($f))['ok']);
        @unlink($f);
    }

    public function testUploadRejectsDisallowedTypesNamesAndSizes(): void
    {
        $png = $this->tinyPng();
        $size = filesize($png);
        $this->assertTrue(ProductService::validateImageFile($png, 'shot.png', $size)['ok']);
        $this->assertSame('png', ProductService::validateImageFile($png, 'wrongext.jpg', $size)['ext'], 'extension comes from the real image type');
        foreach (['x.svg', 'x.php', 'x.exe', 'x.gif', 'x.php.png', 'x.png.php', 'x.html', 'noext', 'x.phtml'] as $name) {
            $this->assertFalse(ProductService::validateImageFile($png, $name, $size)['ok'], $name);
        }
        $this->assertFalse(ProductService::validateImageFile($png, 'big.png', ProductService::MAX_IMAGE_BYTES + 1)['ok']);
        $this->assertFalse(ProductService::validateImageFile($png, 'logo.png', ProductService::MAX_LOGO_BYTES + 1, 'logo')['ok']);
        $this->assertTrue(ProductService::validateImageFile($png, 'logo.png', ProductService::MAX_LOGO_BYTES - 1, 'logo')['ok']);
        $this->assertFalse(ProductService::validateImageFile($png, 'zero.png', 0)['ok']);
        $this->assertFalse(ProductService::validateImageFile('/no/such/file.png', 'a.png', 100)['ok']);
        $tiny = $this->tinyPng(4, 4);
        $this->assertFalse(ProductService::validateImageFile($tiny, 'tiny.png', filesize($tiny))['ok'], 'below minimum dimensions');
        $svg = tempnam(sys_get_temp_dir(), 'zzsvg');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->assertFalse(ProductService::validateImageFile($svg, 'a.png', filesize($svg))['ok'], 'SVG content disguised as PNG');
        foreach ([$png, $tiny, $svg] as $f) { @unlink($f); }
    }

    // ---------------------------------------------------------------- visibility

    public function testOnlyPublishedProductsAreListedAndInSitemap(): void
    {
        $pub = $this->make('Zz-Test Published');
        $this->make('Zz-Test Draft', 'draft');
        $this->make('Zz-Test Archived', 'archived');
        $noi = $this->make('Zz-Test NoIndex', 'published', ['no_index' => '1']);
        $del = $this->make('Zz-Test Deleted');
        $svc = new ProductService();
        $svc->softDelete($del, 1);

        $slugs = array_column($svc->publicList(), 'slug');
        $this->assertContains('zz-test-published', $slugs);
        $this->assertContains('zz-test-noindex', $slugs);
        foreach (['zz-test-draft', 'zz-test-archived', 'zz-test-deleted'] as $hidden) {
            $this->assertNotContains($hidden, $slugs);
        }
        $this->assertContains('worksphere', $slugs);
        $this->assertSame('worksphere', $slugs[0], 'flagship is listed first');

        $xml = (new SitemapService())->generatePagesXml();
        $this->assertStringContainsString('/products/worksphere', $xml);
        $this->assertStringContainsString('/products/zz-test-published', $xml);
        foreach (['zz-test-draft', 'zz-test-archived', 'zz-test-deleted', 'zz-test-noindex'] as $hidden) {
            $this->assertStringNotContainsString('/products/' . $hidden, $xml);
        }
        $this->assertNotNull($svc->findPublished('zz-test-published'));
        $this->assertNull($svc->findPublished('zz-test-draft'));
        $this->assertGreaterThan(0, $pub + $noi);
    }

    public function testPublicRoutesDraftArchivedDeletedAre404AndPublishedIs200(): void
    {
        $this->make('Zz-Test Visible');
        $this->make('Zz-Test Hidden Draft', 'draft');
        $this->make('Zz-Test Hidden Archived', 'archived');
        $gone = $this->make('Zz-Test Hidden Deleted');
        (new ProductService())->softDelete($gone, 1);

        $r = $this->get('products');
        $r->assertStatus(200);
        $r->assertSee('WorkSphere');
        $r->assertSee('Zz-Test Visible');
        $r->assertDontSee('Zz-Test Hidden');
        $r = $this->get('products/zz-test-visible');
        $r->assertStatus(200);
        $r->assertSee('Zz-Test Visible');
        foreach (['zz-test-hidden-draft', 'zz-test-hidden-archived', 'zz-test-hidden-deleted', 'does-not-exist'] as $slug) {
            $code = null;
            try {
                $code = $this->get('products/' . $slug)->response()->getStatusCode();
            } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
                $code = 404;
            }
            $this->assertSame(404, $code, $slug);
        }
    }

    public function testLegacyProductSlugsStillRedirect(): void
    {
        $res = $this->get('products/societyhub');
        $res->assertStatus(301);
        $this->assertStringContainsString('services/it-services', $res->response()->getHeaderLine('Location'));
        $this->get('products/peoplehub')->assertStatus(301);
    }

    public function testFlagshipPageRendersRichLayoutWithHonestDemoText(): void
    {
        $res = $this->get('products/worksphere');
        $res->assertStatus(200);
        $res->assertSee('Start free 7-day demo');
        $res->assertSee('Talk to our team');
        $res->assertSee('What is included in the 7-day demo');
        $res->assertSee('application/ld+json');
        $res->assertSee('FAQPage');
        $res->assertSee('SoftwareApplication');
        $res->assertSee('/products/worksphere');
        $this->assertStringNotContainsString('noindex', strtolower($res->response()->getBody()) === '' ? '' : (preg_match('#<meta name="robots" content="([^"]*)"#', $res->response()->getBody(), $m) ? $m[1] : ''));
    }

    public function testNoIndexProductEmitsNoindexMeta(): void
    {
        $this->make('Zz-Test Hidden From Search', 'published', ['no_index' => '1']);
        $res = $this->get('products/zz-test-hidden-from-search');
        $res->assertStatus(200);
        $this->assertMatchesRegularExpression('#<meta name="robots" content="noindex#', $res->response()->getBody());
    }

    // ---------------------------------------------------------------- XSS

    public function testDetailTemplateEscapesAllUserText(): void
    {
        $xss = '<script>alert("x")</script>';
        $svc = new ProductService();
        $r   = $svc->sanitize([
            'name' => 'Zz-Test ' . $xss, 'slug' => 'zz-test-xss', 'tagline' => '<img src=x onerror=alert(1)>', 'short_description' => $xss,
            'long_description' => "Para one $xss\n\n<b>bold</b>", 'status' => 'published', 'category' => $xss, 'pricing_summary' => $xss, 'seo_title' => $xss,
        ]);
        $id = $svc->save(null, $r['data'], [], 1);
        $svc->save($id, $r['data'], [
            'features' => $svc->sanitizeChildren('features', [['title' => $xss, 'description' => $xss, 'icon' => '"><script>']]),
            'faqs'     => $svc->sanitizeChildren('faqs', [['question' => $xss, 'answer' => $xss]]),
            'modules'  => $svc->sanitizeChildren('modules', [['title' => $xss]]),
            'benefits' => $svc->sanitizeChildren('benefits', [['title' => $xss, 'audience' => $xss]]),
            'sections' => $svc->sanitizeChildren('sections', [['title' => $xss, 'body' => $xss]]),
        ], 1);

        foreach (['products/zz-test-xss', 'products'] as $url) {
            $body = $this->get($url)->response()->getBody();
            $this->assertStringNotContainsString($xss, $body, $url);
            $this->assertStringNotContainsString('<img src=x onerror', $body, $url);
            $this->assertStringNotContainsString('<b>bold</b>', $body, $url);
            $this->assertStringContainsString('&lt;script&gt;', $body, $url);
        }
        // JSON-LD must not allow a break-out of its script block.
        $body = $this->get('products/zz-test-xss')->response()->getBody();
        $this->assertSame(0, preg_match('#<script type="application/ld\+json">[^<]*</script><script>alert#', $body));
    }

    // ---------------------------------------------------------------- enquiry -> CRM lead

    private function signedPost(array $over = []): array
    {
        $ts    = (new \App\Services\FormSecurityService())->generateSignedTimestamp();
        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        return [$token, $over + [
            csrf_token() => $token, 'client_name' => 'Product Tester', 'company' => 'Example Co',
            'email' => 'p' . bin2hex(random_bytes(3)) . self::DOMAIN, 'phone' => '+91 98230 45678',
            'project_description' => 'We would like a demo of the platform for 50 employees.',
            'consent' => '1', 'th_hp_check' => '', 'th_hp_website' => '',
            'th_form_ts' => $ts['th_form_ts'], 'th_form_sig' => $ts['th_form_sig'],
            'cf-turnstile-response' => 'test_token_valid_' . bin2hex(random_bytes(6)),
        ]];
    }

    public function testEnquiryCreatesCrmLeadForProduct(): void
    {
        [$token, $data] = $this->signedPost();
        $res = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json', 'X-CSRF-TOKEN' => $token])
            ->post('products/worksphere/enquiry', $data);
        $this->assertSame(201, $res->response()->getStatusCode(), (string) $res->getJSON());
        $id  = json_decode($res->getJSON(), true)['data']['id'];
        $row = $this->db()->table('it_leads')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('worksphere', $row['service_slug']);
        $this->assertSame('other', $row['service_category']);
        $this->assertSame('products/worksphere', $row['source_page']);
        $this->assertStringContainsString('WorkSphere', $row['project_description']);
        $this->assertNotEmpty($row['consent_at']);
    }

    // ---------------------------------------------------------------- WorkSphere: subscription vs licence content

    /** Put the worksphere row back into its pre-upgrade state, as on a database seeded before this release. */
    private function resetCommercialContent(): void
    {
        $db  = $this->db();
        $pid = (int) $db->query("SELECT id FROM products WHERE slug = 'worksphere' LIMIT 1")->getRow()->id;
        $db->query("DELETE FROM product_sections WHERE product_id = ? AND section_key IN ('ways-to-adopt','licence-for-it-companies')", [$pid]);
        $db->query("DELETE FROM product_faqs WHERE product_id = ? AND question IN (?,?,?,?)", [$pid,
            'Can we buy WorkSphere outright instead of subscribing?', 'What does a subscription include?',
            'Do subscription customers receive the source code?', 'Can an IT company resell or white-label WorkSphere?']);
        $db->query("DELETE FROM website_settings WHERE setting_key = ?", [\App\Database\Migrations\CreateProductCatalogue::COMMERCIAL_MARKER]);
        $db->query("UPDATE products SET pricing_summary = ? WHERE id = ?", ['Start with a free 7-day demo. Talk to us for plans that fit your organisation.', $pid]);
    }

    public function testWorkSphereContentExplainsSubscriptionAndSourceCodeLicence(): void
    {
        $this->resetCommercialContent();
        \App\Database\Migrations\CreateProductCatalogue::upgradeWorkSphereCommercial($this->db());
        $body = $this->get('products/worksphere')->response()->getBody();
        $this->assertStringContainsString('Two ways to adopt WorkSphere', $body);
        $this->assertStringContainsString('Subscription (software as a service)', $body);
        $this->assertStringContainsString('Licence with source code (for IT and software companies)', $body);
        $this->assertStringContainsString('Licensing WorkSphere to an IT company', $body);
        $this->assertStringContainsString('Do subscription customers receive the source code?', $body);
        $this->assertStringContainsString('Subscription plans for organisations, or a licence with source code', $body);
        $this->assertStringContainsString('tp-adopt-card--saas', $body);
        $this->assertStringContainsString('tp-adopt-card--licence', $body);
        $this->assertStringContainsString('data-select-engagement="subscription"', $body);
        $this->assertStringContainsString('data-select-engagement="licence"', $body);
        $this->assertStringContainsString('tp-licence-section', $body);
        $this->assertStringContainsString('tp-licence-item', $body);
        $this->assertStringContainsString('Not sure which fits?', $body);
        // honest: no invented prices or guarantees on this page's commercial copy
        $this->assertDoesNotMatchRegularExpression('#(?:₹|Rs\.?|INR)\s?\d#u', (string) preg_replace('#<script.*?</script>#s', '', substr($body, (int) strpos($body, 'Two ways to adopt'), 3000)));
    }

    public function testCommercialUpgradeRunsOnceAndNeverOverwritesAdminEdits(): void
    {
        $db = $this->db();
        $this->resetCommercialContent();
        $cls = \App\Database\Migrations\CreateProductCatalogue::class;
        $cls::upgradeWorkSphereCommercial($db);
        $pid = (int) $db->query("SELECT id FROM products WHERE slug = 'worksphere'")->getRow()->id;

        // An admin edits the section and the pricing line afterwards; a later run must not touch them.
        $db->query("UPDATE product_sections SET body = 'Edited by admin' WHERE product_id = ? AND section_key = 'ways-to-adopt'", [$pid]);
        $db->query("UPDATE products SET pricing_summary = 'Admin wording' WHERE id = ?", [$pid]);
        $cls::upgradeWorkSphereCommercial($db);
        $this->assertSame('Edited by admin', $db->query("SELECT body FROM product_sections WHERE product_id = ? AND section_key = 'ways-to-adopt'", [$pid])->getRow()->body);
        $this->assertSame('Admin wording', $db->query("SELECT pricing_summary FROM products WHERE id = ?", [$pid])->getRow()->pricing_summary);
        $this->assertSame(1, (int) $db->query("SELECT COUNT(*) c FROM product_sections WHERE product_id = ? AND section_key = 'ways-to-adopt'", [$pid])->getRow()->c);

        // Even if the marker is lost, an admin-edited pricing line and existing rows are left alone and nothing duplicates.
        $db->query("DELETE FROM website_settings WHERE setting_key = ?", [$cls::COMMERCIAL_MARKER]);
        $cls::upgradeWorkSphereCommercial($db);
        $this->assertSame('Admin wording', $db->query("SELECT pricing_summary FROM products WHERE id = ?", [$pid])->getRow()->pricing_summary);
        $this->assertSame('Edited by admin', $db->query("SELECT body FROM product_sections WHERE product_id = ? AND section_key = 'ways-to-adopt'", [$pid])->getRow()->body);
        $this->assertSame(1, (int) $db->query("SELECT COUNT(*) c FROM product_faqs WHERE product_id = ? AND question = 'What does a subscription include?'", [$pid])->getRow()->c);
    }

    public function testFlagshipEnquiryFormOffersTheEngagementChoiceAndPreselectsFromLink(): void
    {
        $res = $this->get('products/worksphere');
        $res->assertSee('name="engagement"');
        $res->assertSee('A subscription for my organisation');
        $res->assertSee('A licence with source code (IT / software company)');
        $linked = $this->get('products/worksphere?interest=licence')->response()->getBody();
        $this->assertMatchesRegularExpression('#<option value="licence" selected>#', $linked);
        $junk = $this->get('products/worksphere?interest=%22%3E%3Cscript%3E')->response()->getBody();
        $this->assertStringNotContainsString('<script>alert', $junk);
        $this->assertDoesNotMatchRegularExpression('#<option [^>]*selected[^>]*>[^<]*script#', $junk);
    }

    public function testEnquiryRecordsTheChosenEngagementAndIgnoresUnknownOnes(): void
    {
        $h = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        [$token, $data] = $this->signedPost(['engagement' => 'licence']);
        $res = $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/worksphere/enquiry', $data);
        $this->assertSame(201, $res->response()->getStatusCode(), (string) $res->getJSON());
        $row = $this->db()->table('it_leads')->where('id', json_decode($res->getJSON(), true)['data']['id'])->get()->getRowArray();
        $this->assertStringContainsString('[Interested in: A licence with source code (IT / software company)]', $row['project_description']);

        [$token, $data] = $this->signedPost(['engagement' => 'free-money<script>']);
        $res = $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/worksphere/enquiry', $data);
        $this->assertSame(201, $res->response()->getStatusCode());
        $row = $this->db()->table('it_leads')->where('id', json_decode($res->getJSON(), true)['data']['id'])->get()->getRowArray();
        $this->assertStringNotContainsString('Interested in', $row['project_description']);
        $this->assertStringNotContainsString('free-money', $row['project_description']);
    }

    public function testNonFlagshipProductsHaveNoEngagementChoice(): void
    {
        $this->make('Zz-Test Plain Product');
        $this->get('products/zz-test-plain-product')->assertDontSee('name="engagement"');
    }

    public function testEnquiryRejectsInvalidBotMissingConsentAndUnpublishedProducts(): void
    {
        $h = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        [$token, $data] = $this->signedPost(['consent' => '']);
        $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/worksphere/enquiry', $data)->assertStatus(422);
        [$token, $data] = $this->signedPost(['email' => 'nope', 'project_description' => 'short']);
        $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/worksphere/enquiry', $data)->assertStatus(422);
        [$token, $data] = $this->signedPost(['th_hp_check' => 'bot']);
        $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/worksphere/enquiry', $data)->assertStatus(422);

        $this->make('Zz-Test Enq Draft', 'draft');
        [$token, $data] = $this->signedPost();
        $code = null;
        try {
            $code = $this->withHeaders($h + ['X-CSRF-TOKEN' => $token])->post('products/zz-test-enq-draft/enquiry', $data)->response()->getStatusCode();
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $code = 404;
        }
        $this->assertSame(404, $code);
        $this->assertSame(0, $this->db()->table('it_leads')->where('service_slug', 'zz-test-enq-draft')->countAllResults(), 'no lead for an unpublished product');
    }

    public function testEnquiryWithoutCsrfIsRefused(): void
    {
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->post('products/worksphere/enquiry', ['client_name' => 'x']);
    }

    // ---------------------------------------------------------------- admin permissions + audit

    public function testAdminAccessControl(): void
    {
        $this->withSession([])->get('admin/products')->assertRedirectTo(base_url('admin/login'));

        $none = $this->admin(['jobs.view']);
        $this->assertNotSame(200, $this->withSession($none)->get('admin/products')->response()->getStatusCode());
        $this->assertNotSame(200, $this->withSession($none)->get('admin/products/1/preview')->response()->getStatusCode());

        $viewer = $this->admin(['products.view']);
        $this->withSession($viewer)->get('admin/products')->assertStatus(200);
        $this->withSession($viewer)->get('admin/products/' . (new ProductService())->findBySlug('worksphere')['id'] . '/edit')->assertStatus(200);
        // view-only cannot open the create form
        $this->assertNotSame(200, $this->withSession($viewer)->get('admin/products/new')->response()->getStatusCode());

        $super = $this->admin(['*'], 1, 'super_admin');
        $r = $this->withSession($super)->get('admin/products');
        $r->assertStatus(200);
        $r->assertSee('WorkSphere');
        $this->withSession($super)->get('admin/products/new')->assertStatus(200);
    }

    public function testViewOnlyAdminCannotMutateAndMutationsAreAudited(): void
    {
        $id  = $this->make('Zz-Test Admin Flow', 'draft');
        $svc = new ProductService();
        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        $post = [csrf_token() => $token];
        $h = ['X-CSRF-TOKEN' => $token];

        // view-only: status change refused, nothing changes
        $this->withHeaders($h)->withSession($this->admin(['products.view']))->post('admin/products/' . $id . '/status', $post + ['status' => 'published']);
        $this->assertSame('draft', $svc->find($id)['status']);
        $this->withHeaders($h)->withSession($this->admin(['products.view']))->post('admin/products/' . $id . '/delete', $post);
        $this->assertNotNull($svc->find($id), 'view-only cannot delete');

        // manager: publish, then delete (soft), each audited
        $mgr = $this->admin(['products.manage'], 5);
        $this->withHeaders($h)->withSession($mgr)->post('admin/products/' . $id . '/status', $post + ['status' => 'published']);
        $this->assertSame('published', $svc->find($id)['status']);
        $this->assertNotEmpty($svc->find($id)['published_at']);

        $audit = $this->db()->table('audit_logs')->where('entity_type', 'product')->where('entity_id', (string) $id)->get()->getResultArray();
        $this->assertContains('PRODUCT_STATUS', array_column($audit, 'action'));

        $this->withHeaders($h)->withSession($mgr)->post('admin/products/' . $id . '/delete', $post);
        $this->assertNull($svc->find($id), 'soft-deleted product is hidden');
        $row = $this->db()->table('products')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at'], 'row retained');
        $this->assertContains('PRODUCT_DELETE', array_column($this->db()->table('audit_logs')->where('entity_type', 'product')->where('entity_id', (string) $id)->get()->getResultArray(), 'action'));
    }

    public function testAdminCreateRequiresShortDescriptionToPublishAndRejectsBadUrls(): void
    {
        $token = csrf_hash();
        $_COOKIE[config('Security')->cookieName] = $token;
        $h     = ['X-CSRF-TOKEN' => $token];
        $super = $this->admin(['*'], 1, 'super_admin');

        $this->withHeaders($h)->withSession($super)->post('admin/products/create', [csrf_token() => $token, 'name' => 'Zz-Test No Short', 'status' => 'published']);
        $this->assertNull((new ProductService())->findBySlug('zz-test-no-short'));

        $this->withHeaders($h)->withSession($super)->post('admin/products/create', [csrf_token() => $token, 'name' => 'Zz-Test Bad Link', 'signup_url' => 'javascript:alert(1)', 'status' => 'draft']);
        $this->assertNull((new ProductService())->findBySlug('zz-test-bad-link'));

        $this->withHeaders($h)->withSession($super)->post('admin/products/create', [
            csrf_token() => $token, 'name' => 'Zz-Test Created', 'short_description' => 'A short one.', 'status' => 'draft',
            'features' => [['title' => 'F1', 'description' => 'D1'], ['title' => '', 'description' => 'dropped']],
        ]);
        $p = (new ProductService())->findBySlug('zz-test-created');
        $this->assertNotNull($p);
        $this->assertCount(1, (new ProductService())->withChildren($p)['features']);
        // Draft is previewable by admin only
        $r = $this->withSession($super)->get('admin/products/' . $p['id'] . '/preview');
        $r->assertStatus(200);
        $r->assertSee('Admin preview');
    }

    public function testReorderAndFeatureToggles(): void
    {
        $a = $this->make('Zz-Test Order A');
        $b = $this->make('Zz-Test Order B');
        $svc = new ProductService();
        $svc->reorder(array_map(static fn ($r) => (int) $r['id'], $svc->adminList('', '', 1, 100)['rows']));
        $before = array_column($svc->adminList('', '', 1, 100)['rows'], 'id');
        $posA = array_search((string) $a, array_map('strval', $before), true);
        $svc->move($a, 'down');
        $after = array_map('strval', array_column($svc->adminList('', '', 1, 100)['rows'], 'id'));
        $this->assertSame($posA + 1, array_search((string) $a, $after, true));
        $this->assertTrue($svc->setFeatured($b, true, 1));
        $this->assertSame(1, (int) $svc->find($b)['is_featured']);
    }

    public function testCatalogueRendersUpcomingProductsAndNoFlagshipBadge(): void
    {
        $res = $this->get('products');
        $res->assertStatus(200);
        $res->assertSee('WorkSphere');
        $res->assertSee('Coming Soon');
        $res->assertSee('Live SaaS');
        $res->assertDontSee('FLAGSHIP PRODUCT');
        $res->assertDontSee('TalentSphere');
        $res->assertDontSee('PulseERP');
    }
}
