import os
import shutil
import re
import json

sql_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql"
bak_path = r"C:\Users\prave\Downloads\u139147679_talenthub.sql.bak"

# Restore from backup first so we start from clean original
if os.path.exists(bak_path):
    shutil.copyfile(bak_path, sql_path)
    print(f"Restored from backup: {bak_path}")

with open(sql_path, "r", encoding="utf-8", errors="replace") as f:
    sql = f.read()

# ── 1. UPDATE website_pages TABLE DATA ──────────────────────────────────────────
pages_data = [
    {
        'id': 1, 'slug': 'home', 'title': 'Home — TalentHub Consultant',
        'meta_title': 'TalentHub Consultant | Leading Job Consultancy in Pune',
        'meta_description': "TalentHub Consultant connects top engineering, IT, and leadership talent with Pune's leading corporate enterprises across Hinjawadi, Kharadi, and Chakan.",
        'meta_keywords': 'job consultancy Pune, recruitment agency Pune, placement consultants Pune, IT jobs Hinjawadi, recruitment agency Katraj',
    },
    {
        'id': 2, 'slug': 'about', 'title': 'About Us — TalentHub Consultant',
        'meta_title': 'About Us | Top Recruitment Consultancy in Pune | TalentHub',
        'meta_description': "Learn about TalentHub Consultant, Pune's trusted recruitment partner. Discover our ethical executive search, corporate staffing SLAs, and proven hiring record.",
        'meta_keywords': 'about TalentHub, Pune recruitment agency, executive search Pune, staffing firm Katraj, corporate headhunters Pune',
    },
    {
        'id': 3, 'slug': 'solutions', 'title': 'Recruitment Solutions & Staffing Tracks',
        'meta_title': 'Recruitment Solutions & Staffing Tracks | TalentHub Pune',
        'meta_description': 'Tailored recruitment solutions in Pune: IT staffing, engineering mandates, executive search, and turnkey volume hiring for Maharashtra corporate enterprises.',
        'meta_keywords': 'staffing solutions Pune, IT recruitment Pune, engineering hiring Chakan, executive headhunting Pune',
    },
    {
        'id': 4, 'slug': 'employers', 'title': 'Employers & Corporate Staffing',
        'meta_title': 'Hire Top Talent in Pune | Corporate Recruitment | TalentHub',
        'meta_description': 'Fast-track corporate hiring in Pune. TalentHub delivers pre-screened technical talent and executive leadership profiles to employers within 7 business days.',
        'meta_keywords': 'hire talent Pune, employer recruitment Pune, IT staffing Hinjawadi, corporate hiring partner Pune',
    },
    {
        'id': 5, 'slug': 'candidates', 'title': 'Candidates & Career Guidance',
        'meta_title': 'Pune Job Opportunities & Career Advisory | TalentHub',
        'meta_description': 'Explore verified career opportunities across Pune tech parks and manufacturing belts. Free candidate representation, salary benchmarks, and interview prep.',
        'meta_keywords': 'jobs in Pune, career guidance Pune, tech jobs Hinjawadi, engineering jobs Chakan, candidate placement Pune',
    },
    {
        'id': 6, 'slug': 'jobs', 'title': 'Current Job Openings in Pune',
        'meta_title': 'Current Job Vacancies in Pune | IT & Core Roles | TalentHub',
        'meta_description': 'Explore active job openings in Pune across software engineering, automotive, finance, and corporate management. Direct application with zero candidate fees.',
        'meta_keywords': 'job vacancies Pune, current openings Hinjawadi, Pune IT jobs, engineering recruitment Pune, walkin jobs Pune',
    },
    {
        'id': 7, 'slug': 'career-enquiry', 'title': 'Career Desk & Resume Submission',
        'meta_title': 'Submit Resume | Confidential Pune Talent Vault | TalentHub',
        'meta_description': "Upload your CV to TalentHub's private Pune candidate vault. Get matched with unadvertised leadership and technical mandates across Hinjawadi and Chakan.",
        'meta_keywords': 'submit resume Pune, upload CV Hinjawadi, candidate registration Pune, confidential job search Pune',
    },
    {
        'id': 8, 'slug': 'insights', 'title': 'Pune Recruitment Insights & Trends',
        'meta_title': 'Pune Hiring Market Trends & Salary Insights | TalentHub',
        'meta_description': 'Stay informed with Pune recruitment intelligence, engineering salary benchmarks, Hinjawadi tech hiring indexes, and quarterly corporate compensation reports.',
        'meta_keywords': 'Pune salary guide, IT hiring trends Pune, Hinjawadi tech salaries, recruitment insights Maharashtra',
    },
    {
        'id': 9, 'slug': 'interviews', 'title': 'Interview Preparation & Guidance',
        'meta_title': 'Interview Preparation & Career Guidance | TalentHub Pune',
        'meta_description': "Expert interview strategies, technical assessment tips, and executive salary negotiation guidance from TalentHub's veteran Pune recruitment consultants.",
        'meta_keywords': 'interview tips Pune, technical interview prep, career coaching Pune, executive interview guidance',
    },
    {
        'id': 10, 'slug': 'contact', 'title': 'Contact Us & Office Headquarters',
        'meta_title': 'Contact TalentHub | Recruitment Office Katraj, Pune Desk',
        'meta_description': 'Get in touch with TalentHub Consultant in Katraj, Pune. Reach our corporate hiring desk via phone +91 72761 46573, WhatsApp, email, or in-person advisory.',
        'meta_keywords': 'TalentHub office Pune, recruitment contact Katraj, staffing agency phone number, hiring consultancy Pune',
    },
    {
        'id': 11, 'slug': 'faq', 'title': 'Frequently Asked Questions',
        'meta_title': 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune',
        'meta_description': 'Common questions on corporate staffing SLAs, candidate representation, hiring turnaround, and recruitment processes with TalentHub Consultant across Pune.',
        'meta_keywords': 'recruitment FAQ Pune, hiring process questions, candidate consultancy fees Pune, staffing FAQs',
    },
    {
        'id': 12, 'slug': 'privacy', 'title': 'Privacy Policy & DPDP Governance',
        'meta_title': 'Privacy Policy & India DPDP Act Compliance | TalentHub',
        'meta_description': 'TalentHub Consultant privacy policy, data protection governance, affirmative consent practices, and statutory India DPDP Act compliance protocols for users.',
        'meta_keywords': 'privacy policy TalentHub, India DPDP Act, data protection recruitment, candidate privacy rights',
    },
    {
        'id': 13, 'slug': 'terms', 'title': 'Terms of Service & Conditions',
        'meta_title': 'Terms of Service & Advisory Conditions | TalentHub Pune',
        'meta_description': 'Terms of service governing the use of TalentHub Consultant recruitment portal, job applications, employer mandate agreements, and staffing advisory services.',
        'meta_keywords': 'terms of service TalentHub, user agreement recruitment, employer terms Pune, job application conditions',
    },
    {
        'id': 14, 'slug': 'application-tracker', 'title': 'Candidate Application Status Tracker',
        'meta_title': 'Track Job Application Status | Candidate Portal | TalentHub',
        'meta_description': 'Check real-time application progress, interview schedules, and screening updates for your submitted Pune job applications with your email and reference ID.',
        'meta_keywords': 'track application status, Pune job application tracker, interview status lookup, candidate application progress',
    },
    {
        'id': 15, 'slug': 'request-cv-deletion', 'title': 'Statutory CV Erasure Request (DPDP Act)',
        'meta_title': 'Request CV Deletion | DPDP Right to Erasure | TalentHub',
        'meta_description': "Exercise your statutory Right to Erasure under the India DPDP Act. Permanently shred and delete your resume files from TalentHub's private document vault.",
        'meta_keywords': 'delete resume DPDP, right to erasure Pune, candidate data deletion, remove CV TalentHub',
    },
]

