<?php

namespace Crm\Services;

/** Sales orders (from accepted quotations) and controlled enquiry closure (won / lost / hold / reopen). */
final class OrderService
{
    public const ORDER_FLOW = [
        'draft'                => ['pending_confirmation', 'cancelled'],
        'pending_confirmation' => ['confirmed', 'on_hold', 'cancelled'],
        'confirmed'            => ['on_hold', 'closed', 'cancelled'],
        'on_hold'              => ['confirmed', 'cancelled'],
        'closed'               => [],
        'cancelled'            => [],
    ];

    /** Convert an ACCEPTED quotation into a sales order. Final values are snapshotted from the accepted version. */
    public static function createFromQuotation(int $quotationId, array $actor, array $o = []): array
    {
        if (! AccessScope::can($actor, 'orders.manage')) {
            throw new CrmException('You do not have permission to create sales orders.');
        }

        return Crm::tx(static function ($db) use ($quotationId, $actor, $o) {
            $q = QuotationService::loadQuotation($quotationId, $actor);
            $q = $db->query('SELECT * FROM crm_quotations WHERE id = ? FOR UPDATE', [$quotationId])->getRowArray();
            if ($q['status'] !== 'accepted') {
                throw new CrmException('Only an accepted quotation can be converted to a sales order (status: ' . $q['status'] . ').');
            }
            if ($db->table('crm_sales_orders')->where('quotation_id', $quotationId)->countAllResults() > 0) {
                throw new CrmException('This quotation has already been converted to a sales order.');
            }
            $v = $db->table('crm_quotation_versions')->where('id', $q['current_version_id'])->get()->getRowArray();
            $e = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
            $advance = Money::round(Money::norm($o['advance_amount'] ?? 0));
            if (Money::cmp($advance, $v['grand_total']) > 0) {
                throw CrmException::validation(['advance_amount' => 'Advance cannot exceed the order total.']);
            }
            $no = NumberSequenceService::next('order');
            $confirm = ! empty($o['confirm']);
            $at = $o['at'] ?? Crm::now();
            $db->table('crm_sales_orders')->insert([
                'order_no' => $no, 'quotation_id' => $quotationId, 'version_id' => $v['id'], 'customer_id' => $q['customer_id'], 'enquiry_id' => $q['enquiry_id'], 'salesperson_id' => $e['current_owner_id'],
                'status' => $confirm ? 'confirmed' : 'pending_confirmation', 'confirmed_at' => $confirm ? $at : null, 'advance_amount' => $advance, 'advance_reference' => $o['advance_reference'] ?? null,
                'expected_delivery' => $o['expected_delivery'] ?? null, 'subtotal' => $v['subtotal'], 'discount_total' => $v['discount_total'], 'tax_total' => $v['tax_total'], 'charges_total' => $v['charges_total'],
                'grand_total' => $v['grand_total'], 'remarks' => $o['remarks'] ?? null, 'created_by' => $actor['id'], 'is_demo' => (int) $q['is_demo'], 'created_at' => $at,
            ]);
            $oid = (int) $db->insertID();
            foreach ($db->table('crm_quotation_items')->where('version_id', $v['id'])->get()->getResultArray() as $i) {
                $db->table('crm_sales_order_items')->insert([
                    'order_id' => $oid, 'product_id' => $i['product_id'], 'sku' => $i['sku'], 'description' => $i['description'], 'quantity' => $i['quantity'], 'unit' => $i['unit'],
                    'unit_price' => $i['unit_price'], 'discount_amount' => $i['discount_amount'], 'tax_amount' => $i['tax_amount'], 'line_total' => $i['line_total'],
                ]);
            }
            if ((int) $q['is_demo']) {
                $db->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', ['crm_sales_orders', $oid]);
            }
            AuditService::workflow('order.created', 'sales_order', $oid, [
                'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $no, 'user_id' => $actor['id'], 'is_demo' => (int) $q['is_demo'], 'created_at' => $at,
                'new' => ['quotation' => $q['quotation_no'], 'version' => (int) $v['version_no'], 'grand_total' => $v['grand_total'], 'status' => $confirm ? 'confirmed' : 'pending_confirmation'],
            ]);
            if ($e['current_owner_id']) {
                NotificationService::notify((int) $e['current_owner_id'], 'order.created', "Sales order {$no} created", $q['quotation_no'], "crm/orders/{$oid}", "order-new:{$oid}", (int) $q['is_demo']);
            }

            return ['id' => $oid, 'order_no' => $no, 'grand_total' => $v['grand_total']];
        });
    }

