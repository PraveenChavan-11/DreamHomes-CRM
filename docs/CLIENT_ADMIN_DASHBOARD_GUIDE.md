# TalentHub Consultant — Enterprise Admin Dashboard Client Guide

---

## 1. Access Credentials & Login Portal

| Detail | Information |
|---|---|
| **Admin Portal URL** | [https://talenthubconsultant.com/admin](https://talenthubconsultant.com/admin) |
| **Default Username / Email** | `admin@talenthubconsultant.com` |
| **Initial Password** | `TalentHub@Admin2026!` |
| **Access Level** | Super Administrator (Full Management Access) |

> **Security Note:** Upon your first login, we recommend navigating to the **Staff Users** tab to update your password or add personalized recruiter accounts.

---

## 2. Page-by-Page Feature & Operations Guide

### A. Core Recruitment & Candidate Pipeline

#### 1. Executive Overview (`Dashboard`)
* **Purpose:** Real-time snapshot of recruitment operations and business pipeline.
* **What you can View:**
  * Total active jobs, new candidate applications, pending employer enquiries, and hired statistics.
  * 30-day interactive application volume chart.
  * Top-performing job mandates by applicant interest.
  * Quick-action shortcut buttons for rapid workflow.

#### 2. Job Mandates (`/jobs`)
* **Purpose:** Manage all corporate job postings displayed on the public website.
* **Actions Available:**
  * **Create:** Add new mandates with Title, Industry Category, Location (Pune / Hybrid / Remote), Experience Range, Salary Package, Job Description, and Requirements.
  * **View:** Inspect full job details, applicant count, and publishing status.
  * **Edit:** Modify job requirements, compensation, deadline, or category.
  * **Status Controls:** Toggle between `PUBLISHED`, `DRAFT`, and `CLOSED`.
  * **Delete:** Permanently remove obsolete job postings.

#### 3. Applications Pipeline (`/applications`)
* **Purpose:** Process applicants through recruitment evaluation stages.
* **Actions Available:**
  * **View:** Search and filter applications by Job Title, Candidate Name, and Current Stage.
  * **Stage Transitions:** Move candidates across stages:
    * `NEW` → `SCREENING` → `SHORTLISTED` → `INTERVIEW_SCHEDULED` → `OFFERED` → `REJECTED` / `WITHDRAWN`.
  * **Recruiter Notes:** Add private internal evaluation notes for team visibility.
  * **Resume Access:** One-click direct link to open and inspect the applicant's resume.

#### 4. Talent Pool & Candidates (`/candidates`)
* **Purpose:** Master directory of all candidates who have applied or registered.
* **Actions Available:**
  * **View:** Full profile details including Email, Phone, Total Experience, Current CTC, Expected CTC, Notice Period, Current Company, and Primary Skills.
  * **Search & Filter:** Find candidates by keyword, skill, experience, or role.
  * **Edit:** Update candidate contact info, availability, or status.
  * **Delete / Archive:** Remove outdated records.

#### 5. Resume Bank (`/resume-bank`)
* **Purpose:** Centralized, private storage vault for candidate CVs (PDF/DOCX).
* **Actions Available:**
  * **Preview:** Secure in-browser preview without downloading to your personal machine.
  * **Download:** Download resumes locally for client submissions.
  * **Search:** Filter by applicant name or job mandate title.

---

### B. Business Development & Client CRM

#### 6. Employer Accounts (`/employers`)
* **Purpose:** Manage hiring corporate companies and client contacts across Pune.
* **Actions Available:**
  * **Create:** Register new client companies with Company Name, Industry, Contact Person, Official Email, Phone, and Office Location.
  * **View:** See all jobs associated with each employer.
  * **Edit:** Update company contact details and account manager assignments.
  * **Delete:** Remove inactive employer profiles.

#### 7. Inquiries & Mandates (`/enquiries`)
* **Purpose:** Manage incoming employer hiring requests and website contact submissions.
* **Actions Available:**
  * **View:** Submissions with company details, staffing requirements, and contact phone/email.
  * **Status Update:** Mark enquiries as `NEW`, `IN_REVIEW`, `CONTACTED`, or `CLOSED`.
  * **Follow-up Notes:** Record conversation logs and next follow-up dates.

---

### C. Content Management (CMS) & SEO

#### 8. Dynamic Pages Manager (`/pages`)
* **Purpose:** Manage custom marketing pages and legal policy pages.
* **Actions Available:**
  * **Create / Edit:** Rich text content, custom URL slugs (e.g. `/terms`, `/privacy-policy`), and SEO Meta tags.
  * **Publish / Draft:** Control live public visibility.
  * **Delete:** Remove unwanted pages.

#### 9. Insights & Blogs (`/content`)
* **Purpose:** Manage articles published on the `/insights` blog section.
* **Actions Available:**
  * **Create:** Author thought leadership articles with Title, Excerpt, Body, Cover Image URL, Tags, and Author Name.
  * **Edit / Update:** Refine articles to maintain high Google search visibility.
  * **Delete:** Archive or delete older posts.

#### 10. Media Library (`/media`)
* **Purpose:** Asset repository for logos, corporate banners, and press media.
* **Actions Available:**
  * **Upload:** Upload PNG, JPG, WebP, and SVG images.
  * **Copy URL:** Instant copy of direct CDN/local URLs to paste into job postings or blogs.
  * **Delete:** Clean up unused media.

#### 11. SEO & Sitemaps (`/seo`)
* **Purpose:** Manage search engine indexing for Google and Bing.
* **Actions Available:**
  * **View Sitemaps:** Inspect live index status for `sitemap-jobs.xml` and `sitemap-pages.xml`.
  * **Regenerate:** One-click manual rebuild of dynamic XML sitemaps.

---

### D. Communications & Automation

#### 12. Email Templates (`/templates`)
* **Purpose:** Customize automated emails sent to candidates and corporate clients.
* **What you can Customize:**
  * Candidate Application Acknowledgement (`app_candidate_ack`)
  * Talent Pool General Registration (`app_talent_pool_ack`)
  * Employer Hiring Inquiry Confirmation (`employer_enquiry_ack`)
  * Interview Shortlisting Notification (`interview_shortlist`)
* **Features:** Dynamic placeholders like `{{candidate_name}}`, `{{job_title}}`, `{{company_name}}`.

#### 13. Email Outbox & Logs (`/email-logs`)
* **Purpose:** Audit log of all emails dispatched via Hostinger SMTP.
* **Actions Available:**
  * View delivery status (`SENT`, `PENDING`, `FAILED`).
  * Inspect error logs if a recipient's inbox bounced.
  * Manual **Resend** button for immediate redelivery.

#### 14. Notifications Center (`/notifications`)
* **Purpose:** In-app real-time alerts.
* **Features:** Instant notifications when a candidate submits a resume or an employer submits a hiring mandate.

---

### E. Security, Administration & Diagnostics

#### 15. Staff Users & Access Control (`/users`)
* **Purpose:** Team management and role assignment.
* **Roles Available:**
  * **Super Administrator:** Complete access to all features, security, and settings.
  * **Senior Recruiter:** Full access to Jobs, Candidates, Applications, Resumes, and CRM.
  * **Content Editor:** Access limited to Blog Insights and CMS content.
* **Actions:** Add new recruiters, update passwords, activate/deactivate accounts.

#### 16. Security Center (`/security`)
* **Purpose:** Security monitoring and telemetry.
* **Features:** View login history, IP access records, and active sessions.

#### 17. Audit Logs (`/audit-logs`)
* **Purpose:** Compliance tracking.
* **Features:** Records every staff action (who created a job, who changed candidate status, who deleted a record) with timestamp and IP address.

#### 18. Website Settings (`/settings`)
* **Purpose:** Update global agency details shown on the website footer and contact page.
* **What you can Edit:** Office Address, Contact Phone numbers, Email, WhatsApp number, and Business Hours.

#### 19. System Health (`/health`)
* **Purpose:** Live infrastructure diagnostics.
* **Features:** Verification of MySQL database, Hostinger SMTP connection, Resume storage vault permissions, and Background Cron Heartbeat.

#### 20. Reports & Data Exports (`/reports`)
* **Purpose:** Reporting and offline analytics.
* **Actions:** One-click CSV export of Candidates, Applications, and Job Performance.
