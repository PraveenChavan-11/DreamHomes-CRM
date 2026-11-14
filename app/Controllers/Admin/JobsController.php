<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;

class JobsController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requireAnyPermission(['jobs.publish', 'jobs.close', 'applications.view'])) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $categoryModel = new JobCategoryModel();

        $search = trim((string) $this->request->getGet('search'));
        $status = trim((string) $this->request->getGet('status'));
        $categoryId = (int) $this->request->getGet('category_id');
        $perPage = (int) ($this->request->getGet('per_page') ?: 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $query = $jobModel->select('jobs.*, job_categories.name as category_name')
                          ->join('job_categories', 'job_categories.id = jobs.category_id', 'left')
                          ->orderBy('jobs.created_at', 'DESC');

        if (!empty($search)) {
            $query->groupStart()
                  ->like('jobs.title', $search)
                  ->orLike('jobs.location', $search)
                  ->orLike('jobs.skills', $search)
                  ->groupEnd();
        }

        if (!empty($status)) {
            if ($status === 'active' || $status === 'PUBLISHED') {
                $query->whereIn('jobs.status', ['PUBLISHED', 'active']);
            } elseif ($status === 'closed' || $status === 'CLOSED') {
                $query->whereIn('jobs.status', ['CLOSED', 'closed']);
            } elseif ($status === 'draft' || $status === 'DRAFT') {
                $query->whereIn('jobs.status', ['DRAFT', 'draft']);
            } else {
                $query->where('jobs.status', $status);
            }
        }

        if ($categoryId > 0) {
            $query->where('jobs.category_id', $categoryId);
        }

        // Export to CSV if requested
        if ($this->request->getGet('export') === 'csv') {
            return $this->exportCsv($query->findAll());
        }

        $jobs = $query->paginate($perPage);
        $pager = $jobModel->pager;
        $categories = $categoryModel->findAll();

        return $this->renderAdminView('admin/jobs/index', [
            'jobs'        => $jobs,
            'pager'       => $pager,
            'categories'  => $categories,
            'search'      => $search,
            'status'      => $status,
            'categoryId'  => $categoryId,
            'perPage'     => $perPage,
            'totalJobs'   => $jobModel->countAllResults(false),
            'title'       => 'Job Postings Management — TalentHub Admin',
        ], 'jobs');
    }

    public function new()
    {
        if ($redirect = $this->requirePermission('jobs.publish')) {
            return $redirect;
        }

        $categoryModel = new JobCategoryModel();
        return $this->renderAdminView('admin/jobs/form', [
            'job'        => null,
            'categories' => $categoryModel->findAll(),
            'title'      => 'Create New Job Posting — TalentHub Admin',
        ], 'jobs');
    }

    public function create()
    {
        if ($redirect = $this->requirePermission('jobs.publish')) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $auditModel = new AuditLogModel();

        $title = trim((string) $this->request->getPost('title'));
        $slug = trim((string) $this->request->getPost('slug'));
        if (empty($slug)) {
            $slug = url_title($title, '-', true);
        }

        // Ensure slug uniqueness
        $existing = $jobModel->where('slug', $slug)->first();
        if ($existing) {
            $slug .= '-' . substr(md5(uniqid()), 0, 4);
        }

        $jobType = match (strtoupper((string) $this->request->getPost('job_type'))) {
            'PT', 'PART-TIME', 'PART_TIME' => 'PT',
            'CT', 'CONTRACT'               => 'CT',
            'IN', 'INTERNSHIP'             => 'IN',
            default                        => 'FT',
        };

        $workMode = match (strtoupper((string) $this->request->getPost('work_mode'))) {
            'HYBRID' => 'HYBRID',
            'REMOTE' => 'REMOTE',
            default  => 'ONSITE',
        };

        $status = match (strtoupper((string) $this->request->getPost('status'))) {
            'DRAFT'   => 'DRAFT',
            'CLOSED'  => 'CLOSED',
            'REVIEW'  => 'REVIEW',
            'EXPIRED' => 'EXPIRED',
            default   => 'PUBLISHED',
        };

        $expLabel = trim((string) ($this->request->getPost('experience_label') ?: $this->request->getPost('experience_level') ?: '2-5 Years'));
        $expMin = (int) ($this->request->getPost('experience_min') ?: 2);
        $expMax = (int) ($this->request->getPost('experience_max') ?: 5);

        $data = [
            'title'                => $title,
            'slug'                 => $slug,
            'category_id'          => (int) $this->request->getPost('category_id'),
            'job_type'             => $jobType,
            'work_mode'            => $workMode,
            'experience_min'       => $expMin,
            'experience_max'       => $expMax,
            'experience_label'     => $expLabel,
            'location'             => trim((string) ($this->request->getPost('location') ?: 'Pune, Maharashtra')),
            'salary_min'           => $this->request->getPost('salary_min') ? (float) $this->request->getPost('salary_min') : null,
            'salary_max'           => $this->request->getPost('salary_max') ? (float) $this->request->getPost('salary_max') : null,
            'is_salary_negotiable' => $this->request->getPost('is_salary_negotiable') ? 1 : 0,
            'is_salary_public'     => $this->request->getPost('is_salary_public') ? 1 : 0,
            'description'          => (string) $this->request->getPost('description'),
            'requirements'         => (string) $this->request->getPost('requirements'),
            'skills'               => (string) $this->request->getPost('skills'),
            'benefits'             => (string) $this->request->getPost('benefits'),
            'status'               => $status,
            'is_featured'          => $this->request->getPost('is_featured') ? 1 : 0,
            'published_at'         => ($status === 'PUBLISHED') ? date('Y-m-d H:i:s') : null,
            'created_by'           => $this->adminUser['id'] ?? null,
            'meta_title'           => trim((string) $this->request->getPost('meta_title')) ?: null,
            'meta_description'     => trim((string) $this->request->getPost('meta_description')) ?: null,
            'meta_keywords'        => trim((string) $this->request->getPost('meta_keywords')) ?: null,
        ];

        try {
            $insertId = $jobModel->insert($data);
            $auditModel->log('CREATE', 'job', $insertId, null, $data);

            $notifyMsg = '';
            if ($status === 'PUBLISHED' && $this->request->getPost('notify_subscribers')) {
                $broadcast = (new \App\Services\EnquiryAutomationService())->broadcastNewJobAlert(array_merge($data, ['id' => $insertId]));
                if (!empty($broadcast['queued'])) {
                    $notifyMsg = " Job alert queued for {$broadcast['queued']} subscribed members.";
                }
            }

            session()->setFlashdata('success', "Job posting '{$title}' created successfully.{$notifyMsg}");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to create job posting: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/jobs'));
    }

    public function edit($id)
    {
        if ($redirect = $this->requirePermission('jobs.publish')) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $job = $jobModel->find($id);
        if (!$job) {
            session()->setFlashdata('error', 'Job posting not found.');
            return redirect()->to(base_url('admin/jobs'));
        }

        $categoryModel = new JobCategoryModel();
        return $this->renderAdminView('admin/jobs/form', [
            'job'        => $job,
            'categories' => $categoryModel->findAll(),
            'title'      => "Edit Job: {$job['title']} — TalentHub Admin",
        ], 'jobs');
    }

    public function update($id)
    {
        if ($redirect = $this->requirePermission('jobs.publish')) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $auditModel = new AuditLogModel();

        $job = $jobModel->find($id);
        if (!$job) {
            session()->setFlashdata('error', 'Job posting not found.');
            return redirect()->to(base_url('admin/jobs'));
        }

        $title = trim((string) $this->request->getPost('title'));
        $slug = trim((string) $this->request->getPost('slug'));
        if (empty($slug)) {
            $slug = url_title($title, '-', true);
        }

        $jobType = match (strtoupper((string) $this->request->getPost('job_type'))) {
            'PT', 'PART-TIME', 'PART_TIME' => 'PT',
            'CT', 'CONTRACT'               => 'CT',
            'IN', 'INTERNSHIP'             => 'IN',
            default                        => 'FT',
        };

        $workMode = match (strtoupper((string) $this->request->getPost('work_mode'))) {
            'HYBRID' => 'HYBRID',
            'REMOTE' => 'REMOTE',
            default  => 'ONSITE',
        };

        $status = match (strtoupper((string) $this->request->getPost('status'))) {
            'DRAFT'   => 'DRAFT',
            'CLOSED'  => 'CLOSED',
            'REVIEW'  => 'REVIEW',
            'EXPIRED' => 'EXPIRED',
            default   => 'PUBLISHED',
        };

        $expLabel = trim((string) ($this->request->getPost('experience_label') ?: $this->request->getPost('experience_level') ?: $job['experience_label']));
        $expMin = (int) ($this->request->getPost('experience_min') ?: $job['experience_min']);
        $expMax = (int) ($this->request->getPost('experience_max') ?: $job['experience_max']);

        $data = [
            'title'                => $title,
            'slug'                 => $slug,
            'category_id'          => (int) $this->request->getPost('category_id'),
            'job_type'             => $jobType,
            'work_mode'            => $workMode,
            'experience_min'       => $expMin,
            'experience_max'       => $expMax,
            'experience_label'     => $expLabel,
            'location'             => trim((string) ($this->request->getPost('location') ?: 'Pune, Maharashtra')),
            'salary_min'           => $this->request->getPost('salary_min') ? (float) $this->request->getPost('salary_min') : null,
            'salary_max'           => $this->request->getPost('salary_max') ? (float) $this->request->getPost('salary_max') : null,
            'is_salary_negotiable' => $this->request->getPost('is_salary_negotiable') ? 1 : 0,
            'is_salary_public'     => $this->request->getPost('is_salary_public') ? 1 : 0,
            'description'          => (string) $this->request->getPost('description'),
            'requirements'         => (string) $this->request->getPost('requirements'),
            'skills'               => (string) $this->request->getPost('skills'),
            'benefits'             => (string) $this->request->getPost('benefits'),
            'status'               => $status,
            'is_featured'          => $this->request->getPost('is_featured') ? 1 : 0,
            'meta_title'           => trim((string) $this->request->getPost('meta_title')) ?: null,
            'meta_description'     => trim((string) $this->request->getPost('meta_description')) ?: null,
            'meta_keywords'        => trim((string) $this->request->getPost('meta_keywords')) ?: null,
        ];

        if ($status === 'PUBLISHED' && empty($job['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        try {
            $jobModel->update($id, $data);
            $auditModel->log('UPDATE', 'job', (int) $id, $job, $data);

            $notifyMsg = '';
            if ($status === 'PUBLISHED' && $this->request->getPost('notify_subscribers')) {
                $broadcast = (new \App\Services\EnquiryAutomationService())->broadcastNewJobAlert(array_merge($job, $data, ['id' => (int) $id]));
                if (!empty($broadcast['queued'])) {
                    $notifyMsg = " Job alert queued for {$broadcast['queued']} subscribed members.";
                }
            }

            session()->setFlashdata('success', "Job posting '{$title}' updated successfully.{$notifyMsg}");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to update job posting: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/jobs'));
    }

    public function toggleStatus($id)
    {
        if ($redirect = $this->requirePermission('jobs.close')) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $auditModel = new AuditLogModel();

        $job = $jobModel->find($id);
        if ($job) {
            $isCurrentActive = in_array(strtoupper($job['status'] ?? ''), ['PUBLISHED', 'ACTIVE']);
            $newStatus = $isCurrentActive ? 'CLOSED' : 'PUBLISHED';
            $jobModel->update($id, ['status' => $newStatus]);
            $auditModel->log('STATUS_CHANGE', 'job', (int) $id, ['status' => $job['status']], ['status' => $newStatus]);
            session()->setFlashdata('success', "Job status changed to " . ucfirst(strtolower($newStatus)) . ".");
        }

        return redirect()->to(base_url('admin/jobs'));
    }

    public function delete($id)
    {
        if ($redirect = $this->requirePermission('jobs.close')) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $auditModel = new AuditLogModel();

        $job = $jobModel->find($id);
        if ($job) {
            $jobModel->delete($id);
            $auditModel->log('ARCHIVE', 'job', (int) $id, $job, ['deleted_at' => date('Y-m-d H:i:s')]);
            session()->setFlashdata('success', 'Job posting archived successfully.');
        }

        return redirect()->to(base_url('admin/jobs'));
    }

    /**
     * Export jobs to CSV format
     */
    private function exportCsv(array $jobs)
    {
        $filename = 'talenthub_jobs_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Category', 'Job Type', 'Work Mode', 'Location', 'Min Salary', 'Max Salary', 'Status', 'Featured', 'Created At']);

        foreach ($jobs as $j) {
            fputcsv($output, [
                $j['id'],
                $j['title'],
                $j['category_name'] ?? 'General',
                $j['job_type'],
                $j['work_mode'] ?? 'ONSITE',
                $j['location'],
                $j['salary_min'] ?? '',
                $j['salary_max'] ?? '',
                $j['status'],
                !empty($j['is_featured']) ? 'Yes' : 'No',
                $j['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }
}
