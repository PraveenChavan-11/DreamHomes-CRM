<?php

namespace App\Models;

use CodeIgniter\Model;

class MailboxConnectionModel extends Model
{
    protected $table            = 'mailbox_connections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'mailbox_resource_id',
        'email',
        'display_name',
        'status',
        'quota_used_bytes',
        'quota_total_bytes',
        'is_default',
        'assigned_roles',
        'last_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getDefaultMailbox(): ?array
    {
        return $this->where('is_default', 1)->first() ?: $this->first();
    }

    public function getByResourceId(string $resourceId): ?array
    {
        return $this->where('mailbox_resource_id', $resourceId)->first();
    }
}

