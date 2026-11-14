import os
import re

# 1. Update scratch/generate_master_user_manual.py
manual_path = 'scratch/generate_master_user_manual.py'
with open(manual_path, 'r', encoding='utf-8') as f:
    text = f.read()

# Update revision history
target_rev = '("v2.5.0", "Nov 01, 2026", "Principal Systems Architect", "Platform Gap Closure Release: Native Email OTP 2FA for Staff Login (/admin/verify-otp), Bulk Candidate Checkbox Actions & Stage Transitions (/admin/applications), Automated Resume Skills Extraction (GAP-03), DPDP Right to Erasure Self-Service Portal (/privacy/request-cv-deletion), Candidate Application Status Tracker (/application/tracker), Executive Placement & Operations Report (/admin/engagement/analytics/report), and Outbox Automation Webhook.")'
replace_rev = '''("v2.5.0", "Nov 01, 2026", "Principal Systems Architect", "Platform Gap Closure Release: Native Email OTP 2FA for Staff Login (/admin/verify-otp), Bulk Candidate Checkbox Actions & Stage Transitions (/admin/applications), Automated Resume Skills Extraction (GAP-03), DPDP Right to Erasure Self-Service Portal (/privacy/request-cv-deletion), Candidate Application Status Tracker (/application/tracker), Executive Placement & Operations Report (/admin/engagement/analytics/report), and Outbox Automation Webhook."),
        ("v2.6.0", "Nov 01, 2026", "SEO & Security Architect", "Top-Tier Dynamic SEO Engine & Administrative Security Release: Complete dynamic Google SEO integration across all 15 CMS public pages, Job Openings, and Blog Insights adhering strictly to 50-60 title and 150-160 snippet standards with interactive SERP preview simulators. Cleaned legacy CMS page editor (removed blank Hero Section Headlines). Added Section 6 to System Settings (/admin/settings) with Two-Step Verification (Email OTP) toggle defaulted to OFF for frictionless staff login. Fixed Dashboard Corporate Hiring Urgency doughnut chart empty-state rendering.")'''

if target_rev in text:
    text = text.replace(target_rev, replace_rev)
    print("Updated Revision History in manual.")
else:
    print("Warning: target_rev not found")

# Update 5.1 Dashboard
target_dash = '("5.1 Executive Dashboard (/admin/dashboard)",\n         "Navigation: Sidebar -> Overview -> Dashboard",\n         "The dashboard provides an instant operational pulse. Key metrics include: Active Published Jobs, New Unscreened Applications, Total Resumes in Vault, Uncontacted Employer Leads, and Pending Outbox Queue. The top Recruitment Alerts bell polls asynchronously to notify recruiters of inbound applications without manual page reloads."),'
replace_dash = '''("5.1 Executive Dashboard (/admin/dashboard)",
         "Navigation: Sidebar -> Overview -> Dashboard",
         "The dashboard provides an instant operational pulse. Key metrics include: Active Published Jobs, New Unscreened Applications, Total Resumes in Vault, Uncontacted Employer Leads, and Pending Outbox Queue. The top Recruitment Alerts bell polls asynchronously to notify recruiters of inbound applications without manual page reloads.\\n"
         "Corporate Hiring Urgency Doughnut Chart: Renders a real-time visual breakdown of client mandate turnaround requirements (Immediate, 15 Days, 30 Days, Pipeline). When zero active mandates exist, the chart engine automatically displays a subtle neutral ring with a centered \'Awaiting Leads / 0 Active Mandates\' status badge, preventing canvas collapse or invisible void states."),'''

if target_dash in text:
    text = text.replace(target_dash, replace_dash)
    print("Updated Dashboard in manual.")
else:
    print("Warning: target_dash not found")

# Update 5.2 Jobs SEO
target_job = '         "4. Select Hiring Urgency (Immediate, 30 Days, Exploratory).\\n"\n         "5. Check \'Broadcast to Newsletter Subscribers\' if immediate promotional dispatch is needed.\\n"\n         "6. Click \'Publish Vacancy\'. The mandate is immediately live and indexed in sitemaps."),'
replace_job = '''         "4. Select Hiring Urgency (Immediate, 30 Days, Exploratory).\\n"
         "5. Configure Google Search Optimization (Dynamic SEO): Enter targeted Meta Title (50-60 characters) and high-CTR Meta Description (150-160 characters). A live Google SERP Simulator previews the exact desktop search snippet in real time, accompanied by live character counter badges.\\n"
         "6. Check \'Broadcast to Newsletter Subscribers\' if immediate promotional dispatch is needed.\\n"
         "7. Click \'Publish Vacancy\'. The mandate is immediately live and indexed in Google sitemaps with dynamic OpenGraph tags."),'''

if target_job in text:
    text = text.replace(target_job, replace_job)
    print("Updated Job SEO in manual.")
else:
    print("Warning: target_job not found")

