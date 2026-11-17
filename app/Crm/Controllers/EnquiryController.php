<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\ActivityService;
use Crm\Services\AssignmentService;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CustomerService;
use Crm\Services\EnquiryService;
use Crm\Services\HistoryService;
use Crm\Services\OrderService;
use Crm\Services\RequirementService;
use Crm\Services\WorkflowService;

/** Enquiry list / pipeline board / creation / workspace. Thin: every mutation is a service call; every read is scope-checked. */
class EnquiryController extends CrmController
{
    private const STATUSES = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation', 'won', 'lost', 'on_hold'];
    private const BOARD    = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation', 'won', 'lost', 'on_hold'];
    private const PER      = 25;

    // ───────────── helpers ─────────────

    /** @return array<string,mixed> enquiry row (joined); 404 when missing or outside the user's visibility */
    private function enquiry(int $id): array
    {
        $row = Crm::db()->table('crm_enquiries e')
            ->select("e.*, COALESCE(org.name, c.company_name, c.name) AS organisation_name, org.org_no,
                      COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) AS contact_person_name,
                      ct.designation AS contact_person_designation, ct.mobile_number AS contact_person_mobile, ct.email AS contact_person_email,
                      c.name AS customer_name, c.customer_no, c.mobile AS customer_mobile, c.email AS customer_email, c.city AS customer_city, CONCAT(o.first_name,' ',o.last_name) AS owner_name,
                      t.name AS team_name, pc.name AS category_name, ls.name AS source_name, lr.name AS lost_reason, CONCAT(cb.first_name,' ',cb.last_name) AS closed_by_name")
            ->join('crm_customers c', 'c.id = e.customer_id', 'left')
            ->join('crm_organisations org', 'org.id = e.organisation_id', 'left')
            ->join('crm_contacts ct', 'ct.id = e.contact_id', 'left')
            ->join('admin_users o', 'o.id = e.current_owner_id', 'left')
            ->join('crm_teams t', 't.id = e.team_id', 'left')
            ->join('crm_product_categories pc', 'pc.id = e.product_category_id', 'left')
            ->join('crm_lead_sources ls', 'ls.id = e.source_id', 'left')
            ->join('crm_lost_reasons lr', 'lr.id = e.lost_reason_id', 'left')
            ->join('admin_users cb', 'cb.id = e.closed_by', 'left')
            ->where('e.id', $id)->get()->getRowArray();
        if (! $row || ! AccessScope::canSeeEnquiry($this->user(), $id)) {
            throw new PageNotFoundException('Enquiry not found');
        }

        return $row;
    }

