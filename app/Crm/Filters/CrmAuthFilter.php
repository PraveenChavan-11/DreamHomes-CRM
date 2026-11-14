<?php

namespace Crm\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Crm\Services\AuthService;
use Crm\Services\Crm;

/** Session guard for every CRM page: login required, idle timeout, live active-user + permission refresh. */
class CrmAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $u       = $session->get('crm_user');
        $isAjax  = $request->isAJAX();
        $deny    = static function (string $msg) use ($request, $isAjax, $session) {
            $session->remove('crm_user');
            if ($isAjax) {
                return service('response')->setStatusCode(401)->setJSON(['success' => false, 'code' => 'UNAUTHORIZED', 'message' => $msg]);
            }
            if ($request->is('get')) {
                $session->set('crm_intended', (string) $request->getUri()->getPath() . ($request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : ''));
            }

            return redirect()->to(site_url('crm/login'))->with('error', $msg);
        };

        if (! $u || empty($u['id'])) {
            return $deny('Please sign in to continue.');
        }
        if (AuthService::isExpired($u)) {
            AuthService::logout((int) $u['id'], $request->getIPAddress(), (string) $request->getUserAgent(), 'timeout');
            $session->destroy();
            session()->start();

            return $deny('Your session expired due to inactivity. Please sign in again.');
        }
        $active = Crm::db()->table('admin_users')->select('is_active')->where('id', (int) $u['id'])->get()->getRowArray();
        if (! $active || (int) $active['is_active'] !== 1) {
            return $deny('This account is no longer active.');
        }
        // refresh roles/permissions from the database on every request so revocations apply immediately
        $fresh = AuthService::sessionUser((int) $u['id']);
        $session->set('crm_user', $fresh);
        Crm::setActor((int) $u['id']);

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
