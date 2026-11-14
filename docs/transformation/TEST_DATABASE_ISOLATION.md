# Test database isolation (mandatory)

PHPUnit runs against **`talenthub_test`** only. It must never touch `talenthub_db` (local development, built from SQL dumps),
staging, or production.

## Why
`DatabaseTestTrait` rolls back/refreshes migrations and the suite inserts and deletes rows. When `database.tests.database`
pointed at `talenthub_db`, a single `php spark migrate` followed by a PHPUnit run dropped real tables
(see `DEV_DATABASE_INCIDENT_2026-11-08.md`).

## How it is enforced (defence in depth)
1. `app/Config/Database.php` — `TEST_DATABASE = 'talenthub_test'`. When `ENVIRONMENT === 'testing'`, **both** the `tests` and `default`
   groups are overwritten *after* `.env` / `phpunit.xml` overrides are applied: database name, hostname `127.0.0.1`, no DSN, no failover.
   Environment variables cannot redirect them, and neither can an explicit `connect('default')`.
2. `tests/bootstrap.php` (set as the PHPUnit bootstrap in `phpunit.dist.xml`) calls `Database::assertSafeTestDatabase()` **before any
   test, migration, refresh or rollback**. It runs `SELECT DATABASE()` on both groups and aborts the whole run unless the answer is
   `talenthub_test` on localhost. The guard also refuses to run if `ENVIRONMENT` is not `testing`.
3. `php spark` cannot boot under `CI_ENVIRONMENT=testing` (missing `SUPPORTPATH`), so spark migrate/rollback can't be pointed at the test DB by accident either.

Each run prints: `[test-db-guard] PHPUnit is using database 'talenthub_test' (verified via SELECT DATABASE()).`

## Verified (2026-11-08)
- Normal run: guard reports `talenthub_test`.
- Hostile run with `database.tests.database=talenthub_db`, `database.default.database=talenthub_db`, `database.tests.hostname=srv1.example.com` set in the environment: guard still reports `talenthub_test`.
- Guard refusal path exercised: outside `testing` it throws `Refusing to run destructive test operations: ENVIRONMENT is "development"…`.
- Dev DB row counts (`email_outbox` 1364, `audit_logs` 508, `contact_enquiries` 205) were identical before and after full-suite runs.
- The incident was reproduced inside `talenthub_test` (registering app migrations then running the suite) — the dev DB was untouched.

## Setting it up on a new machine
```sql
CREATE DATABASE talenthub_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
```bash
mysqldump -uroot --routines --triggers talenthub_db > seed.sql      # or any known-good schema+seed dump
mysql -uroot talenthub_test < seed.sql
```
The suite expects the seeded reference rows (roles, permissions, email templates, admin user), so load a full copy, not schema-only.
To reset the test DB, re-import the dump into `talenthub_test`.

## Permanent rules
- Never run `php spark migrate`, `migrate:rollback`, `migrate:refresh` or `db:seed` against the SQL-dump-based development database
  without an explicitly reviewed migration strategy. Apply new schema with idempotent SQL / the lazy `apply()` helpers and record it.
- Take a restorable `mysqldump` before any DDL on a database that has data.
- Do not point `database.tests.*` at any other database. If the guard aborts a run, fix the environment — do not edit the guard.
