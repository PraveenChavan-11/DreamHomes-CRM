<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\NegotiationService;
use Crm\Services\QuotationService;
use Crm\Services\Settings;

/** Negotiation & discount approval workflow. Authority, self-approval and scope rules live in NegotiationService; this layer only presents and posts. */
class NegotiationController extends CrmController
{
    use QuotationEditorTrait;

    private const PER = 25;

    private function scopeSql(): string
    {
        $ids = AccessScope::visibleUserIds($this->user());
        if ($ids === null) {
            return '1=1';
        }
        $in = implode(',', array_map('intval', $ids));

        return "(e.current_owner_id IN ({$in}) OR e.created_by IN ({$in}))";
    }

    /** @return array<string,mixed> negotiation row the user may see; 404 otherwise */
    private function loadN(int $id): array
    {
        $n = Crm::db()->table('crm_negotiations')->where('id', $id)->get()->getRowArray() ?? throw PageNotFoundException::forPageNotFound();
        try {
            QuotationService::loadQuotation((int) $n['quotation_id'], $this->user());
        } catch (CrmException) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $n;
    }

    /** Can this user decide this request right now (mirrors NegotiationService::decide; the service remains authoritative)? */
    private function canDecide(array $n): bool
    {
        $u = $this->user();
        if (! in_array($n['status'], ['pending', 'escalated'], true)) {
            return false;
        }
        $isAdmin = AccessScope::can($u, 'discounts.approve_admin');
        $isMgr   = AccessScope::can($u, 'discounts.approve_manager');
        if ($n['required_level'] === 'admin' ? ! $isAdmin : ! ($isMgr || $isAdmin)) {
            return false;
        }

        return (int) $n['requested_by'] !== (int) ($u['id'] ?? 0) || Settings::get('discount.allow_self_approval') === '1';
    }

    // ───────────── list ─────────────

