<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('admin_user');

        if (!$user || empty($user['id'])) {
            // If API request, return JSON
            $uri = $request->getUri()->getPath();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '/api/')) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success' => false,
                        'status'  => 401,
                        'code'    => 'UNAUTHORIZED',
                        'message' => 'Authentication required to access administrative resource.',
                    ]);
            }

            // Otherwise redirect to web login
            return redirect()->to(base_url('admin/login'))->with('error', 'Please log in to continue.');
        }

        // CSRF defence-in-depth for session-authenticated API writes (the global CSRF
        // filter is skipped for api/*). Browsers always send Origin on cross-site
        // POST/PUT/PATCH/DELETE, so reject any whose Origin/Referer host differs.
        $uri = ltrim($request->getUri()->getPath(), '/');
        if (str_starts_with($uri, 'api/')
            && in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $source = $request->getHeaderLine('Origin') ?: $request->getHeaderLine('Referer');
            if ($source !== '') {
                $sourceHost = strtolower((string) parse_url($source, PHP_URL_HOST));
                $ownHost    = strtolower($request->getUri()->getHost());
                if ($sourceHost !== $ownHost) {
                    return service('response')->setStatusCode(403)->setJSON([
                        'success' => false,
                        'status'  => 403,
                        'code'    => 'CROSS_ORIGIN_BLOCKED',
                        'message' => 'Cross-origin request rejected.',
                    ]);
                }
            }
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

