<?php
/**
 * TalentHub Consultant — Frequently Asked Questions View
 * Accessible Accordions & Schema.org FAQPage Integration
 */
$faqs = $faqs ?? [];

// Build FAQ Schema dynamically from database records
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'FAQPage',
    'mainEntity' => []
];

foreach ($faqs as $faq) {
    $faqSchema['mainEntity'][] = [
        '@type' => 'Question',
        'name'  => $faq['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => strip_tags($faq['answer'])
        ]
    ];
}
?>

<section class="th-hero th-hero--page">
  <div class="page-width" style="max-width:56rem">
    <span class="th-eyebrow" data-hero>Help Center &amp; Knowledge Base</span>
    <h1 class="th-h1" data-hero style="--d:.1s">Frequently Asked Questions</h1>
    <p class="th-lead" data-hero style="--d:.2s">Transparent answers regarding recruitment processes, candidate fee policy, employer service timelines, and interview coordination across India.</p>
  </div>
</section>
<div class="bg-[#FAF9F5] min-h-screen pt-12 th-page pb-24 text-[#172B22]">
  <!-- Dynamic FAQ Schema -->
  <script type="application/ld+json">
    <?= json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Hero Banner -->

  <div class="page-width max-w-4xl">
    <!-- Header Meta Bar -->
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4 border-b border-[rgba(29,106,52,0.12)] pb-4">
      <div class="flex items-center gap-2 text-xs font-bold text-[#1D6A34]">
        <svg class="h-4 w-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>Verified Consultancy Practices</span>
      </div>
      <span class="text-xs text-[#3d6350] font-semibold"><?= count($faqs) ?> verified questions &amp; answers</span>
    </div>

    <!-- Accordion List -->
    <div class="space-y-4">
      <?php if (!empty($faqs)): ?>
        <?php foreach ($faqs as $index => $faq): ?>
          <div class="card-premium p-5 bg-white">
            <button type="button" class="accordion-trigger w-full flex items-center justify-between gap-4 text-left font-bold text-sm sm:text-base text-[#172B22] cursor-pointer" aria-expanded="false">
              <span class="leading-snug"><?= esc($faq['question']) ?></span>
              <div class="accordion-icon w-8 h-8 rounded-xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center shrink-0 transition-transform duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </button>
            <div class="hidden pt-3 text-xs sm:text-sm text-[#3d6350] leading-relaxed border-t border-[rgba(29,106,52,0.1)] mt-3">
              <?= nl2br(esc($faq['answer'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card-premium p-10 text-center bg-white">
          <p class="text-xs sm:text-sm text-[#3d6350]">Frequently asked questions are currently being updated.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Contact Support Box -->
    <div class="mt-14 card-premium p-8 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20 text-center space-y-3">
      <h3 class="text-xl font-bold text-[#172B22]">Have a Specific Question?</h3>
      <p class="text-xs sm:text-sm text-[#3d6350] max-w-md mx-auto leading-relaxed">
        Our team is available to assist hiring managers and job seekers directly.
      </p>
      <div class="pt-2">
        <a href="<?= base_url('contact') ?>" class="btn-primary text-xs font-bold px-6 py-3">
          <span>Speak Directly With Our Hiring Desk</span>
        </a>
      </div>
    </div>
  </div>
</div>
