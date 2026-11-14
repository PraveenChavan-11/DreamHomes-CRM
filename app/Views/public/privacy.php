<?php
/**
 * TalentHub Consultant — Privacy Policy View
 */
$page = $page ?? [];
$pageData = !empty($page['content_json']) ? json_decode($page['content_json'], true) : [];
?>

<div class="bg-background min-h-screen pt-24 text-foreground">
  <!-- Hero Section -->
  <section class="relative overflow-hidden border-b border-border bg-[linear-gradient(180deg,#F9FBFA_0%,#EAF5EF_100%)] py-16 md:py-20 text-[#172B22]">
    <div class="page-width relative z-10 max-w-4xl text-center">
      <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-[#37CE97]/20 px-4 py-1.5 text-xs font-bold text-[#1D6A34]">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
        <span>Legal &amp; Data Protection Compliance</span>
      </div>
      <h1 class="th-h1 th-h1--ink">
        <?= !empty($page['title']) ? esc($page['title']) : 'Privacy Policy' ?>
      </h1>
      <p class="mx-auto mt-4 text-sm sm:text-base text-[#172B22]/80 leading-relaxed max-w-2xl">
        Transparent disclosures on how TalentHub Consultant collects, handles, stores, and safeguards personal candidate profiles and corporate mandate records in Pune, Maharashtra.
      </p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-[#172B22]/80">
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#1D6A34]/15 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-[#1D6A34]"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
          Compliant with India DPDP Act 2023
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#1D6A34]/15 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-[#1D6A34]"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path><path d="M8 16H3v5"></path></svg>
          Last Updated: <?= !empty($page['updated_at']) ? date('F d, Y', strtotime($page['updated_at'])) : 'February 20, 2026' ?>
        </span>
      </div>
    </div>
  </section>

  <!-- Key Assurance Cards -->
  <div class="page-width max-w-4xl -mt-6 relative z-20">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Zero Placement Fee Guarantee</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">TalentHub Consultant never charges candidates any fees for job placement, application processing, or interview scheduling.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Data Confidentiality</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">Candidate CVs and contact details are never shared with prospective employers without explicit candidate briefing and consent.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Zero Third-Party Selling</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">We never monetize, rent, or sell candidate or employer contact information to marketing networks or external data brokers.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="m16 11 2 2 4-4"></path><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Candidate Access Rights</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">You retain complete rights to request an update, profile review, or immediate erasure of your CV from our internal recruitment database.</p>
      </div>
    </div>
  </div>

  <!-- Main Legal Document Body -->
  <article class="page-width max-w-4xl py-14">
    <div class="rounded-3xl border border-border bg-white dark:bg-[#12281b] p-8 md:p-12 shadow-sm space-y-10 leading-relaxed text-[#172B22]/90 dark:text-white/90">
      <section class="space-y-4">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">1</span>
          Introduction &amp; Scope
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          Welcome to <strong>TalentHub Consultant</strong> ("we," "our," or "us"). We operate the recruitment consultancy website accessible at <a class="text-[#1D6A34] font-semibold underline underline-offset-4" href="/">https://talenthubconsultant.com</a>, with our registered office situated in <strong>Katraj, Pune – 411046, Maharashtra, India</strong>.
        </p>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          This Privacy Policy explains how we collect, process, maintain, and safeguard personal information obtained from candidates, job seekers, corporate employers, and website visitors. We are committed to safeguarding personal data in adherence to the <em>Digital Personal Data Protection Act, 2023 (DPDP Act)</em> and the <em>Information Technology Act, 2000</em> of India.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">2</span>
          Information We Collect
        </h2>
        <div class="space-y-3 text-sm md:text-base text-slate-700 dark:text-slate-300">
          <p>Depending on your interaction with TalentHub Consultant, we may collect the following categories of data:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Candidate Profile Information:</strong> Full name, telephone number, email address, residential locality, current employment designation, target job role, total professional experience, skillsets, and notice period submitted through our career forms or email correspondence.</li>
            <li><strong>Resume &amp; Curriculum Vitae (CV) Data:</strong> Educational credentials, academic institutions, previous employers, project summaries, technical certifications, portfolio links, and self-disclosed compensation benchmarks.</li>
            <li><strong>Employer Mandate Details:</strong> Corporate entity name, contact person name, designation, corporate email, hiring requirements, required job skills, location criteria, and compensation budgets.</li>
            <li><strong>Technical &amp; Usage Information:</strong> Anonymized web telemetry including device type, browser specification, IP address, page visits, and referral headers via Google Analytics (GA4) solely to optimize user experience and platform responsiveness.</li>
          </ul>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">3</span>
          Purposes of Data Processing
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          TalentHub Consultant processes candidate and client personal information strictly for legitimate recruitment and consultancy operations, including:
        </p>
        <ul class="list-disc pl-6 space-y-2 text-sm md:text-base text-slate-700 dark:text-slate-300">
          <li>Evaluating candidate compatibility with active or prospective corporate openings across Pune (Hinjawadi, Kharadi, Magarpatta, Chakan, and related business parks).</li>
          <li>Coordinating interview schedules, technical rounds, and compensation discussions with prospective hiring organizations upon candidate approval.</li>
          <li>Responding promptly to candidate career enquiries, employer staffing inquiries, and verification requests.</li>
          <li>Complying with statutory reporting, labor regulations, and tax requirements under Indian law.</li>
        </ul>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">4</span>
          Zero Candidate Placement Fee Commitment
        </h2>
        <div class="rounded-2xl bg-[#EAF5EF]/70 border border-[#1D6A34]/20 p-5 text-sm md:text-base text-[#172B22]">
          <p class="font-semibold mb-1">Our Universal Anti-Exploitation Policy:</p>
          <p>TalentHub Consultant is an employer-funded recruitment consultancy. We <strong>never</strong> charge job seekers any fee—whether for registration, resume screening, technical evaluations, interview scheduling, or placement facilitation. Any communication requesting financial compensation under the name of TalentHub Consultant is fraudulent and should be reported to us immediately.</p>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">5</span>
          Candidate Consent &amp; Profile Sharing
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          We uphold strict candidate confidentiality. Your CV or personally identifiable information is <strong>never presented or forwarded</strong> to any hiring organization without first contacting you to discuss the role description, organizational profile, location requirements, and compensation parameters.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">6</span>
          Data Retention &amp; Security Safeguards
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          We employ industry-standard administrative, electronic, and physical security measures to safeguard candidate dossiers against unauthorized access, disclosure, alteration, or unlawful destruction. Resume records are maintained within secure recruitment vaults only for as long as necessary to fulfill prospective hiring engagements or as mandated by statutory guidelines.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">7</span>
          Your Rights under India's DPDP Act 2023
        </h2>
        <div class="space-y-2 text-sm md:text-base text-slate-700 dark:text-slate-300">
          <p>Under the provisions of India's Digital Personal Data Protection Act, you are entitled to:</p>
          <ul class="list-disc pl-6 space-y-1.5">
            <li><strong>Right to Access:</strong> Request a summary of personal candidate data processed by TalentHub Consultant.</li>
            <li><strong>Right to Correction:</strong> Request updates or corrections to outdated employment history, certifications, or contact details.</li>
            <li><strong>Right to Erasure:</strong> Request the total deletion of your candidate profile from our recruitment roster at any time.</li>
            <li><strong>Right to Withdraw Consent:</strong> Revoke recruitment representation permissions with immediate prospective effect.</li>
          </ul>
          <p class="pt-2">To exercise any of these statutory rights, please send an email from your registered address to <a href="mailto:hr@talenthubconsultant.com" class="text-[#1D6A34] font-semibold underline">hr@talenthubconsultant.com</a> with the subject line <em>"Data Protection Request"</em>.</p>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">8</span>
          Third-Party Live Chat &amp; Analytics Processing
        </h2>
        <div class="space-y-3 text-sm md:text-base text-slate-700 dark:text-slate-300 leading-relaxed">
          <p>To deliver responsive assistance to candidates and employers, our website utilizes the official <strong>Tawk.to</strong> live communication service:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Live Chat Assistance (Tawk.to):</strong> When you interact with our live chat widget, standard operational telemetry (such as your session duration, browser type, and messages you submit) is processed securely to enable real-time recruitment consultation. We do not sell or monetize chat interaction data.</li>
            <li><strong>Web Telemetry (Google Analytics 4):</strong> Anonymized site metrics are monitored via Google Analytics (Measurement ID: <code>G-K6J0RWMLBS</code>) solely to ensure high-performance site operation, page load velocity, and navigation optimization without personal identification.</li>
          </ul>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">9</span>
          Grievance Officer &amp; Office Contact
        </h2>
        <div class="rounded-2xl border border-border bg-slate-50 dark:bg-white/5 p-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
          <p class="font-semibold text-base text-[#172B22] dark:text-white">TalentHub Consultant — Recruitment Grievance Desk</p>
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0 mt-0.5"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span>Katraj, Pune – 411046, Maharashtra, India</span>
          </div>
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
            <a href="mailto:hr@talenthubconsultant.com" class="text-[#1D6A34] font-semibold underline">hr@talenthubconsultant.com</a>
          </div>
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
            <span>+91 72761 46573</span>
          </div>
          <p class="text-xs text-slate-500 pt-2 border-t border-border">All data privacy requests and candidate verification inquiries are acknowledged within 48 business hours.</p>
        </div>
      </section>
    </div>
  </article>
</div>

