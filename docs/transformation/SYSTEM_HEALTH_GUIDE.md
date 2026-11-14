# System Health — Admin Guide

**Where:** Admin → System Administration → **System Health** (`/admin/system/health`). Needs the `system.view` permission (Super Admin always has it).

## What the statuses mean
| Status | Meaning |
|---|---|
| **Healthy** | There is positive evidence it works (a recorded successful run, a successful query, an unexpired verified token). |
| **Warning** | Works but needs attention (missed runs, expiring token, failed jobs, slow DB, high disk). |
| **Failed** | Broken (database down, stuck jobs, expired token, cron stopped, disk almost full). |
| **Not Configured** | Nothing is set up (no SMTP password, no connected social account, no integration client). |
| **Permission Required** | A social account's authorisation expired and an administrator must reconnect it. |
| **Not Verified** | Something is set up, but nothing proves it works yet (e.g. cron never reported in; a token that was never checked; email only sandboxed). |

**Rule: configuration alone is never "Healthy".** The overall banner is the worst of the core checks and can read "Not Verified" even though nothing has failed.

## The checks
| Card | What is checked | Source |
|---|---|---|
| Application | environment, PHP and CodeIgniter versions, server time | runtime |
| Database | `SELECT 1` + response time (>500 ms = Warning); a connection failure shows a generic message — never host, user or password | live query |
| Storage | writable dirs (`writable`, `cache`, `logs`, `session`, `public/uploads`); disk used/free where hosting allows (≥85% Warning, ≥95% Failed) | filesystem |
| Scheduler & Cron | heartbeat of each cron command — see `CRON_MONITORING_GUIDE.md` | `scheduled_job_runs` |
| Background Jobs | pending / processing / completed / failed / stuck counts for social publishing and email | job tables |
| Email | SMTP password present? failed/queued counts; last *real* delivery. Sandboxed environments (staging, local) show **Not Verified** by design | `email_outbox` |
| Social Integrations | per account: status, token expiry (<7 days = Warning), last verification | `social_accounts`, `social_credentials` |
| External API Clients | inbound WorkSphere client: last accepted event, last failure, failures/rejected auth in 24 h | `crm_integration_events` |

**What this page does not do:** it never calls LinkedIn, Meta, Google or any other external service, never runs shell commands and never reads environment variables for display. So "social" and "external API" statuses describe stored data and recorded events, not a live ping. (Live API tests belong in the integration pages.)

## Caching and refresh
Results are cached for 60 seconds so the page cannot load the database repeatedly. **Re-check now** (a POST button) clears the cache. Staged or corrected data may therefore show up to a minute late unless you re-check.

## Security
- Pages require `system.view`; job retry/cancel require `system.manage`; both enforced on the server (hidden buttons are not the control).
- Nothing rendered contains secrets, tokens, passwords, environment values, stack traces or server paths; error text is scrubbed by `JobRunRecorder::sanitize()`.
- Retry/cancel (and rejected attempts) are written to the audit log (`JOB_RETRY`, `JOB_CANCEL`, `…_REJECTED`) with the acting user.

## Known limitation
Header pill "Mail sync: …" reflects the `mail:sync` heartbeat. On staging/local (sandbox) `mail:sync` currently fails by design of an existing bug (see `MILESTONE_2_PHASE_B_REPORT.md`), so it shows "needs attention" there.
