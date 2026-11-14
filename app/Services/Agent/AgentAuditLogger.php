<?php

namespace App\Services\Agent;

use App\Services\JobRunRecorder;
use CodeIgniter\Database\BaseConnection;

/**
 * Writes the external-agent access/security log. It stores only: agent id, token PREFIX (never the token),
 * event name, method, path (no query string), status, IP, request id and a short scrubbed detail.
 * Request bodies, headers and credentials are never logged.
 */
class AgentAuditLogger
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function log(string $event, ?int $agentId, array $ctx = []): void
    {
        try {
            $path = isset($ctx['path']) ? (string) strtok((string) $ctx['path'], '?') : null;
            $this->db->table('ai_agent_audit')->insert([
                'agent_id'     => $agentId,
                'token_prefix' => isset($ctx['token_prefix']) ? substr((string) $ctx['token_prefix'], 0, 12) : null,
                'event'        => substr($event, 0, 60),
                'method'       => isset($ctx['method']) ? substr(strtoupper((string) $ctx['method']), 0, 8) : null,
                'path'         => $path !== null ? substr($path, 0, 160) : null,
                'status_code'  => $ctx['status'] ?? null,
                'ip'           => isset($ctx['ip']) ? substr((string) $ctx['ip'], 0, 45) : null,
                'request_id'   => isset($ctx['request_id']) ? substr((string) $ctx['request_id'], 0, 16) : null,
                'detail'       => isset($ctx['detail']) ? JobRunRecorder::sanitize((string) $ctx['detail'], 250) : null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AgentAuditLogger failed: ' . get_class($e));
        }
    }
}
