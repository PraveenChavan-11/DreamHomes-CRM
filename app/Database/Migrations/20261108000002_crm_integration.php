<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * CRM extension + WorkSphere integration receiver.
 * Strictly additive and idempotent: new columns/tables/permissions only; ENUMs only ever grow.
 * apply() is also called lazily at runtime (ItLeadService / controllers) so Hostinger needs no manual migrate step.
 */
class CrmIntegration extends Migration
{
    public const STATUS_ENUM = "'new','contacted','requirement_gathering','qualified','demo_scheduled','demo_active','proposal_sent','negotiation','on_hold','won','lost','spam'";

    public function up()
    {
        self::apply($this->db);
    }

    public static function apply(BaseConnection $db): void
    {
        static $applied = [];
        $dbName = $db->getDatabase();
        if (!empty($applied[$dbName])) {
            return;
        }
        require_once APPPATH . 'Database/Migrations/20261106000001_create_it_leads.php';
        \App\Database\Migrations\CreateItLeads::apply($db);

        // ── it_leads: additive columns (MySQL 8 has no ADD COLUMN IF NOT EXISTS, so check first) ──
        $have = array_map('strtolower', array_column($db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'it_leads'")->getResultArray(), 'COLUMN_NAME'));
        $cols = [
            'source_system'     => "VARCHAR(20) NOT NULL DEFAULT 'website'",
            'source'            => "VARCHAR(40) NOT NULL DEFAULT 'project_enquiry'",
            'product_slug'      => 'VARCHAR(120) NULL',
            'external_ref'      => 'VARCHAR(64) NULL',
            'priority'          => "ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal'",
            'estimated_value'   => 'DECIMAL(12,2) NULL',
            'tags'              => 'TEXT NULL',
            'enquiry_count'     => 'INT UNSIGNED NOT NULL DEFAULT 1',
            'last_activity_at'  => 'DATETIME NULL',
            'first_contacted_at' => 'DATETIME NULL',
            'lost_reason'       => 'VARCHAR(255) NULL',
            'verified_email_at' => 'DATETIME NULL',
            'demo_status'       => 'VARCHAR(30) NULL',
            'demo_started_at'   => 'DATETIME NULL',
            'demo_expires_at'   => 'DATETIME NULL',
            'consent_privacy_at' => 'DATETIME NULL',
        ];
        foreach ($cols as $name => $def) {
            if (!in_array($name, $have, true)) {
                $db->query("ALTER TABLE `it_leads` ADD COLUMN `{$name}` {$def}");
            }
        }
        $idx = array_column($db->query('SHOW INDEX FROM `it_leads`')->getResultArray(), 'Key_name');
        $addIdx = [
            'uk_it_leads_extref'  => 'ADD UNIQUE KEY `uk_it_leads_extref` (`external_ref`)',
            'idx_it_leads_source' => 'ADD KEY `idx_it_leads_source` (`source_system`,`source`)',
            'idx_it_leads_product' => 'ADD KEY `idx_it_leads_product` (`product_slug`)',
            'idx_it_leads_priority' => 'ADD KEY `idx_it_leads_priority` (`priority`)',
            'idx_it_leads_created' => 'ADD KEY `idx_it_leads_created` (`created_at`)',
        ];
        foreach ($addIdx as $n => $sql) {
            if (!in_array($n, $idx, true)) {
                $db->query("ALTER TABLE `it_leads` {$sql}");
            }
        }

        // ── ENUM growth (never shrinks) ──
        $statusType = (string) ($db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='it_leads' AND COLUMN_NAME='status'")->getRow()->COLUMN_TYPE ?? '');
        if (!str_contains($statusType, "'spam'") || !str_contains($statusType, "'qualified'") || !str_contains($statusType, "'demo_active'")) {
            $db->query("ALTER TABLE `it_leads` MODIFY `status` ENUM(" . self::STATUS_ENUM . ") NOT NULL DEFAULT 'new'");
        }
        $actType = (string) ($db->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='it_lead_activities' AND COLUMN_NAME='type'")->getRow()->COLUMN_TYPE ?? '');
        if (!str_contains($actType, "'integration'")) {
            $db->query("ALTER TABLE `it_lead_activities` MODIFY `type` ENUM('created','status_change','note','assignment','follow_up','email','system','integration','update') NOT NULL");
        }

        $tail = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        $db->query("CREATE TABLE IF NOT EXISTS `crm_categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(60) NOT NULL,
            `label` VARCHAR(120) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`), UNIQUE KEY `uk_crm_cat_slug` (`slug`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `crm_tags` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(40) NOT NULL,
            `label` VARCHAR(60) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`), UNIQUE KEY `uk_crm_tag_slug` (`slug`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `crm_integration_clients` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key_id` VARCHAR(64) NOT NULL,
            `label` VARCHAR(120) NOT NULL,
            `secret_encrypted` TEXT NOT NULL,
            `secret_prev_encrypted` TEXT NULL,
            `prev_expires_at` DATETIME NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `last_used_at` DATETIME NULL,
            `created_at` DATETIME NULL,
            `rotated_at` DATETIME NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uk_crm_client_key` (`key_id`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `crm_integration_events` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event_id` VARCHAR(64) NOT NULL,
            `key_id` VARCHAR(64) NOT NULL,
            `event_type` VARCHAR(40) NOT NULL,
            `correlation_id` VARCHAR(64) NULL,
            `payload_json` MEDIUMTEXT NULL,
            `received_at` DATETIME NOT NULL,
            `processed_at` DATETIME NULL,
            `lead_id` INT UNSIGNED NULL,
            `result` ENUM('accepted','duplicate','failed') NOT NULL DEFAULT 'failed',
            `error_code` VARCHAR(40) NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uk_crm_evt_id` (`event_id`),
            KEY `idx_crm_evt_corr` (`correlation_id`), KEY `idx_crm_evt_received` (`received_at`), KEY `idx_crm_evt_lead` (`lead_id`), KEY `idx_crm_evt_result` (`result`,`received_at`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `crm_integration_nonces` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key_id` VARCHAR(64) NOT NULL,
            `nonce` VARCHAR(64) NOT NULL,
            `seen_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`), UNIQUE KEY `uk_crm_nonce` (`key_id`,`nonce`), KEY `idx_crm_nonce_seen` (`seen_at`)
        ) {$tail}");

        // Seed configurable categories from the built-in list (insert-ignore; never overwrites admin edits).
        $i = 0;
        foreach (\App\Services\ItLeadService::CATEGORIES as $slug => $label) {
            $db->query('INSERT IGNORE INTO `crm_categories` (`slug`,`label`,`active`,`sort_order`) VALUES (?,?,1,?)', [$slug, $label, $i += 10]);
        }

        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('crm.view','View CRM leads & dashboard','CRM',NOW()),
                ('crm.manage','Manage CRM leads (status, assign, notes)','CRM',NOW()),
                ('crm.export','Export CRM leads (CSV)','CRM',NOW()),
                ('crm.settings','CRM settings & notification recipient','CRM',NOW()),
                ('integrations.view','View integrations & events','Integrations',NOW()),
                ('integrations.manage','Manage integration clients & secrets','Integrations',NOW())");
            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
                    ON p.code IN ('crm.view','crm.manage','crm.export','crm.settings','integrations.view','integrations.manage')
                    WHERE r.slug = 'super_admin'");
            }
        }
        $applied[$dbName] = true;
    }

    public function down()
    {
        // Additive migration: intentionally no destructive rollback (columns/permissions are left in place).
        $this->db->query('DROP TABLE IF EXISTS `crm_integration_nonces`');
        $this->db->query('DROP TABLE IF EXISTS `crm_integration_events`');
        $this->db->query('DROP TABLE IF EXISTS `crm_integration_clients`');
        $this->db->query('DROP TABLE IF EXISTS `crm_tags`');
        $this->db->query('DROP TABLE IF EXISTS `crm_categories`');
    }
}
