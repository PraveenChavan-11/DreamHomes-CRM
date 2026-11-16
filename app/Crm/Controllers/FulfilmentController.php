<?php

namespace Crm\Controllers;

use Crm\Services\Crm;
use Crm\Services\FulfilmentService;

/**
 * Deliveries & Installations Controller
 * Handles logistics dispatch, delivery challans, installation appointments, and technician sign-offs.
 */
class FulfilmentController extends CrmController
{
    // ── DELIVERIES ──

    public function deliveries(): string
    {
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = FulfilmentService::listDeliveries($params);
        $orders = Crm::db()->table('crm_sales_orders o')
            ->select('o.id, o.order_no, c.name as customer_name')
            ->join('crm_customers c', 'c.id = o.customer_id', 'left')
            ->whereNotIn('o.status', ['cancelled'])
            ->orderBy('o.id', 'DESC')
            ->get()->getResultArray();

        return $this->render('fulfilment/deliveries', [
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'orders' => $orders,
        ], 'Deliveries & Logistics', 'deliveries');
    }

    public function deliveryShow(int $id): string
    {
        $d = FulfilmentService::getDelivery($id);
        return $this->render('fulfilment/delivery_show', [
            'd' => $d,
        ], 'Delivery Challan ' . ($d['challan_no'] ?: $d['delivery_no']), 'deliveries');
    }

    public function deliverySave(?int $id = null)
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post, $id) {
            $d = FulfilmentService::saveDelivery($post, $id, $this->user());
            return 'Delivery record ' . $d['delivery_no'] . ' saved successfully.';
        }, 'Delivery saved.', site_url('crm/deliveries'));
    }

    // ── INSTALLATIONS ──

    public function installations(): string
    {
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = FulfilmentService::listInstallations($params);
        $orders = Crm::db()->table('crm_sales_orders o')
            ->select('o.id, o.order_no, c.name as customer_name')
            ->join('crm_customers c', 'c.id = o.customer_id', 'left')
            ->whereNotIn('o.status', ['cancelled'])
            ->orderBy('o.id', 'DESC')
            ->get()->getResultArray();

        return $this->render('fulfilment/installations', [
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'orders' => $orders,
        ], 'Site Installations & Handover', 'installations');
    }

    public function installationShow(int $id): string
    {
        $ins = FulfilmentService::getInstallation($id);
        return $this->render('fulfilment/installation_show', [
            'ins' => $ins,
        ], 'Installation ' . $ins['installation_no'], 'installations');
    }

    public function installationSave(?int $id = null)
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post, $id) {
            $ins = FulfilmentService::saveInstallation($post, $id, $this->user());
            return 'Installation record ' . $ins['installation_no'] . ' saved successfully.';
        }, 'Installation saved.', site_url('crm/installations'));
    }
}
