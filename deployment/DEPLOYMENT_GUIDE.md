# TalentHub Consultant — Hostinger Production Deployment Guide

**Production Domain:** `talenthubconsultant.com`
**Architecture:** CodeIgniter 4 + PHP 8.2+ + MySQL + Tailwind CSS
**Target Host:** Hostinger Standard Shared Hosting

---

## 1. Prerequisites on Hostinger hPanel

1. **PHP Version:** Set to **PHP 8.2** in hPanel → *Websites* → *PHP Configuration*. Ensure extensions `curl`, `intl`, `mbstring`, `mysqli`, `openssl`, `fileinfo`, `gd`, `zip` are enabled.
2. **SSL Certificate:** Ensure Let's Encrypt / Hostinger Free SSL is active with HTTPS enforcement.
3. **Database Setup:**
   - In hPanel → *Databases* → *MySQL Databases*, create a database (e.g. `uXXXXX_talenthub`).
   - Create a database user with full privileges and save the generated password.
   - Open *phpMyAdmin*, select the created database, click **Import**, choose `deployment/database_hostinger_export.sql`, and execute.

---

## 2. Directory Layout Architecture

To maintain enterprise security standards, private application code, credentials, and candidate resumes reside **outside** the public web directory:

```text
/home/uXXXXX/
│
├── talenthub_core/                     <-- Private Application Core (Not web accessible)
│   ├── .env                            <-- Environment credentials & DB config
│   ├── spark                           <-- CLI tool
│   ├── app/                            <-- Controllers, Models, Views, Services
│   ├── vendor/                         <-- Composer dependencies
│   └── writable/
│       ├── cache/                      <-- Page cache
│       ├── logs/                       <-- Daily application logs
│       └── vault/                      <-- Private candidate resume PDFs (Strict chmod 700)
│
└── public_html/                        <-- Public Web Root
    ├── .htaccess                       <-- Apache mod_rewrite, HTTPS & Gzip rules
    ├── index.php                       <-- Front Controller (deployment/hostinger_public_html_index.php)
    ├── favicon.ico
    ├── logo.png
    ├── logo-white.png
    ├── assets/
    │   ├── css/                        <-- app.css, admin.css
    │   └── js/                         <-- app.js, admin.js
    └── uploads/                        <-- Public CMS media images
```

---

## 3. Step-by-Step Upload Instructions

### Method A: Automated Deployment via SSH (Recommended)
1. In hPanel, enable **SSH Access** and connect:
   ```bash
   ssh uXXXXX@talenthubconsultant.com -p 65002
   ```
2. Clone or copy your branch:
   ```bash
   git clone -b feature/ci4-enterprise-transformation https://github.com/PraveenChavan-11/JobConsultancy.git talenthub_repo
   cd talenthub_repo
   bash deployment/hostinger_deploy.sh
   ```

### Method B: Manual File Manager Upload (Non-Technical)
1. Open hPanel → **File Manager**.
2. Go to `/home/uXXXXX/` (one level above `public_html`).
3. Create a folder named `talenthub_core`.
4. Upload `app/`, `vendor/`, `writable/`, `spark`, and `.env` into `talenthub_core/`.
5. Open `public_html/`:
   - Delete legacy Next.js files (if any).
   - Upload `deployment/hostinger_public_html_index.php` and rename it to `index.php`.
   - Upload `deployment/hostinger.htaccess` and rename it to `.htaccess`.
   - Upload `public/assets/`, `public/uploads/`, `public/logo.png`, `public/logo-white.png`, `public/favicon.ico`.

---

## 4. Production `.env` Configuration

Create or update `/home/uXXXXX/talenthub_core/.env`:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://talenthubconsultant.com/'
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = uXXXXX_talenthub
database.default.username = uXXXXX_user
database.default.password = 'YOUR_DB_PASSWORD'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

---

## 5. Hostinger Cron Jobs Configuration

In hPanel → *Advanced* → *Cron Jobs*, configure:

