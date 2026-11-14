# CRM Lead Management Guide (admin)

Menu: **CRM Leads** (formerly "IT Project Leads"), plus **CRM Settings** and **Integrations** for permitted users. URL: `/admin/it-leads`.

## Permissions

| Permission | Allows |
|---|---|
| `crm.view` | list, search, open leads, see dashboard CRM block |
| `crm.manage` | change status, assign, notes, follow-up, priority/value/tags, bulk actions, archive |
| `crm.export` | CSV export (audit-logged) |
| `crm.settings` | notification recipient, test notification |
| `integrations.view` / `integrations.manage` | see / change integration clients and secrets |

Legacy `it_leads.view` / `it_leads.manage` keep working as aliases (`it_leads.manage` also allows export). New permissions are created automatically and granted to Super Administrator; assign them to other roles under Roles.

## Lead sources

`source_system` + `source`: website `project_enquiry`, product pages `product_enquiry`, WorkSphere `worksphere_demo` / `worksphere_enquiry`, plus `manual` / `import` reserved. WorkSphere leads are created and updated by the signed event feed (see `WORKSPHERE_INTEGRATION_RECEIVER.md`).

## Statuses

New, Contacted, Qualified, Demo Scheduled, Demo Active, Proposal Sent, Negotiation, On Hold, **Closed Won**, **Closed Lost** (reason required), Spam. "Requirement Gathering" is a legacy status kept for older leads. Closed Won/Lost/Spam leave the open pipeline and stop counting for follow-ups and duplicate detection. The first move away from New records the first-contact time used by the "average first response" KPI.

## Daily workflow

1. Open **CRM Leads**; filter by status, source, product, priority, assignee, category, date range, tag or "Follow-up overdue"; click column headings to sort.
2. Open a lead: read the enquiry, WorkSphere demo info (status, start, expiry, verification), the duplicate/related-lead banner and the full timeline (status changes, notes, assignments, follow-ups, notification emails and delivery status, WorkSphere events).
3. Update status (Closed Lost needs a reason), assign (the assignee is emailed), set the next follow-up date, priority, estimated value (INR) and tags, add notes.
4. **Mark as spam** removes a lead from the pipeline without deleting it. **Archive** is a soft delete (row retained, restorable in the database).
5. Bulk: tick leads, choose Set status or Assign to, add a reason if closing as lost, Apply. Up to 200 leads per action.
6. **Export CSV** (needs `crm.export`) exports the current filters (max 5000 rows). Cells starting with `= + - @` are prefixed with `'` so spreadsheets cannot execute them. Each export is written to the audit log.

## Duplicates

Same email or phone within 60 days of an open lead is flagged "DUP" with a link. There is no automatic merge: keep the best lead, copy any notes you need, then archive the other. WorkSphere registrations for the same email are folded into the existing open lead automatically (the lead shows e.g. "2x").

## Dashboard (Admin > Dashboard, CRM block)

Leads in range, new today/7/30 days, conversion rate (Closed Won / (Won + Lost); "n/a" when nothing is closed), overdue follow-ups, unassigned open leads, average first response time, open pipeline value (only leads with an estimate), by status/source/product/category, WorkSphere funnel (registered, verified, demo activated, converted, failed provisionings), integration health (last event, failures in 24 h) and CRM email failures/pending. Filters: 7/30/90 days or custom (max 365 days), product, source. All values come from the database; empty periods show empty states, not placeholders.

## Not available yet

Editing categories/tags from the UI (stored in `crm_categories` / `crm_tags`, seeded), automatic merge, scheduled follow-up reminder emails (template ready), per-user lead visibility restrictions (all CRM users see all leads).
