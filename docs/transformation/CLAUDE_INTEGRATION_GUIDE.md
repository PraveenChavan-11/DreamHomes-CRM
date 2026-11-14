# Claude integration guide

**Status: the TalentHub side is built and verified. Nothing on the Claude side has been configured or tested, and no MCP connector exists yet.** Registering an agent labelled "Claude" only issues a credential.

## What TalentHub provides
HTTPS JSON API + OpenAPI description (`EXTERNAL_AGENT_API.md`, `agent-openapi.yaml`); draft-only; mandatory human approval.

## Options (choose and verify on the Claude side)
1. **Your own automation + the Claude API (most controllable).** A script/scheduler (cron, n8n, GitHub Actions…) holds the TalentHub token, asks Claude to write captions (giving it the guidelines/templates fetched from `GET social/content-guidelines` and `GET social/templates`), then calls `POST social/posts/drafts` itself. Claude never sees the token. Define Claude "tools" (`create_draft`, `get_guidelines`, …) that your script executes, and pass only the fields in the draft schema.
2. **MCP connector (not built).** The brief asks for an MCP-compatible adapter. None exists in this milestone. It would be a thin server exposing exactly the nine endpoints above as tools and holding the token server-side. If you want it, it should be a separate small project that talks to this public API only — never to the database — and is reviewed like any other credential holder.
3. **Claude with tool access inside a product that supports custom connectors.** Possible only if that product can reach an HTTPS endpoint you control and keep the token in its secret store — confirm in its documentation; not verified here.

## Setup in TalentHub (same for any option)
Register agent → type *Claude* → default permissions → confirm password → copy the token once → store it in the automation's secret store → test with one draft and review it under **AI Draft Approvals**.

## Suggested weekly workflow (human in the loop)
Monday: fetch guidelines + approved templates → generate five ideas → for each idea create a draft with an `Idempotency-Key` (`weekly-<year>-w<week>-<n>`), optionally `submit-approval` → a TalentHub administrator reviews/edits/approves → the administrator schedules or publishes from the Social screens → later `GET social/analytics` for a summary. The automation cannot do the approval or publishing steps — by design.
