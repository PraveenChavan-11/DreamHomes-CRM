# Security remediation report — Milestone 2 (Phase D)

Date: 2026-11-08 · Scope: preliminary review + fixes of the TalentHub admin and public surfaces. **Not a penetration test.** Everything was verified locally on an isolated database (`talenthub_test`); nothing was deployed, and no live system was touched.

## Summary
| # | Finding | Severity | Status |
|---|---|---|---|
| S1 | **Broken access control — REST admin API** (`/api/v1/admin/*`): any signed-in staff account (even with zero permissions) could read users, roles, audit logs, settings, mail, applications, resume bank and exports, and call writing endpoints (`PUT settings`, `POST users`, SMTP test…) | **Critical** | **Fixed** (central fail-closed permission filter) |
| S2 | **Broken access control — Social module** (~43 routes incl. publish, delete, export enquiries, **LinkedIn/Meta credential settings**, OAuth connect/disconnect) and 13 Engagement routes (edit/delete announcements, **send campaign broadcasts**) required only a login | **Critical** | **Fixed** (fail-closed `_remap` guards) |
| S3 | Hidden "user id 1 / username `admin` is Super Admin" shortcut that bypassed roles | High | **Fixed** (role-only; System Health warns about lockout) |
| S4 | Admin media upload accepted **SVG** (script execution when opened from `/uploads/`), trusted a guessed extension, no size/pixel cap | High | **Fixed** (`SafeImageUpload`) |
| S5 | Raw exception text returned to visitors (JSON and URL) on four public forms | Medium | **Fixed** |
| S6 | "Submission failed" page displayed any `?reason=` text (content spoofing / phishing on the official domain); `return_url` accepted off-site URLs | Medium | **Fixed** (fixed reason codes; same-site URLs only) |
| S7 | Unauthenticated REST POSTs without rate limits (admin forgot/reset password via REST, newsletter token endpoints, cookie consent, mail webhook) | Medium | **Fixed** |
| S8 | Admin error flashes could echo raw exception/provider text | Low | **Fixed** (scrubbed) |
| S9 | Tailwind runtime CDN on every admin page: ~400 KB third-party script, runtime `eval`, forces `unsafe-eval` in the CSP | Medium | **Fixed** — see "Tailwind" |
| S10 | TinyMCE Tiny Cloud key hard-coded in a view | Low (public identifier) | **Fixed** — see "TinyMCE" |
| **S13** | **Personal data and password hashes committed to git**: `deployment/clean_database_import.sql` and `deployment/database_hostinger_export.sql` are tracked and contain `admin_users` rows (bcrypt hashes for `admin` and `hr`) and `applications` / `candidates` rows. The remote is `github.com/PraveenChavan-11/TalentHub` | **High (privacy)** | **NOT changed — needs your decision** (see below) |
| S11 | `settings/test-smtp` lets an authorised admin make the server open an SMTP connection to a host of their choice | Low (needs `settings.manage`) | **Documented** — admin-only; now permission-gated |
| S12 | `api/cron/run?key=…` puts the secret in the query string (appears in access logs) | Low | **Documented** — use the `X-Cron-Key` header; disabled unless `CRON_SECRET_KEY` is set |
| — | Session cookie `Secure` flag | — | **False positive** — already set dynamically when the request is HTTPS (or in production) |

## Method
- Static scan of every routed admin handler for a permission check, then a **dynamic route-wide test** (`AdminAccessControlTest`): every `admin/*` and `api/v1/admin/*` route (270) is called as a signed-in user with no permissions; anything but a refusal fails, apart from an explicit self-service allow-list (own dashboard/profile/notifications/logout).
- Reading of every upload path, every outbound HTTP client, every place exception text reaches a response, cookie/session/CSP configuration, public POST rate-limit coverage.
- Verification of the agent API and health pages as built earlier in this milestone (see their own reports).

## S1/S2 — how access control now works
- **REST admin API:** `AdminApiPermissionFilter` (alias `apiperm`) runs after `auth`. Ordered rules map (method, path) → "any of" permissions that mirror what the equivalent web screens require. **A route that matches no rule is refused**, so a new endpoint is locked until someone maps it. Open to any signed-in admin: own profile, session, logout, dashboard stats, own notifications. Denials are audit-logged (`API_PERMISSION_DENIED`, path + user only).
- **Social/OAuth controllers:** trait `GuardsAdminPermissions` provides `_remap()` with a per-method permission map, **fail-closed** (a handler without an entry is refused for everyone but Super Admin; a reflection test enforces that every public handler has an entry). `social.view|create|publish|manage|settings|enquiries|analytics` are now separated: viewing ≠ publishing ≠ deleting ≠ credentials. The composer lets `social.create` save drafts but needs `social.publish` to publish/schedule.
- **Engagement:** the 13 unguarded handlers now require `engagement.manage`, `newsletter.manage` or `campaigns.send`, consistent with their siblings.
- **Sidebar** items now require the same permissions the server enforces, so no one sees links that only bounce them.
- Tests: route-wide sweep, positive/negative permission matrix, privilege-escalation attempts through `POST/PUT /api/v1/admin/users` (no account or role created), settings-unchanged snapshot, default-deny for unmapped paths, reflection check.

