<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Services\Agent\AgentApprovalService;
use App\Services\Agent\AgentAuditLogger;
use App\Services\Agent\AgentDraftValidator;
use App\Services\Agent\AgentSchema;
use App\Services\Agent\AgentScopes;
use App\Services\Agent\AgentTokenService;
use App\Services\RateLimiterService;

/**
 * External AI agents: registry, credentials and the human approval queue.
 *  agents.view    – look.            agents.manage – register / edit / enable / rotate / revoke.
 *  social.approve – review and approve/reject agent drafts.
 * Issuing or rotating a credential requires re-entering the administrator's password. Revocation does not
 * (an emergency stop must be one click). A plaintext token is rendered once, in the response that creates it
 * (never flashed through the session, never stored, never logged).
 */
class AgentsController extends AdminBaseController
{
    private function adminId(): int
    {
        return (int) ($this->adminUser['id'] ?? 0);
    }

    private function audit(string $action, int|string $id, ?array $data = null): void
    {
        (new AuditLogModel())->log($action, 'ai_agent', $id, null, $data, $this->adminId());
    }

    private function agentLog(string $event, int $agentId, string $detail = ''): void
    {
        (new AgentAuditLogger())->log($event, $agentId, ['detail' => $detail, 'ip' => $this->request->getIPAddress(), 'status' => 200]);
    }

    private function ensure(): void
    {
        if (!AgentSchema::exists()) {
            AgentSchema::ensure();   // additive, idempotent; only reachable by an authenticated administrator
        }
    }

    private function confirmPassword(): bool
    {
        $limiter = new RateLimiterService();
        $r = $limiter->hit('agent_reauth:' . $this->adminId(), 5, 600);
        if (!$r['allowed']) {
            return false;
        }
        $pw  = (string) $this->request->getPost('confirm_password');
        $row = \Config\Database::connect()->table('admin_users')->select('password_hash')->where('id', $this->adminId())->get()->getRowArray();
        return $pw !== '' && $row && password_verify($pw, (string) $row['password_hash']);
    }

    private function stats(array $agent): array
    {
        $db    = \Config\Database::connect();
        $since = date('Y-m-d H:i:s', time() - 30 * 86400);
        $tok   = (new AgentTokenService($db))->currentToken((int) $agent['id']);
        $state = 'none';
        if ($tok) {
            $left  = strtotime($tok['expires_at']) - time();
            $state = $left <= 0 ? 'expired' : ($left < 7 * 86400 ? 'expiring' : 'active');
        } elseif ($db->table('ai_agent_tokens')->where('agent_id', (int) $agent['id'])->countAllResults()) {
            $state = 'revoked';
        }
        $warn = [];
        if ($agent['status'] === 'active' && in_array($state, ['expired', 'none', 'revoked'], true)) {
            $warn[] = $state === 'expired' ? 'Credential expired — this agent cannot authenticate.' : 'No valid credential — this agent cannot authenticate.';
        }
        if ($state === 'expiring') {
            $warn[] = 'Credential expires soon — rotate it.';
        }
        if ($agent['status'] === 'disabled') {
            $warn[] = 'Agent is disabled.';
        }
        return [
            'token' => $tok, 'token_state' => $state, 'warnings' => $warn,
            'drafts'   => (int) $db->table('social_posts')->where('agent_id', (int) $agent['id'])->countAllResults(),
            'requests' => (int) $db->table('ai_agent_audit')->where('agent_id', (int) $agent['id'])->where('event', 'REQUEST')->where('created_at >=', $since)->countAllResults(),
            'failed'   => (int) $db->table('ai_agent_audit')->where('agent_id', (int) $agent['id'])->whereNotIn('event', ['REQUEST', 'DRAFT_CREATED', 'DRAFT_SUBMITTED', 'TOKEN_ISSUED', 'TOKEN_ROTATED', 'TOKEN_REVOKED', 'AGENT_UPDATED', 'AGENT_ENABLED', 'AGENT_DISABLED', 'AGENT_REGISTERED'])->where('created_at >=', $since)->countAllResults(),
        ];
    }

    private function intField(string $name, int $default): int
    {
        $raw = $this->request->getPost($name);
        return ($raw === null || trim((string) $raw) === '') ? $default : (ctype_digit(trim((string) $raw)) ? (int) $raw : -1);
    }

