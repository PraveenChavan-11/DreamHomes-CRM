# TalentHub — Security Audit (static review, Phase 0)

Scope: code/config read-through only. No penetration testing was performed and no finding below has been exploited. Severity is my assessment.

## Positive controls observed
Password hashing and login-attempts table, TOTP/OTP admin login, `auth` / `rbac` / `ratelimit` filters, security-headers filter (HSTS, X-Frame-Options, CSP), document vault for resumes, CAPTCHA service, form-security service, audit log, staging env-safety service, DPDP CV-deletion workflow, `.env` gitignored.

## Status update (2026-11-06)
S-01, S-02 (same-origin check), S-03 fixed in commit 0238577 and covered by the existing suite (77 tests pass). S-05 re-checked: the webhook verifies a shared secret, so it is downgraded to informational. S-04 (SQL dumps) and S-07 remain open; the CSRF finding is mitigated, not replaced by tokens.

## Findings
| ID | Sev | Finding | Evidence | Recommended fix |
|---|---|---|---|---|
| S-01 | **High** | Hardcoded fallback cron secret `talenthub_cron_secret_2026` used when `CRON_SECRET_KEY` is unset; secret travels in the query string (logged by servers/proxies) | Routes.php `api/cron/run` | Require env var (fail closed); prefer CLI cron or header token; rotate |
| S-02 | **High** | CSRF filter excludes `api/*` and `api/v1/*`, yet `api/v1/admin/*` mutating endpoints are session-cookie authenticated | Filters.php:81, Routes.php admin API group | Require CSRF header/bearer token on authenticated API mutations; keep exclusion only for public stateless endpoints. Exploitability *(unverified)*; SameSite=Lax alone is thin defence |
| S-03 | **Med** | `GET /admin/migrate-schema` changes the DB schema via GET | EngagementController::migrateSchema | POST + CSRF + super-admin + confirmation, or CLI only |
| S-04 | **Med** | Tracked SQL dumps (`database_hostinger_export.sql`, `talenthub_db_phase0_backup.sql`, `clean_database_import.sql`) may contain real PII or password hashes | `git ls-files deployment` | Inspect contents *(not yet reviewed)*; if real, purge from history and rotate secrets |
| S-05 | **Med** | `/webhooks/hostinger-mail` shows no auth at route level | Routes.php | Verify HMAC signature in controller *(unverified)* |
| S-06 | **Low** | Roles limited to generic seeds; brief requires 9 roles with independent permissions | DatabaseSchemaService | Seed in a migration |
| S-07 | **Info** | Repo root tracks office docs, `logo.psd/ai`, `scratch/` PHP scripts | `ls` | Confirm the web root is `public/` / exclude from deploy artifact |

## Not yet verified (must be tested on staging)
SQLi/XSS on each form, IDOR on `/admin/*/(:num)` and `documents/(:num)/stream`, upload MIME/signature checks, session fixation, rate-limit effectiveness behind Hostinger's proxy, service-worker exclusion of admin/API, DPDP retention implementation, current DPDP Rules requirements (to be checked against official sources before any compliance claim).
