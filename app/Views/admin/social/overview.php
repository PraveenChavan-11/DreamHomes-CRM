<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-8 pb-12">

  <!-- Header & Filter Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-light text-brand-primary border border-brand-accent/30">
          <span class="w-1.5 h-1.5 rounded-full bg-brand-accent animate-pulse"></span>
          Enterprise Social Media Marketing
        </span>
        <?php if (!empty($kpis['last_synced_at'])): ?>
          <span class="text-xs text-slate-400">Synced: <?= esc(date('M d, H:i', strtotime($kpis['last_synced_at']))) ?></span>
        <?php endif; ?>
      </div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Marketing Overview</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Centralized social media operations, content orchestration, and verified performance analytics.</p>
    </div>

    <!-- Controls -->
    <div class="flex flex-wrap items-center gap-2.5">
      <!-- Date Filter -->
      <form method="GET" action="<?= base_url('admin/social') ?>" class="flex items-center gap-2">
        <select name="range" onchange="this.form.submit()" class="text-xs font-medium bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-700 shadow-sm focus:ring-2 focus:ring-brand-accent">
          <option value="today" <?= ($kpis['date_range'] === 'today') ? 'selected' : '' ?>>Today</option>
          <option value="7_days" <?= ($kpis['date_range'] === '7_days') ? 'selected' : '' ?>>Last 7 Days</option>
          <option value="30_days" <?= ($kpis['date_range'] === '30_days') ? 'selected' : '' ?>>Last 30 Days</option>
          <option value="90_days" <?= ($kpis['date_range'] === '90_days') ? 'selected' : '' ?>>Last 90 Days</option>
          <option value="all" <?= ($kpis['date_range'] === 'all') ? 'selected' : '' ?>>All Time</option>
        </select>
        <select name="platform" onchange="this.form.submit()" class="text-xs font-medium bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-700 shadow-sm focus:ring-2 focus:ring-brand-accent">
          <option value="all" <?= ($kpis['platform_filter'] === 'all') ? 'selected' : '' ?>>All Platforms</option>
          <option value="linkedin" <?= ($kpis['platform_filter'] === 'linkedin') ? 'selected' : '' ?>>LinkedIn</option>
          <option value="instagram" <?= ($kpis['platform_filter'] === 'instagram') ? 'selected' : '' ?>>Instagram</option>
        </select>
      </form>

      <a href="<?= base_url('admin/social/posts/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-lg text-xs font-bold hover:bg-brand-hover transition shadow-sm shadow-brand-primary/20">
        <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Create Post</span>
      </a>
    </div>
  </div>

  <!-- Connected Platforms Strip -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- LinkedIn Account Card -->
    <?php
      $liAccount = null;
      $igAccount = null;
      foreach ($accounts as $acc) {
          if ($acc['platform'] === 'linkedin' && $acc['status'] === 'active') $liAccount = $acc;
          if ($acc['platform'] === 'instagram' && $acc['status'] === 'active') $igAccount = $acc;
      }
    ?>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-[#0A66C2]">
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-900"><?= esc($liAccount['account_name'] ?? 'LinkedIn Company Page') ?></span>
            <?php if ($liAccount): ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Connected</span>
            <?php else: ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Not Connected</span>
            <?php endif; ?>
          </div>
          <span class="text-xs text-slate-500 block mt-0.5"><?= $liAccount ? 'Org ID: ' . esc($liAccount['external_id']) : 'Official LinkedIn Posts & Analytics API' ?></span>
        </div>
      </div>
      <div>
        <?php if ($liAccount): ?>
          <a href="<?= base_url('admin/social/accounts') ?>" class="text-xs font-semibold text-slate-600 hover:text-brand-primary">Manage</a>
        <?php else: ?>
          <a href="<?= base_url('admin/social/oauth/connect/linkedin') ?>" class="text-xs font-bold text-[#0A66C2] hover:underline">Connect</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Instagram Account Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-xl bg-pink-50 border border-pink-200 flex items-center justify-center text-[#E4405F]">
          <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-900"><?= esc($igAccount['account_name'] ?? 'Instagram Professional') ?></span>
            <?php if ($igAccount): ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Connected</span>
            <?php else: ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Not Connected</span>
            <?php endif; ?>
          </div>
          <span class="text-xs text-slate-500 block mt-0.5"><?= $igAccount ? 'Account ID: ' . esc($igAccount['external_id']) : 'Meta Content Publishing Graph API' ?></span>
        </div>
      </div>
      <div>
        <?php if ($igAccount): ?>
          <a href="<?= base_url('admin/social/accounts') ?>" class="text-xs font-semibold text-slate-600 hover:text-brand-primary">Manage</a>
        <?php else: ?>
          <a href="<?= base_url('admin/social/oauth/connect/instagram') ?>" class="text-xs font-bold text-[#E4405F] hover:underline">Connect</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <!-- Published Posts -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-500">
        <span class="text-xs font-semibold uppercase tracking-wider">Published Posts</span>
        <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-slate-900"><?= number_format($kpis['total_published']) ?></span>
        <span class="text-xs text-slate-400 font-medium">(LI: <?= $kpis['linkedin_published'] ?> | IG: <?= $kpis['instagram_published'] ?>)</span>
      </div>
    </div>

    <!-- Scheduled Posts -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-500">
        <span class="text-xs font-semibold uppercase tracking-wider">Scheduled Posts</span>
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-amber-600"><?= number_format($kpis['scheduled_posts']) ?></span>
        <span class="text-xs text-slate-400 font-medium">In Queue</span>
      </div>
    </div>

    <!-- Impressions / Reach -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-500">
        <span class="text-xs font-semibold uppercase tracking-wider">Impressions</span>
        <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-slate-900"><?= number_format($kpis['total_impressions']) ?></span>
        <span class="text-xs text-slate-400 font-medium">Reach: <?= number_format($kpis['total_reach']) ?></span>
      </div>
    </div>

    <!-- Social Leads -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-500">
        <span class="text-xs font-semibold uppercase tracking-wider">Social Leads</span>
        <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
      </div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-brand-primary"><?= number_format($kpis['total_enquiries']) ?></span>
        <span class="text-xs text-emerald-600 font-semibold">(<?= $kpis['new_enquiries'] ?> new)</span>
      </div>
    </div>
  </div>

  <!-- Publishing Velocity Chart & Quick Calendar -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Trend Chart (2 cols) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-base font-bold text-slate-900">Publishing Activity & Velocity</h2>
          <p class="text-xs text-slate-500">Daily posts published to LinkedIn & Instagram over the last 30 days</p>
        </div>
        <div class="flex items-center gap-3 text-xs">
          <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#0A66C2]"></span> LinkedIn</span>
          <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-[#E4405F]"></span> Instagram</span>
        </div>
      </div>
      <div class="h-64">
        <canvas id="publishingTrendsChart"></canvas>
      </div>
    </div>

    <!-- Quick Actions & Pipeline -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
      <div>
        <h2 class="text-base font-bold text-slate-900 mb-1">Quick Content Studio</h2>
        <p class="text-xs text-slate-500 mb-4">Direct publishing and scheduling shortcuts.</p>

        <div class="space-y-2.5">
          <a href="<?= base_url('admin/social/posts/create') ?>" class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-brand-primary/40 hover:bg-brand-light/30 transition group">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-brand-light text-brand-primary flex items-center justify-center font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </div>
              <div>
                <span class="block text-xs font-bold text-slate-800 group-hover:text-brand-primary">Compose Post</span>
                <span class="block text-[11px] text-slate-400">Post composer & AI assistant</span>
              </div>
            </div>
            <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="<?= base_url('admin/social/calendar') ?>" class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-brand-primary/40 hover:bg-brand-light/30 transition group">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <span class="block text-xs font-bold text-slate-800 group-hover:text-brand-primary">Content Calendar</span>
                <span class="block text-[11px] text-slate-400">Monthly publishing schedule</span>
              </div>
            </div>
            <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="<?= base_url('admin/social/media') ?>" class="flex items-center justify-between p-3 rounded-xl border border-slate-200 hover:border-brand-primary/40 hover:bg-brand-light/30 transition group">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <span class="block text-xs font-bold text-slate-800 group-hover:text-brand-primary">Media Library</span>
                <span class="block text-[11px] text-slate-400">Upload graphics & documents</span>
              </div>
            </div>
            <svg class="w-4 h-4 text-slate-400 group-hover:text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

      <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <span>Scheduler Cron:</span>
        <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active (php spark social:publish)
        </span>
      </div>
    </div>
  </div>

  <!-- Recent Posts & Social Leads Table -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Posts -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-slate-900">Recent Social Posts</h2>
        <a href="<?= base_url('admin/social/posts') ?>" class="text-xs font-bold text-brand-primary hover:underline">View All &rarr;</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 font-semibold">
              <th class="pb-2.5">Title</th>
              <th class="pb-2.5">Platform</th>
              <th class="pb-2.5">Status</th>
              <th class="pb-2.5 text-right">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($recentPosts)): ?>
              <tr>
                <td colspan="4" class="py-6 text-center text-slate-400">No posts created yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentPosts as $p): ?>
                <tr class="hover:bg-slate-50/50">
                  <td class="py-3 font-semibold text-slate-800">
                    <a href="<?= base_url('admin/social/posts/' . $p['id']) ?>" class="hover:text-brand-primary"><?= esc(mb_strimwidth($p['title'], 0, 32, '...')) ?></a>
                  </td>
                  <td class="py-3">
                    <?php if ($p['primary_platform'] === 'both'): ?>
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700">Both</span>
                    <?php elseif ($p['primary_platform'] === 'linkedin'): ?>
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">LinkedIn</span>
                    <?php else: ?>
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700">Instagram</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3">
                    <?php if ($p['status'] === 'published'): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">Published</span>
                    <?php elseif ($p['status'] === 'scheduled'): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">Scheduled</span>
                    <?php elseif ($p['status'] === 'failed'): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700">Failed</span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Draft</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 text-right text-slate-400">
                    <?= esc(date('M d', strtotime($p['created_at']))) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Social Inquiries -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-slate-900">Recent Social Enquiries</h2>
        <a href="<?= base_url('admin/social/enquiries') ?>" class="text-xs font-bold text-brand-primary hover:underline">View All Leads &rarr;</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 font-semibold">
              <th class="pb-2.5">Contact</th>
              <th class="pb-2.5">Source</th>
              <th class="pb-2.5">Type</th>
              <th class="pb-2.5">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($recentEnquiries)): ?>
              <tr>
                <td colspan="4" class="py-6 text-center text-slate-400">No social leads captured yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentEnquiries as $enq): ?>
                <tr class="hover:bg-slate-50/50">
                  <td class="py-3">
                    <span class="font-bold text-slate-800 block"><?= esc($enq['contact_name']) ?></span>
                    <span class="text-[11px] text-slate-400 block"><?= esc($enq['email'] ?? $enq['phone'] ?? '—') ?></span>
                  </td>
                  <td class="py-3 capitalize text-slate-600 font-medium">
                    <?= esc($enq['source_platform']) ?>
                  </td>
                  <td class="py-3 text-slate-600">
                    <?= esc($enq['enquiry_type']) ?>
                  </td>
                  <td class="py-3">
                    <?php if ($enq['lead_status'] === 'new'): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">New</span>
                    <?php elseif ($enq['lead_status'] === 'converted'): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">Converted</span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600"><?= esc(ucfirst($enq['lead_status'])) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('publishingTrendsChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($trends['labels']) ?>,
        datasets: [
          {
            label: 'LinkedIn',
            data: <?= json_encode($trends['linkedin']) ?>,
            backgroundColor: '#0A66C2',
            borderRadius: 4,
          },
          {
            label: 'Instagram',
            data: <?= json_encode($trends['instagram']) ?>,
            backgroundColor: '#E4405F',
            borderRadius: 4,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: true, grid: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
        },
        plugins: {
          legend: { display: false }
        }
      }
    });
  }
});
</script>
<?= $this->endSection() ?>

