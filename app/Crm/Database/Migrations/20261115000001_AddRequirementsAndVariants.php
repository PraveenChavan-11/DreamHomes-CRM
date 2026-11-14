<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Variant / size / manual-price support, evolving customer requirements per follow-up, and per-revision change tracking. */
class AddRequirementsAndVariants extends Migration
{
    private const OPTS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    public function up(): void
    {
        $this->addCols('crm_quotation_items', [
            'variant'     => 'VARCHAR(150) NULL AFTER `description`',
            'width'       => 'DECIMAL(10,2) NULL',
            'height'      => 'DECIMAL(10,2) NULL',
            'size_unit'   => 'VARCHAR(5) NULL',
            'nos'         => 'DECIMAL(10,2) NULL',
            'price_source' => "VARCHAR(10) NOT NULL DEFAULT 'catalogue'",
        ]);
        $this->addCols('crm_quotation_versions', [
            'revision_reason'    => 'VARCHAR(500) NULL',
            'source_followup_id' => 'BIGINT NULL',
        ]);
        $this->db->query("CREATE TABLE IF NOT EXISTS `crm_requirements` (
            `id` INT NOT NULL AUTO_INCREMENT, `enquiry_id` INT NOT NULL, `product_id` INT NULL, `description` VARCHAR(500) NOT NULL, `variant` VARCHAR(150) NULL,
            `width` DECIMAL(10,2) NULL, `height` DECIMAL(10,2) NULL, `size_unit` VARCHAR(5) NULL, `nos` DECIMAL(10,2) NULL, `quantity` DECIMAL(12,3) NOT NULL DEFAULT 1,
            `unit` VARCHAR(20) NOT NULL DEFAULT 'nos', `expected_price` DECIMAL(14,2) NULL, `notes` VARCHAR(500) NULL, `status` ENUM('active','dropped') NOT NULL DEFAULT 'active',
            `created_by` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), KEY `idx_req_enq` (`enquiry_id`,`status`), CONSTRAINT `fk_req_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries`(`id`)) " . self::OPTS);
        $this->db->query("CREATE TABLE IF NOT EXISTS `crm_requirement_changes` (
            `id` BIGINT NOT NULL AUTO_INCREMENT, `enquiry_id` INT NOT NULL, `requirement_id` INT NOT NULL, `followup_id` BIGINT NULL, `action` ENUM('added','updated','dropped','restored') NOT NULL,
            `before_json` TEXT NULL, `after_json` TEXT NULL, `reason` VARCHAR(500) NULL, `user_id` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), KEY `idx_rc_enq` (`enquiry_id`,`created_at`), KEY `idx_rc_req` (`requirement_id`)) " . self::OPTS);
        $this->db->query("CREATE TABLE IF NOT EXISTS `crm_quotation_changes` (
            `id` BIGINT NOT NULL AUTO_INCREMENT, `quotation_id` INT NOT NULL, `from_version_no` INT NOT NULL, `to_version_no` INT NOT NULL,
            `change_type` ENUM('added','removed','modified','totals','terms') NOT NULL, `item_ref` VARCHAR(255) NULL, `detail` TEXT NULL, `reason` VARCHAR(500) NULL,
            `user_id` INT NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), KEY `idx_qch_qt` (`quotation_id`,`to_version_no`), CONSTRAINT `fk_qch_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations`(`id`)) " . self::OPTS);
    }

    public function down(): void
    {
        foreach (['crm_quotation_changes', 'crm_requirement_changes', 'crm_requirements'] as $t) {
            $this->db->query("DROP TABLE IF EXISTS `{$t}`");
        }
        // added columns are left in place (non-destructive rollback)
    }

    private function addCols(string $table, array $cols): void
    {
        $have = array_column($this->db->query("SHOW COLUMNS FROM `{$table}`")->getResultArray(), 'Field');
        foreach ($cols as $c => $def) {
            if (! in_array($c, $have, true)) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$c}` {$def}");
            }
        }
    }
}
