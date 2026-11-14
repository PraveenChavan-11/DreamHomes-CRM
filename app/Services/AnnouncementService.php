<?php

namespace App\Services;

use App\Models\AnnouncementModel;
use App\Models\AnnouncementEventModel;

class AnnouncementService
{
    private AnnouncementModel $announcementModel;
    private AnnouncementEventModel $eventModel;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->eventModel        = new AnnouncementEventModel();
    }

    /**
     * Get active announcements for a visitor
     */
    public function getActiveForVisitor(string $audience = 'everyone', ?string $currentPage = null, bool $isReturning = false): array
    {
        $all = $this->announcementModel->getEligibleAnnouncements($audience, $currentPage, $isReturning);

        // Separate modals from banners to prevent modal popup stacking
        $modals = [];
        $banners = [];
        $inlines = [];

        foreach ($all as $item) {
            $format = $item['display_format'];
            if (in_array($format, ['center_modal', 'newsletter_popup'], true)) {
                $modals[] = $item;
            } elseif (in_array($format, ['top_banner', 'bottom_banner'], true)) {
                $banners[] = $item;
            } else {
                $inlines[] = $item;
            }
        }

        $result = [];

        // At most one top-priority modal popup per page view
        if (!empty($modals)) {
            $result[] = $modals[0];
        }

        // At most one banner per page view
        if (!empty($banners)) {
            $result[] = $banners[0];
        }

        // Include any inline announcements
        foreach ($inlines as $inline) {
            $result[] = $inline;
        }

        return $result;
    }

    /**
     * Track announcement event with IP hashing and deduplication
     */
    public function trackEvent(int $announcementId, string $eventType, ?string $ip = null, ?string $userAgent = null, ?string $sessionId = null): bool
    {
        $validTypes = ['impression', 'click', 'dismissal', 'conversion'];
        if (!in_array($eventType, $validTypes, true)) {
            return false;
        }

        $ipHash = $ip ? hash('sha256', $ip . 'talenthub_salt_engagement') : null;
        $uaHash = $userAgent ? hash('sha256', $userAgent) : null;

        return $this->eventModel->recordEvent($announcementId, $eventType, $ipHash, $uaHash, $sessionId);
    }

    /**
     * Run lifecycle state transitions for scheduled & expired campaigns
     */
    public function runLifecycleTransitions(): array
    {
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
        $campaigns = $this->announcementModel->whereIn('status', ['SCHEDULED', 'ACTIVE'])->findAll();

        $promoted = 0;
        $expired = 0;

        foreach ($campaigns as $camp) {
            $tz = !empty($camp['timezone']) ? $camp['timezone'] : 'Asia/Kolkata';
            try {
                $dtZone = new \DateTimeZone($tz);
            } catch (\Throwable $e) {
                $dtZone = new \DateTimeZone('Asia/Kolkata');
            }

            $now = new \DateTime('now', $dtZone);
            $startDt = new \DateTime("{$camp['start_date']} {$camp['start_time']}", $dtZone);
            $endDt   = new \DateTime("{$camp['end_date']} {$camp['end_time']}", $dtZone);

            if ($now > $endDt) {
                $this->announcementModel->update($camp['id'], ['status' => 'EXPIRED']);
                $expired++;
            } elseif ($camp['status'] === 'SCHEDULED' && $now >= $startDt) {
                $this->announcementModel->update($camp['id'], ['status' => 'ACTIVE']);
                $promoted++;
            }
        }

        return [
            'promoted' => $promoted,
            'expired'  => $expired,
        ];
    }
}
