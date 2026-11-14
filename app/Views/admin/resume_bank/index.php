<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">General Resume Bank</h1>
      <p class="text-xs text-slate-500 mt-1">Spontaneous candidate talent pool submissions from the confidential Career Enquiry portal</p>
    </div>

    <!-- Export Action -->
    <div class="flex items-center gap-3">
      <?php 
        $exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
      ?>
      <a href="<?= base_url('admin/resume-bank?' . $exportQuery) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <form action="<?= base_url('admin/resume-bank') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
      <div class="relative w-full sm:w-72">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search candidate, role, email..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="status" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Statuses</option>
        <option value="NEW" <?= (strtoupper($status ?? '') === 'NEW') ? 'selected' : '' ?>>New</option>
        <option value="REVIEWED" <?= (strtoupper($status ?? '') === 'REVIEWED') ? 'selected' : '' ?>>Reviewed</option>
        <option value="CONTACTED" <?= (strtoupper($status ?? '') === 'CONTACTED') ? 'selected' : '' ?>>Contacted</option>
        <option value="ARCHIVED" <?= (strtoupper($status ?? '') === 'ARCHIVED') ? 'selected' : '' ?>>Archived</option>
      </select>

      <select name="per_page" onchange="this.form.submit()" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10 / page</option>
        <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25 / page</option>
        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50 / page</option>
        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100 / page</option>
      </select>

      <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        Filter
      </button>

      <?php if (!empty($search) || !empty($status)): ?>
        <a href="<?= base_url('admin/resume-bank') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium">
      Total Talent Profiles: <span class="font-bold text-slate-800"><?= esc($totalCount ?? count($resumes)) ?></span>
    </div>
  </div>

  <!-- Resumes Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Candidate Profile</th>
            <th class="px-6 py-4">Desired Role &amp; Focus</th>
            <th class="px-6 py-4">Experience</th>
            <th class="px-6 py-4">Preferred Location</th>
            <th class="px-6 py-4">Status &amp; Action</th>
            <th class="px-6 py-4">Submitted On</th>
            <th class="px-6 py-4 text-right">Resume</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($resumes)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                No resume bank entries found matching your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($resumes as $r): ?>
              <?php 
                $rSt = strtoupper($r['status'] ?? 'NEW');
                $candidateName = $r['candidate_name'] ?? $r['full_name'] ?? 'Candidate #' . $r['id'];
                $candidateEmail = $r['candidate_email'] ?? $r['email'] ?? '';
                $candidatePhone = $r['candidate_phone'] ?? $r['phone'] ?? '';
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($candidateName) ?></div>
                  <div class="text-[11px] text-slate-500 mt-0.5 flex flex-col gap-0.5">
                    <?php if (!empty($candidateEmail)): ?>
                      <a href="mailto:<?= esc($candidateEmail) ?>" class="hover:text-brand-primary"><?= esc($candidateEmail) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($candidatePhone)): ?>
                      <span class="text-slate-400 font-mono"><?= esc($candidatePhone) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($r['notes'])): ?>
                    <div class="mt-1.5 text-[10px] text-slate-500 bg-slate-50 p-1.5 rounded border border-slate-100 line-clamp-2">
                      <?= esc($r['notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-800"><?= esc($r['desired_role'] ?? $r['current_role'] ?? 'General Pool') ?></div>
                  <?php if (!empty($r['notice_period'])): ?>
                    <div class="text-[10px] text-brand-primary font-medium mt-0.5">Notice: <?= esc($r['notice_period']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-slate-600">
                  <?= esc($r['experience_years'] ?? '0') ?> yrs
                </td>
                <td class="px-6 py-4 text-slate-600">
                  <?= esc($r['preferred_location'] ?? $r['location'] ?? 'Pune, India') ?>
                </td>
                <td class="px-6 py-4">
                  <form action="<?= base_url('admin/resume-bank/status/' . $r['id']) ?>" method="POST" class="inline">
                    <?= csrf_field() ?>
                    <select name="status" onchange="this.form.submit()" class="py-1 px-2.5 rounded-lg border text-xs font-bold
                      <?= ($rSt === 'NEW') ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : '' ?>
                      <?= ($rSt === 'REVIEWED') ? 'bg-sky-50 text-sky-700 border-sky-200' : '' ?>
                      <?= ($rSt === 'CONTACTED') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' ?>
                      <?= ($rSt === 'ARCHIVED') ? 'bg-slate-100 text-slate-600 border-slate-200' : '' ?>
                    ">
                      <option value="NEW" <?= ($rSt === 'NEW') ? 'selected' : '' ?>>New</option>
                      <option value="REVIEWED" <?= ($rSt === 'REVIEWED') ? 'selected' : '' ?>>Reviewed</option>
                      <option value="CONTACTED" <?= ($rSt === 'CONTACTED') ? 'selected' : '' ?>>Contacted</option>
                      <option value="ARCHIVED" <?= ($rSt === 'ARCHIVED') ? 'selected' : '' ?>>Archived</option>
                    </select>
                  </form>
                </td>
                <td class="px-6 py-4 text-slate-500 text-[11px]">
                  <?= esc(date('M d, Y', strtotime($r['created_at'] ?? 'now'))) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <?php if (!empty($r['document_id']) || !empty($r['resume_path'])): ?>
                    <a href="<?= base_url('admin/resume-bank/resume/' . $r['id']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-light text-brand-primary hover:bg-emerald-100 font-bold transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                      <span>Download</span>
                    </a>
                  <?php else: ?>
                    <span class="text-slate-400">No file</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
        <span class="text-slate-500">
          Showing page <?= esc($page) ?> of <?= esc($totalPages) ?>
        </span>
        <div class="flex items-center gap-2">
          <?php if ($page > 1): ?>
            <?php 
              $prevQuery = http_build_query(array_merge($_GET, ['page' => $page - 1]));
            ?>
            <a href="<?= base_url('admin/resume-bank?' . $prevQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">&larr; Previous</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <?php 
              $nextQuery = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= base_url('admin/resume-bank?' . $nextQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>
