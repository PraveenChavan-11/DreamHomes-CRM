# TalentHub — Performance & Optimization Report

**Audit Date:** November 8, 2026  
**Stack:** CodeIgniter 4.7 / PHP 8.2 / Native CSS & JS  
**Hosting Target:** Hostinger Shared Hosting  

---

## 1. Core Web Vitals Targets
- **LCP (Largest Contentful Paint):** Target ≤ 2.5s
- **INP (Interaction to Next Paint):** Target ≤ 200ms
- **CLS (Cumulative Layout Shift):** Target ≤ 0.1

---

## 2. Optimizations Implemented
1. **Self-Hosted Font Loading:**
   - Switched from external Google Fonts CDN to local variable WOFF2 fonts (`inter-latin-var.woff2`, `plus-jakarta-sans-latin-var.woff2`).
   - Added `<link rel="preload">` in `<head>` to prevent flash of invisible text (FOIT) and eliminate render-blocking external requests.
2. **Lightweight Native Asset Layer:**
   - Replaced bloated libraries with native CSS tokens (`tokens.css`), custom animations (`motion.css`), and zero-dependency scripts (`motion.js`, `forms.js`).
   - Scripts are loaded with `defer` to unblock HTML parsing.
3. **Image Optimization:**
   - Explicit `width` and `height` on images to guarantee zero CLS.
   - Native lazy loading (`loading="lazy"`) and asynchronous decoding (`decoding="async"`).
4. **Database & View Caching:**
   - Dynamic settings cached via CodeIgniter cache service.
   - Sitemap generated with hourly caching.

