# TalentHub — Implementation Roadmap

Sizing: the brief spans ~15 phases (service catalogue, CRM, CMS, SEO admin, 9-role RBAC, new UI, testing, 12+ docs). That is weeks of engineering and cannot be safely built and verified in one pass. Plan: vertical slices, each merged to `main` only after staging validation. No production deploy without explicit approval.

| Slice | Content | Migrations | Depends on |
|---|---|---|---|
| 0 (done) | Audit docs (this folder) | – | – |
| 1 (done) | **Security hardening**: S-01..S-03, S-05; verify S-04 | none | – |
| 2 (mostly done) | **India-wide positioning**: layout fallbacks, public page copy, unverified SLA/guarantee/metric claims and hardcoded fallback testimonials removed, page-meta migration with backup + exact rollback (`20261106000002`). Remaining: homepage redesign, footer link groups, DB `website_settings` text if it still says Pune (admin-editable), legal pages review | data-only, with `down()` | – |
| 3 (partly done) | **Service catalogue**: `service_categories`, `services`, `products` (SocietyHub, PeopleHub) and public pages are done. Remaining: industries, admin CRUD for services/products, per-service detail content (problems, methodology, FAQs), services for ERP/QA/Transformation/Marketing categories | 3 tables (done) | 2 |
| 4 (done, core) | **IT leads CRM**: `it_leads`, `it_lead_activities`; public `/project-enquiry` + `POST /api/v1/service-enquiries`; admin `/admin/it-leads` (filters, pagination, status pipeline, assignment, notes, follow-ups, duplicate flag, CSV export, soft-delete, audit log, dashboard KPI, `it_leads.view/manage` permissions). Not yet: optional document upload, email communication log, lead-assignment notification email, per-lead email templates | 2 tables (done) | 3 |
| 5 | **Navigation & UI**: componentised layout, mega menu, hero, verticals/industries/process sections, CSS-only motion with reduced-motion support | `navigation_menus` | 3 |
| 6 | **RBAC**: 9 roles + permission codes, per-route enforcement audit | seed | 1 |
| 7 | **SEO module**: `seo_metadata`, `redirects`, noindex safeguards, Service/JobPosting/Article schema, sitemap incl. services | 2 tables | 3 |
| 8 | Case studies, interviews table, analytics events + GA4 consent | 2–3 tables | 4 |
| 9 | PWA review (cache exclusions, versioned cache, update prompt) | – | 5 |
| 10 | Testing (PHPUnit, API tests, Lighthouse on staging), test reports, remaining docs | – | all |

## Inputs needed from the business (cannot be invented)
1. Which services and technologies TalentHub can genuinely deliver.
2. Verified metrics, client names, testimonials, case studies (otherwise capability-only content).
3. Verified social links, business email/phone, and whether the Pune office is described as an "operational location".
4. Approval gates for: any production migration, and git-history purge of SQL dumps (S-04).

## Rules
Reversible migrations, backup verified before each production apply, staging first, staging DB/credentials never mixed with production, existing records never deleted.
