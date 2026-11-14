<div class="pt-32 pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-3xl">

    <!-- Header -->
    <div class="mb-8 text-center sm:text-left">
      <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-3 font-semibold justify-center sm:justify-start">
        <a href="<?= base_url() ?>" class="hover:text-[#1D6A34]">Home</a>
        <span>/</span>
        <a href="<?= base_url('candidates') ?>" class="hover:text-[#1D6A34]">Candidates</a>
        <span>/</span>
        <span class="text-[#172B22]">Application Tracker</span>
      </nav>

      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] uppercase tracking-wider mb-2">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span>Candidate Telemetry</span>
      </div>
      <h1 class="text-3xl font-extrabold text-[#172B22] tracking-tight">Application Status Tracker</h1>
      <p class="text-xs sm:text-sm text-[#3d6350] mt-1.5 max-w-xl">
        Enter your registered email address and application ID to check your live recruitment review stage, screening outcome, and follow-up milestones.
      </p>
    </div>

    <!-- Search Card -->
    <div class="card-premium p-6 sm:p-8 bg-white border border-slate-200 shadow-sm rounded-3xl mb-8">
      <form action="<?= base_url('application/tracker') ?>" method="GET" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Your Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="<?= esc($email ?? '') ?>" required placeholder="name@example.com" class="w-full text-xs rounded-xl border border-slate-200 p-3 focus:outline-none focus:border-[#1D6A34]">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Application ID / Reference <span class="text-red-500">*</span></label>
            <input type="text" name="ref" value="<?= esc($ref ?? '') ?>" required placeholder="e.g. 1042 or #1042" class="w-full text-xs rounded-xl border border-slate-200 p-3 focus:outline-none focus:border-[#1D6A34]">
          </div>
        </div>

        <div class="pt-2 flex items-center justify-between">
          <button type="submit" class="btn-primary text-xs font-bold py-2.5 px-6 shadow-sm">
            <span>Track Application &rarr;</span>
          </button>
          <a href="<?= base_url('jobs') ?>" class="text-xs text-slate-500 hover:text-brand-primary">Browse other openings</a>
        </div>
      </form>
    </div>

    <!-- Results Section -->
    <?php if ($searched): ?>
      <?php if (!empty($app)): ?>
        <?php
          $st = strtoupper($app['status'] ?? 'APPLIED');
          $stages = [
            'APPLIED'     => ['label' => 'Applied', 'step' => 1, 'desc' => 'Application received and indexed in recruiter queue.'],
            'SCREENING'   => ['label' => 'Screening', 'step' => 2, 'desc' => 'Technical and credentials review in progress.'],
            'SHORTLISTED' => ['label' => 'Shortlisted', 'step' => 3, 'desc' => 'Profile forwarded to corporate hiring partner.'],
            'INTERVIEW'   => ['label' => 'Interview Round', 'step' => 4, 'desc' => 'Direct employer assessment scheduled.'],
            'OFFER'       => ['label' => 'Offer Extended', 'step' => 5, 'desc' => 'Formal offer letter dispatched.'],
            'HIRED'       => ['label' => 'Placed & Onboarded', 'step' => 6, 'desc' => 'Successfully hired. Welcome to the team!'],
          ];
          $isRejected = ($st === 'REJECTED');
          $isWithdrawn = ($st === 'WITHDRAWN');
        ?>
        <div class="card-premium p-6 sm:p-8 bg-white border border-[#1D6A34]/20 shadow-md rounded-3xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#1D6A34]">Verified Application Record</span>
              <h2 class="text-xl font-black text-slate-900"><?= esc($app['job_title'] ?? 'Corporate Opening') ?></h2>
              <p class="text-xs text-slate-500">Applied on <?= date('F j, Y', strtotime($app['created_at'])) ?> &bull; Ref #<?= esc($app['id']) ?></p>
            </div>
            <div>
              <span class="inline-block px-3 py-1.5 rounded-xl text-xs font-black border
                <?= $isRejected ? 'bg-red-50 text-red-800 border-red-200' : '' ?>
                <?= $isWithdrawn ? 'bg-slate-100 text-slate-700 border-slate-200' : '' ?>
                <?= (!$isRejected && !$isWithdrawn) ? 'bg-[#EAF5EF] text-[#1D6A34] border-[#1D6A34]/30' : '' ?>
              ">
                Status: <?= esc($st) ?>
              </span>
            </div>
          </div>

          <!-- Timeline -->
          <?php if (!$isRejected && !$isWithdrawn): ?>
            <div class="space-y-4 pt-2">
              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Recruitment Pipeline Progress</h3>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php foreach (['APPLIED', 'SCREENING', 'SHORTLISTED', 'INTERVIEW'] as $stageKey): ?>
                  <?php
                    $currStep = $stages[$st]['step'] ?? 1;
                    $thisStep = $stages[$stageKey]['step'];
                    $isPassed = ($currStep >= $thisStep);
                    $isCurrent = ($st === $stageKey);
                  ?>
                  <div class="p-3 rounded-2xl border text-center <?= $isCurrent ? 'bg-[#EAF5EF] border-[#1D6A34] shadow-sm' : ($isPassed ? 'bg-slate-50 border-emerald-200 text-emerald-900' : 'bg-slate-50/50 border-slate-100 text-slate-400') ?>">
                    <span class="block text-[10px] font-extrabold uppercase tracking-wider mb-1 <?= $isCurrent ? 'text-[#1D6A34]' : '' ?>">
                      Step <?= $thisStep ?>
                    </span>
                    <p class="text-xs font-bold <?= $isCurrent ? 'text-slate-900' : '' ?>"><?= $stages[$stageKey]['label'] ?></p>
                    <?php if ($isCurrent): ?>
                      <span class="inline-block w-2 h-2 rounded-full bg-[#1D6A34] mt-2 animate-ping"></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php elseif ($isRejected): ?>
            <div class="p-4 rounded-2xl bg-red-50 border border-red-100 text-xs text-red-800 leading-relaxed">
              <strong>Application Status:</strong> Thank you for your interest in this role. The hiring panel has chosen to move forward with other candidates whose experience more closely matches this mandate. We encourage you to explore other openings across our portal.
            </div>
          <?php endif; ?>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Questions regarding this application?</span>
            <a href="<?= base_url('contact') ?>" class="text-[#1D6A34] font-bold hover:underline">Contact Recruitment Advisory Desk &rarr;</a>
          </div>
        </div>
      <?php else: ?>
        <div class="card-premium p-10 text-center bg-white border border-slate-200 rounded-3xl space-y-3">
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <h3 class="text-base font-bold text-slate-900">No Matching Application Found</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">
            Please ensure you have entered the exact email address used when applying and the correct Application ID.
          </p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

