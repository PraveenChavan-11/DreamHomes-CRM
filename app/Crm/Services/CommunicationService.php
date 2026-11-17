<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class CommunicationService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    public static function log(array $data, int $actorId): array
    {
        $db = self::db();
        $orgId = (int) ($data['organisation_id'] ?? 0);
        if ($orgId <= 0) {
            throw new CrmException('Organisation reference is required for communication logging.');
        }

        $now = Crm::now();
        $record = [
            'organisation_id' => $orgId,
            'contact_id' => ! empty($data['contact_id']) ? (int) $data['contact_id'] : null,
            'communication_type' => $data['communication_type'] ?? 'call',
            'direction' => $data['direction'] ?? 'outgoing',
            'subject' => trim((string) ($data['subject'] ?? 'Sales Communication')),
            'message_body' => trim((string) ($data['message_body'] ?? '')),
            'outcome' => $data['outcome'] ?? null,
            'next_action' => $data['next_action'] ?? null,
            'followup_date' => ! empty($data['followup_date']) ? $data['followup_date'] : null,
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => ! empty($data['related_entity_id']) ? (int) $data['related_entity_id'] : null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'logged_by_user_id' => $actorId,
            'created_at' => $now,
        ];

        $db->table('crm_communication_logs')->insert($record);
        $id = (int) $db->insertID();

        return $record + ['id' => $id];
    }

    public static function getHistoryForOrganisation(int $orgId, int $limit = 50): array
    {
        $db = self::db();
        return $db->table('crm_communication_logs cl')
            ->select("cl.*, c.first_name, c.last_name, c.mobile AS contact_mobile, CONCAT(u.first_name, ' ', u.last_name) AS logged_by_name")
            ->join('crm_contacts c', 'c.id = cl.contact_id', 'left')
            ->join('admin_users u', 'u.id = cl.logged_by_user_id', 'left')
            ->where('cl.organisation_id', $orgId)
            ->orderBy('cl.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    public static function getHistoryForContact(int $contactId, int $limit = 30): array
    {
        $db = self::db();
        return $db->table('crm_communication_logs cl')
            ->select("cl.*, CONCAT(u.first_name, ' ', u.last_name) AS logged_by_name")
            ->join('admin_users u', 'u.id = cl.logged_by_user_id', 'left')
            ->where('cl.contact_id', $contactId)
            ->orderBy('cl.created_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    /**
     * Seeds and ensures all 28 required email templates exist with dynamic variables.
     */
    public static function seedAll28Templates(): void
    {
        $db = self::db();
        $now = Crm::now();

        $templates = [
            [
                'code' => 'welcome_email',
                'name' => '1. Welcome Email',
                'subject' => 'Welcome to Dream Homes Interior Solutions, {{contact_name}}!',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Thank you for partnering with <b>Dream Homes Interior Solutions</b>. We are delighted to assist {{organisation_name}} with premium architectural and interior solutions.</p><p>Your dedicated sales specialist is <b>{{salesperson_name}}</b>.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{salesperson_name}},{{company_name}},{{company_phone}}',
            ],
            [
                'code' => 'lead_assigned',
                'name' => '2. New Lead Assignment',
                'subject' => 'New Opportunity Assigned: {{lead_number}} - {{organisation_name}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>You have been assigned a new sales opportunity: <b>{{lead_number}}</b> for <b>{{organisation_name}}</b> (Project: {{project_name}}).</p><p>Please review and initiate contact promptly.</p>',
                'variables' => '{{salesperson_name}},{{lead_number}},{{organisation_name}},{{project_name}},{{contact_name}}',
            ],
            [
                'code' => 'followup_reminder',
                'name' => '3. Follow-up Reminder',
                'subject' => 'Follow-up Due: {{organisation_name}} - {{project_name}}',
                'body_html' => '<p>Dear {{salesperson_name}},</p><p>Reminder: A follow-up action is due today for <b>{{organisation_name}}</b> (Contact: {{contact_name}}).</p>',
                'variables' => '{{salesperson_name}},{{organisation_name}},{{contact_name}},{{project_name}}',
            ],
            [
                'code' => 'meeting_confirmation',
                'name' => '4. Meeting Confirmation',
                'subject' => 'Confirmed: Architectural Consultation with DHIS Group',
                'body_html' => '<p>Dear {{contact_name}},</p><p>This email confirms our upcoming meeting with {{organisation_name}} regarding {{project_name}}.</p><p>Representative: {{salesperson_name}} ({{company_phone}}).</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{project_name}},{{salesperson_name}},{{company_phone}}',
            ],
            [
                'code' => 'visit_confirmation',
                'name' => '5. Site Visit Confirmation',
                'subject' => 'Scheduled Site Survey: {{project_name}} - DHIS Group',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Our technical survey team has scheduled an on-site visit for {{project_name}}.</p><p>Our representative {{salesperson_name}} will take architectural measurements.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{project_name}},{{salesperson_name}}',
            ],
            [
                'code' => 'quote_submitted_approval',
                'name' => '6. Quotation Submitted for Approval',
                'subject' => 'Quotation {{quotation_number}} Submitted for Commercial Approval',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Your quotation <b>{{quotation_number}}</b> (Discount: {{discount_percentage}}%, Amount: {{quotation_amount}}) has been submitted for management review.</p>',
                'variables' => '{{salesperson_name}},{{quotation_number}},{{discount_percentage}},{{quotation_amount}},{{manager_name}}',
            ],
            [
                'code' => 'approval_request_manager',
                'name' => '7. Approval Request to Sales Manager',
                'subject' => 'Commercial Approval Needed: Quotation {{quotation_number}} ({{discount_percentage}}% Disc)',
                'body_html' => '<p>Attention {{manager_name}},</p><p>Salesperson {{salesperson_name}} has requested commercial approval for quotation <b>{{quotation_number}}</b> (Customer: {{organisation_name}}, Grand Total: {{quotation_amount}}). Proposed discount: <b>{{discount_percentage}}%</b>.</p>',
                'variables' => '{{manager_name}},{{salesperson_name}},{{quotation_number}},{{organisation_name}},{{discount_percentage}},{{quotation_amount}}',
            ],
            [
                'code' => 'quote_approved',
                'name' => '8. Quotation Approved',
                'subject' => 'APPROVED: Commercial Quotation {{quotation_number}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Great news! Quotation <b>{{quotation_number}}</b> has been <b>APPROVED</b> by {{approved_by}}.<br>Remarks: {{approval_remarks}}</p><p>You can now generate and send the official customer PDF.</p>',
                'variables' => '{{salesperson_name}},{{quotation_number}},{{approved_by}},{{approval_remarks}},{{quotation_amount}}',
            ],
            [
                'code' => 'quote_rejected',
                'name' => '9. Quotation Rejected',
                'subject' => 'REJECTED: Commercial Quotation {{quotation_number}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Commercial approval for quotation <b>{{quotation_number}}</b> was <b>REJECTED</b> by {{approved_by}}.<br>Reason: <b>{{approval_remarks}}</b>.</p><p>Please revise commercial terms accordingly.</p>',
                'variables' => '{{salesperson_name}},{{quotation_number}},{{approved_by}},{{approval_remarks}}',
            ],
            [
                'code' => 'quote_returned_revision',
                'name' => '10. Quotation Returned for Revision',
                'subject' => 'Revision Required: Quotation {{quotation_number}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Quotation <b>{{quotation_number}}</b> has been returned for pricing adjustments by {{manager_name}}.<br>Feedback: {{approval_remarks}}</p>',
                'variables' => '{{salesperson_name}},{{quotation_number}},{{manager_name}},{{approval_remarks}}',
            ],
            [
                'code' => 'quote_sent_customer',
                'name' => '11. Quotation Sent to Customer',
                'subject' => 'Official Commercial Quotation {{quotation_number}} - Dream Homes Interior Solutions',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Please find attached our official commercial proposal <b>{{quotation_number}}</b> for {{project_name}} with grand total of <b>{{quotation_amount}}</b>.</p><p>Prepared with care by {{salesperson_name}}.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{project_name}},{{quotation_number}},{{quotation_amount}},{{salesperson_name}}',
            ],
            [
                'code' => 'quote_reminder',
                'name' => '12. Quotation Reminder',
                'subject' => 'Follow-up regarding Quotation {{quotation_number}} - {{organisation_name}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>We are following up on commercial proposal {{quotation_number}} sent earlier. Please let us know if you require any architectural clarifications.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{quotation_number}},{{salesperson_name}}',
            ],
            [
                'code' => 'quote_expiry_reminder',
                'name' => '13. Quotation Expiry Reminder',
                'subject' => 'Important: Quotation {{quotation_number}} is nearing validity expiration',
                'body_html' => '<p>Dear {{contact_name}},</p><p>This is a courtesy notice that the pricing for quotation <b>{{quotation_number}}</b> is valid until the end of this week. Contact {{salesperson_name}} to lock in commercial terms.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{quotation_number}},{{salesperson_name}}',
            ],
            [
                'code' => 'quote_accepted',
                'name' => '14. Quotation Accepted',
                'subject' => 'Congratulations! Quotation {{quotation_number}} Accepted by {{organisation_name}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Quotation <b>{{quotation_number}}</b> has been officially <b>ACCEPTED</b> by {{contact_name}} ({{organisation_name}}). Please prepare the sales order.</p>',
                'variables' => '{{salesperson_name}},{{quotation_number}},{{organisation_name}},{{contact_name}},{{quotation_amount}}',
            ],
            [
                'code' => 'order_confirmed',
                'name' => '15. Sales Order Confirmation',
                'subject' => 'Order Confirmation: {{sales_order_number}} - DHIS Group',
                'body_html' => '<p>Dear {{contact_name}},</p><p>We are pleased to confirm Sales Order <b>{{sales_order_number}}</b> for {{organisation_name}}. Total order value: <b>{{quotation_amount}}</b>.</p><p>Our production and fulfillment teams have been notified.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{quotation_amount}},{{salesperson_name}}',
            ],
            [
                'code' => 'advance_payment_reminder',
                'name' => '16. Advance Payment Reminder',
                'subject' => 'Payment Notice: Advance Clearance for Order {{sales_order_number}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>To commence manufacturing and scheduling for order {{sales_order_number}}, please arrange the advance payment of <b>{{payment_amount}}</b>.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{payment_amount}}',
            ],
            [
                'code' => 'payment_receipt',
                'name' => '17. Payment Receipt',
                'subject' => 'Official Payment Receipt - Order {{sales_order_number}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>We acknowledge receipt of <b>{{payment_amount}}</b> towards order {{sales_order_number}}. Outstanding balance: <b>{{outstanding_amount}}</b>.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{payment_amount}},{{outstanding_amount}}',
            ],
            [
                'code' => 'payment_overdue',
                'name' => '18. Payment Overdue',
                'subject' => 'Payment Overdue Notice: Order {{sales_order_number}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>According to our records, payment for order {{sales_order_number}} in the amount of <b>{{outstanding_amount}}</b> is currently pending. Please facilitate settlement at your earliest convenience.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{outstanding_amount}}',
            ],
            [
                'code' => 'delivery_scheduled',
                'name' => '19. Delivery Scheduled',
                'subject' => 'Dispatch Scheduled: Order {{sales_order_number}} on {{delivery_date}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Consignment for order {{sales_order_number}} has been scheduled for dispatch on <b>{{delivery_date}}</b> to site address: {{project_name}}.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{delivery_date}},{{project_name}}',
            ],
            [
                'code' => 'delivery_completed',
                'name' => '20. Delivery Completed',
                'subject' => 'Delivery Sign-off Confirmation - Order {{sales_order_number}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Materials for order {{sales_order_number}} have been safely delivered to site. Installation team has been scheduled.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{delivery_date}}',
            ],
            [
                'code' => 'installation_scheduled',
                'name' => '21. Installation Scheduled',
                'subject' => 'Installation Scheduled for Order {{sales_order_number}} on {{installation_date}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Our installation team is scheduled to commence on-site fitting for order {{sales_order_number}} on <b>{{installation_date}}</b>.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}},{{installation_date}}',
            ],
            [
                'code' => 'installation_completed',
                'name' => '22. Installation Completed',
                'subject' => 'Installation Sign-off Completed - Order {{sales_order_number}}',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Installation and final quality alignment for order {{sales_order_number}} has been successfully completed. 10-year warranty coverage is now active.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{sales_order_number}}',
            ],
            [
                'code' => 'service_acknowledged',
                'name' => '23. Service Request Acknowledgement',
                'subject' => 'Service Ticket Created - Dream Homes Interior Solutions',
                'body_html' => '<p>Dear {{contact_name}},</p><p>We have received your after-sales service request. A technician will inspect the fittings shortly.</p>',
                'variables' => '{{contact_name}},{{organisation_name}},{{company_phone}}',
            ],
            [
                'code' => 'service_completed',
                'name' => '24. Service Completion',
                'subject' => 'Service Request Resolved - Dream Homes Interior Solutions',
                'body_html' => '<p>Dear {{contact_name}},</p><p>Your service ticket has been marked resolved. Thank you for choosing Dream Homes Interior Solutions.</p>',
                'variables' => '{{contact_name}},{{organisation_name}}',
            ],
            [
                'code' => 'target_achievement',
                'name' => '25. Target Achievement Notification',
                'subject' => 'Congratulations! Monthly Sales Target Achieved: {{salesperson_name}}',
                'body_html' => '<p>Hello {{salesperson_name}},</p><p>Outstanding work! You have reached 100% of your sales target for this period with confirmed revenue of {{quotation_amount}}.</p>',
                'variables' => '{{salesperson_name}},{{quotation_amount}},{{manager_name}}',
            ],
            [
                'code' => 'daily_sales_summary',
                'name' => '26. Daily Sales Summary',
                'subject' => 'DHIS Group Daily Sales Operations Summary',
                'body_html' => '<p>Hello {{manager_name}},</p><p>Attached is today\'s sales activity briefing: activities conducted, new enquiries created, and orders confirmed.</p>',
                'variables' => '{{manager_name}},{{company_name}}',
            ],
            [
                'code' => 'weekly_sales_summary',
                'name' => '27. Weekly Sales Summary',
                'subject' => 'Weekly Commercial Pipeline Report',
                'body_html' => '<p>Hello {{manager_name}},</p><p>Weekly review of team performance, quotation conversions, and outstanding discount approval SLA health.</p>',
                'variables' => '{{manager_name}},{{company_name}}',
            ],
            [
                'code' => 'monthly_sales_report',
                'name' => '28. Monthly Sales Report',
                'subject' => 'Monthly Executive Sales Performance Report',
                'body_html' => '<p>Executive Team,</p><p>Comprehensive monthly review of revenue achieved versus assigned targets, conversion funnel efficiency, and product sales breakdown.</p>',
                'variables' => '{{manager_name}},{{company_name}}',
            ],
        ];

        foreach ($templates as $tpl) {
            $existing = $db->table('crm_email_templates')->where('code', $tpl['code'])->get()->getRowArray();
            if ($existing) {
                $db->table('crm_email_templates')->where('id', $existing['id'])->update([
                    'name' => $tpl['name'],
                    'subject' => $tpl['subject'],
                    'body_html' => $tpl['body_html'],
                    'variables' => $tpl['variables'],
                    'updated_at' => $now,
                ]);
            } else {
                $db->table('crm_email_templates')->insert([
                    'code' => $tpl['code'],
                    'name' => $tpl['name'],
                    'subject' => $tpl['subject'],
                    'body_html' => $tpl['body_html'],
                    'variables' => $tpl['variables'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
