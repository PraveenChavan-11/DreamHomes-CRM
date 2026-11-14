# DHIS Sales CRM — Reuse and Removal Plan

Principle: **add first, remove last, verify between.** Nothing is deleted in Phase 1. Removal happens only on branch `feature/dhis-sales-crm`; `main` keeps the TalentHub product intact.

## A. Reuse (keep and adapt)
| Asset | Use in CRM | Change needed |
|---|---|---|
| `AuthController`, OTP, reset, `login_attempts`, `RateLimitFilter` | CRM login/logout/reset | Add login/logout history rows, session timeout, CRM redirect, DHIS branding |
| `AuthFilter`, `RbacFilter` | Route protection | Extend with record-scope check (`CrmPermissionFilter`) |
| `admin_users` | Base for CRM users | Add columns (team, phone, employee code, approval limits) via CRM migration; do not alter bcrypt scheme |
| `roles`/`permissions`/`role_permissions`/`user_roles` | RBAC store | **Re-seed** CRM roles/permissions (existing catalogue labels are inconsistent) |
| `audit_logs` | Baseline audit | CRM adds append-only `crm_audit_events` + `crm_workflow_events`; legacy table untouched |
| `notifications`, bell polling | In-app notifications | Add dedupe key + CRM event types |
| `email_outbox`, `email_templates`, `OutboxService`, `HostingerMailService` | Email engine | Wrap in CRM `EmailService`; add CRM log table with honest statuses |
| `scheduled_job_runs`, `JobRunRecorder` | Cron execution logs | Reuse as-is |
| `SafeImageUpload`, `DocumentVaultService` | Uploads | Extend to documents (pdf/xlsx/jpg/png) with MIME + size validation |
| `admin/layouts/main.php`, `_sidebar_nav.php`, `admin.css`, `tokens.css` | Shell | New CRM layout + nav; reuse structure |
| Test DB guard, PHPUnit setup | Testing | Re-point constant to `dhis_crm_test` |
| `deployment/hostinger_deploy.sh`, `.htaccess`, `crontab.txt` pattern | Deployment | Rewrite with DHIS placeholders |

## B. Retire (TalentHub-only) — staged, dependency-checked
Order and gating: each item is removed only after `grep` of routes/views/models/services/tests/migrations shows no CRM dependency, then PHPUnit is re-run.

| Stage | Modules | Dependencies to check | Notes |
|---|---|---|---|
| R1 | Public website (`Web/*`, `Views/public`, sitemap, llms/robots, manifest/sw, SEO) | `Home.php`, `Routes.php` section 1, `SecurityHeaders` | Replace `/` with redirect to `/crm/login`. Keep `public/assets` shared CSS. |
| R2 | Recruitment: jobs, applications, resume bank, candidates, employers, job alerts, interviews | `Admin/Jobs|Applications|ResumeBank`, models, emails, nav | |
| R3 | Content/marketing: CMS pages, blog, FAQ, testimonials, social marketing, newsletter, engagement centre, case studies, cookie consent | `Admin/Content|Social*|Engagement|CaseStudies`, `Api/Newsletter|Cms|CookieConsent` | |
| R4 | AI agent + MCP endpoints (`Agent*`, `.well-known/mcp`, `llms*.txt`) | `AgentAuthFilter` | |
| R5 | Product catalogue + WorkSphere webhook + legacy "IT leads" CRM | Repo CLAUDE.md marks these "must remain operational" | **Held until DHIS owner confirms** they may be dropped on this branch; superseded functionally by CRM enquiry/customer/product modules. |
| R6 | Brand/docs/binaries: `logo.*`, `Talent-Hub-*.pdf`, `TalentHub_*.docx/xlsx`, `hero_spec_full.png`, `scratch/`, `deployment/*.sql` dumps | **Dumps contain real TalentHub hashes/emails** | Replace with DHIS assets; remove dumps from CRM package. `logo.*` need a real DHIS logo (not supplied — placeholder wordmark until provided). |

## C. Explicitly NOT deleted
`app/Config`, framework, `spark`, `index.php`, `.htaccess`, `writable/`, shared filters/services listed in §A, git history.

## D. Verification gates per removal stage
1. `php -l` on remaining PHP; route table (`php spark routes`) has no orphan controller refs.
2. `phpunit` — tests belonging to retired modules are removed *with* them and the removal is listed in `CHANGELOG.md`; all remaining tests must pass.
3. Grep for dead references (views, nav, emails, cron).
4. Commit per stage so any stage can be reverted.
