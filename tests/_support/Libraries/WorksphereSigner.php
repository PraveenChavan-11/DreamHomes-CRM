<?php

namespace Tests\Support\Libraries;

/**
 * Reusable signer for the TalentHub <- WorkSphere event contract.
 * Independent of app code (re-implements the signing string) so it also cross-checks the receiver.
 *
 *   $req = WorksphereSigner::build($secret, 'worksphere-prod', WorksphereSigner::event('registration_initiated'));
 *   // $req['headers'] => X-TH-* headers, $req['body'] => raw JSON string to POST
 */
final class WorksphereSigner
{
    public static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    public static function nonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    /** A valid schema_version 1 envelope; $over is merged recursively. */
    public static function event(string $type = 'registration_initiated', array $over = []): array
    {
        $base = [
            'schema_version' => 1, 'event_id' => self::uuid(), 'event_type' => $type,
            'occurred_at' => gmdate('Y-m-d\TH:i:s\Z'), 'source' => 'worksphere', 'correlation_id' => self::uuid(),
            'lead' => ['organization_name' => 'Acme Test Pvt Ltd', 'contact_name' => 'Asha Tester', 'email' => 'asha.' . bin2hex(random_bytes(3)) . '@ws-test.invalid',
                'phone' => '+91 98765 43210', 'employee_size' => '51-200', 'industry' => 'Manufacturing', 'organization_type' => 'Private', 'country' => 'India', 'state' => 'Maharashtra', 'city' => 'Pune'],
            'demo' => ['status' => '', 'started_at' => null, 'expires_at' => null, 'plan' => ''],
            'context' => ['message' => '', 'interest' => 'HRMS', 'failed_stage' => '', 'days_remaining' => null, 'utm' => []],
        ];
        return array_replace_recursive($base, $over);
    }

    /** @return array{headers:array<string,string>,body:string,event_id:string,nonce:string,timestamp:string} */
    public static function build(string $secret, string $keyId, array $event, ?string $timestamp = null, ?string $nonce = null): array
    {
        $body = json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::buildRaw($secret, $keyId, $body, (string) $event['event_id'], $timestamp, $nonce);
    }

    public static function buildRaw(string $secret, string $keyId, string $body, string $eventId, ?string $timestamp = null, ?string $nonce = null): array
    {
        $timestamp ??= (string) time();
        $nonce ??= self::nonce();
        $sig = 'v1=' . hash_hmac('sha256', "v1\n{$timestamp}\n{$nonce}\n{$eventId}\n" . hash('sha256', $body), $secret);
        return [
            'headers' => ['Content-Type' => 'application/json', 'X-TH-Key-Id' => $keyId, 'X-TH-Timestamp' => $timestamp,
                'X-TH-Nonce' => $nonce, 'X-TH-Event-Id' => $eventId, 'X-TH-Signature' => $sig],
            'body' => $body, 'event_id' => $eventId, 'nonce' => $nonce, 'timestamp' => $timestamp,
        ];
    }
}
