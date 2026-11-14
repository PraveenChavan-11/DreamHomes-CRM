<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'role_target',
        'category',
        'title',
        'message',
        'priority',
        'link',
        'is_read',
        'read_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActiveForUser(?int $userId = null, ?string $role = null, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->groupStart();
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        if ($role) {
            $builder->orWhere('role_target', $role)
                    ->orWhere('role_target IS NULL');
        } else {
            $builder->orWhere('user_id IS NULL');
        }
        $builder->groupEnd();

        return $builder->orderBy('is_read', 'ASC')
                       ->orderBy('created_at', 'DESC')
                       ->get($limit)
                       ->getResultArray();
    }
}

