# TalentHub — Product Platform Audit (pre-change baseline)

Audited 2026-11-08, branch `main`, HEAD `2dd6646` (one uncommitted change: `public/assets/css/polish.css`, pre-existing, not touched by this work).
Method: read-only inspection of routes, controllers, services, migrations, filters, views, deployment scripts and tests. Nothing here was assumed from file names; where something was not verified it is marked **not verified**.

## 1. Stack and deployment

* CodeIgniter 4.7 / PHP ^8.2 (local PHP 8.2.12), MySQL/MariaDB (local XAMPP MariaDB 10.4), Composer, no JS build step (server-rendered PHP views + static assets in `public/assets`).
* Hosting: Hostinger shared hosting (`deployment/hostinger_deploy.sh`: rsync of `app/` and `vendor/` to `~/talenthub_core`, public assets to `public_html`). **No shell migration step is assumed** — existing code applies schema changes lazily through idempotent `apply()` methods (`ItLeadService::__construct`, `ServicesController::safe`). New schema work must follow that pattern.
* Environments: production (`env`), staging (`env.staging`, `sw-staging.js`, `robots-staging.txt`, `EnvironmentSafetyService` sandboxes e-mail with a `[STAGING TEST EMAIL]` prefix).
* PWA assets (`manifest*.json`, `sw.js`) present for the public site and admin.

## 2. What exists

| Area | Evidence | State |
|---|---|---|
| Public pages | `app/Controllers/Web/*` (Home, About, Services, Solutions, Employers, Candidates, Jobs, Insights, Interviews, Contact, FAQ, Legal, ProjectEnquiry, CareerEnquiry…) | Working |
| Services | Exactly two primary services (Recruitment, IT Services); capability pages 301-redirect | Working |
| **Products** | `/products` and `/products/(:segment)` → `ServicesController::legacyProducts` | **Legacy redirect only — no product catalogue exists** |
| IT lead CRM | `it_leads`, `it_lead_activities`, `ItLeadService`, `Admin\ItLeadsController`, views `admin/it_leads/*` | Working: statuses (8), assignment, notes, follow-up, soft delete, dedupe (email/phone, 60 days), idempotency key, UTM capture. **Missing:** source system, product, priority, value, tags, spam, lost reason, timeline of integration events, export, configurable categories |
| Other enquiries | `contact_enquiries`, `employer_enquiries`, resume submissions (separate tables, separate admin screens) | Working; not unified with CRM |
| Admin | Auth + OTP + login attempts, RBAC (`roles`, `permissions`, `role_permissions`), `AuthFilter`, `RbacFilter`, audit log, users, roles, settings, content (articles/pages/testimonials/FAQ), mail client, engagement centre (announcements, newsletter, campaigns, cookies) | Working |
| E-mail | `OutboxService` (queue + templates + idempotency keys), `EmailTemplateModel`, delivery log, `HostingerMailService`, SMTP via settings/env | Working; admin notification address currently `contact_email` setting |
| Security | CSRF filter (API excluded), `RateLimitFilter` + `RateLimiterService`, `FormSecurityService` (honeypot/captcha/timing), `SecurityHeadersFilter`, `MaintenanceFilter` | Working |
| Uploads | `DocumentVaultService` (resumes, private vault in `writable/vault`), media asset model | Existing handlers are for resumes; **no public image-upload handler for catalogue media** |
| SEO | `SitemapService` + 5 sitemaps, `robots.txt`, `llms.txt`, page meta table | Working; no product entries |
| Tests | `tests/unit/*` (ItLeadsCrm, Rbac, FormSecurity, Hardening, Health, PublicWebRoutes, …), PHPUnit via `vendor/bin/phpunit` | A full-suite baseline run was started but produced no readable result in the audit session (**baseline not established by the orchestrator; each workstream re-establishes it for the files it touches**) |

## 3. Gaps against the target

1. No dynamic multi-product catalogue; no admin product management; no reusable detail template; no flagship WorkSphere page.
2. CRM covers IT enquiries only; no unified source/product model, no integration events, no priority/value/tags/export.
3. No inbound machine-to-machine API with signing, replay protection or idempotency; the `api/v1` group relies on public CSRF exemption + rate limit only.
4. Admin dashboard has operational counts but no lead funnel, source/product analytics, integration health or e-mail failure view.
5. Admin notification recipient is not a dedicated, validated CRM setting; no per-event templates for CRM events.

## 4. Risks noted

* Lazy runtime migrations run on first request — acceptable here, but destructive DDL is forbidden; all new DDL is additive.
* `encryption.key` availability on Hostinger **not verified**; integration secrets need it (feature fails closed without it).
* Existing `it_leads.status` is an ENUM; it may only grow (MySQL ENUM shrinking would truncate data).
* Deployment script copies `app/` + `vendor/` but does not run migrations; documented in the new docs.

## 5. Decisions

Extend the existing IT-lead tables rather than creating a parallel CRM (preserves data and screens); add a catalogue and an integration receiver as new additive modules; keep WorkSphere as identity/provisioning authority.
