# TalentHub — Database Migration & Schema Guide

## 1. Overview
The TalentHub production database runs on **MySQL 8.0+ / MariaDB** via Hostinger shared hosting using InnoDB tables, `utf8mb4_unicode_ci` collation, strict foreign key constraints, and normalized architecture across 31 tables.

Migrations are version-controlled and idempotent:
- `php-api/migrations/001_initial_schema.sql`
- `php-api/migrations/002_seed_initial_data.sql`
- `php-api/migrate.php` (CLI migration runner for SSH or CI environments)

---

## 2. Table Catalog

| Category | Table Name | Purpose |
| :--- | :--- | :--- |
| **RBAC & Auth** | `roles` | System roles (SUPER_ADMIN, RECRUITER, CONTENT_EDITOR) |
| | `permissions` | Granular capability slugs (`jobs.create`, `resumes.download`, etc.) |
| | `role_permissions` | Many-to-many role permission map |
| | `admin_users` | Admin accounts with bcrypt password hashes |
| | `user_roles` | User-to-role assignments |
| | `login_attempts` | IP-based rate limiting & brute-force throttling |
| **Job Management** | `employers` | B2B Client accounts (Austin Realty, etc.) |
| | `job_categories` | Taxonomies (Engineering, Construction, HR, Sales) |
| | `job_locations` | Cities and hubs (Katraj, Hinjawadi, Chakan) |
| | `jobs` | Job mandates with status (`DRAFT`, `PUBLISHED`, `CLOSED`, `ARCHIVED`) |
| **Candidates & Resumes** | `candidates` | Deduplicated candidate directory (keyed by email) |
| | `candidate_documents` | Private resume metadata, UUID filenames, MIME signatures |
| | `applications` | Flow A: Candidate job applications (idempotent submission) |
| | `application_status_history`| Audit timeline of status changes with recruiter notes |
| | `general_resume_submissions`| Flow B: General talent submissions from career enquiry |
| **Enquiries & CRM** | `employer_enquiries` | Hiring mandates submitted by client employers |
| | `contact_enquiries` | Inbound contact submissions |
| | `enquiry_notes` | Recruiter internal CRM notes |
| | `enquiry_followups` | Follow-up schedules and reminder tracking |
| **CMS & SEO** | `blog_categories` | Content themes |
| | `blog_posts` | Published insights and articles |
| | `faqs` | Published FAQ items with display ordering |
| | `testimonials` | Client and candidate recommendations |
| | `website_settings` | Dynamic site configuration key-value store |
| **Outbox & System** | `email_templates` | Database-backed responsive email templates |
| | `email_outbox` | Async email queue with idempotency keys and retry count |
| | `audit_logs` | Tamper-evident admin action trail |
| | `scheduled_job_runs` | Cron execution logs and lock tracking |
| | `data_retention_logs` | Cleanup audit entries |
| | `migrations` | Schema version tracking |

---

## 3. Running Migrations

### Method A — Via CLI (Recommended for SSH / Staging)
```bash
# Execute CLI migration runner
php php-api/migrate.php
```

### Method B — Via phpMyAdmin (Hostinger hPanel)
1. Log in to Hostinger hPanel -> Databases -> phpMyAdmin.
2. Select your database.
3. Click the **Import** tab.
4. Select `php-api/migrations/001_initial_schema.sql` and click **Import**.
5. Select `php-api/migrations/002_seed_initial_data.sql` and click **Import**.

---

## 4. Rollback Procedures
Never perform destructive schema operations (`DROP TABLE`, `TRUNCATE`) in production without taking a full backup first.
To revert a migration:
1. Export a full database dump:
   ```bash
   mysqldump -u <user> -p <database> > backup_before_rollback.sql
   ```
2. Apply the reverse delta SQL or restore from the verified pre-migration backup.
