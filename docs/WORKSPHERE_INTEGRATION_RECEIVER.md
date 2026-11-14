# WorkSphere Integration Receiver (TalentHub side)

Implements section 3-5 and 9 of `TALENTHUB_WORKSPHERE_INTEGRATION_ARCHITECTURE.md`.

- Endpoint: `POST /api/v1/integrations/worksphere/events` (`App\Controllers\Api\WorksphereEventController::receive`)
- Auth: HMAC-SHA256 request signing inside the controller (no session, no CSRF: the `api/v1/*` group is CSRF-exempt).
- Signing: `App\Services\IntegrationSignatureService`. Lead upsert: `App\Services\WorksphereLeadMapper`.
- Secrets: `App\Services\IntegrationClientService` (encrypted at rest with the CI4 encrypter).

## Required server configuration

| Item | Notes |
|---|---|
| `encryption.key` | **Required.** Set in the server `.env` / environment (generate with `php spark key:generate`; it prints a `hex2bin:...` value). Without it the receiver returns `500 SERVER_MISCONFIGURED` and accepts nothing (fail closed) and `integration:create-client` refuses to run. Changing the key later makes stored secrets undecryptable (rotate every client afterwards); keep old keys in `encryption.previousKeys` only if you also configure that. |
| Database | Tables are created lazily on first use (`CrmIntegration::apply`, idempotent). No manual migrate needed on Hostinger. `php spark migrate` also works. |
| HTTPS | Terminate TLS in front of the app; the signature protects integrity, not confidentiality. |

## Creating / rotating a client

CLI (prints the secret once):

```
php spark integration:create-client worksphere-prod "WorkSphere production"
```

Admin UI: **CRM > Integrations** (permission `integrations.manage`). The secret is shown once on the page after create/rotate and is never listed again.
Re-running the command (or "Rotate secret") issues a new secret; the previous secret stays valid for **24 h** (`prev_expires_at`) so the sender can switch without downtime. Deactivating a client makes all its requests return `401 UNKNOWN_KEY`.

## Request contract (as implemented)

Headers: `X-TH-Key-Id`, `X-TH-Timestamp` (unix s, +-300 s), `X-TH-Nonce` (16-64 `[A-Za-z0-9_-]`), `X-TH-Event-Id` (UUID, must equal body `event_id`), `X-TH-Signature` = `v1=` + hex HMAC-SHA256(secret, `"v1\n" ts "\n" nonce "\n" event_id "\n" sha256_hex(raw_body)`). Body JSON <= 16 KiB.
A reference signer lives in `tests/_support/Libraries/WorksphereSigner.php`.

Processing order: size -> per-IP limit (300/min) -> encryption configured -> key active -> per-key limit (120/min) -> header shape -> timestamp -> signature (constant time, current + grace secret) -> nonce replay -> JSON/schema -> idempotency -> transactional persist -> notification (best effort, after commit).

## Responses and failure matrix

| HTTP | `code` | Cause | Sender should |
|---|---|---|---|
| 200 | `ACCEPTED` | stored, lead created/updated | mark delivered |
| 200 | `DUPLICATE` | `event_id` already accepted | mark delivered |
| 400 | `BAD_REQUEST` | invalid JSON, malformed `X-TH-Event-Id`, header/body event id mismatch | dead-letter |
| 401 | `UNKNOWN_KEY` | key unknown or inactive | retry/alert (rotation) |
| 401 | `BAD_SIGNATURE` | wrong secret, tampered body, missing/malformed signature headers | retry/alert |
| 401 | `STALE_TIMESTAMP` | outside +-300 s | retry (fix clock) |
| 401 | `REPLAY` | nonce already used | retry with a new nonce |
| 413 | `TOO_LARGE` | body > 16 KiB | dead-letter |
| 422 | `INVALID_EVENT` | schema_version != 1, unknown type, bad email/uuid/date, over-long value | dead-letter |
| 429 | `RATE_LIMITED` | 120/min per key or 300/min per IP; `Retry-After` header | back off |
| 500 | `SERVER_MISCONFIGURED` | no `encryption.key` | alert TalentHub admin |
| 500 | `INTERNAL_ERROR` | DB/persist failure (transaction rolled back, event row marked `failed`) | retry |

Responses never include secrets, bodies or stack traces.

## What is stored

`crm_integration_events`: event id, key id, type, correlation id, the **allow-listed** payload (only the fields in the contract; unknown keys such as passwords/tokens are dropped before storage), result (`accepted`/`duplicate`/`failed`) and error code. Auth failures are also recorded (`event_type = auth`, synthetic id, pruned after 7 days) so repeated failures show in the health panel. `crm_integration_nonces` is pruned after 24 h. Request bodies and secrets are never logged.

## Lead mapping rules

Dedup by correlation id (`external_ref`, unique), else by lower(email) + product `worksphere` on an open lead; otherwise a new lead. A new correlation for an existing open lead adds a timeline entry and increments `enquiry_count`.
Status only moves **forward**: `registration_initiated`/`email_verified`/`demo_expiry_approaching`/`demo_expired` never change status of an existing lead; `provisioning_succeeded`/`demo_activated` -> Demo Active only if the lead is earlier in the pipeline (new/contacted/qualified/demo_scheduled); `converted_to_paid` -> Closed Won (unless Spam). On Hold, Closed Lost, Spam and leads staff advanced beyond the event's stage are left untouched but the event is still logged on the timeline. `provisioning_failed` raises priority to High (never lowers Urgent). `demo_expiry_approaching` sets the follow-up date to today.

## Troubleshooting

- `500 SERVER_MISCONFIGURED`: set `encryption.key`.
- Constant `BAD_SIGNATURE`: sender and TalentHub disagree on the secret, or the sender signs a re-serialised body (sign the exact raw bytes sent). If the encryption key changed after the client was created, rotate the secret.
- `STALE_TIMESTAMP`: clock skew > 5 min on the sender.
- `REPLAY` on retries: the sender reused a nonce; it must generate a new nonce per attempt (event id stays stable).
- Leads missing but events `accepted`: check the lead is not soft-deleted/Spam; see the event row `lead_id`.
- No notification emails: the outbox worker must run (`php spark outbox:process` cron); status is visible on the lead and **CRM Settings**.

## Local end-to-end run

```
env "encryption.key=<hex2bin:...>" php spark integration:create-client worksphere-local
env "encryption.key=<same key>" php spark serve --port 8082
```
Then sign requests with `WorksphereSigner::build($secret, 'worksphere-local', WorksphereSigner::event('registration_initiated'))` and POST to `http://localhost:8082/api/v1/integrations/worksphere/events` (use `localhost`, not `127.0.0.1`, with `spark serve` on Windows). The same `encryption.key` must be present for both commands.
