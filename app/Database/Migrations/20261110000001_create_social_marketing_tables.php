<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * TalentHub Enterprise Social Media Marketing & Management Centre Migration.
 *
 * Creates 17 normalized, indexed, and transaction-safe tables for LinkedIn & Instagram
 * marketing, OAuth management, content studio, media library, database queue,
 * campaign tracking, social enquiries, engagement, analytics, and audit logging.
 *
 * Designed with idempotent apply() for lazy runtime execution on shared hosting
 * without requiring shell spark migrate access.
 */
class CreateSocialMarketingTables extends Migration
{
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

        $engine = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        // 1. social_accounts: connected social media profiles & company pages
        $db->query("CREATE TABLE IF NOT EXISTS `social_accounts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `account_name` VARCHAR(191) NOT NULL,
            `account_type` VARCHAR(60) NOT NULL DEFAULT 'company', -- company, personal, business, creator
            `external_id` VARCHAR(120) NOT NULL, -- LinkedIn organization URN ID or Instagram Business Account ID
            `secondary_external_id` VARCHAR(120) NULL, -- Facebook Page ID for Instagram
            `profile_url` VARCHAR(255) NULL,
            `avatar_url` VARCHAR(255) NULL,
            `status` ENUM('active','disconnected','expired','error') NOT NULL DEFAULT 'active',
            `connected_by` INT UNSIGNED NULL,
            `last_verified_at` DATETIME NULL,
            `last_synced_at` DATETIME NULL,
            `last_error` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_social_acc_platform_ext` (`platform`, `external_id`),
            KEY `idx_social_acc_status` (`status`),
            KEY `idx_social_acc_platform` (`platform`)
        ) {$engine}");

        // 2. social_credentials: encrypted secrets, OAuth tokens & permissions at rest
        $db->query("CREATE TABLE IF NOT EXISTS `social_credentials` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `account_id` INT UNSIGNED NULL, -- NULL indicates global platform application credentials
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `client_id` VARCHAR(191) NULL,
            `client_secret_encrypted` TEXT NULL,
            `app_id` VARCHAR(191) NULL,
            `access_token_encrypted` TEXT NULL,
            `refresh_token_encrypted` TEXT NULL,
            `token_type` VARCHAR(40) NOT NULL DEFAULT 'Bearer',
            `token_expires_at` DATETIME NULL,
            `refresh_expires_at` DATETIME NULL,
            `scopes` TEXT NULL,
            `permissions_json` JSON NULL,
            `api_version` VARCHAR(20) NOT NULL DEFAULT 'v2',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_social_cred_acc_platform` (`account_id`, `platform`),
            KEY `idx_social_cred_expires` (`token_expires_at`)
        ) {$engine}");

        // 3. social_oauth_states: CSRF defense-in-depth state tokens for OAuth flows
        $db->query("CREATE TABLE IF NOT EXISTS `social_oauth_states` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `state_token` VARCHAR(128) NOT NULL,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `redirect_uri` VARCHAR(255) NOT NULL,
            `code_verifier` VARCHAR(128) NULL, -- PKCE support
            `expires_at` DATETIME NOT NULL,
            `used_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_social_state_token` (`state_token`),
            KEY `idx_social_state_expires` (`expires_at`, `used_at`)
        ) {$engine}");

