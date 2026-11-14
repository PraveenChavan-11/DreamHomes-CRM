# TalentHub — API Inventory (as found in `app/Config/Routes.php`)

Convention: `/api/v1/*` is canonical, with `/api/*` aliases for a subset. Responses are JSON from `Api\*` controllers; some public POSTs reuse `Web\*` controllers.

## Public (no auth)
| Method | Path | Handler | Rate limit |
|---|---|---|---|
| GET | /api/v1/jobs, /jobs/categories, /jobs/{slug} | JobApi | – |
| POST | /api/v1/applications, /applications/submit | Web\Jobs::submitApplication | 5/600s (upload) |
| POST | /api/v1/resume-submissions, /enquiry/general-resume | Web\CareerEnquiry::submit | 5/600s |
| POST | /api/v1/contact-enquiries, /enquiry/contact | EnquiryApi::submitContact | 5/600s |
| POST | /api/v1/employer-enquiries, /enquiry/employer | EnquiryApi::submitEmployer | 5/600s |
| GET | /api/v1/blogs, /blogs/{slug}, /faqs, /testimonials, /pages/{slug}, /settings, /sitemaps/* | Cms / Settings / Sitemap | – |
| POST/GET | /api/v1/newsletter/{subscribe,verify,unsubscribe,resubscribe,preferences} | NewsletterApi | subscribe 10/60 |
| GET | /api/v1/announcements/active | AnnouncementApi | – |
| POST | /api/v1/analytics/campaign-event | AnnouncementApi::trackEvent | 60/60 |
| POST | /api/v1/job-alerts/{subscribe,unsubscribe}, /cookies/consent | JobAlert, CookieConsent | 10/60 |
| POST | /api/v1/webhooks/hostinger-mail | MailApi::webhook | signature check *(unverified)* |
| GET | /api/cron/run?key= | route closure | key only |

## Admin API (`api/v1/admin/*`, `auth` filter)
profile/password; dashboard; notifications (5); jobs (CRUD, status, duplicate); applications (list/show/status/notes); documents stream; resume-bank; enquiries (employer/contact list + status); blogs/faqs/testimonials CRUD; pages (list/show/draft/publish/rollback); media; email-templates; email-outbox (+resend); mail (mailboxes, folders, messages, send, drafts, templates, analytics, settings, test-connection, link-recruitment); users (list/create/update); roles; security (stats, login-attempts); audit-logs; health; seo/status; sitemap regenerate; reports (applications/candidates/employers export); settings (+website-control, test-smtp).

## Server-rendered admin routes
About 100 under `/admin/*` (Routes.php §2) behind the `auth` filter. Per-route RBAC coverage is *(unverified)*.

## Gaps vs the brief
No `/api/v1/public/services`, `service-enquiries`, or `candidate-registration` (CV submission exists as resume-submissions). No admin APIs for leads, services, case studies, SEO, redirects, newsletter campaigns (server-rendered only), announcements, analytics, interviews. `docs/openapi.yaml` exists; accuracy against routes is *(unverified)*.
