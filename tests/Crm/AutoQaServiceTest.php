<?php

namespace Tests\Crm;

use Crm\Services\AutoQaService;

final class AutoQaServiceTest extends CrmTestCase
{
    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminId = $this->user('admin');
    }

    public function testCatalogueDefinition(): void
    {
        $catalogue = AutoQaService::getCheckCatalogue();
        $this->assertGreaterThanOrEqual(10, count($catalogue));

        $ids = array_column($catalogue, 'id');
        $this->assertContains('APP_HEALTH_DB_CONN', $ids);
        $this->assertContains('SEC_CSRF_SHIELD', $ids);
        $this->assertContains('DB_ENQUIRY_CUSTOMER_FK', $ids);
        $this->assertContains('WF_OWNERSHIP_INTEGRITY', $ids);
        $this->assertContains('FIN_QUOTATION_RECONCILE', $ids);
        $this->assertContains('MASTER_DATA_COMPLETENESS', $ids);
        $this->assertContains('FULFILMENT_LIFECYCLE_INTEGRITY', $ids);
        $this->assertContains('FIN_PAYMENT_RECONCILE', $ids);
    }

    public function testExecuteRunCategoryFilter(): void
    {
        $res = AutoQaService::executeRun('Master Data QA', $this->adminId);
        $this->assertSame(1, (int) $res['total']);
        $this->assertSame('Master Data QA', $res['findings'][0]['category']);
        $this->assertSame(AutoQaService::STATUS_PASSED, $res['findings'][0]['status']);
    }

    public function testExecuteFullRun(): void
    {
        $res = AutoQaService::executeRun('all', $this->adminId);
        $this->assertGreaterThanOrEqual(13, (int) $res['total']);
        $this->assertSame(0, (int) $res['failed'], 'Zero failed checks in AutoQA audit');
        $this->assertGreaterThanOrEqual(90.0, (float) $res['health_score']);
    }
}