**Operational impact to expect:** staff whose roles lacked the relevant permission lose access they should never have had. In the current role data only Super Admin holds `social.*`; the Recruiter/HR roles therefore no longer reach the Social module. Grant `social.*` permissions to a marketing role if needed (Admin → Roles & Permissions).

## S3 — Super Admin shortcut
`AdminBaseController::isSuperAdmin()` returned true for user id 1 or username `admin` regardless of roles. Removed. **Before deploying, confirm on each environment that the owner account holds the `super_admin` role** (`SELECT u.id,u.username,r.slug FROM admin_users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id`). System Health → "Admin Access" turns **Failed** if no active Super Admin exists and **Warning** if account id 1 / `admin` lacks the role, so a lockout is visible rather than silent.

## S4 — uploads
Reviewed: candidate resumes (`DocumentVaultService`: detected MIME via finfo, 5 MB cap, random name, stored under `writable/`), product/case-study images (`ProductService::validateImageFile`: finfo + decode + dimension caps + blocked names + `.htaccess` hardening), social media (`SocialSecurityService::validateMediaFile`), agent API (no uploads; library IDs only). **Fixed:** admin CMS media (`CmsApiController::uploadMedia`) → `SafeImageUpload`: detected type must be JPEG/PNG/WebP/GIF *and* decode as that type, extension derived from the detected type, SVG/script/double-extension names refused, 5 MB and 8000 px caps. `public/uploads/.htaccess` already disables script handlers.

## SSRF
Every outbound client (LinkedIn, Instagram, Hostinger Mail, Turnstile) uses a fixed vendor base URL, TLS verification on, timeouts, and **does not follow redirects**; a test pins this. The agent API and image validators perform no network I/O (tested). Admin-only exception: S11.

## Rate limiting
All public `api/*` write routes now carry a `ratelimit` filter or are explicitly exempt (admin API behind session+RBAC; signed WorkSphere webhook; bearer agent API with its own per-agent limits and failed-auth lockout). A test fails if a new public write route is added without one.

## Error handling
`display_errors` is `0` in production boot. Public controllers now log the exception and return a generic message; a source-scan test fails if a controller sends `$e->getMessage()` to a client (admin flows pass it through `JobRunRecorder::sanitize`, which strips tokens, secrets and server paths).

