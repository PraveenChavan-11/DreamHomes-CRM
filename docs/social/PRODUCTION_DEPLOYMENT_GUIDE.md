# TalentHub — Social Media Marketing Production Deployment Guide
## Step-by-Step Rollout to Hostinger Live Production

---

### Deployment Checklist Overview

| Stage | Task | Verification |
|:---:|:---|:---|
| **1** | Git Push to Main | Remote `origin/main` updated |
| **2** | Hostinger Git Pull / File Deployment | Production files up to date |
| **3** | Database Migration Execution | 17 tables created in `talenthub_db` |
| **4** | Directory & Permissions Verification | `public/uploads/social/` is 0755 |
| **5** | Cron Worker Installation in hPanel | `php spark social:publish` active |
| **6** | Developer Portal Callback Registration | Production URLs whitelisted |
| **7** | Production Smoke & Health Testing | Diagnostic check reports PASS |

---

### Step 1: Git Deployment

On your local workstation or staging server:
```bash
git add .
git commit -m "feat(social): enterprise social media marketing and management centre complete"
git push origin main
```

On your Hostinger Production server via SSH or Hostinger Git Manager:
```bash
cd /home/u123456789/domains/talenthubconsultant.com/public_html
git pull origin main
```

---

### Step 2: Database Migration Execution

The Social Marketing schema consists of 17 normalized tables. TalentHub includes two migration execution methods:

#### Method A: CodeIgniter Spark CLI (Recommended)
Run via SSH terminal on your server:
```bash
php spark migrate
```

#### Method B: Zero-CLI Lazy Auto-Migration (Built-In Fallback)
If you do not have SSH terminal access on Hostinger:
- TalentHub's `SocialMarketingController` includes a self-healing schema inspector in its constructor.
- Simply log into your live TalentHub Admin Dashboard and open **Social Media Marketing &rarr; Overview** (`/admin/social`).
- If the tables are not present, TalentHub automatically applies the migration synchronously and seeds initial permissions.

---

### Step 3: File Storage & Permissions Setup

Ensure the social media upload directory exists with proper write permissions:

```bash
mkdir -p public/uploads/social
chmod 755 public/uploads/social
chmod 755 writable/locks
```

Add an `.htaccess` protection file inside `public/uploads/social/` to ensure uploaded PHP files can never be executed directly:
```apache
# Prevent script execution inside social uploads
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
Options -ExecCGI
```

---

### Step 4: Configuring the Cron Worker

In **Hostinger hPanel &rarr; Websites &rarr; talenthubconsultant.com &rarr; Cron Jobs**:
- **Type**: Custom
- **Command**:
  ```bash
  cd /home/u123456789/domains/talenthubconsultant.com/public_html && php spark social:publish >> /dev/null 2>&1
  ```
- **Interval**: Every 5 minutes (`*/5 * * * *`) or Every 1 minute (`* * * * *`).

---

### Step 5: Post-Deployment Verification

1. **Verify Health Monitor**:
   - Open `https://talenthubconsultant.com/admin/social/settings`.
   - Confirm **System Health & Diagnostic Monitor** shows green checkmarks for:
     - `AES-256 Key Status: PASS`
     - `HTTPS Enforcement: PASS`
     - `Directory Permissions: PASS`
2. **Execute Test Post**:
   - Open `/admin/social/posts/create`.
   - Compose a quick draft post and click **Save Draft**.
   - Confirm the post appears in `/admin/social/drafts`.
3. **Verify Audit Trail**:
   - Open `/admin/social/logs`.
   - Confirm your administrator action is logged with action `post.create` and zero token exposure.

---

### Rollback Plan

If unexpected server incompatibility occurs:
1. Revert to the prior Git commit:
   ```bash
   git checkout HEAD~1
   ```
2. The existing TalentHub features (Recruitment, Jobs, IT CRM, WorkSphere, Contact Forms) are completely decoupled from the social module and will continue operating without disruption.
3. Drop social tables if required:
   ```bash
   php spark migrate:rollback
   ```