        // 4. social_campaigns: marketing initiatives & promotional campaigns
        $db->query("CREATE TABLE IF NOT EXISTS `social_campaigns` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            `slug` VARCHAR(191) NOT NULL,
            `objective` ENUM('recruitment','brand_awareness','lead_generation','hrms_promotion','it_services_promotion','product_launch','educational') NOT NULL DEFAULT 'brand_awareness',
            `description` TEXT NULL,
            `target_audience` VARCHAR(255) NULL,
            `platform` ENUM('all','linkedin','instagram') NOT NULL DEFAULT 'all',
            `start_date` DATE NULL,
            `end_date` DATE NULL,
            `budget` DECIMAL(12,2) NULL DEFAULT 0.00,
            `status` ENUM('draft','active','paused','completed') NOT NULL DEFAULT 'draft',
            `created_by` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_social_camp_slug` (`slug`),
            KEY `idx_social_camp_status` (`status`),
            KEY `idx_social_camp_dates` (`start_date`, `end_date`)
        ) {$engine}");

        // 5. social_posts: master content records
        $db->query("CREATE TABLE IF NOT EXISTS `social_posts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(191) NOT NULL,
            `campaign_id` INT UNSIGNED NULL,
            `primary_platform` ENUM('linkedin','instagram','both') NOT NULL DEFAULT 'both',
            `post_objective` VARCHAR(60) NULL,
            `base_content` TEXT NOT NULL,
            `base_hashtags` VARCHAR(255) NULL,
            `base_cta` VARCHAR(120) NULL,
            `base_destination_url` VARCHAR(255) NULL,
            `status` ENUM('draft','pending_approval','approved','scheduled','processing','published','failed','cancelled','expired','needs_reauthorization') NOT NULL DEFAULT 'draft',
            `scheduled_at` DATETIME NULL,
            `timezone` VARCHAR(60) NOT NULL DEFAULT 'Asia/Kolkata',
            `published_at` DATETIME NULL,
            `created_by` INT UNSIGNED NULL,
            `approved_by` INT UNSIGNED NULL,
            `approved_at` DATETIME NULL,
            `internal_notes` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_posts_status` (`status`),
            KEY `idx_social_posts_scheduled` (`scheduled_at`),
            KEY `idx_social_posts_campaign` (`campaign_id`),
            KEY `idx_social_posts_platform` (`primary_platform`)
        ) {$engine}");

        // 6. social_post_platforms: platform-specific caption, hashtags, and published state
        $db->query("CREATE TABLE IF NOT EXISTS `social_post_platforms` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `post_id` INT UNSIGNED NOT NULL,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `account_id` INT UNSIGNED NULL,
            `caption` TEXT NOT NULL,
            `hashtags` VARCHAR(255) NULL,
            `cta` VARCHAR(120) NULL,
            `destination_url` VARCHAR(255) NULL,
            `external_post_id` VARCHAR(191) NULL,
            `external_permalink` VARCHAR(255) NULL,
            `status` ENUM('pending','publishing','published','failed','skipped') NOT NULL DEFAULT 'pending',
            `published_at` DATETIME NULL,
            `error_message` TEXT NULL,
            `api_response_json` JSON NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_post_platform` (`post_id`, `platform`),
            KEY `idx_post_platform_ext` (`external_post_id`),
            KEY `idx_post_platform_status` (`status`)
        ) {$engine}");

