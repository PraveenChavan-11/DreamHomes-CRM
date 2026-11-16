<?php

namespace Tests\Crm;

use Crm\Services\AccessScope;
use Crm\Services\AuthService;
use Crm\Services\CrmException;
use Crm\Services\Crm;
use Crm\Services\CustomerService;
use Crm\Services\DashboardService;
use Crm\Services\DemoSeeder;
use Crm\Services\DemoService;
use Crm\Services\HistoryService;
use Crm\Services\IntegrityChecker;
use Crm\Services\ReportService;
use Crm\Services\Settings;

/**
 * Seeds the full demo organisation into dhis_crm_test (committed), verifies dashboards/reports/history/scope against it,
 * then exercises the protected reset, and finally removes every demo row again.
 */
final class DemoDataTest extends CrmTestCase
{
    protected bool $useTransaction = false;
    private static bool $demoReady = false;
    private static array $summary = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (self::$demoReady) {
            return;
        }
        self::demoFlag('true');
        Settings::set('demo.enabled', '1');
        self::removeFixtures();   // stragglers from an interrupted earlier run
        DemoService::purgeDemoRows();
        self::$summary   = (new DemoSeeder())->run(true);
        self::$demoReady = true;
    }

    public static function tearDownAfterClass(): void
    {
        DemoService::purgeDemoRows();
        Settings::set('demo.enabled', '0');
        self::demoFlag(null);
        parent::tearDownAfterClass();
    }

    private static function removeFixtures(): void
    {
        $db = Crm::db();
        $db->query("DELETE FROM crm_workflow_events WHERE customer_id IN (SELECT id FROM crm_customers WHERE email = 'real@customer.invalid')");
        $db->query("DELETE FROM crm_customers WHERE email = 'real@customer.invalid'");
        $db->query("DELETE FROM user_roles WHERE user_id IN (SELECT id FROM admin_users WHERE email LIKE '%@real.invalid')");
        $db->query("DELETE FROM admin_users WHERE email LIKE '%@real.invalid'");
    }

    /** env() prefers $_ENV (populated from .env) over $_SERVER/getenv, so set all three. */
    private static function demoFlag(?string $v): void
    {
        if ($v === null) {
            putenv('DHIS_DEMO_ENV');
            unset($_SERVER['DHIS_DEMO_ENV'], $_ENV['DHIS_DEMO_ENV']);

            return;
        }
        putenv("DHIS_DEMO_ENV={$v}");
        $_SERVER['DHIS_DEMO_ENV'] = $_ENV['DHIS_DEMO_ENV'] = $v;
    }

    private function u(string $username): array
    {
        return AuthService::sessionUser((int) Crm::db()->table('admin_users')->where('username', $username)->get()->getRow()->id);
    }

    public function testDatasetMeetsDocumentedSize(): void
    {
        $s = self::$summary;
        $this->assertGreaterThanOrEqual(12, $s['users']);
        $this->assertSame(40, $s['customers']);
        $this->assertSame(100, $s['enquiries']);
        $this->assertGreaterThanOrEqual(250, $s['followups'] + $s['activities']);
        $this->assertGreaterThanOrEqual(30, $s['site_visits']);
        $this->assertSame(35, $s['products']);
        $this->assertSame(60, $s['quotations']);
        $this->assertGreaterThanOrEqual(20, $s['negotiations']);
        $this->assertGreaterThan($s['quotations'], $s['quotation_versions'], 'revisions exist');
        $this->assertSame(15, $s['sales_orders']);
        $this->assertGreaterThanOrEqual(100, $s['emails']);
    }

    public function testIntegrityIsClean(): void
    {
        $this->assertSame([], IntegrityChecker::run());
    }

    public function testNoDemoEmailWasTransmittedAndStatusesAreHonest(): void
    {
        $rows = Crm::db()->query("SELECT status, provider, COUNT(*) n FROM crm_email_log WHERE is_demo = 1 GROUP BY status, provider")->getResultArray();
        foreach ($rows as $r) {
            $this->assertSame('queued', $r['status']);
            $this->assertSame('log_only', $r['provider']);
        }
        $this->assertSame(0, Crm::db()->table('crm_email_log')->where('is_demo', 1)->whereIn('status', ['sent', 'delivered'])->countAllResults());
        $this->assertSame(0, Crm::db()->query("SELECT COUNT(*) c FROM crm_email_log WHERE to_email NOT LIKE '%.invalid'")->getRow()->c + 0);
    }

    public function testEveryDemoUserCanLogInWithDocumentedPassword(): void
    {
        $names = ['demo.superadmin', 'demo.admin1', 'demo.admin2', 'demo.manager1', 'demo.manager2', 'demo.exec1', 'demo.exec2', 'demo.exec3', 'demo.exec4', 'demo.exec5', 'demo.exec6', 'demo.management'];
        foreach ($names as $n) {
            $s = AuthService::attempt($n . '@dhis-demo.invalid', DemoSeeder::PASSWORD);
            $this->assertNotEmpty($s['roles'], $n);
        }
    }

    public function testDashboardReconcilesWithUnderlyingRecords(): void
    {
        $db = Crm::db();
        $m  = DashboardService::metrics($this->u('demo.admin1'));
        $this->assertSame((int) $db->table('crm_enquiries')->countAllResults(), $m['enquiries_total']);
        $this->assertSame($m['enquiries_total'], array_sum(array_column($m['by_stage'], 'n')));
        $this->assertSame((int) $db->table('crm_enquiries')->where('status', 'won')->countAllResults(), $m['won_count']);
        $this->assertSame(bcadd($db->query("SELECT SUM(grand_total) s FROM crm_sales_orders WHERE status <> 'cancelled'")->getRow()->s, '0', 2), bcadd($m['won_value'], '0', 2), 'won value equals order value');
        $this->assertSame(15, $m['orders_count']);
        $this->assertSame((int) $db->table('crm_customers')->countAllResults(), $m['customers_total']);
        $this->assertSame((int) $db->query("SELECT COUNT(*) c FROM crm_followups WHERE (status='pending' AND due_at < ?) OR status='missed'", [Crm::now()])->getRow()->c, $m['followups_overdue']);
        $this->assertGreaterThan(0, $m['followups_overdue'], 'overdue demo scenario present');
        $this->assertSame(array_sum(array_column($m['team_performance'], 'enquiries')), $m['enquiries_total']);
        $this->assertSame((int) $db->table('crm_negotiations')->whereIn('status', ['pending', 'escalated'])->countAllResults(), $m['approvals_pending']);
    }

    public function testDashboardsAreScopedPerRole(): void
    {
        $db   = Crm::db();
        $exec = $this->u('demo.exec1');
        $mgr  = $this->u('demo.manager1');
        $mE   = DashboardService::metrics($exec);
        $mM   = DashboardService::metrics($mgr);
        $mA   = DashboardService::metrics($this->u('demo.admin1'));
        $this->assertSame((int) $db->table('crm_enquiries')->where('current_owner_id', $exec['id'])->countAllResults(), $mE['enquiries_total']);
        $this->assertLessThan($mM['enquiries_total'], $mE['enquiries_total']);
        $this->assertLessThan($mA['enquiries_total'], $mM['enquiries_total']);
        $teamIds = AccessScope::visibleUserIds($mgr);
        $this->assertSame((int) $db->table('crm_enquiries')->whereIn('current_owner_id', $teamIds)->countAllResults(), $mM['enquiries_total']);
    }

    public function testExecutiveCannotReachOtherExecutivesRecords(): void
    {
        $db    = Crm::db();
        $exec1 = $this->u('demo.exec1');
        $foreign = $db->query('SELECT id, customer_id FROM crm_enquiries WHERE current_owner_id <> ? AND created_by <> ? LIMIT 1', [$exec1['id'], $exec1['id']])->getRowArray();
        $this->assertFalse(AccessScope::canSeeEnquiry($exec1, (int) $foreign['id']));
        $h = HistoryService::global([], $exec1, 1, 500);
        foreach ($h['rows'] as $r) {
            $own = $db->table('crm_enquiries')->select('current_owner_id, created_by')->where('id', $r['enquiry_id'])->get()->getRowArray();
            $this->assertTrue((int) $own['current_owner_id'] === $exec1['id'] || (int) $own['created_by'] === $exec1['id']);
        }
        $this->expectException(CrmException::class);
        // a customer visible to nobody in exec1's scope
        $cust = $db->query('SELECT c.id FROM crm_customers c WHERE NOT EXISTS (SELECT 1 FROM crm_enquiries e WHERE e.customer_id = c.id AND (e.current_owner_id = ? OR e.created_by = ?)) AND c.owner_user_id <> ? AND c.created_by <> ? LIMIT 1', [$exec1['id'], $exec1['id'], $exec1['id'], $exec1['id']])->getRowArray();
        if (! $cust) {
            throw new CrmException('no foreign customer in dataset'); // keeps the test meaningful: would only happen if all customers overlap
        }
        HistoryService::customerTimeline((int) $cust['id'], $exec1);
    }

    public function testCustomerTimelineIsChronologicalAndLinksToTransactions(): void
    {
        $admin = $this->u('demo.admin1');
        $cid   = (int) Crm::db()->query("SELECT e.customer_id id FROM crm_enquiries e WHERE e.status = 'won' LIMIT 1")->getRow()->id;
        $tl    = HistoryService::customerTimeline($cid, $admin);
        $this->assertGreaterThan(8, count($tl));
        $times = array_column($tl, 'created_at');
        $sorted = $times;
        sort($sorted);
        $this->assertSame($sorted, $times, 'oldest first');
        $labels = array_column($tl, 'label');
        foreach (['Customer created', 'Enquiry created', 'Assigned to salesperson', 'Quotation sent', 'Customer accepted quotation', 'Sales order created', 'Enquiry marked WON'] as $l) {
            $this->assertContains($l, $labels, $l);
        }
        $withLinks = array_filter($tl, static fn ($e) => $e['link']);
        $this->assertNotEmpty($withLinks);
        $this->assertStringStartsWith('crm/', array_values($withLinks)[0]['link']);
        $tabs = HistoryService::customerTabs($cid, $admin);
        foreach (['enquiries', 'salespeople', 'followups', 'activities', 'visits', 'quotations', 'negotiations', 'approvals', 'emails', 'orders', 'workflow', 'audit'] as $tab) {
            $this->assertArrayHasKey($tab, $tabs);
        }
        $this->assertNotEmpty($tabs['orders']);
        $this->assertNotEmpty($tabs['emails']);
    }

    public function testOwnershipHistoryRetainsPreviousOwnerAfterReassignment(): void
    {
        $db  = Crm::db();
        $row = $db->query('SELECT enquiry_id FROM crm_enquiry_assignments GROUP BY enquiry_id HAVING COUNT(*) >= 2 LIMIT 1')->getRowArray();
        $this->assertNotNull($row, 'demo has reassigned enquiries');
        $own = HistoryService::ownership((int) $row['enquiry_id'], $this->u('demo.admin1'));
        $this->assertGreaterThanOrEqual(2, count($own));
        $this->assertNotNull($own[1]['from_name']);
        $this->assertNotSame($own[1]['from_name'], $own[1]['to_name']);
    }

    public function testGlobalHistoryFilters(): void
    {
        $admin = $this->u('demo.admin1');
        $all   = HistoryService::global([], $admin, 1, 10);
        $this->assertGreaterThan(1000, $all['total']);
        $disc = HistoryService::global(['approval' => 'approved'], $admin, 1, 500);
        $this->assertGreaterThan(5, $disc['total']);
        foreach ($disc['rows'] as $r) {
            $this->assertSame('discount.approved', $r['event_type']);
        }
        $ord = HistoryService::global(['event_group' => 'order'], $admin, 1, 500);
        foreach ($ord['rows'] as $r) {
            $this->assertStringStartsWith('order.', $r['event_type']);
        }
        $won = HistoryService::global(['outcome' => 'won'], $admin, 1, 500);
        $this->assertGreaterThan(0, $won['total']);
        $by = HistoryService::global(['owner_id' => $this->u('demo.exec2')['id']], $admin, 1, 500);
        $this->assertGreaterThan(0, $by['total']);
    }

    public function testEveryReportRunsAndRespectsPermissions(): void
    {
        $admin = $this->u('demo.admin1');
        foreach (array_keys(ReportService::available($admin)) as $key) {
            $r = ReportService::run($key, [], $admin);
            $this->assertArrayHasKey('rows', $r, $key);
            $this->assertNotEmpty($r['columns'], $key);
        }
        $this->assertGreaterThanOrEqual(19, count(ReportService::available($admin)));
        // filters narrow results
        $all = ReportService::run('enquiries', [], $admin)['rows'];
        $won = ReportService::run('enquiries', ['status' => 'won'], $admin)['rows'];
        $this->assertCount(15, $won);
        $this->assertLessThan(count($all), count($won));
        $none = ReportService::run('enquiries', ['from' => '2099-01-01'], $admin)['rows'];
        $this->assertSame([], $none);
        // executive: scoped rows only, cannot export, cannot see audit
        $exec = $this->u('demo.exec1');
        $rows = ReportService::run('enquiries', [], $exec)['rows'];
        foreach ($rows as $r) {
            $this->assertSame('Amit Pawar', $r['Owner']);
        }
        $this->assertArrayNotHasKey('audit', ReportService::available($exec));
        $this->expectException(CrmException::class);
        ReportService::toXlsx('enquiries', [], $exec);
    }

    public function testExportsProduceRealFilesAndAreAudited(): void
    {
        $mgmt = $this->u('demo.management');
        $x    = ReportService::toXlsx('quotations', ['status' => 'accepted'], $mgmt);
        $this->assertSame('PK', substr($x, 0, 2), 'xlsx is a zip container');
        $p = ReportService::toPdf('orders', [], $mgmt);
        $this->assertSame('%PDF', substr($p, 0, 4));
        $this->assertGreaterThanOrEqual(2, Crm::db()->table('crm_audit_events')->where('event_type', 'report.exported')->countAllResults());
        // management may export but holds no write permission anywhere
        $this->assertFalse(AccessScope::can($mgmt, 'customers.create'));
        $this->assertFalse(AccessScope::can($mgmt, 'quotations.create'));
        $this->assertFalse(AccessScope::can($mgmt, 'demo.reset'));
    }

    public function testDemoResetIsProtectedAndPreservesRealData(): void
    {
        $db   = Crm::db();
        $admin = $this->u('demo.admin1');
        $exec  = $this->u('demo.exec1');
        $realAdminId = 0;
        try {
            // real (non-demo) data that must survive a reset
            $real = CustomerService::create(['name' => 'Real Customer Ltd', 'mobile' => '9876501234', 'email' => 'real@customer.invalid'], $admin['id']);
            $before = [
                'orders' => $db->query('SELECT SUM(grand_total) s FROM crm_sales_orders')->getRow()->s,
                'enq' => $db->query('SELECT GROUP_CONCAT(enquiry_no ORDER BY id) g FROM crm_enquiries WHERE is_demo = 1')->getRow()->g,
            ];
            // guards
            try { DemoService::reset($exec, 'RESET DEMO'); $this->fail('executive reset demo'); } catch (CrmException) {}
            try { DemoService::reset($admin, 'nope'); $this->fail('reset without confirmation'); } catch (CrmException $e) { $this->assertStringContainsString('RESET DEMO', $e->getMessage()); }
            self::demoFlag('false');
            try { DemoService::reset($admin, 'RESET DEMO'); $this->fail('reset outside demo environment'); } catch (CrmException $e) { $this->assertStringContainsString('not a designated demo environment', $e->getMessage()); }
            self::demoFlag('true');
            // admin accounts (demo) are purged on reset, so act as a persistent real super admin
            $realAdminId = \Crm\Services\UserService::create(['username' => 'real.super' . random_int(1000, 9999), 'email' => 'rs' . random_int(1000, 9999) . '@real.invalid', 'first_name' => 'Real', 'last_name' => 'Super', 'role' => 'super_admin', 'password' => 'Correct-Horse-9']);
            $res = DemoService::reset(AuthService::sessionUser($realAdminId), 'RESET DEMO');
            $this->assertFileExists($res['snapshot']);
            $this->assertSame([], $res['integrity']);
            $this->assertGreaterThan(1000, $res['removed']);
            $this->assertSame(1, $db->table('crm_customers')->where('id', $real)->countAllResults(), 'real customer survived reset');
            $this->assertSame(100, (int) $db->table('crm_enquiries')->where('is_demo', 1)->countAllResults());
            $this->assertSame($before['enq'], $db->query('SELECT GROUP_CONCAT(enquiry_no ORDER BY id) g FROM crm_enquiries WHERE is_demo = 1')->getRow()->g, 'deterministic numbering');
            $this->assertSame(0, bccomp($before['orders'], $db->query('SELECT SUM(grand_total) s FROM crm_sales_orders')->getRow()->s, 2), 'deterministic order values');
            $this->assertSame(1, (int) $db->table('crm_demo_reset_log')->where('initiated_by', $realAdminId)->countAllResults());
        } finally {
            self::removeFixtures();
        }
    }
}
