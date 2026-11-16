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

1. **Admin overview & Navigation (1 min)** — sign in as `demo.admin1`. Show the dashboard (all figures are live database counts), then **Pipeline** (Kanban by stage) and the expanded 20-module enterprise navigation. Trigger the **20-step interactive product tour** from the top bar or Help Centre.
2. **Master Data Management (1.5 min)** — Open *Masters* (`/crm/masters`). Show the 5 organized master groups (Organisation, Customer, Product, Sales, System). Click into **Branches**, **Customer Segments**, **Product Variants**, or **Payment Terms**. Demonstrate live search, active/inactive toggle, duplicate code detection, and one-click CSV export.
3. **Customer 360 & Project (2 min)** — *Customers → New*: create "Demo Villa Customer" (show duplicate detection). Open an existing customer (e.g. Aditi Kulkarni) to demonstrate the **Customer 360-degree view**: seamless tab navigation across Projects, Enquiries, Quotations, Orders, Payments, Deliveries, Installations, Service Requests, and chronological sales timeline.
4. **Site Visit & Millimeter Measurements (1.5 min)** — *Site Visits & Measurements* (`/crm/activity/visits`): review laser dimension measurements (W x H in mm/inches, area auto-calculation to Sq.Ft / Sq.M).
5. **Architectural Quotation Builder & True A4 PDF (2 min)** — *Quotations → New*: select Vitrum Aluminium Sliding Windows or Saint-Gobain UPVC, customize dimensions, variant finishes (Matte Black, Bronze, DGU glass). Generate the official **A4 portrait PDF** (210 × 297 mm): show repeated headers across pages, itemized specifications, split CGST/SGST/IGST breakdown, Indian Rupee words (Lakhs/Crores), and customer acceptance & authorized signatory blocks.
6. **Negotiation & Approval Chain (1 min)** — Customer requests discount above executive's 5% authority: request discount in *Negotiations*. Sign in as `demo.manager1` to approve up to 15%, or escalate to Management. Demonstrate version revision and side-by-side comparison.
7. **Sales Order, Milestone Payments & Official Receipt (1.5 min)** — Convert accepted quote to Sales Order. Open *Payments* (`/crm/payments`): record milestone advance. Show real-time order balance updating, overpayment guard, and generate the official **A4 Payment Receipt PDF** with transaction UTR and stamp block.
8. **Logistics Delivery Challans & Installation Sign-off (1 min)** — *Fulfilment* (`/crm/deliveries` & `/crm/installations`): review Delivery Challan (vehicle/driver details, transit protection), dispatch to site, and on-site technician installation with customer digital acceptance sign-off.
9. **After-Sales Service & Complaints (1 min)** — *Service & Complaints* (`/crm/service`): demonstrate customer service ticket logging, categorization (warranty claim, repair, maintenance), priority, and technician resolution workflow.
10. **AutoQA Command Centre & Audit (30 s)** — Open *Auto QA* (`/crm/qa`): trigger automated system audits with health scoring, database integrity checks, and financial reconciliation. Demonstrate complete immutability in *Audit Trail*.

More scenarios (lost, rejected discount, reassignment, overdue follow-up): [`docs/CRM_DEMO_SCENARIOS.md`](docs/CRM_DEMO_SCENARIOS.md).
