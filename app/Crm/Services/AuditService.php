<?php

namespace Crm\Services;

/**
 * Centralised, append-only event recorder.
 *  - workflow(): business timeline (customer/enquiry-linked)  -> crm_workflow_events
 *  - audit():    security/config/admin trail                  -> crm_audit_events
 * No update/delete methods exist by design. Secrets are stripped from payloads.
 */
final class AuditService
{
    private const SENSITIVE = ['password', 'token', 'secret', 'api_key', 'smtp_pass', 'otp', 'csrf'];

    public static function scrub(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }
        foreach ($data as $k => $v) {
            if (is_string($k) && self::isSensitive($k)) {
                $data[$k] = '[redacted]';
            } elseif (is_array($v)) {
                $data[$k] = self::scrub($v);
            }
        }

        return $data;
    }

    private static function isSensitive(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SENSITIVE as $s) {
            if (str_contains($key, $s)) {
                return true;
            }
        }

        return false;
    }

    private static function enc(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }

        return json_encode(self::scrub($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * @param array{customer_id?:?int,enquiry_id?:?int,entity_ref?:?string,prev?:mixed,new?:mixed,reason?:?string,meta?:mixed,user_id?:?int,created_at?:string,is_demo?:int,source?:string,correlation_id?:string} $o
     */
    public static function workflow(string $eventType, string $entityType, int $entityId, array $o = []): int
    {
        $db = Crm::db();
        $db->table('crm_workflow_events')->insert([
            'event_type'     => $eventType,
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'entity_ref'     => $o['entity_ref'] ?? null,
            'customer_id'    => $o['customer_id'] ?? null,
            'enquiry_id'     => $o['enquiry_id'] ?? null,
            'user_id'        => array_key_exists('user_id', $o) ? $o['user_id'] : Crm::actorId(),
            'prev_state'     => self::enc($o['prev'] ?? null),
            'new_state'      => self::enc($o['new'] ?? null),
            'reason'         => isset($o['reason']) ? mb_substr((string) $o['reason'], 0, 500) : null,
            'metadata'       => self::enc($o['meta'] ?? null),
            'correlation_id' => $o['correlation_id'] ?? Crm::correlationId(),
            'source'         => $o['source'] ?? Crm::source(),
            'is_demo'        => $o['is_demo'] ?? 0,
            'created_at'     => $o['created_at'] ?? Crm::now(),
        ]);

        return (int) $db->insertID();
    }

    /** @param array{prev?:mixed,new?:mixed,reason?:?string,meta?:mixed,user_id?:?int,ip?:?string} $o */
    public static function audit(string $eventType, string $entityType, string|int|null $entityId, array $o = []): int
    {
        $db = Crm::db();
        $db->table('crm_audit_events')->insert([
            'event_type'     => $eventType,
            'entity_type'    => $entityType,
            'entity_id'      => $entityId === null ? null : (string) $entityId,
            'user_id'        => array_key_exists('user_id', $o) ? $o['user_id'] : Crm::actorId(),
            'prev_state'     => self::enc($o['prev'] ?? null),
            'new_state'      => self::enc($o['new'] ?? null),
            'reason'         => isset($o['reason']) ? mb_substr((string) $o['reason'], 0, 500) : null,
            'metadata'       => self::enc($o['meta'] ?? null),
            'ip_address'     => $o['ip'] ?? (PHP_SAPI === 'cli' ? null : service('request')->getIPAddress()),
            'correlation_id' => Crm::correlationId(),
            'source'         => Crm::source(),
            'created_at'     => Crm::now(),
        ]);

        return (int) $db->insertID();
    }
}
