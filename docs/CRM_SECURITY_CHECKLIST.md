# CRM Security Checklist

Legend: **Verified** = exercised against the running app (HTTP) or covered by an automated test in this repository · **By design** = implemented in code, reviewed but not independently penetration-tested · **Open** = needs action or an external dependency. This is an engineering checklist, not a third-party security audit.

| # | Control | Status | Evidence / note |
|---|---|---|---|
| 1 | Passwords hashed (bcrypt via `password_hash`), min 10 chars with upper, lower, digit | By design / Verified | `AuthService::hash`, `passwordProblems`; weak password rejected when creating a user (HTTP check) |
| 2 | Generic login error, timing equaliser, no user enumeration on forgot-password | By design | `AuthService::attempt`, `AuthController::forgot` |
| 3 | Account lock after 5 failures for 15 min; login/logout/failed/lock history | By design | `crm_login_history`, shown in *Users & Teams → Login history* |
| 4 | Session regenerated on login; idle timeout (30 min default); account re-checked as *active* on every request | By design | `CrmAuthFilter`, `AuthController::login` |
| 5 | Session cookie `HttpOnly`, `SameSite=Lax`, `Secure` automatically on HTTPS | By design | `app/Config/Cookie.php` |
| 6 | CSRF on every POST | **Verified** | POST without token → HTTP 403; with token → success |
| 7 | Output escaping (`esc()`); e-mail bodies shown in a sandboxed `<iframe srcdoc>` | **Verified** | `<script>` probe in a search box is rendered as `&lt;script&gt;` |
| 8 | SQL injection: query builder / bound parameters only | By design | No string-built SQL from user input in the web layer; filters pass values as bindings |
| 9 | Server-side authorization on every route (`crmperm`) + service re-check | **Verified** | Executive / manager / management get 403 on Users, Settings, Demo, Health, New product; Admin gets 200 |
| 10 | IDOR / row-level scope (own / team / all) | **Verified** (e-mail log) / covered by service tests | `exec1` opening another user's e-mail → 404; `AccessScope` used for every record read |
| 11 | Privilege escalation guards | **Verified** | Admin cannot create a Super Admin; cannot reset a Super Admin's password; cannot deactivate self |
| 12 | Approver cannot approve own discount request (unless policy flag on) | By design + service tests | `NegotiationService::decide` |
| 13 | Minimum price / discount limits enforced on the server | **Verified** (product rule) / service tests | `ProductService::validate`, `QuotationService::price` |
| 14 | File uploads: extension allow-list, size cap, real MIME + image-content check, random file name, stored outside/segregated | **Verified** (rejects non-image) | `ProductController::handleImage` (2 MB; JPG/PNG/WebP). Documents/imports are stored under `writable/crm/` (not web-accessible) |
| 15 | Audit trail append-only; secrets scrubbed from payloads; viewable only with `audit.view` | By design | `AuditService::scrub`; *Audit Trail* page 403 for executives and managers |
| 16 | Settings changes audited; SMTP password never shown or stored in DB | By design | *Settings* shows only "set in .env (hidden)" |
| 17 | Demo reset protected: demo-server flag **and** `demo.enabled` **and** `demo.reset` permission **and** typed phrase; snapshot first; no truncation | **Verified** (wrong phrase refused; executive 403) | `DemoService::reset`, `DemoSeeder::assertDemoEnvironment` |
| 18 | E-mail cannot reach customers from demo data; default `log_only` | **Verified** | Manual e-mail recorded as `queued — NOT TRANSMITTED (email.mode=log_only)` |
| 19 | Security headers (`X-Frame-Options`, `X-Content-Type-Options`, CSP, HSTS on HTTPS) | **Verified** (headers present) | `App\Filters\SecurityHeadersFilter`. The CRM currently inherits the *public-site* CSP (broad: includes `unsafe-inline`/`unsafe-eval`); a stricter CRM-specific policy is recommended |
| 20 | Production error handling: no stack traces | **Open — configure** | Set `CI_ENVIRONMENT=production` (the development setting shows trace JSON, as seen during testing) |
| 21 | IP rate limiting on login / forgot / reset | **Open** | The route filter is attached, but `App\Services\RateLimiterService` **fails open** and logs an error when its table `rate_limit_buckets` is absent. That table is not part of the CRM schema, so IP throttling is inactive until it is created. Account lock-out (control 3) still works |
| 22 | Secrets: `.env` git-ignored; `.env.example` has placeholders only | **Verified** | `.gitignore` (with `!.env.example`) |
| 23 | Demo password documented only in `DEMO_ACCESS.md`; must not exist on production | **Open — operational** | Do not seed demo users on production; delete them if present |
| 24 | Backups encrypted/off-server | **Open — operational** | See [CRM_BACKUP_RECOVERY.md](CRM_BACKUP_RECOVERY.md) |
| 25 | HTTPS enforced | **Open — hosting** | Needs SSL on the domain; verify on *System Health* |
| 26 | Dependency review (`composer audit`) | **Open** | Not run as part of this work |
| 27 | Independent penetration test | **Open** | Not performed |
