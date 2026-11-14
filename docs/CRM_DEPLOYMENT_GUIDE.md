# CRM Deployment Guide (Hostinger shared hosting / any LAMP host)

Status: **deployment package ready; nothing has been deployed by this guide.** Actions that need your hosting panel, DNS or SMTP credentials are marked **[YOU]**.

## 1. Requirements

| Item | Needed | Check |
|---|---|---|
| PHP | 8.2+ with `mysqli`, `mbstring`, `intl`, `bcmath`, `fileinfo`, `gd`, `zip`, `curl` | *System Health* page → PHP extensions, or `php -m` |
| Database | MariaDB 10.4+ / MySQL 8, utf8mb4, InnoDB | |
| Web server | Apache with `mod_rewrite` (the repo `.htaccess` is included), document root = `public/` | |
| HTTPS | Valid certificate (Hostinger free SSL) **[YOU]** | *System Health* → HTTPS |
| Cron | One cron entry, every 10 min | |
| Disk | `writable/` and `public/uploads/` writable by PHP | *System Health* → Folders |

`gd` is required to embed the PNG logo in quotation PDFs; without it the PDF falls back to a text wordmark. `zip` + `gd` are used by the XLSX export/import (PhpSpreadsheet).

## 2. First-time install

1. **Upload** the project (everything except `.env`, `writable/*` contents, `vendor/` if you run `composer install --no-dev` on the server). Point the domain's document root to `public/` (Hostinger: *Websites → Advanced → document root* **[YOU]**). If the host forces `public_html`, keep the repository one level above it and put `public/` contents in `public_html` with `index.php` paths adjusted — do **not** expose `app/`, `writable/`, `.env`.
2. **Database [YOU]**: create a database and user in hPanel (host is `127.0.0.1`/`localhost`, never the `srvNNNN.hstgr.io` address from inside the app).
3. **Configuration**: copy `.env.example` to `.env` and fill in base URL, database credentials, `CI_ENVIRONMENT = production`, e-mail settings. Run `php spark key:generate`. **Never commit `.env`.**
4. **Schema** — choose one:
   * *Import the supplied SQL dump* (phpMyAdmin → Import) — contains schema, reference data and the fictional demo data (demo server only); or
   * *Run migrations*: `php spark migrate -n Crm` then `php spark crm:seed-reference`.
5. **Accounts**
   * **Production:** `php spark crm:create-admin you@yourcompany.com --name "First Last"` (the password is prompted, never passed on the command line). Do **not** import or seed demo users on production.
   * **Demo server only:** set `DHIS_DEMO_ENV = true` in `.env`, set the CRM setting `demo.enabled` to `1` (SQL: `UPDATE crm_settings SET setting_value='1' WHERE setting_key='demo.enabled';`), then `php spark crm:demo-seed`. Credentials: see `DEMO_ACCESS.md` (shared demo password — change or remove before any real use).
6. **Permissions:** `chmod -R 775 writable public/uploads` (or the equivalent in File Manager). The CRM creates `writable/crm/` (PDFs, snapshots) itself; keep it **outside** the web root (it is).
7. **Cron [YOU]** (hPanel → Advanced → Cron Jobs; replace the paths):
   ```
   */10 * * * * cd /home/USER/domains/DOMAIN/app && /usr/bin/php8.2 spark crm:reminders >> writable/logs/cron.log 2>&1
   17 2 * * *  cd /home/USER/domains/DOMAIN/app && /usr/bin/php8.2 spark crm:verify    >> writable/logs/cron.log 2>&1
   ```
   `crm:reminders` is idempotent (safe if two runs overlap) and records each run in `scheduled_job_runs` (visible on *System Health*).
8. **E-mail:** the default `email.mode = log_only` records e-mails without sending. To send for real: put SMTP details in `.env`, then *Settings → Email → mode = smtp*. Send a manual e-mail to yourself from *Email Log* and confirm it arrives; the status will read `sent` (SMTP accepted) — `delivered` is only ever set by provider confirmation. **[YOU]** (needs SMTP credentials; not verified by this package.)

## 3. Pre-go-live checklist

- [ ] `CI_ENVIRONMENT=production`, `app.baseURL` is the HTTPS URL, `DHIS_DEMO_ENV` is `false`/absent
- [ ] *System Health*: integrity **Clean**, folders **writable**, extensions loaded, HTTPS **On**
- [ ] Demo users removed (or never imported); a real Super Admin exists
- [ ] DHIS-supplied GSTIN, terms & conditions, payment terms, approval limits entered in *Settings* (they ship as clearly-labelled placeholders / assumed defaults)
- [ ] Backup taken and a restore rehearsed ([CRM_BACKUP_RECOVERY.md](CRM_BACKUP_RECOVERY.md))
- [ ] Cron job running (a row appears in *System Health → Scheduled jobs*)
- [ ] SMTP tested (if `smtp` mode)
- [ ] Demo reset confirmed **blocked** on this server (*Demo Data* page shows "Not demo")

## 4. Updating
Back up DB + `writable/crm/`, upload new code, run `php spark migrate -n Crm` (migrations are idempotent and non-destructive), then `php spark crm:seed-reference` (idempotent), clear `writable/cache`. Never run destructive SQL on production without a verified backup.

## 5. Staging
Use the same steps with a separate database and `.env`; set `CI_ENVIRONMENT=production`, keep `DHIS_DEMO_ENV=false` unless the staging copy is a deliberate demo server.

## 6. Live Hostinger Deployment Status

* **Live Demo Domain:** `https://dhiscrm.talenthubconsultant.com`
* **Server Root Directory:** `/home/u139147679/domains/talenthubconsultant.com/public_html/Product_Demo/DreamHomes`
* **Database:** `u139147679_dhiscrm` (MariaDB 11.8 on `srv1824.hstgr.io`, connects via `127.0.0.1` locally)
* **Auto-Deployment:** Connected to GitHub `main` branch via Hostinger Git Webhook (`https://webhooks.hostinger.com/deploy/29d264ceb80ab83e455347cd12423727`). Every push to `main` auto-deploys.
* **Complete DDL Export:** Available in repository at `deployment/migration_20261116_crm_complete.sql`.
* **Scheduled Cron Jobs (hPanel):**
  ```
  */10 * * * * cd /home/u139147679/domains/talenthubconsultant.com/public_html/Product_Demo/DreamHomes && /usr/bin/php8.2 spark crm:reminders >> writable/logs/cron.log 2>&1
  17 2 * * *  cd /home/u139147679/domains/talenthubconsultant.com/public_html/Product_Demo/DreamHomes && /usr/bin/php8.2 spark crm:verify    >> writable/logs/cron.log 2>&1
  ```
