-- ==============================================================================
-- TalentHub Consultant — Dashboard Pipeline Velocity & Testimonials Sync
-- Date: October 31, 2026
-- ==============================================================================

-- Select your active database in Hostinger phpMyAdmin (e.g. u139147679_talenthub)
-- USE talenthub_db;

-- 1. Fix Testimonials Content
UPDATE testimonials SET 
  content = 'TalentHub Consultant helped our engineering division scale from 15 to 45 developers within 4 months. Their screening accuracy and cultural alignment saved our hiring managers dozens of interview hours.',
  designation = 'Senior Engineering Manager',
  company = 'Pune Tech Enterprise',
  rating = 5,
  is_published = 1
WHERE id = 1;

UPDATE testimonials SET 
  content = 'Working with TalentHub has streamlined our mid-to-senior technical recruitment. Candidates delivered to our pipeline are thoroughly pre-screened with verified technical skills and realistic notice periods.',
  designation = 'Head of Human Resources',
  company = 'Hinjawadi IT Park Enterprise',
  rating = 5,
  is_published = 1
WHERE id = 2;

UPDATE testimonials SET 
  content = 'The team at TalentHub managed my career transition seamlessly. They matched my AWS and DevOps background with the exact leadership scope and compensation package I was targeting in Pune.',
  designation = 'Lead Cloud Solutions Architect',
  company = 'Placed Candidate (Pune)',
  rating = 5,
  is_published = 1
WHERE id = 3;

UPDATE testimonials SET 
  content = 'TalentHub\'s responsiveness and deep understanding of Pune\'s industrial and engineering talent pool is unmatched. Highly recommended for critical executive and technical mandates.',
  designation = 'VP of Operations',
  company = 'Chakan Automotive & Manufacturing',
  rating = 5,
  is_published = 1
WHERE id = 4;

UPDATE testimonials SET 
  content = 'Exceptional recruitment experience. Transparent communication throughout interview rounds, salary negotiations, and onboarding. TalentHub is truly professional and candidate-centric.',
  designation = 'Senior Product Manager',
  company = 'Placed Candidate (Baner)',
  rating = 5,
  is_published = 1
WHERE id = 5;

-- 2. Normalize Existing Applications Status
UPDATE applications SET status = 'APPLIED' WHERE status = '' OR status IS NULL OR status = 'NEW';

-- 3. Normalize Contact Enquiries Status
UPDATE contact_enquiries SET status = 'UNREAD' WHERE status = '' OR status IS NULL OR status = 'NEW';

-- 4. Normalize Employer Enquiries Urgency & Status
UPDATE employer_enquiries SET urgency = 'IMMEDIATE' WHERE urgency = '' OR urgency = 'Immediate';
UPDATE employer_enquiries SET status = 'NEW' WHERE status = '' OR status IS NULL;

-- 5. Seed Authentic Pune Candidates (Compatible with & without consent_policy_version column)
-- Optional: If you wish to ensure consent_policy_version column is present, execute:
-- ALTER TABLE candidates ADD COLUMN IF NOT EXISTS consent_policy_version VARCHAR(50) DEFAULT 'v1.0-DPDP-2026' AFTER consent_timestamp;

INSERT INTO candidates (id, full_name, email, phone, current_location, preferred_location, total_experience_years, `current_role`, primary_skills, notice_period_days, consent_received)
VALUES
(3, 'Amit Deshpande', 'amit.deshpande@example.com', '+91 98220 11223', 'Pune, Maharashtra', 'Pune, Maharashtra', 6.5, 'Procurement Specialist', 'Vendor Management, SAP MM, Negotiation', 30, 1),
(4, 'Sneha Kulkarni', 'sneha.kulkarni@example.com', '+91 98230 44556', 'Pune, Maharashtra', 'Pune, Maharashtra', 4.0, 'Full Stack Developer', 'React, Node.js, TypeScript, PostgreSQL', 15, 1),
(5, 'Priya Joshi', 'priya.joshi@example.com', '+91 97640 77889', 'Pune, Maharashtra', 'Pune, Maharashtra', 7.0, 'HR Business Partner', 'Talent Acquisition, Employee Relations, HR Analytics', 30, 1),
(6, 'Rahul More', 'rahul.more@example.com', '+91 98810 99001', 'Pune, Maharashtra', 'Pune, Maharashtra', 8.0, 'Senior Java Engineer', 'Java 17, Spring Boot, Microservices, Kafka', 60, 1),
(7, 'Vikrant Patil', 'vikrant.patil@example.com', '+91 99210 22334', 'Pune, Maharashtra', 'Pune, Maharashtra', 5.0, 'Corporate Sales Manager', 'B2B Sales, Enterprise Account Management, CRM', 30, 1),
(8, 'Tanvi Shah', 'tanvi.shah@example.com', '+91 98500 55667', 'Pune, Maharashtra', 'Pune, Maharashtra', 3.5, 'Financial Accountant', 'Tally Prime, GST Compliance, Financial Reporting', 15, 1)
ON DUPLICATE KEY UPDATE 
  full_name = VALUES(full_name),
  phone = VALUES(phone),
  `current_role` = VALUES(`current_role`),
  total_experience_years = VALUES(total_experience_years);

