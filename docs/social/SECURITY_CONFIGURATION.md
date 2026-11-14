# TalentHub — Social Marketing Module Security Specification
## Cryptographic Token Storage, Key Rotation, File Upload Defense, and Access Control

---

### Security Architecture Principles

The Social Media Marketing Centre is engineered according to Defense-in-Depth and Zero-Trust principles:
1. **Zero Secret Exposure**: Tokens, secrets, and raw credentials are never sent to browser JavaScript, never logged in plain text, and never stored unencrypted in the database.
2. **Strict MIME & Magic Byte Validation**: File uploads are verified using both operating system file headers and PHP `finfo` byte inspection to prevent PHP web shell attacks.
3. **Role-Based Access Control (RBAC)**: Fine-grained permissions gate administrative capabilities.

---

### Cryptographic Token Encryption at Rest

All external API tokens (`access_token`, `refresh_token`, `client_secret`, and `webhook_secret`) are encrypted prior to database insertion using CodeIgniter 4's `EncrypterInterface`.

- **Cipher**: `AES-256-CTR`
- **Digest Algorithm**: `SHA512`
- **Key Source**: Configured via `encryption.key` in `.env`.
- **Implementation Class**: `App\Services\Social\SocialSecurityService`.

#### Masking Standard for Administrator UI:
Whenever an administrator views integration settings or connected accounts, credentials are sanitized and masked:
- Raw Client Secret: `sec_live_9f8a310c4d8721bf`
- Masked Output: `••••••••••••21bf` (Displays only the last 4 characters for verification, with bullet prefixes).

#### Key Rotation Procedure:
If your server's primary encryption key is rotated:
1. Generate a new 256-bit key:
   ```bash
   php spark key:generate --force
   ```
2. Run an administrative batch re-encryption script or disconnect and re-authorize connected profiles via `/admin/social/accounts`.

---

### OAuth 2.0 PKCE & CSRF Defense

The OAuth handshake between TalentHub and external social providers is protected against Cross-Site Request Forgery (CSRF) and State Tampering:

1. When an admin initiates account connection, `SocialOAuthController::connect()` generates a cryptographically random 64-character token using `bin2hex(random_bytes(32))`.
2. The state token is stored in the database table `social_oauth_states` with:
   - `user_id`: The ID of the authenticated administrator initiating the flow.
   - `platform`: `linkedin` or `instagram`.
   - `expires_at`: A strict **15-minute Time-To-Live (TTL)**.
3. Upon callback, `SocialSecurityService::verifyOAuthState()` validates that the returned state exists, has not expired, matches the initiating admin, and has not been used previously.
4. Used state tokens are immediately invalidated (`used = 1`).

---

### File Upload Hardening & Magic Byte Inspection

Attackers may attempt to upload malicious PHP or executable files renamed with `.jpg` or `.png` extensions.

`SocialMediaLibraryService` mitigates this through a three-stage verification process:

```
 Incoming File
       │
       ▼
 [ Stage 1: Extension Check ] ──(Invalid)──> [ Reject immediately ]
       │ (jpg, jpeg, png, webp, mp4, mov, pdf)
       ▼
 [ Stage 2: MIME Inspection ] ──(Invalid)──> [ Reject immediately ]
       │ (image/jpeg, image/png, image/webp, video/mp4, application/pdf)
       ▼
 [ Stage 3: Magic Bytes ] ────(Invalid)──> [ Reject immediately ]
       │ (Inspect raw hex signature)
       ▼
 [ Stage 4: Unique Hash Storage ]
   Target: public/uploads/social/post_{hash}.{ext}
   Permissions: 0644 (No execution privileges)
```

#### Magic Byte Signatures Enforced:
- **JPEG**: `FF D8 FF`
- **PNG**: `89 50 4E 47 0D 0A 1A 0A`
- **GIF**: `47 49 46 38`
- **PDF**: `25 50 44 46` (`%PDF`)
- **WebP**: `52 49 46 46` (`RIFF`) + `57 45 42 50` (`WEBP`)
- **MP4**: `66 74 79 70` (`ftyp`)

---

### Granular Permission Matrix (RBAC)

The Social Media Marketing Centre introduces 6 fine-grained permissions:

| Permission Code | Display Name | Recommended Roles | Description |
|-----------------|--------------|-------------------|-------------|
| `social.view` | View Social Overview & Content | Super Admin, HR Manager, Marketing Specialist | Allows browsing published posts, calendar, and overview dashboard |
| `social.create` | Create & Edit Social Posts | Super Admin, Marketing Specialist | Allows composing new posts, saving drafts, and using AI Assistant |
| `social.publish` | Publish & Schedule Posts | Super Admin, Marketing Lead | Allows scheduling posts and executing immediate dispatches |
| `social.manage` | Manage Media & Campaigns | Super Admin, Marketing Lead | Allows creating organic campaigns and uploading/deleting media assets |
| `social.enquiries`| Manage Social Leads | Super Admin, HR Manager, Sales Lead | Allows viewing, updating status, and exporting captured leads |
| `social.analytics`| View Analytics & Exports | Super Admin, Marketing Lead | Access to analytics reports and CSV telemetry exports |

