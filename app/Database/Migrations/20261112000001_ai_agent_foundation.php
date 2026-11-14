<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * External AI agent foundation (draft-only). STRICTLY ADDITIVE and idempotent:
 * new tables use CREATE TABLE IF NOT EXISTS; social_posts only gains NULLABLE/defaulted columns (checked via
 * information_schema first); permissions are INSERT IGNORE. Nothing is dropped, truncated, renamed or rewritten,
 * and down() deliberately does nothing destructive.
 *
 * On the SQL-dump-based dev database do NOT run `spark migrate` — call AiAgentFoundation::apply($db) via the admin
 * page (lazy, additive) or run the equivalent SQL after taking a backup. See docs/transformation/AGENT_DEPLOYMENT_NOTES.md.
 */
class AiAgentFoundation extends Migration
{
    public function up()
    {
        self::apply($this->db);
    }

    public static function apply(BaseConnection $db): void
    {
        static $done = [];
        $key = $db->getDatabase();
        if (isset($done[$key])) {
            return;
        }

        $db->query("CREATE TABLE IF NOT EXISTS `ai_agents` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(120) NOT NULL,
            `provider` enum('chatgpt','claude','internal','custom') NOT NULL DEFAULT 'custom',
            `description` varchar(500) DEFAULT NULL,
            `status` enum('active','disabled') NOT NULL DEFAULT 'active',
            `policy_mode` enum('draft_only') NOT NULL DEFAULT 'draft_only',
            `scopes` varchar(500) NOT NULL DEFAULT '',
            `allowed_ips` varchar(500) DEFAULT NULL,
            `rate_limit_per_minute` smallint(5) unsigned NOT NULL DEFAULT 30,
            `daily_draft_limit` smallint(5) unsigned NOT NULL DEFAULT 25,
            `created_by` int(10) unsigned DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            `last_access_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_ai_agents_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `ai_agent_tokens` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `agent_id` int(10) unsigned NOT NULL,
            `token_prefix` char(12) NOT NULL,
            `token_hash` char(64) NOT NULL,
            `expires_at` datetime NOT NULL,
            `revoked_at` datetime DEFAULT NULL,
            `revoked_by` int(10) unsigned DEFAULT NULL,
            `revoke_reason` varchar(60) DEFAULT NULL,
            `created_by` int(10) unsigned DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `last_used_at` datetime DEFAULT NULL,
            `last_used_ip` varchar(45) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_ai_token_prefix` (`token_prefix`),
            KEY `idx_ai_token_agent` (`agent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Access / security log. Never holds tokens, secrets or request bodies.
        $db->query("CREATE TABLE IF NOT EXISTS `ai_agent_audit` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `agent_id` int(10) unsigned DEFAULT NULL,
            `token_prefix` char(12) DEFAULT NULL,
            `event` varchar(60) NOT NULL,
            `method` varchar(8) DEFAULT NULL,
            `path` varchar(160) DEFAULT NULL,
            `status_code` smallint(5) unsigned DEFAULT NULL,
            `ip` varchar(45) DEFAULT NULL,
            `request_id` char(16) DEFAULT NULL,
            `detail` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_ai_audit_agent` (`agent_id`, `created_at`),
            KEY `idx_ai_audit_event` (`event`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `ai_agent_idempotency` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `agent_id` int(10) unsigned NOT NULL,
            `idem_key` varchar(80) NOT NULL,
            `request_hash` char(64) NOT NULL,
            `post_id` int(10) unsigned DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_ai_idem` (`agent_id`, `idem_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($db->tableExists('social_posts')) {
            $existing = array_column($db->query('SHOW COLUMNS FROM `social_posts`')->getResultArray(), 'Field');
            $add = [
                'origin'          => "ENUM('admin','agent') NOT NULL DEFAULT 'admin'",
                'agent_id'        => 'INT UNSIGNED NULL',
                'content_hash'    => 'CHAR(64) NULL',
                'proposed_at'     => 'DATETIME NULL',
                'review_decision' => "ENUM('approved','rejected','changes_requested') NULL",
                'review_note'     => 'VARCHAR(500) NULL',
                'reviewed_by'     => 'INT UNSIGNED NULL',
                'reviewed_at'     => 'DATETIME NULL',
            ];
            foreach ($add as $col => $def) {
                if (!in_array($col, $existing, true)) {
                    $db->query("ALTER TABLE `social_posts` ADD COLUMN `$col` $def");
                }
            }
            $idx = array_column($db->query('SHOW INDEX FROM `social_posts`')->getResultArray(), 'Key_name');
            if (!in_array('idx_social_posts_agent', $idx, true)) {
                $db->query('ALTER TABLE `social_posts` ADD INDEX `idx_social_posts_agent` (`origin`, `agent_id`, `status`)');
            }
            if (!in_array('idx_social_posts_hash', $idx, true)) {
                $db->query('ALTER TABLE `social_posts` ADD INDEX `idx_social_posts_hash` (`content_hash`)');
            }
        }

        // Notification event enum only ever GROWS: existing values are re-listed verbatim, two new ones appended.
        if ($db->tableExists('social_notifications')) {
            $col = $db->query("SHOW COLUMNS FROM `social_notifications` LIKE 'event_type'")->getRowArray();
            if ($col && str_starts_with(strtolower((string) $col['Type']), 'enum(') && !str_contains((string) $col['Type'], 'agent_draft_created')) {
                $type = rtrim((string) $col['Type'], ')') . ",'agent_draft_created','agent_draft_submitted')";
                $null = strtoupper((string) $col['Null']) === 'YES' ? 'NULL' : 'NOT NULL';
                $db->query("ALTER TABLE `social_notifications` MODIFY `event_type` $type $null");
            }
        }

        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('agents.view','View external AI agents & their activity','Administration',NOW()),
                ('agents.manage','Register, rotate and revoke external AI agents','Administration',NOW()),
                ('social.approve','Review and approve/reject agent-created social drafts','Social',NOW())");
            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.code IN ('agents.view','agents.manage','social.approve')
                    WHERE r.slug = 'super_admin'");
            }
        }

        $done[$key] = true;
    }

    public function down()
    {
        // Intentionally non-destructive: agent tables hold audit evidence and social_posts columns hold review history.
    }
}
