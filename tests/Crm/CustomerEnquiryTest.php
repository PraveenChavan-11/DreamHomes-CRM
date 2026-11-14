<?php

namespace Tests\Crm;

use Crm\Services\AccessScope;
use Crm\Services\CrmException;
use Crm\Services\Crm;
use Crm\Services\CustomerService;
use Crm\Services\DuplicateException;
use Crm\Services\EnquiryService;
use Crm\Services\WorkflowService;
use Crm\Services\UserService;

final class CustomerEnquiryTest extends CrmTestCase
{
    private function cust(int $by, array $o = []): int
    {
        static $m = 9000000000;
        $m += 7;

        return CustomerService::create($o + ['name' => 'Test Customer', 'mobile' => (string) $m, 'email' => "c{$m}@demo.invalid"], $by);
    }

    public function testCustomerCreateValidateAndDuplicateDetection(): void
    {
        $admin = $this->user('admin');
        $id    = CustomerService::create(['name' => 'Asha Kulkarni', 'mobile' => '+91 98765 43210', 'email' => 'Asha@Demo.invalid'], $admin);
        $row   = Crm::db()->table('crm_customers')->where('id', $id)->get()->getRowArray();
        $this->assertMatchesRegularExpression('/^CUS-\d{4,}$/', $row['customer_no']);
        $this->assertSame('9876543210', $row['mobile_norm']);

        try {
            CustomerService::create(['name' => 'Other', 'mobile' => '9876543210'], $admin);
            $this->fail('duplicate mobile accepted');
        } catch (DuplicateException $e) {
            $this->assertCount(1, $e->matches);
        }
        $this->assertGreaterThan($id, CustomerService::create(['name' => 'Other', 'mobile' => '9876543210'], $admin, true));

        $this->expectException(CrmException::class);
        CustomerService::create(['name' => '', 'mobile' => '123', 'email' => 'bad'], $admin);
    }

    public function testCustomerWithHistoryCannotBeDeletedOnlyDeactivated(): void
    {
        $admin = $this->user('admin');
        $exec  = $this->user('sales_executive');
        $cid   = $this->cust($admin);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Villa'], $this->sessionUser($admin));
        try {
            CustomerService::delete($cid, $admin);
            $this->fail('deleted customer with history');
        } catch (CrmException $e) {
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }
        CustomerService::setStatus($cid, 'inactive', $admin, 'test');
        $this->assertSame('inactive', Crm::db()->table('crm_customers')->where('id', $cid)->get()->getRow()->status);
        // inactive customers cannot receive new enquiries
        $this->expectException(CrmException::class);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Another'], $this->sessionUser($admin));
        unset($exec);
    }

    public function testCustomerWithoutHistoryCanBeDeletedAndIsAudited(): void
    {
        $admin = $this->user('admin');
        $cid   = $this->cust($admin);
        CustomerService::delete($cid, $admin);
        $this->assertSame(0, Crm::db()->table('crm_customers')->where('id', $cid)->countAllResults());
        $this->assertSame(1, Crm::db()->table('crm_audit_events')->where(['event_type' => 'customer.deleted', 'entity_id' => (string) $cid])->countAllResults());
    }

    public function testAutoAssignRoundRobinCyclesEligibleExecutives(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $team  = UserService::createTeam('RR' . bin2hex(random_bytes(3)), null);
        $execs = [$this->user('sales_executive'), $this->user('sales_executive'), $this->user('sales_executive')];
        foreach ($execs as $x) {
            UserService::addToTeam($team, $x);
        }
        Crm::db()->table('crm_assignment_rules')->update(['is_active' => 0]);
        Crm::db()->table('crm_assignment_rules')->insert(['priority' => 1, 'mode' => 'round_robin', 'team_id' => $team]);
        $cid = $this->cust($admin['id']);
        $got = [];
        for ($i = 0; $i < 6; $i++) {
            $r     = EnquiryService::create(['customer_id' => $cid, 'project_name' => "P{$i}"], $admin);
            $got[] = $r['owner_id'];
            $this->assertSame('auto_round_robin', $r['assignment']);
        }
        $this->assertSame($execs, array_slice($got, 0, 3), 'first cycle visits each executive once');
        $this->assertSame(array_slice($got, 0, 3), array_slice($got, 3, 3), 'second cycle repeats the order');
    }

