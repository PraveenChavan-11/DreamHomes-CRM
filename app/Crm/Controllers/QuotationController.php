<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\NegotiationService;
use Crm\Services\PdfService;
use Crm\Services\QuotationService;
use Crm\Services\RequirementService;
use Crm\Services\Settings;

/**
 * Quotations: list, create, edit draft, revise (new tracked version), send, customer response, compare, PDF.
 * Manual product / variant / size / line-total / negotiated-total editing is handled by one shared editor partial; the SERVER recomputes
 * and validates everything (preview endpoint returns the same numbers the save will produce).
 */
class QuotationController extends CrmController
{
    use QuotationEditorTrait;

    private const PER = 25;
    private const LINE_KEYS = ['product_id', 'description', 'variant', 'width', 'height', 'size_unit', 'nos', 'quantity', 'unit', 'unit_price', 'line_total', 'discount_pct', 'tax_rate', 'specifications'];

    // ───────────── helpers ─────────────

    /** @return array<string,mixed> quotation row the user may see; 404 otherwise (no existence leak) */
    private function loadQ(int $id): array
    {
        try {
            return QuotationService::loadQuotation($id, $this->user());
        } catch (CrmException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /** @return list<array<string,mixed>> posted line rows (blank rows dropped) */
    private function parseLines(): array
    {
        $out = [];
        foreach ((array) $this->request->getPost('lines') as $row) {
            if (! is_array($row)) {
                continue;
            }
            $l = $this->pick($row, self::LINE_KEYS);
            if (($l['product_id'] ?? '') === '' && ($l['description'] ?? '') === '' && ($l['unit_price'] ?? '') === '' && ($l['line_total'] ?? '') === '') {
                continue;
            }
            $l = array_filter($l, static fn ($v) => $v !== '' && $v !== null);   // blank inputs = "not provided" so catalogue defaults apply
            if (! isset($l['quantity'])) {
                $l['quantity'] = '1';
            }
            $out[] = $l;
        }

        return $out;
    }

    /** @return list<array{name:string,amount:string}> */
    private function parseCharges(): array
    {
        $out = [];
        foreach ((array) $this->request->getPost('charges') as $row) {
            if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '' && trim((string) ($row['amount'] ?? '')) !== '') {
                $out[] = ['name' => trim((string) $row['name']), 'amount' => trim((string) $row['amount'])];
            }
        }

        return $out;
    }

    private function parseMeta(): array
    {
        $m = $this->pick($this->request->getPost() ?: [], ['terms', 'payment_terms', 'delivery_estimate', 'valid_until', 'customer_remarks', 'internal_remarks', 'target_total', 'change_note']);
        foreach (['valid_until', 'target_total', 'delivery_estimate'] as $k) {
            if (($m[$k] ?? '') === '') {
                unset($m[$k]);
            }
        }

        return $m;
    }

    // ───────────── list ─────────────

    public function index(): string
    {
        $db = Crm::db();
        $g  = $this->request->getGet();
        $w  = ['1=1'];
        $b  = [];
        $ids = AccessScope::visibleUserIds($this->user());
        if ($ids !== null) {
            $in  = implode(',', array_map('intval', $ids));
            $w[] = "(e.current_owner_id IN ({$in}) OR e.created_by IN ({$in}))";
        }
        if (! empty($g['status'])) { $w[] = 'q.status = ?'; $b[] = $g['status']; }
        if (! empty($g['customer_id'])) { $w[] = 'q.customer_id = ?'; $b[] = (int) $g['customer_id']; }
        if (! empty($g['owner_id'])) { $w[] = 'e.current_owner_id = ?'; $b[] = (int) $g['owner_id']; }
        if (! empty($g['from'])) { $w[] = 'q.created_at >= ?'; $b[] = $g['from'] . ' 00:00:00'; }
        if (! empty($g['to'])) { $w[] = 'q.created_at <= ?'; $b[] = $g['to'] . ' 23:59:59'; }
        if (! empty($g['enquiry'])) { $w[] = 'e.enquiry_no LIKE ?'; $b[] = '%' . $g['enquiry'] . '%'; }
        if (! empty($g['q'])) { $w[] = '(q.quotation_no LIKE ? OR c.name LIKE ? OR c.customer_no LIKE ?)'; $b = array_merge($b, ['%' . $g['q'] . '%', '%' . $g['q'] . '%', '%' . $g['q'] . '%']); }
        $where = implode(' AND ', $w);
        $from  = 'FROM crm_quotations q JOIN crm_enquiries e ON e.id = q.enquiry_id JOIN crm_customers c ON c.id = q.customer_id JOIN crm_quotation_versions v ON v.id = q.current_version_id LEFT JOIN admin_users u ON u.id = e.current_owner_id';
        $sum   = $db->query("SELECT COUNT(*) n, COALESCE(SUM(v.grand_total),0) total {$from} WHERE {$where}", $b)->getRowArray();
        $page  = $this->page();
        $rows  = $db->query("SELECT q.id, q.quotation_no, q.status, q.created_at, q.responded_at, c.id customer_id, c.name customer_name, e.id enquiry_id, e.enquiry_no, e.project_name,
                             v.version_no, v.grand_total, v.discount_total, v.valid_until, v.is_locked, CONCAT(u.first_name,' ',u.last_name) owner_name
                             {$from} WHERE {$where} ORDER BY q.id DESC LIMIT " . self::PER . ' OFFSET ' . (($page - 1) * self::PER), $b)->getResultArray();
        $byStatus = $db->query("SELECT q.status, COUNT(*) n {$from} WHERE {$where} GROUP BY q.status", $b)->getResultArray();
        $owners   = $db->query("SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) name FROM crm_enquiries e JOIN admin_users u ON u.id = e.current_owner_id ORDER BY name")->getResultArray();
        if ($ids !== null) {
            $owners = array_values(array_filter($owners, static fn ($o) => in_array((int) $o['id'], $ids, true)));
        }

        return $this->render('quotations/index', ['rows' => $rows, 'sum' => $sum, 'byStatus' => array_column($byStatus, 'n', 'status'), 'owners' => $owners, 'g' => $g, 'page' => $page, 'per' => self::PER], 'Quotations', 'quotations');
    }

    // ───────────── create / edit draft ─────────────

    public function new()
    {
        $eid = (int) $this->request->getGet('enquiry');
        $db  = Crm::db();
        if (! $eid) {
            $b = $db->table('crm_enquiries e')->select('e.id, e.enquiry_no, e.project_name, e.status, c.name customer_name')->join('crm_customers c', 'c.id = e.customer_id')
                ->whereNotIn('e.status', ['new', 'assigned', 'won', 'lost', 'on_hold']);
            AccessScope::scopeEnquiries($b, $this->user(), 'e');

            return $this->render('quotations/pick', ['enquiries' => $b->orderBy('e.id', 'DESC')->limit(200)->get()->getResultArray()], 'New quotation', 'quotations');
        }
        if (! AccessScope::canSeeEnquiry($this->user(), $eid)) {
            throw PageNotFoundException::forPageNotFound();
        }
        $e    = $db->table('crm_enquiries e')->select('e.*, c.name customer_name, c.state customer_state')->join('crm_customers c', 'c.id = e.customer_id')->where('e.id', $eid)->get()->getRowArray();
        $meta = ['terms' => Settings::get('quotation.terms'), 'payment_terms' => Settings::get('quotation.payment_terms'), 'valid_until' => date('Y-m-d', strtotime('+' . (int) Settings::get('quotation.validity_days') . ' days'))];
        $lines = array_map(static fn ($l) => $l + ['line_total' => '', 'discount_pct' => '', 'tax_rate' => '', 'specifications' => ''], RequirementService::toQuotationLines($eid));

        return $this->render('quotations/form', $this->editorBase([
            'mode' => 'create', 'enquiry' => $e, 'quotation' => null, 'lines' => $lines, 'chargeRows' => [], 'meta' => $meta, 'action' => site_url('crm/quotations/create'),
            'negotiation' => null, 'hidden' => ['enquiry_id' => $eid], 'submit' => 'Save as draft quotation',
        ]), 'New quotation · ' . $e['enquiry_no'], 'quotations');
    }

    public function create()
    {
        $eid = (int) $this->request->getPost('enquiry_id');

        return $this->act(function () use ($eid) {
            $r = QuotationService::create($eid, $this->user(), $this->parseLines(), $this->parseCharges(), $this->parseMeta());

            return 'crm/quotations/' . $r['id'];
        }, 'Quotation draft saved. Review it, then send it to the customer.');
    }

    public function edit(int $id)
    {
        $q = $this->loadQ($id);
        $versions = QuotationService::versions($id, $this->user());
        $cur = $versions[0];
        if ((int) $cur['is_locked'] === 1) {
            return redirect()->to(site_url("crm/quotations/{$id}/revise"))->with('info', 'This version was already sent, so any change creates a new tracked version.');
        }
        $e = Crm::db()->table('crm_enquiries e')->select('e.*, c.name customer_name, c.state customer_state')->join('crm_customers c', 'c.id = e.customer_id')->where('e.id', $q['enquiry_id'])->get()->getRowArray();

        return $this->render('quotations/form', $this->editorBase([
            'mode' => 'edit', 'enquiry' => $e, 'quotation' => $q, 'lines' => $this->itemsToLines($cur['items']), 'chargeRows' => $cur['charges'], 'meta' => $cur, 'action' => site_url("crm/quotations/{$id}/update"),
            'negotiation' => null, 'hidden' => [], 'submit' => 'Save draft changes', 'version' => $cur,
        ]), 'Edit ' . $q['quotation_no'] . ' (draft v' . $cur['version_no'] . ')', 'quotations');
    }

    public function update(int $id)
    {
        $q = $this->loadQ($id);

        return $this->act(function () use ($q, $id) {
            QuotationService::updateDraft((int) $q['current_version_id'], $this->user(), $this->parseLines(), $this->parseCharges(), $this->parseMeta());

            return "crm/quotations/{$id}";
        }, 'Draft saved. The change is recorded in the version history.');
    }

    // ───────────── revise (new version) ─────────────

    public function reviseForm(int $id)
    {
        $q = $this->loadQ($id);
        if (in_array($q['status'], ['accepted', 'rejected', 'cancelled'], true)) {
            return redirect()->to(site_url("crm/quotations/{$id}"))->with('error', 'A closed quotation cannot be revised.');
        }
        $versions = QuotationService::versions($id, $this->user());
        $cur = $versions[0];
        if ((int) $cur['is_locked'] !== 1) {
            return redirect()->to(site_url("crm/quotations/{$id}/edit"));
        }
        $neg = null;
        $nid = (int) $this->request->getGet('negotiation');
        if ($nid) {
            $neg = Crm::db()->table('crm_negotiations')->where(['id' => $nid, 'quotation_id' => $id])->get()->getRowArray();
            if ($neg && $neg['status'] !== 'approved') {
                $neg = null;
            }
        }
        $e = Crm::db()->table('crm_enquiries e')->select('e.*, c.name customer_name, c.state customer_state')->join('crm_customers c', 'c.id = e.customer_id')->where('e.id', $q['enquiry_id'])->get()->getRowArray();

        return $this->render('quotations/form', $this->editorBase([
            'mode' => 'revise', 'enquiry' => $e, 'quotation' => $q, 'lines' => $this->itemsToLines($cur['items'], $neg ? (string) (float) $neg['requested_discount_pct'] : null), 'chargeRows' => $cur['charges'], 'meta' => $cur,
            'action' => site_url("crm/quotations/{$id}/revise"), 'negotiation' => $neg, 'hidden' => $neg ? ['negotiation_id' => $neg['id']] : [], 'submit' => 'Save as new version v' . ($cur['version_no'] + 1), 'version' => $cur,
            'approvedPct' => $neg ? (string) $neg['requested_discount_pct'] : QuotationService::approvedCeiling($id),
        ]), 'Revise ' . $q['quotation_no'] . ' → v' . ($cur['version_no'] + 1), 'quotations');
    }

    public function revise(int $id)
    {
        $this->loadQ($id);
        $nid = (int) $this->request->getPost('negotiation_id') ?: null;

        return $this->act(function () use ($id, $nid) {
            $r = QuotationService::revise($id, $this->user(), $this->parseLines(), $this->parseCharges(), $this->parseMeta(), $nid, trim((string) $this->request->getPost('reason')));

            return "crm/quotations/{$id}?v=" . $r['version_no'];
        }, 'New version created. The previous version and its PDF are preserved.');
    }

    // ───────────── live, server-verified totals (no save) ─────────────

    public function preview()
    {
        $id      = (int) $this->request->getPost('quotation_id');
        $ceiling = null;
        if ($id) {
            $this->loadQ($id);
            $nid     = (int) $this->request->getPost('negotiation_id') ?: null;
            $ceiling = QuotationService::approvedCeiling($id, $nid);
        }
        try {
            $meta = $this->parseMeta();
            $calc = QuotationService::price($this->parseLines(), $this->parseCharges(), $this->user(), $ceiling, $meta['target_total'] ?? null);
        } catch (CrmException $e) {
            return $this->json(['ok' => false, 'message' => $e->getMessage(), 'errors' => $e->errors, 'csrf' => csrf_hash()], 200);
        }
        $lines = [];
        foreach ($calc['lines'] as $i => $l) {
            $lines[] = $l + ['price_source' => $calc['meta'][$i]['price_source'], 'unit_price_fmt' => crm_money($l['unit_price']), 'line_total_fmt' => crm_money($l['line_total'])];
        }
        $t = [];
        foreach (['subtotal', 'discount_total', 'taxable_total', 'charges_total', 'tax_total', 'round_off', 'grand_total'] as $k) {
            $t[$k] = $calc[$k];
        }

        return $this->json(['ok' => true, 'lines' => $lines, 'totals' => $t, 'effective_discount_pct' => $calc['overall_discount_pct'], 'adjustment' => $calc['adjustment_amount'], 'target' => $calc['target_total'],
            'variance' => $calc['target_variance'] ?? null, 'csrf' => csrf_hash()]);
    }

    // ───────────── show / versions ─────────────

    public function show(int $id): string
    {
        $q        = $this->loadQ($id);
        $db       = Crm::db();
        $versions = QuotationService::versions($id, $this->user());
        $no       = (int) $this->request->getGet('v');
        $sel      = $versions[0];
        foreach ($versions as $v) {
            if ((int) $v['version_no'] === $no) {
                $sel = $v;
            }
        }
        $c = $db->table('crm_customers')->where('id', $q['customer_id'])->get()->getRowArray();
        $e = $db->table('crm_enquiries e')->select("e.*, CONCAT(u.first_name,' ',u.last_name) owner_name")->join('admin_users u', 'u.id = e.current_owner_id', 'left')->where('e.id', $q['enquiry_id'])->get()->getRowArray();
        $negs = $db->table('crm_negotiations n')->select("n.*, CONCAT(u.first_name,' ',u.last_name) requester, v.version_no from_version, rv.version_no result_version")->join('admin_users u', 'u.id = n.requested_by', 'left')
            ->join('crm_quotation_versions v', 'v.id = n.version_id', 'left')->join('crm_quotation_versions rv', 'rv.id = n.resulting_version_id', 'left')->where('n.quotation_id', $id)->orderBy('n.id', 'DESC')->get()->getResultArray();
        $order = $db->table('crm_sales_orders')->where('quotation_id', $id)->get()->getRowArray();
        $mails = $db->table('crm_email_log')->select('id, to_email, status, created_at, provider_response')->where('quotation_id', $id)->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();
        $cur   = $versions[0];
        $open  = array_values(array_filter($negs, static fn ($n) => in_array($n['status'], ['pending', 'escalated', 'approved'], true)));

        return $this->render('quotations/show', [
            'q' => $q, 'c' => $c, 'e' => $e, 'versions' => $versions, 'sel' => $sel, 'cur' => $cur, 'negs' => $negs, 'openNeg' => $open[0] ?? null, 'order' => $order, 'mails' => $mails,
            'intra' => strcasecmp(trim((string) $c['state']), Settings::get('company.state')) === 0 || trim((string) $c['state']) === '',
        ], $q['quotation_no'] . ' · v' . $sel['version_no'], 'quotations');
    }

    // ───────────── send / resend / response ─────────────

    public function send(int $id)
    {
        $this->loadQ($id);
        $to = trim((string) $this->request->getPost('to_email'));
        $ch = $this->request->getPost('channel') === 'manual' ? 'manual' : 'email';

        return $this->act(function () use ($id, $ch, $to) {
            QuotationService::send($id, $this->user(), $ch, $to !== '' ? $to : null);

            return "crm/quotations/{$id}";
        }, $ch === 'manual' ? 'Quotation locked and marked as handed over manually.' : 'Quotation locked, PDF generated and email logged (see the email log for the real delivery status).');
    }

    public function resend(int $id)
    {
        $this->loadQ($id);
        $to = trim((string) $this->request->getPost('to_email'));

        return $this->act(function () use ($id, $to) {
            QuotationService::resend($id, $this->user(), $to !== '' ? $to : null);

            return "crm/quotations/{$id}";
        }, 'Email re-queued. Check the email log for its status.');
    }

    public function response(int $id)
    {
        $this->loadQ($id);

        return $this->act(function () use ($id) {
            QuotationService::recordResponse($id, $this->user(), (string) $this->request->getPost('response'), trim((string) $this->request->getPost('remarks')) ?: null);

            return "crm/quotations/{$id}";
        }, 'Customer response recorded.');
    }

    // ───────────── compare · PDF · HTML preview ─────────────

    public function compare(int $id): string
    {
        $q  = $this->loadQ($id);
        $vs = QuotationService::versions($id, $this->user());
        if (count($vs) < 2) {
            throw PageNotFoundException::forPageNotFound();
        }
        $b = (int) ($this->request->getGet('b') ?: $vs[0]['version_no']);
        $a = (int) ($this->request->getGet('a') ?: ($b - 1));
        try {
            $cmp = QuotationService::compare($id, $a, $b, $this->user());
        } catch (CrmException) {
            throw PageNotFoundException::forPageNotFound();
        }
        $byNo = array_column($vs, null, 'version_no');

        return $this->render('quotations/compare', ['q' => $q, 'cmp' => $cmp, 'va' => $byNo[$a], 'vb' => $byNo[$b], 'versions' => $vs], 'Compare ' . $q['quotation_no'] . ' v' . $a . ' ↔ v' . $b, 'quotations');
    }

    private function versionRow(int $id, int $no): array
    {
        $this->loadQ($id);

        return Crm::db()->table('crm_quotation_versions')->where(['quotation_id' => $id, 'version_no' => $no])->get()->getRowArray() ?? throw PageNotFoundException::forPageNotFound();
    }

    public function pdf(int $id, int $no)
    {
        $q = $this->loadQ($id);
        $v = $this->versionRow($id, $no);
        if ($v['pdf_path']) {
            $real = PdfService::resolve((string) $v['pdf_path']);
            if (! $real) {
                return redirect()->to(site_url("crm/quotations/{$id}"))->with('error', 'The stored PDF for this version is missing on the server. Contact an administrator (it is never silently regenerated).');
            }
            $bytes = (string) file_get_contents($real);
            if ($v['pdf_sha256'] && ! hash_equals((string) $v['pdf_sha256'], hash('sha256', $bytes))) {
                return redirect()->to(site_url("crm/quotations/{$id}"))->with('error', 'The stored PDF failed its integrity check and was not served.');
            }
        } else {
            $bytes = PdfService::quotationBytes((int) $v['id']);   // draft: rendered on demand, not stored
        }

        return $this->response->setHeader('Content-Type', 'application/pdf')->setHeader('Content-Disposition', 'inline; filename="' . $q['quotation_no'] . '-V' . $no . ($v['pdf_path'] ? '' : '-DRAFT') . '.pdf"')
            ->setHeader('X-Content-Type-Options', 'nosniff')->setBody($bytes);
    }

    public function previewVersion(int $id, int $no)
    {
        $v = $this->versionRow($id, $no);

        return $this->response->setHeader('X-Content-Type-Options', 'nosniff')->setBody(PdfService::quotationHtml((int) $v['id'])['html']);
    }
}
