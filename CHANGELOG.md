# Changelog — DHIS Sales CRM

Format: [Keep a Changelog](https://keepachangelog.com). Dates are the repository commit/working dates.

## [Unreleased] — 2026-11-14

### Added
- **Web layer for the CRM** under `/crm` (routes in `app/Crm/Routes.php`, wired from `app/Config/Routes.php`; filters `crmauth` / `crmperm` registered in `app/Config/Filters.php`).
- **Products & Pricing** screens: searchable catalogue, product detail with price ladder and price-change history, create/edit with server-side minimum-price protection and strict image-upload validation (JPG/PNG/WebP, ≤ 2 MB, MIME + image-content check).
- **Email Log**: filters, honest status display (`queued` / `sent` / `delivered` / `failed` / `bounced`), full delivery record, sandboxed body preview, manual e-mail, safe retry (failed rows only).
- **Users & Teams**: users, teams, read-only role/permission matrix, sign-in history; create user, activate/deactivate, admin password reset, create team, add member. Guards: no self-deactivation, only a Super Admin may create or change a Super Admin.
- **Audit Trail** viewer (system audit events and business workflow events) with entity / event / user / date filters and a payload viewer.
- **Settings**: company details, quotation defaults, discount & approval limits, security, e-mail mode (SMTP credentials remain `.env`-only and are never displayed), assignment rules, lead-scoring rules, personal notification preferences. Every change is audited. Unconfirmed values are flagged as *assumed default*.
- **Demo Data** control page (environment + setting status, registry counts, reset log, type-to-confirm reset) and **System Health** page (integrity checks, job runs, PHP/DB versions, folder permissions, cron hints).
- `Crm\Services\RuleAdminService` — audited admin edits of assignment rules, scoring rules and notification preferences.
- Documentation set (`docs/CRM_*.md`), `DEMO_ACCESS.md`, `.env.example`.

### Changed
- `App\Filters\MaintenanceFilter` now exempts `/crm` URIs. The TalentHub maintenance flag lives in a `website_settings` table that does not exist in the CRM database, which made every CRM request fail with HTTP 500.

### Security
- All admin mutations are POST + CSRF + route permission filter + service-level permission check; demo reset needs `demo.reset`, the demo-server flag, `demo.enabled` and the typed confirmation phrase.

## [0.1.0] — 2026-11-14 (baseline commit `a74b640`)
- CRM schema (two migrations), services, seeders, CLI commands (`crm:seed-reference`, `crm:demo-seed`, `crm:demo-reset`, `crm:reminders`, `crm:verify`, `crm:create-admin`), quotation PDF template and 60 service-level tests.
