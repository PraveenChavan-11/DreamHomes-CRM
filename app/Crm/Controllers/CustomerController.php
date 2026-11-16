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

    public function index(): string
    {
        $g     = $this->request->getGet();
        $page  = $this->page();
        $per   = 25;
        $total = (int) $this->query($g)->countAllResults(false);
        $rows  = $this->query($g)->orderBy('c.created_at', 'DESC')->orderBy('c.id', 'DESC')->limit($per, ($page - 1) * $per)->get()->getResultArray();
        $cities = Crm::db()->query("SELECT DISTINCT city FROM crm_customers WHERE city IS NOT NULL AND city <> '' ORDER BY city LIMIT 200")->getResultArray();

        return $this->render('customers/index', ['rows' => $rows, 'total' => $total, 'per' => $per, 'page' => $page, 'g' => $g, 'cities' => array_column($cities, 'city')] + $this->lookups(), 'Customers', 'customers');
    }

    public function new(): string
    {
        return $this->render('customers/form', ['c' => [], 'action' => site_url('crm/customers/create'), 'mode' => 'new'] + $this->lookups(), 'New customer', 'customers');
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

    public function edit(string $id): string
    {
        $c = $this->load((int) $id);

        return $this->render('customers/form', ['c' => $c, 'action' => site_url("crm/customers/{$c['id']}/update"), 'mode' => 'edit'] + $this->lookups(), 'Edit ' . $c['customer_no'], 'customers');
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
        $g    = $this->request->getGet();
        $rows = $this->query($g)->orderBy('c.customer_no')->limit(20000)->get()->getResultArray();
        $head = ['Customer No', 'Name', 'Type', 'Company', 'Contact', 'Mobile', 'Email', 'City', 'State', 'PIN', 'GSTIN', 'Source', 'Owner', 'Status', 'Enquiries', 'Created'];
        $safe = static fn ($v) => is_string($v) && isset($v[0]) && in_array($v[0], ['=', '+', '-', '@'], true) && ! is_numeric($v) ? "'" . $v : $v;
        $data = array_map(static fn ($r) => array_map($safe, [$r['customer_no'], $r['name'], $r['customer_type'], $r['company_name'], $r['primary_contact'], $r['mobile'], $r['email'], $r['city'], $r['state'], $r['pin_code'], $r['gstin'], $r['source_name'], $r['owner_name'], $r['status'], $r['enquiry_count'], $r['created_at']]), $rows);
        AuditService::audit('customers.exported', 'report', 'customers', ['meta' => ['rows' => count($rows), 'filters' => $g]]);
        if (($g['format'] ?? '') === 'csv') {
            $fh = fopen('php://temp', 'r+');
            fputcsv($fh, $head);
            foreach ($data as $r) {
                fputcsv($fh, $r);
            }
            rewind($fh);

            return $this->download((string) stream_get_contents($fh), 'customers-' . date('Ymd') . '.csv', 'text/csv; charset=utf-8');
        }
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet()->setTitle('Customers');
        $sh->fromArray([$head], null, 'A1');
        $sh->getStyle('A1:P1')->getFont()->setBold(true);
        $sh->fromArray($data, null, 'A2');
        foreach (range('A', 'P') as $col) {
            $sh->getColumnDimension($col)->setAutoSize(true);
        }
        ob_start();
        (new Xlsx($ss))->save('php://output');

        return $this->download((string) ob_get_clean(), 'customers-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function show(string $id): string
    {
        $this->load((int) $id);
        try {
            $t = HistoryService::customerTabs((int) $id, $this->user());
        } catch (CrmException) {
            throw new PageNotFoundException('Not found');
        }
        $tab = (string) ($this->request->getGet('tab') ?? 'overview');
        if (! isset(self::TABS[$tab])) {
            $tab = 'overview';
        }
        $counts = [];
        foreach (self::TABS as $k => $_) {
            $src = ['salespeople' => 'salespeople', 'approvals' => 'approvals', 'workflow' => 'workflow'][$k] ?? $k;
            $counts[$k] = is_array($t[$src] ?? null) && $k !== 'overview' ? count($t[$src]) : null;
        }
        $db   = Crm::db();
        $meta = $db->query("SELECT s.name source_name, CONCAT(o.first_name,' ',o.last_name) owner_name FROM crm_customers c LEFT JOIN crm_lead_sources s ON s.id = c.source_id LEFT JOIN admin_users o ON o.id = c.owner_user_id WHERE c.id = ?", [(int) $id])->getRowArray();

        return $this->render('customers/show', ['t' => $t, 'c' => $t['customer'] + (array) $meta, 'tab' => $tab, 'tabs' => self::TABS, 'counts' => $counts], $t['customer']['customer_no'] . ' · ' . $t['customer']['name'], 'customers');
    }
}
