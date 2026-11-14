# TalentHub — Baseline Audit (Phase 1)

Date: 2026-11-08 · Branch: `main` @ `dbd0fb8` · Tests at audit start: 172 passing / 1293 assertions.

Scope note: this audit is from code inspection and the PHPUnit suite. It has **not** included a live
browser pass, Lighthouse run, or any call to LinkedIn/Meta/Google APIs. Anything marked "unverified" is exactly that.

## What already exists (do not rebuild)
| Area | Evidence |
|---|---|
| Public site, CMS pages, insights, case studies, products, WorkSphere showcase | `app/Controllers/Web/*`, migrations `20261105…`–`20261109…` |
| Recruitment: jobs, applications, resume bank | `Admin/JobsController`, `ApplicationsController`, `ResumeBankController` |
| CRM / IT leads + HMAC WorkSphere receiver + integration clients | `ItLeadsController`, `WorksphereEventController`, `IntegrationClientService`, `IntegrationSignatureService` |
| Social engine: LinkedIn/Instagram services, DB queue, scheduler, analytics, `spark social:publish` | `app/Services/Social/*`, migration `20261110…` |
| Email centre: outbox, templates, mailbox sync, campaigns, newsletters | `Mail*`, `OutboxService`, `EmailCampaignService` |
| RBAC (roles/permissions + `RbacFilter`), audit log, login throttling, TOTP | `RolesController`, `AuditLogController`, `LoginAttemptModel`, `TotpService` |
| PWA: manifests, `sw.js`, `offline.html`, install handler | repo root + `public/` |
| Staging safety, schema ensure, system-check commands | `EnvironmentSafetyService`, `spark system:check` |

## Gaps against the brief (confirmed absent in code)
| # | Gap | Class |
|---|---|---|
| G1 | **No external AI agent API** (`/api/v1/integrations/agents/*`), no agent registry, scoped tokens, approval/publishing-policy tables, or MCP adapter. Only `integrations/worksphere/events` exists. | Incomplete integration |
| G2 | **No project-management module** (projects, tasks, milestones, deliverables). | Missing module |
| G3 | **No SEO management centre** (per-page metadata exists via `WebsitePageModel` + sitemap service; no audit, redirects, robots editor, Search Console/GA integration). | Missing module |
| G4 | **No System Health page** or background-job/cron monitor in admin (only CLI `system:check`). | Missing module |
| G5 | **No global admin search**, quick-create, or API-health indicator in the header. | UX |
| G6 | No hiring analytics / interview management / pipeline views beyond application status. Unverified depth. | Incomplete |
| G7 | Executive dashboard KPI coverage vs brief (projects, SEO, system) not met; those sources do not exist yet. | Incomplete |

## Findings by class
**Security**
- S1 (medium): TinyMCE Cloud API key is hard-coded in `layouts/main.php` (a public-key-style identifier, but it should be configuration, and the editor is loaded from a third-party CDN with no SRI).
- S2 (medium): Admin loads Tailwind via the **runtime CDN script** (`cdn.tailwindcss.com`). It requires `unsafe-eval`-style behaviour, blocks a strict CSP, and is an unpinned third-party dependency on every admin page.
- S3 (info): Chart.js is pinned (4.4.1) but without SRI.
- Not yet reviewed in depth: upload validation, SSRF in mailbox/integration fetchers, rate-limit coverage on `/api/*`. Scheduled for Phase 10.

**Performance** — S2 above is also the largest admin perf cost (JIT compile in-browser). Public-site Lighthouse numbers: **not measured**.

**UX** — Admin sidebar was one flat ~30-link list (fixed in this pass, see below). Header breadcrumb is just the `activeMenu` string.

**Accessibility** — sidebar had no ARIA state or keyboard model (fixed). Rest of admin: unverified.

**Deployment** — staging/production separation exists (`env.staging`, `manifest-staging.json`, `sw-staging.js`, `StagingProvisionDb`). Cron/scheduler verification on Hostinger is unverified from here.

## Changes made in this pass
- **Phase 4 (partial) — Admin sidebar rebuilt** as data-driven partial `app/Views/admin/layouts/_sidebar_nav.php`:
  - 7 collapsible groups (Recruitment, Consulting/IT & Leads, Products, Social Media, Communication, Content, System) + Dashboard; accordion — only the group containing the current page opens by default.
  - Active item/group highlight (longest-prefix route match, so sub-pages highlight their parent).
  - Searchable (`/` focuses it), arrow-key navigation, `aria-expanded`/`aria-current`.
  - Desktop icon-only collapsed mode with tooltips (`title`), preference remembered in `localStorage`; open-group state remembered too.
  - Independent scrolling nav, with brand header and user footer fixed. Mobile drawer unchanged.
  - Respects `prefers-reduced-motion`; with JavaScript disabled all groups render expanded (`<noscript>` fallback); links are plain `<a>`.
  - Permission visibility logic preserved verbatim from the previous sidebar; server-side `RbacFilter` untouched.
  - No new destinations added; only existing routes are listed.
- Tests: `tests/unit/AdminSidebarNavTest.php` (+2). Suite: **174 tests / 1309 assertions passing**.

## Recommended order for the remaining phases (each is a multi-day item)
1. Header: global search, quick-create, breadcrumbs (G5).
2. System Health page + job/cron status (G4) — small, high operational value.
3. External agent API foundation (G1): tables (`agents`, `agent_tokens` hashed, `agent_audit`), `AgentAuthFilter` with scopes + rate limit, drafts-only endpoints first, approval required by default.
4. Replace CDN Tailwind with a built CSS file (S2); move TinyMCE key to env (S1).
5. Projects module (G2), SEO centre (G3).
6. Measured Lighthouse/security passes, then docs package and UAT.
