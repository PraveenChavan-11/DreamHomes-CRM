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
            ['title' => 'New Organisation', 'category' => 'Action', 'url' => site_url('crm/organisations/new'), 'perm' => 'customers.create', 'icon' => 'briefcase'],
            ['title' => 'New Contact', 'category' => 'Action', 'url' => site_url('crm/contacts/new'), 'perm' => 'customers.create', 'icon' => 'user-plus'],
            ['title' => 'Organisations Directory', 'category' => 'View', 'url' => site_url('crm/organisations'), 'perm' => 'customers.view', 'icon' => 'briefcase'],
            ['title' => 'Contacts Directory', 'category' => 'View', 'url' => site_url('crm/contacts'), 'perm' => 'customers.view', 'icon' => 'users'],
            ['title' => 'Pipeline Board', 'category' => 'View', 'url' => site_url('crm/enquiries?view=kanban'), 'perm' => 'enquiries.view', 'icon' => 'trello'],
            ['title' => 'Follow-ups List', 'category' => 'View', 'url' => site_url('crm/followups'), 'perm' => 'followups.manage', 'icon' => 'clock'],
            ['title' => 'Quotations', 'category' => 'View', 'url' => site_url('crm/quotations'), 'perm' => 'quotations.view', 'icon' => 'file-text'],
            ['title' => 'Commercial Approvals', 'category' => 'View', 'url' => site_url('crm/approvals'), 'perm' => 'quotations.view', 'icon' => 'shield-check'],
            ['title' => 'Sales Targets', 'category' => 'View', 'url' => site_url('crm/targets'), 'perm' => 'reports.view', 'icon' => 'trending-up'],
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

        // 2. Organisations & Contacts search
        if (crm_can('customers.view') && $db->tableExists('crm_organisations')) {
            $orgs = $db->table('crm_organisations')
                ->select('id, org_no, name, type, city, primary_phone')
                ->groupStart()
                    ->like('name', $q)
                    ->orLike('org_no', $q)
                    ->orLike('primary_phone', $q)
                    ->orLike('city', $q)
                ->groupEnd()
                ->limit(5)
                ->get()
                ->getResultArray();

            foreach ($orgs as $org) {
                $results[] = [
                    'id' => 'org-' . $org['id'],
                    'title' => $org['name'],
                    'category' => 'Organisations',
                    'url' => site_url('crm/organisations/' . $org['id']),
                    'sub' => $org['org_no'] . ' · ' . ($org['type'] ?? 'Account') . ($org['city'] ? ' · ' . $org['city'] : ''),
                ];
            }

            if ($db->tableExists('crm_contacts')) {
                $contacts = $db->table('crm_contacts c')
                    ->select('c.id, c.contact_no, c.first_name, c.last_name, c.designation, c.mobile, c.mobile AS mobile_number, c.organisation_id, o.name as org_name')
                    ->join('crm_organisations o', 'o.id = c.organisation_id', 'left')
                    ->groupStart()
                        ->like('c.first_name', $q)
                        ->orLike('c.last_name', $q)
                        ->orLike('c.mobile', $q)
                        ->orLike('c.contact_no', $q)
                    ->groupEnd()
                    ->limit(5)
                    ->get()
                    ->getResultArray();

                foreach ($contacts as $cnt) {
                    $results[] = [
                        'id' => 'cnt-' . $cnt['id'],
                        'title' => trim($cnt['first_name'] . ' ' . $cnt['last_name']),
                        'category' => 'Contacts',
                        'url' => $cnt['organisation_id'] ? site_url('crm/organisations/' . $cnt['organisation_id'] . '?tab=contacts') : site_url('crm/contacts'),
                        'sub' => ($cnt['designation'] ?: 'Contact') . ($cnt['org_name'] ? ' · ' . $cnt['org_name'] : '') . ' · ' . ($cnt['mobile'] ?? $cnt['mobile_number']),
                    ];
                }
            }
        }

        // 3. Enquiries search (respecting ownership access scope)
        if (crm_can('enquiries.view')) {
            $visUserIds = AccessScope::visibleUserIds($u);
            $builder = $db->table('crm_enquiries e')
                ->select('e.id, e.enquiry_no, e.project_name, e.status, COALESCE(o.name, c.name) org_name')
                ->join('crm_customers c', 'c.id = e.customer_id', 'left')
                ->join('crm_organisations o', 'o.id = e.organisation_id', 'left')
                ->groupStart()
                    ->like('e.enquiry_no', $q)
                    ->orLike('e.project_name', $q)
                    ->orLike('o.name', $q)
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
                    'sub' => ($e['org_name'] ?: 'Account') . ' · ' . crm_label($e['status']),
                ];
            }
        }

        // 4. Quotations search
        if (crm_can('quotations.view')) {
            $quotes = $db->table('crm_quotations q')
                ->select('q.id, q.quotation_no, q.status, COALESCE(o.name, c.name) org_name')
                ->join('crm_customers c', 'c.id = q.customer_id', 'left')
                ->join('crm_organisations o', 'o.id = q.organisation_id', 'left')
                ->groupStart()
                    ->like('q.quotation_no', $q)
                    ->orLike('o.name', $q)
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
                    'sub' => ($qt['org_name'] ?: 'Quotation') . ' · ' . crm_label($qt['status']),
                ];
            }
        }

        return $this->response->setJSON(['results' => $results]);
    }
}

