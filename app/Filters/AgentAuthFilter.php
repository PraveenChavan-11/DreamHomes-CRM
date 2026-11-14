<?php

namespace App\Filters;

use App\Services\Agent\AgentAuditLogger;
use App\Services\Agent\AgentScopes;
use App\Services\Agent\AgentTokenService;
use App\Services\EnvironmentSafetyService;
use App\Services\RateLimiterService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Machine-to-machine authentication for /api/v1/integrations/agents/*.  Usage: 'filter' => 'agentauth:<scope>'
 *
 * Order of checks (all fail closed, none uses the admin session or cookies):
 *  1 HTTPS (production)  2 failed-auth lockout per IP  3 bearer token  4 IP allowlist  5 per-agent rate limit
 *  6 scope  7 request size / content type.  Every refusal is written to ai_agent_audit; secrets never are.
 */
class AgentAuthFilter implements FilterInterface
{
    public const MAX_BODY = 65536;
    public const FAIL_LIMIT = 15;     // failed auth attempts per IP …
    public const FAIL_WINDOW = 600;   // … per 10 minutes

    /** Request-scoped state handed to the controller (PHP is share-nothing per request; reset for tests). */
    public static ?array $context = null;

    public function before(RequestInterface $request, $arguments = null)
    {
        self::$context = null;
        $requestId = bin2hex(random_bytes(8));
        $limiter   = new RateLimiterService();
        $ip        = $limiter->getSafeClientIp();
        $method    = strtoupper($request->getMethod());
        $path      = '/' . ltrim($request->getUri()->getPath(), '/');
        $log       = new AgentAuditLogger();
        $base      = ['method' => $method, 'path' => $path, 'ip' => $ip, 'request_id' => $requestId];
        $deny      = fn (string $event, ?int $agentId, int $status, string $code, string $msg, array $extra = [], array $headers = []) =>
            $this->deny($log, $event, $agentId, $base + $extra, $status, $code, $msg, $requestId, $headers);

        if ($request->getHeaderLine('X-Forwarded-Proto') !== '' || $request->getUri()->getScheme() !== '') {
            $https = $request->getUri()->getScheme() === 'https' || strtolower($request->getHeaderLine('X-Forwarded-Proto')) === 'https';
            if (!$https && EnvironmentSafetyService::isProduction()) {
                return $deny('HTTPS_REQUIRED', null, 400, 'HTTPS_REQUIRED', 'This API is only available over HTTPS.');
            }
        }

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('ai_agents') || !$db->tableExists('ai_agent_tokens')) {
                return $deny('NOT_CONFIGURED', null, 503, 'NOT_CONFIGURED', 'The agent API is not enabled on this environment.');
            }

            // 2. brute-force lockout (peek only; failures are counted below)
            if ($this->failedAttempts($db, $ip) >= self::FAIL_LIMIT) {
                return $deny('AUTH_LOCKED_OUT', null, 429, 'TOO_MANY_FAILED_AUTH', 'Too many failed authentication attempts. Try again later.', [], ['Retry-After' => (string) self::FAIL_WINDOW]);
            }

            // 3. bearer token
            $header = $request->getHeaderLine('Authorization');
            $bearer = stripos($header, 'Bearer ') === 0 ? substr($header, 7) : '';
            $auth   = (new AgentTokenService($db))->authenticate($bearer);
            if (!$auth['ok']) {
                $limiter->hit('agent_authfail:' . $ip, self::FAIL_LIMIT, self::FAIL_WINDOW);
                $map = [
                    'INVALID_TOKEN' => [401, 'Invalid or missing credentials.'],
                    'TOKEN_EXPIRED' => [401, 'This token has expired. Ask an administrator to rotate it.'],
                    'TOKEN_REVOKED' => [401, 'This token has been revoked.'],
                    'AGENT_DISABLED' => [403, 'This agent is disabled.'],
                ];
                [$st, $msg] = $map[$auth['code']];
                return $deny('AUTH_FAILED', $auth['agent_id'] ?? null, $st, $auth['code'], $msg, ['token_prefix' => $auth['prefix'] ?? null, 'detail' => $auth['code']], $st === 401 ? ['WWW-Authenticate' => 'Bearer'] : []);
            }
            $agent = $auth['agent'];
            $token = $auth['token'];
            $base += ['token_prefix' => $auth['prefix']];
            $deny = fn (string $event, ?int $agentId, int $status, string $code, string $msg, array $extra = [], array $headers = []) =>
                $this->deny($log, $event, $agentId, $base + $extra, $status, $code, $msg, $requestId, $headers);

