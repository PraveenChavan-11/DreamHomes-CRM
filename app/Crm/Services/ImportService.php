<?php

namespace Crm\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Excel/CSV enquiry import:  upload → preview → map columns → validate → approve → import (single transaction) → error report.
 * A file (by SHA-256) can only be imported once. Duplicates (existing open enquiry, or repeated row in the same file) are never imported.
 */
final class ImportService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_ROWS  = 5000;
    public const FIELDS = [
        'customer_name' => ['customer name', 'name', 'customer', 'client name', 'client'], 'mobile' => ['mobile', 'mobile number', 'phone', 'contact number', 'mobile no'],
        'email' => ['email', 'email id', 'e-mail'], 'city' => ['city', 'location'], 'project_name' => ['project', 'project name', 'site name'],
        'category' => ['category', 'product category', 'product'], 'requirements' => ['requirements', 'requirement', 'details'], 'budget' => ['budget', 'estimated budget', 'est budget'],
        'priority' => ['priority'], 'expected_purchase_date' => ['expected purchase date', 'purchase date', 'expected date'], 'description' => ['description', 'remarks', 'notes'],
    ];
    private const REQUIRED = ['customer_name', 'mobile', 'project_name'];

    public static function upload(string $tmpPath, string $originalName, array $actor): int
    {
        if (! AccessScope::can($actor, 'customers.import')) {
            throw new CrmException('You do not have permission to import data.');
        }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new CrmException('Only .xlsx, .xls or .csv files are accepted.');
        }
        if (! is_file($tmpPath) || filesize($tmpPath) === 0 || filesize($tmpPath) > self::MAX_BYTES) {
            throw new CrmException('File is empty or larger than 5 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        $okMime = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/vnd.ms-excel', 'application/x-ole-storage', 'application/octet-stream', 'text/plain', 'text/csv', 'application/csv'];
        if (! in_array($mime, $okMime, true)) {
            throw new CrmException("Unsupported file content ({$mime}).");
        }
        $hash = hash_file('sha256', $tmpPath);
        $db   = Crm::db();
        $dup  = $db->table('crm_import_jobs')->where(['import_type' => 'enquiries', 'file_sha256' => $hash])->get()->getRowArray();
        if ($dup && $dup['status'] === 'completed') {
            throw new CrmException('This file has already been imported (job #' . $dup['id'] . ' on ' . $dup['completed_at'] . ').');
        }
        if ($dup) {
            $db->table('crm_import_rows')->where('job_id', $dup['id'])->delete();
            $db->table('crm_import_jobs')->where('id', $dup['id'])->delete();
        }
        $dir = WRITEPATH . 'crm/imports/';
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $stored = $dir . $hash . '.' . $ext;
        copy($tmpPath, $stored);

        try {
            $sheet = IOFactory::load($stored)->getActiveSheet();
            $data  = $sheet->toArray(null, false, false, false);   // never evaluate formulas from untrusted uploads
        } catch (\Throwable $e) {
            @unlink($stored);
            throw new CrmException('The file could not be read as a spreadsheet.');
        }
        $data = array_values(array_filter($data, static fn ($r) => (bool) array_filter($r, static fn ($c) => $c !== null && trim((string) $c) !== '')));
        if (count($data) < 2) {
            throw new CrmException('The file has no data rows.');
        }
        if (count($data) - 1 > self::MAX_ROWS) {
            throw new CrmException('Too many rows (limit ' . self::MAX_ROWS . ').');
        }
        $headers = array_map(static fn ($h) => trim((string) $h), $data[0]);

        return (int) Crm::tx(static function ($db) use ($originalName, $hash, $stored, $headers, $data, $actor) {
            $db->table('crm_import_jobs')->insert([
                'import_type' => 'enquiries', 'original_name' => mb_substr($originalName, 0, 255), 'file_sha256' => $hash, 'stored_path' => $stored, 'status' => 'uploaded',
                'total_rows' => count($data) - 1, 'column_map' => json_encode(self::autoMap($headers)), 'created_by' => $actor['id'], 'created_at' => Crm::now(),
            ]);
            $jobId = (int) $db->insertID();
            foreach (array_slice($data, 1) as $i => $row) {
                $assoc = [];
                foreach ($headers as $c => $h) {
                    $assoc[$h] = isset($row[$c]) ? (is_scalar($row[$c]) ? (string) $row[$c] : '') : '';
                }
                $db->table('crm_import_rows')->insert(['job_id' => $jobId, 'row_no' => $i + 2, 'raw_data' => json_encode($assoc, JSON_UNESCAPED_UNICODE), 'status' => 'valid']);
            }
            AuditService::audit('import.uploaded', 'import_job', $jobId, ['meta' => ['file' => $originalName, 'rows' => count($data) - 1, 'sha256' => $hash]]);

            return $jobId;
        });
    }

    /** @return array<string,string> field => header */
    public static function autoMap(array $headers): array
    {
        $map = [];
        foreach (self::FIELDS as $field => $aliases) {
            foreach ($headers as $h) {
                if (in_array(strtolower(trim($h)), $aliases, true) || strtolower(trim($h)) === $field) {
                    $map[$field] = $h;
                    break;
                }
            }
        }

        return $map;
    }

    public static function setMapping(int $jobId, array $map, array $actor): void
    {
        $job = self::job($jobId);
        if ($job['status'] === 'completed') {
            throw new CrmException('Import already completed.');
        }
        $valid = array_intersect_key($map, self::FIELDS);
        Crm::db()->table('crm_import_jobs')->where('id', $jobId)->update(['column_map' => json_encode($valid), 'status' => 'uploaded']);
    }

    public static function job(int $id): array
    {
        return Crm::db()->table('crm_import_jobs')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Import job not found.');
    }

    /** Validate every row against the current mapping; classify valid / invalid / duplicate. @return array{valid:int,invalid:int,duplicate:int} */
    public static function validate(int $jobId, array $actor): array
    {
        $job = self::job($jobId);
        if ($job['status'] === 'completed') {
            throw new CrmException('Import already completed.');
        }
        $map = json_decode((string) $job['column_map'], true) ?: [];
        $missing = array_diff(self::REQUIRED, array_keys($map));
        if ($missing) {
            throw new CrmException('Map the required columns first: ' . implode(', ', $missing) . '.');
        }
        $db   = Crm::db();
        $cats = array_column($db->table('crm_product_categories')->get()->getResultArray(), 'id', 'name');
        $cats = array_change_key_case($cats, CASE_LOWER);
        $seen = [];
        $cnt  = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];

        Crm::tx(static function ($db) use ($jobId, $map, $cats, &$seen, &$cnt) {
            foreach ($db->table('crm_import_rows')->where('job_id', $jobId)->orderBy('row_no')->get()->getResultArray() as $r) {
                $raw = json_decode($r['raw_data'], true);
                $v   = static fn (string $f) => isset($map[$f]) ? trim((string) ($raw[$map[$f]] ?? '')) : '';
                $err = [];
                $name = $v('customer_name');
                $mobile = CustomerService::normMobile($v('mobile'));
                $email  = $v('email');
                if ($name === '') { $err[] = 'Customer name missing'; }
                if (! preg_match('/^[6-9]\d{9}$/', $mobile)) { $err[] = 'Invalid mobile'; }
                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) { $err[] = 'Invalid email'; }
                if ($v('project_name') === '') { $err[] = 'Project name missing'; }
                $catId = null;
                if ($v('category') !== '') {
                    $catId = $cats[strtolower($v('category'))] ?? null;
                    if (! $catId) { $err[] = 'Unknown category "' . $v('category') . '"'; }
                }
                if ($v('priority') !== '' && ! in_array(strtolower($v('priority')), EnquiryService::PRIORITIES, true)) { $err[] = 'Invalid priority'; }
                if ($v('budget') !== '' && (! is_numeric(str_replace(',', '', $v('budget'))) || (float) str_replace(',', '', $v('budget')) < 0)) { $err[] = 'Invalid budget'; }
                if ($v('expected_purchase_date') !== '' && strtotime($v('expected_purchase_date')) === false) { $err[] = 'Invalid date'; }

                $status = 'valid';
                $msg    = null;
                if ($err) {
                    $status = 'invalid';
                    $msg    = implode('; ', $err);
                } else {
                    $key = $mobile . '|' . strtolower($v('project_name')) . '|' . ($catId ?? 0);
                    if (isset($seen[$key])) {
                        $status = 'duplicate';
                        $msg    = 'Repeated in this file (row ' . $seen[$key] . ')';
                    } else {
                        $seen[$key] = $r['row_no'];
                        $cust = $db->table('crm_customers')->where('mobile_norm', $mobile)->get()->getRowArray();
                        if ($cust && EnquiryService::findDuplicates(['customer_id' => $cust['id'], 'product_category_id' => $catId, 'project_name' => $v('project_name')])) {
                            $status = 'duplicate';
                            $msg    = 'An open enquiry for this customer/project already exists';
                        }
                    }
                }
                $cnt[$status]++;
                $db->table('crm_import_rows')->where('id', $r['id'])->update(['status' => $status, 'errors' => $msg]);
            }
            $db->table('crm_import_jobs')->where('id', $jobId)->update(['status' => 'previewed', 'valid_rows' => $cnt['valid'], 'invalid_rows' => $cnt['invalid'], 'duplicate_rows' => $cnt['duplicate']]);
        });

        return $cnt;
    }

    /** @return array{job:array,rows:list<array>} first N rows for the preview screen */
    public static function preview(int $jobId, int $limit = 50): array
    {
        $rows = Crm::db()->table('crm_import_rows')->where('job_id', $jobId)->orderBy('row_no')->limit($limit)->get()->getResultArray();

        return ['job' => self::job($jobId), 'rows' => array_map(static fn ($r) => $r + ['data' => json_decode($r['raw_data'], true)], $rows)];
    }

    /** Approved import: valid rows only, one transaction, all-or-nothing on system errors. @return array{imported:int,skipped:int} */
    public static function run(int $jobId, array $actor): array
    {
        if (! AccessScope::can($actor, 'customers.import') || ! AccessScope::can($actor, 'enquiries.create')) {
            throw new CrmException('You do not have permission to import enquiries.');
        }
        $db = Crm::db();
        try {
            return Crm::tx(static function ($db) use ($jobId, $actor) {
                $job = $db->query('SELECT * FROM crm_import_jobs WHERE id = ? FOR UPDATE', [$jobId])->getRowArray() ?? throw new CrmException('Import job not found.');
                if ($job['status'] !== 'previewed') {
                    throw new CrmException($job['status'] === 'completed' ? 'This import has already been run.' : 'Validate and preview the import before approving it.');
                }
                if ((int) $job['valid_rows'] === 0) {
                    throw new CrmException('There are no valid rows to import.');
                }
                $map  = json_decode((string) $job['column_map'], true);
                $cats = array_change_key_case(array_column($db->table('crm_product_categories')->get()->getResultArray(), 'id', 'name'), CASE_LOWER);
                $src  = (int) ($db->table('crm_lead_sources')->where('code', 'excel_import')->get()->getRow()->id ?? 0);
                $imported = 0;
                foreach ($db->table('crm_import_rows')->where(['job_id' => $jobId, 'status' => 'valid'])->orderBy('row_no')->get()->getResultArray() as $r) {
                    $raw = json_decode($r['raw_data'], true);
                    $v   = static fn (string $f) => isset($map[$f]) ? trim((string) ($raw[$map[$f]] ?? '')) : '';
                    $mobile = CustomerService::normMobile($v('mobile'));
                    $cust   = $db->table('crm_customers')->where('mobile_norm', $mobile)->get()->getRowArray();
                    $cid    = $cust ? (int) $cust['id'] : CustomerService::create(['name' => $v('customer_name'), 'mobile' => $mobile, 'email' => $v('email') ?: null, 'city' => $v('city') ?: null, 'source_id' => $src ?: null], (int) $actor['id'], true);
                    $res    = EnquiryService::create([
                        'customer_id' => $cid, 'project_name' => $v('project_name'), 'product_category_id' => $v('category') !== '' ? ($cats[strtolower($v('category'))] ?? null) : null,
                        'requirements' => $v('requirements') ?: null, 'est_budget' => $v('budget') !== '' ? str_replace(',', '', $v('budget')) : null, 'priority' => strtolower($v('priority')) ?: 'medium',
                        'expected_purchase_date' => $v('expected_purchase_date') !== '' ? date('Y-m-d', strtotime($v('expected_purchase_date'))) : null, 'description' => $v('description') ?: null,
                        'source_channel' => 'excel_import', 'source_id' => $src ?: null, 'import_job_id' => $jobId,
                    ], $actor, true);
                    $db->table('crm_import_rows')->where('id', $r['id'])->update(['status' => 'imported', 'entity_id' => $res['id']]);
                    $imported++;
                }
                $db->table('crm_import_jobs')->where('id', $jobId)->update(['status' => 'completed', 'imported_rows' => $imported, 'completed_at' => Crm::now()]);
                AuditService::audit('import.completed', 'import_job', $jobId, ['meta' => ['imported' => $imported, 'skipped' => (int) $job['invalid_rows'] + (int) $job['duplicate_rows'], 'file' => $job['original_name']]]);

                return ['imported' => $imported, 'skipped' => (int) $job['invalid_rows'] + (int) $job['duplicate_rows']];
            });
        } catch (\Throwable $e) {
            if ($e instanceof CrmException) {
                throw $e;
            }
            $db->table('crm_import_jobs')->where('id', $jobId)->update(['status' => 'failed']);
            throw new CrmException('Import failed and was rolled back: ' . $e->getMessage());
        }
    }

    /** XLSX of every non-imported row with its error, for the user to fix and re-upload. @return string path */
    public static function errorReport(int $jobId): string
    {
        $job  = self::job($jobId);
        $rows = Crm::db()->table('crm_import_rows')->where('job_id', $jobId)->whereIn('status', ['invalid', 'duplicate'])->orderBy('row_no')->get()->getResultArray();
        $ss   = new Spreadsheet();
        $sh   = $ss->getActiveSheet();
        $first = $rows ? array_keys(json_decode($rows[0]['raw_data'], true)) : [];
        $sh->fromArray([array_merge(['Row', 'Status', 'Error'], $first)], null, 'A1');
        $i = 2;
        foreach ($rows as $r) {
            $d = json_decode($r['raw_data'], true);
            $sh->fromArray([array_merge([$r['row_no'], $r['status'], $r['errors']], array_map(static fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'" . $v : $v, array_values($d)))], null, 'A' . $i++);
        }
        $dir  = WRITEPATH . 'crm/imports/';
        $path = $dir . 'errors-job' . $jobId . '.xlsx';
        (new Xlsx($ss))->save($path);
        Crm::db()->table('crm_import_jobs')->where('id', $jobId)->update(['error_report_path' => $path]);
        unset($job);

        return $path;
    }
}
