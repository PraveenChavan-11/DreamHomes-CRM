<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailCampaignModel extends Model
{
    protected $table            = 'email_campaigns';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'campaign_type',
        'template_id',
        'subject',
        'preview_text',
        'body_html',
        'body_text',
        'audience_filter',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'failed_count',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get aggregate delivery statistics across all campaigns
     */
    public function getDeliveryStats(): array
    {
        $db = \Config\Database::connect();
        $row = $db->query("
            SELECT 
                COALESCE(SUM(total_recipients), 0) as total_targeted,
                COALESCE(SUM(sent_count), 0) as total_sent,
                COALESCE(SUM(delivered_count), 0) as total_delivered,
                COALESCE(SUM(failed_count), 0) as total_failed
            FROM email_campaigns
        ")->getRowArray();

        return [
            'targeted'  => (int) ($row['total_targeted'] ?? 0),
            'sent'      => (int) ($row['total_sent'] ?? 0),
            'delivered' => (int) ($row['total_delivered'] ?? 0),
            'failed'    => (int) ($row['total_failed'] ?? 0),
        ];
    }
}
