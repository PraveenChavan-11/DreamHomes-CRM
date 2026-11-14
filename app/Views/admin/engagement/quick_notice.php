<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="p-2 rounded-xl bg-amber-500/10 text-amber-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </span>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">1-Click Direct Notice &amp; Announcement Dispatcher</h1>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Directly broadcast official company notices, urgent recruitment drives, and alerts to all subscribed members in 1 click.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/engagement/subscribers') ?>" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
        View Subscribers (<?= esc($totalSubscribers) ?>)
      </a>
      <a href="<?= base_url('admin/mail') ?>" class="px-4 py-2 rounded-xl bg-brand-primary text-xs font-bold text-white hover:bg-brand-hover transition shadow-sm">
        Outbox Queue
      </a>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">All Verified Recipients</p>
        <p class="text-2xl font-black text-slate-800 mt-1"><?= number_format($totalSubscribers) ?></p>
        <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Double opt-in authenticated</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Candidate Subscribers</p>
        <p class="text-2xl font-black text-slate-800 mt-1"><?= number_format($candidateSubscribers) ?></p>
        <p class="text-[11px] text-brand-primary font-semibold mt-0.5">Job seekers & applicants</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Employer Subscribers</p>
        <p class="text-2xl font-black text-slate-800 mt-1"><?= number_format($employerSubscribers) ?></p>
        <p class="text-[11px] text-purple-600 font-semibold mt-0.5">Corporate client contacts</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      </div>
    </div>
  </div>

  <!-- Main Broadcast Composer -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Column -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
      <h2 class="text-base font-black text-slate-800 mb-1 flex items-center gap-2">
        <span>Compose Announcement Notice</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800">Instant Broadcast</span>
      </h2>
      <p class="text-xs text-slate-500 mb-6">
        Fill the details below. Once confirmed, this announcement will be queued immediately into the outbox for every verified subscriber.
      </p>

      <form action="<?= base_url('admin/engagement/quick-notice/send') ?>" method="POST" id="quick-notice-form" class="space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Target Audience -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Target Audience *
            </label>
            <select name="audience" id="notice-audience" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3 bg-slate-50">
              <option value="all">All Verified Subscribers (<?= esc($totalSubscribers) ?> total)</option>
              <option value="candidates">Candidate &amp; Job Seekers Only</option>
              <option value="employers">Corporate Employers Only</option>
            </select>
          </div>

          <!-- Priority Badge -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Notice Badge / Tag
            </label>
            <select name="priority_badge" id="notice-badge" class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3 bg-slate-50">
              <option value="Official Notice">Official Notice</option>
              <option value="Urgent Announcement">Urgent Announcement</option>
              <option value="Recruitment Drive">Recruitment Drive</option>
              <option value="Executive Update">Executive Update</option>
              <option value="Service Update">Service Update</option>
              <option value="Festive Greetings">Festive Greetings</option>
            </select>
          </div>
        </div>

        <!-- Subject -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Email Subject Line *
          </label>
          <input type="text" name="subject" id="notice-subject" required placeholder="e.g. Official Update: Pune Engineering Recruitment Drive 2026" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Public Headline -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Announcement Headline (Inside Email) *
          </label>
          <input type="text" name="headline" id="notice-headline" required placeholder="e.g. Special Hiring Mandates Across Hinjawadi IT Park & Chakan MIDC" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Message Body -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Notice Message Content *
          </label>
          <textarea name="content" id="notice-content" rows="6" required placeholder="Write the announcement message here. HTML line breaks are automatically supported..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3 leading-relaxed"></textarea>
          <span class="text-[11px] text-slate-400 mt-1 block">
            Supports personalizations: {{subscriber_name}}, {{unsubscribe_url}}, {{preferences_url}}.
          </span>
        </div>

        <!-- Optional CTA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Call to Action Button Text (Optional)
            </label>
            <input type="text" name="cta_text" id="notice-cta-text" placeholder="e.g. Explore Active Openings" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Call to Action URL (Optional)
            </label>
            <input type="url" name="cta_url" id="notice-cta-url" placeholder="https://talenthubconsultant.com/jobs" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
          </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Idempotency-guaranteed &bull; Zero duplicate sends</span>
          </div>

          <button type="button" onclick="confirmDispatch()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/25 transition-all flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>🚀 1-Click Send Direct Notice</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Live Preview Card Column -->
    <div class="space-y-6">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
          <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <span>Live Email Client Preview</span>
        </h3>

        <!-- Mock Email Container -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-slate-50">
          <div class="bg-gradient-to-r from-[#072412] to-[#1D6A34] p-4 text-center text-white">
            <p class="font-extrabold text-sm tracking-tight text-white m-0">TalentHub Consultant</p>
            <p class="text-[9px] text-[#37CE97] uppercase tracking-wider m-0 font-bold">Official Communications Desk</p>
          </div>
          <div class="p-5 bg-white space-y-3">
            <span id="prev-badge" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-50 text-amber-800 border border-amber-200">
              Official Notice
            </span>
            <h4 id="prev-headline" class="text-sm font-black text-slate-800 leading-snug">
              Announcement Headline
            </h4>
            <p class="text-[11px] text-slate-500">Dear [Subscriber Name],</p>
            <p id="prev-content" class="text-xs text-slate-700 leading-relaxed font-normal whitespace-pre-line">
              Your announcement message will render here cleanly with brand typography and styling.
            </p>
            <div id="prev-cta-wrap" class="hidden text-center pt-2">
              <span id="prev-cta-btn" class="inline-block px-4 py-2 rounded-lg bg-[#1D6A34] text-white text-[11px] font-bold shadow-sm">
                Call to Action &rarr;
              </span>
            </div>
            <div class="border-t border-slate-100 pt-3 text-[10px] text-slate-400 text-center">
              TalentHub Pune &bull; 1-Click Unsubscribe
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Delivery Guidelines -->
      <div class="bg-amber-50/60 rounded-3xl border border-amber-200/60 p-6 space-y-3">
        <div class="flex items-center gap-2 text-xs font-bold text-amber-900">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Delivery &amp; Compliance Safeguards</span>
        </div>
        <ul class="text-[11px] text-amber-800/90 space-y-1.5 list-disc list-inside">
          <li>Emails are sent strictly to <strong>verified double opt-in</strong> subscribers.</li>
          <li>Unsubscribed or suppressed emails are automatically skipped.</li>
          <li>Every email includes a mandatory 1-click unsubscribe header and footer.</li>
          <li>Outbox scheduler processes delivery smoothly in rate-limited batches.</li>
        </ul>
      </div>

    </div>

  </div>

  <!-- Recent Broadcast Logs -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800">Recent Direct Notice Outbox Dispatches</h3>
      <span class="text-xs text-slate-400"><?= count($recentBroadcasts) ?> recent items</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-100">
            <th class="py-3 px-6">Recipient</th>
            <th class="py-3 px-6">Subject</th>
            <th class="py-3 px-6">Status</th>
            <th class="py-3 px-6">Queued Timestamp</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!empty($recentBroadcasts)): ?>
            <?php foreach ($recentBroadcasts as $b): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="py-3 px-6">
                  <div class="font-bold text-slate-800"><?= esc($b['recipient_name'] ?: 'Subscriber') ?></div>
                  <div class="text-[11px] text-slate-400"><?= esc($b['recipient_email']) ?></div>
                </td>
                <td class="py-3 px-6 text-slate-700 font-medium">
                  <?= esc($b['subject']) ?>
                </td>
                <td class="py-3 px-6">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($b['status'] === 'SENT') ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                    <?= esc($b['status']) ?>
                  </span>
                </td>
                <td class="py-3 px-6 text-slate-400 text-[11px]">
                  <?= date('d M Y, h:i A', strtotime($b['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="py-8 text-center text-slate-400">
                No direct notices dispatched yet. Use the composer above to broadcast your first announcement.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100 text-center space-y-4">
    <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>

    <h3 class="text-xl font-extrabold text-slate-800">Confirm 1-Click Broadcast</h3>
    <p class="text-xs text-slate-600 leading-relaxed">
      You are about to queue an official notice to <strong class="text-slate-900" id="modal-recipients-count"><?= esc($totalSubscribers) ?> subscribers</strong>. Are you sure you want to proceed with immediate dispatch?
    </p>

    <div class="flex items-center gap-3 pt-2">
      <button type="button" onclick="closeConfirmModal()" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
        Cancel
      </button>
      <button type="button" onclick="submitNoticeForm()" class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black transition shadow-md shadow-amber-500/20">
        Yes, Broadcast Now
      </button>
    </div>
  </div>
</div>

<script>
function updateConfirmPreview() {
  const badge = document.getElementById('notice-badge').value;
  const headline = document.getElementById('notice-headline').value || 'Announcement Headline';
  const content = document.getElementById('notice-content').value || 'Your announcement message will render here cleanly with brand typography and styling.';
  const ctaText = document.getElementById('notice-cta-text').value;

  document.getElementById('prev-badge').innerText = badge;
  document.getElementById('prev-headline').innerText = headline;
  document.getElementById('prev-content').innerText = content;

  const ctaWrap = document.getElementById('prev-cta-wrap');
  if (ctaText.trim()) {
    document.getElementById('prev-cta-btn').innerText = ctaText + ' →';
    ctaWrap.classList.remove('hidden');
  } else {
    ctaWrap.classList.add('hidden');
  }
}

document.getElementById('notice-badge').addEventListener('change', updateConfirmPreview);
document.getElementById('notice-headline').addEventListener('input', updateConfirmPreview);
document.getElementById('notice-content').addEventListener('input', updateConfirmPreview);
document.getElementById('notice-cta-text').addEventListener('input', updateConfirmPreview);

function confirmDispatch() {
  const form = document.getElementById('quick-notice-form');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  document.getElementById('confirm-modal').classList.remove('hidden');
  document.getElementById('confirm-modal').classList.add('flex');
}

function closeConfirmModal() {
  document.getElementById('confirm-modal').classList.add('hidden');
  document.getElementById('confirm-modal').classList.remove('flex');
}

function submitNoticeForm() {
  closeConfirmModal();
  document.getElementById('quick-notice-form').submit();
}
</script>
