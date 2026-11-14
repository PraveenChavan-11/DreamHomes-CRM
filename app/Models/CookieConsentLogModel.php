<?php

namespace App\Models;

use CodeIgniter\Model;

class CookieConsentLogModel extends Model
{
    protected $table            = 'cookie_consent_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'consent_uuid',
        'necessary',
        'analytics',
        'marketing',
        'ip_hash',
        'user_agent_hash',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Get aggregate statistics on consent choices
     */
    public function getConsentStats(): array
    {
        $db = \Config\Database::connect();
        $row = $db->query("
            SELECT 
                COUNT(*) as total_logged,
                SUM(CASE WHEN analytics = 1 THEN 1 ELSE 0 END) as analytics_accepted,
                SUM(CASE WHEN marketing = 1 THEN 1 ELSE 0 END) as marketing_accepted
            FROM cookie_consent_logs
        ")->getRowArray();

        return [
            'total'      => (int) ($row['total_logged'] ?? 0),
            'analytics'  => (int) ($row['analytics_accepted'] ?? 0),
            'marketing'  => (int) ($row['marketing_accepted'] ?? 0),
        ];
    }
}
