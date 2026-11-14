# DHIS Sales CRM — Baseline Audit

Audit date: 2026-11-13 · Source: `https://github.com/PraveenChavan-11/TalentHub.git` @ `a8bb97b` (branch `main`)
Working branch: `feature/dhis-sales-crm` (created from `main`; `main` untouched, history preserved)

## 1. Repository state at start
| Item | Finding |
|---|---|
| Working directory | Was empty; repository cloned fresh. No uncommitted user work existed to protect. |
| Git status after clone | Clean (0 changes). |
| Remote branches | `main`, `feature/engagement-centre`, `feature/products-crm-worksphere-integration`, `feature/talenthub-final-public-redesign`, `fix/admin-media-library-view`, `hostinger-static`, `legacy-nextjs-python` |
| Product | **TalentHub Consultant** — a recruitment/IT-consulting public website with an admin portal. It is *not* a sales CRM; it has a small "CRM leads" module for IT-project enquiries. |
| Licence | Proprietary (LICENSE + WorkSphere commercial agreement in `docs/`). |

## 2. Technology stack (verified)
| Layer | Finding |
|---|---|
| Framework | CodeIgniter **4.7** (`codeigniter4/framework ^4.7`) |
| PHP | `^8.2` required; local XAMPP PHP **8.2.12** |
| Local extensions | mysqli, intl, mbstring, curl, fileinfo, openssl, zip, xml present. **`gd` is NOT loaded** locally (matters for PDF images/charts; see limitations). |
| Database | MariaDB (local XAMPP **10.4.32**); MySQLi driver; utf8mb4 / utf8mb4_unicode_ci |
| Frontend | Server-rendered PHP views. Vanilla JS/CSS. Admin uses a **pre-compiled `admin-tailwind.css` (61 KB)** — there is **no Tailwind build pipeline** (no package.json). Design tokens in `public/assets/css/tokens.css`, `theme.css`. |
| Composer deps | Only framework + dev (phpunit 10.5, faker, vfsstream). **No PDF library, no Excel library, no mailer library** beyond CI's `Email`. |
| Hosting target | Hostinger shared hosting (`/home/<acct>/public_html`, cron via hPanel, `hostinger_deploy.sh`, `.htaccess`) |
| PHPUnit | 10.5.66, hard-guarded to database `talenthub_test` (`tests/bootstrap.php`, `Config\Database::assertSafeTestDatabase`) |

## 3. Baseline test result (before any change)
`php vendor/bin/phpunit --no-coverage` → **OK (305 tests, 2,724 assertions)**, 0 failures, 0 skipped, ~27 s.
Test DB `talenthub_test` (97 tables) pre-existed on the dev machine and is the only DB the suite may touch.

## 4. Inventory of what exists
**Controllers (admin):** Agents, Applications, AuditLog, Auth, CaseStudies, Content, Dashboard, Engagement, Enquiries, Integrations, ItLeads, Jobs, Mail, MailTemplates, Products, Profile, ResumeBank, Roles, Settings, SocialMarketing, SocialOAuth, System, Users.
**Controllers (API):** Agent, Announcement, Application, Cms, CookieConsent, Email, Enquiry, JobAlert, Job, Mail, Newsletter, Notification, Report, Security, Settings, User, WorksphereEvent.
**Public web controllers:** Home, Jobs, Services, Products, CaseStudies, ProjectEnquiry, About, Solutions, Employers, Candidates, CareerEnquiry, Insights, Interviews, Contact, FAQ, Legal, Sitemap, Newsletter, JobAlert, etc.
**Models (41):** AdminUser, AuditLog, Role, Permission, LoginAttempt, PasswordReset, Notification, EmailTemplate, EmailOutbox, EmailDeliveryLog, Mailbox/Mail*, Job*, Candidate*, Employer*, Blog/Faq/Testimonial/Website*, Newsletter*, Announcement*, MediaAsset …
**Services (36):** CrmAnalytics, CrmNotification, ItLead, EnquiryAutomation, Outbox, JobRunRecorder, BackgroundJob, RateLimiter, SafeImageUpload, DocumentVault, Totp, FormSecurity, Captcha, SystemHealth, DatabaseSchema, EnvironmentSafety, HostingerMail, IntegrationSignature, …
**Filters:** Auth, Rbac, AdminApiPermission, AgentAuth, Maintenance, RateLimit, SecurityHeaders (+ framework CSRF globally, API excluded).
**Migrations:** 12 files (Oct 28 – Nov 12 2026). **Base schema (admin_users, roles, permissions, jobs, candidates …) is NOT in migrations** — it exists only in `deployment/database_hostinger_export.sql` / `clean_database_import.sql`. DB is built from SQL dumps, then migrations layered on top.
**Spark commands:** social:publish, engagement:run, outbox:process, mail:sync, sitemap:generate, logs:clear (see `deployment/crontab.txt`). HTTP cron fallback at `api/v1/cron/run`.

