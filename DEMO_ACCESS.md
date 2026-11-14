# DHIS Sales CRM — Demo Access

> **Everything in this demo is fictional.** All names, phone numbers, addresses and e-mail addresses (`*@dhis-demo.invalid`) are made up, and demo e-mails are **never transmitted** to anyone. The shared password below is a *demo-only* credential. **Never reuse it on a production server** — create real accounts with `php spark crm:create-admin` instead and remove the demo users.

## Sign in

| | |
|---|---|
| **Login page** | **Live Demo:** `https://dhiscrm.talenthubconsultant.com/crm/login` (local: `http://localhost:8080/crm/login`) |
| **Identity** | the username in the table below (the e-mail address also works) |
| **Password (all demo users)** | `Demo@DHIS#2026` |
| Session timeout | 30 minutes idle (setting `session.timeout_minutes`) |
| Lock-out | 5 failed attempts lock the account for 15 minutes (a Super Admin/Admin can reset it from *Users & Teams*) |

## Demo users

| Username | Name | Role | Team | Data visible | What to show with this login |
|---|---|---|---|---|---|
| `demo.superadmin` | Kiran Rao | Super Admin | — | Everything | Everything, including creating other Super Admins, Settings, Demo reset |
| `demo.admin1` | Meera Joshi | Admin | — | Everything | Customers, enquiries, assignment history, products & pricing, users & teams, settings, audit, demo reset |
| `demo.admin2` | Sandeep Patil | Admin | — | Everything | Same as above — use as a second approver / reviewer |
| `demo.manager1` | Rahul Deshpande | Sales Manager | manages *Demo Team – Windows* | Own + team (Amit, Sneha, Vikram) | Team pipeline, approving/rejecting discounts up to the manager limit, reassigning enquiries, team reports |
| `demo.manager2` | Priya Naik | Sales Manager | manages *Demo Team – Bathrooms & Kitchens* | Own + team (Pooja, Nikhil, Anjali) | Same, for the second team — shows cross-team isolation |
| `demo.exec1` | Amit Pawar | Sales Executive | Windows | Own records only | Follow-ups, site visits, quotations, negotiation requests |
| `demo.exec2` | Sneha Kale | Sales Executive | Windows | Own records only | " |
| `demo.exec3` | Vikram Shinde | Sales Executive | Windows | Own records only | " |
| `demo.exec4` | Pooja Jadhav | Sales Executive | Bathrooms & Kitchens | Own records only | " |
| `demo.exec5` | Nikhil More | Sales Executive | Bathrooms & Kitchens | Own records only | " |
| `demo.exec6` | Anjali Bhosale | Sales Executive | Bathrooms & Kitchens | Own records only | " |
| `demo.management` | Suresh Iyer | Management (read-only) | — | Everything, read-only | Consolidated dashboards, reports, sales history and audit — **no** ability to change transactions |

Full permission grid: [`docs/CRM_ROLE_PERMISSION_MATRIX.md`](docs/CRM_ROLE_PERMISSION_MATRIX.md).

## Demo data

The seeded organisation is deterministic and internally consistent (the seed creates 12 users in two teams, 40 customers and 100 enquiries; a live system will show a few more once people add records): enquiries across every stage, follow-ups and activities, site visits with measurements, a 35-product catalogue (Windows, Bathrooms, Modular Kitchens, Services), quotations with revisions, negotiation and discount-approval records (approved *and* rejected), sales orders, an e-mail log, notifications and the full workflow/audit history. Every dashboard number is computed from these rows.

* Rebuild it: `php spark crm:demo-seed` (refuses unless `DHIS_DEMO_ENV=true` **and** the setting `demo.enabled` = 1).
* Reset it from the UI: **Demo Data** (Admin / Super Admin) → type `RESET DEMO`. A JSON snapshot is saved first, only demo-owned rows are removed, then integrity is verified. If you are signed in as a demo user you will be signed out and must sign in again.
* Reset is **blocked** on any server that is not flagged as the demo server.

## 10-minute demonstration script

1. **Admin overview (1 min)** — sign in as `demo.admin1`. Show the dashboard (all figures are live database counts), then **Pipeline** (Kanban by stage) and **Calendar**.
2. **Customer & enquiry (2 min)** — *Customers → New*: create "Demo Villa Customer" (try a duplicate mobile number to show duplicate detection). Create an enquiry for them; the system auto-assigns an eligible salesperson. Open the enquiry → **Assignment history** to show who/when/why. Open the customer → tabs and the **chronological timeline**.
3. **Executive work (2 min)** — sign out, sign in as the assigned executive (check the enquiry owner; e.g. `demo.exec1`). Record a call, schedule and complete a follow-up, schedule a site visit and complete it with measurements.
4. **Quotation with manual editing (2 min)** — *Quotations → New* from the enquiry. Pick a product, then **change the variant, width/height/size unit and the unit price by hand**; totals and tax are recalculated by the server (never trusted from the browser). Try a price below the product's minimum to see the approval guard. Save, preview the PDF, send (recorded in the Email Log as *queued — not transmitted* in log-only mode).
5. **Negotiation & discount approval (2 min)** — the customer asks for a reduction: *Negotiations → request* with a percentage above the executive's 5 % limit. Sign in as the team manager (`demo.manager1` for Windows executives) → **approve** (or reject / return / escalate). The executive cannot approve their own request.
6. **Revise & compare (1 min)** — create the **revised quotation** (new version; v1 stays untouched and downloadable) and open **Compare versions** to see line-by-line and total differences with who changed what and why.
7. **Close the sale (1 min)** — record the customer's acceptance, create the **Sales Order**, mark the enquiry **Won**. Show **Sales History**, **Reports** (Excel/PDF export) and — as `demo.management` — read-only visibility.
8. **Audit (30 s)** — as `demo.admin1` open **Audit Trail** → *Business workflow events* and filter by the enquiry to show every action with user, time, previous/new state.

More scenarios (lost, rejected discount, reassignment, overdue follow-up): [`docs/CRM_DEMO_SCENARIOS.md`](docs/CRM_DEMO_SCENARIOS.md).
