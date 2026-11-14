<?php

namespace Tests\Unit;

use App\Services\Agent\AgentScopes;
use App\Services\Agent\AgentSchema;
use App\Services\Agent\AgentTokenService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * @internal
 */
final class AgentsAdminTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    private const ADMIN_ID = 7771;
    private const PASSWORD = 'Correct-Horse-9!';

    protected function setUp(): void
    {
        parent::setUp();
        AgentSchema::ensure($this->dbc());
        \Config\Services::resetSingle('renderer');
        $this->beginRollbackTx();
        $db = $this->dbc();
        foreach (['ai_agent_audit', 'ai_agent_idempotency', 'ai_agent_tokens', 'ai_agents', 'rate_limit_buckets', 'social_notifications'] as $t) {
            $db->query("DELETE FROM `$t`");
        }
        $db->query("DELETE FROM social_posts WHERE origin = 'agent'");
        $db->query("DELETE FROM audit_logs WHERE action LIKE 'AGENT_%'");   // count-based assertions need a clean slate (e2e runs leave rows)
        $db->query('DELETE FROM admin_users WHERE id = ' . self::ADMIN_ID);
        $db->table('admin_users')->insert(['id' => self::ADMIN_ID, 'username' => 'e2e_admin_tester', 'email' => 'e2e_admin_tester@example.invalid', 'password_hash' => password_hash(self::PASSWORD, PASSWORD_DEFAULT), 'first_name' => 'T', 'last_name' => 'Tester', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
    }

    protected function tearDown(): void
    {
        $this->endRollbackTx();
        parent::tearDown();
    }

    private function as(array $perms, bool $super = false): array
    {
        return ['admin_user' => ['id' => self::ADMIN_ID, 'username' => 'e2e_admin_tester', 'name' => 'Tester', 'email' => 'e2e_admin_tester@example.invalid',
            'roles' => [['id' => 99, 'name' => $super ? 'Super Administrator' : 'Limited', 'slug' => $super ? 'super_admin' : 'limited']], 'permissions' => $perms]];
    }

    private function post(string $uri, array $session, array $data = [], bool $csrf = true)
    {
        \Config\Services::resetSingle('renderer');
        return $this->withSession($session)->call('post', $uri, ($csrf ? [csrf_token() => csrf_hash()] : []) + $data);
    }

    private function get(string $uri, array $session)
    {
        \Config\Services::resetSingle('renderer');
        return $this->withSession($session)->call('get', $uri);
    }

    private function register(array $session, array $over = []): \CodeIgniter\Test\TestResponse
    {
        return $this->post('admin/agents/store', $session, $over + ['name' => 'Weekly Claude', 'provider' => 'claude', 'description' => 'weekly ideas', 'scopes' => AgentScopes::DEFAULTS,
            'rate_limit_per_minute' => 30, 'daily_draft_limit' => 25, 'ttl_days' => 30, 'confirm_password' => self::PASSWORD]);
    }

    private function token(string $html): string
    {
        preg_match('/thk_[a-f0-9]{12}_[A-Za-z0-9_-]{43}/', $html, $m);
        return $m[0] ?? '';
    }

    private function api(string $token, string $path = 'capabilities')
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $token])->call('get', 'api/v1/integrations/agents/' . $path);
    }

    private function rows(string $table, array $where = []): int
    {
        return (int) $this->dbc()->table($table)->where($where)->countAllResults();
    }

    // ── access control ───────────────────────────────────────────────────────
    public function testAnonymousAndUnprivilegedUsersAreBlocked(): void
    {
        foreach (['admin/agents', 'admin/agents/create', 'admin/agents/1', 'admin/agents/approvals', 'admin/agents/approvals/1'] as $uri) {
            $this->call('get', $uri)->assertStatus(302);
            $r = $this->get($uri, $this->as(['applications.view']));
            $r->assertStatus(302);
            $this->assertStringNotContainsString('agents', (string) $r->getRedirectUrl(), $uri);
        }
        foreach (['admin/agents/store', 'admin/agents/1/rotate', 'admin/agents/1/revoke', 'admin/agents/1/toggle', 'admin/agents/1/update', 'admin/agents/approvals/1/decide', 'admin/agents/approvals/1/edit'] as $uri) {
            $this->post($uri, $this->as(['applications.view']))->assertStatus(302);
        }
        $this->assertSame(0, $this->rows('ai_agents'));
    }

    public function testViewOnlyUserSeesRegistryButCannotChangeAnything(): void
    {
        [$id] = $this->seedAgent();
        $s = $this->as(['agents.view']);
        $page = $this->get('admin/agents', $s);
        $page->assertStatus(200);
        $this->assertStringNotContainsString('Register agent', $page->getBody());
        $detail = (string) $this->get('admin/agents/' . $id, $s)->getBody();
        $this->assertStringNotContainsString('Revoke immediately', $detail);
        $this->assertStringNotContainsString('Rotate credential', $detail);
        $this->post("admin/agents/{$id}/revoke", $s);
        $this->post("admin/agents/{$id}/toggle", $s);
        $this->assertNotNull($this->dbc()->table('ai_agent_tokens')->where('agent_id', $id)->get()->getRowArray()['revoked_at'] === null ? 1 : null);
        $this->assertSame('active', $this->dbc()->table('ai_agents')->where('id', $id)->get()->getRowArray()['status']);
        $this->get('admin/agents/create', $s)->assertStatus(302);
    }

    private function seedAgent(string $name = 'Seeded'): array
    {
        $db = $this->dbc();
        $db->table('ai_agents')->insert(['name' => $name, 'provider' => 'custom', 'status' => 'active', 'policy_mode' => 'draft_only', 'scopes' => implode(',', AgentScopes::DEFAULTS), 'created_at' => date('Y-m-d H:i:s')]);
        $id = (int) $db->insertID();
        return [$id, (new AgentTokenService($db))->issue($id, 30, null)['token']];
    }

    // ── registration & credentials ───────────────────────────────────────────
    public function testRegistrationNeedsPasswordConfirmationAndShowsTheTokenExactlyOnce(): void
    {
        $s = $this->as(['agents.manage']);
        $bad = $this->register($s, ['confirm_password' => 'wrong']);
        $bad->assertStatus(302);
        $this->assertSame(0, $this->rows('ai_agents'), 'wrong password creates nothing');
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_REAUTH_FAILED']));
        $none = $this->register($s, ['confirm_password' => '']);
        $this->assertSame(0, $this->rows('ai_agents'));

        $res = $this->register($s);
        $res->assertStatus(200);
        $body = (string) $res->getBody();
        $token = $this->token($body);
        $this->assertNotSame('', $token, 'token shown once in the creating response');
        $this->assertStringContainsString('no-store', $res->response()->getHeaderLine('Cache-Control'));
        $agent = $this->dbc()->table('ai_agents')->get()->getRowArray();
        $this->assertSame('draft_only', $agent['policy_mode']);
        $this->assertSame('active', $agent['status']);

        // never stored or logged in clear
        $this->assertSame(hash('sha256', $token), $this->dbc()->table('ai_agent_tokens')->get()->getRowArray()['token_hash']);
        foreach (['ai_agent_tokens', 'ai_agent_audit', 'audit_logs', 'ai_agents'] as $t) {
            $this->assertStringNotContainsString(substr($token, 17), json_encode($this->dbc()->table($t)->get()->getResultArray()), "$t must not contain the token");
        }
        $this->assertStringNotContainsString(substr($token, 17), json_encode(session()->get()), 'not kept in the session');
        // not retrievable afterwards
        $this->assertStringNotContainsString(substr($token, 17), (string) $this->get('admin/agents/' . $agent['id'], $s)->getBody());
        $this->assertStringNotContainsString(substr($token, 17), (string) $this->get('admin/agents', $s)->getBody());
        // and it really works against the API
        $this->api($token)->assertStatus(200);
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_REGISTERED']));
        $this->assertSame(1, $this->rows('ai_agent_audit', ['event' => 'TOKEN_ISSUED']));
    }

    public function testRegistrationValidationAndForbiddenScopesAreDropped(): void
    {
        $s = $this->as([], true);
        foreach ([['name' => 'x'], ['provider' => 'skynet'], ['rate_limit_per_minute' => 5000], ['daily_draft_limit' => 0], ['allowed_ips' => 'not-an-ip'], ['ttl_days' => 9999]] as $bad) {
            $this->register($s, $bad)->assertStatus(302);
        }
        $this->assertSame(0, $this->rows('ai_agents'));
        $this->register($s, ['name' => 'Scopes', 'scopes' => ['social.posts.publish', 'social.schedule', 'admin.users.manage', 'db.read', AgentScopes::DRAFTS_CREATE]])->assertStatus(200);
        $this->assertSame(AgentScopes::DRAFTS_CREATE, $this->dbc()->table('ai_agents')->where('name', 'Scopes')->get()->getRowArray()['scopes']);
        $this->register($s, ['name' => 'Scopes'])->assertStatus(302);   // duplicate name
        $this->assertSame(1, $this->rows('ai_agents'));
    }

    public function testRotationNeedsPasswordAndKillsTheOldTokenImmediately(): void
    {
        $s = $this->as(['agents.manage']);
        [$id, $old] = $this->seedAgent();
        $this->post("admin/agents/{$id}/rotate", $s, ['confirm_password' => 'nope', 'ttl_days' => 30])->assertStatus(302);
        $this->api($old)->assertStatus(200);   // unchanged after a failed confirmation

        $res = $this->post("admin/agents/{$id}/rotate", $s, ['confirm_password' => self::PASSWORD, 'ttl_days' => 30]);
        $res->assertStatus(200);
        $new = $this->token((string) $res->getBody());
        $this->assertNotSame('', $new);
        $this->assertNotSame($old, $new);
        $this->assertSame(401, $this->api($old)->response()->getStatusCode());
        $this->api($new)->assertStatus(200);
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_TOKEN_ROTATED']));
        $this->assertSame(1, $this->rows('ai_agent_audit', ['event' => 'TOKEN_ROTATED']));
        $this->assertSame(1, (int) $this->dbc()->table('ai_agent_tokens')->where('agent_id', $id)->where('revoked_at', null)->countAllResults(), 'exactly one live credential');
    }

    public function testPasswordConfirmationIsRateLimited(): void
    {
        $s = $this->as(['agents.manage']);
        [$id] = $this->seedAgent();
        for ($i = 0; $i < 5; $i++) {
            $this->post("admin/agents/{$id}/rotate", $s, ['confirm_password' => 'bad' . $i]);
        }
        $this->post("admin/agents/{$id}/rotate", $s, ['confirm_password' => self::PASSWORD, 'ttl_days' => 30])->assertStatus(302);   // even the right password is refused while limited
        $this->assertSame(1, $this->rows('ai_agent_tokens', ['agent_id' => $id]), 'no new credential was issued');
    }

    public function testRevokeIsImmediateWithoutPasswordAndDisableBlocksTheAgent(): void
    {
        $s = $this->as(['agents.manage']);
        [$id, $tok] = $this->seedAgent();
        $this->api($tok)->assertStatus(200);
        $this->post("admin/agents/{$id}/revoke", $s)->assertStatus(302);   // no confirm_password supplied on purpose
        $r = $this->api($tok);
        $r->assertStatus(401);
        $this->assertSame('TOKEN_REVOKED', json_decode((string) $r->getJSON(), true)['error']['code']);
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_TOKEN_REVOKED']));
        $this->assertSame(1, $this->rows('ai_agent_audit', ['event' => 'TOKEN_REVOKED']));

        [$id2, $tok2] = $this->seedAgent('Second');
        $this->post("admin/agents/{$id2}/toggle", $s);
        $this->assertSame(403, $this->api($tok2)->response()->getStatusCode());
        $this->post("admin/agents/{$id2}/toggle", $s);
        $this->api($tok2)->assertStatus(200);
    }

    public function testUpdateSanitisesScopesAndValidatesLimits(): void
    {
        $s = $this->as(['agents.manage']);
        [$id, $tok] = $this->seedAgent();
        $this->post("admin/agents/{$id}/update", $s, ['scopes' => [AgentScopes::GUIDELINES_READ, 'social.publish'], 'rate_limit_per_minute' => 20, 'daily_draft_limit' => 5, 'allowed_ips' => '']);
        $row = $this->dbc()->table('ai_agents')->where('id', $id)->get()->getRowArray();
        $this->assertSame(AgentScopes::GUIDELINES_READ, $row['scopes']);
        $this->assertSame('20', (string) $row['rate_limit_per_minute']);
        $this->assertSame(403, $this->api($tok, 'social/accounts')->response()->getStatusCode(), 'removed permission takes effect immediately');
        $this->post("admin/agents/{$id}/update", $s, ['scopes' => [AgentScopes::ACCOUNTS_READ], 'rate_limit_per_minute' => 9999, 'daily_draft_limit' => 5, 'allowed_ips' => 'x']);
        $this->assertSame(AgentScopes::GUIDELINES_READ, $this->dbc()->table('ai_agents')->where('id', $id)->get()->getRowArray()['scopes'], 'invalid update changes nothing');
    }

    public function testMutationsWithoutCsrfAreRefused(): void
    {
        [$id] = $this->seedAgent();
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        try {
            $this->post("admin/agents/{$id}/revoke", $this->as([], true), [], false);
        } finally {
            $this->assertSame(0, $this->rows('ai_agent_tokens', ['agent_id' => $id, 'revoked_at IS NOT NULL' => null]));
        }
    }

    // ── approvals ────────────────────────────────────────────────────────────
    private function agentDraft(string $status = 'pending_approval', array $over = []): int
    {
        [$agentId] = $this->seedAgent('Drafter' . random_int(1, 99999));
        $this->dbc()->table('social_posts')->insert($over + ['title' => 'Agent idea', 'base_content' => 'A clear caption ' . bin2hex(random_bytes(3)), 'base_hashtags' => '#hiring', 'status' => $status, 'primary_platform' => 'linkedin',
            'origin' => 'agent', 'agent_id' => $agentId, 'created_at' => date('Y-m-d H:i:s')]);
        $id = (int) $this->dbc()->insertID();
        $this->dbc()->table('social_post_platforms')->insert(['post_id' => $id, 'platform' => 'linkedin', 'caption' => 'A clear caption', 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s')]);
        return $id;
    }

    private function post_(int $id): array
    {
        return $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray();
    }

    public function testApprovalRequiresSocialApproveAndRecordsApproverAndTime(): void
    {
        $id = $this->agentDraft();
        $this->post("admin/agents/approvals/{$id}/decide", $this->as(['social.publish', 'agents.manage']), ['decision' => 'approve'])->assertStatus(302);
        $this->assertSame('pending_approval', $this->post_($id)['status'], 'agents.manage / social.publish do NOT imply approval');
        $this->assertSame(0, $this->rows('audit_logs', ['action' => 'AGENT_DRAFT_APPROVED']));

        $this->post("admin/agents/approvals/{$id}/decide", $this->as(['social.approve']), ['decision' => 'approve', 'note' => 'Good']);
        $p = $this->post_($id);
        $this->assertSame('approved', $p['status']);
        $this->assertSame('approved', $p['review_decision']);
        $this->assertSame((string) self::ADMIN_ID, (string) $p['approved_by']);
        $this->assertNotNull($p['approved_at']);
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_DRAFT_APPROVED', 'entity_id' => (string) $id]));
        $this->assertSame(0, $this->rows('social_scheduled_jobs', ['post_id' => $id]), 'approval does not schedule or publish');
        $this->post("admin/agents/approvals/{$id}/decide", $this->as(['social.approve']), ['decision' => 'reject', 'note' => 'late']);
        $this->assertSame('approved', $this->post_($id)['status'], 'a reviewed draft cannot be re-decided');
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_DRAFT_REJECTED_REJECTED']));
    }

    public function testRejectAndRequestChangesNeedAReasonAndAgentSeesThem(): void
    {
        $s = $this->as(['social.approve']);
        $a = $this->agentDraft();
        $this->post("admin/agents/approvals/{$a}/decide", $s, ['decision' => 'reject', 'note' => '   ']);
        $this->assertSame('pending_approval', $this->post_($a)['status'], 'a reason is mandatory');
        $this->post("admin/agents/approvals/{$a}/decide", $s, ['decision' => 'reject', 'note' => 'Off-brand']);
        $this->assertSame('cancelled', $this->post_($a)['status']);
        $this->assertSame('rejected', $this->post_($a)['review_decision']);
        $this->assertNull($this->post_($a)['approved_by']);

        $b = $this->agentDraft();
        $this->post("admin/agents/approvals/{$b}/decide", $s, ['decision' => 'changes', 'note' => 'Shorter please']);
        $this->assertSame('draft', $this->post_($b)['status']);
        $this->assertSame('changes_requested', $this->post_($b)['review_decision']);
        $agentId = (int) $this->post_($b)['agent_id'];
        $tok = (new AgentTokenService($this->dbc()))->issue($agentId, 30, null)['token'];
        $d = json_decode((string) $this->api($tok, "social/posts/{$b}")->getJSON(), true)['data']['post'];
        $this->assertSame('changes_requested', $d['review']['decision']);
        $this->assertSame('Shorter please', $d['review']['note']);
        $this->assertFalse($d['can_be_published']);
        $this->post("admin/agents/approvals/{$b}/decide", $s, ['decision' => 'bogus']);
        $this->assertSame('changes_requested', $this->post_($b)['review_decision']);
    }

    public function testEditingApprovedContentVoidsApprovalAndWithdrawsQueuedJobs(): void
    {
        $s = $this->as(['social.approve']);
        $id = $this->agentDraft();
        $this->post("admin/agents/approvals/{$id}/decide", $s, ['decision' => 'approve', 'note' => 'Good to go']);
        $this->dbc()->table('social_scheduled_jobs')->insert(['post_id' => $id, 'platform' => 'linkedin', 'scheduled_at' => date('Y-m-d H:i:s'), 'status' => 'queued', 'created_at' => date('Y-m-d H:i:s')]);
        $this->post("admin/agents/approvals/{$id}/edit", $s, ['title' => 'Edited title', 'caption' => 'Edited caption text', 'hashtags' => '#edited', 'cta' => '', 'destination_url' => '']);
        $p = $this->post_($id);
        $this->assertSame('Edited caption text', $p['base_content']);
        $this->assertSame('pending_approval', $p['status'], 'content changed → back to review');
        $this->assertNull($p['review_decision']);
        $this->assertNull($p['approved_by']);
        $this->assertNull($p['review_note'], 'the stale "approved" note must not survive the voided approval');
        $this->assertSame('cancelled', $this->dbc()->table('social_scheduled_jobs')->where('post_id', $id)->get()->getRowArray()['status'], 'a publish job for the old approved text is withdrawn');
        $this->assertSame('Edited caption text', $this->dbc()->table('social_post_platforms')->where('post_id', $id)->get()->getRowArray()['caption']);
        $this->assertSame(1, $this->rows('audit_logs', ['action' => 'AGENT_DRAFT_EDITED', 'entity_id' => (string) $id]));
    }

    public function testEditsAreValidatedAndLockedPostsCannotBeEdited(): void
    {
        $s = $this->as(['social.approve']);
        $id = $this->agentDraft();
        $before = $this->post_($id)['base_content'];
        $this->post("admin/agents/approvals/{$id}/edit", $s, ['caption' => str_repeat('x', 3001)]);
        $this->assertSame($before, $this->post_($id)['base_content'], 'over-limit caption rejected');
        $this->post("admin/agents/approvals/{$id}/edit", $s, ['destination_url' => 'https://evil.example/']);
        $this->assertSame($before, $this->post_($id)['base_content']);
        $this->post("admin/agents/approvals/{$id}/edit", $s, ['caption' => 'x', 'media_ids' => [987654]]);
        $this->assertSame($before, $this->post_($id)['base_content'], 'unknown media rejected');
        $pub = $this->agentDraft('published');
        $this->post("admin/agents/approvals/{$pub}/edit", $s, ['caption' => 'tamper']);
        $this->assertNotSame('tamper', $this->post_($pub)['base_content']);
    }

    public function testOnlyAgentOriginPostsAppearInTheReviewQueue(): void
    {
        $this->dbc()->table('social_posts')->insert(['title' => 'Human draft', 'base_content' => 'mine', 'status' => 'draft', 'primary_platform' => 'linkedin', 'created_at' => date('Y-m-d H:i:s')]);
        $human = (int) $this->dbc()->insertID();
        $s = $this->as(['social.approve']);
        $this->post("admin/agents/approvals/{$human}/decide", $s, ['decision' => 'approve']);
        $this->assertNull($this->post_($human)['approved_by'], 'the agent approval tool cannot touch human-created posts');
        $this->get("admin/agents/approvals/{$human}", $s)->assertStatus(302);
        $a = $this->agentDraft();
        $page = (string) $this->get('admin/agents/approvals', $s)->getBody();
        $this->assertStringContainsString('#' . $a, $page);
        $this->assertStringNotContainsString('Human draft', $page);
        $this->assertStringContainsString('AI agent', $page);
    }

    public function testSidebarShowsAgentEntriesOnlyToThoseWhoMayUseThem(): void
    {
        $this->agentDraft();
        $super = (string) $this->get('admin/agents', $this->as([], true))->getBody();
        $this->assertStringContainsString('External AI Agents', $super);
        $this->assertStringContainsString('AI Draft Approvals', $super);
        $only = (string) $this->get('admin/agents/approvals', $this->as(['social.approve']))->getBody();
        $this->assertStringContainsString('AI Draft Approvals', $only);
        $this->assertStringNotContainsString('External AI Agents', explode('<main', $only)[0], 'registry link hidden without agents.view');
    }
}