## 5. Authentication & authorization (existing)
- Table `admin_users` (username, email, `password_hash` bcrypt cost 12, first/last name, `is_active`, `last_login_at`, `failed_login_count`, `locked_until`).
- Optional TOTP/OTP step (`verify-otp`, `TotpService`), forgot/reset password (`password_resets`), `login_attempts`, `RateLimitFilter`.
- Session key `admin_user` holds `roles[]` and flat `permissions[]`. `RbacFilter` enforces a *single permission code* per route; `super_admin` bypasses. Roles seeded: `super_admin`, `recruiter`, `content_editor`. Permission codes/names in the dump are inconsistent (e.g. code `media.manage` named "View Job Mandates"), so the permission catalogue must be **re-seeded for CRM**, not reused.
- **Gaps vs CRM brief:** no teams, no row-level (ownership/team) visibility, no per-user approval limits, no login/logout history table, no password-change UX audit beyond profile.

## 6. Reusable infrastructure (keep)
Auth/session/OTP/reset flow, `AuthFilter`/`RbacFilter`/`RateLimitFilter`/`SecurityHeadersFilter`, CSRF config, `audit_logs` + `AuditLogModel` (needs append-only hardening), `notifications` + bell polling, `email_outbox`/`email_templates`/`OutboxService`, `scheduled_job_runs` + `JobRunRecorder`, `SafeImageUpload`, `RateLimiterService`, admin layout (`layouts/main.php`, `_sidebar_nav.php`), `tokens.css`/`admin.css`, Hostinger deploy scripts, cron command pattern, test-DB isolation guard.

## 7. Existing functionality that must keep working until explicitly retired
Per repo `CLAUDE.md`: product catalogue, CRM lead lifecycle, HMAC-signed WorkSphere webhook receiver ("must remain untouched and operational") and 141+ PHPUnit tests. **Conflict with the DHIS brief** (which asks for removal of unrelated TalentHub functionality). Resolution recorded in `CRM_REUSE_AND_REMOVAL_PLAN.md`: additive CRM build first; retire TalentHub-only modules in a later, dependency-checked, test-verified step on this branch only; the WorkSphere webhook and public site remain on `main`.

## 8. Risks found
1. Base schema lives only in SQL dumps → fresh installs need a **new CRM baseline migration** (the CRM must not depend on recruitment tables).
2. Dumps in `deployment/` contain real bcrypt hashes and real email addresses for the TalentHub deployment → **must not be copied into DHIS**; will be excluded from the CRM release package.
3. No PDF/Excel/mail libraries → Composer additions needed (dompdf, PhpSpreadsheet, PHPMailer or CI Email). `gd` absent locally → dompdf image handling and PhpSpreadsheet image features limited; text/vector PDFs unaffected.
4. Existing real hosting identifiers (Hostinger account name in `crontab.txt`) are TalentHub's, not DHIS's. **DHIS hosting target is unknown** → deployment will be a packaged, unverified-on-target deliverable.
5. "Tailwind" in the brief: no build step exists; CRM UI will extend the precompiled admin CSS plus a new `crm.css` token layer. Adopting a Tailwind build is optional and deferred.
