<?php

namespace Crm\Services;

/**
 * Enterprise Payment & Financial Reconciliation Service
 * Records advances, milestone payments, balance collections, refunds and receipt generation.
 */
final class PaymentService
{
    public static function listPayments(array $params = [], ?array $actor = null): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_payments p')
            ->select('p.*, c.name as customer_name, c.mobile as customer_mobile, o.order_no, o.grand_total as order_total')
            ->join('crm_customers c', 'c.id = p.customer_id', 'left')
            ->join('crm_sales_orders o', 'o.id = p.order_id', 'left');

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('p.payment_no', $q)
                ->orLike('p.receipt_no', $q)
                ->orLike('p.transaction_reference', $q)
                ->orLike('c.name', $q)
                ->orLike('c.mobile', $q)
                ->orLike('o.order_no', $q)
                ->groupEnd();
        }

        if (! empty($params['order_id'])) {
            $builder->where('p.order_id', (int) $params['order_id']);
        }
        if (! empty($params['customer_id'])) {
            $builder->where('p.customer_id', (int) $params['customer_id']);
        }
        if (! empty($params['status'])) {
            $builder->where('p.status', $params['status']);
        }

        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('p.id', 'DESC')->get($limit, $offset)->getResultArray();

        // Calculate financial aggregates
        $totals = $db->table('crm_payments')
            ->select('COALESCE(SUM(CASE WHEN status = "paid" AND payment_type != "refund" THEN amount ELSE 0 END), 0) as total_received,
                      COALESCE(SUM(CASE WHEN status = "paid" AND payment_type = "refund" THEN amount ELSE 0 END), 0) as total_refunded')
            ->get()->getRowArray() ?? ['total_received' => 0, 'total_refunded' => 0];

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_received' => (float) $totals['total_received'],
            'total_refunded' => (float) $totals['total_refunded'],
        ];
    }

    public static function getPayment(int $id): array
    {
        $db = Crm::db();
        $row = $db->table('crm_payments p')
            ->select("p.*, c.name as customer_name, c.company_name, c.mobile as customer_mobile, c.email as customer_email, c.billing_address, c.city as customer_city, c.state as customer_state, c.gstin as customer_gstin, o.order_no, o.grand_total as order_total, CONCAT(u.first_name, ' ', u.last_name) as recorded_by_name")
            ->join('crm_customers c', 'c.id = p.customer_id', 'left')
            ->join('crm_sales_orders o', 'o.id = p.order_id', 'left')
            ->join('admin_users u', 'u.id = p.recorded_by', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray() ?? throw new CrmException('Payment record not found.');

        // Compute total paid for this order and current balance
        $paidSoFar = (float) $db->table('crm_payments')
            ->select('COALESCE(SUM(CASE WHEN status = "paid" AND payment_type != "refund" THEN amount ELSE 0 END), 0) as paid')
            ->where('order_id', (int) $row['order_id'])
            ->get()->getRow()->paid;

        $row['order_paid_total'] = $paidSoFar;
        $row['order_balance'] = max(0.0, (float) $row['order_total'] - $paidSoFar);

        return $row;
    }

    public static function recordPayment(array $data, ?array $actor = null): array
    {
        return Crm::tx(static function ($db) use ($data, $actor) {
            $orderId = (int) ($data['order_id'] ?? 0);
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $type = $data['payment_type'] ?? 'advance';
            $mode = $data['payment_mode'] ?? 'bank_transfer';
            $date = ! empty($data['payment_date']) ? $data['payment_date'] : date('Y-m-d');

            if ($orderId <= 0) {
                throw new CrmException('A valid sales order must be selected.');
            }
            if ($amount <= 0) {
                throw new CrmException('Payment amount must be greater than zero.');
            }

            $order = $db->table('crm_sales_orders')->where('id', $orderId)->get()->getRowArray() ?? throw new CrmException('Sales order not found.');
            $customerId = (int) $order['customer_id'];

            // Calculate existing payments on this order
            $existingPaid = (float) $db->table('crm_payments')
                ->select('COALESCE(SUM(CASE WHEN status = "paid" AND payment_type != "refund" THEN amount ELSE 0 END), 0) as paid')
                ->where('order_id', $orderId)
                ->get()->getRow()->paid;

            $grandTotal = (float) $order['grand_total'];
            $outstanding = max(0.0, $grandTotal - $existingPaid);

            if ($type !== 'refund' && $amount > ($outstanding + 0.05)) { // small epsilon for rounding
                throw new CrmException("Payment amount of INR " . number_format($amount, 2) . " exceeds outstanding order balance of INR " . number_format($outstanding, 2));
            }

            $paymentNo = NumberSequenceService::next('payment');
            $receiptNo = NumberSequenceService::next('receipt');

            $payload = [
                'payment_no' => $paymentNo,
                'receipt_no' => $receiptNo,
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'payment_type' => $type,
                'amount' => $amount,
                'payment_mode' => $mode,
                'transaction_reference' => trim((string) ($data['transaction_reference'] ?? '')),
                'payment_date' => $date,
                'status' => 'paid',
                'notes' => trim((string) ($data['notes'] ?? '')),
                'recorded_by' => $actor['id'] ?? null,
                'created_at' => Crm::now(),
                'updated_at' => Crm::now(),
            ];

            $db->table('crm_payments')->insert($payload);
            $paymentId = $db->insertID();

            // Auto-update sales order status based on payments
            $newTotalPaid = $existingPaid + ($type === 'refund' ? -$amount : $amount);
            if ($newTotalPaid >= $grandTotal) {
                $newStatus = ($order['status'] === 'draft' || $order['status'] === 'pending_confirmation') ? 'confirmed' : $order['status'];
            } elseif ($newTotalPaid > 0) {
                $newStatus = 'partially_paid';
            } else {
                $newStatus = $order['status'];
            }

            $db->table('crm_sales_orders')->where('id', $orderId)->update([
                'status' => $newStatus,
                'advance_amount' => $newTotalPaid,
                'updated_at' => Crm::now(),
            ]);

            AuditService::audit('payment_recorded', 'crm_payments', $paymentId, [], $payload);
            AuditService::workflow('order.payment_received', 'sales_order', $orderId, [
                'entity_ref' => $order['order_no'],
                'customer_id' => $customerId,
                'reason' => 'Payment of INR ' . number_format($amount, 2) . ' received (' . $receiptNo . ')',
                'prev' => ['status' => $order['status']],
                'new' => ['status' => $newStatus],
            ]);

            return self::getPayment($paymentId);
        });
    }

    /** Generate HTML string for print-ready payment receipt */
    public static function receiptHtml(int $paymentId): string
    {
        helper(['crm', 'url', 'number']);
        $p = self::getPayment($paymentId);
        $company = [
            'name' => Settings::get('company.name', 'Dream Homes Interior Solutions'),
            'address' => Settings::get('company.address', 'Pune, Maharashtra'),
            'phone' => Settings::get('company.phone', '+91 98765 43210'),
            'email' => Settings::get('company.email', 'sales@dhisgroup.in'),
            'gstin' => Settings::get('company.gstin', '27AABCD1234E1Z5'),
        ];

        return view('Crm\Views\pdf\receipt', ['p' => $p, 'company' => $company]);
    }
}
