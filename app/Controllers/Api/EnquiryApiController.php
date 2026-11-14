<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ContactEnquiryModel;
use App\Models\EmployerEnquiryModel;
use App\Services\CaptchaService;
use App\Services\OutboxService;
use CodeIgniter\HTTP\ResponseInterface;

class EnquiryApiController extends BaseController
{
    private ContactEnquiryModel $contactModel;
    private EmployerEnquiryModel $employerModel;

    public function __construct()
    {
        $this->contactModel = new ContactEnquiryModel();
        $this->employerModel = new EmployerEnquiryModel();
    }

    /**
     * Helper to verify admin permissions for protected API routes
     */
    private function checkAdminPermission(string $perm = 'enquiries.manage'): ?ResponseInterface
    {
        $session = session();
        $adminUser = $session->get('admin_user');
        if (!$adminUser || empty($adminUser['id'])) {
            return $this->respondError('Authentication required to access administrative resource.', 401, 'UNAUTHORIZED');
        }

        // Super Admin bypass
        foreach ($adminUser['roles'] ?? [] as $r) {
            if (($r['slug'] ?? '') === 'super_admin' || ($r['name'] ?? '') === 'Super Administrator') {
                return null;
            }
        }

        $perms = $adminUser['permissions'] ?? [];
        if (!in_array($perm, $perms, true)) {
            return $this->respondError("Access restricted: required privilege '{$perm}'.", 403, 'FORBIDDEN');
        }

        return null;
    }

    public function submitContact(): ResponseInterface
    {
        $json = $this->getSafeJson();

        // 1. Honeypot check
        $hpField = trim((string) ($json['th_hp_email'] ?? $json['website_hp'] ?? $this->request->getPost('th_hp_email') ?? $this->request->getPost('website_hp') ?? ''));
        if (!empty($hpField)) {
            return $this->respondError('Spam submission detected.', 422, 'SPAM_REJECTED');
        }

        // 2. CAPTCHA verification if token provided
        $captchaToken = trim((string) ($json['cf-turnstile-response'] ?? $json['g-recaptcha-response'] ?? $this->request->getPost('cf-turnstile-response') ?? $this->request->getPost('g-recaptcha-response') ?? ''));
        if (!empty($captchaToken)) {
            $captchaService = new CaptchaService();
            $captchaResult = $captchaService->verify($captchaToken, $this->request->getIPAddress(), 'contact');
            if (!$captchaResult['success']) {
                return $this->respondError('Security verification failed. Please refresh and try again.', 422, 'CAPTCHA_FAILED');
            }
        }

        $rawName = trim((string) ($json['name'] ?? $this->request->getPost('name') ?? ''));
        $firstName = trim((string) ($json['first_name'] ?? $this->request->getPost('first_name') ?? ''));
        $lastName = trim((string) ($json['last_name'] ?? $this->request->getPost('last_name') ?? ''));

        if (empty($firstName) && !empty($rawName)) {
            $parts = explode(' ', $rawName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
        }

        $email = strtolower(trim((string) ($json['email'] ?? $this->request->getPost('email') ?? '')));
        $rawPhone = trim((string) ($json['phone'] ?? $this->request->getPost('phone') ?? ''));
        $company = trim((string) ($json['company'] ?? $this->request->getPost('company') ?? ''));
        $subject = trim((string) ($json['subject'] ?? $this->request->getPost('subject') ?? 'General Enquiry'));
        $message = trim((string) ($json['message'] ?? $this->request->getPost('message') ?? ''));
        $consent = (int) ($json['consent'] ?? $this->request->getPost('consent') ?? 0);

        if (empty($firstName) || strlen($firstName) < 2) {
            return $this->respondError('Full name or first name is required', 422, 'VALIDATION_FAILED');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('A valid email address is required', 422, 'VALIDATION_FAILED');
        }
        if (empty($rawPhone)) {
            return $this->respondError('A valid telephone or mobile number is required', 422, 'VALIDATION_FAILED');
        }

        $normPhone = normalize_phone_number($rawPhone);
        if (!$normPhone) {
            return $this->respondError('Please provide a valid Indian (10 digits) or international phone number (with country code).', 422, 'VALIDATION_FAILED');
        }

        if ($consent !== 1) {
            return $this->respondError('You must agree to the Privacy Policy to submit your enquiry.', 422, 'CONSENT_REQUIRED');
        }

        if (empty($message) || strlen($message) < 5) {
            return $this->respondError('Message content is required', 422, 'VALIDATION_FAILED');
        }

        // 3. Server-side Idempotency
        $idempotencyKey = trim((string) ($json['idempotency_key'] ?? $this->request->getHeaderLine('X-Idempotency-Key') ?? ''));
        if (empty($idempotencyKey)) {
            $idempotencyKey = 'cnt_' . md5($email . '|' . $subject . '|' . date('Y-m-d H:i'));
        }

        $existing = $this->contactModel->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $this->respondJson(['id' => (int) $existing['id'], 'duplicate' => true], 'Enquiry already received. Our team will contact you shortly.', 200);
        }

        $id = $this->contactModel->insert([
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $email,
            'phone'             => $normPhone,
            'company'           => $company,
            'subject'           => $subject,
            'message'           => $message,
            'status'            => 'UNREAD',
            'ip_address'        => $this->request->getIPAddress(),
            'idempotency_key'   => $idempotencyKey,
            'consent_received'  => 1,
            'consent_timestamp' => date('Y-m-d H:i:s'),
        ]);

        // Queue confirmation outbox email
        $outbox = new OutboxService();
        $outbox->queueTemplate('contact_enquiry_ack', $email, $firstName . ' ' . $lastName, [
            'name'    => $firstName . ($lastName ? ' ' . $lastName : ''),
            'subject' => $subject,
            'message' => $message,
        ]);

        return $this->respondJson(['id' => $id], 'Enquiry received. Our team will contact you shortly.', 201);
    }

