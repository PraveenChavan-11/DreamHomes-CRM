<?php

namespace Crm\Services;

/** Customer timeline, per-customer tab data and the global sales-transaction history. All reads are scope-checked. */
final class HistoryService
{
    private const LABELS = [
        'customer.created' => 'Customer created', 'customer.updated' => 'Customer details updated', 'customer.deactivated' => 'Customer deactivated', 'customer.reactivated' => 'Customer reactivated', 'customer.archived' => 'Customer archived',
        'enquiry.created' => 'Enquiry created', 'enquiry.assigned' => 'Assigned to salesperson', 'enquiry.reassigned' => 'Reassigned', 'enquiry.status_changed' => 'Status changed', 'enquiry.won' => 'Enquiry marked WON',
        'enquiry.lost' => 'Enquiry marked LOST', 'enquiry.reopened' => 'Enquiry reopened', 'activity.recorded' => 'Customer contacted', 'followup.scheduled' => 'Follow-up scheduled', 'followup.completed' => 'Follow-up completed',
        'followup.rescheduled' => 'Follow-up rescheduled', 'followup.missed' => 'Follow-up missed', 'followup.escalated' => 'Follow-up escalated to manager', 'visit.scheduled' => 'Site visit scheduled', 'visit.completed' => 'Site visit completed',
        'measurement.revised' => 'Measurements revised', 'quotation.created' => 'Quotation prepared', 'quotation.draft_updated' => 'Quotation draft updated', 'quotation.sent' => 'Quotation sent', 'quotation.resent' => 'Quotation re-sent',
        'quotation.revised' => 'Quotation revised (new version)', 'quotation.accepted' => 'Customer accepted quotation', 'quotation.rejected' => 'Customer rejected quotation', 'negotiation.requested' => 'Price reduction requested',
        'discount.requested' => 'Discount approval requested', 'discount.auto_approved' => 'Discount within personal limit', 'discount.approved' => 'Discount approved', 'discount.rejected' => 'Discount rejected',
        'discount.returned' => 'Discount request returned for revision', 'discount.escalated' => 'Discount request escalated', 'order.created' => 'Sales order created', 'order.confirmed' => 'Order confirmed', 'order.on_hold' => 'Order put on hold',
        'order.cancelled' => 'Order cancelled', 'order.closed' => 'Order closed', 'order.pending_confirmation' => 'Order pending confirmation',
    ];

    public static function link(array $ev): ?string
    {
        return match ($ev['entity_type']) {
            'customer'    => 'crm/customers/' . $ev['entity_id'],
            'enquiry'     => 'crm/enquiries/' . $ev['entity_id'],
            'quotation'   => 'crm/quotations/' . $ev['entity_id'],
            'negotiation' => 'crm/negotiations/' . $ev['entity_id'],
            'sales_order' => 'crm/orders/' . $ev['entity_id'],
            'followup', 'site_visit', 'activity' => $ev['enquiry_id'] ? 'crm/enquiries/' . $ev['enquiry_id'] : null,
            default => null,
        };
    }

    /** Decorate a raw workflow event for display (previous/new owner & status, human label, link). */
    public static function present(array $ev, array $users): array
    {
        $prev = $ev['prev_state'] ? (json_decode($ev['prev_state'], true) ?: []) : [];
        $new  = $ev['new_state'] ? (json_decode($ev['new_state'], true) ?: []) : [];
        $nm   = static fn ($id) => $id ? ($users[(int) $id] ?? ('User #' . $id)) : null;

        return $ev + [
            'label' => self::LABELS[$ev['event_type']] ?? ucfirst(str_replace(['.', '_'], ' ', $ev['event_type'])),
            'user_name' => $ev['user_id'] ? $nm($ev['user_id']) : ($ev['source'] === 'cron' ? 'System (scheduler)' : 'System'),
            'prev_owner' => $nm($prev['owner_id'] ?? null), 'new_owner' => $nm($new['owner_id'] ?? null),
            'prev_status' => $prev['status'] ?? null, 'new_status' => $new['status'] ?? null, 'prev' => $prev, 'new' => $new, 'link' => self::link($ev),
        ];
    }

    /** @return array<int,string> id => name (includes deactivated users so history never loses attribution) */
    public static function userNames(): array
    {
        $out = [];
        foreach (Crm::db()->table('admin_users')->select('id, first_name, last_name')->get()->getResultArray() as $u) {
            $out[(int) $u['id']] = trim($u['first_name'] . ' ' . $u['last_name']);
        }

        return $out;
    }

    /** Chronological timeline for one customer (oldest first). */
    public static function customerTimeline(int $customerId, array $actor, int $limit = 500): array
    {
        if (! AccessScope::can($actor, 'history.view') && ! AccessScope::can($actor, 'customers.view')) {
            throw new CrmException('You do not have permission to view history.');
        }
        if (! AccessScope::canSeeCustomer($actor, $customerId)) {
            throw new CrmException('You do not have access to this customer.');
        }
        $rows  = Crm::db()->table('crm_workflow_events')->where('customer_id', $customerId)->orderBy('created_at')->orderBy('id')->limit($limit)->get()->getResultArray();
        $users = self::userNames();

        return array_map(static fn ($r) => self::present($r, $users), $rows);
    }

