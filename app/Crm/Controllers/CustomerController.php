<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\AuditService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\HistoryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerController extends CrmController
{
    private const FIELDS = ['name', 'customer_type', 'company_name', 'primary_contact', 'mobile', 'alt_mobile', 'email', 'billing_address', 'site_address', 'city', 'state', 'pin_code', 'gstin', 'source_id', 'owner_user_id', 'remarks'];
    public const TABS = [
        'overview' => 'Overview', 'projects' => 'Projects', 'enquiries' => 'Enquiries', 'salespeople' => 'Assigned salespeople', 'followups' => 'Follow-ups', 'activities' => 'Activities', 'visits' => 'Site visits',
        'measurements' => 'Measurements', 'quotations' => 'Quotations', 'negotiations' => 'Negotiations', 'approvals' => 'Discount approvals', 'orders' => 'Orders',
        'payments' => 'Payments', 'deliveries' => 'Deliveries', 'installations' => 'Installations', 'service_requests' => 'Service requests',
        'emails' => 'Emails', 'documents' => 'Documents', 'workflow' => 'Workflow history', 'audit' => 'Audit history',
    ];

    /** Filtered + scoped query (shared by list and export). */
    private function query(array $g)
    {
        $b = Crm::db()->table('crm_customers c')
            ->select("c.*, s.name source_name, CONCAT(o.first_name,' ',o.last_name) owner_name, (SELECT COUNT(*) FROM crm_enquiries x WHERE x.customer_id = c.id) enquiry_count", false)
            ->join('crm_lead_sources s', 's.id = c.source_id', 'left')->join('admin_users o', 'o.id = c.owner_user_id', 'left');
        AccessScope::scopeCustomers($b, $this->user());
        if (($q = trim((string) ($g['q'] ?? ''))) !== '') {
            $b->groupStart()->like('c.name', $q)->orLike('c.customer_no', $q)->orLike('c.mobile', $q)->orLike('c.email', $q)->orLike('c.company_name', $q)->groupEnd();
        }
        foreach (['status' => 'c.status', 'customer_type' => 'c.customer_type', 'source_id' => 'c.source_id', 'owner_user_id' => 'c.owner_user_id'] as $k => $col) {
            if (($g[$k] ?? '') !== '') {
                $b->where($col, $g[$k]);
            }
        }
        if (($g['city'] ?? '') !== '') {
            $b->where('c.city', $g['city']);
        }

        return $b;
    }

    private function lookups(): array
    {
        $db  = Crm::db();
        $ids = AccessScope::visibleUserIds($this->user());
        $ub  = $db->table('admin_users')->select("id, CONCAT(first_name,' ',last_name) name", false)->where('is_active', 1);
        if ($ids !== null) {
            $ub->whereIn('id', $ids);
        }

        return [
            'sources' => $db->table('crm_lead_sources')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'owners'  => $ub->orderBy('first_name')->get()->getResultArray(),
        ];
    }

    public function index()
    {
        return redirect()->to(site_url('crm/organisations'));
    }

    public function new()
    {
        return redirect()->to(site_url('crm/organisations/new'));
    }

    /** Only let the user pick an owner they may see; executives always own what they create. */
    private function payload(): array
    {
        $d   = $this->pick($this->request->getPost(), self::FIELDS);
        $ids = AccessScope::visibleUserIds($this->user());
        $own = (int) ($d['owner_user_id'] ?? 0);
        $d['owner_user_id'] = ($own && ($ids === null || in_array($own, $ids, true))) ? $own : $this->uid();
        foreach (['source_id'] as $k) {
            $d[$k] = ($d[$k] ?? '') === '' ? null : (int) $d[$k];
        }
        foreach ($d as $k => $v) {
            if ($v === '') {
                $d[$k] = null;
            }
        }
        $d['name']   = (string) ($d['name'] ?? '');
        $d['mobile'] = (string) ($d['mobile'] ?? '');

        return $d;
    }

    public function create()
    {
        $d     = $this->payload();
        $allow = (bool) $this->request->getPost('allow_duplicate');

        return $this->act(fn () => 'crm/customers/' . CustomerService::create($d, $this->uid(), $allow), 'Customer created.');
    }

    private function load(int $id): array
    {
        $c = Crm::db()->table('crm_customers')->where('id', $id)->get()->getRowArray();
        if (! $c || ! AccessScope::canSeeCustomer($this->user(), $id)) {
            throw new PageNotFoundException('Not found');
        }

        return $c;
    }

    public function edit(string $id)
    {
        $db = Crm::db();
        $c = $db->table('crm_customers')->where('id', (int) $id)->get()->getRowArray();
        if ($c && ! empty($c['organisation_id'])) {
            return redirect()->to(site_url("crm/organisations/{$c['organisation_id']}"));
        }
        return redirect()->to(site_url('crm/organisations'));
    }

    public function update(string $id)
    {
        $c     = $this->load((int) $id);
        $d     = $this->payload();
        $allow = (bool) $this->request->getPost('allow_duplicate');

        return $this->act(function () use ($c, $d, $allow) {
            CustomerService::update((int) $c['id'], $d, $this->uid(), $allow);

            return 'crm/customers/' . $c['id'];
        }, 'Customer updated.');
    }

    public function status(string $id)
    {
        $c      = $this->load((int) $id);
        $to     = (string) $this->request->getPost('status');
        $reason = trim((string) $this->request->getPost('reason'));
        if ($to !== 'active' && $reason === '') {
            return $this->flashBack('error', 'Please give a reason for deactivating or archiving a customer.');
        }

        return $this->act(function () use ($c, $to, $reason) {
            CustomerService::setStatus((int) $c['id'], $to, $this->uid(), $reason ?: null);
        }, 'Customer status updated to ' . $to . '.');
    }

    public function export()
    {
        return redirect()->to(site_url('crm/organisations/export'));
    }

    public function show(string $id)
    {
        $db = Crm::db();
        $c = $db->table('crm_customers')->where('id', (int) $id)->get()->getRowArray();
        if ($c && ! empty($c['organisation_id'])) {
            return redirect()->to(site_url("crm/organisations/{$c['organisation_id']}"));
        }
        if ($c) {
            $org = $db->table('crm_organisations')->where('name', $c['name'])->orWhere('name', $c['company_name'])->get()->getRowArray();
            if ($org) {
                return redirect()->to(site_url("crm/organisations/{$org['id']}"));
            }
        }
        return redirect()->to(site_url('crm/organisations'));
    }
}
