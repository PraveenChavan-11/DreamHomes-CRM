<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Campaign Analytics &amp; Conversion Telemetry</h1>
      <p class="text-xs text-slate-500 mt-1">Audit-verified impression events, user interactions, click-through rates, and goal completions.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= base_url('admin/engagement/analytics/report') ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-sm">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Executive B2B Report (PDF / Print)</span>
      </a>
    </div>
  </div>

  <!-- Analytics Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Campaign Name</th>
            <th class="px-4 py-3.5">Display Format</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Impressions</th>
            <th class="px-4 py-3.5 text-right">Unique Viewers</th>
            <th class="px-4 py-3.5 text-right">CTA Clicks</th>
            <th class="px-4 py-3.5 text-right">Dismissals</th>
            <th class="px-4 py-3.5 text-right">CTR (%)</th>
            <th class="px-6 py-3.5 text-right">Conversions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($analytics)): ?>
            <tr>
              <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                No campaign event data recorded yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($analytics as $row): ?>
              <?php
                $ctr = $row['impressions'] > 0 ? round(($row['clicks'] / $row['impressions']) * 100, 2) : 0;
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4 font-bold text-slate-900"><?= esc($row['title']) ?></td>
                <td class="px-4 py-4 capitalize text-slate-600"><?= esc(str_replace('_', ' ', $row['display_format'])) ?></td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700"><?= esc($row['status']) ?></span>
                </td>
                <td class="px-4 py-4 text-right font-medium text-slate-800"><?= number_format($row['impressions']) ?></td>
                <td class="px-4 py-4 text-right text-slate-600"><?= number_format($row['unique_impressions']) ?></td>
                <td class="px-4 py-4 text-right font-bold text-brand-primary"><?= number_format($row['clicks']) ?></td>
                <td class="px-4 py-4 text-right text-slate-500"><?= number_format($row['dismissals']) ?></td>
                <td class="px-4 py-4 text-right font-black text-slate-900"><?= $ctr ?>%</td>
                <td class="px-6 py-4 text-right font-black text-emerald-600"><?= number_format($row['conversions']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
