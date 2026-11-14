# TalentHub — Cron Automation & Secure Document Storage Guide

## 1. Private Resume Storage Architecture
TalentHub strictly prohibits direct public URL downloads for candidate resumes. All uploaded documents are stored in a private vault outside the web root.

### Storage Layout
```text
/home/u123456789/private_vault/resumes/
├── 550e8400-e29b-41d4-a716-446655440000.pdf
├── 7b1c42f0-128a-49e2-8d76-1b3299c82110.docx
└── ...
```

### Security Controls Enforced:
1. **Physical Location:** Placed in `/home/u123456789/private_vault/resumes/`, completely unreachable by Apache HTTP requests.
2. **File Permissions:** Files are written with `chmod 0600` (readable/writable only by the PHP process owner).
3. **Randomized File Names:** Files are stored using cryptographic UUID v4 filenames. Original filenames (`Rahul_Resume_2026.pdf`) are stored encrypted in MySQL table `candidate_documents`.
4. **MIME Verification:** Validates magic byte signatures (rejecting executable scripts disguised as `.pdf` or `.docx`).
5. **Streaming Downloads:** Resumes are downloaded exclusively through authenticated streaming endpoint:
   `GET /api/v1/admin/documents/{id}/stream`
   Requires valid Super Admin or Recruiter session cookie and permission `resumes.download`.

---

## 2. Background Processing & Cron Runner

Hostinger shared hosting does not permit running persistent Node.js or Celery daemon workers. TalentHub implements a lightweight, database-locked cron processor in `php-api/public/cron.php`.

### Cron Operations:
1. **Email Outbox Dispatch:** Reads pending messages in `email_outbox`, sends via Hostinger SMTP, marks `SENT` or records failure with retry counters. Max 20 emails per batch.
2. **Auto-Closure of Expired Jobs:** Updates `jobs` table where `expires_at <= NOW()` from `PUBLISHED` to `CLOSED`.
3. **CRM Follow-Up Reminders:** Flags overdue employer enquiry follow-ups.
4. **Audit & Log Retention:** Purges logs older than 90 days.

### Setting Up the Cron on Hostinger
In Hostinger **hPanel** -> **Advanced** -> **Cron Jobs**:
- **Cron Type:** Custom Command
- **Minute:** `*/5`
- **Hour, Day, Month, Weekday:** `*`
- **Command:**
  ```bash
  /usr/bin/php /home/u123456789/public_html/api/cron.php >> /home/u123456789/logs/cron.log 2>&1
  ```

### HTTP Trigger Fallback (Token-Secured)
If SSH/CLI cron is temporarily unavailable, the cron can be triggered via HTTP with the shared secret:
```bash
curl -s -X POST "https://talenthubconsultant.com/api/cron.php?key=YOUR_CRON_SECRET"
```
Requests without a matching `key` query parameter are immediately rejected with `403 Forbidden`.
