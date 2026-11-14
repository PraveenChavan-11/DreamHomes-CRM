<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployerEnquiryModel extends Model
{
    protected $table            = 'employer_enquiries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'required_roles',
        'number_of_positions',
        'location',
        'experience_bracket',
        'urgency',
        'notes',
        'status',
        'assigned_to',
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