            // 4. optional IP allowlist
            $allow = array_filter(array_map('trim', explode(',', (string) $agent['allowed_ips'])));
            if ($allow && !in_array($ip, $allow, true)) {
                return $deny('IP_NOT_ALLOWED', (int) $agent['id'], 403, 'IP_NOT_ALLOWED', 'This agent may not call the API from this address.');
            }

            // 5. per-agent rate limit
            $rl = $limiter->hit('agent:' . (int) $agent['id'], (int) $agent['rate_limit_per_minute'], 60);
            if (!$rl['allowed']) {
                return $deny('RATE_LIMITED', (int) $agent['id'], 429, 'RATE_LIMITED', 'Rate limit exceeded.', [], ['Retry-After' => (string) $rl['retry_after'], 'X-RateLimit-Limit' => (string) (int) $agent['rate_limit_per_minute'], 'X-RateLimit-Remaining' => '0']);
            }

            // 6. scope
            $required = $arguments[0] ?? null;
            if ($required !== null && !in_array($required, AgentScopes::parse($agent['scopes']), true)) {
                return $deny('PERMISSION_DENIED', (int) $agent['id'], 403, 'INSUFFICIENT_SCOPE', 'This agent is not permitted to perform this operation.', ['detail' => 'missing ' . $required]);
            }

            // 7. payload limits
            if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                if ((int) $request->getHeaderLine('Content-Length') > self::MAX_BODY || strlen((string) $request->getBody()) > self::MAX_BODY) {
                    return $deny('PAYLOAD_TOO_LARGE', (int) $agent['id'], 413, 'PAYLOAD_TOO_LARGE', 'Request body exceeds 64 KB.');
                }
                if (stripos($request->getHeaderLine('Content-Type'), 'application/json') !== 0) {
                    return $deny('UNSUPPORTED_MEDIA_TYPE', (int) $agent['id'], 415, 'UNSUPPORTED_MEDIA_TYPE', 'Content-Type must be application/json.');
                }
            }

            (new AgentTokenService($db))->touch((int) $token['id'], (int) $agent['id'], $ip);
            self::$context = ['agent' => $agent, 'token' => $token, 'request_id' => $requestId, 'ip' => $ip, 'rate_remaining' => $rl['remaining'], 'base' => $base];
        } catch (\Throwable $e) {
            log_message('error', 'AgentAuthFilter failure: ' . get_class($e));
            return $this->json(500, 'SERVER_ERROR', 'The request could not be processed.', $requestId);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (self::$context !== null) {
            $c = self::$context;
            $response->setHeader('X-Request-Id', $c['request_id'])->setHeader('X-RateLimit-Remaining', (string) $c['rate_remaining']);
            (new AgentAuditLogger())->log('REQUEST', (int) $c['agent']['id'], $c['base'] + ['status' => $response->getStatusCode()]);
        }
        $response->setHeader('Cache-Control', 'no-store')->setHeader('X-Content-Type-Options', 'nosniff');
        return $response;
    }

    private function deny(AgentAuditLogger $log, string $event, ?int $agentId, array $ctx, int $status, string $code, string $msg, string $requestId, array $headers): ResponseInterface
    {
        $log->log($event, $agentId, $ctx + ['status' => $status]);
        return $this->json($status, $code, $msg, $requestId, $headers);
    }

    private function json(int $status, string $code, string $msg, string $requestId, array $headers = []): ResponseInterface
    {
        $r = service('response')->setStatusCode($status)->setJSON(['success' => false, 'error' => ['code' => $code, 'message' => $msg], 'request_id' => $requestId])
            ->setHeader('X-Request-Id', $requestId)->setHeader('Cache-Control', 'no-store');
        foreach ($headers as $k => $v) {
            $r->setHeader($k, $v);
        }
        return $r;
    }

    private function failedAttempts(\CodeIgniter\Database\BaseConnection $db, string $ip): int
    {
        $row = $db->table('rate_limit_buckets')->select('hits')->where('key_hash', hash('sha256', 'agent_authfail:' . $ip))->where('reset_at >', time())->get()->getRowArray();
        return (int) ($row['hits'] ?? 0);
    }
}
