<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Contact Messages</h1>
      <p class="text-xs text-slate-500 mt-1">General inquiries, corporate communication, follow-up datelines, and automated response communications</p>
    </div>

    <!-- Export Action -->
    <div class="flex items-center gap-3">
      <?php 
        $exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
      ?>
      <a href="<?= base_url('admin/enquiries/contact?' . $exportQuery) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <form action="<?= base_url('admin/enquiries/contact') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
      <div class="relative w-full sm:w-72">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search name, email, subject, message..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="status" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Statuses</option>
        <option value="UNREAD" <?= (strtoupper($status ?? '') === 'UNREAD') ? 'selected' : '' ?>>Unread (New)</option>
        <option value="READ" <?= (strtoupper($status ?? '') === 'READ') ? 'selected' : '' ?>>Read</option>
        <option value="RESPONDED" <?= (strtoupper($status ?? '') === 'RESPONDED') ? 'selected' : '' ?>>Responded</option>
        <option value="SPAM" <?= (strtoupper($status ?? '') === 'SPAM') ? 'selected' : '' ?>>Spam</option>
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
        <a href="<?= base_url('admin/enquiries/contact') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium">
      Total Inquiries: <span class="font-bold text-slate-800"><?= esc($totalCount ?? count($messages)) ?></span>
    </div>
  </div>

  <!-- Messages Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Sender</th>
            <th class="px-6 py-4">Subject &amp; Message</th>
            <th class="px-6 py-4">Status &amp; Follow-up</th>
            <th class="px-6 py-4">Received Date</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($messages)): ?>
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-xs">
                No contact messages match your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($messages as $c): ?>
              <?php 
                $cSt = strtoupper($c['status'] ?? 'UNREAD');
                $senderName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ($c['name'] ?? 'Inquirer');
                $followUpTimestamp = !empty($c['follow_up_date']) ? strtotime($c['follow_up_date']) : null;
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($senderName) ?></div>
                  <div class="text-[11px] text-slate-500 mt-0.5">
                    <a href="mailto:<?= esc($c['email']) ?>" class="hover:text-brand-primary"><?= esc($c['email']) ?></a>
                    <?php if (!empty($c['phone'])): ?>
                      &bull; <span class="font-mono text-slate-400"><?= esc($c['phone']) ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 max-w-md">
                  <div class="font-bold text-slate-800"><?= esc($c['subject'] ?? 'Website Inquiry') ?></div>
                  <div class="mt-1 text-slate-600 bg-slate-50 p-2 rounded-xl border border-slate-100 line-clamp-3">
                    <?= esc($c['message'] ?? '') ?>
                  </div>
                  <?php if (!empty($c['follow_up_notes'])): ?>
                    <div class="mt-1 text-[11px] text-emerald-800 bg-emerald-50/70 p-2 rounded-lg border border-emerald-100">
                      <span class="font-bold text-emerald-900">Reply Sent / Notes:</span> <?= esc($c['follow_up_notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                  <div class="space-y-1.5">
                    <div>
                      <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border
                        <?= ($cSt === 'UNREAD') ? 'bg-amber-50 text-amber-700 border-amber-200' : '' ?>
                        <?= ($cSt === 'READ') ? 'bg-sky-50 text-sky-700 border-sky-200' : '' ?>
                        <?= ($cSt === 'RESPONDED') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' ?>
                        <?= ($cSt === 'SPAM') ? 'bg-red-50 text-red-700 border-red-200' : '' ?>
                      ">
                        <?= esc($cSt) ?>
                      </span>
                    </div>

                    <?php if ($followUpTimestamp): ?>
                      <?php
                        $todayStart = strtotime('today');
                        $todayEnd = strtotime('tomorrow');
                      ?>
                      <div>
                        <?php if ($followUpTimestamp < $todayStart && $cSt !== 'RESPONDED'): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-red-100 text-red-800 border border-red-200">
                            <svg class="w-3 h-3 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Overdue: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php elseif ($followUpTimestamp >= $todayStart && $followUpTimestamp < $todayEnd && $cSt !== 'RESPONDED'): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Due Today
                          </span>
                        <?php else: ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200">
                            <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Follow-up: <?= date('M j, Y', $followUpTimestamp) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-500 text-[11px]">
                  <?= esc(date('M d, Y H:i', strtotime($c['created_at'] ?? 'now'))) ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <button type="button" 
                    onclick="openContactModal(<?= esc(json_encode([
                      'id' => $c['id'],
                      'sender_name' => $senderName,
                      'email' => $c['email'] ?? '',
                      'subject' => $c['subject'] ?? 'Inquiry',
                      'message' => $c['message'] ?? '',
                      'status' => $cSt,
                      'follow_up_date' => !empty($c['follow_up_date']) ? date('Y-m-d\TH:i', strtotime($c['follow_up_date'])) : '',
                      'notes' => $c['follow_up_notes'] ?? ''
                    ])) ?>)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold transition shadow-sm text-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Respond &amp; Follow-up</span>
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
            <a href="<?= base_url('admin/enquiries/contact?' . $prevQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">&larr; Previous</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <?php 
              $nextQuery = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= base_url('admin/enquiries/contact?' . $nextQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── INTERACTIVE CONTACT RESPONSE & FOLLOW-UP MODAL ───────────────────────── -->
<div id="contactModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8">
    <button type="button" onclick="closeContactModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-2xl font-bold cursor-pointer">&times;</button>

    <div class="mb-5">
      <span class="text-[11px] font-extrabold uppercase tracking-wider text-brand-primary">Contact Desk Communication</span>
      <h3 id="modalSenderName" class="text-xl font-black text-slate-900 mt-1">Sender Name</h3>
      <p id="modalSenderSubject" class="text-xs text-slate-500 mt-0.5">Subject</p>
    </div>

    <!-- Original Message Preview Box -->
    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 mb-4">
      <span class="block text-[10px] font-extrabold uppercase text-slate-400 mb-1">Inquiry Message:</span>
      <p id="modalOriginalMessage" class="text-xs text-slate-700 leading-relaxed max-h-28 overflow-y-auto"></p>
    </div>

    <form id="contactUpdateForm" method="POST" action="" class="space-y-4">
      <?= csrf_field() ?>

      <!-- Status Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Status *</label>
        <select id="modalContactStatusSelect" name="status" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
          <option value="UNREAD">Unread</option>
          <option value="READ">Read (Internal Review)</option>
          <option value="RESPONDED">Responded (Send Email Response to Sender)</option>
          <option value="SPAM">Spam</option>
        </select>
      </div>

      <!-- Follow-up Dateline -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Follow-up Dateline (Due Date &amp; Time)</label>
        <input type="datetime-local" id="modalContactFollowUpDate" name="follow_up_date" class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary">
      </div>

      <!-- Reply Message -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Automated Email Response Message to Inquirer</label>
        <textarea id="modalReplyMessage" name="reply_message" rows="4" placeholder="Type your response. When status is 'Responded', this email is dispatched immediately to the sender from TalentHub Recruitment Desk..." class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-primary"></textarea>
      </div>

      <!-- Modal Action Buttons -->
      <div class="pt-2 flex items-center justify-end gap-3">
        <button type="button" onclick="closeContactModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
          Cancel
        </button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-dark text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
          <span>Send Response &amp; Save Dateline</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openContactModal(data) {
  document.getElementById('modalSenderName').textContent = data.sender_name;
  document.getElementById('modalSenderSubject').textContent = data.subject + ' (' + data.email + ')';
  document.getElementById('modalOriginalMessage').textContent = data.message;
  document.getElementById('modalContactStatusSelect').value = data.status;
  document.getElementById('modalContactFollowUpDate').value = data.follow_up_date || '';
  document.getElementById('modalReplyMessage').value = data.notes || '';
  document.getElementById('contactUpdateForm').action = '<?= base_url('admin/enquiries/contact/status/') ?>' + data.id;

  document.getElementById('contactModal').classList.remove('hidden');
}

function closeContactModal() {
  document.getElementById('contactModal').classList.add('hidden');
}
</script>
