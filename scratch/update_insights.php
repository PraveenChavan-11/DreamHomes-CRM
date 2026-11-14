<?php
$path = 'd:\\Personal\\TalentHub\\app\\Views\\public\\insights\\index.php';
$content = file_get_contents($path);

$old_block = '      <!-- PAGINATION -->
      <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-center gap-2 mt-12 mb-16">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="<?= base_url(\'insights?page=\' . $p) ?>" class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition <?= ($p === $currentPage) ? \'bg-[#1D6A34] text-white shadow-sm\' : \'border border-[rgba(29,106,52,0.2)] bg-white text-[#172B22] hover:bg-[#EAF5EF]\' ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

      <!-- INLINE NEWSLETTER CALLOUT BANNER -->
      <div class="mt-16 bg-gradient-to-r from-[#072412] via-[#1D6A34] to-[#1B5F52] text-white rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden border border-white/10">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-[#37CE97]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 max-w-3xl">
          <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-white/10 text-[#37CE97] border border-white/15 mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            <span>TalentHub Editorial Desk</span>
          </span>
          <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white mb-2">
            Get Pune Market Trends &amp; Hiring Reports Delivered
          </h2>
          <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed mb-6 max-w-xl">
            Stay informed with our quarterly engineering salary benchmarks, Hinjawadi tech hiring indices, and executive recruitment updates. Double opt-in verified, 1-click unsubscribe anytime.
          </p>

          <form data-newsletter="true" data-source="insights_archive" class="space-y-3 max-w-xl">
            <div class="flex flex-col sm:flex-row gap-2.5">
              <input type="text" name="first_name" placeholder="First Name (Optional)" class="w-full sm:w-1/3 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-xs focus:outline-none focus:border-[#37CE97] focus:ring-1 focus:ring-[#37CE97]">
              <input type="email" name="email" required placeholder="Enter your work email address..." class="w-full sm:w-2/3 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 text-xs focus:outline-none focus:border-[#37CE97] focus:ring-1 focus:ring-[#37CE97]">
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-1">
              <label class="flex items-center gap-2 text-[11px] text-white/80 cursor-pointer">
                <input type="checkbox" name="consent" value="1" required checked class="rounded border-white/30 bg-white/10 text-[#37CE97] focus:ring-0">
                <span>I agree to receive recruitment intelligence emails</span>
              </label>
              <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#37CE97] hover:bg-[#2eb986] text-[#072412] text-xs font-black shadow-lg shadow-[#37CE97]/20 transition-all shrink-0">
                Subscribe Free
              </button>
            </div>
          </form>
        </div>
      </div>';

$new_block = '      <!-- PROFESSIONAL PAGINATION -->
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
                $paginatedItems[] = \'...\';
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
              <a href="<?= base_url(\'insights?\' . http_build_query(array_merge($queryParams, [\'page\' => $currentPage - 1]))) ?>"
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
                <?php if ($item === \'...\'): ?>
                  <span class="w-9 h-9 flex items-center justify-center text-xs text-slate-400 select-none">&hellip;</span>
                <?php elseif ($item === $currentPage): ?>
                  <span class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black shadow-sm"
                        style="background-color: #1D6A34 !important; color: #FFFFFF !important; box-shadow: 0 4px 12px rgba(29,106,52,0.25) !important;"
                        aria-current="page">
                    <?= $item ?>
                  </span>
                <?php else: ?>
                  <a href="<?= base_url(\'insights?\' . http_build_query(array_merge($queryParams, [\'page\' => $item]))) ?>"
                     class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition border border-[rgba(29,106,52,0.25)] bg-white text-[#172B22] hover:bg-[#EAF5EF] hover:text-[#1D6A34] shadow-sm">
                    <?= $item ?>
                  </a>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>

            <!-- NEXT -->
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= base_url(\'insights?\' . http_build_query(array_merge($queryParams, [\'page\' => $currentPage + 1]))) ?>"
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
            Get Pune Market Trends &amp; Hiring Reports Delivered
          </h2>
          <p class="text-xs sm:text-sm leading-relaxed mb-6 max-w-xl" style="color: rgba(255, 255, 255, 0.9) !important;">
            Stay informed with our quarterly engineering salary benchmarks, Hinjawadi tech hiring indices, and executive recruitment updates. Double opt-in verified, 1-click unsubscribe anytime.
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
      </div>';

$content_norm = str_replace("\r\n", "\n", $content);
$old_norm = str_replace("\r\n", "\n", $old_block);
$new_norm = str_replace("\r\n", "\n", $new_block);

if (strpos($content_norm, $old_norm) !== false) {
    $updated = str_replace($old_norm, $new_norm, $content_norm);
    file_put_contents($path, $updated);
    echo "SUCCESS: insights/index.php updated!\n";
} else {
    echo "FAILED: old_block not found in content!\n";
}

