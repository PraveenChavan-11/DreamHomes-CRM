<?php

namespace Crm\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Crm\Services\AccessScope;

/** Route-level permission gate: `crmperm:a.b,c.d` = user needs ANY of the listed permissions. Server-side; hiding buttons is cosmetic. */
class CrmPermFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $u = session()->get('crm_user');
        if (! $u) {
            return redirect()->to(site_url('crm/login'));
        }
        foreach ((array) $arguments as $perm) {
            if (AccessScope::can($u, $perm)) {
                return $request;
            }
        }
        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON(['success' => false, 'code' => 'FORBIDDEN', 'message' => 'Permission denied.']);
        }

        return service('response')->setStatusCode(403)->setBody(view('Crm\Views\errors\403', ['user' => $u]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