    public function submitEmployer(): ResponseInterface
    {
        $json = $this->getSafeJson();

        // 1. Honeypot check
        $hpField = trim((string) ($json['th_hp_email'] ?? $json['website_hp'] ?? $this->request->getPost('th_hp_email') ?? $this->request->getPost('website_hp') ?? ''));
        if (!empty($hpField)) {
            return $this->respondError('Spam submission detected.', 422, 'SPAM_REJECTED');
        }

        // 2. CAPTCHA verification if token provided
        $captchaToken = trim((string) ($json['cf-turnstile-response'] ?? $json['g-recaptcha-response'] ?? $this->request->getPost('cf-turnstile-response') ?? $this->request->getPost('g-recaptcha-response') ?? ''));
        if (!empty($captchaToken)) {
            $captchaService = new CaptchaService();
            $captchaResult = $captchaService->verify($captchaToken, $this->request->getIPAddress(), 'employer');
            if (!$captchaResult['success']) {
                return $this->respondError('Security verification failed. Please refresh and try again.', 422, 'CAPTCHA_FAILED');
            }
        }

        $companyName = trim((string) ($json['company_name'] ?? $this->request->getPost('company_name') ?? ''));
        $contactPerson = trim((string) ($json['contact_person'] ?? $this->request->getPost('contact_person') ?? ''));
        $email = strtolower(trim((string) ($json['email'] ?? $this->request->getPost('email') ?? '')));
        $rawPhone = trim((string) ($json['phone'] ?? $this->request->getPost('phone') ?? ''));
        $requiredRoles = trim((string) ($json['required_roles'] ?? $json['job_title'] ?? $this->request->getPost('required_roles') ?? $this->request->getPost('job_title') ?? ''));
        $openings = (string) ($json['openings_count'] ?? $this->request->getPost('openings_count') ?? $this->request->getPost('number_of_positions') ?? '1');
        $location = trim((string) ($json['location'] ?? $this->request->getPost('location') ?? 'Pune'));
        $message = trim((string) ($json['message'] ?? $this->request->getPost('message') ?? ''));
        $consent = (int) ($json['consent'] ?? $this->request->getPost('consent') ?? 0);

        if (empty($companyName)) {
            return $this->respondError('Company or enterprise name is required', 422, 'VALIDATION_FAILED');
        }
        if (empty($contactPerson)) {
            return $this->respondError('Contact person name is required', 422, 'VALIDATION_FAILED');
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('A valid corporate email address is required', 422, 'VALIDATION_FAILED');
        }
        if (empty($rawPhone)) {
            return $this->respondError('A valid telephone or mobile number is required', 422, 'VALIDATION_FAILED');
        }

        $normPhone = normalize_phone_number($rawPhone);
        if (!$normPhone) {
            return $this->respondError('Please provide a valid Indian (10 digits) or international phone number (with country code).', 422, 'VALIDATION_FAILED');
        }

        if ($consent !== 1) {
            return $this->respondError('You must agree to the Terms & Privacy Policy to submit a hiring mandate.', 422, 'CONSENT_REQUIRED');
        }

        // 3. Server-side Idempotency
        $idempotencyKey = trim((string) ($json['idempotency_key'] ?? $this->request->getHeaderLine('X-Idempotency-Key') ?? ''));
        if (empty($idempotencyKey)) {
            $idempotencyKey = 'emp_' . md5($email . '|' . $companyName . '|' . date('Y-m-d H:i'));
        }

        $existing = $this->employerModel->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $this->respondJson(['id' => (int) $existing['id'], 'duplicate' => true], 'Hiring mandate already registered.', 200);
        }