    public static function setStatus(int $orderId, string $to, array $actor, ?string $reason = null): void
    {
        if (! AccessScope::can($actor, 'orders.manage')) {
            throw new CrmException('You do not have permission to manage sales orders.');
        }
        Crm::tx(static function ($db) use ($orderId, $to, $actor, $reason) {
            $o = $db->query('SELECT * FROM crm_sales_orders WHERE id = ? FOR UPDATE', [$orderId])->getRowArray() ?? throw new CrmException('Order not found.');
            if (! AccessScope::canSeeEnquiry($actor, (int) $o['enquiry_id'])) {
                throw new CrmException('You do not have access to this order.');
            }
            if (! in_array($to, self::ORDER_FLOW[$o['status']] ?? [], true)) {
                throw new CrmException("Order status change {$o['status']} → {$to} is not allowed.");
            }
            if (in_array($to, ['cancelled', 'on_hold'], true) && trim((string) $reason) === '') {
                throw CrmException::validation(['reason' => 'A reason is required.']);
            }
            $upd = ['status' => $to];
            if ($to === 'confirmed' && ! $o['confirmed_at']) {
                $upd['confirmed_at'] = Crm::now();
            }
            $db->table('crm_sales_orders')->where('id', $orderId)->update($upd);
            AuditService::workflow('order.' . $to, 'sales_order', $orderId, [
                'customer_id' => (int) $o['customer_id'], 'enquiry_id' => (int) $o['enquiry_id'], 'entity_ref' => $o['order_no'], 'user_id' => $actor['id'], 'is_demo' => (int) $o['is_demo'],
                'prev' => ['status' => $o['status']], 'new' => ['status' => $to], 'reason' => $reason,
            ]);
        });
    }

    // ───────────── Closure ─────────────

