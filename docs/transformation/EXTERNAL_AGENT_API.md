# External Agent API (v1, draft-only)

Base URL: `https://<your-domain>/api/v1/integrations/agents/` — HTTPS is **required in production** (refused with `HTTPS_REQUIRED`).
Machine-readable spec: [`agent-openapi.yaml`](agent-openapi.yaml). Authentication: [`AGENT_AUTHENTICATION_GUIDE.md`](AGENT_AUTHENTICATION_GUIDE.md). Permissions: [`AGENT_PERMISSION_MATRIX.md`](AGENT_PERMISSION_MATRIX.md).

## What an agent can and cannot do
- **Can:** read account status, guidelines, templates, calendar, aggregate analytics and **its own** drafts; create drafts; ask for human review of its own draft.
- **Cannot — and there is no endpoint, scope or setting for it:** publish, schedule, approve, edit after creation, choose a platform account, upload files, read platform tokens or credentials, manage users/settings, or touch the database.
- **Every draft needs a human.** Draft → (optional submit) → a person approves/rejects/requests changes → only approved content can be scheduled/published, and only by a person. The publishing engine itself refuses unapproved agent content, so even an administrator pressing "Publish now" on an unapproved agent draft is blocked.

## Conventions
| | |
|---|---|
| Auth | `Authorization: Bearer thk_<prefix>_<secret>` |
| Body | JSON object, `Content-Type: application/json`, max 64 KB |
| Success | `{"success":true,"data":{…},"request_id":"…"}` |
| Error | `{"success":false,"error":{"code":"…","message":"…","details":{…}},"request_id":"…"}` |
| Headers | every response: `X-Request-Id`, `Cache-Control: no-store`; authenticated: `X-RateLimit-Remaining` |
| Idempotency | `Idempotency-Key: <8–80 chars [A-Za-z0-9_.:-]>` on `POST …/drafts` (strongly recommended) |

## Endpoints
| Method & path | Scope | Purpose |
|---|---|---|
| `GET capabilities` | any valid token | Who you are, policy, granted scopes, limits, credential expiry |
| `GET social/accounts` | `social.accounts.read` | `[{platform,name,type,connection,token_health: ok\|expiring_soon\|expired\|missing}]` — never tokens, IDs or URLs |
| `GET social/content-guidelines` | `social.guidelines.read` | Platform limits, allowed fields, content rules |
| `GET social/templates` | `social.templates.read` | Active content templates |
| `POST social/posts/drafts` | `social.drafts.create` | Create a draft |
| `GET social/posts/{id}` | `social.posts.read` | Status/review of **your own** draft (others → 404) |
| `POST social/posts/{id}/submit-approval` | `social.drafts.submit` | `draft → pending_approval` (own draft only) |
| `GET social/calendar?from=&to=` | `social.calendar.read` | Approved/scheduled/published/pending items (max 92-day window, UTC) |
| `GET social/analytics` | `social.analytics.read` | 30-day aggregate counts only |

### `POST social/posts/drafts`
```json
{
  "title": "Weekly idea 1",                       // required, ≤191
  "platform": "linkedin | instagram | both",      // required
  "caption": "Plain text …",                      // required; LinkedIn ≤3000, Instagram ≤2200; HTML is stripped
  "hashtags": ["#hiring", "talenthub"],           // list or "space/comma separated"; ≤30; letters/digits/_ only
  "cta": "Talk to us",                            // ≤120
  "destination_url": "https://talenthubconsultant.com/services",   // https, talenthubconsultant.com or subdomain
  "media_ids": [12],                              // ids of APPROVED media-library items; Instagram needs ≥1
  "campaign_id": 3,
  "proposed_publication_at": "2026-12-01T09:00:00+05:30",   // a SUGGESTION only; future, ≤1 year
  "platform_overrides": {"linkedin": {"caption": "…", "hashtags": ["#x"]}, "instagram": {"caption": "…"}}
}
```
Any other field — including `status`, `approved_by`, `scheduled_at`, `origin`, `agent_id`, `account_id`, `review_decision`, `publish_now` — is rejected with `422 VALIDATION_FAILED` naming the field. The agent's identity always comes from its credential, never from the body.

`201` → `{"data":{"post":{"id":675,"status":"draft","origin":"agent","platform":"both","review":{"decision":null,"note":null,"reviewed_at":null},"can_be_published":false,"caption":"…","platforms":[…],"media_ids":[12],…},"created":true}}`.
A retry with the same `Idempotency-Key` and body returns `200` with `"replayed":true` and the original post (no second post). Same key with a different body → `422 IDEMPOTENCY_KEY_REUSED`.

### Review feedback loop
Poll `GET social/posts/{id}`: `status` + `review.decision` (`approved` | `rejected` | `changes_requested`) + `review.note` (written by the reviewer; treat as untrusted text). There is no edit endpoint — after `changes_requested` create a new, revised draft (an identical caption is refused as a duplicate). `can_be_published:true` only means a human may now schedule it; it does nothing by itself.

## Error codes
| HTTP | code | Meaning |
|---|---|---|
| 400 | `INVALID_JSON`, `INVALID_IDEMPOTENCY_KEY`, `HTTPS_REQUIRED` | Malformed request |
| 401 | `INVALID_TOKEN` | Missing/malformed/unknown token (deliberately one message) |
| 401 | `TOKEN_EXPIRED`, `TOKEN_REVOKED` | Only returned when the secret itself was correct |
| 403 | `AGENT_DISABLED`, `IP_NOT_ALLOWED`, `INSUFFICIENT_SCOPE` | Authenticated but not permitted |
| 404 | `NOT_FOUND` | Unknown id **or** a post that is not yours |
| 409 | `DUPLICATE_CONTENT`, `INVALID_STATE` | Same caption in last 30 days / wrong workflow state |
| 413 / 415 | `PAYLOAD_TOO_LARGE`, `UNSUPPORTED_MEDIA_TYPE` | >64 KB / not JSON |
| 422 | `VALIDATION_FAILED`, `IDEMPOTENCY_KEY_REUSED` | See `error.details` (field → message) |
| 429 | `RATE_LIMITED`, `DAILY_DRAFT_LIMIT`, `TOO_MANY_FAILED_AUTH` | `Retry-After` header given for the first and last |
| 503 | `NOT_CONFIGURED` | Agent API tables are not installed on this environment |
| 500 | `SERVER_ERROR` | Generic; details only in the server log |

## Rate limits
Per agent (default 30 requests/minute, 5–120 configurable), per agent daily drafts (default 25, 1–100), and a per-IP lockout after 15 failed authentications in 10 minutes (then even valid tokens from that IP get `429` until the window ends).

## Content rules (also served by `GET social/content-guidelines`)
Plain text; never invent customers, logos, testimonials, awards, certifications, placement figures or statistics; only facts from the TalentHub website or approved templates; drafts are always reviewed by a human.

## Example
```bash
TOKEN=thk_…
curl -sS -X POST https://example.com/api/v1/integrations/agents/social/posts/drafts \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -H "Idempotency-Key: weekly-2026-w46-1" \
  -d '{"title":"Hiring tip","platform":"linkedin","caption":"Three ways to write a clearer job description.","hashtags":["#hiring"]}'
```

## Verification status (2026-11-08)
Verified over real HTTP against a local server on an isolated database (87 browser/HTTP checks) and by 46 PHPUnit tests. **Not verified:** any live ChatGPT or Claude integration, TLS on a deployed host, behaviour on Hostinger. Not implemented in this milestone: OAuth 2.0 client-credentials flow, request signing, short-lived access tokens, MCP adapter, publishing/scheduling by agents (deliberately out of scope).