def escape_sql(s):
    if s is None:
        return "NULL"
    # replace backslashes and single quotes
    s = s.replace("\\", "\\\\").replace("'", "\\'")
    return f"'{s}'"

wp_rows = []
for p in pages_data:
    content_obj = {
        "id": f"{p['slug']}-page",
        "title": p['title'],
        "slug": p['slug'],
        "is_published": True,
        "seo": {
            "meta_title": p['meta_title'],
            "meta_description": p['meta_description'],
            "meta_keywords": p['meta_keywords']
        },
        "sections": []
    }
    content_json_str = json.dumps(content_obj, ensure_ascii=False)
    
    row_str = f"({p['id']}, {escape_sql(p['slug'])}, {escape_sql(p['title'])}, {escape_sql(p['meta_title'])}, {escape_sql(p['meta_description'])}, NULL, 'PUBLISHED', {escape_sql(content_json_str)}, NULL, 1, '2026-10-28 15:40:10', 1, '2026-10-28 15:40:10', '2026-11-01 20:00:00', {escape_sql(p['meta_keywords'])})"
    wp_rows.append(row_str)

new_wp_insert = "INSERT INTO `website_pages` (`id`, `slug`, `title`, `meta_title`, `meta_description`, `og_image`, `status`, `content_json`, `draft_json`, `version`, `last_published_at`, `last_updated_by`, `created_at`, `updated_at`, `meta_keywords`) VALUES\n" + ",\n".join(wp_rows) + ";"

