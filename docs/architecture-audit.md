# TalentHub Consultant — Full-Stack Architecture Audit

**Date:** October 2026  
**Auditor:** Principal Software Architect & DevSecOps Lead  
**Scope:** Repository, Deployment, Infrastructure, Frontend, Backend, and Security Architecture  
**Target Platform:** Hostinger Premium Shared Hosting (PHP 8.2+ / MySQL / Apache / Static Web Root)  
**Budget Constraint:** ₹0 additional monthly infrastructure spend  

---

## 1. Executive Summary

TalentHub Consultant operates a recruitment consultancy portal focused on the Pune industrial & corporate corridors (Hinjawadi, Kharadi, Magarpatta, Baner, Chakan MIDC). The platform currently consists of:
1. **Public Frontend:** A Next.js 15 (React 19) statically exported site deployed to Hostinger's `public_html` via GitHub Actions (`hostinger-static` branch).
2. **Contact Email API:** A standalone, hardened PHP 8.2 script (`api/contact.php`) with PHPMailer SMTP delivery and strict security controls.
3. **Reference Backend:** A comprehensive Django 4+ / DRF / Celery / PostgreSQL application in `backend/` representing the complete data model, business logic, RBAC, and workflow requirements.
4. **Scaffolded Portal:** A separate Next.js 16 app in `frontend/portal` which was intended for candidate/recruiter access but requires Node.js execution.

The objective is to replace the unhosted Django backend and Node-dependent portal with a **zero-cost, robust, production-ready architecture** matching Hostinger Premium shared hosting:
- **Public Website:** Existing Next.js static export in `public_html/` (preserved intact).
- **Admin Management Portal:** A responsive React + Vite + TypeScript Single-Page Application (SPA) compiled to static assets in `public_html/admin/`.
- **Backend API:** A clean, modular, PSR-compliant PHP 8.2+ RESTful API in `public_html/api/v1/` backed by Hostinger MySQL and PDO.
- **Private Storage:** Resumes and uploaded documents stored outside `public_html` with tokenized/authenticated PHP streaming downloads.

---

## 2. Repository Structure Analysis

```
d:\Personal\TalentHub\
├── .github/
│   └── workflows/
│       ├── hostinger-static.yml   # Production static export & git push to hostinger-static branch
│       └── deploy.yml             # Staging/CI test pipeline (Django check + Next build + Vercel/EC2)
├── backend/                       # Full Django backend (preserved as reference & future container target)
│   ├── apps/
│   │   ├── analytics/             # Recruiter dashboard metrics
│   │   ├── applications/          # Applications, interviews, resume scoring
│   │   ├── audit_logs/            # Recruiter activity audit trail
│   │   ├── cms/                   # Site settings, blogs, FAQs, testimonials, enquiries
│   │   ├── companies/             # Employer profiles
│   │   ├── core/                  # Base models, file validators, error handling
│   │   ├── jobs/                  # Job postings, categories, saved jobs
│   │   ├── notifications/         # Email templates and outbox
│   │   ├── roles/                 # RBAC permission groups
│   │   └── users/                 # Custom User model, candidate profiles
│   └── config/urls.py             # Django API v1 route mapping
├── frontend/                      # Public Next.js 15 website
│   ├── app/(public)/              # Static routes: /, /jobs, /employers, /candidates, /about, etc.
│   ├── components/layout/         # Navbar, Footer with Tawk.to, analytics
│   ├── data/                      # Static JSON fallbacks (jobs.json, blogs.json, faqs.json, etc.)
│   ├── portal/                    # Legacy experimental Next.js portal (to be superseded by Vite Admin)
│   ├── public/
│   │   ├── .htaccess              # Hostinger Apache rewrite, security headers, cache rules
│   │   ├── api/contact.php        # Secure SMTP mailer handler
│   │   ├── robots.txt             # Search crawler directives
│   │   └── sitemap.xml            # Google sitemap index
│   └── next.config.ts             # Static export configuration
└── docs/                          # Architecture, audit, testing, and operation manuals
```

---

## 3. Technology Inventory & Capability Matrix

