<?= $this->extend('public/layouts/main') ?>

<?= $this->section('content') ?>
<div class="min-h-[70vh] flex items-center justify-center py-20 px-4 bg-slate-50">
  <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-brand-primary to-slate-500"></div>

    <?php if (!empty($result['success'])): ?>
      <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center mx-auto mb-6">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      </div>

      <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Job Alerts Stopped</h1>
      <p class="text-slate-600 text-sm leading-relaxed mb-6">
        You have successfully unsubscribed from TalentHub automated job alert emails. You can always browse active openings or resubscribe on the Jobs portal.
      </p>

      <div class="flex flex-col sm:flex-row gap-3">
        <a href="<?= base_url('jobs') ?>" class="flex-1 py-3 px-4 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover shadow-md transition-all">Browse Active Jobs</a>
        <a href="<?= base_url() ?>" class="flex-1 py-3 px-4 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition-all">Return to Home</a>
      </div>
    <?php else: ?>
      <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-6">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>

      <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Unsubscribe Notice</h1>
      <p class="text-slate-600 text-sm leading-relaxed mb-6">
        <?= esc($result['message'] ?? 'Invalid or expired job alert token.') ?>
      </p>

      <a href="<?= base_url('jobs') ?>" class="inline-block py-3 px-6 rounded-xl bg-brand-primary text-white font-bold text-xs hover:bg-brand-hover transition-all">Browse Jobs Portal</a>
    <?php endif; ?>
  </div>
</div>
<?= $this->endSection() ?>
