<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\AuditService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\HistoryService;
use Crm\Services\OrderService;
use Crm\Services\QuotationService;

/** Sales orders: converted from ACCEPTED quotations only (one per quotation), final values snapshotted by OrderService. */
class OrderController extends CrmController
{
    private const PER = 25;
    private const DOC_TYPES = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png']];
    private const DOC_MAX = 5 * 1024 * 1024;

    private function scopeSql(string $alias = 'e'): string
    {
        $ids = AccessScope::visibleUserIds($this->user());
        if ($ids === null) {
            return '1=1';
        }
        $in = implode(',', array_map('intval', $ids));

        return "({$alias}.current_owner_id IN ({$in}) OR {$alias}.created_by IN ({$in}))";
    }

    private function loadO(int $id): array
    {
        $o = Crm::db()->table('crm_sales_orders')->where('id', $id)->get()->getRowArray() ?? throw PageNotFoundException::forPageNotFound();
        if (! AccessScope::canSeeEnquiry($this->user(), (int) $o['enquiry_id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $o;
    }

    public function index(): string
    {
        $db = Crm::db();
        $g  = $this->request->getGet();
        $w  = [$this->scopeSql()];
        $b  = [];
        if (! empty($g['status'])) { $w[] = 'o.status = ?'; $b[] = $g['status']; }
        if (! empty($g['q'])) { $w[] = '(o.order_no LIKE ? OR qt.quotation_no LIKE ? OR org.name LIKE ? OR ct.first_name LIKE ? OR c.name LIKE ?)'; $b = array_merge($b, ['%' . $g['q'] . '%', '%' . $g['q'] . '%', '%' . $g['q'] . '%', '%' . $g['q'] . '%', '%' . $g['q'] . '%']); }
        if (! empty($g['from'])) { $w[] = 'o.created_at >= ?'; $b[] = $g['from'] . ' 00:00:00'; }
        if (! empty($g['to'])) { $w[] = 'o.created_at <= ?'; $b[] = $g['to'] . ' 23:59:59'; }
        $where = implode(' AND ', $w);
        $from  = 'FROM crm_sales_orders o JOIN crm_enquiries e ON e.id = o.enquiry_id JOIN crm_customers c ON c.id = o.customer_id JOIN crm_quotations qt ON qt.id = o.quotation_id LEFT JOIN crm_organisations org ON org.id = o.organisation_id LEFT JOIN crm_contacts ct ON ct.id = o.contact_id LEFT JOIN admin_users u ON u.id = o.salesperson_id';
        $sum   = $db->query("SELECT COUNT(*) n, COALESCE(SUM(o.grand_total),0) total, COALESCE(SUM(o.advance_amount),0) advance {$from} WHERE {$where}", $b)->getRowArray();
        $page  = $this->page();
        $rows  = $db->query("SELECT o.*, COALESCE(org.name, c.company_name, c.name) organisation_name, org.org_no, COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) contact_name, c.name customer_name, qt.quotation_no, e.enquiry_no, CONCAT(u.first_name,' ',u.last_name) salesperson {$from} WHERE {$where} ORDER BY o.id DESC LIMIT " . self::PER . ' OFFSET ' . (($page - 1) * self::PER), $b)->getResultArray();
        $awaiting = [];
        if (crm_can('orders.manage')) {
            $awaiting = $db->query("SELECT qt.id, qt.quotation_no, c.name customer_name, v.grand_total, v.version_no FROM crm_quotations qt JOIN crm_enquiries e ON e.id = qt.enquiry_id JOIN crm_customers c ON c.id = qt.customer_id
                JOIN crm_quotation_versions v ON v.id = qt.current_version_id WHERE qt.status = 'accepted' AND NOT EXISTS (SELECT 1 FROM crm_sales_orders so WHERE so.quotation_id = qt.id) AND " . $this->scopeSql() . ' ORDER BY qt.id DESC LIMIT 50')->getResultArray();
        }

        return $this->render('orders/index', ['rows' => $rows, 'sum' => $sum, 'g' => $g, 'page' => $page, 'per' => self::PER, 'awaiting' => $awaiting], 'Sales orders', 'orders');
    }

    /** Strictly validate the optional signed-confirmation upload BEFORE anything is created. @return array{file:\CodeIgniter\HTTP\Files\UploadedFile,ext:string,mime:string}|null */
    private function checkUpload(): ?array
    {
        $f = $this->request->getFile('confirmation');
        if (! $f || $f->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (! $f->isValid() || $f->hasMoved()) {
            throw CrmException::validation(['confirmation' => 'The file could not be uploaded (' . $f->getErrorString() . ').']);
        }
        $ext = strtolower($f->getClientExtension());
        if (! isset(self::DOC_TYPES[$ext])) {
            throw CrmException::validation(['confirmation' => 'Only PDF, JPG or PNG files are allowed.']);
        }
        if ($f->getSize() <= 0 || $f->getSize() > self::DOC_MAX) {
            throw CrmException::validation(['confirmation' => 'The file must be between 1 byte and 5 MB.']);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f->getRealPath());
        if (! in_array($mime, self::DOC_TYPES[$ext], true)) {
            throw CrmException::validation(['confirmation' => "The file content ({$mime}) does not match its .{$ext} extension."]);
        }
        if (str_starts_with($mime, 'image/') && @getimagesize($f->getRealPath()) === false) {
            throw CrmException::validation(['confirmation' => 'The image could not be read.']);
        }

        return ['file' => $f, 'ext' => $ext, 'mime' => $mime];
    }

    private function storeUpload(array $u, int $orderId, int $customerId, int $enquiryId): void
    {
        $dir = WRITEPATH . 'crm/documents/';
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $tmp    = $u['file']->getRealPath();
        $sha    = hash_file('sha256', $tmp);
        $stored = substr($sha, 0, 16) . '-' . bin2hex(random_bytes(6)) . '.' . $u['ext'];
        if (! @move_uploaded_file($tmp, $dir . $stored) && ! @copy($tmp, $dir . $stored)) {
            throw new CrmException('The confirmation file could not be stored. The order was created; upload the file from the order page again or check storage permissions.');
        }
        @chmod($dir . $stored, 0640);
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($u['file']->getClientName()));
        Crm::db()->table('crm_documents')->insert(['entity_type' => 'sales_order', 'entity_id' => $orderId, 'customer_id' => $customerId, 'original_name' => mb_substr($name, 0, 255), 'stored_name' => $stored,
            'mime' => $u['mime'], 'size_bytes' => (int) $u['file']->getSize(), 'sha256' => $sha, 'uploaded_by' => $this->uid(), 'created_at' => Crm::now()]);
        AuditService::audit('document.uploaded', 'sales_order', $orderId, ['meta' => ['name' => $name, 'mime' => $u['mime'], 'sha256' => $sha, 'enquiry_id' => $enquiryId]]);
    }

    public function create()
    {
        $qid = (int) $this->request->getPost('quotation_id');

        return $this->act(function () use ($qid) {
            QuotationService::loadQuotation($qid, $this->user());   // scope check first
            $up = $this->checkUpload();
            $r  = OrderService::createFromQuotation($qid, $this->user(), [
                'advance_amount' => trim((string) $this->request->getPost('advance_amount')), 'advance_reference' => trim((string) $this->request->getPost('advance_reference')) ?: null,
                'expected_delivery' => trim((string) $this->request->getPost('expected_delivery')) ?: null, 'remarks' => trim((string) $this->request->getPost('remarks')) ?: null, 'confirm' => (bool) $this->request->getPost('confirm'),
            ]);
            if ($up) {
                $o = Crm::db()->table('crm_sales_orders')->where('id', $r['id'])->get()->getRowArray();
                $this->storeUpload($up, (int) $r['id'], (int) $o['customer_id'], (int) $o['enquiry_id']);
            }

            return 'crm/orders/' . $r['id'];
        }, 'Sales order created with the accepted quotation values. You can now mark the enquiry as won from the order page.');
    }

    public function show(int $id): string
    {
        $o  = $this->loadO($id);
        $db = Crm::db();
        $c  = $db->table('crm_customers')->where('id', $o['customer_id'])->get()->getRowArray();
        $orgId = ! empty($o['organisation_id']) ? (int) $o['organisation_id'] : (int) ($c['organisation_id'] ?? 0);
        $contactId = ! empty($o['contact_id']) ? (int) $o['contact_id'] : (int) ($c['primary_contact_id'] ?? 0);
        $org = $orgId ? $db->table('crm_organisations')->where('id', $orgId)->get()->getRowArray() : null;
        $contact = $contactId ? $db->table('crm_contacts')->where('id', $contactId)->get()->getRowArray() : null;
        $e  = $db->table('crm_enquiries')->where('id', $o['enquiry_id'])->get()->getRowArray();
        $q  = $db->table('crm_quotations')->where('id', $o['quotation_id'])->get()->getRowArray();
        $v  = $db->table('crm_quotation_versions')->where('id', $o['version_id'])->get()->getRowArray();
        $names = HistoryService::userNames();
        $events = array_map(static fn ($ev) => HistoryService::present($ev, $names), $db->table('crm_workflow_events')->where(['entity_type' => 'sales_order', 'entity_id' => $id])->orderBy('created_at')->orderBy('id')->get()->getResultArray());

        return $this->render('orders/show', [
            'o' => $o, 'c' => $c, 'org' => $org, 'contact' => $contact, 'e' => $e, 'q' => $q, 'v' => $v, 'items' => $db->table('crm_sales_order_items')->where('order_id', $id)->orderBy('id')->get()->getResultArray(),
            'docs' => $db->table('crm_documents')->where(['entity_type' => 'sales_order', 'entity_id' => $id])->orderBy('id', 'DESC')->get()->getResultArray(),
            'events' => $events, 'flow' => OrderService::ORDER_FLOW[$o['status']] ?? [], 'owner' => $names[(int) $o['salesperson_id']] ?? '—', 'creator' => $names[(int) $o['created_by']] ?? '—',
        ], $o['order_no'], 'orders');
    }

    public function status(int $id)
    {
        $this->loadO($id);
        $to = (string) $this->request->getPost('to');

        return $this->act(function () use ($id, $to) {
            OrderService::setStatus($id, $to, $this->user(), trim((string) $this->request->getPost('reason')) ?: null);

            return "crm/orders/{$id}";
        }, 'Order status updated to ' . str_replace('_', ' ', $to) . '.');
    }
}
