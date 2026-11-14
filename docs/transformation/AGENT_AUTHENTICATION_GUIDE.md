# Agent Authentication Guide

## Model
Each agent is registered individually (Admin → System Administration → **External AI Agents**) and receives its own **scoped, expiring, revocable bearer token**. The admin session, cookies and CSRF tokens are never used or accepted for this API (a request carrying only an admin session gets `401`).

## Token
`thk_<12 hex prefix>_<43 char base64url secret>` — the secret is 32 random bytes (256 bits) from `random_bytes`.
- **Stored:** only `SHA-256(token)` and the public prefix. The plaintext exists only in the HTTP response that issues it (no-store headers, not flashed through the session, not logged, not retrievable again).
- **Verified:** lookup by prefix, then `hash_equals` (constant-time); the same work is done whether or not the prefix exists.
- **Expiry:** mandatory. Default 90 days, maximum 365.
- **Specific failure reasons** (`TOKEN_EXPIRED`, `TOKEN_REVOKED`, `AGENT_DISABLED`) are returned only after the secret matched, so someone without the credential only ever sees `INVALID_TOKEN`.

## Lifecycle (all by an administrator with `agents.manage`)
| Action | Effect | Re-authentication |
|---|---|---|
| Register agent | Creates the agent (policy fixed to **draft_only**) and issues the first token | Password re-entry |
| Rotate | Issues a new token and revokes every older one **in the same transaction** | Password re-entry |
| Revoke | All tokens dead immediately | **None** — an emergency stop must be one click |
| Disable / enable | Blocks / restores the agent without touching its tokens | None |
| Edit permissions & limits | Takes effect on the agent's next request | None |
Password confirmation is rate-limited (5 attempts / 10 minutes / administrator) and failures are audit-logged.

## Request protections (in order)
1. HTTPS required in production. 2. Per-IP lockout after 15 failed authentications in 10 minutes. 3. Bearer token. 4. Optional per-agent IP allowlist. 5. Per-agent rate limit. 6. Scope check for the route. 7. Body ≤ 64 KB and `application/json`.
Replay: draft creation is protected by `Idempotency-Key` and by duplicate-content detection (identical caption within 30 days is refused). There is no state-changing endpoint that a replayed request could harm: no publish/approve/schedule exists.

## What is logged (`ai_agent_audit`)
Agent id, token **prefix**, event, method, path (query string dropped), status, IP, request id and a short scrubbed detail. **Never** tokens, headers, request bodies or response bodies. Events: `REQUEST`, `AUTH_FAILED`, `AUTH_LOCKED_OUT`, `PERMISSION_DENIED`, `RATE_LIMITED`, `IP_NOT_ALLOWED`, `DRAFT_CREATED`, `DRAFT_SUBMITTED`, validation/duplicate/state errors, `TOKEN_ISSUED`, `TOKEN_ROTATED`, `TOKEN_REVOKED`, `AGENT_UPDATED`, `AGENT_ENABLED/DISABLED`. Administrator actions are mirrored in the main audit log (`AGENT_REGISTERED`, `AGENT_TOKEN_ROTATED`, `AGENT_TOKEN_REVOKED`, `AGENT_REAUTH_FAILED`, `AGENT_DRAFT_APPROVED/REJECTED/CHANGES_REQUESTED/EDITED`, with the acting administrator).

## Incident response: a token leaked
1. Admin → External AI Agents → the agent → **Revoke immediately** (or Disable agent). Effective on the next request.
2. Review the agent's Activity log for unexpected `DRAFT_CREATED` events; reject any draft you did not expect (nothing can have been published — agents cannot publish).
3. Issue a new token (**Rotate** / **Issue new credential**) and store it only in the provider's secret store.
4. Social platform credentials were never exposed to the agent, so they need no change.

## Not implemented (honest list)
OAuth 2.0 client-credentials / short-lived access tokens, request signing (HMAC), mutual TLS. The bearer token is the only credential today; the controls above (expiry, IP allowlist, scopes, rate limits, instant revocation) compensate. These are candidates for a later milestone.