    /** All tab data for the customer profile. @return array<string,mixed> */
    public static function customerTabs(int $customerId, array $actor): array
    {
        if (! AccessScope::canSeeCustomer($actor, $customerId)) {
            throw new CrmException('You do not have access to this customer.');
        }
        $db = Crm::db();
        $ids = AccessScope::visibleUserIds($actor);
        $eScope = $ids === null ? '1=1' : '(e.current_owner_id IN (' . implode(',', array_map('intval', $ids)) . ') OR e.created_by IN (' . implode(',', array_map('intval', $ids)) . '))';
        $q = static fn (string $sql, array $b = []) => $db->query($sql, array_merge([$customerId], $b))->getResultArray();
        $canAudit = AccessScope::can($actor, 'audit.view');

        return [
            'customer'  => $db->table('crm_customers')->where('id', $customerId)->get()->getRowArray(),
            'enquiries' => $q("SELECT e.*, CONCAT(u.first_name,' ',u.last_name) owner_name FROM crm_enquiries e LEFT JOIN admin_users u ON u.id = e.current_owner_id WHERE e.customer_id = ? AND {$eScope} ORDER BY e.created_at DESC"),
            'salespeople' => $q("SELECT a.*, e.enquiry_no, CONCAT(tu.first_name,' ',tu.last_name) to_name, CONCAT(fu.first_name,' ',fu.last_name) from_name FROM crm_enquiry_assignments a JOIN crm_enquiries e ON e.id = a.enquiry_id
                 LEFT JOIN admin_users tu ON tu.id = a.to_user_id LEFT JOIN admin_users fu ON fu.id = a.from_user_id WHERE e.customer_id = ? AND {$eScope} ORDER BY a.created_at"),
            'followups' => $q("SELECT f.*, e.enquiry_no, CONCAT(u.first_name,' ',u.last_name) user_name FROM crm_followups f LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id LEFT JOIN admin_users u ON u.id = f.assigned_to WHERE f.customer_id = ? ORDER BY f.due_at DESC"),
            'activities' => $q("SELECT a.*, e.enquiry_no, CONCAT(u.first_name,' ',u.last_name) user_name FROM crm_activities a LEFT JOIN crm_enquiries e ON e.id = a.enquiry_id LEFT JOIN admin_users u ON u.id = a.user_id WHERE a.customer_id = ? ORDER BY a.occurred_at DESC"),
            'visits' => $q("SELECT v.*, e.enquiry_no, CONCAT(u.first_name,' ',u.last_name) user_name FROM crm_site_visits v JOIN crm_enquiries e ON e.id = v.enquiry_id LEFT JOIN admin_users u ON u.id = v.assigned_to WHERE v.customer_id = ? ORDER BY v.scheduled_at DESC"),
            'measurements' => $q("SELECT m.*, v.visit_no FROM crm_site_measurements m JOIN crm_site_visits v ON v.id = m.visit_id WHERE v.customer_id = ? ORDER BY v.id, m.version_no"),
            'quotations' => $q("SELECT q.*, v.version_no, v.grand_total, v.valid_until, e.enquiry_no, (SELECT COUNT(*) FROM crm_quotation_versions x WHERE x.quotation_id = q.id) versions FROM crm_quotations q JOIN crm_quotation_versions v ON v.id = q.current_version_id JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE q.customer_id = ? ORDER BY q.created_at DESC"),
            'negotiations' => $q("SELECT n.*, q.quotation_no, CONCAT(u.first_name,' ',u.last_name) requester FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id LEFT JOIN admin_users u ON u.id = n.requested_by WHERE q.customer_id = ? ORDER BY n.created_at DESC"),
            'approvals' => $q("SELECT a.*, n.negotiation_no, n.requested_discount_pct, CONCAT(u.first_name,' ',u.last_name) approver_name FROM crm_discount_approvals a JOIN crm_negotiations n ON n.id = a.negotiation_id JOIN crm_quotations q ON q.id = n.quotation_id LEFT JOIN admin_users u ON u.id = a.approver_id WHERE q.customer_id = ? ORDER BY a.id"),
            'emails' => $q('SELECT id, to_email, subject, template_code, status, provider, provider_response, failure_reason, attempts, created_at, quotation_id FROM crm_email_log WHERE customer_id = ? ORDER BY created_at DESC'),
            'orders' => $q("SELECT o.*, q.quotation_no FROM crm_sales_orders o JOIN crm_quotations q ON q.id = o.quotation_id WHERE o.customer_id = ? ORDER BY o.created_at DESC"),
            'documents' => $q('SELECT id, entity_type, entity_id, original_name, mime, size_bytes, created_at FROM crm_documents WHERE customer_id = ? ORDER BY created_at DESC'),
            'workflow' => self::customerTimeline($customerId, $actor),
            'audit' => $canAudit ? $db->query("SELECT a.* FROM crm_audit_events a WHERE a.entity_type = 'customer' AND a.entity_id = ? ORDER BY a.created_at DESC", [(string) $customerId])->getResultArray() : [],
        ];
    }

    /**
     * Global history with filters. @param array<string,mixed> $f from,to,customer_id,enquiry_no,user_id,owner_id,prev_owner_id,team_id,product_id,event_group,status,approval,outcome
     * @return array{rows:list<array>,total:int}
     */
    public static function global(array $f, array $actor, int $page = 1, int $per = 50): array
    {
        if (! AccessScope::can($actor, 'history.view')) {
            throw new CrmException('You do not have permission to view sales history.');
        }
        $db = Crm::db();
        $w  = ['1=1'];
        $b  = [];
        $ids = AccessScope::visibleUserIds($actor);
        if ($ids !== null) {
            $in  = implode(',', array_map('intval', $ids));
            $w[] = "(e.current_owner_id IN ({$in}) OR e.created_by IN ({$in}))";
        }
        if (! empty($f['from'])) { $w[] = 'w.created_at >= ?'; $b[] = $f['from'] . ' 00:00:00'; }
        if (! empty($f['to'])) { $w[] = 'w.created_at <= ?'; $b[] = $f['to'] . ' 23:59:59'; }
        if (! empty($f['customer_id'])) { $w[] = 'w.customer_id = ?'; $b[] = (int) $f['customer_id']; }
        if (! empty($f['enquiry_no'])) { $w[] = 'e.enquiry_no LIKE ?'; $b[] = '%' . $f['enquiry_no'] . '%'; }
        if (! empty($f['user_id'])) { $w[] = 'w.user_id = ?'; $b[] = (int) $f['user_id']; }
        if (! empty($f['owner_id'])) { $w[] = 'e.current_owner_id = ?'; $b[] = (int) $f['owner_id']; }
        if (! empty($f['prev_owner_id'])) { $w[] = 'EXISTS (SELECT 1 FROM crm_enquiry_assignments pa WHERE pa.enquiry_id = e.id AND pa.from_user_id = ?)'; $b[] = (int) $f['prev_owner_id']; }
        if (! empty($f['team_id'])) { $w[] = 'e.team_id = ?'; $b[] = (int) $f['team_id']; }
        if (! empty($f['product_id'])) { $w[] = 'EXISTS (SELECT 1 FROM crm_quotations pq JOIN crm_quotation_items pi ON pi.version_id IN (SELECT id FROM crm_quotation_versions WHERE quotation_id = pq.id) WHERE pq.enquiry_id = e.id AND pi.product_id = ?)'; $b[] = (int) $f['product_id']; }
        if (! empty($f['status'])) { $w[] = 'e.status = ?'; $b[] = $f['status']; }
        if (! empty($f['outcome'])) { $w[] = 'e.outcome = ?'; $b[] = $f['outcome']; }
        if (! empty($f['event_group'])) {
            $map = ['enquiry' => 'enquiry.%', 'quotation' => 'quotation.%', 'negotiation' => 'negotiation.%', 'discount' => 'discount.%', 'order' => 'order.%', 'followup' => 'followup.%', 'visit' => 'visit.%', 'activity' => 'activity.%'];
            if (isset($map[$f['event_group']])) { $w[] = 'w.event_type LIKE ?'; $b[] = $map[$f['event_group']]; }
        }
        if (! empty($f['approval'])) { $w[] = 'w.event_type = ?'; $b[] = 'discount.' . $f['approval']; }
        $where = implode(' AND ', $w);
        $total = (int) $db->query("SELECT COUNT(*) c FROM crm_workflow_events w LEFT JOIN crm_enquiries e ON e.id = w.enquiry_id WHERE {$where}", $b)->getRow()->c;
        $off   = max(0, ($page - 1) * $per);
        $rows  = $db->query("SELECT w.*, e.enquiry_no, c.name customer_name, c.customer_no FROM crm_workflow_events w LEFT JOIN crm_enquiries e ON e.id = w.enquiry_id LEFT JOIN crm_customers c ON c.id = w.customer_id
                             WHERE {$where} ORDER BY w.created_at DESC, w.id DESC LIMIT {$per} OFFSET {$off}", $b)->getResultArray();
        $users = self::userNames();

        return ['rows' => array_map(static fn ($r) => self::present($r, $users), $rows), 'total' => $total];
    }

    /** Ownership history of an enquiry: every assignment in order, with names. */
    public static function ownership(int $enquiryId, array $actor): array
    {
        if (! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
            throw new CrmException('You do not have access to this enquiry.');
        }
        $u = self::userNames();
        $rows = Crm::db()->table('crm_enquiry_assignments')->where('enquiry_id', $enquiryId)->orderBy('id')->get()->getResultArray();

        return array_map(static fn ($r) => $r + ['from_name' => $r['from_user_id'] ? ($u[(int) $r['from_user_id']] ?? '?') : null, 'to_name' => $r['to_user_id'] ? ($u[(int) $r['to_user_id']] ?? '?') : null, 'by_name' => $r['assigned_by'] ? ($u[(int) $r['assigned_by']] ?? '?') : 'System'], $rows);
    }
}
