# TalentHub Production Transformation — Initial Audit Report (Phase 0)

**Date:** 2026-10-18  
**Auditor:** Principal Full-Stack Architect & DevSecOps Lead  
**Repository:** `https://github.com/PraveenChavan-11/JobConsultancy`  
**Branch:** `feature/talenthub-production-finalization`  

---

## 1. Executive Summary

TalentHub is a hybrid job consultancy platform built with a Next.js (App Router, Tailwind CSS, Framer Motion) public website, a Next.js CRM portal (`frontend/portal`), and a Django REST Framework (DRF, PostgreSQL, Redis, Celery, SimpleJWT) backend. The platform is designed to operate in dual modes: **Static Mode** (JAMstack/SSG for static hosting) and **Dynamic Mode** (connecting to the Django REST API).

While basic foundational components exist across frontend and backend, a rigorous technical inspection revealed multiple **critical security vulnerabilities**, **authorization gaps**, **workflow incompleteness**, and **UI/UX brand token discrepancies** that must be resolved before enterprise production certification.

---

## 2. Existing Features (Verified Active)

### 2.1 Backend (Django REST Framework)
- **Authentication**: JWT-based auth (`/api/v1/auth/login/`, `refresh/`, `logout/`, `register/`, `forgot-password/`, `reset-password/`, `profile/`).
- **Jobs Engine**: Job listings with filtering by category, job type, location, remote status, search queries, and slugs (`/api/v1/jobs/`).
- **Saved Jobs**: Candidate saved job bookmarks (`/api/v1/saved-jobs/`).
- **Applications**: Basic application submission with CV upload (`/api/v1/applications/`).
- **Interviews**: Scheduled interview tracking with Microsoft Teams link integration (`/api/v1/interviews/`).
- **Companies**: Company directory management (`/api/v1/companies/`).
- **CMS**: Dynamic pages, sections, media, FAQs, testimonials, blog posts, and site settings (`/api/v1/cms/`).
- **Audit Logging**: Request audit logging middleware storing changes in `AuditLog` table (`/api/v1/logs/`).
- **Analytics**: Basic dashboard KPI counters (`/api/v1/analytics/`).
- **Heuristic Resume Parser**: Synchronous PyPDF-based keyword extractor comparing extracted text to an in-memory 60+ skills taxonomy.

### 2.2 Frontend (Public Website)
- **App Router Pages**: Home (`/`), About (`/about`), Solutions (`/solutions`), Jobs (`/jobs`), Job Details (`/jobs/[slug]`), Candidates (`/candidates`), Employers (`/employers`), Insights (`/insights`, `/insights/[slug]`), Contact (`/contact`), FAQ (`/faq`), Privacy (`/privacy`), Terms (`/terms`), Interviews (`/interviews`).
- **Dual Data Layer**: `CMSService`, `JobService`, `BlogService`, and `TestimonialService` switch between `static-fetchers` (local JSON) and `api-fetchers` (REST API) via `NEXT_PUBLIC_DATA_MODE`.
- **SEO & Structured Data**: OpenGraph, Twitter card metadata, JSON-LD Schema (`Organization`, `WebSite`, `JobPosting`).

### 2.3 CRM Portal (`frontend/portal`)
- **Protected Recruiter/Admin Dashboard**: Pages for managing Candidates, Jobs, Companies, Interviews, CMS Pages/Sections/Media, Audit Logs, and Settings.
- **Data Visualizations**: ApexCharts integration and tabular recruitment records.

---

## 3. Critical Security Risks & Bugs (High Priority)

| ID | Severity | Category | File / Location | Description & Impact |
|---|---|---|---|---|
| **SEC-01** | **CRITICAL** | Secret Leak | `backend/serviceAccountKey.json` | Real Google/Firebase service account credentials containing private key are committed to git. Must be removed from git tracking, gitignored, and rotated. |
| **SEC-02** | **CRITICAL** | Authorization Escalation | `backend/apps/users/views.py` (`UserViewSet`) | `UserViewSet` allows any authenticated user (including regular candidates) full CRUD access to all user accounts in the database. |
| **SEC-03** | **CRITICAL** | Authorization Escalation | `backend/apps/jobs/views.py` (`JobViewSet`) | Any authenticated user (including candidates) can create, update, or delete job postings via POST/PUT/DELETE because only `IsAuthenticatedOrReadOnly` is enforced without role checking. |
| **SEC-04** | **CRITICAL** | Insecure Settings | `backend/config/settings.py` | Wildcard `ALLOWED_HOSTS = ["*"]` is active. Hardcoded development CORS origins in production. Insecure default secrets. |
| **SEC-05** | **HIGH** | Unrestricted Upload | `backend/apps/users/models.py`, `apps/applications/models.py` | `resume` and `avatar` FileFields have no file size validation, no MIME type restriction, exposing backend to malicious file uploads or DoS. |
| **SEC-06** | **HIGH** | Missing Rate Limiting | `backend/apps/users/views.py` | Sensitive auth endpoints (`login/`, `register/`, `forgot-password/`) lack dedicated scoped rate limits, risking credential stuffing and email bombing. |
| **SEC-07** | **HIGH** | Object-Level Permissions | `backend/apps/applications/views.py` | Lacks explicit object-level permission enforcement on update/destroy operations, risking cross-tenant data modification. |

