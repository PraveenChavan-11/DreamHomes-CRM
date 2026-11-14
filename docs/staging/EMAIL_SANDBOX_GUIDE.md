# TalentHub Consultant — Staging Email Sandbox & Communication Guide

## 1. Overview & Policy

Staging must **NEVER** dispatch real business emails to candidates, corporate employers, newsletter subscribers, or external recipients.

To enforce this guarantee, the application incorporates a central **Email Sandbox Interceptor** within both:
1. `App\Services\OutboxService` (Transactional mailer & scheduled queues)
2. `App\Services\HostingerMailService` (Communication Center & SendApi)

---

## 2. Sandboxing Architecture

```mermaid
flowchart LR
    A[Public/Admin Action] --> B{OutboxService / HostingerMailService}
    B --> C{EnvironmentSafetyService::isEmailSandboxed?}
    C -->|YES - Staging| D[Prefix Subject: [STAGING TEST EMAIL]]
    D --> E[Record in email_outbox / mail_message_index]
    E --> F[Log intercept in writable/logs]
    F --> G[Mark status as SENT / SANDBOXED]
    G --> H[Simulate Delivery - Block External SMTP]
    C -->|NO - Production| I[Execute Real SMTP / Hostinger SendApi]
```

---

## 3. Sandboxing Guarantees

1. **Subject Identification:** Every staging email subject is automatically prefixed with:
   `[STAGING TEST EMAIL] `
2. **Blocked External SMTP:** `OutboxService::processQueue()` detects `isEmailSandboxed()` and bypasses `$emailService->send()`. No connection to `smtp.hostinger.com` is established.
3. **Blocked SendApi:** `HostingerMailService::sendEmail()` intercepts outgoing payload before calling Hostinger REST endpoint `/mailboxes/{id}/send`.
4. **Fidelity Preservation:** All rendered HTML bodies, replacement tokens, and metadata are saved in:
   - `email_outbox` table
   - `mail_message_index` table (Sent folder)
   - `email_delivery_logs` table
5. **Admin Inspection:** Admin can open **Communication Center** (`/admin/mail`) or **Email Outbox** (`/admin/mail/outbox`) to inspect rendered HTML templates, headers, and tokens with full fidelity.

---

## 4. Configuration Settings

In `staging/.env`:

```ini
EMAIL_SANDBOX_ENABLED = true
STAGING_SANDBOX_INBOX = 'staging-logs@talenthubconsultant.com'
HOSTINGER_MAIL_ENABLED = false
```

- `EMAIL_SANDBOX_ENABLED`: When `true`, enables interceptor even if environment is misconfigured.
- `STAGING_SANDBOX_INBOX`: Optional test inbox for captured test logs.
- `HOSTINGER_MAIL_ENABLED`: Disabled by default in staging to prevent any unintentional mailbox sync.

---

## 5. Testing Email Workflows in Staging

Administrators and QA engineers can safely test all communication workflows:

| Workflow | Trigger | Staging Result |
|---|---|---|
| **Job Application Confirmation** | Candidate applies on `/jobs/{slug}` | Enqueued with `[STAGING TEST EMAIL]`, marked SENT (Sandboxed) |
| **Contact Enquiry Auto-Reply** | User submits `/contact` | Enqueued with `[STAGING TEST EMAIL]`, marked SENT (Sandboxed) |
| **Employer Hiring Auto-Reply** | Employer submits `/employers` | Enqueued with `[STAGING TEST EMAIL]`, marked SENT (Sandboxed) |
| **Newsletter Double Opt-In** | User subscribes on homepage footer | Enqueued with `[STAGING TEST EMAIL]`, token generated |
| **Admin Password Reset** | Admin clicks Forgot Password | Enqueued with `[STAGING TEST EMAIL]`, reset link inspectable in DB |
| **Email Campaign Broadcast** | Admin broadcasts newsletter | All subscriber logs recorded as `SANDBOXED`, zero external emails sent |

