<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Services\DocumentVaultService;

class ResumeBankController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requirePermission('candidates.view')) {
            return $redirect;
        }

        $resumeModel = new GeneralResumeSubmissionModel();
        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');
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
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        // CSV Export Handler
        if ($this->request->getGet('export') === 'csv') {
            $exportRecords = $resumeModel->getSubmissionsWithDetails($filters, 2000, 0);

            $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM
            $csvData .= "ID,Candidate Name,Email,Phone,Desired Role,Preferred Location,Experience (Years),Notice Period,Status,Submitted Date\n";

            foreach ($exportRecords as $row) {
                $name = str_replace('"', '""', $row['candidate_name'] ?? '');
                $email = str_replace('"', '""', $row['candidate_email'] ?? '');
                $phone = str_replace('"', '""', $row['candidate_phone'] ?? '');
                $role = str_replace('"', '""', $row['desired_role'] ?? '');
                $loc = str_replace('"', '""', $row['preferred_location'] ?? '');
                $exp = str_replace('"', '""', (string) ($row['experience_years'] ?? '0'));
                $np = str_replace('"', '""', $row['notice_period'] ?? '');
                $st = str_replace('"', '""', $row['status'] ?? '');
                $date = str_replace('"', '""', $row['created_at'] ?? '');

                $csvData .= "\"{$row['id']}\",\"{$name}\",\"{$email}\",\"{$phone}\",\"{$role}\",\"{$loc}\",\"{$exp}\",\"{$np}\",\"{$st}\",\"{$date}\"\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_resumes_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        try {
            $totalCount = $resumeModel->countSubmissionsWithDetails($filters);
            $resumes = $resumeModel->getSubmissionsWithDetails($filters, $perPage, $offset);
        } catch (\Throwable $e) {
            $totalCount = $resumeModel->countAllResults();
            $resumes = $resumeModel->orderBy('created_at', 'DESC')->findAll($perPage, $offset);
        }

        $totalPages = ceil($totalCount / $perPage);

        return $this->renderAdminView('admin/resume_bank/index', [
            'resumes'    => $resumes,
            'search'     => $search,
            'status'     => $status,
            'perPage'    => $perPage,
            'page'       => $page,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'title'      => 'General Resume Bank — TalentHub Admin',
        ], 'resume_bank');
    }

    public function updateStatus($id)
    {
        if ($redirect = $this->requirePermission('candidates.manage')) {
            return $redirect;
        }

        $resumeModel = new GeneralResumeSubmissionModel();
        $record = $resumeModel->find($id);

        if (!$record) {
            session()->setFlashdata('error', 'Resume bank submission not found.');
            return redirect()->to(base_url('admin/resume-bank'));
        }

        $rawStatus = strtoupper(trim($this->request->getPost('status') ?? ''));
        $validStatuses = ['NEW', 'REVIEWED', 'CONTACTED', 'ARCHIVED'];

        if (in_array($rawStatus, $validStatuses)) {
            $oldStatus = $record['status'] ?? 'NEW';
            $resumeModel->update($id, ['status' => $rawStatus]);

            // Audit Log
            $auditModel = new AuditLogModel();
            $auditModel->log('RESUME_STATUS_CHANGE', 'general_resume_submission', (int) $id, [
                'status' => $oldStatus
            ], [
                'status' => $rawStatus
            ]);

            session()->setFlashdata('success', 'Resume bank entry status updated to ' . ucfirst(strtolower($rawStatus)) . '.');
        } else {
            session()->setFlashdata('error', 'Invalid status selected.');
        }

        return redirect()->to(base_url('admin/resume-bank'));
    }

    public function downloadResume($id)
    {
        if ($redirect = $this->requirePermission('resumes.download')) {
            return $redirect;
        }

        $resumeModel = new GeneralResumeSubmissionModel();
        $resume = $resumeModel->find((int) $id);

        if (!$resume) {
            session()->setFlashdata('error', 'Resume bank record not found.');
            return redirect()->to(base_url('admin/resume-bank'));
        }

        // 1. Vault document lookup
        if (!empty($resume['document_id'])) {
            try {
                $vault = new DocumentVaultService();
                $doc = $vault->getDocument((int) $resume['document_id']);
                if ($doc && !empty($doc['full_path']) && file_exists($doc['full_path'])) {
                    $auditModel = new \App\Models\AuditLogModel();
                    $auditModel->log('RESUME_DOWNLOAD', 'general_resume_submission', (int) $id, null, ['document_id' => $resume['document_id']]);

                    return $this->response->download($doc['full_path'], null)->setFileName($doc['original_name'] ?? 'Candidate_CV.pdf');
                }
            } catch (\Throwable $e) {
                log_message('error', 'Download resume vault error: ' . $e->getMessage());
            }
        }

        session()->setFlashdata('error', 'Resume document cannot be located in the secure repository.');
        return redirect()->to(base_url('admin/resume-bank'));
    }
}
