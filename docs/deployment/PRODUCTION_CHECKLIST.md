# TalentHub Consultant — Production Deployment Checklist & Runbook

**Document Version:** 2.0.0-PROD  
**Target Environment:** Production (`talenthubconsultant.com` / `api.talenthubconsultant.com`)  
**Maintained By:** DevSecOps & Enterprise Architecture  

---

## 1. Pre-Deployment Configuration & Environment Variables

### 1.1 Backend Environment Variables (`backend/.env`)

Ensure the following variables are securely configured in the production environment:

| Variable Name | Required Value / Description | Validated |
|---|---|:---:|
| `DJANGO_SECRET_KEY` | Cryptographically random 64+ char string (NOT the default dev key) | [ ] |
| `DJANGO_DEBUG` | `False` (CRITICAL: must never be True in production) | [ ] |
| `ALLOWED_HOSTS` | `api.talenthubconsultant.com,talenthubconsultant.com,127.0.0.1` | [ ] |
| `CORS_ALLOWED_ORIGINS` | `https://talenthubconsultant.com,https://portal.talenthubconsultant.com` | [ ] |
| `CSRF_TRUSTED_ORIGINS` | `https://talenthubconsultant.com,https://portal.talenthubconsultant.com` | [ ] |
| `DATABASE_URL` | `postgres://talenthub_user:<STRONG_PASS>@localhost:5432/talenthub_prod` | [ ] |
| `REDIS_URL` | `redis://localhost:6379/1` | [ ] |
| `CELERY_BROKER_URL` | `redis://localhost:6379/2` | [ ] |
| `CELERY_RESULT_BACKEND` | `redis://localhost:6379/3` | [ ] |
| `JWT_SECRET_KEY` | Distinct 64+ char random string | [ ] |
| `JWT_ACCESS_LIFETIME_MINUTES` | `15` (15-minute rotation) | [ ] |
| `JWT_REFRESH_LIFETIME_DAYS` | `7` (7-day refresh window) | [ ] |
| `MEDIA_ROOT` | `/opt/talenthub/backend/media/` | [ ] |
| `STATIC_ROOT` | `/opt/talenthub/backend/static_collected/` | [ ] |

### 1.2 Frontend Environment Variables (`frontend/.env.production`)

| Variable Name | Required Value / Description | Validated |
|---|---|:---:|
| `NEXT_PUBLIC_API_URL` | `https://api.talenthubconsultant.com/api/v1` | [ ] |
| `NEXT_PUBLIC_SITE_URL` | `https://talenthubconsultant.com` | [ ] |
| `NEXT_PUBLIC_ENABLE_STATIC_FALLBACK` | `true` (Allows graceful degradation if backend is undergoing maintenance) | [ ] |

---

## 2. Server & Infrastructure Pre-Flight Verification

### 2.1 Backend Pre-Flight Execution
Run these commands on the production app server inside `/opt/talenthub/backend`:
```bash
# 1. Activate isolated Python 3.12 environment
source venv/bin/activate

# 2. Install pinned dependencies
pip install --no-cache-dir -r requirements.txt

# 3. Verify deployment security posture
python manage.py check --deploy

# 4. Check for missing migrations
python manage.py makemigrations --check --dry-run

# 5. Apply pending database migrations
python manage.py migrate --noinput

# 6. Collect static assets for Nginx serving
python manage.py collectstatic --noinput

# 7. Run backend test suite on staging DB
python manage.py test
```

### 2.2 Systemd Daemon Management
Ensure services are enabled and reloaded:
```bash
sudo systemctl daemon-reload
sudo systemctl restart gunicorn
sudo systemctl restart celery
sudo systemctl restart celery-beat
sudo systemctl status gunicorn celery celery-beat --no-pager
```

---

## 3. Reverse Proxy & Security Hardening (Nginx)

### 3.1 Essential Nginx Directives
* **SSL/TLS:** TLSv1.2 and TLSv1.3 only; ECDHE cipher suites; OCSP stapling enabled.
* **HTTP to HTTPS Redirect:** Permanent 301 redirect on port 80.
* **Security Headers:**
  * `Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"`
  * `X-Frame-Options "SAMEORIGIN"`
  * `X-Content-Type-Options "nosniff"`
  * `Referrer-Policy "strict-origin-when-cross-origin"`
* **Upload Limits:** `client_max_body_size 10M;`
* **Static Caching:** Fast caching on `/static_collected/` and `/media/` with `expires 30d`.

---

## 4. Frontend Production Build & Deployment

### 4.1 Public Website (`frontend/`)
```bash
cd /opt/talenthub/frontend
npm ci
npm run typecheck
npm run build
# The export is output to `frontend/out` for high-speed edge distribution (Vercel / Cloudflare Pages / Nginx)
```

### 4.2 Management Portal (`frontend/portal/`)
```bash
cd /opt/talenthub/frontend/portal
npm ci
npm run build
# Run via PM2 cluster or Node 20 LTS process:
pm2 restart talenthub-portal || pm2 start npm --name "talenthub-portal" -- start
```

---

## 5. Post-Deployment Smoke Test Protocol

Perform immediate checks after deployment:
1. **Homepage:** `GET https://talenthubconsultant.com` returns `200 OK`, hero search loads without layout shifts.
2. **Robots & Sitemap:**
   * `GET https://talenthubconsultant.com/robots.txt` contains disallow directives for `/admin/` and `/interviews`.
   * `GET https://talenthubconsultant.com/sitemap.xml` returns valid XML index.
3. **Job Search:** Perform test search for Pune IT jobs; verify INR salary display (`₹...L PA`).
4. **Backend Health:** `GET https://api.talenthubconsultant.com/api/v1/jobs/` returns HTTP 200 with JSON payload.
5. **Auth Security:** Test invalid login credentials to confirm throttled error response and JWT rejection.
6. **Celery Worker:** Check Redis queue size and verify Celery worker logs show task readiness.

---

## 6. Rollback Procedures

If a critical blocker arises during deployment:
1. **Backend Rollback:**
   ```bash
   cd /opt/talenthub/backend
   git checkout <PREVIOUS_COMMIT_TAG>
   source venv/bin/activate
   pip install -r requirements.txt
   sudo systemctl restart gunicorn celery
   ```
2. **Frontend Rollback:**
   * In Vercel / Cloudflare: Roll back instantly to the prior deployment alias.
   * If self-hosted: Symlink Nginx root to the prior build release directory.
