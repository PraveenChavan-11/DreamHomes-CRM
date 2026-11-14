<?php

namespace Tests\Crm;

use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\EnquiryService;
use Crm\Services\ImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ImportTest extends CrmTestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function xlsx(array $rows): string
    {
        $ss = new Spreadsheet();
        $ss->getActiveSheet()->fromArray($rows, null, 'A1');
        $p = sys_get_temp_dir() . '/imp_' . bin2hex(random_bytes(6)) . '.xlsx';
        (new Xlsx($ss))->save($p);
        $this->files[] = $p;

        return $p;
    }

    private function headers(): array
    {
        return ['Customer Name', 'Mobile', 'Email', 'City', 'Project', 'Category', 'Budget', 'Priority'];
    }

    public function testFullImportWorkflowWithInvalidAndDuplicateRows(): void
    {
        $adminId = $this->user('admin');
        $admin   = $this->sessionUser($adminId);
        $this->user('sales_executive');
        $m = random_int(7000000000, 7999999000);
        // an existing customer with an open enquiry => duplicate row
        $cid = CustomerService::create(['name' => 'Existing Co', 'mobile' => (string) ($m + 5), 'email' => 'exist@demo.invalid'], $adminId);
        EnquiryService::create(['customer_id' => $cid, 'project_name' => 'Tower A', 'product_category_id' => 1], $admin, true, false);

        $file = $this->xlsx([
            $this->headers(),
            ['Anita Rane', (string) $m, 'anita@demo.invalid', 'Pune', 'Rane Villa', 'Windows', '450000', 'high'],
            ['Bad Mobile', '12345', '', 'Pune', 'X', 'Windows', '', ''],
            ['No Category', (string) ($m + 1), '', 'Pune', 'Y', 'Spaceships', '', ''],
            ['Dup In File', (string) ($m + 2), '', 'Pune', 'Z', 'Windows', '', ''],
            ['Dup In File', (string) ($m + 2), '', 'Pune', 'Z', 'Windows', '', ''],
            ['Existing Co', (string) ($m + 5), '', 'Pune', 'Tower A', 'Windows', '', ''],
            ['Vikas Pawar', (string) ($m + 3), 'vikas@demo.invalid', 'Pune', 'Pawar Flat', 'Bathrooms', '1,20,000', 'urgent'],
        ]);
        $job = ImportService::upload($file, 'enquiries.xlsx', $admin);
        $this->assertSame('Customer Name', ImportService::autoMap($this->headers())['customer_name']);
        $res = ImportService::validate($job, $admin);
        $this->assertSame(['valid' => 3, 'invalid' => 2, 'duplicate' => 2], $res);

        // nothing exists until approval
        $this->assertSame(0, Crm::db()->table('crm_enquiries')->where('import_job_id', $job)->countAllResults());
        $out = ImportService::run($job, $admin);
        $this->assertSame(['imported' => 3, 'skipped' => 4], $out);
        $enqs = Crm::db()->table('crm_enquiries')->where('import_job_id', $job)->get()->getResultArray();
        $this->assertCount(3, $enqs);
        $this->assertSame('excel_import', $enqs[0]['source_channel']);
        $this->assertNotNull($enqs[0]['current_owner_id'], 'imported enquiries are auto-assigned');
        $jobRow = ImportService::job($job);
        $this->assertSame('completed', $jobRow['status']);
        $this->assertSame($admin['id'], (int) $jobRow['created_by']);

        $report = ImportService::errorReport($job);
        $this->assertFileExists($report);
        $this->files[] = $report;
        $this->assertSame('PK', substr((string) file_get_contents($report), 0, 2));

        // repeated import prevention: same job and same file
        try { ImportService::run($job, $admin); $this->fail('ran twice'); } catch (CrmException $e) { $this->assertStringContainsString('already', $e->getMessage()); }
        try { ImportService::upload($file, 'enquiries-copy.xlsx', $admin); $this->fail('same file imported twice'); } catch (CrmException $e) { $this->assertStringContainsString('already been imported', $e->getMessage()); }
    }

    public function testRejectsBadFilesAndUnauthorisedUsers(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $exec  = $this->sessionUser($this->user('sales_executive'));
        $txt   = sys_get_temp_dir() . '/bad_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($txt, '<?php echo 1;');
        $this->files[] = $txt;
        try { ImportService::upload($txt, 'evil.php', $admin); $this->fail('php accepted'); } catch (CrmException $e) { $this->assertStringContainsString('.xlsx', $e->getMessage()); }
        $empty = $this->xlsx([$this->headers()]);
        try { ImportService::upload($empty, 'empty.xlsx', $admin); $this->fail('header-only file accepted'); } catch (CrmException $e) { $this->assertStringContainsString('no data rows', $e->getMessage()); }
        $garbage = sys_get_temp_dir() . '/garbage_' . bin2hex(random_bytes(4)) . '.xlsx';
        file_put_contents($garbage, random_bytes(300));
        $this->files[] = $garbage;
        try { ImportService::upload($garbage, 'garbage.xlsx', $admin); $this->fail('garbage accepted'); } catch (CrmException) {}
        $this->expectException(CrmException::class);
        ImportService::upload($this->xlsx([$this->headers(), ['A', '9000000001', '', '', 'P', 'Windows', '', '']]), 'a.xlsx', $exec);   // executive lacks customers.import
    }

    public function testMissingRequiredMappingBlocksValidation(): void
    {
        $admin = $this->sessionUser($this->user('admin'));
        $job   = ImportService::upload($this->xlsx([['Foo', 'Bar'], ['1', '2']]), 'odd.xlsx', $admin);
        $this->expectException(CrmException::class);
        ImportService::validate($job, $admin);
    }

    public function testManualMappingThenImportAndFormulaInjectionNeutralisedInReport(): void
    {
        $adminId = $this->user('admin');
        $admin   = $this->sessionUser($adminId);
        $this->user('sales_executive');
        $m   = random_int(6000000000, 6999000000);
        $job = ImportService::upload($this->xlsx([['Who', 'Phone', 'What'], ['Neha K', (string) $m, 'Kitchen #1'], ['=HYPERLINK("http://evil")', '99', 'Bad']]), 'custom.xlsx', $admin);
        ImportService::setMapping($job, ['customer_name' => 'Who', 'mobile' => 'Phone', 'project_name' => 'What'], $admin);
        $res = ImportService::validate($job, $admin);
        $this->assertSame(1, $res['valid']);
        $this->assertSame(1, $res['invalid']);
        ImportService::run($job, $admin);
        $report = ImportService::errorReport($job);
        $this->files[] = $report;
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($report)->getActiveSheet();
        $this->assertStringStartsWith("'=", (string) $sheet->getCell('D2')->getValue(), 'formula neutralised');
    }
}
