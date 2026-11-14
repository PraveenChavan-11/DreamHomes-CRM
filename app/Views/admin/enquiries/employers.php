<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Employer Hiring Inquiries</h1>
      <p class="text-xs text-slate-500 mt-1">Corporate talent acquisition requests, staffing mandates, follow-up datelines, and automated corporate communications</p>
    </div>

    <!-- Export Action -->
    <div class="flex items-center gap-3">
      <?php 
        $exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
      ?>
      <a href="<?= base_url('admin/enquiries/employers?' . $exportQuery) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <form action="<?= base_url('admin/enquiries/employers') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
      <div class="relative w-full sm:w-72">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search company, contact person, roles..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="status" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Statuses</option>
        <option value="NEW" <?= (strtoupper($status ?? '') === 'NEW') ? 'selected' : '' ?>>New</option>
        <option value="CONTACTED" <?= (strtoupper($status ?? '') === 'CONTACTED') ? 'selected' : '' ?>>Contacted</option>
        <option value="IN_DISCUSSION" <?= (strtoupper($status ?? '') === 'IN_DISCUSSION') ? 'selected' : '' ?>>In Discussion</option>
        <option value="MANDATE_WON" <?= (strtoupper($status ?? '') === 'MANDATE_WON') ? 'selected' : '' ?>>Mandate Won</option>
        <option value="CLOSED_LOST" <?= (strtoupper($status ?? '') === 'CLOSED_LOST') ? 'selected' : '' ?>>Closed / Lost</option>
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
        <a href="<?= base_url('admin/enquiries/employers') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium">
      Total Mandates: <span class="font-bold text-slate-800"><?= esc($totalCount ?? count($enquiries)) ?></span>
    </div>
  </div>

  <!-- Inquiries Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Company &amp; Contact</th>
            <th class="px-6 py-4">Required Roles</th>
            <th class="px-6 py-4">Positions &amp; Location</th>
            <th class="px-6 py-4">Urgency</th>
            <th class="px-6 py-4">Status &amp; Follow-up</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($enquiries)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                No employer hiring inquiries found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($enquiries as $emp): ?>
              <?php 
                $empSt = strtoupper($emp['status'] ?? 'NEW');
                $followUpTimestamp = !empty($emp['follow_up_date']) ? strtotime($emp['follow_up_date']) : null;
                $isTerminal = in_array($empSt, ['CLOSED_LOST', 'MANDATE_WON']);
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($emp['company_name'] ?? 'Company') ?></div>
                  <div class="text-[11px] text-slate-500 mt-0.5">
                    <span class="font-semibold text-slate-700"><?= esc($emp['contact_person'] ?? '') ?></span>
                    <div class="text-slate-400">
                      <a href="mailto:<?= esc($emp['email']) ?>" class="hover:text-brand-primary"><?= esc($emp['email']) ?></a>
                      &bull; <span class="font-mono"><?= esc($emp['phone'] ?? '') ?></span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 max-w-xs">
                  <div class="font-bold text-slate-800"><?= esc($emp['required_roles'] ?? ($emp['positions_needed'] ?? 'Recruitment Mandate')) ?></div>
                  <?php if (!empty($emp['experience_bracket'])): ?>
                    <div class="text-[10px] text-slate-500 mt-0.5">Exp: <?= esc($emp['experience_bracket']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($emp['notes'])): ?>
                    <div class="mt-1 text-[10px] text-slate-500 bg-slate-50 p-1.5 rounded border border-slate-100 line-clamp-2">
                      <span class="font-bold text-slate-700">Notes:</span> <?= esc($emp['notes']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($emp['follow_up_notes'])): ?>
                    <div class="mt-1 text-[10px] text-emerald-800 bg-emerald-50/70 p-1.5 rounded border border-emerald-100">
                      <span class="font-bold text-emerald-900">Follow-up:</span> <?= esc($emp['follow_up_notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-slate-600">
                  <div class="font-semibold"><?= esc($emp['number_of_positions'] ?? 1) ?> Opening(s)</div>
                  <div class="text-[10px] text-slate-400"><?= esc($emp['location'] ?? 'Pune corridor') ?></div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                    <?= esc(str_replace('_', ' ', $emp['urgency'] ?? 'Immediate')) ?>
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1.5">
                    <div>
                      <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border
                        <?= ($empSt === 'NEW') ? 'bg-amber-50 text-amber-700 border-amber-200' : '' ?>
                        <?= ($empSt === 'CONTACTED') ? 'bg-sky-50 text-sky-700 border-sky-200' : '' ?>
                        <?= ($empSt === 'IN_DISCUSSION') ? 'bg-purple-50 text-purple-700 border-purple-200' : '' ?>
                        <?= ($empSt === 'MANDATE_WON') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' ?>
                        <?= ($empSt === 'CLOSED_LOST') ? 'bg-slate-100 text-slate-600 border-slate-200' : '' ?>
                      ">
                        <?= esc(str_replace('_', ' ', $empSt)) ?>
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
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-100 text-red-800 border border-red-200">
                            <svg class="w-3 h-3 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Overdue: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php elseif ($followUpTimestamp >= $todayStart && $followUpTimestamp < $todayEnd && !$isTerminal): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Due Today (<?= date('g:i A', $followUpTimestamp) ?>)
                          </span>
                        <?php else: ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Follow-up: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($emp['reopened_at'])): ?>
                      <div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">
                          Reopened on <?= date('M j', strtotime($emp['reopened_at'])) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <button type="button" 
                    onclick="openEmployerModal(<?= esc(json_encode([
                      'id' => $emp['id'],
                      'company_name' => $emp['company_name'] ?? 'Company',
                      'contact_person' => $emp['contact_person'] ?? '',
                      'status' => $empSt,
                      'follow_up_date' => !empty($emp['follow_up_date']) ? date('Y-m-d\TH:i', strtotime($emp['follow_up_date'])) : '',
                      'notes' => $emp['notes'] ?? '',
                      'is_terminal' => $isTerminal
                    ])) ?>)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold transition shadow-sm text-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span><?= $isTerminal ? 'Reopen Mandate' : 'Manage &amp; Follow-up' ?></span>
                  </button>
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
            <a href="<?= base_url('admin/enquiries/employers?' . $prevQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">&larr; Previous</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <?php 
              $nextQuery = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= base_url('admin/enquiries/employers?' . $nextQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── INTERACTIVE EMPLOYER STATUS & FOLLOW-UP MODAL ────────────────────────── -->
<div id="employerModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8">
    <button type="button" onclick="closeEmployerModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer">&times;</button>

    <div class="mb-5">
      <span class="text-[11px] font-extrabold uppercase tracking-wider text-amber-600">Corporate Staffing Mandate</span>
      <h3 id="modalCompanyName" class="text-xl font-black text-slate-900 mt-1">Company Mandate</h3>
      <p id="modalContactPerson" class="text-xs text-slate-500 mt-0.5">Contact Person</p>
    </div>

    <form id="employerUpdateForm" method="POST" action="" class="space-y-4">
      <?= csrf_field() ?>

      <!-- Status Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Mandate Status *</label>
        <select id="modalEmpStatusSelect" name="status" onchange="updateEmpAutomailNotice()" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
          <option value="NEW">New (Initial Request)</option>
          <option value="CONTACTED">Contacted (Introductory Brief Sent)</option>
          <option value="IN_DISCUSSION">In Discussion (Scope &amp; Commercials)</option>
          <option value="MANDATE_WON">Mandate Won (Agreement Executed)</option>
          <option value="CLOSED_LOST">Closed / Lost (Mandate Concluded or Inactive)</option>
        </select>
      </div>

      <!-- Follow-up Dateline -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Follow-up / Reopen Dateline (Due Date &amp; Time)</label>
        <input type="datetime-local" id="modalEmpFollowUpDate" name="follow_up_date" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
        <span class="text-[10px] text-slate-400 mt-0.5 block">Schedule next commercial checkpoint or mandate review.</span>
      </div>

      <!-- Recruiter Notes / Comments -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Consultant Notes / Action Items</label>
        <textarea id="modalEmpNotes" name="notes" rows="3" placeholder="Meeting recap, commercial fee agreements, or follow-up summary..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary"></textarea>
      </div>

      <!-- Automated Email Notice Callout -->
      <div id="empAutomailNoticeBox" class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-1">
        <div class="flex items-center gap-1.5 font-bold text-slate-800">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span id="empAutomailNoticeTitle">Mandate Communication Mode</span>
        </div>
        <p id="empAutomailNoticeDesc" class="text-[11px] leading-relaxed text-slate-500">
          Status updates are logged in the audit trail. When mandate is marked Closed/Lost, an executive conclusion notice is dispatched automatically.
        </p>
      </div>

      <!-- Modal Action Buttons -->
      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="closeEmployerModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-dark text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Save Mandate &amp; Dateline</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openEmployerModal(data) {
  document.getElementById('modalCompanyName').textContent = data.company_name;
  document.getElementById('modalContactPerson').textContent = data.contact_person || 'Corporate Mandate';
  document.getElementById('modalEmpStatusSelect').value = data.status;
  document.getElementById('modalEmpFollowUpDate').value = data.follow_up_date || '';
  document.getElementById('modalEmpNotes').value = data.notes || '';
  document.getElementById('employerUpdateForm').action = '<?= base_url('admin/enquiries/employers/status/') ?>' + data.id;

  updateEmpAutomailNotice();
  document.getElementById('employerModal').classList.remove('hidden');
}

function closeEmployerModal() {
  document.getElementById('employerModal').classList.add('hidden');
}

function updateEmpAutomailNotice() {
  const st = document.getElementById('modalEmpStatusSelect').value;
  const title = document.getElementById('empAutomailNoticeTitle');
  const desc = document.getElementById('empAutomailNoticeDesc');
  const box = document.getElementById('empAutomailNoticeBox');

  if (st === 'CLOSED_LOST') {
    box.className = 'p-3.5 rounded-xl bg-slate-100 border border-slate-300 text-xs text-slate-800 space-y-1';
    title.textContent = 'Auto-Mail: Executive Mandate Closure Communication';
    desc.textContent = 'A polite corporate closing communication is sent automatically to the employer, keeping the door open for future hiring mandates.';
  } else if (st === 'MANDATE_WON') {
    box.className = 'p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 space-y-1';
    title.textContent = 'Mandate Won: Active Search Initiated';
    desc.textContent = 'Mandate successfully confirmed. Recruiters can now associate candidate shortlists with this partner.';
  } else {
    box.className = 'p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 space-y-1';
    title.textContent = 'Active Negotiation & Follow-up State';
    desc.textContent = 'Set a follow-up dateline to keep the hiring conversation active.';
  }
}
</script>
