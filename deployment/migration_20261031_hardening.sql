-- ==============================================================================
-- TalentHub Consultant — Production Hardening & Security Migration
-- Date: October 31, 2026
-- ==============================================================================

-- USE talenthub_db; -- Select active database in Hostinger phpMyAdmin (e.g. u139147679_talenthub)

-- 1. Candidates Consent Hardening
ALTER TABLE candidates 
    MODIFY COLUMN consent_received TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS consent_policy_version VARCHAR(50) DEFAULT 'v1.0-DPDP-2026' AFTER consent_timestamp;

-- 2. Employer Enquiries Idempotency & Consent
ALTER TABLE employer_enquiries 
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(100) NULL AFTER assigned_to,
    ADD COLUMN IF NOT EXISTS consent_received TINYINT(1) DEFAULT 0 AFTER idempotency_key,
    ADD COLUMN IF NOT EXISTS consent_timestamp DATETIME NULL AFTER consent_received,
    ADD UNIQUE INDEX IF NOT EXISTS uq_ee_idempotency (idempotency_key);

-- 3. Contact Enquiries Idempotency & Consent
ALTER TABLE contact_enquiries 
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(100) NULL AFTER ip_address,
    ADD COLUMN IF NOT EXISTS consent_received TINYINT(1) DEFAULT 0 AFTER idempotency_key,
    ADD COLUMN IF NOT EXISTS consent_timestamp DATETIME NULL AFTER consent_received,
    ADD UNIQUE INDEX IF NOT EXISTS uq_ce_idempotency (idempotency_key);

-- 4. Persistent Atomic Rate Limiting Table
CREATE TABLE IF NOT EXISTS rate_limit_buckets (
    key_hash VARCHAR(64) NOT NULL PRIMARY KEY,
    rate_key VARCHAR(190) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    reset_at INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CAPTCHA Token Replay Prevention Table
CREATE TABLE IF NOT EXISTS captcha_tokens (
    token_hash VARCHAR(64) NOT NULL PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_captcha_used (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Admin MFA / Two-Factor Authentication Support
ALTER TABLE admin_users 
    ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(64) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) DEFAULT 0 AFTER totp_secret,
    ADD COLUMN IF NOT EXISTS totp_backup_codes TEXT NULL AFTER totp_enabled;

-- 7. Email Outbox PROCESSING state for atomic queue processing
ALTER TABLE email_outbox
    MODIFY COLUMN status ENUM('PENDING','PROCESSING','SENT','FAILED') DEFAULT 'PENDING';
