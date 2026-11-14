<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementEventModel extends Model
{
    protected $table            = 'announcement_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'announcement_id',
        'event_type',
        'ip_hash',
        'user_agent_hash',
        'session_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Record an announcement event with deduplication check (prevent duplicate event within 10 minutes from same session/IP)
     */
    public function recordEvent(int $announcementId, string $eventType, ?string $ipHash = null, ?string $uaHash = null, ?string $sessionId = null): bool
    {
        // Check for duplicate in last 10 minutes
        $tenMinutesAgo = date('Y-m-d H:i:s', strtotime('-10 minutes'));
        $builder = $this->where('announcement_id', $announcementId)
                        ->where('event_type', $eventType)
                        ->where('created_at >=', $tenMinutesAgo);

        if ($sessionId) {
            $builder->where('session_id', $sessionId);
        } elseif ($ipHash) {
            $builder->where('ip_hash', $ipHash);
        }

        $recent = $builder->first();
        if ($recent) {
            // Already counted recently; prevent inflated numbers
            return false;
        }

        return (bool) $this->insert([
            'announcement_id' => $announcementId,
            'event_type'      => $eventType,
            'ip_hash'         => $ipHash,
            'user_agent_hash' => $uaHash,
            'session_id'      => $sessionId,
        ]);
    }

    /**
     * Aggregate event statistics by announcement
     */
    public function getAnalyticsSummary(): array
    {
        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT 
                a.id,
                a.title,
                a.display_format,
                a.status,
                COUNT(CASE WHEN e.event_type = 'impression' THEN 1 END) as impressions,
                COUNT(DISTINCT CASE WHEN e.event_type = 'impression' THEN e.ip_hash END) as unique_impressions,
                COUNT(CASE WHEN e.event_type = 'click' THEN 1 END) as clicks,
                COUNT(CASE WHEN e.event_type = 'dismissal' THEN 1 END) as dismissals,
                COUNT(CASE WHEN e.event_type = 'conversion' THEN 1 END) as conversions
            FROM announcements a
            LEFT JOIN announcement_events e ON a.id = e.announcement_id
            GROUP BY a.id, a.title, a.display_format, a.status
            ORDER BY a.priority DESC, a.id DESC
        ");

        return $query->getResultArray();
    }
}
