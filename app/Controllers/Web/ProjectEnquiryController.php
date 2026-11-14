<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\FormSecurityService;
use App\Services\ItLeadService;
use App\Services\OutboxService;

/**
 * Public IT / technology project enquiry (feeds the IT Services CRM).
 * GET  /project-enquiry               form
 * POST /project-enquiry/submit        web form (CSRF protected)
 * POST /api/v1/service-enquiries      same handler, JSON clients (honeypot + rate limit)
 */
class ProjectEnquiryController extends BaseController
{
    public function index()
    {
        $service  = trim((string) $this->request->getGet('service'));
        $category = trim((string) $this->request->getGet('category'));
        if (!isset(ItLeadService::CATEGORIES[$category])) {
            $category = $this->categoryFromServiceSlug($service) ?? '';
        }
        return $this->renderView('public/project_enquiry', [
            'meta_title'       => 'Discuss Your Technology Project | TalentHub',
            'meta_description' => 'Tell us about your software, web, mobile, ERP/HRMS, QA or digital transformation project. TalentHub works with organisations across India.',
            'category'         => $category,
            'service'          => preg_replace('/[^a-z0-9\-]/', '', strtolower($service)),
            'utm'              => $this->utmFromRequest(),
            'formProtection'   => (new FormSecurityService())->renderProtectionFields('project_enquiry'),
        ]);
    }

    public function submit()
    {
        $post   = $this->request->getPost() ?: ($this->request->getJSON(true) ?: []);
        $isJson = !$this->request->getPost() && !empty($post);
        $wantsJson = $isJson || $this->request->isAJAX() || str_starts_with(ltrim($this->request->getUri()->getPath(), '/'), 'api/');

        $fail = function (string $msg, int $status, string $code, array $errors = []) use ($wantsJson) {
            if ($wantsJson) {
                return $this->respondError($msg, $status, $code, $errors);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        };

        $sec = (new FormSecurityService())->validateSubmission($post, 'project_enquiry', $this->request->getIPAddress());
        if (!$sec['valid']) {
            return $fail($sec['message'], 422, $sec['code']);
        }
        if (empty($post['consent'])) {
            return $fail('Please consent to being contacted under the Privacy Policy.', 422, 'CONSENT_REQUIRED');
        }

        $name    = trim((string) ($post['client_name'] ?? ''));
        $email   = strtolower(trim((string) ($post['email'] ?? '')));
        $desc    = trim((string) ($post['project_description'] ?? ''));
        $phone   = normalize_phone_number((string) ($post['phone'] ?? ''));
        $errors  = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors['client_name'] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!$phone) {
            $errors['phone'] = 'Please enter a valid Indian or international phone number.';
        }
        if (mb_strlen($desc) < 10 || mb_strlen($desc) > 4000) {
            $errors['project_description'] = 'Please describe your project (10–4000 characters).';
        }
        if ($errors) {
            return $fail('Please correct the highlighted fields.', 422, 'VALIDATION_FAILED', $errors);
        }

        $pick = static fn (string $v, array $allowed, string $default = '') => in_array($v, $allowed, true) ? $v : $default;
        $category = $pick((string) ($post['service_category'] ?? ''), array_keys(ItLeadService::CATEGORIES), 'other');
        $utm = $this->utmFromArray($post);
        $src = (string) ($post['source_page'] ?? '');
        $src = $src !== '' ? mb_substr((string) parse_url($src, PHP_URL_PATH), 0, 255) : null;

        try {
            $res = (new ItLeadService())->create([
                'client_name'         => $name,
                'company'             => mb_substr(trim((string) ($post['company'] ?? '')), 0, 150),
                'email'               => $email,
                'phone'               => $phone,
                'location'            => mb_substr(trim((string) ($post['location'] ?? '')), 0, 120),
                'service_category'    => $category,
                'service_slug'        => preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['service_slug'] ?? ''))),
                'project_description' => $desc,
                'budget_range'        => $pick((string) ($post['budget_range'] ?? ''), ItLeadService::BUDGETS),
                'timeline'            => $pick((string) ($post['timeline'] ?? ''), ItLeadService::TIMELINES),
                'preferred_contact'   => $pick((string) ($post['preferred_contact'] ?? ''), ItLeadService::CONTACTS),
                'source_page'         => $src,
                'ip_address'          => $this->request->getIPAddress(),
            ] + $utm);
        } catch (\Throwable $e) {
            log_message('error', 'ProjectEnquiry save failed: ' . $e->getMessage());
            return $fail('We could not save your enquiry right now. Please try again shortly or contact us directly.', 500, 'SAVE_FAILED');
        }

        $crmRecipient = null;
        if (!$res['idempotent']) {
            // CRM notification (never blocks the enquiry; failures are logged inside the service).
            $crm = new \App\Services\CrmNotificationService();
            $crm->notify('website_enquiry', (int) $res['id']);
            $crmRecipient = strtolower($crm->recipient());
        }
        if (!$res['idempotent']) {
            try {
                $adminEmail = $this->siteSettings['contact_email'] ?? 'hr@talenthubconsultant.com';
                if ($crmRecipient !== null && strtolower((string) $adminEmail) === $crmRecipient) {
                    throw new \RuntimeException('legacy admin alert skipped: same recipient as the CRM notification');
                }
                (new OutboxService())->queueTemplate('admin_contact_alert', $adminEmail, 'TalentHub Desk', [
                    'sender_name'     => $name,
                    'sender_email'    => $email,
                    'sender_phone'    => $phone,
                    'enquiry_subject' => 'IT Project Enquiry: ' . ItLeadService::CATEGORIES[$category],
                    'message'         => $desc,
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'ProjectEnquiry admin alert failed: ' . $e->getMessage());
            }
        }

        $msg = 'Thank you. Your project enquiry has been received and our team will contact you.';
        if ($wantsJson) {
            return $this->respondJson(['id' => $res['id'], 'idempotent' => $res['idempotent']], $msg, 201);
        }
        return redirect()->to(base_url('thank-you?type=enquiry&ref=' . $res['id']))->with('success', $msg);
    }

    private function categoryFromServiceSlug(string $slug): ?string
    {
        if ($slug === '') {
            return null;
        }
        try {
            $row = \Config\Database::connect()->table('services s')->select('c.slug AS cat')
                ->join('service_categories c', 'c.id = s.category_id')->where('s.slug', $slug)->get()->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        return match ($row['cat'] ?? '') {
            'it-services'            => 'software-development',
            'erp-hrms'               => 'erp-hrms',
            'qa-testing'             => 'qa-testing',
            'digital-transformation' => 'digital-transformation',
            'digital-marketing'      => 'seo-digital-marketing',
            default                  => null,
        };
    }

    private function utmFromRequest(): array
    {
        return $this->utmFromArray($this->request->getGet() ?: []);
    }

    private function utmFromArray(array $src): array
    {
        $out = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
            $v = trim((string) ($src[$k] ?? ''));
            $out[$k] = $v !== '' ? mb_substr(preg_replace('/[^\w\-\.\s\+%]/u', '', $v), 0, 160) : null;
        }
        return $out;
    }
}
