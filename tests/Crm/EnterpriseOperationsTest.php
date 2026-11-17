<?php

namespace Tests\Crm;

use Crm\Services\Crm;
use Crm\Services\OrganisationService;
use Crm\Services\ContactService;
use Crm\Services\SalesActivityService;
use Crm\Services\ApprovalService;
use Crm\Services\TargetService;
use Crm\Services\CommunicationService;
use Crm\Services\NotificationService;

/**
 * Enterprise Sales Operations & Governance Test Suite
 */
final class EnterpriseOperationsTest extends CrmTestCase
{
    public function testOrganisationLifecycle(): void
    {
        $adminId = $this->user('sales_manager');
        $created = OrganisationService::create([
            'org_name' => 'Apex Infra Projects Ltd',
            'org_type' => 'Builder / Developer',
            'primary_email' => 'contact@apexinfra.example.com',
            'primary_phone' => '9822019999',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'account_owner_id' => $adminId,
            'lead_source' => 'Direct Referral',
            'account_status' => 'active',
            'account_rating' => 'A+',
        ], $adminId);

        $orgId = (int) $created['id'];
        $this->assertGreaterThan(0, $orgId);
        $org = OrganisationService::find($orgId);
        $this->assertNotNull($org);
        $this->assertSame('Apex Infra Projects Ltd', $org['name']);
        $this->assertStringStartsWith('ORG-', $org['org_no']);

        // Check 360 view
        $view = OrganisationService::get360View($orgId);
        $this->assertSame($orgId, (int) $view['org']['id']);
        $this->assertIsArray($view['contacts']);
        $this->assertIsArray($view['enquiries']);
    }

    public function testContactManagement(): void
    {
        $adminId = $this->user('sales_manager');
        $org = OrganisationService::create([
            'org_name' => 'Metro Developers',
            'primary_phone' => '9822019998',
        ], $adminId);
        $orgId = (int) $org['id'];

        $contact = ContactService::create([
            'organisation_id' => $orgId,
            'first_name' => 'Vikram',
            'last_name' => 'Malhotra',
            'designation' => 'Chief Procurement Officer',
            'mobile_number' => '9822012345',
            'email' => 'vikram@metrodev.example.com',
            'contact_role' => 'Decision Maker',
            'is_primary' => 1,
        ], $adminId);

        $contactId = (int) $contact['id'];
        $this->assertGreaterThan(0, $contactId);
        $found = ContactService::find($contactId);
        $this->assertSame('Vikram', $found['first_name']);
        $this->assertSame(1, (int) $found['is_primary']);
        $this->assertStringStartsWith('CNT-', $found['contact_no']);

        $byOrg = ContactService::getByOrganisation($orgId);
        $this->assertCount(1, $byOrg);
    }

    public function testSalesActivityAndConversion(): void
    {
        $adminId = $this->user('sales_manager');
        $repId = $this->user('sales_executive');

        $org = OrganisationService::create([
            'org_name' => 'Prestige Towers',
            'primary_phone' => '9822019997',
        ], $adminId);
        $orgId = (int) $org['id'];

        $act = SalesActivityService::create([
            'organisation_id' => $orgId,
            'activity_type' => 'Site Visit',
            'subject' => 'Architectural profile discussion',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'assigned_to' => $repId,
        ], $adminId);

        $actId = (int) $act['id'];
        $this->assertGreaterThan(0, $actId);
        $found = SalesActivityService::find($actId);
        $this->assertSame('Site Visit', $found['activity_type']);
        $this->assertStringStartsWith('ACT-', $found['activity_no']);

        // Convert activity to enquiry
        $enquiryId = SalesActivityService::convertToEnquiry($actId, $adminId);
        $this->assertGreaterThan(0, $enquiryId);

        $updatedAct = SalesActivityService::find($actId);
        $this->assertSame($enquiryId, (int) $updatedAct['converted_to_lead_id']);
    }