# Add modules 5.14, 5.15, 5.16
target_end_mod = '''        ("5.13 Campaign Analytics & Executive B2B Placement Report (/admin/engagement/analytics & /admin/engagement/analytics/report)",
         "Navigation: Sidebar -> Engagement Centre -> Campaign Analytics",
         "Provides audit-verified impression telemetry, unique visitor views, CTA clicks, dismissals, CTR percentages, and goal conversions across all popup campaigns and announcements.\\n"
         "Executive Placement Report (PDF / Print): Administrators can click \'Executive B2B Report (PDF / Print)\' to open a dedicated print-optimized executive summary (/admin/engagement/analytics/report). It displays live recruitment KPIs (Active Mandates, Inbound Applications, Pipeline Candidates, Placements Made), a graphical talent pipeline funnel with stage conversion rates, and campaign telemetry. Clicking \'Print / Save as PDF\' generates a clean B2B executive PDF report for corporate clients and executive reviews.")
    ]'''

replace_end_mod = '''        ("5.13 Campaign Analytics & Executive B2B Placement Report (/admin/engagement/analytics & /admin/engagement/analytics/report)",
         "Navigation: Sidebar -> Engagement Centre -> Campaign Analytics",
         "Provides audit-verified impression telemetry, unique visitor views, CTA clicks, dismissals, CTR percentages, and goal conversions across all popup campaigns and announcements.\\n"
         "Executive Placement Report (PDF / Print): Administrators can click \'Executive B2B Report (PDF / Print)\' to open a dedicated print-optimized executive summary (/admin/engagement/analytics/report). It displays live recruitment KPIs (Active Mandates, Inbound Applications, Pipeline Candidates, Placements Made), a graphical talent pipeline funnel with stage conversion rates, and campaign telemetry. Clicking \'Print / Save as PDF\' generates a clean B2B executive PDF report for corporate clients and executive reviews."),

        ("5.14 CMS Public Pages Management & Google SERP Simulator (/admin/content/pages)",
         "Navigation: Sidebar -> Content Management -> CMS Pages",
         "Allows administrators to curate and optimize metadata for all 15 public pages (Home, About Us, Solutions, Employers, Candidates, Jobs, Career Desk, Insights, Interviews, Contact Us, FAQ, Privacy Policy, Terms, Application Tracker, and CV Erasure Request).\\n"
         "1. The master table displays page titles, route paths, publication status, and live SEO character limit indicators (e.g., \'58 chars • Optimal\', \'154 chars • Optimal\').\\n"
         "2. Editing a page (/admin/content/pages/edit/:id) presents an interactive Google Search Ranking & Dynamic SEO Suite. The legacy blank \'Hero Section Headlines\' block has been cleanly eliminated in favor of direct, dynamic layout bindings.\\n"
         "3. Includes a live Google SERP Snippet Simulator replicating Google desktop search results in real time, featuring interactive character counters for Meta Title (50–60 characters) and Meta Description (150–160 characters), search keywords, and OpenGraph social preview images."),

        ("5.15 Thought Leadership & Blog Insights SEO (/admin/content/articles)",
         "Navigation: Sidebar -> Content Management -> Articles",
         "Allows editorial staff to publish recruitment intelligence and salary benchmark reports. Every article edit screen features the Google Search Ranking card with real-time character counters and live SERP preview, dynamically generating schema-compliant <meta> tags, OpenGraph article cards, and search keyword tags on /insights/:slug."),

        ("5.16 Brand & System Settings — Administrative Security Suite (/admin/settings)",
         "Navigation: Sidebar -> System Settings -> General Settings",
         "Comprehensive system configuration panel structured into 6 operational sections: Company & Brand Identity, Contact & Operations Desk, Social Media Profiles, Legal & Compliance Governance, Google Analytics 4 Telemetry, and Administrative Security & Two-Step Verification.\\n"
         "Two-Step Verification Toggle (Email OTP): Super Administrators can globally enable or disable two-factor email OTP login across the platform. Configured to OFF by default to provide frictionless administrative access, administrators can switch it ON with a single toggle when enhanced security protocols are required.")
    ]'''

if target_end_mod in text:
    text = text.replace(target_end_mod, replace_end_mod)
    print("Added modules 5.14, 5.15, 5.16 in manual.")
else:
    print("Warning: target_end_mod not found")

# Update 9.3 Security OTP
target_sec93 = 'Super Administrators can enable or disable Email OTP on a per-user basis via Staff User Management (/admin/users/edit/:id), allowing flexible role-based rollout.'
replace_sec93 = '''Super Administrators can enable or disable Email OTP on a per-user basis via Staff User Management (/admin/users/edit/:id), allowing flexible role-based rollout.
    dh.add_bullet(doc, "Global Security Setting (Default OFF): In System Settings (/admin/settings under Section 6: Administrative Security), a global toggle allows Super Administrators to turn Two-Step Verification ON or OFF system-wide. By default, it is configured to OFF to maintain fast, frictionless workflow for day-to-day recruiting tasks, while allowing instant activation whenever heightened security compliance is mandated.")'''

if target_sec93 in text:
    text = text.replace(target_sec93, replace_sec93)
    print("Updated Section 9.3 in manual.")
else:
    print("Warning: target_sec93 not found")

with open(manual_path, 'w', encoding='utf-8') as f:
    f.write(text)

print("Saved updated scratch/generate_master_user_manual.py")

