<div class="space-y-6 sm:space-y-8 max-w-full overflow-hidden">
  
  <!-- Welcome Banner & Period Controls (Mobile Responsive) -->
  <div class="bg-gradient-to-r from-brand-dark via-[#0D381E] to-brand-primary p-4 sm:p-6 md:p-8 rounded-2xl sm:rounded-3xl text-white shadow-xl shadow-brand-dark/10 relative overflow-hidden">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 sm:gap-6 relative z-10">
      <div class="space-y-1.5 sm:space-y-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/10 text-brand-accent backdrop-blur-md border border-white/10">
          <span class="w-2 h-2 rounded-full bg-brand-accent animate-pulse"></span>
          <span>Recruitment Operations Control Center</span>
        </div>
        <h1 class="text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">Executive Dashboard — Talent Hub</h1>
        <p class="text-xs md:text-sm text-slate-200 max-w-2xl leading-relaxed">
          Real-time recruitment telemetry, candidate pipeline velocity, inbound corporate hiring mandates, and Hostinger Mail communication hub.
        </p>
      </div>
      
      <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
        <a href="<?= base_url('admin/jobs/new') ?>" class="inline-flex items-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-brand-accent hover:bg-emerald-300 text-brand-dark font-extrabold text-xs transition shadow-lg shadow-brand-accent/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
          <span>Post New Job</span>
        </a>
        <a href="<?= base_url('admin/mail/compose') ?>" class="inline-flex items-center gap-1.5 sm:gap-2 px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 backdrop-blur-md transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
          <span>Compose Email</span>
        </a>
      </div>
    </div>

    <!-- Period Filter Bar (Horizontally Scrollable on Mobile) -->
    <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 relative z-10">
      <div class="flex items-center gap-1 sm:gap-1.5 bg-black/25 p-1 rounded-2xl backdrop-blur-md border border-white/10 overflow-x-auto max-w-full scrollbar-none">
        <a href="<?= base_url('admin/dashboard?period=today') ?>" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap <?= ($period === 'today') ? 'bg-brand-accent text-brand-dark shadow-sm' : 'text-slate-200 hover:text-white' ?>">Today</a>
        <a href="<?= base_url('admin/dashboard?period=yesterday') ?>" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap <?= ($period === 'yesterday') ? 'bg-brand-accent text-brand-dark shadow-sm' : 'text-slate-200 hover:text-white' ?>">Yesterday</a>
        <a href="<?= base_url('admin/dashboard?period=7days') ?>" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap <?= ($period === '7days') ? 'bg-brand-accent text-brand-dark shadow-sm' : 'text-slate-200 hover:text-white' ?>">7 Days</a>
        <a href="<?= base_url('admin/dashboard?period=30days') ?>" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap <?= ($period === '30days') ? 'bg-brand-accent text-brand-dark shadow-sm' : 'text-slate-200 hover:text-white' ?>">30 Days</a>
        <a href="<?= base_url('admin/dashboard?period=all') ?>" class="px-2.5 sm:px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap <?= ($period === 'all') ? 'bg-brand-accent text-brand-dark shadow-sm' : 'text-slate-200 hover:text-white' ?>">All Time</a>
      </div>

      <div class="text-xs text-slate-300 font-medium flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        <span>Filtering Window: <strong class="text-white capitalize"><?= esc($period) ?></strong></span>
      </div>
    </div>

    <!-- Decorative Glow Element -->
    <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-brand-accent/15 rounded-full blur-3xl pointer-events-none"></div>
  </div>

  <!-- Metric KPI Cards (Responsive Grid) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">
    
    <!-- Active Jobs -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary/40 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Postings</p>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-brand-primary flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= esc($metrics['active_jobs'] ?? 0) ?></h3>
        <p class="text-xs text-brand-primary font-semibold mt-1 flex items-center gap-1">
          <span>&bull;</span>
          <span>Live vacancies in Western India</span>
        </p>
      </div>
    </div>

    <!-- Candidate Applications -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary/40 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Applications</p>
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= esc($metrics['total_applications'] ?? 0) ?></h3>
        <p class="text-xs text-sky-700 font-semibold mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
          <span><?= esc($metrics['period_apps'] ?? 0) ?> in selected window</span>
        </p>
      </div>
    </div>

    <!-- General Talent Vault -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary/40 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Resume Bank</p>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= esc($metrics['resume_bank'] ?? 0) ?></h3>
        <p class="text-xs text-indigo-700 font-semibold mt-1 flex items-center gap-1">
          <span>&bull;</span>
          <span><?= esc($metrics['period_resumes'] ?? 0) ?> registered in window</span>
        </p>
      </div>
    </div>

    <!-- Employer Leads -->
    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary/40 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Employer Leads</p>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
      </div>
      <div class="mt-3">
        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= esc($metrics['employer_leads'] ?? 0) ?></h3>
        <p class="text-xs text-amber-700 font-semibold mt-1 flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
          <span><?= esc($metrics['period_employers'] ?? 0) ?> in selected window</span>
        </p>
      </div>
    </div>

    <!-- IT Project Leads (separate IT Services CRM) -->
    <?php if (isset($metrics['it_leads'])): ?>
    <a href="<?= base_url('admin/it-leads') ?>" class="block bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary/40 hover:shadow-md transition">
      <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">IT Project Leads</p>
      <h3 class="mt-3 text-2xl sm:text-3xl font-extrabold text-slate-900"><?= esc($metrics['it_leads']['total']) ?></h3>
      <p class="text-xs text-emerald-700 font-semibold mt-1"><?= esc($metrics['it_leads']['new']) ?> new · <?= esc($metrics['it_leads']['followups_due']) ?> follow-ups due</p>
    </a>
    <?php endif; ?>

  </div>

  <!-- Interactive Charts Section (Chart.js Powered - Zero Overflow) -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
    
    <!-- 7-Day Inbound Velocity Trend Chart (2 Cols) -->
    <div class="lg:col-span-2 bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4 overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Inbound Acquisition Velocity (7 Days)</h3>
          <p class="text-[11px] sm:text-xs text-slate-500">Tracking daily applications, corporate mandates, and general inquiries</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-4 text-xs font-bold">
          <span class="flex items-center gap-1.5 text-sky-600">
            <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Applications
          </span>
          <span class="flex items-center gap-1.5 text-amber-600">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Employers
          </span>
          <span class="flex items-center gap-1.5 text-emerald-600">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Contacts
          </span>
        </div>
      </div>
      <div class="relative h-60 sm:h-64 w-full min-w-0 max-w-full overflow-hidden">
        <canvas id="velocityChart"></canvas>
      </div>
    </div>

    <!-- Employer Urgency & Demand Mix (1 Col) -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4 flex flex-col justify-between overflow-hidden">
      <div>
        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Corporate Hiring Urgency</h3>
        <p class="text-[11px] sm:text-xs text-slate-500">Hiring mandate turnaround requirement breakdown</p>
      </div>
      <div class="relative h-48 sm:h-56 w-full min-w-0 max-w-full flex items-center justify-center overflow-hidden">
        <canvas id="urgencyChart"></canvas>
        <div id="urgencyCenterBadge" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
          <?php $totalUrgency = array_sum($chartData['urgency'] ?? []); ?>
          <?php if ($totalUrgency > 0): ?>
            <span class="text-2xl sm:text-3xl font-black text-slate-800"><?= $totalUrgency ?></span>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Mandates</span>
          <?php else: ?>
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-1">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <span class="text-[11px] sm:text-xs font-bold text-slate-500">Awaiting Leads</span>
            <span class="text-[9px] sm:text-[10px] text-slate-400">0 Active Mandates</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
          <span class="text-slate-600 truncate">Immediate: <strong><?= esc($chartData['urgency']['Immediate'] ?? 0) ?></strong></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
          <span class="text-slate-600 truncate">15 Days: <strong><?= esc($chartData['urgency']['15 Days'] ?? 0) ?></strong></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-sky-500 shrink-0"></span>
          <span class="text-slate-600 truncate">30 Days: <strong><?= esc($chartData['urgency']['30 Days'] ?? 0) ?></strong></span>
        </div>
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
          <span class="text-slate-600 truncate">Pipeline: <strong><?= esc($chartData['urgency']['Pipeline'] ?? 0) ?></strong></span>
        </div>
      </div>
    </div>

  </div>

  <!-- Pipeline Velocity Progress Bar Widget -->
  <?php if (!empty($metrics['pipeline'])): ?>
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4 overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900">Candidate Recruitment Pipeline Velocity</h3>
          <p class="text-[11px] sm:text-xs text-slate-500">Real-time candidate transition through enterprise screening stages</p>
        </div>
        <a href="<?= base_url('admin/applications') ?>" class="text-xs font-bold text-brand-primary hover:text-brand-hover">
          Manage Pipeline &rarr;
        </a>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2 sm:gap-3 pt-2">
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-slate-50 border border-slate-100 text-center">
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Applied</div>
          <div class="text-lg sm:text-xl font-extrabold text-slate-800 mt-1"><?= esc($metrics['pipeline']['applied'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-amber-50/60 border border-amber-100 text-center">
          <div class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Screening</div>
          <div class="text-lg sm:text-xl font-extrabold text-amber-900 mt-1"><?= esc($metrics['pipeline']['screening'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-sky-50/60 border border-sky-100 text-center">
          <div class="text-[10px] font-bold text-sky-700 uppercase tracking-wider">Shortlisted</div>
          <div class="text-lg sm:text-xl font-extrabold text-sky-900 mt-1"><?= esc($metrics['pipeline']['shortlisted'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-purple-50/60 border border-purple-100 text-center">
          <div class="text-[10px] font-bold text-purple-700 uppercase tracking-wider">Interview</div>
          <div class="text-lg sm:text-xl font-extrabold text-purple-900 mt-1"><?= esc($metrics['pipeline']['interview'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-emerald-50/60 border border-emerald-100 text-center">
          <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Offer</div>
          <div class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-1"><?= esc($metrics['pipeline']['offer'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-emerald-100/60 border border-emerald-200 text-center">
          <div class="text-[10px] font-bold text-brand-dark uppercase tracking-wider">Hired</div>
          <div class="text-lg sm:text-xl font-extrabold text-brand-dark mt-1"><?= esc($metrics['pipeline']['hired'] ?? 0) ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Two Column Layout: Applications & Inquiries + Hostinger Mail & System Telemetry -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
    
    <!-- Left 2 Cols: Recent Applications & Recent Employer Mandates -->
    <div class="lg:col-span-2 space-y-6 sm:space-y-8 overflow-hidden">
      
      <!-- Recent Candidate Applications Table -->
      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Recent Candidate Applications</h3>
            <p class="text-[11px] sm:text-xs text-slate-500">Live submissions direct from public vacancies</p>
          </div>
          <a href="<?= base_url('admin/applications') ?>" class="text-xs font-bold text-brand-primary hover:text-brand-hover">View All &rarr;</a>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[500px]">
            <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
              <tr>
                <th class="px-4 sm:px-6 py-3.5">Candidate</th>
                <th class="px-4 sm:px-6 py-3.5">Position</th>
                <th class="px-4 sm:px-6 py-3.5">Experience</th>
                <th class="px-4 sm:px-6 py-3.5">Status</th>
                <th class="px-4 sm:px-6 py-3.5 text-right">Resume</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($recentApplications)): ?>
                <tr>
                  <td colspan="5" class="px-4 sm:px-6 py-8 text-center text-slate-400 text-xs">No candidate applications logged yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentApplications as $app): ?>
                  <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-4 sm:px-6 py-4">
                      <?php 
                        $candidateName = $app['candidate_name'] ?? $app['full_name'] ?? ('Candidate #' . ($app['id'] ?? ''));
                        $candidateEmail = $app['candidate_email'] ?? $app['email'] ?? '';
                        $candidatePhone = $app['candidate_phone'] ?? $app['phone'] ?? '';
                      ?>
                      <div class="font-bold text-slate-900"><?= esc($candidateName) ?></div>
                      <div class="text-[11px] text-slate-400 mt-0.5">
                        <?= esc($candidateEmail) ?> 
                        <?php if (!empty($candidatePhone)): ?>
                          &bull; <?= esc($candidatePhone) ?>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-4 font-semibold text-slate-800">
                      <?= esc($app['job_title'] ?? ('Job #' . ($app['job_id'] ?? ''))) ?>
                    </td>
                    <td class="px-4 sm:px-6 py-4 text-slate-600">
                      <?= esc($app['experience_years'] ?? '0') ?> yrs
                    </td>
                    <td class="px-4 sm:px-6 py-4">
                      <?php 
                        $st = strtoupper($app['status'] ?? 'APPLIED');
                      ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold
                        <?= in_array($st, ['APPLIED', 'NEW']) ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' ?>
                        <?= ($st === 'SCREENING') ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' ?>
                        <?= ($st === 'SHORTLISTED') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' ?>
                        <?= ($st === 'INTERVIEW') ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' ?>
                        <?= in_array($st, ['OFFER', 'HIRED']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' ?>
                        <?= in_array($st, ['REJECTED', 'WITHDRAWN']) ? 'bg-red-50 text-red-700 border border-red-200' : '' ?>
                      ">
                        <?= esc($st) ?>
                      </span>
                    </td>
                    <td class="px-4 sm:px-6 py-4 text-right">
                      <?php if (!empty($app['document_id']) || !empty($app['resume_path'])): ?>
                        <a href="<?= base_url('admin/applications/resume/' . $app['id']) ?>" target="_blank" class="p-1.5 rounded-lg text-brand-primary hover:bg-brand-light transition inline-flex items-center" title="Download Resume">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                      <?php else: ?>
                        <span class="text-[10px] text-slate-300">None</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Employer Mandates Table -->
      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-slate-900">Recent Employer Hiring Enquiries</h3>
            <p class="text-[11px] sm:text-xs text-slate-500">Corporate recruitment, staff augmentation &amp; retained searches</p>
          </div>
          <a href="<?= base_url('admin/enquiries/employers') ?>" class="text-xs font-bold text-brand-primary hover:text-brand-hover">View All &rarr;</a>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[500px]">
            <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
              <tr>
                <th class="px-4 sm:px-6 py-3.5">Company &amp; Contact</th>
                <th class="px-4 sm:px-6 py-3.5">Roles Needed</th>
                <th class="px-4 sm:px-6 py-3.5">Urgency</th>
                <th class="px-4 sm:px-6 py-3.5 text-right">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($recentEmployers)): ?>
                <tr>
                  <td colspan="4" class="px-4 sm:px-6 py-8 text-center text-slate-400 text-xs">No employer hiring enquiries logged yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentEmployers as $emp): ?>
                  <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-4 sm:px-6 py-4">
                      <div class="font-bold text-slate-900"><?= esc($emp['company_name'] ?? 'Company') ?></div>
                      <div class="text-[11px] text-slate-400 mt-0.5">
                        <?= esc($emp['contact_person'] ?? '') ?> &bull; <?= esc($emp['email'] ?? '') ?>
                      </div>
                    </td>
                    <td class="px-4 sm:px-6 py-4 font-semibold text-slate-800">
                      <?= esc($emp['required_roles'] ?? ($emp['positions_needed'] ?? 'Staffing Mandate')) ?>
                    </td>
                    <td class="px-4 sm:px-6 py-4 text-slate-500">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                        <?= esc($emp['urgency'] ?? 'Immediate') ?>
                      </span>
                    </td>
                    <td class="px-4 sm:px-6 py-4 text-right">
                      <?php $empSt = strtoupper($emp['status'] ?? 'NEW'); ?>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold
                        <?= ($empSt === 'NEW') ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' ?>
                        <?= ($empSt === 'CONTACTED') ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' ?>
                        <?= ($empSt === 'IN_DISCUSSION') ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' ?>
                        <?= ($empSt === 'MANDATE_WON') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' ?>
                        <?= ($empSt === 'CLOSED_LOST') ? 'bg-slate-100 text-slate-600 border border-slate-200' : '' ?>
                      ">
                        <?= esc(str_replace('_', ' ', $empSt)) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Right 1 Col: Hostinger Mail Center & Inquiries & Services Telemetry -->
    <div class="space-y-6 sm:space-y-8 overflow-hidden">
      
      <!-- Hostinger Mailbox Sync Status Card -->
      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-light text-brand-primary flex items-center justify-center font-bold">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-slate-900">Hostinger Mail Center</h3>
              <p class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Connected &amp; Ready</span>
              </p>
            </div>
          </div>
          <form action="<?= base_url('admin/mail/sync') ?>" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold text-brand-primary hover:bg-brand-light border border-brand-primary/20 transition flex items-center gap-1.5 shadow-sm">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Sync</span>
            </button>
          </form>
        </div>

        <div class="space-y-2.5 pt-2">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <div>
              <div class="font-bold text-slate-800 font-mono">hr@talenthubconsultant.com</div>
              <div class="text-[10px] text-slate-400">Recruitment &amp; HR Desk</div>
            </div>
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <div>
              <div class="font-bold text-slate-800 font-mono">info@talenthubconsultant.com</div>
              <div class="text-[10px] text-slate-400">General Enquiries</div>
            </div>
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          </div>
        </div>

        <a href="<?= base_url('admin/mail') ?>" class="block text-center py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 transition">
          Open Email Center &rarr;
        </a>
      </div>

      <!-- Third-Party Telemetry & Integration Services Status Card -->
      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-extrabold text-slate-900">Live Service Telemetry</h3>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Production</span>
        </div>

        <div class="space-y-3 pt-1">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <div class="min-w-0 pr-2">
              <div class="font-bold text-slate-800 truncate">Google Analytics 4</div>
              <div class="text-[10px] text-slate-400 font-mono truncate"><?= esc($settings['ga_measurement_id'] ?? 'G-K6J0RWMLBS') ?></div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">ACTIVE</span>
          </div>

          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <div class="min-w-0 pr-2">
              <div class="font-bold text-slate-800 truncate">Tawk.to Live Chat</div>
              <div class="text-[10px] text-slate-400 truncate">Recruitment Visitor Widget</div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">CONNECTED</span>
          </div>

          <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
            <div class="min-w-0 pr-2">
              <div class="font-bold text-slate-800 truncate">Hostinger Mail SMTP</div>
              <div class="text-[10px] text-slate-400 truncate font-mono">smtp.hostinger.com:465</div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">OPERATIONAL</span>
          </div>
        </div>
      </div>

      <!-- Recent Contact Inquiries -->
      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-extrabold text-slate-900">Recent Contact Inquiries</h3>
          <a href="<?= base_url('admin/enquiries/contact') ?>" class="text-xs font-bold text-brand-primary hover:text-brand-hover">View &rarr;</a>
        </div>

        <div class="space-y-3">
          <?php if (empty($recentContacts)): ?>
            <p class="text-xs text-slate-400 text-center py-4">No contact messages logged yet.</p>
          <?php else: ?>
            <?php foreach ($recentContacts as $c): ?>
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-900">
                    <?= esc(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: ($c['name'] ?? 'Inquiry')) ?>
                  </span>
                  <span class="text-[10px] text-slate-400"><?= esc(date('M d', strtotime($c['created_at'] ?? 'now'))) ?></span>
                </div>
                <div class="text-[11px] text-slate-600 line-clamp-1"><?= esc($c['subject'] ?? $c['message'] ?? '') ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </div>

  <?php if (!empty($crm)): ?>
    <?= view('admin/dashboard/_crm', ['crm' => $crm, 'crmProducts' => $crmProducts ?? [], 'crmSources' => $crmSources ?? []]) ?>
  <?php endif; ?>

</div>

<!-- Chart.js Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Inbound Acquisition Velocity Chart
  const velocityCtx = document.getElementById('velocityChart');
  if (velocityCtx && typeof Chart !== 'undefined') {
    new Chart(velocityCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartData['labels'] ?? []) ?>,
        datasets: [
          {
            label: 'Candidate Applications',
            data: <?= json_encode($chartData['apps'] ?? []) ?>,
            borderColor: '#0284c7',
            backgroundColor: 'rgba(2, 132, 199, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#0284c7'
          },
          {
            label: 'Employer Mandates',
            data: <?= json_encode($chartData['employers'] ?? []) ?>,
            borderColor: '#d97706',
            backgroundColor: 'rgba(217, 119, 6, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#d97706'
          },
          {
            label: 'Contact Inquiries',
            data: <?= json_encode($chartData['contacts'] ?? []) ?>,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#059669'
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#072412',
            padding: 10,
            cornerRadius: 10,
            titleFont: { size: 12, weight: 'bold' },
            bodyFont: { size: 11 }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 10 }, color: '#94a3b8' }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#f1f5f9' },
            ticks: {
              stepSize: 1,
              font: { size: 10 },
              color: '#94a3b8',
              precision: 0
            }
          }
        }
      }
    });
  }

  // 2. Corporate Urgency Doughnut Chart
  const urgencyCtx = document.getElementById('urgencyChart');
  if (urgencyCtx && typeof Chart !== 'undefined') {
    const rawUrgency = [
      <?= (int) ($chartData['urgency']['Immediate'] ?? 0) ?>,
      <?= (int) ($chartData['urgency']['15 Days'] ?? 0) ?>,
      <?= (int) ($chartData['urgency']['30 Days'] ?? 0) ?>,
      <?= (int) ($chartData['urgency']['Pipeline'] ?? 0) ?>
    ];
    const totalUrgency = rawUrgency.reduce((acc, curr) => acc + curr, 0);

    if (totalUrgency === 0) {
      new Chart(urgencyCtx, {
        type: 'doughnut',
        data: {
          labels: ['No Active Mandates'],
          datasets: [{
            data: [1],
            backgroundColor: ['#E2E8F0'],
            borderWidth: 0,
            hoverOffset: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '74%',
          plugins: {
            legend: { display: false },
            tooltip: {
              enabled: true,
              callbacks: {
                label: function() { return 'Awaiting Inbound Corporate Mandates'; }
              }
            }
          }
        }
      });
    } else {
      new Chart(urgencyCtx, {
        type: 'doughnut',
        data: {
          labels: ['Immediate', '15 Days', '30 Days', 'Pipeline'],
          datasets: [{
            data: rawUrgency,
            backgroundColor: ['#ef4444', '#f59e0b', '#0284c7', '#10b981'],
            borderWidth: 0,
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '72%',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#072412',
              padding: 10,
              cornerRadius: 10
            }
          }
        }
      });
    }
  }
});
</script>