| Job Name | Schedule | Command |
|---|---|---|
| **Engagement Centre Scheduler** | Every 1 minute (`* * * * *`) | `/usr/bin/php /home/uXXXXX/talenthub_core/spark engagement:run >/dev/null 2>&1` |
| **Email Outbox Queue** | Every 1 minute (`* * * * *`) | `/usr/bin/php /home/uXXXXX/talenthub_core/spark outbox:process >/dev/null 2>&1` |
| **Hostinger Mail API Sync** | Every 5 minutes (`*/5 * * * *`) | `/usr/bin/php /home/uXXXXX/talenthub_core/spark mail:sync >/dev/null 2>&1` |
| **Sitemap Regeneration** | Daily at 3:00 AM (`0 3 * * *`) | `/usr/bin/php /home/uXXXXX/talenthub_core/spark sitemap:generate >/dev/null 2>&1` |

*Shared Hosting Alternative (HTTP Webhook Cron):*
If your shared hosting tier does not allow SSH/CLI cron execution, trigger via hPanel URL Cron:
`GET https://talenthubconsultant.com/api/v1/cron/run?key=talenthub_cron_secret_2026`

---

## 6. Hostinger Mail API Integration & Configuration

TalentHub features a native Email Management & Recruitment Communication Center powered by the official Hostinger Mail API.

### 6.1 Obtaining Your Bearer Token
1. Log in to Hostinger hPanel.
2. Navigate to **Emails** → Choose `talenthubconsultant.com`.
3. Go to **Agentic Mail** (or **API access**) in the email sidebar.
4. Generate a new API token with full mailbox management and sending scopes.

### 6.2 Adding Token in TalentHub Admin (Non-Technical)
1. Open the TalentHub Admin Dashboard: `https://talenthubconsultant.com/admin`.
2. Navigate to **Communication Center** → **Email Management**.
3. Click the **Settings & Token** tab.
4. Paste your Bearer Token into the **Hostinger Mail API Bearer Token** field.
5. Click **Test Connection (GET /api/v1/me)** to verify.
6. Click **Save Settings**.
*(Alternatively, configure `HOSTINGER_MAIL_API_TOKEN='...'` in your server `.env` file).*

### 6.3 Registering Inbound Webhooks
In your Hostinger Mail API portal, add a webhook targeting:
`https://talenthubconsultant.com/api/v1/webhooks/hostinger-mail` with event `message.created`. New incoming candidate and employer inquiries will appear in real-time in the communication dashboard.

---

## 7. Default Admin Credentials

- **Admin Login Portal:** `https://talenthubconsultant.com/admin/login`
- **Primary Admin:** `admin` or `admin@talenthubconsultant.com`
- **Initial Password:** `TalentHub@Admin2026!`
- **HR Desk Admin:** `hr` or `hr@talenthubconsultant.com`
- **HR Password:** `HR@TalentHub2026`

*Immediately change passwords inside the Admin Portal under My Profile after initial login.*

---

## 8. Engagement Centre & Database Migration

### 8.1 Database Migration
To set up all new Engagement Centre database tables, constraints, permissions, and email templates:
- **Via Spark CLI:**
  ```bash
  php spark migrate
  ```
- **Via phpMyAdmin (Manual Import):**
  Import `deployment/migration_20261101_engagement_centre.sql` into your database.

### 8.2 Verified Features Included
- **Double Opt-In Newsletter System:** Cryptographic SHA-256 token hashing, single-click unsubscribe, preference centers, suppression enforcement.
- **Smart Announcements & Modals:** Center popups, top/bottom announcement banners, inline homepage blocks, audience targeting (Candidates, Employers, Insights, Everyone), and frequency capping (Once per session, 1/7/30 days, until dismissed).
- **Email Campaigns & Automation:** Pre-built responsive HTML templates, test email dispatches, verified subscriber broadcasts, delivery logs.
- **Job Alert Subscriptions:** Matching real vacancies in database with instant, daily, or weekly notifications.
- **Unified Lead Management:** Aggregated candidate inquiries, employer mandates, newsletter subscribers, and job alerts in one view with CSV export.
- **DPDP Act 2023 / GDPR Cookie Consent Manager:** Granular analytics/marketing preferences banner, non-PII logging, and privacy compliance.


