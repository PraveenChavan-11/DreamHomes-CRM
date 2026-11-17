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

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $db = self::db();
            if (! $db->tableExists('crm_tags')) {
                $db->query("CREATE TABLE IF NOT EXISTS `crm_tags` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(40) NOT NULL UNIQUE,
                    `label` VARCHAR(60) NOT NULL,
                    `active` TINYINT(1) NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (! $db->tableExists('crm_territories')) {
                $db->query("CREATE TABLE IF NOT EXISTS `crm_territories` (
                    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL UNIQUE,
                    `city` VARCHAR(100) NULL,
                    `pin_prefix` VARCHAR(6) NULL,
                    `team_id` INT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (! $db->tableExists('crm_product_subcategories')) {
                $db->query("CREATE TABLE IF NOT EXISTS `crm_product_subcategories` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `category_id` INT UNSIGNED NOT NULL,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `description` TEXT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (! $db->tableExists('crm_product_types')) {
                $db->query("CREATE TABLE IF NOT EXISTS `crm_product_types` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `category_id` INT UNSIGNED NOT NULL,
                    `subcategory_id` INT UNSIGNED NULL,
                    `brand_id` INT UNSIGNED NULL,
                    `name` VARCHAR(150) NOT NULL,
                    `code` VARCHAR(60) NOT NULL UNIQUE,
                    `description` TEXT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
            if (! $db->tableExists('crm_projects') || ! $db->tableExists('crm_master_records') || ! $db->tableExists('crm_organisations') || ! $db->tableExists('crm_quotation_approvals') || ! $db->tableExists('crm_sales_activities') || ! $db->tableExists('crm_sales_targets')) {
                $files = glob(APPPATH . 'Crm/Database/Migrations/*.php');
                if ($files) {
                    sort($files);
                    foreach ($files as $file) {
                        require_once $file;
                        $class = 'Crm\\Database\\Migrations\\' . preg_replace('/^\d+_/', '', basename($file, '.php'));
                        if (class_exists($class)) {
                            (new $class())->up();
                        }
                    }
                }
                ReferenceData::seedAll();
            }
            if ($db->tableExists('crm_projects')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_projects')) {
                        $db->query("ALTER TABLE crm_projects ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_site_visits')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_site_visits')) {
                        $db->query("ALTER TABLE crm_site_visits ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_customers')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'primary_contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_customers')) {
                        $db->query("ALTER TABLE crm_customers ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_enquiries')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_enquiries')) {
                        $db->query("ALTER TABLE crm_enquiries ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_quotations')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_quotations')) {
                        $db->query("ALTER TABLE crm_quotations ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_sales_orders')) {
                foreach (['organisation_id' => 'INT UNSIGNED NULL', 'contact_id' => 'INT UNSIGNED NULL'] as $col => $type) {
                    if (! $db->fieldExists($col, 'crm_sales_orders')) {
                        $db->query("ALTER TABLE crm_sales_orders ADD COLUMN {$col} {$type}");
                    }
                }
            }
            if ($db->tableExists('crm_status_transitions')) {
                $active = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation'];
                foreach ($active as $f) {
                    foreach ($active as $t) {
                        if ($f !== $t) {
                            $db->query("INSERT IGNORE INTO crm_status_transitions (entity, from_status, to_status, required_permission, requires_reason) VALUES ('enquiry', ?, ?, 'enquiries.edit', 0)", [$f, $t]);
                        }
                    }
                }
            }
            if ($db->tableExists('crm_organisations') && $db->table('crm_organisations')->countAllResults() === 0 && $db->tableExists('crm_customers') && $db->table('crm_customers')->countAllResults() > 0) {
                OrganisationService::syncFromCustomers();
            }
            if ($db->tableExists('crm_approval_rules') && $db->table('crm_approval_rules')->countAllResults() === 0) {
                ApprovalService::seedDefaultRules();
            }
            if ($db->tableExists('crm_email_templates') && $db->table('crm_email_templates')->countAllResults() === 0) {
                CommunicationService::seedAll28Templates();
            }
        } catch (\Throwable $e) {
            log_message('error', 'Crm::ensureSchema failed: ' . $e->getMessage());
        }
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
