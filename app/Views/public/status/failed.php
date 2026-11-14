<section class="min-h-[80vh] pt-32 pb-20 relative overflow-hidden bg-gradient-to-b from-[#FAF9F5] via-amber-50/30 to-[#FAF9F5]">

  <!-- Ambient Glow Effects -->
  <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-[radial-gradient(circle,rgba(245,158,11,0.15)_0%,transparent_70%)] pointer-events-none blur-3xl"></div>

  <div class="page-width max-w-3xl relative z-10 text-center">

    <!-- Error Card -->
    <div class="bg-white rounded-3xl p-8 sm:p-12 border border-amber-200/80 shadow-2xl shadow-amber-900/5 text-center relative overflow-hidden">
      <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-500 via-rose-500 to-amber-600"></div>

      <!-- Warning Icon -->
      <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-full bg-amber-50 border-2 border-amber-400 flex items-center justify-center text-amber-600 shadow-lg shadow-amber-500/20 mb-6">
        <svg class="w-10 h-10 sm:w-12 sm:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
      </div>

      <!-- Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-extrabold uppercase tracking-wider mb-4">
        <span>Submission Incomplete</span>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] tracking-tight mb-3">
        We Couldn't Complete Your Submission
      </h1>

      <!-- Subtitle -->
      <p class="text-sm sm:text-base text-slate-600 max-w-xl mx-auto leading-relaxed mb-6 font-normal">
        <?= esc($reason) ?>
      </p>

      <!-- Reassurance Box -->
      <div class="my-6 p-4 rounded-2xl bg-amber-50/60 border border-amber-200 text-left">
        <h4 class="text-xs font-bold text-amber-950 mb-1 flex items-center gap-2">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Don't worry — Our Pune Hiring Team is Available Directly</span>
        </h4>
        <p class="text-[11px] text-amber-900 leading-relaxed">
          If you experienced a connection issue, file upload limit, or browser validation block, you can reach out directly via WhatsApp, Phone, or Email without losing your request.
        </p>
      </div>

      <!-- Instant Channels Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 my-6 text-left">
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '917276146573') ?>?text=Hello%20TalentHub%20Team%2C%20I%20faced%20an%20issue%20submitting%20my%20form%20online.%20Here%20are%20my%20details%3A"
           target="_blank" rel="noopener noreferrer"
           class="p-4 rounded-2xl bg-[#FAF9F5] border border-slate-200 hover:border-[#37CE97] transition-all group">
          <div class="flex items-center gap-2 mb-1 text-xs font-bold text-[#172B22] group-hover:text-[#1D6A34]">
            <svg class="w-4 h-4 text-[#37CE97]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            <span>WhatsApp Desk</span>
          </div>
          <p class="text-[11px] text-slate-500">Send requirement or CV directly.</p>
        </a>

        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $settings['contact_phone'] ?? '+917276146573') ?>"
           class="p-4 rounded-2xl bg-[#FAF9F5] border border-slate-200 hover:border-[#1D6A34] transition-all group">
          <div class="flex items-center gap-2 mb-1 text-xs font-bold text-[#172B22] group-hover:text-[#1D6A34]">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            <span>Call Katraj Desk</span>
          </div>
          <p class="text-[11px] text-slate-500"><?= esc($settings['contact_phone'] ?? '+91 72761 46573') ?></p>
        </a>

        <a href="mailto:<?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>"
           class="p-4 rounded-2xl bg-[#FAF9F5] border border-slate-200 hover:border-[#1D6A34] transition-all group">
          <div class="flex items-center gap-2 mb-1 text-xs font-bold text-[#172B22] group-hover:text-[#1D6A34]">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Email Hiring Desk</span>
          </div>
          <p class="text-[11px] text-slate-500"><?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?></p>
        </a>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
        <a href="javascript:history.back()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 rounded-full bg-[#1D6A34] hover:bg-[#155227] text-white text-xs sm:text-sm font-bold shadow-md shadow-[#1D6A34]/20 transition transform hover:-translate-y-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          <span>Go Back &amp; Retry Form</span>
        </a>
        <a href="<?= base_url() ?>" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-bold transition">
          <span>Return to Homepage</span>
        </a>
      </div>

    </div>

  </div>
</section>

