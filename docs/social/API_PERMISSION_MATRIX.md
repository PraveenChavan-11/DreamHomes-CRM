# TalentHub — Social Media API Permission Matrix
## Platform Scopes, App Verification Guidelines, and Capability Mapping

---

### LinkedIn Developer Platform Scopes

LinkedIn divides its API capabilities into Products that require approval in the developer dashboard.

#### 1. Products Required
- **Share on LinkedIn**: Basic member sharing.
- **Sign In with LinkedIn using OpenID Connect**: Identity handshake.
- **Community Management API** (or Marketing Developer Platform): Required for managing Organization / Company Pages (`urn:li:organization:*`).

#### 2. Scope Matrix

| Scope Code | Name | Purpose | Required For |
|:---|:---|:---|:---|
| `openid` | OpenID Connect | Authenticates user identity | OAuth Sign-in Handshake |
| `profile` | Member Profile | Retrieves admin name and photo | Account Avatar & Display |
| `email` | Member Email | Retrieves admin email address | Account Disambiguation |
| `w_member_social` | Member Post Write | Allows publishing to personal profile feed | Personal Post Dispatch |
| `w_organization_social` | Organization Post Write | Allows publishing to TalentHub Company Page | Official Brand Posting |
| `r_organization_social` | Organization Post Read | Retrieves company post performance & metrics | Analytics & KPI Sync |
| `rw_organization_admin` | Organization Administration | Verifies that admin has managerial rights | Role & Page Verification |

#### 3. Organization Page Association Requirement
To publish to TalentHub's LinkedIn Page:
1. The developer application must be linked to the official **TalentHub** LinkedIn Page in the developer portal.
2. An organization admin of that page must approve the link request from the page's **Settings &rarr; Manage Admins &rarr; Developer Apps** section.

---

### Meta for Developers (Instagram Graph API) Scopes

Instagram publishing requires an Instagram Professional Account linked to a Facebook Page, operated within Meta Business Suite.

#### 1. Scope Matrix

| Scope Code | Purpose | Required For |
|:---|:---|:---|
| `instagram_basic` | Access Instagram Business Account info | Account discovery & ID lookup |
| `instagram_content_publish` | Create containers and publish photos, videos, and carousels | Live Post & Reel Publishing |
| `instagram_manage_comments` | Read and moderate comments on company media | Community Engagement Screen |
| `instagram_manage_insights` | Read impression, reach, and interaction statistics | Verified Analytics Telemetry |
| `pages_show_list` | Discover Facebook Pages managed by user | OAuth Account Discovery |
| `pages_read_engagement` | Verify page permissions and engagement | Organization Validation |

#### 2. App Review & Business Verification (Production Checklist)
While in **Development Mode**, the Meta app can only publish to Instagram accounts owned by or registered as Test Users in the Meta Developer portal.

For public company-wide production deployment:
1. **Business Verification**: In Meta Business Manager (`business.facebook.com`), complete Business Verification by submitting TalentHub's certificate of incorporation, business utility bill, or GST registration.
2. **App Review Submission**:
   - Provide screencast showing TalentHub's Post Composer creating and publishing a post to the connected Instagram account.
   - Explain how `instagram_content_publish` is used exclusively by authorized TalentHub employees to manage official company brand communications.
   - Link to TalentHub's Public Privacy Policy: `https://talenthubconsultant.com/privacy`.

