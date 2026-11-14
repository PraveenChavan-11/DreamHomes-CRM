import os
import re

path = 'scratch/generate_master_user_manual.py'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update Revision History table
old_rev = '        ("v2.4.0", "Nov 01, 2026", "Quality Assurance", "Unified admin view rendering with renderAdminView(), resolved 500 errors, hardened popup z-index stacking (z: 99999999), and locked footer compact newsletter layout.")\n    ]'
new_rev = '        ("v2.4.0", "Nov 01, 2026", "Quality Assurance", "Unified admin view rendering with renderAdminView(), resolved 500 errors, hardened popup z-index stacking (z: 99999999), and locked footer compact newsletter layout."),\n        ("v2.5.0", "Nov 01, 2026", "Principal Systems Architect", "Platform Gap Closure Release: Native Email OTP 2FA for Staff Login (/admin/verify-otp), Bulk Candidate Checkbox Actions & Stage Transitions (/admin/applications), Automated Resume Skills Extraction (GAP-03), DPDP Right to Erasure Self-Service Portal (/privacy/request-cv-deletion), Candidate Application Status Tracker (/application/tracker), Executive Placement & Operations Report (/admin/engagement/analytics/report), and Outbox Automation Webhook.")\n    ]'
content = content.replace(old_rev, new_rev)

# 2. Add public modules 3.8 and 3.9 in Section 3
old_pub = '        ("3.7 Submission Status Pages (/thank-you & /submission-failed)", "Web\\SubmissionStatusController",\n         "Dedicated post-submission status pages. /thank-you displays a dynamic 3-step SLA timeline (Request Logged -> Consultant Assigned -> Direct Callback) with unique reference ID. /submission-failed displays retry options and direct helpline pills.")\n    ]'
new_pub = '''        ("3.7 Submission Status Pages (/thank-you & /submission-failed)", "Web\\SubmissionStatusController",
         "Dedicated post-submission status pages. /thank-you displays a dynamic 3-step SLA timeline (Request Logged -> Consultant Assigned -> Direct Callback) with unique reference ID. /submission-failed displays retry options and direct helpline pills."),

        ("3.8 Candidate Application Status Tracker (/application/tracker)", "Web\\CandidateComplianceController::trackApplication",
         "Public self-service tracker allowing applicants to check the real-time status of their job applications. Candidates enter their registered email and optional Application Reference ID. The interface displays an interactive recruitment progress stepper (Applied -> Under Review -> Shortlisted -> Interview Scheduled -> Offer / Concluded), role specifications, submission timestamp, and recruiter feedback notes."),

        ("3.9 Statutory DPDP Candidate Right to Erasure (/privacy/request-cv-deletion)", "Web\\CandidateComplianceController::requestCvDeletion & confirmCvDeletion",
         "Under Section 12 of India\'s Digital Personal Data Protection (DPDP) Act 2023/2026, candidates have the statutory right to request erasure of their personal information and CVs. Candidates enter their email address to trigger a cryptographic verification link. Upon confirmation, the system shreds and deletes physical resume files from writable/vault/resumes/, anonymizes candidate records in MySQL, logs an immutable data retention audit trail, and issues an official Certificate of Erasure.")
    ]'''
content = content.replace(old_pub, new_pub)

# 3. Update Section 5.3 and add 5.13 in Section 5
old_mod53 = '        ("5.3 Inbound Candidate Applications (/admin/applications)",\n         "Navigation: Sidebar -> Candidate Applications -> Inbound Applications",\n         "Step-by-Step Screening & Stage Transition Protocol:\\n"\n         "1. Filter applications by status \'APPLIED\'.\\n"\n         "2. Click \'Download Resume\' to inspect the candidate\'s CV.\\n"\n         "3. Click \'Add Note\' to document screening observations and current/expected CTC.\\n"\n         "4. Transition status via inline dropdown to \'SHORTLISTED\', \'INTERVIEW\', or \'REJECTED\'.\\n"\n         "5. The system automatically triggers the appropriate branded status email template to the candidate."),'

new_mod53 = '''        ("5.3 Inbound Candidate Applications & Bulk Action Engine (/admin/applications)",
         "Navigation: Sidebar -> Candidate Applications -> Inbound Applications",
         "Step-by-Step Screening & Stage Transition Protocol:\\n"
         "1. Filter applications by stage (APPLIED, SCREENING, SHORTLISTED, INTERVIEW, OFFER, HIRED, REJECTED, WITHDRAWN).\\n"
         "2. Click \'Download Resume\' to inspect the candidate\'s CV from the private vault.\\n"
         "3. Click \'Add Note\' to document recruiter screening notes, salary requirements, and notice period.\\n"
         "4. Bulk Selection & Stage Transition: Check individual candidate checkboxes or click the master \'Select All\' checkbox in the table header. A floating Bulk Action Bar dynamically appears at the bottom of the viewport showing selected count.\\n"
         "5. In the Bulk Action Bar, select the target action: Transition Stage (Shortlisted, Screening, Interview, Offer, Hired, Rejected, Withdrawn) or \'Export Selected to CSV\'.\\n"
         "6. Click \'Apply Action\'. All selected records are updated atomically inside a database transaction, audit logs are recorded, and automated candidate email notifications are enqueued via EnquiryAutomationService."),'''

