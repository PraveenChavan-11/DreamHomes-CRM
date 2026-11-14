<?php

namespace App\Controllers\Admin;

use App\Models\ApplicationModel;
use App\Models\AuditLogModel;
use App\Models\JobModel;
use App\Services\DocumentVaultService;
use App\Services\EnquiryAutomationService;

class ApplicationsController extends AdminBaseController
{
    private EnquiryAutomationService $automationService;

    public function __construct()
    {
        $this->automationService = new EnquiryAutomationService();
    }
    public function index()
    {
        if ($redirect = $this->requirePermission('applications.view')) {
            return $redirect;
        }

        $appModel = new ApplicationModel();
        $jobModel = new JobModel();

        $status = $this->request->getGet('status');
        $jobId = $this->request->getGet('job_id');
        $search = $this->request->getGet('search');
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($status)) {
            $filters['status'] = strtoupper($status);
        }
        if (!empty($jobId)) {
            $filters['job_id'] = (int) $jobId;
        }
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        // CSV Export Handler
        if ($this->request->getGet('export') === 'csv') {
            $exportRecords = $appModel->getApplicationsWithDetails($filters, 2000, 0);

            $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM
            $csvData .= "Application ID,Candidate Name,Email,Phone,Applied Job,Experience (Years),Status,Applied Date,Cover Message\n";

            foreach ($exportRecords as $row) {
                $name = str_replace('"', '""', $row['candidate_name'] ?? '');
                $email = str_replace('"', '""', $row['candidate_email'] ?? '');
                $phone = str_replace('"', '""', $row['candidate_phone'] ?? '');
                $job = str_replace('"', '""', $row['job_title'] ?? '');
                $exp = str_replace('"', '""', (string) ($row['experience_years'] ?? '0'));
                $st = str_replace('"', '""', $row['status'] ?? '');
                $date = str_replace('"', '""', $row['created_at'] ?? '');
                $msg = str_replace('"', '""', preg_replace("/\r|\n/", " ", $row['cover_message'] ?? ''));

                $csvData .= "\"{$row['id']}\",\"{$name}\",\"{$email}\",\"{$phone}\",\"{$job}\",\"{$exp}\",\"{$st}\",\"{$date}\",\"{$msg}\"\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_applications_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        try {
            $totalCount = $appModel->countApplicationsWithDetails($filters);
            $applications = $appModel->getApplicationsWithDetails($filters, $perPage, $offset);
        } catch (\Throwable $e) {
            $totalCount = $appModel->countAllResults();
            $applications = $appModel->orderBy('created_at', 'DESC')->findAll($perPage, $offset);
        }

        $jobs = $jobModel->findAll();
        $totalPages = ceil($totalCount / $perPage);

        return $this->renderAdminView('admin/applications/index', [
            'applications' => $applications,
            'jobs'         => $jobs,
            'status'       => $status,
            'jobId'        => $jobId,
            'search'       => $search,
            'perPage'      => $perPage,
            'page'         => $page,
            'totalCount'   => $totalCount,
            'totalPages'   => $totalPages,
            'title'        => 'Candidate Applications — TalentHub Admin',
        ], 'applications');
    }

    public function updateStatus($id)
    {
        if ($redirect = $this->requirePermission('applications.transition')) {
            return $redirect;
        }

        $rawStatus    = strtoupper(trim($this->request->getPost('status') ?? ''));
        $comment      = $this->request->getPost('comment') ?? $this->request->getPost('recruiter_notes');
        $followUpDate = $this->request->getPost('follow_up_date');
        $adminUser    = session()->get('admin_user');
        $adminUserId  = $adminUser['id'] ?? null;

        $statusMap = [
            'NEW'         => 'APPLIED',
            'APPLIED'     => 'APPLIED',
            'REVIEWING'   => 'SCREENING',
            'SCREENING'   => 'SCREENING',
            'SHORTLISTED' => 'SHORTLISTED',
            'INTERVIEW'   => 'INTERVIEW',
            'OFFERED'     => 'OFFER',
            'OFFER'       => 'OFFER',
            'HIRED'       => 'HIRED',
            'REJECTED'    => 'REJECTED',
            'WITHDRAWN'   => 'WITHDRAWN',
        ];

        $targetStatus = $statusMap[$rawStatus] ?? null;

        if (!$targetStatus) {
            session()->setFlashdata('error', 'Invalid candidate status selected.');
            return redirect()->to(base_url('admin/applications'));
        }

        $result = $this->automationService->updateApplicationStatus(
            (int) $id,
            $targetStatus,
            $comment,
            $followUpDate,
            $adminUserId
        );

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setJSON($result);
        }

        if ($result['success']) {
            session()->setFlashdata('success', $result['message']);
        } else {
            session()->setFlashdata('error', $result['message']);
        }