        $rawUrgency = trim((string) ($json['urgency'] ?? $this->request->getPost('urgency') ?? 'Immediate'));
        $urgencyMap = [
            'immediate'   => 'IMMEDIATE',
            '15 days'     => '15_DAYS',
            '30 days'     => '30_DAYS',
            '60 days'     => 'EXPLORATORY',
            'pipeline'    => 'EXPLORATORY',
            'exploratory' => 'EXPLORATORY',
        ];
        $normUrgency = $urgencyMap[strtolower($rawUrgency)] ?? 'IMMEDIATE';

        $id = $this->employerModel->insert([
            'company_name'        => $companyName,
            'contact_person'      => $contactPerson,
            'email'               => $email,
            'phone'               => $normPhone,
            'required_roles'      => $requiredRoles ?: 'General Mandate',
            'number_of_positions' => $openings,
            'location'            => $location,
            'experience_bracket'  => trim((string) ($json['experience_bracket'] ?? $this->request->getPost('experience_bracket') ?? '')),
            'urgency'             => $normUrgency,
            'notes'               => substr($message, 0, 1000),
            'status'              => 'NEW',
            'idempotency_key'     => $idempotencyKey,
            'consent_received'    => 1,
            'consent_timestamp'   => date('Y-m-d H:i:s'),
        ]);

        // Queue acknowledgement
        $outbox = new OutboxService();
        $outbox->queueTemplate('employer_enquiry_ack', $email, $contactPerson, [
            'contact_person' => $contactPerson,
            'company_name'   => $companyName,
            'job_title'      => $requiredRoles ?: 'Hiring Mandate',
            'openings_count' => $openings,
            'location'       => $location,
        ]);

        return $this->respondJson(['id' => $id], 'Hiring mandate registered.', 201);
    }

    public function employerEnquiries(): ResponseInterface
    {
        if ($authErr = $this->checkAdminPermission('enquiries.manage')) {
            return $authErr;
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $builder = $this->employerModel->builder();
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }
        if ($search = $this->request->getGet('search')) {
            $builder->groupStart()->like('company_name', $search)->orLike('contact_person', $search)->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $enquiries = $builder->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();

        return $this->respondJson($enquiries, 'Employer enquiries retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function contactEnquiries(): ResponseInterface
    {
        if ($authErr = $this->checkAdminPermission('enquiries.manage')) {
            return $authErr;
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $builder = $this->contactModel->builder();
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }
        if ($search = $this->request->getGet('search')) {
            $builder->groupStart()->like('first_name', $search)->orLike('email', $search)->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $enquiries = $builder->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();

        return $this->respondJson($enquiries, 'Contact enquiries retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function updateEmployerStatus($id): ResponseInterface
    {
        if ($authErr = $this->checkAdminPermission('enquiries.manage')) {
            return $authErr;
        }

        $enq = $this->employerModel->find($id);
        if (!$enq) {
            return $this->respondError('Enquiry not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $status = $input['status'] ?? 'CONTACTED';

        $this->employerModel->update($id, ['status' => $status]);
        return $this->respondJson(['id' => $id, 'status' => $status], 'Status updated');
    }

    public function updateContactStatus($id): ResponseInterface
    {
        if ($authErr = $this->checkAdminPermission('enquiries.manage')) {
            return $authErr;
        }

        $enq = $this->contactModel->find($id);
        if (!$enq) {
            return $this->respondError('Enquiry not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $status = $input['status'] ?? 'RESPONDED';

        $this->contactModel->update($id, ['status' => $status]);
        return $this->respondJson(['id' => $id, 'status' => $status], 'Status updated');
    }
}
