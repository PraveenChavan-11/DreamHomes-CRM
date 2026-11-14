<?php

namespace Tests\Unit;

use App\Database\Migrations\AiAgentFoundation;
use App\Services\Agent\AgentScopes;
use App\Services\Agent\AgentTokenService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\RollsBackDatabase;

/**
 * @internal
 */
final class AgentApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use RollsBackDatabase;

    private const BASE = 'api/v1/integrations/agents/';
    private int $agentId;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Services\Agent\AgentSchema::ensure($this->dbc());   // additive + idempotent; test DB only
        \Config\Services::resetSingle('renderer');
        $this->beginRollbackTx();
        $db = $this->dbc();
        foreach (['ai_agent_audit', 'ai_agent_idempotency', 'ai_agent_tokens', 'ai_agents', 'rate_limit_buckets', 'social_notifications', 'social_media_library', 'social_accounts', 'social_credentials'] as $t) {
            $db->query("DELETE FROM `$t`");
        }
        $db->query("DELETE FROM social_posts WHERE origin = 'agent'");
        [$this->agentId, $this->token] = $this->makeAgent('Test Agent');
    }

    protected function tearDown(): void
    {
        $this->endRollbackTx();
        parent::tearDown();
    }

    /** @return array{0:int,1:string} */
    private function makeAgent(string $name, array $over = [], ?int $ttl = 30): array
    {
        $db = $this->dbc();
        $db->table('ai_agents')->insert($over + [
            'name' => $name, 'provider' => 'custom', 'status' => 'active', 'policy_mode' => 'draft_only',
            'scopes' => implode(',', AgentScopes::DEFAULTS), 'rate_limit_per_minute' => 60, 'daily_draft_limit' => 25, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        return [$id, (new AgentTokenService($db))->issue($id, $ttl, null)['token']];
    }

    private function req(string $method, string $path, ?array $body = null, ?string $token = null, array $headers = [], bool $raw = false)
    {
        $h = ['Authorization' => 'Bearer ' . ($token ?? $this->token), 'Content-Type' => 'application/json'] + $headers;
        if ($token === '') {
            unset($h['Authorization']);
        }
        $t = $this->withHeaders($h);
        if ($body !== null) {
            $t = $t->withBody($raw ? (string) $body[0] : json_encode($body));
        }
        return $t->call($method, self::BASE . $path);
    }

    private function json($res): array
    {
        return json_decode((string) $res->getJSON(), true) ?: [];
    }

    private function draft(array $over = []): array
    {
        return $over + ['title' => 'Hiring tips', 'platform' => 'linkedin', 'caption' => 'Three ways to write a clearer job description. ' . bin2hex(random_bytes(4)), 'hashtags' => ['#hiring', 'recruitment'], 'cta' => 'Talk to us', 'destination_url' => 'https://talenthubconsultant.com/services'];
    }

    private function media(int $approved = 1): int
    {
        $this->dbc()->table('social_media_library')->insert(['title' => 'm', 'filename' => 'm.jpg', 'original_name' => 'm.jpg', 'file_path' => 'x/m.jpg', 'file_url' => 'https://x/m.jpg', 'mime_type' => 'image/jpeg', 'file_size' => 10, 'media_type' => 'image', 'is_approved' => $approved, 'created_at' => date('Y-m-d H:i:s')]);
        return (int) $this->dbc()->insertID();
    }

    private function audit(string $event): int
    {
        return (int) $this->dbc()->table('ai_agent_audit')->where('event', $event)->countAllResults();
    }

    private function agentPosts(): int
    {
        return (int) $this->dbc()->table('social_posts')->where('origin', 'agent')->countAllResults();
    }

    // ── authentication ───────────────────────────────────────────────────────
    public function testMissingAndMalformedAndWrongTokensGetOneGenericAnswer(): void
    {
        $wrongSecret = substr($this->token, 0, 17) . str_repeat('A', 43);
        foreach (['', 'garbage', 'thk_aaaaaaaaaaaa_' . str_repeat('B', 43), $wrongSecret, str_repeat('x', 500)] as $t) {
            $res = $this->req('get', 'capabilities', null, $t);
            $res->assertStatus(401);
            $j = $this->json($res);
            $this->assertSame('INVALID_TOKEN', $j['error']['code']);
            $this->assertSame('Invalid or missing credentials.', $j['error']['message'], 'no hint whether the prefix exists');
            $this->assertNotEmpty($j['request_id']);
        }
        $this->assertGreaterThanOrEqual(5, $this->audit('AUTH_FAILED'));
    }

    public function testValidTokenReturnsCapabilitiesAndNeverOffersPublishing(): void
    {
        $res = $this->req('get', 'capabilities');
        $res->assertStatus(200);
        $d = $this->json($res)['data'];
        $this->assertSame('draft_only', $d['policy']['mode']);
        $this->assertFalse($d['policy']['direct_publishing']);
        $this->assertFalse($d['policy']['scheduling']);
        $this->assertTrue($d['policy']['human_approval_required']);
        $blob = strtolower(json_encode($d['scopes']));
        foreach (['publish', 'schedule', 'credential', 'admin', 'token'] as $bad) {
            $this->assertStringNotContainsString($bad . '"', $blob);
        }
        $this->assertStringContainsString('no-store', $res->response()->getHeaderLine('Cache-Control'));
        $this->assertNotSame('', $res->response()->getHeaderLine('X-Request-Id'));
    }

    public function testExpiredRevokedAndDisabledAreDistinguishedOnlyWithAValidSecret(): void
    {
        [$expId, $exp] = $this->makeAgent('Exp');
        $this->dbc()->table('ai_agent_tokens')->where('agent_id', $expId)->update(['expires_at' => date('Y-m-d H:i:s', time() - 5)]);
        $this->assertSame('TOKEN_EXPIRED', $this->json($this->req('get', 'capabilities', null, $exp))['error']['code']);

        [$revId, $rev] = $this->makeAgent('Rev');
        (new AgentTokenService($this->dbc()))->revokeAll($revId, 1, 'test');
        $this->req('get', 'capabilities', null, $rev)->assertStatus(401);
        $this->assertSame('TOKEN_REVOKED', $this->json($this->req('get', 'capabilities', null, $rev))['error']['code']);

        [$disId, $dis] = $this->makeAgent('Dis');
        $this->dbc()->table('ai_agents')->where('id', $disId)->update(['status' => 'disabled']);
        $res = $this->req('get', 'capabilities', null, $dis);
        $res->assertStatus(403);
        $this->assertSame('AGENT_DISABLED', $this->json($res)['error']['code']);

        // an attacker without the secret cannot tell the token exists / is expired:
        $guess = substr($exp, 0, 17) . str_repeat('Z', 43);
        $this->assertSame('INVALID_TOKEN', $this->json($this->req('get', 'capabilities', null, $guess))['error']['code']);
    }

    public function testRotationRevokesTheOldTokenImmediately(): void
    {
        $svc = new AgentTokenService($this->dbc());
        $new = $svc->rotate($this->agentId, 30, 1);
        $this->assertSame('TOKEN_REVOKED', $this->json($this->req('get', 'capabilities', null, $this->token))['error']['code']);
        $this->req('get', 'capabilities', null, $new['token'])->assertStatus(200);
    }

    public function testOnlyTheHashIsStoredAndTheTokenNeverAppearsInAnyLog(): void
    {
        $row = $this->dbc()->table('ai_agent_tokens')->where('agent_id', $this->agentId)->get()->getRowArray();
        $this->assertSame(hash('sha256', $this->token), $row['token_hash']);
        $this->assertStringNotContainsString(substr($this->token, 17), json_encode($row));
        $this->req('get', 'capabilities')->assertStatus(200);
        $this->req('get', 'capabilities', null, substr($this->token, 0, 17) . str_repeat('Q', 43));
        $this->assertStringNotContainsString(substr($this->token, 17), json_encode($this->dbc()->table('ai_agent_audit')->get()->getResultArray()));
        $this->assertSame(32, strlen((string) base64_decode(strtr(substr($this->token, 17), '-_', '+/') . '=', true)), 'the secret is 32 random bytes = 256 bits');
    }

    public function testTokenExpiryIsBoundedAndMandatory(): void
    {
        $svc = new AgentTokenService($this->dbc());
        $t = $svc->issue($this->agentId, 99999, null);
        $this->assertLessThanOrEqual(time() + 366 * 86400, strtotime($t['expires_at']));
        $t0 = $svc->issue($this->agentId, 0, null);
        $this->assertGreaterThan(time(), strtotime($t0['expires_at']));
    }

    public function testIpAllowlistIsEnforced(): void
    {
        $this->dbc()->table('ai_agents')->where('id', $this->agentId)->update(['allowed_ips' => '203.0.113.9']);
        $res = $this->req('get', 'capabilities');
        $res->assertStatus(403);
        $this->assertSame('IP_NOT_ALLOWED', $this->json($res)['error']['code']);
        $this->assertSame(1, $this->audit('IP_NOT_ALLOWED'));
    }

    public function testRepeatedFailedAuthLocksTheSourceOut(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->req('get', 'capabilities', null, 'thk_' . bin2hex(random_bytes(6)) . '_' . str_repeat('C', 43))->assertStatus(401);
        }
        $res = $this->req('get', 'capabilities');   // even a VALID token is refused while locked out
        $res->assertStatus(429);
        $this->assertSame('TOO_MANY_FAILED_AUTH', $this->json($res)['error']['code']);
        $this->assertNotSame('', $res->response()->getHeaderLine('Retry-After'));
        $this->assertSame(1, $this->audit('AUTH_LOCKED_OUT'));
    }

    // ── scopes & rate limiting ───────────────────────────────────────────────
    public function testScopeIsEnforcedPerEndpointAndAudited(): void
    {
        $this->dbc()->table('ai_agents')->where('id', $this->agentId)->update(['scopes' => AgentScopes::GUIDELINES_READ]);
        $this->req('get', 'social/content-guidelines')->assertStatus(200);
        foreach ([['get', 'social/accounts'], ['get', 'social/templates'], ['get', 'social/calendar'], ['get', 'social/analytics'], ['get', 'social/posts/1']] as [$m, $p]) {
            $res = $this->req($m, $p);
            $res->assertStatus(403);
            $this->assertSame('INSUFFICIENT_SCOPE', $this->json($res)['error']['code'], $p);
        }
        $this->req('post', 'social/posts/drafts', $this->draft())->assertStatus(403);
        $this->req('post', 'social/posts/1/submit-approval', [])->assertStatus(403);
        $this->assertSame(0, $this->agentPosts());
        $this->assertSame(7, $this->audit('PERMISSION_DENIED'));
    }

    public function testUnknownOrForbiddenScopesCannotBeGranted(): void
    {
        $this->assertSame([AgentScopes::DRAFTS_CREATE], AgentScopes::sanitize(['social.posts.publish', 'social.schedule', 'admin.users.manage', 'db.read', AgentScopes::DRAFTS_CREATE, 'social.credentials.read']));
        $this->assertSame([], array_filter(AgentScopes::all(), static fn ($s) => preg_match('/publish|schedule|credential|admin|approve|db\./', $s)));
    }

    public function testRateLimitReturns429WithRetryAfterAndIsAudited(): void
    {
        $this->dbc()->table('ai_agents')->where('id', $this->agentId)->update(['rate_limit_per_minute' => 3]);
        for ($i = 0; $i < 3; $i++) {
            $this->req('get', 'capabilities')->assertStatus(200);
        }
        $res = $this->req('get', 'capabilities');
        $res->assertStatus(429);
        $this->assertSame('RATE_LIMITED', $this->json($res)['error']['code']);
        $this->assertGreaterThan(0, (int) $res->response()->getHeaderLine('Retry-After'));
        $this->assertSame(1, $this->audit('RATE_LIMITED'));
    }

    public function testRateLimitsAreIndependentPerAgent(): void
    {
        $this->dbc()->table('ai_agents')->where('id', $this->agentId)->update(['rate_limit_per_minute' => 1]);
        [, $other] = $this->makeAgent('Other');
        $this->req('get', 'capabilities')->assertStatus(200);
        $this->req('get', 'capabilities')->assertStatus(429);
        $this->req('get', 'capabilities', null, $other)->assertStatus(200);
    }

    // ── draft creation ───────────────────────────────────────────────────────
    public function testCreateDraftIsDraftOnlyOriginAgentAndNeverScheduled(): void
    {
        $res = $this->req('post', 'social/posts/drafts', $this->draft(['platform' => 'both', 'media_ids' => [$this->media()], 'proposed_publication_at' => gmdate('c', time() + 86400)]));
        $res->assertStatus(201);
        $d = $this->json($res)['data']['post'];
        $this->assertSame('draft', $d['status']);
        $this->assertSame('agent', $d['origin']);
        $this->assertFalse($d['can_be_published']);
        $row = $this->dbc()->table('social_posts')->where('id', $d['id'])->get()->getRowArray();
        $this->assertSame('draft', $row['status']);
        $this->assertSame('agent', $row['origin']);
        $this->assertSame((string) $this->agentId, (string) $row['agent_id']);
        $this->assertNull($row['scheduled_at'], 'a proposed time is only stored as a suggestion');
        $this->assertNotNull($row['proposed_at']);
        $this->assertNull($row['approved_by']);
        $this->assertNull($row['created_by']);
        $this->assertNull($row['review_decision']);
        $this->assertSame(2, (int) $this->dbc()->table('social_post_platforms')->where('post_id', $d['id'])->countAllResults());
        $this->assertSame(1, (int) $this->dbc()->table('social_post_media')->where('post_id', $d['id'])->countAllResults());
        $this->assertSame(0, (int) $this->dbc()->table('social_scheduled_jobs')->where('post_id', $d['id'])->countAllResults(), 'no publish job can exist for a draft');
        $this->assertSame(1, $this->audit('DRAFT_CREATED'));
        $this->assertSame(1, (int) $this->dbc()->table('social_notifications')->where('event_type', 'agent_draft_created')->countAllResults(), 'admin is notified');
    }

    public function testBodyCannotSetWorkflowApprovalAccountOrScheduleFields(): void
    {
        foreach (['status' => 'published', 'approved_by' => 1, 'approved_at' => '2026-01-01', 'scheduled_at' => '2030-01-01 10:00:00', 'origin' => 'admin', 'agent_id' => 999, 'account_id' => 1, 'review_decision' => 'approved', 'created_by' => 1, 'publish_now' => true, 'submit_action' => 'publish_now', 'agent' => 'someone-else'] as $k => $v) {
            $res = $this->req('post', 'social/posts/drafts', $this->draft([$k => $v]));
            $res->assertStatus(422);
            $this->assertArrayHasKey($k, $this->json($res)['error']['details'], $k);
        }
        $this->assertSame(0, $this->agentPosts());
    }

    public function testPlatformAndFieldValidation(): void
    {
        $cases = [
            'platform'                 => $this->draft(['platform' => 'tiktok']),
            'title'                    => $this->draft(['title' => '   ']),
            'caption'                  => $this->draft(['caption' => '']),
            'caption.linkedin'         => $this->draft(['caption' => str_repeat('a', 3001)]),
            'hashtags'                 => $this->draft(['hashtags' => ['#ok', 'bad tag!']]),
            'cta'                      => $this->draft(['cta' => str_repeat('c', 121)]),
            'destination_url'          => $this->draft(['destination_url' => 'http://talenthubconsultant.com/x']),
            'media_ids'                => $this->draft(['platform' => 'instagram']),  // instagram needs media
            'proposed_publication_at'  => $this->draft(['proposed_publication_at' => '2001-01-01T00:00:00Z']),
            'campaign_id'              => $this->draft(['campaign_id' => 987654]),
        ];
        foreach ($cases as $field => $body) {
            $res = $this->req('post', 'social/posts/drafts', $body);
            $res->assertStatus(422);
            $this->assertArrayHasKey($field, $this->json($res)['error']['details'], $field);
        }
        $ig = $this->draft(['platform' => 'instagram', 'media_ids' => [$this->media()], 'caption' => str_repeat('b', 2201)]);
        $this->assertArrayHasKey('caption.instagram', $this->json($this->req('post', 'social/posts/drafts', $ig))['error']['details']);
        $this->assertSame(0, $this->agentPosts());
    }

    public function testDestinationUrlSecurity(): void
    {
        foreach (['https://evil.example/x', 'https://talenthubconsultant.com.evil.example/x', 'https://user:pw@talenthubconsultant.com/', 'https://talenthubconsultant.com:8443/', 'javascript:alert(1)', 'https://127.0.0.1/', 'ftp://talenthubconsultant.com/', 'https://nottalenthubconsultant.com/'] as $u) {
            $this->req('post', 'social/posts/drafts', $this->draft(['destination_url' => $u]))->assertStatus(422);
        }
        $this->req('post', 'social/posts/drafts', $this->draft(['destination_url' => 'https://www.worksphere.talenthubconsultant.com/']))->assertStatus(201);
        $this->assertSame(1, $this->agentPosts());
    }

    public function testMediaMustBeApprovedLibraryIdsNeverPathsOrUrls(): void
    {
        foreach ([['../../etc/passwd'], ['https://evil.example/a.jpg'], [$this->media(0)], [999999], 'notalist', [1.5]] as $m) {
            $res = $this->req('post', 'social/posts/drafts', $this->draft(['media_ids' => $m]));
            $res->assertStatus(422);
            $this->assertArrayHasKey('media_ids', $this->json($res)['error']['details']);
        }
        $this->assertSame(0, $this->agentPosts());
    }

    public function testHtmlIsStrippedAndSqlIsStoredInertly(): void
    {
        $res = $this->req('post', 'social/posts/drafts', $this->draft(['title' => "x'); DROP TABLE social_posts;-- <b>Bold</b>", 'caption' => '<script>alert(1)</script>Hello <img src=x onerror=alert(2)> world ' . bin2hex(random_bytes(3))]));
        $res->assertStatus(201);
        $id = $this->json($res)['data']['post']['id'];
        $row = $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray();
        $this->assertStringNotContainsString('<', $row['title'] . $row['base_content']);
        $this->assertStringContainsString('DROP TABLE social_posts', $row['title']);
        $this->assertTrue($this->dbc()->tableExists('social_posts'));
    }

    public function testBodyShapeErrors(): void
    {
        $this->req('post', 'social/posts/drafts', ['{not json'], null, [], true)->assertStatus(400);
        $this->req('post', 'social/posts/drafts', ['[1,2]'], null, [], true)->assertStatus(400);
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'text/plain'])->withBody('x')->call('post', self::BASE . 'social/posts/drafts')->assertStatus(415);
        $this->req('post', 'social/posts/drafts', [str_repeat('x', 70000)], null, [], true)->assertStatus(413);
        $this->assertSame(0, $this->agentPosts());
    }

    public function testDuplicateContentIsRejected(): void
    {
        $body = $this->draft(['caption' => 'Exactly the same caption for duplicate testing.']);
        $this->req('post', 'social/posts/drafts', $body)->assertStatus(201);
        $res = $this->req('post', 'social/posts/drafts', $body);
        $res->assertStatus(409);
        $this->assertSame('DUPLICATE_CONTENT', $this->json($res)['error']['code']);
        $this->req('post', 'social/posts/drafts', $this->draft(['caption' => "  EXACTLY   the same\ncaption for duplicate testing. "]))->assertStatus(409);
        $this->assertSame(1, $this->agentPosts());
    }

    public function testIdempotencyKeyMakesRetriesSafe(): void
    {
        $body = $this->draft();
        $h = ['Idempotency-Key' => 'weekly-2026-w45-idea-1'];
        $first = $this->req('post', 'social/posts/drafts', $body, null, $h);
        $first->assertStatus(201);
        $second = $this->req('post', 'social/posts/drafts', $body, null, $h);
        $second->assertStatus(200);
        $this->assertTrue($this->json($second)['data']['replayed']);
        $this->assertSame($this->json($first)['data']['post']['id'], $this->json($second)['data']['post']['id']);
        $this->assertSame(1, $this->agentPosts(), 'exactly one post despite the retry');
        $reuse = $this->req('post', 'social/posts/drafts', $this->draft(['title' => 'Different']), null, $h);
        $reuse->assertStatus(422);
        $this->assertSame('IDEMPOTENCY_KEY_REUSED', $this->json($reuse)['error']['code']);
        $this->req('post', 'social/posts/drafts', $this->draft(), null, ['Idempotency-Key' => 'bad key!'])->assertStatus(400);
    }

    public function testDailyDraftLimit(): void
    {
        $this->dbc()->table('ai_agents')->where('id', $this->agentId)->update(['daily_draft_limit' => 2]);
        $this->req('post', 'social/posts/drafts', $this->draft())->assertStatus(201);
        $this->req('post', 'social/posts/drafts', $this->draft())->assertStatus(201);
        $res = $this->req('post', 'social/posts/drafts', $this->draft());
        $res->assertStatus(429);
        $this->assertSame('DAILY_DRAFT_LIMIT', $this->json($res)['error']['code']);
    }

    // ── approval workflow / no direct publishing ─────────────────────────────
    public function testSubmitForApprovalMovesOnlyOwnDraftAndOnlyOnce(): void
    {
        $id = $this->json($this->req('post', 'social/posts/drafts', $this->draft()))['data']['post']['id'];
        $res = $this->req('post', "social/posts/{$id}/submit-approval", []);
        $res->assertStatus(200);
        $this->assertSame('pending_approval', $this->json($res)['data']['post']['status']);
        $again = $this->req('post', "social/posts/{$id}/submit-approval", []);
        $again->assertStatus(409);
        $this->assertSame('INVALID_STATE', $this->json($again)['error']['code']);
        $this->assertSame(1, $this->audit('DRAFT_SUBMITTED'));
        $this->assertSame('pending_approval', $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray()['status']);
    }

    public function testAgentsCannotSeeOrTouchOtherAgentsDraftsOrAdminPosts(): void
    {
        $id = $this->json($this->req('post', 'social/posts/drafts', $this->draft()))['data']['post']['id'];
        [, $other] = $this->makeAgent('Other agent');
        $this->req('get', "social/posts/{$id}", null, $other)->assertStatus(404);
        $this->req('post', "social/posts/{$id}/submit-approval", [], $other)->assertStatus(404);
        $this->assertSame('draft', $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray()['status']);
        $this->dbc()->table('social_posts')->insert(['title' => 'admin post', 'base_content' => 'secret plan', 'status' => 'draft', 'primary_platform' => 'linkedin', 'created_at' => date('Y-m-d H:i:s')]);
        $adminPost = (int) $this->dbc()->insertID();
        $res = $this->req('get', "social/posts/{$adminPost}");
        $res->assertStatus(404);
        $this->assertStringNotContainsString('secret plan', (string) $res->getJSON());
        $this->req('post', "social/posts/{$adminPost}/submit-approval", [])->assertStatus(404);
        $this->assertSame('draft', $this->dbc()->table('social_posts')->where('id', $adminPost)->get()->getRowArray()['status']);
    }

    public function testThereIsNoRouteToPublishScheduleApproveOrEditViaTheApi(): void
    {
        $id = $this->json($this->req('post', 'social/posts/drafts', $this->draft()))['data']['post']['id'];
        foreach ([['post', "social/posts/{$id}/publish"], ['post', "social/posts/{$id}/approve"], ['post', "social/posts/{$id}/schedule"], ['put', "social/posts/{$id}"], ['patch', "social/posts/{$id}"], ['delete', "social/posts/{$id}"], ['post', 'social/accounts'], ['get', 'social/credentials'], ['post', 'agents'], ['get', 'users']] as [$m, $p]) {
            try {
                $res = $this->req($m, $p, $m === 'get' ? null : []);
                $this->assertContains($res->response()->getStatusCode(), [404, 405], "$m $p must not exist");
            } catch (\CodeIgniter\Exceptions\PageNotFoundException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame('draft', $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray()['status']);
        $this->assertSame(0, (int) $this->dbc()->table('social_scheduled_jobs')->where('post_id', $id)->countAllResults());
    }

    public function testPublishingEngineRefusesUnapprovedAgentContent(): void
    {
        $id = $this->json($this->req('post', 'social/posts/drafts', $this->draft()))['data']['post']['id'];
        $engine = new \App\Services\Social\SocialPublishingEngine($this->dbc());
        foreach (['draft', 'pending_approval', 'scheduled', 'failed', 'approved'] as $status) {
            // even if someone forces the status, without a recorded human approval the engine refuses
            $this->dbc()->table('social_posts')->where('id', $id)->update(['status' => $status]);
            $r = $engine->publishPost($id, 1);
            $this->assertFalse($r['success'], $status);
            $this->assertSame('blocked_unapproved', $r['status'], $status);
            $this->assertSame($status, $this->dbc()->table('social_posts')->where('id', $id)->get()->getRowArray()['status'], 'engine must not alter a blocked post');
        }
        $this->assertSame(0, (int) $this->dbc()->table('social_publish_attempts')->where('post_id', $id)->countAllResults());
        // review_decision='approved' without an approver is still refused
        $this->dbc()->table('social_posts')->where('id', $id)->update(['review_decision' => 'approved', 'approved_by' => null]);
        $this->assertSame('blocked_unapproved', $engine->publishPost($id, 1)['status']);
    }

    public function testApprovalIsReflectedToTheAgentButItCannotPublish(): void
    {
        $id = $this->json($this->req('post', 'social/posts/drafts', $this->draft()))['data']['post']['id'];
        $this->req('post', "social/posts/{$id}/submit-approval", []);
        $this->assertTrue((new \App\Services\Agent\AgentApprovalService($this->dbc()))->approve($id, 5, 'Looks good'));
        $d = $this->json($this->req('get', "social/posts/{$id}"))['data']['post'];
        $this->assertSame('approved', $d['status']);
        $this->assertSame('approved', $d['review']['decision']);
        $this->assertTrue($d['can_be_published'], 'informational: a human may now schedule it');
        $this->assertStringNotContainsString('approved_by', (string) json_encode($d), 'reviewer identity is not exposed');
        $this->req('post', "social/posts/{$id}/submit-approval", [])->assertStatus(409);
        $this->assertSame(0, (int) $this->dbc()->table('social_scheduled_jobs')->where('post_id', $id)->countAllResults(), 'approval alone schedules nothing');
    }

    // ── read endpoints ───────────────────────────────────────────────────────
    public function testAccountsEndpointNeverExposesTokensOrPlatformIds(): void
    {
        $db = $this->dbc();
        $db->table('social_accounts')->insert(['platform' => 'linkedin', 'account_name' => 'Acme Page', 'external_id' => 'URN-SENTINEL-1234', 'status' => 'active', 'profile_url' => 'https://p/x', 'created_at' => date('Y-m-d H:i:s')]);
        $aid = (int) $db->insertID();
        $db->table('social_credentials')->insert(['account_id' => $aid, 'platform' => 'linkedin', 'access_token_encrypted' => 'ACCESS-SENTINEL-9988', 'refresh_token_encrypted' => 'REFRESH-SENTINEL-7766', 'client_secret_encrypted' => 'SECRET-SENTINEL-5544', 'token_expires_at' => date('Y-m-d H:i:s', time() + 86400 * 90), 'created_at' => date('Y-m-d H:i:s')]);
        $res = $this->req('get', 'social/accounts');
        $res->assertStatus(200);
        $body = (string) $res->getJSON();
        foreach (['URN-SENTINEL-1234', 'ACCESS-SENTINEL-9988', 'REFRESH-SENTINEL-7766', 'SECRET-SENTINEL-5544', 'token_expires_at', 'profile'] as $s) {
            $this->assertStringNotContainsString($s, $body);
        }
        $a = $this->json($res)['data']['accounts'][0];
        $this->assertSame('ok', $a['token_health']);
        $this->assertSame('Acme Page', $a['name']);
    }

    public function testGuidelinesTemplatesCalendarAndAnalyticsRespond(): void
    {
        $g = $this->json($this->req('get', 'social/content-guidelines'))['data'];
        $this->assertSame(3000, $g['platforms']['linkedin']['max_caption_chars']);
        $this->assertSame(2200, $g['platforms']['instagram']['max_caption_chars']);
        $this->dbc()->table('social_content_templates')->insert(['title' => 'T1', 'category' => 'hiring', 'platform' => 'all', 'caption_template' => 'c', 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        $this->dbc()->table('social_content_templates')->insert(['title' => 'Inactive', 'category' => 'hiring', 'platform' => 'all', 'caption_template' => 'c', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        $titles = array_column($this->json($this->req('get', 'social/templates'))['data']['templates'], 'title');
        $this->assertContains('T1', $titles);
        $this->assertNotContains('Inactive', $titles);
        $this->req('get', 'social/calendar')->assertStatus(200);
        $this->req('get', 'social/calendar?from=nonsense')->assertStatus(422);
        $a = $this->json($this->req('get', 'social/analytics'))['data'];
        $this->assertSame(30, $a['window_days']);
        $this->assertStringNotContainsString('token', strtolower(json_encode($a)));
    }

    public function testEveryRequestIsAuditedWithoutBodiesAndQueryStringsAreDropped(): void
    {
        $this->req('get', 'social/calendar?from=2026-01-01&secret=abc')->assertStatus(200);
        $row = $this->dbc()->table('ai_agent_audit')->where('event', 'REQUEST')->get()->getRowArray();
        $this->assertSame('GET', $row['method']);
        $this->assertStringNotContainsString('secret', (string) $row['path']);
        $this->assertSame((string) $this->agentId, (string) $row['agent_id']);
        $this->assertSame(12, strlen((string) $row['token_prefix']));
        $this->assertSame('200', (string) $row['status_code']);
        $this->assertNotEmpty($row['request_id']);
        $this->assertNotNull($this->dbc()->table('ai_agents')->where('id', $this->agentId)->get()->getRowArray()['last_access_at']);
    }

    public function testApiDoesNotAcceptSessionCookiesAsAuthentication(): void
    {
        $res = $this->withSession(['admin_user' => ['id' => 1, 'username' => 'admin', 'roles' => [['slug' => 'super_admin']], 'permissions' => []]])
            ->withHeaders(['Content-Type' => 'application/json'])->call('get', self::BASE . 'capabilities');
        $res->assertStatus(401);
    }
}
