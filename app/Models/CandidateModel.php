<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateModel extends Model
{
    protected $table            = 'candidates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'full_name',
        'email',
        'phone',
        'current_location',
        'preferred_location',
        'total_experience_years',
        'current_role',
        'primary_skills',
        'notice_period_days',
        'consent_received',
        'consent_timestamp',
        'consent_policy_version',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Upsert candidate profile by email
     */
    public function upsertCandidate(array $data): int
    {
        $existing = $this->where('email', $data['email'])->first();
        if ($existing) {
            $this->update($existing['id'], $data);
            return (int) $existing['id'];
        }

        return (int) $this->insert($data);
    }
}

