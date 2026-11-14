<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function log(string $action, string $entityType, int|string|null $entityId = null, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): void
    {
        try {
            $request = service('request');
            $adminUserId = $userId;
            if ($adminUserId === null && session()->has('admin_user')) {
                $sessionUser = session()->get('admin_user');
                $adminUserId = !empty($sessionUser['id']) ? (int) $sessionUser['id'] : null;
            }

            $safeEntityId = ($entityId !== null && $entityId !== '') ? (string) $entityId : '0';

            $this->insert([
                'user_id'     => $adminUserId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $safeEntityId,
                'old_values'  => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'  => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip_address'  => $request->getIPAddress(),
                'user_agent'  => substr($request->getUserAgent()->getAgentString() ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogModel::log failed: ' . $e->getMessage());
        }
    }

    public function getLogsWithDetails(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $builder = $this->builder();
        $builder->select("audit_logs.*, CONCAT(COALESCE(admin_users.first_name, ''), ' ', COALESCE(admin_users.last_name, '')) as user_name, admin_users.email as user_email")
                ->join('admin_users', 'admin_users.id = audit_logs.user_id', 'left');

        if (!empty($filters['action'])) {
            $builder->where('audit_logs.action', $filters['action']);
        }
        if (!empty($filters['entity_type'])) {
            $builder->where('audit_logs.entity_type', $filters['entity_type']);
        }
        if (!empty($filters['user_id'])) {
            $builder->where('audit_logs.user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('audit_logs.action', $term)
                    ->orLike('audit_logs.entity_type', $term)
                    ->orLike('audit_logs.ip_address', $term)
                    ->orLike('admin_users.first_name', $term)
                    ->orLike('admin_users.last_name', $term)
                    ->orLike('admin_users.username', $term)
                    ->orLike('admin_users.email', $term)
                    ->groupEnd();
        }

        $builder->orderBy('audit_logs.created_at', 'DESC');
        return $builder->get($limit, $offset)->getResultArray();
    }

    public function countLogsWithDetails(array $filters = []): int
    {
        $builder = $this->builder();
        $builder->join('admin_users', 'admin_users.id = audit_logs.user_id', 'left');

        if (!empty($filters['action'])) {
            $builder->where('audit_logs.action', $filters['action']);
        }
        if (!empty($filters['entity_type'])) {
            $builder->where('audit_logs.entity_type', $filters['entity_type']);
        }
        if (!empty($filters['user_id'])) {
            $builder->where('audit_logs.user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('audit_logs.action', $term)
                    ->orLike('audit_logs.entity_type', $term)
                    ->orLike('audit_logs.ip_address', $term)
                    ->orLike('admin_users.first_name', $term)
                    ->orLike('admin_users.last_name', $term)
                    ->orLike('admin_users.username', $term)
                    ->orLike('admin_users.email', $term)
                    ->groupEnd();
        }

        return $builder->countAllResults();
    }
}

