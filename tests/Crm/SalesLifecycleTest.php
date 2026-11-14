<?php

namespace Tests\Crm;

use Crm\Services\AccessScope;
use Crm\Services\ActivityService;
use Crm\Services\CrmException;
use Crm\Services\Crm;
use Crm\Services\CustomerService;
use Crm\Services\EmailService;
use Crm\Services\EnquiryService;
use Crm\Services\Money;
use Crm\Services\NegotiationService;
use Crm\Services\OrderService;
use Crm\Services\ProductService;
use Crm\Services\QuotationCalculator;
use Crm\Services\QuotationService;
use Crm\Services\UserService;

final class SalesLifecycleTest extends CrmTestCase
{
    private int $adminId;
    private array $admin;
    private int $mgrId;
    private int $execId;
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->user('admin');
        $this->admin   = $this->sessionUser($this->adminId);
        $this->mgrId   = $this->user('sales_manager');
        $this->execId  = $this->user('sales_executive');
        $team          = UserService::createTeam('LC' . bin2hex(random_bytes(3)), $this->mgrId);
        UserService::addToTeam($team, $this->execId);
        $db  = Crm::db();
        $cat = (int) $db->table('crm_product_categories')->where('name', 'Windows')->get()->getRow()->id;
        $tax = (int) $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow()->id;
        $this->productId = ProductService::save(null, ['sku' => 'T-' . bin2hex(random_bytes(3)), 'name' => 'Test Window', 'category_id' => $cat, 'unit' => 'sqft', 'pricing_model' => 'sqft', 'base_price' => '500', 'standard_price' => '900', 'min_price' => '800', 'tax_rate_id' => $tax], $this->admin);
    }

    private function enquiry(): array
    {
        static $m = 8000000000;
        $m += 11;
        $cid = CustomerService::create(['name' => 'Rohan Deshmukh', 'mobile' => (string) $m, 'email' => "r{$m}@demo.invalid", 'state' => 'Maharashtra', 'city' => 'Pune'], $this->adminId);
        $r   = EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Wakad Villa', 'current_owner_id' => $this->execId], $this->admin);
        ActivityService::recordContact($r['id'], $this->sessionUser($this->execId), 'call', 'Intro call', 'Interested');

        return $r + ['customer_id' => $cid];
    }

    public function testCalculatorIsExactAndRoundsConsistently(): void
    {
        $c = QuotationCalculator::compute(
            [['quantity' => '3', 'unit_price' => '33.33', 'discount_pct' => '10', 'tax_rate' => '18'], ['quantity' => '1.5', 'unit_price' => '1000', 'tax_rate' => '18']],
            [['name' => 'Installation', 'amount' => '500', 'tax_rate' => '18']],
            true
        );
        // line1: 99.99 - 10% (10.00) = 89.99 taxable, tax 16.20 ; line2: 1500 taxable, tax 270.00
        $this->assertSame('99.99', $c['lines'][0]['line_subtotal']);
        $this->assertSame('10.00', $c['lines'][0]['discount_amount']);
        $this->assertSame('89.99', $c['lines'][0]['taxable_amount']);
        $this->assertSame('16.20', $c['lines'][0]['tax_amount']);
        $this->assertSame('1589.99', $c['taxable_total']);
        $this->assertSame('500.00', $c['charges_total']);
        $this->assertSame('376.20', $c['tax_total']);               // 16.20 + 270 + 90
        $this->assertSame('2466.19', bcadd(bcadd($c['taxable_total'], $c['charges_total'], 2), $c['tax_total'], 2));
        $this->assertSame('2466.00', $c['grand_total']);
        $this->assertSame('-0.19', $c['round_off']);
    }

    public function testCalculatorRejectsBadInput(): void
    {
        $this->expectException(CrmException::class);
        QuotationCalculator::compute([['quantity' => '0', 'unit_price' => '10', 'tax_rate' => '18']]);
    }

    public function testExecutiveCannotExceedDiscountLimitOrMinPrice(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        try {
            QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '10', 'discount_pct' => '9']]);   // limit 5%
            $this->fail('executive exceeded discount limit');
        } catch (CrmException $x) {
            $this->assertStringContainsString('exceeds the limit', $x->getMessage());
        }
        $this->expectException(CrmException::class);   // unit price below min price 800
        QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '10', 'unit_price' => '700']]);
    }

    public function testQuotationCreateSendLockAndRevisionPreservesHistory(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '100', 'discount_pct' => '2']], [['name' => 'Installation', 'amount' => '5000']]);
        $this->assertMatchesRegularExpression('/^QT-\d+$/', $q['quotation_no']);
        $this->assertSame('quotation_preparation', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);

        $sent = QuotationService::send($q['id'], $exec);
        $this->assertFileExists($sent['pdf_path']);
        $this->assertSame('%PDF', substr((string) file_get_contents($sent['pdf_path']), 0, 4));
        $v1 = Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRowArray();
        $this->assertSame('1', $v1['is_locked']);
        $this->assertSame(64, strlen($v1['pdf_sha256']));
        $this->assertSame('quotation_sent', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);

        // email attempt recorded honestly: log_only mode never claims sent/delivered
        $mail = Crm::db()->table('crm_email_log')->where('quotation_id', $q['id'])->get()->getRowArray();
        $this->assertSame('queued', $mail['status']);
        $this->assertSame('log_only', $mail['provider']);
        $this->assertStringContainsString('NOT TRANSMITTED', $mail['provider_response']);
        $this->assertStringContainsString($q['quotation_no'], $mail['subject']);

        // locked version cannot be edited
        try {
            QuotationService::updateDraft($q['version_id'], $exec, [['product_id' => $this->productId, 'quantity' => '1']]);
            $this->fail('locked version edited');
        } catch (CrmException $x) {
            $this->assertStringContainsString('locked', $x->getMessage());
        }
        // revision creates V2 and leaves V1 byte-for-byte intact
        $rev = QuotationService::revise($q['id'], $exec, [['product_id' => $this->productId, 'quantity' => '100', 'discount_pct' => '3']], [['name' => 'Installation', 'amount' => '5000']], [], null, 'Customer asked for a slightly better rate');
        $this->assertSame(2, $rev['version_no']);
        $v1b = Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRowArray();
        $this->assertSame($v1, $v1b);
        $this->assertFileExists($sent['pdf_path']);
        $cmp = QuotationService::compare($q['id'], 1, 2, $exec);
        $this->assertSame('modified', $cmp['items'][0]['change']);
        $this->assertNotSame('0.00', $cmp['totals']['grand_total']['delta']);
    }

    public function testSendWithoutCustomerEmailIsRefusedAndNothingLocked(): void
    {
        $e    = $this->enquiry();
        Crm::db()->table('crm_customers')->where('id', $e['customer_id'])->update(['email' => null]);
        $exec = $this->sessionUser($this->execId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '1']]);
        try {
            QuotationService::send($q['id'], $exec);
            $this->fail('sent with no email');
        } catch (CrmException) {
        }
        $this->assertSame('0', Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRow()->is_locked);
    }

    public function testNegotiationApprovalLimitsAndSelfApprovalBlocked(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $mgr  = $this->sessionUser($this->mgrId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '100']]);
        QuotationService::send($q['id'], $exec);

        $n = NegotiationService::request($q['id'], $exec, '10', 'Customer asked for price cut');
        $this->assertSame('pending', $n['status']);
        $this->assertSame('manager', $n['required_level']);
        $this->assertSame('negotiation', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);

        // executive cannot approve own / any request
        try {
            NegotiationService::decide($n['id'], $exec, 'approve', 'ok');
            $this->fail('executive approved');
        } catch (CrmException) {
        }
        NegotiationService::decide($n['id'], $mgr, 'approve', 'Approved for strategic deal');
        $row = Crm::db()->table('crm_negotiations')->where('id', $n['id'])->get()->getRowArray();
        $this->assertSame('approved', $row['status']);
        $appr = Crm::db()->table('crm_discount_approvals')->where('negotiation_id', $n['id'])->get()->getRowArray();
        $this->assertSame($this->mgrId, (int) $appr['approver_id']);
        // approved discount unlocks a revision above the executive's own 5% limit
        $rev = QuotationService::revise($q['id'], $exec, [['product_id' => $this->productId, 'quantity' => '100', 'discount_pct' => '10']], [], [], $n['id']);
        $this->assertSame(2, $rev['version_no']);
        $this->assertSame('applied', Crm::db()->table('crm_negotiations')->where('id', $n['id'])->get()->getRow()->status);
    }

    public function testManagerCannotApproveBeyondLimitMustEscalateToAdmin(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '100']]);
        QuotationService::send($q['id'], $exec);
        $n = NegotiationService::request($q['id'], $exec, '25', 'Big discount ask');
        $this->assertSame('admin', $n['required_level']);
        try {
            NegotiationService::decide($n['id'], $this->sessionUser($this->mgrId), 'approve', 'x');
            $this->fail('manager approved admin-level request');
        } catch (CrmException) {
        }
        NegotiationService::decide($n['id'], $this->admin, 'approve', 'Admin approved');
        $this->assertSame('approved', Crm::db()->table('crm_negotiations')->where('id', $n['id'])->get()->getRow()->status);
    }

    public function testRejectedRequestRestoresQuotationAndAllowsResubmit(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $mgr  = $this->sessionUser($this->mgrId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '10']]);
        QuotationService::send($q['id'], $exec);
        $n = NegotiationService::request($q['id'], $exec, '12', 'ask');
        NegotiationService::decide($n['id'], $mgr, 'return', 'Try 8%');
        $this->assertSame('sent', Crm::db()->table('crm_quotations')->where('id', $q['id'])->get()->getRow()->status);
        $n2 = NegotiationService::resubmit($n['id'], $exec, '8', 'Revised ask');
        NegotiationService::decide($n2['id'], $mgr, 'reject', 'No');
        $this->assertSame('rejected', Crm::db()->table('crm_negotiations')->where('id', $n2['id'])->get()->getRow()->status);
        $this->assertSame(2, Crm::db()->table('crm_discount_approvals')->whereIn('negotiation_id', [$n['id'], $n2['id']])->countAllResults());
    }

    public function testOrderConversionWonAndDuplicatePrevention(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $mgr  = $this->sessionUser($this->mgrId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '50', 'discount_pct' => '1']]);
        try {
            OrderService::createFromQuotation($q['id'], $mgr);
            $this->fail('order from non-accepted quotation');
        } catch (CrmException $x) {
            $this->assertStringContainsString('accepted', $x->getMessage());
        }
        QuotationService::send($q['id'], $exec);
        QuotationService::recordResponse($q['id'], $exec, 'accepted', 'Customer confirmed by phone');
        try {
            OrderService::createFromQuotation($q['id'], $exec);   // executive lacks orders.manage
            $this->fail('executive created order');
        } catch (CrmException) {
        }
        $o = OrderService::createFromQuotation($q['id'], $mgr, ['advance_amount' => '10000', 'confirm' => true]);
        $this->assertSame($q['grand_total'], $o['grand_total'], 'final price preserved');
        try {
            OrderService::createFromQuotation($q['id'], $mgr);
            $this->fail('duplicate conversion');
        } catch (CrmException $x) {
            $this->assertStringContainsString('already been converted', $x->getMessage());
        }
        OrderService::markWon($e['id'], $mgr);
        $enq = Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRowArray();
        $this->assertSame('won', $enq['status']);
        $this->assertSame($q['grand_total'], $enq['final_value']);
        $this->assertSame($this->mgrId, (int) $enq['closed_by']);
        OrderService::setStatus($o['id'], 'closed', $mgr);
        $this->expectException(CrmException::class);
        OrderService::setStatus($o['id'], 'confirmed', $mgr);   // closed is terminal
    }

    public function testWonWithoutOrderRequiresAdminException(): void
    {
        $e = $this->enquiry();
        $this->expectException(CrmException::class);
        OrderService::markWon($e['id'], $this->sessionUser($this->mgrId));
    }

    public function testLostRequiresReasonCancelsOpenQuotesAndReopenIsAuthorised(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $mgr  = $this->sessionUser($this->mgrId);
        $q    = QuotationService::create($e['id'], $exec, [['product_id' => $this->productId, 'quantity' => '5']]);
        try {
            OrderService::markLost($e['id'], $exec, 0);
            $this->fail('lost without reason');
        } catch (CrmException) {
        }
        $rid = (int) Crm::db()->table('crm_lost_reasons')->get()->getRow()->id;
        OrderService::markLost($e['id'], $exec, $rid, 'Went with competitor', 'Brand X');
        $enq = Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRowArray();
        $this->assertSame('lost', $enq['status']);
        $this->assertSame('Brand X', $enq['lost_competitor']);
        $this->assertSame('cancelled', Crm::db()->table('crm_quotations')->where('id', $q['id'])->get()->getRow()->status);
        try {
            OrderService::reopen($e['id'], $exec, 'changed mind');   // executive may not reopen
            $this->fail('executive reopened');
        } catch (CrmException) {
        }
        OrderService::reopen($e['id'], $mgr, 'Customer came back');
        $this->assertSame('contacted', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);
        $this->assertSame(1, Crm::db()->table('crm_workflow_events')->where(['enquiry_id' => $e['id'], 'event_type' => 'enquiry.reopened'])->countAllResults());
    }

    public function testFollowupCompletionRescheduleHistoryAndReminderDedup(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $f1   = ActivityService::scheduleFollowup($e['id'], $exec, date('Y-m-d H:i:s', time() + 600), 'call', 'Discuss design');
        $next = ActivityService::completeFollowup($f1, $exec, 'Customer wants a visit', null, date('Y-m-d H:i:s', time() + 86400), 'Book visit');
        $this->assertNotNull($next);
        $row = Crm::db()->table('crm_followups')->where('id', $f1)->get()->getRowArray();
        $this->assertSame('completed', $row['status'], 'completed follow-up stays in history');
        $this->assertSame(1, Crm::db()->table('crm_activities')->where('followup_id', $f1)->countAllResults());

        $f2  = ActivityService::scheduleFollowup($e['id'], $exec, date('Y-m-d H:i:s', time() + 900), 'call');
        $new = ActivityService::rescheduleFollowup($f2, $exec, date('Y-m-d H:i:s', time() + 7200), 'Customer travelling');
        $this->assertSame('rescheduled', Crm::db()->table('crm_followups')->where('id', $f2)->get()->getRow()->status);
        $this->assertSame($f2, (int) Crm::db()->table('crm_followups')->where('id', $new)->get()->getRow()->rescheduled_from_id);

        // reminder + overdue + escalation are idempotent
        $past = ActivityService::scheduleFollowup($e['id'], $exec, date('Y-m-d H:i:s', time() + 60), 'call');
        Crm::db()->table('crm_followups')->where('id', $past)->update(['due_at' => date('Y-m-d H:i:s', time() - 3 * 86400)]);
        $r1 = ActivityService::runReminders();
        $r2 = ActivityService::runReminders();
        $this->assertGreaterThanOrEqual(1, $r1['missed']);
        $this->assertSame(0, $r2['missed']);
        $this->assertSame(0, $r2['escalated']);
        $this->assertSame(0, $r2['reminded']);
        $this->assertSame(1, Crm::db()->table('crm_notifications')->where('dedupe_key', "fu-overdue:{$past}")->countAllResults());
    }

    public function testSiteVisitMeasurementVersioning(): void
    {
        $e    = $this->enquiry();
        $exec = $this->sessionUser($this->execId);
        $vid  = ActivityService::scheduleVisit($e['id'], $exec, date('Y-m-d H:i:s', time() + 86400));
        $this->assertSame('site_visit', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);
        ActivityService::completeVisit($vid, $exec, ['Window 1' => '1200x1500 mm'], 'Wall ok', 'Slimline');
        ActivityService::saveMeasurements($vid, $exec, ['Window 1' => '1210x1500 mm'], 'Re-measured');
        $rows = Crm::db()->table('crm_site_measurements')->where('visit_id', $vid)->orderBy('version_no')->get()->getResultArray();
        $this->assertCount(2, $rows);
        $this->assertSame(['0', '1'], [$rows[0]['is_current'], $rows[1]['is_current']]);
        $this->assertStringContainsString('1200', $rows[0]['data'], 'old version preserved');
        $this->assertSame('quotation_preparation', Crm::db()->table('crm_enquiries')->where('id', $e['id'])->get()->getRow()->status);
    }

    public function testEmailRetryStatusesAreHonest(): void
    {
        $id = EmailService::queue('someone@demo.invalid', ['subject' => 'Hi', 'body' => 'Body']);
        $this->assertSame('queued', Crm::db()->table('crm_email_log')->where('id', $id)->get()->getRow()->status);
        $this->expectException(CrmException::class);   // provider confirmation only applies to SMTP-sent mail
        EmailService::confirmDelivery($id, 'delivered');
    }

    public function testAuditTrailAttributesUsersAndCapturesOwnershipHistory(): void
    {
        $e   = $this->enquiry();
        $ev  = Crm::db()->table('crm_workflow_events')->where('enquiry_id', $e['id'])->orderBy('id')->get()->getResultArray();
        $types = array_column($ev, 'event_type');
        $this->assertContains('enquiry.created', $types);
        $this->assertContains('enquiry.assigned', $types);
        $this->assertContains('activity.recorded', $types);
        $this->assertContains('enquiry.status_changed', $types);
        $created = array_values(array_filter($ev, static fn ($r) => $r['event_type'] === 'enquiry.created'))[0];
        $this->assertSame($this->adminId, (int) $created['user_id']);
        $this->assertSame(32, strlen($created['correlation_id']));
        unset($created);
        $this->assertTrue(AccessScope::can($this->sessionUser($this->mgrId), 'history.view'));
    }
}
