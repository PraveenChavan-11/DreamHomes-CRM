# DHIS Sales CRM — Database Design (v1, finalized for Phase 2)

Engine InnoDB, utf8mb4_unicode_ci, MariaDB 10.4+ / MySQL 8 compatible (no JSON-only features required; JSON stored as `LONGTEXT` with `JSON_VALID` avoided for 10.4 portability). All money `DECIMAL(14,2)`, rates `DECIMAL(6,3)`, quantities `DECIMAL(12,3)`. All tables prefixed `crm_` except reused identity tables. Timestamps stored in UTC-agnostic server time with app timezone `Asia/Kolkata` set in config; scheduled logic uses explicit timezone.

Deviation from the brief's *suggested* list (merged/skipped to avoid redundant tables): `customer_contacts`+`customer_addresses` kept only as one `crm_customer_contacts` (extra contacts) — primary billing/site address lives on `crm_customers`; `quotation_approvals`+`discount_approvals` → single `crm_discount_approvals`; `activities` and `followups` split (followup = scheduled task; activity = immutable interaction log); `workflow_events` and `audit_logs` kept separate (business timeline vs. security/config audit) but share one `AuditService`; `tax_rates` → `crm_tax_rates`; `product_prices` → `crm_product_price_history`.

## 1. Identity & access (reuse + extend)
| Table | Purpose / key columns |
|---|---|
| `admin_users` (reused) | + `employee_code`, `team_id`, `reports_to_id`, `max_discount_pct`, `deactivated_at` (CRM migration adds; nullable) |
| `roles`, `permissions`, `role_permissions`, `user_roles` (reused, re-seeded) | slugs: `super_admin, admin, sales_manager, sales_executive, management` |
| `crm_teams`, `crm_team_members` | team, manager_id; member user_id, `is_active` |
| `crm_territories` | name, city/state/pin pattern, team_id (for territory assignment) |
| `crm_login_history` | user_id, event (`login`,`logout`,`failed`,`timeout`), ip, user_agent, created_at |
| `crm_settings` | key/value/group, `updated_by`; approval limits, assignment mode, SMTP *non-secret* values, retention |

## 2. Customers
`crm_customers` (customer_no UNIQUE, name, type, company, primary contact, mobile, alt_mobile, email, billing/site address, city, state, pin, gstin, source_id, owner_user_id, status `active|inactive|archived`, remarks, created/updated_by/at, `deleted_at`)
`crm_customer_contacts` (customer_id FK, name, role, mobile, email, is_primary)
Indexes: `customer_no`, `mobile`, `email`, `(status,owner_user_id)`, FULLTEXT-free LIKE search on name/company. Duplicate detection key: normalized mobile + lowercase email (stored as `mobile_norm`, `email_norm`).
Rule: customers with any enquiry/quotation/order are **never hard-deleted** (FK `RESTRICT`).

## 3. Enquiries & assignment
`crm_lead_sources`, `crm_enquiries` (enquiry_no UNIQUE, customer_id FK, project_name, product_category_id, requirements, site_location, est_budget, expected_purchase_date, source_id, source_channel, priority, lead_score, status, current_owner_id, team_id, territory_id, closed_at, closed_by, outcome `won|lost|on_hold|null`, lost_reason_id, lost_competitor, hold_review_date, dedupe_hash)
`crm_enquiry_assignments` (enquiry_id, from_user_id, to_user_id, method `manual|auto_round_robin|auto_workload|auto_team|auto_territory|fallback|reassign`, reason, approved_by, assigned_by, created_at) — append-only
`crm_enquiry_status_history` (enquiry_id, from_status, to_status, user_id, reason, created_at) — append-only
`crm_status_transitions` (entity, from_status, to_status, required_permission, requires_reason) — **configurable transition matrix**
`crm_assignment_rules` (priority, mode, team_id, territory_id, category_id, is_active), `crm_assignment_state` (rule_id, last_user_id) for round-robin
`crm_lost_reasons`
`crm_scoring_rules` (field, operator, value, points)

## 4. Activity, follow-ups, site visits
`crm_followups` (customer_id, enquiry_id, assigned_to, type, due_at, status `pending|completed|rescheduled|missed|cancelled`, outcome, remarks, next_action, next_followup_id, completed_at, escalated_at, reminder_dedupe_key UNIQUE-per-slot)
`crm_activities` (customer_id, enquiry_id, user_id, type `call|meeting|whatsapp|email|note|visit|system`, summary, outcome, occurred_at, followup_id) — immutable
`crm_site_visits` (visit_no, enquiry_id, customer_id, assigned_to, scheduled_at, status, completed_at, remarks, observations)
`crm_site_measurements` (visit_id, version, is_current, data (text/JSON), recorded_by, created_at) — new row per revision
`crm_documents` (entity_type, entity_id, customer_id, original_name, stored_name, mime, size, sha256, uploaded_by) — stored outside webroot (`writable/uploads/crm`)

