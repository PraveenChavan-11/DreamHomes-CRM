# TalentHub — Database Architecture (as found)

Source: `$table` declarations in `app/Models`, `DatabaseSchemaService`, 2 CI4 migrations, `deployment/*.sql`. Column/FK/index details for core tables (jobs, applications, candidates…) live only in `deployment/database_hostinger_export.sql` and `scratch/database_schema_dump.json` and were **not** audited column-by-column *(to do before writing any migration that touches them)*.

## Existing tables (by model)
- **Auth/RBAC:** admin_users, roles, permissions, role_permissions, user_roles, login_attempts, password_resets, audit_logs, notifications
- **Recruitment:** jobs, job_categories, applications, candidates, candidate_documents, general_resume_submissions, employers, employer_enquiries, job_alert_subscriptions
- **Enquiries:** contact_enquiries
- **CMS:** website_pages, website_page_history, website_settings, blog_posts, blog_categories, faqs, testimonials, media_assets
- **Email/outbox:** email_templates, email_outbox, email_delivery_logs, email_campaigns
- **Mailbox:** mailbox_connections, mail_message_index, mail_local_drafts, mail_recruitment_links, mail_webhook_events, mail_activity_logs
- **Engagement:** newsletter_subscribers, newsletter_preferences, newsletter_activity_logs, announcements, announcement_events, cookie_consent_logs
- **SEO:** sitemap_log

## Brief's logical entities vs existing
| Logical entity | Status |
|---|---|
| jobs, job_categories, job_applications, candidates, candidate_profiles, employer_enquiries, uploaded_files | **Exist** (applications / candidate_documents / media_assets) |
| admin_users, roles, permissions, role_permissions, audit_logs | **Exist**; need 9-role seed and permission codes |
| email_templates, email_logs, newsletters, newsletter_campaigns, newsletter_logs, announcements | **Exist** (email_templates, email_delivery_logs, email_campaigns, newsletter_*, announcements) |
| content_pages, seo_metadata | **Partial**: `website_pages` holds meta fields; no per-service/industry SEO store, no robots/canonical overrides |
| services, service_categories, industries, case_studies | **New** |
| service_enquiries / leads, lead_activities | **New** (employer/contact enquiries exist but are not a CRM) |
| interviews | **New** (interview is only an application status today) |
| navigation_menus, content_sections | **New** (nav is hardcoded in the layout) |
| redirects | **New** |
| analytics_events | **Partial**: `announcement_events`, `cookie_consent_logs`; no generic event store |

## Rules for new migrations
CI4 migration class with working `down()`; `IF NOT EXISTS` guards; InnoDB utf8mb4; FKs with explicit ON DELETE; indexes on filter columns; soft delete via `deleted_at`; no data deletion; verified `mysqldump` backup before any production apply; apply on staging first.
