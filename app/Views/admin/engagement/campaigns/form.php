<?php
  $isEdit = !empty($campaign['id']);
  $formAction = $isEdit ? base_url('admin/engagement/campaigns/update/' . $campaign['id']) : base_url('admin/engagement/campaigns/store');
?>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="<?= base_url('admin/engagement/campaigns') ?>" class="hover:text-brand-primary">Email Campaigns</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold"><?= $isEdit ? 'Edit Campaign' : 'Compose Campaign' ?></span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight"><?= $isEdit ? 'Edit Email Campaign' : 'Compose Email Campaign' ?></h1>
    </div>
    <a href="<?= base_url('admin/engagement/campaigns') ?>" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
      &larr; Back to Campaigns
    </a>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Column -->
    <div class="lg:col-span-2">
      <form method="POST" action="<?= $formAction ?>" class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <?= csrf_field() ?>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Title (Internal Reference) <span class="text-red-500">*</span></label>
          <input type="text" name="title" required value="<?= esc($campaign['title'] ?? '') ?>" placeholder="e.g. Q4 Executive Recruitment Intelligence Issue #12" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Type</label>
            <select name="campaign_type" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
              <option value="newsletter" <?= ($campaign['campaign_type'] ?? '') === 'newsletter' ? 'selected' : '' ?>>Newsletter</option>
              <option value="job_announcement" <?= ($campaign['campaign_type'] ?? '') === 'job_announcement' ? 'selected' : '' ?>>Job Announcement</option>
              <option value="recruitment_update" <?= ($campaign['campaign_type'] ?? '') === 'recruitment_update' ? 'selected' : '' ?>>Recruitment Update</option>
              <option value="service_launch" <?= ($campaign['campaign_type'] ?? '') === 'service_launch' ? 'selected' : '' ?>>Service Launch</option>
              <option value="campaign_announcement" <?= ($campaign['campaign_type'] ?? '') === 'campaign_announcement' ? 'selected' : '' ?>>Promotional Campaign</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Audience Filter</label>
            <select name="audience_filter" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
              <option value="all" <?= ($campaign['audience_filter'] ?? '') === 'all' ? 'selected' : '' ?>>All Verified Subscribers</option>
              <option value="engineering" <?= ($campaign['audience_filter'] ?? '') === 'engineering' ? 'selected' : '' ?>>Engineering &amp; Manufacturing</option>
              <option value="it_tech" <?= ($campaign['audience_filter'] ?? '') === 'it_tech' ? 'selected' : '' ?>>IT &amp; Techcorridor</option>
              <option value="leadership" <?= ($campaign['audience_filter'] ?? '') === 'leadership' ? 'selected' : '' ?>>Leadership &amp; Executives</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Email Subject Line <span class="text-red-500">*</span></label>
          <input type="text" name="subject" required value="<?= esc($campaign['subject'] ?? '') ?>" placeholder="e.g. Pune Executive Hiring: 50+ Senior Mandates Open in Hinjawadi & Chakan" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Preview Text / Preheader</label>
          <input type="text" name="preview_text" value="<?= esc($campaign['preview_text'] ?? '') ?>" placeholder="Short teaser shown in inbox lists..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-bold text-slate-700">HTML Body Content <span class="text-red-500">*</span></label>
            <span class="text-[11px] text-slate-400">Available variables: <code class="text-brand-primary">{{subscriber_name}}</code>, <code class="text-brand-primary">{{unsubscribe_url}}</code></span>
          </div>
          <textarea name="body_html" rows="12" required class="w-full font-mono text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3"><?= esc($campaign['body_html'] ?? '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#ffffff;"><div style="text-align:center;margin-bottom:24px;"><h1 style="color:#072412;margin:0;font-size:22px;">TalentHub Consultant</h1><p style="color:#1D6A34;font-size:13px;margin:4px 0 0 0;">Pune Recruitment Intelligence</p></div><h2 style="color:#1e293b;font-size:18px;">Hello {{subscriber_name}},</h2><p style="color:#475569;font-size:14px;line-height:1.6;">Here is your recruitment intelligence update for Pune industrial and IT corridors...</p><hr style="border:none;border-top:1px solid #f1f5f9;margin:24px 0;"><p style="color:#94a3b8;font-size:11px;text-align:center;">TalentHub Consultant — Katraj, Pune 411046, Maharashtra, India.<br><a href="{{unsubscribe_url}}" style="color:#64748b;text-decoration:underline;">Unsubscribe</a></p></div>') ?></textarea>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
          <button type="submit" class="py-2.5 px-5 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover shadow-md shadow-brand-primary/20 transition">
            <?= $isEdit ? 'Save Changes' : 'Save as Draft' ?>
          </button>
          <a href="<?= base_url('admin/engagement/campaigns') ?>" class="py-2.5 px-4 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
            Cancel
          </a>
        </div>
      </form>
    </div>

    <!-- Right Sidebar: Test Email & Verification Guidelines -->
    <div class="space-y-6">

      <?php if ($isEdit): ?>
        <!-- Test Email Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
            <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Send Test Email</h2>
          </div>
          <p class="text-[11px] text-slate-500">Dispatch an immediate test dispatch to inspect formatting, images, and mobile client rendering before broadcasting.</p>

          <form method="POST" action="<?= base_url('admin/engagement/campaigns/test/' . $campaign['id']) ?>" class="space-y-3">
            <?= csrf_field() ?>
            <input type="email" name="test_email" required placeholder="your.email@example.com" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-2.5">
            <button type="submit" class="w-full py-2.5 rounded-xl border border-brand-primary text-brand-primary font-bold text-xs hover:bg-brand-primary hover:text-white transition">
              Send Test Dispatch
            </button>
          </form>
        </div>
      <?php endif; ?>

      <!-- Compliance Guidelines Card -->
      <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-sm space-y-3">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-brand-accent">Delivery &amp; Suppression Notice</h3>
        <ul class="text-[11px] text-slate-300 space-y-2 leading-relaxed">
          <li class="flex items-start gap-2">
            <span class="text-brand-accent font-bold">&check;</span>
            <span>Only double opt-in verified subscribers receive marketing broadcasts.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-brand-accent font-bold">&check;</span>
            <span>Every marketing dispatch automatically includes a 1-click unsubscribe URL.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-brand-accent font-bold">&check;</span>
            <span>Unsubscribed users are strictly excluded by the database queue builder.</span>
          </li>
        </ul>
      </div>

    </div>

  </div>

</div>
