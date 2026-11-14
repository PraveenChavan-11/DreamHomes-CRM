<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DHIS Sales CRM baseline schema. Run with:  php spark migrate -n Crm
 *
 * Identity tables (admin_users, roles, …) are created IF NOT EXISTS with the exact legacy shape so the CRM works on a
 * fresh database and also coexists with a legacy TalentHub database. CRM-only columns are added idempotently.
 */
class CreateCrmSchema extends Migration
{
    private const OPTS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->statements() as $sql) {
            $this->db->query($sql . ' ' . self::OPTS);
        }
        $this->addMissingUserColumns();
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach (array_reverse($this->crmTables()) as $t) {
            $this->db->query("DROP TABLE IF EXISTS `{$t}`");
        }
        // Identity tables and admin_users columns are intentionally left in place (shared with legacy app).
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }

    /** @return list<string> */
    private function crmTables(): array
    {
        $names = [];
        foreach ($this->statements() as $sql) {
            if (preg_match('/CREATE TABLE IF NOT EXISTS `(crm_[a-z_]+)`/', $sql, $m)) {
                $names[] = $m[1];
            }
        }

        return $names;
    }

    private function addMissingUserColumns(): void
    {
        $cols = [
            'employee_code'    => 'VARCHAR(30) NULL',
            'team_id'          => 'INT NULL',
            'reports_to_id'    => 'INT NULL',
            'max_discount_pct' => 'DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'password_changed_at' => 'DATETIME NULL',
            'deactivated_at'   => 'DATETIME NULL',
        ];
        $existing = array_column($this->db->query('SHOW COLUMNS FROM admin_users')->getResultArray(), 'Field');
        foreach ($cols as $name => $def) {
            if (! in_array($name, $existing, true)) {
                $this->db->query("ALTER TABLE admin_users ADD COLUMN `{$name}` {$def}");
            }
        }
    }

    /** @return list<string> */
    private function statements(): array
    {
        $ts = "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";

        return [
            // ───────── Identity (shared / legacy-compatible) ─────────
            "CREATE TABLE IF NOT EXISTS `admin_users` (
              `id` INT NOT NULL AUTO_INCREMENT, `username` VARCHAR(50) NOT NULL, `email` VARCHAR(190) NOT NULL,
              `password_hash` VARCHAR(255) NOT NULL, `first_name` VARCHAR(100) NOT NULL, `last_name` VARCHAR(100) NOT NULL,
              `phone` VARCHAR(30) NULL, `is_active` TINYINT(1) DEFAULT 1, `last_login_at` DATETIME NULL,
              `failed_login_count` INT DEFAULT 0, `locked_until` DATETIME NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), UNIQUE KEY `username` (`username`), UNIQUE KEY `email` (`email`), KEY `idx_admin_email_active` (`email`,`is_active`))",
            "CREATE TABLE IF NOT EXISTS `roles` (`id` INT NOT NULL AUTO_INCREMENT, `slug` VARCHAR(50) NOT NULL, `name` VARCHAR(100) NOT NULL, `description` TEXT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), UNIQUE KEY `slug` (`slug`))",
            "CREATE TABLE IF NOT EXISTS `permissions` (`id` INT NOT NULL AUTO_INCREMENT, `code` VARCHAR(100) NOT NULL, `name` VARCHAR(150) NOT NULL, `category` VARCHAR(50) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `code` (`code`))",
            "CREATE TABLE IF NOT EXISTS `role_permissions` (`role_id` INT NOT NULL, `permission_id` INT NOT NULL, PRIMARY KEY (`role_id`,`permission_id`), KEY `fk_rp_permission` (`permission_id`))",
            "CREATE TABLE IF NOT EXISTS `user_roles` (`user_id` INT NOT NULL, `role_id` INT NOT NULL, PRIMARY KEY (`user_id`,`role_id`), KEY `fk_ur_role` (`role_id`))",
            "CREATE TABLE IF NOT EXISTS `login_attempts` (`id` BIGINT NOT NULL AUTO_INCREMENT, `email` VARCHAR(190) NOT NULL, `ip_address` VARCHAR(45) NOT NULL, `user_agent` TEXT NULL,
              `success` TINYINT(1) NOT NULL, `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_la_email` (`email`,`attempted_at`))",
            "CREATE TABLE IF NOT EXISTS `password_resets` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `email` VARCHAR(191) NOT NULL, `token_hash` VARCHAR(64) NOT NULL,
              `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(255) NULL, `expires_at` DATETIME NOT NULL, `used_at` DATETIME NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), KEY `idx_pr_token` (`token_hash`), KEY `idx_pr_email` (`email`))",
            "CREATE TABLE IF NOT EXISTS `scheduled_job_runs` (`id` BIGINT NOT NULL AUTO_INCREMENT, `job_name` VARCHAR(100) NOT NULL, `status` ENUM('RUNNING','SUCCESS','FAILED') NOT NULL,
              `items_processed` INT DEFAULT 0, `error_details` TEXT NULL, `duration_ms` INT DEFAULT 0, `started_at` DATETIME NOT NULL, `completed_at` DATETIME NULL,
              PRIMARY KEY (`id`), KEY `idx_sjr_job` (`job_name`,`started_at`))",

            // ───────── Organisation ─────────
            "CREATE TABLE IF NOT EXISTS `crm_teams` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `manager_id` INT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_team_name` (`name`), KEY `idx_team_mgr` (`manager_id`))",
            "CREATE TABLE IF NOT EXISTS `crm_team_members` (`team_id` INT NOT NULL, `user_id` INT NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`team_id`,`user_id`), KEY `idx_tm_user` (`user_id`))",
            "CREATE TABLE IF NOT EXISTS `crm_territories` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `city` VARCHAR(100) NULL, `pin_prefix` VARCHAR(6) NULL, `team_id` INT NULL,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `uq_terr_name` (`name`))",
            "CREATE TABLE IF NOT EXISTS `crm_login_history` (`id` BIGINT NOT NULL AUTO_INCREMENT, `user_id` INT NULL, `email` VARCHAR(190) NULL,
              `event` ENUM('login','logout','failed','timeout','locked','password_reset','password_change') NOT NULL, `ip_address` VARCHAR(45) NULL, `user_agent` VARCHAR(255) NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_lh_user` (`user_id`,`created_at`), KEY `idx_lh_event` (`event`,`created_at`))",
            "CREATE TABLE IF NOT EXISTS `crm_settings` (`setting_key` VARCHAR(100) NOT NULL, `setting_value` TEXT NULL, `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
              `description` VARCHAR(255) NULL, `is_default_assumption` TINYINT(1) NOT NULL DEFAULT 1, `updated_by` INT NULL, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`setting_key`))",
            "CREATE TABLE IF NOT EXISTS `crm_number_sequences` (`seq_key` VARCHAR(30) NOT NULL, `prefix` VARCHAR(10) NOT NULL, `next_value` BIGINT NOT NULL DEFAULT 1, `pad_length` TINYINT NOT NULL DEFAULT 4,
              PRIMARY KEY (`seq_key`))",

            // ───────── Customers ─────────
            "CREATE TABLE IF NOT EXISTS `crm_lead_sources` (`id` INT NOT NULL AUTO_INCREMENT, `code` VARCHAR(30) NOT NULL, `name` VARCHAR(80) NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id`), UNIQUE KEY `uq_src_code` (`code`))",
            "CREATE TABLE IF NOT EXISTS `crm_customers` (`id` INT NOT NULL AUTO_INCREMENT, `customer_no` VARCHAR(20) NOT NULL, `name` VARCHAR(150) NOT NULL,
              `customer_type` ENUM('individual','company','architect','builder','other') NOT NULL DEFAULT 'individual', `company_name` VARCHAR(150) NULL, `primary_contact` VARCHAR(150) NULL,
              `mobile` VARCHAR(20) NOT NULL, `mobile_norm` VARCHAR(15) NOT NULL, `alt_mobile` VARCHAR(20) NULL, `email` VARCHAR(190) NULL, `email_norm` VARCHAR(190) NULL,
              `billing_address` TEXT NULL, `site_address` TEXT NULL, `city` VARCHAR(100) NULL, `state` VARCHAR(100) NULL, `pin_code` VARCHAR(10) NULL, `gstin` VARCHAR(20) NULL,
              `source_id` INT NULL, `owner_user_id` INT NULL, `status` ENUM('active','inactive','archived') NOT NULL DEFAULT 'active', `remarks` TEXT NULL,
              `created_by` INT NULL, `updated_by` INT NULL, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_cust_no` (`customer_no`), KEY `idx_cust_mobile` (`mobile_norm`), KEY `idx_cust_email` (`email_norm`), KEY `idx_cust_owner` (`status`,`owner_user_id`), KEY `idx_cust_name` (`name`),
              CONSTRAINT `fk_cust_src` FOREIGN KEY (`source_id`) REFERENCES `crm_lead_sources`(`id`), CONSTRAINT `fk_cust_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `admin_users`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_customer_contacts` (`id` INT NOT NULL AUTO_INCREMENT, `customer_id` INT NOT NULL, `name` VARCHAR(150) NOT NULL, `role_title` VARCHAR(80) NULL,
              `mobile` VARCHAR(20) NULL, `email` VARCHAR(190) NULL, `is_primary` TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY (`id`), KEY `idx_cc_cust` (`customer_id`),
              CONSTRAINT `fk_cc_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`) ON DELETE CASCADE)",

            // ───────── Catalogue ─────────
            "CREATE TABLE IF NOT EXISTS `crm_product_categories` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `description` VARCHAR(255) NULL, `sort_order` INT NOT NULL DEFAULT 0,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `uq_pcat_name` (`name`))",
            "CREATE TABLE IF NOT EXISTS `crm_tax_rates` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(60) NOT NULL, `rate` DECIMAL(6,3) NOT NULL, `is_default` TINYINT(1) NOT NULL DEFAULT 0,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `uq_tax_name` (`name`))",
            "CREATE TABLE IF NOT EXISTS `crm_products` (`id` INT NOT NULL AUTO_INCREMENT, `sku` VARCHAR(40) NOT NULL, `name` VARCHAR(200) NOT NULL, `brand` VARCHAR(80) NULL, `category_id` INT NOT NULL,
              `description` TEXT NULL, `specifications` TEXT NULL, `image` VARCHAR(255) NULL, `unit` VARCHAR(20) NOT NULL DEFAULT 'nos',
              `pricing_model` ENUM('unit','sqft','rft','qty','package') NOT NULL DEFAULT 'unit', `base_price` DECIMAL(14,2) NOT NULL DEFAULT 0, `standard_price` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `min_price` DECIMAL(14,2) NOT NULL DEFAULT 0, `tax_rate_id` INT NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_prod_sku` (`sku`), KEY `idx_prod_cat` (`category_id`,`is_active`),
              CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `crm_product_categories`(`id`), CONSTRAINT `fk_prod_tax` FOREIGN KEY (`tax_rate_id`) REFERENCES `crm_tax_rates`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_product_price_history` (`id` BIGINT NOT NULL AUTO_INCREMENT, `product_id` INT NOT NULL, `old_base` DECIMAL(14,2) NULL, `new_base` DECIMAL(14,2) NOT NULL,
              `old_standard` DECIMAL(14,2) NULL, `new_standard` DECIMAL(14,2) NOT NULL, `old_min` DECIMAL(14,2) NULL, `new_min` DECIMAL(14,2) NOT NULL, `changed_by` INT NULL,
              `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_pph_prod` (`product_id`,`changed_at`),
              CONSTRAINT `fk_pph_prod` FOREIGN KEY (`product_id`) REFERENCES `crm_products`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_additional_charges` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `calc_type` ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
              `value` DECIMAL(14,2) NOT NULL DEFAULT 0, `is_taxable` TINYINT(1) NOT NULL DEFAULT 1, `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `uq_chg_name` (`name`))",

            // ───────── Enquiries ─────────
            "CREATE TABLE IF NOT EXISTS `crm_lost_reasons` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(120) NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), UNIQUE KEY `uq_lost_name` (`name`))",
            "CREATE TABLE IF NOT EXISTS `crm_enquiries` (`id` INT NOT NULL AUTO_INCREMENT, `enquiry_no` VARCHAR(20) NOT NULL, `customer_id` INT NOT NULL, `project_name` VARCHAR(200) NULL,
              `product_category_id` INT NULL, `requirements` TEXT NULL, `site_location` VARCHAR(255) NULL, `est_budget` DECIMAL(14,2) NULL, `expected_purchase_date` DATE NULL,
              `source_id` INT NULL, `source_channel` VARCHAR(30) NOT NULL DEFAULT 'manual', `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium', `lead_score` INT NOT NULL DEFAULT 0,
              `status` ENUM('new','assigned','contacted','qualified','site_visit','quotation_preparation','quotation_sent','negotiation','won','lost','on_hold') NOT NULL DEFAULT 'new',
              `current_owner_id` INT NULL, `team_id` INT NULL, `territory_id` INT NULL, `description` TEXT NULL,
              `outcome` ENUM('won','lost','on_hold') NULL, `closed_at` DATETIME NULL, `closed_by` INT NULL, `final_value` DECIMAL(14,2) NULL, `lost_reason_id` INT NULL, `lost_competitor` VARCHAR(150) NULL,
              `close_remarks` TEXT NULL, `hold_review_date` DATE NULL, `previous_status` VARCHAR(30) NULL, `dedupe_hash` CHAR(40) NULL, `import_job_id` INT NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0,
              `created_by` INT NULL, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_enq_no` (`enquiry_no`), KEY `idx_enq_cust` (`customer_id`), KEY `idx_enq_owner` (`current_owner_id`,`status`), KEY `idx_enq_status` (`status`,`created_at`),
              KEY `idx_enq_team` (`team_id`), KEY `idx_enq_dedupe` (`dedupe_hash`), KEY `idx_enq_created` (`created_at`),
              CONSTRAINT `fk_enq_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`), CONSTRAINT `fk_enq_owner` FOREIGN KEY (`current_owner_id`) REFERENCES `admin_users`(`id`),
              CONSTRAINT `fk_enq_cat` FOREIGN KEY (`product_category_id`) REFERENCES `crm_product_categories`(`id`), CONSTRAINT `fk_enq_lost` FOREIGN KEY (`lost_reason_id`) REFERENCES `crm_lost_reasons`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_enquiry_assignments` (`id` BIGINT NOT NULL AUTO_INCREMENT, `enquiry_id` INT NOT NULL, `from_user_id` INT NULL, `to_user_id` INT NULL,
              `method` ENUM('manual','auto_round_robin','auto_workload','auto_team','auto_territory','fallback','reassign') NOT NULL, `reason` VARCHAR(255) NULL, `assigned_by` INT NULL, `approved_by` INT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_ea_enq` (`enquiry_id`,`created_at`), KEY `idx_ea_to` (`to_user_id`),
              CONSTRAINT `fk_ea_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_enquiry_status_history` (`id` BIGINT NOT NULL AUTO_INCREMENT, `enquiry_id` INT NOT NULL, `from_status` VARCHAR(30) NULL, `to_status` VARCHAR(30) NOT NULL,
              `user_id` INT NULL, `reason` VARCHAR(255) NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_esh_enq` (`enquiry_id`,`created_at`),
              CONSTRAINT `fk_esh_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_status_transitions` (`id` INT NOT NULL AUTO_INCREMENT, `entity` VARCHAR(30) NOT NULL, `from_status` VARCHAR(30) NOT NULL, `to_status` VARCHAR(30) NOT NULL,
              `required_permission` VARCHAR(100) NULL, `requires_reason` TINYINT(1) NOT NULL DEFAULT 0, `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              PRIMARY KEY (`id`), UNIQUE KEY `uq_trans` (`entity`,`from_status`,`to_status`))",
            "CREATE TABLE IF NOT EXISTS `crm_assignment_rules` (`id` INT NOT NULL AUTO_INCREMENT, `priority` INT NOT NULL DEFAULT 100, `mode` ENUM('round_robin','workload','team','territory') NOT NULL,
              `team_id` INT NULL, `territory_id` INT NULL, `category_id` INT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`), KEY `idx_ar_prio` (`is_active`,`priority`))",
            "CREATE TABLE IF NOT EXISTS `crm_assignment_state` (`scope_key` VARCHAR(60) NOT NULL, `last_user_id` INT NULL, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`scope_key`))",
            "CREATE TABLE IF NOT EXISTS `crm_scoring_rules` (`id` INT NOT NULL AUTO_INCREMENT, `field_name` VARCHAR(40) NOT NULL, `operator` ENUM('eq','gte','lte','contains') NOT NULL, `match_value` VARCHAR(100) NOT NULL,
              `points` INT NOT NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`id`))",

            // ───────── Follow-ups, activities, visits ─────────
            "CREATE TABLE IF NOT EXISTS `crm_followups` (`id` BIGINT NOT NULL AUTO_INCREMENT, `customer_id` INT NOT NULL, `enquiry_id` INT NULL, `assigned_to` INT NOT NULL,
              `activity_type` ENUM('call','meeting','whatsapp','email','visit','other') NOT NULL DEFAULT 'call', `due_at` DATETIME NOT NULL,
              `status` ENUM('pending','completed','rescheduled','missed','cancelled') NOT NULL DEFAULT 'pending', `outcome` VARCHAR(255) NULL, `remarks` TEXT NULL, `next_action` VARCHAR(255) NULL,
              `next_followup_id` BIGINT NULL, `rescheduled_from_id` BIGINT NULL, `completed_at` DATETIME NULL, `escalated_at` DATETIME NULL, `reminder_sent_at` DATETIME NULL, `created_by` INT NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), KEY `idx_fu_user_due` (`assigned_to`,`status`,`due_at`), KEY `idx_fu_enq` (`enquiry_id`), KEY `idx_fu_cust` (`customer_id`), KEY `idx_fu_due` (`status`,`due_at`),
              CONSTRAINT `fk_fu_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`), CONSTRAINT `fk_fu_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`),
              CONSTRAINT `fk_fu_user` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_activities` (`id` BIGINT NOT NULL AUTO_INCREMENT, `customer_id` INT NOT NULL, `enquiry_id` INT NULL, `user_id` INT NULL,
              `activity_type` ENUM('call','meeting','whatsapp','email','note','visit','system') NOT NULL, `summary` VARCHAR(500) NOT NULL, `details` TEXT NULL, `outcome` VARCHAR(255) NULL,
              `occurred_at` DATETIME NOT NULL, `followup_id` BIGINT NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), KEY `idx_act_cust` (`customer_id`,`occurred_at`), KEY `idx_act_enq` (`enquiry_id`,`occurred_at`), KEY `idx_act_user` (`user_id`,`occurred_at`),
              CONSTRAINT `fk_act_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_site_visits` (`id` INT NOT NULL AUTO_INCREMENT, `visit_no` VARCHAR(20) NOT NULL, `enquiry_id` INT NOT NULL, `customer_id` INT NOT NULL, `assigned_to` INT NOT NULL,
              `scheduled_at` DATETIME NOT NULL, `status` ENUM('scheduled','completed','cancelled','rescheduled') NOT NULL DEFAULT 'scheduled', `completed_at` DATETIME NULL, `address` VARCHAR(255) NULL,
              `remarks` TEXT NULL, `observations` TEXT NULL, `product_requirements` TEXT NULL, `follow_up_action` VARCHAR(255) NULL, `reminder_sent_at` DATETIME NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_visit_no` (`visit_no`), KEY `idx_sv_enq` (`enquiry_id`), KEY `idx_sv_user` (`assigned_to`,`status`,`scheduled_at`),
              CONSTRAINT `fk_sv_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`), CONSTRAINT `fk_sv_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_site_measurements` (`id` BIGINT NOT NULL AUTO_INCREMENT, `visit_id` INT NOT NULL, `version_no` INT NOT NULL, `is_current` TINYINT(1) NOT NULL DEFAULT 1,
              `data` TEXT NOT NULL, `notes` VARCHAR(500) NULL, `recorded_by` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), UNIQUE KEY `uq_meas_ver` (`visit_id`,`version_no`), CONSTRAINT `fk_meas_visit` FOREIGN KEY (`visit_id`) REFERENCES `crm_site_visits`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_documents` (`id` BIGINT NOT NULL AUTO_INCREMENT, `entity_type` VARCHAR(40) NOT NULL, `entity_id` INT NOT NULL, `customer_id` INT NULL,
              `original_name` VARCHAR(255) NOT NULL, `stored_name` VARCHAR(255) NOT NULL, `mime` VARCHAR(100) NOT NULL, `size_bytes` INT NOT NULL, `sha256` CHAR(64) NOT NULL, `uploaded_by` INT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_doc_entity` (`entity_type`,`entity_id`), KEY `idx_doc_cust` (`customer_id`))",

            // ───────── Quotations ─────────
            "CREATE TABLE IF NOT EXISTS `crm_quotations` (`id` INT NOT NULL AUTO_INCREMENT, `quotation_no` VARCHAR(20) NOT NULL, `enquiry_id` INT NOT NULL, `customer_id` INT NOT NULL,
              `current_version_id` INT NULL, `status` ENUM('draft','sent','negotiation','revised','accepted','rejected','expired','cancelled') NOT NULL DEFAULT 'draft',
              `customer_response` ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending', `responded_at` DATETIME NULL, `created_by` INT NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_qt_no` (`quotation_no`), KEY `idx_qt_enq` (`enquiry_id`), KEY `idx_qt_cust` (`customer_id`), KEY `idx_qt_status` (`status`,`created_at`),
              CONSTRAINT `fk_qt_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`), CONSTRAINT `fk_qt_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_quotation_versions` (`id` INT NOT NULL AUTO_INCREMENT, `quotation_id` INT NOT NULL, `version_no` INT NOT NULL, `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `discount_total` DECIMAL(14,2) NOT NULL DEFAULT 0, `taxable_total` DECIMAL(14,2) NOT NULL DEFAULT 0, `tax_total` DECIMAL(14,2) NOT NULL DEFAULT 0, `charges_total` DECIMAL(14,2) NOT NULL DEFAULT 0,
              `round_off` DECIMAL(6,2) NOT NULL DEFAULT 0, `grand_total` DECIMAL(14,2) NOT NULL DEFAULT 0, `terms` TEXT NULL, `payment_terms` TEXT NULL, `delivery_estimate` VARCHAR(150) NULL,
              `valid_until` DATE NULL, `customer_remarks` TEXT NULL, `internal_remarks` TEXT NULL, `pdf_path` VARCHAR(255) NULL, `pdf_sha256` CHAR(64) NULL,
              `is_locked` TINYINT(1) NOT NULL DEFAULT 0, `locked_at` DATETIME NULL, `sent_at` DATETIME NULL, `created_by` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), UNIQUE KEY `uq_qv_ver` (`quotation_id`,`version_no`), CONSTRAINT `fk_qv_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_quotation_items` (`id` BIGINT NOT NULL AUTO_INCREMENT, `version_id` INT NOT NULL, `line_no` INT NOT NULL DEFAULT 1, `product_id` INT NULL, `sku` VARCHAR(40) NULL,
              `description` VARCHAR(500) NOT NULL, `specifications` TEXT NULL, `quantity` DECIMAL(12,3) NOT NULL, `unit` VARCHAR(20) NOT NULL, `unit_price` DECIMAL(14,2) NOT NULL, `min_price` DECIMAL(14,2) NULL,
              `discount_pct` DECIMAL(6,3) NOT NULL DEFAULT 0, `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0, `line_subtotal` DECIMAL(14,2) NOT NULL, `taxable_amount` DECIMAL(14,2) NOT NULL,
              `tax_rate` DECIMAL(6,3) NOT NULL, `tax_amount` DECIMAL(14,2) NOT NULL, `line_total` DECIMAL(14,2) NOT NULL,
              PRIMARY KEY (`id`), KEY `idx_qi_ver` (`version_id`), CONSTRAINT `fk_qi_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions`(`id`) ON DELETE CASCADE)",
            "CREATE TABLE IF NOT EXISTS `crm_quotation_charges` (`id` BIGINT NOT NULL AUTO_INCREMENT, `version_id` INT NOT NULL, `name` VARCHAR(100) NOT NULL, `amount` DECIMAL(14,2) NOT NULL,
              `tax_rate` DECIMAL(6,3) NOT NULL DEFAULT 0, `tax_amount` DECIMAL(14,2) NOT NULL DEFAULT 0, PRIMARY KEY (`id`), KEY `idx_qc_ver` (`version_id`),
              CONSTRAINT `fk_qc_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions`(`id`) ON DELETE CASCADE)",

            // ───────── Negotiation ─────────
            "CREATE TABLE IF NOT EXISTS `crm_negotiations` (`id` INT NOT NULL AUTO_INCREMENT, `negotiation_no` VARCHAR(20) NOT NULL, `quotation_id` INT NOT NULL, `version_id` INT NOT NULL,
              `customer_expected_amount` DECIMAL(14,2) NULL, `requested_discount_amount` DECIMAL(14,2) NOT NULL, `requested_discount_pct` DECIMAL(6,3) NOT NULL, `reason` TEXT NULL,
              `proposed_price` DECIMAL(14,2) NOT NULL, `requested_by` INT NOT NULL, `status` ENUM('pending','approved','rejected','returned','escalated','applied','withdrawn') NOT NULL DEFAULT 'pending',
              `required_level` ENUM('self','manager','admin') NOT NULL DEFAULT 'manager', `final_discount_amount` DECIMAL(14,2) NULL, `resulting_version_id` INT NULL, `resubmission_of_id` INT NULL,
              `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_neg_no` (`negotiation_no`), KEY `idx_neg_qt` (`quotation_id`), KEY `idx_neg_status` (`status`),
              CONSTRAINT `fk_neg_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations`(`id`), CONSTRAINT `fk_neg_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_discount_approvals` (`id` BIGINT NOT NULL AUTO_INCREMENT, `negotiation_id` INT NOT NULL, `level` TINYINT NOT NULL DEFAULT 1, `required_role` VARCHAR(30) NOT NULL,
              `approver_id` INT NULL, `decision` ENUM('pending','approved','rejected','returned','escalated') NOT NULL DEFAULT 'pending', `remarks` VARCHAR(500) NULL, `decided_at` DATETIME NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_da_neg` (`negotiation_id`), KEY `idx_da_pending` (`decision`,`required_role`),
              CONSTRAINT `fk_da_neg` FOREIGN KEY (`negotiation_id`) REFERENCES `crm_negotiations`(`id`))",

            // ───────── Orders ─────────
            "CREATE TABLE IF NOT EXISTS `crm_sales_orders` (`id` INT NOT NULL AUTO_INCREMENT, `order_no` VARCHAR(20) NOT NULL, `quotation_id` INT NOT NULL, `version_id` INT NOT NULL, `customer_id` INT NOT NULL,
              `enquiry_id` INT NOT NULL, `salesperson_id` INT NULL, `status` ENUM('draft','pending_confirmation','confirmed','on_hold','cancelled','closed') NOT NULL DEFAULT 'pending_confirmation',
              `confirmed_at` DATETIME NULL, `advance_amount` DECIMAL(14,2) NOT NULL DEFAULT 0, `advance_reference` VARCHAR(100) NULL, `expected_delivery` DATE NULL,
              `subtotal` DECIMAL(14,2) NOT NULL, `discount_total` DECIMAL(14,2) NOT NULL, `tax_total` DECIMAL(14,2) NOT NULL, `charges_total` DECIMAL(14,2) NOT NULL, `grand_total` DECIMAL(14,2) NOT NULL,
              `remarks` TEXT NULL, `created_by` INT NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, {$ts},
              PRIMARY KEY (`id`), UNIQUE KEY `uq_so_no` (`order_no`), UNIQUE KEY `uq_so_quotation` (`quotation_id`), KEY `idx_so_cust` (`customer_id`), KEY `idx_so_status` (`status`,`created_at`),
              CONSTRAINT `fk_so_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations`(`id`), CONSTRAINT `fk_so_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers`(`id`),
              CONSTRAINT `fk_so_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`))",
            "CREATE TABLE IF NOT EXISTS `crm_sales_order_items` (`id` BIGINT NOT NULL AUTO_INCREMENT, `order_id` INT NOT NULL, `product_id` INT NULL, `sku` VARCHAR(40) NULL, `description` VARCHAR(500) NOT NULL,
              `quantity` DECIMAL(12,3) NOT NULL, `unit` VARCHAR(20) NOT NULL, `unit_price` DECIMAL(14,2) NOT NULL, `discount_amount` DECIMAL(14,2) NOT NULL, `tax_amount` DECIMAL(14,2) NOT NULL,
              `line_total` DECIMAL(14,2) NOT NULL, PRIMARY KEY (`id`), KEY `idx_soi_order` (`order_id`), CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `crm_sales_orders`(`id`) ON DELETE CASCADE)",

            // ───────── Communication ─────────
            "CREATE TABLE IF NOT EXISTS `crm_email_templates` (`id` INT NOT NULL AUTO_INCREMENT, `code` VARCHAR(60) NOT NULL, `name` VARCHAR(120) NOT NULL, `subject` VARCHAR(255) NOT NULL, `body_html` MEDIUMTEXT NOT NULL,
              `variables` VARCHAR(500) NULL, `is_active` TINYINT(1) NOT NULL DEFAULT 1, {$ts}, PRIMARY KEY (`id`), UNIQUE KEY `uq_et_code` (`code`))",
            "CREATE TABLE IF NOT EXISTS `crm_email_log` (`id` BIGINT NOT NULL AUTO_INCREMENT, `to_email` VARCHAR(190) NOT NULL, `from_email` VARCHAR(190) NOT NULL, `subject` VARCHAR(255) NOT NULL, `body_html` MEDIUMTEXT NULL,
              `template_code` VARCHAR(60) NULL, `customer_id` INT NULL, `enquiry_id` INT NULL, `quotation_id` INT NULL, `attachment_path` VARCHAR(255) NULL, `triggered_by_user` INT NULL, `triggered_by_automation` VARCHAR(60) NULL,
              `status` ENUM('queued','sent','delivered','failed','bounced') NOT NULL DEFAULT 'queued', `provider` VARCHAR(40) NULL, `provider_response` TEXT NULL, `failure_reason` VARCHAR(500) NULL,
              `attempts` TINYINT NOT NULL DEFAULT 0, `next_retry_at` DATETIME NULL, `last_attempt_at` DATETIME NULL, `sent_at` DATETIME NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), KEY `idx_el_status` (`status`,`next_retry_at`), KEY `idx_el_cust` (`customer_id`,`created_at`), KEY `idx_el_enq` (`enquiry_id`), KEY `idx_el_qt` (`quotation_id`))",
            "CREATE TABLE IF NOT EXISTS `crm_notifications` (`id` BIGINT NOT NULL AUTO_INCREMENT, `user_id` INT NOT NULL, `event_type` VARCHAR(60) NOT NULL, `title` VARCHAR(200) NOT NULL, `body` VARCHAR(500) NULL,
              `link` VARCHAR(255) NULL, `dedupe_key` VARCHAR(150) NULL, `read_at` DATETIME NULL, `is_demo` TINYINT(1) NOT NULL DEFAULT 0, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), UNIQUE KEY `uq_notif_dedupe` (`dedupe_key`), KEY `idx_notif_user` (`user_id`,`read_at`,`created_at`))",
            "CREATE TABLE IF NOT EXISTS `crm_notification_prefs` (`user_id` INT NOT NULL, `event_type` VARCHAR(60) NOT NULL, `in_app` TINYINT(1) NOT NULL DEFAULT 1, `email` TINYINT(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`user_id`,`event_type`))",

            // ───────── Audit & workflow (append-only) ─────────
            "CREATE TABLE IF NOT EXISTS `crm_workflow_events` (`id` BIGINT NOT NULL AUTO_INCREMENT, `event_type` VARCHAR(60) NOT NULL, `entity_type` VARCHAR(40) NOT NULL, `entity_id` INT NOT NULL,
              `entity_ref` VARCHAR(40) NULL, `customer_id` INT NULL, `enquiry_id` INT NULL, `user_id` INT NULL, `prev_state` TEXT NULL, `new_state` TEXT NULL, `reason` VARCHAR(500) NULL,
              `metadata` TEXT NULL, `correlation_id` CHAR(32) NULL, `source` VARCHAR(20) NOT NULL DEFAULT 'web', `is_demo` TINYINT(1) NOT NULL DEFAULT 0, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`), KEY `idx_we_cust` (`customer_id`,`created_at`), KEY `idx_we_enq` (`enquiry_id`,`created_at`), KEY `idx_we_entity` (`entity_type`,`entity_id`), KEY `idx_we_user` (`user_id`,`created_at`), KEY `idx_we_type` (`event_type`), KEY `idx_we_corr` (`correlation_id`))",
            "CREATE TABLE IF NOT EXISTS `crm_audit_events` (`id` BIGINT NOT NULL AUTO_INCREMENT, `event_type` VARCHAR(60) NOT NULL, `entity_type` VARCHAR(40) NOT NULL, `entity_id` VARCHAR(40) NULL, `user_id` INT NULL,
              `prev_state` TEXT NULL, `new_state` TEXT NULL, `reason` VARCHAR(500) NULL, `metadata` TEXT NULL, `ip_address` VARCHAR(45) NULL, `correlation_id` CHAR(32) NULL, `source` VARCHAR(20) NOT NULL DEFAULT 'web',
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `idx_ae_entity` (`entity_type`,`entity_id`), KEY `idx_ae_user` (`user_id`,`created_at`), KEY `idx_ae_type` (`event_type`,`created_at`))",

            // ───────── Import & demo ─────────
            "CREATE TABLE IF NOT EXISTS `crm_import_jobs` (`id` INT NOT NULL AUTO_INCREMENT, `import_type` VARCHAR(30) NOT NULL, `original_name` VARCHAR(255) NOT NULL, `file_sha256` CHAR(64) NOT NULL,
              `stored_path` VARCHAR(255) NULL, `status` ENUM('uploaded','previewed','approved','completed','failed') NOT NULL DEFAULT 'uploaded', `total_rows` INT NOT NULL DEFAULT 0, `valid_rows` INT NOT NULL DEFAULT 0,
              `invalid_rows` INT NOT NULL DEFAULT 0, `duplicate_rows` INT NOT NULL DEFAULT 0, `imported_rows` INT NOT NULL DEFAULT 0, `column_map` TEXT NULL, `error_report_path` VARCHAR(255) NULL, `created_by` INT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `completed_at` DATETIME NULL, PRIMARY KEY (`id`), UNIQUE KEY `uq_imp_file` (`import_type`,`file_sha256`))",
            "CREATE TABLE IF NOT EXISTS `crm_import_rows` (`id` BIGINT NOT NULL AUTO_INCREMENT, `job_id` INT NOT NULL, `row_no` INT NOT NULL, `raw_data` TEXT NOT NULL, `status` ENUM('valid','invalid','duplicate','imported') NOT NULL,
              `errors` VARCHAR(500) NULL, `entity_id` INT NULL, PRIMARY KEY (`id`), KEY `idx_ir_job` (`job_id`,`status`), CONSTRAINT `fk_ir_job` FOREIGN KEY (`job_id`) REFERENCES `crm_import_jobs`(`id`) ON DELETE CASCADE)",
            "CREATE TABLE IF NOT EXISTS `crm_demo_registry` (`table_name` VARCHAR(60) NOT NULL, `row_id` BIGINT NOT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`table_name`,`row_id`))",
            "CREATE TABLE IF NOT EXISTS `crm_demo_reset_log` (`id` INT NOT NULL AUTO_INCREMENT, `initiated_by` INT NULL, `snapshot_path` VARCHAR(255) NULL, `rows_removed` INT NOT NULL DEFAULT 0, `rows_created` INT NOT NULL DEFAULT 0,
              `integrity_ok` TINYINT(1) NOT NULL DEFAULT 0, `notes` VARCHAR(500) NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`))",
        ];
    }
}
