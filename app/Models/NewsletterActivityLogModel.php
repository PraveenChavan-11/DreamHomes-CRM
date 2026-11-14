<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterActivityLogModel extends Model
{
    protected $table            = 'newsletter_activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subscriber_id',
        'activity_type',
        'activity_timestamp',
        'source',
        'metadata',
    ];

    protected $useTimestamps = false;

    /**
     * Record an audit activity
     */
    public function logActivity(int $subscriberId, string $type, ?string $source = null, $metadata = null): int
    {
        return (int) $this->insert([
            'subscriber_id'      => $subscriberId,
            'activity_type'      => $type,
            'activity_timestamp' => date('Y-m-d H:i:s'),
            'source'             => $source,
            'metadata'           => is_array($metadata) ? json_encode($metadata) : (string) $metadata,
        ]);
    }
}
