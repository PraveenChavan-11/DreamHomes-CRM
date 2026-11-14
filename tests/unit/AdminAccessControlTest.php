<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * Route-wide broken-access-control regression test.
 *
 * Logging in is NOT authorisation. This walks EVERY admin route (web `admin/*` and REST `api/v1/admin/*`) as a signed-in
 * staff member who holds no permissions at all and requires that nothing returns a successful page/JSON — except an
 * explicit allow-list of screens that every admin may legitimately open (own dashboard, own profile).
 * New admin routes are covered automatically; a forgotten permission check fails this test.
 *
 * Runs inside a rolled-back transaction on the isolated test database.
 *
 * @internal
 */
final class AdminAccessControlTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    /** Routes any authenticated admin may open. Everything else needs a permission. */
    private const OPEN_TO_ANY_ADMIN = [
        'admin', 'admin/dashboard', 'admin/dashboard/notifications',
        'admin/profile', 'admin/profile/update', 'admin/logout',
        'api/v1/admin/profile', 'api/v1/admin/profile/password',
        'api/v1/admin/session', 'api/v1/admin/dashboard', 'api/v1/admin/logout',
        'api/v1/admin/notifications', 'api/v1/admin/notifications/unread-count', 'api/v1/admin/notifications/read-all',
    ];

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

    private function session(array $perms, string $slug = 'nobody'): array
    {
        return ['admin_user' => ['id' => 7771, 'username' => 'acl_tester', 'name' => 'ACL Tester', 'email' => 'acl@example.invalid',
            'roles' => [['id' => 98, 'name' => ucfirst($slug), 'slug' => $slug]], 'permissions' => $perms]];
    }

    /** @return list<array{0:string,1:string}> [verb, concrete path] */
    private function adminRoutes(): array
    {
        $routes = service('routes');
        $routes->loadRoutes();
        $out = [];
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $verb) {
            foreach (array_keys($routes->getRoutes(strtoupper($verb))) as $pattern) {
                if (!(str_starts_with($pattern, 'admin') || str_starts_with($pattern, 'api/v1/admin'))) {
                    continue;
                }
                if (str_contains($pattern, 'login') || str_contains($pattern, 'forgot') || str_contains($pattern, 'reset-password') || str_contains($pattern, 'verify-otp')) {
                    continue; // public auth screens
                }
                $path = preg_replace('~\((?::num|\[0-9\]\+)\)~', '999999', $pattern);
                $path = preg_replace_callback('~\(([^)]*)\)~', static fn ($m) => str_contains($m[1], '|') ? explode('|', $m[1])[0] : 'x', (string) $path);
                $out[] = [$verb, rtrim((string) $path, '/')];
            }
        }
        return $out;
    }

    public function testEveryAdminRouteRefusesAUserWithNoPermissions(): void
    {
        $session  = $this->session(['nothing.at.all']);
        $failures = [];
        $count    = 0;
        foreach ($this->adminRoutes() as [$verb, $path]) {
            $key = ltrim($path, '/');
            if (in_array($key, self::OPEN_TO_ANY_ADMIN, true) || $key === '') {
                continue;
            }
            $count++;
            \Config\Services::resetSingle('renderer');
            try {
                $call = $this->withSession($session);
                $res  = in_array($verb, ['get'], true)
                    ? $call->call($verb, $path)
                    : $call->call($verb, $path, [csrf_token() => csrf_hash()]);
                $status = $res->response()->getStatusCode();
            } catch (\CodeIgniter\Exceptions\PageNotFoundException) {
                continue; // route not reachable with these placeholders
            } catch (\Throwable $e) {
                $status = 500;   // an unhandled error is not a refusal; reported below
                $failures[] = strtoupper($verb) . " /$key  -> exception " . get_class($e);
                continue;
            }
            // 302 (redirect to dashboard with "access restricted"), 401, 403 and 404 are refusals. 200 or 5xx are not.
            if ($status === 200 || $status >= 500) {
                $failures[] = strtoupper($verb) . " /$key  -> HTTP $status";
            }
        }
        $this->assertGreaterThan(100, $count, 'the route walk must actually cover the admin surface');
        $this->assertSame([], $failures, count($failures) . " of $count admin routes were not refused for a no-permission user:\n" . implode("\n", $failures));
    }

    public function testRecruiterRoleCannotUseSocialOrIntegrationAdmin(): void
    {
        $recruiter = $this->session(['jobs.publish', 'applications.view', 'applications.transition', 'candidates.view'], 'recruiter');
        foreach (['admin/social/settings', 'admin/social/accounts', 'admin/social/posts', 'admin/social/logs', 'admin/social/media', 'admin/social/enquiries/export',
                  'admin/engagement/campaigns', 'admin/system/health', 'admin/agents', 'admin/integrations', 'admin/users', 'admin/roles', 'admin/audit-logs', 'admin/settings'] as $uri) {
            \Config\Services::resetSingle('renderer');
            $res = $this->withSession($recruiter)->call('get', $uri);
            $this->assertNotSame(200, $res->response()->getStatusCode(), "recruiter must not open /$uri");
        }
    }
}
