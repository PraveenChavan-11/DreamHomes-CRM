<?php

namespace Crm\Controllers;

use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\DemoService;
use Crm\Services\HistoryService;
use Crm\Services\IntegrityChecker;
use Crm\Services\ReferenceData;
use Crm\Services\RuleAdminService;
use Crm\Services\Settings;

/** Audit viewer, configuration, demo-data control and system health. Mutations go through Settings / RuleAdminService / DemoService (all audited). */
class AdminController extends CrmController
{
    private const PER = 25;

    /** key => [type, label, hint, extra]  (types: text|textarea|email|int|dec|bool|enum). Keys not listed here can never be changed from the UI. */
    private const SPEC = [
        'Company details (used on quotation PDFs)' => [
            'company.name' => ['text', 'Company name'], 'company.address' => ['textarea', 'Address'], 'company.phone' => ['text', 'Phone'], 'company.email' => ['email', 'Email'],
            'company.gstin' => ['gstin', 'GSTIN', 'Leave blank until DHIS supplies it; shown on PDFs only when set.'], 'company.state' => ['text', 'Home state', 'Decides CGST/SGST vs IGST split.'],
        ],
        'Quotation defaults' => [
            'quotation.validity_days' => ['int', 'Validity (days)', '', [1, 365]], 'tax.default_rate' => ['dec', 'Default GST %', '', [0, 100]], 'quotation.round_off' => ['bool', 'Round grand total to nearest rupee'],
            'quotation.terms' => ['textarea', 'Default terms & conditions'], 'quotation.payment_terms' => ['textarea', 'Default payment terms'],
        ],
        'Discount & approval policy' => [
            'discount.limit.executive_pct' => ['dec', 'Sales Executive limit %', 'Maximum discount applied without approval.', [0, 100]],
            'discount.limit.manager_pct' => ['dec', 'Sales Manager approval limit %', '', [0, 100]], 'discount.limit.admin_pct' => ['dec', 'Admin approval limit %', '', [0, 100]],
            'discount.allow_self_approval' => ['bool', 'Allow approvers to approve their own requests', 'Keep OFF unless DHIS policy explicitly permits.'],
        ],
        'Assignment & follow-ups' => [
            'assignment.default_mode' => ['enum', 'Default assignment mode', '', ['round_robin', 'workload', 'team', 'territory']],
            'followup.escalate_after_hours' => ['int', 'Escalate overdue follow-up after (hours)', '', [1, 720]], 'visit.reminder_hours_before' => ['int', 'Site-visit reminder lead (hours)', '', [1, 168]],
        ],
        'Security' => [
            'session.timeout_minutes' => ['int', 'Idle session timeout (minutes)', '', [5, 480]], 'auth.max_failed_logins' => ['int', 'Failed logins before lock', '', [3, 20]], 'auth.lock_minutes' => ['int', 'Lock duration (minutes)', '', [1, 1440]],
        ],
        'Email' => [
            'email.mode' => ['enum', 'Email mode', 'log_only records emails without contacting anyone. smtp transmits (never for demo records).', ['log_only', 'smtp']],
            'email.retry_max_attempts' => ['int', 'Maximum send attempts', '', [1, 10]],
        ],
    ];

