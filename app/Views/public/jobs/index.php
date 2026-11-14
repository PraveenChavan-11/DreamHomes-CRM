<?php
/**
 * TalentHub Consultant — Job Vacancies Discovery Hub
 * Split Layout: Sticky Faceted Filters (25-30%) + High-Conversion Feed (70-75%) + Mobile Drawer
 */

$activeSearch = $filters['search'] ?? '';
$activeCat = $filters['category'] ?? '';
$activeLoc = $filters['location'] ?? '';
$activeJobType = $filters['job_type'] ?? '';
$activeWorkMode = $filters['work_mode'] ?? '';
$activeExpMin = $filters['exp_min'] ?? '';
$activeSort = $filters['sort'] ?? 'newest';

$hasActiveFilters = !empty($activeSearch) || !empty($activeCat) || !empty($activeLoc) || !empty($activeJobType) || !empty($activeWorkMode) || !empty($activeExpMin) || ($activeSort !== 'newest' && !empty($activeSort));
?>

<div class="pt-32 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">

    <!-- PAGE HERO & BREADCRUMB -->
    <div class="mb-8">
      <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-3 font-semibold" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#1D6A34] transition-colors">Home</a>
        <span>/</span>
        <span class="text-[#172B22]">Career Opportunities Across India</span>
      </nav>

      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] mb-3">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span>Verified Corporate Mandates — Zero Candidate Fees</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 class="th-h1 th-h1--ink">Active Job Opportunities Across India</h1>
          <p class="text-sm sm:text-base text-[#3d6350] mt-1.5 max-w-2xl font-normal">
            Explore verified technical, engineering, and corporate openings with employers across India.
          </p>
        </div>

        <!-- Mobile Filter Button Trigger -->
        <div class="lg:hidden">
          <button id="mobile-filter-toggle" type="button" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white border border-[#1D6A34]/30 text-sm font-bold text-[#1D6A34] shadow-sm cursor-pointer hover:bg-[#EAF5EF] transition-all">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            <span>Filter Opportunities</span>
            <?php if ($hasActiveFilters): ?>
              <span class="w-2 h-2 rounded-full bg-[#37CE97]"></span>
            <?php endif; ?>
          </button>
        </div>
      </div>
    </div>

    <!-- MAIN TWO-COLUMN LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

      <!-- ── LEFT SIDEBAR: STICKY FILTERS PANEL (28%) ───────────────────────── -->
      <aside class="hidden lg:block lg:col-span-4 sticky top-28 space-y-6">
        <div class="card-premium p-6 bg-white shadow-md">
          <div class="flex items-center justify-between pb-4 border-b border-[rgba(29,106,52,0.12)]">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
              <h2 class="text-sm font-bold text-[#172B22] uppercase tracking-wider">Filter Jobs</h2>
            </div>
            <?php if ($hasActiveFilters): ?>
              <a href="<?= base_url('jobs') ?>" class="text-xs font-bold text-[#1D6A34] hover:underline">Reset All</a>
            <?php endif; ?>
          </div>

          <form action="<?= base_url('jobs') ?>" method="GET" class="space-y-5 pt-4">

            <!-- Search Keyword -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Keyword Search</label>
              <div class="relative">
                <input type="text" name="search" value="<?= esc($activeSearch) ?>" placeholder="Title, skills, Java, QA..." class="form-input-premium text-xs pl-8">
                <svg class="w-4 h-4 text-[#3d6350] absolute left-2.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </div>
            </div>

            <!-- Category -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Job Category</label>
              <select name="category" class="form-input-premium text-xs cursor-pointer">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= esc($cat['slug']) ?>" <?= ($activeCat === $cat['slug']) ? 'selected' : '' ?>>
                    <?= esc($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Location</label>
              <select name="location" class="form-input-premium text-xs cursor-pointer">
                <option value="">All Locations</option>
                <option value="Hinjawadi" <?= ($activeLoc === 'Hinjawadi') ? 'selected' : '' ?>>Hinjawadi IT Park Phase 1-3</option>
                <option value="Kharadi" <?= ($activeLoc === 'Kharadi') ? 'selected' : '' ?>>Kharadi / EON Free Zone</option>
                <option value="Magarpatta" <?= ($activeLoc === 'Magarpatta') ? 'selected' : '' ?>>Magarpatta Cybercity</option>
                <option value="Chakan" <?= ($activeLoc === 'Chakan') ? 'selected' : '' ?>>Chakan MIDC (Auto / Mfg)</option>
                <option value="Baner" <?= ($activeLoc === 'Baner') ? 'selected' : '' ?>>Baner / Balewadi High Street</option>
                <option value="Pune" <?= ($activeLoc === 'Pune') ? 'selected' : '' ?>>Pune Central</option>
                <option value="Mumbai" <?= ($activeLoc === 'Mumbai') ? 'selected' : '' ?>>Mumbai</option>
                <option value="Bengaluru" <?= ($activeLoc === 'Bengaluru') ? 'selected' : '' ?>>Bengaluru</option>
                <option value="Hyderabad" <?= ($activeLoc === 'Hyderabad') ? 'selected' : '' ?>>Hyderabad</option>
                <option value="Delhi NCR" <?= ($activeLoc === 'Delhi NCR') ? 'selected' : '' ?>>Delhi NCR</option>
                <option value="Chennai" <?= ($activeLoc === 'Chennai') ? 'selected' : '' ?>>Chennai</option>
                <option value="Remote" <?= ($activeLoc === 'Remote') ? 'selected' : '' ?>>Remote</option>
              </select>
            </div>

            <!-- Minimum Experience -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Experience Level</label>
              <select name="exp_min" class="form-input-premium text-xs cursor-pointer">
                <option value="">Any Experience</option>
                <option value="0" <?= ($activeExpMin === '0') ? 'selected' : '' ?>>Fresher / Entry (0-1 yrs)</option>
                <option value="2" <?= ($activeExpMin === '2') ? 'selected' : '' ?>>Junior-Mid (2+ yrs)</option>
                <option value="4" <?= ($activeExpMin === '4') ? 'selected' : '' ?>>Mid-Senior (4+ yrs)</option>
                <option value="7" <?= ($activeExpMin === '7') ? 'selected' : '' ?>>Lead / Architect (7+ yrs)</option>
                <option value="10" <?= ($activeExpMin === '10') ? 'selected' : '' ?>>Executive / Director (10+ yrs)</option>
              </select>
            </div>

            <!-- Employment Type -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Employment Type</label>
              <div class="space-y-1.5 text-xs text-[#172B22]">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="job_type" value="" <?= empty($activeJobType) ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>All Employment Types</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="job_type" value="FT" <?= ($activeJobType === 'FT') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>Full-Time Permanent</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="job_type" value="CT" <?= ($activeJobType === 'CT') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>Contract Staffing</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="job_type" value="PT" <?= ($activeJobType === 'PT') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>Part-Time / Advisory</span>
                </label>
              </div>
            </div>

            <!-- Work Mode -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Work Arrangement</label>
              <div class="space-y-1.5 text-xs text-[#172B22]">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="work_mode" value="" <?= empty($activeWorkMode) ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>All Work Arrangements</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="work_mode" value="ONSITE" <?= ($activeWorkMode === 'ONSITE') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>On-Site</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="work_mode" value="HYBRID" <?= ($activeWorkMode === 'HYBRID') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>Hybrid</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" name="work_mode" value="REMOTE" <?= ($activeWorkMode === 'REMOTE') ? 'checked' : '' ?> class="text-[#1D6A34] focus:ring-[#37CE97]">
                  <span>100% Remote</span>
                </label>
              </div>
            </div>

            <!-- Sort By -->
            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1.5 uppercase tracking-wider">Sort Results</label>
              <select name="sort" class="form-input-premium text-xs cursor-pointer">
                <option value="newest" <?= ($activeSort === 'newest') ? 'selected' : '' ?>>Newest Postings First</option>
                <option value="oldest" <?= ($activeSort === 'oldest') ? 'selected' : '' ?>>Oldest Postings</option>
                <option value="salary_high" <?= ($activeSort === 'salary_high') ? 'selected' : '' ?>>Highest Salary</option>
                <option value="salary_low" <?= ($activeSort === 'salary_low') ? 'selected' : '' ?>>Lowest Salary</option>
              </select>
            </div>

            <!-- Submit Filter Button -->
            <button type="submit" class="w-full btn-primary text-xs font-bold py-3">
              <span>Apply Filters</span>
            </button>
          </form>
        </div>

        <!-- Sidebar Direct Career Pathway Box -->
        <div class="card-premium p-6 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20">
          <span class="text-[11px] font-extrabold text-[#1D6A34] uppercase tracking-wider block mb-1">Confidential Mandates</span>
          <h3 class="text-sm font-bold text-[#172B22] mb-2">Can't Find Your Exact Role?</h3>
          <p class="text-xs text-[#3d6350] leading-relaxed mb-4">
            Not all executive & senior technical mandates are publicly listed. Submit your CV directly to our advisory team.
          </p>
          <a href="<?= base_url('career-enquiry') ?>" class="btn-outline text-xs w-full justify-center">
            <span>Drop Your Resume</span>
          </a>
        </div>

        <!-- JOB ALERT SUBSCRIPTION WIDGET -->
        <div class="card-premium p-6 bg-gradient-to-br from-[#1D6A34]/10 via-[#EAF5EF] to-white border border-[#1D6A34]/20">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Never Miss New Roles</h3>
          </div>
          <p class="text-xs text-[#3d6350] leading-relaxed mb-4">
            Receive automated notifications when matching technical &amp; executive roles open across India.
          </p>
          <button type="button" onclick="window.openJobAlertModal()" class="w-full py-2.5 px-4 rounded-xl bg-[#1D6A34] text-white text-xs font-bold hover:bg-[#155227] shadow-sm transition">
            Create Job Alert
          </button>
        </div>
      </aside>

      <!-- ── RIGHT MAIN: JOB CARDS FEED & PAGINATION (72%) ──────────────────── -->
      <main class="lg:col-span-8 space-y-5">

        <!-- WHATSAPP CHANNEL BROADCAST BANNER (Admin Controlled) -->
        <?php if (!empty($settings['whatsapp_channel_url'])): ?>
        <div class="p-4 sm:p-5 rounded-2xl bg-[#EAF5EF] border border-[#25D366]/40 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#25D366] text-white flex items-center justify-center shrink-0 shadow-sm">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            </div>
            <div>
              <h3 class="text-xs sm:text-sm font-bold text-[#172B22]">Never Miss a Job Opening</h3>
              <p class="text-[11px] text-[#3d6350]">Join our official WhatsApp channel for direct alerts on high-demand roles.</p>
            </div>
          </div>
          <a href="<?= esc($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-channel inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold transition shadow-sm whitespace-nowrap shrink-0" style="background-color: #25D366 !important; color: #FFFFFF !important; border: 1.5px solid #20BA59 !important;">
            <span style="color: #FFFFFF !important; font-weight: 700 !important;">Join Our WhatsApp Channel – Get Latest Job Updates</span>
            <svg class="w-3.5 h-3.5" style="color: #FFFFFF !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </a>
        </div>
        <?php endif; ?>

        <!-- Feed Control Header & Active Filter Chips -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[rgba(29,106,52,0.12)]">
          <div class="text-xs text-[#3d6350] font-semibold">
            Showing <strong class="text-[#172B22] font-bold"><?= count($jobs) ?></strong> of <strong class="text-[#172B22] font-bold"><?= $totalJobs ?></strong> active vacancies
          </div>

          <?php if ($hasActiveFilters): ?>
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs text-[#3d6350]">Filters:</span>
              <?php if (!empty($activeSearch)): ?>
                <span class="badge-accent text-[11px]">"<?= esc($activeSearch) ?>"</span>
              <?php endif; ?>
              <?php if (!empty($activeCat)): ?>
                <span class="badge-accent text-[11px]"><?= esc($activeCat) ?></span>
              <?php endif; ?>
              <?php if (!empty($activeLoc)): ?>
                <span class="badge-accent text-[11px]"><?= esc($activeLoc) ?></span>
              <?php endif; ?>
              <?php if (!empty($activeJobType)): ?>
                <span class="badge-accent text-[11px]"><?= esc($activeJobType === 'FT' ? 'Full-Time' : 'Contract') ?></span>
              <?php endif; ?>
              <a href="<?= base_url('jobs') ?>" class="text-xs font-bold text-red-600 hover:underline ml-1">Clear</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- JOB CARDS LIST -->
        <?php if (!empty($jobs)): ?>
          <div class="space-y-4">
            <?php foreach ($jobs as $job): ?>
              <article class="card-premium p-6 flex flex-col justify-between group">
                <div>
                  <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="badge-primary text-[11px]">
                        <?= esc($job['category_name'] ?? 'Professional') ?>
                      </span>
                      <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700">
                        <?= esc($job['job_type'] === 'FT' ? 'Full-Time' : ($job['job_type'] === 'CT' ? 'Contract' : 'Part-Time')) ?>
                      </span>
                      <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-[#EAF5EF] text-[#1D6A34]">
                        <?= esc($job['work_mode'] ?? 'ONSITE') ?>
                      </span>
                    </div>

                    <!-- Bookmark & Share Action Icons -->
                    <div class="flex items-center gap-1.5">
                      <button type="button" data-save-job="<?= esc($job['slug']) ?>" class="p-2 rounded-xl border border-[rgba(29,106,52,0.15)] text-[#3d6350] hover:text-[#1D6A34] hover:bg-[#EAF5EF] transition-all cursor-pointer" title="Save Job">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                      </button>
                      <button type="button" onclick="shareJob('<?= esc(addslashes($job['title'])) ?>', '<?= base_url('jobs/' . $job['slug']) ?>', event)" class="p-2 rounded-xl border border-[rgba(29,106,52,0.15)] text-[#3d6350] hover:text-[#1D6A34] hover:bg-[#EAF5EF] transition-all cursor-pointer" title="Share Job">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                      </button>
                    </div>
                  </div>

                  <h2 class="text-xl font-bold text-[#172B22] mb-1.5 group-hover:text-[#1D6A34] transition-colors">
                    <a href="<?= base_url('jobs/' . $job['slug']) ?>"><?= esc($job['title']) ?></a>
                  </h2>

                  <p class="text-xs font-semibold text-[#1D6A34] mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span><?= !empty($job['employer_name']) ? esc($job['employer_name']) : 'Confidential Corporate Client' ?></span>
                  </p>

                  <p class="text-xs text-[#3d6350] flex flex-wrap items-center gap-2 mb-3">
                    <span class="flex items-center gap-1">
                      <svg class="w-3.5 h-3.5 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      <span><?= esc($job['location'] ?? 'India') ?></span>
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="flex items-center gap-1">
                      <svg class="w-3.5 h-3.5 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span>Exp: <?= esc(str_replace(['???', '??'], '–', $job['experience_label'] ?? ($job['experience_min'] . '–' . $job['experience_max'] . ' yrs'))) ?></span>
                    </span>
                    <span class="text-gray-300">•</span>
                    <span>Posted <?= date('M d, Y', strtotime($job['published_at'] ?? 'now')) ?></span>
                  </p>

                  <p class="text-xs text-[#3d6350] line-clamp-2 leading-relaxed mb-4">
                    <?= esc(substr(strip_tags($job['description']), 0, 190)) ?>...
                  </p>
                </div>

                <div class="pt-4 border-t border-[rgba(29,106,52,0.1)] flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <?php if (!empty($job['is_salary_public']) && !empty($job['salary_min'])): ?>
                      <span class="text-xs font-bold text-[#1D6A34]">₹<?= number_format((float) $job['salary_min']) ?> - ₹<?= number_format((float) $job['salary_max']) ?></span>
                    <?php else: ?>
                      <span class="text-xs font-semibold text-[#3d6350]">Salary Competitive / Negotiable</span>
                    <?php endif; ?>
                  </div>

                  <a href="<?= base_url('jobs/' . $job['slug']) ?>" class="btn-primary text-xs font-bold py-2 px-4 shadow-sm">
                    <span>View Details &amp; Apply</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <!-- SERVER-SIDE PROFESSIONAL PAGINATION -->
          <?php if ($totalPages > 1): 
            $limit = 12;
            $startItem = ($currentPage - 1) * $limit + 1;
            $endItem = min($totalJobs ?? ($currentPage * $limit), $currentPage * $limit);
            
            $delta = 2;
            $left = max(1, $currentPage - $delta);
            $right = min($totalPages, $currentPage + $delta);
            $pagesToShow = [];
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i === 1 || $i === $totalPages || ($i >= $left && $i <= $right)) {
                    $pagesToShow[] = $i;
                }
            }
            $paginatedItems = [];
            $prev = null;
            foreach ($pagesToShow as $p) {
                if ($prev !== null && $p - $prev > 1) {
                    $paginatedItems[] = '...';
                }
                $paginatedItems[] = $p;
                $prev = $p;
            }
          ?>
            <div class="pt-10 pb-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-[rgba(29,106,52,0.12)]">
              <p class="text-xs font-semibold text-[#3d6350] order-2 sm:order-1">
                Showing <span class="font-bold text-[#172B22]"><?= $startItem ?></span> to <span class="font-bold text-[#172B22]"><?= $endItem ?></span> of <span class="font-bold text-[#172B22]"><?= number_format($totalJobs ?? 0) ?></span> active opportunities
              </p>

              <nav class="flex items-center gap-1.5 order-1 sm:order-2" aria-label="Jobs Pagination">
                <!-- PREVIOUS -->
                <?php if ($currentPage > 1): ?>
                  <a href="<?= current_url() . '?' . http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>" 
                     class="h-9 px-3 rounded-xl border border-[rgba(29,106,52,0.25)] bg-white text-xs font-bold text-[#1D6A34] hover:bg-[#EAF5EF] transition flex items-center gap-1 shadow-sm"
                     aria-label="Previous Page">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Previous</span>
                  </a>
                <?php else: ?>
                  <span class="h-9 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 flex items-center gap-1 cursor-not-allowed select-none" aria-disabled="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">Previous</span>
                  </span>
                <?php endif; ?>

                <!-- PAGE NUMBERS -->
                <div class="flex items-center gap-1">
                  <?php foreach ($paginatedItems as $item): ?>
                    <?php if ($item === '...'): ?>
                      <span class="w-9 h-9 flex items-center justify-center text-xs text-slate-400 select-none">&hellip;</span>
                    <?php elseif ($item === $currentPage): ?>
                      <span class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black shadow-sm"
                            style="background-color: #1D6A34 !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(29,106,52,0.25) !important;"
                            aria-current="page">
                        <?= $item ?>
                      </span>
                    <?php else: ?>
                      <a href="<?= current_url() . '?' . http_build_query(array_merge($filters, ['page' => $item])) ?>" 
                         class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition border border-[rgba(29,106,52,0.25)] bg-white text-[#172B22] hover:bg-[#EAF5EF] hover:text-[#1D6A34] shadow-sm">
                        <?= $item ?>
                      </a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>

                <!-- NEXT -->
                <?php if ($currentPage < $totalPages): ?>
                  <a href="<?= current_url() . '?' . http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>" 
                     class="h-9 px-3 rounded-xl border border-[rgba(29,106,52,0.25)] bg-white text-xs font-bold text-[#1D6A34] hover:bg-[#EAF5EF] transition flex items-center gap-1 shadow-sm"
                     aria-label="Next Page">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </a>
                <?php else: ?>
                  <span class="h-9 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-300 flex items-center gap-1 cursor-not-allowed select-none" aria-disabled="true">
                    <span class="hidden sm:inline">Next</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </span>
                <?php endif; ?>
              </nav>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <!-- MANDATED PROFESSIONAL EMPTY STATE -->
          <div class="card-premium p-12 text-center bg-white space-y-4">
            <div class="w-16 h-16 rounded-full bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center mx-auto mb-2">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-xl font-bold text-[#172B22]">No Matching Opportunities Found</h2>
            <p class="text-xs sm:text-sm text-[#3d6350] max-w-md mx-auto leading-relaxed">
              No active vacancies currently match your selected filters. Try adjusting your search criteria or contact Talent Hub to express interest in future openings.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
              <a href="<?= base_url('jobs') ?>" class="btn-outline text-xs">
                <span>Clear All Filters</span>
              </a>
              <a href="<?= base_url('career-enquiry') ?>" class="btn-primary text-xs">
                <span>Express Interest for Future Openings</span>
              </a>
            </div>
          </div>
        <?php endif; ?>

        <!-- PERMANENT RESUME PATHWAY BANNER -->
        <div class="card-premium p-8 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20 text-center space-y-3 mt-10">
          <span class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider block">
            Upcoming &amp; Confidential Searches
          </span>
          <h3 class="text-xl font-extrabold text-[#172B22]">
            Didn't Find the Exact Role You're Looking For?
          </h3>
          <p class="text-xs sm:text-sm text-[#3d6350] max-w-xl mx-auto leading-relaxed">
            Your next career move doesn't have to wait for the perfect public listing. Share your resume and career preferences with our team. We will consider your profile for exclusive mandates as they open.
          </p>
          <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
            <a href="<?= base_url('career-enquiry') ?>" class="btn-primary text-xs font-bold px-6 py-3.5">
              <span>Share Your Resume &amp; Career Preferences</span>
              <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="mailto:<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="btn-outline text-xs font-bold px-5 py-3.5 flex items-center gap-2">
              <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>Email Resume: <?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?></span>
            </a>
          </div>
        </div>

      </main>

    </div>
  </div>
</div>

<!-- ── MOBILE FILTER DRAWER MODAL ───────────────────────────────────────────── -->
<div id="mobile-filter-drawer" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex justify-end transition-opacity">
  <div class="w-full max-w-md bg-white h-full flex flex-col justify-between overflow-y-auto p-6 shadow-2xl">
    <div>
      <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
        <h3 class="text-base font-bold text-[#172B22]">Filter Opportunities</h3>
        <button id="mobile-filter-close" type="button" class="p-2 text-gray-500 hover:text-gray-800 rounded-lg">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <form action="<?= base_url('jobs') ?>" method="GET" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Search</label>
          <input type="text" name="search" value="<?= esc($activeSearch) ?>" placeholder="Keywords, title, skills..." class="form-input-premium text-xs">
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Category</label>
          <select name="category" class="form-input-premium text-xs">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= esc($cat['slug']) ?>" <?= ($activeCat === $cat['slug']) ? 'selected' : '' ?>>
                <?= esc($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Location</label>
          <select name="location" class="form-input-premium text-xs">
            <option value="">All Locations</option>
            <option value="Hinjawadi" <?= ($activeLoc === 'Hinjawadi') ? 'selected' : '' ?>>Hinjawadi IT Park</option>
            <option value="Kharadi" <?= ($activeLoc === 'Kharadi') ? 'selected' : '' ?>>Kharadi / EON</option>
            <option value="Chakan" <?= ($activeLoc === 'Chakan') ? 'selected' : '' ?>>Chakan MIDC</option>
            <option value="Magarpatta" <?= ($activeLoc === 'Magarpatta') ? 'selected' : '' ?>>Magarpatta</option>
            <option value="Baner" <?= ($activeLoc === 'Baner') ? 'selected' : '' ?>>Baner</option>
                <option value="Mumbai" <?= ($activeLoc === 'Mumbai') ? 'selected' : '' ?>>Mumbai</option>
                <option value="Bengaluru" <?= ($activeLoc === 'Bengaluru') ? 'selected' : '' ?>>Bengaluru</option>
                <option value="Hyderabad" <?= ($activeLoc === 'Hyderabad') ? 'selected' : '' ?>>Hyderabad</option>
                <option value="Delhi NCR" <?= ($activeLoc === 'Delhi NCR') ? 'selected' : '' ?>>Delhi NCR</option>
                <option value="Chennai" <?= ($activeLoc === 'Chennai') ? 'selected' : '' ?>>Chennai</option>
                <option value="Remote" <?= ($activeLoc === 'Remote') ? 'selected' : '' ?>>Remote</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Employment Type</label>
          <select name="job_type" class="form-input-premium text-xs">
            <option value="">All Types</option>
            <option value="FT" <?= ($activeJobType === 'FT') ? 'selected' : '' ?>>Full-Time</option>
            <option value="CT" <?= ($activeJobType === 'CT') ? 'selected' : '' ?>>Contract</option>
            <option value="PT" <?= ($activeJobType === 'PT') ? 'selected' : '' ?>>Part-Time</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Work Arrangement</label>
          <select name="work_mode" class="form-input-premium text-xs">
            <option value="">All Modes</option>
            <option value="ONSITE" <?= ($activeWorkMode === 'ONSITE') ? 'selected' : '' ?>>On-Site</option>
            <option value="HYBRID" <?= ($activeWorkMode === 'HYBRID') ? 'selected' : '' ?>>Hybrid</option>
            <option value="REMOTE" <?= ($activeWorkMode === 'REMOTE') ? 'selected' : '' ?>>Remote</option>
          </select>
        </div>

        <button type="submit" class="w-full btn-primary text-xs font-bold py-3 mt-4">
          <span>Apply Filters</span>
        </button>
      </form>
    </div>

    <div class="pt-6 border-t border-gray-100">
      <a href="<?= base_url('jobs') ?>" class="block text-center text-xs font-bold text-[#1D6A34] hover:underline">
        Reset All Filters
      </a>
    </div>
  </div>
</div>