# Replace INSERT INTO `website_pages` ... ;
wp_pattern = r"INSERT INTO [`']?website_pages[`']? \([^\)]+\) VALUES\s*\(.*?\);"
sql, count_wp = re.subn(wp_pattern, new_wp_insert, sql, flags=re.DOTALL)
print(f"Replaced website_pages insert statements: {count_wp}")

# ── 2. POPULATE JOBS SEO ────────────────────────────────────────────────────────
# Jobs 101, 102, 103, 104, 105
jobs_seo = {
    101: {
        'meta_title': 'Steel Foreman Jobs in Pune | Construction Hiring | TalentHub',
        'meta_description': 'Hiring Steel Foreman in Pune. Lead rebar fabrication, bar-bending schedules, structural steel assembly, and safety compliance for top construction mandates.',
        'meta_keywords': 'steel foreman Pune, rebar fabrication Pune, construction jobs Pune, structural steel hiring',
    },
    102: {
        'meta_title': 'Purchase Executive Jobs in Pune | Procurement | TalentHub',
        'meta_description': 'Apply for Purchase Executive vacancy in Pune. Manage construction procurement, vendor negotiations, purchase orders, SAP MM costing, and site material supplies.',
        'meta_keywords': 'purchase executive Pune, procurement jobs Pune, vendor management Pune, SAP MM jobs Pune',
    },
    103: {
        'meta_title': 'HR Executive Jobs in Pune | Talent Acquisition | TalentHub',
        'meta_description': 'Join as HR Executive in Pune. Drive talent acquisition, candidate onboarding, employee relations, and statutory HR operations across Pune corporate offices.',
        'meta_keywords': 'HR executive jobs Pune, talent acquisition Pune, HR jobs Hinjawadi, recruiter vacancy Pune',
    },
    104: {
        'meta_title': 'Sales Officer Vacancy in Pune | B2B Corporate | TalentHub',
        'meta_description': 'Explore Sales Officer job openings in Pune. Drive B2B architectural glass and aluminium client acquisitions, corporate site visits, and commercial sales quotas.',
        'meta_keywords': 'sales officer Pune, B2B sales jobs Pune, corporate sales hiring, building material sales Pune',
    },
    105: {
        'meta_title': 'Junior Accountant Jobs in Pune | Tally & GST | TalentHub',
        'meta_description': 'Hiring Junior Accountant in Pune. Manage daily bookkeeping, Tally Prime entries, GST filing, TDS reconciliation, and vendor payments with rapid career growth.',
        'meta_keywords': 'junior accountant Pune, Tally jobs Pune, GST accountant Pune, accounts executive hiring Pune',
    }
}

for j_id, j_seo in jobs_seo.items():
    # Verify limits
    assert 50 <= len(j_seo['meta_title']) <= 60, f"Job {j_id} title length error"
    assert 150 <= len(j_seo['meta_description']) <= 160, f"Job {j_id} desc length error"

    # In jobs table, each line ends with: NULL, NULL, NULL, NULL), or NULL, NULL, NULL, NULL);
    # Pattern matching the job row ending
    pattern_j = rf"(\({j_id},.*?'2026-10-31 02:15:57',\s*)NULL,\s*NULL,\s*NULL,\s*NULL(\)[,;])"
    replacement_j = rf"\g<1>NULL, {escape_sql(j_seo['meta_title'])}, {escape_sql(j_seo['meta_description'])}, {escape_sql(j_seo['meta_keywords'])}\g<2>"
    sql, c_j = re.subn(pattern_j, replacement_j, sql)
    print(f"Updated Job {j_id} SEO: {c_j} matches")

# ── 3. POPULATE BLOG POSTS SEO KEYWORDS ─────────────────────────────────────────
articles_kw = {
    1: 'AI recruitment Pune, IT hiring Hinjawadi, AI resume screening, Pune tech staffing 2026',
    2: 'top companies hiring Pune, Hinjawadi tech parks, Chakan manufacturing jobs, Magarpatta IT careers',
    3: 'Pune salary guide 2026, IT salary benchmark Pune, Hinjawadi compensation index, engineering salaries Maharashtra',
    4: 'ATS resume guide Pune, resume optimization Pune, pass ATS screening, engineering CV format',
    5: 'construction interview tips Pune, project engineering questions, real estate developer jobs Pune',
    6: 'reduce time to hire Pune, Maharashtra recruitment turnaround, employer staffing SLAs, executive talent acquisition'
}

