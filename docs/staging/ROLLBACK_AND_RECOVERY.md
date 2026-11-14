# TalentHub Consultant — Staging Rollback, Recovery & Disaster Playbook

## 1. Overview

This operational playbook defines recovery procedures for the TalentHub Staging Environment in the event of misconfiguration, failed deployments, database corruption, or testing anomalies.

Because staging is completely decoupled from production, any rollback or recovery action executed on staging has **zero risk** of affecting the live production application.

---

## 2. Emergency Scenarios & Recovery Runbooks

### Runbook 1: Failed Staging Deployment Rollback
**Scenario:** A commit pushed to `main` causes an uncaught fatal error or regression on staging.

```bash
# 1. SSH into Hostinger
ssh u139147679@talenthubconsultant.com -p 65002

# 2. Navigate to staging root
cd /home/u139147679/domains/talenthubconsultant.com/public_html/staging

# 3. View commit history
git log -n 5 --oneline

# 4. Rollback to known stable commit
git reset --hard <STABLE_COMMIT_HASH>

# 5. Clear application caches
php spark cache:clear

# 6. Verify health
php spark staging:status
```

---

### Runbook 2: Corrupted Staging Database Reset
**Scenario:** Extreme testing or bad mock data corrupts relationships in the staging database.

```bash
# From staging directory:
php spark staging:reset --force
```

**What this does:**
1. Verifies that the active database is `u139147679_staging` (aborts if production).
2. Truncates all transactional tables (`applications`, `enquiries`, `candidates`, `subscribers`, `email_outbox`, `audit_logs`).
3. Re-seeds fresh, clean test records.
4. Leaves `admin_users` and configuration tables intact.

---

### Runbook 3: Restoring Staging from Backup Dump
**Scenario:** Restoring staging schema to a specific point-in-time snapshot.

```bash
# Import baseline schema export
mysql -u u139147679_staging -p u139147679_staging < deployment/clean_database_import.sql

# Run staging provisioner to re-seed roles and test records
php spark staging:provision-db
php spark staging:seed-test-data
php spark staging:provision-admin
```

---

### Runbook 4: Emergency Service Worker Purge
**Scenario:** A tester's browser caches stale assets or a rogue worker intercepts requests.

1. In tester's browser:
   - Open Developer Tools (`F12`) → **Application** → **Service Workers**.
   - Click **Unregister** on `sw-staging.js`.
   - Under **Storage** → Click **Clear site data**.
2. Service worker updates automatically purge caches prefixed with `talenthub-staging-` upon version bump.

