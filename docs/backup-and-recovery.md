# TalentHub — Backup, Disaster Recovery & Retention Guide

## 1. Backup Strategy Overview
TalentHub disaster recovery covers two critical state stores:
1. **Hostinger MySQL Database** (Jobs, candidates, CRM notes, enquiries, audit logs).
2. **Private Resume Vault** (`/home/u123456789/private_vault/resumes/`).

While Hostinger provides automated weekly snapshot backups on Premium plans, enterprise data governance mandates independent, exportable backups.

---

## 2. Database Backup Procedures

### Automated Daily mysqldump via Hostinger Cron
In hPanel -> **Cron Jobs**, configure a daily backup job at 02:00 AM UTC:
```bash
/usr/bin/mysqldump -u u123456789_th_user -p'DB_PASSWORD' u123456789_talenthub | gzip > /home/u123456789/backups/db/talenthub_db_$(date +\%Y\%m\%d).sql.gz
```

### Manual Backup (Before Any Upgrade or Migration)
1. In hPanel -> **Databases** -> **phpMyAdmin**.
2. Select `u123456789_talenthub`.
3. Click **Export** -> **Quick** -> Format: **SQL** -> Click **Export**.
4. Store the downloaded `.sql` file in an encrypted corporate storage location.

---

## 3. Resume Vault Backup Procedures

To back up uploaded candidate resumes:
```bash
tar -czf /home/u123456789/backups/vault/resumes_$(date +\%Y\%m\%d).tar.gz -C /home/u123456789/private_vault/resumes .
```

---

## 4. Disaster Recovery & Restoration Procedures

### Restoring MySQL from Backup
```bash
gunzip < /home/u123456789/backups/db/talenthub_db_YYYYMMDD.sql.gz | mysql -u u123456789_th_user -p'DB_PASSWORD' u123456789_talenthub
```

### Restoring Resume Vault
```bash
tar -xzf /home/u123456789/backups/vault/resumes_YYYYMMDD.tar.gz -C /home/u123456789/private_vault/resumes/
chmod 0700 /home/u123456789/private_vault/resumes/
chmod 0600 /home/u123456789/private_vault/resumes/*
```

---

## 5. Retention & Purge Policies
- **Inactive Candidates:** Candidate records without activity for 24 months can be purged via GDPR/privacy compliance policies.
- **Audit Logs:** Retained for 90 days in `audit_logs`, automatically rotated by `CronService`.
- **Email Outbox:** Sent messages retained for 30 days for delivery verification before archiving.
