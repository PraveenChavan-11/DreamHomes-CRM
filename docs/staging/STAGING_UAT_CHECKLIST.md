# TalentHub Consultant — Staging End-to-End UAT Checklist

## 1. Scope & Guidelines

- **Environment:** `https://staging.talenthubconsultant.com/` exclusively.
- **Test Accounts:** `staging_admin` (`admin.staging@talenthubconsultant.com`).
- **Test Record Prefix:** `Praveen Testing - Staging` / `[STAGING TEST]`.
- **Golden Rule:** NEVER run test submissions against `https://talenthubconsultant.com/`.

---

## 2. Public Website Functional Test Suite

| Test ID | Module | Scenario | Expected Result | Status |
|---|---|---|---|---|
| **UAT-PUB-01** | Homepage | Load homepage on desktop and mobile | Hero, statistics, featured jobs, and footer load with Staging Banner visible | **PASS** |
| **UAT-PUB-02** | Staging Banner | Verify top alert bar on public pages | Amber alert bar renders: "STAGING TESTING ENVIRONMENT" | **PASS** |
| **UAT-PUB-03** | SEO Defense | Inspect `<head>` robots meta | `<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">` | **PASS** |
| **UAT-PUB-04** | Robots.txt | Visit `/robots.txt` | Returns `User-agent: *\nDisallow: /` with `X-Robots-Tag: noindex` | **PASS** |
| **UAT-PUB-05** | Sitemap 404 | Visit `/sitemap.xml` | Returns HTTP 404 "Sitemaps are disabled on the staging environment." | **PASS** |
| **UAT-PUB-06** | Canonical | Inspect canonical link in `<head>` | Staging URL is never marked as canonical | **PASS** |
| **UAT-PUB-07** | GA4 Isolation | Check Google Analytics scripts in DOM | GA4 script blocked (`$gaEnabled = false`) | **PASS** |
| **UAT-PUB-08** | PWA Isolation | Check manifest & service worker | Loads `manifest-staging.json` & registers `sw-staging.js` | **PASS** |
| **UAT-PUB-09** | Jobs Listing | Browse `/jobs` | Lists staging jobs with filter by category, location, and type | **PASS** |
| **UAT-PUB-10** | Job Detail | View `/jobs/{slug}` | Job details, salary, requirements, and application form render | **PASS** |
| **UAT-PUB-11** | Job Application| Submit application with PDF CV | Stores record in `applications`, file in staging vault, enqueues sandboxed email | **PASS** |
| **UAT-PUB-12** | Contact Form | Submit `/contact` form | Stored in `contact_enquiries`, auto-responder sandboxed | **PASS** |
| **UAT-PUB-13** | Employer Form | Submit `/employers` hiring request | Stored in `employer_enquiries`, status set to NEW | **PASS** |
| **UAT-PUB-14** | Newsletter | Submit newsletter subscribe | Creates subscriber in `newsletter_subscribers`, token generated | **PASS** |
| **UAT-PUB-15** | Announcements | Check smart modal display | Center modal popup renders on target pages per frequency rules | **PASS** |

---

## 3. Admin Operations & CRUD Test Suite

| Test ID | Module | Scenario | Expected Result | Status |
|---|---|---|---|---|
| **UAT-ADM-01** | Admin Login | Login at `/admin/login` with `staging_admin` | Authenticates against `u139147679_staging.admin_users`, establishes staging session | **PASS** |
| **UAT-ADM-02** | Staging Badge | View header indicator | Amber `STAGING ENVIRONMENT` pill is visible | **PASS** |
| **UAT-ADM-03** | Control Centre | Click Staging indicator | Diagnostics modal displays DB name, email sandbox, and record counts | **PASS** |
| **UAT-ADM-04** | Dashboard | View telemetry widgets | Visualizes staging pipeline counts (Jobs, Applications, Enquiries) | **PASS** |
| **UAT-ADM-05** | Job Management | Create new job posting | Saved into staging `jobs` table; published on staging website | **PASS** |
| **UAT-ADM-06** | Application Status| Change application to 'INTERVIEW' | Updates `applications.status` and logs status note | **PASS** |
| **UAT-ADM-07** | Resume Bank | Search & download CV | Downloads PDF CV securely from staging vault with auth check | **PASS** |
| **UAT-ADM-08** | Employer Leads | Assign recruiter & follow-up deadline | Persisted to staging `employer_enquiries` | **PASS** |
| **UAT-ADM-09** | Communication | View Hostinger Mailbox Center | Outbox lists sandboxed emails marked `[STAGING TEST EMAIL]` | **PASS** |
| **UAT-ADM-10** | Email Templates | Edit transactional email template | Updated in staging `email_templates` table | **PASS** |
| **UAT-ADM-11** | Popups/Campaigns| Create announcement banner | Configured in staging `announcements` table | **PASS** |
| **UAT-ADM-12** | CMS Pages | Edit page draft and publish | Updates staging `website_pages` table | **PASS** |
| **UAT-ADM-13** | Audit Trail | View `/admin/audit-logs` | Every staging action is recorded with user ID and timestamp | **PASS** |
| **UAT-ADM-14** | Admin Logout | Click Logout | Invalidates staging session without affecting any production session | **PASS** |

---

## 4. Production Isolation Verification

| Test ID | Verification | Expected Result | Status |
|---|---|---|---|
| **UAT-ISO-01** | Production Database | Check `u139147679_talenthub` row counts | Zero rows created or altered by staging activity | **PASS** |
| **UAT-ISO-02** | Production Domain | Visit `talenthubconsultant.com/staging` | Blocked via `.htaccess` (Returns 403 Forbidden) | **PASS** |
| **UAT-ISO-03** | Real Email Delivery | Monitor production SMTP logs | Zero real emails sent to external recipients | **PASS** |
| **UAT-ISO-04** | Search Console | Inspect robots and headers | Search engines strictly blocked from indexing staging | **PASS** |

