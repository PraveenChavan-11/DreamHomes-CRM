# TalentHub Consultant — Static Career Enquiry Architecture (Phase 6)

## 1. Requirement & Rationale
A recruitment consultancy website must never leave candidates at a dead end when active job listings do not match their specific background, or when zero active jobs are returned by a search filter.

Because TalentHub operates as a **100% static frontend with no hosted backend server**, we must:
1. Avoid fake form submission toasts or claims that a file has been uploaded to a non-existent database.
2. Provide a genuine, high-touch pathway for candidates to submit their career preferences and CVs directly to TalentHub's recruitment team in Katraj, Pune.
3. Explicitly instruct the candidate to attach their CV to the generated email draft.

---

## 2. Dedicated Route: `/career-enquiry`

### 2.1 Route & Metadata
- **URL:** `https://talenthubconsultant.com/career-enquiry`
- **Component File:** `frontend/app/(public)/career-enquiry/page.tsx`
- **Page Title:** `Candidate Career Profile & Resume Submission \| TalentHub Consultant Pune`
- **Meta Description:** *"Submit your career preferences and resume directly to TalentHub Consultant Pune. Our executive recruiters match profiles with upcoming mandates across IT, Auto & Corporate sectors."*
- **Indexing:** Included in `next-sitemap.config.js` and exported static sitemap.

### 2.2 Form Fields
1. **Full Name** (`text`, required)
2. **Email Address** (`email`, required)
3. **Contact Number** (`tel`, required)
4. **Current Location** (`text`, required, e.g. "Kothrud, Pune")
5. **Desired Job Role / Title** (`text`, required, e.g. "Senior Java Developer")
6. **Primary Skills** (`text`, required, e.g. "Java 21, Spring Boot, Microservices, AWS")
7. **Total Relevant Experience** (`select`, options: "0–1 Year", "1–3 Years", "3–5 Years", "5–8 Years", "8–12 Years", "12+ Years")
8. **Preferred Work Location in Pune** (`select`, options: "Anywhere in Pune", "Hinjawadi IT Park", "Kharadi EON / World Trade Center", "Magarpatta Cybercity", "Baner / Balewadi / Wakad", "Chakan / Bhosari Industrial Belt", "Viman Nagar / Kalyani Nagar", "Remote / Hybrid Eligible")
9. **Notice Period** (`select`, options: "Immediate Joiner (< 15 Days)", "30 Days", "60 Days", "90 Days (Standard IT)")
10. **Short Message / Career Summary** (`textarea`, optional)

### 2.3 Transparent Static Mailto Architecture
- When the candidate clicks **"Open Email Draft with Preferences"**:
  - The client validates mandatory fields.
  - Builds a structured plain-text profile summary.
  - Encodes the body and subject into a RFC-compliant `mailto:hr@talenthubconsultant.com` URL.
  - Triggers window location navigation, launching the candidate's default email client (Outlook, Apple Mail, Gmail, Thunderbird) with the email already formatted.
- **Prominent Resume Attachment Notice:**
  > ⚠️ **Resume Attachment Instructions:**
  > Clicking **Open Email Draft** below launches your email application addressed to **hr@talenthubconsultant.com** with these details pre-filled. Please ensure you manually attach your latest Resume / CV (PDF or Word document) before sending.
- **Copy Email Fallback:** A dedicated copy button allows candidates using webmail without a registered desktop mail handler to copy `hr@talenthubconsultant.com` with one click.

---

## 3. Candidate Intake Touchpoints across the Platform

1. **Jobs Page (`/jobs`) — Zero-Results State:**
   - **Trigger:** Filter or search yields 0 matches.
   - **Heading:** `Didn't Find the Right Opportunity?`
   - **Supporting Copy:** *"Your next career move doesn't have to wait for the perfect listing. Tell us what kind of role you're looking for and share your resume with our recruitment team. We'll consider your profile for relevant opportunities as they become available."*
   - **CTA:** `Share Your Resume & Career Preferences` -> Links to `/career-enquiry`.
2. **Jobs Page (`/jobs`) — Permanent Post-Listing Card:**
   - Rendered permanently below the active jobs list so candidates with unlisted skillsets have an immediate pathway.
3. **Job Details Page (`/jobs/[id]`):**
   - Application modal explains resume attachment via email, with reference to general career enquiry if the specific opening is filled.
4. **Candidates Page (`/candidates`):**
   - Hero secondary CTA directs candidates to `/career-enquiry`.
5. **Global Footer (`Footer.tsx`):**
   - Direct link: `Career Preferences Enquiry` under Candidate column.

---

## 4. Verification & Browser Testing
- Successfully tested on desktop and mobile viewports.
- Form validation stops empty submissions.
- Clicking the CTA encodes and launches `mailto:hr@talenthubconsultant.com?subject=...&body=...`.
- Verified in `browser_subagent` run; captured screenshot `career_enquiry_page_1792336397934.png`.
