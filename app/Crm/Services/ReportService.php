<?php

namespace Crm\Services;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Report registry: permission + scope aware, filterable by date/user/team/status/customer, exportable to Excel and PDF. */
final class ReportService
{
    private const MAX_ROWS = 20000;

    /** key => [title, select/from/where(1=1) SQL, date col, owner col, status col, customer col, team col, order by, permission, columns[]] */
    private static function defs(): array
    {
        $u = static fn ($a) => "CONCAT({$a}.first_name,' ',{$a}.last_name)";

        return [
            'customers' => ['Customer register', "SELECT c.customer_no `Customer No`, c.name `Name`, c.customer_type `Type`, c.company_name `Company`, c.mobile `Mobile`, c.email `Email`, c.city `City`, c.status `Status`, c.created_at `Created`, " . $u('o') . " `Owner` FROM crm_customers c LEFT JOIN admin_users o ON o.id = c.owner_user_id WHERE 1=1", 'c.created_at', 'c.owner_user_id', 'c.status', 'c.id', null, 'c.id', 'customers.view'],
            'enquiries' => ['Enquiry register', "SELECT e.enquiry_no `Enquiry No`, c.name `Customer`, e.project_name `Project`, pc.name `Category`, e.status `Status`, e.priority `Priority`, e.lead_score `Score`, " . $u('o') . " `Owner`, e.source_channel `Source`, e.est_budget `Est. Budget`, e.created_at `Created`, e.closed_at `Closed` FROM crm_enquiries e JOIN crm_customers c ON c.id = e.customer_id LEFT JOIN admin_users o ON o.id = e.current_owner_id LEFT JOIN crm_product_categories pc ON pc.id = e.product_category_id WHERE 1=1", 'e.created_at', 'e.current_owner_id', 'e.status', 'e.customer_id', 'e.team_id', 'e.id', 'enquiries.view'],
            'assignments' => ['Assignment history', "SELECT e.enquiry_no `Enquiry No`, a.created_at `When`, " . $u('f') . " `From`, " . $u('t') . " `To`, a.method `Method`, a.reason `Reason`, " . $u('b') . " `Assigned by` FROM crm_enquiry_assignments a JOIN crm_enquiries e ON e.id = a.enquiry_id LEFT JOIN admin_users f ON f.id = a.from_user_id LEFT JOIN admin_users t ON t.id = a.to_user_id LEFT JOIN admin_users b ON b.id = a.assigned_by WHERE 1=1", 'a.created_at', 'e.current_owner_id', 'e.status', 'e.customer_id', 'e.team_id', 'a.id', 'enquiries.view'],
            'salesperson_activity' => ['Salesperson activity', "SELECT " . $u('u') . " `Salesperson`, a.activity_type `Type`, COUNT(*) `Count`, MAX(a.occurred_at) `Last` FROM crm_activities a JOIN admin_users u ON u.id = a.user_id LEFT JOIN crm_enquiries e ON e.id = a.enquiry_id WHERE 1=1", 'a.occurred_at', 'a.user_id', null, 'a.customer_id', 'e.team_id', 'u.id', 'reports.view', 'GROUP BY u.id, u.first_name, u.last_name, a.activity_type'],
            'followups' => ['Follow-up report', "SELECT e.enquiry_no `Enquiry No`, c.name `Customer`, f.activity_type `Type`, f.due_at `Due`, f.status `Status`, f.outcome `Outcome`, f.completed_at `Completed`, " . $u('u') . " `Assigned to` FROM crm_followups f JOIN crm_customers c ON c.id = f.customer_id LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id LEFT JOIN admin_users u ON u.id = f.assigned_to WHERE 1=1", 'f.due_at', 'f.assigned_to', 'f.status', 'f.customer_id', 'e.team_id', 'f.due_at DESC', 'reports.view'],
            'overdue_followups' => ['Overdue follow-ups', "SELECT e.enquiry_no `Enquiry No`, c.name `Customer`, f.due_at `Due`, TIMESTAMPDIFF(HOUR, f.due_at, NOW()) `Hours overdue`, f.status `Status`, " . $u('u') . " `Assigned to`, f.escalated_at `Escalated` FROM crm_followups f JOIN crm_customers c ON c.id = f.customer_id LEFT JOIN crm_enquiries e ON e.id = f.enquiry_id LEFT JOIN admin_users u ON u.id = f.assigned_to WHERE ((f.status = 'pending' AND f.due_at < NOW()) OR f.status = 'missed')", 'f.due_at', 'f.assigned_to', null, 'f.customer_id', 'e.team_id', 'f.due_at', 'reports.view'],
            'site_visits' => ['Site visit report', "SELECT v.visit_no `Visit No`, e.enquiry_no `Enquiry No`, c.name `Customer`, v.scheduled_at `Scheduled`, v.status `Status`, v.completed_at `Completed`, " . $u('u') . " `Assigned to`, v.observations `Observations` FROM crm_site_visits v JOIN crm_enquiries e ON e.id = v.enquiry_id JOIN crm_customers c ON c.id = v.customer_id LEFT JOIN admin_users u ON u.id = v.assigned_to WHERE 1=1", 'v.scheduled_at', 'v.assigned_to', 'v.status', 'v.customer_id', 'e.team_id', 'v.scheduled_at DESC', 'reports.view'],
            'measurements' => ['Measurement report', "SELECT v.visit_no `Visit No`, e.enquiry_no `Enquiry No`, c.name `Customer`, m.version_no `Version`, m.is_current `Current`, m.data `Measurements`, m.created_at `Recorded` FROM crm_site_measurements m JOIN crm_site_visits v ON v.id = m.visit_id JOIN crm_enquiries e ON e.id = v.enquiry_id JOIN crm_customers c ON c.id = v.customer_id WHERE 1=1", 'm.created_at', 'v.assigned_to', null, 'v.customer_id', 'e.team_id', 'v.id, m.version_no', 'reports.view'],
            'quotations' => ['Quotation register', "SELECT q.quotation_no `Quotation No`, e.enquiry_no `Enquiry No`, c.name `Customer`, v.version_no `Version`, q.status `Status`, v.subtotal `Subtotal`, v.discount_total `Discount`, v.tax_total `Tax`, v.grand_total `Grand Total`, v.valid_until `Valid until`, q.created_at `Created`, " . $u('o') . " `Salesperson` FROM crm_quotations q JOIN crm_quotation_versions v ON v.id = q.current_version_id JOIN crm_enquiries e ON e.id = q.enquiry_id JOIN crm_customers c ON c.id = q.customer_id LEFT JOIN admin_users o ON o.id = e.current_owner_id WHERE 1=1", 'q.created_at', 'e.current_owner_id', 'q.status', 'q.customer_id', 'e.team_id', 'q.id DESC', 'quotations.view'],
            'quotation_versions' => ['Quotation version report', "SELECT q.quotation_no `Quotation No`, v.version_no `Version`, v.grand_total `Grand Total`, v.discount_total `Discount`, v.is_locked `Locked`, v.sent_at `Sent`, LEFT(v.pdf_sha256,12) `PDF hash`, v.created_at `Created` FROM crm_quotation_versions v JOIN crm_quotations q ON q.id = v.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE 1=1", 'v.created_at', 'e.current_owner_id', 'q.status', 'q.customer_id', 'e.team_id', 'q.id, v.version_no', 'quotations.view'],
            'negotiations' => ['Negotiation report', "SELECT n.negotiation_no `Negotiation`, q.quotation_no `Quotation`, c.name `Customer`, n.requested_discount_pct `Requested %`, n.proposed_price `Proposed price`, n.status `Status`, n.required_level `Level`, " . $u('r') . " `Requested by`, n.created_at `Created` FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id JOIN crm_customers c ON c.id = q.customer_id LEFT JOIN admin_users r ON r.id = n.requested_by WHERE 1=1", 'n.created_at', 'e.current_owner_id', 'n.status', 'q.customer_id', 'e.team_id', 'n.id DESC', 'quotations.view'],
            'discount_approvals' => ['Discount approval report', "SELECT n.negotiation_no `Negotiation`, a.level `Level`, a.required_role `Required role`, a.decision `Decision`, " . $u('ap') . " `Approver`, a.remarks `Remarks`, a.decided_at `Decided`, n.requested_discount_pct `Requested %` FROM crm_discount_approvals a JOIN crm_negotiations n ON n.id = a.negotiation_id JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id LEFT JOIN admin_users ap ON ap.id = a.approver_id WHERE 1=1", 'a.created_at', 'e.current_owner_id', 'a.decision', 'q.customer_id', 'e.team_id', 'a.id DESC', 'quotations.view'],
            'orders' => ['Sales order report', "SELECT o.order_no `Order No`, q.quotation_no `Quotation`, c.name `Customer`, o.status `Status`, o.grand_total `Order value`, o.advance_amount `Advance`, o.confirmed_at `Confirmed`, o.expected_delivery `Expected delivery`, " . $u('s') . " `Salesperson` FROM crm_sales_orders o JOIN crm_quotations q ON q.id = o.quotation_id JOIN crm_customers c ON c.id = o.customer_id JOIN crm_enquiries e ON e.id = o.enquiry_id LEFT JOIN admin_users s ON s.id = o.salesperson_id WHERE 1=1", 'o.created_at', 'e.current_owner_id', 'o.status', 'o.customer_id', 'e.team_id', 'o.id DESC', 'orders.view'],
            'won_lost' => ['Won / lost report', "SELECT e.enquiry_no `Enquiry No`, c.name `Customer`, e.status `Outcome`, e.final_value `Final value`, lr.name `Lost reason`, e.lost_competitor `Competitor`, e.closed_at `Closed`, " . $u('cb') . " `Closed by` FROM crm_enquiries e JOIN crm_customers c ON c.id = e.customer_id LEFT JOIN crm_lost_reasons lr ON lr.id = e.lost_reason_id LEFT JOIN admin_users cb ON cb.id = e.closed_by WHERE e.status IN ('won','lost')", 'e.closed_at', 'e.current_owner_id', 'e.status', 'e.customer_id', 'e.team_id', 'e.closed_at DESC', 'enquiries.view'],
            'product_sales' => ['Product sales report', "SELECT COALESCE(p.sku,'-') `SKU`, i.description `Product`, COUNT(DISTINCT o.id) `Orders`, SUM(i.quantity) `Quantity`, SUM(i.line_total) `Sales value` FROM crm_sales_order_items i JOIN crm_sales_orders o ON o.id = i.order_id JOIN crm_enquiries e ON e.id = o.enquiry_id LEFT JOIN crm_products p ON p.id = i.product_id WHERE o.status <> 'cancelled'", 'o.created_at', 'e.current_owner_id', 'o.status', 'o.customer_id', 'e.team_id', 'SUM(i.line_total) DESC', 'reports.view', 'GROUP BY p.sku, i.description'],
            'lead_sources' => ['Lead source report', "SELECT COALESCE(s.name,'Unknown') `Source`, COUNT(*) `Enquiries`, SUM(e.status = 'won') `Won`, SUM(e.status = 'lost') `Lost`, ROUND(100*SUM(e.status='won')/NULLIF(SUM(e.status IN ('won','lost')),0),1) `Win %` FROM crm_enquiries e LEFT JOIN crm_lead_sources s ON s.id = e.source_id WHERE 1=1", 'e.created_at', 'e.current_owner_id', 'e.status', 'e.customer_id', 'e.team_id', 'COUNT(*) DESC', 'reports.view', 'GROUP BY s.name'],
            'conversion' => ['Sales conversion report', "SELECT " . $u('u') . " `Salesperson`, COUNT(*) `Enquiries`, SUM(e.status='won') `Won`, SUM(e.status='lost') `Lost`, ROUND(100*SUM(e.status='won')/NULLIF(SUM(e.status IN ('won','lost')),0),1) `Win %`, COALESCE(SUM(CASE WHEN e.status='won' THEN e.final_value END),0) `Won value` FROM crm_enquiries e JOIN admin_users u ON u.id = e.current_owner_id WHERE 1=1", 'e.created_at', 'e.current_owner_id', 'e.status', 'e.customer_id', 'e.team_id', 'COALESCE(SUM(CASE WHEN e.status=\'won\' THEN e.final_value END),0) DESC', 'reports.view', 'GROUP BY u.id, u.first_name, u.last_name'],
            'login_history' => ['Login / logout report', "SELECT l.created_at `When`, l.event `Event`, COALESCE(l.email, u.email) `Email`, " . $u('u') . " `User`, l.ip_address `IP` FROM crm_login_history l LEFT JOIN admin_users u ON u.id = l.user_id WHERE 1=1", 'l.created_at', 'l.user_id', 'l.event', null, null, 'l.id DESC', 'audit.view'],
            'audit' => ['Audit report', "SELECT a.created_at `When`, a.event_type `Event`, a.entity_type `Entity`, a.entity_id `Entity ID`, " . $u('u') . " `User`, a.reason `Reason`, a.source `Source` FROM crm_audit_events a LEFT JOIN admin_users u ON u.id = a.user_id WHERE 1=1", 'a.created_at', 'a.user_id', 'a.event_type', null, null, 'a.id DESC', 'audit.view'],
            'email_log' => ['Email delivery report', "SELECT l.created_at `Created`, l.to_email `Recipient`, l.subject `Subject`, l.template_code `Template`, l.status `Status`, l.provider `Provider`, l.attempts `Attempts`, l.failure_reason `Failure reason`, l.provider_response `Provider response` FROM crm_email_log l LEFT JOIN crm_enquiries e ON e.id = l.enquiry_id WHERE 1=1", 'l.created_at', 'e.current_owner_id', 'l.status', 'l.customer_id', 'e.team_id', 'l.id DESC', 'emails.view'],
        ];
    }

