<?php

namespace App\Services\Agent;

use CodeIgniter\Database\BaseConnection;

/**
 * Issues, verifies, rotates and revokes agent bearer tokens.
 *
 * Token format:  thk_<12 hex prefix>_<43 char base64url secret>   (256 bits of entropy in the secret)
 *  - Only SHA-256(token) and the public prefix are stored; the plaintext exists only in the response that issues it.
 *  - Verification is constant-time (hash_equals) and does the same work whether or not the prefix exists.
 *  - Specific failure reasons (expired / revoked / disabled) are revealed ONLY after the secret has matched, so an
 *    attacker who does not hold the credential learns nothing but "invalid".
 *  - Expiry is mandatory (default 90 days, max 365). Rotation revokes the old token immediately.
 */
class AgentTokenService
{
    public const DEFAULT_TTL_DAYS = 90;
    public const MAX_TTL_DAYS     = 365;

    private const PATTERN = '/^thk_([a-f0-9]{12})_([A-Za-z0-9_-]{43})$/';

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** @return array{token:string,prefix:string,expires_at:string,token_id:int} plaintext token is returned ONCE. */
    public function issue(int $agentId, ?int $ttlDays = null, ?int $createdBy = null): array
    {
        $ttl     = max(1, min(self::MAX_TTL_DAYS, $ttlDays ?? self::DEFAULT_TTL_DAYS));
        $prefix  = bin2hex(random_bytes(6));
        $secret  = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token   = "thk_{$prefix}_{$secret}";
        $expires = date('Y-m-d H:i:s', time() + $ttl * 86400);

        $this->db->table('ai_agent_tokens')->insert([
            'agent_id'     => $agentId,
            'token_prefix' => $prefix,
            'token_hash'   => hash('sha256', $token),
            'expires_at'   => $expires,
            'created_by'   => $createdBy,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return ['token' => $token, 'prefix' => $prefix, 'expires_at' => $expires, 'token_id' => (int) $this->db->insertID()];
    }

    /** Issue a replacement and immediately revoke every other live token of the agent (single transaction). */
    public function rotate(int $agentId, ?int $ttlDays, ?int $adminId): array
    {
        $this->db->transStart();
        $this->revokeAll($agentId, $adminId, 'rotated');
        $new = $this->issue($agentId, $ttlDays, $adminId);
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Token rotation failed.');
        }
        return $new;
    }

    public function revokeAll(int $agentId, ?int $adminId, string $reason = 'revoked'): int
    {
        $this->db->table('ai_agent_tokens')->where('agent_id', $agentId)->where('revoked_at', null)->update([
            'revoked_at' => date('Y-m-d H:i:s'), 'revoked_by' => $adminId, 'revoke_reason' => substr($reason, 0, 60),
        ]);
        return $this->db->affectedRows();
    }

    /**
     * @return array{ok:bool,code:string,agent?:array,token?:array,prefix?:string}
     *   code: OK | INVALID_TOKEN | TOKEN_EXPIRED | TOKEN_REVOKED | AGENT_DISABLED
     */
    public function authenticate(?string $bearer): array
    {
        $bearer = trim((string) $bearer);
        $dummy  = hash('sha256', 'dummy-' . $bearer);
        if (strlen($bearer) > 120 || !preg_match(self::PATTERN, $bearer, $m)) {
            hash_equals($dummy, $dummy); // keep timing uniform
            return ['ok' => false, 'code' => 'INVALID_TOKEN'];
        }
        $row  = $this->db->table('ai_agent_tokens')->where('token_prefix', $m[1])->get()->getRowArray();
        $good = hash_equals($row['token_hash'] ?? $dummy, hash('sha256', $bearer));
        if (!$row || !$good) {
            return ['ok' => false, 'code' => 'INVALID_TOKEN', 'prefix' => $m[1]];
        }
        // From here the caller has proven possession of a real credential, so specific reasons are safe to return.
        if (!empty($row['revoked_at'])) {
            return ['ok' => false, 'code' => 'TOKEN_REVOKED', 'prefix' => $m[1], 'agent_id' => (int) $row['agent_id']];
        }
        if (strtotime($row['expires_at']) <= time()) {
            return ['ok' => false, 'code' => 'TOKEN_EXPIRED', 'prefix' => $m[1], 'agent_id' => (int) $row['agent_id']];
        }
        $agent = $this->db->table('ai_agents')->where('id', (int) $row['agent_id'])->get()->getRowArray();
        if (!$agent || $agent['status'] !== 'active') {
            return ['ok' => false, 'code' => 'AGENT_DISABLED', 'prefix' => $m[1], 'agent_id' => (int) $row['agent_id']];
        }
        return ['ok' => true, 'code' => 'OK', 'agent' => $agent, 'token' => $row, 'prefix' => $m[1]];
    }

    public function touch(int $tokenId, int $agentId, ?string $ip): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('ai_agent_tokens')->where('id', $tokenId)->update(['last_used_at' => $now, 'last_used_ip' => $ip ? substr($ip, 0, 45) : null]);
        $this->db->table('ai_agents')->where('id', $agentId)->update(['last_access_at' => $now]);
    }

    /** Active, unexpired token summary for the admin UI (never the token itself). */
    public function currentToken(int $agentId): ?array
    {
        return $this->db->table('ai_agent_tokens')->where('agent_id', $agentId)->where('revoked_at', null)
            ->orderBy('id', 'DESC')->limit(1)->get()->getRowArray() ?: null;
    }
}
