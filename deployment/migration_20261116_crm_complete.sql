-- =============================================================================
-- DHIS Sales CRM — Complete Schema & Reference Data Export
-- Includes CreateCrmSchema, AddRequirementsAndVariants, AddManualPricingTracking, AddRateLimitBuckets
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `crm_activities`;
CREATE TABLE `crm_activities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('call','meeting','whatsapp','email','note','visit','system') NOT NULL,
  `summary` varchar(500) NOT NULL,
  `details` text DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `occurred_at` datetime NOT NULL,
  `followup_id` bigint(20) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_act_cust` (`customer_id`,`occurred_at`),
  KEY `idx_act_enq` (`enquiry_id`,`occurred_at`),
  KEY `idx_act_user` (`user_id`,`occurred_at`),
  CONSTRAINT `fk_act_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_additional_charges`;
CREATE TABLE `crm_additional_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `calc_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed',
  `value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chg_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_assignment_rules`;
CREATE TABLE `crm_assignment_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL DEFAULT 100,
  `mode` enum('round_robin','workload','team','territory') NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `territory_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ar_prio` (`is_active`,`priority`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_assignment_state`;
CREATE TABLE `crm_assignment_state` (
  `scope_key` varchar(60) NOT NULL,
  `last_user_id` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`scope_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_audit_events`;
CREATE TABLE `crm_audit_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(60) NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` varchar(40) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `prev_state` text DEFAULT NULL,
  `new_state` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `correlation_id` char(32) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'web',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ae_entity` (`entity_type`,`entity_id`),
  KEY `idx_ae_user` (`user_id`,`created_at`),
  KEY `idx_ae_type` (`event_type`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_customer_contacts`;
CREATE TABLE `crm_customer_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `role_title` varchar(80) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cc_cust` (`customer_id`),
  CONSTRAINT `fk_cc_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_customers`;
CREATE TABLE `crm_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_no` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `customer_type` enum('individual','company','architect','builder','other') NOT NULL DEFAULT 'individual',
  `company_name` varchar(150) DEFAULT NULL,
  `primary_contact` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `mobile_norm` varchar(15) NOT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `email_norm` varchar(190) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `site_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pin_code` varchar(10) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','archived') NOT NULL DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cust_no` (`customer_no`),
  KEY `idx_cust_mobile` (`mobile_norm`),
  KEY `idx_cust_email` (`email_norm`),
  KEY `idx_cust_owner` (`status`,`owner_user_id`),
  KEY `idx_cust_name` (`name`),
  KEY `fk_cust_src` (`source_id`),
  KEY `fk_cust_owner` (`owner_user_id`),
  CONSTRAINT `fk_cust_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `admin_users` (`id`),
  CONSTRAINT `fk_cust_src` FOREIGN KEY (`source_id`) REFERENCES `crm_lead_sources` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_demo_registry`;
CREATE TABLE `crm_demo_registry` (
  `table_name` varchar(60) NOT NULL,
  `row_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`table_name`,`row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_demo_reset_log`;
CREATE TABLE `crm_demo_reset_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `initiated_by` int(11) DEFAULT NULL,
  `snapshot_path` varchar(255) DEFAULT NULL,
  `rows_removed` int(11) NOT NULL DEFAULT 0,
  `rows_created` int(11) NOT NULL DEFAULT 0,
  `integrity_ok` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_discount_approvals`;
CREATE TABLE `crm_discount_approvals` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `negotiation_id` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 1,
  `required_role` varchar(30) NOT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `decision` enum('pending','approved','rejected','returned','escalated') NOT NULL DEFAULT 'pending',
  `remarks` varchar(500) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_da_neg` (`negotiation_id`),
  KEY `idx_da_pending` (`decision`,`required_role`),
  CONSTRAINT `fk_da_neg` FOREIGN KEY (`negotiation_id`) REFERENCES `crm_negotiations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_documents`;
CREATE TABLE `crm_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size_bytes` int(11) NOT NULL,
  `sha256` char(64) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_entity` (`entity_type`,`entity_id`),
  KEY `idx_doc_cust` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_email_log`;
CREATE TABLE `crm_email_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(190) NOT NULL,
  `from_email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` mediumtext DEFAULT NULL,
  `template_code` varchar(60) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `triggered_by_user` int(11) DEFAULT NULL,
  `triggered_by_automation` varchar(60) DEFAULT NULL,
  `status` enum('queued','sent','delivered','failed','bounced') NOT NULL DEFAULT 'queued',
  `provider` varchar(40) DEFAULT NULL,
  `provider_response` text DEFAULT NULL,
  `failure_reason` varchar(500) DEFAULT NULL,
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `next_retry_at` datetime DEFAULT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_el_status` (`status`,`next_retry_at`),
  KEY `idx_el_cust` (`customer_id`,`created_at`),
  KEY `idx_el_enq` (`enquiry_id`),
  KEY `idx_el_qt` (`quotation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_email_templates`;
CREATE TABLE `crm_email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `variables` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_et_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_enquiries`;
CREATE TABLE `crm_enquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enquiry_no` varchar(20) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `project_name` varchar(200) DEFAULT NULL,
  `product_category_id` int(11) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `site_location` varchar(255) DEFAULT NULL,
  `est_budget` decimal(14,2) DEFAULT NULL,
  `expected_purchase_date` date DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `source_channel` varchar(30) NOT NULL DEFAULT 'manual',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `lead_score` int(11) NOT NULL DEFAULT 0,
  `status` enum('new','assigned','contacted','qualified','site_visit','quotation_preparation','quotation_sent','negotiation','won','lost','on_hold') NOT NULL DEFAULT 'new',
  `current_owner_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `territory_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `outcome` enum('won','lost','on_hold') DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `final_value` decimal(14,2) DEFAULT NULL,
  `lost_reason_id` int(11) DEFAULT NULL,
  `lost_competitor` varchar(150) DEFAULT NULL,
  `close_remarks` text DEFAULT NULL,
  `hold_review_date` date DEFAULT NULL,
  `previous_status` varchar(30) DEFAULT NULL,
  `dedupe_hash` char(40) DEFAULT NULL,
  `import_job_id` int(11) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enq_no` (`enquiry_no`),
  KEY `idx_enq_cust` (`customer_id`),
  KEY `idx_enq_owner` (`current_owner_id`,`status`),
  KEY `idx_enq_status` (`status`,`created_at`),
  KEY `idx_enq_team` (`team_id`),
  KEY `idx_enq_dedupe` (`dedupe_hash`),
  KEY `idx_enq_created` (`created_at`),
  KEY `fk_enq_cat` (`product_category_id`),
  KEY `fk_enq_lost` (`lost_reason_id`),
  CONSTRAINT `fk_enq_cat` FOREIGN KEY (`product_category_id`) REFERENCES `crm_product_categories` (`id`),
  CONSTRAINT `fk_enq_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`),
  CONSTRAINT `fk_enq_lost` FOREIGN KEY (`lost_reason_id`) REFERENCES `crm_lost_reasons` (`id`),
  CONSTRAINT `fk_enq_owner` FOREIGN KEY (`current_owner_id`) REFERENCES `admin_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_enquiry_assignments`;
CREATE TABLE `crm_enquiry_assignments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `to_user_id` int(11) DEFAULT NULL,
  `method` enum('manual','auto_round_robin','auto_workload','auto_team','auto_territory','fallback','reassign') NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ea_enq` (`enquiry_id`,`created_at`),
  KEY `idx_ea_to` (`to_user_id`),
  CONSTRAINT `fk_ea_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_enquiry_status_history`;
CREATE TABLE `crm_enquiry_status_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_esh_enq` (`enquiry_id`,`created_at`),
  CONSTRAINT `fk_esh_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=533 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_followups`;
CREATE TABLE `crm_followups` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `activity_type` enum('call','meeting','whatsapp','email','visit','other') NOT NULL DEFAULT 'call',
  `due_at` datetime NOT NULL,
  `status` enum('pending','completed','rescheduled','missed','cancelled') NOT NULL DEFAULT 'pending',
  `outcome` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `next_action` varchar(255) DEFAULT NULL,
  `next_followup_id` bigint(20) DEFAULT NULL,
  `rescheduled_from_id` bigint(20) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `escalated_at` datetime DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fu_user_due` (`assigned_to`,`status`,`due_at`),
  KEY `idx_fu_enq` (`enquiry_id`),
  KEY `idx_fu_cust` (`customer_id`),
  KEY `idx_fu_due` (`status`,`due_at`),
  CONSTRAINT `fk_fu_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`),
  CONSTRAINT `fk_fu_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`),
  CONSTRAINT `fk_fu_user` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_import_jobs`;
CREATE TABLE `crm_import_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_type` varchar(30) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_sha256` char(64) NOT NULL,
  `stored_path` varchar(255) DEFAULT NULL,
  `status` enum('uploaded','previewed','approved','completed','failed') NOT NULL DEFAULT 'uploaded',
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `valid_rows` int(11) NOT NULL DEFAULT 0,
  `invalid_rows` int(11) NOT NULL DEFAULT 0,
  `duplicate_rows` int(11) NOT NULL DEFAULT 0,
  `imported_rows` int(11) NOT NULL DEFAULT 0,
  `column_map` text DEFAULT NULL,
  `error_report_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_imp_file` (`import_type`,`file_sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_import_rows`;
CREATE TABLE `crm_import_rows` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `row_no` int(11) NOT NULL,
  `raw_data` text NOT NULL,
  `status` enum('valid','invalid','duplicate','imported') NOT NULL,
  `errors` varchar(500) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ir_job` (`job_id`,`status`),
  CONSTRAINT `fk_ir_job` FOREIGN KEY (`job_id`) REFERENCES `crm_import_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_lead_sources`;
CREATE TABLE `crm_lead_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(80) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_src_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_login_history`;
CREATE TABLE `crm_login_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `event` enum('login','logout','failed','timeout','locked','password_reset','password_change') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lh_user` (`user_id`,`created_at`),
  KEY `idx_lh_event` (`event`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_lost_reasons`;
CREATE TABLE `crm_lost_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lost_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_negotiations`;
CREATE TABLE `crm_negotiations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `negotiation_no` varchar(20) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `customer_expected_amount` decimal(14,2) DEFAULT NULL,
  `requested_discount_amount` decimal(14,2) NOT NULL,
  `requested_discount_pct` decimal(6,3) NOT NULL,
  `reason` text DEFAULT NULL,
  `proposed_price` decimal(14,2) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','returned','escalated','applied','withdrawn') NOT NULL DEFAULT 'pending',
  `required_level` enum('self','manager','admin') NOT NULL DEFAULT 'manager',
  `final_discount_amount` decimal(14,2) DEFAULT NULL,
  `resulting_version_id` int(11) DEFAULT NULL,
  `resubmission_of_id` int(11) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_neg_no` (`negotiation_no`),
  KEY `idx_neg_qt` (`quotation_id`),
  KEY `idx_neg_status` (`status`),
  KEY `fk_neg_ver` (`version_id`),
  CONSTRAINT `fk_neg_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`),
  CONSTRAINT `fk_neg_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_notification_prefs`;
CREATE TABLE `crm_notification_prefs` (
  `user_id` int(11) NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `in_app` tinyint(1) NOT NULL DEFAULT 1,
  `email` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_notifications`;
CREATE TABLE `crm_notifications` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` varchar(500) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `dedupe_key` varchar(150) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notif_dedupe` (`dedupe_key`),
  KEY `idx_notif_user` (`user_id`,`read_at`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_number_sequences`;
CREATE TABLE `crm_number_sequences` (
  `seq_key` varchar(30) NOT NULL,
  `prefix` varchar(10) NOT NULL,
  `next_value` bigint(20) NOT NULL DEFAULT 1,
  `pad_length` tinyint(4) NOT NULL DEFAULT 4,
  PRIMARY KEY (`seq_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_product_categories`;
CREATE TABLE `crm_product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pcat_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_product_price_history`;
CREATE TABLE `crm_product_price_history` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `old_base` decimal(14,2) DEFAULT NULL,
  `new_base` decimal(14,2) NOT NULL,
  `old_standard` decimal(14,2) DEFAULT NULL,
  `new_standard` decimal(14,2) NOT NULL,
  `old_min` decimal(14,2) DEFAULT NULL,
  `new_min` decimal(14,2) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pph_prod` (`product_id`,`changed_at`),
  CONSTRAINT `fk_pph_prod` FOREIGN KEY (`product_id`) REFERENCES `crm_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_products`;
CREATE TABLE `crm_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(40) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(80) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'nos',
  `pricing_model` enum('unit','sqft','rft','qty','package') NOT NULL DEFAULT 'unit',
  `base_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `standard_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `min_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_sku` (`sku`),
  KEY `idx_prod_cat` (`category_id`,`is_active`),
  KEY `fk_prod_tax` (`tax_rate_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `crm_product_categories` (`id`),
  CONSTRAINT `fk_prod_tax` FOREIGN KEY (`tax_rate_id`) REFERENCES `crm_tax_rates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_quotation_changes`;
CREATE TABLE `crm_quotation_changes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `from_version_no` int(11) NOT NULL,
  `to_version_no` int(11) NOT NULL,
  `change_type` enum('added','removed','modified','totals','terms') NOT NULL,
  `item_ref` varchar(255) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_qch_qt` (`quotation_id`,`to_version_no`),
  CONSTRAINT `fk_qch_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_quotation_charges`;
CREATE TABLE `crm_quotation_charges` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `version_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `tax_rate` decimal(6,3) NOT NULL DEFAULT 0.000,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_qc_ver` (`version_id`),
  CONSTRAINT `fk_qc_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_quotation_items`;
CREATE TABLE `crm_quotation_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `version_id` int(11) NOT NULL,
  `line_no` int(11) NOT NULL DEFAULT 1,
  `product_id` int(11) DEFAULT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `variant` varchar(150) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `min_price` decimal(14,2) DEFAULT NULL,
  `discount_pct` decimal(6,3) NOT NULL DEFAULT 0.000,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_subtotal` decimal(14,2) NOT NULL,
  `taxable_amount` decimal(14,2) NOT NULL,
  `tax_rate` decimal(6,3) NOT NULL,
  `tax_amount` decimal(14,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `size_unit` varchar(5) DEFAULT NULL,
  `nos` decimal(10,2) DEFAULT NULL,
  `price_source` varchar(10) NOT NULL DEFAULT 'catalogue',
  `override_total` decimal(14,2) DEFAULT NULL,
  `std_price` decimal(14,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_ver` (`version_id`),
  CONSTRAINT `fk_qi_ver` FOREIGN KEY (`version_id`) REFERENCES `crm_quotation_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_quotation_versions`;
CREATE TABLE `crm_quotation_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `taxable_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `charges_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(6,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `terms` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `delivery_estimate` varchar(150) DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `customer_remarks` text DEFAULT NULL,
  `internal_remarks` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `pdf_sha256` char(64) DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revision_reason` varchar(500) DEFAULT NULL,
  `source_followup_id` bigint(20) DEFAULT NULL,
  `target_total` decimal(14,2) DEFAULT NULL,
  `adjustment_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qv_ver` (`quotation_id`,`version_no`),
  CONSTRAINT `fk_qv_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_quotations`;
CREATE TABLE `crm_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_no` varchar(20) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `current_version_id` int(11) DEFAULT NULL,
  `status` enum('draft','sent','negotiation','revised','accepted','rejected','expired','cancelled') NOT NULL DEFAULT 'draft',
  `customer_response` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `responded_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qt_no` (`quotation_no`),
  KEY `idx_qt_enq` (`enquiry_id`),
  KEY `idx_qt_cust` (`customer_id`),
  KEY `idx_qt_status` (`status`,`created_at`),
  CONSTRAINT `fk_qt_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`),
  CONSTRAINT `fk_qt_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_requirement_changes`;
CREATE TABLE `crm_requirement_changes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `followup_id` bigint(20) DEFAULT NULL,
  `action` enum('added','updated','dropped','restored') NOT NULL,
  `before_json` text DEFAULT NULL,
  `after_json` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rc_enq` (`enquiry_id`,`created_at`),
  KEY `idx_rc_req` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_requirements`;
CREATE TABLE `crm_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `variant` varchar(150) DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `size_unit` varchar(5) DEFAULT NULL,
  `nos` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT 1.000,
  `unit` varchar(20) NOT NULL DEFAULT 'nos',
  `expected_price` decimal(14,2) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `status` enum('active','dropped') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req_enq` (`enquiry_id`,`status`),
  CONSTRAINT `fk_req_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_sales_order_items`;
CREATE TABLE `crm_sales_order_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `discount_amount` decimal(14,2) NOT NULL,
  `tax_amount` decimal(14,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_soi_order` (`order_id`),
  CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `crm_sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_sales_orders`;
CREATE TABLE `crm_sales_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(20) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `salesperson_id` int(11) DEFAULT NULL,
  `status` enum('draft','pending_confirmation','confirmed','on_hold','cancelled','closed') NOT NULL DEFAULT 'pending_confirmation',
  `confirmed_at` datetime DEFAULT NULL,
  `advance_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `advance_reference` varchar(100) DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL,
  `discount_total` decimal(14,2) NOT NULL,
  `tax_total` decimal(14,2) NOT NULL,
  `charges_total` decimal(14,2) NOT NULL,
  `grand_total` decimal(14,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_so_no` (`order_no`),
  UNIQUE KEY `uq_so_quotation` (`quotation_id`),
  KEY `idx_so_cust` (`customer_id`),
  KEY `idx_so_status` (`status`,`created_at`),
  KEY `fk_so_enq` (`enquiry_id`),
  CONSTRAINT `fk_so_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`),
  CONSTRAINT `fk_so_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`),
  CONSTRAINT `fk_so_qt` FOREIGN KEY (`quotation_id`) REFERENCES `crm_quotations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_scoring_rules`;
CREATE TABLE `crm_scoring_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(40) NOT NULL,
  `operator` enum('eq','gte','lte','contains') NOT NULL,
  `match_value` varchar(100) NOT NULL,
  `points` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_settings`;
CREATE TABLE `crm_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `is_default_assumption` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_site_measurements`;
CREATE TABLE `crm_site_measurements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `data` text NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_meas_ver` (`visit_id`,`version_no`),
  CONSTRAINT `fk_meas_visit` FOREIGN KEY (`visit_id`) REFERENCES `crm_site_visits` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_site_visits`;
CREATE TABLE `crm_site_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_no` varchar(20) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled') NOT NULL DEFAULT 'scheduled',
  `completed_at` datetime DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `product_requirements` text DEFAULT NULL,
  `follow_up_action` varchar(255) DEFAULT NULL,
  `reminder_sent_at` datetime DEFAULT NULL,
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visit_no` (`visit_no`),
  KEY `idx_sv_enq` (`enquiry_id`),
  KEY `idx_sv_user` (`assigned_to`,`status`,`scheduled_at`),
  KEY `fk_sv_cust` (`customer_id`),
  CONSTRAINT `fk_sv_cust` FOREIGN KEY (`customer_id`) REFERENCES `crm_customers` (`id`),
  CONSTRAINT `fk_sv_enq` FOREIGN KEY (`enquiry_id`) REFERENCES `crm_enquiries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_status_transitions`;
CREATE TABLE `crm_status_transitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity` varchar(30) NOT NULL,
  `from_status` varchar(30) NOT NULL,
  `to_status` varchar(30) NOT NULL,
  `required_permission` varchar(100) DEFAULT NULL,
  `requires_reason` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trans` (`entity`,`from_status`,`to_status`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_tax_rates`;
CREATE TABLE `crm_tax_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `rate` decimal(6,3) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tax_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_team_members`;
CREATE TABLE `crm_team_members` (
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`team_id`,`user_id`),
  KEY `idx_tm_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_teams`;
CREATE TABLE `crm_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_name` (`name`),
  KEY `idx_team_mgr` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_territories`;
CREATE TABLE `crm_territories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pin_prefix` varchar(6) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_terr_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `crm_workflow_events`;
CREATE TABLE `crm_workflow_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(60) NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_ref` varchar(40) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `prev_state` text DEFAULT NULL,
  `new_state` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `correlation_id` char(32) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'web',
  `is_demo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_we_cust` (`customer_id`,`created_at`),
  KEY `idx_we_enq` (`enquiry_id`,`created_at`),
  KEY `idx_we_entity` (`entity_type`,`entity_id`),
  KEY `idx_we_user` (`user_id`,`created_at`),
  KEY `idx_we_type` (`event_type`),
  KEY `idx_we_corr` (`correlation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `rate_limit_buckets`;
CREATE TABLE `rate_limit_buckets` (
  `key_hash` varchar(64) NOT NULL,
  `rate_key` varchar(190) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 1,
  `reset_at` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`key_hash`),
  KEY `idx_rate_reset` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
