<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Admin-managed case studies / portfolio.
 *
 * Additive and idempotent (CREATE TABLE IF NOT EXISTS, INSERT IGNORE). It seeds NO rows: the public site shows
 * case studies only after an administrator publishes real ones. Also applied lazily at runtime by CaseStudyService
 * (Hostinger shared hosting has no shell for `spark migrate`). down() drops the two new tables only.
 */
class CreateCaseStudies extends Migration
{
    public function up()
    {
        self::apply($this->db);
    }

    public static function apply(\CodeIgniter\Database\BaseConnection $db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `case_studies` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(100) NOT NULL,
            `title` VARCHAR(180) NOT NULL,
            `client_name` VARCHAR(150) NULL,
            `category` VARCHAR(80) NULL,
            `summary` VARCHAR(600) NULL,
            `challenge` TEXT NULL,
            `solution` TEXT NULL,
            `outcome` TEXT NULL,
            `technologies` VARCHAR(500) NULL COMMENT 'comma separated',
            `project_url` VARCHAR(500) NULL,
            `cover_path` VARCHAR(255) NULL,
            `seo_title` VARCHAR(200) NULL,
            `seo_description` VARCHAR(320) NULL,
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
            `display_order` INT NOT NULL DEFAULT 0,
            `published_at` DATETIME NULL,
            `created_by` INT UNSIGNED NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_case_studies_slug` (`slug`),
            KEY `idx_case_studies_list` (`status`, `deleted_at`, `display_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `case_study_images` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `case_study_id` INT UNSIGNED NOT NULL,
            `path` VARCHAR(255) NOT NULL,
            `caption` VARCHAR(255) NULL,
            `alt_text` VARCHAR(255) NULL,
            `sort` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `idx_csi_case` (`case_study_id`, `sort`),
            CONSTRAINT `fk_csi_case` FOREIGN KEY (`case_study_id`) REFERENCES `case_studies` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('case_studies.view','View Case Studies','Case Studies',NOW()),
                ('case_studies.manage','Create, Edit, Publish & Delete Case Studies','Case Studies',NOW())");
            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.code IN ('case_studies.view','case_studies.manage')
                    WHERE r.slug = 'super_admin'");
            }
        }
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `case_study_images`');
        $this->db->query('DROP TABLE IF EXISTS `case_studies`');
    }
}
