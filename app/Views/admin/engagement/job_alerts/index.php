<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Candidate Job Alert Subscriptions</h1>
      <p class="text-xs text-slate-500 mt-1">Candidates subscribed to automated role notifications matching Pune industrial corridors.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="<?= base_url('admin/engagement/job-alerts/export') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Table Container -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="text-xs text-slate-500 font-semibold">
        Active Subscriptions: <span class="font-bold text-slate-900"><?= number_format($totalActive ?? 0) ?></span>
      </div>
      <form method="GET" action="<?= base_url('admin/engagement/job-alerts') ?>" class="relative w-full sm:w-64">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search alerts..." class="w-full pl-9 pr-4 py-1.5 text-xs rounded-xl border border-slate-200 focus:border-brand-primary focus:ring-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </form>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Candidate Email</th>
            <th class="px-4 py-3.5">Preferred Category</th>
            <th class="px-4 py-3.5">Location</th>
            <th class="px-4 py-3.5 text-center">Frequency</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5">Last Alert Dispatched</th>
            <th class="px-6 py-3.5 text-right">Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($alerts)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <p class="font-bold text-slate-600 mb-1">No candidate job alerts active</p>
                <p class="text-[11px] text-slate-400">When job seekers subscribe to automated vacancy alerts, they will appear here.</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($alerts as $a): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4 font-bold text-slate-900"><?= esc($a['email']) ?></td>
                <td class="px-4 py-4 text-slate-700"><?= esc($a['job_category'] ?: 'All Categories') ?></td>
                <td class="px-4 py-4 text-slate-600"><?= esc($a['preferred_location'] ?: 'Pune') ?></td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 capitalize"><?= esc($a['frequency']) ?></span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= ($a['status'] === 'active') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' ?> capitalize">
                    <?= esc($a['status']) ?>
                  </span>
                </td>
                <td class="px-4 py-4 text-slate-500">
                  <?= !empty($a['last_alert_sent_at']) ? date('M j, Y — g:i A', strtotime($a['last_alert_sent_at'])) : 'Never' ?>
                </td>
                <td class="px-6 py-4 text-right text-slate-500">
                  <?= date('M j, Y', strtotime($a['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
