# TalentHub Consultant — Migration Mapping & Data Architecture

**Date:** October 2026  
**Auditor:** Database Architect & Senior PHP Engineer  
**Scope:** Field-by-field mapping from Django models and static JSON to Hostinger MySQL 8.0 schema.

---

## 1. Schema Mapping Overview

```
DJANGO ORM / STATIC JSON           ───►  HOSTINGER MYSQL (InnoDB, utf8mb4)
────────────────────────────────────────────────────────────────────────
apps.users.User                   ───►  admin_users, candidates
apps.roles.*                      ───►  roles, permissions, role_permissions, user_roles
apps.jobs.Category                ───►  job_categories
apps.jobs.Job                     ───►  jobs, job_locations
apps.companies.Company            ───►  employers
apps.applications.Application     ───►  applications, application_status_history
apps.core.validators (resumes)    ───►  candidate_documents
Career Enquiry Form               ───►  general_resume_submissions
apps.cms.Enquiry / contact.php    ───►  contact_enquiries, employer_enquiries
Followup / Notes                  ───►  enquiry_notes, enquiry_followups
apps.cms.BlogPost                 ───►  blog_posts, blog_categories
apps.cms.FAQ                      ───►  faqs
apps.cms.Testimonial              ───►  testimonials
apps.cms.SiteSettings             ───►  website_settings
apps.notifications.EmailTemplate  ───►  email_templates, email_outbox
apps.audit_logs.AuditLog          ───►  audit_logs, login_attempts
Hostinger Cron Monitor            ───►  scheduled_job_runs, data_retention_logs
```

---

## 2. Table-by-Table Schema Specification

### 2.1 Access Control & Administrative Users
- `roles` (`id`, `slug` [VARCHAR 50 UNIQUE], `name` [VARCHAR 100], `description` [TEXT], `created_at`, `updated_at`)
  - Default seeds: `super_admin`, `recruiter`, `content_editor`
- `permissions` (`id`, `code` [VARCHAR 100 UNIQUE], `name` [VARCHAR 150], `category` [VARCHAR 50], `created_at`)
- `role_permissions` (`role_id` [FK], `permission_id` [FK], PRIMARY KEY (`role_id`, `permission_id`))
- `admin_users` (`id`, `username` [VARCHAR 50 UNIQUE], `email` [VARCHAR 190 UNIQUE], `password_hash` [VARCHAR 255], `first_name` [VARCHAR 100], `last_name` [VARCHAR 100], `phone` [VARCHAR 25], `is_active` [TINYINT(1) DEFAULT 1], `last_login_at` [DATETIME], `failed_login_count` [INT DEFAULT 0], `locked_until` [DATETIME], `created_at`, `updated_at`)
- `user_roles` (`user_id` [FK], `role_id` [FK], PRIMARY KEY (`user_id`, `role_id`))
- `login_attempts` (`id`, `email` [VARCHAR(190)], `ip_address` [VARCHAR(45)], `user_agent` [TEXT], `success` [TINYINT(1)], `attempted_at` [DATETIME])

### 2.2 Job Management
- `job_categories` (`id`, `name` [VARCHAR 150 UNIQUE], `slug` [VARCHAR 150 UNIQUE], `description` [TEXT], `order_num` [INT DEFAULT 0], `is_active` [TINYINT(1) DEFAULT 1], `created_at`, `updated_at`)
- `employers` (`id`, `name` [VARCHAR 200], `slug` [VARCHAR 200 UNIQUE], `contact_person` [VARCHAR 150], `contact_email` [VARCHAR 190], `contact_phone` [VARCHAR 30], `industry` [VARCHAR 100], `location` [VARCHAR 200], `website` [VARCHAR 255], `description` [TEXT], `is_active` [TINYINT(1) DEFAULT 1], `created_at`, `updated_at`)
- `jobs` (`id`, `employer_id` [FK NULLABLE], `category_id` [FK], `title` [VARCHAR 200], `slug` [VARCHAR 200 UNIQUE], `experience_min` [INT DEFAULT 0], `experience_max` [INT DEFAULT 0], `experience_label` [VARCHAR 50], `job_type` [ENUM('FT','PT','CT','IN') DEFAULT 'FT'], `work_mode` [ENUM('ONSITE','HYBRID','REMOTE') DEFAULT 'ONSITE'], `location` [VARCHAR 255], `salary_min` [DECIMAL(10,2) NULL], `salary_max` [DECIMAL(10,2) NULL], `is_salary_negotiable` [TINYINT(1) DEFAULT 1], `is_salary_public` [TINYINT(1) DEFAULT 0], `description` [LONGTEXT], `requirements` [LONGTEXT], `status` [ENUM('DRAFT','REVIEW','PUBLISHED','CLOSED','EXPIRED') DEFAULT 'DRAFT'], `published_at` [DATETIME], `expires_at` [DATETIME], `created_by` [FK admin_users], `created_at`, `updated_at`, `deleted_at` [DATETIME NULL])

