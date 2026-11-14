<?php
/**
 * TalentHub Consultant — Virtual Interview Coordination View
 * Technical Checklist & Microsoft Teams Meeting Access
 */
?>

<section class="th-hero th-hero--page">
  <div class="page-width" style="max-width:56rem">
    <span class="th-eyebrow" data-hero>Virtual Coordination</span>
    <h1 class="th-h1" data-hero style="--d:.1s">Virtual Interview Room</h1>
    <p class="th-lead" data-hero style="--d:.2s">Direct link and technical preparation checklist for candidate virtual interviews coordinated through TalentHub Consultant.</p>
  </div>
</section>
<div class="pt-12 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-3xl">


    <!-- MAIN ACCESS CARD -->
    <div class="card-premium p-8 sm:p-10 bg-white shadow-xl shadow-[#1D6A34]/5 space-y-6 text-center">
      <div class="w-16 h-16 rounded-3xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center mx-auto shadow-sm border border-[#1D6A34]/15">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      </div>

      <div class="space-y-2">
        <h2 class="text-xl font-bold text-[#172B22]">Scheduled Virtual Interview Desk</h2>
        <p class="text-xs sm:text-sm text-[#3d6350] max-w-md mx-auto leading-relaxed">
          <?= esc($instructions) ?>
        </p>
      </div>

      <?php if (!empty($hasValidInvitation) && !empty($teamsLink)): ?>
        <div class="pt-2">
          <a href="<?= esc($teamsLink) ?>" target="_blank" rel="noopener noreferrer" class="btn-primary text-xs font-bold px-8 py-3.5 shadow-lg shadow-[#1D6A34]/25">
            <span>Launch Microsoft Teams Meeting</span>
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          </a>
        </div>
      <?php else: ?>
        <div class="p-5 rounded-2xl bg-[#EAF5EF] border border-[#1D6A34]/20 text-left space-y-2 max-w-md mx-auto">
          <div class="flex items-center gap-2 text-xs font-bold text-[#1D6A34]">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Individual Meeting Invitation Required</span>
          </div>
          <p class="text-[11px] text-[#3d6350] leading-relaxed">
            Personalized Microsoft Teams session links are sent directly to shortlisted candidates via email invitation. If you have an upcoming interview scheduled today, please refer to your invitation email or contact your assigned recruitment partner.
          </p>
          <div class="pt-1 text-[11px]">
            <a href="mailto:hr@talenthubconsultant.com" class="font-bold text-[#1D6A34] hover:underline">hr@talenthubconsultant.com</a>
            <span class="text-gray-400 mx-1.5">•</span>
            <a href="tel:+917276146573" class="font-bold text-[#1D6A34] hover:underline">+91 72761 46573</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="pt-6 border-t border-gray-100 text-left space-y-3">
        <h3 class="text-xs font-bold text-[#172B22] uppercase tracking-wider">Candidate Preparation Checklist:</h3>
        <ul class="space-y-2 text-xs text-[#3d6350]">
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#37CE97] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Test microphone, earphones, and webcam prior to joining.</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#37CE97] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Keep your PDF resume open for reference during the discussion.</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#37CE97] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Join 5 minutes early to avoid technical delay.</span>
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#37CE97] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Ensure quiet surroundings with stable internet connectivity.</span>
          </li>
        </ul>
      </div>
    </div>

  </div>
</div>
