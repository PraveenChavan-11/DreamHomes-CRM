# Milestone 2 — Phase B completion report (System Health, Background Jobs, Scheduler)

Date: 2026-11-08 · Phase A commit `5a1d4a8` is unchanged. Nothing has been pushed or deployed. Phase C has not been started.

## 1. Database repair and recovery
See `DEV_DATABASE_INCIDENT_2026-11-08.md` (full detail, evidence tables, what is and is not recoverable).
- Backups before any change: `D:\Personal\_db_backups\talenthub_db_pre_repair_20261108_203415.sql` (93 tables), `affected_tables_…sql`, and `talenthub_db_post_repair_…sql`. Restore proven (loaded into `talenthub_test`, row counts matched).
- Both affected tables had 0 rows, no FKs/triggers/views; the SQL file matched the models; only those two tables were dropped/recreated (plus the six mail tables via the project's own `mail:setup-tables`).
- Root cause of the stale shapes fixed in code (`DatabaseSchemaService` carried the old definitions).
- Not recoverable: any hand-entered product edits and any real `it_leads`/social data that may have existed (no backup newer than the Nov 1–3 dumps). Evidence indicates the social posts and CRM client were test residue; two `social_posts` rows (98 is not a multiple of 4) are unaccounted for.

## 2. Table counts / schema (dev `talenthub_db`)
| | Before repair | After |
|---|---|---|
| tables | 93 | 93 |
| `email_campaigns` | 0 rows, columns `name, preheader, content_html …` | 0 rows, `title, preview_text, body_html, body_text, audience_filter, template_id, delivered_count, created_by …` |
| `newsletter_activity_logs` | 0 rows, JSON-checked `metadata`, `ip_hash` | 0 rows, `metadata TEXT`, FK to `newsletter_subscribers` (CASCADE) |
| every other table | — | row-count diff vs post-repair snapshot: only `scheduled_job_runs` (+4, see §7 note) |

## 3. Separate test database
`talenthub_test` (copy of the repaired dev DB, 93 tables). `Config\Database` pins **both** `tests` and `default` groups to it under `testing` (after env/`.env` overrides), plus `tests/bootstrap.php` aborts the run unless `SELECT DATABASE()` is `talenthub_test` on localhost. Evidence (`TEST_DATABASE_ISOLATION.md`): normal run and a hostile env-override run both print `[test-db-guard] PHPUnit is using database 'talenthub_test'`; the refusal path throws outside `testing`; dev row counts identical before/after full-suite runs; `spark` cannot boot under `testing`.

## 4. Protection of dev / staging / production
Dev: pinned out of PHPUnit as above. Staging/production: PHPUnit has no route to them — the guard forces hostname `127.0.0.1` and the database name, ignoring env overrides. Nothing was deployed or pushed.

## 5. PHPUnit
Baseline 174 → **222 tests, 1539 assertions, all passing**, three consecutive runs including `--order-by=random`. New: `SystemHealthServiceTest` (24), `BackgroundJobServiceTest` (14 incl. recorder), `SystemControllerTest` (14), plus the existing `AdminSidebarNavTest`.
The six errors: 4 fixed by the table repair (reproduced in `talenthub_test`); the 2 `CrmLeadLifecycleTest` errors **root-caused and reproduced**: stale `crm-L<id>` idempotency keys colliding with reset lead ids (details in the incident report); fixed by making the test's setUp clear orphaned keys. No assertion was removed, skipped or weakened.

## 6. Edge browser verification
`SYSTEM_HEALTH_E2E_RESULTS.txt`: **61/61** checks (+ 33/33 original sidebar checks re-run on the new layout). Covers missing / successful / missed / stale / failed / stuck heartbeat states, the four real heartbeat rows, job list/filter/detail, retry and cancel through the real UI with the confirm dialog, view-only / manage / restricted users, forged and CSRF-less POSTs, double-retry, sensitive-string scan of every page (sentinel secret never appears), desktop + 390px layouts, and the social queue badge.

## 7. Cron heartbeat evidence
All four commands were executed for real against `talenthub_test`: `social:publish` SUCCESS (0 jobs), `engagement:run` SUCCESS, `outbox:process` SUCCESS (1 item, sandboxed), `mail:sync` **FAILED** with its genuine error. Scheduler stays **Not Verified** whenever no heartbeat exists. **Real Hostinger server cron is NOT verified.**
Note: an earlier set of the same four runs hit the dev DB by mistake (env override ineffective); effects (4 heartbeat rows, 26 sandboxed outbox flips, no real email) are disclosed in the incident report.

## 8. Security / permission results
Passing automated + browser: anonymous → login; no `system.view` → blocked on all 4 pages and no sidebar entries; view-only sees data, no buttons, forged POST refused with job unchanged; missing CSRF → 403; GET cannot mutate; double retry rejected and audited as `…_REJECTED`; running job cannot be cancelled even by a forged request; unknown queue → 404, missing job → friendly redirect; SQL-injection/XSS in filter params harmless; no secrets, env values, paths or stack traces in any page; the service source is scanned for network/shell calls (none); health cached 60 s; refresh is POST-only.
**Findings (not fixed here):**
1. `AdminBaseController::isSuperAdmin()` grants Super Admin to any user with `id === 1` **or** `username === 'admin'`, independent of roles — a role-bypass that survives role removal. (It also invalidated my first browser run.) Recommend removing it after confirming a real `super_admin` role exists for the owner.
2. `HostingerMailService::getAccountInfo()` references an undefined `$msgUid` in its sandbox branch, so `mail:sync` always crashes in non-production. Pre-existing; now visible as a FAILED heartbeat and a "needs attention" pill.
3. The CI shared view renderer retains data across requests in one PHP process — a test-only artefact, handled in the tests; production unaffected.
4. Dotted environment variables cannot override `.env` (CodeIgniter loader); don't rely on them for safety.

## 9. Files changed
Modified: `app/Commands/{EngagementScheduler,ProcessOutbox,SocialPublishCommand,SyncMailboxes}.php`, `app/Config/{Database,Routes}.php`, `app/Services/DatabaseSchemaService.php`, `app/Views/admin/layouts/{_sidebar_nav,main}.php`, `app/Views/admin/social/queue.php`, `phpunit.dist.xml`, `tests/unit/CrmLeadLifecycleTest.php`.
New: `app/Controllers/Admin/SystemController.php`, `app/Services/{SystemHealthService,BackgroundJobService,JobRunRecorder}.php`, `app/Views/admin/system/{_status,health,jobs,job_detail,scheduler}.php`, `app/Database/Migrations/20261111000001_system_health_operations.php`, `tests/bootstrap.php`, `tests/_support/Libraries/RollsBackDatabase.php`, `tests/unit/{SystemHealthServiceTest,BackgroundJobServiceTest,SystemControllerTest}.php`, six docs in `docs/transformation/`.
**Migration:** `20261111000001_system_health_operations` (idempotent: creates `scheduled_job_runs` only if missing, adds `idx_job_started`, inserts permissions `system.view`/`system.manage` for super_admin). On dump-built databases apply its SQL; do not run `spark migrate`.

## 10. Remaining limitations
- Hostinger server cron unverified; mail:sync fails in sandbox (finding 2).
- No data-loss recovery beyond schema/seed; two social rows unaccounted for.
- Health "social" and "external API" statuses are derived from stored data, not live API calls (by design — no SSRF surface).
- Safari/Firefox/real-device/screen-reader not tested; Edge only.
- `system.view` / `system.manage` are granted to `super_admin` only; other roles need an admin to assign them.
- Phases C (agent API), D (TinyMCE key, Tailwind CDN), E remaining tests, F docs 4–10, and G commits 3–4 are not started.
