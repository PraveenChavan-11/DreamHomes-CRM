<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Operational health checks for the admin System Health page.
 *
 * Principles:
 *  - Cheap, read-only checks against our own database/filesystem. NO outbound HTTP probes and NO command
 *    execution, so the page cannot be turned into an SSRF or shell-execution surface.
 *  - A status is "healthy" only with positive evidence (e.g. a recorded successful run). Having a
 *    configuration value never produces "healthy"; absence of evidence is "not_verified".
 *  - Nothing returned here contains secrets, env values, or stack traces.
 */
class SystemHealthService
{
    public const HEALTHY             = 'healthy';
    public const WARNING             = 'warning';
    public const FAILED              = 'failed';
    public const NOT_CONFIGURED      = 'not_configured';
    public const PERMISSION_REQUIRED = 'permission_required';
    public const NOT_VERIFIED        = 'not_verified';

    public const LABELS = [
        self::HEALTHY             => 'Healthy',
        self::WARNING             => 'Warning',
        self::FAILED              => 'Failed',
        self::NOT_CONFIGURED      => 'Not Configured',
        self::PERMISSION_REQUIRED => 'Permission Required',
        self::NOT_VERIFIED        => 'Not Verified',
    ];

    /**
     * Cron jobs we expect to see heartbeats from. `required` jobs affect the overall status; optional ones
     * are shown for information only. `interval` is the expected gap in minutes between runs.
     */
    public const SCHEDULED_JOBS = [
        'social:publish'  => ['label' => 'Social publishing queue',        'interval' => 5,  'required' => true],
        'engagement:run'  => ['label' => 'Engagement, alerts & email outbox', 'interval' => 15, 'required' => true],
        'outbox:process'  => ['label' => 'Email outbox (standalone)',      'interval' => 5,  'required' => false],
        'mail:sync'       => ['label' => 'Hostinger mailbox sync',         'interval' => 60, 'required' => false],
    ];

    public const STUCK_JOB_MINUTES = 15;
    private const SEVERITY = [self::FAILED => 4, self::WARNING => 3, self::NOT_VERIFIED => 2, self::HEALTHY => 1];

    private BaseConnection $db;
    private int $now;

    public function __construct(?BaseConnection $db = null, ?int $now = null)
    {
        $this->db  = $db ?? \Config\Database::connect();
        $this->now = $now ?? time();
    }

    /** @return array{overall:string,generated_at:string,checks:array<string,array>} */
    public function run(): array
    {
        $checks = [
            'application'  => $this->checkApplication(),
            'database'     => $this->checkDatabase(),
            'storage'      => $this->checkStorage(),
            'scheduler'    => $this->checkScheduler(),
            'jobs'         => $this->checkBackgroundJobs(),
            'email'        => $this->checkEmail(),
            'social'       => $this->checkSocial(),
            'integrations' => $this->checkIntegrations(),
            'admin_access' => $this->checkAdminAccess(),
        ];

        // A dead database makes every DB-derived check meaningless; say so instead of showing stale data.
        $overall = self::HEALTHY;
        foreach ($checks as $c) {
            if (($c['affects_overall'] ?? true) && (self::SEVERITY[$c['status']] ?? 0) > (self::SEVERITY[$overall] ?? 0)) {
                $overall = $c['status'];
            }
        }
        return ['overall' => $overall, 'generated_at' => date('Y-m-d H:i:s', $this->now), 'checks' => $checks];
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? 'Unknown';
    }

    private function check(string $status, string $summary, array $details = [], bool $affects = true): array
    {
        return ['status' => $status, 'summary' => $summary, 'details' => $details, 'affects_overall' => $affects];
    }

    // ── Application ──────────────────────────────────────────────────────────
    public function checkApplication(): array
    {
        return $this->check(self::HEALTHY, 'Application is serving this request.', [
            'Environment'       => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown',
            'PHP version'       => PHP_VERSION,
            'CodeIgniter'       => \CodeIgniter\CodeIgniter::CI_VERSION,
            'Server time'       => date('Y-m-d H:i:s T', $this->now),
        ]);
    }

