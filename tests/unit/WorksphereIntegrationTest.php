<?php

namespace Tests\Unit;

use App\Services\IntegrationClientService;
use App\Services\RateLimiterService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Libraries\WorksphereSigner as Signer;

/**
 * WorkSphere signed event receiver. Uses a random per-run encryption key and a random client secret
 * (nothing secret is stored in the repo). Test rows use the reserved domain @ws-test.invalid.
 * Run against a scratch DB: set database.tests.database=talenthub_test_crm in the environment.
 *
 * @internal
 */
final class WorksphereIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const URL = 'api/v1/integrations/worksphere/events';
    private string $keyId;
    private string $secret;

    private function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    protected function setUp(): void
    {
        parent::setUp();
        IntegrationClientService::useKeyForTesting(random_bytes(32));
        $this->keyId = 'wstest-' . bin2hex(random_bytes(4));
        $this->secret = (new IntegrationClientService())->createOrRotate($this->keyId, 'phpunit')['secret'];
        $this->db()->query('DELETE FROM rate_limit_buckets');
    }

    protected function tearDown(): void
    {
        $db = $this->db();
        $ids = array_column($db->query("SELECT id FROM it_leads WHERE email LIKE '%@ws-test.invalid'")->getResultArray(), 'id');
        if ($ids) {
            $in = implode(',', array_map('intval', $ids));
            $db->query("DELETE FROM email_outbox WHERE idempotency_key REGEXP '^crm-L(" . implode('|', array_map('intval', $ids)) . ")-'");
            $db->query("DELETE FROM it_leads WHERE id IN ({$in})");
        }
        $db->table('crm_integration_events')->where('key_id', $this->keyId)->delete();
        $db->table('crm_integration_nonces')->where('key_id', $this->keyId)->delete();
        $db->table('crm_integration_clients')->where('key_id', $this->keyId)->delete();
        IntegrationClientService::useKeyForTesting(null);
        parent::tearDown();
    }

    private function send(array $req)
    {
        return $this->withBody($req['body'])->withHeaders($req['headers'])->post(self::URL);
    }

    private function code($res): string
    {
        return (string) (json_decode($res->getJSON(), true)['code'] ?? '');
    }

    private function signed(string $type = 'registration_initiated', array $over = []): array
    {
        return Signer::build($this->secret, $this->keyId, Signer::event($type, $over));
    }

    private function leadByEmail(string $email): ?array
    {
        return $this->db()->table('it_leads')->where('email', $email)->get()->getRowArray();
    }

    private function countLeads(string $email): int
    {
        return $this->db()->table('it_leads')->where('email', $email)->countAllResults();
    }

    public function testValidSignedEventIsAcceptedAndCreatesLead(): void
    {
        $evt = Signer::event('registration_initiated', ['demo' => ['status' => 'pending'], 'context' => ['utm' => ['utm_source' => 'google']]]);
        $res = $this->send(Signer::build($this->secret, $this->keyId, $evt));
        $res->assertStatus(200);
        $this->assertSame('ACCEPTED', $this->code($res));

        $lead = $this->leadByEmail($evt['lead']['email']);
        $this->assertNotNull($lead);
        $this->assertSame('worksphere', $lead['source_system']);
        $this->assertSame('worksphere_demo', $lead['source']);
        $this->assertSame('worksphere', $lead['product_slug']);
        $this->assertSame($evt['correlation_id'], $lead['external_ref']);
        $this->assertSame('new', $lead['status']);
        $this->assertSame('google', $lead['utm_source']);
        $this->assertSame('accepted', $this->db()->table('crm_integration_events')->where('event_id', $evt['event_id'])->get()->getRowArray()['result']);
        $this->assertGreaterThan(0, $this->db()->table('it_lead_activities')->where('lead_id', $lead['id'])->where('type', 'integration')->countAllResults());
    }

    public function testDuplicateEventIdReturnsDuplicateAndKeepsOneLead(): void
    {
        $evt = Signer::event('registration_initiated');
        $this->send(Signer::build($this->secret, $this->keyId, $evt))->assertStatus(200);
        // Retry: same event id, fresh nonce/timestamp (a legitimate sender retry)
        $res = $this->send(Signer::build($this->secret, $this->keyId, $evt));
        $res->assertStatus(200);
        $this->assertSame('DUPLICATE', $this->code($res));
        $this->assertSame(1, $this->countLeads($evt['lead']['email']));
    }

    public function testReplayedNonceIsRejected(): void
    {
        $nonce = Signer::nonce();
        $a = Signer::build($this->secret, $this->keyId, Signer::event('registration_initiated'), null, $nonce);
        $this->send($a)->assertStatus(200);
        $b = Signer::build($this->secret, $this->keyId, Signer::event('registration_initiated'), null, $nonce);
        $res = $this->send($b);
        $res->assertStatus(401);
        $this->assertSame('REPLAY', $this->code($res));
        // exact captured request replayed
        $this->assertSame('REPLAY', $this->code($this->send($a)));
    }

    public function testStaleAndFutureTimestampsAreRejected(): void
    {
        foreach ([time() - 400, time() + 400] as $ts) {
            $res = $this->send(Signer::build($this->secret, $this->keyId, Signer::event(), (string) $ts));
            $res->assertStatus(401);
            $this->assertSame('STALE_TIMESTAMP', $this->code($res));
        }
        $res = $this->send(Signer::build($this->secret, $this->keyId, Signer::event(), (string) (time() - 250)));
        $res->assertStatus(200); // inside the +-300 s window
    }

    public function testTamperedBodyIsRejected(): void
    {
        $req = $this->signed();
        $req['body'] = str_replace('Asha Tester', 'Mallory', $req['body']);
        $res = $this->send($req);
        $res->assertStatus(401);
        $this->assertSame('BAD_SIGNATURE', $this->code($res));
    }

    public function testWrongSecretWrongKeyAndInactiveClientAreRejected(): void
    {
        $evt = Signer::event();
        $res = $this->send(Signer::build(bin2hex(random_bytes(32)), $this->keyId, $evt));
        $res->assertStatus(401);
        $this->assertSame('BAD_SIGNATURE', $this->code($res));

        $res = $this->send(Signer::build($this->secret, 'no-such-key', Signer::event()));
        $res->assertStatus(401);
        $this->assertSame('UNKNOWN_KEY', $this->code($res));

        (new IntegrationClientService())->setActive($this->keyId, false);
        $res = $this->send($this->signed());
        $res->assertStatus(401);
        $this->assertSame('UNKNOWN_KEY', $this->code($res));
        $this->assertNull($this->leadByEmail($evt['lead']['email']));
    }

    public function testOversizedBodyReturns413(): void
    {
        $evt = Signer::event('registration_initiated', ['context' => ['message' => str_repeat('A', 17000)]]);
        $res = $this->send(Signer::build($this->secret, $this->keyId, $evt));
        $res->assertStatus(413);
        $this->assertSame('TOO_LARGE', $this->code($res));
    }

    public function testUnknownEventTypeAndInvalidEmailReturn422(): void
    {
        $evt = Signer::event('registration_initiated');
        $evt['event_type'] = 'account_deleted';
        $res = $this->send(Signer::build($this->secret, $this->keyId, $evt));
        $res->assertStatus(422);
        $this->assertSame('INVALID_EVENT', $this->code($res));

        $res = $this->send(Signer::build($this->secret, $this->keyId, Signer::event('registration_initiated', ['lead' => ['email' => 'not-an-email']])));
        $res->assertStatus(422);
    }

    public function testMalformedJsonReturns400(): void
    {
        $eid = Signer::uuid();
        $res = $this->send(Signer::buildRaw($this->secret, $this->keyId, '{not json', $eid));
        $res->assertStatus(400);
        $this->assertSame('BAD_REQUEST', $this->code($res));
    }

    public function testSecretsAndUnknownFieldsAreDroppedAndNeverPersisted(): void
    {
        $evt = Signer::event('registration_initiated');
        $evt['password'] = 'Sup3rSecretPw!';
        $evt['lead']['password_hash'] = 'pbkdf2$leakme';
        $evt['context']['token'] = 'tok_leak_123';
        $evt['context']['verification_link'] = 'https://ws.example/verify?t=leak';
        $evt['extra'] = ['jwt' => 'eyJleak'];
        $this->send(Signer::build($this->secret, $this->keyId, $evt))->assertStatus(200);

        $payload = (string) $this->db()->table('crm_integration_events')->where('event_id', $evt['event_id'])->get()->getRowArray()['payload_json'];
        foreach (['Sup3rSecretPw', 'leakme', 'tok_leak', 'verify?t=leak', 'eyJleak', 'password', 'token', 'jwt'] as $needle) {
            $this->assertStringNotContainsString($needle, $payload);
        }
        $lead = json_encode($this->leadByEmail($evt['lead']['email']));
        $this->assertStringNotContainsString('leak', $lead);
    }

    public function testSameEmailNewCorrelationUpdatesLeadInsteadOfDuplicating(): void
    {
        $first = Signer::event('registration_initiated');
        $this->send(Signer::build($this->secret, $this->keyId, $first))->assertStatus(200);
        $second = Signer::event('registration_initiated', ['lead' => ['email' => strtoupper($first['lead']['email'])]]);
        $this->send(Signer::build($this->secret, $this->keyId, $second))->assertStatus(200);

        $this->assertSame(1, $this->countLeads($first['lead']['email']));
        $lead = $this->leadByEmail($first['lead']['email']);
        $this->assertSame(2, (int) $lead['enquiry_count']);
        $this->assertSame($first['correlation_id'], $lead['external_ref'], 'original correlation retained');
    }

    public function testLifecycleEventsMapToStatusAndPriority(): void
    {
        $email = 'life.' . bin2hex(random_bytes(3)) . '@ws-test.invalid';
        $corr = Signer::uuid();
        $mk = fn (string $t, array $o = []) => Signer::build($this->secret, $this->keyId, Signer::event($t, ['correlation_id' => $corr, 'lead' => ['email' => $email]] + $o));
        $this->send($mk('registration_initiated'))->assertStatus(200);
        $this->send($mk('email_verified'))->assertStatus(200);
        $lead = $this->leadByEmail($email);
        $this->assertSame('new', $lead['status']);
        $this->assertNotEmpty($lead['verified_email_at']);

        $this->send($mk('provisioning_failed', ['context' => ['failed_stage' => 'create_tenant']]))->assertStatus(200);
        $this->assertSame('high', $this->leadByEmail($email)['priority']);

        $this->send($mk('demo_activated', ['demo' => ['status' => 'active', 'started_at' => '2026-11-08T10:00:00Z', 'expires_at' => '2026-11-22T10:00:00Z']]))->assertStatus(200);
        $lead = $this->leadByEmail($email);
        $this->assertSame('demo_active', $lead['status']);
        $this->assertNotEmpty($lead['demo_expires_at']);

        $this->send($mk('converted_to_paid'))->assertStatus(200);
        $this->assertSame('won', $this->leadByEmail($email)['status']);
        $this->assertSame(1, $this->countLeads($email));
    }

    public function testStaffSetStatusIsNotOverriddenByLaterEvents(): void
    {
        $email = 'staff.' . bin2hex(random_bytes(3)) . '@ws-test.invalid';
        $corr = Signer::uuid();
        $mk = fn (string $t) => Signer::build($this->secret, $this->keyId, Signer::event($t, ['correlation_id' => $corr, 'lead' => ['email' => $email]]));
        $this->send($mk('registration_initiated'))->assertStatus(200);
        $id = (int) $this->leadByEmail($email)['id'];
        $svc = new \App\Services\ItLeadService();
        $this->assertTrue($svc->changeStatus($id, 'proposal_sent', 1));
        $this->send($mk('demo_activated'))->assertStatus(200);
        $this->assertSame('proposal_sent', $this->leadByEmail($email)['status'], 'event must not move the lead backwards');
        $this->assertTrue($svc->changeStatus($id, 'on_hold', 1));
        $this->send($mk('provisioning_succeeded'))->assertStatus(200);
        $this->assertSame('on_hold', $this->leadByEmail($email)['status']);
        $this->assertGreaterThanOrEqual(3, $this->db()->table('it_lead_activities')->where('lead_id', $id)->where('type', 'integration')->countAllResults(), 'events still logged');
    }

    public function testNotificationQueuedOncePerLeadEvent(): void
    {
        $evt = Signer::event('registration_initiated');
        $this->send(Signer::build($this->secret, $this->keyId, $evt))->assertStatus(200);
        $this->send(Signer::build($this->secret, $this->keyId, $evt))->assertStatus(200); // duplicate delivery
        $id = (int) $this->leadByEmail($evt['lead']['email'])['id'];
        $n = $this->db()->table('email_outbox')->like('idempotency_key', "crm-L{$id}-ws_registration_initiated-", 'after')->countAllResults();
        $this->assertSame(1, $n);
        $row = $this->db()->table('email_outbox')->like('idempotency_key', "crm-L{$id}-", 'after')->get()->getRowArray();
        $this->assertStringNotContainsString($this->secret, $row['body_html'] . $row['body_text'] . $row['subject']);
        $this->assertStringNotContainsString("\n", $row['subject']);
    }

    public function testRateLimitReturns429(): void
    {
        $limiter = new RateLimiterService();
        for ($i = 0; $i < 121; $i++) {
            $limiter->hit('wsint_key_' . $this->keyId, 120, 60);
        }
        $res = $this->send($this->signed());
        $res->assertStatus(429);
        $this->assertSame('RATE_LIMITED', $this->code($res));
    }

    public function testFailsClosedWithoutEncryptionKey(): void
    {
        IntegrationClientService::useKeyForTesting('');
        $evt = Signer::event();
        $res = $this->send(Signer::build($this->secret, $this->keyId, $evt));
        $res->assertStatus(500);
        $this->assertSame('SERVER_MISCONFIGURED', $this->code($res));
        // an unsigned call must not be accepted either
        $res = $this->withBody(json_encode($evt))->withHeaders(['Content-Type' => 'application/json'])->post(self::URL);
        $this->assertContains($res->response()->getStatusCode(), [500]);
        $this->assertNull($this->leadByEmail($evt['lead']['email']));
    }

    public function testUnsignedCallIsRejectedWhenConfigured(): void
    {
        $evt = Signer::event();
        $res = $this->withBody(json_encode($evt))->withHeaders(['Content-Type' => 'application/json'])->post(self::URL);
        $res->assertStatus(401);
        $this->assertNull($this->leadByEmail($evt['lead']['email']));
    }

    public function testRotationKeepsPreviousSecretValidDuringGraceWindow(): void
    {
        $old = $this->secret;
        $new = (new IntegrationClientService())->createOrRotate($this->keyId)['secret'];
        $this->assertNotSame($old, $new);
        $this->send(Signer::build($old, $this->keyId, Signer::event()))->assertStatus(200);
        $this->send(Signer::build($new, $this->keyId, Signer::event()))->assertStatus(200);
        $this->db()->table('crm_integration_clients')->where('key_id', $this->keyId)->update(['prev_expires_at' => date('Y-m-d H:i:s', time() - 5)]);
        $this->send(Signer::build($old, $this->keyId, Signer::event()))->assertStatus(401);
    }
}
