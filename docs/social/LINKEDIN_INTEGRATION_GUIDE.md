# TalentHub — LinkedIn API Integration Manual
## Official Rest Posts API, Organization Targeting, and Media Publishing

---

### API Architecture & Endpoint Specifications

TalentHub integrates directly with LinkedIn's current **Versioned REST API** using the `LinkedIn-Version: 202401` (or latest supported date version) and `X-Restli-Protocol-Version: 2.0.0` protocol standards.

All requests are executed via `App\Services\Social\LinkedInApiService`.

#### Key API Endpoints
- **Authorization Code**: `https://www.linkedin.com/oauth/v2/authorization`
- **Access Token Exchange**: `https://www.linkedin.com/oauth/v2/accessToken`
- **Member Profile (OpenID)**: `https://api.linkedin.com/v2/userinfo`
- **Organization ACL Verification**: `https://api.linkedin.com/v2/organizationalEntityAcls?q=roleAssignee`
- **Media Upload Registration**: `https://api.linkedin.com/rest/images?action=initializeUpload` & `https://api.linkedin.com/rest/videos?action=initializeUpload`
- **Posts API**: `https://api.linkedin.com/rest/posts`
- **Organizational Share Statistics**: `https://api.linkedin.com/rest/organizationalEntityShareStatistics?q=organizationalEntity`

---

### Supported Media Types & Payload Structures

TalentHub supports four distinct post formats on LinkedIn:

#### 1. Plain Text Post (Thought Leadership & Job Openings)
```json
{
  "author": "urn:li:organization:12345678",
  "commentary": "We are excited to announce new senior engineering opportunities at TalentHub! #Hiring #TechJobs",
  "visibility": "PUBLIC",
  "distribution": {
    "feedDistribution": "MAIN_FEED",
    "targetEntities": [],
    "thirdPartyDistributionChannels": []
  },
  "lifecycleState": "PUBLISHED",
  "isReshareDisabledByAuthor": false
}
```

#### 2. Single Image & Multi-Image Gallery
- **Initialize Upload**: Request upload URL and asset URN from `/rest/images?action=initializeUpload`.
- **Binary Upload**: Upload raw image buffer using HTTP PUT to the signed upload URL.
- **Post Dispatch**: Reference the uploaded URN(s):
```json
{
  "author": "urn:li:organization:12345678",
  "commentary": "Celebrating 100+ successful leadership placements in Q1!",
  "visibility": "PUBLIC",
  "distribution": { "feedDistribution": "MAIN_FEED" },
  "content": {
    "multiImage": {
      "images": [
        { "id": "urn:li:image:D5605AQE..." },
        { "id": "urn:li:image:D5605AQF..." }
      ]
    }
  },
  "lifecycleState": "PUBLISHED"
}
```

#### 3. Native Video (Recruitment Video & Product Demos)
- Videos are initialized using `/rest/videos?action=initializeUpload` specifying `videoPurpose: USER_GENERATED_CONTENT`.
- Chunked or single-part binary upload is performed to LinkedIn's media CDN.
- Post references `"content": { "media": { "id": "urn:li:video:..." } }`.

#### 4. PDF Document Sharing (Whitepapers, HR Salary Guides & Carousels)
- Uploaded via LinkedIn's document upload protocol.
- Renders as a swipeable, paginated document reader directly within LinkedIn feeds.

---

### Character Limits & Best Practices

| Attribute | LinkedIn Limit | TalentHub Recommended Standard |
|-----------|----------------|--------------------------------|
| **Post Commentary** | 3,000 characters | 800 - 1,500 characters for optimal dwell time |
| **Hashtags** | No strict limit | 3 - 5 highly relevant industry tags |
| **Image Resolution** | Up to 7680 × 4320 px | 1200 × 627 px (landscape) or 1080 × 1080 px (square) |
| **Image File Size** | 10 MB per image | Under 5 MB (WebP/JPG) for fast feed rendering |
| **Video Duration** | 3 seconds to 10 minutes | 30 to 90 seconds for corporate engagement |
| **PDF File Size** | 100 MB | Under 15 MB for smooth mobile swiping |

---

### Rate Limits & Error Handling

LinkedIn enforces application-level and organization-level rate limits based on a 24-hour sliding window.

TalentHub's `SocialPublishingEngine` categorizes LinkedIn API HTTP responses:

- **201 Created**: Post successfully published. TalentHub parses the `x-restli-id` response header to extract the canonical post URN.
- **429 Too Many Requests**: Rate limit reached. The job is placed back into the `social_scheduled_jobs` queue with an exponential backoff retry (delayed by 15-30 minutes).
- **401 Unauthorized**: Access token revoked, invalidated, or expired. TalentHub marks the account status as `expired` and triggers an urgent administrative notification.
- **422 Unprocessable Content / Duplicate Post**: Content was posted recently with identical text. TalentHub flags the post as failed with descriptive UI messaging.
- **500 / 503 Service Unavailable**: LinkedIn internal issue. Classified as transient and retried up to 3 times before terminal failure.

