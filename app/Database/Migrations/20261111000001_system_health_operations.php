<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * System Health & Operations: scheduler heartbeat table + permissions.
 * Additive and idempotent. `scheduled_job_runs` already exists on deployed databases (it was created from
 * SQL dumps, never by a migration) so it is created only if missing and its shape is left untouched.
 */
class SystemHealthOperations extends Migration
{
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `scheduled_job_runs` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `job_name` varchar(100) NOT NULL,
            `status` enum('RUNNING','SUCCESS','FAILED') NOT NULL,
            `items_processed` int(11) DEFAULT 0,
            `error_details` text DEFAULT NULL,
            `duration_ms` int(11) DEFAULT 0,
            `started_at` datetime NOT NULL,
            `completed_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Lookup index for "latest run per job" queries (skipped if it already exists).
        $has = $this->db->query("SHOW INDEX FROM `scheduled_job_runs` WHERE Key_name = 'idx_job_started'")->getResultArray();
        if (!$has) {
            $this->db->query('ALTER TABLE `scheduled_job_runs` ADD INDEX `idx_job_started` (`job_name`, `started_at`)');
        }

        if ($this->db->tableExists('permissions')) {
            $this->db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('system.view','View system health, jobs & scheduler','Administration',NOW()),
                ('system.manage','Retry/cancel background jobs','Administration',NOW())");
            if ($this->db->tableExists('role_permissions') && $this->db->tableExists('roles')) {
                $this->db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.code IN ('system.view','system.manage')
                    WHERE r.slug = 'super_admin'");
            }
        }
    }

    public function down()
    {
        // Additive: the heartbeat table may pre-date this migration, so it is intentionally not dropped.
        $this->db->query("DELETE FROM `permissions` WHERE `code` IN ('system.view','system.manage')");
    }
}
