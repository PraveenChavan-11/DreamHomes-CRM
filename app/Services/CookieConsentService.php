<?php

namespace App\Services;

use App\Models\CookieConsentLogModel;

class CookieConsentService
{
    private CookieConsentLogModel $consentModel;

    public function __construct()
    {
        $this->consentModel = new CookieConsentLogModel();
    }

    /**
     * Record cookie consent decisions
     */
    public function recordConsent(string $uuid, bool $analytics, bool $marketing, ?string $ip = null, ?string $userAgent = null): bool
    {
        $ipHash = $ip ? hash('sha256', $ip . 'cookie_salt_talenthub') : null;
        $uaHash = $userAgent ? hash('sha256', $userAgent) : null;

        return (bool) $this->consentModel->insert([
            'consent_uuid'    => substr(trim($uuid), 0, 64),
            'necessary'       => 1,
            'analytics'       => $analytics ? 1 : 0,
            'marketing'       => $marketing ? 1 : 0,
            'ip_hash'         => $ipHash,
            'user_agent_hash' => $uaHash,
        ]);
    }

    /**
     * Get aggregate statistics
     */
    public function getConsentStats(): array
    {
        return $this->consentModel->getConsentStats();
    }
}
