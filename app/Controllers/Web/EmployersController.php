<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\EmployerEnquiryModel;
use App\Models\WebsitePageModel;
use App\Services\OutboxService;

class EmployersController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('employers');

        $metaTitle = $page['meta_title'] ?? 'Employer Hiring Services & Staffing Partner | TalentHub Pune';
        $metaDesc = $page['meta_description'] ?? 'Partner with TalentHub Consultant to close urgent technical headcount and confidential leadership mandates in Pune within 7 days.';

        if ($this->wantsMarkdown()) {
            $md = "# For Employers — Corporate Staffing & Executive Search | TalentHub Pune\n\n"
                . "> {$metaDesc}\n\n"
                . "## Why Pune Employers Partner With Us\n"
                . "1. **Rapid Technical Screening**: Verified notice period feasibility, salary expectations, and practical engineering skills.\n"
                . "2. **Zero Upfront Retainer**: 100% contingency placement fee payable strictly after successful candidate onboarding.\n"
                . "3. **90-Day Replacement Guarantee**: Full protection for your hiring investments.\n"
                . "4. **Coverage**: Hinjawadi IT Park, Kharadi, Magarpatta, Chakan & Bhosari MIDC, and PCMC.\n\n"
                . "## Register a Hiring Mandate\n"
                . "Submit requirements via web: " . base_url('employers') . "\n"
                . "Direct Corporate Recruitment Desk: " . ($this->siteSettings['contact_phone'] ?? '+91 72761 46573') . " | " . ($this->siteSettings['contact_email'] ?? 'hr@talenthubconsultant.com') . "\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/employers', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('employer_enquiry'),
        ]);
    }

    public function submitEnquiry()
    {
        // 1. Anti-Bot & Form Security Verification (Honeypot, Signed Timestamp, CAPTCHA)
        $formSec = new \App\Services\FormSecurityService();
        $secCheck = $formSec->validateSubmission($this->request->getPost(), 'employer_enquiry', $this->request->getIPAddress());
        if (!$secCheck['valid']) {
            if ($this->request->isAJAX()) {
                return $this->respondError($secCheck['message'], 422, $secCheck['code']);
            }
            return redirect()->back()->withInput()->with('error', $secCheck['message']);
        }

        // 2. Affirmative Consent Verification
        if (!$this->request->getPost('consent')) {
            $msg = 'Please acknowledge and agree to our corporate mandate terms & privacy policy.';
            if ($this->request->isAJAX()) {
                return $this->respondError($msg, 422, 'CONSENT_REQUIRED');
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $rules = [
            'company_name'   => 'required|min_length[2]|max_length[150]',
            'contact_person' => 'required|min_length[2]|max_length[100]',
            'email'          => 'required|valid_email|max_length[150]',
            'phone'          => 'required|min_length[8]|max_length[25]',
            'required_roles' => 'required|min_length[3]|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            if ($this->request->isAJAX()) {
                return $this->respondError('Validation failed', 422, 'VALIDATION_FAILED', $this->validator->getErrors());
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // 3. Phone Number Validation and Normalization
        $rawPhone = (string) $this->request->getPost('phone');
        $normalizedPhone = normalize_phone_number($rawPhone);
        if (!$normalizedPhone) {
            $msg = 'Please enter a valid official phone number (e.g. +91 98220 12345).';
            if ($this->request->isAJAX()) {
                return $this->respondError($msg, 422, 'INVALID_PHONE');
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $empEnqModel = new EmployerEnquiryModel();
        $outbox = new OutboxService();

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $roles = trim((string) $this->request->getPost('required_roles'));
        $company = trim((string) $this->request->getPost('company_name'));
        $contactPerson = trim((string) $this->request->getPost('contact_person'));

        // 4. Server-Side Idempotency
        $idempotencyKey = 'emp_' . md5($email . '_' . substr($roles, 0, 30) . '_' . date('YmdHi'));
        $existing = $empEnqModel->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            $msg = 'Thank you! Your hiring mandate has already been registered and queued for partner consultation.';
            if ($this->request->isAJAX()) {
                return $this->respondJson(['enquiry_id' => $existing['id'], 'idempotent' => true], $msg);
            }
            return redirect()->to(base_url('thank-you?type=employer&ref=' . $existing['id']))->with('success', $msg);
        }

        $rawUrgency = trim((string)($this->request->getPost('urgency') ?? 'Immediate'));
        $urgencyMap = [
            'immediate'   => 'IMMEDIATE',
            '15 days'     => '15_DAYS',
            '30 days'     => '30_DAYS',
            '60 days'     => 'EXPLORATORY',
            'pipeline'    => 'EXPLORATORY',
            'exploratory' => 'EXPLORATORY',
        ];
        $normalizedUrgency = $urgencyMap[strtolower($rawUrgency)] ?? 'IMMEDIATE';

        try {
            $enqId = $empEnqModel->insert([
                'company_name'        => $company,
                'contact_person'      => $contactPerson,
                'email'               => $email,
                'phone'               => $normalizedPhone,
                'required_roles'      => $roles,
                'number_of_positions' => (int) ($this->request->getPost('number_of_positions') ?: 1),
                'location'            => trim($this->request->getPost('location') ?? 'Pune'),
                'experience_bracket'  => trim($this->request->getPost('experience_bracket') ?? ''),
                'urgency'             => $normalizedUrgency,
                'notes'               => substr(trim($this->request->getPost('notes') ?? ''), 0, 1000),
                'status'              => 'NEW',
                'idempotency_key'     => $idempotencyKey,
                'consent_received'    => 1,
                'consent_timestamp'   => date('Y-m-d H:i:s'),
            ]);

            // Queue B2B acknowledgment email to employer
            $outbox->queueTemplate('employer_enquiry_ack', $email, $contactPerson, [
                'contact_person' => $contactPerson,
                'company_name'   => $company,
                'required_roles' => $roles,
            ]);

            // Queue admin alert to corporate/customer recruitment desk
            $adminEmail = $this->siteSettings['contact_email'] ?? 'hr@talenthubconsultant.com';
            $outbox->queueTemplate('admin_employer_alert', $adminEmail, 'TalentHub Corporate Desk', [
                'company_name'   => $company,
                'contact_person' => $contactPerson,
                'email'          => $email,
                'phone'          => $normalizedPhone,
                'required_roles' => $roles,
                'urgency'        => $normalizedUrgency,
                'notes'          => substr(trim($this->request->getPost('notes') ?? ''), 0, 1000),
            ]);

            if ($this->request->isAJAX()) {
                return $this->respondJson([
                    'enquiry_id' => $enqId,
                    'redirect_url' => base_url('thank-you?type=employer&ref=' . $enqId),
                ], 'Thank you! Your hiring mandate has been received. A senior recruitment consultant will contact you within 24 business hours.');
            }

            return redirect()->to(base_url('thank-you?type=employer&ref=' . $enqId))
                             ->with('success', 'Your hiring requirements have been received. A senior recruitment partner in Pune will connect with you within 24 hours.');
        } catch (\Throwable $e) {
            log_message('error', 'EmployersController::submitEnquiry error: ' . $e->getMessage());
            if ($this->request->isAJAX()) {
                return $this->respondError('We could not complete your request because of a problem on our side. Your information was not lost. Please try again in a few minutes or contact us directly.', 500);
            }
            return redirect()->to(base_url('submission-failed?type=employer&reason=server_error'));
        }
    }
}

