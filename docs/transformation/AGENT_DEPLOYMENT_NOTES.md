# Agent API — deployment notes (staging / production)

**Nothing in this milestone was applied to the SQL-dump-based dev database, staging or production.** All tests and browser/HTTP verification ran on the isolated `talenthub_test` database.

## Schema change (additive only)
`App\Database\Migrations\AiAgentFoundation::apply()` (file `20261112000001_ai_agent_foundation.php`):
- creates `ai_agents`, `ai_agent_tokens`, `ai_agent_audit`, `ai_agent_idempotency` (`CREATE TABLE IF NOT EXISTS`);
- adds **nullable/defaulted** columns to `social_posts`: `origin` (default `admin`), `agent_id`, `content_hash`, `proposed_at`, `review_decision`, `review_note`, `reviewed_by`, `reviewed_at`, plus two indexes — existing rows are untouched (`origin='admin'`);
- appends two values (`agent_draft_created`, `agent_draft_submitted`) to the `social_notifications.event_type` ENUM (existing values re-listed verbatim);
- inserts permissions `agents.view`, `agents.manage`, `social.approve` for `super_admin` (`INSERT IGNORE`).
Nothing is dropped, truncated, renamed or rewritten, and `down()` is intentionally a no-op (the tables hold audit evidence).

## How to apply (do NOT use `php spark migrate` on a dump-built database)
1. **Back up first:** `mysqldump --single-transaction --routines --triggers <db> > backup.sql`, and confirm it restores.
2. Apply once, in a quiet moment, by either: opening **Admin → External AI Agents** as a Super Admin (the page calls `AgentSchema::ensure()`, which is idempotent), or running the equivalent SQL manually.
3. Verify: `SHOW TABLES LIKE 'ai_agent%'`; `SHOW COLUMNS FROM social_posts LIKE 'origin'`.
Until step 2 runs, the API answers `503 NOT_CONFIGURED`, the publishing engine's new gate is inert (`origin` absent ⇒ treated as `admin`), and no existing behaviour changes.

## Hosting checklist
- Serve over **HTTPS** only (the API refuses plain HTTP when `ENVIRONMENT=production`; behind a proxy ensure `X-Forwarded-Proto` is set).
- Keep staging and production tokens/agents separate; staging is sandboxed for email and must not publish.
- `encryption.key` is unrelated to agent tokens (they are hashed, not encrypted), but is required for social platform credentials.
- Rate limits use the existing `rate_limit_buckets` table; make sure it exists (it does on current schemas).
- Do not put agent tokens in repositories, `.env` files that are committed, tickets or chat.

## Rollback
Disable or revoke agents in the admin UI (instant). To remove the feature entirely, stop calling the routes; the new columns/tables can stay (they are inert). Do not drop them without a backup — they contain the approval history.
