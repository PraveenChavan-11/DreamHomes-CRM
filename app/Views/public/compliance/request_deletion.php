<div class="pt-32 pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-2xl">

    <!-- Header -->
    <div class="mb-8 text-center sm:text-left">
      <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-3 font-semibold justify-center sm:justify-start">
        <a href="<?= base_url() ?>" class="hover:text-[#1D6A34]">Home</a>
        <span>/</span>
        <a href="<?= base_url('privacy') ?>" class="hover:text-[#1D6A34]">Privacy Policy</a>
        <span>/</span>
        <span class="text-[#172B22]">Right to Erasure</span>
      </nav>

      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-red-700 uppercase tracking-wider mb-2">
        <span class="w-2 h-2 rounded-full bg-red-500"></span>
        <span>DPDP Act 2023 Statutory Request</span>
      </div>
      <h1 class="text-3xl font-extrabold text-[#172B22] tracking-tight">Request CV &amp; Profile Erasure</h1>
      <p class="text-xs sm:text-sm text-[#3d6350] mt-1.5 leading-relaxed">
        Under India's Digital Personal Data Protection (DPDP) Act 2023, you have the statutory Right to Erasure. Enter your registered email address to receive a secure confirmation link to permanently delete your resume and profile data.
      </p>
    </div>

    <!-- Flash Messages -->
    <?php if (session()->getFlashdata('error')): ?>
      <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-xs font-bold text-red-800 flex items-start gap-3">
        <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800 flex items-start gap-3">
        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
      </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="card-premium p-6 sm:p-8 bg-white border border-slate-200 shadow-sm rounded-3xl space-y-6">
      <form action="<?= base_url('privacy/request-cv-deletion') ?>" method="POST" class="space-y-5">
        <?= csrf_field() ?>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Registered Candidate Email Address <span class="text-red-500">*</span></label>
          <input type="email" name="email" required placeholder="name@example.com" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 p-3.5 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
        </div>

        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
          <strong class="block mb-1 font-bold text-amber-950">Statutory Notice on Erasure:</strong>
          Confirming this action will immediately remove all uploaded PDF/Word resumes from our non-public secure document vault, anonymize candidate application entries, and withdraw any active candidature. This action cannot be reversed.
        </div>

        <label class="flex items-start gap-3 text-xs text-slate-700 cursor-pointer pt-1">
          <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
          <span>I understand and formally request the complete erasure of my personal profile and resume documents from TalentHub Consultant's database and vault.</span>
        </label>

        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition cursor-pointer">
          Dispatch Secure Deletion Confirmation Link &rarr;
        </button>
      </form>
    </div>

    <div class="mt-8 text-center text-xs text-slate-400">
      <p>TalentHub Consultant &bull; Compliance Desk &bull; Katraj, Pune, Maharashtra</p>
    </div>

  </div>
</div>

