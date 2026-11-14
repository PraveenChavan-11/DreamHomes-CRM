<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Job Postings Management</h1>
      <p class="text-xs text-slate-500 mt-1">Manage corporate vacancies, career opportunities, and candidate requirements</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/jobs') ?>?export=csv<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
      <a href="<?= base_url('admin/jobs/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        <span>Post New Job</span>
      </a>
    </div>
  </div>

  <!-- Filters & Search -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
    <form action="<?= base_url('admin/jobs') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full md:w-auto">
      <div class="relative w-full sm:w-64">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search job title, skills, location..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="status" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Statuses</option>
        <option value="active" <?= ($status === 'active' || $status === 'PUBLISHED') ? 'selected' : '' ?>>Published / Active</option>
        <option value="closed" <?= ($status === 'closed' || $status === 'CLOSED') ? 'selected' : '' ?>>Closed Only</option>
        <option value="draft" <?= ($status === 'draft' || $status === 'DRAFT') ? 'selected' : '' ?>>Draft Only</option>
      </select>

      <select name="category_id" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= esc($cat['id']) ?>" <?= (!empty($categoryId) && $categoryId == $cat['id']) ? 'selected' : '' ?>>
            <?= esc($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="per_page" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10 per page</option>
        <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25 per page</option>
        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50 per page</option>
        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100 per page</option>
      </select>

      <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        Filter
      </button>

      <?php if (!empty($search) || !empty($status) || !empty($categoryId)): ?>
        <a href="<?= base_url('admin/jobs') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium shrink-0">
      Total Records: <span class="font-bold text-slate-800"><?= esc($totalJobs ?? count($jobs)) ?></span>
    </div>
  </div>

  <!-- Jobs Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Title &amp; Public Link</th>
            <th class="px-6 py-4">Category</th>
            <th class="px-6 py-4">Type / Mode</th>
            <th class="px-6 py-4">Location</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Posted Date</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($jobs)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                No job postings found matching your search.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($jobs as $j): ?>
              <?php $isLive = in_array(strtoupper($j['status'] ?? ''), ['PUBLISHED', 'ACTIVE']); ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <a href="<?= base_url('admin/jobs/edit/' . $j['id']) ?>" class="hover:text-brand-primary">
                      <?= esc($j['title']) ?>
                    </a>
                    <?php if (!empty($j['is_featured'])): ?>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">Featured</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-[11px] text-slate-400 mt-0.5 font-mono">/jobs/<?= esc($j['slug']) ?></div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-xs text-slate-600 font-medium">
                    <?= esc($j['category_name'] ?? 'General') ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col gap-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 w-fit">
                      <?= esc($j['job_type'] === 'FT' ? 'Full-Time' : ($j['job_type'] === 'CT' ? 'Contract' : ($j['job_type'] === 'PT' ? 'Part-Time' : 'Internship'))) ?>
                    </span>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">
                      <?= esc($j['work_mode'] ?? 'ONSITE') ?>
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 font-medium text-slate-600">
                  <?= esc($j['location'] ?? 'Pune, India') ?>
                </td>
                <td class="px-6 py-4">
                  <form action="<?= base_url('admin/jobs/toggle/' . $j['id']) ?>" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" title="Click to toggle status" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition <?= $isLive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200' ?>">
                      <span class="w-1.5 h-1.5 rounded-full <?= $isLive ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                      <span><?= $isLive ? 'Published' : esc(ucfirst(strtolower($j['status']))) ?></span>
                    </button>
                  </form>
                </td>
                <td class="px-6 py-4 text-slate-500">
                  <?= esc(date('M d, Y', strtotime($j['created_at'] ?? 'now'))) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="<?= base_url('jobs/' . $j['slug']) ?>" target="_blank" title="View on Live Site" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="<?= base_url('admin/jobs/edit/' . $j['id']) ?>" title="Edit Job" class="p-1.5 rounded-lg text-brand-primary hover:bg-brand-light">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form action="<?= base_url('admin/jobs/delete/' . $j['id']) ?>" method="POST" onsubmit="return confirm('Are you sure you want to archive this job posting?');" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" title="Archive Job" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
