# TalentHub Consultant — Staging Admin Login & Access Guide

## 1. Portal URLs

- **Production Admin Portal:** `https://talenthubconsultant.com/admin/login` (Live Operations)
- **Staging Admin Portal:** `https://staging.talenthubconsultant.com/admin/login` (QA & Testing)

---

## 2. Default Staging Administrator Account

A dedicated, isolated administrator account has been provisioned exclusively inside the `u139147679_staging` database:

- **Portal URL:** `https://staging.talenthubconsultant.com/admin/login`
- **Username:** `staging_admin`
- **Email:** `admin.staging@talenthubconsultant.com`
- **Initial Password:** `TalentHub@Staging2026!`
- **Assigned Role:** `Super Administrator` (Role ID: 1 — Unrestricted permissions)

> [!IMPORTANT]
> The staging administrator account is completely independent from the production administrator. Modifying passwords, details, or permissions in staging does NOT affect production.

---

## 3. Provisioning or Rotating Staging Admin Credentials

Administrators can rotate or create new staging staff accounts via CLI:

```bash
# Rotate password or create new staging admin
php spark staging:provision-admin --username=staging_admin --email=admin.staging@talenthubconsultant.com --password=YourNewSecurePassword123!

# If no password is provided, a cryptographically secure 20-character password will be auto-generated:
php spark staging:provision-admin --username=qa_lead --email=qa.lead@talenthubconsultant.com
```

---

## 4. Staging Control Centre & Visual Indicator

When logged into the Staging Admin Portal:

1. **Header Indicator:** A prominent Amber badge appears in the top navigation:
   `STAGING ENVIRONMENT | DB: u139147679_staging | SANDBOX ACTIVE`
2. **Interactive Diagnostics Modal:** Clicking the Amber badge opens the **Staging Control Centre Modal**, displaying:
   - Active runtime environment (`staging`)
   - Isolated database name and connection health
   - Outbound email sandboxing status
   - Search engine indexing shield status
   - PWA cache partition and service worker scope
   - Real-time test record counts across all core tables
   - Quick reference for Spark CLI commands

---

## 5. Testing Admin Dashboard Modules

Every admin module functions independently against the staging database:

| Admin Module | Staging Behavior | Verification Check |
|---|---|---|
| **Jobs Management** | Add, edit, publish, close job postings | Affects only staging job listings |
| **Job Applications** | Shortlist, change pipeline stages, add notes | Staging applications only |
| **General Resume Bank** | View and search test candidate CVs | Reads staging vault |
| **Employer Enquiries** | Transition lead stages, update follow-up deadlines | Isolated staging leads |
| **Contact Messages** | Review and close test contact submissions | Isolated staging inquiries |
| **Communication Center**| View sent emails; all deliveries sandboxed | Zero real emails dispatched |
| **Engagement Centre** | Create center modals, announcement banners, campaigns | Staging audience only |
| **Content Management** | Edit CMS page drafts, blog articles, FAQs | Staging content only |
| **Settings & Branding** | Configure company contacts and social links | Staging database only |
| **Staff & Roles** | Manage staff users and role permissions | Staging `admin_users` table |
| **Audit Logs** | Full audit trail of all staging administrative actions | Isolated staging audit logs |

