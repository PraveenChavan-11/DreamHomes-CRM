# Local dev database incident — 2026-11-08 (final report)

**Scope: local XAMPP database `talenthub_db` only. Staging and production were never touched.**

## What happened
1. I ran `php spark migrate` on the local dev database. That database was built from the SQL dumps in `deployment/`, not migrations, so
   its `migrations` table held only the test-support row. `migrate` recorded all app migrations.
2. `phpunit` used `Config\Database::$tests['database'] = 'talenthub_db'` (the same database). `DatabaseTestTrait` rolled back every recorded
   migration, executing their `down()` methods and dropping tables. Runtime `ensureSchema()`/`apply()` helpers then recreated some of them
   lazily — empty, and (for two tables) in an outdated shape. The run also cleared the `migrations` table, so no repeat rollback occurred.
3. Separately I ran `php spark outbox:process 0`. All 1,245 rows were handled by the staging email sandbox (`[SANDBOXED]`): no real email was sent; local rows flipped PENDING → SENT.

Root cause chain: my `migrate` on a dump-built DB + test config that shared the dev DB. Both are now closed (see Safeguards).

## Recovery performed (with your conditional approval)
Before any repair: full backup `D:\Personal\_db_backups\talenthub_db_pre_repair_20261108_203415.sql` (93 tables), plus
`affected_tables_20261108_203415.sql` (both affected tables). Restorability was proven by loading a dump into `talenthub_test` (93 tables; row counts matched).

