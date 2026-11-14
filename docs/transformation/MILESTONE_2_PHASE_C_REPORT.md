# Milestone 2 — Phase C report: External AI Agent API foundation (draft-only)

Date: 2026-11-08 · Built on `b22ea94`. **Nothing pushed, deployed, or applied to the SQL-dump dev database, staging or production.** All tests and verification ran on the isolated `talenthub_test` database. Phase D (TinyMCE key, Tailwind CDN, security review) is not started.

## What was built
| Area | Delivered |
|---|---|
| Registry | `ai_agents` (+ UI: list, register, detail, permission matrix, limits, IP allowlist, enable/disable). Labels ChatGPT / Claude / TalentHub Internal Automation / Custom — labels only, no provider connection. |
| Credentials | `ai_agent_tokens`: `thk_<prefix>_<256-bit secret>`, SHA-256 hash only, constant-time compare, mandatory expiry (≤365 d), rotate (atomic, old token dies at once), revoke (one click), password re-entry for issue/rotate. Shown once, never stored/logged/flashed. |
| API | `/api/v1/integrations/agents/`: `capabilities`, `social/accounts`, `social/content-guidelines`, `social/templates`, `social/calendar`, `social/analytics`, `POST social/posts/drafts`, `GET social/posts/{id}`, `POST social/posts/{id}/submit-approval`. Consistent envelope, request ids, `no-store`, OpenAPI spec. |
| Protections | HTTPS (production), per-IP failed-auth lockout, optional IP allowlist, per-agent rate limit, per-agent daily draft cap, closed scope whitelist, 64 KB / JSON-only, strict field whitelist, idempotency keys, duplicate-content refusal, own-drafts-only (IDOR → 404). |
| Draft rules | Platform limits (LinkedIn 3000 / Instagram 2200, ≤30 hashtags, Instagram needs media), plain text only, https destination on talenthub domains only, media = approved library ids only (no paths/URLs), proposed time is a suggestion, status always `draft`, origin `agent`, no account choice. |
| Approval | New screens: AI Draft Approvals + review page (edit captions/hashtags/CTA/URL/media, approve, reject, request changes — reasons mandatory for the last two); approver id + time recorded; editing approved content voids the approval and withdraws queued jobs. Agent-origin chips on the existing Posts screens. |
| Gate | `SocialPublishingEngine::publishPost()` and the reschedule action refuse agent content without `review_decision='approved'` **and** a recorded approver — so even the existing "Publish now" button, the scheduler and retries cannot bypass approval. |
| Audit | `ai_agent_audit` (events incl. failed auth, lockout, permission denied, rate limit, draft created/submitted, rotate, revoke) + admin actions in `audit_logs`. No tokens, headers or bodies. |
| Side fix | `renderAdminView()` no longer leaks one page's content into the next layout within a process (`saveData=false`). |

## Migration (additive only)
`20261112000001_ai_agent_foundation`: 4 new tables, 8 nullable/defaulted columns + 2 indexes on `social_posts`, 2 new values appended to the `social_notifications.event_type` ENUM, 3 permissions (`agents.view`, `agents.manage`, `social.approve`). Nothing dropped/rewritten; `down()` is a no-op. See `AGENT_DEPLOYMENT_NOTES.md` — apply with a backup, **not** via `spark migrate`.

## Tests
- PHPUnit: **268 tests, 1955 assertions, all passing** (222 before + 31 `AgentApiTest` + 15 `AgentsAdminTest`), default order and three random seeds. Pinned to `talenthub_test` (guard line printed each run).
- Real HTTP + Edge: **87/87** agent checks (`AGENT_E2E_RESULTS.txt`); sidebar 33/33 and System 61/61 re-run after the layout changes.
- **Pre-migration safety:** with no agent tables and no new columns (pure old schema) all **222 existing tests pass**; over HTTP the API answers `503 NOT_CONFIGURED`, `/admin`, social, queue, calendar and system pages return 200 and create nothing; visiting `/admin/agents` applies the migration additively; all pre-existing posts stay `origin=admin`.
- Dev database after everything: 93 tables, no agent tables, no `origin` column (verified).

