<?php

namespace App\Models;

use CodeIgniter\Model;

class MailMessageIndexModel extends Model
{
    protected $table            = 'mail_message_index';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mailbox_resource_id',
        'message_uid',
        'folder',
        'thread_id',
        'internet_message_id',
        'in_reply_to',
        'references_header',
        'subject',
        'from_name',
        'from_email',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'reply_to',
        'snippet',
        'body_html',
        'body_text',
        'has_attachments',
        'attachments_meta',
        'is_read',
        'is_flagged',
        'is_draft',
        'category',
        'recruitment_type',
        'recruitment_id',
        'internal_notes',
        'assigned_to',
        'message_date',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByUid(string $mailboxId, string $folder, string $uid): ?array
    {
        return $this->where('mailbox_resource_id', $mailboxId)
                    ->where('folder', $folder)
                    ->where('message_uid', $uid)
                    ->first();
    }
}

