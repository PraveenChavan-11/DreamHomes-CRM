<?php

namespace Tests\Unit;

use App\Filters\AdminApiPermissionFilter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * Positive and negative checks that each permission grants exactly its own access (see AdminAccessControlTest for the
 * route-wide sweep). Includes privilege-escalation attempts through the REST user API and side-effect checks.
 *
 * @internal
 */
final class AdminAccessMatrixTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Services::resetSingle('renderer');
        $this->beginRollbackTx();
    }

    protected function tearDown(): void
    {
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function session(array $perms, string $slug = 'limited'): array
    {
        return ['admin_user' => ['id' => 7771, 'username' => 'acl_tester', 'name' => 'ACL Tester', 'email' => 'acl@example.invalid',
            'roles' => [['id' => 98, 'name' => ucfirst($slug), 'slug' => $slug]], 'permissions' => $perms]];
    }

    private function httpCode(string $verb, string $uri, array $session, array $data = [], bool $json = false): int
    {
        \Config\Services::resetSingle('renderer');
        $t = $this->withSession($session);
        if ($json) {
            $t = $t->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json']);
        }
        $res = $verb === 'get' ? $t->call($verb, $uri) : $t->call($verb, $uri, [csrf_token() => csrf_hash()] + $data);
        return $res->response()->getStatusCode();
    }

    public function testSocialPermissionsAreSeparatedByCapability(): void
    {
        $viewer = $this->session(['social.view'], 'marketing');
        $this->assertSame(200, $this->httpCode('get', 'admin/social/posts', $viewer));
        $this->assertSame(200, $this->httpCode('get', 'admin/social/queue', $viewer));
        foreach (['admin/social/settings', 'admin/social/enquiries/export', 'admin/social/posts/create'] as $uri) {
            $this->assertNotSame(200, $this->httpCode('get', $uri, $viewer), "social.view must not open /$uri");
        }
        $this->assertNotSame(200, $this->httpCode('post', 'admin/social/posts/1/reschedule', $viewer, ['scheduled_at' => '2030-01-01 10:00:00']), 'viewing is not publishing');
        $this->assertNotSame(200, $this->httpCode('post', 'admin/social/posts/1/delete', $viewer));

        $author = $this->session(['social.create'], 'marketing');
        $this->assertSame(200, $this->httpCode('get', 'admin/social/posts/create', $author));
        $before = (int) $this->dbc()->table('social_posts')->countAllResults();
        $this->httpCode('post', 'admin/social/posts/store', $author, ['title' => 'x', 'base_content' => 'y', 'platform' => 'linkedin', 'submit_action' => 'publish_now']);
        $this->assertSame($before, (int) $this->dbc()->table('social_posts')->countAllResults(), 'publish_now without social.publish creates nothing');
        $this->assertNotSame(200, $this->httpCode('get', 'admin/social/settings', $author));

        $settings = $this->session(['social.settings'], 'admin');
        $this->assertSame(200, $this->httpCode('get', 'admin/social/settings', $settings));
        $this->assertNotSame(200, $this->httpCode('get', 'admin/social/posts', $settings), 'settings permission does not imply viewing posts');
    }

    public function testSocialControllersAreFailClosedByReflection(): void
    {
        foreach ([\App\Controllers\Admin\SocialMarketingController::class, \App\Controllers\Admin\SocialOAuthController::class] as $class) {
            $rc  = new \ReflectionClass($class);
            $map = $rc->getConstant('PERMISSIONS');
            foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
                if ($m->class !== $class && $m->class !== null && !str_contains((string) $m->getFileName(), basename($rc->getFileName()))) {
                    continue;   // inherited / trait helpers
                }
                if (in_array($m->name, ['__construct', '_remap', 'initController'], true) || $m->class !== $class) {
                    continue;
                }
                $this->assertArrayHasKey($m->name, $map, "$class::{$m->name} is a routable handler without a permission entry");
                $this->assertNotEmpty($map[$m->name]);
            }
        }
    }

    public function testRestAdminApiHonoursPermissionsAndNeverLetsAReaderWrite(): void
    {
        $reader = $this->session(['users.view'], 'viewer');
        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/users', $reader, [], true));
        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/roles', $reader, [], true));
        $this->assertSame(403, $this->httpCode('get', 'api/v1/admin/audit-logs', $reader, [], true));
        $this->assertSame(403, $this->httpCode('get', 'api/v1/admin/settings', $reader, [], true));
        $this->assertSame(403, $this->httpCode('post', 'api/v1/admin/users', $reader, ['email' => 'evil@example.invalid', 'role_ids' => [1]], true));

        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/settings', $this->session(['settings.manage'], 'ops'), [], true));
        $this->assertSame(403, $this->httpCode('get', 'api/v1/admin/users', $this->session(['settings.manage'], 'ops'), [], true));
        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/audit-logs', $this->session(['audit.view']), [], true));
        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/jobs', $this->session(['jobs.publish'], 'recruiter'), [], true));
    }

    public function testPrivilegeEscalationThroughTheUserApiIsBlocked(): void
    {
        $admins = (int) $this->dbc()->table('admin_users')->countAllResults();
        $roles  = (int) $this->dbc()->table('user_roles')->countAllResults();
        foreach ([['users.view'], ['jobs.publish', 'applications.view'], ['roles.view'], []] as $perms) {
            $s = $this->session($perms, 'limited');
            $this->assertSame(403, $this->httpCode('post', 'api/v1/admin/users', $s, ['username' => 'backdoor', 'email' => 'backdoor@example.invalid', 'password' => 'Str0ng-Passw0rd!', 'role_ids' => [1], 'roles' => [1]], true));
            $this->assertSame(403, $this->httpCode('put', 'api/v1/admin/users/2', $s, ['role_ids' => [1], 'is_active' => 1], true));
        }
        $this->assertSame($admins, (int) $this->dbc()->table('admin_users')->countAllResults(), 'no account was created');
        $this->assertSame($roles, (int) $this->dbc()->table('user_roles')->countAllResults(), 'no role was assigned');
    }

    public function testSettingsCannotBeChangedWithoutThePermission(): void
    {
        $snapshot = fn () => md5(json_encode($this->dbc()->table('website_settings')->orderBy('id')->get()->getResultArray()));
        $before   = $snapshot();
        foreach ([['jobs.publish'], ['users.manage'], ['content.manage']] as $perms) {
            $s = $this->session($perms, 'limited');
            $this->assertSame(403, $this->httpCode('put', 'api/v1/admin/settings', $s, ['site_name' => 'Hacked', 'smtp_host' => 'evil.example'], true));
            $this->assertSame(403, $this->httpCode('put', 'api/v1/admin/settings/website-control', $s, ['maintenance_mode' => 1], true));
            $this->assertSame(403, $this->httpCode('post', 'api/v1/admin/settings/test-smtp', $s, ['smtp_host' => 'evil.example'], true));
            $this->assertSame(403, $this->httpCode('post', 'api/v1/admin/mail/settings', $s, ['x' => 'y'], true));
        }
        $this->assertSame($before, $snapshot(), 'website_settings is unchanged');
    }

    public function testUnmappedApiRoutesAreDeniedByDefaultButSuperAdminPasses(): void
    {
        $limited = $this->session(['users.manage', 'settings.manage', 'jobs.publish', 'content.manage'], 'ops');
        // A route that does not exist yet cannot be reached through the router, so drive the filter directly:
        // a future endpoint under api/v1/admin that nobody has mapped must be refused for everyone but Super Admin.
        $filter = new AdminApiPermissionFilter();
        $make   = static function (string $method, string $path) {
            $app = config('App');
            $req = new \CodeIgniter\HTTP\IncomingRequest($app, new \CodeIgniter\HTTP\SiteURI($app, 'api/v1/admin/' . $path), null, new \CodeIgniter\HTTP\UserAgent());
            return $req->setMethod($method);
        };
        $_SESSION = $limited;
        foreach (['brand-new-endpoint', 'secrets', 'export/everything', 'users/../settings-x'] as $path) {
            $res = $filter->before($make('GET', $path));
            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $res, "unmapped /$path must be refused");
            $this->assertSame(403, $res->getStatusCode());
        }
        $_SESSION = $this->session([], 'super_admin');
        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $filter->before($make('GET', 'brand-new-endpoint')), 'Super Admin is never locked out');
        $_SESSION = [];
        $this->assertSame(401, $filter->before($make('GET', 'users'))->getStatusCode(), 'no session → 401');
        // every real REST admin path that exists today is covered by a rule (nothing relies on the default-deny)
        $patterns = array_map(static fn ($r) => $r[1], AdminApiPermissionFilter::RULES);
        foreach (['users', 'users/5', 'roles', 'settings', 'settings/test-smtp', 'audit-logs', 'security/stats', 'security/login-attempts', 'mail/settings', 'mail/messages',
                  'resume-bank', 'documents/1/stream', 'jobs', 'jobs/3/status', 'applications/2/notes', 'blogs/4', 'media/1', 'email-templates/2', 'reports/candidates', 'seo/status', 'health'] as $p) {
            $hit = false;
            foreach ($patterns as $rx) {
                $hit = $hit || (bool) preg_match($rx, $p);
            }
            $this->assertTrue($hit, "$p must be covered by a permission rule");
        }
        $this->assertSame(200, $this->httpCode('get', 'api/v1/admin/users', $this->session([], 'super_admin'), [], true));
        \Config\Services::resetSingle('renderer');
        $_SESSION = [];
        \Config\Services::resetSingle('session');   // forget the previous request's signed-in user
        $this->assertSame(401, $this->withHeaders(['Accept' => 'application/json'])->call('get', 'api/v1/admin/users')->response()->getStatusCode());
    }

    public function testEngagementWritesNeedTheirPermissions(): void
    {
        $s = $this->session(['applications.view'], 'recruiter');
        foreach (['post admin/engagement/announcements/delete/999999', 'post admin/engagement/announcements/toggle/999999', 'post admin/engagement/campaigns/send/999999',
                  'post admin/engagement/campaigns/test/999999', 'post admin/engagement/subscribers/toggle/999999', 'get admin/engagement/campaigns/logs/999999', 'get admin/engagement/announcements/edit/999999'] as $call) {
            [$verb, $uri] = explode(' ', $call);
            $code = $this->httpCode($verb, $uri, $s);
            $this->assertNotSame(200, $code, $call);
            $this->assertLessThan(500, $code, "$call must be refused cleanly, not crash");
        }
        $this->assertSame(302, $this->httpCode('get', 'admin/engagement/campaigns/edit/999999', $this->session(['campaigns.send'], 'marketing')), 'a permitted user reaches the handler (not-found redirect)');
    }
}
