<div class="space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Engagement Centre Overview</h1>
      <p class="text-xs text-slate-500 mt-1">Real-time performance telemetry, subscriber growth, campaign lifecycles, and consent metrics.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="<?= base_url('admin/engagement/announcements/new') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover shadow-md shadow-brand-primary/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Campaign</span>
      </a>
      <a href="<?= base_url('admin/engagement/campaigns/new') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
        <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Compose Email</span>
      </a>
    </div>
  </div>

  <!-- Primary Metric KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Card 1: Verified Subscribers -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Subscribers</span>
        <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
      </div>
      <div class="text-3xl font-black text-slate-900"><?= number_format($subStats['verified'] ?? 0) ?></div>
      <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
        <span>Total: <?= number_format($subStats['total'] ?? 0) ?></span>
        <span class="text-emerald-600 font-semibold">+<?= number_format($subStats['new_last_7_days'] ?? 0) ?> this week</span>
      </div>
    </div>

    <!-- Card 2: Active Announcements & Popups -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Campaigns</span>
        <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        </span>
      </div>
      <div class="text-3xl font-black text-slate-900"><?= number_format($campaignStats['active'] ?? 0) ?></div>
      <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
        <span>Scheduled: <?= number_format($campaignStats['scheduled'] ?? 0) ?></span>
        <span>Expired: <?= number_format($campaignStats['expired'] ?? 0) ?></span>
      </div>
    </div>

    <!-- Card 3: Job Alerts Subscribed -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Job Alerts</span>
        <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </span>
      </div>
      <div class="text-3xl font-black text-slate-900"><?= number_format($alertCount ?? 0) ?></div>
      <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
        <span>Automated Matching</span>
        <span class="text-purple-600 font-semibold">Active</span>
      </div>
    </div>

    <!-- Card 4: Campaign Impressions & Conversions -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">CTR &amp; Conversions</span>
        <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </span>
      </div>
      <div class="text-3xl font-black text-slate-900"><?= esc($overallCtr) ?>%</div>
      <div class="flex items-center justify-between text-[11px] text-slate-500 mt-2 pt-2 border-t border-slate-100">
        <span><?= number_format($totalImpressions) ?> views</span>
        <span class="text-amber-600 font-semibold"><?= number_format($totalClicks) ?> clicks</span>
      </div>
    </div>
  </div>

  <!-- Campaign Performance Breakdown & Live Feed -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Column 1 & 2: Active Announcements & Analytics -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div>
          <h2 class="text-sm font-extrabold text-slate-900">Campaign Performance &amp; Conversion Analytics</h2>
          <p class="text-[11px] text-slate-500">Live impression telemetry, unique viewers, click-through, and dismissals.</p>
        </div>
        <a href="<?= base_url('admin/engagement/analytics') ?>" class="text-xs text-brand-primary font-bold hover:underline">Full Analytics &rarr;</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
            <tr>
              <th class="px-6 py-3">Campaign</th>
              <th class="px-4 py-3">Format</th>
              <th class="px-4 py-3 text-center">Status</th>
              <th class="px-4 py-3 text-right">Views</th>
              <th class="px-4 py-3 text-right">Clicks</th>
              <th class="px-4 py-3 text-right">CTR</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($analytics)): ?>
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-slate-400">No campaigns launched yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($analytics as $c): ?>
                <?php
                  $ctr = $c['impressions'] > 0 ? round(($c['clicks'] / $c['impressions']) * 100, 1) : 0;
                  $statusBadge = match($c['status']) {
                    'ACTIVE'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'SCHEDULED' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'PAUSED'    => 'bg-amber-50 text-amber-700 border-amber-200',
                    'EXPIRED'   => 'bg-slate-100 text-slate-600 border-slate-200',
                    default     => 'bg-slate-50 text-slate-500 border-slate-200',
                  };
                ?>
                <tr class="hover:bg-slate-50/60 transition">
                  <td class="px-6 py-3 font-semibold text-slate-900 max-w-[200px] truncate"><?= esc($c['title']) ?></td>
                  <td class="px-4 py-3 text-slate-600 capitalize"><?= esc(str_replace('_', ' ', $c['display_format'])) ?></td>
                  <td class="px-4 py-3 text-center">
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $statusBadge ?>"><?= esc($c['status']) ?></span>
                  </td>
                  <td class="px-4 py-3 text-right font-medium text-slate-700"><?= number_format($c['impressions']) ?></td>
                  <td class="px-4 py-3 text-right font-bold text-brand-primary"><?= number_format($c['clicks']) ?></td>
                  <td class="px-4 py-3 text-right font-bold text-slate-900"><?= $ctr ?>%</td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Column 3: Recent Subscriber Activity Feed -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
          <h2 class="text-sm font-extrabold text-slate-900">Recent Activity Feed</h2>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Audit Verified</span>
        </div>

        <div class="space-y-3.5">
          <?php if (empty($recentActivity)): ?>
            <div class="text-center py-8 text-xs text-slate-400">No activity recorded yet.</div>
          <?php else: ?>
            <?php foreach ($recentActivity as $act): ?>
              <?php
                $iconClass = match($act['activity_type']) {
                  'verified'    => 'bg-emerald-50 text-emerald-600',
                  'unsubscribed'=> 'bg-red-50 text-red-600',
                  'resubscribed'=> 'bg-blue-50 text-blue-600',
                  default       => 'bg-slate-100 text-slate-600',
                };
              ?>
              <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-lg <?= $iconClass ?> flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-bold text-slate-800 capitalize truncate"><?= esc(str_replace('_', ' ', $act['activity_type'])) ?></div>
                  <div class="text-[10px] text-slate-400"><?= date('M j, Y — g:i A', strtotime($act['activity_timestamp'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="pt-4 mt-4 border-t border-slate-100 text-center">
        <a href="<?= base_url('admin/engagement/subscribers') ?>" class="text-xs text-brand-primary font-bold hover:underline">View All Subscribers &rarr;</a>
      </div>
    </div>

  </div>

  <!-- Cookie Consent Telemetry Bar -->
  <div class="bg-slate-900 text-white rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-6 shadow-md">
    <div>
      <div class="text-xs uppercase font-extrabold tracking-wider text-brand-accent mb-1">Privacy &amp; Compliance</div>
      <h3 class="text-lg font-black text-white">GDPR &amp; India DPDP Privacy Telemetry</h3>
      <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
        Total visitor consent interactions: <strong><?= number_format($cookieStats['total'] ?? 0) ?></strong>. Analytics consent: <strong><?= number_format($cookieStats['analytics'] ?? 0) ?></strong>, Marketing consent: <strong><?= number_format($cookieStats['marketing'] ?? 0) ?></strong>.
      </p>
    </div>
    <a href="<?= base_url('admin/engagement/cookies') ?>" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold text-xs shrink-0 transition">
      View Cookie Settings &amp; Logs
    </a>
  </div>

</div>
