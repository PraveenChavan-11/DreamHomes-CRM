# TalentHub — Instagram Graph API Integration Manual
## Meta Graph API v21.0, Content Publishing Protocol, and Media Container Workflow

---

### Integration Architecture Overview

Publishing to Instagram through the official Meta Graph API differs fundamentally from traditional REST APIs. Direct binary uploads inside a single request are not supported for feed publishing. Instead, Meta requires a **Two-Step Containerized Publishing Flow**:

```
 Step 1: Create Container                Step 2: Check Status & Publish
 ┌───────────────────────┐               ┌───────────────────────────────┐
 │ TalentHub Server      │               │ TalentHub Server              │
 │ POST /{ig-id}/media   │               │ POST /{ig-id}/media_publish   │
 │ image_url=public_url  │               │ creation_id={container-id}    │
 └──────────┬────────────┘               └───────────────┬───────────────┘
            │                                            │
            ▼                                            ▼
 ┌───────────────────────┐               ┌───────────────────────────────┐
 │ Meta Container Created│               │ Published to Instagram Feed   │
 │ Status: IN_PROGRESS   │ ────────────> │ Status: PUBLISHED             │
 │ Returns: container_id │               │ Returns: ig_media_id          │
 └───────────────────────┘               └───────────────────────────────┘
```

All interactions are implemented in `App\Services\Social\InstagramApiService`.

---

### Step-by-Step Media Container Publishing

#### 1. Public Media URL Requirement (Critical)
Meta servers independently download media assets directly from your web server over public HTTPS.
- Therefore, image and video URLs passed to `image_url` or `video_url` **must be publicly reachable over HTTPS** (e.g., `https://talenthubconsultant.com/uploads/social/post_abc123.jpg`).
- Localhost URLs (`http://localhost...`) will fail container creation on live Meta servers unless tunneled via Cloudflare Tunnel or ngrok during local development testing.

#### 2. Publishing Single Photo
```http
POST https://graph.facebook.com/v21.0/{instagram-account-id}/media
Content-Type: application/x-www-form-urlencoded

image_url=https%3A%2F%2Ftalenthubconsultant.com%2Fuploads%2Fsocial%2Fbanner.jpg
&caption=Empowering+enterprises+with+top+tech+talent.+%23TalentHub+%23Recruitment
&access_token={long-lived-page-access-token}
```
**Response**: `{"id": "1792348572910"}` (This is the `creation_id` / Container ID).

#### 3. Publishing Instagram Reels / Video
- For video, `media_type=REELS` is supplied along with `video_url`.
- Video containers require encoding and thumbnail generation on Meta servers before they can be published.
- TalentHub polls the container status via `GET https://graph.facebook.com/v21.0/{container-id}?fields=status_code`:
  - `IN_PROGRESS`: Encoding continues; wait 5 seconds.
  - `FINISHED`: Container is ready to be published.
  - `ERROR`: Encoding failed (unsupported aspect ratio or bitrate).

#### 4. Publishing Multi-Image Carousel
Carousels support up to 10 images or videos:
1. Create individual child item containers with `is_carousel_item=true`.
2. Create the master carousel container:
   ```http
   POST /{instagram-account-id}/media
   media_type=CAROUSEL
   &caption=5+Essential+Hiring+Trends+for+2026
   &children=item_container_id_1,item_container_id_2,item_container_id_3
   ```
3. Publish the master carousel container via `/media_publish`.

#### 5. Executing the Final Publish
```http
POST https://graph.facebook.com/v21.0/{instagram-account-id}/media_publish
Content-Type: application/x-www-form-urlencoded

creation_id={master-container-id}
&access_token={long-lived-page-access-token}
```
**Response**: `{"id": "1802934812345678"}` (Permanent live Instagram post ID).

---

### Instagram Formatting & Aspect Ratio Constraints

| Attribute | Constraint | Recommendation |
|-----------|------------|----------------|
| **Caption Limit** | 2,200 characters | 500 - 1,200 characters |
| **Max Hashtags** | Strict maximum of 30 hashtags | 5 - 10 focused tags |
| **Square Aspect Ratio** | 1:1 | 1080 × 1080 px |
| **Portrait Aspect Ratio** | 4:5 | 1080 × 1350 px (highest feed screen occupancy) |
| **Landscape Aspect Ratio**| 1.91:1 | 1080 × 566 px |
| **Reels / Vertical Video**| 9:16 | 1080 × 1920 px (H.264 / AAC, 30fps) |
| **Max Video Size** | 100 MB | Keep under 50 MB for faster processing |

---

### Publishing Limits & Error Codes

Meta enforces a publishing rate limit of **50 posts per rolling 24-hour period** per Instagram Business Account.

Common Meta Graph API error codes handled by TalentHub:

| Error Subcode | Explanation | System Action |
|---------------|-------------|---------------|
| `2207001` | Aspect ratio is not supported by Instagram | Post marked as failed with helpful aspect ratio guide |
| `2207003` | Media could not be fetched from the URL | Retry once; check if web server is blocking Meta crawler |
| `2207050` | Video format or codec is not supported | Recommends MP4 container with H.264 codec |
| `190` | Access token has expired or user changed password | Status updated to `expired`; admin notification dispatched |
| `4` / `17` | Application request rate limit reached | Scheduled for automatic retry with exponential delay |

