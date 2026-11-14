<?php

namespace App\Controllers\Api;

use App\Services\CrmNotificationService;
use App\Services\IntegrationClientService;
use App\Services\IntegrationSignatureService as Sig;
use App\Services\RateLimiterService;
use App\Services\WorksphereLeadMapper;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * POST /api/v1/integrations/worksphere/events  (signed, machine-to-machine; no session, no CSRF).
 * Verification order (cheapest first): size, IP limit, config, key, key limit, timestamp, signature, nonce,
 * JSON/schema, idempotency, persist. See docs/WORKSPHERE_INTEGRATION_RECEIVER.md.
 * Responses never echo secrets, request bodies or internals.
 */
class WorksphereEventController extends Controller
{
    public const IP_LIMIT_PER_MIN  = 300;
    public const KEY_LIMIT_PER_MIN = 120;

    private function reply(int $status, string $code, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setHeader('Cache-Control', 'no-store')
            ->setJSON(['success' => $status < 300, 'status' => $status, 'code' => $code] + $extra);
    }

    public function receive(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $body = (string) $this->request->getBody();

        // 1. size (declared and actual) before any parsing
        $declared = (int) $this->request->getHeaderLine('Content-Length');
        if ($declared > Sig::MAX_BODY_BYTES || strlen($body) > Sig::MAX_BODY_BYTES) {
            return $this->reply(413, 'TOO_LARGE');
        }

        // 2. per-IP limit before key lookup
        $limiter = new RateLimiterService();
        $ip = $limiter->getSafeClientIp();
        $r = $limiter->hit('wsint_ip_' . $ip, self::IP_LIMIT_PER_MIN, 60);
        if (!$r['allowed']) {
            return $this->reply(429, 'RATE_LIMITED', ['retry_after' => $r['retry_after']])->setHeader('Retry-After', (string) $r['retry_after']);
        }

        // 3. fail closed: without an encryption key no client secret can be decrypted, so nothing can be authenticated
        if (!IntegrationClientService::isConfigured()) {
            log_message('error', 'WorkSphere receiver disabled: encryption.key is not configured.');
            return $this->reply(500, 'SERVER_MISCONFIGURED');
        }

        try {
            $clients = new IntegrationClientService($db);
            $h = fn (string $n) => trim($this->request->getHeaderLine($n));

            // 4. key id known and active
            $keyId = $h('X-TH-Key-Id');
            $client = IntegrationClientService::validKeyId($keyId) ? $clients->find($keyId) : null;
            if (!$client || (int) $client['is_active'] !== 1) {
                return $this->reply(401, 'UNKNOWN_KEY');
            }
            $r = $limiter->hit('wsint_key_' . $keyId, self::KEY_LIMIT_PER_MIN, 60);
            if (!$r['allowed']) {
                return $this->reply(429, 'RATE_LIMITED', ['retry_after' => $r['retry_after']])->setHeader('Retry-After', (string) $r['retry_after']);
            }

            $ts = $h('X-TH-Timestamp');
            $nonce = $h('X-TH-Nonce');
            $headerEventId = strtolower($h('X-TH-Event-Id'));
            $sigHeader = $h('X-TH-Signature');
            if ($ts === '' || $sigHeader === '' || !Sig::validNonce($nonce) || !str_starts_with($sigHeader, 'v1=') || strlen($sigHeader) > 80) {
                return $this->authFail($db, $keyId, 'BAD_SIGNATURE', 401);
            }
            if (!Sig::validEventId($headerEventId)) {
                return $this->reply(400, 'BAD_REQUEST');
            }

            // 5. timestamp window
            if (!Sig::timestampFresh($ts)) {
                return $this->authFail($db, $keyId, 'STALE_TIMESTAMP', 401);
            }

            // 6. signature (constant time; current secret, plus the previous one during a rotation grace window)
            $secrets = $clients->secretsFor($client);
            if (!$secrets || !Sig::verify($secrets, $ts, $nonce, $headerEventId, $body, $sigHeader)) {
                return $this->authFail($db, $keyId, 'BAD_SIGNATURE', 401);
            }

            // 7. replay protection (only signed requests consume nonces)
            $db->query('INSERT IGNORE INTO crm_integration_nonces (key_id, nonce, seen_at) VALUES (?,?,?)', [$keyId, $nonce, date('Y-m-d H:i:s')]);
            if ($db->affectedRows() < 1) {
                return $this->authFail($db, $keyId, 'REPLAY', 401);
            }
            if (mt_rand(1, 50) === 1) {
                $db->query('DELETE FROM crm_integration_nonces WHERE seen_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
                $db->query("DELETE FROM crm_integration_events WHERE event_id LIKE 'auth-%' AND received_at < ?", [date('Y-m-d H:i:s', time() - 7 * 86400)]);
            }
            $clients->touch($keyId);

            // 8. JSON + schema
            $json = json_decode($body, true, 8);
            if (!is_array($json) || array_is_list($json)) {
                return $this->reply(400, 'BAD_REQUEST');
            }
            if (strtolower((string) ($json['event_id'] ?? '')) !== $headerEventId) {
                return $this->reply(400, 'BAD_REQUEST');
            }
            $evt = WorksphereLeadMapper::sanitize($json);
            if ($evt === null) {
                $this->recordFailure($db, $headerEventId, $keyId, (string) ($json['event_type'] ?? 'unknown'), 'INVALID_EVENT');
                return $this->reply(422, 'INVALID_EVENT');
            }

            // 9. idempotency
            $existing = $db->table('crm_integration_events')->where('event_id', $headerEventId)->get()->getRowArray();
            if ($existing && $existing['result'] !== 'failed') {
                return $this->reply(200, 'DUPLICATE');
            }

            // 10. persist atomically
            $db->transBegin();
            try {
                $res = (new WorksphereLeadMapper($db))->apply($evt);
                $row = ['key_id' => $keyId, 'event_type' => $evt['event_type'], 'correlation_id' => $evt['correlation_id'],
                    'payload_json' => json_encode($evt, JSON_UNESCAPED_UNICODE), 'received_at' => date('Y-m-d H:i:s'),
                    'processed_at' => date('Y-m-d H:i:s'), 'lead_id' => $res['lead_id'], 'result' => 'accepted', 'error_code' => null];
                if ($existing) {
                    $db->table('crm_integration_events')->where('id', $existing['id'])->update($row);
                } else {
                    $db->table('crm_integration_events')->insert(['event_id' => $headerEventId] + $row);
                }
                $db->transCommit();
            } catch (\Throwable $e) {
                $db->transRollback();
                if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'uk_crm_evt_id')) {
                    return $this->reply(200, 'DUPLICATE'); // concurrent delivery of the same event won the race
                }
                log_message('error', 'WorkSphere event persist failed: ' . get_class($e));
                $this->recordFailure($db, $headerEventId, $keyId, $evt['event_type'], 'PERSIST_FAILED');
                return $this->reply(500, 'INTERNAL_ERROR');
            }

            // 11. notification is best-effort and happens after the lead is committed
            if (!empty($res['notify'])) {
                (new CrmNotificationService($db))->notify($res['notify'], $res['lead_id'], [], $headerEventId);
            }
            return $this->reply(200, 'ACCEPTED');
        } catch (\Throwable $e) {
            log_message('error', 'WorkSphere receiver error: ' . get_class($e));
            return $this->reply(500, 'INTERNAL_ERROR');
        }
    }

    private function authFail($db, string $keyId, string $code, int $status): ResponseInterface
    {
        try {
            $db->table('crm_integration_events')->insert(['event_id' => 'auth-' . bin2hex(random_bytes(12)), 'key_id' => $keyId,
                'event_type' => 'auth', 'received_at' => date('Y-m-d H:i:s'), 'result' => 'failed', 'error_code' => $code]);
        } catch (\Throwable $e) {
        }
        return $this->reply($status, $code);
    }

    private function recordFailure($db, string $eventId, string $keyId, string $type, string $code): void
    {
        try {
            $db->query('INSERT INTO crm_integration_events (event_id,key_id,event_type,received_at,result,error_code) VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE error_code = IF(result = \'failed\', VALUES(error_code), error_code)',
                [$eventId, $keyId, mb_substr(preg_replace('/[^a-z_]/', '', strtolower($type)) ?: 'unknown', 0, 40), date('Y-m-d H:i:s'), 'failed', $code]);
        } catch (\Throwable $e) {
        }
    }
}
