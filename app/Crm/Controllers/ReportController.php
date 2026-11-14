<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ReportService;

class ReportController extends CrmController
{
    private const GROUPS = [
        'Customers & enquiries' => ['customers', 'enquiries', 'assignments', 'lead_sources', 'conversion', 'won_lost'],
        'Activity' => ['salesperson_activity', 'followups', 'overdue_followups', 'site_visits', 'measurements'],
        'Quotations & negotiation' => ['quotations', 'quotation_versions', 'negotiations', 'discount_approvals'],
        'Orders & products' => ['orders', 'product_sales'],
        'Communication, security & audit' => ['email_log', 'login_history', 'audit'],
    ];
    private const FILTERS = ['from', 'to', 'user_id', 'team_id', 'status', 'customer_id'];
    private const PER = 50;

    private function filters(): array
    {
        $f = [];
        foreach (self::FILTERS as $k) {
            $v = trim((string) ($this->request->getGet($k) ?? ''));
            if ($v !== '') {
                $f[$k] = $v;
            }
        }
        foreach (['from', 'to'] as $d) {
            if (isset($f[$d]) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $f[$d])) {
                unset($f[$d]);
            }
        }

        return $f;
    }

    private function guard(string $key): void
    {
        if (! isset(ReportService::available($this->user())[$key])) {
            throw new PageNotFoundException('Not found');
        }
    }

    public function index(): string
    {
        $avail = ReportService::available($this->user());
        $groups = [];
        $seen = [];
        foreach (self::GROUPS as $g => $keys) {
            foreach ($keys as $k) {
                if (isset($avail[$k])) {
                    $groups[$g][$k] = $avail[$k];
                    $seen[$k] = 1;
                }
            }
        }
        foreach ($avail as $k => $t) {   // any report added to the service later still shows up
            if (! isset($seen[$k])) {
                $groups['Other'][$k] = $t;
            }
        }

        return $this->render('reports/index', ['groups' => $groups], 'Reports', 'reports');
    }

    public function show(string $key): string
    {
        $this->guard($key);
        $f = $this->filters();
        try {
            $r = ReportService::run($key, $f, $this->user());
        } catch (CrmException $e) {
            throw new PageNotFoundException($e->getMessage());
        }
        $page  = $this->page();
        $total = count($r['rows']);
        $db    = Crm::db();
        $ids   = AccessScope::visibleUserIds($this->user());
        $ub    = $db->table('admin_users')->select("id, CONCAT(first_name,' ',last_name) name", false);
        if ($ids !== null) {
            $ub->whereIn('id', $ids);
        }
        $cb = AccessScope::scopeCustomers($db->table('crm_customers c')->select('c.id, c.customer_no, c.name'), $this->user())->orderBy('c.name')->limit(600)->get()->getResultArray();

        return $this->render('reports/show', [
            'key' => $key, 'title' => $r['title'], 'columns' => $r['columns'], 'rows' => array_slice($r['rows'], ($page - 1) * self::PER, self::PER), 'total' => $total,
            'per' => self::PER, 'page' => $page, 'f' => $f, 'users' => $ub->orderBy('first_name')->get()->getResultArray(), 'teams' => $db->table('crm_teams')->orderBy('name')->get()->getResultArray(), 'customers' => $cb,
        ], $r['title'], 'reports');
    }

    public function xlsx(string $key)
    {
        $this->guard($key);

        return $this->export($key, 'xlsx');
    }

    public function pdf(string $key)
    {
        $this->guard($key);

        return $this->export($key, 'pdf');
    }

    private function export(string $key, string $fmt)
    {
        try {
            $f = $this->filters();
            if ($fmt === 'xlsx') {
                return $this->download(ReportService::toXlsx($key, $f, $this->user()), $key . '-' . date('Ymd') . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            }

            return $this->download(ReportService::toPdf($key, $f, $this->user()), $key . '-' . date('Ymd') . '.pdf', 'application/pdf');
        } catch (CrmException $e) {
            return $this->flashBack('error', $e->getMessage());
        }
    }
}
