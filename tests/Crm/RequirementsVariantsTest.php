<?php

namespace Tests\Crm;

use Crm\Services\ActivityService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\EnquiryService;
use Crm\Services\ProductService;
use Crm\Services\QuotationService;
use Crm\Services\RequirementService;

final class RequirementsVariantsTest extends CrmTestCase
{
    private array $admin;
    private array $exec;
    private int $execId;
    private int $winA;
    private int $winB;
    private int $enq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin  = $this->sessionUser($this->user('admin'));
        $this->execId = $this->user('sales_executive');
        $this->exec   = $this->sessionUser($this->execId);
        $db  = Crm::db();
        $cat = (int) $db->table('crm_product_categories')->where('name', 'Windows')->get()->getRow()->id;
        $tax = (int) $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow()->id;
        $mk  = fn ($sku, $name, $std, $min) => ProductService::save(null, ['sku' => $sku . bin2hex(random_bytes(2)), 'name' => $name, 'category_id' => $cat, 'unit' => 'sqft', 'pricing_model' => 'sqft', 'base_price' => '300', 'standard_price' => $std, 'min_price' => $min, 'tax_rate_id' => $tax], $this->admin);
        $this->winA = $mk('A-', 'Aluminium Sliding Window', '1000', '900');
        $this->winB = $mk('B-', 'UPVC Casement Window', '800', '700');
        static $m = 7100000000;
        $m += 13;
        $cid = CustomerService::create(['name' => 'Variant Customer', 'mobile' => (string) $m, 'email' => "v{$m}@demo.invalid", 'state' => 'Maharashtra'], $this->admin['id']);
        $this->enq = EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Variant Villa', 'current_owner_id' => $this->execId], $this->admin)['id'];
        ActivityService::recordContact($this->enq, $this->exec, 'call', 'Intro');
    }

    public function testQuantityIsDerivedFromSizeAndPieces(): void
    {
        // 1200 x 1500 mm = 3.93701 ft x 4.92126 ft = 19.375 sq ft; x3 pieces = 58.125
        $q = RequirementService::quantityFromSize('sqft', 1200, 1500, 'mm', 3);
        $this->assertEqualsWithDelta(58.125, (float) $q, 0.01);
        $this->assertEqualsWithDelta(40.0, (float) RequirementService::quantityFromSize('sqft', 8, 5, 'ft', 1), 0.001);
        $this->assertEqualsWithDelta(24.0, (float) RequirementService::quantityFromSize('rft', 12, null, 'ft', 2), 0.001);
        $this->assertNull(RequirementService::quantityFromSize('unit', 12, 5, 'ft', 2));
    }

    public function testRequirementsAreEditableAtEveryFollowupAndFullyTracked(): void
    {
        $f   = ActivityService::scheduleFollowup($this->enq, $this->exec, date('Y-m-d H:i:s', time() + 3600));
        $r1  = RequirementService::add($this->enq, $this->exec, ['product_id' => $this->winA, 'variant' => '2-track, bronze', 'width' => 1200, 'height' => 1500, 'size_unit' => 'mm', 'nos' => 3, 'expected_price' => '950'], 'Initial ask', $f);
        $r2  = RequirementService::add($this->enq, $this->exec, ['description' => 'Custom arch window', 'quantity' => 2, 'unit' => 'nos', 'expected_price' => '18000'], 'Customer wants a custom piece', $f);
        $row = Crm::db()->table('crm_requirements')->where('id', $r1)->get()->getRowArray();
        $this->assertEqualsWithDelta(58.125, (float) $row['quantity'], 0.01, 'size drives quantity');

        // customer changes mind at the next follow-up: different size & variant, then drops the custom piece
        RequirementService::update($r1, $this->exec, ['variant' => '3-track, black', 'width' => 1500, 'height' => 1500], 'Wants bigger black frames', $f);
        RequirementService::drop($r2, $this->exec, 'Too costly, customer dropped it', $f);
        $this->assertCount(1, RequirementService::active($this->enq));
        $h = RequirementService::history($this->enq, $this->exec);
        $this->assertSame(['dropped', 'updated', 'added', 'added'], array_column($h, 'action'));
        $upd = $h[1];
        $this->assertSame('2-track, bronze', $upd['before']['variant']);
        $this->assertSame('3-track, black', $upd['after']['variant']);
        $this->assertSame($this->execId, (int) $upd['user_id']);
        $this->assertSame((int) $f, (int) $upd['followup_id']);
        $this->assertSame(4, Crm::db()->table('crm_workflow_events')->where('enquiry_id', $this->enq)->like('event_type', 'requirement.', 'after')->countAllResults(), 'timeline shows each change');
        // dropping needs a reason; nothing is deleted
        $this->assertSame(2, Crm::db()->table('crm_requirements')->where('enquiry_id', $this->enq)->countAllResults());
        $this->expectException(CrmException::class);
        RequirementService::drop($r1, $this->exec, ' ');
    }

    public function testQuotationBuiltFromRequirementsThenRevisedWithVariantSizePriceChangesTracked(): void
    {
        RequirementService::add($this->enq, $this->exec, ['product_id' => $this->winA, 'variant' => '2-track', 'width' => 8, 'height' => 5, 'size_unit' => 'ft', 'nos' => 2, 'expected_price' => '1000']);
        $q  = QuotationService::create($this->enq, $this->exec, [], [['name' => 'Installation', 'amount' => '4000']]);   // lines come from requirements
        $v1 = Crm::db()->table('crm_quotation_items')->where('version_id', $q['version_id'])->get()->getRowArray();
        $this->assertEqualsWithDelta(80.0, (float) $v1['quantity'], 0.001, '8x5 ft x 2 pcs');
        $this->assertSame('2-track', $v1['variant']);
        $this->assertSame('catalogue', $v1['price_source']);
        QuotationService::send($q['id'], $this->exec);
        $v1Total = Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRow()->grand_total;

        // negotiation visit: customer wants a different product + a bigger size + a hand-typed price on a custom item
        $rev = QuotationService::revise($q['id'], $this->exec, [
            ['product_id' => $this->winB, 'variant' => 'Casement, white', 'width' => 6, 'height' => 4, 'size_unit' => 'ft', 'nos' => 3, 'unit_price' => '780'],   // manual price below standard 800 but above min 700
            ['description' => 'Site-built bay window frame', 'unit' => 'nos', 'quantity' => 1, 'unit_price' => '25500', 'variant' => 'Custom'],               // free-typed item
        ], [['name' => 'Installation', 'amount' => '5500']], [], null, 'Customer switched to UPVC casement and added a custom bay window', null);
        $this->assertSame(2, $rev['version_no']);
        $items = Crm::db()->table('crm_quotation_items')->where('version_id', $rev['version_id'])->orderBy('line_no')->get()->getResultArray();
        $this->assertEqualsWithDelta(72.0, (float) $items[0]['quantity'], 0.001);
        $this->assertSame('manual', $items[0]['price_source']);
        $this->assertSame('manual', $items[1]['price_source']);
        $this->assertNull($items[1]['product_id']);

        $log   = QuotationService::changeLog($q['id'], $this->exec);
        $types = array_count_values(array_column($log, 'change_type'));
        $this->assertSame(2, $types['added']);
        $this->assertSame(1, $types['removed']);
        $this->assertArrayHasKey('totals', $types);
        $this->assertSame('Customer switched to UPVC casement and added a custom bay window', $log[0]['reason']);
        $this->assertSame($this->execId, (int) $log[0]['user_id']);

        // previous version untouched
        $this->assertSame($v1Total, Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRow()->grand_total);
        $cmp = QuotationService::compare($q['id'], 1, 2, $this->exec);
        $this->assertNotSame('0.00', $cmp['totals']['grand_total']['delta']);
        $this->assertSame('Customer switched to UPVC casement and added a custom bay window', $cmp['reason']);
    }

    public function testManualPriceCannotBypassMinimumPriceAndRevisionNeedsReason(): void
    {
        RequirementService::add($this->enq, $this->exec, ['product_id' => $this->winA, 'quantity' => 10]);
        $q = QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'quantity' => 10]]);
        QuotationService::send($q['id'], $this->exec);
        try {
            QuotationService::revise($q['id'], $this->exec, [['product_id' => $this->winA, 'quantity' => 10, 'unit_price' => '500']], [], [], null, 'Trying a deep manual cut');   // min 900
            $this->fail('manual price below minimum accepted');
        } catch (CrmException $e) {
            $this->assertStringContainsString('minimum selling price', $e->getMessage());
        }
        try {
            QuotationService::revise($q['id'], $this->exec, [['product_id' => $this->winA, 'quantity' => 10, 'unit_price' => '950']]);   // no reason
            $this->fail('revision without reason accepted');
        } catch (CrmException $e) {
            $this->assertArrayHasKey('reason', $e->errors);
        }
        $this->assertSame(1, Crm::db()->table('crm_quotation_versions')->where('quotation_id', $q['id'])->countAllResults(), 'failed attempts leave no version behind');
    }

    public function testPdfShowsVariantAndSize(): void
    {
        $q   = QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'variant' => 'Tilt-turn, grey', 'width' => 1200, 'height' => 1500, 'size_unit' => 'mm', 'nos' => 2]]);
        $html = \Crm\Services\PdfService::quotationHtml($q['version_id'])['html'];
        $this->assertStringContainsString('Tilt-turn, grey', $html);
        $this->assertStringContainsString('1200', $html);
    }
}
