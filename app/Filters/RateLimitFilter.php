<?php

namespace App\Filters;

use App\Services\RateLimiterService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limiter = new RateLimiterService();
        $safeIp = $limiter->getSafeClientIp();
        $path = $request->getUri()->getPath();

        // Configurable limits: defaults to 5 submissions per 600s (10 min) for forms
        $maxAttempts  = (int) ($arguments[0] ?? 5);
        $decaySeconds = (int) ($arguments[1] ?? 600);
        $scope        = (string) ($arguments[2] ?? 'general');

        $rateKey = "rl_{$scope}_{$path}_{$safeIp}";
        $result = $limiter->hit($rateKey, $maxAttempts, $decaySeconds);

        if (!$result['allowed']) {
            $retryAfter = $result['retry_after'];
            $minutes = ceil($retryAfter / 60);
            $msg = "Too many submission attempts from your network. Please wait {$minutes} minute(s) before trying again or reach our Pune desk directly at hr@talenthubconsultant.com / +91 72761 46573.";

            $response = service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setHeader('X-RateLimit-Limit', (string) $maxAttempts)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('X-RateLimit-Reset', (string) (time() + $retryAfter));

            $isJson = false;
            $contentType = $request->header('Content-Type') ? $request->header('Content-Type')->getValue() : '';
            $accept = $request->header('Accept') ? $request->header('Accept')->getValue() : '';

            if (
                $request->isAJAX() ||
                str_contains(strtolower($contentType), 'application/json') ||
                str_contains(strtolower($accept), 'application/json') ||
                str_starts_with($path, 'api/')
            ) {
                $isJson = true;
            }

            if ($isJson) {
                return $response->setJSON([
                    'success'     => false,
                    'status'      => 429,
                    'code'        => 'RATE_LIMIT_EXCEEDED',
                    'message'     => $msg,
                    'retry_after' => $retryAfter,
                ]);
            }

            session()->setFlashdata('error', $msg);
            return redirect()->back()->withInput();
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
