<div class="pt-32 pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-xl text-center space-y-6">

    <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto shadow-md">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>

    <div class="space-y-2">
      <span class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-200">
        DPDP Act 2023 Compliance Executed
      </span>
      <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Personal Data &amp; Resumes Permanently Erased</h1>
      <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-md mx-auto">
        All resume vault files and associated candidate profiles for <strong class="text-slate-900"><?= esc($email) ?></strong> have been permanently deleted and anonymized from our repository.
      </p>
    </div>

    <div class="card-premium p-6 bg-white border border-slate-200 rounded-3xl text-left space-y-3 text-xs text-slate-700 shadow-sm max-w-md mx-auto">
      <div class="flex items-center justify-between border-b border-slate-100 pb-2">
        <span class="font-bold text-slate-500">Regulatory Policy:</span>
        <span class="font-mono font-bold text-slate-900">DPDP_RIGHT_TO_ERASURE</span>
      </div>
      <div class="flex items-center justify-between border-b border-slate-100 pb-2">
        <span class="font-bold text-slate-500">Vault Files Purged:</span>
        <span class="font-mono font-bold text-emerald-700"><?= esc($purged_files ?? 1) ?> document(s) unlinked</span>
      </div>
      <div class="flex items-center justify-between">
        <span class="font-bold text-slate-500">Execution Timestamp:</span>
        <span class="font-mono text-slate-600"><?= date('Y-m-d H:i:s T') ?></span>
      </div>
    </div>

    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
      <a href="<?= base_url() ?>" class="btn-primary text-xs font-bold py-2.5 px-6 shadow-sm">
        <span>Return to Homepage</span>
      </a>
      <a href="<?= base_url('privacy') ?>" class="btn-outline text-xs py-2.5 px-5">
        <span>View Privacy Policy</span>
      </a>
    </div>

  </div>
</div>