    /** @return array<string,string> key => title for reports the actor may open */
    public static function available(array $actor): array
    {
        $out = [];
        foreach (self::defs() as $k => $d) {
            if (AccessScope::can($actor, 'reports.view') || AccessScope::can($actor, $d[8])) {
                if (in_array($k, ['login_history', 'audit'], true) && ! AccessScope::can($actor, 'audit.view')) {
                    continue;
                }
                $out[$k] = $d[0];
            }
        }

        return $out;
    }

    /** @return array{title:string,columns:list<string>,rows:list<array<string,mixed>>} */
    public static function run(string $key, array $f, array $actor): array
    {
        $defs = self::defs();
        $d    = $defs[$key] ?? throw new CrmException('Unknown report.');
        if (! isset(self::available($actor)[$key])) {
            throw new CrmException('You do not have permission to run this report.');
        }
        [$title, $sql, $dateCol, $ownerCol, $statusCol, $custCol, $teamCol, $order] = $d;
        $group = $d[9] ?? '';
        $b = [];
        if ($ownerCol && ($ids = AccessScope::visibleUserIds($actor)) !== null) {
            $sql .= " AND {$ownerCol} IN (" . implode(',', array_map('intval', $ids)) . ')';
        }
        if (! empty($f['from']) && $dateCol) { $sql .= " AND {$dateCol} >= ?"; $b[] = $f['from'] . ' 00:00:00'; }
        if (! empty($f['to']) && $dateCol) { $sql .= " AND {$dateCol} <= ?"; $b[] = $f['to'] . ' 23:59:59'; }
        if (! empty($f['user_id']) && $ownerCol) { $sql .= " AND {$ownerCol} = ?"; $b[] = (int) $f['user_id']; }
        if (! empty($f['team_id']) && $teamCol) { $sql .= " AND {$teamCol} = ?"; $b[] = (int) $f['team_id']; }
        if (! empty($f['status']) && $statusCol) { $sql .= " AND {$statusCol} = ?"; $b[] = $f['status']; }
        if (! empty($f['customer_id']) && $custCol) { $sql .= " AND {$custCol} = ?"; $b[] = (int) $f['customer_id']; }
        $sql .= " {$group} ORDER BY {$order} LIMIT " . self::MAX_ROWS;
        $rows = Crm::db()->query($sql, $b)->getResultArray();

        return ['title' => $title, 'columns' => $rows ? array_keys($rows[0]) : self::columnsOf($sql), 'rows' => $rows];
    }