    public function index(): string
    {
        $db   = Crm::db();
        $u    = $this->user();
        $g    = $this->request->getGet();
        $isApprover = AccessScope::can($u, 'discounts.approve_manager') || AccessScope::can($u, 'discounts.approve_admin');
        $tab  = in_array($g['tab'] ?? '', ['pending', 'mine', 'all'], true) ? $g['tab'] : ($isApprover ? 'pending' : 'mine');
        $from = 'FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id JOIN crm_customers c ON c.id = q.customer_id
                 JOIN admin_users u ON u.id = n.requested_by JOIN crm_quotation_versions v ON v.id = n.version_id';
        $base = [$this->scopeSql()];
        $bb   = [];
        $tabWhere = static function (string $t) use ($u): array {
            $self = Settings::get('discount.allow_self_approval') === '1';
            if ($t === 'pending') {
                $lvl = AccessScope::can($u, 'discounts.approve_admin') ? "n.required_level IN ('manager','admin')" : (AccessScope::can($u, 'discounts.approve_manager') ? "n.required_level = 'manager'" : '1=0');

                return ["n.status IN ('pending','escalated')", $lvl] + ($self ? [] : [2 => 'n.requested_by <> ' . (int) $u['id']]);
            }
            if ($t === 'mine') {
                return ['n.requested_by = ' . (int) $u['id']];
            }

            return [];
        };
        $counts = [];
        foreach (['pending', 'mine', 'all'] as $t) {
            $counts[$t] = (int) $db->query('SELECT COUNT(*) c ' . $from . ' WHERE ' . implode(' AND ', array_merge($base, $tabWhere($t))))->getRow()->c;
        }
        $w = array_merge($base, $tabWhere($tab));
        if (! empty($g['status'])) { $w[] = 'n.status = ?'; $bb[] = $g['status']; }
        if (! empty($g['q'])) { $w[] = '(n.negotiation_no LIKE ? OR q.quotation_no LIKE ? OR c.name LIKE ?)'; $bb = array_merge($bb, ['%' . $g['q'] . '%', '%' . $g['q'] . '%', '%' . $g['q'] . '%']); }
        if (! empty($g['from'])) { $w[] = 'n.created_at >= ?'; $bb[] = $g['from'] . ' 00:00:00'; }
        if (! empty($g['to'])) { $w[] = 'n.created_at <= ?'; $bb[] = $g['to'] . ' 23:59:59'; }
        $where = implode(' AND ', $w);
        $total = (int) $db->query("SELECT COUNT(*) c {$from} WHERE {$where}", $bb)->getRow()->c;
        $page  = $this->page();
        $rows  = $db->query("SELECT n.*, q.quotation_no, q.id qid, c.id customer_id, c.name customer_name, CONCAT(u.first_name,' ',u.last_name) requester, v.version_no, v.grand_total original_total
                             {$from} WHERE {$where} ORDER BY n.id DESC LIMIT " . self::PER . ' OFFSET ' . (($page - 1) * self::PER), $bb)->getResultArray();

        return $this->render('negotiations/index', ['rows' => $rows, 'tab' => $tab, 'counts' => $counts, 'g' => $g, 'total' => $total, 'page' => $page, 'per' => self::PER], 'Negotiations & discount approvals', 'negotiations');
    }

    // ───────────── request / decide / resubmit ─────────────

    public function request()
    {
        $qid = (int) $this->request->getPost('quotation_id');

        return $this->act(function () use ($qid) {
            $q   = QuotationService::loadQuotation($qid, $this->user());
            $pct = trim((string) $this->request->getPost('requested_discount_pct'));
            $exp = trim((string) $this->request->getPost('customer_expected_amount'));
            if ($pct === '' && $exp !== '') {
                $pct = NegotiationService::pctForTarget((int) $q['current_version_id'], $exp);
            }
            if ($pct === '') {
                throw CrmException::validation(['requested_discount_pct' => 'Enter the requested discount % or the amount the customer expects.']);
            }
            $r = NegotiationService::request($qid, $this->user(), $pct, trim((string) $this->request->getPost('reason')), $exp !== '' ? $exp : null);

            return 'crm/negotiations/' . $r['id'];
        }, 'Negotiation recorded. See its status and the approval trail below.');
    }

    public function decide(int $id)
    {
        $this->loadN($id);
        $d = (string) $this->request->getPost('decision');

        return $this->act(function () use ($id, $d) {
            NegotiationService::decide($id, $this->user(), $d, trim((string) $this->request->getPost('remarks')) ?: null);

            return "crm/negotiations/{$id}";
        }, 'Decision recorded: ' . $d . '.');
    }

    public function resubmit(int $id)
    {
        $this->loadN($id);

        return $this->act(function () use ($id) {
            $r = NegotiationService::resubmit($id, $this->user(), trim((string) $this->request->getPost('requested_discount_pct')), trim((string) $this->request->getPost('reason')));

            return 'crm/negotiations/' . $r['id'];
        }, 'Request resubmitted as a new negotiation round.');
    }

    // ───────────── detail ─────────────

    public function show(int $id): string
    {
        $n  = $this->loadN($id);
        $db = Crm::db();
        $u  = $this->user();
        $names = \Crm\Services\HistoryService::userNames();
        $q  = $db->table('crm_quotations')->where('id', $n['quotation_id'])->get()->getRowArray();
        $c  = $db->table('crm_customers')->where('id', $q['customer_id'])->get()->getRowArray();
        $e  = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
        $versions = QuotationService::versions((int) $q['id'], $u);
        $byId     = array_column($versions, null, 'id');
        $orig     = $byId[(int) $n['version_id']];
        $cur      = $versions[0];
        $result   = $n['resulting_version_id'] ? ($byId[(int) $n['resulting_version_id']] ?? null) : null;
        $approvals = array_map(static fn ($a) => $a + ['approver_name' => $a['approver_id'] ? ($names[(int) $a['approver_id']] ?? '?') : null],
            $db->table('crm_discount_approvals')->where('negotiation_id', $id)->orderBy('id')->get()->getResultArray());
        $rounds = array_map(static fn ($r) => $r + ['requester' => $names[(int) $r['requested_by']] ?? '?', 'from_no' => $byId[(int) $r['version_id']]['version_no'] ?? null, 'to_no' => $r['resulting_version_id'] ? ($byId[(int) $r['resulting_version_id']]['version_no'] ?? null) : null],
            $db->table('crm_negotiations')->where('quotation_id', $q['id'])->orderBy('id')->get()->getResultArray());

        $editor = null;
        $canRevise = crm_can('quotations.create') && (int) $cur['is_locked'] === 1 && in_array($q['status'], ['sent', 'negotiation'], true);
        if ($n['status'] === 'approved' && $canRevise) {
            $editor = $this->editorBase([
                'mode' => 'revise', 'quotation' => $q, 'negotiation' => $n, 'lines' => $this->itemsToLines($cur['items'], (string) (float) $n['requested_discount_pct']), 'chargeRows' => $cur['charges'], 'meta' => $cur,
                'action' => site_url('crm/quotations/' . $q['id'] . '/revise'), 'hidden' => ['negotiation_id' => $n['id']], 'submit' => 'Create revised quotation v' . ($cur['version_no'] + 1), 'approvedPct' => (string) $n['requested_discount_pct'], 'formId' => 'qform',
            ]);
        }

        return $this->render('negotiations/show', [
            'n' => $n, 'q' => $q, 'c' => $c, 'e' => $e, 'orig' => $orig, 'cur' => $cur, 'result' => $result, 'approvals' => $approvals, 'rounds' => $rounds, 'editor' => $editor, 'canRevise' => $canRevise,
            'canDecide' => $this->canDecide($n), 'requester' => $names[(int) $n['requested_by']] ?? '?', 'limit' => AccessScope::can($u, 'discounts.approve_admin') ? Settings::get('discount.limit.admin_pct') : (string) ($u['max_discount_pct'] ?: Settings::get('discount.limit.manager_pct')),
            'estimate' => NegotiationService::estimate((int) $n['version_id'], (string) $n['requested_discount_pct']),
        ], $n['negotiation_no'], 'negotiations');
    }
}
