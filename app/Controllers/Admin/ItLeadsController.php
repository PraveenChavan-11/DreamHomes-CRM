<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Services\CrmNotificationService;
use App\Services\ItLeadService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CRM / IT Services leads (website enquiries, product enquiries, WorkSphere events).
 * Permissions: crm.view|manage|export|settings. The older it_leads.view|manage codes keep working (aliases).
 */
class ItLeadsController extends AdminBaseController
{
    private ItLeadService $svc;

    private const SORTS = ['created_at' => 'l.created_at', 'last_activity' => 'l.last_activity_at', 'priority' => 'l.priority',
        'status' => 'l.status', 'name' => 'l.client_name', 'value' => 'l.estimated_value', 'followup' => 'l.next_follow_up_at'];

    public function __construct()
    {
        $this->svc = new ItLeadService();
    }

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    private function adminId(): int
    {
        return (int) ($this->adminUser['id'] ?? 0);
    }

    private function audit(string $action, int $id, ?array $new = null): void
    {
        try {
            (new AuditLogModel())->log($action, 'it_lead', $id, null, $new, $this->adminId());
        } catch (\Throwable $e) {
            log_message('error', 'IT lead audit failed: ' . $e->getMessage());
        }
    }

    /** Effective permission check with legacy it_leads.* aliases. */
    private function can(string $action): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $p = $this->adminUser['permissions'] ?? [];
        $any = static fn (array $codes) => (bool) array_intersect($codes, $p);
        return match ($action) {
            'view'     => $any(['crm.view', 'crm.manage', 'it_leads.view', 'it_leads.manage']),
            'manage'   => $any(['crm.manage', 'it_leads.manage']),
            'export'   => $any(['crm.export', 'it_leads.manage']),
            'settings' => $any(['crm.settings']),
            default    => false,
        };
    }

    private function need(string $action): ?ResponseInterface
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if ($this->can($action)) {
            return null;
        }
        $msg = "Access restricted: required privilege 'crm.{$action}'.";
        if ($this->isApiRequest() || $this->request->isAJAX()) {
            return $this->respondError($msg, 403, 'FORBIDDEN');
        }
        session()->setFlashdata('error', $msg);
        return redirect()->to(base_url('admin'));
    }

    private function staff(): array
    {
        return $this->db()->table('admin_users')->select('id, first_name, last_name, username')
            ->where('is_active', 1)->orderBy('first_name')->get()->getResultArray();
    }

    private function filterValues(): array
    {
        $g = fn (string $k) => trim((string) $this->request->getGet($k));
        return ['status' => $g('status'), 'source' => $g('source'), 'product' => $g('product'), 'priority' => $g('priority'),
            'assigned' => $g('assigned'), 'category' => $g('category'), 'date_from' => $g('date_from'), 'date_to' => $g('date_to'),
            'tag' => $g('tag'), 'followup' => $g('followup'), 'search' => $g('search'), 'sort' => $g('sort'), 'dir' => $g('dir')];
    }

    private function filtered(): \CodeIgniter\Database\BaseBuilder
    {
        $f = $this->filterValues();
        $b = $this->db()->table('it_leads l')
            ->select("l.*, TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS assignee_name", false)
            ->join('admin_users u', 'u.id = l.assigned_to', 'left')
            ->where('l.deleted_at IS NULL');

        if (isset(ItLeadService::STATUSES[$f['status']])) {
            $b->where('l.status', $f['status']);
        }
        if ($f['category'] !== '' && isset(ItLeadService::CATEGORIES[$f['category']])) {
            $b->where('l.service_category', $f['category']);
        }
        if ($f['source'] !== '' && preg_match('/^[a-z_]{1,40}$/', $f['source'])) {
            $b->where('l.source', $f['source']);
        }
        if ($f['product'] !== '' && preg_match('/^[a-z0-9\-]{1,120}$/', $f['product'])) {
            $b->where('l.product_slug', $f['product']);
        }
        if (isset(ItLeadService::PRIORITIES[$f['priority']])) {
            $b->where('l.priority', $f['priority']);
        }
        if ($f['assigned'] === 'none') {
            $b->where('l.assigned_to IS NULL');
        } elseif ($f['assigned'] !== '' && ctype_digit($f['assigned'])) {
            $b->where('l.assigned_to', (int) $f['assigned']);
        }
        foreach (['date_from' => ['>=', ' 00:00:00'], 'date_to' => ['<=', ' 23:59:59']] as $k => [$op, $suffix]) {
            $d = \DateTime::createFromFormat('Y-m-d', $f[$k]);
            if ($d && $d->format('Y-m-d') === $f[$k]) {
                $b->where("l.created_at {$op}", $f[$k] . $suffix);
            }
        }
        if ($f['tag'] !== '' && preg_match('/^[a-z0-9\- ]{1,30}$/', $f['tag'])) {
            $b->like('l.tags', $f['tag']);
        }
        if ($f['followup'] === 'due') {
            $b->whereNotIn('l.status', ItLeadService::CLOSED)->where('l.next_follow_up_at <=', date('Y-m-d'));
        }
        if ($f['search'] !== '') {
            $b->groupStart()->like('l.client_name', $f['search'])->orLike('l.company', $f['search'])
              ->orLike('l.email', $f['search'])->orLike('l.phone', $f['search'])->orLike('l.project_description', $f['search'])->groupEnd();
        }
        return $b;
    }

    private function applySort(\CodeIgniter\Database\BaseBuilder $b): \CodeIgniter\Database\BaseBuilder
    {
        $f = $this->filterValues();
        $col = self::SORTS[$f['sort']] ?? 'l.created_at';
        $dir = strtolower($f['dir']) === 'asc' ? 'ASC' : 'DESC';
        return $b->orderBy($col, $dir)->orderBy('l.id', 'DESC');
    }

    /** CSV cell neutralisation: values that spreadsheets would execute as formulas are prefixed with a quote. */
    public static function csvSafe($v)
    {
        if (is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r\n") !== false) {
            return "'" . $v;
        }
        return $v;
    }

    private function exportCsv(): ResponseInterface
    {
        $rows = $this->applySort($this->filtered())->limit(5000)->get()->getResultArray();
        $this->audit('CRM_LEADS_EXPORT', 0, ['rows' => count($rows), 'filters' => array_filter($this->filterValues())]);
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['ID', 'Received', 'Name', 'Company', 'Email', 'Phone', 'Location', 'Service', 'Product', 'Source', 'Status', 'Priority', 'Estimated Value',
            'Tags', 'Assigned To', 'Next Follow-up', 'Enquiries', 'Demo Status', 'Demo Expires', 'Lost Reason', 'Source Page', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Consent At']);
        foreach ($rows as $x) {
            fputcsv($fh, array_map([self::class, 'csvSafe'], [
                $x['id'], $x['created_at'], $x['client_name'], $x['company'], $x['email'], $x['phone'], $x['location'],
                ItLeadService::CATEGORIES[$x['service_category']] ?? $x['service_category'], $x['product_slug'], $x['source'],
                ItLeadService::STATUSES[$x['status']] ?? $x['status'], $x['priority'], $x['estimated_value'], $x['tags'], $x['assignee_name'],
                $x['next_follow_up_at'], $x['enquiry_count'], $x['demo_status'], $x['demo_expires_at'], $x['lost_reason'],
                $x['source_page'], $x['utm_source'], $x['utm_medium'], $x['utm_campaign'], $x['consent_at'],
            ]));
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="talenthub_crm_leads_' . date('Y-m-d_His') . '.csv"')
            ->setBody($csv);
    }

    public function index()
    {
        if ($r = $this->need('view')) {
            return $r;
        }
        if ($this->request->getGet('export') === 'csv') {
            if ($r = $this->need('export')) {
                return $r;
            }
            return $this->exportCsv();
        }

        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $total = (clone $this->filtered())->countAllResults();
        $leads = $this->applySort($this->filtered())->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        $db = $this->db();
        $products = array_column($db->query("SELECT DISTINCT product_slug FROM it_leads WHERE product_slug IS NOT NULL AND product_slug <> '' AND deleted_at IS NULL ORDER BY product_slug LIMIT 100")->getResultArray(), 'product_slug');
        $sources  = array_column($db->query("SELECT DISTINCT source FROM it_leads WHERE deleted_at IS NULL ORDER BY source LIMIT 50")->getResultArray(), 'source');

        return $this->renderAdminView('admin/it_leads/index', [
            'title'      => 'CRM Leads — TalentHub Admin',
            'leads'      => $leads,
            'totalCount' => $total,
            'totalPages' => (int) ceil($total / $perPage),
            'page'       => $page,
            'perPage'    => $perPage,
            'filters'    => $this->filterValues(),
            'products'   => $products,
            'sources'    => $sources,
            'staff'      => $this->staff(),
            'kpis'       => $this->svc->counts(),
            'canManage'  => $this->can('manage'),
            'canExport'  => $this->can('export'),
            'canSettings' => $this->can('settings'),
        ], 'it_leads');
    }

    public function show(int $id)
    {
        if ($r = $this->need('view')) {
            return $r;
        }
        $lead = $this->svc->find($id);
        if (!$lead) {
            session()->setFlashdata('error', 'Lead not found.');
            return redirect()->to(base_url('admin/it-leads'));
        }
        $db   = $this->db();
        $acts = $db->table('it_lead_activities a')
            ->select("a.*, TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS author", false)
            ->join('admin_users u', 'u.id = a.admin_user_id', 'left')
            ->where('a.lead_id', $id)->orderBy('a.created_at', 'DESC')->orderBy('a.id', 'DESC')->limit(500)->get()->getResultArray();
        $dups = $db->table('it_leads')->select('id, client_name, status, created_at')
            ->where('deleted_at IS NULL')->where('id !=', $id)
            ->groupStart()->where('email', $lead['email'])->orWhere('phone', $lead['phone'])->groupEnd()
            ->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();
        $emails = $db->table('email_outbox')->select('id, template_code, status, attempts, last_error, created_at')
            ->like('idempotency_key', 'crm-L' . $id . '-', 'after')->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();
        $events = $db->table('crm_integration_events')->select('event_type, result, error_code, received_at')
            ->where('lead_id', $id)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();

        return $this->renderAdminView('admin/it_leads/show', [
            'title'     => 'Lead #' . $id . ' — TalentHub Admin',
            'lead'      => $lead,
            'acts'      => $acts,
            'dups'      => $dups,
            'emails'    => $emails,
            'events'    => $events,
            'staff'     => $this->staff(),
            'canManage' => $this->can('manage'),
        ], 'it_leads');
    }

    /** Shared wrapper for POST actions: permission, result flash, redirect back to lead. */
    private function act(int $id, callable $fn, string $okMsg, string $failMsg, string $auditAction, ?array $auditData = null)
    {
        if ($r = $this->need('manage')) {
            return $r;
        }
        $ok = $fn();
        if ($ok) {
            $this->audit($auditAction, $id, $auditData);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? $okMsg : $failMsg);
        return redirect()->to(base_url('admin/it-leads/' . $id));
    }

    public function updateStatus(int $id)
    {
        $status = (string) $this->request->getPost('status');
        $reason = (string) $this->request->getPost('lost_reason');
        return $this->act($id, fn () => $this->svc->changeStatus($id, $status, $this->adminId(), $reason),
            'Status updated.', $status === 'lost' ? 'A reason is required to mark a lead Closed Lost.' : 'Could not update status.',
            'IT_LEAD_STATUS', ['status' => $status, 'lost_reason' => $status === 'lost' ? $reason : null]);
    }

    public function assign(int $id)
    {
        $uid = (string) $this->request->getPost('assigned_to');
        $uid = ctype_digit($uid) && $uid !== '' ? (int) $uid : null;
        return $this->act($id, function () use ($id, $uid) {
            $ok = $this->svc->assign($id, $uid, $this->adminId());
            if ($ok && $uid && $uid !== $this->adminId()) {
                (new CrmNotificationService())->notify('lead_assigned', $id, [], 'assign-' . $uid . '-' . date('YmdHi'));
            }
            return $ok;
        }, 'Assignment updated.', 'Could not assign (user inactive or lead missing).', 'IT_LEAD_ASSIGN', ['assigned_to' => $uid]);
    }

    public function addNote(int $id)
    {
        $note = (string) $this->request->getPost('note');
        return $this->act($id, fn () => $this->svc->addNote($id, $note, $this->adminId()),
            'Note added.', 'Note cannot be empty.', 'IT_LEAD_NOTE');
    }

    public function followUp(int $id)
    {
        $date = (string) $this->request->getPost('next_follow_up_at');
        return $this->act($id, fn () => $this->svc->setFollowUp($id, $date, $this->adminId()),
            'Follow-up saved.', 'Invalid follow-up date.', 'IT_LEAD_FOLLOWUP', ['date' => $date]);
    }

    /** Priority, estimated value and tags in one form. */
    public function updateMeta(int $id)
    {
        $p = (string) $this->request->getPost('priority');
        $v = (string) $this->request->getPost('estimated_value');
        $t = (string) $this->request->getPost('tags');
        return $this->act($id, fn () => $this->svc->setPriority($id, $p, $this->adminId())
                && $this->svc->setEstimatedValue($id, $v, $this->adminId()) && $this->svc->setTags($id, $t, $this->adminId()),
            'Lead details saved.', 'Invalid priority or estimated value (use a number such as 250000).', 'IT_LEAD_META', ['priority' => $p, 'estimated_value' => $v, 'tags' => $t]);
    }

    public function bulk()
    {
        if ($r = $this->need('manage')) {
            return $r;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->request->getPost('ids')))));
        $ids = array_slice($ids, 0, 200);
        $action = (string) $this->request->getPost('bulk_action');
        $value  = (string) $this->request->getPost('bulk_value');
        $reason = (string) $this->request->getPost('bulk_reason');
        $back   = redirect()->to(base_url('admin/it-leads'));
        if (!$ids || !in_array($action, ['status', 'assign'], true)) {
            session()->setFlashdata('error', 'Select at least one lead and an action.');
            return $back;
        }
        $done = 0;
        foreach ($ids as $id) {
            if ($action === 'status') {
                $ok = $this->svc->changeStatus($id, $value, $this->adminId(), $reason);
            } else {
                $ok = $this->svc->assign($id, ctype_digit($value) && $value !== '' ? (int) $value : null, $this->adminId());
            }
            $done += $ok ? 1 : 0;
        }
        $this->audit('CRM_LEADS_BULK', 0, ['action' => $action, 'value' => $value, 'requested' => count($ids), 'done' => $done]);
        session()->setFlashdata($done ? 'success' : 'error', "Bulk {$action}: {$done} of " . count($ids) . ' lead(s) updated.' . (($value === 'lost' && $reason === '') ? ' Closed Lost needs a reason.' : ''));
        return $back;
    }

    public function delete(int $id)
    {
        if ($r = $this->need('manage')) {
            return $r;
        }
        $ok = $this->svc->softDelete($id, $this->adminId());
        if ($ok) {
            $this->audit('IT_LEAD_ARCHIVE', $id);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Lead archived. It is soft-deleted and can be restored from the database.' : 'Lead not found.');
        return redirect()->to(base_url('admin/it-leads'));
    }

    // ── CRM settings (notification recipient) ──────────────────────────────────
    public function settings()
    {
        if ($r = $this->need('settings')) {
            return $r;
        }
        $n = new CrmNotificationService();
        $db = $this->db();
        return $this->renderAdminView('admin/it_leads/settings', [
            'title'      => 'CRM Settings — TalentHub Admin',
            'recipient'  => $n->recipient(),
            'categories' => $db->table('crm_categories')->orderBy('sort_order')->get()->getResultArray(),
            'recent'     => $db->table('email_outbox')->select('id, template_code, status, attempts, last_error, created_at')->like('template_code', 'crm_', 'after')->orderBy('id', 'DESC')->limit(15)->get()->getResultArray(),
        ], 'it_leads');
    }

    public function saveSettings()
    {
        if ($r = $this->need('settings')) {
            return $r;
        }
        $email = (string) $this->request->getPost('crm_notification_email');
        $ok = (new CrmNotificationService())->saveRecipient($email);
        if ($ok) {
            $this->audit('CRM_SETTINGS_RECIPIENT', 0, ['crm_notification_email' => strtolower(trim($email))]);
        }
        session()->setFlashdata($ok ? 'success' : 'error', $ok ? 'Notification recipient saved.' : 'Please enter a valid email address.');
        return redirect()->to(base_url('admin/it-leads/settings'));
    }

    public function testNotification()
    {
        if ($r = $this->need('settings')) {
            return $r;
        }
        $id = (new CrmNotificationService())->sendTest();
        $this->audit('CRM_TEST_NOTIFICATION', 0, ['outbox_id' => $id]);
        session()->setFlashdata($id ? 'success' : 'error', $id ? "Test notification queued (outbox #{$id}). It is sent by the outbox worker; check status below." : 'Could not queue the test notification (see logs).');
        return redirect()->to(base_url('admin/it-leads/settings'));
    }
}
