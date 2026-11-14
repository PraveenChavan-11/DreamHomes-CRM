<?php
/**
 * TalentHub Consultant — Contact Us Desk
 * Enterprise Inbound Message Center & Pune Headquarters Information
 */
?>

<section class="th-hero th-hero--page">
  <div class="page-width" style="max-width:56rem">
    <span class="th-eyebrow" data-hero>Connect with Us</span>
    <h1 class="th-h1" data-hero style="--d:.1s">Contact TalentHub Consultant</h1>
    <p class="th-lead" data-hero style="--d:.2s">Speak directly with our recruitment and technology consulting team. We are here to answer hiring mandates and candidate career inquiries.</p>
  </div>
</section>
<div class="pt-12 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">


    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start max-w-5xl mx-auto">

      <!-- LEFT: OFFICE & CONTACT DETAILS (5 COLS) -->
      <div class="lg:col-span-5 space-y-6">
        <div class="card-premium p-8 bg-white shadow-sm space-y-6">
          <h2 class="text-lg font-bold text-[#172B22]">Operations Base — Pune</h2>

          <div class="space-y-4 text-xs text-[#3d6350]">
            <div class="flex items-start gap-3.5">
              <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              </div>
              <div>
                <p class="font-bold text-[#172B22]">Headquarters Address</p>
                <p class="mt-0.5 leading-relaxed"><?= esc($settings['office_address'] ?? 'Katraj, Pune — 411046, Maharashtra, India') ?></p>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
              </div>
              <div>
                <p class="font-bold text-[#172B22]">Direct Phone Number</p>
                <p class="mt-0.5">
                  <a href="tel:<?= preg_replace('/[^0-9+]/', '', $settings['contact_phone'] ?? '+917276146573') ?>" class="text-[#1D6A34] font-bold hover:underline">
                    <?= esc($settings['contact_phone'] ?? '+91 72761 46573') ?>
                  </a>
                </p>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <p class="font-bold text-[#172B22]">Customer &amp; Corporate Enquiries</p>
                <p class="mt-0.5">
                  <a href="mailto:<?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold hover:underline">
                    <?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>
                  </a>
                </p>
                <span class="text-[10px] text-[#3d6350]">For client mandates and business enquiries</span>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <p class="font-bold text-[#172B22]">Candidate Careers &amp; Resumes</p>
                <p class="mt-0.5">
                  <a href="mailto:<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold hover:underline">
                    <?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>
                  </a>
                </p>
                <span class="text-[10px] text-[#3d6350]">For direct resume submissions and job applications</span>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <div>
                <p class="font-bold text-[#172B22]">Consulting Desk Hours</p>
                <p class="mt-0.5"><?= esc($settings['working_hours'] ?? 'Monday – Saturday: 9:00 AM – 7:00 PM IST') ?></p>
              </div>
            </div>
          </div>

          <div class="pt-2 space-y-2">
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '917276146573') ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn-outline w-full justify-center text-xs font-bold gap-2">
              <svg class="w-4 h-4 text-[#1D6A34]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
              <span>Instant Chat via WhatsApp</span>
            </a>
            <?php if (!empty($settings['whatsapp_channel_url'])): ?>
            <a href="<?= esc($settings['whatsapp_channel_url']) ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn-whatsapp-channel w-full justify-center text-xs font-bold gap-2 py-3.5 px-4 rounded-xl shadow-md transition"
               style="background-color: #25D366 !important; color: #FFFFFF !important; border: 1.5px solid #20BA59 !important;">
              <svg class="w-4 h-4" style="color: #FFFFFF !important; fill: currentColor !important;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.82.49 3.53 1.34 5L2 22l5.18-1.32c1.42.79 3.06 1.24 4.82 1.24 5.52 0 10-4.48 10-10S17.52 2 12 2zm.02 18c-1.57 0-3.04-.44-4.3-1.21l-.31-.19-3.19.82.85-3.08-.2-.33A7.95 7.95 0 014 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-7.98 8z"/></svg>
              <span style="color: #FFFFFF !important; font-weight: 700 !important;">Join Our WhatsApp Channel – Get Latest Job Updates</span>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- RIGHT: INQUIRY FORM (7 COLS) -->
      <div class="lg:col-span-7">
        <div class="card-premium p-8 sm:p-10 bg-white shadow-xl shadow-[#1D6A34]/5 space-y-6">
          <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Send an Inquiry</span>
            <h2 class="text-2xl font-bold text-[#172B22] mt-1">Direct Message Desk</h2>
            <p class="text-xs text-[#3d6350] mt-1">We respond to corporate and candidate inquiries within 24 business hours.</p>
          </div>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="p-4 rounded-2xl bg-[#072412] text-white text-xs font-semibold flex items-center gap-2">
              <svg class="w-4 h-4 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span><?= esc(session()->getFlashdata('success')) ?></span>
            </div>
          <?php endif; ?>

          <form action="<?= base_url('contact/submit') ?>" method="POST" data-ajax="true" toolname="submit_contact_inquiry" tooldescription="Send direct inquiry message to the TalentHub team" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">First Name *</label>
                <input type="text" name="first_name" required placeholder="e.g. Priya" class="form-input-premium text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Last Name</label>
                <input type="text" name="last_name" placeholder="e.g. Kulkarni" class="form-input-premium text-xs">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Email Address *</label>
                <input type="email" name="email" required placeholder="priya@example.com" class="form-input-premium text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Phone Number *</label>
                <input type="tel" name="phone" required placeholder="+91 98810 54321" pattern="^(\+?[0-9\s\-\(\)]{8,20})$" title="Please enter a valid 10-digit Indian (+91) or international phone number" class="form-input-premium text-xs">
                <span class="text-[10px] text-[#3d6350] mt-0.5 block">Accepts Indian (+91) and international numbers</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Company / Organization (Optional)</label>
                <input type="text" name="company" placeholder="e.g. Tech Solutions Pvt Ltd" class="form-input-premium text-xs">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Inquiry Purpose</label>
                <select name="subject" class="form-input-premium text-xs cursor-pointer">
                  <?php $subjPre = (string) service('request')->getGet('subject'); $subjOpts = ['Hiring Talent' => 'Hiring Talent / Corporate Mandate', 'IT Services & Software Development' => 'IT Services & Software Development', 'Executive Search' => 'Executive Search', 'Candidate Representation' => 'Candidate Representation / Career Guidance']; ?>
                  <?php foreach ($subjOpts as $v => $l): ?>
                  <option value="<?= esc($v) ?>" <?= ($subjPre === $v || ($subjPre === '' && $v === 'Hiring Talent')) ? 'selected' : '' ?>><?= esc($l) ?></option>
                  <?php endforeach; ?>
                  <option value="Billing / Administration">General Inquiry / Partnership</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1">Your Message / Requirement <span class="text-xs text-slate-400 font-normal">(Optional)</span></label>
              <textarea name="message" rows="4" placeholder="Describe your requirement or inquiry (optional)..." class="form-input-premium text-xs"></textarea>
            </div>

            <!-- Anti-Spam, Honeypot & CAPTCHA Protection -->
            <?= $formProtection ?? '' ?>

            <div class="flex items-start gap-2 pt-1">
              <input type="checkbox" name="consent" id="contact-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
              <label for="contact-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">
                I affirmatively consent to TalentHub Consultant contacting me regarding this inquiry under the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy (v1.0-DPDP)</a>.
              </label>
            </div>

            <div class="pt-2">
              <button type="submit" class="w-full btn-primary text-xs font-bold py-3.5 shadow-lg shadow-[#1D6A34]/25">
                <span>Send Message to TalentHub</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>

  </div>
</div>
