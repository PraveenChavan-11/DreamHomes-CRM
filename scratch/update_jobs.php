<?php
$path = 'd:\\Personal\\TalentHub\\app\\Views\\public\\jobs\\index.php';
$content = file_get_contents($path);

$old_block = '          <!-- SERVER-SIDE PAGINATION -->
          <?php if ($totalPages > 1): ?>
            <div class="pt-8 flex items-center justify-center gap-2">
              <?php if ($currentPage > 1): ?>
                <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $currentPage - 1])) ?>" class="p-2.5 rounded-xl border border-[rgba(29,106,52,0.2)] bg-white text-xs font-bold text-[#1D6A34] hover:bg-[#EAF5EF] transition">
                  &larr; Previous
                </a>
              <?php endif; ?>

              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $i])) ?>" class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition <?= ($i === $currentPage) ? \'bg-[#1D6A34] text-white\' : \'bg-white border border-[rgba(29,106,52,0.2)] text-[#172B22] hover:bg-[#EAF5EF]\' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($currentPage < $totalPages): ?>
                <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $currentPage + 1])) ?>" class="p-2.5 rounded-xl border border-[rgba(29,106,52,0.2)] bg-white text-xs font-bold text-[#1D6A34] hover:bg-[#EAF5EF] transition">
                  Next &rarr;
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>';

$new_block = '          <!-- SERVER-SIDE PROFESSIONAL PAGINATION -->
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
                    $paginatedItems[] = \'...\';
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
                  <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $currentPage - 1])) ?>"
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
                      <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $item])) ?>"
                         class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition border border-[rgba(29,106,52,0.25)] bg-white text-[#172B22] hover:bg-[#EAF5EF] hover:text-[#1D6A34] shadow-sm">
                        <?= $item ?>
                      </a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>

                <!-- NEXT -->
                <?php if ($currentPage < $totalPages): ?>
                  <a href="<?= current_url() . \'?\' . http_build_query(array_merge($filters, [\'page\' => $currentPage + 1])) ?>"
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
          <?php endif; ?>';

$content_norm = str_replace("\r\n", "\n", $content);
$old_norm = str_replace("\r\n", "\n", $old_block);
$new_norm = str_replace("\r\n", "\n", $new_block);

if (strpos($content_norm, $old_norm) !== false) {
    $updated = str_replace($old_norm, $new_norm, $content_norm);
    file_put_contents($path, $updated);
    echo "SUCCESS: jobs/index.php updated!\n";
} else {
    echo "FAILED: old_block not found in content!\n";
}

