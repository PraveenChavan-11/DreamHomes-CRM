# TalentHub — Enterprise Social Media Marketing & Management Centre
## Production Architecture & Operational Manual

---

### Executive Overview

The **TalentHub Social Media Marketing & Management Centre** is an enterprise-grade extension built natively into the TalentHub platform (CodeIgniter 4.7+, PHP 8.2+, MariaDB/MySQL). It empowers TalentHub's talent consultants, IT staffing directors, and marketing teams to author, schedule, publish, monitor, and analyze content across **LinkedIn** and **Instagram** without requiring expensive monthly SaaS subscriptions (e.g. Hootsuite, Sprout Social, Buffer).

```
   ┌────────────────────────────────────────────────────────────────────────┐
   │                       TalentHub Admin Dashboard                        │
   │   (Overview, Studio, Calendar, Queue, Media, Campaigns, Analytics)     │
   └───────────────────────────────────┬────────────────────────────────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    ▼                                     ▼
        ┌───────────────────────┐             ┌───────────────────────┐
        │  Content Studio (UI)  │             │  Scheduler & Cron     │
        │  • Live Simulators    │             │  • Non-blocking lock  │
        │  • AI Content Assist  │             │  • Exponential retry  │
        │  • Media Validation   │             │  • php spark social   │
        └───────────┬───────────┘             └───────────┬───────────┘
                    │                                     │
                    └──────────────────┬──────────────────┘
                                       ▼
                       ┌───────────────────────────────┐
                       │   SocialPublishingEngine      │
                       │   • Idempotency enforcement   │
                       │   • Payload sanitization      │
                       │   • Audit log dispatch        │
                       └───────────────┬───────────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    ▼                                     ▼
      ┌───────────────────────────┐         ┌───────────────────────────┐
      │    LinkedInApiService     │         │    InstagramApiService    │
      │  • OAuth 2.0 PKCE         │         │  • Meta Graph API v21.0   │
      │  • Rest Posts API         │         │  • Container -> Publish   │
      │  • Single/Multi/Doc Share │         │  • Reels, Feed, Carousel  │
      └───────────────────────────┘         └───────────────────────────┘
```

---

### Key Capabilities

1. **Multi-Platform Content Authoring**:
   - Single unified composer with live visual mockups for both LinkedIn (desktop feed) and Instagram (mobile card with carousel indicators).
   - Platform-specific caption adaptation with strict character count limits (LinkedIn 3,000 chars, Instagram 2,200 chars, 30 hashtags max).
   - Document sharing (PDF carousels and whitepapers for LinkedIn).

2. **Offline AI Content Assistant**:
   - Algorithmic content generator covering 15 talent acquisition & IT service topics (recruitment mandates, employer branding, tech stack spotlights, WorkSphere HRMS announcements, client case studies).
   - 7 distinct tone profiles: Professional, Urgent, Thought Leadership, Conversational, Technical, Celebratory, Data-Driven.
   - Smart hashtag clusters and conversion-focused call-to-actions.

3. **Autonomous Scheduling & Queue Engine**:
   - Local non-blocking file locking (`social_scheduler.lock`) for high-concurrency environments.
   - Atomic job state transitions (`pending` -> `processing` -> `published`/`failed`).
   - Exponential backoff retry policies (2m, 4m, 8m) for transient API or network rate limits.
   - Hostinger shared hosting cron-ready via `php spark social:publish`.

4. **Zero-Trust Security & Token Protection**:
   - AES-256-CTR cryptographic token encryption at rest using CI4 `EncrypterInterface`.
   - Complete masking of developer secrets (`••••4a9f`) in UI, templates, logs, and database backups.
   - Strict file validation: MIME detection, magic byte verification, file extension whitelist. Max sizes: Images (10MB), Videos (100MB), Documents (20MB).

5. **Integrated Lead & Enquiry Attribution**:
   - Auto-capture and tracking of inbound social leads originating from campaigns.
   - Automatic deduplication by phone and email.
   - Pipeline stages (`new` -> `contacted` -> `qualified` -> `converted` -> `archived`).

