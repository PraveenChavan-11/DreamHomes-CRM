<?php

namespace Tests\Crm;

use Crm\Services\Crm;
use Crm\Services\CustomerService;
use Crm\Services\EnquiryService;
use Crm\Services\PaymentService;
use Crm\Services\PdfService;
use Crm\Services\ProductService;
use Crm\Services\QuotationCalculator;
use Crm\Services\QuotationService;
use Crm\Services\UserService;

final class A4QuotationPdfTest extends CrmTestCase
{
    private int $adminId;
    private array $admin;
    private int $execId;
    private array $exec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->user('admin');
        $this->admin   = $this->sessionUser($this->adminId);
        $this->execId  = $this->user('sales_executive');
        $this->exec    = $this->sessionUser($this->execId);
    }

    public function testInrWordsFormatting(): void
    {
        helper('crm');

        $this->assertSame('Zero Rupees Only', crm_inr_words(0));
        $this->assertSame('Fifty Thousand Rupees Only', crm_inr_words(50000));
        $this->assertSame('One Lakh Eighty Five Thousand Rupees Only', crm_inr_words(185000));
        $this->assertSame('Twelve Lakh Fifty Thousand Rupees Only', crm_inr_words(1250000));
        $this->assertSame('One Crore Twenty Five Lakh Rupees Only', crm_inr_words(12500000));
    }

    public function testQuotationPdfHtmlDimensionsAndBranding(): void
    {
        helper(['crm', 'url', 'number']);
        $db = Crm::db();
        $cat = (int) $db->table('crm_product_categories')->where('name', 'Windows')->get()->getRow()->id;
        $tax = (int) $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow()->id;

        $pid = ProductService::save(null, [
            'sku' => 'VIT-WIN-' . bin2hex(random_bytes(3)),
            'name' => 'Vitrum Slimline 3-Track Sliding Window',
            'brand' => 'Vitrum',
            'category_id' => $cat,
            'unit' => 'sqft',
            'pricing_model' => 'sqft',
            'base_price' => '1200',
            'standard_price' => '1800',
            'min_price' => '1500',
            'tax_rate_id' => $tax,
            'specifications' => 'Toughened glass, powder coated finish',
        ], $this->admin);

        $cid = CustomerService::create([
            'name' => 'Aditi Kulkarni',
            'customer_type' => 'individual',
            'mobile' => '98888' . sprintf('%05d', mt_rand(10000, 99999)),
            'email' => 'aditi.' . uniqid() . '@demo.invalid',
            'site_address' => 'Villa 14, Supreme Palms, Balewadi',
            'billing_address' => 'Villa 14, Supreme Palms, Balewadi',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pin_code' => '411045',
            'owner_user_id' => $this->execId,
        ], $this->adminId);

        $enq = EnquiryService::create([
            'customer_id' => $cid,
            'project_name' => 'Supreme Palms Villa 14',
            'current_owner_id' => $this->execId,
        ], $this->admin);

        \Crm\Services\ActivityService::recordContact($enq['id'], $this->exec, 'call', 'Initial consultation', 'Interested in Vitrum windows');

        $res = QuotationService::create(
            $enq['id'],
            $this->admin,
            [['product_id' => $pid, 'quantity' => '120', 'unit_price' => '1800.00', 'discount_pct' => '5.00']],
            [['name' => 'Installation & Sub-frame Fixing', 'amount' => '12000.00']],
            [
                'valid_until' => date('Y-m-d', strtotime('+30 days')),
                'terms' => '50% advance with PO, 40% prior to dispatch, 10% upon installation handover.',
            ]
        );
        $versionId = (int) $res['version_id'];

        $render = PdfService::quotationHtml($versionId);
        $html = $render['html'];

        // 1. A4 Portrait dimensions verification
        $this->assertStringContainsString('210mm 297mm portrait', $html, 'PDF must specify exact A4 portrait dimensions');
        $this->assertStringContainsString('table-header-group', $html, 'Headers must repeat across pages cleanly');

        // 2. DHIS Brand verification
        $this->assertStringContainsString('Dream Homes Interior Solutions', $html);
        $this->assertStringContainsString('Vitrum Slimline 3-Track Sliding Window', $html);
        $this->assertStringContainsString('Aditi Kulkarni', $html);

        // 3. Indian Currency Words and GST verification
        $this->assertStringContainsString('Rupees Only', $html);
        $this->assertStringContainsString('₹', $html);
        $this->assertStringContainsString('CGST', $html);
        $this->assertStringContainsString('SGST', $html);

        // 4. Signatures & Acceptance
        $this->assertStringContainsString('Acceptance Signature', $html);
        $this->assertStringContainsString('Signatory', $html);
    }

    public function testPaymentReceiptPdfHtml(): void
    {
        helper(['crm', 'url', 'number']);
        $db = Crm::db();

        $cid = CustomerService::create([
            'name' => 'Vikram Malhotra',
            'customer_type' => 'individual',
            'mobile' => '97777' . sprintf('%05d', mt_rand(10000, 99999)),
            'email' => 'vikram.' . uniqid() . '@demo.invalid',
            'site_address' => 'Penthouse 12B, Panchshil Towers',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pin_code' => '411014',
            'owner_user_id' => $this->execId,
        ], $this->adminId);

        // Create dummy order
        $db->table('crm_enquiries')->insert([
            'enquiry_no' => 'ENQ-T-' . uniqid(),
            'customer_id' => $cid,
            'current_owner_id' => $this->execId,
            'project_name' => 'Panchshil 12B',
            'status' => 'won',
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        $enquiryId = (int) $db->insertID();

        $db->table('crm_quotations')->insert([
            'quotation_no' => 'QT-T-' . uniqid(),
            'customer_id' => $cid,
            'enquiry_id' => $enquiryId,
            'status' => 'accepted',
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        $quotationId = (int) $db->insertID();

        $db->table('crm_quotation_versions')->insert([
            'quotation_id' => $quotationId,
            'version_no' => 1,
            'subtotal' => 100000.00,
            'discount_total' => 0.00,
            'tax_total' => 18000.00,
            'charges_total' => 0.00,
            'grand_total' => 118000.00,
            'is_accepted' => 1,
            'created_at' => Crm::now(),
        ]);
        $versionId = (int) $db->insertID();
        $db->table('crm_quotations')->where('id', $quotationId)->update(['current_version_id' => $versionId]);

        $db->table('crm_sales_orders')->insert([
            'order_no' => 'SO-T-' . uniqid(),
            'quotation_id' => $quotationId,
            'version_id' => $versionId,
            'customer_id' => $cid,
            'enquiry_id' => $enquiryId,
            'salesperson_id' => $this->execId,
            'status' => 'draft',
            'subtotal' => 100000.00,
            'discount_total' => 0.00,
            'tax_total' => 18000.00,
            'charges_total' => 0.00,
            'grand_total' => 118000.00,
            'advance_amount' => 0.00,
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        $orderId = (int) $db->insertID();

        $pay = PaymentService::recordPayment([
            'order_id' => $orderId,
            'amount' => 59000.00,
            'payment_type' => 'advance',
            'payment_mode' => 'bank_transfer',
            'transaction_reference' => 'NEFT-DHIS-8899',
            'payment_date' => date('Y-m-d'),
        ], $this->admin);

        $receiptHtml = PaymentService::receiptHtml((int) $pay['id']);

        $this->assertStringContainsString('PAYMENT RECEIPT', $receiptHtml);
        $this->assertStringContainsString($pay['receipt_no'], $receiptHtml);
        $this->assertStringContainsString('Vikram Malhotra', $receiptHtml);
        $this->assertStringContainsString('Fifty Nine Thousand Rupees Only', $receiptHtml);
        $this->assertStringContainsString('BANK TRANSFER', $receiptHtml);
        $this->assertStringContainsString('NEFT-DHIS-8899', $receiptHtml);
        $this->assertStringContainsString('Authorised Accounts Signatory', $receiptHtml);
    }
}
