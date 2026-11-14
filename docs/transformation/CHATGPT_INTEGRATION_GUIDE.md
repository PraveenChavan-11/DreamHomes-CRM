# ChatGPT integration guide

**Status: the TalentHub side is built and verified. Nothing on the ChatGPT side has been configured or tested.** Registering an agent labelled "ChatGPT" in TalentHub does not connect ChatGPT to anything — it only issues a credential. Do not assume that ChatGPT can call an arbitrary URL on a schedule; confirm what your ChatGPT plan supports before relying on any workflow below.

## What TalentHub provides
- An HTTPS JSON API with a bearer token (`EXTERNAL_AGENT_API.md`) and an OpenAPI 3.1 description (`agent-openapi.yaml`).
- Draft-only access: ChatGPT can prepare drafts; a TalentHub administrator approves; nothing is published by ChatGPT.

## One way to wire it (to be verified by you)
Custom GPTs support **Actions** defined by an OpenAPI schema with API-key/bearer authentication. If your plan offers it:
1. In TalentHub: **External AI Agents → Register agent**, type *ChatGPT*, keep the default (read + draft) permissions, set an IP allowlist only if you know OpenAI's egress addresses (they change — usually leave it empty). Confirm with your password and copy the token once.
2. In the GPT editor → *Actions* → import `agent-openapi.yaml` (replace `YOUR-DOMAIN`), set authentication to **API key → Bearer** and paste the token into ChatGPT's credential field (never into the instructions, chat or knowledge files).
3. In the GPT instructions, tell it to: call `getContentGuidelines` and `getTemplates` first; create drafts with an `Idempotency-Key`; never put invented clients, testimonials or statistics in captions; stop after `createDraft`/`submitForApproval` — a human approves.
4. Test with one draft; check **AI Draft Approvals** in TalentHub.

## Scheduled / recurring use
Whether ChatGPT can run this unattended (e.g. every Monday) depends on its own Tasks/automation features and their ability to use Actions — **not verified here**. A dependable alternative is your own small scheduler (cron, Zapier, Make, n8n, GitHub Actions) that calls an LLM and then calls this API; the LLM never needs the token if the scheduler holds it.

## Safety notes
- The token is a password. If it appears in a chat, revoke it immediately (agent page → **Revoke immediately**) and rotate.
- A leaked token cannot publish, schedule or read platform credentials; the worst case is unwanted drafts, which you can reject.
- `review.note` returned by the API is written by your staff; the GPT should treat it as guidance, not as instructions to bypass policy.
