<?php

namespace Crm\Services;

/**
 * Discount negotiation + approval. requested_discount_pct is the TOTAL discount % off the quotation subtotal being asked for.
 * Authority levels come from settings: personal limit (auto) → manager limit → admin. Self-approval is refused unless the policy flag allows it.
 */
final class NegotiationService
{
    public static function levelFor(string $pct, array $requester): string
    {
        if (bccomp($pct, (string) ($requester['max_discount_pct'] ?? '0'), 3) <= 0 && ! in_array('management', $requester['roles'] ?? [], true)) {
            return 'self';
        }

        return bccomp($pct, Settings::get('discount.limit.manager_pct'), 3) <= 0 ? 'manager' : 'admin';
    }

    /** Estimated grand total if $pct were applied uniformly to every line of the version (uses the real calculator). */
    public static function estimate(int $versionId, string $pct): string
    {
        $db    = Crm::db();
        $items = $db->table('crm_quotation_items')->where('version_id', $versionId)->get()->getResultArray();
        $chg   = $db->table('crm_quotation_charges')->where('version_id', $versionId)->get()->getResultArray();
        $lines = array_map(static fn ($i) => ['quantity' => $i['quantity'], 'unit_price' => $i['unit_price'], 'discount_pct' => $pct, 'tax_rate' => $i['tax_rate']], $items);

        return QuotationCalculator::compute($lines, array_map(static fn ($c) => ['name' => $c['name'], 'amount' => $c['amount'], 'tax_rate' => $c['tax_rate']], $chg), Settings::get('quotation.round_off') === '1')['grand_total'];
    }

