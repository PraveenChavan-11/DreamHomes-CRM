<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\ResumeBankModel;
use App\Models\WebsitePageModel;
use App\Services\DocumentVaultService;
use App\Services\OutboxService;
use Config\Database;

class CandidateComplianceController extends BaseController
{
    /**
     * Self-Service Application Status Tracker (GAP-06)
     */
    public function trackApplication()
    {
        $app = null;
        $searched = false;
        $email = trim((string) ($this->request->getVar('email') ?? ''));
        $ref = trim((string) ($this->request->getVar('ref') ?? ''));

        if (!empty($email) && !empty($ref)) {
            $searched = true;
            $appModel = new ApplicationModel();

            // Find application by ID or reference and email
            $cleanId = (int) preg_replace('/[^0-9]/', '', $ref);
            if ($cleanId > 0) {
                $found = $appModel->select('candidate_applications.*, job_postings.title as job_title, job_postings.slug as job_slug, job_postings.location as job_location')
                                  ->join('job_postings', 'job_postings.id = candidate_applications.job_id', 'left')
                                  ->where('candidate_applications.id', $cleanId)
                                  ->where('candidate_applications.candidate_email', strtolower($email))
                                  ->first();
                if ($found) {
                    $app = $found;
                }
            }
        }

        $pageModel = new WebsitePageModel();
        $pageRecord = $pageModel->getPublishedBySlug('application-tracker');

        $metaTitle = !empty($pageRecord['meta_title']) ? $pageRecord['meta_title'] : 'Track Job Application Status | Candidate Portal | TalentHub';
        $metaDesc = !empty($pageRecord['meta_description']) ? $pageRecord['meta_description'] : 'Check real-time application review stages, screening updates, and mandate milestones for your TalentHub job applications.';

        return $this->renderView('public/compliance/tracker', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $pageRecord,
            'app'              => $app,
            'searched'         => $searched,
            'email'            => $email,
            'ref'              => $ref,
        ]);
    }

    /**
     * Request Resume / Profile Erasure under DPDP Act 2023 (GAP-07)
     */
    public function requestCvDeletion()
    {
        $session = session();

        if ($this->request->getMethod() === 'post' || $this->request->is('post')) {
            $email = strtolower(trim((string) ($this->request->getPost('email') ?? '')));
            $consent = (bool) ($this->request->getPost('consent') ?? false);

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'Please provide a valid email address.');
            }

            if (!$consent) {
                return redirect()->back()->withInput()->with('error', 'Please confirm your consent for permanent data erasure.');
            }

            $appModel = new ApplicationModel();
            $apps = $appModel->where('candidate_email', $email)->findAll();

            $bankModel = new ResumeBankModel();
            $bankEntries = $bankModel->where('email', $email)->findAll();

            $totalRecords = count($apps) + count($bankEntries);

            if ($totalRecords === 0) {
                return redirect()->back()->with('error', 'No candidate applications or CV vault records were found matching that email address.');
            }

            // Generate secure deletion verification token
            $token = bin2hex(random_bytes(24));
            $session->set("dpdp_deletion_{$token}", [
                'email'      => $email,
                'expires_at' => time() + 86400, // 24 hours
            ]);

            // Dispatch confirmation link
            try {
                $confirmUrl = base_url("privacy/confirm-cv-deletion?token={$token}&email=" . urlencode($email));
                $outbox = new OutboxService();
                $outbox->enqueue(
                    $email,
                    'Confirm Permanent Resume & Profile Erasure Request | TalentHub',
                    view('emails/layout', [
                        'subject' => 'Confirm Permanent Resume Erasure Request',
                        'content' => '
                            <div style="text-align: center; margin-bottom: 20px;">
                                <span class="badge" style="background:#fee2e2;color:#b91c1c;font-size:11px;padding:4px 12px;border-radius:9999px;font-weight:bold;">DPDP Data Erasure Request</span>
                                <h2 style="color:#0f172a;margin:12px 0 6px 0;font-size:20px;font-weight:800;">Confirm Permanent CV &amp; Profile Erasure</h2>
                                <p style="color:#64748b;font-size:13px;margin:0;">A request was submitted to permanently delete all resumes and application records associated with <strong>' . esc($email) . '</strong>.</p>
                            </div>
                            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin:20px 0;font-size:12px;color:#991b1b;">
                                <strong>Warning:</strong> This action cannot be reversed. All uploaded PDF/DOCX resumes will be permanently destroyed from our private vault, and application profiles will be completely anonymized.
                            </div>
                            <div style="text-align:center;margin:28px 0;">
                                <a href="' . esc($confirmUrl) . '" style="display:inline-block;padding:12px 28px;background:#dc2626;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:10px;font-size:13px;">Confirm Permanent Erasure &rarr;</a>
                            </div>
                            <p style="font-size:11px;color:#94a3b8;text-align:center;">This link is valid for 24 hours. If you did not request this, you may safely ignore this message.</p>
                        ',
                    ]),
                    'dpdp_compliance',
                    null
                );
                $outbox->processPending(1);
            } catch (\Throwable $e) {
                log_message('error', 'DPDP deletion email dispatch error: ' . $e->getMessage());
            }

            return redirect()->back()->with('success', 'A secure confirmation link has been dispatched to your email address. Please click the link within 24 hours to execute permanent erasure.');
        }

        $pageModel = new WebsitePageModel();
        $pageRecord = $pageModel->getPublishedBySlug('request-cv-deletion');

        $metaTitle = !empty($pageRecord['meta_title']) ? $pageRecord['meta_title'] : 'Request CV Deletion | DPDP Right to Erasure | TalentHub';
        $metaDesc = !empty($pageRecord['meta_description']) ? $pageRecord['meta_description'] : 'Exercise your statutory Right to Erasure under the Digital Personal Data Protection Act 2023 to permanently delete your resume and profile from TalentHub.';

        return $this->renderView('public/compliance/request_deletion', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $pageRecord,
        ]);
    }

    /**
     * Confirm & Execute CV Deletion
     */
    public function confirmCvDeletion()
    {
        $token = trim((string) ($this->request->getGet('token') ?? ''));
        $email = strtolower(trim((string) ($this->request->getGet('email') ?? '')));
        $session = session();

        $saved = $session->get("dpdp_deletion_{$token}");

        if (!$saved || ($saved['email'] ?? '') !== $email || ($saved['expires_at'] ?? 0) < time()) {
            return $this->renderView('public/status/failed', [
                'meta_title' => 'Deletion Link Expired or Invalid — TalentHub Consultant',
                'error_title' => 'Invalid or Expired Erasure Link',
                'error_message' => 'The data erasure confirmation link is invalid or has expired. Please submit a fresh request.',
                'back_url' => base_url('privacy/request-cv-deletion'),
            ]);
        }

        // Execute permanent file deletion & data anonymization
        $vault = new DocumentVaultService();
        $db = Database::connect();
        $purgedFiles = 0;

        // 1. Applications
        $appModel = new ApplicationModel();
        $apps = $appModel->where('candidate_email', $email)->findAll();
        foreach ($apps as $app) {
            if (!empty($app['document_id'])) {
                try {
                    $doc = $vault->getDocument((int) $app['document_id']);
                    if ($doc && !empty($doc['full_path']) && file_exists($doc['full_path'])) {
                        @unlink($doc['full_path']);
                        $purgedFiles++;
                    }
                    $db->table('documents')->where('id', (int) $app['document_id'])->delete();
                } catch (\Throwable $e) {}
            }

            $appModel->update($app['id'], [
                'candidate_name'  => '[Erased per DPDP Act]',
                'candidate_email' => 'erased_' . $app['id'] . '@dpdp.invalid',
                'candidate_phone' => null,
                'cover_message'   => null,
                'resume_path'     => null,
                'document_id'     => null,
                'status'          => 'WITHDRAWN',
            ]);
        }

        // 2. Resume Bank
        $bankModel = new ResumeBankModel();
        $bankEntries = $bankModel->where('email', $email)->findAll();
        foreach ($bankEntries as $b) {
            if (!empty($b['resume_path']) && file_exists(WRITEPATH . $b['resume_path'])) {
                @unlink(WRITEPATH . $b['resume_path']);
                $purgedFiles++;
            }
            $bankModel->delete($b['id']);
        }

        // Log regulatory entry in data_retention_logs
        try {
            $db->table('data_retention_logs')->insert([
                'policy_name'    => 'DPDP_RIGHT_TO_ERASURE',
                'records_purged' => count($apps) + count($bankEntries),
                'details'        => "Candidate self-service erasure completed for {$email}. Purged {$purgedFiles} vault file(s).",
                'executed_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        // Invalidate session token
        $session->remove("dpdp_deletion_{$token}");

        return $this->renderView('public/compliance/deletion_success', [
            'meta_title'    => 'Data Erasure Complete | TalentHub Consultant',
            'email'         => $email,
            'purged_files'  => $purgedFiles,
            'total_records' => count($apps) + count($bankEntries),
        ]);
    }
}