## S9 — Tailwind runtime CDN removed
**Before:** every admin page loaded `https://cdn.tailwindcss.com` (Tailwind 3.4.17, a 407 KB script that compiles CSS in the visitor's browser). It required `'unsafe-eval'` in the Content-Security-Policy and made the admin depend on a third-party host on every request.
**Now:** a pre-built, minified `public/assets/css/admin-tailwind.css` (**61 KB**), built with the *same pinned version* and the *same colour/font config* the layout used (`tools/tailwind/`, README included). Hosting needs no Node — the built file is committed. The standalone executive report and the offline page use it too (the offline page previously needed the CDN precisely when the network was down; the stylesheet is now precached and the service-worker cache version was bumped).
**CSP:** the admin area now has its own policy — **no `'unsafe-eval'`**, no Tailwind CDN, no Tag Manager/Tawk/Turnstile; only Google Fonts, Chart.js (jsdelivr) and Tiny Cloud. The public policy keeps its integrations but no longer lists the Tailwind host. `'unsafe-inline'` remains for scripts because the admin views contain inline scripts (moving to nonces is a larger, separate change).
**Lazy loading:** Chart.js and TinyMCE are now loaded only on pages that use them.

**Verification (not assumed):** the CDN-rendered pages and the new pages were captured back-to-back on identical data in Edge, 66 admin routes × desktop (1366 px) and mobile (390 px):
| Check | Result |
|---|---|
| Class coverage: CDN-generated class names (incl. `hover:`/`md:` variants) missing from the built CSS | **0 of 1,021** |
| Computed style of every element (51,006 elements, 102 page-viewports compared in both runs) | **30 differ**, all explained: time/data-dependent text on System Health and Social Settings, a 1/255 rounding difference in a semi-transparent border colour, and the asynchronously loaded Tiny Cloud editor shell |
| CSP violations on 132 page loads under the strict policy | **0** |
| Horizontal overflow | none on any page, either width |
| PHPUnit (`AdminFrontendAssetsTest`, 9 tests) | pass |
| Cold-cache load, median of 7 per page (5 pages, localhost): first contentful paint | **1,610 ms → 759 ms (−53%)** |
| Load event | 2,285 ms → 1,116 ms (−51%) |
| Main-thread time / script execution / style recalculation | −49% / −50% / −60% |
| Data transferred | 1,846 KB → 1,102 KB (−40%) |
| Requests / third-party hosts per page | 17.4 → 15.0 / 5.0 → 2.6 |
(Localhost timings against the real CDN; absolute numbers will differ on production hosting, the direction and rough size will not.)

**Bugs found by this verification and fixed:** my first lazy-loading attempt consumed CodeIgniter's `content` section (`renderSection()` returns *and removes* it unless `saveData=true`), blanking every social page. Existing tests caught it; the cause is now pinned by a regression test (content appears exactly once, inside `<body>`).

**Maintenance note:** after adding Tailwind classes in admin views run `npm run build` in `tools/tailwind` and commit the CSS. Class names must be complete literal strings (no `'bg-' + colour`).

## S10 — TinyMCE Cloud key
The Tiny Cloud API key is a **public client-side identifier** (it must appear in the page that loads the editor), not a secret; its protection is the **Approved Domains** list in the Tiny dashboard. It was hard-coded in `layouts/main.php`. Now: `app/Config/Editor.php` reads `editor.tinymceApiKey` from `.env` (templates `env` / `env.staging` document it), validates the format, and builds the script URL; with no key the script isn't loaded and the fields remain plain text areas (the pages already guard `typeof tinymce`). The local `.env` was given the existing key so your development editor keeps working. A test fails if a Tiny key ever reappears in `app/`.
Verified in Edge: TinyMCE 7.9.4 initialises (editor iframe + toolbar) on the job form, mail composer and mail-template editor under the strict CSP with no violations, and does **not** load on pages without an editor.
**Action needed from you (cannot be done from code):** (1) add `editor.tinymceApiKey = …` to the production and staging `.env`; until then the editor degrades to plain text areas there. (2) The key is in git history (since commit `8a28c71`) and on the public GitHub remote. Open the Tiny dashboard and make sure **Approved Domains** lists only your real domains (so the key is useless elsewhere), or rotate it and put the new key only in `.env`.

## S13 — personal data and password hashes in the repository (needs your decision)
Found while checking that the Tiny key was not in anything I was about to commit. Two tracked SQL dumps under `deployment/` contain real-looking data, not just schema:
- `admin_users`: ids 1 and 2 (`admin@…`, `hr@…`) with **bcrypt password hashes**, phone numbers and last-login times;
- `applications` and `candidates`: job-applicant records (names, contact details, CV references).
They have been in history since commit `fdc82f2`. I do **not** know whether the GitHub repository is public or private — please check. **I changed nothing here**: removing them from history means rewriting git history (destructive; force-pushing is against the project rules) and the dumps may be used by `deployment/hostinger_deploy.sh`.
Recommended order: (1) check the repo's visibility; (2) change the `admin` and `hr` passwords everywhere they are used (hashes in a public repo can be attacked offline); (3) replace the dumps with schema-only or synthetic-data files and stop tracking real ones (`git rm --cached`, add to `.gitignore`); (4) if the repo is or was public, decide with legal/compliance whether applicant data exposure needs handling under your privacy obligations, and purge history (e.g. `git filter-repo`) once you have approved a rewrite and coordinated with anyone who has clones. `scratch/` also holds ad-hoc exports (`roles_and_perms.json`, schema dumps) worth reviewing for the same reason.

## Dependency check
- `composer audit`: **no advisories** (run 2026-11-09).
- `npm audit` for the build-time Tailwind tooling (`tools/tailwind`, 72 packages, never deployed): **0 vulnerabilities**.

## Not covered (be aware)
Not a penetration test; no authenticated scanning tool or fuzzing was run. Public-site CSP still allows `'unsafe-inline'` and `'unsafe-eval'` for Tag Manager/Tawk. Admin CSP still allows `'unsafe-inline'` scripts. No review of the hosting layer (TLS, headers set by the web server, file permissions, backups). The REST admin API permission map mirrors current web-screen permissions; revisit it whenever roles change. Session id rotation can log a user out when two requests overlap during the rotation (observed during testing with a slow page); it is an availability nuisance, not a security gap.
