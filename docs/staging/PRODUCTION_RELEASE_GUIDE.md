# TalentHub Consultant — Production Release Governance & Protocol

## 1. Release Governance Overview

To protect live customer applications, candidate CVs, business inquiries, and high-visibility SEO rankings, production releases follow a strict, gated promotion process:

```text
Develop on feature branch
        │
        ▼
Pull Request & Merge to main
        │
        ▼
Automated Staging Deployment (staging.talenthubconsultant.com)
        │
        ▼
Functional Testing & End-to-End UAT Signoff
        │
        ▼
Production Release Approval Gate (Manual Signoff)
        │
        ▼
Controlled Production Release (talenthubconsultant.com)
```

---

## 2. Pre-Release Acceptance Checklist

Before initiating any release to production, the QA Lead and Principal Architect must confirm:

- [ ] All features and bug fixes have been validated on `staging.talenthubconsultant.com`.
- [ ] No regression detected in public candidate job application flows.
- [ ] No regression in employer hiring mandate forms.
- [ ] Admin authentication, 2FA/TOTP, and role permissions verified.
- [ ] Database migrations tested on staging without errors.
- [ ] Candidate document vault permissions verified.
- [ ] Dynamic XML sitemap verified (`talenthubconsultant.com/sitemap.xml`).
- [ ] SEO meta tags and Google Analytics 4 verified on production templates.

---

## 3. Promoting a Release to Production

### Method A: Via GitHub Actions Workflow Dispatch (Recommended)
1. Open GitHub repository → **Actions** → **TalentHub Production Gated Release Pipeline**.
2. Click **Run workflow**.
3. Enter release tag (e.g. `v2.4.0`).
4. Type mandatory confirmation string: `DEPLOY-TO-LIVE-PRODUCTION`.
5. Click **Run workflow**.
6. The pipeline runs security scans, verifies CodeIgniter health, and securely updates production.

### Method B: Via Semantic Version Git Tag
1. From the verified `main` commit:
   ```bash
   git tag -a v2.4.0 -m "Production Release v2.4.0 — Engagement Centre & SEO Hardening"
   git push origin v2.4.0
   ```
2. The GitHub Actions release pipeline triggers on the tagged push, requests required environment reviewer signoff, and executes the release.

### Method C: Manual Production Deployment via Hostinger SSH
1. Connect to Hostinger via SSH:
   ```bash
   ssh u139147679@talenthubconsultant.com -p 65002
   ```
2. Navigate to production root:
   ```bash
   cd /home/u139147679/domains/talenthubconsultant.com/public_html
   ```
3. Backup production database before pulling:
   ```bash
   mysqldump -u u139147679_talenthub -p u139147679_talenthub > pre_release_backup_$(date +%Y%m%d_%H%M%S).sql
   ```
4. Pull approved release tag:
   ```bash
   git fetch origin
   git checkout tags/v2.4.0
   ```
5. Apply production deployment script:
   ```bash
   bash deployment/hostinger_deploy.sh
   ```
6. Verify live health check:
   ```bash
   php spark system:check
   ```

