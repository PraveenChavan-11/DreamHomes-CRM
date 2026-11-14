# TalentHub — Hostinger Production Deployment Guide
**Target Domain:** https://talenthubconsultant.com  
**Target Architecture:** Next.js Static Web + React/Vite Admin SPA + PHP 8.2+ REST API + Hostinger MySQL  
**Monthly Cost:** ₹0 Additional Expenditure (Hostinger Premium Shared Plan)

---

## 1. Directory Structure on Hostinger

```text
/home/u123456789/
├── config/
│   └── env.php                     <- [CHMOD 0600] Private configuration outside web root
├── private_vault/
│   └── resumes/                    <- [CHMOD 0700] Private resume files (UUID-named)
├── logs/
│   ├── cron.log
│   └── php_error.log
└── public_html/                    <- Main Web Root
    ├── index.html                  <- Next.js Static Export Homepage
    ├── jobs.html                   <- Next.js Static Export Jobs
    ├── jobs/                       <- Job detail pages
    ├── contact.html                <- Contact form
    ├── .htaccess                   <- Top-level Apache routing & security headers
    ├── admin/                      <- Built React + Vite SPA
    │   ├── index.html
    │   └── assets/
    └── api/                        <- PHP API Entry & Core
        ├── .htaccess               <- Security gate for api/
        ├── cron.php                <- CLI / curl cron runner
        ├── config/
        ├── src/
        ├── migrations/
        └── v1/
            ├── .htaccess           <- Rewrites all /api/v1/* to index.php
            └── index.php           <- Front Controller & Routing
```

---

## 2. Step-by-Step Deployment Steps

### Step 2.1 — Create Database & Run Schema Migrations
1. Log in to **Hostinger hPanel** -> **Databases** -> **MySQL Databases**.
2. Create a new database:
   - **Database Name:** e.g., `u123456789_talenthub`
   - **Username:** e.g., `u123456789_th_admin`
   - **Password:** Generate a strong 24+ character password.
3. Open **phpMyAdmin** for this database.
4. Import the schema files in order:
   - `php-api/migrations/001_initial_schema.sql`
   - `php-api/migrations/002_seed_initial_data.sql`
   *(Alternatively, run `php php-api/migrate.php` over SSH).*

### Step 2.2 — Set Up Private Storage & Config
1. In hPanel **File Manager** (or via SSH):
2. Navigate up to `/home/u123456789/` (one level above `public_html`).
3. Create folder `config/` and upload `env.php` (created from `php-api/config/env.example.php`).
4. Set permissions of `/home/u123456789/config/env.php` to `0600`.
5. Create folder `private_vault/resumes/` and set permissions to `0700`.

### Step 2.3 — Automated Deployment via GitHub Actions
Our GitHub Actions workflow `.github/workflows/hostinger-static.yml` automatically:
1. Runs the PHP API test suite (`php php-api/tests/test_runner.php`).
2. Builds the Next.js static site (`npm run build` in `frontend/`).
3. Builds the React Admin dashboard (`npm run build` in `admin/`).
4. Assembles the deployable bundle in `frontend/out/` containing:
   - Static public site
   - `admin/` SPA
   - `api/v1/` PHP backend
5. Force pushes the verified bundle to the `hostinger-static` branch.
6. In Hostinger hPanel -> **Advanced** -> **GIT**:
   - Point Git repository to `https://github.com/PraveenChavan-11/JobConsultancy`
   - Branch: `hostinger-static`
   - Deploy Directory: `/public_html`
   - Click **Deploy** (or configure Webhook for automatic deployments).

---

## 3. Hostinger Scheduled Tasks (Cron)

Set up a Hostinger Cron Job in hPanel -> **Advanced** -> **Cron Jobs**:
- **Type:** Custom Command
- **Interval:** Every 5 minutes (`*/5 * * * *`)
- **Command:**
  ```bash
  /usr/bin/php /home/u123456789/public_html/api/cron.php >> /home/u123456789/logs/cron.log 2>&1
  ```
This background runner processes:
- Outbox emails in batches of 20 with retry limits.
- Automatically marks jobs with past expiry dates as `CLOSED`.
- Flags overdue employer enquiry follow-ups.
- Cleans up audit logs older than 90 days.

---

## 4. Verification URLs

| Target | URL | Expected Response |
| :--- | :--- | :--- |
| **Public Website** | `https://talenthubconsultant.com/` | 200 OK — Next.js static homepage |
| **Active Jobs** | `https://talenthubconsultant.com/jobs` | 200 OK — Active Pune vacancies |
| **Public API Health** | `https://talenthubconsultant.com/api/v1/jobs` | 200 OK JSON — Real jobs list |
| **Admin Login** | `https://talenthubconsultant.com/admin/login` | 200 OK — Vite React Admin Login |
| **Protected Stream** | `https://talenthubconsultant.com/api/v1/admin/documents/1/stream` | 401 Unauthorized (unless logged in) |