    private function parseAgentForm(): array
    {
        $errors = [];
        $name = AgentDraftValidator::text($this->request->getPost('name'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors[] = 'Name must be 2–120 characters.';
        }
        $provider = (string) $this->request->getPost('provider');
        if (!isset(AgentScopes::PROVIDERS[$provider])) {
            $errors[] = 'Choose a valid agent type.';
        }
        $desc = mb_substr(AgentDraftValidator::text($this->request->getPost('description')), 0, 500);
        $scopes = AgentScopes::sanitize((array) $this->request->getPost('scopes'));
        // An empty field falls back to the default; an explicit value (including 0) must be valid.
        $rate  = $this->intField('rate_limit_per_minute', 30);
        $daily = $this->intField('daily_draft_limit', 25);
        if ($rate < 5 || $rate > 120) {
            $errors[] = 'Requests per minute must be between 5 and 120.';
        }
        if ($daily < 1 || $daily > 100) {
            $errors[] = 'Drafts per day must be between 1 and 100.';
        }
        $ips = [];
        foreach (preg_split('/[\s,]+/', trim((string) $this->request->getPost('allowed_ips')), -1, PREG_SPLIT_NO_EMPTY) as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $errors[] = 'Invalid IP address in the allowlist: ' . esc($ip);
            } else {
                $ips[] = $ip;
            }
        }
        $ttl = $this->intField('ttl_days', AgentTokenService::DEFAULT_TTL_DAYS);
        if ($ttl < 1 || $ttl > AgentTokenService::MAX_TTL_DAYS) {
            $errors[] = 'Token lifetime must be 1–' . AgentTokenService::MAX_TTL_DAYS . ' days.';
        }
        return [$errors, ['name' => $name, 'provider' => $provider, 'description' => $desc ?: null, 'scopes' => implode(',', $scopes), 'rate_limit_per_minute' => $rate,
                          'daily_draft_limit' => $daily, 'allowed_ips' => $ips ? implode(',', $ips) : null], $ttl];
    }

    // ── registry ─────────────────────────────────────────────────────────────
    public function index()
    {
        if ($r = $this->requirePermission('agents.view')) {
            return $r;
        }
        $this->ensure();
        $db = \Config\Database::connect();
        $agents = $db->table('ai_agents')->orderBy('id', 'DESC')->get()->getResultArray();
        foreach ($agents as &$a) {
            $a['stats'] = $this->stats($a);
        }
        return $this->renderAdminView('admin/agents/index', [
            'title' => 'External AI Agents — TalentHub Admin', 'agents' => $agents, 'canManage' => $this->canManage(),
            'pending' => (new AgentApprovalService())->pendingCount(),
        ], 'agents');
    }

    private function canManage(): bool
    {
        return $this->isSuperAdmin() || in_array('agents.manage', $this->adminUser['permissions'] ?? [], true);
    }

