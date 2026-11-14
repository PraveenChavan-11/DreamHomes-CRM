import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT

import docx_helper as dh

def build_quick_reference():
    doc = docx.Document()
    dh.add_header_footer(doc, "TalentHub Admin Sidebar & Operations Handbook")
    dh.add_cover_page(
        doc,
        doc_title="TALENTHUB — Admin Dashboard Sidebar & Everyday Operations Handbook",
        doc_subtitle="Complete Plain-English Guide for Non-Technical Administrators: What Each Sidebar Menu Does, Why to Use It, When to Use It, and Step-by-Step Instructions",
        doc_ref="TH-OPS-HANDBOOK-2026-V2",
        doc_type="NON-TECHNICAL ADMINISTRATOR HANDBOOK"
    )

    # Document Overview & Access Card
    dh.add_heading_1(doc, "1. Executive Orientation for Non-Technical Administrators")
    dh.add_paragraph(
        doc,
        "Welcome to the TalentHub Consultant Administration Portal. This handbook is written specifically for non-technical operational staff, HR executives, recruitment consultants, and office managers. You do NOT need any programming or technical background to master every feature."
    )
    dh.add_paragraph(
        doc,
        "The TalentHub Admin Dashboard is your central control room for managing job postings, candidate resumes, employer hiring requests, email communications, and website content. Everything is organized cleanly inside the Left Sidebar Navigation Menu."
    )

    dh.add_callout(
        doc,
        "PORTAL ACCESS LINK: https://talenthubconsultant.com/admin/login\n"
        "Staff Work Credentials: Enter your assigned username and password. Two-Step Email Verification is configured OFF by default for smooth, instant access. If you ever forget your password, contact your Super Administrator or use the 'Forgot Password' link.",
        title="PORTAL ACCESS & SECURITY",
        callout_type="success"
    )

    # Section 2: Complete Sidebar Navigation Map
    dh.add_heading_1(doc, "2. Master Sidebar Navigation Map (All 7 Sections & 21 Menus)")
    dh.add_paragraph(
        doc,
        "The left sidebar is grouped into seven (7) logical operational departments. The table below outlines each section, its menus, and who can access it:"
    )

    map_headers = ["Sidebar Section", "Menu Item", "URL Path", "Role Scope", "Primary Everyday Purpose"]
    map_data = [
        ("1. Overview", "Dashboard", "/admin", "All Staff", "Live operations pulse: active jobs, new resumes, and pending tasks"),
        ("2. Recruitment", "Job Postings", "/admin/jobs", "HR & Admin", "Create, edit, publish, and close corporate vacancies"),
        ("2. Recruitment", "Applications", "/admin/applications", "HR & Admin", "Review candidate applications, screen CVs, and update stages"),
        ("2. Recruitment", "Resume Bank", "/admin/resume-bank", "HR & Admin", "Search unadvertised candidate resumes from Pune talent vault"),
        ("3. Enquiries", "Employer Requests", "/admin/enquiries/employers", "HR & Admin", "Manage corporate hiring mandates and client callbacks within SLA"),
        ("3. Enquiries", "Contact Messages", "/admin/enquiries/contact", "HR & Admin", "Read and respond to general website visitor enquiries"),
        ("4. Communication", "Hostinger Mail", "/admin/mail", "HR & Admin", "Read candidate emails, compose replies, and sync mailboxes"),
        ("4. Communication", "Email Templates", "/admin/mail/templates", "Super Admin", "Customize automated email subjects, body text, and branding"),
        ("5. Engagement", "Overview", "/admin/engagement", "HR & Admin", "Marketing telemetry: subscribers, popup views, and goal conversions"),
        ("5. Engagement", "Announcements", "/admin/engagement/announcements", "HR & Admin", "Create and schedule website popups, banners, and drive alerts"),
        ("5. Engagement", "1-Click Notice", "/admin/engagement/quick-notice", "HR & Admin", "Broadcast urgent recruitment drives to all verified subscribers with 1 click"),
        ("5. Engagement", "Subscribers", "/admin/engagement/subscribers", "HR & Admin", "View verified newsletter subscribers and export to CSV"),
        ("5. Engagement", "Job Alerts", "/admin/engagement/job-alerts", "HR & Admin", "Manage candidate category job alert subscriptions"),
        ("5. Engagement", "Email Campaigns", "/admin/engagement/campaigns", "HR & Admin", "Compose and dispatch bulk targeted recruitment newsletters"),
        ("5. Engagement", "Unified Leads", "/admin/engagement/leads", "HR & Admin", "Combined pipeline of all candidate and employer inquiries"),
        ("5. Engagement", "Analytics", "/admin/engagement/analytics", "HR & Admin", "Inspect popup CTR percentages and generate Executive B2B PDF Reports"),
        ("5. Engagement", "Cookie Logs", "/admin/engagement/cookies", "Super Admin", "Statutory audit logs of user DPDP cookie consent decisions"),
        ("6. Content & CMS", "Articles & Insights", "/admin/content/articles", "HR & Admin", "Publish market reports, Pune salary guides, and career blogs"),
        ("6. Content & CMS", "Page Content", "/admin/content/pages", "Super Admin", "Manage all 15 public pages with Google SERP SEO Simulators"),
        ("6. Content & CMS", "Testimonials", "/admin/content/testimonials", "HR & Admin", "Manage candidate and employer corporate reviews"),
        ("6. Content & CMS", "FAQs", "/admin/content/faqs", "HR & Admin", "Add, edit, and reorder public frequently asked questions"),
        ("7. Administration", "Staff & Users", "/admin/users", "Super Admin", "Add recruiters, assign roles, and manage login access"),
        ("7. Administration", "Roles & Permissions", "/admin/roles", "Super Admin", "Define roles and control granular sidebar menu permissions"),
        ("7. Administration", "Audit Logs", "/admin/audit-logs", "Super Admin", "Immutable activity trail of all logins, changes, and deletions"),
        ("7. Administration", "Website Settings", "/admin/settings", "Super Admin", "Configure brand info, phone, email, GA4, and Two-Step OTP toggle"),
        ("7. Administration", "My Profile", "/admin/profile", "All Staff", "Update your staff profile name, work phone, and login password")
    ]
    dh.add_styled_table(doc, map_headers, map_data, [1.0, 1.2, 1.2, 0.9, 2.5])

    # Section 3: Deep-Dive Sidebar Menu Handbook
    dh.add_heading_1(doc, "3. Deep-Dive Menu Handbook: What, Why, When & How")
    dh.add_paragraph(
        doc,
        "This is the heart of the handbook. For every single menu item in your sidebar, read the 4 standard questions: What does it do? Why do you use it? When should you use it? and How to do it step-by-step."
    )

    menus = [
        # 1. Overview
        {
            "num": "3.1",
            "section": "Overview",
            "name": "Dashboard",
            "url": "/admin",
            "what": "The central operational control center. Shows live count cards (Active Jobs, New Applications, Resumes in Vault, Employer Leads), the Recruitment Alerts notification bell, and the Corporate Hiring Urgency doughnut chart.",
            "why": "Gives you an instant snapshot of your recruitment business without digging through multiple screens. You can spot urgent client requirements and unprocessed candidate applications in 5 seconds.",
            "when": "Every morning when you first log in, and multiple times throughout the business day to monitor incoming candidate applications and client leads.",
            "how": [
                "Click 'Dashboard' at the top of the left sidebar.",
                "Look at the top KPI stat cards: note any 'New Unscreened Applications' or 'Uncontacted Employer Leads'.",
                "Check the 'Recruitment Alerts' bell at the top right to see if any new candidates submitted applications while you were away.",
                "Inspect the 'Corporate Hiring Urgency' chart to see how many client mandates are marked 'Immediate', '15 Days', or '30 Days'. (If zero, it shows 'Awaiting Leads').",
                "Click on any metric card to jump directly to that module."
            ],
            "mistakes": "Do not ignore red badges on stat cards. If 'Uncontacted Employer Leads' shows a number, a Pune corporate client is waiting for your phone call!"
        },

        # 2. Recruitment & Talent
        {
            "num": "3.2",
            "section": "Recruitment & Talent",
            "name": "Job Postings",
            "url": "/admin/jobs",
            "what": "The master catalog where you create, edit, manage, and close all corporate job openings displayed on the public https://talenthubconsultant.com/jobs website.",
            "why": "Job postings attract qualified candidates. Accurate role specifications, experience brackets, and salary ranges ensure high-quality applications and reduce recruiter screening time.",
            "when": "Whenever a corporate client issues a new hiring mandate, when an opening is filled (close it), or when client salary budgets change.",
            "how": [
                "To Post a New Job: Click '+ Post New Job' button at top right.",
                "Fill in Job Title (e.g. 'Senior Java Architect') and Select Department (e.g. 'IT / Software Engineering').",
                "Select Workplace Type ('On-site', 'Hybrid', 'Remote') and Location (e.g. 'Hinjawadi Phase 3, Pune').",
                "Specify Experience Range (e.g. 5 to 8 years) and Annual CTC Package (e.g. ₹18,00,000 to ₹25,00,000).",
                "Paste Role Responsibilities and Qualifications into the rich-text editor box.",
                "Select Hiring Urgency: 'Immediate', '15 Days', '30 Days', or 'Pipeline'.",
                "In the Google Search Ranking (SEO) box, verify the Meta Title (50-60 chars) and Meta Description (150-160 chars) in the live Google SERP preview.",
                "Check 'Broadcast to Newsletter Subscribers' if you want an automated email blast sent to all verified job alert members.",
                "Click 'Publish Vacancy'. The job is immediately live on the website!"
            ],
            "mistakes": "Never leave a filled or cancelled job in 'PUBLISHED' status. Immediately click 'Close Vacancy' so job seekers don't submit obsolete applications."
        },

        {
            "num": "3.3",
            "section": "Recruitment & Talent",
            "name": "Applications",
            "url": "/admin/applications",
            "what": "The central inbox for every candidate who submitted their resume for an active job vacancy on your portal. Includes applicant details, CV downloads, and bulk actions.",
            "why": "Allows recruiters to systematically evaluate candidates, download resumes from the private vault, record interview notes, and transition candidates through hiring stages with automated branded emails.",
            "when": "Daily. Applications should be screened within 24 hours of arrival to maintain high candidate responsiveness.",
            "how": [
                "Click 'Applications' in the left sidebar.",
                "Use the Stage Filter dropdown to view candidates in 'APPLIED' status.",
                "Click 'Download Resume' to view the candidate's PDF or Word CV securely.",
                "Click 'Add Note' to document your phone screening findings (Current CTC, Expected CTC, Notice Period).",
                "To Shortlist: Change status dropdown to 'SHORTLISTED'. The system automatically emails the candidate confirming they passed initial screening!",
                "To Reject: Change status dropdown to 'REJECTED'. The candidate receives a polite rejection email, and their CV remains safely in your talent vault.",
                "Bulk Actions: Select multiple candidate checkboxes. A floating black Bulk Action Bar will appear at the bottom. Choose 'Transition Stage' (e.g. Shortlisted) and click 'Apply Action' to process 20+ candidates in one click!"
            ],
            "mistakes": "Do not leave candidates sitting in 'APPLIED' status for weeks. Moving candidates to 'SHORTLISTED' or 'REJECTED' keeps your pipeline tidy and triggers professional automated communication."
        },

        {
            "num": "3.4",
            "section": "Recruitment & Talent",
            "name": "General Resume Bank",
            "url": "/admin/resume-bank",
            "what": "A searchable talent vault holding candidates who submitted unsolicited resumes through the public Career Desk (/career-enquiry) without applying to a specific job opening.",
            "why": "Your secret weapon for passive sourcing. When a corporate client suddenly needs a specialized role (e.g. SAP MM Lead or Tally Accountant), search here first to find candidates without spending money on job ads.",
            "when": "Whenever a new employer mandate arrives, or when sourcing candidates for hard-to-fill corporate positions.",
            "how": [
                "Click 'General Resume Bank' in the left sidebar.",
                "Use the search box to type desired job titles, keywords (e.g. 'Java', 'Procurement', 'Tally'), or Pune locations (e.g. 'Kharadi', 'Chakan').",
                "Filter by Experience Years (e.g. 3+ years) and Notice Period (e.g. 'Immediate' or '15 Days').",
                "Click 'Download Resume' to view the candidate's CV.",
                "Click 'Contact Candidate' or use their phone/WhatsApp number to reach out and pitch the client opening."
            ],
            "mistakes": "Don't forget to check the 'Primary Skills' tags automatically extracted by the resume parser."
        },

        # 3. Inbound Enquiries
        {
            "num": "3.5",
            "section": "Inbound Enquiries",
            "name": "Employer Hiring Requests",
            "url": "/admin/enquiries/employers",
            "what": "Manages corporate hiring requests submitted by enterprise HR heads and company founders via the public /employers portal.",
            "why": "This is direct B2B corporate revenue! Each lead represents a company looking to pay recruitment consultancy fees for hiring employees.",
            "when": "Check immediately when a lead arrives (aim for under 4-hour callback response SLA during business hours).",
            "how": [
                "Click 'Employer Hiring Requests' in the left sidebar.",
                "Identify rows with the yellow 'NEW' badge.",
                "Read the Company Name, Contact Person, Phone, Email, Hiring Positions, and Urgency.",
                "Call the employer contact person immediately to understand their hiring requirements.",
                "Click 'Add Follow-up Note' and record call outcomes (e.g., 'Spoke with Mr. Sharma, needs 3 Java devs by Nov 15').",
                "Update Status from 'NEW' to 'CONTACTED', and then 'IN_PROGRESS' once mandate terms are agreed.",
                "Set a 'Follow-Up Deadline Date' so the system sends you an alert before the deadline lapses."
            ],
            "mistakes": "Never let an employer request sit in 'NEW' status overnight. Corporate clients hire the consultancy that responds first."
        },

        {
            "num": "3.6",
            "section": "Inbound Enquiries",
            "name": "Contact Messages",
            "url": "/admin/enquiries/contact",
            "what": "Stores general inquiries, feedback, and questions submitted through the public /contact form.",
            "why": "Ensures prompt customer service for partners, vendors, job seekers, and corporate visitors.",
            "when": "Review daily at midday and evening.",
            "how": [
                "Click 'Contact Messages' in the left sidebar.",
                "Read the visitor's subject and message.",
                "Click 'Send Email Reply' or call the provided phone number.",
                "Mark status as 'RESOLVED' once answered."
            ],
            "mistakes": "Do not delete messages without responding. Even if an inquiry is not an immediate client, professional replies build brand trust."
        },

        # 4. Communication
        {
            "num": "3.7",
            "section": "Communication",
            "name": "Hostinger Mail Center",
            "url": "/admin/mail",
            "what": "A full-featured webmail interface built directly inside your dashboard, connected securely to your Hostinger business mailboxes (e.g. hr@talenthubconsultant.com).",
            "why": "Allows recruiters to read candidate emails, review replies, and compose outbound emails without switching tabs to external webmail.",
            "when": "Multiple times daily to read candidate emails, interview acceptances, and corporate client communications.",
            "how": [
                "Click 'Hostinger Mail Center' in the left sidebar.",
                "Click the green 'Sync Mailbox Now' button at the top right to download new inbound messages.",
                "Click any email thread in the inbox list to read the message body and attachments.",
                "Click 'Reply' to write a back-and-forth response directly to the candidate.",
                "Click '+ Compose Email' to draft a brand-new message to any recipient with attachments.",
                "Check the Outbox tab to verify that outgoing messages have dispatched cleanly."
            ],
            "mistakes": "If you don't see an expected email, click 'Sync Mailbox Now' to pull the newest messages from Hostinger servers."
        },

        {
            "num": "3.8",
            "section": "Communication",
            "name": "Email Templates",
            "url": "/admin/mail/templates",
            "what": "Where all eighteen (18) automated system email templates are stored (Shortlisting alerts, rejection notes, interview reminders, welcome emails, verification links).",
            "why": "Ensures your corporate emails look elegant, professional, branded, and consistent across all recruitment operations.",
            "when": "When you want to tweak automated email wording, change the signature, or update office address details.",
            "how": [
                "Click 'Email Templates' in the left sidebar.",
                "Find the template you wish to modify (e.g. 'Candidate Shortlisted Notification').",
                "Click 'Edit Template'.",
                "Edit the Email Subject and HTML body. Keep the placeholders like {{candidate_name}}, {{job_title}}, {{company_name}} intact—the system fills them in automatically!",
                "Click 'Send Test Preview' and enter your personal email to verify the email appearance.",
                "Click 'Save Template Changes'."
            ],
            "mistakes": "Never delete variable tags like {{verification_url}} or {{candidate_name}}—they are required for personalized delivery!"
        },

        # 5. Engagement Centre
        {
            "num": "3.9",
            "section": "Engagement Centre",
            "name": "Engagement Overview",
            "url": "/admin/engagement",
            "what": "The marketing command center showing verified subscriber numbers, active popup campaigns, lead conversion rates, and recent subscriber activity logs.",
            "why": "Helps you monitor audience growth and evaluate how well your website converts visitors into job candidates and newsletter subscribers.",
            "when": "Weekly during team meetings to review subscriber growth and recruitment marketing KPIs.",
            "how": [
                "Click 'Engagement Overview' in the left sidebar.",
                "Review the 4 core metric cards: Total Verified Subscribers, Active Popups, Lead Conversions, and Total Interactions.",
                "Inspect the 'Recent Subscriber Growth' telemetry graph.",
                "Check 'Recent Engagement Activity' to see who verified their email today."
            ],
            "mistakes": "None—this is a read-only monitoring dashboard."
        },

        {
            "num": "3.10",
            "section": "Engagement Centre",
            "name": "Announcements & Popups",
            "url": "/admin/engagement/announcements",
            "what": "Manage high-impact marketing popups, center modals, top sticky bars, and bottom recruitment drive banners on the public website.",
            "why": "Immediately captures visitor attention when they land on your website to promote urgent hiring drives, mega walk-in events, or executive mandates.",
            "when": "When launching a new recruitment drive (e.g. 'Q4 Pune Corporate Hiring Drive: 50+ Roles Open') or urgent hiring notice.",
            "how": [
                "Click 'Announcements & Popups' in the left sidebar.",
                "Click '+ Create Announcement' at top right.",
                "Enter Campaign Title (internal name) and Public Heading (displayed to visitors).",
                "Write clear, inviting Public Content describing the hiring drive.",
                "Choose Display Format: 'Center Modal Popup' (most prominent), 'Top Banner', or 'Bottom Banner'.",
                "Set Start Date and End Date. Set Display Frequency to 'Once per session' so visitors aren't annoyed.",
                "Enter Action Button Label (e.g. 'Explore Open Mandates') and Destination URL (e.g. '/jobs').",
                "Set Priority (e.g. 10) and Status to 'ACTIVE'. Click 'Save Announcement'. It goes live instantly!"
            ],
            "mistakes": "Do not set frequency to 'Every Page Load'. Always use 'Once per session' so visitors can browse comfortably."
        },

        {
            "num": "3.11",
            "section": "Engagement Centre",
            "name": "1-Click Direct Notice",
            "url": "/admin/engagement/quick-notice",
            "what": "A high-speed broadcast tool that sends an urgent recruitment notification email to ALL double opt-in verified subscribers with exactly 1 click.",
            "why": "When you have an urgent weekend walk-in drive or an exclusive leadership vacancy that needs candidate applications today, this reaches thousands of inbox in minutes.",
            "when": "Urgent recruitment announcements (recommend 1 to 2 times per week maximum to prevent subscriber fatigue).",
            "how": [
                "Click '1-Click Direct Notice' in the left sidebar.",
                "Enter the Notice Subject (e.g. 'Urgent Walk-in Drive This Saturday: 30+ IT Mandates in Hinjawadi').",
                "Enter Public Headline and detailed Announcement Body.",
                "Optionally add a Priority Badge (e.g. 'Urgent Recruitment Drive') and Action Button.",
                "Choose Target Audience: 'All Verified Subscribers' or 'Job Seekers Only'.",
                "Click 'Dispatch Direct Notice Now'. The system enforces suppression lists, handles unsubscribes automatically, and queues branded emails!"
            ],
            "mistakes": "Do not send multiple direct notices in the same day. Keep content valuable, relevant, and focused on real job opportunities."
        },

        {
            "num": "3.12",
            "section": "Engagement Centre",
            "name": "Newsletter Subscribers",
            "url": "/admin/engagement/subscribers",
            "what": "Displays every person who subscribed to TalentHub job market updates, their verification status (pending, subscribed, unsubscribed), and consent timestamp.",
            "why": "Your proprietary talent and employer audience asset. You can monitor subscriber list health and export RFC-compliant CSVs.",
            "when": "Weekly to check list growth, or whenever marketing needs to export the subscriber list for external reporting.",
            "how": [
                "Click 'Newsletter Subscribers' in the left sidebar.",
                "Filter by Status ('subscribed', 'pending', 'unsubscribed').",
                "To Export: Click the 'Export CSV' button at top right to download a clean spreadsheet of all verified emails."
            ],
            "mistakes": "Never manually change an 'unsubscribed' person back to 'subscribed'—respect candidate consent under DPDP Act."
        },

        {
            "num": "3.13",
            "section": "Engagement Centre",
            "name": "Job Alert Subscriptions",
            "url": "/admin/engagement/job-alerts",
            "what": "Shows candidate email subscriptions filtered by specific job categories (e.g. IT, Automotive, Accounts) and preferred frequency (Immediate or Weekly).",
            "why": "Allows highly targeted job broadcasting. When you publish an IT job, only IT subscribers receive the job alert.",
            "when": "Check monthly to see which industry sectors have the highest candidate demand.",
            "how": [
                "Click 'Job Alert Subscriptions' in the left sidebar.",
                "Review subscriber roles, target categories, and frequency settings."
            ],
            "mistakes": "None—this list is managed automatically when candidates subscribe via the website popup."
        },

        {
            "num": "3.14",
            "section": "Engagement Centre",
            "name": "Email Campaigns",
            "url": "/admin/engagement/campaigns",
            "what": "Where you draft, schedule, and send rich marketing campaigns and monthly Pune hiring index newsletters.",
            "why": "Keeps your consultancy top-of-mind with corporate HR heads and senior candidates.",
            "when": "Monthly or bi-weekly for curated recruitment intelligence reports.",
            "how": [
                "Click 'Email Campaigns' -> 'Create Campaign'.",
                "Enter Campaign Name, Email Subject, and compose the newsletter content.",
                "Select Audience segment.",
                "Click 'Send Campaign Now' or schedule for future dispatch."
            ],
            "mistakes": "Always test send a campaign to your own email before broadcasting to your entire database."
        },

        {
            "num": "3.15",
            "section": "Engagement Centre",
            "name": "Unified Leads",
            "url": "/admin/engagement/leads",
            "what": "A single, master dashboard combining all inbound candidate inquiries, resume desk uploads, employer hiring requests, and newsletter signups in one chronological stream.",
            "why": "Eliminates having to check 3 different modules. Recruiters can see everything that happened on the website in real time.",
            "when": "Check every morning and before leaving in the evening.",
            "how": [
                "Click 'Unified Leads' in the left sidebar.",
                "Inspect the latest inbound leads, source channel, and contact info.",
                "Click 'Action' on any lead to open its full detail view."
            ],
            "mistakes": "Ensure every lead is assigned to a team member so inquiries don't fall through the cracks."
        },

        {
            "num": "3.16",
            "section": "Engagement Centre",
            "name": "Campaign Analytics & Executive Report",
            "url": "/admin/engagement/analytics",
            "what": "Displays impression views, click-through rates (CTR), dismissals, and conversions for all marketing campaigns. Includes the print-to-PDF Executive B2B Placement Report.",
            "why": "Shows you exactly which campaigns are working and allows you to print professional PDF recruitment performance reports for corporate clients.",
            "when": "Monthly for client executive reviews and internal strategy meetings.",
            "how": [
                "Click 'Campaign Analytics' in the left sidebar.",
                "Review the CTR% for active popup campaigns.",
                "To Print Executive Report: Click the 'Executive B2B Report (PDF / Print)' button.",
                "On the report view (/admin/engagement/analytics/report), inspect KPI cards and recruitment funnel.",
                "Click 'Print / Save as PDF'. In your browser print dialog, choose 'Save as PDF' to generate a beautiful B2B executive report!"
            ],
            "mistakes": "Don't share internal conversion figures with competitors; use the executive report strictly for approved clients."
        },

        {
            "num": "3.17",
            "section": "Engagement Centre",
            "name": "Cookie Consent Logs",
            "url": "/admin/engagement/cookies",
            "what": "An immutable audit log storing candidate and visitor privacy consent timestamps, IP hashes, and policy versions under India's Digital Personal Data Protection (DPDP) Act.",
            "why": "Statutory legal proof of compliance if an audit or regulatory inquiry occurs.",
            "when": "Quarterly compliance review or during statutory legal audits.",
            "how": [
                "Click 'Cookie Consent Logs' in the left sidebar.",
                "Review consent UUIDs, timestamps, and accepted categories ('Necessary', 'Analytics', 'Marketing')."
            ],
            "mistakes": "Never attempt to edit or delete these audit records—they are legally binding compliance proof."
        },

        # 6. Content & CMS
        {
            "num": "3.18",
            "section": "Content & CMS",
            "name": "Articles & Insights",
            "url": "/admin/content/articles",
            "what": "Publish and edit recruitment blogs, Pune salary benchmarks, interview preparation guides, and corporate hiring reports at https://talenthubconsultant.com/insights.",
            "why": "Drives free Google search traffic (SEO) to your website, positions TalentHub as Pune's premier recruitment authority, and attracts high-salary corporate candidates.",
            "when": "Weekly or bi-weekly when publishing fresh industry insights.",
            "how": [
                "Click 'Articles & Insights' -> Click '+ New Article'.",
                "Enter Article Title (e.g. 'Pune Tech Salary Guide 2026: IT & Engineering Benchmarks').",
                "Paste your article text into the rich-text editor box.",
                "In the Google Search Ranking (SEO) box, enter Meta Title (50-60 chars), Keywords, and Meta Description (150-160 chars). Check the live Google SERP preview simulator!",
                "Select Category, add a Featured Image URL, and set Status to 'PUBLISHED'.",
                "Click 'Save Article'. It goes live on /insights immediately!"
            ],
            "mistakes": "Avoid publishing thin, 1-paragraph articles. High-ranking Google articles typically contain 500+ words with practical local Pune career insights."
        },

        {
            "num": "3.19",
            "section": "Content & CMS",
            "name": "Page Content & SEO Suite",
            "url": "/admin/content/pages",
            "what": "Manage metadata, titles, and Google search ranking configuration for all fifteen (15) public pages on the website. Features live Google SERP preview simulators.",
            "why": "Guarantees top search rankings on Google for keywords like 'job consultancy Pune', 'recruitment agency Pune', 'hire tech talent Pune'.",
            "when": "Whenever updating corporate page titles, branding keywords, or social preview images.",
            "how": [
                "Click 'Page Content' in the left sidebar.",
                "Inspect the master table: note the green status pills ('58 chars • Optimal', '154 chars • Optimal') for each page.",
                "Click 'Edit SEO' on any page (e.g. Home, About Us, Employers, Jobs, Contact).",
                "In the Google Search Ranking Suite, type your Meta Title (aim for 50-60 characters) and Meta Description (aim for 150-160 characters).",
                "Watch the real-time Google SERP Snippet Simulator show you exactly how your page appears on Google search results!",
                "Add targeted search keywords separated by commas.",
                "Click 'Update Page SEO'. Changes take effect immediately across the public website!"
            ],
            "mistakes": "Never exceed 60 characters in Meta Title or 160 characters in Meta Description—Google truncates extra text with '...'!"
        },

        {
            "num": "3.20",
            "section": "Content & CMS",
            "name": "Testimonials & Reviews",
            "url": "/admin/content/testimonials",
            "what": "Manage client and candidate testimonials displayed on the homepage and about page.",
            "why": "Social proof and testimonials significantly increase candidate applications and employer mandate submissions.",
            "when": "Whenever a candidate is successfully placed or a corporate client praises your staffing SLA.",
            "how": [
                "Click 'Testimonials' -> '+ Add Testimonial'.",
                "Enter Client Name, Role/Designation, Company Name, Quote, and Rating (5 Stars).",
                "Toggle Status to 'PUBLISHED' and Save."
            ],
            "mistakes": "Always verify with corporate clients before publishing their company brand name publicly."
        },

        {
            "num": "3.21",
            "section": "Content & CMS",
            "name": "FAQs",
            "url": "/admin/content/faqs",
            "what": "Manage Frequently Asked Questions displayed on the public /faq and candidate/employer landing pages.",
            "why": "Answers common questions (e.g. consultancy fees, turnaround times, replacement guarantees) so recruiters spend less time answering repetitive questions.",
            "when": "When you notice employers or candidates frequently asking the same question on the phone.",
            "how": [
                "Click 'FAQs' -> '+ Add FAQ'.",
                "Enter Question and Answer.",
                "Select Category ('General', 'Employers', 'Candidates').",
                "Set Sort Order and Save."
            ],
            "mistakes": "Keep answers clear, concise, and friendly."
        },

        # 7. Administration
        {
            "num": "3.22",
            "section": "Administration",
            "name": "Staff & Users",
            "url": "/admin/users",
            "what": "Create staff accounts, reset passwords, toggle user active status, and assign roles for recruiters, HR executives, and team leaders.",
            "why": "Restricts system access to authorized employees only and protects confidential candidate resume data.",
            "when": "When hiring a new recruiter, when an employee departs (deactivate them immediately), or when updating staff roles.",
            "how": [
                "Click 'Staff & Users' in the left sidebar.",
                "To Add User: Click '+ Create Staff User'.",
                "Enter First Name, Last Name, Work Email, Phone, Username, and Password.",
                "Select Role: 'Super Administrator' or 'HR Manager'.",
                "Click 'Save User'. The user can now log in immediately!"
            ],
            "mistakes": "When an employee leaves the company, IMMEDIATELY edit their account and toggle 'Is Active' to OFF to revoke portal access."
        },

        {
            "num": "3.23",
            "section": "Administration",
            "name": "Roles & Permissions",
            "url": "/admin/roles",
            "what": "Configure role-based access control (RBAC). Super Admins can define granular permissions for each role.",
            "why": "Ensures HR staff only see recruitment tools while administrative controls (settings, staff accounts) remain restricted to senior leadership.",
            "when": "When establishing new job responsibilities or delegating specific modules.",
            "how": [
                "Click 'Roles & Permissions' in the left sidebar.",
                "Click 'Edit Permissions' on HR Manager role.",
                "Check or uncheck specific module permissions (e.g. Job Posting, Applications, CMS).",
                "Click 'Save Permissions'."
            ],
            "mistakes": "Never remove all permissions from Role 1 (Super Administrator)."
        },

        {
            "num": "3.24",
            "section": "Administration",
            "name": "Audit Logs",
            "url": "/admin/audit-logs",
            "what": "An immutable security log recording every staff login, status change, candidate deletion, and setting update with timestamp, staff username, and IP address.",
            "why": "Provides complete transparency and accountability across your recruiting team.",
            "when": "During monthly security reviews or when investigating why a status was changed.",
            "how": [
                "Click 'Audit Logs' in the left sidebar.",
                "Filter by Action ('LOGIN', 'STATUS_CHANGE', 'DELETE') or Staff Username.",
                "Read the exact change details and timestamp."
            ],
            "mistakes": "None—audit logs are automated and cannot be edited."
        },

        {
            "num": "3.25",
            "section": "Administration",
            "name": "Website Settings",
            "url": "/admin/settings",
            "what": "Master system settings organized into six (6) operational tabs: Company Profile, Contact & Operations Desk, Social Links, Legal & Compliance, GA4 Analytics, and Administrative Security.",
            "why": "Centralizes all core business data (phone numbers, WhatsApp, office address, working hours, and security toggles) in one convenient place.",
            "when": "When office hours change, when updating WhatsApp/helpline phone numbers, or when toggling Two-Step OTP verification.",
            "how": [
                "Click 'Website Settings' in the left sidebar.",
                "Section 1: Company Profile (Site Name, Tagline).",
                "Section 2: Contact Desk (Official Email, Phone, WhatsApp +91 72761 46573, Katraj Office Address, Working Hours).",
                "Section 3: Social Media Links (LinkedIn, Facebook, Instagram).",
                "Section 4: Legal & DPDP Compliance (Privacy Policy version, Terms).",
                "Section 5: Google Analytics (GA4 Measurement ID G-K6J0RWMLBS).",
                "Section 6: Administrative Security (Two-Step Email OTP Toggle). Toggle OFF for normal frictionless login; toggle ON when enhanced two-factor verification is required.",
                "Click 'Save All Settings' at the bottom of the screen."
            ],
            "mistakes": "Always verify phone numbers and email addresses before clicking save—they update across the entire public website footer and header instantly!"
        },

        {
            "num": "3.26",
            "section": "Administration",
            "name": "My Profile & Password",
            "url": "/admin/profile",
            "what": "Your personal staff account settings screen where you can update your name, work phone number, and change your login password.",
            "why": "Allows individual staff members to keep their credentials secure without contacting the technical team.",
            "when": "Quarterly to change your password, or whenever updating your personal phone contact.",
            "how": [
                "Click 'My Profile & Password' at the bottom of the left sidebar.",
                "To Change Password: Enter your Current Password, your New Password (minimum 8 characters), and Confirm New Password.",
                "Click 'Update Profile & Password'.",
                "Log out and log back in with your new password to verify."
            ],
            "mistakes": "Never share your staff password with anyone. Every recruiter must have their own unique login account."
        }
    ]

    for m in menus:
        dh.add_heading_2(doc, f"{m['num']} {m['name']} ({m['url']})")
        dh.add_paragraph(doc, f"Sidebar Location: {m['section']}  ->  {m['name']}", bold_prefix="Menu Location:")
        dh.add_paragraph(doc, m['what'], bold_prefix="What does it do?")
        dh.add_paragraph(doc, m['why'], bold_prefix="Why do you use it?")
        dh.add_paragraph(doc, m['when'], bold_prefix="When should you use it?")
        
        p_how = doc.add_paragraph()
        p_how.paragraph_format.space_before = Pt(4)
        p_how.paragraph_format.space_after = Pt(2)
        r_how = p_how.add_run("How to do it? (Step-by-Step Click Guide):")
        r_how.font.name = "Segoe UI"
        r_how.font.size = Pt(10)
        r_how.font.bold = True
        r_how.font.color.rgb = dh.COLOR_BRAND_GREEN

        for step in m['how']:
            dh.add_bullet(doc, step)

        dh.add_callout(doc, m['mistakes'], title="COMMON MISTAKE TO AVOID", callout_type="warn")

    # Section 4: Daily Operating Checklists
    dh.add_heading_1(doc, "4. Everyday Administrator Operating Checklists")
    dh.add_paragraph(
        doc,
        "Print or follow these daily routine checklists to ensure smooth, responsive recruitment operations:"
    )

    dh.add_heading_2(doc, "Morning 15-Minute Protocol (9:30 AM IST)")
    m_steps = [
        ("Step 1: Check Inbound Leads", "Go to Inbound Enquiries -> Employer Hiring Requests (/admin/enquiries/employers). Call any new corporate inquiries immediately."),
        ("Step 2: Screen New Applications", "Go to Recruitment -> Applications (/admin/applications). Filter status 'APPLIED'. Shortlist or reject new applicants."),
        ("Step 3: Review Outbox Queue", "Go to Communication -> Hostinger Mail Center (/admin/mail). Verify that overnight emails sent cleanly with zero errors."),
        ("Step 4: Sync Inbound Mail", "Click 'Sync Mailbox Now' to download any candidate interview replies or resume emails.")
    ]
    for st, sd in m_steps:
        dh.add_bullet(doc, sd, bold_prefix=st)

    dh.add_heading_2(doc, "Midday SLA Sweep (2:00 PM IST)")
    mid_steps = [
        ("Step 1: Follow-Up Deadlines", "Check Unified Leads (/admin/engagement/leads) for any mandate follow-up deadlines scheduled for today."),
        ("Step 2: Candidate Interview Reminders", "Verify that candidates scheduled for tomorrow's interviews have confirmed attendance."),
        ("Step 3: Contact Messages", "Review and clear general Contact Messages (/admin/enquiries/contact).")
    ]
    for st, sd in mid_steps:
        dh.add_bullet(doc, sd, bold_prefix=st)

    dh.add_heading_2(doc, "Evening Wrap-Up Protocol (6:30 PM IST)")
    eve_steps = [
        ("Step 1: Close Filled Vacancies", "Check Job Postings (/admin/jobs). If a client finalized a candidate today, close the job immediately."),
        ("Step 2: Send Tomorrow's Reminders", "Ensure all candidate interview reminders for tomorrow morning are enqueued."),
        ("Step 3: Log Out Safely", "Click 'Log Out' in the sidebar footer to protect your session.")
    ]
    for st, sd in eve_steps:
        dh.add_bullet(doc, sd, bold_prefix=st)

    # Section 5: Master Status Dictionary in Simple Words
    dh.add_heading_1(doc, "5. Status Dictionary: What Every Status Means in Simple Words")
    dh.add_paragraph(
        doc,
        "Here is what each status means across different modules in simple, non-technical terms:"
    )

    sd_headers = ["Module", "Status Word", "What it means in simple English", "What happens next"]
    sd_data = [
        ("Jobs", "PUBLISHED", "The job is actively visible to public visitors on the website.", "Candidates can click and submit applications."),
        ("Jobs", "DRAFT", "The job is being written or reviewed internally; hidden from public.", "Recruiters can edit and polish before publishing."),
        ("Jobs", "CLOSED", "The job was filled by a client or hiring is finished.", "Application form is locked; job shows as closed."),
        ("Applications", "APPLIED", "Candidate just submitted their resume; no recruiter has reviewed it yet.", "Recruiter needs to download and review resume."),
        ("Applications", "SHORTLISTED", "Candidate passed initial resume screening.", "System automatically sends congratulations email to candidate."),
        ("Applications", "INTERVIEW", "Candidate has an interview scheduled with client.", "Recruiter coordinates time and Teams/office location."),
        ("Applications", "HIRED", "Candidate accepted the client job offer!", "Consultancy invoices client; candidate onboarded."),
        ("Applications", "REJECTED", "Candidate did not match client requirement.", "System sends polite email; CV saved in talent vault."),
        ("Employer Leads", "NEW", "A company submitted a hiring request; awaiting first phone call.", "Recruiter must call client within 4-hour SLA."),
        ("Employer Leads", "CONTACTED", "Recruiter spoke with client; discussing candidate requirements.", "Recruiter searches resume bank for matching profiles."),
        ("Employer Leads", "IN_PROGRESS", "Recruiter is submitting candidate CVs to client.", "Client interviews submitted candidates."),
        ("Employer Leads", "CLOSED", "Hiring mandate completed or closed.", "Mandate marked finished; audit logged."),
        ("Newsletter", "subscribed", "Candidate/employer verified their email and wants job updates.", "Can receive recruitment drive notifications."),
        ("Newsletter", "unsubscribed", "User clicked unsubscribe link in email.", "System permanently stops sending emails to this address.")
    ]
    dh.add_styled_table(doc, sd_headers, sd_data, [1.1, 1.2, 2.5, 2.0])

    # Section 6: Emergency Contacts
    dh.add_heading_1(doc, "6. Emergency Contacts & Support Directory")
    dh.add_paragraph(
        doc,
        "If you ever encounter technical difficulties, database questions, or locked accounts, reach out to the contacts below:"
    )

    dh.add_callout(
        doc,
        "TalentHub Consultant Operational Headquarters\n"
        "Physical Office: Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046, India\n"
        "Operations Desk Telephone & WhatsApp: +91 72761 46573\n"
        "Official Operations Email: hr@talenthubconsultant.com\n"
        "Technical & Systems Admin: admin@talenthubconsultant.com",
        title="OPERATIONAL HEADQUARTERS & HELPLINE",
        callout_type="success"
    )

    out_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "TalentHub_Admin_Quick_Reference.docx")
    doc.save(out_path)
    print(f"SUCCESS: Generated Non-Technical Admin Handbook at: {out_path}")

if __name__ == "__main__":
    build_quick_reference()
