# TalentHub — Social Media API Setup & Configuration Guide
## Developer Portal Registration, OAuth Handshake, and Credential Management

---

### Prerequisites & URL Directory

Before setting up API applications on LinkedIn Developer Portal or Meta for Developers, ensure you know the target URLs for both staging and production:

| Environment | Base URL | Redirect URI (LinkedIn) | Redirect URI (Instagram) |
|-------------|----------|-------------------------|--------------------------|
| **Production** | `https://talenthubconsultant.com` | `https://talenthubconsultant.com/admin/social/oauth/callback/linkedin` | `https://talenthubconsultant.com/admin/social/oauth/callback/instagram` |
| **Staging** | `https://staging.talenthubconsultant.com` | `https://staging.talenthubconsultant.com/admin/social/oauth/callback/linkedin` | `https://staging.talenthubconsultant.com/admin/social/oauth/callback/instagram` |
| **Local Dev** | `http://localhost:8080` | `http://localhost:8080/admin/social/oauth/callback/linkedin` | `http://localhost:8080/admin/social/oauth/callback/instagram` |

---

### Configuration Strategy: UI vs. Environment Variables

TalentHub features a flexible dual-tier configuration system:

1. **Database Tier (Recommended for Admin UI)**:
   Navigate to **Admin Dashboard &rarr; Social Media Marketing &rarr; Integration Settings** (`/admin/social/settings`). Authorized Super Administrators can paste Client IDs, Client Secrets, and Webhook verification tokens directly into the encrypted form. All secrets are immediately encrypted using AES-256-CTR before touching the database.

2. **Environment Variable Tier (Recommended for CI/CD & Production Hardening)**:
   You may define credentials in your server's `.env` file. Database entries override `.env` entries if explicitly configured via UI:
   ```env
   # LinkedIn Developer App
   LINKEDIN_CLIENT_ID=your_linkedin_client_id_here
   LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret_here
   LINKEDIN_ORGANIZATION_URN=urn:li:organization:12345678

   # Meta / Instagram Graph API App
   INSTAGRAM_APP_ID=your_facebook_app_id_here
   INSTAGRAM_APP_SECRET=your_facebook_app_secret_here
   INSTAGRAM_ACCOUNT_ID=your_instagram_business_account_id_here
   ```

---

### Step-by-Step LinkedIn Application Setup

1. **Access Portal**:
   - Log into the [LinkedIn Developer Portal](https://www.linkedin.com/developers/) using your organization's primary admin LinkedIn account.
2. **Create New Application**:
   - Click **Create App**.
   - **App Name**: `TalentHub Marketing Centre`
   - **LinkedIn Page**: Link to `https://www.linkedin.com/company/talenthub-consultant/` (You must be an admin of the Company Page to complete association).
   - **Privacy Policy URL**: `https://talenthubconsultant.com/privacy`
   - **App Logo**: Upload TalentHub's square icon (300x300 PNG).
3. **Request Product Products & Permissions**:
   Under the **Products** tab, request access to:
   - **Share on LinkedIn** (enables member posting: `openid`, `profile`, `w_member_social`).
   - **Community Management API** (enables Company Page posting: `w_organization_social`, `r_organization_social`, `rw_organization_admin`).
4. **Configure OAuth 2.0 Settings**:
   - Navigate to the **Auth** tab.
   - Note the **Client ID** and **Client Secret**.
   - Under **Authorized redirect URLs for your app**, click the plus icon and add:
     - `https://talenthubconsultant.com/admin/social/oauth/callback/linkedin`
     - `https://staging.talenthubconsultant.com/admin/social/oauth/callback/linkedin`
5. **Connect via TalentHub**:
   - Save credentials in TalentHub under `/admin/social/settings`.
   - Go to `/admin/social/accounts` and click **Connect LinkedIn Account**. Complete the authorization prompt.

---

### Step-by-Step Meta (Instagram) Application Setup

Instagram publishing requires an **Instagram Professional (Business or Creator) Account** connected to an active **Facebook Page**.

1. **Verify Instagram & Facebook Association**:
   - In Instagram mobile app or web: Settings &rarr; Account Type &rarr; Switch to Professional Account (Business).
   - Link the Instagram Business profile to the official **TalentHub Facebook Page**.
2. **Access Meta for Developers**:
   - Navigate to [Meta for Developers](https://developers.facebook.com/) and register as a developer.
3. **Create App**:
   - Click **Create App**.
   - **Use Case**: Select **Other** &rarr; **Business**.
   - **App Display Name**: `TalentHub Social Centre`
   - **Contact Email**: `admin@talenthubconsultant.com`
4. **Add Instagram & Facebook Login Products**:
   - Add **Instagram Graph API**.
   - Add **Facebook Login for Business**.
5. **Configure OAuth Settings**:
   - Under **Facebook Login &rarr; Settings &rarr; Valid OAuth Redirect URIs**, enter:
     - `https://talenthubconsultant.com/admin/social/oauth/callback/instagram`
     - `https://staging.talenthubconsultant.com/admin/social/oauth/callback/instagram`
6. **Required Scopes for Content Publishing**:
   - `instagram_basic`
   - `instagram_content_publish`
   - `instagram_manage_comments`
   - `instagram_manage_insights`
   - `pages_show_list`
   - `pages_read_engagement`
7. **Exchange for Long-Lived Token**:
   - TalentHub's `SocialOAuthController` automatically takes the short-lived access token returned by OAuth and invokes Meta's `oauth/access_token?grant_type=fb_exchange_token` endpoint to upgrade it to a **60-day long-lived token**.
8. **Token Expiry Alerting**:
   - The system tracks token expiration timestamps in the `social_accounts` table. When a token has fewer than 10 days remaining, an in-app banner will appear in the Admin Dashboard prompting an administrator to re-authorize.

---

### System Compliance Verification

Once credentials are saved, open `/admin/social/settings`. The **System Health & Diagnostic Monitor** will immediately test:
- **AES-256 Key Status**: Confirms encryption key is valid and configured.
- **HTTPS Enforcement**: Verifies production traffic runs over secure TLS.
- **Directory Permissions**: Checks write permissions for `public/uploads/social/`.
- **API App Configurations**: Validates length and format of Client IDs and Secrets.
- **Scheduler Heartbeat**: Verifies that the publishing cron worker has executed within expected intervals.

