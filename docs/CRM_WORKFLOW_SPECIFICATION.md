# CRM Workflow Specification

Statuses and transitions below are taken from the schema enums and `ReferenceData` / service code. Items marked **ASSUMED** are configurable defaults, not confirmed DHIS policy.

## Business numbers
`CUS-0001` customer · `ENQ-0001` enquiry · `QT-0001` quotation · `NEG-0001` negotiation · `SO-0001` sales order · `SV-0001` site visit. Generated under a row lock in `NumberSequenceService`; prefixes/padding live in `crm_number_sequences`.

## Enquiry lifecycle

`new → assigned → contacted → qualified → site_visit → quotation_preparation → quotation_sent → negotiation → won | lost | on_hold`

* Transitions are rows in `crm_status_transitions` (seeded from `ReferenceData::ENQUIRY_TRANSITIONS`); each names the permission required and whether a **reason** is mandatory. Anything not in the matrix is refused.
* `contacted → qualified | site_visit | quotation_preparation` and `site_visit → quotation_preparation` allow skipping stages that do not apply; `quotation_sent → quotation_preparation` and `negotiation → quotation_preparation | quotation_sent` allow re-work.
* **Lost** and **On hold** need a reason (lost: a lost-reason from the list; hold: also a next review date). **Won** needs an accepted quotation *and* a sales order, or an Admin-level exception with a reason.
* **Reopen** (`lost → contacted`, `won → negotiation`, `on_hold → contacted`) needs `enquiries.reopen` + reason and is audited.
* Every change writes `crm_enquiry_status_history` and a workflow event.

### Creation & assignment
Sources: manual, website, phone, whatsapp, walk_in, reference, architect, builder, advertisement, campaign, excel_import. A duplicate is an **open** enquiry with the same customer + product category + project name (`EnquiryService::dedupeHash`); it is refused with the matching enquiries listed, unless the user deliberately confirms creating it anyway. A **lead score** is computed from `crm_scoring_rules` (editable in Settings).

Auto-assignment (`AssignmentService::pick`) walks active `crm_assignment_rules` by priority: `round_robin` (state kept in `crm_assignment_state`), `workload` (fewest open enquiries), `team`, `territory` (city / PIN prefix). Only **active Sales Executives** are eligible for automatic assignment (managers/admins can assign manually). It **never overwrites an existing owner**; when no eligible user exists the enquiry stays `new` and unassigned (fallback) and Sales Managers and Admins get an `enquiry.unassigned` notification. Manual assign / reassign (reason mandatory for reassign) write `crm_enquiry_assignments` (from → to, by, reason, time), so previous owners remain visible forever.

## Follow-ups, activities, visits
* Follow-up: `pending → completed | rescheduled | missed | cancelled`; types call / meeting / whatsapp / email / visit / other. Completing records outcome, remarks, next action and (optionally) schedules the next one. Completed items stay in history.
* **Daily Sales Activity & Price Negotiation Integration**: Follow-ups directly surface linked quotations and active negotiation approval statuses (`Pending Approval`, `Approved - X%`, `Rejected - Revise`). When completing a follow-up, salespeople can simultaneously check "Client Wants Price Concession" to specify requested discount % or customer expected total ₹, and submit it directly to senior managers for price approval while logging the client discussion.
* `crm:reminders` (cron) sends due reminders once (dedupe keys), marks missed, and **escalates** overdue items to the manager after `followup.escalate_after_hours` (**ASSUMED** 24 h).
* Site visit: `scheduled → completed | cancelled | rescheduled`. Completing stores measurements as a new **version** (`crm_site_measurements.version_no`, `is_current`), observations, product requirements and a follow-up action. Reminder `visit.reminder_hours_before` (**ASSUMED** 24 h).
* Customer requirements per enquiry (variant, width × height, unit, nos, expected price) are tracked with a before/after change log (`crm_requirement_changes`) and can seed quotation lines.

## Quotation

`draft → sent → negotiation → revised → accepted | rejected | expired | cancelled`

