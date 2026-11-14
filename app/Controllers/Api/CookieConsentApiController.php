<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\CookieConsentService;

class CookieConsentApiController extends ResourceController
{
    protected $format = 'json';
    private CookieConsentService $consentService;

    public function __construct()
    {
        $this->consentService = new CookieConsentService();
    }

    /**
     * POST /api/v1/cookies/consent
     */
    public function logConsent()
    {
        $uuid      = (string) ($this->request->getPost('consent_uuid') ?? $this->request->getVar('consent_uuid') ?? '');
        $analytics = (bool) ($this->request->getPost('analytics') ?? $this->request->getVar('analytics') ?? false);
        $marketing = (bool) ($this->request->getPost('marketing') ?? $this->request->getVar('marketing') ?? false);

        if (empty($uuid)) {
            $uuid = bin2hex(random_bytes(16));
        }

        $ip = $this->request->getIPAddress();
        $ua = (string) $this->request->getUserAgent();

        $this->consentService->recordConsent($uuid, $analytics, $marketing, $ip, $ua);

        return $this->respond([
            'success'      => true,
            'consent_uuid' => $uuid,
            'analytics'    => $analytics,
            'marketing'    => $marketing,
        ]);
    }
}
