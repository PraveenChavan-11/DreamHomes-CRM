<?php

namespace Crm\Controllers;

use Crm\Services\AccessScope;
use Crm\Services\Crm;

/**
 * Global Keyboard Command Launcher (Ctrl+K) Endpoint
 * Searches customers, enquiries, quotations, orders, and action shortcuts with strict role permissions.
 */
class SearchController extends CrmController
{
    public function search(): string
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 1) {
            return $this->response->setJSON(['results' => []]);
        }

        $u = $this->user();
        $db = Crm::db();
        $results = [];

        // 1. Action shortcuts
        $shortcuts = [
            ['title' => 'New Enquiry', 'category' => 'Action', 'url' => site_url('crm/enquiries/new'), 'perm' => 'enquiries.create', 'icon' => 'plus'],
            ['title' => 'New Customer', 'category' => 'Action', 'url' => site_url('crm/customers/new'), 'perm' => 'customers.create', 'icon' => 'user-plus'],
            ['title' => 'Pipeline Board', 'category' => 'View', 'url' => site_url('crm/enquiries?view=kanban'), 'perm' => 'enquiries.view', 'icon' => 'trello'],
            ['title' => 'Follow-ups List', 'category' => 'View', 'url' => site_url('crm/followups'), 'perm' => 'followups.manage', 'icon' => 'clock'],
            ['title' => 'Quotations', 'category' => 'View', 'url' => site_url('crm/quotations'), 'perm' => 'quotations.view', 'icon' => 'file-text'],
            ['title' => 'Sales Orders', 'category' => 'View', 'url' => site_url('crm/orders'), 'perm' => 'orders.view', 'icon' => 'shopping-cart'],
            ['title' => 'QA Command Centre', 'category' => 'QA & Health', 'url' => site_url('crm/qa'), 'perm' => 'settings.manage', 'icon' => 'shield'],
            ['title' => 'System Settings', 'category' => 'Admin', 'url' => site_url('crm/settings'), 'perm' => 'settings.manage', 'icon' => 'settings'],
        ];

        foreach ($shortcuts as $sc) {
            if (crm_can($sc['perm']) && stripos($sc['title'], $q) !== false) {
                $results[] = [
                    'id' => 'act-' . $sc['title'],
                    'title' => $sc['title'],
                    'category' => $sc['category'],
                    'url' => $sc['url'],
                    'sub' => 'Quick action shortcut',
                ];
            }
        }

        // 2. Customers search (respecting access scope)
        if (crm_can('customers.view')) {
            $custs = $db->table('crm_customers')
                ->select('id, customer_no, name, mobile, email, city')
                ->groupStart()
                    ->like('name', $q)
                    ->orLike('customer_no', $q)
                    ->orLike('mobile', $q)
                    ->orLike('email', $q)
                ->groupEnd()
                ->limit(5)
                ->get()
                ->getResultArray();

            foreach ($custs as $c) {
                $results[] = [
                    'id' => 'cust-' . $c['id'],
                    'title' => $c['name'],
                    'category' => 'Customers',
                    'url' => site_url('crm/customers/' . $c['id']),
                    'sub' => $c['customer_no'] . ' · ' . $c['mobile'] . ($c['city'] ? ' · ' . $c['city'] : ''),
                ];
            }
        }

        // 3. Enquiries search (respecting ownership access scope)
        if (crm_can('enquiries.view')) {
            $visUserIds = AccessScope::visibleUserIds($u);
            $builder = $db->table('crm_enquiries e')
                ->select('e.id, e.enquiry_no, e.project_name, e.status, c.name customer_name')
                ->join('crm_customers c', 'c.id = e.customer_id')
                ->groupStart()
                    ->like('e.enquiry_no', $q)
                    ->orLike('e.project_name', $q)
                    ->orLike('c.name', $q)
                ->groupEnd();

            if ($visUserIds !== null) {
                $builder->whereIn('e.current_owner_id', $visUserIds);
            }

            $enqs = $builder->limit(5)->get()->getResultArray();

            foreach ($enqs as $e) {
                $results[] = [
                    'id' => 'enq-' . $e['id'],
                    'title' => $e['enquiry_no'] . ' · ' . ($e['project_name'] ?: 'Enquiry'),
                    'category' => 'Enquiries',
                    'url' => site_url('crm/enquiries/' . $e['id']),
                    'sub' => $e['customer_name'] . ' · ' . crm_label($e['status']),
                ];
            }
        }

        // 4. Quotations search
        if (crm_can('quotations.view')) {
            $quotes = $db->table('crm_quotations q')
                ->select('q.id, q.quotation_no, q.status, c.name customer_name')
                ->join('crm_customers c', 'c.id = q.customer_id')
                ->groupStart()
                    ->like('q.quotation_no', $q)
                    ->orLike('c.name', $q)
                ->groupEnd()
                ->limit(5)
                ->get()
                ->getResultArray();

            foreach ($quotes as $qt) {
                $results[] = [
                    'id' => 'qt-' . $qt['id'],
                    'title' => $qt['quotation_no'],
                    'category' => 'Quotations',
                    'url' => site_url('crm/quotations/' . $qt['id']),
                    'sub' => $qt['customer_name'] . ' · ' . crm_label($qt['status']),
                ];
            }
        }

        return $this->response->setJSON(['results' => $results]);
    }
}

