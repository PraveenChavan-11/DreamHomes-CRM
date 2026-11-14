# CRM Backup & Recovery

## What to protect

| Asset | Location | Why |
|---|---|---|
| Database | MariaDB schema used by the CRM | All customers, enquiries, quotations (versions, items), orders, e-mail log, audit/workflow history |
| Quotation PDFs | `writable/crm/quotations/` | The exact PDF of every **sent** version (SHA-256 stored per version in `crm_quotation_versions.pdf_sha256`) |
| Uploaded files | `writable/crm/` (documents, import files) and `public/uploads/crm-products/` | Site photos, signed confirmations, product images |
| Configuration | `.env` (store in a password manager, **never** in git or the backup archive left on the web root) | Credentials |
| Demo snapshots | `writable/crm/snapshots/demo-YYYYMMDD-HHMMSS.json` | Recoverable copy of demo rows taken before each demo reset |

## Backup procedure

1. **Database dump** (daily, plus before every update/import):
   ```
   mysqldump --single-transaction --routines --default-character-set=utf8mb4 -u USER -p DBNAME > crm-YYYYMMDD.sql
   ```
   On Hostinger, hPanel → Databases → phpMyAdmin → Export (Quick, SQL) or the hPanel *Backups* feature **[YOU]**.
2. **Files:** archive `writable/crm/` and `public/uploads/crm-products/`.
3. Store copies **off the server** (second location). Keep at least 7 daily and 4 weekly copies; the retention values are a suggestion, not a confirmed DHIS policy.
4. Compress and encrypt dumps that contain real customer data; never e-mail them.

## Restore procedure

1. Put the site in a safe state (stop cron, restrict access).
2. Create an empty database (or drop and re-create the affected one), then `mysql -u USER -p DBNAME < crm-YYYYMMDD.sql` (or phpMyAdmin → Import).
3. Restore the file archives to the same paths; `chmod -R 775 writable public/uploads`.
4. Run `php spark crm:verify` (or open **System Health**) — expect *Clean*. Open a few quotations and PDFs from before the backup to confirm.
5. Re-enable cron.

## Demo-data recovery
*Demo Data → Reset* always writes a JSON snapshot first. It is a forensic/recovery copy of demo-owned rows (customers, enquiries, follow-ups, activities, visits, quotations, versions, negotiations, orders, products, users **without password hashes**, workflow events). It is not a one-click restore; re-seed with `php spark crm:demo-seed` to rebuild the deterministic dataset.

## Safety rules built into the application
* Demo reset only touches rows registered in `crm_demo_registry`, never truncates, and is blocked unless the server is flagged as the demo server.
* Customers and users with history are deactivated, never deleted; audit and workflow tables are append-only.
* Migrations are idempotent and their `down()` methods never drop pre-existing data columns.

## Recovery objectives
No RPO/RTO has been agreed with DHIS. Daily backups imply up to 24 h of data loss; shorten the interval if that is not acceptable.
