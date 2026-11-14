<?php

namespace App\Services;

use App\Models\EmailOutboxModel;
use App\Models\EmailTemplateModel;
use App\Models\WebsiteSettingModel;
use App\Services\EnvironmentSafetyService;
use Config\Services;

class OutboxService
{
    private EmailOutboxModel $outboxModel;
    private EmailTemplateModel $templateModel;
    private WebsiteSettingModel $settingModel;

    public function __construct()
    {
        $this->outboxModel = new EmailOutboxModel();
        $this->templateModel = new EmailTemplateModel();
        $this->settingModel = new WebsiteSettingModel();
    }

    /**
     * Enqueue a raw HTML email directly into outbox
     */
    public function enqueue(
        string $recipientEmail,
        string $subject,
        string $bodyHtml,
        string $sourceType = 'direct',
        ?int $sourceId = null,
        ?string $recipientName = null
    ): ?int {
        // Staging Sandbox Guard: Identifiable subject prefix
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            if (!str_starts_with($subject, '[STAGING TEST EMAIL]')) {
                $subject = '[STAGING TEST EMAIL] ' . $subject;
            }
        }

        return $this->outboxModel->enqueue(
            $recipientEmail,
            $recipientName ?? $recipientEmail,
            $subject,
            $bodyHtml,
            strip_tags($bodyHtml),
            $sourceType,
            $sourceType . '_' . ($sourceId ?? time()) . '_' . bin2hex(random_bytes(4))
        );
    }

    /**
     * Alias for processQueue
     */
    public function processPending(int $batchSize = 15): array
    {
        return $this->processQueue($batchSize);
    }

    /**
     * Send or queue an email using a named template
     */
    public function queueTemplate(
        string $templateCode,
        string $recipientEmail,
        string $recipientName,
        array $variables = [],
        ?string $idempotencyKey = null
    ): ?int {
        $template = $this->templateModel->getByCode($templateCode);
        if (!$template) {
            log_message('error', "OutboxService: Email template {$templateCode} not found.");
            return null;
        }

        $subject = $this->replaceVariables($template['subject'], $variables);
        $bodyHtml = $this->replaceVariables($template['body_html'], $variables);
        $bodyText = $this->replaceVariables($template['body_text'] ?? strip_tags($template['body_html']), $variables);

        return $this->outboxModel->enqueue(
            $recipientEmail,
            $recipientName,
            $subject,
            $bodyHtml,
            $bodyText,
            $templateCode,
            $idempotencyKey
        );
    }

    /**
     * Replace {{variable}} tags safely
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $val) {
            if (is_scalar($val)) {
                $content = str_replace(['{{' . $key . '}}', '{{ ' . $key . ' }}'], (string) $val, $content);
            }
        }
        return $content;
    }

    /**
     * Process pending emails from outbox
     */
    public function processQueue(int $batchSize = 15): array
    {
        // Atomically claim pending emails to prevent race conditions & duplicate dispatch across concurrent cron executions
        $pending = $this->outboxModel->claimBatch($batchSize);
        $results = ['sent' => 0, 'failed' => 0, 'total' => count($pending)];

        if (empty($pending)) {
            return $results;
        }

        $emailService = $this->configureMailer();
        $isSandboxed = EnvironmentSafetyService::isEmailSandboxed();

        foreach ($pending as $item) {
            try {
                // STAGING SANDBOX INTERCEPTOR:
                // Strictly prevent any external SMTP connection or external email delivery
                if ($isSandboxed) {
                    $sandboxLog = sprintf(
                        '[STAGING EMAIL SANDBOX] Captured test email to: %s <%s>. Subject: %s',
                        $item['recipient_name'],
                        $item['recipient_email'],
                        $item['subject']
                    );
                    log_message('info', $sandboxLog);

                    $this->outboxModel->update($item['id'], [
                        'status'     => 'SENT',
                        'sent_at'    => date('Y-m-d H:i:s'),
                        'last_error' => '[SANDBOXED] Successfully captured in Staging Email Sandbox. External dispatch blocked.',
                    ]);
                    $results['sent']++;
                    continue;
                }

                $emailService->clear();
                $emailService->setTo($item['recipient_email'], $item['recipient_name']);
                $emailService->setSubject($item['subject']);
                $emailService->setMessage($item['body_html']);
                if (!empty($item['body_text'])) {
                    $emailService->setAltMessage($item['body_text']);
                }

                if ($emailService->send(false)) {
                    $this->outboxModel->update($item['id'], [
                        'status'  => 'SENT',
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);
                    $results['sent']++;
                } else {
                    $error = $emailService->printDebugger(['headers']);
                    $attempts = ((int) $item['attempts']) + 1;
                    $status = ($attempts >= 4) ? 'FAILED' : 'PENDING';
                    $this->outboxModel->update($item['id'], [
                        'attempts'   => $attempts,
                        'status'     => $status,
                        'last_error' => substr($error, 0, 500),
                    ]);
                    $results['failed']++;
                }
            } catch (\Throwable $e) {
                $attempts = ((int) $item['attempts']) + 1;
                $status = ($attempts >= 4) ? 'FAILED' : 'PENDING';
                $this->outboxModel->update($item['id'], [
                    'attempts'   => $attempts,
                    'status'     => $status,
                    'last_error' => substr($e->getMessage(), 0, 500),
                ]);
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Configure CodeIgniter Email service dynamically from website_settings / environment
     */
    public function configureMailer(): \CodeIgniter\Email\Email
    {
        $settings = $this->settingModel->getAllKeyed();
        $email = Services::email();

        $smtpHost = $settings['smtp_host'] ?? getenv('SMTP_HOST') ?: 'smtp.hostinger.com';
        $smtpPort = (int) ($settings['smtp_port'] ?? getenv('SMTP_PORT') ?: 465);
        $smtpUser = $settings['smtp_user'] ?? getenv('SMTP_USER') ?: 'noreply@talenthubconsultant.com';
        $smtpPass = $settings['smtp_pass'] ?? getenv('SMTP_PASS') ?: '';
        $smtpCrypto = ($smtpPort === 465) ? 'ssl' : 'tls';
        $fromEmail = $settings['from_email'] ?? $smtpUser;
        $fromName = $settings['site_name'] ?? 'TalentHub Consultant';
        $replyTo = $settings['contact_email'] ?? 'info@talenthubconsultant.com';

        $config = [
            'protocol'   => 'smtp',
            'SMTPHost'   => $smtpHost,
            'SMTPPort'   => $smtpPort,
            'SMTPUser'   => $smtpUser,
            'SMTPPass'   => $smtpPass,
            'SMTPCrypto' => $smtpCrypto,
            'mailType'   => 'html',
            'charset'    => 'utf-8',
            'wordWrap'   => true,
            'newline'    => "\r\n",
            'CRLF'       => "\r\n",
            'fromEmail'  => $fromEmail,
            'fromName'   => $fromName,
        ];

        $email->initialize($config);
        if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $email->setReplyTo($replyTo, $fromName);
        }
        return $email;
    }
}

