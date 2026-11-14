<section class="min-h-[80vh] pt-32 pb-20 relative overflow-hidden bg-gradient-to-b from-[#FAF9F5] via-[#EAF5EF]/40 to-[#FAF9F5]">

  <!-- Ambient Glow Effects -->
  <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-[radial-gradient(circle,rgba(55,206,151,0.18)_0%,transparent_70%)] pointer-events-none blur-3xl"></div>

  <div class="page-width max-w-3xl relative z-10 text-center">

    <!-- Success Card -->
    <div class="bg-white rounded-3xl p-8 sm:p-12 border border-[rgba(29,106,52,0.15)] shadow-2xl shadow-[#1D6A34]/5 text-center relative overflow-hidden">
      <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-[#1D6A34] via-[#37CE97] to-[#155227]"></div>

      <!-- Animated Success Circle -->
      <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-full bg-[#EAF5EF] border-2 border-[#37CE97] flex items-center justify-center text-[#1D6A34] shadow-lg shadow-[#37CE97]/20 mb-6">
        <svg class="w-10 h-10 sm:w-12 sm:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
      </div>

      <!-- Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#1D6A34]/10 text-[#1D6A34] text-xs font-extrabold uppercase tracking-wider mb-4">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span><?= esc($config['badge']) ?></span>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl sm:text-4xl font-extrabold text-[#172B22] tracking-tight mb-3">
        <?= esc($config['heading']) ?>
      </h1>

      <!-- Subtitle -->
      <p class="text-sm sm:text-base text-[#3d6350] max-w-xl mx-auto leading-relaxed mb-6 font-normal">
        <?= esc($config['subheading']) ?>
      </p>

      <?php if (!empty($ref)): ?>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 mb-8">
          <span>Tracking Reference:</span>
          <span class="font-mono text-[#1D6A34]"><?= esc($ref) ?></span>
        </div>
      <?php endif; ?>

      <!-- 3-Step Process Timeline -->
      <div class="my-8 pt-8 border-t border-[rgba(29,106,52,0.12)] text-left">
        <h3 class="text-xs uppercase tracking-wider text-[#1D6A34] font-extrabold mb-5 text-center">
          What Happens Next — Dedicated Pune SLA
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <?php foreach ($config['timeline'] as $item): ?>
            <div class="p-4 rounded-2xl bg-[#FAF9F5] border border-[rgba(29,106,52,0.1)] flex flex-col justify-between">
              <div>
                <span class="inline-block w-6 h-6 rounded-lg bg-[#1D6A34] text-white text-xs font-bold text-center leading-6 mb-2">
                  <?= esc($item['step']) ?>
                </span>
                <h4 class="text-xs sm:text-sm font-bold text-[#172B22] mb-1"><?= esc($item['title']) ?></h4>
                <p class="text-[11px] text-[#3d6350] leading-relaxed"><?= esc($item['desc']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Direct Recruiter Assistance Strip -->
      <div class="my-6 p-4 rounded-2xl bg-[#EAF5EF]/70 border border-[#37CE97]/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-white text-[#1D6A34] flex items-center justify-center shrink-0 shadow-sm">
            <svg class="w-5 h-5 text-[#37CE97]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
          </div>
          <div>
            <h4 class="text-xs font-bold text-[#172B22]">Have an urgent mandate or immediate enquiry?</h4>
            <p class="text-[11px] text-[#3d6350]">Chat directly with a Senior Recruitment Partner on WhatsApp.</p>
          </div>
        </div>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '917276146573') ?>?text=Hello%20TalentHub%20Team%2C%20I%20have%20submitted%20an%20enquiry%20and%20would%20like%20to%20connect%20with%20a%20consultant."
           target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white text-xs font-bold shadow-sm transition shrink-0">
          <span>WhatsApp Desk</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
      </div>

      <!-- CTAs -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
        <a href="<?= esc($config['primary_cta']['url']) ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 rounded-full bg-[#1D6A34] hover:bg-[#155227] text-white text-xs sm:text-sm font-bold shadow-md shadow-[#1D6A34]/20 transition transform hover:-translate-y-0.5">
          <span><?= esc($config['primary_cta']['label']) ?></span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
        <a href="<?= esc($config['secondary_cta']['url']) ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full border border-[rgba(29,106,52,0.25)] text-[#1D6A34] hover:bg-[#1D6A34]/5 text-xs sm:text-sm font-bold transition">
          <span><?= esc($config['secondary_cta']['label']) ?></span>
        </a>
      </div>

    </div>

  </div>
</section>

