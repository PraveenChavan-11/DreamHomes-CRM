<?php

namespace Tests\Crm;

use Crm\Services\ActivityService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\EnquiryService;
use Crm\Services\NegotiationService;
use Crm\Services\PdfService;
use Crm\Services\ProductService;
use Crm\Services\QuotationCalculator;
use Crm\Services\QuotationService;

/**
 * Manual product / variant / size / LINE-TOTAL / negotiated-total editing and the version tracking behind it.
 * Pure-calculator tests need no data; the rest run in a rolled-back transaction like every CRM test.
 */
final class WebQuotationTest extends CrmTestCase
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
        $this->admin   = $this->sessionUser($this->user('admin'));
        $this->execId  = $this->user('sales_executive');
        $this->exec    = $this->sessionUser($this->execId);
        $db  = Crm::db();
        $cat = (int) $db->table('crm_product_categories')->where('name', 'Windows')->get()->getRow()->id;
        $tax = (int) $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow()->id;
        $mk  = fn ($sku, $name, $std, $min) => ProductService::save(null, ['sku' => $sku . bin2hex(random_bytes(2)), 'name' => $name, 'category_id' => $cat, 'unit' => 'sqft', 'pricing_model' => 'sqft', 'base_price' => '300', 'standard_price' => $std, 'min_price' => $min, 'tax_rate_id' => $tax], $this->admin);
        $this->winA = $mk('WA-', 'Aluminium Sliding Window', '1000', '700');
        $this->winB = $mk('WB-', 'UPVC Casement Window', '800', '600');
        static $m = 7300000000;
        $m += 17;
        $cid = CustomerService::create(['name' => 'Manual Pricing Customer', 'mobile' => (string) $m, 'email' => "w{$m}@demo.invalid", 'state' => 'Maharashtra'], $this->admin['id']);
        $this->enq = EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Manual Villa', 'current_owner_id' => $this->execId], $this->admin)['id'];
        ActivityService::recordContact($this->enq, $this->exec, 'call', 'Intro');
    }

    // ───────────── calculator (no DB) ─────────────

    public function testSolveUnitPriceHitsTheTypedLineTotalExactlyWhenThereIsNoPercentDiscount(): void
    {
        foreach ([['58.125', '123456.78'], ['72', '100000.00'], ['3', '17.31'], ['1.5', '99999.99']] as [$qty, $target]) {
            $r = QuotationCalculator::solveUnitPrice($qty, '0', '0', '18', $target);
            $this->assertSame($target, $r['line_total'], "qty {$qty}");
            $this->assertSame('0.00', $r['variance']);
            $again = QuotationCalculator::compute([['quantity' => $qty, 'unit_price' => $r['unit_price'], 'discount_pct' => 0, 'discount_amount' => $r['discount_amount'], 'tax_rate' => '18']], [], false);
            $this->assertSame($target, $again['lines'][0]['line_total'], 'the calculator reproduces the solved line total');
        }
    }

    public function testSolveUnitPriceIsDeterministicAndReportsVarianceWithAPercentDiscount(): void
    {
        $a = QuotationCalculator::solveUnitPrice('72', '5', '0', '18', '100000.00');
        $b = QuotationCalculator::solveUnitPrice('72', '5', '0', '18', '100000.00');
        $this->assertSame($a, $b);
        $this->assertLessThan(1.0, (float) $a['variance'], 'closest achievable total is within a rupee');
        $this->assertSame('0.00', $a['discount_amount'], 'a % discount line never gets a hidden rounding discount');
    }

    public function testGrandTotalFitLandsOnTheTargetAndNeverRaisesTheTotal(): void
    {
        $lines   = [['quantity' => '72', 'unit_price' => '800', 'discount_pct' => '0', 'discount_amount' => '0', 'tax_rate' => '18'], ['quantity' => '1', 'unit_price' => '25500', 'discount_pct' => '2', 'discount_amount' => '0', 'tax_rate' => '18']];
        $charges = [['name' => 'Installation', 'amount' => '5500', 'tax_rate' => '18']];
        $base    = QuotationCalculator::compute($lines, $charges, true);
        foreach (['90000', '99999', '95432'] as $t) {
            $f = QuotationCalculator::fitGrandTotal($lines, $charges, true, $t);
            $this->assertSame(number_format((float) $t, 2, '.', ''), $f['grand_total']);
            $this->assertSame('0.00', $f['variance']);
            $this->assertGreaterThan(0, (float) $f['adjustment'], 'the reduction is recorded as extra discount');
            $this->assertSame($f['grand_total'], $f['calc']['grand_total']);
        }
        $this->expectException(CrmException::class);
        QuotationCalculator::fitGrandTotal($lines, $charges, true, (string) ((float) $base['grand_total'] + 1000));
    }

    // ───────────── engine ─────────────

    public function testManualLineTotalIsStoredAsManualWithTheTypedTotal(): void
    {
        $q = QuotationService::create($this->enq, $this->admin, [['product_id' => $this->winA, 'variant' => '3-track', 'width' => 1200, 'height' => 1500, 'size_unit' => 'mm', 'nos' => 3, 'line_total' => '90000']]);
        $i = Crm::db()->table('crm_quotation_items')->where('version_id', $q['version_id'])->get()->getRowArray();
        $this->assertSame('90000.00', $i['line_total']);
        $this->assertSame('90000.00', $i['override_total']);
        $this->assertSame('manual', $i['price_source']);
        $this->assertSame('1000.00', $i['std_price'], 'catalogue price kept for audit');
        $this->assertEqualsWithDelta(58.125, (float) $i['quantity'], 0.001, 'quantity still comes from the size');
    }

    public function testManualReductionBelowStandardPriceCountsAgainstTheDiscountLimit(): void
    {
        // 5% executive limit; unit price 800 against standard 1000 = 20% implicit discount
        try {
            QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'quantity' => 10, 'unit_price' => '800']]);
            $this->fail('implicit 20% discount accepted for a 5% user');
        } catch (CrmException $e) {
            $this->assertStringContainsString('exceeds the limit', $e->getMessage());
        }
        try {
            QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'quantity' => 10, 'line_total' => '8500']]);
            $this->fail('a typed line total far below standard accepted for a 5% user');
        } catch (CrmException $e) {
            $this->assertStringContainsString('exceeds the limit', $e->getMessage());
        }
        $ok = QuotationService::create($this->enq, $this->admin, [['product_id' => $this->winA, 'quantity' => 10, 'unit_price' => '800']]);   // admin level may price freely
        $this->assertGreaterThan(0, $ok['id']);
    }

    public function testNegotiatedTotalIsAnExplicitTrackedAdjustmentBoundByTheLimits(): void
    {
        $q = QuotationService::create($this->enq, $this->admin, [['product_id' => $this->winA, 'quantity' => 100]], [], ['target_total' => '100000']);
        $v = Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRowArray();
        $this->assertSame('100000.00', $v['grand_total']);
        $this->assertSame('100000.00', $v['target_total']);
        $this->assertGreaterThan(0, (float) $v['adjustment_amount']);
        try {
            QuotationService::price([['product_id' => $this->winA, 'quantity' => 100]], [], $this->exec, null, '50000');
            $this->fail('negotiated total beyond the executive limit accepted');
        } catch (CrmException $e) {
            $this->assertStringContainsString('exceeds the limit', $e->getMessage());
        }
        try {
            QuotationService::price([['product_id' => $this->winA, 'quantity' => 100]], [], $this->admin, null, '999999999');
            $this->fail('negotiated total higher than the computed total accepted');
        } catch (CrmException $e) {
            $this->assertArrayHasKey('target_total', $e->errors);
        }
    }

    // ───────────── tracking ─────────────

    public function testEveryDraftSaveIsTrackedFieldByFieldWithWhoAndWhy(): void
    {
        $q = QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'variant' => '2-track', 'width' => 8, 'height' => 5, 'size_unit' => 'ft', 'nos' => 1]], [['name' => 'Installation', 'amount' => '1000']]);
        QuotationService::updateDraft($q['version_id'], $this->exec, [['product_id' => $this->winA, 'variant' => '3-track, black', 'width' => 10, 'height' => 5, 'size_unit' => 'ft', 'nos' => 1]], [['name' => 'Installation', 'amount' => '2500']],
            ['change_note' => 'Customer wants a wider black frame']);
        $log = QuotationService::changeLog($q['id'], $this->exec);
        $this->assertNotEmpty($log);
        foreach ($log as $c) {
            $this->assertSame(1, (int) $c['from_version_no']);
            $this->assertSame(1, (int) $c['to_version_no'], 'draft saves are recorded against the same version');
            $this->assertSame($this->execId, (int) $c['user_id']);
            $this->assertSame('Customer wants a wider black frame', $c['reason']);
        }
        $types = array_column($log, 'change_type');
        $this->assertContains('modified', $types);
        $this->assertContains('added', $types, 'the new installation charge is tracked too');
        $mod = array_values(array_filter($log, static fn ($c) => $c['change_type'] === 'modified' && str_starts_with((string) $c['item_ref'], 'Charge') === false))[0] ?? null;
        $this->assertNull($mod, 'variant/size are part of the line identity, so they surface as a replacement with field-level detail');
        $added = array_values(array_filter($log, static fn ($c) => $c['change_type'] === 'added' && ! str_starts_with((string) $c['item_ref'], 'Charge')))[0];
        $this->assertArrayHasKey('variant', $added['data']['replaces']['fields']);
        $this->assertSame('2-track', $added['data']['replaces']['fields']['variant']['from']);
        $this->assertSame('3-track, black', $added['data']['replaces']['fields']['variant']['to']);
        $this->assertArrayHasKey('width', $added['data']['replaces']['fields']);
        // an identical save records nothing
        $before = count($log);
        QuotationService::updateDraft($q['version_id'], $this->exec, [['product_id' => $this->winA, 'variant' => '3-track, black', 'width' => 10, 'height' => 5, 'size_unit' => 'ft', 'nos' => 1]], [['name' => 'Installation', 'amount' => '2500']]);
        $this->assertCount($before, QuotationService::changeLog($q['id'], $this->exec));
    }

    public function testSwappingTheProductOnALineIsRecordedAsAReplacementInARevision(): void
    {
        $q = QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'quantity' => 10]]);
        QuotationService::send($q['id'], $this->exec, 'manual');
        $r = QuotationService::revise($q['id'], $this->exec, [['product_id' => $this->winB, 'quantity' => 10]], [], [], null, 'Customer prefers UPVC');
        $this->assertSame(2, $r['version_no']);
        $add = array_values(array_filter(QuotationService::changeLog($q['id'], $this->exec), static fn ($c) => $c['change_type'] === 'added'))[0];
        $this->assertStringContainsString('UPVC Casement Window', $add['item_ref']);
        $this->assertStringContainsString('Aluminium Sliding Window', $add['data']['replaces']['item']);
        $this->assertArrayHasKey('unit_price', $add['data']['replaces']['fields']);
        $this->assertSame('1000.00', $add['data']['replaces']['fields']['unit_price']['from']);
        $this->assertSame('800.00', $add['data']['replaces']['fields']['unit_price']['to']);
    }

    public function testVersionHistoryKeepsTheSentVersionImmutableAndLinksTheNegotiationRound(): void
    {
        $q = QuotationService::create($this->enq, $this->exec, [['product_id' => $this->winA, 'quantity' => 10]]);
        QuotationService::send($q['id'], $this->exec, 'manual');
        $v1Total = Crm::db()->table('crm_quotation_versions')->where('id', $q['version_id'])->get()->getRow()->grand_total;
        $n = NegotiationService::request($q['id'], $this->exec, '10', 'Customer wants 10% off');
        $this->assertSame('manager', $n['required_level']);
        NegotiationService::decide($n['id'], $this->admin, 'approve', 'ok');   // admin level covers manager-level asks and sees every team
        $r = QuotationService::revise($q['id'], $this->exec, [['product_id' => $this->winB, 'variant' => 'White', 'quantity' => 12, 'discount_pct' => '10']], [], [], $n['id'], 'Approved discount with a different product');

        $versions = QuotationService::versions($q['id'], $this->exec);
        $this->assertSame([2, 1], array_map(static fn ($v) => (int) $v['version_no'], $versions));
        $this->assertNull($versions[1]['negotiation']);
        $this->assertSame($n['id'], (int) $versions[0]['negotiation']['id'], 'each round points at the version it produced');
        $this->assertSame($r['version_id'], (int) $versions[0]['negotiation']['resulting_version_id']);
        $this->assertNotEmpty($versions[0]['changes']);
        $this->assertSame($v1Total, $versions[1]['grand_total'], 'sent v1 is untouched');
        $this->assertSame(1, (int) $versions[1]['is_locked']);
        $this->assertSame($this->execId, (int) $versions[0]['created_by']);
        $this->expectException(CrmException::class);
        QuotationService::updateDraft($q['version_id'], $this->exec, [['product_id' => $this->winA, 'quantity' => 1]]);   // locked v1
    }

    public function testStoredPdfLookupIsPortableAcrossMachines(): void
    {
        $dir = PdfService::dir();
        file_put_contents($dir . 'WEBTEST-0001-V1.pdf', '%PDF-1.4 test');
        try {
            $this->assertSame($dir . 'WEBTEST-0001-V1.pdf', PdfService::resolve('D:\\Some\\Other\\Machine\\writable\\crm/quotations/WEBTEST-0001-V1.pdf'));
            $this->assertNull(PdfService::resolve('../../.env'));
            $this->assertNull(PdfService::resolve('/etc/passwd'));
            $this->assertNull(PdfService::resolve(null));
        } finally {
            @unlink($dir . 'WEBTEST-0001-V1.pdf');
        }
    }
}
