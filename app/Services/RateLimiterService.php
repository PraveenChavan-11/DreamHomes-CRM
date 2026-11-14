<?php

namespace App\Services;

use Config\App;
use Config\Database;

class RateLimiterService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Resolve the client IP safely without trusting arbitrary forwarded headers
     */
    public function getSafeClientIp(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Check if trusted reverse proxies are defined
        $appConfig = config(App::class);
        $trustedProxies = $appConfig->proxyIPs ?? [];

        if (empty($trustedProxies)) {
            return $remoteAddr;
        }

        // Only inspect proxy headers if REMOTE_ADDR is in trusted list
        $isTrusted = false;
        if (in_array('*', $trustedProxies, true) || in_array($remoteAddr, $trustedProxies, true)) {
            $isTrusted = true;
        } else {
            foreach ($trustedProxies as $proxyKey => $header) {
                if (is_string($proxyKey) && $this->ipMatchesSubnet($remoteAddr, $proxyKey)) {
                    $isTrusted = true;
                    break;
                }
            }
        }

        if ($isTrusted) {
            $cfIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
            if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }

            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
            if ($forwarded) {
                $ips = explode(',', $forwarded);
                $client = trim($ips[0]);
                if (filter_var($client, FILTER_VALIDATE_IP)) {
                    return $client;
                }
            }
        }

        return $remoteAddr;
    }

    private function ipMatchesSubnet(string $ip, string $subnet): bool
    {
        if (!str_contains($subnet, '/')) {
            return $ip === $subnet;
        }
        [$sub, $mask] = explode('/', $subnet);
        return (ip2long($ip) & ~((1 << (32 - (int) $mask)) - 1)) === ip2long($sub);
    }

    /**
     * Check and atomically increment rate limit hit counter.
     *
     * @param string $key Unique rate limit identifier
     * @param int $maxAttempts Maximum allowed requests in window
     * @param int $decaySeconds Window length in seconds
     * @return array [ 'allowed' => bool, 'hits' => int, 'remaining' => int, 'retry_after' => int ]
     */
    public function hit(string $key, int $maxAttempts = 5, int $decaySeconds = 600): array
    {
        $keyHash = hash('sha256', $key);
        $now = time();
        $resetAt = $now + $decaySeconds;

        try {
            // Atomic MySQL upsert
            $sql = "INSERT INTO rate_limit_buckets (key_hash, rate_key, hits, reset_at)
                    VALUES (?, ?, 1, ?)
                    ON DUPLICATE KEY UPDATE
                        hits = IF(reset_at < ?, 1, hits + 1),
                        reset_at = IF(reset_at < ?, VALUES(reset_at), reset_at)";

            $this->db->query($sql, [$keyHash, substr($key, 0, 190), $resetAt, $now, $now]);

            $row = $this->db->table('rate_limit_buckets')
                            ->select('hits, reset_at')
                            ->where('key_hash', $keyHash)
                            ->get()
                            ->getRowArray();

            $hits = (int) ($row['hits'] ?? 1);
            $bucketReset = (int) ($row['reset_at'] ?? $resetAt);
            $retryAfter = max(1, $bucketReset - $now);

            // Housekeeping: 1% chance to purge expired records
            if (mt_rand(1, 100) === 1) {
                $this->db->query("DELETE FROM rate_limit_buckets WHERE reset_at < ?", [$now - 3600]);
            }

            if ($hits > $maxAttempts) {
                return [
                    'allowed'     => false,
                    'hits'        => $hits,
                    'current'     => $hits,
                    'remaining'   => 0,
                    'retry_after' => $retryAfter,
                ];
            }

            return [
                'allowed'     => true,
                'hits'        => $hits,
                'current'     => $hits,
                'remaining'   => max(0, $maxAttempts - $hits),
                'retry_after' => $retryAfter,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'RateLimiterService error: ' . $e->getMessage());
            // Fail open gracefully if DB is unavailable, but log error
            return [
                'allowed'     => true,
                'hits'        => 1,
                'current'     => 1,
                'remaining'   => $maxAttempts - 1,
                'retry_after' => 0,
            ];
        }
    }
}