### 2.3 Candidates & Documents
- `candidates` (`id`, `full_name` [VARCHAR 150], `email` [VARCHAR 190 UNIQUE], `phone` [VARCHAR 30], `current_location` [VARCHAR 150], `preferred_location` [VARCHAR 150], `total_experience_years` [DECIMAL(4,1) DEFAULT 0.0], `current_role` [VARCHAR 150], `primary_skills` [TEXT], `notice_period_days` [INT DEFAULT 30], `consent_received` [TINYINT(1) DEFAULT 1], `consent_timestamp` [DATETIME], `created_at`, `updated_at`)
- `candidate_documents` (`id`, `candidate_id` [FK], `document_type` [ENUM('RESUME','COVER_LETTER','CERTIFICATE') DEFAULT 'RESUME'], `original_name` [VARCHAR 255], `storage_filename` [VARCHAR 255 UNIQUE], `file_path` [VARCHAR 500], `file_size` [BIGINT], `mime_type` [VARCHAR 100], `file_hash` [CHAR(64)], `uploaded_at` [DATETIME])

### 2.4 Applications & Pipelines
- `applications` (`id`, `job_id` [FK], `candidate_id` [FK], `document_id` [FK], `cover_message` [TEXT], `status` [ENUM('APPLIED','SCREENING','SHORTLISTED','INTERVIEW','OFFER','HIRED','REJECTED','WITHDRAWN') DEFAULT 'APPLIED'], `recruiter_notes` [TEXT], `rating` [INT DEFAULT 0], `assigned_recruiter_id` [FK admin_users NULL], `source` [VARCHAR(50) DEFAULT 'WEBSITE'], `ip_address` [VARCHAR(45)], `created_at`, `updated_at`, UNIQUE KEY `unique_candidate_job_active` (`job_id`, `candidate_id`))
- `application_status_history` (`id`, `application_id` [FK], `previous_status` [VARCHAR 30], `new_status` [VARCHAR 30], `changed_by_user_id` [FK admin_users NULL], `comment` [TEXT], `created_at` [DATETIME])
- `general_resume_submissions` (`id`, `candidate_id` [FK], `document_id` [FK], `desired_role` [VARCHAR 150], `preferred_location` [VARCHAR 150], `notice_period` [VARCHAR 50], `notes` [TEXT], `status` [ENUM('NEW','REVIEWED','CONTACTED','ARCHIVED') DEFAULT 'NEW'], `assigned_recruiter_id` [FK admin_users NULL], `created_at`, `updated_at`)

