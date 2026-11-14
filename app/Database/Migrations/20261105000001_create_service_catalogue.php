<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Additive only: creates service_categories, services, products and seeds capability-led content.
 * Never alters or deletes existing tables/rows. down() drops only the tables created here.
 * Seed text makes no guarantees and no metrics; business must review before production.
 */
class CreateServiceCatalogue extends Migration
{
    public function up()
    {
        self::apply($this->db);
    }

    /** Idempotent; also called lazily at runtime so deployments need no manual migrate step. */
    public static function apply(\CodeIgniter\Database\BaseConnection $db): void
    {

        $db->query("CREATE TABLE IF NOT EXISTS `service_categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(100) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `tagline` VARCHAR(255) NULL,
            `summary` TEXT NULL,
            `highlights` TEXT NULL COMMENT 'JSON list of capability names',
            `meta_title` VARCHAR(255) NULL,
            `meta_description` VARCHAR(320) NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_service_categories_slug` (`slug`),
            KEY `idx_service_categories_status_sort` (`status`,`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `services` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `category_id` INT UNSIGNED NOT NULL,
            `slug` VARCHAR(120) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `summary` VARCHAR(500) NULL,
            `description` TEXT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` VARCHAR(320) NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_services_slug` (`slug`),
            KEY `idx_services_category` (`category_id`,`status`,`sort_order`),
            CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(100) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `tagline` VARCHAR(255) NULL,
            `summary` TEXT NULL,
            `features` TEXT NULL COMMENT 'JSON list',
            `product_url` VARCHAR(255) NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` VARCHAR(320) NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_products_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $now = date('Y-m-d H:i:s');

        $cats = [
            ['recruitment', 'Recruitment & Talent Acquisition', 'Pan-India hiring for startups, SMEs and enterprises.', 'Permanent, contract and specialist hiring across India, from sourcing and screening to interview coordination.', null],
            ['it-services', 'IT Services & Software Development', 'Web, mobile and enterprise software built to requirements.', 'Custom software, web and mobile applications, APIs and modernisation of existing systems.', null],
            ['erp-hrms', 'ERP, HRMS & Enterprise Automation', 'Digitise operations: HR, payroll, inventory and workflows.', 'Business-process software and automation tailored to how your organisation works.', ['HRMS Development', 'Payroll Management Systems', 'Employee Self-Service Portals', 'Attendance and Leave Management', 'Recruitment Management Systems', 'ERP Manufacturing Workflows', 'Inventory Management', 'Billing and Reporting Automation', 'Workflow Automation', 'Excel VBA Automation', 'Business Process Digitisation', 'Custom Enterprise Dashboards', 'Legacy ERP Integration']],
            ['qa-testing', 'Quality Assurance & Software Testing', 'Structured testing from requirements to UAT sign-off.', 'Requirement Analysis, Test Planning, Test Execution, Defect Reporting, Retesting, Regression and UAT Sign-off.', ['Manual and Functional Testing', 'Regression Testing', 'API and REST API Testing (Postman)', 'Integration Testing', 'UAT Support', 'Database Validation', 'Cross-browser and Responsive Testing', 'Bug Tracking and Defect Management', 'Test Case Documentation', 'Release Validation']],
            ['digital-transformation', 'Digital Transformation & IT Consulting', 'Clear requirements, sound architecture, controlled delivery.', 'Advisory that turns business needs into specified, planned and delivered technology change.', ['Business Requirement Gathering', 'Business Analysis', 'Digital Transformation Consulting', 'Process Automation', 'Software Requirement Specification', 'System Architecture Consulting', 'Technology Advisory', 'Project Management', 'Application Modernisation', 'Workflow Optimisation', 'Technical Documentation', 'UAT and Release Management']],
            ['digital-marketing', 'Digital Marketing & Online Growth', 'Technical SEO and website performance, measured honestly.', 'Search and site-quality work. We do not guarantee rankings or traffic.', ['Technical SEO', 'On-page SEO', 'Off-page SEO', 'Local and National SEO', 'Website Performance Optimisation', 'Google Analytics and Search Console Integration', 'Sitemap Management and Schema Markup', 'Conversion and Landing Page Optimisation', 'Website Maintenance', 'Digital Presence Consulting']],
        ];
        $catId = [];
        foreach ($cats as $i => $c) {
            $db->query("INSERT IGNORE INTO service_categories (slug,name,tagline,summary,highlights,sort_order,status,created_at,updated_at) VALUES (?,?,?,?,?,?, 'published',?,?)",
                [$c[0], $c[1], $c[2], $c[3], $c[4] ? json_encode($c[4]) : null, $i + 1, $now, $now]);
            $catId[$c[0]] = (int) $db->query('SELECT id FROM service_categories WHERE slug=?', [$c[0]])->getRow()->id;
        }

        $svc = [
            'recruitment' => [
                ['permanent-recruitment', 'Permanent Recruitment', 'Full-time hiring across functions and seniority levels.'],
                ['contract-staffing', 'Contract Staffing', 'Contract and temporary resources for project and seasonal needs.'],
                ['it-recruitment', 'IT Recruitment', 'Hiring for software, data, infrastructure and QA roles.'],
                ['non-it-recruitment', 'Non-IT Recruitment', 'Hiring for operations, finance, HR, sales and engineering roles.'],
                ['executive-search', 'Executive Search', 'Confidential search for senior and leadership roles.'],
                ['bulk-hiring', 'Bulk Hiring', 'Structured high-volume hiring drives.'],
                ['campus-recruitment', 'Campus Recruitment', 'Coordination of campus hiring with educational institutions.'],
                ['technical-recruitment', 'Technical Recruitment', 'Skill-assessed hiring for engineering and technical roles.'],
                ['rpo', 'Recruitment Process Outsourcing (RPO)', 'Run all or part of your hiring process through TalentHub.'],
                ['resume-screening-shortlisting', 'Resume Screening and Shortlisting', 'Requirement-matched screening so you review fewer, better-fit profiles.'],
                ['candidate-sourcing', 'Candidate Sourcing', 'Active sourcing for hard-to-fill and niche roles.'],
                ['interview-coordination', 'Interview Coordination', 'Scheduling and follow-up between candidates and hiring teams.'],
                ['workforce-planning', 'Workforce Planning', 'Plan headcount and hiring timelines against business goals.'],
            ],
            'it-services' => [
                ['custom-software-development', 'Custom Software Development', 'Software designed around your processes and requirements.'],
                ['website-development', 'Website Development', 'Responsive, SEO-ready business websites.'],
                ['web-application-development', 'Web Application Development', 'Secure, database-backed web applications.'],
                ['enterprise-portal-development', 'Enterprise Portal Development', 'Role-based portals for employees, partners and customers.'],
                ['mobile-application-development', 'Mobile Application Development', 'Mobile apps and installable web apps (PWA).'],
                ['react-frontend-development', 'React and Modern Frontend Development', 'Component-based, accessible user interfaces.'],
                ['php-codeigniter-development', 'PHP and CodeIgniter Development', 'Backend and full-stack development on PHP and CodeIgniter.'],
                ['python-django-development', 'Python and Django Development', 'Backend development with Python and Django.'],
                ['api-development-integration', 'API Development and Integration', 'REST APIs and third-party integrations.'],
                ['backend-system-development', 'Backend System Development', 'Services, jobs and workflows behind your applications.'],
                ['database-architecture', 'Database Architecture', 'Schema design, indexing and migration planning (MySQL, PostgreSQL).'],
                ['cloud-application-integration', 'Cloud Application Integration', 'Connect applications to cloud services and hosting.'],
                ['legacy-system-modernisation', 'Legacy System Modernisation', 'Incremental modernisation without disrupting live operations.'],
            ],
        ];
        foreach ($svc as $cslug => $items) {
            foreach ($items as $i => $s) {
                $db->query("INSERT IGNORE INTO services (category_id,slug,name,summary,sort_order,status,created_at,updated_at) VALUES (?,?,?,?,?, 'published',?,?)",
                    [$catId[$cslug], $s[0], $s[1], $s[2], $i + 1, $now, $now]);
            }
        }

        $db->query("INSERT IGNORE INTO products (slug,name,tagline,summary,features,sort_order,status,created_at,updated_at) VALUES
            ('societyhub','SocietyHub','Society Management System','A society management system for housing societies and residential communities. Product details to be confirmed by TalentHub before publishing claims.',?,1,'published',?,?),
            ('peoplehub','PeopleHub','HRM & Payroll System','An HRM and payroll product for managing employee records, attendance, leave and payroll. Working name; details to be confirmed by TalentHub.',?,2,'published',?,?)",
            [json_encode(['Member and unit management', 'Maintenance billing and collections', 'Notices and communication', 'Reports']), $now, $now,
             json_encode(['Employee records', 'Attendance and leave', 'Payroll processing', 'Employee self-service', 'Reports']), $now, $now]);
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `services`');
        $this->db->query('DROP TABLE IF EXISTS `service_categories`');
        $this->db->query('DROP TABLE IF EXISTS `products`');
    }
}
