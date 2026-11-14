# TalentHub — Initial Administrator Provisioning & RBAC Guide

## 1. Initial Administrator Provisioning
Public admin registration is strictly disabled in the production architecture. Admin accounts can only be provisioned through secure migration seeds or by an active Super Admin from the Admin Users management panel.

### Default Initial Super Admin
When `002_seed_initial_data.sql` is imported, the primary administrator account is provisioned:
- **Email:** `admin@talenthubconsultant.com`
- **Initial Password:** Provisioned as a bcrypt-hashed hash (`PASSWORD_BCRYPT` with cost factor 12)
- **Role:** `SUPER_ADMIN`

> **CRITICAL SECURITY STEP:** Immediately upon first login, the administrator must change their temporary password from **Settings -> Change Password** or update the hash directly in the database.

---

## 2. Role-Based Access Control (RBAC) Matrix

TalentHub enforces server-side permission checks in PHP before every protected API handler executes. React route guards provide client-side navigation convenience, but the API is the authoritative security boundary.

| Capability / Permission | Super Admin | Recruiter | Content Editor |
| :--- | :---: | :---: | :---: |
| **View Dashboard KPIs** | ✅ | ✅ | ❌ |
| **Manage Jobs (Create, Edit, Publish)** | ✅ | ✅ | ❌ |
| **View Candidate Directory** | ✅ | ✅ | ❌ |
| **Download / Stream Resumes** | ✅ | ✅ | ❌ |
| **Update Application Status & Recruiter Notes** | ✅ | ✅ | ❌ |
| **Manage Employer CRM & Enquiries** | ✅ | ✅ | ❌ |
| **Edit Insights / Blogs & FAQs** | ✅ | ❌ | ✅ |
| **Edit Email Templates** | ✅ | ❌ | ❌ |
| **View Tamper-Evident Audit Logs** | ✅ | ❌ | ❌ |
| **Manage Admin Users & Roles** | ✅ | ❌ | ❌ |
| **Modify Website Settings** | ✅ | ❌ | ❌ |

---

## 3. Creating New Admin Accounts
To provision a new recruiter or editor:
1. Log in to the Admin Dashboard as Super Admin (`https://talenthubconsultant.com/admin`).
2. Navigate to **Users & Roles**.
3. Click **Add New User**.
4. Enter the user's Full Name, Corporate Email (`@talenthubconsultant.com`), and select their designated Role (`RECRUITER` or `CONTENT_EDITOR`).
5. Provide a strong temporary password (min 10 characters, upper/lower/numbers/symbols).
6. The user will be created and audited in `audit_logs`.
