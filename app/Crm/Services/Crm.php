<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

/** Tiny static kernel: DB handle, correlation id, current actor, clock. Keeps services testable without a DI container. */
final class Crm
{
    private static ?string $correlation = null;
    private static ?int $actorId = null;
    private static string $source = 'web';
    private static ?string $clock = null;

    public static function db(): BaseConnection
    {
        return \Config\Database::connect();
    }

    public static function correlationId(): string
    {
        return self::$correlation ??= bin2hex(random_bytes(16));
    }

    public static function resetContext(): void
    {
        self::$correlation = null;
        self::$actorId     = null;
        self::$source      = 'web';
        self::$clock       = null;
    }

    public static function setActor(?int $id, string $source = 'web'): void
    {
        self::$actorId = $id;
        self::$source  = $source;
    }

    public static function actorId(): ?int
    {
        if (self::$actorId !== null) {
            return self::$actorId;
        }
        if (PHP_SAPI !== 'cli') {
            $u = session()->get('crm_user');

            return isset($u['id']) ? (int) $u['id'] : null;
        }

        return null;
    }

    public static function source(): string
    {
        return self::$source;
    }

    /** Freeze the business clock (used by the demo seeder to build backdated history). null = real time. */
    public static function setClock(?string $datetime): void
    {
        self::$clock = $datetime;
    }

    public static function now(): string
    {
        return self::$clock ?? date('Y-m-d H:i:s');
    }

    /** Run $fn in a DB transaction; rethrow after rollback. Nested calls join the outer transaction. */
    public static function tx(callable $fn): mixed
    {
        $db    = self::db();
        $outer = $db->transDepth > 0;
        if (! $outer) {
            $db->transBegin();
        }
        try {
            $r = $fn($db);
            if (! $outer) {
                $db->transCommit();
            }

            return $r;
        } catch (\Throwable $e) {
            if (! $outer) {
                $db->transRollback();
            }
            throw $e;
        }
    }
}
