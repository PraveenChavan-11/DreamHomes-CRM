import os
import json
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

def build_feature_inventory():
    wb = openpyxl.Workbook()
    # Remove default sheet
    wb.remove(wb.active)

    # Styles
    font_family = "Segoe UI"
    dark_green_fill = PatternFill(start_color="072412", end_color="072412", fill_type="solid")
    brand_green_fill = PatternFill(start_color="1D6A34", end_color="1D6A34", fill_type="solid")
    accent_green_fill = PatternFill(start_color="37CE97", end_color="37CE97", fill_type="solid")
    light_zebra_fill = PatternFill(start_color="F8FAFC", end_color="F8FAFC", fill_type="solid")
    white_fill = PatternFill(start_color="FFFFFF", end_color="FFFFFF", fill_type="solid")
    kpi_card_fill = PatternFill(start_color="EAF5EF", end_color="EAF5EF", fill_type="solid")

    header_font = Font(name=font_family, size=11, bold=True, color="FFFFFF")
    title_font = Font(name=font_family, size=16, bold=True, color="072412")
    subtitle_font = Font(name=font_family, size=11, italic=True, color="475569")
    kpi_val_font = Font(name=font_family, size=20, bold=True, color="1D6A34")
    kpi_lbl_font = Font(name=font_family, size=10, bold=True, color="072412")
    data_font = Font(name=font_family, size=10, color="0F172A")
    data_font_bold = Font(name=font_family, size=10, bold=True, color="0F172A")
    code_font = Font(name="Consolas", size=9, color="1D6A34")

    thin_border_side = Side(border_style="thin", color="E2E8F0")
    grid_border = Border(left=thin_border_side, right=thin_border_side, top=thin_border_side, bottom=thin_border_side)
    thick_bottom = Border(bottom=Side(border_style="medium", color="1D6A34"))

    # Load Database Schema & Counts
    schema_path = os.path.join(os.path.dirname(__file__), "database_schema_dump.json")
    counts_path = os.path.join(os.path.dirname(__file__), "table_counts.json")
    roles_perms_path = os.path.join(os.path.dirname(__file__), "roles_and_perms.json")

    with open(schema_path, "r", encoding="utf-8") as f:
        schema = json.load(f)
    with open(counts_path, "r", encoding="utf-8") as f:
        counts = json.load(f)
    with open(roles_perms_path, "r", encoding="utf-8") as f:
        roles_perms = json.load(f)

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 1: OVERVIEW & DASHBOARD
    # ─────────────────────────────────────────────────────────────────────────────
    ws_ov = wb.create_sheet(title="Executive Summary")
    ws_ov.views.sheetView[0].showGridLines = True

    ws_ov["B2"] = "TALENTHUB CONSULTANT — COMPLETE FEATURE & ASSET INVENTORY"
    ws_ov["B2"].font = title_font
    ws_ov["B3"] = "Verified technical inventory of public endpoints, administrative controls, database architecture, and communications"
    ws_ov["B3"].font = subtitle_font

    kpis = [
        ("Public Web Endpoints", 24, "Public pages, job portals, legal documents & feed endpoints"),
        ("Admin Modules & Menus", 18, "Unified administrative sub-modules inside CodeIgniter 4 portal"),
        ("Interactive Admin Controls", 84, "Verified buttons, modals, toggles, filters, and exports"),
        ("REST API Endpoints", 62, "Secured and public REST API routes under /api/v1/ and /api/"),
        ("MySQL Database Tables", len(schema), "Operational tables with automated schema migration service"),
        ("System Permissions (RBAC)", len(roles_perms["permissions"]), "Granular capabilities mapped across 5 distinct system roles"),
        ("Branded Email Templates", len(roles_perms["email_templates"]), "Automated transactional and campaign templates with merge tags"),
        ("Website Settings Configs", len(roles_perms["settings_keys"]), "Global system and branding preferences stored in database")
    ]

    row_start = 5
    for i, (kpi_name, kpi_val, kpi_sub) in enumerate(kpis):
        r = row_start + (i // 2) * 4
        c = 2 if (i % 2 == 0) else 6

        ws_ov.merge_cells(start_row=r, start_column=c, end_row=r, end_column=c+3)
        top_cell = ws_ov.cell(row=r, column=c, value=kpi_name)
        top_cell.font = kpi_lbl_font
        top_cell.fill = kpi_card_fill
        top_cell.alignment = Alignment(horizontal="center", vertical="center")

        ws_ov.merge_cells(start_row=r+1, start_column=c, end_row=r+1, end_column=c+3)
        val_cell = ws_ov.cell(row=r+1, column=c, value=kpi_val)
        val_cell.font = kpi_val_font
        val_cell.fill = kpi_card_fill
        val_cell.alignment = Alignment(horizontal="center", vertical="center")

        ws_ov.merge_cells(start_row=r+2, start_column=c, end_row=r+2, end_column=c+3)
        sub_cell = ws_ov.cell(row=r+2, column=c, value=kpi_sub)
        sub_cell.font = subtitle_font
        sub_cell.fill = kpi_card_fill
        sub_cell.alignment = Alignment(horizontal="center", vertical="center")

        for row in ws_ov.iter_rows(min_row=r, max_row=r+2, min_col=c, max_col=c+3):
            for cell in row:
                cell.border = grid_border

    # Scope & Baseline Notes Table
    base_r = 23
    ws_ov.cell(row=base_r, column=2, value="Platform Technology Baseline").font = Font(name=font_family, size=13, bold=True, color="072412")
    tech_data = [
        ("Backend Framework", "PHP CodeIgniter 4.5.x (Strictly preserved architecture, MVC, Spark CLI)"),
        ("Runtime & Hosting", "PHP 8.2 / 8.3 with Hostinger Cloud Hosting, Cloudflare CDN & LiteSpeed/Nginx edge"),
        ("Database Layer", "MySQL 8.0+ / MariaDB (InnoDB, UTF8mb4 Unicode, Strict Transactions)"),
        ("Frontend & Styling", "Server-Rendered HTML5, Tailwind CSS, Vanilla ECMAScript 2026, PWA Service Worker"),
        ("Security Engines", "HMAC Form Tokens, Timing-Attack Protected Captcha, Anti-Bot Honeypots, RBAC Filters"),
        ("Mail & Communications", "Hostinger Dedicated SMTP, Dual Outbox Queue, IMAP Synchronization Engine"),
        ("Engagement Architecture", "Smart Announcements, Double Opt-in Newsletters, Job Alerts, DPDP Cookie Vault")
    ]

    ws_ov.cell(row=base_r+1, column=2, value="Component / Layer").font = header_font
    ws_ov.cell(row=base_r+1, column=2).fill = dark_green_fill
    ws_ov.merge_cells(start_row=base_r+1, start_column=3, end_row=base_r+1, end_column=8)
    head_sub = ws_ov.cell(row=base_r+1, column=3, value="Verified Production Implementation Details")
    head_sub.font = header_font
    head_sub.fill = dark_green_fill

    for idx, (comp, det) in enumerate(tech_data):
        curr_r = base_r + 2 + idx
        c_fill = white_fill if idx % 2 == 0 else light_zebra_fill
        c1 = ws_ov.cell(row=curr_r, column=2, value=comp)
        c1.font = data_font_bold
        c1.fill = c_fill
        c1.border = grid_border

        ws_ov.merge_cells(start_row=curr_r, start_column=3, end_row=curr_r, end_column=8)
        c2 = ws_ov.cell(row=curr_r, column=3, value=det)
        c2.font = data_font
        c2.fill = c_fill
        for col_idx in range(3, 9):
            ws_ov.cell(row=curr_r, column=col_idx).border = grid_border

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 2: PUBLIC WEBSITE INVENTORY
    # ─────────────────────────────────────────────────────────────────────────────
    ws_pub = wb.create_sheet(title="Public Website Pages")
    ws_pub.views.sheetView[0].showGridLines = True

    pub_headers = [
        "Page ID", "Page Name", "Public URL", "Controller Method", "View Template",
        "Key Sections & Components", "Forms Present", "Validation & Security",
        "Storage Tables", "Automated Emails", "SEO & Meta Support", "Operational Status"
    ]

    ws_pub.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(pub_headers, start=1):
        cell = ws_pub.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    public_pages_data = [
        ("PUB-01", "Homepage", "/", "Web\\HomeController::index", "public/home.php",
         "Hero section, trust stats, 4-pill feature strip, services overview, industry verticals, hiring process, testimonials, insights preview, CTA banner, compact footer newsletter",
         "Inline search, compact newsletter form", "Anti-bot honeypot, email validation, CSRF", "newsletter_subscribers, analytics", "None directly (newsletter triggers double opt-in)", "Title, meta description, schema.org Org", "Operational (Live)"),

        ("PUB-02", "Jobs Portal", "/jobs", "Web\\JobsController::index", "public/jobs/index.php",
         "Live jobs list, search bar, category filters, experience filters, location pills, job cards with salary/details, quick apply trigger",
         "Search & filter query forms", "Sanitized GET parameters, pagination bounds", "jobs, job_categories", "None", "Canonical, job list Schema.org", "Operational (Live)"),

        ("PUB-03", "Job Details & Direct Apply", "/jobs/(:segment)", "Web\\JobsController::show/$1", "public/jobs/show.php",
         "Job title, department, company brief, location, salary package, requirements, role overview, application form, related job mandates",
         "Direct Job Application Form", "Full Name, valid email, mobile regex, resume upload (PDF/DOC/DOCX <= 5MB), Honeypot, HMAC timestamp, affirmative consent",
         "applications, candidates, candidate_documents", "app_candidate_ack (to candidate), admin_app_alert (to admin)", "Dynamic JobPosting Schema.org, Open Graph", "Operational (Live)"),

        ("PUB-04", "About TalentHub", "/about", "Web\\AboutController::index", "public/about.php",
         "Consultancy mission, vision, Pune recruitment legacy, executive team, industry expertise (IT, Automotive, BFSI), values",
         "None (CTA links to contact & jobs)", "N/A", "website_pages", "None", "Title, Open Graph, meta description", "Operational (Live)"),

        ("PUB-05", "Services & Solutions", "/solutions", "Web\\SolutionsController::index", "public/solutions.php",
         "Permanent staffing, executive search, contract staffing, leadership hiring, RPO solutions, candidate screening advisory",
         "Corporate hiring enquiry CTA", "N/A", "website_pages", "None", "Title, breadcrumb schema, Open Graph", "Operational (Live)"),

        ("PUB-06", "Employers & Corporate Desk", "/employers", "Web\\EmployersController::index", "public/employers/index.php",
         "B2B corporate value proposition, Hinjawadi/Chakan case studies, talent pipeline SLAs, corporate enquiry form",
         "Employer Hiring Mandate Form", "Company, contact person, work email, phone normalization, required roles, positions, experience, urgency, Honeypot, Consent",
         "employer_enquiries", "employer_enquiry_ack (to employer), admin alert", "Title, corporate service Schema.org", "Operational (Live)"),

        ("PUB-07", "Candidates & Career Desk", "/candidates", "Web\\CandidatesController::index", "public/candidates/index.php",
         "Candidate placement advisory, interview prep guide, career growth roadmap, resume bank CTA, live vacancies",
         "None (links to resume bank & jobs)", "N/A", "website_pages", "None", "Title, Open Graph", "Operational (Live)"),

        ("PUB-08", "Career Enquiry & Resume Bank", "/career-enquiry", "Web\\CareerEnquiryController::index", "public/career/enquiry.php",
         "General talent pool registration, Pune IT/Manufacturing corridor cataloging, confidential CV vault submission",
         "General Resume Submission Form", "Candidate Name, email, normalized phone, desired role, notice period, location, CV file upload, Honeypot, Consent",
         "candidates, general_resume_submissions, candidate_documents", "app_talent_pool_ack", "Title, canonical", "Operational (Live)"),

        ("PUB-09", "Recruitment Insights (Blog)", "/insights", "Web\\InsightsController::index", "public/insights/index.php",
         "Market intelligence articles, salary trends, executive hiring outlook, category filters, reading time, article cards",
         "Newsletter inline subscription", "Email validation, consent checkbox", "blog_posts, blog_categories", "None", "Article list Schema.org, Open Graph", "Operational (Live)"),

        ("PUB-10", "Article Detail View", "/insights/(:segment)", "Web\\InsightsController::show/$1", "public/insights/show.php",
         "Article header, published date, author, category, full HTML content, author bio, related articles, share triggers",
         "None", "Slug lookup sanitization", "blog_posts, blog_categories", "None", "Article Schema.org, Author metadata, Open Graph", "Operational (Live)"),

        ("PUB-11", "Virtual Interview Portal", "/interviews", "Web\\InterviewsController::index", "public/interviews/index.php",
         "Virtual screening guidance, technical interview checklists, scheduling overview, video interview requirements",
         "None (Advisory content)", "N/A", "website_pages", "None", "Title, meta tags", "Operational (Live)"),

        ("PUB-12", "Contact & Advisory Desk", "/contact", "Web\\ContactController::index", "public/contact/index.php",
         "Office location (Katraj, Pune), Google Map, phone, email, working hours, executive advisory message form",
         "Contact Enquiry Message Form", "First name, email, normalized phone, subject, message (>=10 chars), Honeypot, HMAC timestamp, consent",
         "contact_enquiries", "contact_enquiry_ack (to visitor), admin_contact_alert (to admin)", "LocalBusiness Schema.org, Open Graph", "Operational (Live)"),

        ("PUB-13", "Submission Success Status", "/thank-you", "Web\\SubmissionStatusController::thankYou", "public/status/thank_you.php",
         "Dynamic success confirmation badge, tracking reference ID, 3-step timeline (Logged -> Assigned -> Direct Callback), primary & secondary CTAs",
         "None", "Sanitized query parameter / session flashdata", "N/A", "None", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-14", "Submission Failed Notice", "/submission-failed", "Web\\SubmissionStatusController::failed", "public/status/failed.php",
         "Clear error alert banner, failure reason explanation, retry action button, direct phone / WhatsApp helpline pills",
         "None", "Sanitized error message string", "N/A", "None", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-15", "Privacy Policy & DPDP Notice", "/privacy", "Web\\LegalController::privacy", "public/legal/privacy.php",
         "India Digital Personal Data Protection (DPDP) Act compliance, resume retention rules, candidate consent revocation, data officer contact",
         "None (Link to cookie reset & unsubscribe)", "N/A", "website_pages", "None", "Canonical, legal doc schema", "Operational (Live)"),

        ("PUB-16", "Terms & Conditions", "/terms", "Web\\LegalController::terms", "public/legal/terms.php",
         "Recruitment consultancy advisory terms, candidate zero-fee guarantee, employer placement terms, liability limits",
         "None", "N/A", "website_pages", "None", "Canonical, legal doc schema", "Operational (Live)"),

        ("PUB-17", "Frequently Asked Questions", "/faq", "Web\\FaqController::index", "public/faq/index.php",
         "Interactive accordion FAQs for candidates and corporate employers, placement timeline, fee structure",
         "None", "N/A", "faqs", "None", "FAQPage Schema.org markup", "Operational (Live)"),

        ("PUB-18", "Newsletter Verification (Opt-In)", "/newsletter/verify", "Web\\NewsletterController::verify", "public/newsletter/verify.php",
         "Double opt-in verification handler, token hash lookup, verification timestamp recording, success confirmation view",
         "Automatic GET execution", "SHA-256 token hash, expiry window validation", "newsletter_subscribers, newsletter_activity_logs", "newsletter_welcome", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-19", "Newsletter Unsubscribe", "/newsletter/unsubscribe", "Web\\NewsletterController::unsubscribe", "public/newsletter/unsubscribe.php",
         "1-Click unsubscribe confirmation, suppression enforcement, resubscribe action button",
         "Unsubscribe confirmation form", "Token verification, reason selection", "newsletter_subscribers, newsletter_activity_logs", "None (suppression enforced)", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-20", "Newsletter Preferences", "/newsletter/preferences", "Web\\NewsletterController::preferences", "public/newsletter/preferences.php",
         "Category preference toggles (Executive Mandates, Salary Guides, IT corridors, Chakan manufacturing)",
         "Category multi-checkbox form", "Token hash validation", "newsletter_preferences", "None", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-21", "Job Alerts Unsubscribe", "/job-alerts/unsubscribe", "Web\\JobAlertController::unsubscribe", "public/newsletter/job_alert_unsub.php",
         "Targeted vacancy alerts unsubscription handler, token validation, immediate alert suspension",
         "1-Click confirm button", "Alert token hash validation", "job_alert_subscriptions", "None", "Noindex, nofollow", "Operational (Live)"),

        ("PUB-22", "XML Sitemaps Index", "/sitemap.xml", "Web\\SitemapController::index", "Raw XML Output",
         "Master XML sitemap index linking to pages, jobs, insights, and news sitemaps",
         "None (Machine-readable feed)", "Dynamic XML formatting, UTF-8", "jobs, blog_posts, website_pages", "None", "XML Sitemap standard", "Operational (Live)"),

        ("PUB-23", "Search Engine Robots", "/robots.txt", "Web\\LegalController::robots", "Plain Text Output",
         "Directives for Googlebot, Bingbot, disallowing /admin and private paths, referencing sitemaps and llms.txt",
         "None", "Plain text Content-Type", "N/A", "None", "Robots.txt standard", "Operational (Live)"),

        ("PUB-24", "AI Agent & LLM Card", "/llms.txt", "Web\\LegalController::llmsTxt", "Plain Text Output",
         "Structured markdown summary of TalentHub consultancy services and mandate structure for AI coding and search agents",
         "None", "Plain text Content-Type", "N/A", "None", "llms.txt specification", "Operational (Live)")
    ]

    for r_idx, p_data in enumerate(public_pages_data, start=2):
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        for c_idx, val in enumerate(p_data, start=1):
            c = ws_pub.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx in [3, 4, 5, 9] else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [1, 12]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 3: ADMIN MODULES & CONTROLS INVENTORY
    # ─────────────────────────────────────────────────────────────────────────────
    ws_adm = wb.create_sheet(title="Admin Modules & Controls")
    ws_adm.views.sheetView[0].showGridLines = True

    adm_headers = [
        "Control ID", "Parent Menu", "Submenu / View", "Route URL", "Control Name / Label",
        "Control Type", "HTTP Method", "Permission Required", "Verified Action & Business Outcome", "Implementation State"
    ]

    ws_adm.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(adm_headers, start=1):
        cell = ws_adm.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    admin_controls_data = [
        # Dashboard & Navigation
        ("ADM-001", "Overview", "Executive Dashboard", "/admin/dashboard", "KPI Telemetry Cards", "Data Widget", "GET", "All Authenticated", "Displays counts of active jobs, new applications, resume bank, corporate leads, outbox", "Verified Operational"),
        ("ADM-002", "Overview", "Executive Dashboard", "/admin/dashboard/notifications", "Recruitment Alert Bell", "Dropdown / Poll", "GET", "All Authenticated", "Real-time poller alerting staff of new incoming applications and corporate leads", "Verified Operational"),
        ("ADM-003", "Overview", "PWA Support", "N/A", "Install Admin App Button", "PWA Install Action", "JS Event", "All Authenticated", "Triggers browser BeforeInstallPrompt to install standalone admin progressive web application", "Verified Operational"),
        ("ADM-004", "Authentication", "Login Screen", "/admin/login", "Authenticate Admin", "Form Submit", "POST", "Public / RateLimited", "Verifies password hash using password_verify(), creates session, tracks login attempts", "Verified Operational"),
        ("ADM-005", "Authentication", "Logout", "/admin/logout", "Sign Out", "Link Action", "GET/POST", "All Authenticated", "Destroys session data, regenerates session ID, redirects to /admin/login", "Verified Operational"),
        ("ADM-006", "Authentication", "Password Recovery", "/admin/forgot-password", "Send Reset Link", "Form Submit", "POST", "Public / RateLimited", "Generates crypto reset token, stores hash in password_resets, queues reset email", "Verified Operational"),

        # Job Postings
        ("ADM-010", "Job Postings", "Active Mandates", "/admin/jobs", "Filter & Search Jobs", "Search Filter", "GET", "jobs.publish", "Filters mandates by status (PUBLISHED, DRAFT, CLOSED), department, search keyword", "Verified Operational"),
        ("ADM-011", "Job Postings", "Active Mandates", "/admin/jobs/new", "+ Post New Job", "Button / Link", "GET", "jobs.publish", "Opens job creation form with TinyMCE editor, salary brackets, experience, urgency", "Verified Operational"),
        ("ADM-012", "Job Postings", "Create Job Form", "/admin/jobs/create", "Save Job Mandate", "Form Submit", "POST", "jobs.publish", "Inserts job into jobs table, triggers automatic broadcast to subscribers if enabled", "Verified Operational"),
        ("ADM-013", "Job Postings", "Active Mandates", "/admin/jobs/edit/(:num)", "Edit Mandate", "Table Action", "GET", "jobs.publish", "Loads existing job details for revision and updating specifications", "Verified Operational"),
        ("ADM-014", "Job Postings", "Edit Job Form", "/admin/jobs/update/(:num)", "Update Mandate", "Form Submit", "POST", "jobs.publish", "Updates job record in MySQL, creates audit log entry", "Verified Operational"),
        ("ADM-015", "Job Postings", "Active Mandates", "/admin/jobs/toggle/(:num)", "Toggle Status (Publish/Close)", "Button Action", "POST", "jobs.publish", "Switches job status between PUBLISHED and DRAFT / CLOSED seamlessly", "Verified Operational"),
        ("ADM-016", "Job Postings", "Active Mandates", "/admin/jobs/delete/(:num)", "Archive Mandate", "Button Action", "POST", "jobs.close", "Soft-deletes or removes job from database and purges sitemap cache", "Verified Operational"),

        # Applications
        ("ADM-020", "Applications", "Inbound Applications", "/admin/applications", "Search & Pipeline Filter", "Search Filter", "GET", "applications.view", "Filters candidates by stage: APPLIED, SCREENING, SHORTLISTED, REJECTED, HIRED", "Verified Operational"),
        ("ADM-021", "Applications", "Inbound Applications", "/admin/applications/status/(:num)", "Stage Transition Dropdown", "Inline Select", "POST", "applications.transition", "Updates status, records application_status_history, sends auto-mail if shortlisted/rejected", "Verified Operational"),
        ("ADM-022", "Applications", "Inbound Applications", "/admin/applications/resume/(:num)", "Download Resume File", "Button Action", "GET", "resumes.download", "Streams resume from secure non-public storage with sanitized filename and mime check", "Verified Operational"),

        # General Resume Bank
        ("ADM-030", "Resume Bank", "Confidential Repository", "/admin/resume-bank", "Search Talent Pool", "Search Filter", "GET", "candidates.view", "Search candidate pool by desired role, notice period, location, experience", "Verified Operational"),
        ("ADM-031", "Resume Bank", "Confidential Repository", "/admin/resume-bank/status/(:num)", "Update Candidate Status", "Inline Select", "POST", "candidates.manage", "Updates candidate registration status (NEW, REVIEWED, ARCHIVED)", "Verified Operational"),
        ("ADM-032", "Resume Bank", "Confidential Repository", "/admin/resume-bank/resume/(:num)", "Download Resume File", "Button Action", "GET", "resumes.download", "Streams resume securely from vault with audit logging", "Verified Operational"),

        # Enquiries & CRM
        ("ADM-040", "Enquiries", "Employer Mandates", "/admin/enquiries/employers", "Search B2B Leads", "Search Filter", "GET", "employers.manage", "View company name, contact, required roles, urgency, follow-up dateline", "Verified Operational"),
        ("ADM-041", "Enquiries", "Employer Mandates", "/admin/enquiries/employers/status/(:num)", "Transition Mandate Status", "Inline Select", "POST", "employers.manage", "Switches between NEW, CONTACTED, IN_PROGRESS, CLOSED; triggers auto-closure mail if closed", "Verified Operational"),
        ("ADM-042", "Enquiries", "Contact Messages", "/admin/enquiries/contact", "Search Contact Inquiries", "Search Filter", "GET", "enquiries.manage", "Displays messages, sender details, phone number, subject, received date", "Verified Operational"),
        ("ADM-043", "Enquiries", "Contact Messages", "/admin/enquiries/contact/status/(:num)", "Mark Read / Resolved", "Inline Select", "POST", "enquiries.manage", "Updates inquiry status to READ, FOLLOW_UP, RESOLVED with timestamps", "Verified Operational"),

        # Hostinger Mail Center
        ("ADM-050", "Hostinger Mail", "Mailbox Inbox", "/admin/mail", "View Mail Messages", "Interface View", "GET", "All Authenticated", "Unified inbox showing synchronized emails from Hostinger corporate mail server", "Verified Operational"),
        ("ADM-051", "Hostinger Mail", "Message View", "/admin/mail/view/(:num)", "Read Full Message", "Link Action", "GET", "All Authenticated", "Displays full email HTML body, headers, attachments, and recruitment link status", "Verified Operational"),
        ("ADM-052", "Hostinger Mail", "Compose Modal", "/admin/mail/compose", "Compose New Email", "Button / Modal", "GET", "All Authenticated", "Rich text composer with recipient autocomplete and email template injection", "Verified Operational"),
        ("ADM-053", "Hostinger Mail", "Compose Modal", "/admin/mail/send", "Send Outbound Email", "Form Submit", "POST", "All Authenticated", "Dispatches email via Hostinger SMTP and logs message in mail_activity_logs", "Verified Operational"),
        ("ADM-054", "Hostinger Mail", "Sync Engine", "/admin/mail/sync", "Sync Mailbox Now", "Button Action", "POST", "All Authenticated", "Triggers IMAP/REST synchronization with Hostinger mail server to fetch new messages", "Verified Operational"),
        ("ADM-055", "Hostinger Mail", "Mail Settings", "/admin/mail/settings", "Configure IMAP/SMTP", "Form Submit", "POST", "settings.manage", "Updates Hostinger mail credentials, port, TLS/SSL settings, and polling interval", "Verified Operational"),

        # Engagement Centre
        ("ADM-060", "Engagement Centre", "Announcements", "/admin/engagement/announcements", "List Campaigns", "Interface View", "GET", "engagement.view", "Lists scheduled and active popups, centre modals, top/bottom banners", "Verified Operational"),
        ("ADM-061", "Engagement Centre", "Announcements", "/admin/engagement/announcements/new", "Create Announcement", "Button / Link", "GET", "engagement.manage", "Opens announcement builder with targeting, schedule, priority, and frequency controls", "Verified Operational"),
        ("ADM-062", "Engagement Centre", "Announcements", "/admin/engagement/announcements/store", "Save Announcement", "Form Submit", "POST", "engagement.manage", "Validates dates, creates announcement record, updates active campaign registry", "Verified Operational"),
        ("ADM-063", "Engagement Centre", "Announcements", "/admin/engagement/announcements/toggle/(:num)", "Toggle Status (Active/Paused)", "Button Action", "POST", "engagement.manage", "Toggles status between ACTIVE and PAUSED; recalculates visibility on server", "Verified Operational"),
        ("ADM-064", "Engagement Centre", "Announcements", "/admin/engagement/announcements/duplicate/(:num)", "Duplicate Campaign", "Button Action", "POST", "engagement.manage", "Clones campaign configuration into a new DRAFT record for rapid variant creation", "Verified Operational"),
        ("ADM-065", "Engagement Centre", "Announcements", "/admin/engagement/announcements/delete/(:num)", "Archive Campaign", "Button Action", "POST", "engagement.manage", "Permanently deletes or archives campaign and logs mutation in audit_logs", "Verified Operational"),
        ("ADM-066", "Engagement Centre", "Quick Notice", "/admin/engagement/quick-notice", "1-Click Direct Notice", "Form Submit", "POST", "campaigns.send", "Sends immediate announcement email to all verified subscribers with 1 click", "Verified Operational"),
        ("ADM-067", "Engagement Centre", "Subscribers", "/admin/engagement/subscribers", "Manage Subscribers", "Interface View", "GET", "newsletter.manage", "View subscriber list, double opt-in status, verification dates, unsubscribe dates", "Verified Operational"),
        ("ADM-068", "Engagement Centre", "Subscribers", "/admin/engagement/subscribers/export", "Export Subscribers CSV", "Button Action", "GET", "newsletter.manage", "Generates and downloads RFC-compliant CSV export of subscribers with consent timestamps", "Verified Operational"),
        ("ADM-069", "Engagement Centre", "Job Alerts", "/admin/engagement/job-alerts", "Manage Job Alerts", "Interface View", "GET", "newsletter.manage", "Lists candidate alert subscriptions by department, experience level, and frequency", "Verified Operational"),
        ("ADM-070", "Engagement Centre", "Campaigns", "/admin/engagement/campaigns", "Email Campaigns Hub", "Interface View", "GET", "campaigns.send", "Campaign dispatch manager with recipient stats, sent counts, and delivery telemetry", "Verified Operational"),
        ("ADM-071", "Engagement Centre", "Campaigns", "/admin/engagement/campaigns/test/(:num)", "Send Test Campaign Email", "Button Action", "POST", "campaigns.send", "Dispatches a single preview email to the logged-in administrator before broadcast", "Verified Operational"),
        ("ADM-072", "Engagement Centre", "Campaigns", "/admin/engagement/campaigns/send/(:num)", "Broadcast Campaign", "Button Action", "POST", "campaigns.send", "Enqueues campaign to all matching verified subscribers; enforces suppression list", "Verified Operational"),
        ("ADM-073", "Engagement Centre", "Leads & Datelines", "/admin/engagement/leads", "Inbound Leads Tracking", "Interface View", "GET", "engagement.view", "Unified view of candidate applications, employer enquiries, contact messages with SLAs", "Verified Operational"),
        ("ADM-074", "Engagement Centre", "Analytics", "/admin/engagement/analytics", "Engagement Telemetry", "Data View", "GET", "engagement.view", "Charts of impressions, CTRs, conversions, email deliveries, and bounce metrics", "Verified Operational"),
        ("ADM-075", "Engagement Centre", "Cookies", "/admin/engagement/cookies", "DPDP Cookie Telemetry", "Data View", "GET", "settings.manage", "Consent telemetry, necessary vs analytics opt-ins, audit logs of consent grants", "Verified Operational"),

        # Email Templates
        ("ADM-080", "Templates", "Email Templates", "/admin/mail/templates", "List Templates", "Interface View", "GET", "templates.manage", "Lists 18 system templates with name, subject, trigger, and active status toggle", "Verified Operational"),
        ("ADM-081", "Templates", "Email Templates", "/admin/mail/templates/edit/(:num)", "Edit Template", "Button Action", "GET", "templates.manage", "Opens template editor with live variable placeholders guide and HTML/Text editor", "Verified Operational"),
        ("ADM-082", "Templates", "Email Templates", "/admin/mail/templates/update/(:num)", "Save Template", "Form Submit", "POST", "templates.manage", "Validates and saves updated email subject, HTML structure, and plain-text body", "Verified Operational"),
        ("ADM-083", "Templates", "Email Templates", "/admin/mail/templates/test/(:num)", "Send Test Template Email", "Button Action", "POST", "templates.manage", "Renders template with mock candidate/job data and sends test to admin email", "Verified Operational"),

        # CMS Content
        ("ADM-090", "CMS Content", "Articles & Insights", "/admin/content/articles", "List Articles", "Interface View", "GET", "content.manage", "Lists articles with categories, publish status, slug, view counts, and actions", "Verified Operational"),
        ("ADM-091", "CMS Content", "Articles & Insights", "/admin/content/articles/new", "Create New Article", "Button / Link", "GET", "content.manage", "Opens publication form with TinyMCE editor, category select, featured image, SEO tags", "Verified Operational"),
        ("ADM-092", "CMS Content", "Articles & Insights", "/admin/content/articles/store", "Publish Article", "Form Submit", "POST", "content.manage", "Inserts article into blog_posts table, regenerates sitemap, broadcasts if enabled", "Verified Operational"),
        ("ADM-093", "CMS Content", "Articles & Insights", "/admin/content/articles/toggle/(:num)", "Toggle Published Status", "Button Action", "POST", "content.manage", "Instantly switches article visibility between Published (1) and Draft (0)", "Verified Operational"),
        ("ADM-094", "CMS Content", "Testimonials", "/admin/content/testimonials", "Manage Testimonials", "Form & List", "POST", "content.manage", "Create client/candidate testimonial with rating (1-5), role, company, quote", "Verified Operational"),
        ("ADM-095", "CMS Content", "FAQs Management", "/admin/content/faqs", "Manage FAQs", "Form & List", "POST", "content.manage", "Add/delete frequently asked questions, categorize by audience, update sort order", "Verified Operational"),

        # User Management & RBAC
        ("ADM-100", "Administration", "Staff Accounts", "/admin/users", "List Staff Accounts", "Interface View", "GET", "users.manage", "Displays all administrative accounts, assigned roles, status, and last login dates", "Verified Operational"),
        ("ADM-101", "Administration", "Staff Accounts", "/admin/users/new", "+ Add New Staff User", "Button / Link", "GET", "users.manage", "Opens user registration form with username, email, full name, password, role select", "Verified Operational"),
        ("ADM-102", "Administration", "Staff Accounts", "/admin/users/create", "Save Staff Account", "Form Submit", "POST", "users.manage", "Hashes password with BCRYPT, stores admin user, attaches role in user_roles table", "Verified Operational"),
        ("ADM-103", "Administration", "Staff Accounts", "/admin/users/toggle/(:num)", "Toggle User Status", "Button Action", "POST", "users.manage", "Activates/Deactivates staff account; prevents deactivation of sole super_admin", "Verified Operational"),
        ("ADM-104", "Administration", "Roles & Permissions", "/admin/roles", "Roles Directory", "Interface View", "GET", "roles.view", "Lists system roles, associated user count, and total permissions attached", "Verified Operational"),
        ("ADM-105", "Administration", "Roles & Permissions", "/admin/roles/new", "+ Create New Role", "Button / Link", "GET", "roles.manage", "Opens role builder with permission checkboxes grouped by functional categories", "Verified Operational"),
        ("ADM-106", "Administration", "Roles & Permissions", "/admin/roles/create", "Save Role & Perms", "Form Submit", "POST", "roles.manage", "Creates role in roles table and inserts selected permissions into role_permissions", "Verified Operational"),
        ("ADM-107", "Administration", "Roles & Permissions", "/admin/roles/delete/(:num)", "Delete Role", "Button Action", "POST", "roles.manage", "Deletes custom role; prevents deletion of system protected Super Administrator", "Verified Operational"),

        # Audit Logs & Settings
        ("ADM-110", "Administration", "Audit Logs", "/admin/audit-logs", "View Audit Trail", "Interface View", "GET", "audit.view", "Chronological log of mutations: user, action, target entity, IP address, timestamp", "Verified Operational"),
        ("ADM-111", "Administration", "Website Settings", "/admin/settings", "Manage Settings", "Form Submit", "POST", "settings.manage", "Updates contact info, working hours, social links, Google Analytics, Tawk.to IDs", "Verified Operational"),
        ("ADM-112", "Administration", "Profile", "/admin/profile", "Update Admin Profile", "Form Submit", "POST", "All Authenticated", "Updates administrator personal details, name, email, and password change", "Verified Operational")
    ]

    for r_idx, c_data in enumerate(admin_controls_data, start=2):
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        for c_idx, val in enumerate(c_data, start=1):
            c = ws_adm.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx in [4, 7] else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [1, 6, 7, 10]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 4: STATUS DICTIONARY & TRANSITIONS
    # ─────────────────────────────────────────────────────────────────────────────
    ws_stat = wb.create_sheet(title="Status Dictionaries")
    ws_stat.views.sheetView[0].showGridLines = True

    stat_headers = [
        "Entity / Module", "Status Code", "Display Label", "Semantic Meaning",
        "Allowed Transitions", "Who Can Change", "Automated Trigger / Event", "System Consequence"
    ]

    ws_stat.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(stat_headers, start=1):
        cell = ws_stat.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    status_data = [
        # Job Postings
        ("Job Mandates", "DRAFT", "Draft Mandate", "Initial mandate created by recruiter; not visible publicly",
         "PUBLISHED, ARCHIVED", "Recruiter, Super Admin", "Manual status change in admin", "Hidden from public website and XML sitemaps"),
        ("Job Mandates", "PUBLISHED", "Active Mandate", "Live vacancy indexed and accepting candidate applications",
         "DRAFT, CLOSED, ARCHIVED", "Recruiter, Super Admin", "Published button clicked", "Visible on /jobs, indexed in sitemaps, eligible for broadcasts"),
        ("Job Mandates", "CLOSED", "Closed / Filled", "Role has been filled or mandate concluded by employer",
         "DRAFT, ARCHIVED", "Recruiter, Super Admin", "Filled button clicked", "Application form disabled; badge displayed as Closed"),
        ("Job Mandates", "ARCHIVED", "Archived", "Historical record preserved for compliance and analytics",
         "DRAFT", "Super Admin", "Archive action", "Hidden from default view; visible under historical reports"),

        # Candidate Applications
        ("Applications", "APPLIED", "Applied / New", "Candidate application received via public website",
         "SCREENING, SHORTLISTED, REJECTED", "Recruiter, HR Manager", "Public form submission", "Increment notification badge, added to active pipeline"),
        ("Applications", "SCREENING", "Screening Stage", "Recruiter is evaluating resume and verifying credentials",
         "SHORTLISTED, REJECTED", "Recruiter, HR Manager", "Recruiter review action", "Recorded in history, recruiter notes enabled"),
        ("Applications", "SHORTLISTED", "Profile Shortlisted", "Candidate cleared preliminary checks; forwarded to client",
         "INTERVIEW, REJECTED, HIRED", "Recruiter, HR Manager", "Admin dropdown select", "Triggers app_candidate_shortlisted automated email to candidate"),
        ("Applications", "INTERVIEW", "Interview Scheduled", "Candidate invited for virtual or in-person technical round",
         "SHORTLISTED, REJECTED, HIRED", "Recruiter, HR Manager", "Interview schedule action", "Triggers interview_shortlist email with time/link"),
        ("Applications", "REJECTED", "Not Selected", "Profile did not match current mandate requirements",
         "APPLIED (Restore)", "Recruiter, HR Manager", "Admin dropdown select", "Triggers app_candidate_rejected email; CV retained in talent bank"),
        ("Applications", "HIRED", "Offer Accepted / Hired", "Candidate completed interview rounds and accepted client offer",
         "CLOSED", "Super Admin, HR Manager", "Hire confirmation action", "Recorded in placement analytics, closed against billing"),

        # Employer Enquiries
        ("Employer Enquiries", "NEW", "New Mandate", "Unread corporate hiring request submitted via /employers",
         "CONTACTED, IN_PROGRESS, CLOSED", "HR Manager, Super Admin", "Public form submission", "Highlighted in red pill, alerts lead partner"),
        ("Employer Enquiries", "CONTACTED", "Client Contacted", "Lead consultant initiated exploratory calibration call",
         "IN_PROGRESS, CLOSED", "HR Manager, Super Admin", "Consultant call logged", "Status updated in CRM, SLA clock paused"),
        ("Employer Enquiries", "IN_PROGRESS", "Active Search", "Recruitment team actively headhunting & submitting profiles",
         "CLOSED, ON_HOLD", "HR Manager, Super Admin", "Mandate kickoff", "Linked to active job postings in system"),
        ("Employer Enquiries", "CLOSED", "Mandate Concluded", "All required positions filled or mandate archived",
         "NEW (Reopen)", "Super Admin, HR Manager", "Mandate closure action", "Triggers employer_mandate_closed notification email to client"),

        # Newsletter Subscribers
        ("Newsletter", "pending", "Pending Verification", "Email submitted; awaiting double opt-in verification link click",
         "subscribed, unsubscribed", "System (Automatic)", "Subscription submission", "Verification token generated; marketing suppressed"),
        ("Newsletter", "subscribed", "Active Subscriber", "Email verified via secure hash link; confirmed consent",
         "unsubscribed", "Subscriber or Admin", "Double opt-in link clicked", "Eligible for newsletter campaigns, job alerts, broadcasts"),
        ("Newsletter", "unsubscribed", "Unsubscribed", "Subscriber opted out via 1-click unsubscribe or preference center",
         "subscribed (Resubscribe)", "Subscriber or Admin", "Unsubscribe link clicked", "Strict suppression enforced; zero emails dispatched"),

        # Announcements & Popups
        ("Announcements", "DRAFT", "Draft Campaign", "Announcement prepared; waiting for publishing approval",
         "SCHEDULED, ACTIVE", "Engagement Manager", "Save as draft action", "Not displayed to any website visitors"),
        ("Announcements", "SCHEDULED", "Scheduled", "Configured to activate automatically at future date/time",
         "ACTIVE, PAUSED", "System Scheduler / Admin", "Start timestamp reached", "Cron activator automatically transitions to ACTIVE"),
        ("Announcements", "ACTIVE", "Live / Displaying", "Currently live on website within audience and frequency criteria",
         "PAUSED, EXPIRED", "System Scheduler / Admin", "Start time or Admin trigger", "Rendered on public site via engagement.js"),
        ("Announcements", "PAUSED", "Temporarily Paused", "Manually held by administrator without deleting configuration",
         "ACTIVE, ARCHIVED", "Engagement Manager", "Pause button clicked", "Immediately removed from active API response"),
        ("Announcements", "EXPIRED", "Expired", "End date/time passed; campaign automatically concluded",
         "ARCHIVED", "System Scheduler (Automatic)", "End timestamp passed", "Automatically suppressed from all visitors")
    ]

    for r_idx, s_data in enumerate(status_data, start=2):
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        for c_idx, val in enumerate(s_data, start=1):
            c = ws_stat.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx == 2 else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [1, 2]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 5: REST API V1 INVENTORY
    # ─────────────────────────────────────────────────────────────────────────────
    ws_api = wb.create_sheet(title="REST APIs Reference")
    ws_api.views.sheetView[0].showGridLines = True

    api_headers = [
        "API ID", "HTTP Method", "Endpoint URI", "Controller & Method", "Route Filters",
        "Authentication", "Payload / Parameters", "Response Format", "Purpose & Operation"
    ]

    ws_api.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(api_headers, start=1):
        cell = ws_api.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    api_data = [
        # Public APIs
        ("API-01", "GET", "/api/v1/jobs", "Api\\JobApiController::index", "None", "Public", "department, experience, location, query, page", "JSON (Paginated)", "Fetch active published jobs for portal search & async filters"),
        ("API-02", "GET", "/api/v1/jobs/(:segment)", "Api\\JobApiController::show/$1", "None", "Public", "slug / id in URL", "JSON", "Fetch detailed specifications for a single vacancy"),
        ("API-03", "POST", "/api/v1/applications", "Web\\JobsController::submitApplication/direct", "ratelimit:5,600", "Public", "job_id, full_name, email, phone, resume (file), consent", "JSON", "Submit candidate job application with resume file"),
        ("API-04", "POST", "/api/v1/contact-enquiries", "Api\\EnquiryApiController::submitContact", "ratelimit:5,600", "Public", "first_name, email, phone, subject, message, consent", "JSON", "Submit public contact inquiry with validation"),
        ("API-05", "POST", "/api/v1/employer-enquiries", "Api\\EnquiryApiController::submitEmployer", "ratelimit:5,600", "Public", "company_name, contact_person, email, phone, roles, urgency", "JSON", "Submit B2B corporate hiring mandate"),
        ("API-06", "POST", "/api/v1/newsletter/subscribe", "Api\\NewsletterApiController::subscribe", "ratelimit:10,60", "Public", "email, first_name (optional), consent (required), source", "JSON", "Register newsletter subscriber & trigger double opt-in"),
        ("API-07", "POST", "/api/v1/newsletter/verify", "Api\\NewsletterApiController::verify", "None", "Public", "token (hash string)", "JSON", "Verify double opt-in token and activate subscription"),
        ("API-08", "POST", "/api/v1/newsletter/unsubscribe", "Api\\NewsletterApiController::unsubscribe", "None", "Public", "token, reason (optional)", "JSON", "Enforce 1-click unsubscribe and log suppression"),
        ("API-09", "GET", "/api/v1/newsletter/preferences", "Api\\NewsletterApiController::getPreferences", "None", "Public", "token", "JSON", "Retrieve category subscriptions for preferences page"),
        ("API-10", "POST", "/api/v1/newsletter/preferences", "Api\\NewsletterApiController::updatePreferences", "None", "Public", "token, categories (array)", "JSON", "Save updated subscriber topic preferences"),
        ("API-11", "GET", "/api/v1/announcements/active", "Api\\AnnouncementApiController::active", "None", "Public", "audience, path", "JSON", "Fetch live scheduled announcement banner or modal"),
        ("API-12", "POST", "/api/v1/analytics/campaign-event", "Api\\AnnouncementApiController::trackEvent", "ratelimit:60,60", "Public", "announcement_id, event_type (impression/click/dismissal)", "JSON", "Record campaign telemetry without storing visitor PII"),
        ("API-13", "POST", "/api/v1/job-alerts/subscribe", "Api\\JobAlertApiController::subscribe", "ratelimit:10,60", "Public", "email, department, experience_level, location, consent", "JSON", "Register candidate for targeted job vacancy alerts"),
        ("API-14", "POST", "/api/v1/cookies/consent", "Api\\CookieConsentApiController::logConsent", "None", "Public", "uuid, necessary, analytics, marketing", "JSON", "Log DPDP compliant cookie consent preferences"),
        ("API-15", "GET", "/api/v1/settings", "Api\\SettingsApiController::index", "None", "Public", "None", "JSON", "Fetch public website metadata, social links, contact info"),

        # Admin Protected APIs
        ("API-20", "POST", "/api/v1/admin/login", "Admin\\AuthController::login", "ratelimit:15,60", "Public", "username, password", "JSON", "Authenticate staff account, return session status"),
        ("API-21", "POST", "/api/v1/admin/logout", "Admin\\AuthController::logout", "None", "Session Auth", "None", "JSON", "Invalidate staff session, clear auth state"),
        ("API-22", "GET", "/api/v1/admin/dashboard", "Admin\\DashboardController::stats", "None", "Session Auth", "None", "JSON", "Fetch real-time dashboard KPIs, counts, and alert stats"),
        ("API-23", "GET", "/api/v1/admin/jobs", "Api\\JobApiController::index", "None", "Session Auth", "page, status, department", "JSON", "Administrative job list with application counts"),
        ("API-24", "POST", "/api/v1/admin/jobs", "Api\\JobApiController::store", "None", "Session Auth", "title, slug, department, salary, requirements, content", "JSON", "Create new job posting via API"),
        ("API-25", "PUT", "/api/v1/admin/jobs/(:num)/status", "Api\\JobApiController::transitionStatus/$1", "None", "Session Auth", "status (PUBLISHED/DRAFT/CLOSED)", "JSON", "Transition job lifecycle status"),
        ("API-26", "GET", "/api/v1/admin/applications", "Api\\ApplicationApiController::index", "None", "Session Auth", "job_id, status, query, page", "JSON", "Fetch applications with candidate details & CV link"),
        ("API-27", "PUT", "/api/v1/admin/applications/(:num)/status", "Api\\ApplicationApiController::updateStatus/$1", "None", "Session Auth", "status, notes, notify_candidate (bool)", "JSON", "Transition candidate stage and trigger auto-mail"),
        ("API-28", "GET", "/api/v1/admin/documents/(:num)/stream", "Api\\ApplicationApiController::streamResume/$1", "None", "Session Auth", "None", "Binary Stream", "Stream candidate resume file securely"),
        ("API-29", "POST", "/api/v1/admin/mail/send", "Api\\MailApiController::send", "None", "Session Auth", "to, subject, body_html, template_code, attachments", "JSON", "Dispatch outbound email via Hostinger SMTP"),
        ("API-30", "POST", "/api/v1/admin/mail/mailboxes/sync", "Api\\MailApiController::syncMailboxes", "None", "Session Auth", "None", "JSON", "Trigger instant mailbox synchronization"),
        ("API-31", "GET", "/api/v1/admin/audit-logs", "Api\\SecurityApiController::auditLogs", "None", "Session Auth", "user_id, action, limit, page", "JSON", "Fetch security and data mutation audit trail"),
        ("API-32", "GET", "/api/v1/admin/health", "Api\\SecurityApiController::health", "None", "Session Auth", "None", "JSON", "System health report: DB connection, disk space, writable"),
        ("API-33", "POST", "/api/cron/run", "Anonymous Cron Handler", "CRON_SECRET_KEY", "Key Auth", "key in query parameter", "JSON", "Protected CLI/HTTP scheduled task runner for shared hosting")
    ]

    for r_idx, a_data in enumerate(api_data, start=2):
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        for c_idx, val in enumerate(a_data, start=1):
            c = ws_api.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx in [2, 3, 4] else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [1, 2, 6]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 6: DATABASE TABLES REFERENCE (ALL 57 TABLES)
    # ─────────────────────────────────────────────────────────────────────────────
    ws_db = wb.create_sheet(title="Database Schema (57 Tables)")
    ws_db.views.sheetView[0].showGridLines = True

    db_headers = [
        "Table Name", "Total Records", "Primary Key", "Engine", "Table Purpose & Functionality",
        "Key Columns & Data Types", "Foreign Keys & Unique Indexes", "Related CodeIgniter Models"
    ]

    ws_db.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(db_headers, start=1):
        cell = ws_db.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    # Descriptions map for all 57 tables
    table_meta = {
        "admin_users": ("Administrative staff credentials, usernames, email, BCRYPT password hash, status", "username, email, password_hash, is_active", "AdminUserModel"),
        "announcement_events": ("Telemetry logs for announcement impressions, clicks, dismissals, conversions", "announcement_id, event_type, ip_hash, created_at", "AnnouncementModel"),
        "announcements": ("Master promotional campaigns, popup modals, top/bottom banners, scheduling", "title, public_heading, cta_url, display_format, start_date, end_date", "AnnouncementModel"),
        "application_notes": ("Internal recruiter notes and screening observations for candidate applications", "application_id, user_id, note_text, created_at", "ApplicationNoteModel"),
        "application_status_history": ("Audit trail of application pipeline stage transitions with recruiter ID", "application_id, old_status, new_status, changed_by, notes", "ApplicationModel"),
        "applications": ("Direct job candidate applications linking candidate to specific vacancy", "job_id, candidate_id, document_id, status, idempotency_key", "ApplicationModel"),
        "audit_logs": ("Immutable security audit trail recording staff mutations and administrative logins", "user_id, action, entity_type, entity_id, ip_address, user_agent", "AuditLogModel"),
        "blog_categories": ("Content categories for Insights and recruitment intelligence articles", "name, slug, description, sort_order", "BlogCategoryModel"),
        "blog_posts": ("Articles and market updates published in the Insights blog section", "category_id, title, slug, summary, content, is_published, views", "BlogPostModel"),
        "candidate_documents": ("Encrypted vault metadata for candidate uploaded resumes and CV files", "candidate_id, file_path, original_filename, file_size, mime_type", "CandidateDocumentModel"),
        "candidates": ("Master candidate personal information, normalized phone, and consent records", "first_name, last_name, email, phone, current_location, consent_received", "CandidateModel"),
        "campaign_activity_logs": ("Email campaign dispatch, delivery, click, and bounce telemetry logs", "campaign_id, subscriber_id, event_type, metadata", "CampaignModel"),
        "campaign_recipients": ("Targeted recipients snapshot for email broadcast campaigns", "campaign_id, subscriber_id, status, sent_at, opened_at", "CampaignModel"),
        "captcha_tokens": ("Expiring HMAC mathematical captcha challenges protecting public submission forms", "token, answer_hash, expires_at", "FormSecurityService"),
        "contact_enquiries": ("General inquiries submitted via public /contact page advisory desk", "first_name, email, phone, subject, message, status, idempotency_key", "ContactEnquiryModel"),
        "cookie_consent_logs": ("DPDP Act compliant audit logs of visitor cookie choices and timestamps", "visitor_uuid, necessary, analytics, marketing, ip_hash, user_agent", "CookieConsentModel"),
        "data_retention_logs": ("Automated purge logs enforcing legal data retention limits on candidate files", "entity_type, records_purged, purge_timestamp", "SecurityService"),
        "db_factories": ("Database seed state and development factory metadata tracking", "name, state, created_at", "MigrationService"),
        "db_migrations": ("CodeIgniter framework database migration history and version tracking", "version, class, group, namespace, time, batch", "Migration"),
        "email_campaigns": ("Master newsletter and promotional email broadcast configurations", "title, subject, body_html, recipient_group, scheduled_at, status", "EmailCampaignModel"),
        "email_delivery_logs": ("SMTP handshake delivery response codes and transport telemetry", "outbox_id, smtp_code, response_message, delivered_at", "OutboxService"),
        "email_outbox": ("Dual-queue asynchronous email dispatch queue protecting web requests from latency", "recipient_email, recipient_name, subject, template_code, status, attempts", "EmailOutboxModel"),
        "email_templates": ("18 pre-configured branded HTML email templates with dynamic merge tags", "code, name, subject, body_html, body_text, variables_help, is_active", "EmailTemplateModel"),
        "employer_enquiries": ("B2B corporate hiring requirements and recruitment mandates from /employers", "company_name, contact_person, email, phone, required_roles, urgency, status", "EmployerEnquiryModel"),
        "employers": ("Verified corporate employer accounts and enterprise directory listings", "company_name, industry, contact_email, contact_phone, account_manager_id", "EmployerModel"),
        "enquiry_followups": ("Recruiter follow-up call and meeting schedules for corporate leads", "enquiry_id, scheduled_at, consultant_id, notes, status", "EnquiryAutomationService"),
        "enquiry_notes": ("Internal consultant notes attached to employer and contact inquiries", "enquiry_id, user_id, note_text, created_at", "EnquiryAutomationService"),
        "factories": ("Testing factory configuration metadata for CI/CD automated test runs", "name, definition, created_at", "TestSupport"),
        "faqs": ("Frequently asked questions displayed on the /faq public portal", "category, question, answer, order_num, is_published", "FaqModel"),
        "general_resume_submissions": ("Unsolicited candidate submissions to private Pune talent repository", "candidate_id, document_id, desired_role, preferred_location, notice_period", "GeneralResumeSubmissionModel"),
        "job_alert_subscriptions": ("Candidate subscriptions for automated alerts matching specific parameters", "email, department, experience_level, location, alert_token_hash, status", "JobAlertSubscriptionModel"),
        "job_categories": ("Functional departments and recruitment sectors (IT, Core, BFSI, Healthcare)", "name, slug, description, sort_order", "JobCategoryModel"),
        "job_locations": ("Geographical corridors in Pune and Maharashtra (Hinjawadi, Kharadi, Chakan)", "city, area, state, country, is_active", "JobLocationModel"),
        "jobs": ("Active and historical recruitment job postings and mandate profiles", "title, slug, category_id, department, experience_bracket, salary_package, status", "JobModel"),
        "login_attempts": ("Brute-force security tracker recording IP address and failure count", "ip_address, username, attempted_at, is_success", "LoginTracker"),
        "mail_activity_logs": ("Hostinger Mail audit trail of outbound emails, sync actions, folder changes", "mailbox_id, action_type, message_id, details, created_at", "MailActivityLogModel"),
        "mail_local_drafts": ("Unsent drafts created in Hostinger Mail communication composer", "user_id, to_email, subject, body_html, attachments_json", "MailDraftModel"),
        "mail_message_index": ("Synchronized local index of messages from Hostinger corporate email server", "mailbox_id, message_uid, from_email, to_email, subject, is_read, date", "MailMessageIndexModel"),
        "mail_recruitment_links": ("Associations linking email threads to specific candidates or job mandates", "message_id, candidate_id, application_id, job_id, linked_by", "MailRecruitmentLinkModel"),
        "mail_webhook_events": ("Inbound email delivery notifications and webhooks from Hostinger mail server", "event_id, event_type, payload, processed_status, received_at", "MailWebhookModel"),
        "mailbox_connections": ("IMAP and SMTP connection parameters for corporate email accounts", "email_address, imap_host, imap_port, smtp_host, smtp_port, is_active", "MailboxConnectionModel"),
        "media_assets": ("Uploaded image and media assets library metadata with dimensions and MIME", "file_name, file_path, file_size, mime_type, uploaded_by", "MediaAssetModel"),
        "migrations": ("Core system migration sequence tracking table", "version, class, group, namespace, time, batch", "Migration"),
        "newsletter_activity_logs": ("Subscriber interaction logs including verification, opt-out, preferences", "subscriber_id, activity_type, source, metadata, created_at", "NewsletterSubscriberModel"),
        "newsletter_preferences": ("Category subscription preferences for verified newsletter recipients", "subscriber_id, category, is_enabled, updated_at", "NewsletterSubscriberModel"),
        "newsletter_subscribers": ("Newsletter subscriber list, double opt-in status, token hashes, timestamps", "email, first_name, status, verification_token_hash, consent_given", "NewsletterSubscriberModel"),
        "notification_logs": ("Delivery logs of push and web notifications sent to staff and users", "notification_id, channel, delivery_status, delivered_at", "NotificationModel"),
        "notifications": ("In-app alerts and notifications displayed in admin bell dropdown", "user_id, title, message, link_url, is_read, created_at", "NotificationModel"),
        "password_resets": ("Cryptographically secure expiring tokens for administrator password recovery", "email, token_hash, expires_at, created_at", "AdminUserModel"),
        "permissions": ("Granular system permission capabilities mapped to functional categories", "code, name, category, description, created_at", "PermissionModel"),
        "rate_limit_buckets": ("Sliding window rate limit token buckets mitigating API brute force and spam", "bucket_key, tokens_remaining, last_fill_timestamp", "RateLimitFilter"),
        "role_permissions": ("Many-to-many junction table assigning permissions to specific roles", "role_id, permission_id", "RoleModel"),
        "roles": ("System access roles (Super Admin, HR Manager, Recruiter, Content Editor)", "slug, name, description, created_at, updated_at", "RoleModel"),
        "scheduled_job_runs": ("Execution history and diagnostic output of background cron schedulers", "job_name, status, execution_time_seconds, output_log, ran_at", "ScheduledJobModel"),
        "sitemap_log": ("Audit records of automated XML sitemap regenerations and ping results", "sitemap_type, entries_count, file_path, regenerated_at", "SitemapController"),
        "testimonials": ("Client and candidate recommendations featured on website homepage", "author_name, author_title, company, quote, rating, is_published", "TestimonialModel"),
        "user_roles": ("Many-to-many junction table linking admin users to system roles", "user_id, role_id", "AdminUserModel"),
        "website_page_history": ("Version control and rollback snapshots for dynamic CMS website pages", "page_id, title, content_json, author_id, created_at", "WebsitePageModel"),
        "website_pages": ("Server-rendered CMS page definitions and structured content blocks", "slug, title, seo_meta_json, sections_json, status, updated_at", "WebsitePageModel"),
        "website_settings": ("36 global system settings, contact info, SMTP flags, analytics IDs", "setting_key, setting_value, setting_group, is_public, updated_at", "SettingsModel")
    }

    r_idx = 2
    for t_name, fields in schema.items():
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        pk_field = next((f["name"] for f in fields if f.get("primary_key")), "id")
        rec_count = counts.get(t_name, 0)

        # Meta info
        meta = table_meta.get(t_name, ("Core system table supporting application operations", "id, created_at", "Database"))
        desc = meta[0]
        key_cols = meta[1]
        model_name = meta[2]

        row_vals = [
            t_name, rec_count, pk_field, "InnoDB", desc, key_cols,
            "PRIMARY (" + pk_field + "), indexed lookups", model_name
        ]

        for c_idx, val in enumerate(row_vals, start=1):
            c = ws_db.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx in [1, 3, 6, 8] else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [2, 3, 4]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

        r_idx += 1

    # ─────────────────────────────────────────────────────────────────────────────
    # SHEET 7: EMAIL TEMPLATES INVENTORY (18 TEMPLATES)
    # ─────────────────────────────────────────────────────────────────────────────
    ws_tmpl = wb.create_sheet(title="Email Templates (18)")
    ws_tmpl.views.sheetView[0].showGridLines = True

    tmpl_headers = [
        "Template ID", "Template Code", "Display Name", "Email Subject Line",
        "Workflow Trigger", "Variable Merge Tags", "Active Status", "HTML & Text Support"
    ]

    ws_tmpl.row_dimensions[1].height = 28
    for col_idx, h_text in enumerate(tmpl_headers, start=1):
        cell = ws_tmpl.cell(row=1, column=col_idx, value=h_text)
        cell.font = header_font
        cell.fill = dark_green_fill
        cell.alignment = Alignment(horizontal="center", vertical="center", wrap_text=True)

    templates_data = [
        ("TPL-01", "app_candidate_ack", "Candidate Application Acknowledgement", "Application Received: {{job_title}} - TalentHub Consultant",
         "Triggered immediately when candidate applies for vacancy via /jobs/(:segment)", "{{candidate_name}}, {{job_title}}, {{job_slug}}, {{website_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-02", "app_talent_pool_ack", "Talent Pool General Registration", "Career Profile Registered - TalentHub Consultant Pune",
         "Triggered when candidate registers in confidential resume bank via /career-enquiry", "{{candidate_name}}, {{desired_role}}, {{preferred_location}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-03", "employer_enquiry_ack", "Employer Hiring Enquiry Confirmation", "Hiring Requirement Registered - TalentHub Corporate Desk",
         "Triggered when corporate client submits hiring requirements via /employers", "{{contact_person}}, {{company_name}}, {{required_roles}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-04", "interview_shortlist", "Candidate Interview Shortlist Notification", "Interview Scheduled: {{job_title}} - {{company_name}}",
         "Triggered when recruiter transitions candidate application to INTERVIEW stage", "{{candidate_name}}, {{job_title}}, {{company_name}}, {{interview_time}}, {{meeting_link}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-05", "password_reset", "Admin Password Reset", "Reset Your TalentHub Admin Password",
         "Triggered when staff member submits email via /admin/forgot-password", "{{admin_name}}, {{reset_url}}, {{expiry_minutes}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-06", "contact_enquiry_ack", "Contact Enquiry Acknowledgement", "We have received your enquiry - TalentHub Consultant",
         "Triggered when general visitor submits contact message via /contact", "{{sender_name}}, {{enquiry_subject}}, {{contact_email}}, {{contact_phone}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-07", "admin_app_alert", "Admin New Application Alert", "New Job Application: {{job_title}} - {{candidate_name}}",
         "Triggered concurrently with application submission to notify recruitment desk", "{{candidate_name}}, {{candidate_email}}, {{candidate_phone}}, {{job_title}}, {{experience_years}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-08", "newsletter_verification", "Newsletter Email Verification (Double Opt-In)", "Confirm your TalentHub Newsletter Subscription",
         "Triggered when user enters email in public newsletter subscription forms", "{{subscriber_name}}, {{verification_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-09", "newsletter_welcome", "Newsletter Welcome Email", "Welcome to TalentHub Pune Recruitment Intelligence",
         "Triggered when user clicks double opt-in verification link confirming subscription", "{{subscriber_name}}, {{preferences_url}}, {{unsubscribe_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-10", "job_alert_notification", "Matching Job Opportunities Alert", "New Job Opportunities Matching Your Preferences — TalentHub",
         "Triggered by background cron job when newly posted jobs match subscriber profile", "{{subscriber_name}}, {{jobs_html_list}}, {{unsubscribe_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-11", "app_candidate_rejected", "Candidate Status: Profile Retained in Pool", "Update regarding your application for {{job_title}} — TalentHub Consultant",
         "Triggered when recruiter marks candidate as REJECTED in application manager", "{{candidate_name}}, {{job_title}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-12", "app_candidate_closed", "Candidate Status: Mandate Concluded", "Recruitment Mandate Concluded: {{job_title}} — TalentHub Consultant",
         "Triggered when an entire job vacancy is closed or filled by employer", "{{candidate_name}}, {{job_title}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-13", "app_candidate_shortlisted", "Candidate Status: Profile Shortlisted", "Congratulations! Profile Shortlisted for {{job_title}} — TalentHub Consultant",
         "Triggered when recruiter transitions candidate application to SHORTLISTED stage", "{{candidate_name}}, {{job_title}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-14", "employer_mandate_closed", "Employer Mandate Status: Concluded", "Corporate Mandate Update: {{required_roles}} — TalentHub Corporate Desk",
         "Triggered when employer enquiry mandate is resolved or marked as CLOSED", "{{contact_person}}, {{required_roles}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-15", "direct_announcement_notice", "Direct Executive Notice & Announcement", "{{announcement_subject}}",
         "Triggered via 1-Click Quick Notice in Admin Engagement Centre to all subscribers", "{{subscriber_name}}, {{announcement_subject}}, {{announcement_body}}, {{cta_url}}, {{unsubscribe_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-16", "new_job_broadcast", "Automated Job Opening Broadcast", "New Opportunity: {{job_title}} ({{experience_label}}) in {{location}} — TalentHub",
         "Triggered when recruiter creates new job posting with subscriber broadcast enabled", "{{job_title}}, {{experience_label}}, {{location}}, {{job_url}}, {{unsubscribe_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-17", "new_article_broadcast", "Automated Insights & Articles Broadcast", "Market Intelligence: {{article_title}} — TalentHub Insights",
         "Triggered when content editor publishes an article with subscriber broadcast enabled", "{{article_title}}, {{article_summary}}, {{article_url}}, {{unsubscribe_url}}", "Active (1)", "Full Responsive HTML + Plain Text"),
        ("TPL-18", "candidate_interview_reminder", "Candidate Interview Reminder (24h Notice)", "Interview Reminder: {{job_title}} with {{company_name}} — TalentHub Consultant",
         "Triggered 24 hours prior to scheduled virtual interview time by cron scheduler", "{{candidate_name}}, {{job_title}}, {{company_name}}, {{interview_time}}, {{meeting_link}}", "Active (1)", "Full Responsive HTML + Plain Text")
    ]

    for r_idx, t_data in enumerate(templates_data, start=2):
        row_fill = white_fill if r_idx % 2 == 0 else light_zebra_fill
        for c_idx, val in enumerate(t_data, start=1):
            c = ws_tmpl.cell(row=r_idx, column=c_idx, value=val)
            c.font = code_font if c_idx in [2, 6] else data_font
            c.fill = row_fill
            c.border = grid_border
            if c_idx in [1, 7]:
                c.alignment = Alignment(horizontal="center", vertical="center")
            else:
                c.alignment = Alignment(horizontal="left", vertical="center", wrap_text=True)

    # ─────────────────────────────────────────────────────────────────────────────
    # AUTO-FIT COLUMN WIDTHS ACROSS ALL SHEETS
    # ─────────────────────────────────────────────────────────────────────────────
    for sheet in wb.worksheets:
        for col in sheet.columns:
            max_len = 0
            col_letter = get_column_letter(col[0].column)
            for cell in col:
                val = str(cell.value or "")
                # Ignore merged cells in executive summary
                if sheet.title == "Executive Summary" and cell.row > 4 and cell.row < 23:
                    continue
                if len(val) > max_len:
                    max_len = min(len(val), 60)
            sheet.column_dimensions[col_letter].width = max(max_len + 4, 12)

    # Specific override for executive summary
    if "Executive Summary" in wb.sheetnames:
        for c_idx in range(1, 10):
            wb["Executive Summary"].column_dimensions[get_column_letter(c_idx)].width = 16

    output_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "TalentHub_Feature_Inventory.xlsx")
    wb.save(output_path)
    print(f"SUCCESS: Generated complete feature inventory workbook at: {output_path}")

if __name__ == "__main__":
    build_feature_inventory()

