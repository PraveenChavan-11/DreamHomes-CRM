<?php

namespace App\Models;

use CodeIgniter\Model;

class MailWebhookEventModel extends Model
{
    protected $table            = 'mail_webhook_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'event_id',
        'event_type',
        'mailbox_resource_id',
        'payload_json',
        'status',
        'error_message',
        'ip_address',
        'processed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}