## Security tests covered
Auth (missing/malformed/wrong/expired/revoked/disabled/rotated), uniform generic errors, hash-only storage, token absent from every log/DB table/session/page, IP allowlist, brute-force lockout, scope-per-endpoint, rate limits (per agent, independent), no publish/schedule/approve/edit routes, field smuggling (`status`, `approved_by`, `scheduled_at`, …), IDOR (other agents' and admin posts), SQL-injection/XSS in drafts, SSRF attempts (metadata URL as media, off-domain destination, credentialed/odd-port URLs), oversized/invalid/wrong-type bodies, duplicate + idempotent retries, daily limit, engine gate in every status, approval needs `social.approve` (not `agents.manage`/`social.publish`), password re-auth (+ rate limit), CSRF on admin mutations, view-only users, admin sessions rejected by the API.

## Findings
1. **Fixed while testing:** a limit of `0` was silently replaced by the default (now rejected); a stale "approved" note survived a voided approval (now cleared); the pending-count query could error on a not-yet-migrated schema (now guarded); the reschedule guard needed the same protection.
2. **Carried over from Phase B (still open):** `isSuperAdmin()` grants Super Admin to `id === 1` / `username === 'admin'` regardless of roles; `HostingerMailService::getAccountInfo()` `$msgUid` bug.
3. **Pre-existing test fragility (not changed):** `SocialEnquiryAndAnalyticsTest` builds a random phone with `str_shuffle` (PHPUnit seeds it) and never cleans up, so re-running the *same* random seed can collide with its own earlier row.
4. Hosted AI services use changing egress IPs, so the IP allowlist is optional and usually left empty.

## Not done / limitations (honest)
- **No live ChatGPT or Claude integration was configured or tested**; the integration guides describe options only. **No MCP adapter** exists.
- Not implemented: OAuth 2.0 client-credentials / short-lived tokens, HMAC request signing, "approval-required scheduling" and "pre-authorised automation" modes (publishing by agents is deliberately out of scope), agent media upload (media must already be in the approved library), editing a draft via the API.
- The analytics endpoint returns aggregates from local data; no live platform call is made by the agent API.
- Edge only; no TLS/hosting verification; the migration has **not** been run on the real dev/staging/production databases.
- After approval, scheduling/publishing uses the existing Social screens; that final step was never exercised against LinkedIn/Instagram (no platform calls were made in testing).

## Files
New: `app/Services/Agent/{AgentScopes,AgentTokenService,AgentAuditLogger,AgentDraftValidator,AgentSocialService,AgentApprovalService,AgentApiException,AgentSchema}.php`, `app/Filters/AgentAuthFilter.php`, `app/Controllers/Api/AgentApiController.php`, `app/Controllers/Admin/AgentsController.php`, `app/Database/Migrations/20261112000001_ai_agent_foundation.php`, `app/Views/admin/agents/{_ui,index,create,show,token_issued,approvals,review}.php`, `tests/unit/{AgentApiTest,AgentsAdminTest}.php`, docs (`EXTERNAL_AGENT_API.md`, `agent-openapi.yaml`, `AGENT_AUTHENTICATION_GUIDE.md`, `AGENT_PERMISSION_MATRIX.md`, `CHATGPT_INTEGRATION_GUIDE.md`, `CLAUDE_INTEGRATION_GUIDE.md`, `AGENT_DEPLOYMENT_NOTES.md`, `AGENT_E2E_RESULTS.txt`, this report).
Modified: `app/Config/{Filters,Routes}.php`, `app/Controllers/Admin/{AdminBaseController,SocialMarketingController}.php`, `app/Services/Social/SocialPublishingEngine.php`, `app/Views/admin/layouts/_sidebar_nav.php`, `app/Views/admin/social/posts/{index,show}.php`.