1. **Create** from an enquiry (executive/manager/admin with `quotations.create`). Each line: product (optional), description, **variant, width, height, size unit, nos**, quantity, unit, **unit price (catalogue or manually overridden)**, line discount, tax. `price_source` records `catalogue` or `manual`; size-priced products (sqft / rft) derive quantity from the entered size on the server.
2. **Server pricing** (`QuotationService::price`): bcmath; subtotal = qty × price; discount % or amount (capped at subtotal); taxable; GST per line; additional charges (with tax); grand total, optional round-off to the rupee (`quotation.round_off`). It then enforces authority: overall discount ≤ the user's personal limit (or an approved ceiling), and every line's net price ≥ the product **minimum selling price** unless an approved negotiation ceiling or Admin authority applies.
3. **Draft** can be edited in place (`updateDraft`). **Send** (PDF generated with Dompdf, SHA-256 stored, e-mail logged) **locks** that version; further changes are only possible by **revising**.
4. **Revise** creates version N+1 (reason mandatory; optional link to a negotiation and follow-up). Prior versions, their PDFs, values, terms and timestamps are never altered. The item-level change log (added / removed / modified / totals / terms) and a side-by-side **compare** are available at `/crm/quotations/{id}/compare`. All versions support both **View A4 PDF** (inline) and **Download A4 PDF** (`?download=1`).
5. **Response**: customer accepted / rejected / pending is recorded with remarks and date; acceptance moves the enquiry toward order conversion.
6. Validity default 15 days (**ASSUMED**), terms and payment terms are **placeholders** until DHIS supplies wording; GSTIN is empty until supplied and is printed only when set. CGST/SGST vs IGST depends on `company.state` vs customer state.

## Negotiation & discount approval

Statuses: `pending → approved | rejected | returned | escalated`, then `applied` (revised quotation issued) or `withdrawn`.

* Executive/manager raises a request: requested **total discount %** (or customer's expected amount, converted to the closest uniform %), reason (mandatory), original quotation version snapshot.
* **Required level** (`NegotiationService::levelFor`): ≤ the requester's personal limit → `self` (auto-recorded, no approval); ≤ `discount.limit.manager_pct` (**ASSUMED** 15 %) → `manager`; above → `admin`.
* **Decide** (`approve / reject / return / escalate`): remarks mandatory except for approve; approver needs `discounts.approve_manager` or `…_admin`, must be inside the enquiry's scope, within their own limit, and **cannot decide their own request** unless `discount.allow_self_approval` = 1. Row-locked, so double clicks cannot double-decide.
* **When Approved**: The salesperson receives a notification and can immediately:
  1. Generate the revised quotation version (vX) pre-loaded with the approved discount ceiling and audit reason.
  2. Adjust line items, dimensions, quantities, or prices if customer requirements shifted.
  3. View or download the official A4 PDF and send the approved version to the client.
* **When Rejected**: The manager's rejection remarks are prominently displayed to the sales executive. As per workflow, the sales executive can:
  1. Re-cost the project by adjusting product specifications, dimensions (Width × Height), quantities, or materials as a new version (v2, v3...).
  2. Or submit a revised negotiation round with an adjusted ask within the manager's guidance.
* Every negotiation round links to the quotation version it originated from and the resulting version it produced (`crm_negotiations`, `crm_discount_approvals`).

## Sales order

`draft → pending_confirmation → confirmed → on_hold → cancelled | closed` (allowed moves: draft→pending_confirmation|cancelled; pending_confirmation→confirmed|on_hold|cancelled; confirmed→on_hold|closed|cancelled; on_hold→confirmed|cancelled). Created from an **accepted** quotation version, once (duplicate conversion is refused); values are copied as a snapshot (`crm_sales_orders`, `crm_sales_order_items`) so later edits can never change them. Hold / cancel need a reason.

## E-mail
`queued → sent (SMTP accepted) → delivered | bounced` (provider confirmation only) or `failed` (with retry back-off `300 s × attempts²`, max `email.retry_max_attempts`, **ASSUMED** 3). Default mode `log_only` records but never transmits; demo rows are never transmitted.

## Audit & history
Every action above writes a workflow event (customer timeline, global sales history, ownership history) and/or an audit event (settings, users, products, logins). See [CRM_SECURITY_CHECKLIST.md](CRM_SECURITY_CHECKLIST.md).
