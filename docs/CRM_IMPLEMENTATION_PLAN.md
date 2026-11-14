# DHIS Sales CRM — Implementation Plan

Status legend: ✅ done · 🔄 in progress · ⏳ not started. Updated at the end of every phase together with tests.

| Phase | Scope | Exit gate | Status |
|---|---|---|---|
| 1 | Repo audit, branch, baseline tests, 5 audit docs | Baseline 305/305 green; docs committed | ✅ |
| 2 | Architecture, CRM baseline migration, number sequences, Audit/Workflow service, `.env`, test DB re-point | Migration runs up & down on `dhis_crm` and `dhis_crm_test`; service tests green | ⏳ next |
| 3 | CRM auth (login history, timeout), roles/permissions seed, `AccessScope`, users & teams UI | Auth + permission + IDOR tests green | ⏳ |
| 4 | Customers, enquiries, assignment engine, status matrix | Tests C, D green | ⏳ |
| 5 | Follow-ups, activities, site visits, measurements, uploads | Tests E green | ⏳ |
| 6 | Products/pricing, quotation engine (bcmath), PDF (dompdf), versions | Calc + PDF + version tests green | ⏳ |
| 7 | Negotiation + discount approval | Tests G green (self-approval blocked) | ⏳ |
| 8 | Email service/log/retry (safe provider) | Tests F/email green | ⏳ |
| 9 | Orders, won/lost, reopen | Tests H green | ⏳ |
| 10 | Customer history, global history, audit UI | Tests I green | ⏳ |
| 11 | Dashboards, reports + Excel/PDF export, notifications, cron commands | Dashboard values reconcile with SQL | ⏳ |
| 12 | Demo seed (deterministic), DEMO_ACCESS.md, protected reset | Reset refuses outside demo env; integrity check passes | ⏳ |
| 13 | Full automated + E2E scenario + security tests | All green, counts reported honestly | ⏳ |
| 14 | UI polish, responsive & console checks | Manual/browser verification recorded | ⏳ |
| 15 | Deployment package | Remaining external actions listed | ⏳ |

## Confirmed vs. assumed (defaults to be replaced by DHIS)
**Confirmed by brief:** roles, stages, modules, numbering concept, brand direction (navy/white/teal).
**Assumed defaults (NOT DHIS policy):** discount limits (Executive 5 %, Manager 15 %, Admin unlimited), default GST 18 %, quotation validity 15 days, number formats `CUS-0001 / ENQ-0001 / QT-0001 / NEG-0001 / SO-0001`, session timeout 30 min, assignment default = round-robin within team, currency INR. Every one is a `crm_settings` row editable by Admin. No real DHIS GSTIN, address, prices or logo have been supplied; PDFs/emails use clearly marked placeholders.

## New dependencies planned
`dompdf/dompdf` (PDF), `phpoffice/phpspreadsheet` (Excel import/export; needs `zip`, present), mail via existing CI Email/`HostingerMailService` (SMTP; test mode writes to `crm_email_log` only and never contacts real recipients). `gd` absent locally: PDFs avoid raster processing; logo as inline SVG/PNG is passed through dompdf which does not need gd for PNG without alpha — to be verified in Phase 6.

## Working agreements
- TDD for services; tests run against `dhis_crm_test` only (guard retained).
- Commit per phase on `feature/dhis-sales-crm`; no force-push; no secrets (`.env` is git-ignored).
- Any defect found is fixed before the next phase starts.
