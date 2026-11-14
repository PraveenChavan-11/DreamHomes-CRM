<?php
/**
 * TalentHub Consultant — Insights & Market Intelligence List View
 * Regional Salary Benchmarks, Sector Reports, and Executive Commentary
 */
?>

<div class="pt-32 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">

    <!-- HERO HEADER -->
    <div class="mb-12 max-w-2xl">
      <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-3 font-semibold" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#1D6A34] transition-colors">Home</a>
        <span>/</span>
        <span class="text-[#172B22]">Industry Insights &amp; Market Intelligence</span>
      </nav>

      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] uppercase tracking-wider mb-3">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span>Market Intelligence &amp; Salary Guides</span>
      </div>
      <h1 class="th-h1 th-h1--ink">Recruitment &amp; Technology Insights</h1>
      <p class="text-sm text-[#3d6350] mt-2 leading-relaxed font-normal">
        Quarterly hiring salary trends, regional talent reports, and executive commentary for business leaders and career seekers across India.
      </p>
    </div>

    <!-- ARTICLES GRID -->
    <?php if (!empty($posts)): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($posts as $post):
          $wordCount = str_word_count(strip_tags($post['content'] ?? ''));
          $readTime = max(2, (int) ceil($wordCount / 200));
        ?>
          <article class="card-premium overflow-hidden flex flex-col justify-between group bg-white">
            <div>
              <?php if (!empty($post['featured_image'])): ?>
                <div class="h-48 w-full overflow-hidden bg-gray-100">
                  <img src="<?= esc($post['featured_image']) ?>" alt="<?= esc($post['title']) ?>" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                </div>
              <?php else: ?>
                <div class="h-28 w-full bg-[linear-gradient(135deg,#072412,#1D6A34)] flex items-center px-6">
                  <span class="text-xs font-extrabold uppercase tracking-widest text-[#37CE97]">TalentHub Intelligence</span>
                </div>
              <?php endif; ?>

              <div class="p-6 space-y-2.5">
                <div class="flex items-center justify-between text-xs">
                  <span class="badge-primary text-[10px]">
                    <?= esc($post['category_name'] ?? 'Industry Analysis') ?>
                  </span>
                  <span class="text-[#3d6350] font-semibold text-[11px]"><?= $readTime ?> min read</span>
                </div>

                <h2 class="text-base font-bold text-[#172B22] group-hover:text-[#1D6A34] transition-colors leading-snug">
                  <a href="<?= base_url('insights/' . $post['slug']) ?>"><?= esc($post['title']) ?></a>
                </h2>

                <p class="text-xs text-[#3d6350] line-clamp-3 leading-relaxed font-normal">
                  <?= esc($post['summary']) ?>
                </p>
              </div>
            </div>

            <div class="px-6 pb-6 pt-3 border-t border-[rgba(29,106,52,0.1)] flex items-center justify-between text-xs text-[#3d6350]">
              <span class="font-semibold text-[11px]"><?= date('M d, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
              <a href="<?= base_url('insights/' . $post['slug']) ?>" class="font-bold text-[#1D6A34] hover:underline inline-flex items-center gap-1">
                <span>Read Analysis</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- PROFESSIONAL PAGINATION -->
      <?php if ($totalPages > 1): 
        $limit = 9;
        $startItem = ($currentPage - 1) * $limit + 1;
        $endItem = min($totalPosts ?? ($currentPage * $limit), $currentPage * $limit);
        
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
        $queryParams = $_GET ?? [];
      ?>
        <div class="mt-14 mb-16 pt-8 border-t border-[rgba(29,106,52,0.12)] flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-xs font-semibold text-[#3d6350] order-2 sm:order-1">
            Showing <span class="font-bold text-[#172B22]"><?= $startItem ?></span> to <span class="font-bold text-[#172B22]"><?= $endItem ?></span> of <span class="font-bold text-[#172B22]"><?= number_format($totalPosts ?? 0) ?></span> articles
          </p>

          <nav class="flex items-center gap-1.5 order-1 sm:order-2" aria-label="Articles Pagination">
            <!-- PREVIOUS -->
            <?php if ($currentPage > 1): ?>
              <a href="<?= base_url('insights?' . http_build_query(array_merge($queryParams, ['page' => $currentPage - 1]))) ?>" 
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
                  <a href="<?= base_url('insights?' . http_build_query(array_merge($queryParams, ['page' => $item]))) ?>" 
                     class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition border border-[rgba(29,106,52,0.25)] bg-white text-[#172B22] hover:bg-[#EAF5EF] hover:text-[#1D6A34] shadow-sm">
                    <?= $item ?>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>

            <!-- NEXT -->
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= base_url('insights?' . http_build_query(array_merge($queryParams, ['page' => $currentPage + 1]))) ?>" 
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

      <!-- INLINE NEWSLETTER CALLOUT BANNER (Guaranteed Fail-Safe High Contrast) -->
      <div class="mt-16 rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden border border-emerald-500/30"
           style="background-color: #072412 !important; background: linear-gradient(135deg, #072412 0%, #1D6A34 50%, #114221 100%) !important; color: #FFFFFF !important;">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full blur-3xl pointer-events-none" style="background-color: rgba(55, 206, 151, 0.25) !important;"></div>
        <div class="relative z-10 max-w-3xl">
          <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider mb-3"
                style="background-color: rgba(255, 255, 255, 0.15) !important; color: #37CE97 !important; border: 1px solid rgba(55, 206, 151, 0.35) !important;">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            <span>TalentHub Editorial Desk</span>
          </span>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight mb-2" style="color: #FFFFFF !important;">
            Get Hiring &amp; Technology Insights Delivered
          </h2>
          <p class="text-xs sm:text-sm leading-relaxed mb-6 max-w-xl" style="color: rgba(255, 255, 255, 0.9) !important;">
            Stay informed with our quarterly engineering salary benchmarks, technology hiring trends, and executive recruitment updates. Double opt-in verified, 1-click unsubscribe anytime.
          </p>

          <form data-newsletter="true" data-source="insights_archive" class="space-y-3 max-w-xl">
            <div class="flex flex-col sm:flex-row gap-2.5">
              <input type="text" name="first_name" placeholder="First Name (Optional)" 
                     style="background-color: rgba(255, 255, 255, 0.12) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; color: #FFFFFF !important;"
                     class="w-full sm:w-1/3 px-4 py-3 rounded-xl placeholder-white/60 text-xs focus:outline-none focus:border-[#37CE97]">
              <input type="email" name="email" required placeholder="Enter your work email address..." 
                     style="background-color: rgba(255, 255, 255, 0.12) !important; border: 1px solid rgba(255, 255, 255, 0.25) !important; color: #FFFFFF !important;"
                     class="w-full sm:w-2/3 px-4 py-3 rounded-xl placeholder-white/60 text-xs focus:outline-none focus:border-[#37CE97]">
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-1">
              <label class="flex items-center gap-2 text-[11px] cursor-pointer" style="color: rgba(255, 255, 255, 0.9) !important;">
                <input type="checkbox" name="consent" value="1" required checked class="rounded border-white/30 bg-white/10 text-[#37CE97] focus:ring-0">
                <span style="color: rgba(255, 255, 255, 0.95) !important;">I agree to receive recruitment intelligence emails</span>
              </label>
              <button type="submit" 
                      style="background-color: #37CE97 !important; color: #072412 !important; font-weight: 800 !important; border: none !important; box-shadow: 0 4px 14px rgba(55,206,151,0.35) !important;"
                      class="w-full sm:w-auto px-6 py-2.5 rounded-xl hover:bg-[#2eb986] text-xs font-black transition-all shrink-0">
                Subscribe Free &rarr;
              </button>
            </div>
          </form>
        </div>
      </div>

    <?php else: ?>
      <div class="card-premium p-16 text-center bg-white space-y-3">
        <p class="text-sm font-semibold text-[#3d6350]">No published market intelligence articles found currently.</p>
        <a href="<?= base_url('jobs') ?>" class="btn-primary text-xs">Browse Active Opportunities</a>
      </div>
    <?php endif; ?>

  </div>
</div>
