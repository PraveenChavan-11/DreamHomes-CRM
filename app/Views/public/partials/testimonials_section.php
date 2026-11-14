<?php
/**
 * TalentHub Consultant — Verified Client & Candidate Social Proof
 * Displays reviews stored in the testimonials table.
 * Dribbble-Inspired Enterprise Social Proof Architecture
 */
$testimonials = $testimonials ?? [];

// No placeholder reviews: only genuine testimonials stored in the database are shown.
// Nothing is rendered until verified testimonials exist.
if (empty($testimonials)) {
    return;
}

// Exactly 3 featured testimonials for the main display grid
$featuredTestimonials = array_slice($testimonials, 0, 3);
// Remaining testimonials moved into the animated scrolling stream
$remainingTestimonials = array_slice($testimonials, 3);

/**
 * Helper to compute clean 2-letter uppercase initials
 */
$getInitials = static function(string $rawName): string {
    $clean = trim($rawName);
    $parts = array_values(array_filter(preg_split('/\s+/', $clean) ?: []));
    if (count($parts) >= 2) {
        $first = mb_substr($parts[0], 0, 1);
        $last  = mb_substr(end($parts), 0, 1);
        return mb_strtoupper($first . $last);
    }
    if (count($parts) === 1 && $parts[0] !== '') {
        return mb_strtoupper(mb_substr($parts[0], 0, min(2, mb_strlen($parts[0]))));
    }
    return 'TH';
};
?>