for a_id, a_kw in articles_kw.items():
    pattern_a = rf"(\({a_id},.*?'2026-10-28 15:40:10',\s*)NULL(\)[,;])"
    replacement_a = rf"\g<1>{escape_sql(a_kw)}\g<2>"
    sql, c_a = re.subn(pattern_a, replacement_a, sql)
    print(f"Updated Article {a_id} keywords: {c_a} matches")

# ── 4. CLEAN DUMMY DATA FROM candidates TABLE ──────────────────────────────────
# Candidates to keep: ID 3 (Amit Deshpande), 4 (Sneha Kulkarni), 9 (Sheetal Kakhandaki)
# Dummy candidates to remove: 1, 2, 5, 6, 7, 8 (all @example.com)
cand_insert_match = re.search(r"INSERT INTO [`']?candidates[`']? \([^\)]+\) VALUES\s*(.*?);", sql, re.DOTALL)
if cand_insert_match:
    cand_block = cand_insert_match.group(0)
    cand_tuples = re.findall(r"\(\d+,.*?\)(?:,\n|;)", cand_insert_match.group(1), re.DOTALL)
    kept_cands = []
    for t in cand_tuples:
        # Check if contains example.com
        if "example.com" not in t.lower():
            # strip trailing comma or semicolon
            cleaned_t = t.rstrip(",;\r\n ")
            kept_cands.append(cleaned_t)
            print("  KEPT CANDIDATE:", cleaned_t[:80])
        else:
            print("  REMOVED DUMMY CANDIDATE:", t[:80])

    cand_header = cand_block.split("VALUES")[0] + "VALUES\n"
    new_cand_insert = cand_header + ",\n".join(kept_cands) + ";"
    sql = sql.replace(cand_block, new_cand_insert)
    print("Candidates table cleaned successfully.")

# ── 5. CLEAN DUMMY DATA FROM candidate_documents TABLE ─────────────────────────
# Documents to keep: 13 (candidate 3), 14 (candidate 4), 15 (candidate 9)
doc_insert_match = re.search(r"INSERT INTO [`']?candidate_documents[`']? \([^\)]+\) VALUES\s*(.*?);", sql, re.DOTALL)
if doc_insert_match:
    doc_block = doc_insert_match.group(0)
    doc_tuples = re.findall(r"\(\d+,.*?\)(?:,\n|;)", doc_insert_match.group(1), re.DOTALL)
    kept_docs = []
    for t in doc_tuples:
        # check candidate id (the 2nd integer: (doc_id, cand_id, ...)
        m_cand = re.search(r"^\((\d+),\s*(\d+),", t.strip())
        if m_cand:
            c_id = int(m_cand.group(2))
            if c_id in [3, 4, 9]:
                cleaned_t = t.rstrip(",;\r\n ")
                kept_docs.append(cleaned_t)
                print("  KEPT DOCUMENT:", cleaned_t[:80])
            else:
                print("  REMOVED DUMMY DOCUMENT:", t[:80])

    doc_header = doc_block.split("VALUES")[0] + "VALUES\n"
    new_doc_insert = doc_header + ",\n".join(kept_docs) + ";"
    sql = sql.replace(doc_block, new_doc_insert)
    print("Candidate documents table cleaned successfully.")

# ── 6. CLEAN DUMMY DATA FROM applications TABLE ────────────────────────────────
# Application 1 was for dummy candidate 2. Application 2 is for real candidate 3.
app_insert_match = re.search(r"INSERT INTO [`']?applications[`']? \([^\)]+\) VALUES\s*(.*?);", sql, re.DOTALL)
if app_insert_match:
    app_block = app_insert_match.group(0)
    app_tuples = re.findall(r"\(\d+,.*?\)(?:,\n|;)", app_insert_match.group(1), re.DOTALL)
    kept_apps = []
    for t in app_tuples:
        # check candidate id: (id, job_id, candidate_id, ...)
        m_app = re.search(r"^\((\d+),\s*(\d+),\s*(\d+),", t.strip())
        if m_app:
            cand_id = int(m_app.group(3))
            if cand_id in [3, 4, 9]:
                cleaned_t = t.rstrip(",;\r\n ")
                kept_apps.append(cleaned_t)
                print("  KEPT APPLICATION:", cleaned_t[:80])
            else:
                print("  REMOVED DUMMY APPLICATION:", t[:80])

    if kept_apps:
        app_header = app_block.split("VALUES")[0] + "VALUES\n"
        new_app_insert = app_header + ",\n".join(kept_apps) + ";"
        sql = sql.replace(app_block, new_app_insert)
    print("Applications table cleaned successfully.")

