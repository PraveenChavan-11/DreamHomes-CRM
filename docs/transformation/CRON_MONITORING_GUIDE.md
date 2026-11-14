# Cron & Scheduler Monitoring

## How it works
PHP cannot read your hosting's crontab, and a cron line existing proves nothing. So each cron command **records its own heartbeat** in `scheduled_job_runs` when it actually runs:

`RUNNING` row at start → updated to `SUCCESS` (items processed, duration) or `FAILED` (scrubbed error) at the end. If the process dies, the row stays `RUNNING` and is reported as "never finished".

| Command | Expected every | Required for overall status |
|---|---|---|
| `php spark social:publish` | 5 min | yes |
| `php spark engagement:run` | 15 min | yes (also sends the email outbox) |
| `php spark outbox:process` | 5 min | no (optional, standalone) |
| `php spark mail:sync` | 60 min | no (optional) |

(The expected intervals are the `SystemHealthService::SCHEDULED_JOBS` constants; change them there if your cron schedule differs.)

## Status rules (age = time since the last run started)
- No heartbeat ever → **Not Verified**.
- ≤ 2.5 × interval and last run succeeded → **Healthy**.
- Last run failed → **Warning**.
- 2.5–6 × interval → **Warning** ("missed runs").
- > 6 × interval → **Failed** ("appears to have stopped").
- `RUNNING` for more than max(30 min, 3 × interval) → **Failed** (crashed or killed).
The scheduler card is Healthy only when **every required** job is Healthy. The social queue page's "Cron worker" badge and the header "Mail sync" pill use the same data.

## Setting up cron on Hostinger (hPanel → Advanced → Cron Jobs)
```
*/5  * * * *  /usr/bin/php /home/u139147679/public_html/spark social:publish  >> /dev/null 2>&1
*/15 * * * *  /usr/bin/php /home/u139147679/public_html/spark engagement:run  >> /dev/null 2>&1
```
`public_html` here is the folder containing `spark` (adjust if your production app is elsewhere). After saving, wait one interval and open **Scheduler & Cron**: a job turns Healthy only once it has genuinely run. **Until then the status stays Not Verified — by design.**

## Heartbeat table
`scheduled_job_runs(id, job_name, status ENUM('RUNNING','SUCCESS','FAILED'), items_processed, error_details, duration_ms, started_at, completed_at)` with index `idx_job_started(job_name, started_at)`. The table already existed on deployed databases (created from SQL dumps); migration `20261111000001_system_health_operations` creates it only if missing and adds the index and the `system.view` / `system.manage` permissions. **Apply that migration's SQL by hand or via the project's idempotent helpers on dump-built databases — do not run `spark migrate` on them** (see `TEST_DATABASE_ISOLATION.md`).

## Timezone note
The application runs in UTC; heartbeat timestamps are written by PHP in UTC. If you stage or inspect rows directly in SQL, use `UTC_TIMESTAMP()`, not `NOW()` (MySQL's clock may be local time).

## Verified on 2026-11-08
All four commands were actually executed against the isolated test database and each wrote a heartbeat (3 × SUCCESS, `mail:sync` FAILED with its real error). The missing / successful / missed / stale / failed / stuck states were then verified in Edge. **Real server cron on Hostinger has NOT been verified** — that needs the entries above on the server.
