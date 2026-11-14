<div class="max-w-5xl mx-auto space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>Platform Operation Controls</span>
      </div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Website Configuration Control Centre</h1>
      <p class="text-xs text-slate-500 mt-1">Manage brand identity, contact desk numbers, social presence, analytics telemetry, and maintenance locks</p>
    </div>
  </div>

  <form action="<?= base_url('admin/settings/update') ?>" method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <!-- Section 1: Consultancy Identity -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-5">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-extrabold text-slate-900">Consultancy &amp; Brand Identity</h2>
          <p class="text-xs text-slate-500">Public name, branding tagline, and business operating hours</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Company / Website Name *</label>
          <input type="text" name="site_name" required value="<?= esc($settings['site_name'] ?? ($settings['website_name'] ?? 'TalentHub Consultant')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Agency Tagline</label>
          <input type="text" name="site_tagline" value="<?= esc($settings['site_tagline'] ?? ($settings['website_tagline'] ?? 'Pune Leading Job Consultancy & Recruitment Specialists')) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-slate-700 mb-1">Working &amp; Consultation Hours</label>
          <input type="text" name="working_hours" value="<?= esc($settings['working_hours'] ?? 'Monday - Saturday: 9:30 AM - 7:00 PM IST') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
      </div>
    </div>

    <!-- Section 2: Email Routing & Office Headquarters -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-5">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-extrabold text-slate-900">Email Routing &amp; Office Headquarters</h2>
          <p class="text-xs text-slate-500">Configure separate destination mailboxes for candidate resumes vs customer enquiries, hotline numbers, and headquarters address</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 rounded-2xl bg-slate-50 border border-slate-200/60">
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Customer &amp; Business Enquiries Email (Contact Us / Hiring Mandates) *
          </label>
          <input type="email" name="contact_email" required value="<?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[11px] text-slate-500 mt-1">Customers, companies, and employers will reach out here. Receives Contact Us submissions and corporate hiring mandates.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Candidate Applications &amp; Resumes Email (Careers / Job Applications) *
          </label>
          <input type="email" name="hiring_email" required value="<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[11px] text-slate-500 mt-1">Candidates directly send their CVs and job applications here. Receives job application alerts and talent pool submissions.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-1">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Recruitment Hotline / Phone Number *</label>
          <input type="text" name="contact_phone" required value="<?= esc($settings['contact_phone'] ?? '+91 72761 46573') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp Direct Chat Number</label>
          <input type="text" name="whatsapp_number" value="<?= esc($settings['whatsapp_number'] ?? '+91 72761 46573') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-slate-700 mb-1">Registered Office Address *</label>
          <input type="text" name="office_address" required value="<?= esc($settings['office_address'] ?? 'Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
      </div>
    </div>

    <!-- Section 3: Social & Professional Channels -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-5">
      <div class="border-b border-slate-100 pb-3">
        <h2 class="text-sm font-extrabold text-slate-900">Social Channels &amp; Network Links</h2>
        <p class="text-xs text-slate-500">Completely dynamic: social icons only appear on the public website if their URL is provided. Empty links are automatically hidden.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="md:col-span-2 p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200/60">
          <label class="block text-xs font-bold text-emerald-950 mb-1">Official WhatsApp Channel Link</label>
          <input type="url" name="whatsapp_channel_url" value="<?= esc($settings['whatsapp_channel_url'] ?? 'https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R') ?>" placeholder="https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R" class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-white text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[11px] text-emerald-800 mt-1">When configured, candidate buttons reading <strong>"Join Our WhatsApp Channel – Get Latest Job Updates"</strong> appear on Careers, Jobs, and Footer.</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">LinkedIn Company Page URL</label>
          <input type="url" name="linkedin_url" value="<?= esc($settings['linkedin_url'] ?? '') ?>" placeholder="https://www.linkedin.com/company/your-company" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[10px] text-slate-400 mt-1">Displays LinkedIn icon in footer and Schema.org when populated.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Instagram Business Page URL</label>
          <input type="url" name="instagram_url" value="<?= esc($settings['instagram_url'] ?? '') ?>" placeholder="https://www.instagram.com/your-business" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[10px] text-slate-400 mt-1">Displays Instagram icon in footer and Schema.org when populated.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Facebook Page URL</label>
          <input type="url" name="facebook_url" value="<?= esc($settings['facebook_url'] ?? '') ?>" placeholder="https://www.facebook.com/your-page" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[10px] text-slate-400 mt-1">Displays Facebook icon when populated.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Twitter / X URL</label>
          <input type="url" name="twitter_url" value="<?= esc($settings['twitter_url'] ?? '') ?>" placeholder="https://x.com/your-handle" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[10px] text-slate-400 mt-1">Displays X (Twitter) icon when populated.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">YouTube Channel URL</label>
          <input type="url" name="youtube_url" value="<?= esc($settings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/@your-channel" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[10px] text-slate-400 mt-1">Displays YouTube icon when populated.</p>
        </div>
      </div>
    </div>

    <!-- Section 4: Analytics & Live Telemetry -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-6">
      <div class="border-b border-slate-100 pb-3">
        <h2 class="text-sm font-extrabold text-slate-900">Third-Party Telemetry &amp; Live Chat Integrations</h2>
        <p class="text-xs text-slate-500">Google Analytics 4 measurement ID and Tawk.to instant recruitment visitor desk</p>
      </div>

      <!-- Google Analytics 4 -->
      <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">GA</div>
            <div>
              <h3 class="text-xs font-bold text-slate-900">Google Analytics 4 (GA4)</h3>
              <p class="text-[11px] text-slate-500">Captures user traffic, job views, resume uploads, and corporate lead submissions</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="ga_enabled" value="1" <?= (!empty($settings['ga_enabled']) && $settings['ga_enabled'] == '1') ? 'checked' : '' ?> class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
          </label>
        </div>
        <div class="max-w-sm">
          <label class="block text-xs font-semibold text-slate-700 mb-1">GA4 Measurement ID</label>
          <input type="text" name="ga_measurement_id" value="<?= esc($settings['ga_measurement_id'] ?? 'G-K6J0RWMLBS') ?>" placeholder="G-XXXXXXXXXX" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
      </div>

      <!-- Tawk.to Live Chat -->
      <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-800 flex items-center justify-center font-bold text-xs">TK</div>
            <div>
              <h3 class="text-xs font-bold text-slate-900">Tawk.to Visitor Chat Desk</h3>
              <p class="text-[11px] text-slate-500">Floating widget connecting job seekers and hiring managers with live advisors</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="tawk_enabled" value="1" <?= (!empty($settings['tawk_enabled']) && $settings['tawk_enabled'] == '1') ? 'checked' : '' ?> class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
          </label>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tawk.to Property ID</label>
            <input type="text" name="tawk_property_id" value="<?= esc($settings['tawk_property_id'] ?? '6ad65a04c40a0134c95ce3b6') ?>" placeholder="6ad65a04c40a0134c95ce3b6" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tawk.to Widget ID</label>
            <input type="text" name="tawk_widget_id" value="<?= esc($settings['tawk_widget_id'] ?? '1k5akv9ak') ?>" placeholder="1k5akv9ak" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>
        </div>
      </div>

    </div>

    <!-- Section 5: Feature Toggles & Platform Access Controls -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-6">
      <div class="border-b border-slate-100 pb-3">
        <h2 class="text-sm font-extrabold text-slate-900">Platform Availability &amp; Emergency Mode</h2>
        <p class="text-xs text-slate-500">Fine-grained operational gating for platform maintenance and form submissions</p>
      </div>

      <!-- Maintenance Mode Switch -->
      <div class="p-5 rounded-2xl bg-amber-50/50 border border-amber-200/60 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-xs font-extrabold text-amber-900">Scheduled Maintenance Mode</h3>
            <p class="text-[11px] text-amber-700">When enabled, public visitors are served the branded 503 maintenance page. Admin portal remains fully accessible.</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="maintenance_mode" value="1" <?= (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] == '1') ? 'checked' : '' ?> class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
          </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
          <div>
            <label class="block text-xs font-bold text-amber-900 mb-1">Maintenance Headline</label>
            <input type="text" name="maintenance_headline" value="<?= esc($settings['maintenance_headline'] ?? 'We Are Improving Your Experience') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-amber-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-amber-900 mb-1">Maintenance Notice Message</label>
            <input type="text" name="maintenance_message" value="<?= esc($settings['maintenance_message'] ?? 'TalentHub Consultant is currently undergoing scheduled platform upgrades to serve you better. Our recruitment advisors remain reachable via phone.') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-amber-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-amber-500">
          </div>
        </div>
      </div>

      <!-- Module Toggles -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-2">
        <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
          <input type="checkbox" name="jobs_enabled" value="1" <?= (!empty($settings['jobs_enabled']) && $settings['jobs_enabled'] == '1') ? 'checked' : '' ?> class="w-4 h-4 text-brand-primary rounded border-slate-300 focus:ring-brand-primary">
          <div>
            <div class="text-xs font-bold text-slate-800">Job Board Active</div>
            <div class="text-[10px] text-slate-400">Public listings searchable</div>
          </div>
        </label>

        <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
          <input type="checkbox" name="applications_enabled" value="1" <?= (!empty($settings['applications_enabled']) && $settings['applications_enabled'] == '1') ? 'checked' : '' ?> class="w-4 h-4 text-brand-primary rounded border-slate-300 focus:ring-brand-primary">
          <div>
            <div class="text-xs font-bold text-slate-800">Applications Open</div>
            <div class="text-[10px] text-slate-400">Direct vacancy applications</div>
          </div>
        </label>

        <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
          <input type="checkbox" name="employer_enquiry_enabled" value="1" <?= (!empty($settings['employer_enquiry_enabled']) && $settings['employer_enquiry_enabled'] == '1') ? 'checked' : '' ?> class="w-4 h-4 text-brand-primary rounded border-slate-300 focus:ring-brand-primary">
          <div>
            <div class="text-xs font-bold text-slate-800">Employer Mandates Open</div>
            <div class="text-[10px] text-slate-400">Corporate hiring submission desk</div>
          </div>
        </label>
      </div>

    </div>

    <!-- Section 6: Administrative Security & Two-Step Verification -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-5">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-extrabold text-slate-900">Administrative Security &amp; Two-Step Verification</h2>
          <p class="text-xs text-slate-500">Enhanced login protection for administrators and recruitment consultants</p>
        </div>
      </div>

      <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
              <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-bold text-slate-900">Two-Step Email OTP Login Verification</h3>
              <p class="text-[11px] text-slate-500">Require a 6-digit one-time passcode dispatched to the administrator's work email upon login</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="admin_email_otp_enabled" value="1" <?= (!empty($settings['admin_email_otp_enabled']) && $settings['admin_email_otp_enabled'] == '1') ? 'checked' : '' ?> class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
          </label>
        </div>
        <div class="text-[11px] text-slate-600 bg-white p-3.5 rounded-xl border border-slate-200/80 leading-relaxed flex items-start gap-2.5">
          <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div>
            <strong>Default: OFF (Disabled).</strong> When switched ON, any staff login attempt (after logout or session timeout) will dispatch a secure 6-digit numeric verification token with a 10-minute expiry to their registered email before granting dashboard access. No external authenticator app needed.
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-4 pt-4">
      <button type="submit" class="px-8 py-3 rounded-2xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-extrabold shadow-lg shadow-brand-primary/20 transition">
        Save &amp; Apply All Settings
      </button>
    </div>

  </form>

</div>
