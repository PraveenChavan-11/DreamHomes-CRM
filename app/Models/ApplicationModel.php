<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table            = 'applications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'job_id',
        'candidate_id',
        'document_id',
        'cover_message',
        'status',
        'recruiter_notes',
        'rating',
        'assigned_recruiter_id',
        'source',
        'ip_address',
        'idempotency_key',
        'follow_up_date',
        'follow_up_notes',
        'reopened_at',
        'closed_at',
        'last_contacted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get applications with candidate, job, and document joins
     */
    public function getApplicationsWithDetails(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->builder();
        $builder->select('applications.*, candidates.full_name as candidate_name, candidates.email as candidate_email, candidates.phone as candidate_phone, candidates.total_experience_years as experience_years, jobs.title as job_title, jobs.slug as job_slug, candidate_documents.original_name as resume_name, candidate_documents.id as document_id')
                ->join('candidates', 'candidates.id = applications.candidate_id', 'left')
                ->join('jobs', 'jobs.id = applications.job_id', 'left')
                ->join('candidate_documents', 'candidate_documents.id = applications.document_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('applications.status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $builder->where('applications.job_id', $filters['job_id']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('candidates.full_name', $term)
                    ->orLike('candidates.email', $term)
                    ->orLike('jobs.title', $term)
                    ->groupEnd();
        }

        $builder->orderBy('applications.created_at', 'DESC');
        return $builder->get($limit, $offset)->getResultArray();
    }

    public function countApplicationsWithDetails(array $filters = []): int
    {
        $builder = $this->builder();
        $builder->join('candidates', 'candidates.id = applications.candidate_id', 'left')
                ->join('jobs', 'jobs.id = applications.job_id', 'left');

        if (!empty($filters['status'])) {
            $builder->where('applications.status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $builder->where('applications.job_id', $filters['job_id']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('candidates.full_name', $term)
                    ->orLike('candidates.email', $term)
                    ->orLike('jobs.title', $term)
                    ->groupEnd();
        }

        return $builder->countAllResults();
    }
}

