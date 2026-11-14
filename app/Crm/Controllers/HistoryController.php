<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\AuditService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\HistoryService;
use Crm\Services\Settings;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Global sales-transaction history (read-only, scope-checked) and the detailed per-enquiry transaction view. */
class HistoryController extends CrmController
{
    private const FILTERS = ['from', 'to', 'customer_id', 'enquiry_no', 'user_id', 'owner_id', 'prev_owner_id', 'team_id', 'product_id', 'event_group', 'status', 'approval', 'outcome'];
    private const EXPORT_MAX = 5000;
    private const PDF_MAX = 800;

    private function filters(): array
    {
        $f = [];
        foreach (self::FILTERS as $k) {
            $v = trim((string) ($this->request->getGet($k) ?? ''));
            if ($v !== '') {
                $f[$k] = $v;
            }
        }
        foreach (['from', 'to'] as $d) {
            if (isset($f[$d]) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$d])) {
                unset($f[$d]);
            }
        }

        return $f;
    }

    public function index(): string
    {
        $f    = $this->filters();
        $page = $this->page();
        $per  = 25;
        try {
            $res = HistoryService::global($f, $this->user(), $page, $per);
        } catch (CrmException $e) {
            throw new PageNotFoundException($e->getMessage());
        }
        $db  = Crm::db();
        $ids = AccessScope::visibleUserIds($this->user());
        $cb  = AccessScope::scopeCustomers($db->table('crm_customers c')->select('c.id, c.customer_no, c.name'), $this->user())->orderBy('c.name')->limit(600)->get()->getResultArray();
        $ub  = $db->table('admin_users')->select("id, CONCAT(first_name,' ',last_name) name, is_active", false);
        if ($ids !== null) {
            $ub->whereIn('id', $ids);
        }

        return $this->render('history/index', [
            'res' => $res, 'f' => $f, 'page' => $page, 'per' => $per, 'customers' => $cb, 'users' => $ub->orderBy('first_name')->get()->getResultArray(),
            'teams' => $db->table('crm_teams')->orderBy('name')->get()->getResultArray(),
            'products' => $db->table('crm_products')->select('id, sku, name')->where('is_active', 1)->orderBy('name')->limit(300)->get()->getResultArray(),
        ], 'Sales history', 'history');
    }

    public function enquiry(string $id): string
    {
        $id = (int) $id;
        $e  = Crm::db()->query("SELECT e.*, c.name customer_name, c.customer_no, CONCAT(o.first_name,' ',o.last_name) owner_name FROM crm_enquiries e JOIN crm_customers c ON c.id = e.customer_id LEFT JOIN admin_users o ON o.id = e.current_owner_id WHERE e.id = ?", [$id])->getRowArray();
        if (! $e || ! AccessScope::canSeeEnquiry($this->user(), $id)) {
            throw new PageNotFoundException('Not found');
        }
        $db    = Crm::db();
        $users = HistoryService::userNames();
        $events = array_map(static fn ($r) => HistoryService::present($r, $users), $db->table('crm_workflow_events')->where('enquiry_id', $id)->orderBy('created_at')->orderBy('id')->get()->getResultArray());
        $approvals = $db->query("SELECT a.*, n.negotiation_no, n.requested_discount_pct, n.proposed_price, q.quotation_no, CONCAT(u.first_name,' ',u.last_name) approver_name
              FROM crm_discount_approvals a JOIN crm_negotiations n ON n.id = a.negotiation_id JOIN crm_quotations q ON q.id = n.quotation_id LEFT JOIN admin_users u ON u.id = a.approver_id WHERE q.enquiry_id = ? ORDER BY a.id", [$id])->getResultArray();
        $quotes = $db->query('SELECT q.id, q.quotation_no, q.status, q.current_version_id FROM crm_quotations q WHERE q.enquiry_id = ? ORDER BY q.id', [$id])->getResultArray();
        foreach ($quotes as &$q) {
            $q['versions'] = $db->query('SELECT version_no, grand_total, discount_total, sent_at, created_at, revision_reason FROM crm_quotation_versions WHERE quotation_id = ? ORDER BY version_no', [$q['id']])->getResultArray();
        }
        unset($q);
        $emails = $db->query('SELECT id, to_email, subject, status, attempts, failure_reason, created_at FROM crm_email_log WHERE enquiry_id = ? ORDER BY created_at DESC', [$id])->getResultArray();
        try {
            $owners = HistoryService::ownership($id, $this->user());
        } catch (CrmException) {
            throw new PageNotFoundException('Not found');
        }

        return $this->render('history/enquiry', ['e' => $e, 'events' => $events, 'owners' => $owners, 'approvals' => $approvals, 'quotes' => $quotes, 'emails' => $emails], 'Transaction ' . $e['enquiry_no'], 'history');
    }

    public function export()
    {
        $f   = $this->filters();
        $fmt = $this->request->getGet('format') === 'pdf' ? 'pdf' : 'xlsx';
        if (! crm_can('reports.export') && ! crm_can('customers.export')) {
            throw new PageNotFoundException('Not found');
        }
        try {
            $rows = HistoryService::global($f, $this->user(), 1, self::EXPORT_MAX)['rows'];
        } catch (CrmException $e) {
            return $this->flashBack('error', $e->getMessage());
        }
        $head = ['When', 'Event', 'Reference', 'Customer', 'Enquiry', 'User', 'Previous owner', 'New owner', 'Previous status', 'New status', 'Remarks'];
        $data = array_map(static fn ($r) => [$r['created_at'], $r['label'], $r['entity_ref'] ?? '', trim(($r['customer_no'] ?? '') . ' ' . ($r['customer_name'] ?? '')), $r['enquiry_no'] ?? '', $r['user_name'], $r['prev_owner'] ?? '', $r['new_owner'] ?? '', crm_label($r['prev_status']) === '—' ? '' : crm_label($r['prev_status']), crm_label($r['new_status']) === '—' ? '' : crm_label($r['new_status']), $r['reason'] ?? ''], $rows);
        AuditService::audit('report.exported', 'report', 'sales_history', ['meta' => ['format' => $fmt, 'rows' => count($data), 'filters' => $f]]);
        if ($fmt === 'pdf') {
            $all  = count($data);
            $data = array_slice($data, 0, self::PDF_MAX);   // Dompdf is memory-bound; the Excel export carries the full set
            $h ='<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#1b2a41}h2{color:#0f2b4c}table{width:100%;border-collapse:collapse}th{background:#0f2b4c;color:#fff;padding:3px;text-align:left}td{border-bottom:1px solid #d8e0ea;padding:3px}</style></head><body><h2>Sales transaction history</h2><p>'
                . esc(Settings::get('company.name')) . ' · generated ' . esc(crm_dt(Crm::now())) . ' · ' . ($all > count($data) ? 'first ' . count($data) . ' of ' . $all . ' events (use the Excel export for the full set)' : $all . ' event(s)') . '</p><table><tr>';
            foreach ($head as $c) { $h .= '<th>' . esc($c) . '</th>'; }
            $h .= '</tr>';
            foreach ($data as $r) { $h .= '<tr>'; foreach ($r as $v) { $h .= '<td>' . esc((string) $v) . '</td>'; } $h .= '</tr>'; }
            $p = new Dompdf();
            $p->loadHtml($h . '</table></body></html>');
            $p->setPaper('A4', 'landscape');
            $p->render();

            return $this->download($p->output(), 'sales-history-' . date('Ymd') . '.pdf', 'application/pdf');
        }
        $safe = static fn ($v) => is_string($v) && isset($v[0]) && in_array($v[0], ['=', '+', '-', '@'], true) && ! is_numeric($v) ? "'" . $v : $v;
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet()->setTitle('Sales history');
        $sh->fromArray([$head], null, 'A1');
        $sh->getStyle('A1:K1')->getFont()->setBold(true);
        $sh->fromArray(array_map(static fn ($r) => array_map($safe, $r), $data), null, 'A2');
        foreach (range('A', 'K') as $col) { $sh->getColumnDimension($col)->setAutoSize(true); }
        ob_start();
        (new Xlsx($ss))->save('php://output');

        return $this->download((string) ob_get_clean(), 'sales-history-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
