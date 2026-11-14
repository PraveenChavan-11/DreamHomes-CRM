<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table            = 'announcements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'internal_description',
        'public_heading',
        'public_content',
        'image_url',
        'cta_text',
        'cta_url',
        'announcement_type',
        'display_format',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'timezone',
        'audience',
        'target_pages',
        'frequency',
        'priority',
        'status',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Fetch active announcements matching audience, page, and schedule
     */
    public function getEligibleAnnouncements(string $audience = 'everyone', ?string $currentPage = null, bool $isReturning = false): array
    {
        // 1. Fetch potential campaigns with ACTIVE or SCHEDULED status
        $campaigns = $this->whereIn('status', ['ACTIVE', 'SCHEDULED'])
                          ->orderBy('priority', 'DESC')
                          ->orderBy('id', 'DESC')
                          ->findAll();

        $eligible = [];

        foreach ($campaigns as $camp) {
            $tz = !empty($camp['timezone']) ? $camp['timezone'] : 'Asia/Kolkata';

            try {
                $dtZone = new \DateTimeZone($tz);
            } catch (\Throwable $e) {
                $dtZone = new \DateTimeZone('Asia/Kolkata');
            }

            $now = new \DateTime('now', $dtZone);
            $startStr = "{$camp['start_date']} {$camp['start_time']}";
            $endStr   = "{$camp['end_date']} {$camp['end_time']}";

            $startDt = new \DateTime($startStr, $dtZone);
            $endDt   = new \DateTime($endStr, $dtZone);

            // Check if expired
            if ($now > $endDt) {
                if ($camp['status'] === 'ACTIVE' || $camp['status'] === 'SCHEDULED') {
                    $this->update($camp['id'], ['status' => 'EXPIRED']);
                }
                continue;
            }

            // Check if not yet reached
            if ($now < $startDt) {
                continue;
            }

            // If scheduled and reached start time, promote to ACTIVE
            if ($camp['status'] === 'SCHEDULED') {
                $this->update($camp['id'], ['status' => 'ACTIVE']);
                $camp['status'] = 'ACTIVE';
            }

            // Audience targeting check
            $campAudience = $camp['audience'] ?? 'everyone';
            if ($campAudience !== 'everyone') {
                if ($campAudience === 'candidates' && $audience !== 'candidates') {
                    continue;
                }
                if ($campAudience === 'employers' && $audience !== 'employers') {
                    continue;
                }
                if ($campAudience === 'homepage' && ($currentPage !== '/' && $currentPage !== '')) {
                    continue;
                }
                if ($campAudience === 'insights' && !str_starts_with((string) $currentPage, 'insights')) {
                    continue;
                }
                if ($campAudience === 'new_visitors' && $isReturning) {
                    continue;
                }
                if ($campAudience === 'returning_visitors' && !$isReturning) {
                    continue;
                }
                if ($campAudience === 'selected_pages' && !empty($camp['target_pages'])) {
                    $pagesList = array_map('trim', explode(',', $camp['target_pages']));
                    if (!in_array($currentPage, $pagesList, true) && !in_array('/' . ltrim((string) $currentPage, '/'), $pagesList, true)) {
                        continue;
                    }
                }
            }

            $eligible[] = $camp;
        }

        return $eligible;
    }

    /**
     * Campaign telemetry counts
     */
    public function getCampaignStats(): array
    {
        $active = $this->where('status', 'ACTIVE')->countAllResults(false);
        $scheduled = $this->where('status', 'SCHEDULED')->countAllResults(false);
        $expired = $this->where('status', 'EXPIRED')->countAllResults(false);
        $draft = $this->where('status', 'DRAFT')->countAllResults(false);
        $paused = $this->where('status', 'PAUSED')->countAllResults(false);

        return [
            'active'    => $active,
            'scheduled' => $scheduled,
            'expired'   => $expired,
            'draft'     => $draft,
            'paused'    => $paused,
            'total'     => $active + $scheduled + $expired + $draft + $paused,
        ];
    }
}
