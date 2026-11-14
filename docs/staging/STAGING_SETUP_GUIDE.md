# TalentHub Consultant — Staging Environment Setup Guide

## 1. Hosting Architecture Overview

The staging environment is hosted on Hostinger Shared / Cloud hosting under:
- **Subdomain:** `staging.talenthubconsultant.com`
- **Document Root:** `/home/u139147679/domains/talenthubconsultant.com/public_html/staging`
- **PHP Version:** PHP 8.2 or 8.3 (configured with `mysqli`, `curl`, `intl`, `mbstring`, `fileinfo`, `gd`, `zip`)
- **SSL Certificate:** Free Let's Encrypt / Hostinger SSL (HTTPS enforced)

---

## 2. Directory Layout on Hostinger

```text
/home/u139147679/domains/talenthubconsultant.com/public_html/
│
├── .htaccess                       <-- Production Web Router (Blocks direct access to /staging)
├── index.php                       <-- Production Entrypoint
├── app/                            <-- Production CodeIgniter app
├── public/                         <-- Production Web Root
│
└── staging/                        <-- STAGING ROOT & DOCUMENT ROOT
    ├── .env                        <-- Staging environment variables (CI_ENVIRONMENT=staging)
    ├── .htaccess                   <-- Staging Apache routing & security rules
    ├── index.php                   <-- Staging Entrypoint
    ├── robots.txt                  <-- Disallow: /
    ├── manifest-staging.json       <-- Staging PWA manifest
    ├── sw-staging.js               <-- Staging service worker
    ├── spark                       <-- Staging Spark CLI
    ├── app/                        <-- Staging application code
    ├── public/                     <-- Staging public assets & uploads
    └── writable/                   <-- Staging writable storage
        ├── cache/                  <-- Staging page cache
        ├── logs/                   <-- Staging log files
        ├── session/                <-- Staging sessions
        └── vault/resumes/          <-- Staging candidate resumes
```

---

## 3. Server Configuration (`.env`) for Staging

Create `/home/u139147679/domains/talenthubconsultant.com/public_html/staging/.env`:

```ini
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = staging

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'https://staging.talenthubconsultant.com/'
app.indexPage = ''
app.forceGlobalSecureRequests = true
app.CSPEnabled = false

#--------------------------------------------------------------------
# DATABASE (ISOLATED HOSTINGER STAGING DATABASE)
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = u139147679_staging
database.default.username = u139147679_staging
database.default.password = 'YOUR_GENERATED_STAGING_PASSWORD'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

#--------------------------------------------------------------------
# BROWSER COOKIE & SESSION PARTITIONING
#--------------------------------------------------------------------
cookie.prefix = 'th_staging_'
session.cookieName = 'ci_session_staging'
security.cookieName = 'csrf_cookie_staging'

#--------------------------------------------------------------------
# EMAIL & NOTIFICATION SANDBOX
#--------------------------------------------------------------------
EMAIL_SANDBOX_ENABLED = true
STAGING_SANDBOX_INBOX = 'staging-logs@talenthubconsultant.com'
HOSTINGER_MAIL_ENABLED = false
```

---

## 4. Initial Staging Provisioning via Spark CLI

Execute these commands inside `/home/u139147679/domains/talenthubconsultant.com/public_html/staging`:

```bash
# 1. Verify environment telemetry and isolation status
php spark staging:status

# 2. Provision and verify schema & roles in staging DB
php spark staging:provision-db

# 3. Seed rich dummy records for UAT (Jobs, Enquiries, Applications, Subscribers)
php spark staging:seed-test-data

# 4. Provision dedicated staging administrator account
php spark staging:provision-admin --username=staging_admin --email=admin.staging@talenthubconsultant.com
```

---

## 5. File & Directory Permissions

Ensure strict directory permissions on the staging folder:

```bash
chmod -R 775 /home/u139147679/domains/talenthubconsultant.com/public_html/staging/writable
chmod -R 700 /home/u139147679/domains/talenthubconsultant.com/public_html/staging/writable/vault
chmod 644 /home/u139147679/domains/talenthubconsultant.com/public_html/staging/.htaccess
chmod 644 /home/u139147679/domains/talenthubconsultant.com/public_html/staging/index.php
```

---

## 6. Verification Checklist

1. [x] Visit `https://staging.talenthubconsultant.com/` — Verifies HTTP 200 and Staging Banner displays.
2. [x] Inspect HTML `<head>` — Verifies `<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">`.
3. [x] Visit `https://staging.talenthubconsultant.com/robots.txt` — Verifies `User-agent: *\nDisallow: /`.
4. [x] Visit `https://staging.talenthubconsultant.com/sitemap.xml` — Verifies HTTP 404 response.
5. [x] Visit `https://staging.talenthubconsultant.com/admin/login` — Verifies independent admin portal loads.
6. [x] Log in with `staging_admin` — Verifies dashboard loads with Amber `STAGING ENVIRONMENT` badge.
7. [x] Click `STAGING ENVIRONMENT` badge — Verifies Staging Control Centre diagnostic modal opens.