        // 7. social_media_library: centralized marketing media assets
        $db->query("CREATE TABLE IF NOT EXISTS `social_media_library` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(191) NOT NULL,
            `filename` VARCHAR(191) NOT NULL,
            `original_name` VARCHAR(191) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_url` VARCHAR(255) NOT NULL,
            `thumbnail_url` VARCHAR(255) NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
            `media_type` ENUM('image','video','document') NOT NULL DEFAULT 'image',
            `width` INT UNSIGNED NULL,
            `height` INT UNSIGNED NULL,
            `duration_seconds` INT UNSIGNED NULL,
            `category` VARCHAR(60) NOT NULL DEFAULT 'corporate_branding',
            `tags` VARCHAR(255) NULL,
            `uploaded_by` INT UNSIGNED NULL,
            `usage_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_media_type` (`media_type`),
            KEY `idx_social_media_category` (`category`),
            KEY `idx_social_media_approved` (`is_approved`)
        ) {$engine}");

        // 8. social_post_media: association between posts and media assets
        $db->query("CREATE TABLE IF NOT EXISTS `social_post_media` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `post_id` INT UNSIGNED NOT NULL,
            `media_library_id` INT UNSIGNED NULL,
            `platform` ENUM('all','linkedin','instagram') NOT NULL DEFAULT 'all',
            `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `media_type` ENUM('image','video','document') NOT NULL DEFAULT 'image',
            `file_path` VARCHAR(255) NOT NULL,
            `file_url` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
            `width` INT UNSIGNED NULL,
            `height` INT UNSIGNED NULL,
            `duration_seconds` INT UNSIGNED NULL,
            `alt_text` VARCHAR(255) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_post_media_post` (`post_id`),
            KEY `idx_post_media_lib` (`media_library_id`)
        ) {$engine}");

        // 9. social_scheduled_jobs: resilient database-backed publishing queue
        $db->query("CREATE TABLE IF NOT EXISTS `social_scheduled_jobs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `post_id` INT UNSIGNED NOT NULL,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `scheduled_at` DATETIME NOT NULL,
            `locked_at` DATETIME NULL,
            `locked_by` VARCHAR(100) NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
            `next_attempt_at` DATETIME NULL,
            `status` ENUM('queued','processing','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
            `last_error` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_jobs_due` (`status`, `scheduled_at`, `next_attempt_at`),
            KEY `idx_social_jobs_post` (`post_id`),
            KEY `idx_social_jobs_lock` (`locked_at`)
        ) {$engine}");

        // 10. social_publish_attempts: immutable execution & retry audit records
        $db->query("CREATE TABLE IF NOT EXISTS `social_publish_attempts` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `job_id` BIGINT UNSIGNED NOT NULL,
            `post_id` INT UNSIGNED NOT NULL,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `attempt_number` TINYINT UNSIGNED NOT NULL,
            `started_at` DATETIME NOT NULL,
            `completed_at` DATETIME NULL,
            `status` ENUM('success','retryable_failure','permanent_failure') NOT NULL,
            `http_status` INT NULL,
            `error_code` VARCHAR(60) NULL,
            `error_message` TEXT NULL,
            `request_payload_sanitized` TEXT NULL,
            `response_payload_sanitized` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_pub_attempt_job` (`job_id`),
            KEY `idx_pub_attempt_post` (`post_id`),
            KEY `idx_pub_attempt_status` (`status`)
        ) {$engine}");

        // 11. social_content_templates: reusable marketing caption & hashtag templates
        $db->query("CREATE TABLE IF NOT EXISTS `social_content_templates` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(191) NOT NULL,
            `category` VARCHAR(60) NOT NULL DEFAULT 'Recruitment',
            `platform` ENUM('all','linkedin','instagram') NOT NULL DEFAULT 'all',
            `caption_template` TEXT NOT NULL,
            `hashtag_group` VARCHAR(255) NULL,
            `cta_template` VARCHAR(120) NULL,
            `tone` VARCHAR(40) NOT NULL DEFAULT 'Professional',
            `created_by` INT UNSIGNED NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_tpl_cat` (`category`),
            KEY `idx_social_tpl_active` (`is_active`)
        ) {$engine}");

        // 12. social_campaign_posts: many-to-many relationship between campaigns and posts
        $db->query("CREATE TABLE IF NOT EXISTS `social_campaign_posts` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `campaign_id` INT UNSIGNED NOT NULL,
            `post_id` INT UNSIGNED NOT NULL,
            `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_camp_post` (`campaign_id`, `post_id`),
            KEY `idx_camp_post_post` (`post_id`)
        ) {$engine}");

        // 13. social_analytics_snapshots: verified metrics per post and platform
        $db->query("CREATE TABLE IF NOT EXISTS `social_analytics_snapshots` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `account_id` INT UNSIGNED NULL,
            `post_id` INT UNSIGNED NULL,
            `external_post_id` VARCHAR(191) NULL,
            `snapshot_date` DATE NOT NULL,
            `impressions` INT UNSIGNED NOT NULL DEFAULT 0,
            `reach` INT UNSIGNED NOT NULL DEFAULT 0,
            `reactions` INT UNSIGNED NOT NULL DEFAULT 0,
            `likes` INT UNSIGNED NOT NULL DEFAULT 0,
            `comments` INT UNSIGNED NOT NULL DEFAULT 0,
            `shares` INT UNSIGNED NOT NULL DEFAULT 0,
            `video_views` INT UNSIGNED NOT NULL DEFAULT 0,
            `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
            `engagement_rate` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
            `source` ENUM('api','local_calc','manual') NOT NULL DEFAULT 'api',
            `raw_metrics_json` JSON NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_analytics_post_date` (`platform`, `external_post_id`, `snapshot_date`),
            KEY `idx_analytics_date` (`snapshot_date`),
            KEY `idx_analytics_post` (`post_id`)
        ) {$engine}");

        // 14. social_enquiries: inbound leads originated via social channels
        $db->query("CREATE TABLE IF NOT EXISTS `social_enquiries` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `contact_name` VARCHAR(191) NOT NULL,
            `email` VARCHAR(191) NULL,
            `phone` VARCHAR(40) NULL,
            `company` VARCHAR(191) NULL,
            `enquiry_type` ENUM('recruitment','it_services','worksphere_hrms','consulting','general') NOT NULL DEFAULT 'general',
            `message` TEXT NOT NULL,
            `source_platform` ENUM('linkedin','instagram','website','campaign','referral','manual') NOT NULL DEFAULT 'linkedin',
            `campaign_id` INT UNSIGNED NULL,
            `external_ref` VARCHAR(120) NULL,
            `lead_status` ENUM('new','contacted','qualified','proposal_sent','converted','closed','not_relevant') NOT NULL DEFAULT 'new',
            `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
            `assigned_to` INT UNSIGNED NULL,
            `follow_up_date` DATE NULL,
            `notes` TEXT NULL,
            `conversion_value` DECIMAL(12,2) NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_enq_status` (`lead_status`),
            KEY `idx_social_enq_source` (`source_platform`),
            KEY `idx_social_enq_campaign` (`campaign_id`),
            KEY `idx_social_enq_created` (`created_at`)
        ) {$engine}");

        // 15. social_comments: official API comments & authorized replies
        $db->query("CREATE TABLE IF NOT EXISTS `social_comments` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `account_id` INT UNSIGNED NULL,
            `platform` ENUM('linkedin','instagram') NOT NULL,
            `post_id` INT UNSIGNED NULL,
            `external_post_id` VARCHAR(191) NOT NULL,
            `external_comment_id` VARCHAR(191) NOT NULL,
            `parent_comment_id` VARCHAR(191) NULL,
            `author_name` VARCHAR(191) NOT NULL,
            `author_id` VARCHAR(120) NULL,
            `comment_text` TEXT NOT NULL,
            `comment_date` DATETIME NOT NULL,
            `like_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `reply_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `can_reply` TINYINT(1) NOT NULL DEFAULT 1,
            `has_replied` TINYINT(1) NOT NULL DEFAULT 0,
            `our_reply_text` TEXT NULL,
            `replied_at` DATETIME NULL,
            `status` ENUM('visible','hidden','flagged') NOT NULL DEFAULT 'visible',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_social_comment_ext` (`platform`, `external_comment_id`),
            KEY `idx_social_comment_post` (`external_post_id`),
            KEY `idx_social_comment_date` (`comment_date`)
        ) {$engine}");

        // 16. social_notifications: in-dashboard and email alert dispatches
        $db->query("CREATE TABLE IF NOT EXISTS `social_notifications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NULL, -- NULL denotes broadcast to all marketing admins
            `event_type` ENUM('publish_success','publish_failed','token_expiring','account_disconnected','permission_failure','new_enquiry','campaign_summary','scheduler_failure') NOT NULL,
            `title` VARCHAR(191) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `email_sent` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_notif_user` (`user_id`, `is_read`),
            KEY `idx_social_notif_created` (`created_at`)
        ) {$engine}");

        // 17. social_activity_logs: security & mutation audit trail (never logs raw secrets/tokens)
        $db->query("CREATE TABLE IF NOT EXISTS `social_activity_logs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NULL,
            `action` VARCHAR(100) NOT NULL,
            `entity_type` VARCHAR(60) NOT NULL,
            `entity_id` VARCHAR(60) NULL,
            `metadata_json` JSON NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `result` ENUM('success','failure','warning') NOT NULL DEFAULT 'success',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_social_log_action` (`action`),
            KEY `idx_social_log_user` (`user_id`),
            KEY `idx_social_log_created` (`created_at`)
        ) {$engine}");

        // Seed initial permissions for Social Media Marketing Centre
        if ($db->tableExists('permissions')) {
            $db->query("INSERT IGNORE INTO `permissions` (`code`, `name`, `category`, `created_at`) VALUES
                ('social.view', 'View Social Marketing Dashboard & Posts', 'Social Media', NOW()),
                ('social.create', 'Create & Edit Social Media Posts & Drafts', 'Social Media', NOW()),
                ('social.publish', 'Publish & Schedule Social Media Posts', 'Social Media', NOW()),
                ('social.manage', 'Manage Campaigns, Media & Templates', 'Social Media', NOW()),
                ('social.enquiries', 'Manage Social Leads & Enquiries', 'Social Media', NOW()),
                ('social.analytics', 'View & Export Social Media Analytics', 'Social Media', NOW()),
                ('social.settings', 'Configure Social API Keys & OAuth Connections', 'Social Media', NOW())");

            if ($db->tableExists('role_permissions') && $db->tableExists('roles')) {
                $db->query("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
                    SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
                    ON p.code IN ('social.view','social.create','social.publish','social.manage','social.enquiries','social.analytics','social.settings')
                    WHERE r.slug = 'super_admin'");
            }
        }

        // Seed initial content templates across TalentHub core verticals
        $templateCount = (int) ($db->query("SELECT COUNT(*) as c FROM `social_content_templates`")->getRow()->c ?? 0);
        if ($templateCount === 0) {
            $templates = [
                [
                    'title' => 'Talent Acquisition — Executive Tech Hiring Spotlight',
                    'category' => 'Recruitment & Hiring',
                    'platform' => 'all',
                    'caption_template' => "Bridging the gap between premier tech enterprises and exceptional software engineering talent. 🚀\n\nAt TalentHub, we connect forward-thinking companies with battle-tested senior engineers, architects, and engineering leaders.\n\nLooking to scale your engineering bench with verified experts? Connect with our talent acquisition advisory today.",
                    'hashtag_group' => '#TalentAcquisition #TechRecruitment #ExecutiveHiring #SoftwareEngineering #TalentHub',
                    'cta_template' => 'Partner With TalentHub',
                    'tone' => 'Professional'
                ],
                [
                    'title' => 'WorkSphere HRMS — Enterprise Workforce Automation',
                    'category' => 'HRMS / WorkSphere',
                    'platform' => 'all',
                    'caption_template' => "Streamline attendance, biometric sync, automated payroll, and compliance with WorkSphere HRMS.\n\nDesigned specifically for high-growth enterprises and modern distributed teams, WorkSphere eliminates HR administrative overhead so your leaders can focus on execution.\n\nExplore our live interactive interactive workspace and book your dedicated executive walkthrough today.",
                    'hashtag_group' => '#WorkSphere #HRMS #PayrollAutomation #EnterpriseSoftware #WorkforceManagement #TalentHub',
                    'cta_template' => 'Explore WorkSphere',
                    'tone' => 'Corporate'
                ],
                [
                    'title' => 'Custom Software & Cloud Architecture — Digital Transformation',
                    'category' => 'IT Services & Software Development',
                    'platform' => 'all',
                    'caption_template' => "Turn strategic business visions into high-performance, fault-tolerant digital systems.\n\nTalentHub IT Services delivers enterprise web applications, high-throughput microservices, and secure API integrations built for scalability.\n\nLet's architect your next digital leap.",
                    'hashtag_group' => '#SoftwareDevelopment #CloudArchitecture #ITServices #DigitalTransformation #WebDevelopment',
                    'cta_template' => 'Request Technical Consultation',
                    'tone' => 'Thought Leadership'
                ],
                [
                    'title' => 'Industry Insights — Modern Engineering Retention Strategies',
                    'category' => 'Technology Insights',
                    'platform' => 'linkedin',
                    'caption_template' => "What truly drives long-term retention for elite engineering teams?\n\nIt's rarely just compensation. High-performing engineering organizations prioritize:\n1. Clear technical autonomy and architectural ownership\n2. Streamlined CI/CD pipelines that respect developer flow\n3. Modern tooling and transparent career progression\n\nHow is your organization evolving its engineering culture this quarter?",
                    'hashtag_group' => '#EngineeringLeadership #DeveloperExperience #TechCulture #TalentStrategy #TalentHub',
                    'cta_template' => 'Read Full Insight',
                    'tone' => 'Thought Leadership'
                ],
                [
                    'title' => 'Life at TalentHub — Culture & Innovation Showcase',
                    'category' => 'Corporate Branding',
                    'platform' => 'instagram',
                    'caption_template' => "Behind every successful project delivery is a dedicated team of passionate problem solvers. ✨\n\nFrom technical brainstorming sessions to celebrating collaborative milestones, we believe that empowering our people creates extraordinary results for our partners.\n\nSwipe through to see our week in review! 👉",
                    'hashtag_group' => '#LifeAtTalentHub #CompanyCulture #Innovation #TeamTalentHub #GrowWithUs',
                    'cta_template' => 'Join Our Team',
                    'tone' => 'Engaging'
                ]
            ];

            foreach ($templates as $t) {
                $db->table('social_content_templates')->insert($t);
            }
        }

        $applied[$dbName] = true;
    }

    public function down()
    {
        $tables = [
            'social_activity_logs',
            'social_notifications',
            'social_comments',
            'social_enquiries',
            'social_analytics_snapshots',
            'social_campaign_posts',
            'social_content_templates',
            'social_publish_attempts',
            'social_scheduled_jobs',
            'social_post_media',
            'social_media_library',
            'social_post_platforms',
            'social_posts',
            'social_campaigns',
            'social_oauth_states',
            'social_credentials',
            'social_accounts',
        ];

        foreach ($tables as $t) {
            $this->db->query("DROP TABLE IF EXISTS `{$t}`");
        }
    }
}