    public function testApprovalMatrixEvaluation(): void
    {
        // 4% discount -> Within rep discretion (no approval required)
        $eval0 = ApprovalService::evaluateDiscount(4.0, 100000.0);
        $this->assertFalse($eval0['requires_approval']);

        // 8% discount -> L1 Sales Manager approval
        $eval1 = ApprovalService::evaluateDiscount(8.0, 100000.0);
        $this->assertTrue($eval1['requires_approval']);
        $this->assertSame(1, $eval1['level_required']);

        // 12% discount -> L2 Senior Manager approval
        $eval2 = ApprovalService::evaluateDiscount(12.0, 200000.0);
        $this->assertTrue($eval2['requires_approval']);
        $this->assertSame(2, $eval2['level_required']);

        // 20% discount -> L3 Business Head approval
        $eval3 = ApprovalService::evaluateDiscount(20.0, 500000.0);
        $this->assertTrue($eval3['requires_approval']);
        $this->assertSame(3, $eval3['level_required']);
    }

    public function testSalesTargets(): void
    {
        $adminId = $this->user('sales_manager');
        $repId = $this->user('sales_executive');

        $target = TargetService::create([
            'user_id' => $repId,
            'frequency' => 'monthly',
            'period_start' => date('Y-m-01'),
            'period_end' => date('Y-m-t'),
            'period_label' => date('F Y'),
            'revenue_target' => 1500000.0,
            'orders_target' => 8,
            'activities_target' => 40,
            'quotations_target' => 20,
            'collections_target' => 1000000.0,
        ], $adminId);

        $targetId = (int) $target['id'];
        $this->assertGreaterThan(0, $targetId);
        $found = TargetService::find($targetId);
        $this->assertNotNull($found);
        $this->assertSame(1500000.0, (float) $found['revenue_target']);

        $actuals = TargetService::calculateActuals($targetId);
        $this->assertIsArray($actuals);
        $this->assertArrayHasKey('actual_revenue', $actuals);
        $this->assertArrayHasKey('revenue_pct', $actuals);
    }

    public function testCommunicationTemplatesAndEventTrigger(): void
    {
        CommunicationService::seedAll28Templates();
        $db = Crm::db();
        $count = $db->table('crm_email_templates')->countAllResults();
        $this->assertGreaterThanOrEqual(28, $count);

        $adminId = $this->user('sales_manager');
        NotificationService::trigger('quotation.approved', [
            'quotation_id' => 1,
            'quotation_no' => 'Q-TEST-001',
            'recipient_user_id' => $adminId,
            'to_email' => 'client@test.example.com',
            'customer_name' => 'Demo Client',
            'grand_total' => '1,50,000.00',
        ], $adminId);

        $notif = $db->table('crm_notifications')->where('user_id', $adminId)->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Approved', $notif['title']);
    }

    public function testEnterpriseViewsAndListingsRenderCleanly(): void
    {
        $adminId = $this->user('admin');
        $sessionUser = $this->sessionUser($adminId);
        Crm::setActor($adminId);

        $activities = SalesActivityService::list([], 1, 10);
        $this->assertIsArray($activities);
        $this->assertArrayHasKey('rows', $activities);
        if (! empty($activities['rows'])) {
            $this->assertArrayHasKey('subject', $activities['rows'][0]);
        }

        $approvals = ApprovalService::list([], $sessionUser, 1, 10);
        $this->assertIsArray($approvals);
        $this->assertArrayHasKey('rows', $approvals);

        $targets = TargetService::list([], $sessionUser, 1, 10);
        $this->assertIsArray($targets);
        $this->assertArrayHasKey('rows', $targets);
        if (! empty($targets['rows'])) {
            $this->assertArrayHasKey('actual_revenue', $targets['rows'][0]);
            $this->assertArrayHasKey('revenue_progress_pct', $targets['rows'][0]);
        }
    }
}
