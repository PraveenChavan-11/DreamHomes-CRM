<?php

namespace App\Models;

use CodeIgniter\Model;

class MailLocalDraftModel extends Model
{
    protected $table            = 'mail_local_drafts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'admin_user_id',
        'mailbox_resource_id',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'subject',
        'body_html',
        'body_text',
        'attachments_json',
        'in_reply_to',
        'thread_id',
        'category',
        'recruitment_type',
        'recruitment_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

