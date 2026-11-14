# DHIS Sales CRM — Repository Architecture (current → target)

## Current (inherited)
```
app/Controllers/{Admin,Api,Web}   thin-ish controllers, much logic inline
app/Models                        CI4 Model classes (41)
app/Services                      mixed: infrastructure + feature services
app/Filters                       auth, rbac, ratelimit, security headers, maintenance
app/Views/{admin,public,emails}   server-rendered PHP views
app/Database/Migrations           12 incremental migrations (no base schema)
app/Commands                      spark commands used by cron
public/assets/{css,js}            tokens.css, theme.css, admin-tailwind.css (precompiled), vanilla JS
tests/unit                        PHPUnit, DB guard -> talenthub_test only
deployment/                       Hostinger scripts, crontab, SQL dumps
```
Request flow: `Routes.php` → global filters (maintenance, csrf) → route filter (`auth`, `rbac:<perm>`, `ratelimit`) → Controller → Model/Service → View.

## Target (modular monolith, additive)
CRM code lives in its own namespace so it can coexist with, and later replace, TalentHub modules without cross-contamination:

```
app/Crm/
  Controllers/      Auth/User/Team/Customer/Enquiry/Followup/SiteVisit/Product/Quotation/
                    Negotiation/Order/History/Report/Dashboard/Import/Notification/Settings/Demo
  Models/           one per table; no business rules
  Services/         NumberSequenceService, AssignmentService, WorkflowService (status transitions),
                    AuditService (append-only), QuotationCalculator, QuotationService, DiscountApprovalService,
                    PdfService, EmailService, NotificationService, ReportService, ImportService,
                    AccessScope (row-level visibility), DemoSeeder/DemoResetService
  Filters/          CrmPermissionFilter (permission + scope), CorrelationIdFilter
  Commands/         crm:reminders, crm:escalate, crm:email-retry, crm:demo-seed, crm:demo-reset, crm:migrate-check
  Views/            layout + per-module views, shared components (badge, timeline, datatable, kanban)
app/Database/Migrations/Crm/   CRM-only baseline + incremental migrations (own namespace, own history)
app/Database/Seeds/Crm/        reference seeds (roles, permissions, tax, statuses) + demo seeds
public/assets/css/crm.css      design tokens (navy / white / teal) extending admin-tailwind.css
public/assets/js/crm/          small ES modules: datatable, kanban, charts, form-validate, toast, select-search
tests/Crm/{Unit,Feature,Scenario} PHPUnit; scenario tests drive services against the real test DB
```

### Layering rules
1. Controllers: authenticate → authorize (permission + `AccessScope`) → validate → call one service method → render/redirect/JSON. No SQL, no business rules.
2. Services own business rules and open **DB transactions** for multi-table writes. Every state change goes through `WorkflowService`, which calls `AuditService` in the same transaction.
3. Models: persistence only. Money is `DECIMAL` handled with `bcmath` strings in `QuotationCalculator` (no floats).
4. Numbering via `crm_number_sequences` using `SELECT … FOR UPDATE` inside the creating transaction (concurrency-safe, gap-tolerant, unique index as backstop).
5. Authorization is server-side per route **and** per record (IDOR check through `AccessScope`).
6. Responses for JSON endpoints follow the existing envelope `{success,status,code,message,data}`.

### Database environments
| Env | Database | Notes |
|---|---|---|
| local dev | `dhis_crm` | created fresh; migrations + seed |
| tests | `talenthub_test` (existing guard) → will be re-pointed to **`dhis_crm_test`** via `Config\Database::TEST_DATABASE` with the guard retained | guard logic kept; only the constant changes |
| staging/prod | provided by host | not available yet |

### Scheduler
No always-on worker. All automation is `php spark crm:*` commands run by cron every 5–15 min (Hostinger-compatible), each wrapped by existing `JobRunRecorder` for execution logs, with idempotency keys (unique `dedupe_key` on notifications/reminders). HTTP fallback endpoint with secret key retained from the existing cron design.
