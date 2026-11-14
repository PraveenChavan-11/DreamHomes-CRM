# CRM Role & Permission Matrix

_Generated from `app/Crm/Services/ReferenceData.php` — the same constants that seed the database. Re-generate after changing roles or permissions._

Permissions are enforced on the server twice: by the `crmperm:` route filter and again inside the services. Hiding a button is cosmetic only.

## Data visibility (row-level scope)

| Role | Scope | Meaning |
|---|---|---|
| Super Admin | `all` | Every customer, enquiry and transaction |
| Admin | `all` | Every customer, enquiry and transaction |
| Sales Manager | `team` | Own records plus members of teams they manage |
| Sales Executive | `own` | Only records they own or created |
| Management (Read-only) | `all` | Every customer, enquiry and transaction |

## Permission grid

| Permission | Code | Super Admin | Admin | Sales Manager | Sales Executive | Management (Read-only) |
|---|---|---|---|---|---|---|
| **Dashboard** | | | | | | |
| View dashboard | `dashboard.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Customers** | | | | | | |
| View customers | `customers.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create customers | `customers.create` | ✓ | ✓ | ✓ | ✓ | – |
| Edit customers | `customers.edit` | ✓ | ✓ | ✓ | ✓ | – |
| Deactivate customers | `customers.deactivate` | ✓ | ✓ | – | – | – |
| Import customers/enquiries | `customers.import` | ✓ | ✓ | ✓ | – | – |
| Export customers | `customers.export` | ✓ | ✓ | ✓ | – | ✓ |
| **Enquiries** | | | | | | |
| View enquiries | `enquiries.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create enquiries | `enquiries.create` | ✓ | ✓ | ✓ | ✓ | – |
| Edit enquiries / change status | `enquiries.edit` | ✓ | ✓ | ✓ | ✓ | – |
| Assign enquiries | `enquiries.assign` | ✓ | ✓ | ✓ | – | – |
| Reassign enquiries | `enquiries.reassign` | ✓ | ✓ | ✓ | – | – |
| Mark won / lost / hold | `enquiries.close` | ✓ | ✓ | ✓ | ✓ | – |
| Reopen closed enquiries | `enquiries.reopen` | ✓ | ✓ | ✓ | – | – |
| **Activity** | | | | | | |
| Manage follow-ups & activities | `followups.manage` | ✓ | ✓ | ✓ | ✓ | – |
| Manage site visits & measurements | `visits.manage` | ✓ | ✓ | ✓ | ✓ | – |
| **Catalogue** | | | | | | |
| View products | `products.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Manage products & pricing | `products.manage` | ✓ | ✓ | – | – | – |
| **Quotations** | | | | | | |
| View quotations | `quotations.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create / revise quotations | `quotations.create` | ✓ | ✓ | ✓ | ✓ | – |
| Send quotations | `quotations.send` | ✓ | ✓ | ✓ | ✓ | – |
| **Negotiations** | | | | | | |
| Create negotiation / discount requests | `negotiations.create` | ✓ | ✓ | ✓ | ✓ | – |
| Approve discounts (manager level) | `discounts.approve_manager` | ✓ | ✓ | ✓ | – | – |
| Approve discounts (admin level) | `discounts.approve_admin` | ✓ | ✓ | – | – | – |
| **Orders** | | | | | | |
| View sales orders | `orders.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create / manage sales orders | `orders.manage` | ✓ | ✓ | ✓ | – | – |
| **History** | | | | | | |
| View sales history | `history.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Reports** | | | | | | |
| View reports | `reports.view` | ✓ | ✓ | ✓ | – | ✓ |
| Export reports | `reports.export` | ✓ | ✓ | ✓ | – | ✓ |
| **Admin** | | | | | | |
| Manage users | `users.manage` | ✓ | ✓ | – | – | – |
| Manage teams | `teams.manage` | ✓ | ✓ | – | – | – |
| Manage CRM configuration | `settings.manage` | ✓ | ✓ | – | – | – |
| View audit history | `audit.view` | ✓ | ✓ | – | – | ✓ |
| **Communication** | | | | | | |
| View email log | `emails.view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| Send manual emails | `emails.send` | ✓ | ✓ | ✓ | ✓ | – |
| **Admin** | | | | | | |
| Reset demo data | `demo.reset` | ✓ | ✓ | – | – | – |

Super Admin and Admin both hold every permission (`*`); only Super Admin may create or change Super Admin accounts and bypasses all permission checks.

## Discount authority (defaults are ASSUMED, not confirmed DHIS policy — editable in Settings)

| Role | Default personal limit |
|---|---|
| Sales Executive | 5 % (applies without approval) |
| Sales Manager | approves up to 15 % |
| Admin / Super Admin | up to 100 % |

An approver cannot decide their own request unless `discount.allow_self_approval` is switched on.
