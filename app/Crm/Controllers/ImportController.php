<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Excel/CSV enquiry import wizard: upload → map → validate → approve → result / error report. All rules live in ImportService. */
class ImportController extends CrmController
{
    private const PER = 25;
    private const REQUIRED = ['customer_name', 'mobile', 'project_name'];
    private const LABELS = [
        'customer_name' => 'Customer name', 'mobile' => 'Mobile', 'email' => 'Email', 'city' => 'City', 'project_name' => 'Project name', 'category' => 'Product category',
        'requirements' => 'Requirements', 'budget' => 'Budget (₹)', 'priority' => 'Priority', 'expected_purchase_date' => 'Expected purchase date', 'description' => 'Description / notes',
    ];

    /** Admin-level users see every job; others only their own (404 otherwise — no information leak). */
    private function job(int $id): array
    {
        $job = Crm::db()->table('crm_import_jobs')->where('id', $id)->get()->getRowArray();
        if (! $job || (AccessScope::level($this->user()) !== 'all' && (int) $job['created_by'] !== $this->uid())) {
            throw new PageNotFoundException('Import not found');
        }

        return $job;
    }

    public function index(): string
    {
        $mk = function () {
            $b = Crm::db()->table('crm_import_jobs j')->select("j.*, CONCAT(u.first_name,' ',u.last_name) AS user_name")->join('admin_users u', 'u.id = j.created_by', 'left')->where('j.import_type', 'enquiries');
            if (AccessScope::level($this->user()) !== 'all') {
                $b->where('j.created_by', $this->uid());
            }

            return $b;
        };
        $page  = $this->page();
        $total = $mk()->countAllResults();

        return $this->render('imports/index', [
            'jobs' => $mk()->orderBy('j.id', 'DESC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray(), 'total' => $total,
            'pager' => view('Crm\Views\enquiries\_pager', ['total' => $total, 'per' => self::PER, 'page' => $page, 'query' => []]), 'maxMb' => ImportService::MAX_BYTES / 1048576, 'maxRows' => ImportService::MAX_ROWS,
        ], 'Import enquiries', 'imports');
    }

    public function upload()
    {
        $f = $this->request->getFile('file');
        if (! $f || ! $f->isValid()) {
            return redirect()->to(site_url('crm/imports'))->with('error', 'Choose an .xlsx or .csv file to upload' . ($f && $f->getErrorString() && $f->getError() !== UPLOAD_ERR_NO_FILE ? ' (' . $f->getErrorString() . ').' : '.'));
        }
        try {
            $id = ImportService::upload($f->getTempName(), $f->getClientName(), $this->user());
        } catch (CrmException $e) {
            return redirect()->to(site_url('crm/imports'))->with('error', $e->getMessage());
        }

        return redirect()->to(site_url("crm/imports/{$id}"))->with('success', 'File uploaded. Check the column mapping, then validate.');
    }

    public function show(int $id): string
    {
        $job = $this->job($id);
        $db  = Crm::db();
        $first = $db->table('crm_import_rows')->select('raw_data')->where('job_id', $id)->orderBy('row_no')->limit(1)->get()->getRowArray();
        $headers = $first ? array_keys(json_decode($first['raw_data'], true) ?: []) : [];
        $filter  = in_array($this->request->getGet('rows'), ['all', 'valid', 'invalid', 'duplicate', 'imported'], true) ? $this->request->getGet('rows') : 'all';
        $b = $db->table('crm_import_rows')->where('job_id', $id);
        if ($filter !== 'all') {
            $b->where('status', $filter);
        }
        $rows = array_map(static fn ($r) => $r + ['data' => json_decode($r['raw_data'], true) ?: []], $b->orderBy('row_no')->limit(100)->get()->getResultArray());
        $counts = array_column($db->query('SELECT status, COUNT(*) c FROM crm_import_rows WHERE job_id = ? GROUP BY status', [$id])->getResultArray(), 'c', 'status');

        return $this->render('imports/show', [
            'job' => $job, 'headers' => $headers, 'map' => json_decode((string) $job['column_map'], true) ?: [], 'fields' => ImportService::FIELDS, 'labels' => self::LABELS, 'required' => self::REQUIRED,
            'rows' => $rows, 'filter' => $filter, 'counts' => $counts, 'owner' => $db->query("SELECT CONCAT(first_name,' ',last_name) n FROM admin_users WHERE id = ?", [(int) $job['created_by']])->getRow()->n ?? '—',
        ], 'Import #' . $id, 'imports');
    }

    public function mapping(int $id)
    {
        $this->job($id);
        $map = array_filter((array) $this->request->getPost('map'), static fn ($v) => is_string($v) && $v !== '');

        return $this->act(function () use ($id, $map) {
            $missing = array_diff(self::REQUIRED, array_keys($map));
            if ($missing) {
                throw new CrmException('Map the required columns: ' . implode(', ', array_map(static fn ($k) => self::LABELS[$k], $missing)) . '.');
            }
            ImportService::setMapping($id, $map, $this->user());
        }, 'Mapping saved. Validate the rows next.', "crm/imports/{$id}");
    }

    public function validateRows(int $id)
    {
        $this->job($id);

        return $this->act(function () use ($id) {
            $c = ImportService::validate($id, $this->user());
            session()->setFlashdata('info', "Validation finished: {$c['valid']} valid, {$c['invalid']} invalid, {$c['duplicate']} duplicate.");
        }, 'Rows validated.', "crm/imports/{$id}");
    }

    public function run(int $id)
    {
        $this->job($id);
        $r = null;
        $res = $this->act(function () use ($id, &$r) {
            $r = ImportService::run($id, $this->user());
        }, 'Import completed.', "crm/imports/{$id}");

        return $r ? $res->with('success', "Import completed: {$r['imported']} enquiries created, {$r['skipped']} rows skipped.") : $res;
    }

    public function errors(int $id)
    {
        $job = $this->job($id);
        if ((int) $job['invalid_rows'] + (int) $job['duplicate_rows'] === 0) {
            return redirect()->to(site_url("crm/imports/{$id}"))->with('info', 'There are no invalid or duplicate rows to report.');
        }
        $path = ImportService::errorReport($id);

        return $this->download((string) file_get_contents($path), 'import-' . $id . '-errors.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function template()
    {
        $cats = array_column(Crm::db()->table('crm_product_categories')->select('name')->orderBy('sort_order')->get()->getResultArray(), 'name');
        $c1   = $cats[0] ?? 'Windows';
        $c2   = $cats[1] ?? $c1;
        $ss   = new Spreadsheet();
        $sh   = $ss->getActiveSheet()->setTitle('Enquiries');
        $sh->fromArray([['Customer Name', 'Mobile', 'Email', 'City', 'Project Name', 'Category', 'Requirements', 'Budget', 'Priority', 'Expected Purchase Date', 'Description'],
            ['Sample Customer One', '9000000001', 'sample.one@example.invalid', 'Pune', 'Sample 3BHK flat', $c1, 'Sample: 4 sliding windows', '150000', 'medium', date('Y-m-d', strtotime('+30 days')), 'Fictional sample row — delete before uploading'],
            ['Sample Customer Two', '9000000002', '', 'Mumbai', 'Sample bungalow', $c2, 'Sample: two bathrooms', '400000', 'high', date('Y-m-d', strtotime('+60 days')), 'Fictional sample row — delete before uploading']], null, 'A1');
        foreach (range('A', 'K') as $col) {
            $sh->getColumnDimension($col)->setAutoSize(true);
        }
        $sh->getStyle('A1:K1')->getFont()->setBold(true);
        $sh->getCell('B2')->setValueExplicit('9000000001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sh->getCell('B3')->setValueExplicit('9000000002', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        (new Xlsx($ss))->save($tmp);
        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $this->download($bytes, 'dhis-enquiry-import-template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
