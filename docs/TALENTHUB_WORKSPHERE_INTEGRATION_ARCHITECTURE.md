# TalentHub × WorkSphere — Integration Architecture

Status: design of record for the implementation in both repositories. Copy kept in `WorkSphere/docs/` (identical).

## 1. Roles (source of truth)

| Concern | Owner |
|---|---|
| Authentication, identity, tenant provisioning, demo lifecycle, billing, subscription state | **WorkSphere** |
| Marketing site, product catalogue, enquiries, lead CRM, follow-up, admin notifications | **TalentHub** |

TalentHub never creates, modifies or reads WorkSphere accounts. WorkSphere never reads TalentHub data.
The only coupling is a one-way, signed, asynchronous **event feed** WorkSphere → TalentHub.

## 2. Principles

1. WorkSphere registration/provisioning **must not depend on TalentHub availability**. Events are written to a local
   outbox in the same transaction as the business change and delivered afterwards. A TalentHub outage only delays CRM visibility.
2. TalentHub reports success to the browser only after the enquiry/lead row is persisted.
3. Delivery is **at-least-once**; TalentHub processing is **idempotent** (event id + nonce).
4. Data minimisation: no passwords, hashes, tokens, verification links, payment data, employee/payroll data, demo credentials,
   session/JWT values, or WorkSphere internal tenant data ever leave WorkSphere.
5. Dedicated machine identity (key id + secret). No admin user session is used for server-to-server calls.

## 3. Transport contract

`POST {TALENTHUB_BASE_URL}/api/v1/integrations/worksphere/events` — `Content-Type: application/json`, body ≤ 16 KiB, HTTPS only in production.

| Header | Meaning |
|---|---|
| `X-TH-Key-Id` | identifies the integration client (e.g. `worksphere-prod`) |
| `X-TH-Timestamp` | unix seconds when signed; accepted window ±300 s |
| `X-TH-Nonce` | 16–64 chars `[A-Za-z0-9_-]`, unique per delivery *attempt* |
| `X-TH-Event-Id` | UUID v4, **stable across retries** (idempotency key) |
| `X-TH-Signature` | `v1=` + hex `HMAC-SHA256(secret, signing_string)` |

`signing_string = "v1\n" + timestamp + "\n" + nonce + "\n" + event_id + "\n" + sha256_hex(raw_body)`

Verification order on TalentHub (cheapest first, constant-time compare): size → key id known & active → timestamp window →
signature → nonce unused (replay) → body JSON/schema → event id idempotency → persist → 2xx.

| Status | `code` | Sender behaviour |
|---|---|---|
| 200 | `ACCEPTED` / `DUPLICATE` | mark delivered |
| 400 | `BAD_REQUEST` | permanent failure (dead-letter after first attempt) |
| 401 | `BAD_SIGNATURE`, `STALE_TIMESTAMP`, `UNKNOWN_KEY`, `REPLAY` | retry (clock skew / rotation can heal), counts toward attempts |
| 413 | `TOO_LARGE` | permanent |
| 422 | `INVALID_EVENT` | permanent |
| 429 | `RATE_LIMITED` | retry after `Retry-After` |
| 5xx / network / timeout | — | retry with backoff |

Rate limit: 120 requests/minute per key id (and per IP before key lookup: 300/min). Responses never echo secrets or internals.

## 4. Event envelope (schema_version 1)

```json
{
  "schema_version": 1,
  "event_id": "uuid",
  "event_type": "registration_initiated",
  "occurred_at": "2026-11-08T10:15:00Z",
  "source": "worksphere",
  "correlation_id": "uuid (stable per registration)",
  "lead": {
    "organization_name": "", "contact_name": "", "email": "", "phone": "",
    "employee_size": "", "industry": "", "organization_type": "",
    "country": "", "state": "", "city": ""
  },
  "demo": { "status": "", "started_at": null, "expires_at": null, "plan": "" },
  "context": { "message": "", "interest": "", "failed_stage": "", "days_remaining": null, "utm": {} }
}
```

Event types: `registration_initiated`, `email_verified`, `provisioning_succeeded`, `provisioning_failed`, `demo_activated`,
`demo_expiry_approaching`, `demo_expired`, `converted_to_paid`, `consultation_requested`.
Unknown types → 422. `failed_stage` is a stage *name* only (never an exception text). Unknown keys are ignored, not stored.

## 5. Lead mapping (TalentHub)

* Product: `worksphere` (catalogue slug). Source: `worksphere_demo` (registration events) or `worksphere_enquiry` (consultation).
* Dedup key: `lower(email)` + product. An open lead is updated (timeline entry), never duplicated. A new `correlation_id` for an
  email that already has an open lead adds a timeline entry and increments `enquiry_count`.
* Lifecycle mapping: `registration_initiated`→New · `email_verified`→Contacted-eligible (stays New, flagged "verified") ·
  `provisioning_succeeded`/`demo_activated`→Demo Active · `demo_expiry_approaching`→Follow-up due · `converted_to_paid`→Closed Won ·
  `provisioning_failed`→New + high priority + alert. Events never move a lead *backwards* past a manual status set by staff
  (staff-set statuses win; the event is still logged on the timeline).
* Raw event row is retained (`integration_events`) for audit and replay-safe reprocessing; payload stored after allow-list filtering.

## 6. WorkSphere outbox

Table `crm_sync_outboxevent`: `event_id` (uuid, unique), `event_type`, `payload` (JSON, already allow-listed), `status`
(`PENDING`/`DELIVERED`/`DEAD`), `attempts`, `next_attempt_at`, `last_error` (≤300 chars, never bodies), `delivered_at`, `created_at`.
Written inside the same DB transaction as the business change (`transaction.on_commit` triggers the first delivery attempt).
Delivery: short timeouts (connect 3 s / read 6 s), bounded backoff 1 m → 5 m → 15 m → 1 h → 6 h → 24 h, 8 attempts then `DEAD`.
A sweeper (management command and, in single-instance mode, a background thread) retries due rows with row locking
(`select_for_update(skip_locked=True)`) so overlapping workers cannot double-send. Disabled cleanly when `TALENTHUB_CRM_URL` is unset.
Health: superadmin-visible counts by status, oldest pending age, last success time.

## 7. Notifications (TalentHub)

Recipient configurable in admin (`crm_notification_email`, default `projects@talenthubconsultant.com`). One template per event
type, queued through the existing email outbox (with delivery log and retries). Lead persistence never waits on email; the admin
UI shows email status. Test-send available in admin. SMTP credentials stay in server env, never in the UI or Git.

## 8. Admin security (TalentHub)

Permissions: `products.view|manage`, `crm.view|manage|export|settings`, `integrations.view|manage`. Existing RBAC filter, CSRF on
all admin POSTs, audit log entries for every product/lead/setting change. Integration secrets encrypted at rest with the CI4
encrypter; shown once at creation/rotation. Uploads: extension + MIME allow-list, size caps, re-encode/verify image, random
file names, stored under `public/uploads/products/`, no SVG with scripts, video by URL only (https, host allow-list for
YouTube/Vimeo, no server-side fetching → no SSRF).

## 9. Failure matrix

| Failure | Effect |
|---|---|
| TalentHub down | WorkSphere unaffected; events retry up to 24 h then `DEAD` (visible, re-queueable) |
| Signature/clock problem | 401 → retries; surfaced in health as repeated auth failures |
| Duplicate delivery | `DUPLICATE` 200, no second lead |
| Replay of captured request | `REPLAY` 401 |
| Email provider down | lead stored; email row `failed`, retried by outbox cron |
| Secret rotation | two active secrets per client during rotation window |
