<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use CodeIgniter\HTTP\ResponseInterface;

class JobApiController extends BaseController
{
    private JobModel $jobModel;

    public function __construct()
    {
        $this->jobModel = new JobModel();
    }

    public function index(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'    => trim((string) $this->request->getGet('search')),
            'category'  => trim((string) $this->request->getGet('category')),
            'location'  => trim((string) $this->request->getGet('location')),
            'job_type'  => trim((string) $this->request->getGet('job_type')),
            'work_mode' => trim((string) $this->request->getGet('work_mode')),
        ];

        // If staff user requesting admin jobs, allow viewing drafts/closed
        $isAdminReq = str_contains($this->request->getUri()->getPath(), 'admin');
        if ($isAdminReq) {
            $builder = $this->jobModel->builder();
            $builder->select('jobs.*, job_categories.name as category_name, employers.name as employer_name')
                    ->join('job_categories', 'job_categories.id = jobs.category_id', 'left')
                    ->join('employers', 'employers.id = jobs.employer_id', 'left')
                    ->where('jobs.deleted_at IS NULL');

            if (!empty($filters['search'])) {
                $term = $filters['search'];
                $builder->groupStart()->like('jobs.title', $term)->orLike('jobs.location', $term)->groupEnd();
            }
            if ($status = $this->request->getGet('status')) {
                $builder->where('jobs.status', $status);
            }

            $total = $builder->countAllResults(false);
            $jobs = $builder->orderBy('jobs.created_at', 'DESC')->get($limit, $offset)->getResultArray();

            return $this->respondJson($jobs, 'Jobs retrieved', 200, [
                'pagination' => [
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => (int) ceil($total / $limit),
                ]
            ]);
        }

        $total = $this->jobModel->countPublishedJobs($filters);
        $jobs = $this->jobModel->getPublishedJobs($filters, $limit, $offset);

        return $this->respondJson($jobs, 'Jobs retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function show($idOrSlug): ResponseInterface
    {
        if (is_numeric($idOrSlug)) {
            $job = $this->jobModel->find($idOrSlug);
        } else {
            $job = $this->jobModel->getPublishedBySlug((string) $idOrSlug);
        }

        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        return $this->respondJson($job);
    }

    public function categories(): ResponseInterface
    {
        $catModel = new JobCategoryModel();
        return $this->respondJson($catModel->getActiveCategories());
    }

    public function store(): ResponseInterface
    {
        $rules = [
            'title'       => 'required|min_length[3]|max_length[200]',
            'category_id' => 'required|numeric',
            'location'    => 'required|min_length[2]|max_length[150]',
            'description' => 'required|min_length[20]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Validation failed', 422, 'VALIDATION_FAILED', $this->validator->getErrors());
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug = url_title($title, '-', true) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $data = [
            'employer_id'          => $this->request->getPost('employer_id') ?: 1,
            'category_id'          => $this->request->getPost('category_id'),
            'title'                => $title,
            'slug'                 => $slug,
            'experience_min'       => $this->request->getPost('experience_min') ?: null,
            'experience_max'       => $this->request->getPost('experience_max') ?: null,
            'experience_label'     => $this->request->getPost('experience_label') ?: null,
            'job_type'             => $this->request->getPost('job_type') ?: 'FT',
            'work_mode'            => $this->request->getPost('work_mode') ?: 'ONSITE',
            'location'             => $this->request->getPost('location'),
            'salary_min'           => $this->request->getPost('salary_min') ?: null,
            'salary_max'           => $this->request->getPost('salary_max') ?: null,
            'is_salary_negotiable' => $this->request->getPost('is_salary_negotiable') ? 1 : 0,
            'is_salary_public'     => $this->request->getPost('is_salary_public') ? 1 : 0,
            'description'          => $this->request->getPost('description'),
            'requirements'         => $this->request->getPost('requirements'),
            'status'               => $this->request->getPost('status') ?: 'PUBLISHED',
            'published_at'         => ($this->request->getPost('status') === 'PUBLISHED') ? date('Y-m-d H:i:s') : null,
            'created_by'           => session()->get('admin_user')['id'] ?? 1,
        ];

        $id = $this->jobModel->insert($data);
        $data['id'] = $id;

        $audit = new AuditLogModel();
        $audit->log('JOB_CREATED', 'jobs', $id, null, ['title' => $title]);

        return $this->respondJson($data, 'Job created successfully', 201);
    }

    public function update($id): ResponseInterface
    {
        $job = $this->jobModel->find($id);
        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $allowed = [
            'title', 'category_id', 'employer_id', 'experience_min', 'experience_max',
            'experience_label', 'job_type', 'work_mode', 'location', 'salary_min',
            'salary_max', 'is_salary_negotiable', 'is_salary_public', 'description',
            'requirements', 'status'
        ];

        $updates = [];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[$field] = $input[$field];
            }
        }

        if (isset($updates['status']) && $updates['status'] === 'PUBLISHED' && empty($job['published_at'])) {
            $updates['published_at'] = date('Y-m-d H:i:s');
        }

        $this->jobModel->update($id, $updates);

        $audit = new AuditLogModel();
        $audit->log('JOB_UPDATED', 'jobs', (int) $id, $job, $updates);

        return $this->respondJson($this->jobModel->find($id), 'Job updated successfully');
    }

    public function transitionStatus($id): ResponseInterface
    {
        $job = $this->jobModel->find($id);
        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $newStatus = $input['status'] ?? null;
        if (!in_array($newStatus, ['DRAFT', 'PUBLISHED', 'CLOSED', 'ARCHIVED'], true)) {
            return $this->respondError('Invalid status', 422);
        }

        $updates = ['status' => $newStatus];
        if ($newStatus === 'PUBLISHED' && empty($job['published_at'])) {
            $updates['published_at'] = date('Y-m-d H:i:s');
        }

        $this->jobModel->update($id, $updates);
        return $this->respondJson(['id' => $id, 'status' => $newStatus], 'Job status transitioned');
    }

    public function duplicate($id): ResponseInterface
    {
        $job = $this->jobModel->find($id);
        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        unset($job['id'], $job['created_at'], $job['updated_at'], $job['deleted_at']);
        $job['title'] = $job['title'] . ' (Copy)';
        $job['slug'] = url_title($job['title'], '-', true) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        $job['status'] = 'DRAFT';
        $job['published_at'] = null;

        $newId = $this->jobModel->insert($job);
        $job['id'] = $newId;

        return $this->respondJson($job, 'Job duplicated as draft', 201);
    }

    public function destroy($id): ResponseInterface
    {
        $job = $this->jobModel->find($id);
        if (!$job) {
            return $this->respondError('Job not found', 404);
        }

        $this->jobModel->delete($id);

        $audit = new AuditLogModel();
        $audit->log('JOB_DELETED', 'jobs', (int) $id, $job, null);

        return $this->respondJson([], 'Job archived successfully');
    }
}