    /** WON: requires accepted quotation + existing sales order (or admin-level exception with reason). */
    public static function markWon(int $enquiryId, array $actor, ?string $exceptionReason = null, ?string $at = null): void
    {
        Crm::tx(static function ($db) use ($enquiryId, $actor, $exceptionReason, $at) {
            $e = $db->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
            $order = $db->table('crm_sales_orders')->where('enquiry_id', $enquiryId)->whereNotIn('status', ['cancelled'])->get()->getRowArray();
            $final = null;
            if ($order) {
                $final = $order['grand_total'];
            } else {
                if (trim((string) $exceptionReason) === '' || ! AccessScope::can($actor, 'discounts.approve_admin')) {
                    throw new CrmException('Won requires an accepted quotation converted to a sales order (admin exception with a reason is the only alternative).');
                }
            }
            WorkflowService::transition($enquiryId, 'won', $actor, $exceptionReason ?: 'Customer accepted quotation', [
                'outcome' => 'won', 'closed_at' => $at ?? Crm::now(), 'closed_by' => $actor['id'], 'final_value' => $final, 'close_remarks' => $exceptionReason,
            ]);
            AuditService::workflow('enquiry.won', 'enquiry', $enquiryId, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'], 'is_demo' => (int) $e['is_demo'], 'created_at' => $at ?? Crm::now(),
                'new' => ['final_value' => $final, 'order' => $order['order_no'] ?? null, 'exception' => $exceptionReason],
            ]);
        });
    }

    public static function markLost(int $enquiryId, array $actor, int $lostReasonId, ?string $remarks = null, ?string $competitor = null, ?string $at = null): void
    {
        if (! $lostReasonId || ! Crm::db()->table('crm_lost_reasons')->where('id', $lostReasonId)->countAllResults()) {
            throw CrmException::validation(['lost_reason_id' => 'A lost reason is required.']);
        }
        Crm::tx(static function ($db) use ($enquiryId, $actor, $lostReasonId, $remarks, $competitor, $at) {
            $e = $db->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
            $reason = $db->table('crm_lost_reasons')->where('id', $lostReasonId)->get()->getRow()->name;
            WorkflowService::transition($enquiryId, 'lost', $actor, $reason . ($remarks ? ": {$remarks}" : ''), [
                'outcome' => 'lost', 'closed_at' => $at ?? Crm::now(), 'closed_by' => $actor['id'], 'lost_reason_id' => $lostReasonId, 'lost_competitor' => $competitor, 'close_remarks' => $remarks,
            ]);
            $db->table('crm_quotations')->where('enquiry_id', $enquiryId)->whereIn('status', ['draft', 'sent', 'negotiation', 'revised'])->update(['status' => 'cancelled']);
            $db->query("UPDATE crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id SET n.status = 'withdrawn' WHERE q.enquiry_id = ? AND n.status IN ('pending','escalated','approved','returned')", [$enquiryId]);
            AuditService::workflow('enquiry.lost', 'enquiry', $enquiryId, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'], 'is_demo' => (int) $e['is_demo'], 'created_at' => $at ?? Crm::now(),
                'new' => ['reason' => $reason, 'competitor' => $competitor], 'reason' => $remarks,
            ]);
            if ($e['current_owner_id'] && (int) $e['current_owner_id'] !== (int) $actor['id']) {
                NotificationService::notify((int) $e['current_owner_id'], 'enquiry.lost', "Enquiry {$e['enquiry_no']} marked lost", $reason, "crm/enquiries/{$enquiryId}", "lost:{$enquiryId}", (int) $e['is_demo']);
            }
        });
    }

    public static function putOnHold(int $enquiryId, array $actor, string $reason, string $reviewDate): void
    {
        if (strtotime($reviewDate) === false || strtotime($reviewDate) < strtotime('today')) {
            throw CrmException::validation(['hold_review_date' => 'Choose a future review date.']);
        }
        WorkflowService::transition($enquiryId, 'on_hold', $actor, $reason, ['outcome' => 'on_hold', 'hold_review_date' => date('Y-m-d', strtotime($reviewDate))]);
    }

    public static function reopen(int $enquiryId, array $actor, string $reason): void
    {
        Crm::tx(static function ($db) use ($enquiryId, $actor, $reason) {
            $e = $db->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
            if (! in_array($e['status'], ['lost', 'won'], true)) {
                throw new CrmException('Only won or lost enquiries can be reopened.');
            }
            $to = $e['status'] === 'lost' ? 'contacted' : 'negotiation';
            if ($e['status'] === 'won' && $db->table('crm_sales_orders')->where('enquiry_id', $enquiryId)->whereIn('status', ['pending_confirmation', 'confirmed', 'closed', 'on_hold', 'draft'])->countAllResults() > 0) {
                throw new CrmException('Cancel the sales order before reopening a won enquiry.');
            }
            WorkflowService::transition($enquiryId, $to, $actor, $reason, ['outcome' => null, 'closed_at' => null, 'closed_by' => null, 'final_value' => null, 'lost_reason_id' => null, 'lost_competitor' => null]);
            AuditService::workflow('enquiry.reopened', 'enquiry', $enquiryId, ['customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'], 'prev' => ['status' => $e['status']], 'new' => ['status' => $to], 'reason' => $reason, 'is_demo' => (int) $e['is_demo']]);
        });
    }
}