content = content.replace(old_mod53, new_mod53)

old_mod512 = '        ("5.12 Staff User Accounts & Roles (/admin/users & /admin/roles)",\n         "Navigation: Sidebar -> Administration -> Staff Accounts / Roles",\n         "Super Administrators can create staff accounts, assign granular permissions, and toggle access. System protects role 1 (Super Administrator) from deletion.")\n    ]'
new_mod512 = '''        ("5.12 Staff User Accounts & Roles (/admin/users & /admin/roles)",
         "Navigation: Sidebar -> Administration -> Staff Accounts / Roles",
         "Super Administrators can create staff accounts, assign granular permissions, toggle active status, and enable/disable Native Email OTP Two-Factor Authentication. The system protects role 1 (Super Administrator) from deletion."),

        ("5.13 Campaign Analytics & Executive B2B Placement Report (/admin/engagement/analytics & /admin/engagement/analytics/report)",
         "Navigation: Sidebar -> Engagement Centre -> Campaign Analytics",
         "Provides audit-verified impression telemetry, unique visitor views, CTA clicks, dismissals, CTR percentages, and goal conversions across all popup campaigns and announcements.\\n"
         "Executive Placement Report (PDF / Print): Administrators can click \'Executive B2B Report (PDF / Print)\' to open a dedicated print-optimized executive summary (/admin/engagement/analytics/report). It displays live recruitment KPIs (Active Mandates, Inbound Applications, Pipeline Candidates, Placements Made), a graphical talent pipeline funnel with stage conversion rates, and campaign telemetry. Clicking \'Print / Save as PDF\' generates a clean B2B executive PDF report for corporate clients and executive reviews.")
    ]'''

content = content.replace(old_mod512, new_mod512)

# 4. Add Section 9.3 and 9.4 in Section 9
old_sec9 = '    img2_path = os.path.join(os.path.dirname(__file__), "..", "screenshots", "02_homepage_cookie_banner.png")'
new_sec9 = '''    dh.add_heading_2(doc, "9.3 Native Email OTP Two-Factor Authentication (/admin/verify-otp)")
    dh.add_paragraph(
        doc,
        "To ensure impenetrable staff account security without imposing complex third-party authenticator apps (such as Google Authenticator), TalentHub implements a native, zero-dependency Email One-Time Passcode (OTP) system:"
    )
    dh.add_bullet(doc, "Passcode Generation & Security: When a staff member submits valid username and password credentials, AuthController generates a cryptographically secure 6-digit numeric code, hashes it with BCRYPT, stores an expiry timestamp of 10 minutes, and emails it using a branded security template (admin_login_otp.php).")
    dh.add_bullet(doc, "Verification Interface: The user is redirected to /admin/verify-otp displaying a masked email indicator (e.g. p***n@talenthubconsultant.com), remote client IP address, and a 60-second cooldown timer before resending.")
    dh.add_bullet(doc, "Administrative Control: Super Administrators can enable or disable Email OTP on a per-user basis via Staff User Management (/admin/users/edit/:id), allowing flexible role-based rollout.")

    dh.add_heading_2(doc, "9.4 India DPDP Act 2023/2026 Statutory Compliance & Right to Erasure")
    dh.add_paragraph(
        doc,
        "TalentHub is fully engineered for compliance with India\'s Digital Personal Data Protection Act 2023/2026:"
    )
    dh.add_bullet(doc, "Affirmative Consent: Candidate applications and newsletter subscriptions require explicit, affirmative opt-in with recorded consent timestamps, policy versions, and IP addresses.")
    dh.add_bullet(doc, "Self-Service Erasure Portal: Candidates can exercise their Right to Correction and Erasure at /privacy/request-cv-deletion. A cryptographic token verifies ownership of the email address before any deletion is executed.")
    dh.add_bullet(doc, "Physical Shredding & Anonymization: Confirmed requests physically unlink and delete the candidate\'s resume file from writable/vault/resumes/, remove document vault database rows, anonymize candidate PII, and record an audit log in data_retention_logs.")

    img2_path = os.path.join(os.path.dirname(__file__), "..", "screenshots", "02_homepage_cookie_banner.png")'''

content = content.replace(old_sec9, new_sec9)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated scratch/generate_master_user_manual.py successfully.")

