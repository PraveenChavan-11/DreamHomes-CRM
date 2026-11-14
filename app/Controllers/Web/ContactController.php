<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\ContactEnquiryModel;
use App\Models\WebsitePageModel;
use App\Services\OutboxService;

class ContactController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\ResponseInterface|string
    {
        $pageModel = new WebsitePageModel();
        $page = $pageModel->getPublishedBySlug('contact');

        $metaTitle = $page['meta_title'] ?? 'Contact TalentHub Consultant Pune Desk';
        $metaDesc = $page['meta_description'] ?? 'Get in touch with TalentHub recruitment team in Katraj, Pune for employer talent hiring or candidate representation.';

        if ($this->wantsMarkdown()) {
            $md = "# Contact TalentHub Consultant — Pune Recruitment Desk\n\n"
                . "> {$metaDesc}\n\n"
                . "## Direct Contact Details\n"
                . "- **Office Address**: Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046, India\n"
                . "- **Primary Phone**: +91 72761 46573\n"
                . "- **WhatsApp Desk**: +91 72761 46573 (https://wa.me/917276146573)\n"
                . "- **Customer & Corporate Enquiries**: " . ($this->siteSettings['contact_email'] ?? 'hr@talenthubconsultant.com') . "\n"
                . "- **Candidate Applications & Resumes**: " . ($this->siteSettings['hiring_email'] ?? 'hiring@talenthubconsultant.com') . "\n"
                . "- **Operating Hours**: Monday – Saturday: 9:30 AM – 7:00 PM IST\n\n"
                . "## Web Form\n"
                . "Submit an online enquiry at: " . base_url('contact') . "\n";

            return $this->respondMarkdown($md);
        }

        return $this->renderView('public/contact', [
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDesc,
            'page'             => $page,
            'content'          => $page['content'] ?? [],
            'formProtection'   => (new \App\Services\FormSecurityService())->renderProtectionFields('contact_enquiry'),
        ]);
    }

    public function submit()
    {
        // 1. Anti-Bot & Form Security Verification (Honeypot, Signed Timestamp, CAPTCHA)
        $formSec = new \App\Services\FormSecurityService();
        $secCheck = $formSec->validateSubmission($this->request->getPost(), 'contact_enquiry', $this->request->getIPAddress());
        if (!$secCheck['valid']) {
            if ($this->request->isAJAX()) {
                return $this->respondError($secCheck['message'], 422, $secCheck['code']);
            }
            return redirect()->back()->withInput()->with('error', $secCheck['message']);
        }

        // 2. Affirmative Consent Verification
        if (!$this->request->getPost('consent')) {
            $msg = 'Please acknowledge and consent to our communication and privacy policy.';
            if ($this->request->isAJAX()) {
                return $this->respondError($msg, 422, 'CONSENT_REQUIRED');
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[60]',
            'last_name'  => 'permit_empty|max_length[60]',
            'email'      => 'required|valid_email|max_length[150]',
            'phone'      => 'required|min_length[8]|max_length[25]',
            'subject'    => 'permit_empty|max_length[150]',
            'message'    => 'permit_empty|max_length[2000]',
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
            $msg = 'Please enter a valid telephone or mobile number (e.g. +91 98810 54321).';
            if ($this->request->isAJAX()) {
                return $this->respondError($msg, 422, 'INVALID_PHONE');
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $enqModel = new ContactEnquiryModel();
        $outbox = new OutboxService();

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $subject = trim((string) ($this->request->getPost('subject') ?? 'General Enquiry'));
        $message = trim((string) $this->request->getPost('message'));
        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName = trim((string) ($this->request->getPost('last_name') ?? ''));

        // 4. Server-Side Idempotency
        $idempotencyKey = 'cnt_' . md5($email . '_' . substr($message, 0, 30) . '_' . date('YmdHi'));
        $existing = $enqModel->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            $msg = 'Thank you! Your message has already been received and forwarded to our Pune desk.';
            if ($this->request->isAJAX()) {
                return $this->respondJson(['enquiry_id' => $existing['id'], 'idempotent' => true], $msg);
            }
            return redirect()->to(base_url('thank-you?type=enquiry&ref=' . $existing['id']))->with('success', $msg);
        }

        try {
            $enqId = $enqModel->insert([
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'email'             => $email,
                'phone'             => $normalizedPhone,
                'company'           => trim($this->request->getPost('company') ?? ''),
                'subject'           => $subject,
                'message'           => $message,
                'status'            => 'UNREAD',
                'ip_address'        => $this->request->getIPAddress(),
                'idempotency_key'   => $idempotencyKey,
                'consent_received'  => 1,
                'consent_timestamp' => date('Y-m-d H:i:s'),
            ]);

            // Send alert to admin
            $adminEmail = $this->siteSettings['contact_email'] ?? 'hr@talenthubconsultant.com';
            $fullName = trim($firstName . ' ' . $lastName);

            $outbox->queueTemplate('admin_contact_alert', $adminEmail, 'TalentHub Desk', [
                'sender_name'     => $fullName,
                'sender_email'    => $email,
                'sender_phone'    => $normalizedPhone,
                'enquiry_subject' => $subject,
                'message'         => $message,
            ]);

            if ($this->request->isAJAX()) {
                return $this->respondJson([
                    'enquiry_id' => $enqId,
                    'redirect_url' => base_url('thank-you?type=enquiry&ref=' . $enqId),
                ], 'Thank you for reaching out! Our team in Katraj, Pune will get back to you shortly.');
            }

            return redirect()->to(base_url('thank-you?type=enquiry&ref=' . $enqId))
                             ->with('success', 'Your message has been received. Our team will contact you shortly.');
        } catch (\Throwable $e) {
            log_message('error', 'ContactController::submit error: ' . $e->getMessage());
            if ($this->request->isAJAX()) {
                return $this->respondError('We could not complete your request because of a problem on our side. Your information was not lost. Please try again in a few minutes or contact us directly.', 500);
            }
            return redirect()->to(base_url('submission-failed?type=enquiry&reason=server_error'));
        }
    }
}

