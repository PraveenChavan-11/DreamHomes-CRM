<?php
/**
 * TalentHub Consultant — For Candidates & Professionals
 * Career Guidance, Zero Candidate Fees, and Direct Corporate Access
 */
?>

<section class="th-hero th-hero--page">
  <div class="page-width" style="max-width:56rem">
    <span class="th-eyebrow" data-hero>For Job Seekers &amp; Working Professionals</span>
    <h1 class="th-h1" data-hero style="--d:.1s">Advance Your Career with Employers Across India</h1>
    <p class="th-lead" data-hero style="--d:.2s">Connect with our recruitment team for verified corporate roles across India. Candidate representation is free of charge.</p>
    <div class="th-btn-row" style="justify-content:center;margin-top:1.5rem" data-hero style="--d:.3s">
      <a href="<?= base_url('jobs') ?>" class="th-btn th-btn--light"><span>Explore Active Vacancies</span></a>
      <a href="<?= base_url('career-enquiry') ?>" class="th-btn th-btn--outline-light"><span>Drop Resume in Talent Bank</span></a>
      <?php if (!empty($settings['whatsapp_channel_url'])): ?>
      <a href="<?= esc($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener noreferrer" class="th-btn th-btn--outline-light btn-whatsapp-channel"><span>Join Our WhatsApp Channel – Get Latest Job Updates</span></a>
      <?php endif; ?>
    </div>

  </div>
</section>
<div class="pt-12 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">

    <!-- HERO SECTION -->

    <!-- CANDIDATE VALUE PILLARS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
      <div class="card-premium p-8 bg-white shadow-sm space-y-3">
        <div class="w-12 h-12 rounded-2xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center font-bold text-base border border-[#1D6A34]/15">
          ₹0
        </div>
        <h3 class="text-lg font-bold text-[#172B22]">Zero Placement Fees</h3>
        <p class="text-xs text-[#3d6350] leading-relaxed">
          Candidate services are strictly non-chargeable. Corporate employers pay our placement fees. Job seekers never pay any registration fees or hidden charges.
        </p>
      </div>

      <div class="card-premium p-8 bg-white shadow-sm space-y-3">
        <div class="w-12 h-12 rounded-2xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center border border-[#1D6A34]/15">
          <svg class="w-6 h-6 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-[#172B22]">Strict Confidentiality</h3>
        <p class="text-xs text-[#3d6350] leading-relaxed">
          Your current employment and profile details are completely confidential. We only submit your resume with your explicit knowledge and prior consent.
        </p>
      </div>

      <div class="card-premium p-8 bg-white shadow-sm space-y-3">
        <div class="w-12 h-12 rounded-2xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center border border-[#1D6A34]/15">
          <svg class="w-6 h-6 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-[#172B22]">Direct Interview Access</h3>
        <p class="text-xs text-[#3d6350] leading-relaxed">
          Bypass anonymous job portals and resume black holes. Our recruiters place your CV directly before verified decision-makers and hiring directors.
        </p>
      </div>
    </div>

    <!-- 4-STEP CANDIDATE JOURNEY -->
    <div class="card-premium p-10 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20 mb-16">
      <div class="text-center max-w-2xl mx-auto mb-10">
        <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Placement Process</span>
        <h2 class="text-2xl font-extrabold text-[#172B22] mt-1">How We Accelerate Your Hiring</h2>
        <p class="text-xs sm:text-sm text-[#3d6350] mt-1.5">A clear, supportive candidate representation workflow.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-[rgba(29,106,52,0.12)]">
          <span class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider block mb-1">Step 01</span>
          <h4 class="text-sm font-bold text-[#172B22] mb-1">Profile Discovery</h4>
          <p class="text-xs text-[#3d6350] leading-relaxed">Submit your resume with preferred work locations and compensation goals.</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-[rgba(29,106,52,0.12)]">
          <span class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider block mb-1">Step 02</span>
          <h4 class="text-sm font-bold text-[#172B22] mb-1">Recruiter Call</h4>
          <p class="text-xs text-[#3d6350] leading-relaxed">A specialized domain recruiter calls to review relevant mandates and discuss company culture.</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-[rgba(29,106,52,0.12)]">
          <span class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider block mb-1">Step 03</span>
          <h4 class="text-sm font-bold text-[#172B22] mb-1">Client Presentation</h4>
          <p class="text-xs text-[#3d6350] leading-relaxed">With your consent, your profile is pitched directly to the hiring manager for fast-tracked interviews.</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-[rgba(29,106,52,0.12)]">
          <span class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider block mb-1">Step 04</span>
          <h4 class="text-sm font-bold text-[#172B22] mb-1">Offer &amp; Onboarding</h4>
          <p class="text-xs text-[#3d6350] leading-relaxed">We assist in salary negotiation, offer letter acceptance, and smooth resignation handover.</p>
        </div>
      </div>
    </div>

    <!-- FINAL PATHWAY CALLOUT -->
    <div class="text-center space-y-4 max-w-xl mx-auto">
      <h3 class="text-2xl font-extrabold text-[#172B22]">Ready to Discover Your Next Career Move?</h3>
      <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
        <a href="<?= base_url('jobs') ?>" class="btn-primary text-xs font-bold">
          <span>Explore Verified Openings</span>
        </a>
        <a href="<?= base_url('career-enquiry') ?>" class="btn-outline text-xs font-bold">
          <span>Submit Resume to Talent Bank</span>
        </a>
      </div>
      <p class="text-xs text-[#3d6350] pt-2">
        Candidates can also directly email their resumes to our hiring desk: 
        <a href="mailto:<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold underline hover:text-[#155227]">
          <?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>
        </a>
      </p>
    </div>

  </div>
</div>