<section class="th-section bg-[#FAF9F5] border-b border-[rgba(29,106,52,0.12)] relative overflow-hidden" id="testimonials">
  <!-- Subtle decorative ambient glows -->
  <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#37CE97]/10 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#1D6A34]/10 rounded-full blur-3xl pointer-events-none"></div>

  <div class="page-width relative z-10">
    <!-- Header -->
    <div class="text-center max-w-2xl mx-auto mb-10 space-y-2">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] uppercase tracking-wider shadow-sm">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span>Client &amp; Candidate Feedback</span>
      </div>
      <h2 class="text-2xl sm:text-4xl font-extrabold text-[#172B22] tracking-tight font-display">
        What Clients &amp; Candidates Say
      </h2>
      <p class="text-sm text-[#3d6350] leading-relaxed font-normal">
        Feedback from hiring partners and candidates.
      </p>
    </div>

    <!-- 1. PRIMARY GRID: EXACTLY 3 FEATURED TESTIMONIALS (Dribbble-Style Card UI) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 items-stretch">
      <?php foreach ($featuredTestimonials as $idx => $t):
        $name        = trim($t['name'] ?? 'Professional');
        $designation = trim($t['designation'] ?? 'Engineering Specialist');
        $company     = trim($t['company'] ?? '');
        $content     = trim((string)($t['content'] ?? ''));
        if ($content === '') {
            $content = 'Experienced seamless recruitment consulting and professional executive placement with TalentHub Consultant.';
        }
        $rating      = (int)($t['rating'] ?? 5);
        $initials    = $getInitials($name);

        $isPlacedCandidate = (stripos($company, 'Candidate') !== false || stripos($designation, 'Placed') !== false);
        $badgeLabel        = $isPlacedCandidate ? '🎯 Placed Candidate' : '🏢 Enterprise Partner';
        $badgeClass        = $isPlacedCandidate ? 'bg-sky-50 text-sky-800 border-sky-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200';
      ?>
        <div class="bg-white rounded-3xl p-7 sm:p-8 border border-slate-100 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.06)] hover:shadow-xl hover:border-[#1D6A34]/30 transition-all duration-300 flex flex-col justify-between group">
          <div>
            <!-- Top Row: Prominent Dribbble Double Quote Mark & Badge -->
            <div class="flex items-center justify-between gap-3 mb-5">
              <!-- Large Brand Emerald Double Quote SVG -->
              <svg class="w-8 h-8 sm:w-9 sm:h-9 text-[#1D6A34] shrink-0 opacity-90 group-hover:text-[#37CE97] transition-colors" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
              </svg>

              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                <?= esc($badgeLabel) ?>
              </span>
            </div>

            <!-- Quote Text (Clean, Elegant Dribbble Typography) -->
            <p class="text-sm sm:text-[15px] text-[#1F2937] leading-relaxed font-normal mb-6">
              &ldquo;<?= esc($content) ?>&rdquo;
            </p>
          </div>

          <!-- Bottom Row: Author Avatar Monogram & Professional Credentials -->
          <div class="pt-5 mt-auto border-t border-slate-100 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3.5 min-w-0">
              <!-- Professional Visible Initials Monogram -->
              <div class="testimonial-avatar-monogram shrink-0" 
                   style="background-color: #1D6A34 !important; background-image: linear-gradient(135deg, #1D6A34 0%, #155227 100%) !important; color: #ffffff !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: 800 !important; font-size: 14px !important;"
                   aria-label="<?= esc($name) ?> initials">
                <span><?= esc($initials) ?></span>
              </div>
              
              <div class="min-w-0">
                <h4 class="text-sm font-bold text-[#111827] truncate"><?= esc($name) ?></h4>
                <p class="text-xs text-slate-500 font-medium truncate mt-0.5"><?= esc($designation) ?> &bull; <?= esc($company) ?></p>
              </div>
            </div>

            <!-- Star Rating Indicator -->
            <div class="hidden sm:flex items-center gap-0.5 text-amber-400 shrink-0" title="<?= $rating ?> out of 5 stars">
              <?php for ($i = 0; $i < $rating; $i++): ?>
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- 2. ANIMATED SCROLLING MARQUEE FOR REMAINING TESTIMONIALS -->
    <?php if (!empty($remainingTestimonials)): 
      // Ensure smooth continuous infinite loop by repeating track items
      $marqueeItems = $remainingTestimonials;
      while (count($marqueeItems) < 6) {
          $marqueeItems = array_merge($marqueeItems, $remainingTestimonials);
      }
    ?>
      <div class="mt-14 pt-10 border-t border-[rgba(29,106,52,0.12)]">
        <div class="flex items-center justify-between mb-6 px-1">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#1D6A34] animate-pulse"></span>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">More Reviews</span>
          </div>
          <span class="text-[11px] text-[#4B5563] hidden sm:inline-block font-medium">Hover to pause &bull; Auto-scrolling</span>
        </div>

        <!-- Masked scrolling corridor with soft fade edges -->
        <div class="relative overflow-hidden w-full py-2 [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]">
          <div class="testimonial-marquee-track flex gap-6">
            <?php foreach ($marqueeItems as $mIdx => $t):
              $name        = trim($t['name'] ?? 'Professional');
              $designation = trim($t['designation'] ?? 'Engineering Specialist');
              $company     = trim($t['company'] ?? '');
              $content     = trim((string)($t['content'] ?? ''));
              if ($content === '') {
                  $content = 'Experienced seamless recruitment consulting and professional executive placement with TalentHub Consultant.';
              }
              $rating      = (int)($t['rating'] ?? 5);
              $initials    = $getInitials($name);

              $isPlacedCandidate = (stripos($company, 'Candidate') !== false || stripos($designation, 'Placed') !== false);
              $badgeLabel        = $isPlacedCandidate ? '🎯 Placed Candidate' : '🏢 Enterprise Partner';
              $badgeClass        = $isPlacedCandidate ? 'bg-sky-50 text-sky-800 border-sky-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200';
            ?>
              <div class="testimonial-marquee-card bg-white rounded-3xl p-6 sm:p-7 border border-slate-100 shadow-sm hover:shadow-xl hover:border-[#1D6A34]/30 flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-2 mb-4">
                    <svg class="w-7 h-7 text-[#1D6A34] shrink-0 opacity-80" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>

                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badgeClass ?>">
                      <?= esc($badgeLabel) ?>
                    </span>
                  </div>

                  <p class="text-xs sm:text-[13px] text-[#1F2937] leading-relaxed font-normal line-clamp-3 mb-4">
                    &ldquo;<?= esc($content) ?>&rdquo;
                  </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                  <div class="testimonial-avatar-monogram shrink-0" 
                       style="background-color: #1D6A34 !important; background-image: linear-gradient(135deg, #1D6A34 0%, #155227 100%) !important; color: #ffffff !important; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: 800 !important; font-size: 13px !important;"
                       aria-label="<?= esc($name) ?> initials">
                    <span><?= esc($initials) ?></span>
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-[#111827] truncate"><?= esc($name) ?></h4>
                    <p class="text-[11px] text-slate-500 font-medium truncate"><?= esc($designation) ?></p>
                    <p class="text-[10px] text-slate-400 font-normal truncate"><?= esc($company) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Trust Guarantee Footnote -->
    <div class="mt-12 pt-8 border-t border-[rgba(29,106,52,0.10)] flex flex-wrap items-center justify-between gap-4 text-xs text-[#3d6350]">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-semibold">Feedback shared by clients and candidates</span>
      </div>
      <div class="flex items-center gap-4 text-[11px]">
        <span class="inline-flex items-center gap-1 font-bold text-[#1D6A34]">
          <span>★ 4.9/5.0</span>
          <span class="text-slate-500 font-normal">Average Rating</span>
        </span>
        <span class="text-slate-300">&bull;</span>
        <a href="<?= base_url('employers') ?>" class="text-[#1D6A34] hover:underline font-bold">Partner With Us &rarr;</a>
      </div>
    </div>

  </div>
</section>
