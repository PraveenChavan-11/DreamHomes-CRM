# CRM Architecture

DHIS Sales CRM is a **modular monolith** inside the existing CodeIgniter 4 / PHP 8.2 / MariaDB application. It is self-contained under `app/Crm` (PSR-4 namespace `Crm`) and shares only the framework, the `admin_users` / `roles` / `permissions` identity tables and the Hostinger-style deployment model with the surrounding TalentHub codebase. No microservices, no queue worker, no external infrastructure.

## Layering

```
Browser ──► Route (app/Crm/Routes.php, group "crm")
              │  filters: crmauth (session, idle timeout, live active-user + permission refresh)
              │           crmperm:<perm,…> (any-of permission gate)  · ratelimit on public auth routes
              ▼
        Controller (app/Crm/Controllers/*)      thin: map HTTP → service call → view / redirect
              │  $this->act(fn, successMsg, redirect) turns CrmException / validation errors into flash + old input
              ▼
        Service (app/Crm/Services/*)            ALL business rules, permission re-checks, transactions, audit
              │  Crm::tx() = DB transaction; Crm::db() = shared connection; Crm::now() = injectable clock
              ▼
        MariaDB (crm_* tables + admin_users/roles/permissions)         View (app/Crm/Views/*) — escaped output only
```

| Concern | Where |
|---|---|
| Authentication, lock-out, password policy, reset tokens | `AuthService`, `AuthController` |
| Row-level visibility (all / team / own) and permission test | `AccessScope` — **every** record read goes through it (IDOR defence) |
| Customers, duplicates | `CustomerService` |
| Enquiry lifecycle, scoring, auto-assignment (round-robin / workload / team / territory) | `EnquiryService`, `AssignmentService`, `WorkflowService` (configurable transition matrix) |
| Follow-ups, activities, visits, measurements, reminders & escalation | `ActivityService`, `RequirementService` |
| Products & price history | `ProductService` |
| Quotation maths (bcmath, half-up rounding) | `QuotationCalculator`, `Money`, re-run server-side on every save by `QuotationService::price()` |
| Quotation versions, locking, send, compare, change log | `QuotationService` |
| Negotiation & discount approval chain | `NegotiationService` |
| Sales orders, won / lost / hold / reopen | `OrderService` |
| E-mail queue, dispatch, retry, honest statuses | `EmailService` |
| PDF (Dompdf) | `PdfService`, `Views/pdf/quotation.php` |
| In-app notifications (de-duplicated, per-user prefs) | `NotificationService` |
| Customer timeline, global sales history, ownership history | `HistoryService` |
| Dashboards / reports / XLSX+PDF export | `DashboardService`, `ReportService` |
| Excel import (upload → map → validate → run, idempotent by file hash) | `ImportService` |
| Audit + workflow events (append-only) | `AuditService` (`audit()` system events, `workflow()` business events) |
| Business numbers (CUS-/ENQ-/QT-/NEG-/SO-/SV-0001…) | `NumberSequenceService` (row-locked, concurrency-safe) |
| Configuration | `Settings` (`crm_settings`; defaults flagged `is_default_assumption`), `RuleAdminService` |
| Demo data | `DemoSeeder`, `DemoService` (snapshot → purge demo-owned rows only → reseed → integrity check) |
| Integrity audit | `IntegrityChecker` (`php spark crm:verify`, *System Health* page) |
| Scheduled work | `php spark crm:reminders` (follow-up reminders/overdue/escalation, visit reminders, e-mail retry) via `JobRunner` → `scheduled_job_runs` |

## Cross-cutting rules

* **Server is the source of truth.** Money is never calculated from browser values; client-side JS is progressive enhancement only.
* **Authorization twice:** route filter (`crmperm`) and a service-level check. Controllers add ownership checks via `AccessScope`.
* **Transactions** wrap every multi-table operation (`Crm::tx`). Number sequences and quotation versions use `SELECT … FOR UPDATE`.
* **Immutability:** a quotation version that has been sent is locked (`is_locked`, PDF SHA-256 stored); revisions create a new version and an item-level change log (`crm_quotation_changes`).
* **Append-only history:** `crm_workflow_events` (business timeline) and `crm_audit_events` (system) are never updated or deleted by the application; `AuditService::scrub()` strips passwords, tokens and secrets before storage. Every event stores correlation ID, source (`web` / `cli` / `cron`) and previous/new state.
* **No hard delete of commercial data:** customers with transactions are deactivated/archived, not deleted; users are deactivated so history keeps attribution.
* **E-mail honesty:** `log_only` is the default mode; demo-owned rows are never transmitted in any mode; `delivered` / `bounced` can only be set by provider confirmation of an SMTP-accepted (`sent`) message.

## Frontend

Server-rendered PHP views inside `Views/layout/main.php`, one stylesheet (`public/assets/crm/css/crm.css`, navy/white/teal tokens) and one small script (`public/assets/crm/js/crm.js`: modals, confirmation, double-submit protection, form validation messages, SVG charts, Kanban drag-and-drop, line-item editor, notification polling). No CDN, no external fonts. Pages remain usable without JavaScript except modal dialogs and drag-and-drop (every action also has a normal form or button).

## Data model

See [CRM_DATABASE_DESIGN.md](CRM_DATABASE_DESIGN.md). Three migrations in `app/Crm/Database/Migrations` (idempotent: `IF NOT EXISTS` / column-exists checks; `down()` drops only the tables it created).
