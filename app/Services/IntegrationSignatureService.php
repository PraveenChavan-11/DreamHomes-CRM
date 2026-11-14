<?php

namespace App\Services;

/**
 * TalentHub x WorkSphere signing contract (docs/TALENTHUB_WORKSPHERE_INTEGRATION_ARCHITECTURE.md section 3).
 *   signing_string = "v1\n" . timestamp . "\n" . nonce . "\n" . event_id . "\n" . sha256_hex(raw_body)
 *   signature      = "v1=" . hex(HMAC-SHA256(secret, signing_string))
 */
class IntegrationSignatureService
{
    public const MAX_BODY_BYTES = 16384;
    public const TIMESTAMP_WINDOW = 300;

    public static function signingString(string $timestamp, string $nonce, string $eventId, string $rawBody): string
    {
        return "v1\n{$timestamp}\n{$nonce}\n{$eventId}\n" . hash('sha256', $rawBody);
    }

    public static function sign(string $secret, string $timestamp, string $nonce, string $eventId, string $rawBody): string
    {
        return 'v1=' . hash_hmac('sha256', self::signingString($timestamp, $nonce, $eventId, $rawBody), $secret);
    }

    /** Constant-time comparison against each acceptable secret. */
    public static function verify(array $secrets, string $timestamp, string $nonce, string $eventId, string $rawBody, string $signature): bool
    {
        $ok = false;
        foreach ($secrets as $secret) {
            // No short-circuit: evaluate every candidate so timing does not reveal which secret matched.
            $ok = hash_equals(self::sign($secret, $timestamp, $nonce, $eventId, $rawBody), $signature) || $ok;
        }
        return $ok;
    }

    public static function timestampFresh(string $timestamp, ?int $now = null): bool
    {
        if (!ctype_digit($timestamp) || strlen($timestamp) > 11) {
            return false;
        }
        return abs(($now ?? time()) - (int) $timestamp) <= self::TIMESTAMP_WINDOW;
    }

    public static function validNonce(string $nonce): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_\-]{16,64}$/', $nonce);
    }

    public static function validEventId(string $id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id);
    }
}