    private static function columnsOf(string $sql): array
    {
        preg_match_all('/`([^`]+)`(?=,| FROM)/', $sql, $m);

        return array_values(array_unique($m[1] ?? []));
    }

    /** Excel export honouring permissions and filters. @return string binary xlsx */
    public static function toXlsx(string $key, array $f, array $actor): string
    {
        if (! AccessScope::can($actor, 'reports.export')) {
            throw new CrmException('You do not have permission to export reports.');
        }
        $r  = self::run($key, $f, $actor);
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $r['title']), 0, 31));
        $sh->fromArray([$r['columns']], null, 'A1');
        $sh->getStyle('A1:' . $sh->getHighestColumn() . '1')->getFont()->setBold(true);
        $i = 2;
        foreach ($r['rows'] as $row) {
            $vals = array_values(array_map(static fn ($v) => is_string($v) && isset($v[0]) && in_array($v[0], ['=', '+', '-', '@'], true) && ! is_numeric($v) ? "'" . $v : $v, $row));
            $sh->fromArray([$vals], null, 'A' . $i++);
        }
        foreach (range('A', $sh->getHighestColumn()) as $col) {
            $sh->getColumnDimension($col)->setAutoSize(true);
        }
        AuditService::audit('report.exported', 'report', $key, ['meta' => ['format' => 'xlsx', 'rows' => count($r['rows']), 'filters' => $f]]);
        ob_start();
        (new Xlsx($ss))->save('php://output');

        return (string) ob_get_clean();
    }

    public static function toPdf(string $key, array $f, array $actor): string
    {
        if (! AccessScope::can($actor, 'reports.export')) {
            throw new CrmException('You do not have permission to export reports.');
        }
        $r = self::run($key, $f, $actor);
        $h = '<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#1b2a41}h2{color:#0f2b4c}table{width:100%;border-collapse:collapse}th{background:#0f2b4c;color:#fff;padding:3px;text-align:left}td{border-bottom:1px solid #d8e0ea;padding:3px}</style></head><body><h2>' . esc($r['title']) . '</h2><p>' . esc(Settings::get('company.name')) . ' - generated ' . esc(Crm::now()) . ' - ' . count($r['rows']) . ' rows</p><table><tr>';
        foreach ($r['columns'] as $c) {
            $h .= '<th>' . esc($c) . '</th>';
        }
        $h .= '</tr>';
        foreach (array_slice($r['rows'], 0, 2000) as $row) {
            $h .= '<tr>';
            foreach ($row as $v) {
                $h .= '<td>' . esc((string) $v) . '</td>';
            }
            $h .= '</tr>';
        }
        $h .= '</table></body></html>';
        $p = new Dompdf();
        $p->loadHtml($h);
        $p->setPaper('A4', 'landscape');
        $p->render();
        AuditService::audit('report.exported', 'report', $key, ['meta' => ['format' => 'pdf', 'rows' => count($r['rows']), 'filters' => $f]]);

        return $p->output();
    }
}
