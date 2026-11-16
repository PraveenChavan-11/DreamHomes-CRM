<?php

namespace Crm\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfService
{
    public static function dir(): string
    {
        $d = WRITEPATH . 'crm/quotations/';
        if (! is_dir($d)) {
            mkdir($d, 0750, true);
        }

        return $d;
    }

    /**
     * Portable lookup of a stored PDF: the DB keeps whatever path existed when the file was written (possibly another machine / folder),
     * so only the file NAME is trusted and it is always resolved inside the CRM PDF directory (no path traversal, survives DB export/import).
     */
    public static function resolve(?string $storedPath): ?string
    {
        if ($storedPath === null || $storedPath === '') {
            return null;
        }
        $name = basename(str_replace('\\', '/', $storedPath));
        if (! preg_match('/^[A-Za-z0-9._-]+\.pdf$/', $name)) {
            return null;
        }
        $full = self::dir() . $name;

        return is_file($full) ? $full : null;
    }

    /** @return array{html:string,data:array<string,mixed>} */
    public static function quotationHtml(int $versionId): array
    {
        helper(['crm', 'url', 'number']);
        $db = Crm::db();
        $v  = $db->table('crm_quotation_versions')->where('id', $versionId)->get()->getRowArray() ?? throw new CrmException('Quotation version not found.');
        $q  = $db->table('crm_quotations')->where('id', $v['quotation_id'])->get()->getRowArray();
        $c  = $db->table('crm_customers')->where('id', $q['customer_id'])->get()->getRowArray();
        $e  = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
        $sp = $e['current_owner_id'] ? $db->table('admin_users')->where('id', $e['current_owner_id'])->get()->getRowArray() : null;
        $logo = FCPATH . 'assets/crm/img/dhis-logo.png';
        $data = [
            'q' => $q, 'v' => $v, 'c' => $c, 'e' => $e, 'salesperson' => $sp ? trim($sp['first_name'] . ' ' . $sp['last_name']) : '',
            'items' => $db->table('crm_quotation_items')->where('version_id', $versionId)->orderBy('line_no')->get()->getResultArray(),
            'charges' => $db->table('crm_quotation_charges')->where('version_id', $versionId)->get()->getResultArray(),
            'company' => ['name' => Settings::get('company.name'), 'address' => Settings::get('company.address'), 'phone' => Settings::get('company.phone'), 'email' => Settings::get('company.email'), 'gstin' => Settings::get('company.gstin'), 'state' => Settings::get('company.state')],
            'intra' => strcasecmp(trim((string) $c['state']), Settings::get('company.state')) === 0 || trim((string) $c['state']) === '',
            // dompdf needs ext-gd to embed PNG; fall back to a text wordmark when it is missing.
            'logo' => (extension_loaded('gd') && is_file($logo)) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logo)) : null,
        ];

        return ['html' => view('Crm\Views\pdf\quotation', $data), 'data' => $data];
    }

    /** Render a version to PDF bytes WITHOUT storing it (used for drafts; sent versions keep their stored original). */
    public static function quotationBytes(int $versionId): string
    {
        $r    = self::quotationHtml($versionId);
        $opts = new Options();
        $opts->set('isRemoteEnabled', false);
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($opts);
        $pdf->loadHtml($r['html']);
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }

    /** @return array{path:string,sha256:string,bytes:int} */
    public static function renderQuotation(int $versionId): array
    {
        $r    = self::quotationHtml($versionId);
        $opts = new Options();
        $opts->set('isRemoteEnabled', false);
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($opts);
        $pdf->loadHtml($r['html']);
        $pdf->setPaper('A4');
        $pdf->render();
        $bin  = $pdf->output();
        $name = $r['data']['q']['quotation_no'] . '-V' . $r['data']['v']['version_no'] . '.pdf';
        $path = self::dir() . $name;
        file_put_contents($path, $bin);

        return ['path' => $path, 'sha256' => hash('sha256', $bin), 'bytes' => strlen($bin)];
    }

    /** Render a payment receipt to PDF bytes */
    public static function receiptBytes(int $paymentId): string
    {
        $html = PaymentService::receiptHtml($paymentId);
        $opts = new Options();
        $opts->set('isRemoteEnabled', false);
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('defaultFont', 'DejaVu Sans');
        $pdf  = new Dompdf($opts);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }
}
