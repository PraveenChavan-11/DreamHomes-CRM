<?php
/**
 * TalentHub Consultant — Career Enquiry / Private Talent Bank Registration
 * Direct Candidate Profile Vaulting with Zero Placement Fees
 */
?>

<div class="pt-32 pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-3xl">

    <div class="text-center mb-10 space-y-3">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] uppercase tracking-wider">
        <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
        <span>Confidential Talent Pool — 100% Free for Candidates</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#172B22] tracking-tight">Submit Your Career Preferences</h1>
      <p class="text-xs sm:text-sm text-[#3d6350] max-w-xl mx-auto leading-relaxed font-normal">
        Join our private talent repository. When an unlisted, active, or confidential mandate matching your profile opens in Pune, our recruiters will connect directly.
      </p>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="mb-6 p-4 rounded-2xl bg-[#072412] border border-[#37CE97]/30 text-white text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-[#37CE97] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="mb-6 p-4 rounded-2xl bg-red-950 border border-red-500/30 text-white text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
      </div>
    <?php endif; ?>

    <!-- Official WhatsApp Channel Callout (Admin Controlled) -->
    <?php if (!empty($settings['whatsapp_channel_url'])): ?>
    <div class="mb-6 p-4 rounded-2xl bg-[#EAF5EF] border border-[#25D366]/40 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-[#25D366] text-white flex items-center justify-center shrink-0 shadow-sm">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
        </div>
        <p class="text-xs text-[#172B22] font-bold">Get immediate notifications about verified Pune job openings on WhatsApp</p>
      </div>
      <a href="<?= esc($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-channel text-xs font-bold py-2.5 px-4 shadow-sm whitespace-nowrap shrink-0 rounded-xl" style="background-color: #25D366 !important; color: #FFFFFF !important; border: 1.5px solid #20BA59 !important;">
        <span style="color: #FFFFFF !important; font-weight: 700 !important;">Join Our WhatsApp Channel – Get Latest Job Updates</span>
      </a>
    </div>
    <?php endif; ?>

    <div class="card-premium p-8 sm:p-10 bg-white shadow-xl shadow-[#1D6A34]/5">
      <form action="<?= base_url('career-enquiry/submit') ?>" method="POST" enctype="multipart/form-data" data-ajax="true" toolname="register_candidate_profile" tooldescription="Submit confidential candidate profile and resume to Talent Hub Pune talent pool with zero placement fees" class="space-y-5">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Full Name *</label>
            <input type="text" name="full_name" required placeholder="e.g. Amit Patil" class="form-input-premium text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Email Address *</label>
            <input type="email" name="email" required placeholder="amit@example.com" class="form-input-premium text-xs">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Mobile Phone *</label>
            <input type="tel" name="phone" required placeholder="+91 98230 45678" pattern="^(\+?[0-9\s\-\(\)]{8,20})$" title="Please enter a valid 10-digit Indian (+91) or international mobile number" class="form-input-premium text-xs">
            <span class="text-[10px] text-[#3d6350] mt-0.5 block">Accepts Indian (+91) and international mobile formats</span>
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Current Residential Location</label>
            <input type="text" name="location" placeholder="e.g. Kothrud, Pune" class="form-input-premium text-xs">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Desired Role / Technology Stack *</label>
            <input type="text" name="desired_role" required placeholder="e.g. Lead Java Spring Boot Architect" class="form-input-premium text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Total Experience (Years)</label>
            <input type="number" min="0" max="50" step="0.5" name="experience_years" placeholder="e.g. 6.5" class="form-input-premium text-xs">
            <span class="text-[10px] text-[#3d6350] mt-0.5 block">0 to 50 years in increments of 0.5</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Preferred Pune Work Location</label>
            <select name="preferred_location" class="form-input-premium text-xs cursor-pointer">
              <option value="Any Pune Corridor" selected>Any Pune Corridor</option>
              <option value="Hinjawadi IT Park">Hinjawadi IT Park (Phase 1–3)</option>
              <option value="Kharadi / Magarpatta">Kharadi / Magarpatta (East Pune)</option>
              <option value="Chakan / Bhosari">Chakan / Bhosari (Automotive Corridor)</option>
              <option value="Baner / Balewadi">Baner / Balewadi</option>
              <option value="Hybrid / Remote">Hybrid / Remote</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Current Notice Period</label>
            <select name="notice_period" class="form-input-premium text-xs cursor-pointer">
              <option value="Immediate" selected>Immediate Joiner</option>
              <option value="15 Days">15 Days</option>
              <option value="30 Days">30 Days</option>
              <option value="60 Days">60 Days</option>
              <option value="90 Days">90 Days</option>
              <option value="Serving Notice">Serving Notice Period</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Attach Resume Document (PDF, DOC, DOCX up to 5MB) *</label>
          <input type="file" name="resume" id="career-resume-input" required accept=".pdf,.doc,.docx" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-dashed border-[rgba(29,106,52,0.3)] bg-[#FAF9F5] focus:outline-none text-[#172B22]">
          <div id="career-resume-error" class="text-xs text-red-600 font-semibold mt-1 hidden" role="alert"></div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Additional Notes / Current CTC / Expectations (Optional)</label>
          <textarea name="notes" rows="3" placeholder="Briefly describe your core strengths, preferred compensation, or target industry..." class="form-input-premium text-xs"></textarea>
        </div>

        <!-- Anti-Spam, Honeypot & CAPTCHA Protection -->
        <?= $formProtection ?? '' ?>

        <div class="flex items-start gap-2 pt-1">
          <input type="checkbox" name="consent" id="career-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
          <label for="career-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">
            I affirmatively consent to TalentHub Consultant securely retaining my CV in its confidential talent bank for future job matches in Pune under the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy (v1.0-DPDP)</a>.
          </label>
        </div>

        <div class="pt-2">
          <button type="submit" id="career-submit-btn" class="w-full btn-primary text-xs font-bold py-3.5 shadow-lg shadow-[#1D6A34]/25">
            <span>Submit Profile to Talent Bank</span>
            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </button>
        </div>
      </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const resumeInput = document.getElementById('career-resume-input');
      const errorDiv = document.getElementById('career-resume-error');
      const submitBtn = document.getElementById('career-submit-btn');

      if (resumeInput) {
        resumeInput.addEventListener('change', function() {
          const file = this.files[0];
          errorDiv.classList.add('hidden');
          errorDiv.textContent = '';
          if (!file) return;

          const allowedExts = ['pdf', 'doc', 'docx'];
          const ext = file.name.split('.').pop().toLowerCase();
          const maxBytes = 5 * 1024 * 1024; // 5 MB

          if (!allowedExts.includes(ext)) {
            errorDiv.textContent = 'Invalid file extension. Only .pdf, .doc, and .docx documents are accepted.';
            errorDiv.classList.remove('hidden');
            this.value = '';
            return;
          }

          if (file.size > maxBytes) {
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            errorDiv.textContent = 'File is too large (' + sizeMb + ' MB). Maximum allowed size is 5.0 MB.';
            errorDiv.classList.remove('hidden');
            this.value = '';
            return;
          }
        });
      }
    });
    </script>

    <!-- Candidate Guarantee & Trust Callout -->
    <div class="mt-8 text-center text-xs text-[#3d6350] space-y-2">
      <p class="font-bold text-[#172B22]">100% Confidential Candidate Representation Guarantee</p>
      <p>We never share your profile with any employer without your prior discussion and consent.</p>
      <p class="pt-2 text-[11px] text-[#3d6350]">
        Prefer sending your resume via direct email? Candidates can directly email their CVs to: 
        <a href="mailto:<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold underline hover:text-[#155227]">
          <?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>
        </a>
      </p>
    </div>

  </div>
</div>
