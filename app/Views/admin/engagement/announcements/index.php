<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Announcements &amp; Popup Manager</h1>
      <p class="text-xs text-slate-500 mt-1">Design, schedule, target, and monitor smart popups, banners, and recruitment drives.</p>
    </div>
    <a href="<?= base_url('admin/engagement/announcements/new') ?>" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover shadow-md shadow-brand-primary/20 transition self-start sm:self-auto">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Create New Campaign</span>
    </a>
  </div>

  <!-- Filters & Search Toolbar -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
    <div class="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
      <a href="<?= base_url('admin/engagement/announcements') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= empty($statusFilter) ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All</a>
      <a href="<?= base_url('admin/engagement/announcements?status=ACTIVE') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'ACTIVE') ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Active</a>
      <a href="<?= base_url('admin/engagement/announcements?status=SCHEDULED') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'SCHEDULED') ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Scheduled</a>
      <a href="<?= base_url('admin/engagement/announcements?status=PAUSED') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'PAUSED') ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Paused</a>
      <a href="<?= base_url('admin/engagement/announcements?status=EXPIRED') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'EXPIRED') ? 'bg-slate-700 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Expired</a>
      <a href="<?= base_url('admin/engagement/announcements?status=DRAFT') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($statusFilter === 'DRAFT') ? 'bg-slate-500 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Draft</a>
    </div>

    <form method="GET" action="<?= base_url('admin/engagement/announcements') ?>" class="relative w-full sm:w-64">
      <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search campaigns..." class="w-full pl-9 pr-4 py-1.5 text-xs rounded-xl border border-slate-200 focus:border-brand-primary focus:ring-brand-primary">
      <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </form>
  </div>

  <!-- Announcements Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Campaign Title</th>
            <th class="px-4 py-3.5">Display Format</th>
            <th class="px-4 py-3.5">Audience &amp; Target</th>
            <th class="px-4 py-3.5">Schedule</th>
            <th class="px-4 py-3.5 text-center">Priority</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($announcements)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <div class="max-w-xs mx-auto">
                  <p class="font-bold text-slate-600 mb-1">No announcement campaigns found</p>
                  <p class="text-[11px] text-slate-400 mb-4">Create your first popup modal or top banner announcement to engage visitors.</p>
                  <a href="<?= base_url('admin/engagement/announcements/new') ?>" class="inline-block px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover transition">Create Campaign</a>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($announcements as $c): ?>
              <?php
                $statusBadge = match($c['status']) {
                  'ACTIVE'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                  'SCHEDULED' => 'bg-blue-50 text-blue-700 border-blue-200',
                  'PAUSED'    => 'bg-amber-50 text-amber-700 border-amber-200',
                  'EXPIRED'   => 'bg-slate-100 text-slate-600 border-slate-200',
                  'DRAFT'     => 'bg-slate-50 text-slate-600 border-slate-200',
                  default     => 'bg-slate-50 text-slate-500 border-slate-200',
                };
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900"><?= esc($c['title']) ?></div>
                  <div class="text-[11px] text-slate-500 truncate max-w-[240px]"><?= esc($c['public_heading']) ?></div>
                </td>
                <td class="px-4 py-4">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-semibold text-[11px] capitalize">
                    <?= esc(str_replace('_', ' ', $c['display_format'])) ?>
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-slate-700 capitalize"><?= esc(str_replace('_', ' ', $c['audience'])) ?></div>
                  <div class="text-[10px] text-slate-400 capitalize"><?= esc(str_replace('_', ' ', $c['frequency'])) ?></div>
                </td>
                <td class="px-4 py-4">
                  <div class="text-slate-800 font-medium"><?= esc($c['start_date']) ?> to <?= esc($c['end_date']) ?></div>
                  <div class="text-[10px] text-slate-400"><?= esc($c['timezone'] ?? 'Asia/Kolkata') ?></div>
                </td>
                <td class="px-4 py-4 text-center font-bold text-slate-700">
                  <?= (int) $c['priority'] ?>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $statusBadge ?>"><?= esc($c['status']) ?></span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Toggle Status -->
                    <form method="POST" action="<?= base_url('admin/engagement/announcements/toggle/' . $c['id']) ?>" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" title="<?= ($c['status'] === 'ACTIVE') ? 'Pause' : 'Activate' ?>" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-brand-primary hover:border-brand-primary transition">
                        <?php if ($c['status'] === 'ACTIVE'): ?>
                          <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php else: ?>
                          <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php endif; ?>
                      </button>
                    </form>

                    <!-- Duplicate -->
                    <form method="POST" action="<?= base_url('admin/engagement/announcements/duplicate/' . $c['id']) ?>" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" title="Duplicate Campaign" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                      </button>
                    </form>

                    <!-- Edit -->
                    <a href="<?= base_url('admin/engagement/announcements/edit/' . $c['id']) ?>" title="Edit" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-brand-primary hover:border-brand-primary transition">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>

                    <!-- Delete -->
                    <form method="POST" action="<?= base_url('admin/engagement/announcements/delete/' . $c['id']) ?>" onsubmit="return confirm('Delete this announcement campaign permanently?');" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" title="Delete" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
