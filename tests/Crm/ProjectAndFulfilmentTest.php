<?php

namespace Tests\Crm;

use Crm\Services\AfterSalesService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\FulfilmentService;
use Crm\Services\PaymentService;
use Crm\Services\ProjectService;

final class ProjectAndFulfilmentTest extends CrmTestCase
{
    private int $adminId;
    private array $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->user('admin');
        $this->admin   = $this->sessionUser($this->adminId);
    }

    private function createCustomer(): int
    {
        return CustomerService::create([
            'name' => 'Fulfilment Test Customer',
            'customer_type' => 'individual',
            'mobile' => '98888' . sprintf('%05d', mt_rand(10000, 99999)),
            'email' => 'fulfilment.' . uniqid() . '@test.invalid',
            'site_address' => 'Villa 402, Baner, Pune',
            'billing_address' => 'Villa 402, Baner, Pune',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pin_code' => '411045',
            'owner_user_id' => $this->adminId,
        ], $this->adminId, false, 1);
    }

    private function createOrder(int $customerId, float $amount = 250000.00): int
    {
        $db = Crm::db();
        // 1. Create enquiry
        $db->table('crm_enquiries')->insert([
            'enquiry_no' => 'ENQ-T-' . uniqid(),
            'customer_id' => $customerId,
            'current_owner_id' => $this->adminId,
            'project_name' => 'Test Project',
            'status' => 'won',
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        $enquiryId = (int) $db->insertID();

        // 2. Create quotation
        $db->table('crm_quotations')->insert([
            'quotation_no' => 'QT-T-' . uniqid(),
            'customer_id' => $customerId,
            'enquiry_id' => $enquiryId,
            'status' => 'accepted',
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        $quotationId = (int) $db->insertID();

        // 3. Create version
        $db->table('crm_quotation_versions')->insert([
            'quotation_id' => $quotationId,
            'version_no' => 1,
            'subtotal' => round($amount / 1.18, 2),
            'discount_total' => 0.00,
            'tax_total' => round($amount - ($amount / 1.18), 2),
            'charges_total' => 0.00,
            'grand_total' => $amount,
            'is_accepted' => 1,
            'created_at' => Crm::now(),
        ]);
        $versionId = (int) $db->insertID();
        $db->table('crm_quotations')->where('id', $quotationId)->update(['current_version_id' => $versionId]);

        // 4. Create sales order
        $db->table('crm_sales_orders')->insert([
            'order_no' => 'SO-T-' . uniqid(),
            'quotation_id' => $quotationId,
            'version_id' => $versionId,
            'customer_id' => $customerId,
            'enquiry_id' => $enquiryId,
            'salesperson_id' => $this->adminId,
            'status' => 'draft',
            'subtotal' => round($amount / 1.18, 2),
            'discount_total' => 0.00,
            'tax_total' => round($amount - ($amount / 1.18), 2),
            'charges_total' => 0.00,
            'grand_total' => $amount,
            'advance_amount' => 0.00,
            'created_at' => Crm::now(),
            'updated_at' => Crm::now(),
        ]);
        return (int) $db->insertID();
    }

    public function testProjectLifecycle(): void
    {
        $customerId = $this->createCustomer();

        // 1. Create project
        $project = ProjectService::saveProject([
            'customer_id' => $customerId,
            'name' => 'Koregaon Park Luxury Penthouse',
            'site_location' => 'Lane 7, Koregaon Park',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'pin_code' => '411001',
            'status' => 'planning',
            'budget' => 750000.00,
            'remarks' => 'Vitrum high-performance slimline sliding windows throughout',
        ], null, $this->admin);

        $this->assertNotEmpty($project['id']);
        $this->assertStringStartsWith('PRJ-', $project['project_no']);
        $this->assertSame('Koregaon Park Luxury Penthouse', $project['name']);

        // 2. Fetch project
        $fetched = ProjectService::getProject((int) $project['id']);
        $this->assertSame($customerId, (int) $fetched['customer_id']);

        // 3. List projects
        $list = ProjectService::listProjects(['customer_id' => $customerId]);
        $this->assertSame(1, (int) $list['total']);

        // 4. Update project status
        $updated = ProjectService::saveProject([
            'status' => 'in_progress',
        ], (int) $project['id'], $this->admin);
        $this->assertSame('in_progress', $updated['status']);
    }

    public function testPaymentAndBalanceValidation(): void
    {
        $customerId = $this->createCustomer();
        $orderId = $this->createOrder($customerId, 100000.00);

        // 1. Record advance payment of 40,000 (40%)
        $pay1 = PaymentService::recordPayment([
            'order_id' => $orderId,
            'amount' => 40000.00,
            'payment_type' => 'advance',
            'payment_mode' => 'bank_transfer',
            'transaction_reference' => 'UTR99887766',
            'payment_date' => date('Y-m-d'),
        ], $this->admin);

        $this->assertNotEmpty($pay1['id']);
        $this->assertStringStartsWith('PAY-', $pay1['payment_no']);
        $this->assertStringStartsWith('RCP-', $pay1['receipt_no']);
        $this->assertEquals(40000.00, (float) $pay1['order_paid_total']);
        $this->assertEquals(60000.00, (float) $pay1['order_balance']);

        // 2. Overpayment attempt should fail
        $this->expectException(CrmException::class);
        $this->expectExceptionMessageMatches('/exceeds outstanding order balance/');
        PaymentService::recordPayment([
            'order_id' => $orderId,
            'amount' => 65000.00, // 60,000 outstanding + 5,000 excess
            'payment_type' => 'milestone',
            'payment_mode' => 'bank_transfer',
        ], $this->admin);
    }

    public function testFulfilmentDeliveriesAndInstallations(): void
    {
        $customerId = $this->createCustomer();
        $orderId = $this->createOrder($customerId, 150000.00);

        // 1. Create delivery challan
        $del = FulfilmentService::saveDelivery([
            'order_id' => $orderId,
            'delivery_address' => 'Plot 88, Baner Road, Pune',
            'vehicle_details' => 'MH 12 QW 5544',
            'driver_name' => 'Ramesh Patil',
            'driver_phone' => '9822011223',
            'status' => 'dispatched',
            'notes' => 'Handled with glass transit brackets',
        ], null, $this->admin);

        $this->assertNotEmpty($del['id']);
        $this->assertStringStartsWith('DEL-', $del['delivery_no']);
        $this->assertStringStartsWith('DC-', $del['challan_no']);
        $this->assertSame('dispatched', $del['status']);

        // Update to delivered
        $delUpdated = FulfilmentService::saveDelivery([
            'order_id' => $orderId,
            'status' => 'delivered',
            'delivered_date' => date('Y-m-d'),
            'notes' => 'Site received in good condition',
        ], (int) $del['id'], $this->admin);
        $this->assertSame('delivered', $delUpdated['status']);

        // 2. Schedule installation
        $ins = FulfilmentService::saveInstallation([
            'order_id' => $orderId,
            'delivery_id' => (int) $del['id'],
            'scheduled_date' => date('Y-m-d', strtotime('+2 days')),
            'technician_name' => 'Ganesh Kadam',
            'technician_phone' => '9890012345',
            'status' => 'scheduled',
            'remarks' => 'Laser level check and structural silicone sealing',
        ], null, $this->admin);

        $this->assertNotEmpty($ins['id']);
        $this->assertStringStartsWith('INS-', $ins['installation_no']);
        $this->assertSame('scheduled', $ins['status']);

        // Complete installation with signoff
        $insCompleted = FulfilmentService::saveInstallation([
            'order_id' => $orderId,
            'status' => 'completed',
            'customer_signoff_name' => 'Dr. A. K. Verma',
            'signoff_date' => date('Y-m-d'),
            'completed_at' => Crm::now(),
            'remarks' => 'Client verified smooth operation of all 4 Vitrum sliders',
        ], (int) $ins['id'], $this->admin);

        $this->assertSame('completed', $insCompleted['status']);
        $this->assertSame('Dr. A. K. Verma', $insCompleted['customer_signoff_name']);
    }

    public function testAfterSalesServiceTickets(): void
    {
        $customerId = $this->createCustomer();

        // 1. Log a service ticket
        $ticket = AfterSalesService::saveTicket([
            'customer_id' => $customerId,
            'request_type' => 'repair_request',
            'priority' => 'high',
            'issue_description' => 'Left sliding panel requires roller tension calibration',
        ], null, $this->admin);

        $this->assertNotEmpty($ticket['id']);
        $this->assertStringStartsWith('SRV-', $ticket['ticket_no']);
        $this->assertSame('open', $ticket['status']);

        // 2. Resolve ticket
        $resolved = AfterSalesService::saveTicket([
            'customer_id' => $customerId,
            'status' => 'resolved',
            'issue_description' => 'Left sliding panel requires roller tension calibration',
            'resolution_notes' => 'Roller bearings lubricated and height adjusted. Passed 50 cycle test.',
            'resolved_at' => Crm::now(),
        ], (int) $ticket['id'], $this->admin);
        $this->assertSame('resolved', $resolved['status']);
        $this->assertNotEmpty($resolved['resolved_at']);

        // 3. List customer service tickets
        $tickets = AfterSalesService::listTickets(['customer_id' => $customerId]);
        $this->assertSame(1, (int) $tickets['total']);
    }
}
