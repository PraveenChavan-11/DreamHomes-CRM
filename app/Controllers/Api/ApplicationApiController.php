<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\CandidateModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Models\JobModel;
use App\Services\DocumentVaultService;
use App\Services\OutboxService;
use CodeIgniter\HTTP\ResponseInterface;

class ApplicationApiController extends BaseController
{
    private ApplicationModel $appModel;

    public function __construct()
    {
        $this->appModel = new ApplicationModel();
    }

    public function index(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'status' => $this->request->getGet('status'),
            'job_id' => $this->request->getGet('job_id'),
            'search' => trim((string) $this->request->getGet('search')),
        ];

        $total = $this->appModel->countApplicationsWithDetails($filters);
        $apps = $this->appModel->getApplicationsWithDetails($filters, $limit, $offset);

        return $this->respondJson($apps, 'Applications retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function show($id): ResponseInterface
    {
        $app = $this->appModel->select('applications.*, candidates.*, jobs.title as job_title, candidate_documents.original_name as resume_name')
                              ->join('candidates', 'candidates.id = applications.candidate_id', 'left')
                              ->join('jobs', 'jobs.id = applications.job_id', 'left')
                              ->join('candidate_documents', 'candidate_documents.id = applications.document_id', 'left')
                              ->find($id);

        if (!$app) {
            return $this->respondError('Application not found', 404);
        }

        return $this->respondJson($app);
    }

    public function updateStatus($id): ResponseInterface
    {
        $app = $this->appModel->find($id);
        if (!$app) {
            return $this->respondError('Application not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $rawStatus = strtoupper(trim((string)($input['status'] ?? '')));

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
            return $this->respondError('Invalid pipeline stage', 422);
        }

        $this->appModel->update($id, ['status' => $targetStatus]);

        // Record stage transition history
        $db = \Config\Database::connect();
        $db->table('application_status_history')->insert([
            'application_id' => $id,
            'from_status'    => $app['status'],
            'to_status'      => $targetStatus,
            'changed_by'     => session()->get('admin_user')['id'] ?? 1,
            'notes'          => $input['notes'] ?? 'Pipeline stage updated via admin portal',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        return $this->respondJson(['id' => $id, 'status' => $status], 'Application status updated');
    }

    public function addNote($id): ResponseInterface
    {
        $app = $this->appModel->find($id);
        if (!$app) {
            return $this->respondError('Application not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $note = trim((string) ($input['note'] ?? $input['recruiter_notes'] ?? ''));

        if (!$note) {
            return $this->respondError('Note content required', 422);
        }

        $existing = $app['recruiter_notes'] ?? '';
        $timestamp = date('d M Y H:i');
        $user = session()->get('admin_user')['username'] ?? 'Recruiter';
        $updatedNote = "[{$timestamp} by {$user}]: {$note}\n\n" . $existing;

        $this->appModel->update($id, ['recruiter_notes' => $updatedNote]);
        return $this->respondJson(['recruiter_notes' => $updatedNote], 'Note added');
    }

    public function streamResume($docId)
    {
        $vault = new DocumentVaultService();
        $doc = $vault->getDocument((int) $docId);

        if (!$doc) {
            return $this->respondError('Document not found or inaccessible in vault', 404);
        }

        $response = service('response');
        $response->setHeader('Content-Type', $doc['mime_type']);
        $response->setHeader('Content-Disposition', 'inline; filename="' . addslashes($doc['original_name']) . '"');
        $response->setHeader('Content-Length', (string) $doc['file_size']);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('Cache-Control', 'private, max-age=300');
        $response->setBody(file_get_contents($doc['full_path']));

        return $response;
    }

    public function resumeBank(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $genModel = new GeneralResumeSubmissionModel();
        $filters = [
            'status' => $this->request->getGet('status'),
            'search' => trim((string) $this->request->getGet('search')),
        ];

        $subs = $genModel->getSubmissionsWithDetails($filters, $limit, $offset);
        $total = $genModel->countAllResults();

        return $this->respondJson($subs, 'Resume bank records retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function updateResumeBankStatus($id): ResponseInterface
    {
        $genModel = new GeneralResumeSubmissionModel();
        $sub = $genModel->find($id);
        if (!$sub) {
            return $this->respondError('Submission not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $status = $input['status'] ?? 'REVIEWED';

        $genModel->update($id, ['status' => $status]);
        return $this->respondJson(['id' => $id, 'status' => $status], 'Resume bank status updated');
    }
}