    /** Uniform discount % (3 dp) that brings the version's estimated grand total closest to the customer's expected amount. */
    public static function pctForTarget(int $versionId, string $target): string
    {
        if (! is_numeric($target) || bccomp($target, '0', 2) <= 0) {
            throw CrmException::validation(['customer_expected_amount' => 'Expected amount must be a positive number.']);
        }
        $lo = '0';
        $hi = '100';
        for ($i = 0; $i < 30; $i++) {
            $mid = bcdiv(bcadd($lo, $hi, 6), '2', 6);
            if (bccomp(self::estimate($versionId, $mid), $target, 2) >= 0) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return Money::round($lo, 3);
    }

    public static function request(int $quotationId, array $actor, string $requestedPct, ?string $reason, ?string $customerExpected = null, ?int $resubmissionOf = null, int $isDemo = 0): array
    {
        if (! AccessScope::can($actor, 'negotiations.create')) {
            throw new CrmException('You do not have permission to raise negotiation requests.');
        }
        if (trim((string) $reason) === '') {
            throw CrmException::validation(['reason' => 'A reason is required.']);
        }
        if (! is_numeric($requestedPct) || bccomp($requestedPct, '0', 3) <= 0 || bccomp($requestedPct, '100', 3) > 0) {
            throw CrmException::validation(['requested_discount_pct' => 'Discount % must be between 0 and 100.']);
        }

        return Crm::tx(static function ($db) use ($quotationId, $actor, $requestedPct, $reason, $customerExpected, $resubmissionOf, $isDemo) {
            $q = QuotationService::loadQuotation($quotationId, $actor);
            $q = $db->query('SELECT * FROM crm_quotations WHERE id = ? FOR UPDATE', [$quotationId])->getRowArray();
            if (! in_array($q['status'], ['sent', 'negotiation'], true)) {
                throw new CrmException('Negotiation can only start on a sent quotation (status: ' . $q['status'] . ').');
            }
            if ($db->table('crm_negotiations')->where('quotation_id', $quotationId)->whereIn('status', ['pending', 'escalated', 'approved'])->countAllResults() > 0) {
                throw new CrmException('There is already an open negotiation on this quotation. Resolve or apply it first.');
            }
            $v = $db->table('crm_quotation_versions')->where('id', $q['current_version_id'])->get()->getRowArray();
            $pct = Money::round($requestedPct, 3);
            $level = self::levelFor($pct, $actor);
            $amount = Money::pct($v['subtotal'], $pct);
            $proposed = self::estimate((int) $v['id'], $pct);
            $no = NumberSequenceService::next('negotiation');
            $status = $level === 'self' ? 'approved' : 'pending';
            $db->table('crm_negotiations')->insert([
                'negotiation_no' => $no, 'quotation_id' => $quotationId, 'version_id' => $v['id'], 'customer_expected_amount' => $customerExpected !== null && $customerExpected !== '' ? $customerExpected : null,
                'requested_discount_amount' => $amount, 'requested_discount_pct' => $pct, 'reason' => $reason, 'proposed_price' => $proposed, 'requested_by' => $actor['id'], 'status' => $status,
                'required_level' => $level, 'resubmission_of_id' => $resubmissionOf, 'is_demo' => $isDemo,
            ]);
            $nid = (int) $db->insertID();
            $ctx = ['customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $no, 'is_demo' => $isDemo];
            AuditService::workflow('negotiation.requested', 'negotiation', $nid, $ctx + ['user_id' => $actor['id'], 'new' => ['quotation' => $q['quotation_no'], 'pct' => $pct, 'proposed_price' => $proposed, 'level' => $level], 'reason' => $reason]);
            $db->table('crm_quotations')->where('id', $quotationId)->update(['status' => 'negotiation']);
            $e = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
            if ($e['status'] === 'quotation_sent') {
                WorkflowService::transition((int) $e['id'], 'negotiation', $actor, "Customer requested a price reduction on {$q['quotation_no']}");
            }
            if ($level === 'self') {
                $db->table('crm_discount_approvals')->insert(['negotiation_id' => $nid, 'level' => 0, 'required_role' => 'self', 'approver_id' => $actor['id'], 'decision' => 'approved', 'remarks' => 'Within personal discount limit', 'decided_at' => Crm::now()]);
                AuditService::workflow('discount.auto_approved', 'negotiation', $nid, $ctx + ['user_id' => $actor['id'], 'new' => ['pct' => $pct]]);
            } else {
                $role = $level === 'manager' ? 'sales_manager' : 'admin';
                $db->table('crm_discount_approvals')->insert(['negotiation_id' => $nid, 'level' => 1, 'required_role' => $role, 'decision' => 'pending']);
                AuditService::workflow('discount.requested', 'negotiation', $nid, $ctx + ['user_id' => $actor['id'], 'new' => ['pct' => $pct, 'required_role' => $role]]);
                self::notifyApprovers($level, $e, $no, $pct, $isDemo);
            }

            return ['id' => $nid, 'negotiation_no' => $no, 'status' => $status, 'required_level' => $level, 'proposed_price' => $proposed];
        });
    }

    private static function notifyApprovers(string $level, array $enq, string $no, string $pct, int $isDemo): void
    {
        $db = Crm::db();
        $ids = [];
        if ($level === 'manager' && $enq['current_owner_id']) {
            foreach ($db->query('SELECT DISTINCT t.manager_id FROM crm_team_members tm JOIN crm_teams t ON t.id = tm.team_id WHERE tm.user_id = ? AND t.manager_id IS NOT NULL', [$enq['current_owner_id']])->getResultArray() as $r) {
                $ids[] = (int) $r['manager_id'];
            }
        }
        foreach ($ids as $id) {
            NotificationService::notify($id, 'discount.requested', "Discount approval needed: {$no} ({$pct}%)", $enq['enquiry_no'], 'crm/negotiations', "disc-req:{$no}:{$id}", $isDemo);
        }
        if ($level === 'admin' || ! $ids) {
            NotificationService::notifyRoles(['admin'], 'discount.requested', "Discount approval needed: {$no} ({$pct}%)", $enq['enquiry_no'], 'crm/negotiations', "disc-req-adm:{$no}");
        }
    }

    /** @param 'approve'|'reject'|'return'|'escalate' $decision */
    public static function decide(int $negotiationId, array $approver, string $decision, ?string $remarks = null): void
    {
        if (! in_array($decision, ['approve', 'reject', 'return', 'escalate'], true)) {
            throw new CrmException('Unknown decision.');
        }
        if ($decision !== 'approve' && trim((string) $remarks) === '') {
            throw CrmException::validation(['remarks' => 'Remarks are required for this decision.']);
        }
        Crm::tx(static function ($db) use ($negotiationId, $approver, $decision, $remarks) {
            $n = $db->query('SELECT * FROM crm_negotiations WHERE id = ? FOR UPDATE', [$negotiationId])->getRowArray() ?? throw new CrmException('Negotiation not found.');
            if (! in_array($n['status'], ['pending', 'escalated'], true)) {
                throw new CrmException('This request is no longer awaiting a decision (status: ' . $n['status'] . ').');
            }
            $q = $db->table('crm_quotations')->where('id', $n['quotation_id'])->get()->getRowArray();
            $level = $n['required_level'];
            $isAdmin = AccessScope::can($approver, 'discounts.approve_admin');
            $isMgr = AccessScope::can($approver, 'discounts.approve_manager');
            if ($level === 'admin' ? ! $isAdmin : ! ($isMgr || $isAdmin)) {
                throw new CrmException('You are not authorised to decide this request.');
            }
            if ((int) $n['requested_by'] === (int) $approver['id'] && Settings::get('discount.allow_self_approval') !== '1') {
                throw new CrmException('You cannot decide your own request.');
            }
            if (! AccessScope::canSeeEnquiry($approver, (int) $q['enquiry_id'])) {
                throw new CrmException('This request is outside your team.');
            }
            $limit = $isAdmin ? Settings::get('discount.limit.admin_pct') : (string) ($approver['max_discount_pct'] ?: Settings::get('discount.limit.manager_pct'));
            if ($decision === 'approve' && bccomp((string) $n['requested_discount_pct'], $limit, 3) > 0) {
                throw new CrmException("Requested {$n['requested_discount_pct']}% exceeds your approval limit of {$limit}%. Escalate it.");
            }
            $pending = $db->table('crm_discount_approvals')->where(['negotiation_id' => $negotiationId, 'decision' => 'pending'])->orderBy('id', 'DESC')->get()->getRowArray();
            $map = ['approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned', 'escalate' => 'escalated'];
            if ($pending) {
                $db->table('crm_discount_approvals')->where('id', $pending['id'])->update(['approver_id' => $approver['id'], 'decision' => $map[$decision], 'remarks' => $remarks ? mb_substr($remarks, 0, 500) : null, 'decided_at' => Crm::now()]);
            }
            $newStatus = ['approve' => 'approved', 'reject' => 'rejected', 'return' => 'returned', 'escalate' => 'escalated'][$decision];
            $upd = ['status' => $newStatus];
            if ($decision === 'escalate') {
                $upd['required_level'] = 'admin';
                $db->table('crm_discount_approvals')->insert(['negotiation_id' => $negotiationId, 'level' => 2, 'required_role' => 'admin', 'decision' => 'pending']);
                $e = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
                NotificationService::notifyRoles(['admin'], 'discount.escalated', "Escalated discount request {$n['negotiation_no']}", $e['enquiry_no'], 'crm/negotiations', "disc-esc:{$n['negotiation_no']}");
            }
            $db->table('crm_negotiations')->where('id', $negotiationId)->update($upd);
            // A rejected/returned request puts the quotation back to 'sent' so the customer can still accept the original terms.
            if (in_array($decision, ['reject', 'return'], true)) {
                $db->table('crm_quotations')->where('id', $n['quotation_id'])->update(['status' => 'sent']);
            }
            AuditService::workflow('discount.' . $newStatus, 'negotiation', $negotiationId, [
                'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $n['negotiation_no'], 'user_id' => $approver['id'], 'is_demo' => (int) $n['is_demo'],
                'prev' => ['status' => $n['status']], 'new' => ['status' => $newStatus, 'pct' => $n['requested_discount_pct']], 'reason' => $remarks,
            ]);
            NotificationService::notify((int) $n['requested_by'], 'discount.' . $newStatus, "Discount request {$n['negotiation_no']} {$newStatus}", $remarks, "crm/quotations/{$n['quotation_id']}", "disc-dec:{$n['negotiation_no']}:{$newStatus}", (int) $n['is_demo']);
        });
    }

    /** After a 'returned' decision the requester may resubmit with a revised ask; creates a linked new request. */
    public static function resubmit(int $negotiationId, array $actor, string $newPct, string $reason): array
    {
        $n = Crm::db()->table('crm_negotiations')->where('id', $negotiationId)->get()->getRowArray() ?? throw new CrmException('Negotiation not found.');
        if ($n['status'] !== 'returned' || (int) $n['requested_by'] !== (int) $actor['id']) {
            throw new CrmException('Only the requester can resubmit a returned request.');
        }

        return self::request((int) $n['quotation_id'], $actor, $newPct, $reason, null, $negotiationId, (int) $n['is_demo']);
    }
}
