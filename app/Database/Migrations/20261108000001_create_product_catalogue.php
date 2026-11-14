<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Product catalogue (WorkSphere and future TalentHub products).
 *
 * Additive and idempotent. A legacy `products` table (retired SocietyHub / PeopleHub placeholder rows, created by
 * 20261105000001) may already exist with a narrower column set: it is EXTENDED in place (columns added, nothing dropped,
 * legacy rows untouched). On a fresh database the table is created with the legacy columns too, so the older
 * migration's INSERT IGNORE keeps working. Also called lazily at runtime by ProductService (no shell on Hostinger).
 * down() drops only the child tables; the shared `products` table is left in place.
 */
class CreateProductCatalogue extends Migration
{
    public function up()
    {
        self::apply($this->db);
    }

    public static function apply(\CodeIgniter\Database\BaseConnection $db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(100) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `tagline` VARCHAR(255) NULL,
            `summary` TEXT NULL COMMENT 'legacy',
            `features` TEXT NULL COMMENT 'legacy JSON list',
            `product_url` VARCHAR(255) NULL COMMENT 'legacy',
            `meta_title` VARCHAR(255) NULL COMMENT 'legacy',
            `meta_description` VARCHAR(320) NULL COMMENT 'legacy',
            `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'legacy',
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_products_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Columns added to the (possibly legacy) table. name => definition.
        $cols = [
            'short_description'  => 'TEXT NULL',
            'long_description'   => 'MEDIUMTEXT NULL',
            'category'           => 'VARCHAR(80) NULL',
            'is_featured'        => 'TINYINT(1) NOT NULL DEFAULT 0',
            'is_flagship'        => 'TINYINT(1) NOT NULL DEFAULT 0',
            'display_order'      => 'INT NOT NULL DEFAULT 0',
            'logo_path'          => 'VARCHAR(255) NULL',
            'hero_image_path'    => 'VARCHAR(255) NULL',
            'hero_video_url'     => 'VARCHAR(500) NULL',
            'demo_url'           => 'VARCHAR(500) NULL',
            'signup_url'         => 'VARCHAR(500) NULL',
            'docs_url'           => 'VARCHAR(500) NULL',
            'pricing_summary'    => 'VARCHAR(255) NULL',
            'cta_label'          => 'VARCHAR(80) NULL',
            'cta_url'            => 'VARCHAR(500) NULL',
            'primary_cta_type'   => "ENUM('enquiry','demo','external') NOT NULL DEFAULT 'enquiry'",
            'seo_title'          => 'VARCHAR(255) NULL',
            'seo_description'    => 'VARCHAR(320) NULL',
            'og_image_path'      => 'VARCHAR(255) NULL',
            'canonical_url'      => 'VARCHAR(500) NULL',
            'no_index'           => 'TINYINT(1) NOT NULL DEFAULT 0',
            'created_by'         => 'INT UNSIGNED NULL',
            'updated_by'         => 'INT UNSIGNED NULL',
            'published_at'       => 'DATETIME NULL',
        ];
        $existing = [];
        foreach ($db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products'")->getResultArray() as $r) {
            $existing[strtolower($r['COLUMN_NAME'])] = true;
        }
        foreach ($cols as $name => $def) {
            if (!isset($existing[$name])) {
                $db->query("ALTER TABLE `products` ADD COLUMN `$name` $def");
            }
        }
        // The legacy default was 'published'; new rows must be explicit drafts unless set otherwise.
        $db->query("ALTER TABLE `products` MODIFY `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft'");
        if (!self::hasIndex($db, 'products', 'idx_products_listing')) {
            $db->query('ALTER TABLE `products` ADD KEY `idx_products_listing` (`status`,`deleted_at`,`is_flagship`,`is_featured`,`display_order`)');
        }

        $child = static function (string $table, string $body) use ($db): void {
            $db->query("CREATE TABLE IF NOT EXISTS `$table` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT UNSIGNED NOT NULL,
                $body
                `sort` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_{$table}_product` (`product_id`,`sort`),
                CONSTRAINT `fk_{$table}_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        };
        $child('product_features', '`title` VARCHAR(160) NOT NULL, `description` VARCHAR(600) NULL, `icon` VARCHAR(40) NULL,');
        $child('product_modules', '`title` VARCHAR(160) NOT NULL, `description` VARCHAR(600) NULL,');
        $child('product_benefits', '`title` VARCHAR(160) NOT NULL, `description` VARCHAR(600) NULL, `audience` VARCHAR(80) NULL,');
        $child('product_audiences', '`title` VARCHAR(160) NOT NULL, `description` VARCHAR(600) NULL,');
        $child('product_faqs', '`question` VARCHAR(255) NOT NULL, `answer` TEXT NOT NULL,');
        $child('product_media', "`kind` ENUM('screenshot','video','gallery') NOT NULL DEFAULT 'screenshot', `path_or_url` VARCHAR(500) NOT NULL, `caption` VARCHAR(255) NULL, `alt_text` VARCHAR(255) NULL,");
        // Future-proof extra sections (plain text body, rendered escaped) keyed by a short section_key.
        $child('product_sections', '`section_key` VARCHAR(60) NOT NULL, `title` VARCHAR(160) NULL, `body` TEXT NULL,');

        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`,`name`,`category`,`created_at`) VALUES
                ('products.view','View Product Catalogue','Product Catalogue',NOW()),
                ('products.manage','Create, Edit, Publish & Delete Products','Product Catalogue',NOW())");
            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`,`permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.code IN ('products.view','products.manage')
                    WHERE r.slug = 'super_admin'");
            }
        }

        if ($db->tableExists('website_settings')) {
            $db->query("INSERT IGNORE INTO `website_settings` (`setting_key`,`setting_value`,`setting_group`,`is_public`)
                VALUES ('worksphere_signup_url', ?, 'products', 1)", [self::DEFAULT_SIGNUP_URL]);
        }

        self::seedWorkSphere($db);
        self::upgradeWorkSphereCommercial($db);
    }

    public const DEFAULT_SIGNUP_URL = 'https://worksphere.talenthubconsultant.com/register';

    private static function hasIndex(\CodeIgniter\Database\BaseConnection $db, string $table, string $index): bool
    {
        return (bool) $db->query("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1", [$table, $index])->getRow();
    }

    /**
     * Seeds WorkSphere once. If a row with the slug exists (even soft-deleted or edited) nothing is touched,
     * so admin edits and deletions are never overwritten by a later lazy apply().
     * Content is limited to capabilities that exist in the WorkSphere product (README / module inventory).
     */
    /** website_settings key recording that the commercial-models content below has been applied once. */
    public const COMMERCIAL_MARKER = 'worksphere_commercial_models_v1';

    private const OLD_PRICING_SUMMARY = 'Start with a free 7-day demo. Talk to us for plans that fit your organisation.';
    private const NEW_PRICING_SUMMARY = 'Subscription plans for organisations, or a licence with source code for IT and software companies. Start with a free 7-day demo.';

    /**
     * One-time content update for the WorkSphere page: how to adopt the product (subscription for an organisation, or a
     * licence with source code for an IT company). Additive and safe to run on a live database:
     *  - runs once (marker row), so later admin edits or deletions are never overwritten;
     *  - the pricing line is replaced only if it still has the original seeded text;
     *  - a section / FAQ is added only if no row with the same key / question exists.
     * Nothing here states a price, a guarantee or a delivery promise: terms are agreed in writing.
     */
    public static function upgradeWorkSphereCommercial(\CodeIgniter\Database\BaseConnection $db): void
    {
        if (!$db->tableExists('website_settings') || !$db->tableExists('product_sections') || !$db->tableExists('product_faqs')) {
            return;
        }
        if ($db->query("SELECT 1 FROM website_settings WHERE setting_key = ? LIMIT 1", [self::COMMERCIAL_MARKER])->getRow()) {
            return;
        }
        $product = $db->query("SELECT id FROM products WHERE slug = 'worksphere' AND deleted_at IS NULL LIMIT 1")->getRow();
        if (!$product) {
            return;                                   // not seeded yet (or deleted by an admin): try again later, change nothing
        }
        $pid = (int) $product->id;
        $now = date('Y-m-d H:i:s');

        $db->transStart();
        $db->query("UPDATE products SET pricing_summary = ?, updated_at = ? WHERE id = ? AND pricing_summary = ?",
            [self::NEW_PRICING_SUMMARY, $now, $pid, self::OLD_PRICING_SUMMARY]);

        $sections = [
            ['ways-to-adopt', 'Two ways to adopt WorkSphere',
                "Subscription (software as a service). Your organisation uses WorkSphere as a managed service with its own separate workspace. We host it and keep it updated, and you pay a subscription based on the plan and the number of employees, billed monthly, quarterly or yearly. Start with a free 7-day demo, then a live evaluation period, and move to a paid plan only when you decide to.\n\n"
                . "Licence with source code (for IT and software companies). A company that wants to own the product can license WorkSphere together with its source code, to host, brand, extend and offer to its own customers. What is handed over, the commercial terms and the support period are agreed with TalentHub in a written agreement before anything is delivered.\n\n"
                . "Not sure which fits? Tell us about your organisation in the enquiry form below and we will recommend one."],
            ['licence-for-it-companies', 'Licensing WorkSphere to an IT company',
                "A licence discussion normally covers: the rights you receive and any limits on them; the source code and documentation to be handed over; deployment and knowledge-transfer support; branding and white-labelling; customisation you want built; the support and maintenance period after delivery; and the price and payment schedule.\n\n"
                . "These are discussion points, not a fixed price list. We scope each licence individually and put the outcome in a written agreement. Organisations that only want to use WorkSphere do not need a licence: a subscription gives full use of the hosted service."],
        ];
        foreach ($sections as $i => [$key, $title, $body]) {
            if (!$db->query("SELECT 1 FROM product_sections WHERE product_id = ? AND section_key = ? LIMIT 1", [$pid, $key])->getRow()) {
                $db->table('product_sections')->insert(['product_id' => $pid, 'sort' => 90 + $i, 'section_key' => $key, 'title' => $title, 'body' => $body]);
            }
        }

        $faqs = [
            ['Can we buy WorkSphere outright instead of subscribing?',
                'IT and software companies can license WorkSphere together with its source code. The scope, price and support are agreed in a written agreement. Use the enquiry form on this page and choose "Licence with source code".'],
            ['What does a subscription include?',
                'A separate hosted workspace for your organisation, the modules in your plan, updates, and GST invoices. Plans differ by employee count and billing period. A subscription gives use of the service; it does not include the source code.'],
            ['Do subscription customers receive the source code?',
                'No. Source code is provided only under a licence agreement with TalentHub.'],
            ['Can an IT company resell or white-label WorkSphere?',
                'That is part of the licence discussion: branding, hosting and resale rights are agreed in writing, so tell us in the enquiry form how you plan to use it.'],
        ];
        $sort = (int) ($db->query("SELECT COALESCE(MAX(sort), 0) AS m FROM product_faqs WHERE product_id = ?", [$pid])->getRow()->m ?? 0);
        foreach ($faqs as [$q, $a]) {
            if (!$db->query("SELECT 1 FROM product_faqs WHERE product_id = ? AND question = ? LIMIT 1", [$pid, $q])->getRow()) {
                $db->table('product_faqs')->insert(['product_id' => $pid, 'sort' => ++$sort, 'question' => $q, 'answer' => $a]);
            }
        }

        $db->query("INSERT IGNORE INTO `website_settings` (`setting_key`,`setting_value`,`setting_group`,`is_public`) VALUES (?, ?, 'products', 0)",
            [self::COMMERCIAL_MARKER, $now]);
        $db->transComplete();
    }

    private static function seedWorkSphere(\CodeIgniter\Database\BaseConnection $db): void
    {
        if ($db->query("SELECT 1 FROM products WHERE slug = 'worksphere' LIMIT 1")->getRow()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $signup = self::DEFAULT_SIGNUP_URL;
        if ($db->tableExists('website_settings')) {
            $row = $db->query("SELECT setting_value FROM website_settings WHERE setting_key = 'worksphere_signup_url' LIMIT 1")->getRow();
            if ($row && preg_match('#^https?://#i', (string) $row->setting_value)) {
                $signup = (string) $row->setting_value;
            }
        }

        $db->transStart();
        $db->table('products')->insert([
            'slug'              => 'worksphere',
            'name'              => 'WorkSphere',
            'tagline'           => 'HRMS, payroll and workforce management for growing organisations',
            'short_description' => 'A multi-tenant HRMS and payroll SaaS that brings employee records, attendance, leave, payroll, projects, recruitment, performance, letters and compliance into one role-based workspace.',
            'long_description'  => "WorkSphere is a cloud HRMS and payroll platform from TalentHub. Each organisation gets its own isolated workspace, with role-based access so employees, managers, HR and administrators each see the right tools.\n\nIt covers the employee lifecycle from recruitment and onboarding through attendance, leave, projects and tasks, payroll and statutory compliance, performance, and HR letters and documents. You can try it with a free 7-day demo workspace, or talk to our team about your requirements.",
            'category'          => 'HRMS & Payroll',
            'status'            => 'published',
            'is_featured'       => 1,
            'is_flagship'       => 1,
            'display_order'     => 1,
            'logo_path'         => 'assets/img/products/worksphere-mark.png',
            'signup_url'        => $signup,
            'pricing_summary'   => 'Start with a free 7-day demo. Talk to us for plans that fit your organisation.',
            'cta_label'         => 'Start free 7-day demo',
            'primary_cta_type'  => 'demo',
            'seo_title'         => 'WorkSphere HRMS & Payroll Software | TalentHub',
            'seo_description'   => 'WorkSphere is a multi-tenant HRMS and payroll platform from TalentHub: employees, attendance, leave, payroll, projects, recruitment, performance, letters and compliance. Try the free 7-day demo.',
            'published_at'      => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $pid = (int) $db->insertID();

        $ins = static function (string $table, array $rows) use ($db, $pid): void {
            foreach ($rows as $i => $r) {
                $db->table($table)->insert($r + ['product_id' => $pid, 'sort' => $i + 1]);
            }
        };

        $ins('product_features', [
            ['title' => 'Employee records', 'description' => 'A single employee master with profile, job, documents and history, plus a searchable directory.', 'icon' => 'users'],
            ['title' => 'Attendance and shifts', 'description' => 'Check-in and check-out, shift rosters, regularisation requests and biometric device integration.', 'icon' => 'clock'],
            ['title' => 'Leave management', 'description' => 'Configurable leave types and policies, balances, holiday calendars and multi-level approvals.', 'icon' => 'calendar'],
            ['title' => 'Payroll processing', 'description' => 'Salary structures, monthly payroll runs, payslips and salary advances.', 'icon' => 'wallet'],
            ['title' => 'Projects and tasks', 'description' => 'Projects, task assignment, timesheets and resource utilisation reports.', 'icon' => 'layers'],
            ['title' => 'Recruitment', 'description' => 'Candidate pipeline, interview tracking and offer generation inside the same system.', 'icon' => 'briefcase'],
            ['title' => 'Performance', 'description' => 'KRAs, goals and OKRs, and appraisal cycles with self and manager reviews.', 'icon' => 'target'],
            ['title' => 'HR letters and documents', 'description' => 'Template-driven letters such as offer and appointment letters, generated from employee data.', 'icon' => 'file'],
            ['title' => 'Statutory compliance', 'description' => 'PF, ESIC, Professional Tax and TDS handling for Indian payroll, with compliance records.', 'icon' => 'shield'],
            ['title' => 'Role-based access and audit trail', 'description' => 'Granular roles and permissions, with an audit log of changes.', 'icon' => 'lock'],
        ]);
        $ins('product_modules', [
            ['title' => 'Organisation and employee master', 'description' => 'Company, units, departments, designations and employee records.'],
            ['title' => 'Recruitment and onboarding', 'description' => 'Candidate pipeline, offers and Day-1 onboarding.'],
            ['title' => 'Attendance and shifts', 'description' => 'Attendance capture, shift rosters and regularisation.'],
            ['title' => 'Leave', 'description' => 'Leave policies, balances, holidays and approvals.'],
            ['title' => 'Projects, tasks and timesheets', 'description' => 'Project master, tasks and resource timesheets.'],
            ['title' => 'Payroll and salary', 'description' => 'Salary components, payroll runs and payslips.'],
            ['title' => 'Statutory compliance', 'description' => 'PF, ESIC, Professional Tax and TDS.'],
            ['title' => 'Performance', 'description' => 'Goals, OKRs and appraisal cycles.'],
            ['title' => 'Letters and documents', 'description' => 'HR letter templates and document generation.'],
            ['title' => 'Meetings and action items', 'description' => 'Minutes of meeting converted into trackable tasks.'],
            ['title' => 'Helpdesk', 'description' => 'IT and HR support tickets linked to task resolution.'],
            ['title' => 'Administration and audit', 'description' => 'Roles, permissions, settings and audit logs.'],
        ]);
        $ins('product_benefits', [
            ['title' => 'One system instead of many', 'description' => 'HR records, attendance, leave, payroll and projects share the same employee data, so there is less re-entry and fewer mismatches.', 'audience' => 'Organisation'],
            ['title' => 'The right tools for each role', 'description' => 'Employees, managers, HR and administrators get workspaces limited to what their role needs.', 'audience' => 'Organisation'],
            ['title' => 'Payroll with Indian statutory rules', 'description' => 'PF, ESIC, Professional Tax and TDS are part of payroll, so HR spends less time on manual calculations.', 'audience' => 'HR and finance'],
            ['title' => 'Self-service for employees', 'description' => 'Employees can apply for leave, view payslips and raise requests without going through HR for every step.', 'audience' => 'Employees'],
            ['title' => 'Visibility for managers', 'description' => 'Managers approve leave and attendance requests and follow team tasks and timesheets.', 'audience' => 'Managers'],
            ['title' => 'Control and accountability for administrators', 'description' => 'Permission management and an audit trail of changes.', 'audience' => 'Administrators'],
        ]);
        $ins('product_audiences', [
            ['title' => 'HR managers and operations heads', 'description' => 'Run the employee lifecycle, payroll cycles, recruitment and compliance.'],
            ['title' => 'Managers and team leads', 'description' => 'Approve requests and track team work.'],
            ['title' => 'Employees', 'description' => 'Self-service for leave, attendance, payslips and documents.'],
            ['title' => 'Administrators', 'description' => 'Configure roles, permissions, policies and settings.'],
        ]);
        $ins('product_faqs', [
            ['question' => 'What is included in the free 7-day demo?', 'answer' => 'The demo gives you a trial workspace for 7 days so you can explore the product yourself. Sign-up is on the WorkSphere site. The demo is for evaluation: please do not enter real employee data.'],
            ['question' => 'Can WorkSphere be used by more than one organisation?', 'answer' => 'Yes. WorkSphere is multi-tenant: each organisation has its own separate workspace, users and data.'],
            ['question' => 'Does it support Indian payroll compliance?', 'answer' => 'WorkSphere includes PF, ESIC, Professional Tax and TDS handling in payroll. Please talk to us about your specific statutory setup before you rely on it for live payroll.'],
            ['question' => 'How do I talk to someone before trying it?', 'answer' => 'Use the enquiry form on this page and a TalentHub consultant will contact you to understand your requirements.'],
            ['question' => 'Can WorkSphere be customised for our process?', 'answer' => 'TalentHub also builds custom software and ERP/HRMS workflows. Tell us what you need through the enquiry form and we will discuss scope with you.'],
        ]);
        $db->transComplete();
    }

    public function down()
    {
        foreach (['product_sections', 'product_media', 'product_faqs', 'product_audiences', 'product_benefits', 'product_modules', 'product_features'] as $t) {
            $this->db->query("DROP TABLE IF EXISTS `$t`");
        }
    }
}
