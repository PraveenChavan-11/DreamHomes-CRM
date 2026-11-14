<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Unified Lead Management</h1>
      <p class="text-xs text-slate-500 mt-1">Centralized pipeline for employer hiring mandates, candidate applications, follow-up datelines, and general inquiries.</p>
    </div>
    <div class="flex items-center gap-2.5">
      <a href="<?= base_url('admin/engagement/leads/export') ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filters Toolbar -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
    <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
      <a href="<?= base_url('admin/engagement/leads') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($typeFilter === 'all') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All Inquiries</a>
      <a href="<?= base_url('admin/engagement/leads?type=employer') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($typeFilter === 'employer') ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Employer Mandates</a>
      <a href="<?= base_url('admin/engagement/leads?type=candidate') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($typeFilter === 'candidate') ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Candidate Applications</a>
      <a href="<?= base_url('admin/engagement/leads?type=contact') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($typeFilter === 'contact') ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">General Contact</a>
    </div>

    <form method="GET" action="<?= base_url('admin/engagement/leads') ?>" class="relative w-full sm:w-64">
      <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search leads..." class="w-full pl-9 pr-4 py-1.5 text-xs rounded-xl border border-slate-200 focus:border-brand-primary focus:ring-brand-primary">
      <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </form>
  </div>

  <!-- Leads Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Lead Type</th>
            <th class="px-4 py-3.5">Contact Person / Candidate</th>
            <th class="px-4 py-3.5">Email &amp; Telephone</th>
            <th class="px-4 py-3.5">Requirement / Role</th>
            <th class="px-4 py-3.5">Status &amp; Follow-up</th>
            <th class="px-6 py-3.5 text-right">Received Date</th>
            <th class="px-4 py-3.5 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($leads)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <p class="font-bold text-slate-600 mb-1">No inbound inquiries found</p>
                <p class="text-[11px] text-slate-400">Employer mandates, applications, and inquiries will appear in this unified feed.</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($leads as $lead): ?>
              <?php
                if ($lead['lead_type'] === 'employer') {
                  $typeBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                  $actionUrl = base_url('admin/enquiries/employers?search=' . urlencode($lead['email']));
                } elseif ($lead['lead_type'] === 'candidate') {
                  $typeBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                  $actionUrl = base_url('admin/applications?search=' . urlencode($lead['email']));
                } else {
                  $typeBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                  $actionUrl = base_url('admin/enquiries/contact?search=' . urlencode($lead['email']));
                }
                $followUpTimestamp = !empty($lead['follow_up_date']) ? strtotime($lead['follow_up_date']) : null;
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $typeBadge ?>">
                    <?= esc($lead['type']) ?>
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="font-bold text-slate-900"><?= esc($lead['contact_name']) ?></div>
                  <div class="text-[11px] text-slate-500"><?= esc($lead['company']) ?></div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-slate-800 font-medium"><?= esc($lead['email']) ?></div>
                  <div class="text-[11px] text-slate-500 font-mono"><?= esc($lead['phone']) ?></div>
                </td>
                <td class="px-4 py-4 text-slate-600 max-w-xs truncate">
                  <?= esc($lead['summary']) ?>
                </td>
                <td class="px-4 py-4">
                  <div class="space-y-1">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 capitalize">
                      <?= esc(str_replace('_', ' ', $lead['status'])) ?>
                    </span>

                    <?php if ($followUpTimestamp): ?>
                      <?php
                        $todayStart = strtotime('today');
                        $todayEnd = strtotime('tomorrow');
                      ?>
                      <div>
                        <?php if ($followUpTimestamp < $todayStart): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-100 text-red-800 border border-red-200">
                            Overdue: <?= date('M j', $followUpTimestamp) ?>
                          </span>
                        <?php elseif ($followUpTimestamp >= $todayStart && $followUpTimestamp < $todayEnd): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">
                            Due Today
                          </span>
                        <?php else: ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                            Follow-up: <?= date('M j', $followUpTimestamp) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 text-right text-slate-500 text-[11px]">
                  <?= date('M j, Y — g:i A', strtotime($lead['created_at'])) ?>
                </td>
                <td class="px-4 py-4 text-right">
                  <a href="<?= $actionUrl ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-[11px] transition">
                    <span>Manage</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