## 5. Catalogue & pricing
`crm_product_categories`, `crm_products` (sku UNIQUE, name, category_id, description, specs, image, unit, pricing_model `unit|sqft|rft|qty|package`, base_price, standard_price, min_price, tax_rate_id, is_active), `crm_product_price_history` (product_id, old/new prices, changed_by, at), `crm_tax_rates` (name, rate, is_default), `crm_additional_charges` (name, calc `fixed|percent`, value, taxable)

## 6. Quotations
`crm_quotations` (quotation_no UNIQUE e.g. `QT-0001`, enquiry_id, customer_id, current_version_id, status `draft|sent|negotiation|revised|accepted|rejected|expired|cancelled`, created_by)
`crm_quotation_versions` (quotation_id, version_no, UNIQUE(quotation_id,version_no), subtotal, discount_total, taxable_total, tax_total, charges_total, round_off, grand_total, terms, payment_terms, delivery_estimate, valid_until, customer_remarks, internal_remarks, pdf_path, pdf_sha256, is_locked, locked_at, created_by) — **locked once sent/approved; never UPDATEd after lock** (enforced in service + DB trigger-free guard test)
`crm_quotation_items` (version_id, product_id, description, specs, qty, unit, unit_price, discount_pct, discount_amt, tax_rate, line_taxable, line_tax, line_total) — snapshot of price/tax at time of quote
`crm_quotation_charges` (version_id, name, amount, tax)

## 7. Negotiation & approvals
`crm_negotiations` (negotiation_no, quotation_id, version_id, customer_expected_amount, requested_discount_amt, requested_discount_pct, reason, proposed_price, requested_by, status, final_discount_amt, resulting_version_id)
`crm_discount_approvals` (negotiation_id, level, required_role, approver_id, decision `pending|approved|rejected|returned|escalated`, remarks, decided_at); limits from `crm_settings` / `admin_users.max_discount_pct`. DB UNIQUE not enough for self-approval; enforced in `DiscountApprovalService` + tests.

## 8. Orders
`crm_sales_orders` (order_no UNIQUE, quotation_id UNIQUE → blocks duplicate conversion, version_id, customer_id, enquiry_id, salesperson_id, status, confirmed_at, advance_amount, expected_delivery, subtotal/tax/grand_total snapshot, remarks), `crm_sales_order_items` (snapshot of accepted lines)

## 9. Communication & notifications
`crm_email_templates`, `crm_email_log` (to, from, subject, template, customer_id, enquiry_id, quotation_id, triggered_by_user/automation, status `queued|sent|delivered|failed|bounced`, provider_response, error, attempts, next_retry_at, created_at, sent_at) — "sent" = SMTP accepted; "delivered" only with provider confirmation.
`crm_notifications` (user_id, type, title, body, link, dedupe_key UNIQUE, read_at)
`crm_notification_prefs` (user_id, event, in_app, email)

## 10. Audit & workflow
`crm_workflow_events` (id, event_type, entity_type, entity_id, customer_id, enquiry_id, user_id, prev_state, new_state, reason, metadata, correlation_id, source, created_at) — **append-only** (no UPDATE/DELETE in code; DB user for prod can be granted INSERT/SELECT only; test asserts).
`crm_audit_events` (same shape for configuration/security changes: users, permissions, settings, exports, resets); sensitive keys (`password`, `token`, `secret`) stripped by `AuditService`.
Indexes: `(customer_id,created_at)`, `(enquiry_id,created_at)`, `(entity_type,entity_id)`, `(user_id,created_at)`, `(event_type)`. Retention: configurable, archive not delete by default.

## 11. Operations
`crm_number_sequences` (key `customer|enquiry|quotation|negotiation|order|visit`, prefix, next_value, year-reset flag), `crm_import_jobs` (+ `crm_import_rows`, file sha256 UNIQUE-per-type to block duplicate imports), `crm_demo_registry` (table_name,row_id — marks demo-owned rows so reset touches only those), `scheduled_job_runs` (reused).

## 12. Integrity principles
FKs `RESTRICT` on anything with history; `ON DELETE CASCADE` only for pure child rows (items, contacts). `CHECK`-style rules enforced in services (MariaDB 10.4 enforces CHECK but MySQL 5.7 does not — not relied upon alone). Every multi-table write in a transaction. Historical snapshots (quotation items, order items, assignment rows) never reference mutable master values for amounts.
