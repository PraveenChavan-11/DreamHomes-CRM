# Agent Permission Matrix

## Agent scopes (closed whitelist — `App\Services\Agent\AgentScopes`)
| Scope | Endpoint | Default | Notes |
|---|---|:---:|---|
| `social.accounts.read` | `GET social/accounts` | ✔ | status only; no tokens/IDs/URLs |
| `social.guidelines.read` | `GET social/content-guidelines` | ✔ | |
| `social.templates.read` | `GET social/templates` | ✔ | active templates |
| `social.drafts.create` | `POST social/posts/drafts` | ✔ | draft only, origin `agent` |
| `social.drafts.submit` | `POST social/posts/{id}/submit-approval` | ✔ | own draft → `pending_approval`; asks for review, does not approve |
| `social.posts.read` | `GET social/posts/{id}` | ✔ | own drafts only |
| `social.calendar.read` | `GET social/calendar` | ✔ | |
| `social.analytics.read` | `GET social/analytics` | ✔ | aggregates only |

**Never grantable** (no such scope exists; unknown names are silently dropped on save): publish, schedule, approve, edit after creation, choose account, upload media, platform credentials, account configuration, user/admin management, database access.

Policy modes: only **Draft Only** exists in this milestone. "Approval required" scheduling and "pre-authorised automation" are not implemented.

## Administrator permissions (new)
| Permission | Allows | Default holder |
|---|---|---|
| `agents.view` | See the registry, an agent's permissions, activity and drafts | Super Admin |
| `agents.manage` | Register, edit scopes/limits, enable/disable, rotate (password required), revoke | Super Admin |
| `social.approve` | Review, edit, approve, reject, request changes on agent drafts | Super Admin |
`agents.manage` or `social.publish` do **not** imply `social.approve`. Other roles receive these only if an administrator assigns them.

## Who can do what to an agent draft
| Action | Agent | Human with `social.approve` | Human with `social.publish` only |
|---|:---:|:---:|:---:|
| Create draft | ✔ | – | – |
| Submit for review | ✔ (own) | – | – |
| Edit content | ✘ | ✔ (editing approved content voids the approval) | ✘ |
| Approve / reject / request changes | ✘ | ✔ (reason required to reject/request) | ✘ |
| Schedule / publish | ✘ | via normal Social screens, **only after approval** | refused by the engine until approved |
Enforced in code at three layers: the API (no such endpoints/scopes), the review service (atomic status-conditional updates), and `SocialPublishingEngine::publishPost()` + the reschedule action (agent content needs `review_decision='approved'` and a recorded approver).
