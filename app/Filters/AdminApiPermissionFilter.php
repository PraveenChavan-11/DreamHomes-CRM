<?php

namespace App\Filters;

use App\Models\AuditLogModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authorisation for the REST admin API (/api/v1/admin/*). The `auth` filter only proves the caller is signed in;
 * this filter decides WHAT they may do, centrally and fail-closed:
 *
 *   - Super Admin (role slug `super_admin`) passes.
 *   - Everyone else needs at least one permission from the first rule matching (method, path).
 *   - A route that matches NO rule is denied — so a newly added endpoint is locked until someone deliberately maps it.
 *   - Only self-service routes (own profile, own notifications, session, dashboard stats) are open to any signed-in admin.
 *
 * The permission sets mirror what the equivalent server-rendered admin screens already require, so the API can never be
 * a way around them. Denials are logged (path + user only) and answered with a generic 403 JSON body.
 */
class AdminApiPermissionFilter implements FilterInterface
{
    private const READ  = ['GET', 'HEAD'];
    private const WRITE = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * [methods, path regex (relative to api/v1/admin/), permissions (ANY of), or null = any signed-in admin]
     * Order matters: first match wins.
     */
    public const RULES = [
        // self-service
        [['*'], '#^(profile|profile/password|session|logout|dashboard)$#', null],
        [['*'], '#^notifications(/.*)?$#', null],

        // recruitment
        [self::READ,  '#^jobs(/\d+)?$#',                       ['jobs.publish', 'jobs.close', 'applications.view']],
        [['PUT'],     '#^jobs/\d+/status$#',                   ['jobs.publish', 'jobs.close']],
        [['*'],       '#^jobs(/.*)?$#',                        ['jobs.publish']],
        [self::READ,  '#^applications(/\d+)?$#',               ['applications.view']],
        [['*'],       '#^applications/\d+/(status)$#',         ['applications.transition']],
        [['*'],       '#^applications/\d+/notes$#',            ['applications.notes', 'applications.transition']],
        [['GET'],     '#^documents/\d+/stream$#',              ['resumes.download']],
        [self::READ,  '#^resume-bank$#',                       ['candidates.view', 'candidates.manage']],
        [['*'],       '#^resume-bank/\d+/status$#',            ['candidates.manage']],
        [['*'],       '#^enquiries/employers(/.*)?$#',         ['employers.manage', 'enquiries.manage']],
        [['*'],       '#^enquiries/contact(/.*)?$#',           ['enquiries.manage']],

        // content
        [['*'],       '#^(blogs|faqs|testimonials)(/.*)?$#',   ['content.manage']],
        [['*'],       '#^pages(/.*)?$#',                       ['content.manage', 'pages.manage']],
        [['*'],       '#^media(/.*)?$#',                       ['content.manage', 'media.manage']],

        // communications
        [['*'],       '#^email-templates(/.*)?$#',             ['templates.manage']],
        [['*'],       '#^email-outbox(/.*)?$#',                ['templates.manage', 'enquiries.manage']],
        [['*'],       '#^mail/(settings|test-connection|mailboxes/sync)$#', ['settings.manage']],
        [['*'],       '#^mail(/.*)?$#',                        ['templates.manage', 'enquiries.manage', 'candidates.manage']],

        // people & security
        [self::READ,  '#^(users|roles)$#',                     ['users.view', 'users.manage', 'roles.view', 'roles.manage']],
        [['*'],       '#^users(/.*)?$#',                       ['users.manage']],
        [['*'],       '#^security(/.*)?$#',                    ['security.view', 'audit.view']],
        [['*'],       '#^audit-logs$#',                        ['audit.view', 'security.view']],
        [['*'],       '#^health$#',                            ['security.view', 'audit.view', 'settings.manage']],

        // site operations & settings
        [['*'],       '#^seo(/.*)?$#',                         ['settings.manage', 'content.manage']],
        [['GET'],     '#^reports/applications$#',              ['applications.view']],
        [['GET'],     '#^reports/candidates$#',                ['candidates.view', 'candidates.manage']],
        [['GET'],     '#^reports/employers$#',                 ['employers.manage', 'enquiries.manage']],
        [['*'],       '#^settings(/.*)?$#',                    ['settings.manage']],
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('admin_user');
        if (empty($user['id'])) {
            return $this->deny(401, 'UNAUTHORIZED', 'Authentication required.');
        }
        foreach ((array) ($user['roles'] ?? []) as $r) {
            if (($r['slug'] ?? '') === 'super_admin') {
                return $request;
            }
        }

        $method = strtoupper($request->getMethod());
        $path   = trim((string) preg_replace('#^/?api/v1/admin/?#', '', '/' . ltrim($request->getUri()->getPath(), '/')), '/');

        foreach (self::RULES as [$methods, $pattern, $perms]) {
            if (!in_array('*', $methods, true) && !in_array($method, $methods, true)) {
                continue;
            }
            if (!preg_match($pattern, $path)) {
                continue;
            }
            if ($perms === null) {
                return $request;
            }
            $have = (array) ($user['permissions'] ?? []);
            if (array_intersect($perms, $have)) {
                return $request;
            }
            return $this->refuse($user, $method, $path);
        }

        return $this->refuse($user, $method, $path);   // unmapped = locked (fail closed)
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function refuse(array $user, string $method, string $path): ResponseInterface
    {
        try {
            (new AuditLogModel())->log('API_PERMISSION_DENIED', 'admin_api', substr($method . ' /' . $path, 0, 90), null, null, (int) $user['id']);
        } catch (\Throwable) {
        }
        return $this->deny(403, 'FORBIDDEN', 'You do not have permission to perform this action.');
    }

    private function deny(int $status, string $code, string $message): ResponseInterface
    {
        return service('response')->setStatusCode($status)->setJSON(['success' => false, 'status' => $status, 'code' => $code, 'message' => $message]);
    }
}
