# TalentHub — Social Media Marketing Administrator User Guide
## Daily Workflows, Content Creation, Scheduling, and Lead Pipeline Management

---

### Navigating the Marketing Centre

Authorized TalentHub administrators can access the Social Media Marketing Centre directly from the primary Admin Sidebar under the **Social Media Marketing** accordion menu.

The module provides 15 dedicated workspaces:

1. **Marketing Overview** (`/admin/social/overview`): Executive telemetry, publishing velocity charts, recent posts, and quick-action shortcuts.
2. **Create Post** (`/admin/social/posts/create`): The Post Creation Studio with dual live feed previews and AI Assistant.
3. **All Posts** (`/admin/social/posts`): Comprehensive inventory of published, scheduled, draft, and failed content.
4. **Content Calendar** (`/admin/social/calendar`): Monthly interactive grid for visualizing publishing cadences and campaign themes.
5. **Scheduled Posts** (`/admin/social/scheduled`): Filtered view of upcoming automated dispatches.
6. **Drafts** (`/admin/social/drafts`): Work-in-progress content pending review or asset finalization.
7. **Publishing Queue** (`/admin/social/queue`): Real-time monitor of the asynchronous database queue and worker heartbeat.
8. **Media Library** (`/admin/social/media`): Centralized brand asset management for graphics, photography, videos, and PDFs.
9. **Campaigns** (`/admin/social/campaigns`): Thematic marketing groupings (e.g., WorkSphere HRMS Launch, Leadership Hiring Q2).
10. **Social Enquiries** (`/admin/social/enquiries`): Lead capture, follow-up status, and customer notes.
11. **Comments & Engagement** (`/admin/social/engagement`): Review public feedback and inbound candidate engagement.
12. **Analytics & Reports** (`/admin/social/analytics`): Audited performance metrics, formula documentation, and CSV data export.
13. **Social Accounts** (`/admin/social/accounts`): Connected LinkedIn and Instagram profile management and OAuth health.
14. **Integration Settings** (`/admin/social/settings`): Developer API credentials, redirect URIs, and security validator.
15. **Activity Logs** (`/admin/social/logs`): Immutable audit trail of administrative operations and publishing attempts.

---

### How to Create & Publish a Post

#### Step 1: Open the Creation Studio
Click **Create Post** in the sidebar or click the "+ Create Post" button on the Overview dashboard.

#### Step 2: Choose Target Platforms
Toggle **LinkedIn**, **Instagram**, or **Both**. The preview pane on the right adapts dynamically.

#### Step 3: Compose Content or Leverage AI Assistant
- Enter a title for internal administrative identification.
- Type your core message in the caption box. Character counters update in real-time with visual color alerts if you exceed platform limits (LinkedIn 3,000 chars, Instagram 2,200 chars).
- **Using the AI Assistant**:
  1. Click **AI Content Assistant** at the top right of the composer.
  2. Select your category (e.g. *Recruitment & Hiring*, *IT Services Spotlight*, *WorkSphere HRMS*).
  3. Pick a tone (e.g. *Thought Leadership*, *Professional*, *Urgent Hiring*).
  4. Type a short topic keyword (e.g., *DevOps Engineers in Pune*).
  5. Click **Generate Suggestions**. Choose any suggestion to automatically populate your editor with pre-formatted hashtags and CTAs.

#### Step 4: Attach Media
- Drag and drop files onto the media dropzone or click **Select Files**.
- Supported formats:
  - **Images**: JPEG, PNG, WebP (up to 10 MB per file).
  - **Videos**: MP4, MOV (up to 100 MB).
  - **Documents (LinkedIn)**: PDF (up to 20 MB).
- Rearrange media order or remove unwanted attachments using the quick delete button.

#### Step 5: Choose Publishing Action
- **Publish Now**: Immediately queues the post and initiates background API dispatch.
- **Schedule**: Pick a future date and time in your local timezone (IST).
- **Save Draft**: Saves work without scheduling.

---

### Using the Content Calendar

The **Content Marketing Calendar** provides a visual calendar view of your posting schedule:
- **Green pills**: Successfully published posts.
- **Amber pills**: Scheduled posts queued for future dates.
- **Blue pills**: Drafts.
- Click any post pill to open its detail modal, view full caption copy, inspect attached media, or reschedule the post.

---

### Managing Leads & Inbound Inquiries

When prospects reply or message expressing hiring needs or product interest, staff can register them in **Social Enquiries**:
1. Navigate to `/admin/social/enquiries`.
2. Click **Record Lead / Enquiry**.
3. Select the platform origin (**LinkedIn** or **Instagram**).
4. Enter candidate/client contact info, company name, requirement, and estimated deal value.
5. Update statuses through the lifecycle:
   - **New** &rarr; Newly recorded lead.
   - **Contacted** &rarr; Staff has sent an initial email or phone response.
   - **Qualified** &rarr; Verified budget and recruitment criteria.
   - **Converted** &rarr; Client contract signed or candidate placed.
   - **Archived** &rarr; Unqualified or unresponsive lead.
6. Export the pipeline anytime using the **Export CSV** button.

---

### Reviewing Authentic Analytics

Open `/admin/social/analytics` to analyze marketing performance:
- Filter data by **Today**, **Last 7 Days**, **Last 30 Days**, **Last 90 Days**, or **All Time**.
- Filter by platform (**All**, **LinkedIn**, or **Instagram**).
- Transparent documentation displays the source of every number:
  - **Platform API**: Direct, verified metrics pulled from LinkedIn and Meta endpoints.
  - **Locally Calculated**: In-house engagement rates computed using standardized industry formulas:
    $$\text{Engagement Rate} = \frac{\text{Interactions}}{\text{Impressions}} \times 100$$
  - **Pending Token**: Clearly indicates metrics that require live API permissions rather than showing synthetic or fabricated numbers.

