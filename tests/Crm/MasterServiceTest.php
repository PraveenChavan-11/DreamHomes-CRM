<?php

namespace Tests\Crm;

use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\MasterService;

final class MasterServiceTest extends CrmTestCase
{
    public function testMasterGroupsStructure(): void
    {
        $groups = MasterService::MASTER_GROUPS;
        $this->assertArrayHasKey('organisation', $groups);
        $this->assertArrayHasKey('customer', $groups);
        $this->assertArrayHasKey('product', $groups);
        $this->assertArrayHasKey('sales', $groups);
        $this->assertArrayHasKey('system', $groups);

        // Spot-check key masters
        $this->assertArrayHasKey('branches', $groups['organisation']['items']);
        $this->assertArrayHasKey('customer_types', $groups['customer']['items']);
        $this->assertArrayHasKey('products', $groups['product']['items']);
        $this->assertArrayHasKey('product_variants', $groups['product']['items']);
        $this->assertArrayHasKey('sales_stages', $groups['sales']['items']);
        $this->assertArrayHasKey('document_numbering', $groups['system']['items']);
    }

    public function testGetMasterMeta(): void
    {
        $meta = MasterService::getMasterMeta('branches');
        $this->assertNotNull($meta);
        $this->assertSame('Branches', $meta['name']);
        $this->assertSame('records', $meta['mode']);
        $this->assertSame('organisation', $meta['group_key']);

        $taxMeta = MasterService::getMasterMeta('tax_rates');
        $this->assertNotNull($taxMeta);
        $this->assertSame('native', $taxMeta['mode']);
        $this->assertSame('crm_tax_rates', $taxMeta['table']);

        $this->assertNull(MasterService::getMasterMeta('non_existent_master_key'));
    }

    public function testListItemsAndSearch(): void
    {
        MasterService::seedDefaultMasters();

        // 1. List branches
        $res = MasterService::listItems('branches', ['limit' => 10]);
        $this->assertGreaterThanOrEqual(1, $res['total']);
        $this->assertNotEmpty($res['rows']);

        // 2. Search branches
        $search = MasterService::listItems('branches', ['q' => 'Flagship']);
        $this->assertGreaterThanOrEqual(1, $search['total']);
        $this->assertStringContainsString('Flagship', $search['rows'][0]['name']);

        // 3. List native master (tax rates)
        $taxes = MasterService::listItems('tax_rates', ['limit' => 10]);
        $this->assertGreaterThanOrEqual(1, $taxes['total']);
    }

    public function testSaveItemAndDuplicatePrevention(): void
    {
        // 1. Create a custom record in branches
        $created = MasterService::saveRecord('branches', [
            'code' => 'BR-TEST-01',
            'name' => 'Test Koregaon Park Showroom',
            'description' => 'Test boutique location',
            'sort_order' => 50,
        ]);
        $this->assertNotEmpty($created['id']);
        $this->assertSame('BR-TEST-01', $created['code']);

        // 2. Prevent duplicate code
        $this->expectException(CrmException::class);
        $this->expectExceptionMessageMatches('/already exists/');
        MasterService::saveRecord('branches', [
            'code' => 'BR-TEST-01',
            'name' => 'Duplicate Code Branch',
        ]);
    }

    public function testToggleActiveAndDependencyProtection(): void
    {
        // 1. Create a test department record
        $dept = MasterService::saveRecord('departments', [
            'code' => 'DEP-QA-TEST',
            'name' => 'Quality Assurance Testing',
            'description' => 'QA validation department',
        ]);
        $id = (int) $dept['id'];

        // Deactivate it
        MasterService::toggleActive('departments', $id);
        $item = MasterService::getRecord('departments', $id);
        $this->assertSame(0, (int) $item['is_active']);

        // Reactivate it
        MasterService::toggleActive('departments', $id);
        $item = MasterService::getRecord('departments', $id);
        $this->assertSame(1, (int) $item['is_active']);

        // 2. Test dependency protection: tax rate linked to a product cannot be deactivated
        $db = Crm::db();
        $tax = $db->table('crm_tax_rates')->where('is_active', 1)->get()->getRowArray();
        if ($tax) {
            $catId = $db->table('crm_product_categories')->get()->getRow()->id;
            // Insert dummy active product using this tax
            $db->table('crm_products')->insert([
                'sku' => 'TEST-DEP-SKU',
                'name' => 'Test Dependent Product',
                'category_id' => $catId,
                'tax_rate_id' => $tax['id'],
                'standard_price' => 1000.00,
                'is_active' => 1,
            ]);

            try {
                MasterService::toggleActive('tax_rates', (int) $tax['id']);
                $this->fail('Expected CrmException for tax rate with active products');
            } catch (CrmException $e) {
                $this->assertStringContainsString('active products are linked to it', $e->getMessage());
            }
        }
    }

    public function testExportCsv(): void
    {
        MasterService::seedDefaultMasters();
        $csv = MasterService::exportCsv('branches');
        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('code', $csv);
        $this->assertStringContainsString('name', $csv);
    }
}