---

## 4. Incomplete Implementations & Missing Features

### 4.1 Recruitment Lifecycle Gaps
- **Specification Status Flow**: `Job Draft → Review → Published → Applications → Screening → Shortlisted → Interview → Offer → Hired / Rejected → Closed`.
- **Current State**:
  - `Job.Status` only has `DRAFT`, `PUBLISHED`, `CLOSED`. Missing `REVIEW` and `EXPIRED`.
  - `Application.Status` only has `APPLIED`, `SCREENING`, `INTERVIEW`, `OFFER`, `HIRED`, `REJECTED`. Missing `SHORTLISTED`.
  - No database or serializer validation preventing invalid status transitions (e.g. jumping from `APPLIED` straight to `HIRED` or updating a `CLOSED` job).
- **Duplicate Application Prevention**: No database `UniqueConstraint(fields=['job', 'candidate'])` and no validation check in `ApplicationViewSet.perform_create` or `ApplicationSerializer`. Candidates can submit duplicate applications repeatedly.
- **Candidate Self-Service**:
  - No application withdrawal endpoint (`/applications/{id}/withdraw/`).
  - No account deletion / GDPR privacy data wipe endpoint.
- **Resume Parser Realism**:
  - Currently marketed as "AI Screening" in summary descriptions, but is purely synchronous regex matching against a hardcoded Python list.
  - No asynchronous background parsing with retries when PDFs are large.
  - No recruiter override or scoring explanation breakdown in the model.

### 4.2 Frontend & UI/UX Inconsistencies
- **Brand Palette Deviations**:
  - Specification defines: Primary Green `#1D6A34`, Accent Mint `#37CE97`, Deep Green `#0D4222`, Secondary Teal `#1B5F52`, Background `#F9FBFA`, Soft Surface `#EAF5EF`, Main Text `#172B22`.
  - Current `globals.css` uses `#167D46` for primary, `#1C6E5E` for secondary, `#0D4222` for text instead of `#172B22`, and `#e6efe8` instead of `#EAF5EF`.
- **Navigation Architecture**:
  - Public navigation in `Navbar.tsx` lacks dedicated CTAs ("Find Jobs", "For Employers", "For Candidates"), mobile drawer focus trapping, and active route visual indicators.
- **Static vs Dynamic Integrity**:
  - The static mode must clearly inform users when backend-only actions (such as submitting an application or resetting password) require the dynamic API, rather than silently failing or providing deceptive mock states.

### 4.3 Automated Testing & CI/CD
- **Outdated CI/CD Actions**: `.github/workflows/deploy.yml` uses deprecated `actions/checkout@v3`, `actions/setup-node@v3` (Node 18), `actions/setup-python@v4`, `appleboy/ssh-action@v0.1.6`, `amondnet/vercel-action@v20`.
- **Minimal Test Coverage**: Exactly 2 basic tests exist in `apps/users/tests.py`. Zero automated tests exist for Jobs, Applications, Permissions, Companies, CMS, or Frontend E2E workflows.

---

## 5. Risk & Priority Classifications

| Priority | Phase | Core Objectives |
|---|---|---|
| **P0 (Urgent)** | **Phase 1** | Untrack `serviceAccountKey.json`, fix wildcard `ALLOWED_HOSTS`, fix `UserViewSet` and `JobViewSet` permissions, implement duplicate application prevention, add missing job/application statuses, add file validation. |
| **P1 (High)** | **Phase 1 & 2** | Refactor resume parser to Celery background task with transparent scoring; align CSS tokens with brand guide; modernize Navbar and Footer; implement application withdrawal and candidate profile deletion. |
| **P2 (Medium)** | **Phase 3** | Validate JobPosting schema, verify Core Web Vitals, audit canonical URLs and robots.txt. |
| **P3 (Normal)** | **Phase 4** | Implement comprehensive test suite (DRF tests + E2E Playwright), update GitHub Actions CI/CD to modern LTS actions. |

---

## 6. Action Plan & Next Steps

1. **Execute Phase 1**: Remediate all critical security vulnerabilities, harden DRF permissions, update recruitment workflow models and migrations, test duplicate prevention, and implement background parsing.
2. **Execute Phase 2**: Standardize design system tokens, modernize navigation, responsive layout polish, and breadcrumbs.
3. **Execute Phase 3**: SEO schema audits and performance optimizations.
4. **Execute Phase 4**: Automated testing and CI/CD pipeline modernization.
