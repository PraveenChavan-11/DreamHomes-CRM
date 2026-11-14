<?php
/**
 * TalentHub Consultant — For Employers (B2B Talent Solutions)
 * High-Conversion Mandate Submission & Corporate Staffing Portal
 */
?>

<div class="pt-32 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">

    <!-- HERO & MANDATE SUBMISSION SPLIT -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start mb-20">

      <!-- LEFT: VALUE PROPOSITION -->
      <div class="w-full min-w-0 space-y-6">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#EAF5EF] border border-[#1D6A34]/20 text-xs font-bold text-[#1D6A34] uppercase tracking-wider">
          <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
          <span>Pan-India Recruitment Partner</span>
        </div>

        <h1 class="th-h1 th-h1--ink">
          Find and Hire Technical &amp; Leadership Talent Across India
        </h1>

        <p class="text-sm sm:text-base text-[#3d6350] leading-relaxed font-normal">
          Pre-screened candidates delivered directly to your hiring manager. Fees, timelines and replacement terms are agreed in writing before work begins.
        </p>

        <div class="space-y-4 pt-2">
          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-[#172B22]">Rapid Technical Screening</h3>
              <p class="text-xs text-[#3d6350] leading-relaxed">Every candidate's CTC, notice period feasibility, and practical competencies are verified before submission.</p>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-[#172B22]">Zero Upfront Retainer</h3>
              <p class="text-xs text-[#3d6350] leading-relaxed">Pure contingency placement. Fee is payable strictly upon candidate joining and successful onboarding.</p>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-[#172B22]">90-Day Replacement Assurance</h3>
              <p class="text-xs text-[#3d6350] leading-relaxed">Replacement terms are set out clearly in your engagement agreement.</p>
            </div>
          </div>
        </div>

        <div class="card-premium p-6 bg-white space-y-2">
          <p class="text-xs uppercase font-bold text-[#3d6350]">Need Immediate Assistance?</p>
          <p class="text-sm font-bold text-[#172B22]">Call Senior Recruitment Desk: <a href="tel:<?= preg_replace('/[^0-9+]/', '', $settings['contact_phone'] ?? '+917276146573') ?>" class="text-[#1D6A34] hover:underline font-mono"><?= esc($settings['contact_phone'] ?? '+91 72761 46573') ?></a></p>
          <p class="text-xs text-[#3d6350]">Customer & Employer Desk: <a href="mailto:<?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>" class="text-[#1D6A34] hover:underline font-mono"><?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?></a></p>
        </div>
      </div>

      <!-- RIGHT: SUBMIT MANDATE FORM -->
      <div class="w-full min-w-0">
        <div class="card-premium p-6 sm:p-8 bg-white shadow-xl shadow-[#1D6A34]/6 space-y-5">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">B2B Talent Request</span>
            <h2 class="text-2xl font-bold text-[#172B22] mt-1">Register a Hiring Mandate</h2>
            <p class="text-xs text-[#3d6350] mt-1">Our sector recruitment lead will review your roles and connect within 24 business hours.</p>
          </div>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="p-4 rounded-2xl bg-[#072412] text-white text-xs font-semibold flex items-center gap-2">
              <svg class="w-4 h-4 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span><?= esc(session()->getFlashdata('success')) ?></span>
            </div>
          <?php endif; ?>

          <form id="hiring-enquiry" action="<?= base_url('employers/enquiry') ?>" method="POST" data-ajax="true" class="space-y-4" toolname="submit_employer_mandate" tooldescription="Submit a corporate talent acquisition brief or staffing request to TalentHub Consultant Pune">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Company Name *</label>
                <input type="text" name="company_name" required placeholder="e.g. Persistent SaaS Ltd" class="form-input-premium text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Contact Person *</label>
                <input type="text" name="contact_person" required placeholder="e.g. Sanjay Verma (HR Head)" class="form-input-premium text-xs">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Official Work Email *</label>
                <input type="email" name="email" required placeholder="sanjay@company.com" class="form-input-premium text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Contact Phone *</label>
                <input type="tel" name="phone" required placeholder="+91 98220 12345" pattern="^(\+?[0-9\s\-\(\)]{8,20})$" title="Please enter a valid 10-digit Indian (+91) or international phone number" class="form-input-premium text-xs">
                <span class="text-[10px] text-[#3d6350] mt-0.5 block">Accepts Indian (+91) and international numbers</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1">Required Roles &amp; Technologies *</label>
              <input type="text" name="required_roles" required placeholder="e.g. 2 Senior Java Developers, 1 Plant Maintenance Head" class="form-input-premium text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Positions</label>
                <select name="number_of_positions" class="form-input-premium text-xs cursor-pointer">
                  <option value="1">1 Position</option>
                  <option value="2-5" selected>2–5 Positions</option>
                  <option value="6-15">6–15 Positions</option>
                  <option value="15+">15+ (Bulk Drive)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Hiring Location</label>
                <select name="location" class="form-input-premium text-xs cursor-pointer">
                  <option value="Hinjawadi">Hinjawadi</option>
                  <option value="Kharadi">Kharadi</option>
                  <option value="Chakan">Chakan MIDC</option>
                  <option value="Baner">Baner / Balewadi</option>
                  <option value="Pune Central">Pune General</option>
                  <option value="Mumbai">Mumbai</option>
                  <option value="Bengaluru">Bengaluru</option>
                  <option value="Hyderabad">Hyderabad</option>
                  <option value="Delhi NCR">Delhi NCR</option>
                  <option value="Chennai">Chennai</option>
                  <option value="Remote">Remote</option>
                  <option value="Other India" selected>Other / Multiple (India)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Hiring Urgency</label>
                <select name="urgency" class="form-input-premium text-xs cursor-pointer">
                  <option value="Immediate" selected>Immediate (&lt;15 days)</option>
                  <option value="30 Days">30 Days</option>
                  <option value="60 Days">60 Days</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1">Requirements &amp; CTC Budget (Optional)</label>
              <textarea name="notes" rows="3" placeholder="Provide CTC range, shift details, must-have skills, or specific project milestones..." class="form-input-premium text-xs"></textarea>
            </div>

            <!-- Anti-Spam, Honeypot & CAPTCHA Protection -->
            <?= $formProtection ?? '' ?>

            <div class="flex items-start gap-2 pt-1">
              <input type="checkbox" name="consent" id="employer-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
              <label for="employer-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">
                I agree to the <a href="<?= base_url('terms') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Terms of Engagement</a> and authorize TalentHub Consultant to contact me regarding this mandate under the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy (v1.0-DPDP)</a>.
              </label>
            </div>

            <div class="pt-2">
              <button type="submit" class="w-full btn-primary text-xs font-bold py-3.5 shadow-lg shadow-[#1D6A34]/25">
                <span>Submit Hiring Mandate</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </div>
</div>
