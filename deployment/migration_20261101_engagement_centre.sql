-- TalentHub Consultant — Master Engagement Centre Database Migration
-- Target: MySQL / MariaDB (Hostinger & Local Development)
-- Created: 2026-11-01

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NULL,
  `status` ENUM('unverified', 'subscribed', 'unsubscribed', 'bounced') NOT NULL DEFAULT 'unverified',
  `verification_token_hash` VARCHAR(64) NULL,
  `verification_expires_at` DATETIME NULL,
  `unsubscribe_token_hash` VARCHAR(64) NULL,
  `consent_given` TINYINT(1) NOT NULL DEFAULT 1,
  `consent_timestamp` DATETIME NOT NULL,
  `consent_source` VARCHAR(100) NOT NULL DEFAULT 'web_form',
  `verified_at` DATETIME NULL,
  `unsubscribed_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `uk_newsletter_email` (`email`),
  KEY `idx_newsletter_status` (`status`),
  KEY `idx_newsletter_verification_token` (`verification_token_hash`),
  KEY `idx_newsletter_unsubscribe_token` (`unsubscribe_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Newsletter Subscriber Preferences Table
CREATE TABLE IF NOT EXISTS `newsletter_preferences` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subscriber_id` INT UNSIGNED NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NULL,
  UNIQUE KEY `uk_subscriber_category` (`subscriber_id`, `category`),
  CONSTRAINT `fk_newsletter_pref_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Newsletter Activity Logs Table
