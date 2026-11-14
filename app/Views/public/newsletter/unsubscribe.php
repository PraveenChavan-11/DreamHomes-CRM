<?= $this->extend('public/layouts/main') ?>

<?= $this->section('content') ?>
<div class="min-h-[75vh] flex items-center justify-center py-20 px-4 sm:px-6 bg-[#FAF9F5]">
  <div class="max-w-lg w-full bg-white rounded-3xl shadow-2xl border border-[rgba(29,106,52,0.12)] p-6 sm:p-10 relative overflow-hidden">
    <!-- Top accent bar -->
    <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-red-500 via-rose-500 to-amber-500"></div>

    <!-- Active Form Panel -->
    <div id="unsub-init-panel">
      <!-- Icon -->
      <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center mx-auto mb-6 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
      </div>

      <div class="text-center mb-6">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-red-50 text-red-700 border border-red-100 mb-2">
          TalentHub Email Management
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] tracking-tight">
          Unsubscribe from Updates
        </h1>
        <p class="text-xs sm:text-sm text-[#3d6350] mt-2 leading-relaxed">
          We are sorry to see you go. If our updates are too frequent or not matching your career goals, you can adjust your preferences or confirm unsubscription below.
        </p>
      </div>

      <form id="form-unsub" class="space-y-5">
        <!-- Email Input (shown if no token in URL) -->
        <?php if (empty($token)): ?>
        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1.5">
            Registered Work / Personal Email Address *
          </label>
          <input type="email" id="unsub-email" required placeholder="Enter your email to unsubscribe..." class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs text-[#172B22] focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
        </div>
        <?php else: ?>
        <input type="hidden" id="unsub-email" value="">
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-[#3d6350] flex items-center justify-between">
          <span class="flex items-center gap-1.5 font-semibold">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Verified link authenticated
          </span>
          <span class="text-slate-400">1-Click Fast Track</span>
        </div>
        <?php endif; ?>

        <!-- Reason Selection -->
        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-2 uppercase tracking-wide">
            Please select a reason (Helps us improve):
          </label>
          <div class="space-y-2">
            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-[#1D6A34] cursor-pointer transition bg-slate-50/50">
              <input type="radio" name="unsub_reason" value="too_frequent" checked class="text-red-600 focus:ring-red-500">
              <div class="text-xs">
                <span class="font-bold text-[#172B22] block">Emails are too frequent</span>
                <span class="text-[11px] text-[#3d6350]">I receive more communications than I would prefer.</span>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-[#1D6A34] cursor-pointer transition bg-slate-50/50">
              <input type="radio" name="unsub_reason" value="not_relevant" class="text-red-600 focus:ring-red-500">
              <div class="text-xs">
                <span class="font-bold text-[#172B22] block">Content is no longer relevant</span>
                <span class="text-[11px] text-[#3d6350]">Job postings or industry insights don't match my profile.</span>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-[#1D6A34] cursor-pointer transition bg-slate-50/50">
              <input type="radio" name="unsub_reason" value="job_found" class="text-red-600 focus:ring-red-500">
              <div class="text-xs">
                <span class="font-bold text-[#172B22] block">Found a new job / Not actively looking</span>
                <span class="text-[11px] text-[#3d6350]">I am happily placed and pausing career searches for now.</span>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-[#1D6A34] cursor-pointer transition bg-slate-50/50">
              <input type="radio" name="unsub_reason" value="never_subscribed" class="text-red-600 focus:ring-red-500">
              <div class="text-xs">
                <span class="font-bold text-[#172B22] block">Never signed up</span>
                <span class="text-[11px] text-[#3d6350]">I did not intend to subscribe to this publication.</span>
              </div>
            </label>

            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-[#1D6A34] cursor-pointer transition bg-slate-50/50">
              <input type="radio" name="unsub_reason" value="other" class="text-red-600 focus:ring-red-500">
              <div class="text-xs">
                <span class="font-bold text-[#172B22] block">Other reason</span>
                <span class="text-[11px] text-[#3d6350]">I have another specific reason.</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Optional Comments -->
        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">
            Additional Feedback / Comments (Optional):
          </label>
          <textarea id="unsub-comments" rows="2" placeholder="Tell us how we can serve professionals better..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-[#172B22] focus:outline-none focus:border-red-500"></textarea>
        </div>

        <div id="unsub-alert" class="hidden text-xs p-3 rounded-xl"></div>

        <!-- Action Buttons -->
        <div class="pt-2 flex flex-col gap-2.5">
          <button type="submit" id="btn-do-unsub" class="w-full py-3.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Confirm One-Click Unsubscribe</span>
          </button>

          <a href="<?= base_url('newsletter/preferences' . (!empty($token) ? '?token=' . esc($token) : '')) ?>" class="w-full py-3 px-4 rounded-xl border border-[rgba(29,106,52,0.3)] bg-[#EAF5EF]/60 text-[#1D6A34] font-bold text-xs hover:bg-[#EAF5EF] transition-all text-center block">
            Fewer Emails? Adjust Preferences Instead &rarr;
          </a>
        </div>
      </form>
    </div>

    <!-- Done State (Hidden by default) -->
    <div id="unsub-done-panel" class="hidden text-center py-4">
      <div class="w-20 h-20 rounded-3xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6 shadow-sm">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      </div>

      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100 mb-2">
        Request Completed
      </span>
      <h2 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] mb-3">
        You Have Been Unsubscribed
      </h2>
      <p class="text-[#3d6350] text-xs sm:text-sm leading-relaxed mb-6 max-w-sm mx-auto">
        Your email address has been safely removed from our marketing and news mailings. Transactional emails regarding active applications will remain active if applicable.
      </p>

      <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-6 text-left space-y-2">
        <div class="flex items-center gap-2 text-xs font-bold text-[#172B22]">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <span>Suppression List Enforced</span>
        </div>
        <p class="text-[11px] text-[#3d6350]">
          You will not receive future promotional broadcasts unless you explicitly reactivate your subscription.
        </p>
      </div>

      <div class="flex flex-col gap-2.5">
        <button type="button" id="btn-resubscribe" class="w-full py-3 px-4 rounded-xl border-2 border-[#1D6A34] text-[#1D6A34] hover:bg-[#1D6A34] hover:text-white font-extrabold text-xs transition-all shadow-sm">
          Changed your mind? Click to Resubscribe
        </button>
        <a href="<?= base_url() ?>" class="w-full py-2.5 px-4 rounded-xl text-slate-500 hover:text-slate-800 text-xs font-semibold transition">
          Return to TalentHub Home
        </a>
      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const rawToken = <?= json_encode($token ?? '') ?>;
  const formUnsub = document.getElementById('form-unsub');
  const btnUnsub = document.getElementById('btn-do-unsub');
  const btnResub = document.getElementById('btn-resubscribe');
  const initPanel = document.getElementById('unsub-init-panel');
  const donePanel = document.getElementById('unsub-done-panel');
  const alertBox = document.getElementById('unsub-alert');
  let currentEmail = '';

  if (formUnsub) {
    formUnsub.addEventListener('submit', async (e) => {
      e.preventDefault();
      btnUnsub.disabled = true;
      btnUnsub.innerHTML = '<span>Processing Unsubscribe...</span>';
      alertBox.classList.add('hidden');

      const emailInput = document.getElementById('unsub-email');
      const email = emailInput ? emailInput.value.trim() : '';
      currentEmail = email;

      const reasonRadio = document.querySelector('input[name="unsub_reason"]:checked');
      const reasonVal = reasonRadio ? reasonRadio.value : 'unspecified';
      const comments = document.getElementById('unsub-comments')?.value || '';
      const fullReason = comments ? (reasonVal + ': ' + comments) : reasonVal;

      try {
        const res = await fetch('<?= base_url('api/v1/newsletter/unsubscribe') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            token: rawToken,
            email: email,
            reason: fullReason,
          }),
        });

        const data = await res.json();
        if (data.success) {
          initPanel.classList.add('hidden');
          donePanel.classList.remove('hidden');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          alertBox.className = 'text-xs p-3 rounded-xl bg-red-50 border border-red-200 text-red-700';
          alertBox.innerText = data.message || 'Could not unsubscribe. Please verify your email or link.';
          alertBox.classList.remove('hidden');
          btnUnsub.disabled = false;
          btnUnsub.innerHTML = '<span>Confirm One-Click Unsubscribe</span>';
        }
      } catch (err) {
        alertBox.className = 'text-xs p-3 rounded-xl bg-red-50 border border-red-200 text-red-700';
        alertBox.innerText = 'Network connection error. Please try again.';
        alertBox.classList.remove('hidden');
        btnUnsub.disabled = false;
        btnUnsub.innerHTML = '<span>Confirm One-Click Unsubscribe</span>';
      }
    });
  }

  if (btnResub) {
    btnResub.addEventListener('click', async () => {
      btnResub.disabled = true;
      btnResub.innerText = 'Reactivating subscription...';

      try {
        const res = await fetch('<?= base_url('api/v1/newsletter/resubscribe') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            token: rawToken,
            email: currentEmail,
          }),
        });
        const data = await res.json();
        if (data.success) {
          alert(data.message || 'Welcome back! Your subscription is reactivated.');
          window.location.href = '<?= base_url() ?>';
        } else {
          alert(data.message || 'Error reactivating subscription.');
          btnResub.disabled = false;
          btnResub.innerText = 'Changed your mind? Click to Resubscribe';
        }
      } catch (e) {
        alert('Network connection error.');
        btnResub.disabled = false;
        btnResub.innerText = 'Changed your mind? Click to Resubscribe';
      }
    });
  }
});
</script>
<?= $this->endSection() ?>