### 2.5 CRM & Enquiries
- `employer_enquiries` (`id`, `company_name` [VARCHAR 200], `contact_person` [VARCHAR 150], `email` [VARCHAR 190], `phone` [VARCHAR 30], `required_roles` [TEXT], `number_of_positions` [INT DEFAULT 1], `location` [VARCHAR 150], `experience_bracket` [VARCHAR 100], `urgency` [ENUM('IMMEDIATE','15_DAYS','30_DAYS','EXPLORATORY') DEFAULT 'IMMEDIATE'], `notes` [TEXT], `status` [ENUM('NEW','CONTACTED','IN_DISCUSSION','MANDATE_WON','CLOSED_LOST') DEFAULT 'NEW'], `assigned_to` [FK admin_users NULL], `created_at`, `updated_at`)
- `contact_enquiries` (`id`, `first_name` [VARCHAR 100], `last_name` [VARCHAR 100], `email` [VARCHAR 190], `phone` [VARCHAR 30], `company` [VARCHAR 150], `subject` [VARCHAR 200], `message` [TEXT], `status` [ENUM('UNREAD','READ','RESPONDED','SPAM') DEFAULT 'UNREAD'], `ip_address` [VARCHAR 45], `created_at`, `updated_at`)
- `enquiry_notes` (`id`, `enquiry_type` [ENUM('EMPLOYER','GENERAL')], `enquiry_id` [BIGINT], `author_id` [FK admin_users], `note` [TEXT], `created_at` [DATETIME])
- `enquiry_followups` (`id`, `enquiry_type` [ENUM('EMPLOYER','GENERAL')], `enquiry_id` [BIGINT], `scheduled_date` [DATETIME], `reminder_date` [DATETIME], `status` [ENUM('PENDING','COMPLETED','OVERDUE','CANCELLED') DEFAULT 'PENDING'], `assigned_to` [FK admin_users], `outcome` [TEXT], `created_at`, `updated_at`)

### 2.6 CMS & Site Settings
- `blog_categories` (`id`, `name` [VARCHAR 100 UNIQUE], `slug` [VARCHAR 100 UNIQUE], `created_at`)
- `blog_posts` (`id`, `category_id` [FK NULLABLE], `title` [VARCHAR 255], `slug` [VARCHAR 255 UNIQUE], `summary` [TEXT], `content` [LONGTEXT], `featured_image` [VARCHAR 255], `meta_title` [VARCHAR 255], `meta_description` [TEXT], `is_published` [TINYINT(1) DEFAULT 1], `published_at` [DATETIME], `author_id` [FK admin_users], `created_at`, `updated_at`)
- `faqs` (`id`, `category` [VARCHAR 100 DEFAULT 'General'], `question` [VARCHAR 500], `answer` [TEXT], `order_num` [INT DEFAULT 0], `is_published` [TINYINT(1) DEFAULT 1], `created_at`, `updated_at`)
- `testimonials` (`id`, `name` [VARCHAR 150], `designation` [VARCHAR 150], `company` [VARCHAR 150], `content` [TEXT], `rating` [INT DEFAULT 5], `photo_path` [VARCHAR 255], `order_num` [INT DEFAULT 0], `is_published` [TINYINT(1) DEFAULT 1], `created_at`, `updated_at`)
- `website_settings` (`id`, `setting_key` [VARCHAR 100 UNIQUE], `setting_value` [LONGTEXT], `setting_group` [VARCHAR 50], `is_public` [TINYINT(1) DEFAULT 1], `updated_at`)

### 2.7 Email Outbox & Audit Logging
- `email_templates` (`id`, `code` [VARCHAR 100 UNIQUE], `name` [VARCHAR 150], `subject` [VARCHAR 255], `body_html` [LONGTEXT], `body_text` [LONGTEXT], `variables_help` [TEXT], `is_active` [TINYINT(1) DEFAULT 1], `updated_at`)
- `email_outbox` (`id`, `template_code` [VARCHAR 100 NULL], `recipient_email` [VARCHAR 190], `recipient_name` [VARCHAR 150], `subject` [VARCHAR 255], `body_html` [LONGTEXT], `body_text` [LONGTEXT], `status` [ENUM('PENDING','SENT','FAILED') DEFAULT 'PENDING'], `attempts` [INT DEFAULT 0], `last_error` [TEXT], `idempotency_key` [VARCHAR 100 UNIQUE], `scheduled_at` [DATETIME], `sent_at` [DATETIME NULL], `created_at` [DATETIME])
- `audit_logs` (`id`, `user_id` [FK admin_users NULL], `action` [VARCHAR 100], `entity_type` [VARCHAR 100], `entity_id` [VARCHAR 100], `old_values` [JSON], `new_values` [JSON], `ip_address` [VARCHAR 45], `user_agent` [VARCHAR 500], `created_at` [DATETIME])
- `scheduled_job_runs` (`id`, `job_name` [VARCHAR 100], `status` [ENUM('RUNNING','SUCCESS','FAILED')], `items_processed` [INT DEFAULT 0], `error_details` [TEXT], `duration_ms` [INT DEFAULT 0], `started_at` [DATETIME], `completed_at` [DATETIME])
