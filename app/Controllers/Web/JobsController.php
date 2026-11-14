<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\CandidateModel;
use App\Models\JobCategoryModel;
use App\Models\JobModel;
use App\Models\WebsitePageModel;
use App\Services\DocumentVaultService;
use App\Services\OutboxService;
use CodeIgniter\Exceptions\PageNotFoundException;

class JobsController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $pageRecord = $pageModel->getPublishedBySlug('jobs');

        $jobModel = new JobModel();
        $catModel = new JobCategoryModel();

        $page = (int) ($this->request->getGet('page') ?? 1);
        $page = max(1, $page);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'    => trim((string) $this->request->getGet('search')),
            'category'  => trim((string) $this->request->getGet('category')),
            'location'  => trim((string) $this->request->getGet('location')),
            'job_type'  => trim((string) $this->request->getGet('job_type')),
            'work_mode' => trim((string) $this->request->getGet('work_mode')),
            'exp_min'   => trim((string) $this->request->getGet('exp_min')),
            'sort'      => trim((string) $this->request->getGet('sort')),
        ];

        $totalJobs = $jobModel->countPublishedJobs($filters);
        $jobs = $jobModel->getPublishedJobs($filters, $limit, $offset);
        $categories = $catModel->getActiveCategories();

        $totalPages = (int) ceil($totalJobs / $limit);

        $metaTitle = !empty($pageRecord['meta_title']) ? $pageRecord['meta_title'] : 'Current Job Vacancies in Pune | IT & Core Roles | TalentHub';
        $metaDesc = !empty($pageRecord['meta_description']) ? $pageRecord['meta_description'] : 'Explore active job vacancies across IT, Engineering, Automotive, and Manufacturing sectors in Hinjawadi, Kharadi, and Chakan with direct Pune corporate employers.';

        if ($this->wantsMarkdown()) {
            $md = "# Active Career Opportunities in Pune | TalentHub Consultant\n\n"
                . "> Verified corporate mandates across Hinjawadi, Kharadi, and Chakan corridors with zero candidate placement fees.\n\n"
                . "Total Active Vacancies: {$totalJobs}\n\n";

            foreach ($jobs as $job) {
                $exp = str_replace(['???', '??'], '–', $job['experience_label'] ?? ($job['experience_min'] . '–' . $job['experience_max'] . ' yrs'));
                $salary = (!empty($job['is_salary_public']) && !empty($job['salary_min']))
                    ? ('₹' . number_format((float) $job['salary_min']) . ' – ₹' . number_format((float) $job['salary_max']))
                    : 'Competitive / Best in Industry';

                $md .= "### [" . esc($job['title']) . "](" . base_url('jobs/' . $job['slug']) . ")\n"
                    . "- **Employer**: " . (!empty($job['employer_name']) ? esc($job['employer_name']) : 'Confidential Corporate Partner') . "\n"
                    . "- **Location**: " . esc($job['location'] ?? 'Pune, Maharashtra') . "\n"
                    . "- **Type**: " . ($job['job_type'] === 'FT' ? 'Full-Time Permanent' : 'Contract') . " | **Mode**: " . esc($job['work_mode'] ?? 'Onsite') . "\n"
                    . "- **Experience**: {$exp}\n"
                    . "- **Compensation**: {$salary}\n"
                    . "- **Details & Direct Apply**: " . base_url('jobs/' . $job['slug']) . "\n\n";
            }

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/jobs/index', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $pageRecord,
            'jobs'             => $jobs,
            'categories'       => $categories,
            'filters'          => $filters,
            'totalJobs'        => $totalJobs,
            'currentPage'      => $page,
            'totalPages'       => $totalPages,
        ]);
    }

    public function show(string $slug): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $jobModel = new JobModel();
        $job = $jobModel->getPublishedBySlug($slug);

        if (!$job) {
            throw PageNotFoundException::forPageNotFound("Job opportunity '{$slug}' not found or is no longer accepting applications.");
        }

        // Fetch 3 related jobs from same category
        $relatedJobs = $jobModel->getPublishedJobs([
            'category' => $job['category_slug'] ?? '',
        ], 4, 0);
        // Exclude current job from related
        $relatedJobs = array_filter($relatedJobs, fn($j) => $j['id'] != $job['id']);
        $relatedJobs = array_slice($relatedJobs, 0, 3);

        $title = !empty($job['meta_title']) ? $job['meta_title'] : ($job['title'] . ' in ' . ($job['location'] ?? 'Pune') . ' | TalentHub');
        $desc = !empty($job['meta_description']) ? $job['meta_description'] : substr(strip_tags($job['description']), 0, 158);
        $keywords = !empty($job['meta_keywords']) ? $job['meta_keywords'] : ($job['title'] . ', ' . ($job['location'] ?? 'Pune') . ' jobs, ' . ($job['skills'] ?? '') . ', recruitment Pune');

        if ($this->wantsMarkdown()) {
            $cleanExp = str_replace(['???', '??'], '–', $job['experience_label'] ?? ($job['experience_min'] . '–' . $job['experience_max'] . ' years'));
            $md = "# {$job['title']}\n\n"
                . "- **Location**: " . ($job['location'] ?? 'Pune, Maharashtra') . "\n"
                . "- **Employment Type**: " . ($job['job_type'] === 'FT' ? 'Full-Time Permanent' : 'Contract') . "\n"
                . "- **Experience**: " . $cleanExp . "\n"
                . "- **Work Mode**: " . ($job['work_mode'] ?? 'On-Site') . "\n"
                . "- **Mandate Ref**: " . $job['slug'] . "\n\n"
                . "## Position Overview\n"
                . strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $job['description'])) . "\n\n";

            if (!empty($job['requirements'])) {
                $md .= "## Requirements & Skills\n"
                    . strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $job['requirements'])) . "\n\n";
            }

            $md .= "## How to Apply\n"
                . "Apply directly at " . base_url('jobs/' . $job['slug']) . " or email your resume to " . ($this->siteSettings['hiring_email'] ?? 'hiring@talenthubconsultant.com') . " referencing {$job['slug']}.\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/jobs/show', [
            'meta_title'       => $title,
            'meta_description' => $desc,
            'meta_keywords'    => $keywords,
            'og_type'          => 'article',
            'job'              => $job,
            'relatedJobs'      => $relatedJobs,
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('job_apply'),
        ]);
    }

    /**
     * Handle job application form submission
     */
    public function submitApplication(string $slug = 'direct')
    {
        $jobModel = new JobModel();
        if ($slug === 'direct' || empty($slug)) {
            $jobId = (int) ($this->request->getPost('job_id') ?: 1);
            $job = $jobModel->find($jobId);
        } else {
            $job = $jobModel->getPublishedBySlug($slug);
        }

        if (!$job) {
            return $this->respondError('Job not found or inactive.', 404);
        }

        // 1. Anti-Bot & Form Security Verification (Honeypot, Signed Timestamp, CAPTCHA)
        $formSec = new \App\Services\FormSecurityService();
        $secCheck = $formSec->validateSubmission($this->request->getPost(), 'job_apply', $this->request->getIPAddress());
        if (!$secCheck['valid']) {
            return $this->respondError($secCheck['message'], 422, $secCheck['code']);
        }

        // 2. Affirmative Consent Verification
        if (!$this->request->getPost('consent')) {
            return $this->respondError('You must affirmatively consent to the processing of your resume under our privacy policy.', 422, 'CONSENT_REQUIRED');
        }

        $fullName = trim((string) ($this->request->getPost('full_name') ?: $this->request->getPost('candidate_name') ?: $this->request->getPost('name')));
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $phone = trim((string) $this->request->getPost('phone'));

        if (empty($fullName) || strlen($fullName) < 2) {
            return $this->respondError('Full name is required (minimum 2 characters).', 422, 'VALIDATION_FAILED');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('A valid email address is required.', 422, 'VALIDATION_FAILED');
        }

        // 3. Phone Number Validation and Normalization
        $normalizedPhone = normalize_phone_number($phone);
        if (!$normalizedPhone) {
            return $this->respondError('Please provide a valid Indian (e.g. +91 98765 43210) or international mobile number.', 422, 'INVALID_PHONE');
        }

        // 4. Experience Validation (Reject negative, bound to max 50)
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
        $appModel = new ApplicationModel();
        $outbox = new OutboxService();

        try {
            // 6. Upsert Candidate Profile with Consent Record
            $candId = $candModel->upsertCandidate([
                'full_name'              => $fullName,
                'email'                  => $email,
                'phone'                  => $normalizedPhone,
                'current_location'       => trim($this->request->getPost('location') ?? ''),
                'total_experience_years' => $expYears,
                'consent_received'       => 1,
                'consent_timestamp'      => date('Y-m-d H:i:s'),
                'consent_policy_version' => 'v1.0-DPDP-2026',
            ]);

            // 7. Securely validate & vault resume file
            $vaultResult = $vault->storeResume($file, $candId);

            // 7.1 Automated Skills Extraction (GAP-03: Resume Keyword Parsing)
            try {
                $ext = strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION));
                if (!empty($vaultResult['file_path'])) {
                    $extractedSkills = $vault->extractResumeKeywords($vaultResult['file_path'], $ext);
                    if (!empty($extractedSkills)) {
                        $candRec = $candModel->find($candId);
                        $existingSkills = !empty($candRec['primary_skills']) ? array_map('trim', explode(',', $candRec['primary_skills'])) : [];
                        $allSkills = array_unique(array_merge($existingSkills, $extractedSkills));
                        $candModel->update($candId, ['primary_skills' => implode(', ', array_slice($allSkills, 0, 15))]);
                    }
                }
            } catch (\Throwable $skillErr) {
                log_message('warning', 'JobsController: Resume skill extraction skipped: ' . $skillErr->getMessage());
            }

            // 8. Server-Side Idempotency Check
            $idempotencyKey = 'app_' . $job['id'] . '_' . md5($email) . '_' . date('Ymd');
            $existing = $appModel->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondJson([
                        'application_id' => $existing['id'],
                        'idempotent'     => true,
                    ], 'Thank you! Your application for ' . $job['title'] . ' has already been received today.');
                }
                return redirect()->to(base_url('jobs/' . $job['slug']))
                                 ->with('success', 'Your application for ' . $job['title'] . ' has already been submitted today.');
            }

            $utms = (array) (session()->get('landing_utm_params') ?? []);
            $sourceStr = !empty($utms['utm_source']) ? ('UTM:' . substr($utms['utm_source'], 0, 40)) : 'CAREER_PORTAL';

            $appId = $appModel->insert([
                'job_id'          => $job['id'],
                'candidate_id'    => $candId,
                'document_id'     => $vaultResult['document_id'],
                'cover_message'   => substr(trim($this->request->getPost('cover_message') ?? ''), 0, 1000),
                'status'          => 'APPLIED',
                'source'          => $sourceStr,
                'ip_address'      => $this->request->getIPAddress(),
                'idempotency_key' => $idempotencyKey,
            ]);

            // 9. Queue candidate acknowledgment email
            $outbox->queueTemplate('app_candidate_ack', $email, $fullName, [
                'candidate_name' => $fullName,
                'job_title'      => $job['title'],
                'job_slug'       => $job['slug'],
            ]);

            // 10. Queue admin application alert to hiring desk
            $adminEmail = $this->siteSettings['hiring_email'] ?? 'hiring@talenthubconsultant.com';
            $outbox->queueTemplate('admin_app_alert', $adminEmail, 'TalentHub Hiring Desk', [
                'candidate_name'   => $fullName,
                'candidate_email'  => $email,
                'email'            => $email,
                'candidate_phone'  => $normalizedPhone,
                'phone'            => $normalizedPhone,
                'job_title'        => $job['title'],
                'experience_years' => ($expYears !== null) ? ($expYears . ' Years') : 'Not specified',
            ]);

            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondJson([
                    'application_id' => $appId,
                    'redirect_url' => base_url('thank-you?type=application&ref=' . $appId . '&title=' . urlencode($job['title'])),
                ], 'Thank you! Your application for ' . $job['title'] . ' has been received. Our recruitment advisors in Pune will review your profile.');
            }

            return redirect()->to(base_url('thank-you?type=application&ref=' . $appId . '&title=' . urlencode($job['title'])))
                             ->with('success', 'Your application for ' . $job['title'] . ' has been submitted successfully! We will contact you soon.');
        } catch (\Throwable $e) {
            log_message('error', 'JobsController::submitApplication error: ' . $e->getMessage());
            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondError('We could not complete your request because of a problem on our side. Your information was not lost. Please try again in a few minutes or contact us directly.', 500);
            }
            return redirect()->to(base_url('submission-failed?type=application&reason=server_error'));
        }
    }
}
