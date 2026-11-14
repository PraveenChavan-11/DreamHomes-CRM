<?php

namespace App\Services;

use App\Models\EmailOutboxModel;
use CodeIgniter\Database\BaseConnection;

/**
 * CRM admin notifications. Lead persistence never depends on this class: every public method swallows
 * (and logs) failures. Emails go through the existing email_outbox (retries + delivery handled by ProcessOutbox),
 * with an idempotency key per lead+event so retried requests/events never double-send.
 * No secrets or passwords are ever placed in a template variable.
 */
class CrmNotificationService
{
    public const DEFAULT_RECIPIENT = 'projects@talenthubconsultant.com';
    public const SETTING_KEY       = 'crm_notification_email';

    /** event => [template code, template name, subject, headline, intro] */
    public const EVENTS = [
        'website_enquiry'         => ['crm_new_website_enquiry', 'CRM: New website enquiry', 'New website enquiry: {{lead_name}}', 'New website enquiry', 'A new project enquiry was submitted on the website.'],
        'product_enquiry'         => ['crm_new_product_enquiry', 'CRM: New product enquiry', 'New product enquiry: {{product}} - {{lead_name}}', 'New product enquiry', 'A visitor enquired about a product.'],
        'ws_registration_initiated' => ['crm_ws_registration_initiated', 'CRM: WorkSphere registration initiated', 'WorkSphere registration started: {{company}}', 'WorkSphere registration initiated', 'Someone started a WorkSphere demo registration.'],
        'ws_email_verified'       => ['crm_ws_email_verified', 'CRM: WorkSphere email verified', 'WorkSphere email verified: {{company}}', 'WorkSphere email verified', 'The registrant verified their email address.'],
        'ws_demo_activated'       => ['crm_ws_demo_activated', 'CRM: WorkSphere demo activated', 'WorkSphere demo active: {{company}}', 'WorkSphere demo activated', 'A WorkSphere demo workspace is now active.'],
        'ws_provisioning_failed'  => ['crm_ws_provisioning_failed', 'CRM: WorkSphere provisioning failed', 'ACTION NEEDED - WorkSphere provisioning failed: {{company}}', 'WorkSphere provisioning failed', 'Automatic demo provisioning failed. Please contact the prospect personally.'],
        'ws_demo_expiring'        => ['crm_ws_demo_expiring', 'CRM: WorkSphere demo expiring', 'WorkSphere demo expiring: {{company}}', 'WorkSphere demo expiring soon', 'A demo is about to expire - a good moment to follow up.'],
        'ws_converted'            => ['crm_ws_converted', 'CRM: WorkSphere converted to paid', 'WorkSphere converted to paid: {{company}}', 'WorkSphere customer converted', 'The prospect converted to a paid WorkSphere plan.'],
        'lead_assigned'           => ['crm_lead_assigned', 'CRM: Lead assigned', 'Lead assigned to you: {{lead_name}}', 'Lead assigned to you', 'A CRM lead has been assigned to you.'],
        'followup_due'            => ['crm_followup_due', 'CRM: Follow-up due / overdue', 'Follow-up due: {{lead_name}}', 'Follow-up due', 'A CRM follow-up is due or overdue.'],
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function recipient(): string
    {
        try {
            $row = $this->db->table('website_settings')->select('setting_value')->where('setting_key', self::SETTING_KEY)->get()->getRowArray();
            $v = trim((string) ($row['setting_value'] ?? ''));
            if ($v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL)) {
                return $v;
            }
        } catch (\Throwable $e) {
        }
        return self::DEFAULT_RECIPIENT;
    }

