<?php

namespace App\Models;

use CodeIgniter\Model;

class MailActivityLogModel extends Model
{
    protected $table            = 'mail_activity_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'admin_user_id',
        'action',
        'mailbox_resource_id',
        'message_uid',
        'details_json',
        'ip_address',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function record(string $action, ?string $mailboxId = null, ?string $uid = null, array $details = [], ?int $userId = null): void
    {
        $this->insert([
            'admin_user_id'       => $userId ?? (session()->get('user_id') ?? null),
            'action'              => $action,
            'mailbox_resource_id' => $mailboxId,
            'message_uid'         => $uid,
            'details_json'        => !empty($details) ? json_encode($details) : null,
            'ip_address'          => service('request')->getIPAddress(),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }
}

