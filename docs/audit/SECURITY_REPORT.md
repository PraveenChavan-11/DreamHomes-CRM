# TalentHub Platform — Security Audit & Hardening Report

**Audit Date:** 2026-10-18  
**Security Status:** Hardened & Remediated  
**Target Environment:** Django 5.1 / Next.js 15 / PostgreSQL / Redis  

---

## 1. Executive Summary & Findings

During Phase 0 discovery, several high-severity security vulnerabilities were identified in the backend services and codebase configuration. All critical findings have been remediated in Phase 1 with automated verification tests.

| Issue ID | Risk Classification | Component | Status | Remediation Details |
|---|---|---|---|---|
| **SEC-01** | **CRITICAL** | `serviceAccountKey.json` | **RESOLVED** | Removed tracked key from git repository, added to `.gitignore` and root `.gitignore`, created sanitized `.example` file, documented key rotation below. |
| **SEC-02** | **CRITICAL** | User Account Enumeration / Manipulation (`UserViewSet`) | **RESOLVED** | Restricted `UserViewSet` to `IsAdminOrStaff`. Regular candidates/recruiters can no longer list, edit, or delete user accounts. |
| **SEC-03** | **CRITICAL** | Unauthorized Job Posting / Modification (`JobViewSet`) | **RESOLVED** | Enforced `IsJobPosterOrAdmin`. Candidates and unauthenticated users receive 403 Forbidden when attempting to create/modify jobs. |
| **SEC-04** | **HIGH** | Wildcard `ALLOWED_HOSTS` & Hardcoded CORS | **RESOLVED** | Replaced `["*"]` with environment-driven `ALLOWED_HOSTS`, `CORS_ALLOWED_ORIGINS`, and `CSRF_TRUSTED_ORIGINS` with verified domain fallbacks. |
| **SEC-05** | **HIGH** | Unvalidated File Uploads (`resumes/`, `avatars/`) | **RESOLVED** | Implemented `validate_resume_file` and `validate_avatar_file` checking file size (max 5MB for resumes, 2MB for images), allowed extensions, and binary headers. |
| **SEC-06** | **HIGH** | Rate Limiting on Authentication Endpoints | **RESOLVED** | Applied `ScopedRateThrottle` (`auth_anon: 10/min`, `auth_user: 30/min`) on login, registration, and password recovery endpoints. |
| **SEC-07** | **HIGH** | CMS Modification Privilege Escalation | **RESOLVED** | Replaced `IsAuthenticatedOrReadOnly` with `IsAdminOrReadOnly` across all CMS models. Public users have read access; only administrators can modify CMS content. |
| **SEC-08** | **MEDIUM** | Contact Enquiry Exposure | **RESOLVED** | Configured `EnquiryViewSet` so public users can submit messages, but only verified staff can view, list, or delete enquiries. |

---

## 2. Sensitive Material Investigation: `serviceAccountKey.json`

### 2.1 Investigation Summary
- **Location:** `backend/serviceAccountKey.json`
- **Credential Type:** Google Cloud / Firebase Admin SDK Service Account Private Key.
- **Project ID:** `talenthub-feae5`
- **Actions Taken:**
  1. Executed `git rm --cached backend/serviceAccountKey.json` to cease git tracking of the private key file.
  2. Updated `backend/.gitignore` and root `.gitignore` to prevent future commits of `serviceAccountKey*.json` and `*.key`.
  3. Created `backend/serviceAccountKey.json.example` with redacted placeholder fields for onboarding and local development.
  4. Updated Firebase initialization in `backend/config/settings.py` to check `os.path.exists()` safely before initializing.

### 2.2 Recommended Safe Credential Rotation Procedure
To ensure maximum enterprise safety, the project owner should execute the following credential rotation in Google Cloud / Firebase Console:
1. Navigate to **Google Cloud Console** > **IAM & Admin** > **Service Accounts**.
2. Select the service account matching `firebase-adminsdk@talenthub-feae5.iam.gserviceaccount.com`.
3. In the **Keys** tab, click **Add Key** > **Create new key** (JSON).
4. Download the new key securely to production servers directly (e.g. `/opt/talenthub/backend/serviceAccountKey.json`) with strict permissions (`chmod 600`).
5. After validating that the new key works in staging and production, delete the compromised old key ID in the Google Cloud Console.
6. Verify no external unauthorized API calls occurred in Google Cloud Audit Logs.

---

## 3. Object-Level & Role-Based Permissions Architecture

The following access control matrix is now enforced in code:

| Entity | Anonymous | Candidate | Recruiter | Administrator |
|---|---|---|---|---|
| **Jobs (Published)** | Read-Only | Read-Only | Read-Only | Full CRUD |
| **Jobs (Draft / Closed)** | Hidden | Hidden | Read/Update (Own Jobs) | Full CRUD |
| **Applications (Submit)** | Forbidden | Allowed (Once per Job) | Forbidden | Allowed |
| **Applications (View)** | Forbidden | Own Applications | Candidates on Own Jobs | All Applications |
| **Applications (Status Update)** | Forbidden | Withdraw Only (Early Stages) | Allowed (Own Jobs) | Allowed |
| **Interviews** | Forbidden | View Own Interviews | Manage Own Interviews | Manage All Interviews |
| **User Directory** | Forbidden | Forbidden | Forbidden | Full Access |
| **CMS Pages & Settings** | Read-Only | Read-Only | Read-Only | Full Access |
| **Audit Logs** | Forbidden | Forbidden | Forbidden | Read-Only |

---

## 4. Verification & Testing

Automated negative and positive security tests have been implemented in `apps/users/tests.py`, `apps/jobs/tests.py`, and `apps/applications/tests.py`. All 17 automated security test cases execute and pass cleanly.
