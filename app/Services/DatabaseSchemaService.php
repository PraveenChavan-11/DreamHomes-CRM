<?php

namespace App\Services;

class DatabaseSchemaService
{
    private static bool $bootstrapped = false;

    /**
     * Ensure all required database tables and initial seeds exist.
     * Runs in milliseconds and caches execution per request.
     */
    public static function ensureSchema(bool $force = false): array
    {
        if (self::$bootstrapped && !$force) {
            return ['success' => true, 'message' => 'Schema already verified.'];
        }

        $db = \Config\Database::connect();
        $createdTables = [];
        $seededItems = [];

        try {
            // ── 1. ROLES & PERMISSIONS TABLES ─────────────────────────────────────────
            $queries = [
                'roles' => "CREATE TABLE IF NOT EXISTS `roles` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` TEXT NULL,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_role_slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'permissions' => "CREATE TABLE IF NOT EXISTS `permissions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `code` VARCHAR(100) NOT NULL UNIQUE,
                    `name` VARCHAR(150) NOT NULL,
                    `category` VARCHAR(100) NOT NULL,
                    `description` TEXT NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_perm_code` (`code`),
                    KEY `idx_perm_cat` (`category`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'role_permissions' => "CREATE TABLE IF NOT EXISTS `role_permissions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `role_id` INT UNSIGNED NOT NULL,
                    `permission_id` INT UNSIGNED NOT NULL,
                    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
                    KEY `idx_rp_role` (`role_id`),
                    KEY `idx_rp_permission` (`permission_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'user_roles' => "CREATE TABLE IF NOT EXISTS `user_roles` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT UNSIGNED NOT NULL,
                    `role_id` INT UNSIGNED NOT NULL,
                    UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
                    KEY `idx_ur_user` (`user_id`),
                    KEY `idx_ur_role` (`role_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                // ── 2. ENGAGEMENT CENTRE TABLES ───────────────────────────────────────────
                'announcements' => "CREATE TABLE IF NOT EXISTS `announcements` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `internal_description` TEXT NULL,
                    `public_heading` VARCHAR(255) NOT NULL,
                    `public_content` TEXT NOT NULL,
                    `image_url` VARCHAR(500) NULL,
                    `cta_text` VARCHAR(100) NULL,
                    `cta_url` VARCHAR(500) NULL,
                    `announcement_type` VARCHAR(50) NOT NULL DEFAULT 'recruitment_drive',
                    `display_format` VARCHAR(50) NOT NULL DEFAULT 'center_modal',
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NOT NULL,
                    `start_time` TIME NOT NULL DEFAULT '00:00:00',
                    `end_time` TIME NOT NULL DEFAULT '23:59:59',
                    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
                    `audience` VARCHAR(50) NOT NULL DEFAULT 'everyone',
                    `target_pages` TEXT NULL,
                    `frequency` VARCHAR(50) NOT NULL DEFAULT 'once_per_session',
                    `priority` INT NOT NULL DEFAULT 0,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'DRAFT',
                    `impressions_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `clicks_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `dismissals_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `conversions_count` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_ann_status` (`status`),
                    KEY `idx_ann_dates` (`start_date`, `end_date`),
                    KEY `idx_ann_priority` (`priority`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'announcement_events' => "CREATE TABLE IF NOT EXISTS `announcement_events` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `announcement_id` INT UNSIGNED NOT NULL,
                    `event_type` VARCHAR(50) NOT NULL,
                    `ip_hash` VARCHAR(64) NULL,
                    `user_agent_hash` VARCHAR(64) NULL,
                    `session_id` VARCHAR(100) NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_evt_announcement` (`announcement_id`),
                    KEY `idx_evt_type` (`event_type`),
                    KEY `idx_evt_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'newsletter_subscribers' => "CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(255) NOT NULL UNIQUE,
                    `first_name` VARCHAR(100) NULL,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                    `verification_token_hash` VARCHAR(64) NULL,
                    `verification_expires_at` DATETIME NULL,
                    `consent_given` TINYINT(1) NOT NULL DEFAULT 1,
                    `consent_timestamp` DATETIME NULL,
                    `consent_source` VARCHAR(100) NOT NULL DEFAULT 'web_form',
                    `verified_at` DATETIME NULL,
                    `unsubscribed_at` DATETIME NULL,
                    `unsubscribe_reason` TEXT NULL,
                    `unsubscribe_token_hash` VARCHAR(64) NULL,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_sub_email` (`email`),
                    KEY `idx_sub_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'newsletter_preferences' => "CREATE TABLE IF NOT EXISTS `newsletter_preferences` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `subscriber_id` INT UNSIGNED NOT NULL,
                    `category` VARCHAR(100) NOT NULL,
                    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
                    `updated_at` DATETIME NULL,
                    UNIQUE KEY `uk_sub_category` (`subscriber_id`, `category`),
                    KEY `idx_pref_subscriber` (`subscriber_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'newsletter_activity_logs' => "CREATE TABLE IF NOT EXISTS `newsletter_activity_logs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `subscriber_id` INT UNSIGNED NOT NULL,
                    `activity_type` VARCHAR(50) NOT NULL,
                    `activity_timestamp` DATETIME NOT NULL,
                    `source` VARCHAR(100) NULL,
                    `metadata` TEXT NULL,
                    KEY `idx_newsletter_act_subscriber` (`subscriber_id`),
                    KEY `idx_newsletter_act_type` (`activity_type`),
                    CONSTRAINT `fk_newsletter_act_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'job_alert_subscriptions' => "CREATE TABLE IF NOT EXISTS `job_alert_subscriptions` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(255) NOT NULL,
                    `job_category` VARCHAR(100) NULL,
                    `preferred_location` VARCHAR(100) NULL,
                    `experience_level` VARCHAR(50) NULL,
                    `employment_type` VARCHAR(50) NULL,
                    `frequency` VARCHAR(50) NOT NULL DEFAULT 'weekly',
                    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                    `token_hash` VARCHAR(64) NOT NULL,
                    `last_alert_sent_at` DATETIME NULL,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_job_alert_email` (`email`),
                    KEY `idx_job_alert_status` (`status`),
                    KEY `idx_job_alert_token` (`token_hash`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'email_campaigns' => "CREATE TABLE IF NOT EXISTS `email_campaigns` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `campaign_type` VARCHAR(50) NOT NULL DEFAULT 'newsletter',
                    `template_id` INT UNSIGNED NULL,
                    `subject` VARCHAR(255) NOT NULL,
                    `preview_text` VARCHAR(255) NULL,
                    `body_html` MEDIUMTEXT NOT NULL,
                    `body_text` TEXT NULL,
                    `audience_filter` VARCHAR(100) NOT NULL DEFAULT 'all',
                    `status` ENUM('DRAFT', 'SCHEDULED', 'SENDING', 'SENT', 'PAUSED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
                    `scheduled_at` DATETIME NULL,
                    `sent_at` DATETIME NULL,
                    `total_recipients` INT NOT NULL DEFAULT 0,
                    `sent_count` INT NOT NULL DEFAULT 0,
                    `delivered_count` INT NOT NULL DEFAULT 0,
                    `failed_count` INT NOT NULL DEFAULT 0,
                    `created_by` INT UNSIGNED NULL,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_campaign_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'email_delivery_logs' => "CREATE TABLE IF NOT EXISTS `email_delivery_logs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `campaign_id` INT UNSIGNED NULL,
                    `subscriber_id` INT UNSIGNED NULL,
                    `email` VARCHAR(255) NOT NULL,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
                    `tracking_pixel_token` VARCHAR(64) NULL,
                    `opened_at` DATETIME NULL,
                    `clicked_at` DATETIME NULL,
                    `error_message` TEXT NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_del_camp` (`campaign_id`),
                    KEY `idx_del_sub` (`subscriber_id`),
                    KEY `idx_del_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'cookie_consent_logs' => "CREATE TABLE IF NOT EXISTS `cookie_consent_logs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `consent_uuid` VARCHAR(64) NOT NULL,
                    `necessary` TINYINT(1) NOT NULL DEFAULT 1,
                    `analytics` TINYINT(1) NOT NULL DEFAULT 0,
                    `marketing` TINYINT(1) NOT NULL DEFAULT 0,
                    `ip_hash` VARCHAR(64) NULL,
                    `user_agent_hash` VARCHAR(64) NULL,
                    `created_at` DATETIME NULL,
                    KEY `idx_cookie_consent_uuid` (`consent_uuid`),
                    KEY `idx_cookie_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'blog_categories' => "CREATE TABLE IF NOT EXISTS `blog_categories` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` TEXT NULL,
                    `created_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                'blog_posts' => "CREATE TABLE IF NOT EXISTS `blog_posts` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `category_id` INT UNSIGNED NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL UNIQUE,
                    `summary` TEXT NULL,
                    `content` MEDIUMTEXT NOT NULL,
                    `featured_image` VARCHAR(500) NULL,
                    `author_name` VARCHAR(100) DEFAULT 'TalentHub Research',
                    `status` VARCHAR(50) DEFAULT 'PUBLISHED',
                    `views_count` INT UNSIGNED DEFAULT 0,
                    `published_at` DATETIME NULL,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    KEY `idx_blog_slug` (`slug`),
                    KEY `idx_blog_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            ];

            foreach ($queries as $tblName => $ddl) {
                if (!$db->tableExists($tblName)) {
                    $db->query($ddl);
                    $createdTables[] = $tblName;
                }
            }

            // ── 3. EXTEND ENQUIRY TABLES WITH DEADLINES & CLOSURE COLUMNS ───────────────
            $enquiryTables = ['contact_enquiries', 'employer_enquiries', 'applications'];
            foreach ($enquiryTables as $tbl) {
                if ($db->tableExists($tbl)) {
                    $fields = $db->getFieldNames($tbl);
                    if (!in_array('follow_up_deadline', $fields, true)) {
                        $db->query("ALTER TABLE `{$tbl}` ADD COLUMN `follow_up_deadline` DATE NULL;");
                    }
                    if (!in_array('last_status_note', $fields, true)) {
                        $db->query("ALTER TABLE `{$tbl}` ADD COLUMN `last_status_note` TEXT NULL;");
                    }
                    if (!in_array('is_closed', $fields, true)) {
                        $db->query("ALTER TABLE `{$tbl}` ADD COLUMN `is_closed` TINYINT(1) NOT NULL DEFAULT 0;");
                    }
                }
            }

            // ── 3.1 ENSURE ADMIN MFA COLUMNS ──────────────────────────────────────────
            if ($db->tableExists('admin_users')) {
                if (!$db->fieldExists('email_otp_enabled', 'admin_users')) {
                    $db->query("ALTER TABLE `admin_users` ADD COLUMN `email_otp_enabled` TINYINT(1) NOT NULL DEFAULT 0;");
                }
                if (!$db->fieldExists('email_otp_code', 'admin_users')) {
                    $db->query("ALTER TABLE `admin_users` ADD COLUMN `email_otp_code` VARCHAR(255) NULL;");
                }
                if (!$db->fieldExists('email_otp_expires_at', 'admin_users')) {
                    $db->query("ALTER TABLE `admin_users` ADD COLUMN `email_otp_expires_at` DATETIME NULL;");
                }
            }

            // ── 3.2 ENSURE SEO & METADATA COLUMNS ─────────────────────────────────────
            if ($db->tableExists('website_pages')) {
                if (!$db->fieldExists('meta_keywords', 'website_pages')) {
                    $db->query("ALTER TABLE `website_pages` ADD COLUMN `meta_keywords` VARCHAR(255) NULL;");
                }
                if (!$db->fieldExists('og_image', 'website_pages')) {
                    $db->query("ALTER TABLE `website_pages` ADD COLUMN `og_image` VARCHAR(500) NULL;");
                }
            }

            if ($db->tableExists('jobs')) {
                if (!$db->fieldExists('meta_title', 'jobs')) {
                    $db->query("ALTER TABLE `jobs` ADD COLUMN `meta_title` VARCHAR(255) NULL;");
                }
                if (!$db->fieldExists('meta_description', 'jobs')) {
                    $db->query("ALTER TABLE `jobs` ADD COLUMN `meta_description` TEXT NULL;");
                }
                if (!$db->fieldExists('meta_keywords', 'jobs')) {
                    $db->query("ALTER TABLE `jobs` ADD COLUMN `meta_keywords` VARCHAR(255) NULL;");
                }
            }

            if ($db->tableExists('blog_posts')) {
                if (!$db->fieldExists('meta_keywords', 'blog_posts')) {
                    $db->query("ALTER TABLE `blog_posts` ADD COLUMN `meta_keywords` VARCHAR(255) NULL;");
                }
            }

            if ($db->tableExists('audit_logs')) {
                try {
                    $fields = $db->getFieldData('audit_logs');
                    foreach ($fields as $field) {
                        if ($field->name === 'entity_id' && !$field->nullable) {
                            $db->query("ALTER TABLE `audit_logs` MODIFY COLUMN `entity_id` VARCHAR(100) NULL DEFAULT NULL;");
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'DatabaseSchemaService: audit_logs entity_id alter skipped: ' . $e->getMessage());
                }
            }

            // ── 3.3 ENSURE DEFAULT WEBSITE SETTINGS ───────────────────────────────────
            if ($db->tableExists('website_settings')) {
                $existsOtpSetting = $db->table('website_settings')->where('setting_key', 'admin_email_otp_enabled')->countAllResults();
                if ($existsOtpSetting === 0) {
                    $db->table('website_settings')->insert([
                        'setting_key'   => 'admin_email_otp_enabled',
                        'setting_value' => '0',
                        'setting_group' => 'general',
                        'is_public'     => 0,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                // Ensure candidate hiring email default is hiring@talenthubconsultant.com
                $hiringSetting = $db->table('website_settings')->where('setting_key', 'hiring_email')->get()->getRowArray();
                if (!$hiringSetting) {
                    $db->table('website_settings')->insert([
                        'setting_key'   => 'hiring_email',
                        'setting_value' => 'hiring@talenthubconsultant.com',
                        'setting_group' => 'contact',
                        'is_public'     => 1,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                } elseif ($hiringSetting['setting_value'] === 'hr@talenthubconsultant.com') {
                    $db->table('website_settings')->where('setting_key', 'hiring_email')->update([
                        'setting_value' => 'hiring@talenthubconsultant.com',
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                // Ensure WhatsApp Channel link exists
                $existsWaChannel = $db->table('website_settings')->where('setting_key', 'whatsapp_channel_url')->countAllResults();
                if ($existsWaChannel === 0) {
                    $db->table('website_settings')->insert([
                        'setting_key'   => 'whatsapp_channel_url',
                        'setting_value' => 'https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R',
                        'setting_group' => 'social',
                        'is_public'     => 1,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                // Ensure YouTube URL entry exists
                $existsYt = $db->table('website_settings')->where('setting_key', 'youtube_url')->countAllResults();
                if ($existsYt === 0) {
                    $db->table('website_settings')->insert([
                        'setting_key'   => 'youtube_url',
                        'setting_value' => '',
                        'setting_group' => 'social',
                        'is_public'     => 1,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                // Remove deprecated Google Search Console verification token setting
                $db->table('website_settings')->where('setting_key', 'google_site_verification')->delete();
            }

            // ── 4. SEED DEFAULT ROLES & PERMISSIONS ────────────────────────────────────
            if ($db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
                    (1, 'Super Administrator', 'super_admin', 'Full unrestricted administrative access across the entire platform.', NOW(), NOW()),
                    (2, 'HR Manager', 'hr_manager', 'Recruitment operations, candidate pipeline, and enquiry management.', NOW(), NOW());");
            }

            if ($db->tableExists('permissions')) {
                $db->query("INSERT IGNORE INTO `permissions` (`id`, `code`, `name`, `category`, `created_at`) VALUES
                    (1, 'dashboard.view', 'View Dashboard Telemetry', 'Dashboard', NOW()),
                    (2, 'jobs.view', 'View Job Postings', 'Recruitment', NOW()),
                    (3, 'jobs.manage', 'Create & Edit Job Postings', 'Recruitment', NOW()),
                    (4, 'jobs.publish', 'Publish Job Postings', 'Recruitment', NOW()),
                    (5, 'jobs.close', 'Close Job Postings', 'Recruitment', NOW()),
                    (6, 'applications.view', 'View Job Applications', 'Recruitment', NOW()),
                    (7, 'applications.transition', 'Change Application Status & Shortlist', 'Recruitment', NOW()),
                    (8, 'candidates.view', 'View General Resume Bank', 'Candidates', NOW()),
                    (9, 'candidates.manage', 'Export & Manage Candidate Profiles', 'Candidates', NOW()),
                    (10, 'employers.view', 'View Employer Hiring Requests', 'Enquiries', NOW()),
                    (11, 'employers.manage', 'Manage & Follow Up Employer Mandates', 'Enquiries', NOW()),
                    (12, 'enquiries.view', 'View Contact Messages', 'Enquiries', NOW()),
                    (13, 'enquiries.manage', 'Respond & Close Contact Inquiries', 'Enquiries', NOW()),
                    (14, 'mail.view', 'Access Hostinger Mailbox Center', 'Communication', NOW()),
                    (15, 'mail.send', 'Compose & Dispatch Emails', 'Communication', NOW()),
                    (16, 'templates.manage', 'Edit Transactional Email Templates', 'Communication', NOW()),
                    (17, 'content.manage', 'Manage CMS Pages, Blogs & FAQs', 'Content', NOW()),
                    (18, 'settings.manage', 'Manage System & Brand Settings', 'System', NOW()),
                    (19, 'engagement.view', 'View Engagement Overview & Telemetry', 'Engagement', NOW()),
                    (20, 'engagement.manage', 'Manage Announcements, Popups & Campaigns', 'Engagement', NOW()),
                    (21, 'newsletter.manage', 'Manage Newsletter Subscribers & Preferences', 'Engagement', NOW()),
                    (22, 'campaigns.send', 'Compose and Dispatch Email Campaigns', 'Engagement', NOW()),
                    (23, 'users.view', 'View Staff & Admin Users', 'Administration', NOW()),
                    (24, 'users.manage', 'Create, Edit & Deactivate Staff Users', 'Administration', NOW()),
                    (25, 'roles.view', 'View Roles & Permission Matrix', 'Administration', NOW()),
                    (26, 'roles.manage', 'Create & Configure Roles & Permissions', 'Administration', NOW());");
            }

            if ($db->tableExists('role_permissions') && $db->tableExists('permissions')) {
                // Assign all permissions to Super Admin
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
                    SELECT 1, id FROM `permissions`;");

                // Assign operational permissions to HR Manager
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
                    SELECT 2, id FROM `permissions` WHERE `code` IN (
                        'dashboard.view',
                        'jobs.view', 'jobs.manage', 'jobs.publish', 'jobs.close',
                        'applications.view', 'applications.transition',
                        'candidates.view', 'candidates.manage',
                        'employers.view', 'employers.manage',
                        'enquiries.view', 'enquiries.manage',
                        'mail.view', 'mail.send',
                        'engagement.view', 'newsletter.manage'
                    );");
            }

            if ($db->tableExists('user_roles') && $db->tableExists('admin_users')) {
                // Ensure default admin user 1 has role 1
                $db->query("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);");
            }

            // ── 5. SEED INITIAL ANNOUNCEMENT ──────────────────────────────────────────
            if ($db->tableExists('announcements')) {
                $db->query("INSERT IGNORE INTO `announcements` (
                    `id`, `title`, `internal_description`, `public_heading`, `public_content`,
                    `image_url`, `cta_text`, `cta_url`, `announcement_type`, `display_format`,
                    `start_date`, `end_date`, `start_time`, `end_time`, `timezone`,
                    `audience`, `frequency`, `priority`, `status`, `created_at`, `updated_at`
                ) VALUES (
                    1,
                    'Pune Tech & Engineering Recruitment Drive 2026',
                    'Campaign targeting tech and engineering talent across Hinjawadi, Magarpatta & Chakan',
                    'Q4 Pune Corporate Hiring Drive: 50+ Senior & Mid-Level Mandates Open',
                    'Leading enterprises across Hinjawadi Phase 1-3, Chakan Industrial Zone, and Kharadi are actively hiring IT architects, project engineers, and corporate leaders through TalentHub Consultant. Fast-track your application today.',
                    NULL,
                    'Explore Open Mandates',
                    '/jobs',
                    'recruitment_drive',
                    'center_modal',
                    '2026-01-01',
                    '2026-12-31',
                    '00:00:00',
                    '23:59:59',
                    'Asia/Kolkata',
                    'everyone',
                    'once_per_session',
                    10,
                    'ACTIVE',
                    NOW(),
                    NOW()
                );");
            }

            // ── 6. SEED ESSENTIAL EMAIL TEMPLATES ──────────────────────────────────────
            if ($db->tableExists('email_templates')) {
                $templates = [
                    [
                        'code' => 'newsletter_verification',
                        'name' => 'Newsletter Email Verification (Double Opt-In)',
                        'subject' => 'Confirm your TalentHub Newsletter Subscription',
                        'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:24px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:14px;margin-top:4px;">Pune Recruitment &amp; Executive Search</p></div><h2 style="color:#1e293b;font-size:18px;">Please verify your email address</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Hello {{subscriber_name}},</p><p style="color:#475569;font-size:14px;line-height:1.6;">Thank you for subscribing to Pune recruitment insights, job market intelligence, and career updates from TalentHub Consultant. Please click the button below to confirm your subscription:</p><div style="text-align:center;margin:32px 0;"><a href="{{verification_url}}" style="display:inline-block;padding:12px 28px;background-color:#1D6A34;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:8px;font-size:14px;">Confirm Subscription</a></div><p style="color:#64748b;font-size:12px;line-height:1.5;">If the button does not work, copy and paste this link into your browser:<br><a href="{{verification_url}}" style="color:#1D6A34;">{{verification_url}}</a></p><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant &mdash; Katraj, Pune 411046, Maharashtra, India.<br>If you did not request this, please ignore this email.</p></div>',
                        'body_text' => "Confirm your TalentHub Newsletter Subscription\n\nHello {{subscriber_name}},\n\nPlease confirm your subscription by visiting:\n{{verification_url}}\n\nTalentHub Consultant &mdash; Katraj, Pune 411046",
                        'variables_help' => '{{subscriber_name}}, {{verification_url}}',
                    ],
                    [
                        'code' => 'admin_contact_alert',
                        'name' => 'Admin New Contact Enquiry Alert',
                        'subject' => 'New Contact Enquiry: {{enquiry_subject}} - {{sender_name}}',
                        'body_html' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><h3 style="color:#1D6A34;margin:0 0 16px;">New Contact Enquiry Received</h3><p>A new customer/business enquiry has been submitted through the website:</p><ul style="font-size:13px;color:#334155;line-height:1.6;"><li><strong>Sender:</strong> {{sender_name}}</li><li><strong>Email:</strong> {{sender_email}}</li><li><strong>Phone:</strong> {{sender_phone}}</li><li><strong>Subject:</strong> {{enquiry_subject}}</li></ul><div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:13px;color:#1e293b;white-space:pre-wrap;">{{message}}</div></div>',
                        'body_text' => "New Contact Enquiry:

Sender: {{sender_name}}
Email: {{sender_email}}
Phone: {{sender_phone}}
Subject: {{enquiry_subject}}

Message:
{{message}}",
                        'variables_help' => '{{sender_name}}, {{sender_email}}, {{sender_phone}}, {{enquiry_subject}}, {{message}}',
                    ],
                    [
                        'code' => 'admin_employer_alert',
                        'name' => 'Admin New Employer Mandate Alert',
                        'subject' => 'New Employer Mandate: {{company_name}} - {{required_roles}}',
                        'body_html' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><h3 style="color:#1D6A34;margin:0 0 16px;">New Corporate Hiring Mandate Registered</h3><p>A corporate employer has submitted a hiring requirement:</p><ul style="font-size:13px;color:#334155;line-height:1.6;"><li><strong>Company:</strong> {{company_name}}</li><li><strong>Contact Person:</strong> {{contact_person}}</li><li><strong>Email:</strong> {{email}}</li><li><strong>Phone:</strong> {{phone}}</li><li><strong>Required Roles:</strong> {{required_roles}}</li><li><strong>Urgency:</strong> {{urgency}}</li></ul><p style="margin-top:16px;font-size:13px;color:#475569;"><strong>Notes / Scope:</strong><br>{{notes}}</p></div>',
                        'body_text' => "New Employer Mandate:

Company: {{company_name}}
Contact Person: {{contact_person}}
Email: {{email}}
Phone: {{phone}}
Required Roles: {{required_roles}}
Urgency: {{urgency}}
Notes: {{notes}}",
                        'variables_help' => '{{company_name}}, {{contact_person}}, {{email}}, {{phone}}, {{required_roles}}, {{urgency}}, {{notes}}',
                    ],
                    [
                        'code' => 'newsletter_welcome',
                        'name' => 'Newsletter Welcome Email',
                        'subject' => 'Welcome to TalentHub Pune Recruitment Intelligence',
                        'body_html' => '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:24px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:14px;margin-top:4px;">Welcome to the TalentHub Community</p></div><h2 style="color:#1e293b;font-size:18px;">Your subscription is confirmed!</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Hello {{subscriber_name}},</p><p style="color:#475569;font-size:14px;line-height:1.6;">You are now subscribed to verified Pune recruitment updates, executive hiring reports, engineering and IT mandates across Hinjawadi, Kharadi, and Chakan.</p><div style="text-align:center;margin:32px 0;"><a href="{{preferences_url}}" style="display:inline-block;padding:12px 28px;background-color:#1D6A34;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:8px;font-size:14px;">Manage Preferences</a></div><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant &mdash; Katraj, Pune 411046.<br><a href="{{unsubscribe_url}}" style="color:#64748b;">Unsubscribe</a></p></div>',
                        'body_text' => "Welcome to TalentHub Community!\n\nHello {{subscriber_name}},\n\nYour subscription is active.\nPreferences: {{preferences_url}}\nUnsubscribe: {{unsubscribe_url}}",
                        'variables_help' => '{{subscriber_name}}, {{preferences_url}}, {{unsubscribe_url}}',
                    ]
                ];

                foreach ($templates as $tmpl) {
                    $exists = $db->table('email_templates')->where('code', $tmpl['code'])->countAllResults();
                    if ($exists === 0) {
                        $db->table('email_templates')->insert(array_merge($tmpl, ['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')]));
                    }
                }
            }

            // ── 7. SEED ALL 15 PUBLIC PAGES WITH TOP-RANKING SEO ──────────────────────
            if ($db->tableExists('website_pages')) {
                $pagesToSeed = [
                    ['slug' => 'home', 'title' => 'Home — TalentHub Consultant', 'meta_title' => 'TalentHub Consultant | Leading Job Consultancy in Pune', 'meta_description' => "TalentHub Consultant connects top engineering, IT, and leadership talent with Pune's leading corporate enterprises across Hinjawadi, Kharadi, and Chakan.", 'meta_keywords' => 'job consultancy Pune, recruitment agency Pune, placement consultants Pune, IT jobs Hinjawadi, recruitment agency Katraj'],
                    ['slug' => 'about', 'title' => 'About Us — TalentHub Consultant', 'meta_title' => 'About Us | Top Recruitment Consultancy in Pune | TalentHub', 'meta_description' => "Learn about TalentHub Consultant, Pune's trusted recruitment partner. Discover our ethical executive search, corporate staffing SLAs, and proven hiring record.", 'meta_keywords' => 'about TalentHub, Pune recruitment agency, executive search Pune, staffing firm Katraj, corporate headhunters Pune'],
                    ['slug' => 'solutions', 'title' => 'Recruitment Solutions & Staffing Tracks', 'meta_title' => 'Recruitment Solutions & Staffing Tracks | TalentHub Pune', 'meta_description' => 'Tailored recruitment solutions in Pune: IT staffing, engineering mandates, executive search, and turnkey volume hiring for Maharashtra corporate enterprises.', 'meta_keywords' => 'staffing solutions Pune, IT recruitment Pune, engineering hiring Chakan, executive headhunting Pune'],
                    ['slug' => 'employers', 'title' => 'Employers & Corporate Staffing', 'meta_title' => 'Hire Top Talent in Pune | Corporate Recruitment | TalentHub', 'meta_description' => 'Fast-track corporate hiring in Pune. TalentHub delivers pre-screened technical talent and executive leadership profiles to employers within 7 business days.', 'meta_keywords' => 'hire talent Pune, employer recruitment Pune, IT staffing Hinjawadi, corporate hiring partner Pune'],
                    ['slug' => 'candidates', 'title' => 'Candidates & Career Guidance', 'meta_title' => 'Pune Job Opportunities & Career Advisory | TalentHub', 'meta_description' => 'Explore verified career opportunities across Pune tech parks and manufacturing belts. Free candidate representation, salary benchmarks, and interview prep.', 'meta_keywords' => 'jobs in Pune, career guidance Pune, tech jobs Hinjawadi, engineering jobs Chakan, candidate placement Pune'],
                    ['slug' => 'jobs', 'title' => 'Current Job Openings in Pune', 'meta_title' => 'Current Job Vacancies in Pune | IT & Core Roles | TalentHub', 'meta_description' => 'Explore active job openings in Pune across software engineering, automotive, finance, and corporate management. Direct application with zero candidate fees.', 'meta_keywords' => 'job vacancies Pune, current openings Hinjawadi, Pune IT jobs, engineering recruitment Pune, walkin jobs Pune'],
                    ['slug' => 'career-enquiry', 'title' => 'Career Desk & Resume Submission', 'meta_title' => 'Submit Resume | Confidential Pune Talent Vault | TalentHub', 'meta_description' => "Upload your CV to TalentHub's private Pune candidate vault. Get matched with unadvertised leadership and technical mandates across Hinjawadi and Chakan.", 'meta_keywords' => 'submit resume Pune, upload CV Hinjawadi, candidate registration Pune, confidential job search Pune'],
                    ['slug' => 'insights', 'title' => 'Pune Recruitment Insights & Trends', 'meta_title' => 'Pune Hiring Market Trends & Salary Insights | TalentHub', 'meta_description' => 'Stay informed with Pune recruitment intelligence, engineering salary benchmarks, Hinjawadi tech hiring indexes, and quarterly corporate compensation reports.', 'meta_keywords' => 'Pune salary guide, IT hiring trends Pune, Hinjawadi tech salaries, recruitment insights Maharashtra'],
                    ['slug' => 'interviews', 'title' => 'Interview Preparation & Guidance', 'meta_title' => 'Interview Preparation & Career Guidance | TalentHub Pune', 'meta_description' => "Expert interview strategies, technical assessment tips, and executive salary negotiation guidance from TalentHub's veteran Pune recruitment consultants.", 'meta_keywords' => 'interview tips Pune, technical interview prep, career coaching Pune, executive interview guidance'],
                    ['slug' => 'contact', 'title' => 'Contact Us & Office Headquarters', 'meta_title' => 'Contact TalentHub | Recruitment Office Katraj, Pune Desk', 'meta_description' => 'Get in touch with TalentHub Consultant in Katraj, Pune. Reach our corporate hiring desk via phone +91 72761 46573, WhatsApp, email, or in-person advisory.', 'meta_keywords' => 'TalentHub office Pune, recruitment contact Katraj, staffing agency phone number, hiring consultancy Pune'],
                    ['slug' => 'faq', 'title' => 'Frequently Asked Questions', 'meta_title' => 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune', 'meta_description' => 'Common questions on corporate staffing SLAs, candidate representation, hiring turnaround, and recruitment processes with TalentHub Consultant across Pune.', 'meta_keywords' => 'recruitment FAQ Pune, hiring process questions, candidate consultancy fees Pune, staffing FAQs'],
                    ['slug' => 'privacy', 'title' => 'Privacy Policy & DPDP Governance', 'meta_title' => 'Privacy Policy & India DPDP Act Compliance | TalentHub', 'meta_description' => 'TalentHub Consultant privacy policy, data protection governance, affirmative consent practices, and statutory India DPDP Act compliance protocols for users.', 'meta_keywords' => 'privacy policy TalentHub, India DPDP Act, data protection recruitment, candidate privacy rights'],
                    ['slug' => 'terms', 'title' => 'Terms of Service & Conditions', 'meta_title' => 'Terms of Service & Advisory Conditions | TalentHub Pune', 'meta_description' => 'Terms of service governing the use of TalentHub Consultant recruitment portal, job applications, employer mandate agreements, and staffing advisory services.', 'meta_keywords' => 'terms of service TalentHub, user agreement recruitment, employer terms Pune, job application conditions'],
                    ['slug' => 'application-tracker', 'title' => 'Candidate Application Status Tracker', 'meta_title' => 'Track Job Application Status | Candidate Portal | TalentHub', 'meta_description' => 'Check real-time application progress, interview schedules, and screening updates for your submitted Pune job applications with your email and reference ID.', 'meta_keywords' => 'track application status, Pune job application tracker, interview status lookup, candidate application progress'],
                    ['slug' => 'request-cv-deletion', 'title' => 'Statutory CV Erasure Request (DPDP Act)', 'meta_title' => 'Request CV Deletion | DPDP Right to Erasure | TalentHub', 'meta_description' => "Exercise your statutory Right to Erasure under the India DPDP Act. Permanently shred and delete your resume files from TalentHub's private document vault.", 'meta_keywords' => 'delete resume DPDP, right to erasure Pune, candidate data deletion, remove CV TalentHub'],
                ];

                foreach ($pagesToSeed as $p) {
                    $existingPage = $db->table('website_pages')->where('slug', $p['slug'])->get()->getRowArray();
                    if (!$existingPage) {
                        $db->table('website_pages')->insert([
                            'slug'             => $p['slug'],
                            'title'            => $p['title'],
                            'meta_title'       => $p['meta_title'],
                            'meta_description' => $p['meta_description'],
                            'meta_keywords'    => $p['meta_keywords'],
                            'status'           => 'PUBLISHED',
                            'version'          => 1,
                            'created_at'       => date('Y-m-d H:i:s'),
                            'updated_at'       => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            self::$bootstrapped = true;

            return [
                'success'        => true,
                'created_tables' => $createdTables,
                'message'        => 'Database schema verified and operational.',
            ];

        } catch (\Throwable $e) {
            log_message('error', 'DatabaseSchemaService::ensureSchema error: ' . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}

