<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailDeliveryLogModel extends Model
{
    protected $table            = 'email_delivery_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'campaign_id',
        'subscriber_id',
        'recipient_email',
        'subject',
        'provider',
        'provider_message_id',
        'status',
        'attempt_count',
        'last_attempt',
        'error_message',
        'sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