| Component | Current Implementation | Target Production Architecture | Hosting Compatibility |
|---|---|---|---|
| **Public Frontend** | Next.js 15 Static Export (`out/`) | Next.js 15 Static Export (`out/`) | 100% compatible with Hostinger Apache static hosting |
| **Admin Dashboard** | Prototype in `frontend/portal` (Node-dependent) | React + Vite + TypeScript SPA in `public_html/admin/` | 100% static client-side bundle; zero Node.js server required |
| **Backend API** | Django REST Framework (Python 3.12) | PHP 8.2+ Lightweight Modular REST API in `public_html/api/v1/` | 100% native Hostinger PHP 8.2 FPM / FastCGI execution |
| **Database** | PostgreSQL / SQLite (Django) | Hostinger MySQL 8.0 (InnoDB, UTF8MB4, UTC) | 100% native Hostinger MySQL via PDO prepared statements |
| **Document Storage** | `backend/media/` (Local / Unprotected) | `private/uploads/` (Outside webroot) via PHP streaming | Secure; prevents direct public URL access to candidate CVs |
| **Email Delivery** | Django Celery SMTP / `contact.php` PHPMailer | Database Outbox + PHP Cron (`api/cron.php`) | 100% compatible with Hostinger Cron Jobs and Hostinger SMTP |
| **Authentication** | Django SimpleJWT | PHP Native Secure Session (HttpOnly, Secure, SameSite) | Eliminates client-side token leakage; hardened for shared hosting |

---

## 4. Security & Compliance Findings

1. **Document Exposure Risk:** Candidate CVs must never be readable via direct URL (e.g. `https://talenthubconsultant.com/uploads/resume.pdf`). All uploads must reside outside `public_html/` or be blocked by `.htaccess` deny rules, streamed only through authenticated PHP scripts.
2. **Zero Plaintext Secrets:** Hostinger credentials (`DB_PASS`, `SMTP_PASS`, `SESSION_SECRET`) must be kept in `/home/<user>/config/env.php` or `.env` above `public_html`, strictly inaccessible over HTTP.
3. **Idempotency & Duplicate Prevention:** Application submission and general resume submission must be protected by unique database constraints (`job_id + email` within 30 days) and client-side idempotency keys.
4. **Third-Party Integrations:**
   - **Tawk.to Live Chat:** Configured with property ID `69f5c2250fa8951c3a647037` and CSP rules. Must remain untouched.
   - **Google Analytics:** Configured in `frontend/app/layout.tsx`. Must remain active on the public site.
5. **SEO & Structured Data:** `JobPosting` schema, `BreadcrumbList`, dynamic canonical URLs, and `robots.txt` disallows for admin and internal text streams are operational and must be preserved.

---

## 5. Architectural Decision Records (ADRs)

### ADR-01: PHP 8.2 Native REST API for Production Backend
- **Context:** Hostinger Premium shared hosting does not support persistent background Python/Django daemons, Gunicorn, or Celery workers.
- **Decision:** Implement a modular, zero-framework, dependency-light PHP 8.2+ REST API under `public_html/api/v1/`.
- **Consequences:** Eliminates monthly VPS costs ($10–$25/mo), runs reliably under shared hosting PHP-FPM, uses native MySQL PDO with prepared statements. Django backend preserved in `backend/` for future enterprise expansion.

### ADR-02: React + Vite Single-Page Application for Admin Portal
- **Context:** The administrative dashboard requires interactive tables, filter drawers, resume previews, and status transitions without page reloads.
- **Decision:** Build the admin interface with Vite + React 19 + TypeScript + Tailwind CSS, compiling to static HTML/CSS/JS in `public_html/admin/`.
- **Consequences:** Hostinger serves the static bundle instantly with zero server CPU overhead. The client communicates with `api/v1/admin/*` over secure cookie-backed sessions.

### ADR-03: Two-Tier Private Document Vault
- **Context:** Candidate resumes contain PII (Personally Identifiable Information), phone numbers, addresses, and employment records.
- **Decision:** Store uploaded resumes in `/home/<user>/private_vault/resumes/` (or protected subfolder with `Deny from all`). Files are given random UUID filenames. Downloads require an authenticated Recruiter session via `GET /api/v1/admin/documents/{id}/stream`.
- **Consequences:** 100% defense against unauthorized scraping, direct URL guessing, or public indexing.

### ADR-04: Database-Backed Asynchronous Email Outbox
- **Context:** Sending synchronous SMTP emails during form submission slows down candidate requests and causes timeouts if Hostinger's mail server lags.
- **Decision:** Store outgoing emails in `email_outbox` table. A scheduled PHP script (`api/cron.php`) runs every 5 minutes via Hostinger Cron to deliver batched emails with retry tracking.
- **Consequences:** Near-instant candidate response times (<300ms), zero lost emails on transient SMTP failure, automated retry limits.
