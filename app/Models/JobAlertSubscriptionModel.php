<?php

namespace App\Models;

use CodeIgniter\Model;

class JobAlertSubscriptionModel extends Model
{
    protected $table            = 'job_alert_subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'job_category',
        'category_id',
        'preferred_location',
        'experience_level',
        'employment_type',
        'frequency',
        'status',
        'token_hash',
        'last_alert_sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Find active subscription by email and category
     */
    public function findExisting(string $email, ?string $category = null): ?array
    {
        $builder = $this->where('email', strtolower(trim($email)));
        if ($category) {
            $builder->where('job_category', $category);
        }
        return $builder->first();
    }

    /**
     * Find by token hash
     */
    public function findByTokenHash(string $hash): ?array
    {
        return $this->where('token_hash', $hash)->first();
    }

    /**
     * Fetch active alerts due for processing
     */
    public function getDueAlerts(string $frequency = 'daily'): array
    {
        $cutoff = match ($frequency) {
            'immediate' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'daily'     => date('Y-m-d H:i:s', strtotime('-24 hours')),
            'weekly'    => date('Y-m-d H:i:s', strtotime('-7 days')),
            default     => date('Y-m-d H:i:s', strtotime('-24 hours')),
        };

        return $this->where('status', 'active')
                    ->where('frequency', $frequency)
                    ->groupStart()
                        ->where('last_alert_sent_at IS NULL', null, false)
                        ->orWhere('last_alert_sent_at <=', $cutoff)
                    ->groupEnd()
                    ->findAll(50);
    }
}
