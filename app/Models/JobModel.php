<?php

namespace App\Models;

use CodeIgniter\Model;

class JobModel extends Model
{
    protected $table            = 'jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employer_id',
        'category_id',
        'title',
        'slug',
        'experience_min',
        'experience_max',
        'experience_label',
        'job_type',
        'work_mode',
        'location',
        'salary_min',
        'salary_max',
        'is_salary_negotiable',
        'is_salary_public',
        'description',
        'requirements',
        'skills',
        'benefits',
        'status',
        'is_featured',
        'published_at',
        'expires_at',
        'created_by',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Get published jobs with category & employer joins and filters
     */
    public function getPublishedJobs(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $builder = $this->builder();
        $builder->select('jobs.*, job_categories.name as category_name, job_categories.slug as category_slug, employers.name as employer_name, employers.slug as employer_slug')
                ->join('job_categories', 'job_categories.id = jobs.category_id', 'left')
                ->join('employers', 'employers.id = jobs.employer_id', 'left')
                ->where('jobs.status', 'PUBLISHED')
                ->where('jobs.deleted_at IS NULL');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('jobs.title', $term)
                    ->orLike('jobs.description', $term)
                    ->orLike('jobs.location', $term)
                    ->orLike('job_categories.name', $term)
                    ->groupEnd();
        }

        if (!empty($filters['category'])) {
            $builder->where('job_categories.slug', $filters['category']);
        }

        if (!empty($filters['location'])) {
            $builder->like('jobs.location', $filters['location']);
        }

        if (!empty($filters['job_type'])) {
            $builder->where('jobs.job_type', $filters['job_type']);
        }

        if (!empty($filters['work_mode'])) {
            $builder->where('jobs.work_mode', $filters['work_mode']);
        }

        if (isset($filters['exp_min']) && is_numeric($filters['exp_min'])) {
            $builder->where('jobs.experience_min >=', (int) $filters['exp_min']);
        }

        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'oldest':
                    $builder->orderBy('jobs.published_at', 'ASC');
                    break;
                case 'salary_high':
                    $builder->orderBy('jobs.salary_max', 'DESC');
                    break;
                case 'salary_low':
                    $builder->orderBy('jobs.salary_min', 'ASC');
                    break;
                case 'newest':
                default:
                    $builder->orderBy('jobs.published_at', 'DESC');
                    break;
            }
        } else {
            $builder->orderBy('jobs.published_at', 'DESC');
        }
        return $builder->get($limit, $offset)->getResultArray();
    }

    /**
     * Count published jobs matching filters
     */
    public function countPublishedJobs(array $filters = []): int
    {
        $builder = $this->builder();
        $builder->join('job_categories', 'job_categories.id = jobs.category_id', 'left')
                ->join('employers', 'employers.id = jobs.employer_id', 'left')
                ->where('jobs.status', 'PUBLISHED')
                ->where('jobs.deleted_at IS NULL');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $builder->groupStart()
                    ->like('jobs.title', $term)
                    ->orLike('jobs.description', $term)
                    ->orLike('jobs.location', $term)
                    ->orLike('job_categories.name', $term)
                    ->groupEnd();
        }

        if (!empty($filters['category'])) {
            $builder->where('job_categories.slug', $filters['category']);
        }

        if (!empty($filters['location'])) {
            $builder->like('jobs.location', $filters['location']);
        }

        if (!empty($filters['job_type'])) {
            $builder->where('jobs.job_type', $filters['job_type']);
        }

        if (!empty($filters['work_mode'])) {
            $builder->where('jobs.work_mode', $filters['work_mode']);
        }

        return $builder->countAllResults();
    }

    /**
     * Get a single published job by slug
     */
    public function getPublishedBySlug(string $slug): ?array
    {
        return $this->select('jobs.*, job_categories.name as category_name, job_categories.slug as category_slug, employers.name as employer_name, employers.website as employer_website, employers.location as employer_location')
                    ->join('job_categories', 'job_categories.id = jobs.category_id', 'left')
                    ->join('employers', 'employers.id = jobs.employer_id', 'left')
                    ->where('jobs.slug', $slug)
                    ->where('jobs.status', 'PUBLISHED')
                    ->first();
    }
}

