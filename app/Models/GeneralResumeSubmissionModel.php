<?php

namespace App\Models;

use CodeIgniter\Model;

class GeneralResumeSubmissionModel extends Model
{
    protected $table            = 'general_resume_submissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'candidate_id',
        'document_id',
        'desired_role',
        'preferred_location',
        'notice_period',
        'notes',
        'status',
        'assigned_recruiter_id',
        'idempotency_key',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getSubmissionsWithDetails(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->builder();
        $builder->select('general_resume_submissions.*, candidates.full_name as candidate_name, candidates.email as candidate_email, candidates.phone as candidate_phone, candidates.total_experience_years as experience_years, candidate_documents.original_name as resume_name')
                ->join('candidates', 'candidates.id = general_resume_submissions.candidate_id', 'left')
                ->join('candidate_documents', 'candidate_documents.id = general_resume_submissions.document_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('general_resume_submissions.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('candidates.full_name', $term)
                    ->orLike('candidates.email', $term)
                    ->orLike('general_resume_submissions.desired_role', $term)
                    ->groupEnd();
        }

        $builder->orderBy('general_resume_submissions.created_at', 'DESC');
        return $builder->get($limit, $offset)->getResultArray();
    }

    public function countSubmissionsWithDetails(array $filters = []): int
    {
        $builder = $this->builder();
        $builder->join('candidates', 'candidates.id = general_resume_submissions.candidate_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('general_resume_submissions.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('candidates.full_name', $term)
                    ->orLike('candidates.email', $term)
                    ->orLike('general_resume_submissions.desired_role', $term)
                    ->groupEnd();
        }

        return $builder->countAllResults();
    }
}

