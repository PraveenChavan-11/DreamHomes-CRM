# TalentHub — Social Media Scheduler & Cron Configuration Guide
## Automated Publishing Worker, Concurrency Locking, and Shared Hosting Setup

---

### Worker Architecture

TalentHub's scheduled publishing engine is built to run reliably on shared or dedicated hosting environments (such as Hostinger Business Web Hosting) without requiring Redis, RabbitMQ, Docker, or external daemon supervisors.

The worker is invoked via CodeIgniter 4's CLI Spark tool:
```bash
php spark social:publish
```

```
                        Cron Worker (Every 1 - 5 mins)
                                     │
                                     ▼
                      ┌──────────────────────────────┐
                      │    SocialPublishCommand      │
                      └──────────────┬───────────────┘
                                     │
                                     ▼
                ┌──────────────────────────────────────────┐
                │ Non-Blocking File Lock Verification      │
                │ File: WRITEPATH/locks/social_scheduler.lock│
                └────────────────────┬─────────────────────┘
                                     │
                    ┌────────────────┴────────────────┐
         (Lock held)│                                 │(Lock acquired)
                    ▼                                 ▼
            [ Exit immediately ]          ┌───────────────────────┐
            (Prevents overlapping         │ SocialSchedulerService│
             executions & race conds)     └───────────┬───────────┘
                                                      │
                                                      ▼
                                       ┌─────────────────────────────┐
                                       │ 1. Release Stale Locks (>15m)│
                                       │ 2. Claim Due Jobs (Atomic)  │
                                       │ 3. Dispatch to APIs         │
                                       │ 4. Handle Exponential Retry │
                                       └─────────────────────────────┘
```

---

### Hostinger hPanel Configuration (Production)

To enable automated posting on Hostinger:

1. Log into your **Hostinger hPanel**.
2. Select your website (`talenthubconsultant.com`).
3. Under the **Advanced** section in the left sidebar, click **Cron Jobs**.
4. In the **Create Cron Job** section:
   - **Type**: Select **Custom**.
   - **Command**:
     ```bash
     cd /home/u123456789/domains/talenthubconsultant.com/public_html && php spark social:publish >> /dev/null 2>&1
     ```
     *(Note: Replace `/home/u123456789/domains/talenthubconsultant.com/public_html` with your exact document root reported in hPanel File Manager).*
   - **Interval**: Set to **Every 1 minute** (`* * * * *`) or **Every 5 minutes** (`*/5 * * * *`).
5. Click **Save**.

---

### Command-Line Execution & Flags

You can test and run the worker manually via SSH or PowerShell terminal:

#### 1. Standard Run
Processes all due jobs up to the default batch size (10):
```bash
php spark social:publish
```

#### 2. Specifying Custom Batch Limit
Process up to 25 jobs in one execution:
```bash
php spark social:publish --limit 25
```

#### 3. Forcing Immediate Execution (Bypassing Lock Check)
Useful when diagnosing a stuck job:
```bash
php spark social:publish --force
```

#### Sample Command Output:
```text
[2026-11-08 17:45:00] TalentHub Social Publishing Worker Started.
Found 2 scheduled job(s) due for publishing.
- Processing Job #14 (Post #8 -> linkedin)... SUCCESS (URN: urn:li:share:7123456789)
- Processing Job #15 (Post #8 -> instagram)... SUCCESS (IG Media ID: 1802934812345678)
All jobs processed. 2 succeeded, 0 failed. Lock released.
```

---

### Atomic Concurrency & Stale Lock Protection

To prevent multiple simultaneous cron runs from double-posting content to social networks:

1. **Local File Lock**:
   `SocialPublishCommand` uses PHP's native `flock($fp, LOCK_EX | LOCK_NB)` on `WRITEPATH . 'locks/social_scheduler.lock'`. If a previous worker is still streaming a large video upload, new cron executions exit immediately without consuming server memory.
2. **Database Job Claiming**:
   `SocialSchedulerService::claimDueJobs()` retrieves eligible IDs (`scheduled_at <= NOW()` and `status = 'pending'`), and updates them atomically to `status = 'processing'` and `locked_at = NOW()`.
3. **Stale Lock Auto-Recovery**:
   If a PHP process crashes unexpectedly mid-dispatch (e.g. server power outage or script timeout), jobs stuck in `processing` for longer than **15 minutes** are automatically reclaimed and reset to `pending` on the next run.

---

### Exponential Backoff Retry Schedule

If LinkedIn or Instagram returns a transient HTTP error (e.g., rate limit 429 or server error 503), the job is not deleted:
- **Attempt 1 Failed**: Scheduled retry in **2 minutes**.
- **Attempt 2 Failed**: Scheduled retry in **4 minutes**.
- **Attempt 3 Failed**: Scheduled retry in **8 minutes**.
- **Attempt 4 Failed**: Marked as permanently `failed`. An urgent notification is logged in `social_notifications` and visible on `/admin/social/queue`. Administrators can click **Retry Now** in the UI anytime after fixing the underlying issue.

