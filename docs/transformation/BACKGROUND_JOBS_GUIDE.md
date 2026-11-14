# Background Jobs — Admin Guide

**Where:** Admin → System Administration → **Background Jobs** (`/admin/system/jobs`). View: `system.view`. Retry/cancel: `system.manage`.

One list for the two real queues:
- **Social publishing** — `social_scheduled_jobs` (LinkedIn/Instagram). Processed by `php spark social:publish`.
- **Email** — `email_outbox`. Processed by `engagement:run` or `outbox:process`.

Columns: job, type, status, created, completed, duration (social: from the last publish attempt), tries, related record (links to the post), problem (shortened, scrubbed). Filters: queue and status. Server-side pagination (25 per page).

## Statuses (normalised)
Pending · Processing · Completed · Failed · Cancelled. **Stuck** = processing for more than 15 minutes (shown in the counters and in System Health).

## Job detail
Shows timing, attempts, next attempt, related record, the scrubbed problem summary and (social) the recent publish attempts. It never shows message bodies, API request/response payloads, tokens or paths.

## Actions (need `system.manage`)
| Action | Allowed when | What it does |
|---|---|---|
| **Retry** | status = Failed | Social: back to *queued*, attempts reset, next attempt now; the post goes *failed → scheduled* (a post in any other state is left alone). Email: *FAILED → PENDING*, attempts reset. |
| **Cancel** | social job, status = Pending | Marks it *cancelled*. A job a worker already claimed (Processing) cannot be cancelled. Email cannot be cancelled (that would mean deleting the message). |

Safety:
- Each action is one atomic conditional `UPDATE … WHERE status = …`; two admins clicking at once, a stale browser page, or a worker claiming the row at the same moment can never produce a double retry or cancel a running job. The loser is told "can no longer be retried".
- Actions are POST + CSRF only, confirmed by a browser dialog, and audit-logged (`JOB_RETRY`, `JOB_CANCEL`, and `…_REJECTED` for refused attempts).
- A retried publish still goes through the existing publishing engine (idempotent receipts), so retrying cannot post twice.

## Troubleshooting
- *Many "Pending" social jobs never move:* the scheduler is not running — see System Health → Scheduler & Cron.
- *Job "Failed" with a platform error:* open the detail, fix the cause (usually reconnect the account), then Retry.
- *Job "Stuck":* the scheduler releases stale locks on its next run; if it does not, cron is not running.
