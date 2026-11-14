-- TalentHub Recruitment Consultancy — Follow-up Dateline, Reopen & Automated Status Emails Migration
-- Date: 2026-11-01

-- 1. Applications Table Enhancements
ALTER TABLE `applications`
  ADD COLUMN IF NOT EXISTS `follow_up_date` DATETIME NULL DEFAULT NULL AFTER `recruiter_notes`,
  ADD COLUMN IF NOT EXISTS `follow_up_notes` TEXT NULL DEFAULT NULL AFTER `follow_up_date`,
  ADD COLUMN IF NOT EXISTS `reopened_at` DATETIME NULL DEFAULT NULL AFTER `follow_up_notes`,
  ADD COLUMN IF NOT EXISTS `closed_at` DATETIME NULL DEFAULT NULL AFTER `reopened_at`,
  ADD COLUMN IF NOT EXISTS `last_contacted_at` DATETIME NULL DEFAULT NULL AFTER `closed_at`;

-- 2. Employer Enquiries Table Enhancements
ALTER TABLE `employer_enquiries`
  ADD COLUMN IF NOT EXISTS `follow_up_date` DATETIME NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `follow_up_notes` TEXT NULL DEFAULT NULL AFTER `follow_up_date`,
  ADD COLUMN IF NOT EXISTS `reopened_at` DATETIME NULL DEFAULT NULL AFTER `follow_up_notes`,
  ADD COLUMN IF NOT EXISTS `closed_at` DATETIME NULL DEFAULT NULL AFTER `reopened_at`,
  ADD COLUMN IF NOT EXISTS `last_contacted_at` DATETIME NULL DEFAULT NULL AFTER `closed_at`;

-- 3. Contact Enquiries Table Enhancements
ALTER TABLE `contact_enquiries`
  ADD COLUMN IF NOT EXISTS `follow_up_date` DATETIME NULL DEFAULT NULL AFTER `message`,
  ADD COLUMN IF NOT EXISTS `follow_up_notes` TEXT NULL DEFAULT NULL AFTER `follow_up_date`,
  ADD COLUMN IF NOT EXISTS `reopened_at` DATETIME NULL DEFAULT NULL AFTER `follow_up_notes`,
  ADD COLUMN IF NOT EXISTS `closed_at` DATETIME NULL DEFAULT NULL AFTER `reopened_at`,
  ADD COLUMN IF NOT EXISTS `last_contacted_at` DATETIME NULL DEFAULT NULL AFTER `closed_at`;

