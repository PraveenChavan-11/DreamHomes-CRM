<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Candidate Job Applications</h1>
      <p class="text-xs text-slate-500 mt-1">Review applicant profiles, screening statuses, follow-up datelines, and automated candidate communications</p>
    </div>

    <!-- Export Action -->
    <div class="flex items-center gap-3">
      <?php 
        $exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
      ?>
      <a href="<?= base_url('admin/applications?' . $exportQuery) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filter Controls -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <form action="<?= base_url('admin/applications') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
      <div class="relative w-full sm:w-64">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search candidate name, email, job..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="status" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Pipeline Stages</option>
        <option value="APPLIED" <?= (strtoupper($status ?? '') === 'APPLIED') ? 'selected' : '' ?>>Applied (New)</option>
        <option value="SCREENING" <?= (strtoupper($status ?? '') === 'SCREENING') ? 'selected' : '' ?>>Screening</option>
        <option value="SHORTLISTED" <?= (strtoupper($status ?? '') === 'SHORTLISTED') ? 'selected' : '' ?>>Shortlisted</option>
        <option value="INTERVIEW" <?= (strtoupper($status ?? '') === 'INTERVIEW') ? 'selected' : '' ?>>Interview</option>
        <option value="OFFER" <?= (strtoupper($status ?? '') === 'OFFER') ? 'selected' : '' ?>>Offer Extended</option>
        <option value="HIRED" <?= (strtoupper($status ?? '') === 'HIRED') ? 'selected' : '' ?>>Hired</option>
        <option value="REJECTED" <?= (strtoupper($status ?? '') === 'REJECTED') ? 'selected' : '' ?>>Rejected</option>
        <option value="WITHDRAWN" <?= (strtoupper($status ?? '') === 'WITHDRAWN') ? 'selected' : '' ?>>Withdrawn</option>
      </select>

      <select name="job_id" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary max-w-xs truncate font-medium">
        <option value="">All Job Openings</option>
        <?php foreach ($jobs as $j): ?>
          <option value="<?= esc($j['id']) ?>" <?= (!empty($jobId) && $jobId == $j['id']) ? 'selected' : '' ?>>
            <?= esc($j['title']) ?>
          </option>
        <?php endforeach; ?>
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

      <?php if (!empty($search) || !empty($status) || !empty($jobId)): ?>
        <a href="<?= base_url('admin/applications') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium">
      Total: <span class="font-bold text-slate-800"><?= esc($totalCount ?? count($applications)) ?></span> records
    </div>
  </div>

  <!-- Applications Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="w-10 px-4 py-4 text-center">
              <input type="checkbox" id="bulk-select-all" class="rounded border-slate-300 text-brand-primary focus:ring-0 cursor-pointer" title="Select All Applications">
            </th>
            <th class="px-6 py-4">Candidate Profile</th>
            <th class="px-6 py-4">Applied Job</th>
            <th class="px-6 py-4">Experience</th>
            <th class="px-6 py-4">Status &amp; Follow-up Dateline</th>
            <th class="px-6 py-4">Applied On</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($applications)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                No candidate applications match your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($applications as $app): ?>
              <?php 
                $appSt = strtoupper($app['status'] ?? 'APPLIED');
                $candidateName = $app['candidate_name'] ?? $app['full_name'] ?? 'Candidate #' . $app['id'];
                $candidateEmail = $app['candidate_email'] ?? $app['email'] ?? '';
                $candidatePhone = $app['candidate_phone'] ?? $app['phone'] ?? '';
                $followUpTimestamp = !empty($app['follow_up_date']) ? strtotime($app['follow_up_date']) : null;
                $isTerminal = in_array($appSt, ['REJECTED', 'WITHDRAWN', 'HIRED']);
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="w-10 px-4 py-4 text-center">
                  <input type="checkbox" value="<?= $app['id'] ?>" class="bulk-app-checkbox rounded border-slate-300 text-brand-primary focus:ring-0 cursor-pointer">
                </td>
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
                  <?php if (!empty($app['cover_message'])): ?>
                    <div class="mt-2 text-[11px] text-slate-600 bg-slate-50 p-2 rounded-lg border border-slate-100 max-w-sm line-clamp-2">
                      <span class="font-bold text-slate-700">Cover:</span> <?= esc($app['cover_message']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($app['follow_up_notes'])): ?>
                    <div class="mt-1 text-[11px] text-emerald-800 bg-emerald-50/70 p-2 rounded-lg border border-emerald-100 max-w-sm">
                      <span class="font-bold text-emerald-900">Notes:</span> <?= esc($app['follow_up_notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-800">
                  <div><?= esc($app['job_title'] ?? ('Job #' . ($app['job_id'] ?? ''))) ?></div>
                  <?php if (!empty($app['job_slug'])): ?>
                    <a href="<?= base_url('jobs/' . $app['job_slug']) ?>" target="_blank" class="text-[10px] text-slate-400 hover:text-brand-primary">View Post &rarr;</a>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-slate-600">
                  <?= esc($app['experience_years'] ?? '0') ?> yrs
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1.5">
                    <!-- Status Badge -->
                    <div>
                      <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border
                        <?= in_array($appSt, ['APPLIED', 'NEW']) ? 'bg-sky-50 text-sky-800 border-sky-200' : '' ?>
                        <?= ($appSt === 'SCREENING') ? 'bg-amber-50 text-amber-800 border-amber-200' : '' ?>
                        <?= ($appSt === 'SHORTLISTED') ? 'bg-indigo-50 text-indigo-800 border-indigo-200' : '' ?>
                        <?= ($appSt === 'INTERVIEW') ? 'bg-purple-50 text-purple-800 border-purple-200' : '' ?>
                        <?= in_array($appSt, ['OFFER', 'HIRED']) ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : '' ?>
                        <?= in_array($appSt, ['REJECTED', 'WITHDRAWN']) ? 'bg-red-50 text-red-800 border-red-200' : '' ?>
                      ">
                        <?= esc($appSt) ?>
                      </span>
                    </div>

                    <!-- Follow-up Dateline Badge -->
                    <?php if ($followUpTimestamp): ?>
                      <?php
                        $todayStart = strtotime('today');
                        $todayEnd = strtotime('tomorrow');
                      ?>
                      <div>
                        <?php if ($followUpTimestamp < $todayStart && !$isTerminal): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-100 text-red-800 border border-red-200" title="Dateline overdue">
                            <svg class="w-3 h-3 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Overdue: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php elseif ($followUpTimestamp >= $todayStart && $followUpTimestamp < $todayEnd && !$isTerminal): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200" title="Follow-up due today">
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Due Today (<?= date('g:i A', $followUpTimestamp) ?>)
                          </span>
                        <?php else: ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200" title="Scheduled follow-up">
                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Follow-up: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <!-- Reopened Indicator -->
                    <?php if (!empty($app['reopened_at'])): ?>
                      <div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">
                          Reopened on <?= date('M j', strtotime($app['reopened_at'])) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-500 text-[11px]">
                  <?= esc(date('M d, Y', strtotime($app['created_at'] ?? 'now'))) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Update Status / Follow-up Dateline Action -->
                    <button type="button" 
                      onclick="openStatusModal(<?= esc(json_encode([
                        'id' => $app['id'],
                        'candidate_name' => $candidateName,
                        'job_title' => $app['job_title'] ?? 'Role',
                        'status' => $appSt,
                        'follow_up_date' => !empty($app['follow_up_date']) ? date('Y-m-d\TH:i', strtotime($app['follow_up_date'])) : '',
                        'recruiter_notes' => $app['recruiter_notes'] ?? '',
                        'is_terminal' => $isTerminal
                      ])) ?>)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold transition shadow-sm text-xs cursor-pointer">
                      <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      <span><?= $isTerminal ? 'Reopen / Manage' : 'Update &amp; Follow-up' ?></span>
                    </button>

                    <!-- Download Resume -->
                    <?php if (!empty($app['document_id']) || !empty($app['resume_path'])): ?>
                      <a href="<?= base_url('admin/applications/resume/' . $app['id']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-light text-brand-primary hover:bg-emerald-100 font-bold transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Resume</span>
                      </a>
                    <?php endif; ?>
                  </div>
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
            <a href="<?= base_url('admin/applications?' . $prevQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">&larr; Previous</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <?php 
              $nextQuery = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= base_url('admin/applications?' . $nextQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── INTERACTIVE STATUS & FOLLOW-UP DATELINE MODAL DIALOG ──────────────────── -->
<div id="statusModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8">
    <button type="button" onclick="closeStatusModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer">&times;</button>

    <div class="mb-5">
      <span class="text-[11px] font-extrabold uppercase tracking-wider text-brand-primary">Recruitment Pipeline Action</span>
      <h3 id="modalCandidateName" class="text-xl font-black text-slate-900 mt-1">Update Application</h3>
      <p id="modalJobTitle" class="text-xs text-slate-500 mt-0.5">Role Position</p>
    </div>

    <form id="statusUpdateForm" method="POST" action="" class="space-y-4">
      <?= csrf_field() ?>

      <!-- Status Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Pipeline Status *</label>
        <select id="modalStatusSelect" name="status" onchange="updateAutomailNotice()" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
          <option value="APPLIED">Applied (New Profile)</option>
          <option value="SCREENING">Screening (Profile In Review)</option>
          <option value="SHORTLISTED">Shortlisted (Forwarded to Client Hiring Team)</option>
          <option value="INTERVIEW">Interview (Interview Scheduled)</option>
          <option value="OFFER">Offer (Formal Offer Extended)</option>
          <option value="HIRED">Hired (Candidate Placement Confirmed)</option>
          <option value="REJECTED">Rejected (Not Selected / Talent Pool Retained)</option>
          <option value="WITHDRAWN">Withdrawn (Mandate Concluded / Closed)</option>
        </select>
      </div>

      <!-- Follow-up Dateline -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Follow-up / Reopen Dateline (Due Date &amp; Time)</label>
        <input type="datetime-local" id="modalFollowUpDate" name="follow_up_date" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
        <span class="text-[10px] text-slate-400 mt-0.5 block">Sets an automatic review dateline. Overdue reminders appear on dashboard if not acted upon.</span>
      </div>

      <!-- Recruiter Notes / Comments -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Recruiter Notes / Reason / Feedback</label>
        <textarea id="modalComment" name="comment" rows="3" placeholder="Add specific notes, interview feedback, or screening details..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary"></textarea>
      </div>

      <!-- Automated Email Notice Callout -->
      <div id="automailNoticeBox" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-1">
        <div class="flex items-center gap-1.5 font-bold text-slate-800">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span id="automailNoticeTitle">Automated Notification Status</span>
        </div>
        <p id="automailNoticeDesc" class="text-[11px] leading-relaxed text-slate-500">
          No manual candidate emailing needed. When status changes to Rejected, Shortlisted, Interview, or Closed, a personalized, professional notification is dispatched automatically.
        </p>
      </div>

      <!-- Modal Action Buttons -->
      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="closeStatusModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-dark text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Save Pipeline &amp; Dateline</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openStatusModal(data) {
  document.getElementById('modalCandidateName').textContent = data.candidate_name;
  document.getElementById('modalJobTitle').textContent = data.job_title;
  document.getElementById('modalStatusSelect').value = data.status;
  document.getElementById('modalFollowUpDate').value = data.follow_up_date || '';
  document.getElementById('modalComment').value = data.recruiter_notes || '';
  document.getElementById('statusUpdateForm').action = '<?= base_url('admin/applications/status/') ?>' + data.id;

  updateAutomailNotice();
  document.getElementById('statusModal').classList.remove('hidden');
}