    public function testWorkloadAssignmentPicksLeastLoaded(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $team  = UserService::createTeam('WL' . bin2hex(random_bytes(3)), null);
        $busy  = $this->user('sales_executive');
        $idle  = $this->user('sales_executive');
        UserService::addToTeam($team, $busy);
        UserService::addToTeam($team, $idle);
        Crm::db()->table('crm_assignment_rules')->update(['is_active' => 0]);
        Crm::db()->table('crm_assignment_rules')->insert(['priority' => 1, 'mode' => 'workload', 'team_id' => $team]);
        $cid = $this->cust($admin['id']);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'A', 'current_owner_id' => $busy], $admin);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'B', 'current_owner_id' => $busy], $admin);
        $r = EnquiryService::create(['customer_id' => $cid, 'project_name' => 'C'], $admin);
        $this->assertSame($idle, $r['owner_id']);
        $this->assertSame('auto_workload', $r['assignment']);
    }

    public function testNoEligibleSalespersonFallsBackToUnassignedWithAlert(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $team  = UserService::createTeam('EMPTY' . bin2hex(random_bytes(3)), null);
        Crm::db()->table('crm_assignment_rules')->update(['is_active' => 0]);
        Crm::db()->table('crm_assignment_rules')->insert(['priority' => 1, 'mode' => 'team', 'team_id' => $team]);
        $r = EnquiryService::create(['customer_id' => $this->cust($admin['id']), 'project_name' => 'Orphan'], $admin);
        $this->assertNull($r['owner_id']);
        $this->assertSame('fallback_unassigned', $r['assignment']);
        $this->assertSame('new', Crm::db()->table('crm_enquiries')->where('id', $r['id'])->get()->getRow()->status);
    }

    public function testAutomaticAssignmentNeverOverwritesExistingOwnerAndManualAssignRefuses(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $a     = $this->user('sales_executive');
        $b     = $this->user('sales_executive');
        $r     = EnquiryService::create(['customer_id' => $this->cust($admin['id']), 'project_name' => 'Owned', 'current_owner_id' => $a], $admin);
        $this->assertSame($a, $r['owner_id']);
        try {
            EnquiryService::assign($r['id'], $b, $admin);
            $this->fail('assign() overwrote existing owner');
        } catch (CrmException $e) {
            $this->assertStringContainsString('already has an owner', $e->getMessage());
        }
        $this->assertSame($a, (int) Crm::db()->table('crm_enquiries')->where('id', $r['id'])->get()->getRow()->current_owner_id);
    }

    public function testReassignmentKeepsHistoryAndMovesPendingFollowups(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $a     = $this->user('sales_executive');
        $b     = $this->user('sales_executive');
        $r     = EnquiryService::create(['customer_id' => $this->cust($admin['id']), 'project_name' => 'Move', 'current_owner_id' => $a], $admin);
        EnquiryService::reassign($r['id'], $b, $admin, 'Workload balancing');
        $rows = Crm::db()->table('crm_enquiry_assignments')->where('enquiry_id', $r['id'])->orderBy('id')->get()->getResultArray();
        $this->assertCount(2, $rows);
        $this->assertSame($a, (int) $rows[1]['from_user_id']);
        $this->assertSame($b, (int) $rows[1]['to_user_id']);
        $this->assertSame('reassign', $rows[1]['method']);
        $ev = Crm::db()->table('crm_workflow_events')->where(['enquiry_id' => $r['id'], 'event_type' => 'enquiry.reassigned'])->get()->getRowArray();
        $this->assertStringContainsString((string) $a, $ev['prev_state']);
        $this->assertStringContainsString((string) $b, $ev['new_state']);

        $this->expectException(CrmException::class);   // reason mandatory
        EnquiryService::reassign($r['id'], $a, $admin, ' ');
    }

    public function testExecutiveCannotReassignOrSeeOthersEnquiry(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $a     = $this->user('sales_executive');
        $b     = $this->user('sales_executive');
        $r     = EnquiryService::create(['customer_id' => $this->cust($admin['id']), 'project_name' => 'Private', 'current_owner_id' => $a], $admin);
        $this->assertTrue(AccessScope::canSeeEnquiry($this->sessionUser($a), $r['id']));
        $this->assertFalse(AccessScope::canSeeEnquiry($this->sessionUser($b), $r['id']), 'cross-owner IDOR');
        $this->expectException(CrmException::class);
        EnquiryService::reassign($r['id'], $b, $this->sessionUser($a), 'try');
    }

    public function testStatusMachineEnforcesTransitionsPermissionsAndReasons(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $a     = $this->user('sales_executive');
        $r     = EnquiryService::create(['customer_id' => $this->cust($admin['id']), 'project_name' => 'Flow', 'current_owner_id' => $a], $admin);
        $exec  = $this->sessionUser($a);
        $this->assertSame('assigned', Crm::db()->table('crm_enquiries')->where('id', $r['id'])->get()->getRow()->status);

        WorkflowService::transition($r['id'], 'contacted', $exec);
        try {
            WorkflowService::transition($r['id'], 'won', $exec);   // contacted -> won not configured
            $this->fail('illegal jump allowed');
        } catch (CrmException $e) {
            $this->assertStringContainsString('not allowed', $e->getMessage());
        }
        try {
            WorkflowService::transition($r['id'], 'lost', $exec);  // reason required
            $this->fail('lost without reason');
        } catch (CrmException $e) {
            $this->assertStringContainsString('reason', $e->getMessage());
        }
        WorkflowService::transition($r['id'], 'lost', $exec, 'Customer chose competitor');
        $hist = array_column(Crm::db()->table('crm_enquiry_status_history')->where('enquiry_id', $r['id'])->orderBy('id')->get()->getResultArray(), 'to_status');
        $this->assertSame(['new', 'assigned', 'contacted', 'lost'], $hist);
        // reopen requires enquiries.reopen: executive lacks it
        $this->expectException(CrmException::class);
        WorkflowService::transition($r['id'], 'contacted', $exec, 'reopen');
    }

    public function testDuplicateEnquiryDetectionAndLeadScoring(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $cid   = $this->cust($admin['id']);
        $r     = EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Villa A', 'priority' => 'urgent', 'est_budget' => '600000', 'source_channel' => 'architect'], $admin, false, false);
        // urgent 20 + budget>=5L 15 + budget>=2L 8 (tiers are additive) + architect 10
        $this->assertSame(20 + 15 + 8 + 10,(int) Crm::db()->table('crm_enquiries')->where('id', $r['id'])->get()->getRow()->lead_score);
        $this->expectException(DuplicateException::class);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'villa a'], $admin, false, false);
    }
}
