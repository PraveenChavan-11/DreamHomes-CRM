<?php
  $isEdit = !empty($announcement['id']);
  $formAction = $isEdit ? base_url('admin/engagement/announcements/update/' . $announcement['id']) : base_url('admin/engagement/announcements/store');
?>

<div class="space-y-6">

  <!-- Breadcrumb & Header -->
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="<?= base_url('admin/engagement/announcements') ?>" class="hover:text-brand-primary">Announcements</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold"><?= $isEdit ? 'Edit Campaign' : 'Create Campaign' ?></span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight"><?= $isEdit ? 'Edit Announcement Campaign' : 'Create New Campaign' ?></h1>
    </div>
    <a href="<?= base_url('admin/engagement/announcements') ?>" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
      &larr; Back to List
    </a>
  </div>

  <form method="POST" action="<?= $formAction ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= csrf_field() ?>

    <!-- Left 2 Cols: Content & Targeting -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Basic Campaign Info -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 pb-2 border-b border-slate-100">1. Campaign Identification &amp; Content</h2>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Title (Internal Reference) <span class="text-red-500">*</span></label>
          <input type="text" name="title" required value="<?= esc($announcement['title'] ?? '') ?>" placeholder="e.g. Q4 Hinjawadi Tech Drive 2026" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Internal Description / Campaign Objective</label>
          <input type="text" name="internal_description" value="<?= esc($announcement['internal_description'] ?? '') ?>" placeholder="e.g. Attract senior Java & cloud architects" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div class="pt-2">
          <label class="block text-xs font-bold text-slate-700 mb-1">Public Display Heading <span class="text-red-500">*</span></label>
          <input type="text" id="input-heading" name="public_heading" required value="<?= esc($announcement['public_heading'] ?? '') ?>" placeholder="e.g. Senior IT & Automotive Mandates Open Across Pune" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Public Content / Body Text <span class="text-red-500">*</span></label>
          <textarea id="input-content" name="public_content" required rows="4" placeholder="Enter clear, compelling announcement copy..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5"><?= esc($announcement['public_content'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Call to Action (CTA) Text</label>
            <input type="text" id="input-cta-text" name="cta_text" value="<?= esc($announcement['cta_text'] ?? 'Explore Vacancies') ?>" placeholder="e.g. View Mandates" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">CTA Destination URL</label>
            <input type="text" id="input-cta-url" name="cta_url" value="<?= esc($announcement['cta_url'] ?? '/jobs') ?>" placeholder="e.g. /jobs or /contact" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Optional Feature Image URL</label>
          <input type="text" name="image_url" value="<?= esc($announcement['image_url'] ?? '') ?>" placeholder="https://..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>
      </div>

      <!-- Targeting & Frequency -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 pb-2 border-b border-slate-100">2. Audience Targeting &amp; Frequency</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Target Audience Segment</label>
            <select name="audience" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
              <option value="everyone" <?= ($announcement['audience'] ?? '') === 'everyone' ? 'selected' : '' ?>>Everyone (Global Audience)</option>
              <option value="candidates" <?= ($announcement['audience'] ?? '') === 'candidates' ? 'selected' : '' ?>>Candidates (Job Seekers)</option>
              <option value="employers" <?= ($announcement['audience'] ?? '') === 'employers' ? 'selected' : '' ?>>Employers &amp; Corporate Clients</option>
              <option value="homepage" <?= ($announcement['audience'] ?? '') === 'homepage' ? 'selected' : '' ?>>Homepage Visitors Only</option>
              <option value="insights" <?= ($announcement['audience'] ?? '') === 'insights' ? 'selected' : '' ?>>Insights &amp; Blog Readers</option>
              <option value="selected_pages" <?= ($announcement['audience'] ?? '') === 'selected_pages' ? 'selected' : '' ?>>Specific Website Pages</option>
              <option value="new_visitors" <?= ($announcement['audience'] ?? '') === 'new_visitors' ? 'selected' : '' ?>>First-time Visitors</option>
              <option value="returning_visitors" <?= ($announcement['audience'] ?? '') === 'returning_visitors' ? 'selected' : '' ?>>Returning Visitors</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Display Frequency Rule</label>
            <select name="frequency" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
              <option value="once_per_session" <?= ($announcement['frequency'] ?? '') === 'once_per_session' ? 'selected' : '' ?>>Once per browser session (Recommended)</option>
              <option value="once_per_day" <?= ($announcement['frequency'] ?? '') === 'once_per_day' ? 'selected' : '' ?>>Once per day (24 hours)</option>
              <option value="once_7_days" <?= ($announcement['frequency'] ?? '') === 'once_7_days' ? 'selected' : '' ?>>Once every 7 days</option>
              <option value="once_30_days" <?= ($announcement['frequency'] ?? '') === 'once_30_days' ? 'selected' : '' ?>>Once every 30 days</option>
              <option value="until_dismissed" <?= ($announcement['frequency'] ?? '') === 'until_dismissed' ? 'selected' : '' ?>>Until explicitly dismissed by user</option>
              <option value="every_visit" <?= ($announcement['frequency'] ?? '') === 'every_visit' ? 'selected' : '' ?>>Every single page view</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Target Pages (Comma-separated URLs, if Specific Pages selected)</label>
          <input type="text" name="target_pages" value="<?= esc($announcement['target_pages'] ?? '') ?>" placeholder="e.g. /jobs, /solutions, /employers" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>
      </div>

    </div>

    <!-- Right Col: Schedule, Format & Status -->
    <div class="space-y-6">

      <!-- Format & Type -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 pb-2 border-b border-slate-100">3. Display Format &amp; Status</h2>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Display Format</label>
          <select id="select-format" name="display_format" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
            <option value="center_modal" <?= ($announcement['display_format'] ?? '') === 'center_modal' ? 'selected' : '' ?>>Centre Modal Popup</option>
            <option value="top_banner" <?= ($announcement['display_format'] ?? '') === 'top_banner' ? 'selected' : '' ?>>Top Announcement Banner</option>
            <option value="bottom_banner" <?= ($announcement['display_format'] ?? '') === 'bottom_banner' ? 'selected' : '' ?>>Bottom Sticky Banner</option>
            <option value="inline_homepage" <?= ($announcement['display_format'] ?? '') === 'inline_homepage' ? 'selected' : '' ?>>Homepage Inline Card</option>
            <option value="newsletter_popup" <?= ($announcement['display_format'] ?? '') === 'newsletter_popup' ? 'selected' : '' ?>>Newsletter Subscribe Popup</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Type</label>
          <select name="announcement_type" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
            <option value="recruitment_drive" <?= ($announcement['announcement_type'] ?? '') === 'recruitment_drive' ? 'selected' : '' ?>>Recruitment Drive</option>
            <option value="job_announcement" <?= ($announcement['announcement_type'] ?? '') === 'job_announcement' ? 'selected' : '' ?>>New Job Announcement</option>
            <option value="newsletter_subscription" <?= ($announcement['announcement_type'] ?? '') === 'newsletter_subscription' ? 'selected' : '' ?>>Newsletter Subscription Drive</option>
            <option value="service_launch" <?= ($announcement['announcement_type'] ?? '') === 'service_launch' ? 'selected' : '' ?>>New Service Launch</option>
            <option value="company_announcement" <?= ($announcement['announcement_type'] ?? '') === 'company_announcement' ? 'selected' : '' ?>>Company Announcement</option>
            <option value="festive_greetings" <?= ($announcement['announcement_type'] ?? '') === 'festive_greetings' ? 'selected' : '' ?>>Festive Greetings</option>
            <option value="employer_campaign" <?= ($announcement['announcement_type'] ?? '') === 'employer_campaign' ? 'selected' : '' ?>>Employer Campaign</option>
            <option value="candidate_campaign" <?= ($announcement['announcement_type'] ?? '') === 'candidate_campaign' ? 'selected' : '' ?>>Candidate Campaign</option>
            <option value="custom_promotional" <?= ($announcement['announcement_type'] ?? '') === 'custom_promotional' ? 'selected' : '' ?>>Custom Promotional</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Priority (1-100)</label>
            <input type="number" name="priority" value="<?= esc($announcement['priority'] ?? 10) ?>" min="1" max="100" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5 text-center">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Lifecycle Status</label>
            <select name="status" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
              <option value="DRAFT" <?= ($announcement['status'] ?? '') === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
              <option value="SCHEDULED" <?= ($announcement['status'] ?? '') === 'SCHEDULED' ? 'selected' : '' ?>>SCHEDULED</option>
              <option value="ACTIVE" <?= ($announcement['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
              <option value="PAUSED" <?= ($announcement['status'] ?? '') === 'PAUSED' ? 'selected' : '' ?>>PAUSED</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Schedule & Timezone -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold text-slate-900 pb-2 border-b border-slate-100">4. Schedule &amp; Timezone</h2>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Start Date <span class="text-red-500">*</span></label>
            <input type="date" name="start_date" required value="<?= esc($announcement['start_date'] ?? date('Y-m-d')) ?>" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">End Date <span class="text-red-500">*</span></label>
            <input type="date" name="end_date" required value="<?= esc($announcement['end_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Start Time</label>
            <input type="time" name="start_time" value="<?= esc($announcement['start_time'] ?? '00:00') ?>" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">End Time</label>
            <input type="time" name="end_time" value="<?= esc($announcement['end_time'] ?? '23:59') ?>" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Timezone</label>
          <input type="text" name="timezone" value="<?= esc($announcement['timezone'] ?? 'Asia/Kolkata') ?>" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="space-y-2.5">
        <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover shadow-md shadow-brand-primary/20 transition">
          <?= $isEdit ? 'Save Changes' : 'Save &amp; Publish Campaign' ?>
        </button>
        <a href="<?= base_url('admin/engagement/announcements') ?>" class="w-full py-2.5 px-4 rounded-xl border border-slate-200 bg-white text-slate-700 font-bold text-xs hover:bg-slate-50 transition block text-center">
          Cancel
        </a>
      </div>

    </div>

  </form>

</div>
