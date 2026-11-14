# TalentHub Consultant — Git & Staging Deployment Architecture

## 1. Source Repository & Branch Strategy

- **Repository:** `https://github.com/PraveenChavan-11/TalentHub.git`
- **Primary Source Branch:** `main`
- **Staging Deployment Branch:** Tracks `main` automatically.
- **Production Deployment:** Controlled via protected releases or tags (`v*.*.*`).

---

## 2. Automated Staging Deployment Workflow

The GitHub Actions workflow `.github/workflows/staging-deploy.yml` runs automatically whenever changes are pushed to `main`:

```text
Developer pushes commit to main
        │
        ▼
GitHub Actions CI/CD Pipeline
        │
        ├─► Job 1: Security & Secrets Scan
        ├─► Job 2: PHP Syntax Lint Across Project
        ├─► Job 3: CodeIgniter 4 Backend Verification
        │
        ▼ (All Tests Pass)
Deploy to Staging Environment
        │
        ├─► Target: https://staging.talenthubconsultant.com
        └─► Path:   /home/u139147679/domains/talenthubconsultant.com/public_html/staging
```

---

## 3. Production Protection Guarantees

1. **Pushes to `main` NEVER touch production:**
   - Production deployment is completely decoupled from the `main` push trigger.
   - Pushes to `main` trigger `staging-deploy.yml` ONLY.
2. **Production Gated Pipeline:**
   - Production deployment requires triggering `.github/workflows/production-release.yml` with:
     - Explicit release version tag (e.g. `v2.4.0`)
     - Mandatory confirmation string: `DEPLOY-TO-LIVE-PRODUCTION`
     - GitHub Environment approval signoff.
3. **Target Directory Guard:**
   - Deployment scripts verify that target directory ends with `/staging`. If the path is `/public_html` without `/staging`, the deployment script aborts immediately.

---

## 4. Manual Deployment via Hostinger SSH

If automated SSH keys are not configured in GitHub Secrets, staging can be deployed directly from Hostinger SSH:

```bash
# 1. Connect to Hostinger via SSH
ssh u139147679@talenthubconsultant.com -p 65002

# 2. Navigate to staging directory
cd /home/u139147679/domains/talenthubconsultant.com/public_html/staging

# 3. Pull latest main branch
git fetch origin main
git reset --hard origin/main

# 4. Execute staging deployment & health check
bash deployment/hostinger_staging_deploy.sh
```

---

## 5. Deployment Rollback

To rollback staging to a previous commit:

```bash
cd /home/u139147679/domains/talenthubconsultant.com/public_html/staging
git reset --hard <PREVIOUS_COMMIT_HASH>
php spark staging:status
```

