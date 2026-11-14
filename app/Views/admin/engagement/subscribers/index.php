<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Newsletter Subscribers</h1>
      <p class="text-xs text-slate-500 mt-1">Manage verified double opt-in subscribers, suppression lists, and category preferences.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="<?= base_url('admin/engagement/subscribers/export') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Telemetry Row -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Records</div>
      <div class="text-2xl font-black text-slate-900 mt-1"><?= number_format($stats['total'] ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Verified &amp; Active</div>
      <div class="text-2xl font-black text-emerald-600 mt-1"><?= number_format($stats['verified'] ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Pending Double Opt-In</div>
      <div class="text-2xl font-black text-amber-600 mt-1"><?= number_format($stats['unverified'] ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Suppressed / Unsubscribed</div>
      <div class="text-2xl font-black text-red-500 mt-1"><?= number_format($stats['unsubscribed'] ?? 0) ?></div>
    </div>
  </div>

  <!-- Filters Toolbar -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
    <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
      <a href="<?= base_url('admin/engagement/subscribers') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= empty($statusFilter) ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All</a>
      <a href="<?= base_url('admin/engagement/subscribers?status=subscribed') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'subscribed') ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Active Subscribed</a>
      <a href="<?= base_url('admin/engagement/subscribers?status=unverified') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'unverified') ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Pending Verification</a>
      <a href="<?= base_url('admin/engagement/subscribers?status=unsubscribed') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'unsubscribed') ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Unsubscribed</a>
    </div>

    <form method="GET" action="<?= base_url('admin/engagement/subscribers') ?>" class="relative w-full sm:w-64">
      <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search by email..." class="w-full pl-9 pr-4 py-1.5 text-xs rounded-xl border border-slate-200 focus:border-brand-primary focus:ring-brand-primary">
      <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </form>
  </div>

  <!-- Subscribers Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Subscriber</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5">Consent Record</th>
            <th class="px-4 py-3.5">Verified At</th>
            <th class="px-4 py-3.5">Date Subscribed</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($subscribers)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                <p class="font-bold text-slate-600 mb-1">No subscribers found</p>
                <p class="text-[11px] text-slate-400">Public visitors subscribing to the newsletter will appear here.</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($subscribers as $s): ?>
              <?php
                $badge = match($s['status']) {
                  'subscribed'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                  'unverified'   => 'bg-amber-50 text-amber-700 border-amber-200',
                  'unsubscribed' => 'bg-red-50 text-red-700 border-red-200',
                  default        => 'bg-slate-50 text-slate-500 border-slate-200',
                };
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900"><?= esc($s['email']) ?></div>
                  <div class="text-[11px] text-slate-500"><?= esc($s['first_name'] ?: 'No Name Provided') ?></div>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize <?= $badge ?>"><?= esc($s['status']) ?></span>
                </td>
                <td class="px-4 py-4">
                  <div class="text-slate-700 font-medium"><?= $s['consent_given'] ? 'Explicit Consent' : 'Pending' ?></div>
                  <div class="text-[10px] text-slate-400">Source: <?= esc($s['consent_source']) ?></div>
                </td>
                <td class="px-4 py-4 text-slate-600">
                  <?= !empty($s['verified_at']) ? date('M j, Y — g:i A', strtotime($s['verified_at'])) : '<span class="text-amber-500">Unverified</span>' ?>
                </td>
                <td class="px-4 py-4 text-slate-600">
                  <?= date('M j, Y', strtotime($s['created_at'])) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <form method="POST" action="<?= base_url('admin/engagement/subscribers/toggle/' . $s['id']) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="px-2.5 py-1 rounded-lg border text-[11px] font-bold transition <?= ($s['status'] === 'subscribed') ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' ?>">
                      <?= ($s['status'] === 'subscribed') ? 'Suppressed / Unsubscribe' : 'Reactivate' ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
