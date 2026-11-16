<?php

namespace Crm\Controllers;

use Crm\Services\AfterSalesService;
use Crm\Services\Crm;

/**
 * After-Sales Service & Complaints Controller
 * Tracks customer complaints, warranty claims, service tickets, and field repair resolution.
 */
class ServiceController extends CrmController
{
    public function index(): string
    {
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
            'request_type' => (string) $this->request->getGet('request_type'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = AfterSalesService::listTickets($params);
        $customers = Crm::db()->table('crm_customers')->where('status', 'active')->orderBy('name')->get()->getResultArray();
        $orders = Crm::db()->table('crm_sales_orders o')
            ->select('o.id, o.order_no, c.name as customer_name')
            ->join('crm_customers c', 'c.id = o.customer_id', 'left')
            ->orderBy('o.id', 'DESC')
            ->get()->getResultArray();
        $technicians = Crm::db()->table('admin_users')->where('is_active', 1)->orderBy('first_name')->get()->getResultArray();

        return $this->render('service/index', [
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'customers' => $customers,
            'orders' => $orders,
            'technicians' => $technicians,
        ], 'After-Sales Service & Support', 'service');
    }

    public function show(int $id): string
    {
        $ticket = AfterSalesService::getTicket($id);
        $technicians = Crm::db()->table('admin_users')->where('is_active', 1)->orderBy('first_name')->get()->getResultArray();

        return $this->render('service/show', [
            'ticket' => $ticket,
            'technicians' => $technicians,
        ], 'Service Ticket ' . $ticket['ticket_no'], 'service');
    }

    public function save(?int $id = null)
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post, $id) {
            $t = AfterSalesService::saveTicket($post, $id, $this->user());
            return 'Service ticket ' . $t['ticket_no'] . ' saved successfully.';
        }, 'Service ticket saved.', site_url('crm/service'));
    }
}
