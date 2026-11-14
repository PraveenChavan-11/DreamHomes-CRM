# DHIS Sales CRM (module in this repository)

A full sales CRM for **Dream Homes Interior Solutions (DHIS Group)** lives in `app/Crm` and is served under **`/crm`** — customers, enquiries & auto-assignment, follow-ups, site visits & measurements, product/price master, versioned quotations (manual variant / size / price editing with a full revision history), negotiation & discount approval, e-mail log, sales orders, won/lost, dashboards, reports, audit trail and demo-data control. It runs on the same CodeIgniter 4 / PHP 8.2 / MariaDB stack; the TalentHub website code below is untouched.

* **Demo logins, walkthrough:** [`DEMO_ACCESS.md`](DEMO_ACCESS.md) (fictional data; demo server only)
* **Run locally:** `cp .env.example .env` (fill DB), `php spark migrate -n Crm`, `php spark crm:seed-reference`, optionally `php spark crm:demo-seed` (needs `DHIS_DEMO_ENV=true` + setting `demo.enabled=1`), `php spark serve --port 8080`, open `/crm/login`
* **Scheduler:** `php spark crm:reminders` every 10 minutes via cron · **Integrity check:** `php spark crm:verify`
* **Tests:** `php vendor/bin/phpunit -c phpunit.crm.xml --no-coverage` (uses the isolated `dhis_crm_test` database)
* **Docs:** `docs/CRM_ARCHITECTURE.md`, `CRM_WORKFLOW_SPECIFICATION.md`, `CRM_ROLE_PERMISSION_MATRIX.md`, `CRM_API_DOCUMENTATION.md`, `CRM_DEPLOYMENT_GUIDE.md`, `CRM_BACKUP_RECOVERY.md`, `CRM_SECURITY_CHECKLIST.md`, `CRM_UAT_CHECKLIST.md`, `CRM_DEMO_SCENARIOS.md`, `CRM_KNOWN_LIMITATIONS.md` · [`CHANGELOG.md`](CHANGELOG.md)

---

# TalentHub — Enterprise Recruitment Platform & Administration Center

