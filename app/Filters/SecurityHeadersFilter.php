<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\EnvironmentSafetyService;

class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Security: HSTS for HTTPS enforcement
        if ($request->isSecure()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Security: Content-Security-Policy.
        // The ADMIN and CRM areas have their own strict policies: no 'unsafe-eval', no third-party trackers.
        $path    = ltrim($request->getUri()->getPath(), '/');
        $isAdmin = $path === 'admin' || str_starts_with($path, 'admin/');
        $isCrm   = $path === 'crm' || str_starts_with($path, 'crm/');
        if ($isCrm) {
            $csp = "default-src 'self' data:; "
                . "script-src 'self' 'unsafe-inline'; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
                . "font-src 'self' data: https://fonts.gstatic.com; "
                . "img-src 'self' data: blob:; "
                . "connect-src 'self'; "
                . "frame-src 'self'; "
                . "frame-ancestors 'self'; "
                . "object-src 'none'; "
                . "base-uri 'self';";
        } elseif ($isAdmin) {
            $csp = "default-src 'self' data:; "
                . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.tiny.cloud; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tiny.cloud; "
                . "font-src 'self' data: https://fonts.gstatic.com https://cdn.tiny.cloud; "
                . "img-src 'self' data: blob: https://cdn.tiny.cloud https://sp.tinymce.com; "
                . "connect-src 'self' https://cdn.tiny.cloud https://sp.tinymce.com; "
                . "frame-src 'self'; "
                . "frame-ancestors 'self'; "
                . "object-src 'none'; "
                . "base-uri 'self';";
        } else {
            $csp = "default-src 'self' data:; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://cdn.tiny.cloud https://embed.tawk.to https://*.tawk.to https://cdn.jsdelivr.net https://challenges.cloudflare.com; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tiny.cloud https://*.tawk.to; "
                . "font-src 'self' data: https://fonts.gstatic.com https://*.tawk.to; "
                . "img-src 'self' data: blob: https://www.googletagmanager.com https://*.google-analytics.com https://*.tawk.to https://praveenchavan.vercel.app; "
                . "connect-src 'self' data: blob: https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com https://*.tawk.to wss://*.tawk.to https://api.mail.hostinger.com https://challenges.cloudflare.com https://*.cloudflare.com https://*.cloudflarecaptcha.com; "
                . "frame-src 'self' https://*.tawk.to https://challenges.cloudflare.com; "
                . "object-src 'none'; "
                . "base-uri 'self';";
        }
        $response->setHeader('Content-Security-Policy', $csp);

        // Vary responses by Accept header for seamless HTML vs Markdown agent negotiation
        $existingVary = $response->getHeaderLine('Vary');
        if (!empty($existingVary)) {
            if (!str_contains($existingVary, 'Accept')) {
                $response->setHeader('Vary', $existingVary . ', Accept');
            }
        } else {
            $response->setHeader('Vary', 'Accept');
        }

        $baseUrl = rtrim(base_url(), '/');

        // STAGING SEO & ENVIRONMENT HEADERS
        if (EnvironmentSafetyService::isStaging()) {
            // Defense Layer 1: Universal HTTP Noindex Header for Staging
            $response->setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
            $response->setHeader('X-Environment', 'staging');
            // Do NOT advertise sitemaps on staging
            $response->setHeader('Link', "<{$baseUrl}/llms.txt>; rel=\"describedby\", <{$baseUrl}/?format=md>; rel=\"alternate\"; type=\"text/markdown\"");
        } else {
            // Production Agent & Sitemap Discovery Link Headers (SEP-1649 / llms.txt)
            $response->setHeader('Link', "<{$baseUrl}/llms.txt>; rel=\"describedby\", <{$baseUrl}/?format=md>; rel=\"alternate\"; type=\"text/markdown\", <{$baseUrl}/sitemap.xml>; rel=\"sitemap\"");
        }

        return $response;
    }
}