-- 4. Application Status History Enhancements
ALTER TABLE `application_status_history`
  ADD COLUMN IF NOT EXISTS `follow_up_date` DATETIME NULL DEFAULT NULL AFTER `comment`,
  ADD COLUMN IF NOT EXISTS `email_dispatched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `follow_up_date`,
  ADD COLUMN IF NOT EXISTS `notification_type` VARCHAR(50) NULL DEFAULT NULL AFTER `email_dispatched`;

-- 5. Insert Automated Email Templates
INSERT INTO `email_templates` (`code`, `name`, `subject`, `body_html`, `body_text`, `variables_help`, `is_active`, `updated_at`)
VALUES
(
  'app_candidate_rejected',
  'Candidate Application Status: Not Selected / Talent Pool Retained',
  'Update regarding your application for {{job_title}} — TalentHub Consultant',
  '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 620px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;"><div style="border-bottom: 2px solid #1D6A34; padding-bottom: 16px; margin-bottom: 20px;"><h2 style="color: #072412; margin: 0; font-size: 22px;">TalentHub Consultant</h2><p style="color: #1D6A34; font-size: 13px; font-weight: bold; margin: 4px 0 0 0; text-transform: uppercase;">Executive Recruitment Services</p></div><p>Dear <strong>{{candidate_name}}</strong>,</p><p>Thank you for taking the time to apply for the position of <strong>{{job_title}}</strong>{{company_clause}} through TalentHub Consultant.</p><p>Our recruitment specialists and the hiring committee have thoroughly reviewed your background. While your credentials are commendable, after careful deliberation, the hiring team has decided to proceed with other candidates whose skill profiles more precisely match the immediate requirements for this opening.</p>{{notes_block}}<p><strong>Your profile remains active in our Executive Talent Repository:</strong></p><p>TalentHub regularly manages confidential technical, engineering, and corporate leadership searches across Pune and Maharashtra. We have catalogued your resume in our talent bank, and our consultants will reach out directly as new matching vacancies arise.</p><div style="text-align: center; margin: 28px 0;"><a href="{{jobs_url}}" style="display: inline-block; padding: 12px 26px; background-color: #1D6A34; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 8px; font-size: 13px;">Explore Other Opportunities in Pune</a></div><p>We sincerely appreciate your interest and wish you every success in your ongoing career endeavors.</p><hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" /><p style="font-size: 12px; color: #64748b; margin: 0;">TalentHub Pune Recruitment Desk<br>hr@talenthubconsultant.com | +91 72761 46573</p></div>',
  'Dear {{candidate_name}},\n\nThank you for applying for {{job_title}} through TalentHub Consultant.\n\nAfter thorough evaluation, our hiring team has decided to advance with other candidates for this specific vacancy.\n\nYour resume remains active in our Executive Talent Repository, and our consultants will reach out directly as new matching opportunities open.\n\nBrowse current vacancies: {{jobs_url}}\n\nBest regards,\nTalentHub Recruitment Desk',
  '{{candidate_name}}, {{job_title}}, {{company_name}}, {{recruiter_notes}}, {{jobs_url}}',
  1,
  NOW()
),
(
  'app_candidate_closed',
  'Candidate Application Status: Mandate Concluded / Closed',
  'Recruitment Mandate Concluded: {{job_title}} — TalentHub Consultant',
  '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 620px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;"><div style="border-bottom: 2px solid #1D6A34; padding-bottom: 16px; margin-bottom: 20px;"><h2 style="color: #072412; margin: 0; font-size: 22px;">TalentHub Consultant</h2><p style="color: #1D6A34; font-size: 13px; font-weight: bold; margin: 4px 0 0 0; text-transform: uppercase;">Recruitment Notice</p></div><p>Dear <strong>{{candidate_name}}</strong>,</p><p>We are writing to inform you that the recruitment mandate for the position of <strong>{{job_title}}</strong> has now been formally concluded.</p><p>We want to thank you for your participation throughout the process and for sharing your credentials with TalentHub Consultant.</p>{{notes_block}}<p>Because your profile is retained in our verified talent bank, our recruitment consultants will contact you as soon as new positions opening in your domain are listed.</p><div style="text-align: center; margin: 28px 0;"><a href="{{jobs_url}}" style="display: inline-block; padding: 12px 26px; background-color: #1D6A34; color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 8px; font-size: 13px;">View Active Opportunities</a></div><hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" /><p style="font-size: 12px; color: #64748b; margin: 0;">TalentHub Consultant Pune | hr@talenthubconsultant.com | +91 72761 46573</p></div>',
  'Dear {{candidate_name}},\n\nThe recruitment mandate for {{job_title}} has been formally closed.\n\nThank you for sharing your credentials. Your profile remains active in our verified talent bank, and our recruiters will reach out as new matching opportunities open.\n\nBrowse current openings: {{jobs_url}}\n\nBest regards,\nTalentHub Recruitment Desk',
  '{{candidate_name}}, {{job_title}}, {{recruiter_notes}}, {{jobs_url}}',
  1,
  NOW()
),
(
  'app_candidate_shortlisted',
  'Candidate Application Status: Profile Shortlisted',
  'Congratulations! Profile Shortlisted for {{job_title}} — TalentHub Consultant',
  '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 620px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;"><div style="border-bottom: 2px solid #1D6A34; padding-bottom: 16px; margin-bottom: 20px;"><h2 style="color: #072412; margin: 0; font-size: 22px;">TalentHub Consultant</h2><p style="color: #1D6A34; font-size: 13px; font-weight: bold; margin: 4px 0 0 0; text-transform: uppercase;">Shortlist Notification</p></div><p>Dear <strong>{{candidate_name}}</strong>,</p><p>We are pleased to inform you that your profile has been <strong>shortlisted</strong> for the role of <strong>{{job_title}}</strong>{{company_clause}}.</p><p>Our talent acquisition consultants have reviewed your resume against the client mandate criteria and forwarded your profile directly to the primary hiring committee.</p>{{notes_block}}<p><strong>Next Steps:</strong></p><p>A designated TalentHub recruitment coordinator will contact you shortly via phone or WhatsApp to verify your current availability, notice period, and preferred interview schedule.</p><hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" /><p style="font-size: 12px; color: #64748b; margin: 0;">TalentHub Recruitment Desk | Pune, Maharashtra | hr@talenthubconsultant.com</p></div>',
  'Dear {{candidate_name}},\n\nYour application for {{job_title}} has been shortlisted!\n\nOur client hiring team has accepted your credentials for next-stage evaluation. A recruitment consultant will contact you shortly to coordinate interview availability.\n\nBest regards,\nTalentHub Recruitment Desk',
  '{{candidate_name}}, {{job_title}}, {{company_name}}, {{recruiter_notes}}',
  1,
  NOW()
),
(
  'employer_mandate_closed',
  'Employer Hiring Mandate Status: Concluded / Archived',
  'Corporate Mandate Update: {{required_roles}} — TalentHub Corporate Desk',
  '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 620px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;"><div style="border-bottom: 2px solid #1D6A34; padding-bottom: 16px; margin-bottom: 20px;"><h2 style="color: #072412; margin: 0; font-size: 22px;">TalentHub Consultant</h2><p style="color: #1D6A34; font-size: 13px; font-weight: bold; margin: 4px 0 0 0; text-transform: uppercase;">Corporate Advisory Desk</p></div><p>Dear <strong>{{contact_person}}</strong>,</p><p>We are writing regarding your hiring mandate for <strong>{{required_roles}}</strong> at <strong>{{company_name}}</strong>.</p><p>This mandate has now been marked as <strong>Closed</strong> in our recruitment management system. We thank you and the <strong>{{company_name}}</strong> team for partnering with TalentHub Consultant.</p>{{notes_block}}<p>Should your organization initiate new staffing mandates or executive search requirements in Pune, our specialized corporate desk remains at your disposal.</p><hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" /><p style="font-size: 12px; color: #64748b; margin: 0;">TalentHub Corporate Staffing Advisory | Pune | hr@talenthubconsultant.com</p></div>',
  'Dear {{contact_person}},\n\nYour recruitment mandate for {{required_roles}} at {{company_name}} has been marked as Closed.\n\nThank you for partnering with TalentHub Consultant.\n\nBest regards,\nTalentHub Corporate Advisory',
  '{{contact_person}}, {{company_name}}, {{required_roles}}, {{recruiter_notes}}',
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `subject` = VALUES(`subject`),
  `body_html` = VALUES(`body_html`),
  `body_text` = VALUES(`body_text`),
  `variables_help` = VALUES(`variables_help`),
  `is_active` = 1,
  `updated_at` = NOW();
