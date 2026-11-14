<?php

namespace Crm\Services;

/** Referential / business-rule integrity audit over the whole CRM database. Returns a list of human-readable problems (empty = clean). */
final class IntegrityChecker
{
    /** @return list<string> */
    public static function run(): array
    {
        $db   = Crm::db();
        $bad  = [];
        $checks = [
            'enquiries without a valid customer'                => 'SELECT COUNT(*) c FROM crm_enquiries e LEFT JOIN crm_customers c ON c.id = e.customer_id WHERE c.id IS NULL',
            'assigned enquiries whose owner is not a sales user' => "SELECT COUNT(*) c FROM crm_enquiries e WHERE e.current_owner_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = e.current_owner_id AND r.slug IN ('sales_executive','sales_manager'))",
            'assigned/active enquiries still in status new'     => "SELECT COUNT(*) c FROM crm_enquiries WHERE status = 'new' AND current_owner_id IS NOT NULL",
            'follow-ups pointing to missing enquiries'          => 'SELECT COUNT(*) c FROM crm_followups f LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id WHERE f.enquiry_id IS NOT NULL AND e.id IS NULL',
            'quotations whose current version is missing'       => 'SELECT COUNT(*) c FROM crm_quotations q LEFT JOIN crm_quotation_versions v ON v.id = q.current_version_id WHERE v.id IS NULL',
            'quotation versions without line items'             => 'SELECT COUNT(*) c FROM crm_quotation_versions v WHERE NOT EXISTS (SELECT 1 FROM crm_quotation_items i WHERE i.version_id = v.id)',
            'quotation items referencing missing products'      => 'SELECT COUNT(*) c FROM crm_quotation_items i LEFT JOIN crm_products p ON p.id = i.product_id WHERE i.product_id IS NOT NULL AND p.id IS NULL',
            'quotation totals that do not reconcile to lines'   => 'SELECT COUNT(*) c FROM crm_quotation_versions v JOIN (SELECT version_id, SUM(taxable_amount) t, SUM(tax_amount) x FROM crm_quotation_items GROUP BY version_id) s ON s.version_id = v.id WHERE ABS(s.t - v.taxable_total) > 0.01',
            'locked versions without a stored PDF hash'         => 'SELECT COUNT(*) c FROM crm_quotation_versions WHERE is_locked = 1 AND (pdf_sha256 IS NULL OR pdf_path IS NULL)',
            'approvals decided without an approver'             => "SELECT COUNT(*) c FROM crm_discount_approvals WHERE decision IN ('approved','rejected','returned','escalated') AND approver_id IS NULL",
            'self-approved discounts (policy off)'              => "SELECT COUNT(*) c FROM crm_discount_approvals a JOIN crm_negotiations n ON n.id = a.negotiation_id WHERE a.approver_id = n.requested_by AND a.required_role <> 'self'",
            'orders not linked to an accepted quotation'        => "SELECT COUNT(*) c FROM crm_sales_orders o JOIN crm_quotations q ON q.id = o.quotation_id WHERE q.status <> 'accepted'",
            'orders whose total differs from accepted version'  => 'SELECT COUNT(*) c FROM crm_sales_orders o JOIN crm_quotation_versions v ON v.id = o.version_id WHERE ABS(o.grand_total - v.grand_total) > 0.001',
            'won enquiries without closure record or order'     => "SELECT COUNT(*) c FROM crm_enquiries e WHERE e.status = 'won' AND (e.closed_at IS NULL OR e.closed_by IS NULL OR NOT EXISTS (SELECT 1 FROM crm_sales_orders o WHERE o.enquiry_id = e.id AND o.status <> 'cancelled'))",
            'lost enquiries without reason'                     => "SELECT COUNT(*) c FROM crm_enquiries WHERE status = 'lost' AND lost_reason_id IS NULL",
            'duplicate business numbers'                        => 'SELECT (SELECT COUNT(*) - COUNT(DISTINCT quotation_no) FROM crm_quotations) + (SELECT COUNT(*) - COUNT(DISTINCT enquiry_no) FROM crm_enquiries) + (SELECT COUNT(*) - COUNT(DISTINCT order_no) FROM crm_sales_orders) + (SELECT COUNT(*) - COUNT(DISTINCT customer_no) FROM crm_customers) c',
            'enquiries lacking a creation audit event'          => "SELECT COUNT(*) c FROM crm_enquiries e WHERE NOT EXISTS (SELECT 1 FROM crm_workflow_events w WHERE w.entity_type = 'enquiry' AND w.entity_id = e.id AND w.event_type = 'enquiry.created')",
            'status changes lacking a history row'              => "SELECT COUNT(*) c FROM crm_enquiries e WHERE NOT EXISTS (SELECT 1 FROM crm_enquiry_status_history h WHERE h.enquiry_id = e.id AND h.to_status = e.status)",
        ];
        foreach ($checks as $label => $sql) {
            $n = (int) $db->query($sql)->getRow()->c;
            if ($n > 0) {
                $bad[] = "{$n} {$label}";
            }
        }

        return $bad;
    }
}
