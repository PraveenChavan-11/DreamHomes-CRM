# CRM Known Limitations & Open Items

Honest list of what is **not** done, **not verified**, or **assumed**. Nothing here has been hidden to produce a clean report.

## Not verified (needs something only DHIS / the host can provide)
* **Live SMTP:** no real e-mail has been sent. Default `email.mode = log_only`. SMTP delivery, SPF/DKIM and `delivered`/`bounced` confirmation (there is no provider webhook; `EmailService::confirmDelivery` exists but nothing calls it yet) are untested.
* **Production hosting:** no deployment was performed; HTTPS, document root, cron registration and database creation are done from the hosting panel (see [CRM_DEPLOYMENT_GUIDE.md](CRM_DEPLOYMENT_GUIDE.md)).
* **Browser-level QA:** automated checks were HTTP-level (status, content, authorization, CSRF, escaping). Console-error and visual responsive checks on real devices still need a human pass ([CRM_UAT_CHECKLIST.md](CRM_UAT_CHECKLIST.md) section H).
* **Penetration test / `composer audit`:** not run.
* **E-mail body preview** uses a sandboxed `<iframe srcdoc>` under the application's Content-Security-Policy; confirm it renders in your target browsers.

## Assumed business values (placeholders — replace with DHIS policy)
GSTIN (empty), terms & conditions, payment terms, default GST 18 %, quotation validity 15 days, discount limits (executive 5 %, manager 15 %, admin 100 %), follow-up escalation after 24 h, visit reminder 24 h, e-mail retry 3 attempts, session timeout 30 min, lock-out 5 attempts / 15 min. They are stored in `crm_settings` flagged `is_default_assumption=1` and shown as **assumed default** on the *Settings* page until someone saves them.
Company name/address/phone/e-mail come from DHIS's public website; no prices, customers or approval limits were invented — the product catalogue and all customers are fictional demo data.

## Technical notes & configurations
* **IP rate limiting:** Active and backed by the `rate_limit_buckets` table (introduced via migration `20261116000001_AddRateLimitBuckets`).
* **Content-Security-Policy:** Strictly hardened for `/crm` (no `unsafe-eval`, no third-party trackers) in `SecurityHeadersFilter`.
* **Shared `MaintenanceFilter`:** Configured to exempt `/crm` (the TalentHub maintenance flag lives in `website_settings`, which is absent from the CRM database). The CRM maintains its own independent session guard.
* **Modal dialogs and Kanban drag-and-drop:** Progressive enhancement via JavaScript (e.g. *New user*, *Reset password*, *Send manual e-mail*). All data is still readable without JS; every other action is a normal form.
* **Single database connection, synchronous work:** PDF generation, report export and import run in the request (no queue worker is assumed on shared hosting).
* **PDF engine:** Dompdf. Logo embedding requires PHP `gd`; without it a text wordmark is used.
* **Scheduler:** Requires the host's cron to run `php spark crm:reminders`; records each run in `scheduled_job_runs` (visible on *System Health*).
* **Demo reset:** Removes only rows registered as demo-owned; records created by hand on a demo server are left in place (and may reference removed demo rows only if they were linked to them).
* **Retention policies:** (audit, e-mail log, snapshots) are managed via database backup/archival.
* **Scope boundary:** No customer-facing portal, WhatsApp/SMS gateway transmission, accounting, inventory or manufacturing — out of scope by design.
* **Role structure:** Five pre-configured commercial roles with fine-grained granular permissions (Super Admin, Admin, Sales Manager, Sales Executive, Management).
* Dataset sizes shown in documents are those created by the seed; a live system will differ.
