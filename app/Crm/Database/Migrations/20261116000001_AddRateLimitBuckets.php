<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Backing table for the shared `ratelimit` route filter (login / password-reset throttling). Idempotent. */
class AddRateLimitBuckets extends Migration
{
    public function up(): void
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `rate_limit_buckets` (
            `key_hash` VARCHAR(64) NOT NULL, `rate_key` VARCHAR(190) NOT NULL, `hits` INT UNSIGNED NOT NULL DEFAULT 1, `reset_at` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`key_hash`), KEY `idx_rate_reset` (`reset_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `rate_limit_buckets`');
    }
}
