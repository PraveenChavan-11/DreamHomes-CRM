<?php

namespace Crm\Services;

/**
 * Template rendering, logging, dispatch and safe retry.
 * Status honesty:  queued → (smtp accepted) sent → (provider confirmation only) delivered | failed | bounced.
 *  - mode "log_only" (default): NOTHING leaves the system; row stays `queued` with provider_response "NOT TRANSMITTED".
 *  - Demo-owned rows (is_demo=1) are never transmitted, whatever the mode.
 */
final class EmailService
{
    public static function render(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', static fn ($m) => esc((string) ($vars[$m[1]] ?? '')), $template);
    }

    /**
     * @param array{template?:string,customer_id?:int,enquiry_id?:int,quotation_id?:int,attachment?:string,user_id?:int,automation?:string,is_demo?:int,vars?:array,subject?:string,body?:string} $o
     * @return int email log id
     */
    public static function queue(string $to, array $o): int
    {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw CrmException::validation(['to' => 'Recipient email address is invalid.']);
        }
        $db = Crm::db();
        if (! empty($o['template'])) {
            $t = $db->table('crm_email_templates')->where(['code' => $o['template'], 'is_active' => 1])->get()->getRowArray() ?? throw new CrmException('Unknown email template.');
            $subject = self::render($t['subject'], $o['vars'] ?? []);
            $body    = self::render($t['body_html'], $o['vars'] ?? []);
        } else {
            $subject = (string) ($o['subject'] ?? '');
            $body    = nl2br(esc((string) ($o['body'] ?? '')));
            if ($subject === '' || $body === '') {
                throw CrmException::validation(['subject' => 'Subject and body are required.']);
            }
        }
        $from = (string) (env('email.fromEmail') ?: Settings::get('company.email'));
        $db->table('crm_email_log')->insert([
            'to_email' => $to, 'from_email' => $from, 'subject' => mb_substr($subject, 0, 255), 'body_html' => $body, 'template_code' => $o['template'] ?? null,
            'customer_id' => $o['customer_id'] ?? null, 'enquiry_id' => $o['enquiry_id'] ?? null, 'quotation_id' => $o['quotation_id'] ?? null,
            'attachment_path' => $o['attachment'] ?? null, 'triggered_by_user' => $o['user_id'] ?? Crm::actorId(), 'triggered_by_automation' => $o['automation'] ?? null,
            'status' => 'queued', 'is_demo' => $o['is_demo'] ?? 0, 'created_at' => Crm::now(),
        ]);
        $id = (int) $db->insertID();
        self::dispatch($id);

        return $id;
    }

    /** Attempt transmission of one log row. Safe to call repeatedly: only queued/failed rows are touched. */
    public static function dispatch(int $logId): string
    {
        $db  = Crm::db();
        $row = $db->table('crm_email_log')->where('id', $logId)->get()->getRowArray() ?? throw new CrmException('Email log not found.');
        if (! in_array($row['status'], ['queued', 'failed'], true) || $row['provider'] === 'log_only') {
            return $row['status'];
        }
        $attempts = (int) $row['attempts'] + 1;
        $now      = Crm::now();
        $mode     = Settings::get('email.mode');

        if ((int) $row['is_demo'] === 1 || $mode !== 'smtp') {
            $why = (int) $row['is_demo'] === 1 ? 'demo record - transmission blocked' : 'email.mode=log_only';
            $db->table('crm_email_log')->where('id', $logId)->update(['attempts' => $attempts, 'last_attempt_at' => $now, 'provider' => 'log_only', 'provider_response' => "NOT TRANSMITTED ({$why})", 'status' => 'queued', 'next_retry_at' => null]);

            return 'queued';
        }

        try {
            $mail = \Config\Services::email();
            $mail->setFrom($row['from_email'], Settings::get('company.name'));
            $mail->setTo($row['to_email']);
            $mail->setSubject($row['subject']);
            $mail->setMessage((string) $row['body_html']);
            if ($row['attachment_path'] && is_file($row['attachment_path'])) {
                $mail->attach($row['attachment_path']);
            }
            if ($mail->send(false)) {
                $db->table('crm_email_log')->where('id', $logId)->update(['status' => 'sent', 'provider' => 'smtp', 'provider_response' => 'Accepted by SMTP server (delivery not confirmed)', 'attempts' => $attempts, 'last_attempt_at' => $now, 'sent_at' => $now, 'failure_reason' => null, 'next_retry_at' => null]);

                return 'sent';
            }
            throw new \RuntimeException(strip_tags((string) $mail->printDebugger(['headers'])));
        } catch (\Throwable $e) {
            $max   = (int) Settings::get('email.retry_max_attempts');
            $retry = $attempts < $max ? date('Y-m-d H:i:s', time() + 300 * $attempts * $attempts) : null;
            $db->table('crm_email_log')->where('id', $logId)->update([
                'status' => 'failed', 'provider' => 'smtp', 'failure_reason' => mb_substr($e->getMessage(), 0, 500), 'provider_response' => mb_substr($e->getMessage(), 0, 500),
                'attempts' => $attempts, 'last_attempt_at' => $now, 'next_retry_at' => $retry,
            ]);

            return 'failed';
        }
    }

    /** Cron: retry failed emails whose backoff has elapsed. @return int attempted */
    public static function retryDue(?string $now = null): int
    {
        $rows = Crm::db()->table('crm_email_log')->select('id')->where('status', 'failed')->where('next_retry_at <=', $now ?? Crm::now())->where('is_demo', 0)->get()->getResultArray();
        foreach ($rows as $r) {
            self::dispatch((int) $r['id']);
        }

        return count($rows);
    }

    /** Provider webhook / manual confirmation: the only paths allowed to set delivered|bounced. */
    public static function confirmDelivery(int $logId, string $status, ?string $providerResponse = null): void
    {
        if (! in_array($status, ['delivered', 'bounced'], true)) {
            throw new CrmException('Only delivered or bounced can be confirmed by a provider.');
        }
        $row = Crm::db()->table('crm_email_log')->where('id', $logId)->get()->getRowArray() ?? throw new CrmException('Email log not found.');
        if ($row['status'] !== 'sent') {
            throw new CrmException('Only emails accepted by SMTP (status sent) can be confirmed.');
        }
        Crm::db()->table('crm_email_log')->where('id', $logId)->update(['status' => $status, 'provider_response' => $providerResponse]);
    }
}
