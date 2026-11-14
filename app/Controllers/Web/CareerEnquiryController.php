<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\CandidateModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Models\WebsitePageModel;
use App\Services\DocumentVaultService;
use App\Services\OutboxService;

class CareerEnquiryController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('career-enquiry');

        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : 'Submit Resume | Confidential Pune Talent Vault | TalentHub';
        $metaDesc = !empty($page['meta_description']) ? $page['meta_description'] : 'Submit your resume and career preferences to TalentHub Consultant\'s confidential talent pool in Pune with zero candidate fees.';

        if ($this->wantsMarkdown()) {
            $md = "# Submit Resume & Career Preferences | TalentHub Consultant Pune\n\n"
                . "> Join TalentHub Consultant's confidential Pune candidate network with 100% free placement services.\n\n"
                . "## Candidate Submission Guidelines\n"
                . "- **Accepted Formats**: PDF, DOC, DOCX (Up to 5MB)\n"
                . "- **Confidentiality**: Your details are never forwarded to corporate clients without your explicit briefing and permission.\n"
                . "- **Direct Submission**: Apply online at " . base_url('career-enquiry') . " or email " . ($this->siteSettings['hiring_email'] ?? 'hiring@talenthubconsultant.com') . "\n\n"
                . "## Active Opportunities\n"
                . "Browse active job openings in Pune: " . base_url('jobs') . "\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/career_enquiry', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('career_enquiry'),
        ]);
    }

    public function submit()
    {
        // 1. Anti-Bot & Form Security Verification (Honeypot, Signed Timestamp, CAPTCHA)
        $formSec = new \App\Services\FormSecurityService();
        $secCheck = $formSec->validateSubmission($this->request->getPost(), 'career_enquiry', $this->request->getIPAddress());
        if (!$secCheck['valid']) {
            return $this->respondError($secCheck['message'], 422, $secCheck['code']);
        }

        // 2. Affirmative Consent Verification
        if (!$this->request->getPost('consent')) {
            return $this->respondError('You must affirmatively consent to the confidential retention of your CV under our privacy policy.', 422, 'CONSENT_REQUIRED');
        }

        $fullName = trim((string) ($this->request->getPost('full_name') ?: $this->request->getPost('name')));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $phone = trim((string) $this->request->getPost('phone'));
        $desiredRole = trim((string) ($this->request->getPost('desired_role') ?: $this->request->getPost('preferred_role') ?: 'General Mandate'));

        if (empty($fullName) || strlen($fullName) < 2) {
            return $this->respondError('Full name is required (minimum 2 characters).', 422, 'VALIDATION_FAILED');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('A valid email address is required.', 422, 'VALIDATION_FAILED');
        }

        // 3. Phone Number Validation and Normalization
        $normalizedPhone = normalize_phone_number($phone);
        if (!$normalizedPhone) {
            return $this->respondError('Please provide a valid Indian (e.g. +91 98230 45678) or international mobile number.', 422, 'INVALID_PHONE');
        }

        // 4. Experience Validation
        $rawExp = $this->request->getPost('experience_years');
        $expYears = validate_experience_years($rawExp);
        if ($rawExp !== null && $rawExp !== '' && $expYears === false) {
            return $this->respondError('Total experience must be a valid non-negative number between 0 and 50 years.', 422, 'INVALID_EXPERIENCE');
        }

        // 5. Resume File Upload
        $file = $this->request->getFile('resume');
        if (!$file || !$file->isValid()) {
            return $this->respondError('A valid resume file (.pdf, .doc, .docx up to 5MB) is required.', 422, 'VALIDATION_FAILED');
        }

        $vault = new DocumentVaultService();
        $candModel = new CandidateModel();
        $genModel = new GeneralResumeSubmissionModel();
        $outbox = new OutboxService();

        try {
            // 6. Upsert Candidate Profile with Consent Record
            $candId = $candModel->upsertCandidate([
                'full_name'              => $fullName,
                'email'                  => $email,
                'phone'                  => $normalizedPhone,
                'current_location'       => trim($this->request->getPost('location') ?? $this->request->getPost('current_location') ?? ''),
                'total_experience_years' => $expYears,
                'consent_received'       => 1,
                'consent_timestamp'      => date('Y-m-d H:i:s'),
                'consent_policy_version' => 'v1.0-DPDP-2026',
            ]);

            $vaultResult = $vault->storeResume($file, $candId);

            // 7. Server-Side Idempotency Check
            $idempotencyKey = 'gen_' . md5($email) . '_' . date('Ymd');
            $existing = $genModel->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondJson([
                        'submission_id' => $existing['id'],
                        'idempotent'    => true,
                    ], 'Your resume has already been received today and is catalogued in our private talent repository.');
                }
                return redirect()->to(base_url('career-enquiry'))
                                 ->with('success', 'Your career preferences have already been saved in our Pune talent bank.');
            }

            $subId = $genModel->insert([
                'candidate_id'       => $candId,
                'document_id'        => $vaultResult['document_id'],
                'desired_role'       => $desiredRole,
                'preferred_location' => trim((string) ($this->request->getPost('preferred_location') ?: 'Pune')),
                'notice_period'      => trim((string) ($this->request->getPost('notice_period') ?: 'Immediate')),
                'notes'              => substr(trim((string) ($this->request->getPost('notes') ?: '')), 0, 1000),
                'status'             => 'NEW',
                'idempotency_key'    => $idempotencyKey,
            ]);

            // Queue candidate acknowledgment email
            $outbox->queueTemplate('app_talent_pool_ack', $email, $fullName, [
                'candidate_name'     => $fullName,
                'desired_role'       => $desiredRole,
                'preferred_location' => trim((string) ($this->request->getPost('preferred_location') ?: 'Pune')),
            ]);

            // Queue hiring desk alert
            $hiringDeskEmail = $this->siteSettings['hiring_email'] ?? 'hiring@talenthubconsultant.com';
            $outbox->queueTemplate('admin_app_alert', $hiringDeskEmail, 'TalentHub Hiring Desk', [
                'candidate_name'   => $fullName,
                'candidate_email'  => $email,
                'email'            => $email,
                'candidate_phone'  => $normalizedPhone,
                'phone'            => $normalizedPhone,
                'job_title'        => 'Talent Pool: ' . $desiredRole,
                'experience_years' => ($expYears !== null) ? ($expYears . ' Years') : 'Not specified',
                'notice_period'    => trim((string) ($this->request->getPost('notice_period') ?: 'Immediate')),
            ]);

            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondJson([
                    'submission_id' => $subId,
                    'redirect_url' => base_url('thank-you?type=career&ref=' . $subId),
                ], 'Your resume has been catalogued in our private talent repository. Our recruiters will contact you when matching opportunities open.');
            }

            return redirect()->to(base_url('thank-you?type=career&ref=' . $subId))
                             ->with('success', 'Your career preferences have been saved in our Pune talent bank. We will contact you when matching vacancies arise.');
        } catch (\Throwable $e) {
            log_message('error', 'CareerEnquiryController::submit error: ' . $e->getMessage());
            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondError('We could not complete your request because of a problem on our side. Your information was not lost. Please try again in a few minutes or contact us directly.', 500);
            }
            return redirect()->to(base_url('submission-failed?type=career&reason=server_error'));
        }
    }
}
