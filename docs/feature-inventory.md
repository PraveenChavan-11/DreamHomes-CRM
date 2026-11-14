# TalentHub Consultant — Feature Inventory & Status Matrix

**Date:** October 2026  
**Auditor:** Technical Product Owner & QA Automation Lead  
**Scope:** Complete functional inventory across Public Frontend, Admin Capabilities, Data Pipelines, and SEO.

---

## 1. Public Website Feature Inventory

| Module / Feature | Current Implementation | Status | Live URL | Future PHP API Integration |
|---|---|---|---|---|
| **Homepage** | Next.js Server Components, Framer Motion, Static JSON | **WORKING** | `/` | Retain static presentation; dynamically consume active jobs & testimonials |
| **Browse Jobs** | Client-side search, filtering by category/work mode, saved bookmarks (localStorage) | **WORKING** | `/jobs` | Connect to `GET /api/v1/jobs` with fallback to static JSON |
| **Job Details** | Pre-rendered static pages, Schema.org `JobPosting` + `BreadcrumbList` | **WORKING** | `/jobs/[slug]` | Connect to `GET /api/v1/jobs/{slug}` with dynamic pre-generation |
| **Job Application (Flow A)** | Modal with pre-filled `mailto:` link | **PARTIAL** | `/jobs/[slug]` | Upgrade to direct file-upload POST to `POST /api/v1/applications` |
| **Career Enquiry (Flow B - General Resume)** | Interactive form with `mailto:` generation | **PARTIAL** | `/career-enquiry` | Upgrade to direct POST to `POST /api/v1/resume-submissions` |
| **For Employers Landing** | B2B solutions, 5-step workflow, sector cards, FAQ accordion | **WORKING** | `/employers` | Connect CTA directly to employer mandate modal & API |
| **Employer Hiring Enquiry** | Contact form with `/api/contact.php` SMTP submission | **WORKING** | `/contact` | Connect to `POST /api/v1/employer-enquiries` with DB persistence & outbox |
| **General Contact Form** | Sanitized PHP mailer (`/api/contact.php`) with honeypot & rate limit | **WORKING** | `/contact` | Connect to `POST /api/v1/contact-enquiries` |
| **Candidate Hub** | Candidate advisory, interview tips, domain guides | **WORKING** | `/candidates` | Retain static presentation |
| **Insights / Blog** | Static blog list & dynamic slug reader from `blogs.json` | **WORKING** | `/insights`, `/insights/[slug]` | Connect to `GET /api/v1/blogs` with admin CRUD |
| **FAQ Hub** | Categorized accordion questions from `faqs.json` | **WORKING** | `/faq` | Connect to `GET /api/v1/faqs` with admin CRUD |
| **About Us** | Leadership team, mission, value pillars, milestones | **WORKING** | `/about` | Retain static presentation |
| **Solutions** | Executive search, permanent staffing, contract staffing details | **WORKING** | `/solutions` | Retain static presentation |
| **Tawk.to Live Chat** | Client-side widget script injection with CSP header | **WORKING** | Global | Preserve exact configuration |
| **Google Analytics** | Measurement ID gtag injection | **WORKING** | Global | Preserve exact configuration |

---

## 2. Admin & Recruiter Operations Inventory

| Admin Feature | Target Status | Description & Requirements | Target Route in Vite Admin |
|---|---|---|---|
| **Authentication** | **TO IMPLEMENT** | Secure email/password login, rate-limited attempts, session cookie | `/admin/login` |
| **Executive Overview** | **TO IMPLEMENT** | KPI cards: Active Jobs, New Applications, Screening, Shortlisted, Enquiries | `/admin/` |
| **Job Management** | **TO IMPLEMENT** | Create, edit, preview, publish, close, archive, duplicate job mandates | `/admin/jobs` |
| **Candidate Vault** | **TO IMPLEMENT** | Searchable candidate index, experience, primary skills, contact details | `/admin/candidates` |
| **Application Pipeline** | **TO IMPLEMENT** | Kanban/Table view, stage transitions (Applied → Screening → Shortlist → Offer) | `/admin/applications` |
| **General Resume Bank** | **TO IMPLEMENT** | Dedicated queue for spontaneous general submissions without specific job ID | `/admin/resume-bank` |
| **Resume Streaming** | **TO IMPLEMENT** | Secure in-browser preview & tokenized PDF/DOCX download (no direct URLs) | `/admin/resumes/:id` |
| **Employer CRM** | **TO IMPLEMENT** | Manage client mandates, requirements, priority level, recruiter assignment | `/admin/employers` |
| **Enquiry & Follow-ups** | **TO IMPLEMENT** | Track corporate & candidate enquiries, schedule follow-ups, overdue alerts | `/admin/enquiries` |
| **CMS Content Management** | **TO IMPLEMENT** | Update Blogs, FAQs, Testimonials, and Company Contact details | `/admin/content` |
| **Email Template Editor** | **TO IMPLEMENT** | Manage templates: Application Acknowledgement, Shortlist Notice, Enquiry Reply | `/admin/email-templates` |
| **Audit Logs** | **TO IMPLEMENT** | Immutable history of all administrative actions, status changes, and logins | `/admin/audit-logs` |
| **Users & Permissions** | **TO IMPLEMENT** | Manage Recruiter and Content Editor accounts with RBAC controls | `/admin/users` |
| **System Settings** | **TO IMPLEMENT** | SMTP health, storage path, maintenance mode toggle, data retention | `/admin/settings` |

---

## 3. Data Integrity & Validation Inventory

1. **Job Posting Integrity:**
   - Real active jobs currently in database: 3 (Steel Foreman, Purchase Executive, HR Executive for Austin Realty, Pune).
   - Strict rule: Dummy or placeholder jobs must never be published to the live public website.
   - Wage secrecy policy: Salary min/max fields remain confidential on public pages unless explicitly configured.
2. **Resume Upload Controls:**
   - Permitted MIME types: `application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX).
   - Maximum file size: 5 MB per document.
   - Filename sanitization: Original filename stored in database; physical file stored as SHA-256 / UUID hash.
3. **Application Deduplication:**
   - Duplicate prevention: If candidate submits the same email for the same job within 30 days, prevent re-entry with a polite notice informing them their application is already in review.
