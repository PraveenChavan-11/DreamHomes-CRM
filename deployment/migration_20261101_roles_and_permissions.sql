-- TalentHub Role-Based Access Control (RBAC) & HR Role Migration
-- Date: 2026-11-01

INSERT INTO `roles` (`id`, `slug`, `name`, `description`, `created_at`, `updated_at`)
VALUES (4, 'hr_manager', 'HR Manager / Talent Partner', 'Full recruitment operations: candidate pipeline, vacancies, applications, resume bank, and corporate client communication', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `updated_at` = NOW();

-- Assign HR default permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions` WHERE `code` IN (
  'jobs.publish',
  'jobs.close',
  'candidates.view',
  'candidates.manage',
  'resumes.download',
  'applications.view',
  'applications.transition',
  'applications.notes',
  'employers.manage',
  'enquiries.manage'
);

