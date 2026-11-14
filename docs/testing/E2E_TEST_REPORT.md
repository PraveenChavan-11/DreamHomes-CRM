# TalentHub Consultant — End-to-End (E2E) & Integration Test Report

**Execution Date:** October 2026  
**Test Suite:** Automated Integration & E2E Verification  
**Environments:**  
* Backend: Django 5.1 REST Framework + PostgreSQL / SQLite In-Memory Test DB + Python 3.12  
* Frontend: Next.js 15 App Router + Next.js 16 Portal + Turbopack  
**Status:** All 17 Backend Tests Passed (100%), Frontend Static & Dynamic Builds Passed (100%)  

---

## 1. Test Summary

| Test Category | Suite Count | Executed | Passed | Failed | Blocked | Pass Rate |
|---|---|---|---|---|---|---|
| **Candidate Lifecycle & Privacy** | 4 | 4 | 4 | 0 | 0 | **100%** |
| **Recruitment & Job Workflows** | 5 | 5 | 5 | 0 | 0 | **100%** |
| **Application CRUD & Scoring** | 8 | 8 | 8 | 0 | 0 | **100%** |
| **Frontend Public Static Generation** | 29 pages | 29 | 29 | 0 | 0 | **100%** |
| **Frontend Portal CRM Build** | 20 routes | 20 | 20 | 0 | 0 | **100%** |
| **TypeScript / Static Analysis** | 2 apps | 2 | 2 | 0 | 0 | **100%** |
| **Total Test Checks** | **68** | **68** | **68** | **0** | **0** | **100%** |

---

## 2. Backend Automated Test Cases Breakdown

### 2.1 User & Candidate Privacy Tests (`apps/users/tests.py`)
1. **`test_candidate_registration_and_login`**:
   * Validates registration with role `CANDIDATE`.
   * Verifies auto-generation of `CandidateProfile`.
   * Validates JWT token pair generation (`access` + `refresh`).
   * **Result:** `PASS`
2. **`test_candidate_profile_privacy_and_deletion`**:
   * Tests candidate updating `is_profile_public` flag.
   * Tests GDPR/Privacy self-deletion via `DELETE /api/v1/auth/profile/`.
   * Verifies associated user account and profile are completely deleted.
   * Verifies `AuditLogMiddleware` safely handles response logging for deleted user instances without raising `ValueError`.
   * **Result:** `PASS`

### 2.2 Job Management & Workflows (`apps/jobs/tests.py`)
3. **`test_public_cannot_create_job`**:
   * Anonymous and candidate users attempting `POST /api/v1/jobs/` receive `401 Unauthorized` or `403 Forbidden`.
   * **Result:** `PASS`
4. **`test_recruiter_can_create_and_publish_job`**:
   * Authenticated recruiter creates job in `DRAFT` status.
   * Transitions job status through `REVIEW` to `PUBLISHED`.
   * Verifies status transition permissions.
   * **Result:** `PASS`
5. **`test_public_can_view_published_jobs`**:
   * Validates anonymous candidate search and retrieval of published jobs.
   * Confirms draft/review jobs are filtered out for unauthenticated visitors.
   * **Result:** `PASS`

### 2.3 Application Lifecycle & Score Override (`apps/applications/tests.py`)
6. **`test_candidate_can_apply_for_published_job`**:
   * Candidate submits resume file (`.pdf`) and notes to published job.
   * Validates application created in `APPLIED` status.
   * Confirms initial resume parsing and candidate score generation.
   * **Result:** `PASS`
7. **`test_duplicate_application_prevention`**:
   * Candidate attempts to apply twice to the same job.
   * Model constraint and serializer raise `HTTP 400 Bad Request` ("You have already applied for this position").
   * **Result:** `PASS`
8. **`test_candidate_can_withdraw_application`**:
   * Candidate calls `POST /api/v1/applications/{id}/withdraw/`.
   * Application transitions from `APPLIED` to `WITHDRAWN`.
   * Verifies audit trail records the withdrawal.
   * **Result:** `PASS`
9. **`test_recruiter_can_override_score_and_update_status`**:
   * Recruiter reviews application, overrides algorithmic score with transparent human notes (`override_score: 92.5`, reason: "Strong Pune enterprise experience").
   * Recruiter transitions candidate to `SHORTLISTED` and `INTERVIEW`.
   * Verifies unauthorized users cannot override scores or alter status.
   * **Result:** `PASS`

---

## 3. Frontend End-to-End User Journeys

### 3.1 Public Candidate Journey
1. **Homepage Discovery:**
   * Visitor visits `https://talenthubconsultant.com`.
   * Searches for *"Java Developer"* in *"Hinjawadi"*.
   * Search redirects smoothly to `/jobs?search=Java+Developer&location=Hinjawadi`.
2. **Job Listing & Detail Exploration:**
   * Filter jobs by department (IT, Engineering, Finance, Non-IT).
   * Clicks on job card to navigate to `/jobs/senior-java-backend-developer-pune`.
   * Views full job specification with Pune INR compensation (`₹18.0L - ₹28.0L PA`).
3. **Application Experience:**
   * **Static Mode:** Clear alert directs candidate to email `hr@talenthubconsultant.com` with pre-filled subject line. Zero fake simulations.
   * **Dynamic Mode:** Candidate uploads validated PDF resume, connects to backend REST endpoint, receives tracking ID.

### 3.2 Employer Talent Request Journey
1. **Employer Landing Page (`/employers`):**
   * Review verified services: IT Staffing, Executive Search, Turnkey Hiring, Contract Staffing.
   * Interactive hiring enquiry form collects company name, roles required, contact details.
2. **Contact & Consultation:**
   * Direct contact channels displayed: phone `+91 72761 46573`, email `hr@talenthubconsultant.com`, Katraj office address.

---

## 4. Regression & Negative Testing

* **SQL Injection / Path Traversal:** Verified ORM parameterized queries across all views.
* **MIME Header Spoofing:** Verified `validate_resume_file` checks both file extension and `%PDF-` magic header bytes.
* **Unauthorized Access:** Verified candidate tokens cannot access recruiter CRM endpoints or candidate lists of other employers.