    // ── Database ─────────────────────────────────────────────────────────────
    public function checkDatabase(): array
    {
        $t0 = microtime(true);
        try {
            $ok = $this->db->query('SELECT 1 AS ok')->getRowArray();
            $ms = (int) round((microtime(true) - $t0) * 1000);
            if (($ok['ok'] ?? null) != 1) {
                return $this->check(self::FAILED, 'Database answered but returned an unexpected result.');
            }
            $status = $ms > 500 ? self::WARNING : self::HEALTHY;
            return $this->check($status, $status === self::HEALTHY ? 'Database query succeeded.' : 'Database is responding slowly.', [
                'Response time' => $ms . ' ms',
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Health: database check failed: ' . get_class($e));
            return $this->check(self::FAILED, 'Database connection or query failed. Check the error log on the server.');
        }
    }

    // ── Storage ──────────────────────────────────────────────────────────────
    public function checkStorage(): array
    {
        $dirs = [
            'writable'         => WRITEPATH,
            'writable/cache'   => WRITEPATH . 'cache',
            'writable/logs'    => WRITEPATH . 'logs',
            'writable/session' => WRITEPATH . 'session',
            'public/uploads'   => FCPATH . 'uploads',
        ];
        $unwritable = [];
        foreach ($dirs as $label => $path) {
            if (!is_dir($path) || !is_writable($path)) {
                $unwritable[] = $label;
            }
        }
        $free  = @disk_free_space(WRITEPATH);
        $total = @disk_total_space(WRITEPATH);
        $details = ['Writable directories' => $unwritable ? 'Not writable: ' . implode(', ', $unwritable) : 'All writable'];

        $status = self::HEALTHY;
        $summary = 'Storage is writable.';
        if ($free === false || $total === false || $total <= 0) {
            $details['Disk space'] = 'Not available (restricted by hosting)';
            $status  = self::NOT_VERIFIED;
            $summary = 'Disk usage cannot be read on this hosting; writable check only.';
        } else {
            $usedPct = round((1 - $free / $total) * 100, 1);
            $details['Disk used']  = $usedPct . '%';
            $details['Free']       = self::bytes((float) $free);
            $details['Total']      = self::bytes((float) $total);
            if ($usedPct >= 95) {
                $status = self::FAILED; $summary = 'Disk is almost full.';
            } elseif ($usedPct >= 85) {
                $status = self::WARNING; $summary = 'Disk usage is high.';
            }
        }
        if ($unwritable) {
            $status  = self::FAILED;
            $summary = 'Some required directories are not writable.';
        }
        return $this->check($status, $summary, $details);
    }

    // ── Scheduler / cron (heartbeat-based) ───────────────────────────────────
    /** @return array<string,array> per-job status keyed by job name */
    public function schedulerJobs(): array
    {
        $out = [];
        $tableOk = $this->tableExists('scheduled_job_runs');
        foreach (self::SCHEDULED_JOBS as $name => $meta) {
            $out[$name] = $this->buildJobRow($name, $meta, $tableOk);
        }
        return $out;
    }

    /** Status of one scheduled job (cheap: used by the always-visible header indicator). */
    public function schedulerJob(string $name): array
    {
        return $this->buildJobRow($name, self::SCHEDULED_JOBS[$name], $this->tableExists('scheduled_job_runs'));
    }

    private function buildJobRow(string $name, array $meta, bool $tableOk): array
    {
        $row = ['name' => $name] + $meta + [
            'last_started' => null, 'last_success' => null, 'last_status' => null, 'last_error' => null,
            'failures_24h' => 0, 'next_expected' => null, 'status' => self::NOT_VERIFIED,
            'summary' => 'No heartbeat has ever been recorded. The cron entry may be missing, or has not run since heartbeats were introduced.',
        ];
        if (!$tableOk) {
            $row['status']  = self::FAILED;
            $row['summary'] = 'Heartbeat table is missing. Run the database migrations.';
            return $row;
        }
        $last = $this->db->table('scheduled_job_runs')->where('job_name', $name)->orderBy('started_at', 'DESC')->limit(1)->get()->getRowArray();
        if ($last) {
            $okRun = $this->db->table('scheduled_job_runs')->selectMax('completed_at', 'c')->where('job_name', $name)->where('status', 'SUCCESS')->get()->getRowArray();
            $row['last_started'] = $last['started_at'];
            $row['last_status']  = $last['status'];
            $row['last_error']   = $last['error_details'] ? JobRunRecorder::sanitize($last['error_details']) : null;
            $row['last_success'] = $okRun['c'] ?? null;
            $row['failures_24h'] = $this->db->table('scheduled_job_runs')->where('job_name', $name)->where('status', 'FAILED')
                ->where('started_at >=', date('Y-m-d H:i:s', $this->now - 86400))->countAllResults();
            $row['next_expected'] = date('Y-m-d H:i:s', strtotime($last['started_at']) + $meta['interval'] * 60);

            $ageMin = ($this->now - strtotime($last['started_at'])) / 60;
            [$row['status'], $row['summary']] = $this->judgeHeartbeat($last['status'], $ageMin, $meta['interval'], (bool) $row['last_success']);
        }
        return $row;
    }

    private function judgeHeartbeat(string $lastStatus, float $ageMin, int $interval, bool $hasEverSucceeded): array
    {
        if ($lastStatus === 'RUNNING' && $ageMin > max(30, $interval * 3)) {
            return [self::FAILED, 'Last run started ' . round($ageMin) . ' minutes ago and never finished (crashed or was killed).'];
        }
        if ($ageMin > $interval * 6) {
            return [self::FAILED, 'No run for ' . self::duration($ageMin) . ' — expected about every ' . $interval . ' min. The cron job appears to have stopped.'];
        }
        if ($ageMin > $interval * 2.5) {
            return [self::WARNING, 'Last run was ' . self::duration($ageMin) . ' ago — one or more scheduled runs were missed (expected every ' . $interval . ' min).'];
        }
        if ($lastStatus === 'FAILED') {
            return [self::WARNING, 'The most recent run failed.' . ($hasEverSucceeded ? '' : ' It has never succeeded.')];
        }
        return [self::HEALTHY, 'Ran ' . self::duration($ageMin) . ' ago and completed successfully.'];
    }

    public function checkScheduler(): array
    {
        $jobs = $this->schedulerJobs();
        $worst = self::HEALTHY;
        $details = [];
        foreach ($jobs as $name => $j) {
            if ($j['required'] && (self::SEVERITY[$j['status']] ?? 0) > (self::SEVERITY[$worst] ?? 0)) {
                $worst = $j['status'];
            }
            $details[$name] = self::label($j['status']) . ' — ' . $j['summary'];
        }
        $summary = match ($worst) {
            self::HEALTHY      => 'All required scheduled jobs are reporting heartbeats on time.',
            self::NOT_VERIFIED => 'No heartbeat recorded for a required job. Cron has not been verified.',
            self::WARNING      => 'A required scheduled job missed runs or failed.',
            default            => 'A required scheduled job has stopped running.',
        };
        return $this->check($worst, $summary, $details);
    }

    // ── Background jobs ──────────────────────────────────────────────────────
    public function jobCounts(): array
    {
        $counts = ['pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0, 'stuck' => 0, 'last_success' => null];
        if ($this->tableExists('social_scheduled_jobs')) {
            foreach ($this->db->query('SELECT status, COUNT(*) c FROM social_scheduled_jobs GROUP BY status')->getResultArray() as $r) {
                $k = ['queued' => 'pending', 'processing' => 'processing', 'completed' => 'completed', 'failed' => 'failed'][$r['status']] ?? null;
                if ($k) { $counts[$k] += (int) $r['c']; }
            }
            $counts['stuck'] += $this->db->table('social_scheduled_jobs')->where('status', 'processing')
                ->where('locked_at <', date('Y-m-d H:i:s', $this->now - self::STUCK_JOB_MINUTES * 60))->countAllResults();
            $ls = $this->db->table('social_scheduled_jobs')->selectMax('updated_at', 'u')->where('status', 'completed')->get()->getRowArray();
            $counts['last_success'] = $ls['u'] ?? null;
        }
        if ($this->tableExists('email_outbox')) {
            foreach ($this->db->query('SELECT status, COUNT(*) c FROM email_outbox GROUP BY status')->getResultArray() as $r) {
                $k = ['PENDING' => 'pending', 'SENT' => 'completed', 'FAILED' => 'failed'][$r['status']] ?? null;
                if ($k) { $counts[$k] += (int) $r['c']; }
            }
        }
        return $counts;
    }

    public function checkBackgroundJobs(): array
    {
        if (!$this->tableExists('social_scheduled_jobs') && !$this->tableExists('email_outbox')) {
            return $this->check(self::NOT_CONFIGURED, 'Job queue tables do not exist. Run the database migrations.');
        }
        $c = $this->jobCounts();
        $details = [
            'Pending' => $c['pending'], 'Processing' => $c['processing'], 'Completed' => $c['completed'],
            'Failed' => $c['failed'], 'Stuck (processing > ' . self::STUCK_JOB_MINUTES . ' min)' => $c['stuck'],
            'Last social job completed' => $c['last_success'] ?? 'never',
        ];
        if ($c['stuck'] > 0) {
            return $this->check(self::FAILED, $c['stuck'] . ' job(s) appear stuck. Their locks should be released by the scheduler; if not, the scheduler is not running.', $details);
        }
        if ($c['failed'] > 0) {
            return $this->check(self::WARNING, $c['failed'] . ' failed job(s) need attention.', $details);
        }
        return $this->check(self::HEALTHY, 'No failed or stuck jobs.', $details);
    }

    // ── Email ────────────────────────────────────────────────────────────────
    public function checkEmail(): array
    {
        $hasPass = (string) (getenv('SMTP_PASS') ?: '') !== '' || $this->settingPresent('smtp_pass');
        if (!$hasPass) {
            return $this->check(self::NOT_CONFIGURED, 'SMTP credentials are not set; outgoing email cannot be delivered.');
        }
        if (!$this->tableExists('email_outbox')) {
            return $this->check(self::NOT_VERIFIED, 'Email outbox table is missing.');
        }
        $failed   = (int) $this->db->table('email_outbox')->where('status', 'FAILED')->countAllResults();
        $pending  = (int) $this->db->table('email_outbox')->where('status', 'PENDING')->countAllResults();
        $oldest   = $this->db->table('email_outbox')->selectMin('created_at', 'm')->where('status', 'PENDING')->get()->getRowArray()['m'] ?? null;
        $lastReal = $this->db->query("SELECT MAX(sent_at) AS t FROM email_outbox WHERE status='SENT' AND (last_error IS NULL OR last_error NOT LIKE '[SANDBOXED]%')")->getRowArray()['t'] ?? null;
        $details = ['Pending' => $pending, 'Failed (all time)' => $failed, 'Last real delivery' => $lastReal ?? 'none recorded'];
        $sandbox = EnvironmentSafetyService::isEmailSandboxed();
        if ($sandbox) {
            $details['Mode'] = 'Sandbox — external delivery is blocked on this environment';
            return $this->check(self::NOT_VERIFIED, 'Email is sandboxed here; real delivery is not exercised.', $details);
        }
        $ageMin = $oldest ? ($this->now - strtotime($oldest)) / 60 : 0;
        if ($failed > 0 && !$lastReal) {
            return $this->check(self::FAILED, 'Emails are failing and none have ever been delivered.', $details);
        }
        if ($failed > 0 || $ageMin > 30) {
            return $this->check(self::WARNING, $failed > 0 ? $failed . ' email(s) failed to send.' : 'Oldest queued email has waited ' . self::duration($ageMin) . '.', $details);
        }
        if (!$lastReal) {
            return $this->check(self::NOT_VERIFIED, 'SMTP is configured but no email has been delivered yet.', $details);
        }
        return $this->check(self::HEALTHY, 'Recent emails delivered; no failures queued.', $details);
    }

    // ── Social ───────────────────────────────────────────────────────────────
    public function socialAccounts(): array
    {
        if (!$this->tableExists('social_accounts')) {
            return [];
        }
        $rows = $this->db->query("SELECT a.id, a.platform, a.account_name, a.status, a.last_verified_at, a.last_synced_at, a.last_error,
                    c.token_expires_at, c.permissions_json,
                    (c.access_token_encrypted IS NOT NULL AND c.access_token_encrypted <> '') AS has_token
                FROM social_accounts a LEFT JOIN social_credentials c ON c.account_id = a.id ORDER BY a.platform, a.id")->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $status = self::HEALTHY; $why = 'Token stored and not expired; last verified ' . ($r['last_verified_at'] ?? 'never') . '. (Checked from stored data, not a live call.)';
            $exp = $r['token_expires_at'] ? strtotime($r['token_expires_at']) : null;
            if ($r['status'] !== 'active') {
                // social_accounts.status enum: active | disconnected | expired | error
                [$status, $why] = match ($r['status']) {
                    'expired'      => [self::PERMISSION_REQUIRED, 'Authorisation expired — an administrator must reconnect this account.'],
                    'disconnected' => [self::NOT_CONFIGURED, 'Account is disconnected.'],
                    default        => [self::FAILED, 'Account reported an error' . (!empty($r['last_error']) ? ': ' . JobRunRecorder::sanitize($r['last_error'], 160) : '.')],
                };
            } elseif (!(int) $r['has_token']) {
                $status = self::NOT_CONFIGURED; $why = 'No access token stored. Connect the account.';
            } elseif ($exp !== null && $exp < $this->now) {
                $status = self::FAILED; $why = 'Access token expired on ' . $r['token_expires_at'] . '.';
            } elseif ($exp !== null && $exp < $this->now + 7 * 86400) {
                $status = self::WARNING; $why = 'Access token expires ' . $r['token_expires_at'] . '. Reconnect soon.';
            } elseif (!empty($r['last_error'])) {
                $status = self::WARNING; $why = 'Last error: ' . JobRunRecorder::sanitize($r['last_error'], 160);
            } elseif (!$r['last_verified_at']) {
                $status = self::NOT_VERIFIED; $why = 'Token present but the connection has never been verified with the platform.';
            }
            $out[] = ['platform' => $r['platform'], 'name' => $r['account_name'], 'status' => $status, 'summary' => $why,
                      'token_expires_at' => $r['token_expires_at'], 'last_verified_at' => $r['last_verified_at'], 'last_synced_at' => $r['last_synced_at']];
        }
        return $out;
    }

    public function checkSocial(): array
    {
        $accts = $this->socialAccounts();
        if (!$accts) {
            return $this->check(self::NOT_CONFIGURED, 'No LinkedIn or Instagram account is connected.');
        }
        $worst = self::HEALTHY; $details = [];
        foreach ($accts as $a) {
            $details[ucfirst($a['platform']) . ' — ' . $a['name']] = self::label($a['status']) . ': ' . $a['summary'];
            $sev = ['permission_required' => 3, 'not_configured' => 2][$a['status']] ?? (self::SEVERITY[$a['status']] ?? 0);
            $cur = self::SEVERITY[$worst] ?? 0;
            if ($sev > $cur) {
                $worst = in_array($a['status'], [self::PERMISSION_REQUIRED, self::NOT_CONFIGURED], true) ? self::WARNING : $a['status'];
            }
        }
        $summary = $worst === self::HEALTHY ? 'All connected accounts have unexpired tokens and a recorded verification.' : 'One or more social accounts need attention.';
        return $this->check($worst, $summary, $details);
    }

    // ── Integrations (inbound webhook clients; no outbound probing) ──────────
    public function checkIntegrations(): array
    {
        if (!$this->tableExists('crm_integration_clients')) {
            return $this->check(self::NOT_CONFIGURED, 'Integration tables do not exist.', [], false);
        }
        $active = (int) $this->db->table('crm_integration_clients')->where('is_active', 1)->countAllResults();
        if ($active === 0) {
            return $this->check(self::NOT_CONFIGURED, 'No active integration client is registered.', [], false);
        }
        $since = date('Y-m-d H:i:s', $this->now - 86400);
        $fails = (int) $this->db->table('crm_integration_events')->where('result', 'failed')->where('received_at >=', $since)->countAllResults();
        $auth  = (int) $this->db->table('crm_integration_events')->where('event_type', 'auth')->where('received_at >=', $since)->countAllResults();
        $last  = $this->db->query("SELECT MAX(received_at) AS t FROM crm_integration_events WHERE result IN ('accepted','duplicate')")->getRowArray()['t'] ?? null;
        $lastFail = $this->db->query("SELECT MAX(received_at) AS t FROM crm_integration_events WHERE result = 'failed'")->getRowArray()['t'] ?? null;
        $details = ['Active clients' => $active, 'Last accepted event' => $last ?? 'none', 'Last failure' => $lastFail ?? 'none',
                    'Failures (24h)' => $fails, 'Rejected auth attempts (24h)' => $auth,
                    'Note' => 'Inbound only. TalentHub does not probe external services from this page.'];
        if (!$last) {
            return $this->check(self::NOT_VERIFIED, 'A client is registered but no event has ever been received.', $details, false);
        }
        if ($fails > 0 || $auth > 0) {
            return $this->check(self::WARNING, 'Recent failures or rejected authentications in the last 24 hours.', $details, false);
        }
        return $this->check(self::HEALTHY, 'Events received and accepted; no recent failures.', $details, false);
    }

    // ── Admin access (lockout safety net) ────────────────────────────────────
    /**
     * Super Admin is decided only by an assigned role. If nobody holds it, nobody can manage the platform; and an account that
     * used to rely on the removed "user id 1 / username admin" shortcut now has only the roles it was actually given.
     */
    public function checkAdminAccess(): array
    {
        try {
            if (!$this->tableExists('admin_users') || !$this->tableExists('user_roles') || !$this->tableExists('roles')) {
                return $this->check(self::NOT_VERIFIED, 'Role tables are missing.');
            }
            $supers = (int) $this->db->query("SELECT COUNT(DISTINCT u.id) c FROM admin_users u JOIN user_roles ur ON ur.user_id = u.id
                JOIN roles r ON r.id = ur.role_id WHERE r.slug = 'super_admin' AND u.is_active = 1")->getRowArray()['c'];
            $legacy = $this->db->query("SELECT u.id, u.username FROM admin_users u WHERE u.is_active = 1 AND (u.id = 1 OR u.username = 'admin')
                AND NOT EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.slug = 'super_admin')")->getResultArray();
            $details = ['Active Super Admins' => $supers];
            if ($supers === 0) {
                return $this->check(self::FAILED, 'No active administrator holds the Super Admin role — nobody can manage roles, settings or integrations. Assign the role directly in the database now.', $details);
            }
            if ($legacy) {
                $details['Accounts without the role'] = implode(', ', array_map(static fn ($u) => '#' . $u['id'] . ' ' . $u['username'], $legacy));
                return $this->check(self::WARNING, 'Account "' . $legacy[0]['username'] . '" has no Super Admin role. It used to be treated as Super Admin by user id/name; it now has only its assigned roles.', $details);
            }
            if ($supers === 1) {
                $details['Note'] = 'Only one Super Admin: keep a second one as a recovery account.';
                return $this->check(self::HEALTHY, 'One active Super Admin.', $details);
            }
            return $this->check(self::HEALTHY, $supers . ' active Super Admins.', $details);
        } catch (\Throwable) {
            return $this->check(self::NOT_VERIFIED, 'Admin roles could not be read.');
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────
    private function tableExists(string $t): bool
    {
        try {
            return $this->db->tableExists($t);
        } catch (\Throwable) {
            return false;
        }
    }

    private function settingPresent(string $key): bool
    {
        try {
            if (!$this->tableExists('website_settings')) {
                return false;
            }
            $r = $this->db->table('website_settings')->select('setting_value')->where('setting_key', $key)->get()->getRowArray();
            return $r && trim((string) $r['setting_value']) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function bytes(float $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $u) {
            if ($b < 1024 || $u === 'TB') {
                return round($b, $u === 'B' ? 0 : 1) . ' ' . $u;
            }
            $b /= 1024;
        }
        return '';
    }

    public static function duration(float $minutes): string
    {
        if ($minutes < 1) { return 'under a minute'; }
        if ($minutes < 90) { return round($minutes) . ' min'; }
        if ($minutes < 2880) { return round($minutes / 60, 1) . ' h'; }
        return round($minutes / 1440, 1) . ' days';
    }
}