function closeStatusModal() {
  document.getElementById('statusModal').classList.add('hidden');
}

function updateAutomailNotice() {
  const st = document.getElementById('modalStatusSelect').value;
  const title = document.getElementById('automailNoticeTitle');
  const desc = document.getElementById('automailNoticeDesc');
  const box = document.getElementById('automailNoticeBox');

  if (st === 'REJECTED') {
    box.className = 'p-3.5 rounded-xl bg-red-50 border border-red-200 text-xs text-red-900 space-y-1';
    title.textContent = 'Auto-Mail: Polite Rejection & Talent Bank Retention';
    desc.textContent = 'A respectful rejection letter will be sent automatically. The candidate is informed their credentials are preserved in TalentHub\'s Executive Talent Bank for future openings.';
  } else if (st === 'WITHDRAWN') {
    box.className = 'p-3.5 rounded-xl bg-slate-100 border border-slate-200 text-xs text-slate-800 space-y-1';
    title.textContent = 'Auto-Mail: Mandate Concluded Notice';
    desc.textContent = 'A formal notice confirming this position has closed will be sent automatically, thanking the candidate for their time.';
  } else if (st === 'SHORTLISTED') {
    box.className = 'p-3.5 rounded-xl bg-indigo-50 border border-indigo-200 text-xs text-indigo-900 space-y-1';
    title.textContent = 'Auto-Mail: Shortlist Notification';
    desc.textContent = 'An encouraging shortlist update will be dispatched informing the applicant that their resume has been advanced to the client hiring manager.';
  } else if (st === 'INTERVIEW') {
    box.className = 'p-3.5 rounded-xl bg-purple-50 border border-purple-200 text-xs text-purple-900 space-y-1';
    title.textContent = 'Auto-Mail: Interview Invitation & Briefing';
    desc.textContent = 'An interview readiness briefing email will be dispatched automatically with recruiter contact details.';
  } else if (st === 'OFFER') {
    box.className = 'p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 space-y-1';
    title.textContent = 'Pipeline Notice: Offer Extended';
    desc.textContent = 'Record candidate compensation details and formal acceptance datelines in notes.';
  } else {
    box.className = 'p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-1';
    title.textContent = 'Internal Status Transition';
    desc.textContent = 'Internal review state. Candidate will be notified automatically if moved to Shortlisted, Interview, Rejected, or Concluded.';
  }
}

  // Bulk Selection Engine
  const bulkSelectAll = document.getElementById('bulk-select-all');
  const bulkCheckboxes = document.querySelectorAll('.bulk-app-checkbox');
  const bulkBar = document.getElementById('bulk-action-bar');
  const bulkCountSpan = document.getElementById('bulk-selected-count');
  const bulkForm = document.getElementById('bulk-action-form');
  const bulkClearBtn = document.getElementById('bulk-clear-btn');

  function updateBulkState() {
    const checked = Array.from(bulkCheckboxes).filter(c => c.checked);
    if (checked.length > 0) {
      bulkCountSpan.textContent = checked.length + ' application(s) selected';
      bulkBar.classList.remove('hidden');
      bulkBar.classList.add('flex');
    } else {
      bulkBar.classList.add('hidden');
      bulkBar.classList.remove('flex');
      if (bulkSelectAll) bulkSelectAll.checked = false;
    }
  }

  bulkSelectAll?.addEventListener('change', () => {
    bulkCheckboxes.forEach(c => c.checked = bulkSelectAll.checked);
    updateBulkState();
  });

  bulkCheckboxes.forEach(c => c.addEventListener('change', updateBulkState));

  bulkClearBtn?.addEventListener('click', () => {
    bulkCheckboxes.forEach(c => c.checked = false);
    if (bulkSelectAll) bulkSelectAll.checked = false;
    updateBulkState();
  });

  bulkForm?.addEventListener('submit', (e) => {
    bulkForm.querySelectorAll('input[name="selected_ids[]"]').forEach(el => el.remove());
    const checked = Array.from(bulkCheckboxes).filter(c => c.checked);
    if (checked.length === 0) {
      e.preventDefault();
      alert('Please select at least one application.');
      return;
    }
    const actionType = document.getElementById('bulk-action-type').value;
    if (actionType === 'transition') {
      const targetStatus = document.getElementById('bulk-target-status').value;
      if (!targetStatus) {
        e.preventDefault();
        alert('Please select a target pipeline stage.');
        return;
      }
    }
    checked.forEach(c => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_ids[]';
      input.value = c.value;
      bulkForm.appendChild(input);
    });
  });
</script>
