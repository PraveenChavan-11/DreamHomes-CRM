<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\EmailTemplateModel;
use App\Services\OutboxService;

class MailTemplatesController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requirePermission('templates.manage')) {
            return $redirect;
        }

        $templateModel = new EmailTemplateModel();
        $templates = $templateModel->orderBy('name', 'ASC')->findAll();

        return $this->renderAdminView('admin/mail_templates/index', [
            'templates' => $templates,
            'title'     => 'Email Templates — TalentHub Admin',
        ], 'email_templates');
    }

    public function edit($id)
    {
        if ($redirect = $this->requirePermission('templates.manage')) {
            return $redirect;
        }

        $templateModel = new EmailTemplateModel();
        $template = $templateModel->find($id);

        if (!$template) {
            session()->setFlashdata('error', 'Email template not found.');
            return redirect()->to(base_url('admin/mail/templates'));
        }

        return $this->renderAdminView('admin/mail_templates/edit', [
            'template' => $template,
            'title'    => 'Edit Email Template: ' . esc($template['name']) . ' — TalentHub Admin',
        ], 'email_templates');
    }

    public function update($id)
    {
        if ($redirect = $this->requirePermission('templates.manage')) {
            return $redirect;
        }

        $templateModel = new EmailTemplateModel();
        $template = $templateModel->find($id);

        if (!$template) {
            session()->setFlashdata('error', 'Email template not found.');
            return redirect()->to(base_url('admin/mail/templates'));
        }

        $subject = trim($this->request->getPost('subject') ?? '');
        $bodyHtml = trim($this->request->getPost('body_html') ?? '');
        $bodyText = trim($this->request->getPost('body_text') ?? '');
        $isActive = (int) ($this->request->getPost('is_active') ? 1 : 0);

        if (empty($subject) || empty($bodyHtml)) {
            session()->setFlashdata('error', 'Email subject and HTML body cannot be empty.');
            return redirect()->back()->withInput();
        }

        if (empty($bodyText)) {
            $bodyText = strip_tags($bodyHtml);
        }

        $oldData = [
            'subject'   => $template['subject'],
            'is_active' => $template['is_active'],
        ];

        $newData = [
            'subject'   => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'is_active' => $isActive,
        ];

        $templateModel->update($id, $newData);

        // Audit Log
        $auditModel = new AuditLogModel();
        $auditModel->log('EMAIL_TEMPLATE_UPDATE', 'email_template', (int) $id, $oldData, [
            'subject'   => $subject,
            'is_active' => $isActive,
        ]);

        session()->setFlashdata('success', 'Email template "' . esc($template['name']) . '" updated successfully.');
        return redirect()->to(base_url('admin/mail/templates'));
    }

    public function testSend($id)
    {
        if ($redirect = $this->requirePermission('templates.manage')) {
            return $redirect;
        }

        $templateModel = new EmailTemplateModel();
        $template = $templateModel->find($id);

        if (!$template) {
            session()->setFlashdata('error', 'Email template not found.');
            return redirect()->to(base_url('admin/mail/templates'));
        }

        $testRecipient = trim($this->request->getPost('test_email') ?? '');
        if (empty($testRecipient) || !filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
            $adminUser = session()->get('admin_user');
            $testRecipient = $adminUser['email'] ?? 'admin@talenthubconsultant.com';
        }

        // Generate mock variable values for testing
        $mockVars = [
            'job_title'      => 'Senior Lead Architect',
            'company_name'   => 'TalentHub Partner Enterprise',
            'candidate_name' => 'Dr. Rajesh Deshmukh',
            'contact_name'   => 'Dr. Rajesh Deshmukh',
            'application_id' => 'TH-2026-9081',
            'reset_link'     => base_url('admin/reset-password?token=mock_test_token_12345'),
            'portal_url'     => base_url(),
            'support_phone'  => '+91 91589 83282',
            'support_email'  => 'hr@talenthubconsultant.com',
        ];

        try {
            $outbox = new OutboxService();
            $outboxId = $outbox->queueTemplate(
                $template['code'],
                $testRecipient,
                'Test Administrator',
                $mockVars,
                'test_' . $template['code'] . '_' . time()
            );

            if ($outboxId) {
                // Trigger queue processing immediately for test
                $outbox->processQueue(1);
                session()->setFlashdata('success', "Test message for template '{$template['name']}' queued and sent to {$testRecipient}.");
            } else {
                session()->setFlashdata('error', "Failed to queue test message for template.");
            }
        } catch (\Throwable $e) {
            session()->setFlashdata('error', "Error triggering test email: " . $e->getMessage());
        }

        return redirect()->to(base_url('admin/mail/templates'));
    }
}