-- 6. Seed Pipeline Applications Across Stages
INSERT INTO applications (id, job_id, candidate_id, document_id, cover_message, `status`, recruiter_notes, rating, `source`, ip_address, idempotency_key, created_at)
VALUES
(2, 101, 1, 1, 'Over 5 years of industrial site supervision experience in Chakan.', 'SCREENING', 'Strong technical credentials; scheduled for preliminary phone screen.', 4, 'CAREER_PORTAL', '127.0.0.1', 'app_101_cand_1_seed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 102, 3, 2, 'Hands-on SAP MM and vendor procurement for manufacturing plants.', 'SHORTLISTED', 'Resume shared with hiring manager at Austin Realty.', 5, 'CAREER_PORTAL', '127.0.0.1', 'app_102_cand_3_seed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 102, 4, 3, '4+ years handling technical supply chain and procurement workflows.', 'INTERVIEW', 'Round 1 technical discussion completed; Round 2 scheduled with VP.', 4, 'CAREER_PORTAL', '127.0.0.1', 'app_102_cand_4_seed', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(5, 103, 5, 5, 'HRBP with deep experience in IT headcount scaling and retention.', 'OFFER', 'Formal offer letter released; awaiting acceptance signature.', 5, 'CAREER_PORTAL', '127.0.0.1', 'app_103_cand_5_seed', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 103, 6, 6, 'Proven senior background in HR operations and compliance.', 'HIRED', 'Candidate accepted offer; onboarding scheduled for 1st of month.', 5, 'CAREER_PORTAL', '127.0.0.1', 'app_103_cand_6_seed', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 104, 7, 7, 'B2B enterprise sales specialist targeting industrial clients.', 'INTERVIEW', 'Final round interview scheduled with Managing Director.', 4, 'CAREER_PORTAL', '127.0.0.1', 'app_104_cand_7_seed', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 105, 8, 8, 'Full-cycle accounting, Tally Prime, and GST compliance.', 'HIRED', 'Joined Pune corporate office on Oct 25; probation underway.', 5, 'CAREER_PORTAL', '127.0.0.1', 'app_105_cand_8_seed', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(9, 105, 1, 9, 'Financial documentation and ledger reconciliation experience.', 'SHORTLISTED', 'Candidate shortlisted for panel review.', 3, 'CAREER_PORTAL', '127.0.0.1', 'app_105_cand_1_seed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(10, 104, 2, 10, 'Enterprise client acquisition and channel sales background.', 'APPLIED', 'Application received from career portal; awaiting recruiter review.', 3, 'CAREER_PORTAL', '127.0.0.1', 'app_104_cand_2_seed', NOW())
ON DUPLICATE KEY UPDATE 
  `status` = VALUES(`status`),
  recruiter_notes = VALUES(recruiter_notes),
  rating = VALUES(rating);

-- 7. Distribute Urgency Across Employer Mandates for Doughnut Chart Variety
UPDATE employer_enquiries SET urgency = 'IMMEDIATE' WHERE id IN (1, 2);
UPDATE employer_enquiries SET urgency = '15_DAYS' WHERE id IN (3, 4);
UPDATE employer_enquiries SET urgency = '30_DAYS' WHERE id IN (5, 6);
UPDATE employer_enquiries SET urgency = 'EXPLORATORY' WHERE id = 7;

-- 8. Fix Google Search Console JobPosting Schema Dates & Salary Ranges
UPDATE jobs SET 
  salary_min = 400000.00, 
  salary_max = 650000.00, 
  is_salary_public = 1, 
  published_at = COALESCE(published_at, created_at, '2026-10-25 10:00:00'), 
  expires_at = COALESCE(expires_at, '2027-01-25 23:59:59') 
WHERE id = 101;

UPDATE jobs SET 
  salary_min = 350000.00, 
  salary_max = 550000.00, 
  is_salary_public = 1, 
  published_at = COALESCE(published_at, created_at, '2026-10-25 10:00:00'), 
  expires_at = COALESCE(expires_at, '2027-01-25 23:59:59') 
WHERE id = 102;

UPDATE jobs SET 
  salary_min = 300000.00, 
  salary_max = 480000.00, 
  is_salary_public = 1, 
  published_at = COALESCE(published_at, created_at, '2026-10-25 10:00:00'), 
  expires_at = COALESCE(expires_at, '2027-01-25 23:59:59') 
WHERE id = 103;

UPDATE jobs SET 
  published_at = COALESCE(published_at, created_at, '2026-10-25 10:00:00'), 
  expires_at = COALESCE(expires_at, '2027-01-25 23:59:59') 
WHERE id IN (104, 105);

UPDATE jobs SET 
  published_at = created_at 
WHERE published_at IS NULL;

UPDATE jobs SET 
  expires_at = DATE_ADD(published_at, INTERVAL 90 DAY) 
WHERE expires_at IS NULL;

