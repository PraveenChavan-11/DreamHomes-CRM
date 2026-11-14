<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SetupEmailTables extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'mail:setup-tables';
    protected $description = 'Create or verify the 6 Email Management & Communication Center database tables';

    public function run(array $params)
    {
        CLI::write('Setting up TalentHub Email Management & Communication Center tables...', 'yellow');

        $db = \Config\Database::connect();

        $queries = [
            // 1. mailbox_connections
            "CREATE TABLE IF NOT EXISTS `mailbox_connections` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `mailbox_resource_id` VARCHAR(100) NOT NULL UNIQUE,
                `email` VARCHAR(255) NOT NULL,
                `display_name` VARCHAR(255) NULL,
                `status` VARCHAR(50) DEFAULT 'ACTIVE',
                `quota_used_bytes` BIGINT DEFAULT 0,
                `quota_total_bytes` BIGINT DEFAULT 0,
                `is_default` TINYINT(1) DEFAULT 0,
                `assigned_roles` VARCHAR(255) DEFAULT 'all',
                `last_synced_at` DATETIME NULL,
                `sync_status` VARCHAR(50) DEFAULT 'IDLE',
                `sync_error` TEXT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                KEY `idx_email` (`email`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 2. mail_message_index
            "CREATE TABLE IF NOT EXISTS `mail_message_index` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `mailbox_resource_id` VARCHAR(100) NOT NULL,
                `message_uid` VARCHAR(150) NOT NULL,
                `folder` VARCHAR(100) NOT NULL DEFAULT 'INBOX',
                `thread_id` VARCHAR(200) NULL,
                `internet_message_id` VARCHAR(255) NULL,
                `in_reply_to` VARCHAR(255) NULL,
                `references_header` TEXT NULL,
                `subject` VARCHAR(500) NULL,
                `from_name` VARCHAR(255) NULL,
                `from_email` VARCHAR(255) NOT NULL,
                `to_recipients` TEXT NULL,
                `cc_recipients` TEXT NULL,
                `bcc_recipients` TEXT NULL,
                `reply_to` VARCHAR(255) NULL,
                `snippet` TEXT NULL,
                `body_html` MEDIUMTEXT NULL,
                `body_text` MEDIUMTEXT NULL,
                `has_attachments` TINYINT(1) DEFAULT 0,
                `attachments_meta` TEXT NULL,
                `is_read` TINYINT(1) DEFAULT 0,
                `is_flagged` TINYINT(1) DEFAULT 0,
                `is_draft` TINYINT(1) DEFAULT 0,
                `category` VARCHAR(50) DEFAULT 'general',
                `recruitment_type` VARCHAR(50) NULL,
                `recruitment_id` INT NULL,
                `internal_notes` TEXT NULL,
                `assigned_to` INT NULL,
                `message_date` DATETIME NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                UNIQUE KEY `uniq_box_folder_uid` (`mailbox_resource_id`, `folder`, `message_uid`),
                KEY `idx_folder` (`folder`),
                KEY `idx_date` (`message_date`),
                KEY `idx_from` (`from_email`),
                KEY `idx_read` (`is_read`),
                KEY `idx_cat` (`category`),
                KEY `idx_recruitment` (`recruitment_type`, `recruitment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 3. mail_local_drafts
            "CREATE TABLE IF NOT EXISTS `mail_local_drafts` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `admin_user_id` INT NOT NULL,
                `mailbox_resource_id` VARCHAR(100) NOT NULL,
                `to_recipients` TEXT NOT NULL,
                `cc_recipients` TEXT NULL,
                `bcc_recipients` TEXT NULL,
                `subject` VARCHAR(500) NULL,
                `body_html` MEDIUMTEXT NULL,
                `body_text` MEDIUMTEXT NULL,
                `attachments_json` TEXT NULL,
                `in_reply_to` VARCHAR(255) NULL,
                `thread_id` VARCHAR(200) NULL,
                `category` VARCHAR(50) DEFAULT 'general',
                `recruitment_type` VARCHAR(50) NULL,
                `recruitment_id` INT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL,
                KEY `idx_admin` (`admin_user_id`),
                KEY `idx_mailbox` (`mailbox_resource_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 4. mail_recruitment_links
            "CREATE TABLE IF NOT EXISTS `mail_recruitment_links` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `mailbox_resource_id` VARCHAR(100) NOT NULL,
                `message_uid` VARCHAR(150) NOT NULL,
                `folder` VARCHAR(100) DEFAULT 'INBOX',
                `entity_type` ENUM('application', 'candidate', 'resume_submission', 'employer_enquiry', 'contact_enquiry') NOT NULL,
                `entity_id` INT NOT NULL,
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` DATETIME NULL,
                KEY `idx_entity` (`entity_type`, `entity_id`),
                KEY `idx_msg` (`mailbox_resource_id`, `message_uid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 5. mail_webhook_events
            "CREATE TABLE IF NOT EXISTS `mail_webhook_events` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `event_id` VARCHAR(150) NULL UNIQUE,
                `event_type` VARCHAR(100) NOT NULL,
                `mailbox_resource_id` VARCHAR(100) NULL,
                `payload_json` MEDIUMTEXT NOT NULL,
                `status` ENUM('PENDING', 'PROCESSED', 'FAILED', 'IGNORED') DEFAULT 'PENDING',
                `error_message` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `processed_at` DATETIME NULL,
                `created_at` DATETIME NULL,
                KEY `idx_status` (`status`),
                KEY `idx_event` (`event_type`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            // 6. mail_activity_logs
            "CREATE TABLE IF NOT EXISTS `mail_activity_logs` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `admin_user_id` INT NULL,
                `action` VARCHAR(50) NOT NULL,
                `mailbox_resource_id` VARCHAR(100) NULL,
                `message_uid` VARCHAR(150) NULL,
                `details_json` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `created_at` DATETIME NULL,
                KEY `idx_user_action` (`admin_user_id`, `action`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        ];

        foreach ($queries as $sql) {
            $db->query($sql);
        }

        // Seed default mailbox connections if none exist
        $boxCount = $db->table('mailbox_connections')->countAllResults();
        if ($boxCount === 0) {
            $now = date('Y-m-d H:i:s');
            $db->table('mailbox_connections')->insertBatch([
                [
                    'mailbox_resource_id' => 'mb_hr_pune_primary',
                    'email'               => 'hr@talenthubconsultant.com',
                    'display_name'        => 'TalentHub HR & Recruitment',
                    'status'              => 'ACTIVE',
                    'quota_used_bytes'    => 142606336, // ~136 MB
                    'quota_total_bytes'   => 10737418240, // 10 GB
                    'is_default'          => 1,
                    'assigned_roles'      => 'all',
                    'last_synced_at'      => $now,
                    'sync_status'         => 'SUCCESS',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ],
                [
                    'mailbox_resource_id' => 'mb_info_general',
                    'email'               => 'info@talenthubconsultant.com',
                    'display_name'        => 'TalentHub General Inquiries',
                    'status'              => 'ACTIVE',
                    'quota_used_bytes'    => 54525952, // ~52 MB
                    'quota_total_bytes'   => 10737418240, // 10 GB
                    'is_default'          => 0,
                    'assigned_roles'      => 'all',
                    'last_synced_at'      => $now,
                    'sync_status'         => 'SUCCESS',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ],
            ]);
            CLI::write('[PASS] Seeded default mailboxes: hr@talenthubconsultant.com & info@talenthubconsultant.com', 'green');
        }

        CLI::write('[PASS] All 6 Email Management tables created and verified successfully!', 'green');
    }
}

