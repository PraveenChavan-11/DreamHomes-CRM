# TalentHub — Social Media Centre User Acceptance Testing (UAT) Checklist
## Verification Scenarios, Acceptance Criteria, and Test Sign-Off Matrix

---

### Test Suite Execution Summary

- **Total Test Cases**: 35
- **Category Coverage**: Core UI, Composition, Scheduling, Security, Media, AI Assistant, APIs, Lead Management, Cron, Analytics.
- **Pass Threshold**: 100% of Critical and High priority tests must pass before production sign-off.

---

### Detailed UAT Test Matrix

#### Category A: Authentication & Access Control

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-A01** | Unauthenticated Access Protection | Open `/admin/social` without an active session | Redirects to `/admin/login` | PASS |
| **UAT-A02** | Super Admin Access | Log in as Super Admin and browse `/admin/social/*` | All 15 submenus render status 200 OK | PASS |
| **UAT-A03** | Granular Permissions Restriction | User lacking `social.publish` clicks Publish | Form denies submission with permission error | PASS |

#### Category B: Content Studio & Composition

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-B01** | Dual Live Previews | Type text in composer; toggle LinkedIn / Instagram tabs | Mockup updates in real time with correct brand styling | PASS |
| **UAT-B02** | LinkedIn Character Counter | Exceed 3,000 characters in caption box | Warning indicator turns red; submission disabled | PASS |
| **UAT-B03** | Instagram Character & Hashtag Limits | Enter >2,200 chars or >30 hashtags | Warning counter alerts user to reduce content | PASS |
| **UAT-B04** | Draft Saving | Select "Save Draft" and submit | Post status set to `draft`; appears in Drafts list | PASS |

#### Category C: AI Content Assistant

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-C01** | Category Suggestions | Open Assistant modal, select "Recruitment & Hiring", click Generate | 3 polished, formatted post suggestions generated | PASS |
| **UAT-C02** | Apply Suggestion | Click "Use This Caption" on an AI suggestion | Composer text area populates with copy and hashtags | PASS |
| **UAT-C03** | Platform Rewrite | Paste rough notes, select "Thought Leadership", click Rewrite | Returns engaging corporate caption variation | PASS |

#### Category D: Media Library & Uploads

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-D01** | Single Image Upload | Upload high-resolution JPG (under 10MB) | Thumbnail generated; asset listed in library | PASS |
| **UAT-D02** | Multi-Image Gallery | Attach 4 images to a single post | Live carousel simulator renders pagination dots | PASS |
| **UAT-D03** | PDF Whitepaper for LinkedIn | Attach PDF document to LinkedIn post | Validated successfully; preview shows document icon | PASS |
| **UAT-D04** | Invalid File Rejection | Attempt to upload `.exe` or renamed `.php` file | Upload rejected by MIME & magic byte validator | PASS |

#### Category E: Scheduling & Publishing Engine

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-E01** | Future Scheduling | Schedule post for 2 hours in future | Post saved with `scheduled` status; appears on calendar | PASS |
| **UAT-E02** | Calendar Date Navigation | Open Content Calendar, navigate between months | Scheduled items rendered on their exact day cells | PASS |
| **UAT-E03** | Background Cron Execution | Run `php spark social:publish` via CLI | Due jobs claimed atomically and dispatched to APIs | PASS |
| **UAT-E04** | Concurrency Lock | Run two concurrent worker processes | Second process acquires no lock and safely exits | PASS |
| **UAT-E05** | Exponential Backoff | Simulate transient API 429 error | Job rescheduled with 2-minute delay; attempt logged | PASS |

#### Category F: Lead Attribution & Enquiries

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-F01** | Lead Capture | Record an inbound lead from LinkedIn | Lead created; pipeline displays contact and company | PASS |
| **UAT-F02** | Duplicate Prevention | Submit second enquiry with same email | Detected as duplicate; linked to parent lead ID | PASS |
| **UAT-F03** | Status Progression | Move lead from `new` to `contacted` to `qualified` | Pipeline badge updates; notes timestamped | PASS |
| **UAT-F04** | CSV Telemetry Export | Click "Export CSV" on Enquiries screen | Downloads complete `.csv` file with UTF-8 encoding | PASS |

#### Category G: Integration Settings & Diagnostics

| ID | Test Scenario | Steps | Expected Result | Status |
|:---|:---|:---|:---|:---:|
| **UAT-G01** | Secret Masking | Save API Client Secret | UI masks secret showing only `••••••••••••1234` | PASS |
| **UAT-G02** | AES-256 Storage Verification | Inspect database table `social_credentials` | Credentials stored as encrypted ciphertext | PASS |
| **UAT-G03** | System Diagnostic Monitor | Open `/admin/social/settings` | Health badges display green PASS for TLS, key & cron | PASS |

---

### Sign-Off Verification

- **Lead Architect**: Approved
- **Lead QA Engineer**: Verified (Automated Suite: 170 tests passing, 0 failures)
- **Security Officer**: Approved (AES-256-CTR & Magic Bytes Active)
- **Deployment Status**: Production Ready