    public function create()
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $this->ensure();
        return $this->renderAdminView('admin/agents/create', ['title' => 'Register AI Agent — TalentHub Admin', 'old' => []], 'agents');
    }

    public function store()
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $this->ensure();
        [$errors, $data, $ttl] = $this->parseAgentForm();
        if (!$errors && !$this->confirmPassword()) {
            $this->audit('AGENT_REAUTH_FAILED', 'new');
            $errors[] = 'Password confirmation failed (or too many attempts — try again in a few minutes).';
        }
        $db = \Config\Database::connect();
        if (!$errors && $db->table('ai_agents')->where('name', $data['name'])->countAllResults()) {
            $errors[] = 'An agent with this name already exists.';
        }
        if ($errors) {
            session()->setFlashdata('error', implode(' ', $errors));
            return redirect()->back()->withInput();
        }
        $now = date('Y-m-d H:i:s');
        $db->table('ai_agents')->insert($data + ['status' => 'active', 'policy_mode' => 'draft_only', 'created_by' => $this->adminId(), 'created_at' => $now, 'updated_at' => $now]);
        $id  = (int) $db->insertID();
        $tok = (new AgentTokenService($db))->issue($id, $ttl, $this->adminId());
        $this->audit('AGENT_REGISTERED', $id, ['name' => $data['name'], 'provider' => $data['provider'], 'scopes' => $data['scopes']]);
        $this->agentLog('TOKEN_ISSUED', $id, 'prefix ' . $tok['prefix']);
        return $this->tokenPage($id, $data['name'], $tok, 'registered');
    }

    public function show(int $id)
    {
        if ($r = $this->requirePermission('agents.view')) {
            return $r;
        }
        $this->ensure();
        $db    = \Config\Database::connect();
        $agent = $db->table('ai_agents')->where('id', $id)->get()->getRowArray();
        if (!$agent) {
            session()->setFlashdata('error', 'Agent not found.');
            return redirect()->to(base_url('admin/agents'));
        }
        return $this->renderAdminView('admin/agents/show', [
            'title' => $agent['name'] . ' — External AI Agents', 'agent' => $agent, 'stats' => $this->stats($agent), 'canManage' => $this->canManage(),
            'activity' => $db->table('ai_agent_audit')->where('agent_id', $id)->orderBy('id', 'DESC')->limit(60)->get()->getResultArray(),
            'drafts'   => $db->table('social_posts')->select('id,title,status,review_decision,created_at')->where('agent_id', $id)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray(),
        ], 'agents');
    }

    public function update(int $id)
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $db    = \Config\Database::connect();
        $agent = $db->table('ai_agents')->where('id', $id)->get()->getRowArray();
        if (!$agent) {
            return redirect()->to(base_url('admin/agents'))->with('error', 'Agent not found.');
        }
        [$errors, $data] = $this->parseAgentForm();
        unset($data['name'], $data['provider']);   // identity is fixed after registration
        $errors = array_values(array_filter($errors, static fn ($e) => !str_starts_with($e, 'Name') && !str_starts_with($e, 'Choose')));
        if ($errors) {
            session()->setFlashdata('error', implode(' ', $errors));
            return redirect()->to(base_url('admin/agents/' . $id));
        }
        $db->table('ai_agents')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
        $this->audit('AGENT_UPDATED', $id, ['scopes' => $data['scopes'], 'rate' => $data['rate_limit_per_minute'], 'daily' => $data['daily_draft_limit']]);
        $this->agentLog('AGENT_UPDATED', $id, 'by admin #' . $this->adminId());
        session()->setFlashdata('success', 'Agent settings saved.');
        return redirect()->to(base_url('admin/agents/' . $id));
    }

    public function toggle(int $id)
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $db    = \Config\Database::connect();
        $agent = $db->table('ai_agents')->where('id', $id)->get()->getRowArray();
        if ($agent) {
            $new = $agent['status'] === 'active' ? 'disabled' : 'active';
            $db->table('ai_agents')->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->audit($new === 'active' ? 'AGENT_ENABLED' : 'AGENT_DISABLED', $id);
            $this->agentLog($new === 'active' ? 'AGENT_ENABLED' : 'AGENT_DISABLED', $id, 'by admin #' . $this->adminId());
            session()->setFlashdata('success', 'Agent ' . ($new === 'active' ? 'enabled' : 'disabled') . '.');
        }
        return redirect()->to(base_url('admin/agents/' . $id));
    }

    public function rotate(int $id)
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $db    = \Config\Database::connect();
        $agent = $db->table('ai_agents')->where('id', $id)->get()->getRowArray();
        if (!$agent) {
            return redirect()->to(base_url('admin/agents'))->with('error', 'Agent not found.');
        }
        if (!$this->confirmPassword()) {
            $this->audit('AGENT_REAUTH_FAILED', $id);
            session()->setFlashdata('error', 'Password confirmation failed (or too many attempts — try again in a few minutes). Nothing was changed.');
            return redirect()->to(base_url('admin/agents/' . $id));
        }
        $ttl = $this->intField('ttl_days', AgentTokenService::DEFAULT_TTL_DAYS);
        if ($ttl < 1 || $ttl > AgentTokenService::MAX_TTL_DAYS) {
            session()->setFlashdata('error', 'Token lifetime must be 1–' . AgentTokenService::MAX_TTL_DAYS . ' days. Nothing was changed.');
            return redirect()->to(base_url('admin/agents/' . $id));
        }
        try {
            $tok = (new AgentTokenService($db))->rotate($id, $ttl, $this->adminId());
        } catch (\Throwable) {
            session()->setFlashdata('error', 'Rotation failed; the previous credential is unchanged.');
            return redirect()->to(base_url('admin/agents/' . $id));
        }
        $this->audit('AGENT_TOKEN_ROTATED', $id, ['prefix' => $tok['prefix']]);
        $this->agentLog('TOKEN_ROTATED', $id, 'new prefix ' . $tok['prefix']);
        return $this->tokenPage($id, $agent['name'], $tok, 'rotated');
    }

    /** Emergency stop: immediate, no extra confirmation step. */
    public function revoke(int $id)
    {
        if ($r = $this->requirePermission('agents.manage')) {
            return $r;
        }
        $n = (new AgentTokenService())->revokeAll($id, $this->adminId(), 'revoked');
        $this->audit('AGENT_TOKEN_REVOKED', $id, ['tokens' => $n]);
        $this->agentLog('TOKEN_REVOKED', $id, 'by admin #' . $this->adminId());
        session()->setFlashdata('success', $n ? 'All credentials for this agent were revoked immediately.' : 'This agent had no active credential.');
        return redirect()->to(base_url('admin/agents/' . $id));
    }

    private function tokenPage(int $id, string $name, array $tok, string $what)
    {
        $html = $this->renderAdminView('admin/agents/token_issued', [
            'title' => 'Credential issued — TalentHub Admin', 'agentId' => $id, 'agentName' => $name, 'token' => $tok['token'], 'expires' => $tok['expires_at'], 'what' => $what,
        ], 'agents');
        return $this->response->setHeader('Cache-Control', 'no-store, private')->setHeader('Pragma', 'no-cache')->setBody($html);
    }

    // ── approvals ────────────────────────────────────────────────────────────
    public function approvals()
    {
        if ($r = $this->requirePermission('social.approve')) {
            return $r;
        }
        $this->ensure();
        $filter = in_array($this->request->getGet('filter'), ['pending', 'approved', 'closed', 'all'], true) ? $this->request->getGet('filter') : 'pending';
        return $this->renderAdminView('admin/agents/approvals', [
            'title' => 'AI Draft Approvals — TalentHub Admin', 'filter' => $filter, 'rows' => (new AgentApprovalService())->queue($filter),
        ], 'agent_approvals');
    }

    public function review(int $id)
    {
        if ($r = $this->requirePermission('social.approve')) {
            return $r;
        }
        $svc  = new AgentApprovalService();
        $post = $svc->find($id);
        if (!$post) {
            session()->setFlashdata('error', 'That agent draft was not found.');
            return redirect()->to(base_url('admin/agents/approvals'));
        }
        $media = \Config\Database::connect()->table('social_media_library')->select('id,title,media_type,thumbnail_url,file_url')->where('is_approved', 1)->orderBy('id', 'DESC')->limit(60)->get()->getResultArray();
        return $this->renderAdminView('admin/agents/review', ['title' => 'Review draft #' . $id . ' — TalentHub Admin', 'post' => $post, 'library' => $media], 'agent_approvals');
    }

    public function decide(int $id)
    {
        if ($r = $this->requirePermission('social.approve')) {
            return $r;
        }
        $svc    = new AgentApprovalService();
        $action = (string) $this->request->getPost('decision');
        $note   = (string) $this->request->getPost('note');
        $ok = match ($action) {
            'approve' => $svc->approve($id, $this->adminId(), $note),
            'reject'  => $svc->reject($id, $this->adminId(), $note),
            'changes' => $svc->requestChanges($id, $this->adminId(), $note),
            default   => false,
        };
        $labels = ['approve' => 'approved — it can now be scheduled or published by an administrator', 'reject' => 'rejected', 'changes' => 'sent back with your requested changes'];
        if ($ok) {
            session()->setFlashdata('success', 'Draft #' . $id . ' ' . $labels[$action] . '.');
        } else {
            session()->setFlashdata('error', in_array($action, ['reject', 'changes'], true) && trim($note) === ''
                ? 'A reason is required for this decision.'
                : 'This draft can no longer be changed that way (it may already have been reviewed).');
        }
        return redirect()->to(base_url('admin/agents/approvals/' . $id));
    }

    public function saveEdits(int $id)
    {
        if ($r = $this->requirePermission('social.approve')) {
            return $r;
        }
        $res = (new AgentApprovalService())->edit($id, $this->adminId(), [
            'title' => $this->request->getPost('title'), 'caption' => $this->request->getPost('caption'), 'hashtags' => $this->request->getPost('hashtags'),
            'cta' => $this->request->getPost('cta'), 'destination_url' => $this->request->getPost('destination_url'), 'media_ids' => (array) $this->request->getPost('media_ids'),
        ]);
        session()->setFlashdata($res['ok'] ? 'success' : 'error', $res['ok'] ? 'Edits saved. If this draft had been approved, it is back in review and must be approved again.' : 'Not saved: ' . implode(' ', array_map(static fn ($k, $v) => "$k: $v", array_keys($res['errors']), $res['errors'])));
        return redirect()->to(base_url('admin/agents/approvals/' . $id));
    }
}