    public function audit(): string
    {
        $db  = Crm::db();
        $src = $this->request->getGet('src') === 'workflow' ? 'workflow' : 'audit';
        $tbl = $src === 'workflow' ? 'crm_workflow_events' : 'crm_audit_events';
        $f   = [
            'src' => $src, 'entity' => (string) $this->request->getGet('entity'), 'event' => trim((string) $this->request->getGet('event')), 'user' => (int) $this->request->getGet('user'),
            'ref' => trim((string) $this->request->getGet('ref')), 'from' => (string) $this->request->getGet('from'), 'to' => (string) $this->request->getGet('to'),
        ];
        $b = $db->table("{$tbl} a");
        if ($f['entity'] !== '') {
            $b->where('a.entity_type', $f['entity']);
        }
        if ($f['event'] !== '') {
            $b->like('a.event_type', $f['event'], 'after');
        }
        if ($f['user']) {
            $b->where('a.user_id', $f['user']);
        }
        if ($f['ref'] !== '') {
            $b->where($src === 'workflow' ? 'a.entity_id' : 'a.entity_id', $f['ref']);
        }
        if ($f['from'] !== '' && strtotime($f['from'])) {
            $b->where('a.created_at >=', $f['from'] . ' 00:00:00');
        }
        if ($f['to'] !== '' && strtotime($f['to'])) {
            $b->where('a.created_at <=', $f['to'] . ' 23:59:59');
        }
        $total = (int) (clone $b)->countAllResults(false);
        $pages = max(1, (int) ceil($total / self::PER));
        $page  = min($this->page(), $pages);
        $rows  = $b->select('a.*')->orderBy('a.id', 'DESC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();
        $users = HistoryService::userNames();
        foreach ($rows as &$r) {
            $r['user_name'] = $r['user_id'] ? ($users[(int) $r['user_id']] ?? 'User #' . $r['user_id']) : ($r['source'] === 'cron' ? 'System (scheduler)' : 'System');
            foreach (['prev_state', 'new_state', 'metadata'] as $k) {
                $j        = $r[$k] ? json_decode($r[$k], true) : null;
                $r[$k . '_pretty'] = $j === null ? ($r[$k] ?: '') : json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        unset($r);

        return $this->render('admin/audit', [
            'rows' => $rows, 'f' => $f, 'total' => $total, 'page' => $page, 'pages' => $pages, 'users' => $users,
            'entities' => array_column($db->query("SELECT DISTINCT entity_type FROM {$tbl} ORDER BY entity_type")->getResultArray(), 'entity_type'),
        ], 'Audit Trail', 'audit');
    }

    public function settings(): string
    {
        $db   = Crm::db();
        $flag = array_column($db->table('crm_settings')->select('setting_key, is_default_assumption')->get()->getResultArray(), 'is_default_assumption', 'setting_key');
        $vals = [];
        foreach (self::SPEC as $fields) {
            foreach (array_keys($fields) as $k) {
                $vals[$k] = Settings::get($k);
            }
        }
        $mine = [];
        foreach ($db->table('crm_notification_prefs')->where('user_id', $this->uid())->get()->getResultArray() as $p) {
            $mine[$p['event_type']] = $p;
        }

        return $this->render('admin/settings', [
            'spec' => self::SPEC, 'vals' => $vals, 'assumed' => $flag,
            'arules' => $db->table('crm_assignment_rules r')->select('r.*, t.name AS team, c.name AS category')->join('crm_teams t', 't.id = r.team_id', 'left')->join('crm_product_categories c', 'c.id = r.category_id', 'left')->orderBy('r.priority')->get()->getResultArray(),
            'srules' => $db->table('crm_scoring_rules')->orderBy('id')->get()->getResultArray(),
            'events' => RuleAdminService::EVENTS, 'mine' => $mine,
            'mail' => [
                'from' => (string) (env('email.fromEmail') ?: '(falls back to company email)'), 'host' => (string) (env('email.SMTPHost') ?: '(not configured)'),
                'port' => (string) (env('email.SMTPPort') ?: '—'), 'password' => env('email.SMTPPass') ? 'set in .env (hidden)' : 'not set',
            ],
            'demoOn' => Settings::get('demo.enabled') === '1',
        ], 'Settings', 'settings');
    }

    public function saveSettings()
    {
        $post = $this->request->getPost();
        $user = $this->user();

        return $this->act(function () use ($post, $user) {
            $in = (array) ($post['s'] ?? []);
            $e  = [];
            $ok = [];
            foreach (self::SPEC as $fields) {
                foreach ($fields as $k => $spec) {
                    if (! array_key_exists($k, $in)) {
                        continue;
                    }
                    $v = is_string($in[$k]) ? trim($in[$k]) : '';
                    $f = "s[{$k}]";
                    switch ($spec[0]) {
                        case 'int':
                            if (! ctype_digit($v) || (int) $v < $spec[3][0] || (int) $v > $spec[3][1]) {
                                $e[$f] = "Enter a whole number between {$spec[3][0]} and {$spec[3][1]}.";
                            }
                            break;
                        case 'dec':
                            if (! is_numeric($v) || $v < $spec[3][0] || $v > $spec[3][1]) {
                                $e[$f] = "Enter a number between {$spec[3][0]} and {$spec[3][1]}.";
                            }
                            break;
                        case 'bool':
                            $v = $v === '1' ? '1' : '0';
                            break;
                        case 'enum':
                            if (! in_array($v, $spec[3], true)) {
                                $e[$f] = 'Choose a valid option.';
                            }
                            break;
                        case 'email':
                            if (! filter_var($v, FILTER_VALIDATE_EMAIL)) {
                                $e[$f] = 'Enter a valid email address.';
                            }
                            break;
                        case 'gstin':
                            $v = strtoupper($v);
                            if ($v !== '' && ! preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $v)) {
                                $e[$f] = 'GSTIN must be 15 characters in the standard format.';
                            }
                            break;
                        default:
                            if ($v === '' && in_array($k, ['company.name', 'company.state'], true)) {
                                $e[$f] = 'This field is required.';
                            }
                            if (mb_strlen($v) > 4000) {
                                $e[$f] = 'Too long.';
                            }
                    }
                    $ok[$k] = $v;
                }
            }
            if (isset($ok['discount.limit.executive_pct'], $ok['discount.limit.manager_pct'], $ok['discount.limit.admin_pct']) && ! $e
                && ((float) $ok['discount.limit.executive_pct'] > (float) $ok['discount.limit.manager_pct'] || (float) $ok['discount.limit.manager_pct'] > (float) $ok['discount.limit.admin_pct'])) {
                $e['s[discount.limit.manager_pct]'] = 'Limits must increase: executive ≤ manager ≤ admin.';
            }
            if ($e) {
                throw CrmException::validation($e);
            }
            $changed = Crm::tx(function () use ($ok, $post, $user) {
                $n = 0;
                foreach ($ok as $k => $v) {
                    if (Settings::get($k) !== $v) {
                        Settings::set($k, $v, (int) $user['id']);
                        $n++;
                    }
                }
                $n += RuleAdminService::saveAssignmentRules((array) ($post['arule'] ?? []), $user);
                $n += RuleAdminService::saveScoringRules((array) ($post['srule'] ?? []), $user);
                RuleAdminService::saveMyPrefs((int) $user['id'], (array) ($post['pref'] ?? []));

                return $n;
            });
            session()->setFlashdata('info', $changed ? "{$changed} configuration item(s) changed and audited." : 'No configuration values changed.');

            return 'crm/settings';
        }, 'Settings saved.');
    }

    public function demo(): string
    {
        $db  = Crm::db();
        $env = strtolower((string) (env('DHIS_DEMO_ENV') ?: getenv('DHIS_DEMO_ENV')));
        $log = $db->query("SELECT l.*, CONCAT(u.first_name,' ',u.last_name) by_name FROM crm_demo_reset_log l LEFT JOIN admin_users u ON u.id = l.initiated_by ORDER BY l.id DESC LIMIT 10")->getResultArray();

        return $this->render('admin/demo', [
            'envOn' => in_array($env, ['true', '1'], true), 'settingOn' => Settings::get('demo.enabled') === '1', 'phrase' => DemoService::CONFIRM_PHRASE,
            'registry' => $db->query('SELECT table_name, COUNT(*) n FROM crm_demo_registry GROUP BY table_name ORDER BY table_name')->getResultArray(), 'log' => $log,
            'iAmDemo' => (bool) $db->table('crm_demo_registry')->where(['table_name' => 'admin_users', 'row_id' => $this->uid()])->countAllResults(),
        ], 'Demo Data', 'demo');
    }

    public function demoReset()
    {
        $user    = $this->user();
        $confirm = trim((string) $this->request->getPost('confirm'));

        return $this->act(function () use ($user, $confirm) {
            $r = DemoService::reset($user, $confirm);
            session()->setFlashdata('info', sprintf('Snapshot saved (%s). Removed %d rows, created %d rows. Integrity problems: %d.', basename($r['snapshot']), $r['removed'], array_sum($r['created']), count($r['integrity'])));

            return 'crm/demo';
        }, 'Demo data was reset and rebuilt.');
    }

    public function health(): string
    {
        $db   = Crm::db();
        $dirs = [];
        foreach (['writable/' => WRITEPATH, 'writable/crm/' => WRITEPATH . 'crm/', 'public/uploads/crm-products/' => FCPATH . 'uploads/crm-products/', 'writable/logs/' => WRITEPATH . 'logs/'] as $l => $p) {
            $dirs[$l] = is_dir($p) ? (is_writable($p) ? 'writable' : 'NOT writable') : 'missing';
        }
        $ext = [];
        foreach (['mysqli', 'mbstring', 'intl', 'bcmath', 'fileinfo', 'gd', 'zip', 'curl'] as $x) {
            $ext[$x] = extension_loaded($x);
        }

        $latestLog = '';
        $logFiles = glob(WRITEPATH . 'logs/log-*.log');
        if ($logFiles) {
            rsort($logFiles);
            $content = (string) @file_get_contents($logFiles[0]);
            $latestLog = substr($content, -4000);
        }

        return $this->render('admin/health', [
            'problems' => IntegrityChecker::run(),
            'jobs' => $db->query('SELECT * FROM scheduled_job_runs ORDER BY id DESC LIMIT 15')->getResultArray(),
            'php' => PHP_VERSION, 'dbv' => $db->getVersion(), 'env' => ENVIRONMENT, 'https' => str_starts_with((string) base_url(), 'https://'), 'dirs' => $dirs, 'ext' => $ext,
            'emailMode' => Settings::get('email.mode'), 'demoEnv' => in_array(strtolower((string) env('DHIS_DEMO_ENV')), ['true', '1'], true),
            'counts' => [
                'Customers' => $db->table('crm_customers')->countAllResults(), 'Enquiries' => $db->table('crm_enquiries')->countAllResults(), 'Quotations' => $db->table('crm_quotations')->countAllResults(),
                'Orders' => $db->table('crm_sales_orders')->countAllResults(), 'Emails logged' => $db->table('crm_email_log')->countAllResults(), 'Audit events' => $db->table('crm_audit_events')->countAllResults(),
            ],
            'lastReminder' => $db->query("SELECT * FROM scheduled_job_runs WHERE job_name LIKE '%reminder%' ORDER BY id DESC LIMIT 1")->getRowArray(),
            'roles' => count(ReferenceData::ROLES),
            'latestLog' => $latestLog,
        ], 'System Health', 'settings');
    }
}