CREATE TABLE IF NOT EXISTS `newsletter_activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subscriber_id` INT UNSIGNED NOT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `activity_timestamp` DATETIME NOT NULL,
  `source` VARCHAR(100) NULL,
  `metadata` TEXT NULL,
  KEY `idx_newsletter_act_subscriber` (`subscriber_id`),
  KEY `idx_newsletter_act_type` (`activity_type`),
  CONSTRAINT `fk_newsletter_act_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Smart Announcements & Popups Table
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `internal_description` VARCHAR(500) NULL,
  `public_heading` VARCHAR(255) NOT NULL,
  `public_content` TEXT NOT NULL,
  `image_url` VARCHAR(500) NULL,
  `cta_text` VARCHAR(100) NULL,
  `cta_url` VARCHAR(500) NULL,
  `announcement_type` VARCHAR(50) NOT NULL DEFAULT 'company_announcement',
  `display_format` ENUM('center_modal', 'top_banner', 'bottom_banner', 'inline_homepage', 'newsletter_popup') NOT NULL DEFAULT 'center_modal',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `start_time` TIME NOT NULL DEFAULT '00:00:00',
  `end_time` TIME NOT NULL DEFAULT '23:59:59',
  `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
  `audience` ENUM('everyone', 'candidates', 'employers', 'homepage', 'insights', 'selected_pages', 'new_visitors', 'returning_visitors') NOT NULL DEFAULT 'everyone',
  `target_pages` TEXT NULL,
  `frequency` ENUM('every_visit', 'once_per_session', 'once_per_day', 'once_7_days', 'once_30_days', 'until_dismissed') NOT NULL DEFAULT 'once_per_session',
  `priority` INT NOT NULL DEFAULT 1,
  `status` ENUM('DRAFT', 'SCHEDULED', 'ACTIVE', 'PAUSED', 'EXPIRED', 'ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  KEY `idx_announcement_status` (`status`),
  KEY `idx_announcement_dates` (`start_date`, `end_date`),
  KEY `idx_announcement_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Announcement Analytics & Events Table
CREATE TABLE IF NOT EXISTS `announcement_events` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `announcement_id` INT UNSIGNED NOT NULL,
  `event_type` ENUM('impression', 'click', 'dismissal', 'conversion') NOT NULL,
  `ip_hash` VARCHAR(64) NULL,
  `user_agent_hash` VARCHAR(64) NULL,
  `session_id` VARCHAR(100) NULL,
  `created_at` DATETIME NULL,
  KEY `idx_announcement_evt_announcement` (`announcement_id`),
  KEY `idx_announcement_evt_type` (`event_type`),
  KEY `idx_announcement_evt_created` (`created_at`),
  CONSTRAINT `fk_announcement_evt_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Email Campaigns Table
CREATE TABLE IF NOT EXISTS `email_campaigns` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Email Delivery Logs Table
CREATE TABLE IF NOT EXISTS `email_delivery_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT UNSIGNED NULL,
  `subscriber_id` INT UNSIGNED NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'smtp',
  `provider_message_id` VARCHAR(255) NULL,
  `status` ENUM('QUEUED', 'SENT', 'DELIVERED', 'FAILED', 'BOUNCED') NOT NULL DEFAULT 'QUEUED',
  `attempt_count` INT NOT NULL DEFAULT 1,
  `last_attempt` DATETIME NULL,
  `error_message` TEXT NULL,
  `sent_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  KEY `idx_delivery_campaign` (`campaign_id`),
  KEY `idx_delivery_subscriber` (`subscriber_id`),
  KEY `idx_delivery_recipient` (`recipient_email`),
  KEY `idx_delivery_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Job Alert Subscriptions Table
CREATE TABLE IF NOT EXISTS `job_alert_subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `job_category` VARCHAR(100) NULL,
  `category_id` INT UNSIGNED NULL,
  `preferred_location` VARCHAR(100) NULL,
  `experience_level` VARCHAR(50) NULL,
  `employment_type` VARCHAR(50) NULL,
  `frequency` ENUM('immediate', 'daily', 'weekly') NOT NULL DEFAULT 'weekly',
  `status` ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
  `token_hash` VARCHAR(64) NOT NULL,
  `last_alert_sent_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  KEY `idx_job_alert_email` (`email`),
  KEY `idx_job_alert_status` (`status`),
  KEY `idx_job_alert_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Cookie Consent Logs Table
CREATE TABLE IF NOT EXISTS `cookie_consent_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Engagement Centre Permissions & Roles Seed
INSERT IGNORE INTO `permissions` (`id`, `code`, `name`, `category`, `created_at`) VALUES
(19, 'engagement.view', 'View Engagement Overview & Telemetry', 'Engagement', NOW()),
(20, 'engagement.manage', 'Manage Announcements, Popups & Campaigns', 'Engagement', NOW()),
(21, 'newsletter.manage', 'Manage Newsletter Subscribers & Preferences', 'Engagement', NOW()),
(22, 'campaigns.send', 'Compose and Dispatch Email Campaigns', 'Engagement', NOW());

-- Assign all new permissions to Super Admin role (role id 1)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions` WHERE id IN (19, 20, 21, 22);

-- Also add default email templates for newsletter verification and welcome if not exists
INSERT IGNORE INTO `email_templates` (`code`, `name`, `subject`, `body_html`, `body_text`, `variables_help`, `is_active`, `updated_at`)
VALUES
(
  'newsletter_verification',
  'Newsletter Email Verification (Double Opt-In)',
  'Confirm your TalentHub Newsletter Subscription',
  '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:24px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:14px;margin-top:4px;">Pune Recruitment & Executive Search</p></div><h2 style="color:#1e293b;font-size:18px;">Please verify your email address</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Hello {{subscriber_name}},</p><p style="color:#475569;font-size:14px;line-height:1.6;">Thank you for subscribing to Pune recruitment insights, job market intelligence, and career updates from TalentHub Consultant. Please click the button below to confirm your subscription:</p><div style="text-align:center;margin:32px 0;"><a href="{{verification_url}}" style="display:inline-block;padding:12px 28px;background-color:#1D6A34;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:8px;font-size:14px;">Confirm Subscription</a></div><p style="color:#64748b;font-size:12px;line-height:1.5;">If the button does not work, copy and paste this link into your browser:<br><a href="{{verification_url}}" style="color:#1D6A34;">{{verification_url}}</a></p><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant — Katraj, Pune 411046, Maharashtra, India.<br>If you did not request this, please ignore this email.</p></div>',
  'Confirm your TalentHub Newsletter Subscription\n\nHello {{subscriber_name}},\n\nPlease confirm your subscription by visiting:\n{{verification_url}}\n\nTalentHub Consultant — Katraj, Pune 411046',
  '{{subscriber_name}}, {{verification_url}}',
  1,
  NOW()
),
(
  'newsletter_welcome',
  'Newsletter Welcome Email',
  'Welcome to TalentHub Pune Recruitment Intelligence',
  '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:24px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:14px;margin-top:4px;">Welcome to the TalentHub Community</p></div><h2 style="color:#1e293b;font-size:18px;">Your subscription is confirmed!</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Hello {{subscriber_name}},</p><p style="color:#475569;font-size:14px;line-height:1.6;">You are now subscribed to verified Pune recruitment updates, executive hiring reports, engineering and IT mandates across Hinjawadi, Kharadi, and Chakan.</p><p style="color:#475569;font-size:14px;line-height:1.6;">You can manage your preferences or unsubscribe at any time using the links below.</p><div style="text-align:center;margin:32px 0;"><a href="{{preferences_url}}" style="display:inline-block;padding:12px 28px;background-color:#1D6A34;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:8px;font-size:14px;">Manage Preferences</a></div><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant — Katraj, Pune 411046, Maharashtra, India.<br><a href="{{unsubscribe_url}}" style="color:#64748b;text-decoration:underline;">Unsubscribe</a> | <a href="{{preferences_url}}" style="color:#64748b;text-decoration:underline;">Preferences</a></p></div>',
  'Welcome to TalentHub Community!\n\nHello {{subscriber_name}},\n\nYour subscription is active.\nManage preferences: {{preferences_url}}\nUnsubscribe: {{unsubscribe_url}}\n\nTalentHub Consultant, Katraj, Pune',
  '{{subscriber_name}}, {{preferences_url}}, {{unsubscribe_url}}',
  1,
  NOW()
),
(
  'job_alert_notification',
  'Matching Job Opportunities Alert',
  'New Job Opportunities Matching Your Preferences — TalentHub',
  '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:24px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:14px;margin-top:4px;">New Job Alert</p></div><h2 style="color:#1e293b;font-size:18px;">We found new vacancies matching your criteria:</h2><div style="margin:20px 0;">{{jobs_list_html}}</div><div style="text-align:center;margin:32px 0;"><a href="{{jobs_url}}" style="display:inline-block;padding:12px 28px;background-color:#1D6A34;color:#ffffff;text-decoration:none;font-weight:bold;border-radius:8px;font-size:14px;">View All Active Jobs</a></div><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant — Katraj, Pune 411046.<br><a href="{{unsubscribe_url}}" style="color:#64748b;text-decoration:underline;">Unsubscribe from Job Alerts</a></p></div>',
  'New Job Alert from TalentHub\n\n{{jobs_list_text}}\n\nView all jobs: {{jobs_url}}\nUnsubscribe: {{unsubscribe_url}}',
  '{{jobs_list_html}}, {{jobs_list_text}}, {{jobs_url}}, {{unsubscribe_url}}',
  1,
  NOW()
);

-- Seed a sample smart announcement for TalentHub
INSERT IGNORE INTO `announcements` (
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
);

SET FOREIGN_KEY_CHECKS = 1;
