# TalentHub — Existing System Audit (Phase 0)

Date: 2026-11-05 · Branch: `main` @ ea7ae61 · Method: static inspection of the repository (routes, controllers, services, schema, config). **Nothing was executed against a running server or database in this pass.** Items marked *(unverified)* need runtime confirmation.

## 1. Stack (verified from repo)
- PHP + CodeIgniter 4 (`app/`, `spark`, `vendor/`), MySQL, server-rendered PHP views + Tailwind, PWA (`sw.js`, `sw-staging.js`, `manifest*.json`).
- Hostinger hosting; CI in `.github/workflows` (ci, staging-deploy, production-release). Staging isolation already exists (`env.staging`, `Staging*` spark commands, `EnvironmentSafetyService`, `robots-staging.txt`, `docs/staging/*`).
- **Stale docs warning:** `docs/audit/INITIAL_AUDIT.md` and several `docs/frontend/*` files describe a Next.js + Django architecture that is *not* this codebase. Treat them as historical, not as a source of truth.

## 2. Inventory
| Area | Count / items |
|---|---|
| Controllers | 16 Web, 16 Admin, 15 Api (jobs, applications, enquiries, CMS, email, mail, newsletter, announcements, job alerts, cookies, notifications, reports, security, settings, users) |
| Models | 42 (see DATABASE_ARCHITECTURE) |
| Services | 22 (outbox, mailbox, campaigns, newsletter, sitemap, captcha, TOTP, rate limiter, document vault, env safety, enquiry automation…) |
| Filters | auth, rbac, ratelimit, security headers, maintenance |
| CI4 migrations | 2 (`email_management`, `engagement_centre`); the rest of the schema lives in `DatabaseSchemaService::ensureSchema` and raw SQL in `deployment/` |
| Spark commands | 14 |
| Tests | 9 PHPUnit files in `tests/unit` (not executed in this pass) |
| Routes | ~281 route definitions |

## 3. Public pages that exist
`/`, `/about`, `/solutions`, `/employers`, `/candidates`, `/jobs`, `/jobs/{slug}`, `/career-enquiry`, `/insights`, `/insights/{slug}`, `/interviews`, `/contact`, `/faq`, `/privacy`, `/terms`, thank-you/failed, application tracker, DPDP CV-deletion request/confirm, newsletter verify/unsubscribe/preferences, job-alert unsubscribe, dynamic `robots.txt`, `llms.txt`, 5 sitemaps.

**Missing vs. the transformation brief:** service catalogue and per-service pages, industry pages, case studies, IT/QA/ERP/digital-marketing verticals, mega menu, cookie-policy and disclaimer pages, IT project-enquiry flow, lead CRM, redirect manager, SEO admin beyond `website_pages`, the 9 requested roles (only generic RBAC with Super Admin / HR Manager seeds).

## 4. Admin functionality that exists (must be preserved)
Dashboard + notifications; Jobs CRUD/toggle; Applications (status, bulk action, vault resume download); Resume bank; Employer and contact enquiries (status); Mail client (mailboxes, compose, templates, analytics); Engagement centre (announcements, quick notice, subscribers + CSV, job alerts, campaigns + logs + test send, leads, analytics, cookie logs); Users; Roles/permissions; Content (articles, pages with draft/publish/rollback API, FAQs, testimonials, media); Settings (SMTP test, OTP); Audit logs; Profile; TOTP/OTP login.

## 5. Findings affecting the plan
1. **Positioning is Pune-only in code.** `DatabaseSchemaService` seeds Pune/Hinjawadi/Katraj meta for every page; `public/layouts/main.php` hardcodes a Pune tagline, keywords, legalName "… Pune" and Pune addressLocality in Organization schema. Seeded `website_pages` rows already in production will not change by themselves, so a reversible data migration is needed.
2. **Schema management is split** between CI4 migrations, a runtime `ensureSchema()` and loose SQL files. New work should use CI4 migrations with `down()`.
3. The homepage is one 826-line view and the layout is monolithic; componentise before adding a mega menu and new sections.
4. Service worker caches the shell and skips non-GET; exclusion of admin/API GETs must be confirmed *(unverified)*. Cache name `talenthub-cache-v3` is bumped manually.
5. Staging isolation is substantial; reuse it rather than rebuild.

Sibling documents: DATABASE_ARCHITECTURE, API_INVENTORY, SECURITY_AUDIT, IMPLEMENTATION_ROADMAP.
