# TalentHub Consultant — Staging Database Configuration & Security Boundaries

## 1. Database Separation Overview

| Parameter | Production Database | Staging Database | Boundary Status |
|---|---|---|---|
| **Hostinger Account** | `u139147679` | `u139147679` | Same physical cluster |
| **Database Name** | `u139147679_talenthub` | `u139147679_staging` | **Completely Separate** |
| **Database User** | `u139147679_talenthub` | `u139147679_staging` | **Independent User** |
| **Assigned Domain** | `talenthubconsultant.com` | `staging.talenthubconsultant.com` | Isolated per vHost |
| **Internal App Host** | `localhost` / `127.0.0.1:3306` | `localhost` / `127.0.0.1:3306` | Local Unix socket |
| **Remote Host (Admin)** | `srv1824.hstgr.io:3306` | `srv1824.hstgr.io:3306` | Controlled IP whitelist |
| **Production Grants** | Full DDL/DML on `talenthub` | **ZERO ACCESS to talenthub** | Verified blocked |
| **Customer Data Copied**| Live candidate records | **ZERO PRODUCTION DATA** | Clean schema only |

---

## 2. Security Boundaries & Negative Verification Results

Negative security validation was explicitly executed using `scratch/test_db_negative.php`:

### Test 1: Direct Connection Attempt to Production DB
- **Action:** Staging user `u139147679_staging` attempts connection to database `u139147679_talenthub`.
- **Result:** `BLOCKED` — MySQL returned:
  ```text
  Access denied for user 'u139147679_staging'@'...' to database 'u139147679_talenthub'
  ```

### Test 2: Cross-Database SQL Injection / Querying Attempt
- **Action:** Staging user connected to `u139147679_staging` attempts cross-schema read:
  `SELECT * FROM u139147679_talenthub.admin_users LIMIT 1;`
- **Result:** `BLOCKED` — MySQL returned:
  ```text
  SELECT command denied to user 'u139147679_staging'@'...' for table 'u139147679_talenthub'.'admin_users'
  ```

### Test 3: Programmatic Safeguard (`EnvironmentSafetyService`)
- Any destructive operation (`staging:reset`, `staging:seed-test-data`) invokes:
  ```php
  EnvironmentSafetyService::assertNotProduction('Operation Name');
  ```
- If the current environment is `production` OR the active database name does not contain `staging`, execution terminates with a fatal `RuntimeException`.

---

## 3. Schema Provisioning & Seed Data

The staging database schema was initialized from `deployment/clean_database_import.sql` without any production customer data.

### Seeded Staging Tables (53 Tables Verified):
- `admin_users` — Dedicated staging administrator (`staging_admin`)
- `roles` & `permissions` & `user_roles` — RBAC matrix
- `jobs`, `job_categories`, `job_locations` — Pune tech & core engineering listings
- `candidates` & `candidate_documents` — Isolated test candidates
- `applications` & `application_status_history` — Shortlisted dummy candidate pipeline
- `contact_enquiries` & `employer_enquiries` — Inbound test leads
- `newsletter_subscribers` & `newsletter_preferences` — Double opt-in test subscribers
- `announcements` & `announcement_events` — Smart popups & banners
- `email_outbox` & `email_delivery_logs` — Sandboxed email records
- `website_pages` & `faqs` & `testimonials` — Public CMS content
- `audit_logs` — Security and activity audit trail

---

## 4. Staging Backup & Restoration Procedure

### Creating a Staging Backup
To create a snapshot of staging before testing a major feature:

```bash
# Via Hostinger CLI / SSH:
mysqldump -u u139147679_staging -p u139147679_staging > staging_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Resetting Staging to Clean State
To purge all test applications, inquiries, and emails and re-seed clean test data:

```bash
# Safe Spark CLI command:
php spark staging:reset
# Prompts for explicit confirmation token 'RESET-STAGING'
```