    public function saveRecipient(string $email): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || preg_match('/[\r\n]/', $email)) {
            return false;
        }
        $exists = $this->db->table('website_settings')->where('setting_key', self::SETTING_KEY)->countAllResults();
        if ($exists) {
            $this->db->table('website_settings')->where('setting_key', self::SETTING_KEY)->update(['setting_value' => $email]);
        } else {
            $this->db->table('website_settings')->insert(['setting_key' => self::SETTING_KEY, 'setting_value' => $email, 'setting_group' => 'crm', 'is_public' => 0]);
        }
        return true;
    }

    /** Idempotent seeding of the 10 templates (never overwrites edits made in Email Templates). */
    public function ensureTemplates(): void
    {
        static $done = false;
        if ($done || !$this->db->tableExists('email_templates')) {
            return;
        }
        $done = true;
        foreach (self::EVENTS as [$code, $name, $subject, $headline, $intro]) {
            if ($this->db->table('email_templates')->where('code', $code)->countAllResults()) {
                continue;
            }
            $rows = '';
            foreach ([['Name', 'lead_name'], ['Company', 'company'], ['Email', 'email'], ['Phone', 'phone'], ['Product / service', 'product'], ['Source', 'source'], ['Status', 'status'], ['Priority', 'priority'], ['Details', 'detail']] as [$l, $k]) {
                $rows .= '<tr><td style="padding:6px 10px;color:#64748b;font-size:13px;width:140px;vertical-align:top;">' . $l . '</td><td style="padding:6px 10px;color:#0f172a;font-size:13px;white-space:pre-wrap;">{{' . $k . '}}</td></tr>';
            }
            $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;">'
                . '<h3 style="color:#1D6A34;margin:0 0 12px;">' . $headline . '</h3><p style="color:#334155;font-size:14px;margin:0 0 16px;">' . $intro . '</p>'
                . '<table style="border-collapse:collapse;width:100%;">' . $rows . '</table>'
                . '<p style="margin:20px 0 0;"><a href="{{lead_url}}" style="display:inline-block;padding:10px 20px;background:#1D6A34;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:bold;font-size:13px;">Open lead in CRM</a></p></div>';
            $text = $headline . "\n\n" . $intro . "\n\nName: {{lead_name}}\nCompany: {{company}}\nEmail: {{email}}\nPhone: {{phone}}\nProduct: {{product}}\nSource: {{source}}\nStatus: {{status}}\nPriority: {{priority}}\nDetails: {{detail}}\n\nOpen: {{lead_url}}";
            $this->db->table('email_templates')->insert([
                'code' => $code, 'name' => $name, 'subject' => $subject, 'body_html' => $html, 'body_text' => $text,
                'variables_help' => '{{lead_name}}, {{company}}, {{email}}, {{phone}}, {{product}}, {{source}}, {{status}}, {{priority}}, {{detail}}, {{lead_url}}',
                'is_active' => 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Queue one notification. Returns the outbox id, or null when skipped/failed. Never throws.
     *
     * @param string $eventKey distinguishes repeated occurrences (e.g. the WorkSphere event id); same key = same email
     */
    public function notify(string $event, int $leadId, array $extra = [], string $eventKey = ''): ?int
    {
        try {
            if (!isset(self::EVENTS[$event])) {
                return null;
            }
            $lead = $this->db->table('it_leads')->where('id', $leadId)->get()->getRowArray();
            if (!$lead) {
                return null;
            }
            $to = $this->recipient();
            if ($event === 'lead_assigned') {
                $u = $this->db->table('admin_users')->select('email')->where('id', (int) ($lead['assigned_to'] ?? 0))->where('is_active', 1)->get()->getRowArray();
                if (!$u || !filter_var($u['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    return null;
                }
                $to = $u['email'];
            }
            $key = 'crm-L' . $leadId . '-' . $event . '-' . substr(md5($eventKey), 0, 12);
            $existing = $this->db->table('email_outbox')->select('id')->where('idempotency_key', $key)->get()->getRowArray();
            if ($existing) {
                return null; // already queued once for this lead event
            }

            $detail = (string) ($extra['detail'] ?? $lead['project_description'] ?? '');
            $vars = [
                'lead_name' => $lead['client_name'], 'company' => $lead['company'] ?: $lead['client_name'], 'email' => $lead['email'],
                'phone'     => $lead['phone'] ?: '-', 'product' => $lead['product_slug'] ?: (ItLeadService::CATEGORIES[$lead['service_category']] ?? $lead['service_category']),
                'source'    => $lead['source'] ?? '', 'status' => ItLeadService::STATUSES[$lead['status']] ?? $lead['status'],
                'priority'  => $lead['priority'] ?? 'normal', 'detail' => mb_substr($detail, 0, 1200),
                'lead_url'  => base_url('admin/it-leads/' . $leadId),
            ];
            $id = $this->queue($event, $to, $vars, $key);
            if ($id) {
                $this->db->table('it_lead_activities')->insert([
                    'lead_id' => $leadId, 'admin_user_id' => null, 'type' => 'email',
                    'body' => 'Notification queued: ' . self::EVENTS[$event][1] . ' (outbox #' . $id . ')', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return $id;
        } catch (\Throwable $e) {
            log_message('error', 'CRM notification failed (' . $event . '): ' . $e->getMessage());
            return null;
        }
    }

    /** Admin "Send test notification": a clearly-labelled sample, no lead data. */
    public function sendTest(): ?int
    {
        try {
            $vars = ['lead_name' => 'Test Notification', 'company' => 'TalentHub CRM', 'email' => 'test@example.com', 'phone' => '-', 'product' => 'n/a',
                'source' => 'test', 'status' => 'n/a', 'priority' => 'normal', 'detail' => 'This is a test of the CRM notification pipeline. No action needed.', 'lead_url' => base_url('admin/it-leads')];
            return $this->queue('website_enquiry', $this->recipient(), $vars, 'crm-test-' . bin2hex(random_bytes(6)), '[TEST] ');
        } catch (\Throwable $e) {
            log_message('error', 'CRM test notification failed: ' . $e->getMessage());
            return null;
        }
    }

    private function queue(string $event, string $to, array $vars, string $key, string $subjectPrefix = ''): ?int
    {
        $this->ensureTemplates();
        [$code] = self::EVENTS[$event];
        $tpl = $this->db->table('email_templates')->where('code', $code)->where('is_active', 1)->get()->getRowArray();
        if (!$tpl) {
            log_message('error', "CRM notification template {$code} missing or disabled.");
            return null;
        }
        $clean = static fn ($v) => trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $v));
        $html = $tpl['body_html'];
        $text = $tpl['body_text'];
        $subject = $tpl['subject'];
        foreach ($vars as $k => $v) {
            $v = (string) $v;
            $html    = str_replace('{{' . $k . '}}', htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $html);
            $text    = str_replace('{{' . $k . '}}', str_replace(["\r", "\0"], '', $v), $text);
            $subject = str_replace('{{' . $k . '}}', $clean($v), $subject);
        }
        $subject = mb_substr($clean($subjectPrefix . $subject), 0, 180);
        $id = (new EmailOutboxModel())->enqueue($to, 'TalentHub CRM', $subject, $html, $text, $code, $key);
        return $id ?: null;
    }
}
