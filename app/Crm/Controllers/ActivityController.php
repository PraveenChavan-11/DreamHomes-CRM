<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\ActivityService;
use Crm\Services\AuditService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\HistoryService;

/** Follow-ups list/actions, site visits, versioned measurements, and validated document upload/download. */
class ActivityController extends CrmController
{
    private const PER = 25;
    /** extension => allowed real MIME types (detected from file content, never from the client). */
    private const DOC_TYPES = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'], 'pdf' => ['application/pdf'],
    ];
    private const DOC_MAX = 8 * 1024 * 1024;

    // ───────────── follow-ups ─────────────

    /** Restrict a follow-up query (alias f, enquiries e) to what the signed-in user may see. */
    private function scopeFollowups($b)
    {
        $ids = AccessScope::visibleUserIds($this->user());
        if ($ids !== null) {
            $in = implode(',', array_map('intval', $ids));
            $b->where("((f.enquiry_id IS NULL AND f.assigned_to IN ({$in})) OR (f.enquiry_id IS NOT NULL AND (e.current_owner_id IN ({$in}) OR e.created_by IN ({$in}) OR f.assigned_to IN ({$in}))))", null, false);
        }

        return $b;
    }

    private function followupQuery(string $tab, ?int $assignee)
    {
        $b = Crm::db()->table('crm_followups f')
            ->select("f.*, e.enquiry_no, e.project_name, c.name AS customer_name, c.mobile AS customer_mobile, CONCAT(u.first_name,' ',u.last_name) AS user_name,
                      q.id AS quotation_id, q.quotation_no, q.status AS quotation_status,
                      n.id AS negotiation_id, n.negotiation_no, n.status AS negotiation_status, n.requested_discount_pct")
            ->join('crm_enquiries e', 'e.id = f.enquiry_id', 'left')
            ->join('crm_customers c', 'c.id = f.customer_id')
            ->join('admin_users u', 'u.id = f.assigned_to', 'left')
            ->join('crm_quotations q', 'q.enquiry_id = f.enquiry_id AND q.id = (SELECT MAX(q2.id) FROM crm_quotations q2 WHERE q2.enquiry_id = f.enquiry_id)', 'left')
            ->join('crm_negotiations n', 'n.quotation_id = q.id AND n.id = (SELECT MAX(n2.id) FROM crm_negotiations n2 WHERE n2.quotation_id = q.id)', 'left');
        $this->scopeFollowups($b);
        $today = date('Y-m-d');
        match ($tab) {
            'today'     => $b->whereIn('f.status', ['pending', 'missed'])->where('f.due_at >=', $today . ' 00:00:00')->where('f.due_at <=', $today . ' 23:59:59'),
            'upcoming'  => $b->where('f.status', 'pending')->where('f.due_at >', $today . ' 23:59:59'),
            'overdue'   => $b->whereIn('f.status', ['pending', 'missed'])->where('f.due_at <', $today . ' 00:00:00'),
            default     => $b->whereIn('f.status', ['completed', 'rescheduled', 'cancelled']),
        };
        if ($assignee) {
            $b->where('f.assigned_to', $assignee);
        }

        return $b;
    }

    public function followups(): string
    {
        $tab = in_array($this->request->getGet('tab'), ['today', 'upcoming', 'overdue', 'completed'], true) ? $this->request->getGet('tab') : 'today';
        $assignee = (int) $this->request->getGet('user_id') ?: null;
        if (AccessScope::level($this->user()) === 'own') {
            $assignee = null;   // own scope already restricts; hide the picker
        }
        $counts = [];
        foreach (['today', 'upcoming', 'overdue', 'completed'] as $t) {
            $counts[$t] = $this->followupQuery($t, $assignee)->countAllResults();
        }
        $page = $this->page();
        $rows = $this->followupQuery($tab, $assignee)->orderBy('f.due_at', $tab === 'completed' ? 'DESC' : 'ASC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();
        $users = Crm::db()->query("SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id WHERE u.is_active = 1 AND r.slug IN ('sales_executive','sales_manager') ORDER BY name")->getResultArray();
        $q = array_filter(['tab' => $tab, 'user_id' => $assignee]);

        return $this->render('activity/followups', [
            'tab' => $tab, 'rows' => $rows, 'counts' => $counts, 'assignee' => $assignee, 'users' => $users, 'showPicker' => AccessScope::level($this->user()) !== 'own',
            'pager' => view('Crm\Views\enquiries\_pager', ['total' => $counts[$tab], 'per' => self::PER, 'page' => $page, 'query' => $q]), 'now' => date('Y-m-d H:i:s'),
        ], 'Follow-ups', 'followups');
    }

    public function completeFollowup(int $id)
    {
        $p = $this->request;
        $n = trim((string) $p->getPost('next_due_at'));
        $u = $this->user();

        return $this->act(function () use ($id, $p, $n, $u) {
            ActivityService::completeFollowup($id, $u, trim((string) $p->getPost('outcome')), trim((string) $p->getPost('remarks')) ?: null,
                $n !== '' ? $n : null, trim((string) $p->getPost('next_action')) ?: null);

            $qid = (int) $p->getPost('quotation_id');
            $askPct = trim((string) $p->getPost('requested_discount_pct'));
            $expAmt = trim((string) $p->getPost('customer_expected_amount'));
            $negReason = trim((string) $p->getPost('negotiation_reason'));

            if ($qid > 0 && ($askPct !== '' || $expAmt !== '') && $negReason !== '') {
                if ($askPct === '' && $expAmt !== '') {
                    $q = \Crm\Services\QuotationService::loadQuotation($qid, $u);
                    $askPct = \Crm\Services\NegotiationService::pctForTarget((int) $q['current_version_id'], $expAmt);
                }
                if ($askPct !== '') {
                    \Crm\Services\NegotiationService::request($qid, $u, $askPct, $negReason, $expAmt !== '' ? $expAmt : null);
                }
            }

            return 'crm/followups';
        }, 'Follow-up completed and logged.');
    }

    public function rescheduleFollowup(int $id)
    {
        $p = $this->request;

        return $this->act(fn () => ActivityService::rescheduleFollowup($id, $this->user(), (string) $p->getPost('new_due_at'), trim((string) $p->getPost('reason'))), 'Follow-up rescheduled (original kept in history).');
    }

    // ───────────── visits ─────────────

    private function visitRow(int $id): array
    {
        $v = Crm::db()->table('crm_site_visits v')
            ->select("v.*, e.enquiry_no, e.project_name, e.status AS enquiry_status, c.name AS customer_name, c.mobile AS customer_mobile, CONCAT(u.first_name,' ',u.last_name) AS user_name")
            ->join('crm_enquiries e', 'e.id = v.enquiry_id')->join('crm_customers c', 'c.id = v.customer_id')->join('admin_users u', 'u.id = v.assigned_to', 'left')
            ->where('v.id', $id)->get()->getRowArray();
        if (! $v || ! AccessScope::canSeeEnquiry($this->user(), (int) $v['enquiry_id'])) {
            throw new PageNotFoundException('Visit not found');
        }

        return $v;
    }

    public function visits(): string
    {
        $tab = in_array($this->request->getGet('tab'), ['scheduled', 'completed', 'all'], true) ? $this->request->getGet('tab') : 'scheduled';
        $mk = function () use ($tab) {
            $b = Crm::db()->table('crm_site_visits v')
                ->select("v.*, e.enquiry_no, e.project_name, COALESCE(o.name, c.company_name, c.name) AS organisation_name, COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) AS contact_person, ct.designation AS contact_role, o.id AS organisation_id, ct.id AS contact_id, c.name AS customer_name, CONCAT(u.first_name,' ',u.last_name) AS user_name, (SELECT COUNT(*) FROM crm_site_measurements m WHERE m.visit_id = v.id) AS meas_versions")
                ->join('crm_enquiries e', 'e.id = v.enquiry_id')
                ->join('crm_customers c', 'c.id = v.customer_id', 'left')
                ->join('crm_organisations o', 'o.id = e.organisation_id', 'left')
                ->join('crm_contacts ct', 'ct.id = e.contact_id', 'left')
                ->join('admin_users u', 'u.id = v.assigned_to', 'left');
            AccessScope::scopeEnquiries($b, $this->user(), 'e');
            if ($tab !== 'all') {
                $b->where('v.status', $tab);
            }

            return $b;
        };
        $page  = $this->page();
        $total = $mk()->countAllResults();
        $rows  = $mk()->orderBy('v.scheduled_at', $tab === 'completed' ? 'DESC' : 'ASC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();

        return $this->render('activity/visits', ['tab' => $tab, 'rows' => $rows, 'total' => $total,
            'pager' => view('Crm\Views\enquiries\_pager', ['total' => $total, 'per' => self::PER, 'page' => $page, 'query' => ['tab' => $tab]])], 'Site visits', 'visits');
    }

    public function measurements(): string
    {
        $db = Crm::db();
        $q = trim((string) $this->request->getGet('q'));
        $b = $db->table('crm_site_measurements m')
            ->select("m.*, v.visit_no, v.scheduled_at, e.enquiry_no, e.project_name, 
                      COALESCE(o.name, c.company_name, c.name) AS organisation_name, 
                      COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) AS contact_person,
                      ct.designation AS contact_role,
                      o.id AS organisation_id, ct.id AS contact_id,
                      c.name AS customer_name, c.mobile AS customer_mobile, CONCAT(u.first_name, ' ', u.last_name) AS recorded_by_name")
            ->join('crm_site_visits v', 'v.id = m.visit_id')
            ->join('crm_enquiries e', 'e.id = v.enquiry_id')
            ->join('crm_customers c', 'c.id = v.customer_id', 'left')
            ->join('crm_organisations o', 'o.id = e.organisation_id', 'left')
            ->join('crm_contacts ct', 'ct.id = e.contact_id', 'left')
            ->join('admin_users u', 'u.id = m.recorded_by', 'left');
        AccessScope::scopeEnquiries($b, $this->user(), 'e');

        if ($q !== '') {
            $b->groupStart()
                ->like('v.visit_no', $q)
                ->orLike('e.enquiry_no', $q)
                ->orLike('e.project_name', $q)
                ->orLike('o.name', $q)
                ->orLike('c.name', $q)
                ->orLike('ct.first_name', $q)
                ->orLike('ct.last_name', $q)
                ->orLike('m.revision_note', $q)
                ->groupEnd();
        }

        $page = $this->page();
        $total = $b->countAllResults(false);
        $rows = $b->orderBy('m.id', 'DESC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['items'] = json_decode((string) $r['data'], true) ?: [];
        }

        return $this->render('activity/measurements', [
            'rows' => $rows,
            'total' => $total,
            'q' => $q,
            'pager' => view('Crm\Views\enquiries\_pager', ['total' => $total, 'per' => self::PER, 'page' => $page, 'query' => ['q' => $q]]),
        ], 'Site Measurements & Dimensions', 'measurements');
    }

    public function visit(int $id): string
    {
        $v  = $this->visitRow($id);
        $db = Crm::db();
        $u  = HistoryService::userNames();
        $versions = array_map(static function ($m) use ($u) {
            $m['items']     = json_decode((string) $m['data'], true) ?: [];
            $m['user_name'] = $m['recorded_by'] ? ($u[(int) $m['recorded_by']] ?? '?') : 'System';

            return $m;
        }, $db->table('crm_site_measurements')->where('visit_id', $id)->orderBy('version_no', 'DESC')->get()->getResultArray());
        $current = null;
        foreach ($versions as $m) {
            if ((int) $m['is_current'] === 1) {
                $current = $m;
            }
        }
        $docs = $db->query('SELECT d.*, 1 AS x FROM crm_documents d WHERE d.entity_type = \'site_visit\' AND d.entity_id = ? ORDER BY d.created_at DESC', [$id])->getResultArray();
        foreach ($docs as &$d) {
            $d['user_name'] = $d['uploaded_by'] ? ($u[(int) $d['uploaded_by']] ?? '?') : 'System';
        }

        return $this->render('activity/visit', ['v' => $v, 'versions' => $versions, 'current' => $current, 'docs' => $docs, 'exts' => array_keys(self::DOC_TYPES)], $v['visit_no'] . ' · Site visit', 'visits');
    }

    /** @return array<string,string> label => "W × H unit — notes" from the posted measurement rows */
    private function postedMeasurements(): array
    {
        $r = $this->request;
        $labels = (array) $r->getPost('m_label');
        $out = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $w = trim((string) ($r->getPost('m_width')[$i] ?? ''));
            $h = trim((string) ($r->getPost('m_height')[$i] ?? ''));
            $unit = (string) ($r->getPost('m_unit')[$i] ?? 'mm');
            $notes = trim((string) ($r->getPost('m_notes')[$i] ?? ''));
            if ($label === '' && $w === '' && $h === '' && $notes === '') {
                continue;
            }
            if ($label === '') {
                throw CrmException::validation(['m_label' => 'Every measurement row needs an item/room name.']);
            }
            foreach ([$w, $h] as $n) {
                if ($n !== '' && (! is_numeric($n) || (float) $n < 0)) {
                    throw CrmException::validation(['m_width' => "Measurement “{$label}”: width/height must be positive numbers."]);
                }
            }
            if (! in_array($unit, ['mm', 'cm', 'inch', 'ft'], true)) {
                $unit = 'mm';
            }
            $size = ($w !== '' ? $w : '?') . ' × ' . ($h !== '' ? $h : '?') . ' ' . $unit;
            $key = $label;
            for ($k = 2; isset($out[$key]); $k++) {
                $key = $label . ' (' . $k . ')';
            }
            $out[$key] = mb_substr($size . ($notes !== '' ? ' — ' . $notes : ''), 0, 300);
        }

        return $out;
    }

    public function completeVisit(int $id)
    {
        $v = $this->visitRow($id);
        $p = $this->request;

        return $this->act(function () use ($id, $p) {
            ActivityService::completeVisit($id, $this->user(), $this->postedMeasurements(), trim((string) $p->getPost('observations')) ?: null,
                trim((string) $p->getPost('product_requirements')) ?: null, trim((string) $p->getPost('follow_up_action')) ?: null);
        }, "Visit {$v['visit_no']} completed.", "crm/visits/{$id}");
    }

    public function saveMeasurements(int $id)
    {
        $this->visitRow($id);   // scope check (the service itself does not check visibility)
        $notes = trim((string) $this->request->getPost('revision_note'));

        return $this->act(function () use ($id, $notes) {
            if ($notes === '') {
                throw CrmException::validation(['revision_note' => 'Say why the measurements are being revised.']);
            }
            $m = $this->postedMeasurements();
            if (! $m) {
                throw CrmException::validation(['m_label' => 'Enter at least one measurement.']);
            }
            ActivityService::saveMeasurements($id, $this->user(), $m, $notes);
        }, 'New measurement version saved (previous versions kept).', "crm/visits/{$id}");
    }

    // ───────────── documents ─────────────

    public function uploadDocument(int $id)
    {
        $v = $this->visitRow($id);

        return $this->act(function () use ($id, $v) {
            $f = $this->request->getFile('document');
            if (! $f || ! $f->isValid() || $f->hasMoved()) {
                throw CrmException::validation(['document' => 'Choose a file to upload.']);
            }
            $ext = strtolower($f->getClientExtension());
            if (! isset(self::DOC_TYPES[$ext])) {
                throw CrmException::validation(['document' => 'Only ' . implode(', ', array_keys(self::DOC_TYPES)) . ' files are allowed.']);
            }
            if ($f->getSize() <= 0 || $f->getSize() > self::DOC_MAX) {
                throw CrmException::validation(['document' => 'File must be between 1 byte and 8 MB.']);
            }
            $tmp  = $f->getRealPath();
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (! in_array($mime, self::DOC_TYPES[$ext], true)) {
                throw CrmException::validation(['document' => "The file content ({$mime}) does not match its .{$ext} extension."]);
            }
            if (str_starts_with($mime, 'image/') && @getimagesize($tmp) === false) {
                throw CrmException::validation(['document' => 'The image could not be read.']);
            }
            $dir = WRITEPATH . 'crm/documents/';
            if (! is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
            $sha    = hash_file('sha256', $tmp);
            $stored = substr($sha, 0, 16) . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (! @move_uploaded_file($tmp, $dir . $stored) && ! @copy($tmp, $dir . $stored)) {
                throw new CrmException('The file could not be stored. Check storage permissions.');
            }
            @chmod($dir . $stored, 0640);
            $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($f->getClientName()));
            $db = Crm::db();
            $db->table('crm_documents')->insert([
                'entity_type' => 'site_visit', 'entity_id' => $id, 'customer_id' => (int) $v['customer_id'], 'original_name' => mb_substr($name, 0, 255), 'stored_name' => $stored,
                'mime' => $mime, 'size_bytes' => (int) $f->getSize(), 'sha256' => $sha, 'uploaded_by' => $this->uid(), 'created_at' => Crm::now(),
            ]);
            AuditService::audit('document.uploaded', 'site_visit', $id, ['meta' => ['name' => $name, 'mime' => $mime, 'size' => (int) $f->getSize(), 'sha256' => $sha, 'enquiry_id' => (int) $v['enquiry_id']]]);
        }, 'Document uploaded.', "crm/visits/{$id}");
    }

    public function document(int $id)
    {
        $d = Crm::db()->table('crm_documents')->where('id', $id)->get()->getRowArray();
        if (! $d) {
            throw new PageNotFoundException('Not found');
        }
        $ok = false;
        if ($d['entity_type'] === 'site_visit') {
            $v  = Crm::db()->table('crm_site_visits')->select('enquiry_id')->where('id', (int) $d['entity_id'])->get()->getRowArray();
            $ok = $v && AccessScope::canSeeEnquiry($this->user(), (int) $v['enquiry_id']);
        } elseif ($d['entity_type'] === 'enquiry') {
            $ok = AccessScope::canSeeEnquiry($this->user(), (int) $d['entity_id']);
        } elseif ($d['customer_id']) {
            $ok = AccessScope::canSeeCustomer($this->user(), (int) $d['customer_id']);
        }
        $path = WRITEPATH . 'crm/documents/' . basename((string) $d['stored_name']);
        if (! $ok || ! is_file($path)) {
            throw new PageNotFoundException('Not found');
        }
        $inline = str_starts_with($d['mime'], 'image/');   // PDFs always download; the site-wide CSP header replaces any stricter one set here

        return $this->response->setHeader('Content-Type', $d['mime'])
            ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $d['original_name']) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox")
            ->setHeader('Cache-Control', 'private, max-age=0, no-store')->setBody((string) file_get_contents($path));
    }
}
