<?php
/**
 * TalentHub Consultant — Terms and Conditions View
 */
$page = $page ?? [];
?>

<div class="bg-background min-h-screen pt-24 text-foreground">
  <!-- Hero Section -->
  <section class="relative overflow-hidden border-b border-border bg-[linear-gradient(180deg,#F9FBFA_0%,#EAF5EF_100%)] py-16 md:py-20 text-[#172B22]">
    <div class="page-width relative z-10 max-w-4xl text-center">
      <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-[#37CE97]/20 px-4 py-1.5 text-xs font-bold text-[#1D6A34]">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>
        <span>Consultancy Service Agreement</span>
      </div>
      <h1 class="th-h1 th-h1--ink">
        <?= !empty($page['title']) ? esc($page['title']) : 'Terms &amp; Conditions' ?>
      </h1>
      <p class="mx-auto mt-4 text-sm sm:text-base text-[#172B22]/80 leading-relaxed max-w-2xl">
        Understand the rules, operational policies, employer commitments, and candidate protections that govern recruitment engagements with TalentHub Consultant.
      </p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-[#172B22]/80">
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#1D6A34]/15 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-[#1D6A34]"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
          Legally Binding Agreement
        </span>
        <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-[#1D6A34]/15 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-[#1D6A34]"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path><path d="M8 16H3v5"></path></svg>
          Last Updated: <?= !empty($page['updated_at']) ? date('F d, Y', strtotime($page['updated_at'])) : 'February 20, 2026' ?>
        </span>
      </div>
    </div>
  </section>

  <!-- Key Terms Summary Cards -->
  <div class="page-width max-w-4xl -mt-6 relative z-20">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path><circle cx="12" cy="8" r="6"></circle></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">100% Free for Candidates</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">Zero application fees, zero registration charges, zero commission deductions from candidate salaries at any stage of placement.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="M7 21h10"></path><path d="M12 3v18"></path><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Employer Mandate Terms</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">Commercial recruitment engagements operate on structured SLA terms with defined turnaround timelines and replacement guarantee clauses.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Accuracy of Representations</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">Candidates and hiring employers must provide truthful information regarding credentials, experience, compensation, and workplace conditions.</p>
      </div>

      <div class="rounded-2xl border border-[#1D6A34]/15 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="flex items-center gap-3 mb-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#EAF5EF] text-[#1D6A34]">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M10 12h4"></path><path d="M10 8h4"></path><path d="M14 21v-3a2 2 0 0 0-4 0v3"></path><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"></path><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"></path></svg>
          </div>
          <h3 class="font-bold text-sm text-[#172B22]">Pune Jurisdiction</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">All consultancy agreements and operations are governed by the laws of India and subject to the exclusive jurisdiction of courts in Pune, Maharashtra.</p>
      </div>
    </div>
  </div>

  <!-- Main Legal Document Body -->
  <article class="page-width max-w-4xl py-14">
    <div class="rounded-3xl border border-border bg-white dark:bg-[#12281b] p-8 md:p-12 shadow-sm space-y-10 leading-relaxed text-[#172B22]/90 dark:text-white/90">
      <section class="space-y-4">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">1</span>
          Agreement to Terms
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          By accessing or using the website of <strong>TalentHub Consultant</strong> ("the Website") available at <a class="text-[#1D6A34] font-semibold underline underline-offset-4" href="/">https://talenthubconsultant.com</a>, submitting a resume, or engaging our recruitment consultancy services in <strong>Katraj, Pune, Maharashtra</strong>, you acknowledge that you have read, understood, and agreed to be bound by these Terms and Conditions.
        </p>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          If you do not agree with any provision of these terms, please discontinue using the website and notify our team to remove any submitted resumes or mandate requests.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">2</span>
          Recruitment Intermediary Role
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          TalentHub Consultant functions as a professional human resource and recruitment intermediary connecting qualified professionals with independent corporate employers across Pune, Maharashtra, and India.
        </p>
        <div class="rounded-2xl bg-amber-50/80 border border-amber-200 p-5 text-sm md:text-base text-amber-900">
          <p class="font-semibold mb-1">Important Clarification on Final Hiring Decisions:</p>
          <p>TalentHub Consultant facilitates introductions, skills assessment, profile calibration, and interview coordination. However, the final employment decision, salary formulation, issuance of offer letters, employment contracts, and workplace terms remain the exclusive prerogative of the hiring company. TalentHub Consultant does not guarantee employment or placement to any candidate.</p>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">3</span>
          Candidate Provisions &amp; Zero Placement Fee Policy
        </h2>
        <div class="space-y-3 text-sm md:text-base text-slate-700 dark:text-slate-300">
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Zero Charges for Candidates:</strong> In strict compliance with fair recruitment practices, TalentHub Consultant never charges job applicants any fees for resume registration, interview scheduling, profile presentation, or successful placement.</li>
            <li><strong>Authenticity of Candidate Credentials:</strong> Candidates represent and warrant that all information submitted—including educational qualifications, prior employment tenures, technical proficiencies, portfolio code, and compensation histories—is accurate, current, and verifiable.</li>
            <li><strong>Direct Interview Participation:</strong> Candidates agree to notify our recruitment desk promptly if unable to attend scheduled interviews or if conflicting offers are received.</li>
          </ul>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">4</span>
          Corporate Employer Terms &amp; Placement Mandates
        </h2>
        <div class="space-y-3 text-sm md:text-base text-slate-700 dark:text-slate-300">
          <p>Organizations engaging TalentHub Consultant for permanent staffing, executive search, or contract hiring agree to standard commercial terms executed in specific recruitment Service Level Agreements (SLAs), including:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Mandate Specifications:</strong> Providing clear job descriptions, requisite qualifications, reporting hierarchy, and verified CTC compensation brackets.</li>
            <li><strong>Confidentiality of Candidate Dossiers:</strong> Candidate resumes provided by TalentHub Consultant are intended exclusively for the hiring organization's internal evaluation and shall not be shared with external third parties without prior authorization.</li>
            <li><strong>Placement Success Fees:</strong> Employer recruitment success fees are due and payable in accordance with the signed commercial mandate agreement following candidate joining.</li>
            <li><strong>Replacement Guarantee:</strong> In the event a placed candidate resigns or is terminated for just cause within the agreed guarantee period (typically 90 days), TalentHub Consultant will provide a replacement candidate at no additional consultancy charge.</li>
          </ul>
        </div>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">5</span>
          Intellectual Property Rights
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          All website content, brand assets, logos, design marks, graphics, articles, salary insights, and code on <strong>talenthubconsultant.com</strong> are the intellectual property of TalentHub Consultant. Unauthorized reproduction, modification, scraping, or commercial republication without written consent is strictly prohibited.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">6</span>
          Limitation of Liability
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          To the fullest extent permitted under applicable Indian law, TalentHub Consultant and its officers shall not be liable for any indirect, incidental, special, or consequential damages resulting from employment outcomes, candidate workplace performance, company organizational restructuring, or temporary website service interruptions.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">7</span>
          Governing Law &amp; Legal Jurisdiction
        </h2>
        <p class="text-sm md:text-base text-slate-700 dark:text-slate-300">
          These Terms and Conditions and any dispute or claim arising out of or in connection with our consultancy services shall be governed by and construed in accordance with the laws of <strong>India</strong>. Any legal dispute, arbitration, or proceeding shall be subject to the exclusive jurisdiction of the competent courts located in <strong>Pune, Maharashtra, India</strong>.
        </p>
      </section>

      <section class="space-y-4 border-t border-border pt-8">
        <h2 class="text-xl md:text-2xl font-bold text-[#172B22] dark:text-white flex items-center gap-2.5">
          <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#EAF5EF] text-xs font-bold text-[#1D6A34]">8</span>
          Contact &amp; Official Correspondence
        </h2>
        <div class="rounded-2xl border border-border bg-slate-50 dark:bg-white/5 p-6 space-y-3 text-sm text-slate-700 dark:text-slate-300">
          <p class="font-semibold text-base text-[#172B22] dark:text-white">TalentHub Consultant — Corporate &amp; Legal Desk</p>
          <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0 mt-0.5"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle></svg>
            <span>Katraj, Pune – 411046, Maharashtra, India</span>
          </div>
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect></svg>
            <a href="mailto:hr@talenthubconsultant.com" class="text-[#1D6A34] font-semibold underline">hr@talenthubconsultant.com</a>
          </div>
          <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#1D6A34] shrink-0"><path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path></svg>
            <span>+91 72761 46573</span>
          </div>
        </div>
      </section>
    </div>
  </article>
</div>

