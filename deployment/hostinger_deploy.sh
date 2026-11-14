#!/usr/bin/env bash
# ==============================================================================
# TalentHub Consultant — Hostinger One-Click Production Deployment Script
# Target: talenthubconsultant.com on Hostinger Shared Hosting
# ==============================================================================

set -e

echo "=== TALENTHUB ENTERPRISE PRODUCTION DEPLOYMENT ==="

HOSTINGER_USER=$(whoami)
ROOT_DIR="/home/${HOSTINGER_USER}"
CORE_DIR="${ROOT_DIR}/talenthub_core"
WEB_DIR="${ROOT_DIR}/public_html"

echo "Hostinger User: ${HOSTINGER_USER}"
echo "Core Path:      ${CORE_DIR}"
echo "Web Path:       ${WEB_DIR}"

# 1. Create Core Directory if not exists
mkdir -p "${CORE_DIR}"
mkdir -p "${CORE_DIR}/writable/cache"
mkdir -p "${CORE_DIR}/writable/logs"
mkdir -p "${CORE_DIR}/writable/session"
mkdir -p "${CORE_DIR}/writable/vault/resumes"

# 2. Deploy Web Assets to public_html
echo "Deploying public assets to ${WEB_DIR}..."
cp -r public/assets "${WEB_DIR}/"
cp -r public/uploads "${WEB_DIR}/" || true
cp public/favicon.ico "${WEB_DIR}/"
cp public/favicon.png "${WEB_DIR}/" || true
cp public/logo.png "${WEB_DIR}/"
cp public/logo-white.png "${WEB_DIR}/"
cp public/logo-icon.png "${WEB_DIR}/"
cp public/apple-touch-icon.png "${WEB_DIR}/"

# 3. Deploy front controller & .htaccess
cp deployment/hostinger_public_html_index.php "${WEB_DIR}/index.php"
cp deployment/hostinger.htaccess "${WEB_DIR}/.htaccess"

# 4. Deploy Application Core
echo "Deploying application code to ${CORE_DIR}..."
rsync -av --delete app/ "${CORE_DIR}/app/"
rsync -av --delete vendor/ "${CORE_DIR}/vendor/"
cp spark "${CORE_DIR}/spark"
chmod +x "${CORE_DIR}/spark"

# 5. Fix Permissions
echo "Setting secure permissions..."
chmod -R 775 "${CORE_DIR}/writable"
chmod -R 700 "${CORE_DIR}/writable/vault"
chmod 644 "${WEB_DIR}/index.php"
chmod 644 "${WEB_DIR}/.htaccess"

# 6. Execute Diagnostic Check
echo "Running system check..."
php "${CORE_DIR}/spark" system:check

echo "=== DEPLOYMENT COMPLETED SUCCESSFULLY ==="

