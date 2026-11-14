#!/usr/bin/env bash
# ==============================================================================
# TalentHub Consultant — Hostinger Staging Isolated Deployment Script
# Target: https://staging.talenthubconsultant.com
# Document Root: /home/u139147679/domains/talenthubconsultant.com/public_html/staging
# ==============================================================================

set -euo pipefail

echo "===================================================================="
echo " TALENTHUB STAGING ISOLATED DEPLOYMENT"
echo "===================================================================="

HOSTINGER_USER=$(whoami)
STAGING_DIR="/home/${HOSTINGER_USER}/domains/talenthubconsultant.com/public_html/staging"
PRODUCTION_DIR="/home/${HOSTINGER_USER}/domains/talenthubconsultant.com/public_html"

# ── 1. STRICT PRODUCTION SAFETY GUARD ──────────────────────────────────────────
echo "1. Performing strict safety assertions..."

if [[ "${STAGING_DIR}" == "${PRODUCTION_DIR}" ]]; then
    echo "FATAL ERROR: Target staging path equals production path! Aborting."
    exit 1
fi

if [[ "${STAGING_DIR}" != *"/staging" ]]; then
    echo "FATAL ERROR: Target path does not end in '/staging'! Aborting."
    exit 1
fi

echo "  -> Target Staging Directory: ${STAGING_DIR}"
echo "  -> Production Directory:       ${PRODUCTION_DIR} (UNTOUCHED)"

# ── 2. PREPARE STAGING DIRECTORY STRUCTURE ────────────────────────────────────
echo "2. Preparing isolated staging directory structure..."
mkdir -p "${STAGING_DIR}"
mkdir -p "${STAGING_DIR}/writable/cache"
mkdir -p "${STAGING_DIR}/writable/logs"
mkdir -p "${STAGING_DIR}/writable/session"
mkdir -p "${STAGING_DIR}/writable/vault/resumes"
mkdir -p "${STAGING_DIR}/writable/uploads"
mkdir -p "${STAGING_DIR}/public/uploads"

# ── 3. DEPLOY APPLICATION CORE TO STAGING ─────────────────────────────────────
echo "3. Synchronizing repository files to staging root..."
rsync -av --delete \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='writable/logs/*' \
    --exclude='writable/cache/*' \
    --exclude='writable/session/*' \
    --exclude='writable/vault/resumes/*' \
    ./ "${STAGING_DIR}/"

# ── 4. CONFIGURE ISOLATED STAGING ENVIRONMENT ────────────────────────────────
echo "4. Checking staging environment configuration..."
if [[ ! -f "${STAGING_DIR}/.env" ]]; then
    echo "  -> Provisioning .env from env.staging..."
    cp "${STAGING_DIR}/env.staging" "${STAGING_DIR}/.env"
fi

# Ensure robots.txt in staging root strictly disallows indexing
if [[ -f "${STAGING_DIR}/robots-staging.txt" ]]; then
    cp "${STAGING_DIR}/robots-staging.txt" "${STAGING_DIR}/robots.txt"
    cp "${STAGING_DIR}/robots-staging.txt" "${STAGING_DIR}/public/robots.txt"
fi

# ── 5. SECURE PERMISSIONS ────────────────────────────────────────────────────
echo "5. Applying secure permission masks..."
chmod -R 775 "${STAGING_DIR}/writable"
chmod -R 700 "${STAGING_DIR}/writable/vault"
chmod 644 "${STAGING_DIR}/.htaccess" || true
chmod 644 "${STAGING_DIR}/index.php" || true

# ── 6. EXECUTE DIAGNOSTIC HEALTH CHECK ───────────────────────────────────────
echo "6. Running CodeIgniter 4 Staging Health Telemetry..."
cd "${STAGING_DIR}"
php spark staging:status

echo "===================================================================="
echo " STAGING DEPLOYMENT COMPLETED SUCCESSFULLY"
echo " Staging Portal: https://staging.talenthubconsultant.com"
echo "===================================================================="