        return redirect()->to(base_url('admin/applications'));
    }

    /**
     * Bulk Application Actions (Pipeline Stage Transition & Export)
     */
    public function bulkAction()
    {
        if ($redirect = $this->requirePermission('applications.transition')) {
            return $redirect;
        }

        $action       = $this->request->getPost('bulk_action');
        $selectedIds  = (array) ($this->request->getPost('selected_ids') ?? []);
        $targetStatus = strtoupper(trim((string) ($this->request->getPost('target_status') ?? '')));
        $notes        = trim((string) ($this->request->getPost('notes') ?? 'Bulk updated by administrator.'));
        $followUpDate = $this->request->getPost('follow_up_date') ?: null;
        $adminUser    = session()->get('admin_user');
        $adminUserId  = $adminUser['id'] ?? null;

        $validIds = array_values(array_filter(array_map('intval', $selectedIds)));

        if (empty($validIds)) {
            session()->setFlashdata('error', 'No candidate applications were selected. Please select at least one record.');
            return redirect()->to(base_url('admin/applications'));
        }

        $appModel = new ApplicationModel();

        // 1. Bulk Export as CSV
        if ($action === 'export') {
            $records = $appModel->whereIn('id', $validIds)->findAll();
            $csvData = "\xEF\xBB\xBF";
            $csvData .= "Application ID,Candidate Name,Email,Phone,Applied Job ID,Status,Created At\n";
            foreach ($records as $row) {
                $name = str_replace('"', '""', $row['candidate_name'] ?? '');
                $email = str_replace('"', '""', $row['candidate_email'] ?? '');
                $phone = str_replace('"', '""', $row['candidate_phone'] ?? '');
                $jobId = str_replace('"', '""', (string) ($row['job_id'] ?? ''));
                $st = str_replace('"', '""', $row['status'] ?? '');
                $date = str_replace('"', '""', $row['created_at'] ?? '');
                $csvData .= "\"{$row['id']}\",\"{$name}\",\"{$email}\",\"{$phone}\",\"{$jobId}\",\"{$st}\",\"{$date}\"\n";
            }
            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_selected_apps_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        // 2. Bulk Transition Pipeline Stage
        if ($action === 'transition') {
            $statusMap = [
                'NEW'         => 'APPLIED',
                'APPLIED'     => 'APPLIED',
                'REVIEWING'   => 'SCREENING',
                'SCREENING'   => 'SCREENING',
                'SHORTLISTED' => 'SHORTLISTED',
                'INTERVIEW'   => 'INTERVIEW',
                'OFFERED'     => 'OFFER',
                'OFFER'       => 'OFFER',
                'HIRED'       => 'HIRED',
                'REJECTED'    => 'REJECTED',
                'WITHDRAWN'   => 'WITHDRAWN',
            ];

            $canonicalStatus = $statusMap[$targetStatus] ?? null;
            if (!$canonicalStatus) {
                session()->setFlashdata('error', 'Invalid target pipeline stage selected for bulk transition.');
                return redirect()->to(base_url('admin/applications'));
            }

            $successCount = 0;
            foreach ($validIds as $id) {
                $res = $this->automationService->updateApplicationStatus(
                    $id,
                    $canonicalStatus,
                    $notes,
                    $followUpDate,
                    $adminUserId
                );
                if ($res['success']) {
                    $successCount++;
                }
            }

            try {
                $audit = new AuditLogModel();
                $audit->log('BULK_APPLICATIONS_TRANSITION', 'candidate_applications', null, null, [
                    'count'         => $successCount,
                    'target_status' => $canonicalStatus,
                    'ids'           => $validIds,
                ], $adminUserId);
            } catch (\Throwable $e) {}

            session()->setFlashdata('success', "Successfully transitioned {$successCount} candidate application(s) to {$canonicalStatus}.");
            return redirect()->to(base_url('admin/applications'));
        }

        session()->setFlashdata('error', 'Unrecognized bulk action requested.');
        return redirect()->to(base_url('admin/applications'));
    }

    public function downloadResume($id)
    {
        if ($redirect = $this->requirePermission('resumes.download')) {
            return $redirect;
        }

        $appModel = new ApplicationModel();
        $app = $appModel->find((int) $id);

        if (!$app) {
            session()->setFlashdata('error', 'Candidate application not found.');
            return redirect()->to(base_url('admin/applications'));
        }

        // 1. Vault retrieval if document_id present
        if (!empty($app['document_id'])) {
            try {
                $vault = new DocumentVaultService();
                $doc = $vault->getDocument((int) $app['document_id']);
                if ($doc && !empty($doc['full_path']) && file_exists($doc['full_path'])) {
                    $auditModel = new \App\Models\AuditLogModel();
                    $auditModel->log('RESUME_DOWNLOAD', 'application', (int) $id, null, ['document_id' => $app['document_id']]);

                    return $this->response->download($doc['full_path'], null)->setFileName($doc['original_name'] ?? 'Candidate_Resume.pdf');
                }
            } catch (\Throwable $e) {
                log_message('error', 'Download resume vault error: ' . $e->getMessage());
            }
        }

        session()->setFlashdata('error', 'Resume document cannot be located in the secure repository.');
        return redirect()->to(base_url('admin/applications'));
    }
}
