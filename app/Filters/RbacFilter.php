<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RbacFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('admin_user');

        if (!$user) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'status'  => 401,
                    'code'    => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ]);
        }

        // Super Admin bypasses all checks
        if (!empty($user['roles'])) {
            foreach ($user['roles'] as $r) {
                if (($r['slug'] ?? '') === 'super_admin') {
                    return $request;
                }
            }
        }

        // Required permission passed in argument
        $requiredPerm = $arguments[0] ?? null;
        if ($requiredPerm) {
            $userPerms = $user['permissions'] ?? [];
            if (!in_array($requiredPerm, $userPerms, true)) {
                $uri = $request->getUri()->getPath();
                if (str_starts_with($uri, 'api/') || str_starts_with($uri, '/api/')) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'success' => false,
                            'status'  => 403,
                            'code'    => 'FORBIDDEN',
                            'message' => "Permission denied. Required privilege: {$requiredPerm}",
                        ]);
                }

                return redirect()->to(base_url('admin'))->with('error', 'You do not have permission to access that area.');
            }
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