# ── 7. CLEAN DUMMY DATA FROM mail_activity_logs & mail_message_index ───────────
# Remove lines with candidate.qa.test@example.com
for tbl_name in ['mail_activity_logs', 'mail_message_index']:
    m_mail = re.search(rf"INSERT INTO [`']?{tbl_name}[`']? \([^\)]+\) VALUES\s*(.*?);", sql, re.DOTALL)
    if m_mail:
        mail_block = m_mail.group(0)
        mail_tuples = re.findall(r"\(\d+,.*?\)(?:,\n|;)", m_mail.group(1), re.DOTALL)
        kept_mail = []
        for t in mail_tuples:
            if "example.com" not in t.lower():
                kept_mail.append(t.rstrip(",;\r\n "))
            else:
                print(f"  REMOVED {tbl_name} row:", t[:80])
        if kept_mail:
            cols = m_mail.group(0).split("VALUES")[0]
            new_mail_insert = cols + "VALUES\n" + ",\n".join(kept_mail) + ";"
            sql = sql.replace(mail_block, new_mail_insert)
        else:
            # If all were test logs, remove the entire INSERT statement
            sql = sql.replace(mail_block, f"-- Table `{tbl_name}` test logs cleared.")

# ── 8. RESET admin_users LOCKS & VERIFY OTP OFF ────────────────────────────────
# Ensure failed_login_count = 0, locked_until = NULL, email_otp_enabled = 0 for hr and admin
sql = re.sub(
    r"(\(2,\s*'hr'.*?), 6, '2026-10-30 18:00:20'(.*?\))",
    r"\g<1>, 0, NULL\g<2>",
    sql
)
print("Reset HR admin user lock state.")

# ── 9. ENSURE website_settings HAS admin_email_otp_enabled = '0' ───────────────
if "'admin_email_otp_enabled'" not in sql:
    # Append setting 36 to website_settings INSERT
    ws_match = re.search(r"(INSERT INTO [`']?website_settings[`']? [^\;]+);", sql, re.DOTALL)
    if ws_match:
        old_ws = ws_match.group(1)
        new_ws = old_ws + ",\n(37, 'admin_email_otp_enabled', '0', 'security', 0, '2026-11-01 20:00:00');"
        sql = sql.replace(old_ws + ";", new_ws)
        print("Appended admin_email_otp_enabled to website_settings.")

# ── 10. ADD MASTER DROP TABLE AND FOREIGN_KEY_CHECKS ───────────────────────────
all_tables = [
    'admin_users', 'announcements', 'announcement_events', 'applications', 'application_status_history',
    'audit_logs', 'blog_categories', 'blog_posts', 'candidates', 'candidate_documents',
    'captcha_tokens', 'contact_enquiries', 'cookie_consent_logs', 'data_retention_logs',
    'db_factories', 'db_migrations', 'email_campaigns', 'email_delivery_logs', 'email_outbox',
    'email_templates', 'employers', 'employer_enquiries', 'enquiry_followups', 'enquiry_notes',
    'factories', 'faqs', 'general_resume_submissions', 'jobs', 'job_alert_subscriptions',
    'job_categories', 'job_locations', 'login_attempts', 'mailbox_connections', 'mail_activity_logs',
    'mail_local_drafts', 'mail_message_index', 'mail_recruitment_links', 'mail_webhook_events',
    'media_assets', 'migrations', 'newsletter_activity_logs', 'newsletter_preferences',
    'newsletter_subscribers', 'notifications', 'notification_logs', 'password_resets',
    'permissions', 'rate_limit_buckets', 'roles', 'role_permissions', 'scheduled_job_runs',
    'sitemap_log', 'testimonials', 'user_roles', 'website_pages', 'website_page_history', 'website_settings'
]

drop_stmt = "DROP TABLE IF EXISTS " + ", ".join(f"`{t}`" for t in all_tables) + ";\n"

header_repl = f"""SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
{drop_stmt}START TRANSACTION;"""

sql = re.sub(
    r'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";.*?(START TRANSACTION;)',
    header_repl,
    sql,
    flags=re.DOTALL
)

if "SET FOREIGN_KEY_CHECKS = 1;" not in sql:
    sql += "\n\nSET FOREIGN_KEY_CHECKS = 1;\nCOMMIT;\n"

# Write back to file
with open(sql_path, "w", encoding="utf-8") as f:
    f.write(sql)

print(f"\nSUCCESS: Cleaned and fully enriched {sql_path}!")