**Production Domain:** [talenthubconsultant.com](https://talenthubconsultant.com)  
**Primary Architecture:** CodeIgniter 4 + PHP 8.2+ + MySQL + Tailwind CSS  
**Target Hosting:** Hostinger Standard Shared Hosting (Apache mod_rewrite, PHP 8.2)  
**Communication Center:** Official Hostinger Mail API (`https://api.mail.hostinger.com/api/v1`)  

---

## 1. System Architecture

TalentHub is an enterprise-grade recruitment consultancy and administration platform designed specifically for non-technical consultancy owners. 100% of website operations—job openings, candidate applications, private resume vaults, client company mandates, content pages, SEO sitemaps, and consultancy mailboxes—are managed directly through the secure administrative portal without editing code, accessing Git, running terminal commands, or modifying databases.

```
talenthub/
│
├── app/                                # CodeIgniter 4 MVC Application Core
│   ├── Commands/                       # CLI commands (outbox:process, mail:sync, sitemap:generate)
│   ├── Config/                         # Application, Database, HostingerMail & Security Config
│   ├── Controllers/
│   │   ├── Web/                        # Server-rendered public pages (Home, Jobs, Employers, etc.)
│   │   ├── Admin/                      # Administrative auth & dashboard controllers
│   │   └── Api/                        # RESTful JSON APIs (/api/v1/...)
│   ├── Database/                       # Migrations & database seeds
│   ├── Filters/                        # Auth, RBAC, RateLimit, Maintenance, SecurityHeaders
│   ├── Models/                         # Active-Record MySQL models
│   ├── Services/                       # HostingerMail, DocumentVault, Outbox, Sitemap, Telemetry
│   └── Views/                          # Server-rendered PHP public views, admin shells, emails
│
├── public/                             # Public Web Root (Mapped to public_html on Hostinger)
│   ├── index.php                       # Front controller
│   ├── .htaccess                       # Apache rewrites, HTTPS enforcement & Gzip compression
│   ├── assets/                         # Compiled CSS & JS (app.css, admin.css, admin.js)
│   ├── uploads/                        # Public CMS media uploads
│   └── favicon.ico, logo.png
│
├── admin/                              # Admin Portal Source (React 19 + TypeScript + Vite)
│   ├── src/                            # Admin pages, components, and Hostinger Mail client
│   └── package.json                    # Compiles into public/assets/js/admin.js
│
├── deployment/                         # Hostinger Shared Hosting Deployment Package
│   ├── DEPLOYMENT_GUIDE.md             # Non-technical step-by-step deployment runbook
│   ├── hostinger_deploy.sh             # One-click SSH automated deployment script
│   ├── hostinger_public_html_index.php # Production front controller
│   ├── hostinger.htaccess              # Production Apache rules
│   ├── crontab.txt                     # Hostinger cron job schedules
│   └── database_hostinger_export.sql   # Complete clean MySQL export (all 43 tables + seeds)
│
├── docs/                               # Architectural & Client Handover Documentation
├── tests/                              # Unit & integration tests
├── writable/                           # Private Storage (Outside web root)
│   ├── vault/                          # Private candidate resume documents (Strict chmod 700)
│   ├── logs/                           # Application & security audit logs
│   └── cache/                          # Response & page caches
├── spark                               # CodeIgniter 4 CLI tool
└── .env                                # Local / Server environment secrets (Gitignored)
```

---

## 2. Core Modules & Features

### 2.1 Public Server-Rendered Recruitment Portal
* **Brand Identity & Layout:** 100% visual fidelity matching TalentHub branding (`#1D6A34` Forest Green, `#37CE97` Emerald, `#172B22` Deep Dark).
* **Faceted Job Search & Openings:** Real-time job filtering by sector, city, experience, and employment type (`/jobs`, `/jobs/{slug}`).
* **Direct Job Applications:** Multipart application submission with real-time CV upload to private vault (`/jobs/{slug}/apply`).
* **General Resume Drop Bank:** Speculative CV deposit for active talent pool matching (`/career-enquiry`).
* **Employer Hiring Desk:** Corporate mandate submission form with automatic recruiter notification (`/employers`).
* **Editorial Insights & CMS:** Consultancy blog articles, FAQs, and reviews (`/insights`, `/faq`).
* **Dynamic XML Sitemaps:** Automated search engine indexing (`/sitemap.xml`, `/sitemap-jobs.xml`, `/sitemap-insights.xml`, `/sitemap-pages.xml`).

### 2.2 Enterprise Admin Dashboard (`/admin`)
* **Executive Telemetry & KPI Cards:** Live statistics on applications, resume vault count, active jobs, employer mandates, and conversion rates.
* **Job Mandates Manager:** Complete lifecycle controls (Draft, Published, Closed), slug management, requirements editor, and duplicate job action.
* **Application & Candidate Vault:** Candidate review, status pipelines (New, Shortlisted, Interview Scheduled, Rejected, Hired), and secure resume streaming.
* **Website Control & Maintenance Mode:** Dynamic maintenance mode returning HTTP 503 to visitors while preserving admin access.
* **Branded Email Templates:** 10 recruitment scenario templates with dynamic variables (`{{candidate_name}}`, `{{job_title}}`).
* **Security & Audit Trail:** Immutable logging of admin logins, session tracking, rate limiting, and password reset workflows.

### 2.3 Email Management & Communication Center
* **Official Hostinger Mail API Integration:** Connects directly to `https://api.mail.hostinger.com/api/v1` using Bearer tokens.
* **3-Pane Email Client:** Responsive interface with multi-mailbox switcher, storage quota meters, folder navigation (`Inbox`, `Sent`, `Drafts`, `Trash`), and search.
* **Recruitment Linking:** Associate email conversations with candidate applications, jobs, and employer inquiries.
* **Rich-Text Composer:** Rich formatting, attachments, reply threading (`In-Reply-To`), and template auto-population.
* **Inbound Webhooks:** Real-time incoming email notifications with deduplication (`POST /api/v1/webhooks/hostinger-mail`).
* **Dual Token Configuration:** Add your token in the Admin UI under **Communication Center** → **Settings & Token** or via `.env`.

---

## 3. Hostinger Production Deployment

Follow the complete runbook in [`deployment/DEPLOYMENT_GUIDE.md`](deployment/DEPLOYMENT_GUIDE.md).

### Cron Schedule (`deployment/crontab.txt`)
```text
* * * * * /usr/bin/php /home/uXXXXX/talenthub_core/spark outbox:process >/dev/null 2>&1
*/5 * * * * /usr/bin/php /home/uXXXXX/talenthub_core/spark mail:sync >/dev/null 2>&1
0 3 * * * /usr/bin/php /home/uXXXXX/talenthub_core/spark sitemap:generate >/dev/null 2>&1
```

---

## 4. Default Credentials

- **Admin Portal URL:** `https://talenthubconsultant.com/admin/login`
- **Primary Admin:** `admin` / `TalentHub@Admin2026!`
- **HR Desk Admin:** `hr` / `HR@TalentHub2026`

*Change initial passwords immediately inside Admin → My Profile after deployment.*

---

## 5. Legacy Archives

The historical Next.js and Python codebases are permanently archived on the dedicated Git branch:
* **Branch:** [`legacy-nextjs-python`](https://github.com/PraveenChavan-11/TalentHub/tree/legacy-nextjs-python)