    /** @return list<array<string,mixed>> active sales users (executives + managers) for assignment pickers */
    private function salesUsers(): array
    {
        return Crm::db()->query("SELECT DISTINCT u.id, CONCAT(u.first_name,' ',u.last_name) AS name, r.slug AS role FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id JOIN roles r ON r.id = ur.role_id
            WHERE u.is_active = 1 AND r.slug IN ('sales_executive','sales_manager') ORDER BY name")->getResultArray();
    }

    /** @return array<string,list<array<string,mixed>>> lookups used by filters and forms */
    private function lookups(): array
    {
        $db = Crm::db();

        return [
            'categories' => $db->table('crm_product_categories')->orderBy('sort_order')->orderBy('name')->get()->getResultArray(),
            'sources'    => $db->table('crm_lead_sources')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'teams'      => $db->table('crm_teams')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'users'      => $this->salesUsers(),
        ];
    }

    /** Apply list filters + visibility scope. */
    private function listQuery(array $f)
    {
        $b = Crm::db()->table('crm_enquiries e')
            ->select("e.id, e.enquiry_no, e.project_name, e.status, e.priority, e.lead_score, e.est_budget, e.created_at, e.outcome, e.customer_id, e.organisation_id, e.contact_id, e.current_owner_id, e.final_value,
                      COALESCE(org.name, c.company_name, c.name) AS organisation_name, org.org_no,
                      COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) AS contact_name, ct.designation AS contact_designation,
                      c.name AS customer_name, c.customer_no, CONCAT(o.first_name,' ',o.last_name) AS owner_name, t.name AS team_name, pc.name AS category_name")
            ->join('crm_customers c', 'c.id = e.customer_id', 'left')
            ->join('crm_organisations org', 'org.id = e.organisation_id', 'left')
            ->join('crm_contacts ct', 'ct.id = e.contact_id', 'left')
            ->join('admin_users o', 'o.id = e.current_owner_id', 'left')
            ->join('crm_teams t', 't.id = e.team_id', 'left')
            ->join('crm_product_categories pc', 'pc.id = e.product_category_id', 'left');
        AccessScope::scopeEnquiries($b, $this->user());
        if (($f['q'] ?? '') !== '') {
            $like = $f['q'];
            $b->groupStart()
                ->like('e.enquiry_no', $like)
                ->orLike('e.project_name', $like)
                ->orLike('org.name', $like)
                ->orLike('ct.first_name', $like)
                ->orLike('ct.last_name', $like)
                ->orLike('c.name', $like)
                ->orLike('c.mobile', $like)
                ->groupEnd();
        }
        if (in_array($f['status'] ?? '', self::STATUSES, true)) {
            $b->where('e.status', $f['status']);
        } elseif (($f['status'] ?? '') === 'active') {
            $b->whereIn('e.status', WorkflowService::ACTIVE);
        }
        foreach (['priority' => 'e.priority', 'owner_id' => 'e.current_owner_id', 'team_id' => 'e.team_id', 'source_id' => 'e.source_id', 'category_id' => 'e.product_category_id', 'outcome' => 'e.outcome'] as $k => $col) {
            if (($f[$k] ?? '') !== '') {
                $b->where($col, $f[$k]);
            }
        }
        if (($f['unassigned'] ?? '') === '1') {
            $b->where('e.current_owner_id IS NULL', null, false);
        }
        if (! empty($f['from']) && strtotime($f['from'])) {
            $b->where('e.created_at >=', date('Y-m-d', strtotime($f['from'])) . ' 00:00:00');
        }
        if (! empty($f['to']) && strtotime($f['to'])) {
            $b->where('e.created_at <=', date('Y-m-d', strtotime($f['to'])) . ' 23:59:59');
        }

        return $b;
    }

    private function filters(): array
    {
        $keys = ['q', 'status', 'priority', 'owner_id', 'team_id', 'source_id', 'category_id', 'outcome', 'from', 'to', 'unassigned'];
        $f    = [];
        foreach ($keys as $k) {
            $v = $this->request->getGet($k);
            if (is_string($v) && trim($v) !== '') {
                $f[$k] = trim($v);
            }
        }

        return $f;
    }

    // ───────────── list / board ─────────────

    public function index(): string
    {
        $f      = $this->filters();
        $kanban = $this->request->getGet('view') === 'kanban';
        $data   = ['f' => $f, 'kanban' => $kanban, 'statuses' => self::STATUSES] + $this->lookups();

        if ($kanban) {
            $cols = [];
            foreach (self::BOARD as $st) {
                $q = $f;
                $q['status'] = $st;
                $cnt = (clone $this->listQuery($q))->countAllResults(false);
                $rows = $this->listQuery($q)->orderBy('e.priority = \'urgent\'', 'DESC', false)->orderBy('e.created_at', 'DESC')->limit(30)->get()->getResultArray();
                $cols[$st] = ['rows' => $rows, 'count' => $cnt, 'next' => WorkflowService::allowedNext($st)];
            }
            $data['cols'] = $cols;

            return $this->render('enquiries/index', $data, 'Sales pipeline', 'pipeline');
        }

        $page  = $this->page();
        $total = $this->listQuery($f)->countAllResults();
        $data['rows']  = $this->listQuery($f)->orderBy('e.created_at', 'DESC')->orderBy('e.id', 'DESC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();
        $data['total'] = $total;
        $data['pager'] = view('Crm\Views\enquiries\_pager', ['total' => $total, 'per' => self::PER, 'page' => $page, 'query' => $f]);

        return $this->render('enquiries/index', $data, 'Enquiries', 'enquiries');
    }

    // ───────────── create ─────────────

    public function new(): string
    {
        $db = Crm::db();
        $orgId = (int) $this->request->getGet('organisation_id');
        $cid = (int) $this->request->getGet('customer_id');
        $sel = null;
        if ($orgId) {
            $selOrg = $db->table('crm_organisations')->where('id', $orgId)->get()->getRowArray();
            if ($selOrg) {
                $sel = ['id' => $selOrg['id'], 'org_no' => $selOrg['org_no'], 'name' => $selOrg['name'], 'city' => $selOrg['city'], 'mobile' => $selOrg['primary_phone']];
            }
        } elseif ($cid && AccessScope::canSeeCustomer($this->user(), $cid)) {
            $sel = $db->table('crm_customers')->select('id, customer_no, name, mobile, city, organisation_id')->where('id', $cid)->get()->getRowArray();
            if ($sel && ! empty($sel['organisation_id'])) {
                $orgId = (int) $sel['organisation_id'];
            }
        }

        $organisations = $db->table('crm_organisations')
            ->select('id, org_no, name, type, city, primary_phone')
            ->where('account_status', 'active')
            ->orderBy('name')
            ->limit(500)
            ->get()->getResultArray();

        $contacts = $db->table('crm_contacts')
            ->select('id, organisation_id, first_name, last_name, designation, mobile_number, email')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()->getResultArray();

        $builder = $db->table('crm_customers c')->select('c.id, c.customer_no, c.name, c.mobile, c.city, c.organisation_id')->where('c.status', 'active');
        AccessScope::scopeCustomers($builder, $this->user());

        return $this->render('enquiries/form', [
            'selected' => $sel,
            'selected_org_id' => $orgId,
            'organisations' => $organisations,
            'contacts' => $contacts,
            'customers' => $builder->orderBy('c.name')->limit(500)->get()->getResultArray(),
            'duplicates' => session()->getFlashdata('duplicates') ?? [],
        ] + $this->lookups(), 'New enquiry', 'enquiries');
    }

    public function create()
    {
        $p    = $this->request->getPost();
        $user = $this->user();
        $allow = ! empty($p['allow_duplicate']);
        $d = $this->pick($p, ['project_name', 'product_category_id', 'requirements', 'site_location', 'est_budget', 'expected_purchase_date', 'source_id', 'source_channel', 'priority', 'description', 'team_id']);
        foreach ($d as $k => $v) {
            if ($v === '') {
                unset($d[$k]);
            }
        }
        if (! empty($p['current_owner_id']) && AccessScope::can($user, 'enquiries.assign')) {
            $d['current_owner_id'] = (int) $p['current_owner_id'];
        }
        $channels = array_keys(\Crm\Services\ReferenceData::LEAD_SOURCES);
        if (isset($d['source_channel']) && ! in_array($d['source_channel'], $channels, true)) {
            unset($d['source_channel']);
        }

        $msg = null;
        $res = $this->act(function () use ($p, $d, $user, $allow, &$msg) {
            return Crm::tx(function () use ($p, $d, $user, $allow, &$msg) {
                $db = Crm::db();
                $orgId = (int) ($p['organisation_id'] ?? 0);
                $contactId = (int) ($p['contact_id'] ?? 0);
                $cid = (int) ($p['customer_id'] ?? 0);

                if (! empty($p['org_mode']) && $p['org_mode'] === 'new') {
                    // Create New Organisation & Contact
                    $orgName = trim((string) ($p['org_name'] ?? ''));
                    if ($orgName === '') {
                        throw new CrmException('Organisation name is required.');
                    }
                    $orgNo = \Crm\Services\NumberSequenceService::next('organisation');
                    $db->table('crm_organisations')->insert([
                        'org_no' => $orgNo,
                        'name' => $orgName,
                        'type' => $p['org_type'] ?? 'Corporate',
                        'city' => $p['org_city'] ?? 'Pune',
                        'primary_phone' => $p['contact_mobile'] ?? $p['org_phone'] ?? null,
                        'primary_email' => $p['contact_email'] ?? $p['org_email'] ?? null,
                        'account_status' => 'active',
                        'created_by' => $user['id'],
                        'created_at' => Crm::now(),
                        'updated_at' => Crm::now(),
                    ]);
                    $orgId = (int) $db->insertID();

                    $contactName = trim((string) ($p['contact_name'] ?? $orgName));
                    $parts = explode(' ', $contactName, 2);
                    $contactNo = \Crm\Services\NumberSequenceService::next('contact');
                    $db->table('crm_contacts')->insert([
                        'contact_no' => $contactNo,
                        'organisation_id' => $orgId,
                        'first_name' => $parts[0],
                        'last_name' => $parts[1] ?? '',
                        'designation' => $p['contact_designation'] ?? 'Primary Contact',
                        'mobile_number' => $p['contact_mobile'] ?? '9000000000',
                        'email' => $p['contact_email'] ?? null,
                        'is_primary' => 1,
                        'status' => 'active',
                        'created_by' => $user['id'],
                        'created_at' => Crm::now(),
                        'updated_at' => Crm::now(),
                    ]);
                    $contactId = (int) $db->insertID();
                }

                if ($orgId > 0) {
                    $org = $db->table('crm_organisations')->where('id', $orgId)->get()->getRowArray() ?? throw new CrmException('Organisation not found.');
                    $d['organisation_id'] = $orgId;
                    if ($contactId > 0) {
                        $d['contact_id'] = $contactId;
                    }
                    // Find or create anchor customer record for backward compatibility
                    $cust = $db->table('crm_customers')->where('organisation_id', $orgId)->get()->getRowArray();
                    if ($cust) {
                        $cid = (int) $cust['id'];
                    } else {
                        $cid = CustomerService::create([
                            'name' => $org['name'],
                            'company_name' => $org['name'],
                            'customer_type' => $org['type'] ?? 'company',
                            'organisation_id' => $orgId,
                            'primary_contact_id' => $contactId ?: null,
                            'mobile' => $org['primary_phone'] ?? '9000000000',
                            'email' => $org['primary_email'] ?? null,
                            'city' => $org['city'] ?? 'Pune',
                        ], (int) $user['id'], true, 1);
                    }
                } elseif ($cid) {
                    if (! AccessScope::canSeeCustomer($user, $cid)) {
                        throw new CrmException('You do not have access to that client.');
                    }
                    $cust = $db->table('crm_customers')->where('id', $cid)->get()->getRowArray();
                    if ($cust && ! empty($cust['organisation_id'])) {
                        $d['organisation_id'] = (int) $cust['organisation_id'];
                        $d['contact_id'] = (int) ($cust['primary_contact_id'] ?? 0);
                    }
                } else {
                    $c = $this->pick($p, ['name', 'mobile', 'email', 'city', 'customer_type', 'company_name']);
                    $c = array_filter($c, static fn ($v) => $v !== '');
                    $cid = CustomerService::create($c, (int) $user['id'], $allow);
                }

                $res = EnquiryService::create($d + ['customer_id' => $cid], $user, $allow);
                $owner = $res['owner_id'] ? (HistoryService::userNames()[$res['owner_id']] ?? 'a salesperson') : null;
                $msg = "Enquiry {$res['enquiry_no']} created. " . ($owner ? "Assigned to {$owner} ({$res['assignment']})." : 'No eligible salesperson was available — it is unassigned; managers were alerted.');

                return 'crm/enquiries/' . $res['id'];
            });
        }, 'Enquiry created.');

        return $msg ? $res->with('success', $msg) : $res;
    }

    // ───────────── workspace ─────────────

    public function show(int $id): string
    {
        $e    = $this->enquiry($id);
        $db   = Crm::db();
        $user = $this->user();
        $names = HistoryService::userNames();

        $events = array_map(static fn ($r) => HistoryService::present($r, $names),
            $db->table('crm_workflow_events')->where('enquiry_id', $id)->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->limit(300)->get()->getResultArray());
        $visits = $db->query("SELECT v.*, CONCAT(u.first_name,' ',u.last_name) AS user_name, (SELECT COUNT(*) FROM crm_site_measurements m WHERE m.visit_id = v.id) AS meas_versions
            FROM crm_site_visits v LEFT JOIN admin_users u ON u.id = v.assigned_to WHERE v.enquiry_id = ? ORDER BY v.scheduled_at DESC", [$id])->getResultArray();
        $visitIds = array_column($visits, 'id') ?: [0];
        $docs = $db->query('SELECT d.id, d.entity_type, d.entity_id, d.original_name, d.mime, d.size_bytes, d.created_at FROM crm_documents d
            WHERE (d.entity_type = \'enquiry\' AND d.entity_id = ?) OR (d.entity_type = \'site_visit\' AND d.entity_id IN (' . implode(',', array_map('intval', $visitIds)) . ')) ORDER BY d.created_at DESC', [$id])->getResultArray();
        $quotes = $db->query("SELECT q.*, v.version_no AS cur_version, v.grand_total, v.valid_until, (SELECT COUNT(*) FROM crm_quotation_versions x WHERE x.quotation_id = q.id) AS versions
            FROM crm_quotations q LEFT JOIN crm_quotation_versions v ON v.id = q.current_version_id WHERE q.enquiry_id = ? ORDER BY q.id DESC", [$id])->getResultArray();
        $negs = $db->query("SELECT n.*, q.quotation_no, CONCAT(u.first_name,' ',u.last_name) AS requester FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id
            LEFT JOIN admin_users u ON u.id = n.requested_by WHERE q.enquiry_id = ? ORDER BY n.id DESC", [$id])->getResultArray();
        $orders = $db->query('SELECT o.*, q.quotation_no FROM crm_sales_orders o JOIN crm_quotations q ON q.id = o.quotation_id WHERE o.enquiry_id = ? ORDER BY o.id DESC', [$id])->getResultArray();

        $next = array_values(array_diff(WorkflowService::allowedNext($e['status']), ['won', 'lost', 'on_hold']));
        $active = WorkflowService::isActive($e['status']);

        return $this->render('enquiries/show', [
            'e' => $e, 'active' => $active, 'next' => $next, 'canWon' => in_array('won', WorkflowService::allowedNext($e['status']), true),
            'requirements' => $db->table('crm_requirements')->where('enquiry_id', $id)->orderBy("status = 'dropped'", 'ASC', false)->orderBy('id')->get()->getResultArray(),
            'reqHistory' => RequirementService::history($id, $user), 'events' => $events, 'ownership' => HistoryService::ownership($id, $user),
            'statusHistory' => $db->query("SELECT h.*, CONCAT(u.first_name,' ',u.last_name) AS user_name FROM crm_enquiry_status_history h LEFT JOIN admin_users u ON u.id = h.user_id WHERE h.enquiry_id = ? ORDER BY h.id DESC", [$id])->getResultArray(),
            'followups' => $db->query("SELECT f.*, CONCAT(u.first_name,' ',u.last_name) AS user_name FROM crm_followups f LEFT JOIN admin_users u ON u.id = f.assigned_to WHERE f.enquiry_id = ? ORDER BY f.due_at DESC", [$id])->getResultArray(),
            'activities' => $db->query("SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS user_name FROM crm_activities a LEFT JOIN admin_users u ON u.id = a.user_id WHERE a.enquiry_id = ? ORDER BY a.occurred_at DESC, a.id DESC LIMIT 100", [$id])->getResultArray(),
            'visits' => $visits, 'documents' => $docs, 'quotes' => $quotes, 'negotiations' => $negs, 'orders' => $orders,
            'products' => $db->table('crm_products')->select('id, sku, name, unit, standard_price, pricing_model')->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
            'lostReasons' => $db->table('crm_lost_reasons')->where('is_active', 1)->orderBy('name')->get()->getResultArray(), 'salesUsers' => $this->salesUsers(),
            'tab' => in_array($this->request->getGet('tab'), ['overview', 'requirements', 'timeline', 'ownership', 'status', 'activity', 'visits', 'quotations', 'negotiations', 'orders', 'documents'], true) ? $this->request->getGet('tab') : 'overview',
            'openModal' => in_array($this->request->getGet('modal'), ['lost', 'hold', 'won'], true) ? $this->request->getGet('modal') : null,
        ], $e['enquiry_no'] . ' · ' . ($e['project_name'] ?: $e['customer_name']), 'enquiries');
    }

    // ───────────── actions ─────────────

    public function assign(int $id)
    {
        $e = $this->enquiry($id);
        $to = (int) $this->request->getPost('user_id');
        $reason = trim((string) $this->request->getPost('reason'));

        return $this->act(function () use ($e, $id, $to, $reason) {
            if ($e['current_owner_id']) {
                EnquiryService::reassign($id, $to, $this->user(), $reason);
            } else {
                EnquiryService::assign($id, $to, $this->user(), $reason ?: null);
            }
        }, 'Enquiry assignment saved.', "crm/enquiries/{$id}?tab=ownership");
    }

    public function status(int $id)
    {
        $this->enquiry($id);
        $to = (string) $this->request->getPost('to');
        $reason = trim((string) $this->request->getPost('reason'));
        $map = ['lost' => 'lost', 'on_hold' => 'hold', 'won' => 'won'];
        if (isset($map[$to])) {
            return redirect()->to(site_url("crm/enquiries/{$id}?modal=" . $map[$to]))->with('info', 'Please complete the required details to ' . str_replace('_', ' ', $to === 'on_hold' ? 'put this enquiry on hold' : "mark it {$to}") . '.');
        }

        return $this->act(fn () => WorkflowService::transition($id, $to, $this->user(), $reason ?: null), 'Status updated.', "crm/enquiries/{$id}");
    }

    public function won(int $id)
    {
        $this->enquiry($id);

        return $this->act(fn () => OrderService::markWon($id, $this->user(), trim((string) $this->request->getPost('exception_reason')) ?: null), 'Enquiry marked WON.', "crm/enquiries/{$id}");
    }

    public function lost(int $id)
    {
        $this->enquiry($id);
        $p = $this->request;

        return $this->act(fn () => OrderService::markLost($id, $this->user(), (int) $p->getPost('lost_reason_id'), trim((string) $p->getPost('remarks')) ?: null, trim((string) $p->getPost('competitor')) ?: null), 'Enquiry marked LOST.', "crm/enquiries/{$id}");
    }

    public function hold(int $id)
    {
        $this->enquiry($id);
        $p = $this->request;

        return $this->act(fn () => OrderService::putOnHold($id, $this->user(), trim((string) $p->getPost('reason')), (string) $p->getPost('hold_review_date')), 'Enquiry put on hold.', "crm/enquiries/{$id}");
    }

    public function reopen(int $id)
    {
        $this->enquiry($id);

        return $this->act(fn () => OrderService::reopen($id, $this->user(), trim((string) $this->request->getPost('reason'))), 'Enquiry reopened.', "crm/enquiries/{$id}");
    }

    public function contact(int $id)
    {
        $this->enquiry($id);
        $p = $this->request;
        $at = $p->getPost('occurred_at');

        return $this->act(fn () => ActivityService::recordContact($id, $this->user(), (string) $p->getPost('type'), trim((string) $p->getPost('summary')), trim((string) $p->getPost('outcome')) ?: null,
            $at && strtotime((string) $at) ? date('Y-m-d H:i:s', strtotime((string) $at)) : null), 'Customer contact recorded.', "crm/enquiries/{$id}?tab=activity");
    }

    public function scheduleFollowup(int $id)
    {
        $this->enquiry($id);
        $p = $this->request;

        return $this->act(fn () => ActivityService::scheduleFollowup($id, $this->user(), (string) $p->getPost('due_at'), (string) ($p->getPost('type') ?: 'call'), trim((string) $p->getPost('remarks')) ?: null,
            (int) $p->getPost('assign_to') ?: null, trim((string) $p->getPost('next_action')) ?: null), 'Follow-up scheduled.', "crm/enquiries/{$id}?tab=activity");
    }

    public function scheduleVisit(int $id)
    {
        $this->enquiry($id);
        $p = $this->request;

        return $this->act(fn () => ActivityService::scheduleVisit($id, $this->user(), (string) $p->getPost('scheduled_at'), (int) $p->getPost('assign_to') ?: null,
            trim((string) $p->getPost('address')) ?: null, trim((string) $p->getPost('remarks')) ?: null), 'Site visit scheduled.', "crm/enquiries/{$id}?tab=visits");
    }

    private function reqInput(): array
    {
        $d = $this->pick($this->request->getPost(), ['product_id', 'description', 'variant', 'width', 'height', 'size_unit', 'nos', 'quantity', 'unit', 'expected_price', 'notes']);

        return $d;
    }

    public function addRequirement(int $id)
    {
        $this->enquiry($id);
        $fu = (int) $this->request->getPost('followup_id') ?: null;

        return $this->act(fn () => RequirementService::add($id, $this->user(), $this->reqInput(), trim((string) $this->request->getPost('reason')) ?: null, $fu), 'Requirement added.', "crm/enquiries/{$id}?tab=requirements");
    }

    private function reqEnquiryId(int $reqId): int
    {
        $row = Crm::db()->table('crm_requirements')->select('enquiry_id')->where('id', $reqId)->get()->getRowArray();
        if (! $row) {
            throw new PageNotFoundException('Requirement not found');
        }
        $this->enquiry((int) $row['enquiry_id']);   // scope check (404 when not visible)

        return (int) $row['enquiry_id'];
    }

    public function updateRequirement(int $reqId)
    {
        $eid = $this->reqEnquiryId($reqId);
        $fu  = (int) $this->request->getPost('followup_id') ?: null;

        return $this->act(fn () => RequirementService::update($reqId, $this->user(), $this->reqInput(), trim((string) $this->request->getPost('reason')) ?: null, $fu), 'Requirement updated (change recorded).', "crm/enquiries/{$eid}?tab=requirements");
    }

    public function dropRequirement(int $reqId)
    {
        $eid = $this->reqEnquiryId($reqId);

        return $this->act(fn () => RequirementService::drop($reqId, $this->user(), trim((string) $this->request->getPost('reason'))), 'Requirement dropped (kept in history).', "crm/enquiries/{$eid}?tab=requirements");
    }
}
