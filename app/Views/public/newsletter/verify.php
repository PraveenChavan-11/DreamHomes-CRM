<?= $this->extend('public/layouts/main') ?>

<?= $this->section('content') ?>
<div class="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-slate-50">
  <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center relative overflow-hidden">
    <!-- Decorative top accent -->
    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-brand-primary via-brand-accent to-brand-primary"></div>

    <?php if (!empty($result['success'])): ?>
      <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-6 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      </div>

      <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Subscription Confirmed!</h1>
      <p class="text-slate-600 text-sm leading-relaxed mb-6">
        <?= esc($result['message'] ?? 'Your email has been successfully verified. Welcome to TalentHub Pune Recruitment Intelligence.') ?>
      </p>

      <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-6 text-left">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">What you'll receive:</div>
        <ul class="text-xs text-slate-600 space-y-1.5">
          <li class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-brand-primary shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>Weekly Pune IT & Manufacturing hiring intelligence</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-brand-primary shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>Exclusive Hinjawadi, Kharadi & Chakan executive mandates</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-brand-primary shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>Quarterly compensation benchmark reports</span>
          </li>
        </ul>
      </div>

      <div class="flex flex-col sm:flex-row gap-3">
        <a href="<?= base_url('jobs') ?>" class="flex-1 py-3 px-4 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover shadow-md transition-all">Explore Active Jobs</a>
        <a href="<?= base_url() ?>" class="flex-1 py-3 px-4 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-all">Back to Home</a>
      </div>

    <?php else: ?>
      <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-6 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>

      <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Verification Notice</h1>
      <p class="text-slate-600 text-sm leading-relaxed mb-6">
        <?= esc($result['message'] ?? 'This confirmation link is invalid or has expired.') ?>
      </p>

      <a href="<?= base_url() ?>" class="inline-block py-3 px-6 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover transition-all">Return to Homepage</a>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>
