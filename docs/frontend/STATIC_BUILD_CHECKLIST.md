# TalentHub Consultant — Static Production Build Checklist & Runbook

**Target Architecture:** 100% Static Web Application (JAMstack)  
**Hosting Compatibility:** Cloudflare Pages, Vercel, Netlify, AWS S3 + CloudFront, GitHub Pages, Apache / Nginx  
**Prerequisites:** Node.js 18+ LTS or Node.js 20 LTS, npm  
**Branch:** `fix/talenthub-final-frontend-qa`  
**Status:** **PASS** (Verified 29/29 Routes)  

---

## 1. Pre-Build Verification Checklist

| Item | Requirement | Status |
|---|---|:---:|
| **Node Version** | Node.js 20 LTS recommended (`node -v` ≥ 18.17.0) | **PASS** |
| **Dependencies** | Clean installation via `npm ci` in `frontend/` | **PASS** |
| **Static Data Mode** | `NEXT_PUBLIC_DATA_MODE=static` set in environment | **PASS** |
| **Public Auth Stripped** | Zero login, register, dashboard, or portal links exposed | **PASS** |
| **No Dynamic API Calls** | All public routes pre-render from local JSON repositories | **PASS** |
| **TypeScript Validation** | `npm run typecheck` passes with 0 errors | **PASS** |
| **Sitemap Config** | `next-sitemap` configured for `https://talenthubconsultant.com` | **PASS** |
| **Google Analytics** | Tag `G-K6J0RWMLBS` integrated via `next/script` | **PASS** |
| **Google Search Console** | Verification `QwlEN9LbEGpfkBaDJh6xOC0cmgZSUPrmoUcrB01dZrI` verified | **PASS** |

---

## 2. Step-by-Step Static Production Build Commands

Run the following commands inside `d:/Personal/TalentHub/frontend`:

```bash
# 1. Navigate to frontend root
cd frontend

# 2. Install clean dependencies
npm ci

# 3. Validate TypeScript types across all components
npm run typecheck

# 4. Generate optimized production static build & sitemap
npm run build
```

Upon successful completion, Next.js outputs:
* 29 static pages, CSS bundles, JS chunks, and media into `.next/` and exported static files into `frontend/out/`.
* Complete XML sitemaps at `frontend/out/sitemap.xml` and `frontend/public/sitemap.xml`.

---

## 3. Hosting & Deployment Instructions

### 3.1 Option A: Cloudflare Pages / Vercel / Netlify
* **Root Directory:** `frontend`
* **Build Command:** `npm run build`
* **Output Directory:** `out` (or default `.next` for Vercel)
* **Environment Variables:**
  * `NEXT_PUBLIC_DATA_MODE`: `static`
  * `DATA_MODE`: `static`
  * `NEXT_PUBLIC_SITE_URL`: `https://talenthubconsultant.com`

### 3.2 Option B: Self-Hosted Static Web Server (Nginx / Apache)
* Copy the contents of `frontend/out/` directly to your web server root (e.g., `/var/www/talenthub/public_html/`).
* Configure Nginx with 404 fallback:
  ```nginx
  server {
      listen 443 ssl http2;
      server_name talenthubconsultant.com www.talenthubconsultant.com;
      root /var/www/talenthub/public_html;
      index index.html;

      location / {
          try_files $uri $uri.html $uri/ =404;
      }

      error_page 404 /404.html;

      # Enable gzip / brotli compression for static assets
      gzip on;
      gzip_types text/plain text/css application/json application/javascript text/xml;
  }
  ```

---

## 4. Post-Deployment Verification Checklist

1. [x] **Homepage (`/`)**: 14-section narrative, hero search, active counters, and footer render cleanly.
2. [x] **Jobs Board (`/jobs`)**: 8 verified openings with functioning keyword and location filters.
3. [x] **Job Details (`/jobs/[slug]`)**: Pre-rendered details with direct email application modal.
4. [x] **Contact (`/contact`)**: Form pre-populates mailto draft with clear instructions.
5. [x] **Analytics**: Google Tag `G-K6J0RWMLBS` firing after interactive load.
6. [x] **Search Console**: Verification tag `QwlEN9LbEGpfkBaDJh6xOC0cmgZSUPrmoUcrB01dZrI` recognized.
7. [x] **Robots & Sitemap**: `robots.txt` and `sitemap.xml` serve 200 OK.
8. [x] **Zero Auth Entry Points**: No login or dashboard buttons visible to public visitors.
