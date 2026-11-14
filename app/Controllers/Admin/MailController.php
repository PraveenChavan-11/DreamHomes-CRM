<?php

namespace App\Controllers\Admin;

use App\Models\EmailOutboxModel;
use App\Models\EmailTemplateModel;
use App\Models\MailboxConnectionModel;
use App\Models\MailMessageIndexModel;
use App\Models\WebsiteSettingModel;
use App\Services\HostingerMailService;
use App\Services\MailboxService;

class MailController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requireAnyPermission(['templates.manage', 'enquiries.manage', 'candidates.manage'])) {
            return $redirect;
        }

        $mailModel = new MailMessageIndexModel();
        $mailboxModel = new MailboxConnectionModel();
        $outboxModel = new EmailOutboxModel();

        $search = $this->request->getGet('search');
        $folder = $this->request->getGet('folder') ?? 'INBOX';

        $query = $mailModel->orderBy('message_date', 'DESC');
        if (!empty($folder)) {
            $query->where('folder', $folder);
        }
        if (!empty($search)) {
            $query->groupStart()
                ->like('subject', $search)
                ->orLike('from_email', $search)
                ->orLike('from_name', $search)
                ->orLike('snippet', $search)
                ->groupEnd();
        }

        try {
            $messages = $query->findAll(50);
        } catch (\Throwable $e) {
            try {
                $messages = $mailModel->orderBy('created_at', 'DESC')->findAll(50);
            } catch (\Throwable $e2) {
                $messages = [];
            }
        }

        try {
            $mailboxes = $mailboxModel->findAll();
        } catch (\Throwable $e) {
            $mailboxes = [];
        }

        try {
            $outboxCount = $outboxModel->whereIn('status', ['PENDING', 'pending', 'PROCESSING', 'processing', 'QUEUED', 'queued'])->countAllResults();
        } catch (\Throwable $e) {
            $outboxCount = 0;
        }

        return $this->renderAdminView('admin/mail/index', [
            'messages'    => $messages,
            'mailboxes'   => $mailboxes,
            'folder'      => $folder,
            'search'      => $search,
            'outboxCount' => $outboxCount,
            'title'       => 'Hostinger Email Management & Communication Center — TalentHub Admin',
        ], 'mail');
    }

    public function view($id)
    {
        if ($redirect = $this->requireAnyPermission(['templates.manage', 'enquiries.manage', 'candidates.manage'])) {
            return $redirect;
        }

        $mailModel = new MailMessageIndexModel();
        $email = $mailModel->find($id);

        if (!$email) {
            session()->setFlashdata('error', 'Email message not found.');
            return redirect()->to(base_url('admin/mail'));
        }

        // Mark as read
        if (empty($email['is_read'])) {
            $mailModel->update($id, ['is_read' => 1]);
        }

        return $this->renderAdminView('admin/mail/view', [
            'email' => $email,
            'title' => esc($email['subject'] ?? 'View Message') . ' — Hostinger Mail',
        ], 'mail');
    }

    public function compose()
    {
        if ($redirect = $this->requireAnyPermission(['templates.manage', 'enquiries.manage', 'candidates.manage'])) {
            return $redirect;
        }

        $mailboxModel = new MailboxConnectionModel();
        $templateModel = new EmailTemplateModel();

        $mailboxes = $mailboxModel->findAll();
        $templates = $templateModel->findAll();

        $replyTo = $this->request->getGet('to');
        $replySubject = $this->request->getGet('subject');

        return $this->renderAdminView('admin/mail/compose', [
            'mailboxes'    => $mailboxes,
            'templates'    => $templates,
            'to'           => $replyTo,
            'subject'      => $replySubject,
            'title'        => 'Compose Recruitment Email — Hostinger Mail',
        ], 'mail');
    }

    public function send()
    {
        if ($redirect = $this->requireAnyPermission(['templates.manage', 'enquiries.manage', 'candidates.manage'])) {
            return $redirect;
        }

        $to = trim($this->request->getPost('to') ?? '');
        $from = trim($this->request->getPost('from') ?? 'hr@talenthubconsultant.com');
        $subject = trim($this->request->getPost('subject') ?? '');
        $body = $this->request->getPost('body') ?? '';

        if (empty($to) || empty($subject) || empty($body)) {
            session()->setFlashdata('error', 'Recipient email, subject, and message body are required.');
            return redirect()->back()->withInput();
        }

        try {
            $outboxModel = new EmailOutboxModel();
            $outboxModel->insert([
                'recipient_email' => $to,
                'sender_email'    => $from,
                'subject'         => $subject,
                'body_html'       => $body,
                'status'          => 'pending',
                'retry_count'     => 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            // Attempt instant dispatch via HostingerMailService
            try {
                $service = new HostingerMailService();
                $service->sendEmail([
                    'from'    => $from,
                    'to'      => $to,
                    'subject' => $subject,
                    'html'    => $body,
                ]);
            } catch (\Throwable $apiEx) {
                // If API fails or offline, outbox queue will process it automatically
            }

            session()->setFlashdata('success', "Email to {$to} dispatched successfully.");
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Failed to dispatch email: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/mail'));
    }

    public function sync()
    {
        if ($redirect = $this->requireAnyPermission(['templates.manage', 'enquiries.manage', 'candidates.manage'])) {
            return $redirect;
        }

        try {
            $service = new MailboxService();
            $result = $service->syncAllMailboxes();
            session()->setFlashdata('success', 'Hostinger mailboxes and message index synchronized successfully.');
        } catch (\Throwable $e) {
            session()->setFlashdata('error', 'Hostinger sync completed with notice: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/mail'));
    }

    public function settings()
    {
        if ($redirect = $this->requirePermission('settings.manage')) {
            return $redirect;
        }

        $settingModel = new WebsiteSettingModel();
        $mailSettings = [
            'hostinger_mail_token'  => $settingModel->getSetting('hostinger_mail_token') ?? '',
            'hostinger_default_from'=> $settingModel->getSetting('hostinger_default_from') ?? 'hr@talenthubconsultant.com',
            'hostinger_auto_sync'   => $settingModel->getSetting('hostinger_auto_sync') ?? '1',
        ];

        return $this->renderAdminView('admin/mail/settings', [
            'mailSettings' => $mailSettings,
            'title'        => 'Hostinger Mail API Settings — TalentHub Admin',
        ], 'mail');
    }

    public function updateSettings()
    {
        if ($redirect = $this->requirePermission('settings.manage')) {
            return $redirect;
        }

        $settingModel = new WebsiteSettingModel();
        $token = trim($this->request->getPost('hostinger_mail_token') ?? '');
        $defaultFrom = trim($this->request->getPost('hostinger_default_from') ?? 'hr@talenthubconsultant.com');

        $settingModel->setSetting('hostinger_mail_token', $token);
        $settingModel->setSetting('hostinger_default_from', $defaultFrom);

        session()->setFlashdata('success', 'Hostinger Mail API configuration saved.');
        return redirect()->to(base_url('admin/mail/settings'));
    }
}

