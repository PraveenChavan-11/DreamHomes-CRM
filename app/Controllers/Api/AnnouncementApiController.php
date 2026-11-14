<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\AnnouncementService;

class AnnouncementApiController extends ResourceController
{
    protected $format = 'json';
    private AnnouncementService $announcementService;

    public function __construct()
    {
        $this->announcementService = new AnnouncementService();
    }

    /**
     * GET /api/v1/announcements/active
     */
    public function active()
    {
        $audience    = (string) ($this->request->getGet('audience') ?? 'everyone');
        $currentPage = (string) ($this->request->getGet('page') ?? '/');
        $isReturning = (bool) ($this->request->getGet('returning') ?? false);

        $announcements = $this->announcementService->getActiveForVisitor($audience, $currentPage, $isReturning);

        // Public campaign responses use explicit cache-control headers and revalidation (Phase 9 requirement)
        $this->response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        return $this->respond([
            'success'       => true,
            'announcements' => $announcements,
            'count'         => count($announcements),
        ]);
    }

    /**
     * POST /api/v1/analytics/campaign-event
     */
    public function trackEvent()
    {
        $announcementId = (int) ($this->request->getPost('announcement_id') ?? $this->request->getVar('announcement_id') ?? 0);
        $eventType      = (string) ($this->request->getPost('event_type') ?? $this->request->getVar('event_type') ?? '');
        $sessionId      = (string) ($this->request->getPost('session_id') ?? $this->request->getVar('session_id') ?? null);

        if ($announcementId <= 0 || empty($eventType)) {
            return $this->failValidationError('Missing announcement_id or event_type.');
        }

        $ip = $this->request->getIPAddress();
        $ua = (string) $this->request->getUserAgent();

        $recorded = $this->announcementService->trackEvent($announcementId, $eventType, $ip, $ua, $sessionId);

        return $this->respond([
            'success'  => true,
            'recorded' => $recorded,
        ]);
    }
}
