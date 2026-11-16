<?php

namespace Crm\Controllers;

use Crm\Services\Crm;
use Crm\Services\PaymentService;
use Crm\Services\PdfService;

/**
 * Payments & Financial Receipts Controller
 * Records customer milestone settlements, balances and outputs official PDF receipts.
 */
class PaymentController extends CrmController
{
    public function index(): string
    {
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
            'order_id' => (int) $this->request->getGet('order_id'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = PaymentService::listPayments($params, $this->user());
        $orders = Crm::db()->table('crm_sales_orders o')
            ->select('o.id, o.order_no, o.grand_total, c.name as customer_name')
            ->join('crm_customers c', 'c.id = o.customer_id', 'left')
            ->whereNotIn('o.status', ['cancelled'])
            ->orderBy('o.id', 'DESC')
            ->get()->getResultArray();

        return $this->render('payments/index', [
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'total_received' => $data['total_received'],
            'total_refunded' => $data['total_refunded'],
            'params' => $params,
            'orders' => $orders,
        ], 'Payment Transactions & Receipts', 'payments');
    }

    public function show(int $id): string
    {
        $payment = PaymentService::getPayment($id);
        return $this->render('payments/show', [
            'p' => $payment,
        ], 'Payment Receipt ' . ($payment['receipt_no'] ?: $payment['payment_no']), 'payments');
    }

    public function record()
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post) {
            $p = PaymentService::recordPayment($post, $this->user());
            return 'Payment ' . $p['payment_no'] . ' recorded. Receipt: ' . $p['receipt_no'];
        }, 'Payment recorded successfully.', site_url('crm/payments'));
    }

    /** Stream or download PDF receipt */
    public function receiptPdf(int $id)
    {
        $bytes = PdfService::receiptBytes($id);
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="receipt-' . $id . '.pdf"')
            ->setBody($bytes);
    }
}
