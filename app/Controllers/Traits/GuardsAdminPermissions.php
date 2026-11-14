<?php

namespace App\Controllers\Traits;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Server-side authorisation for admin controllers that have no per-method checks.
 *
 * The using controller declares:
 *     protected const PERMISSIONS = ['methodName' => ['perm.a', 'perm.b'], ...];   // ANY of the listed permissions
 *     protected const PERMISSIONS_FAIL_CLOSED = true;                                // optional, default true
 *
 * CodeIgniter calls `_remap()` for every routed request to the controller. With FAIL_CLOSED, a routed method that is not
 * in the map is refused, so a handler added later is locked until someone decides who may use it. With FAIL_CLOSED=false
 * only the listed methods are checked (for controllers that already guard their other methods themselves).
 * Super Admin always passes. Hiding a link in the sidebar is never the access control.
 */
trait GuardsAdminPermissions
{
    public function _remap($method, ...$params)
    {
        if (!method_exists($this, $method)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forControllerNotFound(static::class, $method);
        }
        $map        = static::PERMISSIONS;
        $failClosed = defined('static::PERMISSIONS_FAIL_CLOSED') ? static::PERMISSIONS_FAIL_CLOSED : true;

        if (array_key_exists($method, $map)) {
            if (!$this->adminHasAny($map[$method])) {
                return $this->denyAdminAccess($map[$method]);
            }
        } elseif ($failClosed && !$this->adminIsSuper()) {
            return $this->denyAdminAccess([]);
        }

        return $this->{$method}(...$params);
    }

    protected function adminIsSuper(): bool
    {
        $user = session()->get('admin_user');
        foreach ((array) ($user['roles'] ?? []) as $r) {
            if (($r['slug'] ?? '') === 'super_admin' || ($r['name'] ?? '') === 'Super Administrator') {
                return true;
            }
        }
        return false;
    }

    /** @param list<string> $codes */
    protected function adminHasAny(array $codes): bool
    {
        $user = session()->get('admin_user');
        if (empty($user['id'])) {
            return false;
        }
        return $this->adminIsSuper() || (bool) array_intersect($codes, (array) ($user['permissions'] ?? []));
    }

    /** @param list<string> $codes */
    protected function denyAdminAccess(array $codes): ResponseInterface
    {
        $wantsJson = $this->request->isAJAX() || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
        if ($wantsJson) {
            return service('response')->setStatusCode(403)->setJSON(['success' => false, 'status' => 403, 'code' => 'FORBIDDEN', 'message' => 'You do not have permission to perform this action.']);
        }
        session()->setFlashdata('error', 'Access restricted: you do not have permission to use this section.');
        return redirect()->to(base_url('admin'));
    }
}
