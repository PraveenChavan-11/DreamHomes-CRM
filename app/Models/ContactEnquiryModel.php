<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactEnquiryModel extends Model
{
    protected $table            = 'contact_enquiries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'subject',
        'message',
        'status',
        'ip_address',
        'idempotency_key',
        'consent_received',
        'consent_timestamp',
        'follow_up_date',
        'follow_up_notes',
        'reopened_at',
        'closed_at',
        'last_contacted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

