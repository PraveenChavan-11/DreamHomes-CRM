<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * IT Services CRM (separate from employer/contact enquiries).
 * Additive only: new tables + new permission codes. No existing rows are changed or removed.
 * down() drops only the two tables created here (permission rows are left in place; harmless).
 */
class CreateItLeads extends Migration
{
    public function up()
    {
        self::apply($this->db);
    }

    /** Idempotent; also called lazily at runtime so deployments need no manual migrate step. */
    public static function apply(\CodeIgniter\Database\BaseConnection $db): void
    {

        $db->query("CREATE TABLE IF NOT EXISTS `it_leads` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `client_name` VARCHAR(150) NOT NULL,
            `company` VARCHAR(150) NULL,
            `email` VARCHAR(190) NOT NULL,
            `phone` VARCHAR(30) NOT NULL,
            `location` VARCHAR(120) NULL,
            `service_category` VARCHAR(60) NOT NULL DEFAULT 'other',
            `service_slug` VARCHAR(120) NULL,
            `project_description` TEXT NOT NULL,
            `budget_range` VARCHAR(60) NULL,
            `timeline` VARCHAR(60) NULL,
            `preferred_contact` VARCHAR(30) NULL,
            `source_page` VARCHAR(255) NULL,
            `utm_source` VARCHAR(120) NULL,
            `utm_medium` VARCHAR(120) NULL,
            `utm_campaign` VARCHAR(160) NULL,
            `utm_term` VARCHAR(160) NULL,
            `utm_content` VARCHAR(160) NULL,
            `consent_at` DATETIME NOT NULL,
            `ip_address` VARCHAR(64) NULL,
            `status` ENUM('new','contacted','requirement_gathering','proposal_sent','negotiation','won','lost','on_hold') NOT NULL DEFAULT 'new',
            `assigned_to` INT UNSIGNED NULL,
            `next_follow_up_at` DATE NULL,
            `duplicate_of` INT UNSIGNED NULL,
            `idempotency_key` VARCHAR(80) NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_it_leads_idem` (`idempotency_key`),
            KEY `idx_it_leads_status` (`status`,`created_at`),
            KEY `idx_it_leads_category` (`service_category`),
            KEY `idx_it_leads_email` (`email`),
            KEY `idx_it_leads_assigned` (`assigned_to`),
            KEY `idx_it_leads_followup` (`next_follow_up_at`),
            KEY `idx_it_leads_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `it_lead_activities` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `lead_id` INT UNSIGNED NOT NULL,
            `admin_user_id` INT UNSIGNED NULL,
            `type` ENUM('created','status_change','note','assignment','follow_up','email','system') NOT NULL,
            `body` TEXT NULL,
            `created_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `idx_it_lead_act_lead` (`lead_id`,`created_at`),
            CONSTRAINT `fk_it_lead_act_lead` FOREIGN KEY (`lead_id`) REFERENCES `it_leads`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('it_leads.view','View IT Project Leads','IT Services CRM',NOW()),
                ('it_leads.manage','Manage, Assign & Export IT Project Leads','IT Services CRM',NOW())");
            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.code IN ('it_leads.view','it_leads.manage')
                    WHERE r.slug = 'super_admin'");
            }
        }
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `it_lead_activities`');
        $this->db->query('DROP TABLE IF EXISTS `it_leads`');
    }
}
