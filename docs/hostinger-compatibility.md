# TalentHub Consultant — Hostinger Compatibility & Deployment Matrix

**Date:** October 2026  
**Auditor:** Principal DevSecOps Engineer & Systems Architect  
**Platform:** Hostinger Premium Shared Hosting (CloudLinux / cPanel / hPanel)  
**Cost Impact:** ₹0 additional monthly expenditure  

---

## 1. Hosting Environment Capabilities

| Feature / Limit | Hostinger Premium Shared Plan | TalentHub Architectural Alignment |
|---|---|---|
| **Web Server** | Apache 2.4 + LiteSpeed LSAPI | Standard `.htaccess` rewrites, compression, and security rules |
| **PHP Version** | PHP 8.2 & 8.3 supported | Targeted for PHP 8.2+ with `declare(strict_types=1)` and PDO |
| **Database** | MySQL 8.0 / MariaDB 10.11 | InnoDB engine, `utf8mb4_unicode_ci`, strict SQL mode, foreign keys |
| **Max Execution Time** | 30–60 seconds per web request | API requests respond in <200ms; background tasks chunked |
| **Memory Limit** | 512 MB per PHP process | Peak memory footprint < 16 MB per request |
| **Cron Jobs** | Minimum interval: 1 minute (recommended 5–15 min) | Scheduled cron job runs every 5 minutes (`api/cron.php`) |
| **Outgoing SMTP** | Supported (Hostinger Mail or external) | Hostinger SMTP `smtp.hostinger.com:465` (SSL) via PHPMailer |
| **File Storage Hierarchy** | User home directory `/home/uXXXXXXX/` | Private files placed in `/home/uXXXXXXX/private_vault/` |
| **Public Root** | `/home/uXXXXXXX/public_html/` | Contains static frontend, `admin/` bundle, and `api/` entry point |

---

## 2. Directory Layout on Hostinger

```
/home/uXXXXXXX/
├── config/                          ◄── 100% PRIVATE (Never accessible via web)
│   ├── env.php                      ◄── Real DB, SMTP, and Session secrets
│   └── smtp_config.php              ◄── Hostinger SMTP credentials (chmod 600)
├── private_vault/                   ◄── 100% PRIVATE (Protected candidate CVs)
│   ├── resumes/                     ◄── Candidate CV uploads (UUID-named, unexecutable)
│   └── backups/                     ◄── Automated weekly database dumps
├── logs/                            ◄── Application and error audit logs
│   ├── api_errors.log
│   └── cron_runs.log
└── public_html/                     ◄── PUBLIC WEB ROOT
    ├── .htaccess                    ◄── Security headers, routing, noindex rules
    ├── index.html, jobs.html, ...   ◄── Pre-rendered static Next.js frontend
    ├── _next/                       ◄── Next.js compiled chunks & media assets
    ├── admin/                       ◄── Compiled React + Vite Admin Dashboard
    │   ├── index.html
    │   └── assets/                  ◄── Vite JS/CSS bundles
    └── api/                         ◄── PHP API Public Entry Point
        ├── v1/
        │   ├── index.php            ◄── REST front-controller routing
        │   └── .htaccess            ◄── Force JSON, disable directory browsing
        └── cron.php                 ◄── CLI or token-protected cron entry point
```

---

## 3. Apache `.htaccess` Routing Integration

The existing `public_html/.htaccess` must route traffic cleanly without conflicts:
1. Direct requests to `/admin/*` serve `admin/index.html` (Vite SPA client routing).
2. Direct requests to `/api/v1/*` route to `api/v1/index.php`.
3. Direct requests to `/api/contact.php` preserve legacy contact mailer backwards-compatibility.
4. All existing Next.js static pages (`/jobs`, `/about`, `/employers`, etc.) continue serving their pre-rendered `.html` files.
5. All `.txt` Next.js flight files are served with `X-Robots-Tag: noindex, nofollow` to protect SEO.

```apache
# ─── API and Admin Front Controllers ──────────────────────────────────────────
RewriteRule ^api/v1/(.*)$ api/v1/index.php [QSA,L]
RewriteRule ^api/cron\.php$ api/cron.php [L]

# ─── Admin SPA Routing ───────────────────────────────────────────────────────
RewriteCond %{REQUEST_URI} ^/admin
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^admin/.*$ admin/index.html [L]
```

---

## 4. Cron Task Schedule & Limits

Hostinger restricts shared hosting users from running continuous daemons or high-CPU scripts. The scheduled cron architecture uses a **bounded, single-pass batch processor**:
- **Command:** `php /home/uXXXXXXX/public_html/api/cron.php --token=YOUR_CRON_SECRET` (or CLI `php /home/uXXXXXXX/public_html/api/cron.php`)
- **Frequency:** Every 5 minutes (`*/5 * * * *`)
- **Execution Budget:** Max 25 seconds; processes up to 10 outbox emails and auto-closes expired jobs.
- **Locking:** Uses file lock (`flock`) or database mutex to guarantee zero concurrent execution overlaps.