6. **Authentic Analytics (Zero Metric Fabrication)**:
   - Clear distinction between API-verified platform statistics, locally calculated figures, and metrics currently pending live API token connection.
   - CSV export capabilities for stakeholder reporting.

---

### Database Architecture

The module utilizes 17 normalized tables:

| Table Name | Purpose |
|------------|---------|
| `social_accounts` | Connected LinkedIn Organization and Instagram Business profiles |
| `social_credentials` | Encrypted API keys, Client IDs, Secrets, and Webhook tokens |
| `social_oauth_states` | CSRF-protected transient OAuth handshakes with 15-minute TTL |
| `social_campaigns` | Strategic organic marketing campaigns (WorkSphere launch, Tech Hiring) |
| `social_posts` | Master post content, category, schedule date, and approval status |
| `social_post_platforms` | Platform-specific adaptations, status, external post IDs, and metrics |
| `social_media_library` | Uploaded assets (images, videos, PDFs) with dimensions and MIME checks |
| `social_post_media` | Many-to-many relationship mapping media items to posts with display order |
| `social_scheduled_jobs` | Asynchronous queue with atomic locking, retry counters, and error states |
| `social_publish_attempts` | Comprehensive audit trail of every HTTP dispatch to social APIs |
| `social_content_templates`| Reusable post templates, seasonal themes, and canned replies |
| `social_campaign_posts` | Attribution link between campaigns and individual posts |
| `social_analytics_snapshots` | Historical timeline metrics for reach, impressions, and engagement |
| `social_enquiries` | Captured leads and prospective client inquiries with attribution |
| `social_comments` | Moderation queue for inbound social interactions |
| `social_notifications` | In-app alerts for published posts, failures, and token expiries |
| `social_activity_logs` | Immutable audit log of all administrator actions |

---

### File & Directory Structure

```
app/
├── Commands/
│   └── SocialPublishCommand.php           # CLI spark social:publish cron runner
├── Controllers/
│   └── Admin/
│       ├── SocialMarketingController.php  # Main UI & AJAX endpoints
│       └── SocialOAuthController.php      # OAuth connect, callbacks & disconnect
├── Database/
│   └── Migrations/
│       └── 20261110000001_create_social_marketing_tables.php # Full schema definition
├── Services/
│   └── Social/
│       ├── InstagramApiService.php        # Meta Graph API publishing & container status
│       ├── LinkedInApiService.php         # LinkedIn Posts & Organization API
│       ├── SocialAnalyticsService.php     # Audited KPIs, trends & CSV exports
│       ├── SocialContentAssistantService.php # Offline AI caption generator & rewriter
│       ├── SocialEnquiryService.php       # Lead capture & status transitions
│       ├── SocialMediaLibraryService.php  # Secure upload, thumbnail & MIME checking
│       ├── SocialPublishingEngine.php     # Validation, idempotency & API dispatch
│       ├── SocialSchedulerService.php     # Queue runner & exponential retries
│       ├── SocialSecurityService.php      # Token encryption & secret masking
│       └── SocialSettingsService.php      # Health check & credential configuration
└── Views/
    └── admin/
        └── social/
            ├── accounts/index.php         # Connected profiles & token lifespans
            ├── analytics/index.php        # Verified KPIs, charts & export
            ├── calendar.php               # Visual monthly content calendar
            ├── campaigns/
            │   ├── form.php               # Create/edit campaign
            │   └── index.php              # Campaign listings
            ├── comments/index.php         # Moderation & reply center
            ├── enquiries/index.php        # Social lead pipeline & manual add
            ├── logs/index.php             # Security audit logs
            ├── media/index.php            # Asset gallery with drag-and-drop
            ├── overview.php               # Executive KPI dashboard & quick actions
            ├── posts/
            │   ├── create.php             # Multi-platform studio & live simulators
            │   ├── index.php              # Content inventory & filters
            │   └── show.php               # Post detail, stats & attempt logs
            ├── queue.php                  # Live scheduler status & job retry
            └── settings/index.php         # Developer credentials & compliance health
```

