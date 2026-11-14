<?php

namespace App\Models;

use CodeIgniter\Model;

class MailRecruitmentLinkModel extends Model
{
    protected $table            = 'mail_recruitment_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mailbox_resource_id',
        'message_uid',
        'folder',
        'entity_type',
        'entity_id',
        'notes',
        'created_by',
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
}

