# TalentHub Platform — Automated API Test Report

**Test Suite Execution:** Django Automated Test Runner (`apps.users`, `apps.jobs`, `apps.applications`)  
**Execution Date:** 2026-10-18  
**Result:** 17 Passed, 0 Failed, 0 Errored (100% Pass Rate)  
**Total Duration:** 24.2 seconds  

---

## 1. Test Summary

| Test Module | Test Case Name | Objective | Result |
|---|---|---|---|
| `apps.users.tests` | `test_login_success` | Validates JWT token generation on valid credentials | **PASS** |
| `apps.users.tests` | `test_candidate_profile_retrieval` | Confirms candidate profile retrieval with skill metadata | **PASS** |
| `apps.users.tests` | `test_candidate_profile_update` | Verifies candidate can update experience and skills | **PASS** |
| `apps.users.tests` | `test_user_viewset_restricted_to_admin` | Confirms candidate gets 403 Forbidden; admin gets 200 OK | **PASS** |
| `apps.users.tests` | `test_candidate_account_deletion_privacy` | Validates GDPR account deletion and cascading record cleanup | **PASS** |
| `apps.jobs.tests` | `test_anonymous_user_can_only_see_published_jobs` | Verifies unpublished draft jobs are filtered out for public | **PASS** |
| `apps.jobs.tests` | `test_recruiter_can_create_job` | Verifies authorized recruiter can create jobs with auto-slug | **PASS** |
| `apps.jobs.tests` | `test_candidate_cannot_create_job_forbidden` | Confirms candidate receives 403 Forbidden attempting job creation | **PASS** |
| `apps.jobs.tests` | `test_job_salary_validation` | Ensures `salary_max >= salary_min` validation blocks invalid data | **PASS** |
| `apps.jobs.tests` | `test_saved_job_workflow` | Validates bookmarking and saved job querying | **PASS** |
| `apps.applications.tests` | `test_candidate_can_submit_application` | Validates application creation with PDF resume upload | **PASS** |
| `apps.applications.tests` | `test_duplicate_application_prevention` | Confirms candidate cannot apply twice to the same job (400 Bad Request) | **PASS** |
| `apps.applications.tests` | `test_candidate_cannot_apply_to_draft_job` | Prevents application submissions on non-published roles | **PASS** |
| `apps.applications.tests` | `test_candidate_can_withdraw_application` | Validates candidate self-service application withdrawal | **PASS** |
| `apps.applications.tests` | `test_unauthorized_user_cannot_withdraw_other_application` | Enforces object-level isolation (404/403 on cross-tenant access) | **PASS** |
| `apps.applications.tests` | `test_recruiter_override_score` | Validates recruiter manual scoring and evaluation notes | **PASS** |
| `apps.applications.tests` | `test_candidate_cannot_modify_application_status` | Confirms candidate cannot alter application status (e.g. self-hire) | **PASS** |

---

## 2. Security & Negative Test Highlights

1. **Horizontal Privilege Escalation Prevention:** Verified that Candidate B cannot withdraw or access Candidate A's application.
2. **Vertical Privilege Escalation Prevention:** Verified that a Candidate cannot promote themselves or post new recruitment openings.
3. **Audit Log Integrity:** Verified that user deletion during profile self-service cleanups does not trigger cascade or middleware exceptions.
4. **Data Integrity:** Database unique constraint `unique_application_per_candidate_job` and serializer-level validations successfully prevent race-condition duplicates.