| Table | Pre-repair | Action | After |
|---|---|---|---|
| 6 mail tables (`mailbox_connections`, `mail_message_index`, `mail_local_drafts`, `mail_recruitment_links`, `mail_webhook_events`, `mail_activity_logs`) | missing | `php spark mail:setup-tables` (project's own idempotent command) | created; default mailboxes re-seeded; otherwise empty |
| `email_campaigns` | 0 rows, no FKs, no triggers. Columns `name, preheader, content_html, content_text, target_*, opened_count, clicked_count` (no `delivered_count`) | `DROP` + `CREATE` from `deployment/migration_20261101_engagement_centre.sql` | 0 rows; columns `title, preview_text, body_html, body_text, audience_filter, template_id, delivered_count, created_by…` |
| `newsletter_activity_logs` | 0 rows. `metadata` JSON with `json_valid` CHECK, extra `ip_hash` | same | 0 rows; `metadata TEXT`, FK `fk_newsletter_act_subscriber → newsletter_subscribers(id) ON DELETE CASCADE` |

Schema check against the application (condition 5): `EmailCampaignModel::$allowedFields` and `EmailCampaignService` use exactly the SQL file's column names
(`title, preview_text, body_html, body_text, audience_filter, delivered_count, created_by, template_id`); `NewsletterActivityLogModel` writes `metadata` as a string (`''` when null,
which a JSON CHECK rejects) and never uses `ip_hash`. **The SQL file matches the application; the table shapes that existed were the stale ones.**
No other table was dropped, truncated or recreated by the repair. Dev table count: 93 before, 93 after.

**Latent bug fixed in code:** `DatabaseSchemaService::ensureSchema()` contained those same stale definitions and would have recreated the wrong shapes whenever a table was missing.
Its two definitions now match the SQL file/models.

## Data-loss investigation (evidence, not assumption)
| Observation | Evidence | Conclusion |
|---|---|---|
| `social_posts` 98 → 24 | All 24 survivors are the 4 titles the social tests create (`Test LinkedIn Hiring Post`, `Already Published Post`, `Scheduled Due Post`, `Future Post`; 6 each), all created 14:51–14:58 after the drop. The social tests do not clean up after themselves. The queue page earlier showed only these titles. 98 is not a multiple of 4, so **2 of the lost rows are unaccounted for**. | Almost certainly test residue. Cannot prove the 2 odd rows. |
| `social_*` other tables fell by a similar ratio | Same fixtures (jobs, attempts, logs, notifications, enquiries). Social tables appear in **no** deployment dump. | Not recoverable from dumps; consistent with test residue. |
| `crm_integration_clients` 1 → 0 | `encryption.key` is **not configured** in the local `.env` (log: "WorkSphere receiver disabled: encryption.key is not configured"); `IntegrationClientService::createOrRotate` refuses to work without it. The only code paths that create clients locally are `WorksphereIntegrationTest` (`wstest-<hex>` key ids, random per-run encryption key) and `CrmLeadLifecycleTest`. | The row was a test client with an unusable secret. A real WorkSphere client could not have been created on this machine. High confidence, not proof. |
| `it_leads` (now 18 rows) | All 18 are test fixtures (`.invalid`/example emails) created 14:56–14:58 after the drop. Pre-drop count was never recorded. | Pre-drop contents **unknown**; deployment dumps contain no `it_leads` rows. Cannot establish whether any real lead existed. |
| `products` (now 3 rows) | `societyhub`, `peoplehub`, `worksphere` all created 14:56:12–13 = code-seeded defaults. Dumps hold no `products` rows. | Seed data regenerated. Any admin edits to product copy before the drop are **lost and unrecoverable**. |
| Mail tables | `mail_message_index` held demo data (the project has `mail:seed-messages`; dumps contain 3 INSERT statements). | Demo data only; **not** re-seeded automatically (it is fabricated sample mail). Run `php spark mail:seed-messages` if wanted. |

**What can be recovered:** schema (done), seed rows (done by the app). **What cannot:** any hand-entered product edits, any real `it_leads`/social data that might have existed. There is no backup newer than `deployment/*.sql` (Nov 1–3), which predate those tables. Nothing was restored or fabricated.

## The two `CrmLeadLifecycleTest` errors — root cause found and reproduced
`testSubjectIsHeaderInjectionSafe` and `testRecipientSettingIsValidatedAndUsed` errored with "array offset on null" because `CrmNotificationService::notify()` returned null.
**Cause:** `notify()` builds an idempotency key `crm-L<leadId>-<event>-…` and returns null ("already queued") if a row with that key exists in `email_outbox`.
The incident dropped and recreated `it_leads`, so its AUTO_INCREMENT restarted at 1, while `email_outbox` kept ~180 stale `crm-L<id>-…` keys from earlier days. New test leads reused old ids,
collided with the stale keys, and `notify()` correctly (by its own contract) returned null. Each test's `tearDown` deletes the keys for its own lead id, so the collisions "healed" themselves one id at a time — which is why the failures looked
deterministic for two reruns and then vanished, and why earlier reconstructions (which lacked the stale keys) passed.

**Reproduction (isolated `talenthub_test`):** emptied `it_leads`, reset `AUTO_INCREMENT=1`, planted `crm-L1…L59` keys in `email_outbox` → the same two errors (plus the first test) appeared.
**Fix:** `CrmLeadLifecycleTest::setUp` now deletes *orphaned* `crm-L<id>-…` keys (keys whose lead does not exist). Orphans belong to no lead, so this cannot hide a real defect; no assertion was changed, skipped or weakened.
Re-running the identical hostile scenario: 17/17 pass. Production is not affected (lead ids never repeat there); this only mattered for a reset database.

The other four errors (3 × newsletter JSON `metadata`, 1 × `delivered_count`) were also reproduced in `talenthub_test` (by registering the app migrations and running the suite → 13 errors including those four) and are fixed by the table repair.

## Additional side effects of my own runs on the dev database (disclosed)
While verifying Phase B I assumed environment variables would redirect the CLI to `talenthub_test`; they cannot (CodeIgniter's loader ignores real env for dotted keys), so four cron commands ran against `talenthub_db`:
- 4 rows added to `scheduled_job_runs` (genuine heartbeats of real runs) — kept as evidence.
- 26 `email_outbox` rows flipped PENDING → SENT, all `[SANDBOXED]` (verified 26/26): no real email left the machine.
- `social:publish` processed 0 jobs; `mail:sync` stopped at a pre-existing bug before any network call (non-production returns early).
- A row-count diff of every table against the post-repair snapshot showed no other change.
The runs were then redone correctly against `talenthub_test`, after proving the target with `SELECT DATABASE()`.

## Safeguards now in place
See `TEST_DATABASE_ISOLATION.md`. Permanent rule: never run `spark migrate`/rollback/refresh or destructive PHPUnit DB operations against the dump-based development database without an explicitly reviewed migration strategy.
