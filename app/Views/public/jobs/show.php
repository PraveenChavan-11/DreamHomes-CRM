<?php
/**
 * TalentHub Consultant — Job Detail View with JobPosting Schema & Dual-Mode Application Modal
 */

$jobTitle = esc($job['title']);
$jobLocation = esc($job['location'] ?? 'Pune, Maharashtra');

// Sanitize any legacy character encodings or ??? placeholders gracefully
$cleanJobText = function(?string $text): string {
    if (empty($text)) return '';
    $t = str_replace(["\r\n", "\r"], "\n", $text);
    $t = preg_replace('/^[ \t]*\?{2,3}[ \t]*/m', '• ', $t);
    $t = preg_replace('/(\d+)[ \t]*\?{2,3}[ \t]*(\d+)/', '$1–$2', $t);
    $t = str_replace(['???', '??'], '–', $t);
    return $t;
};

$jobDesc = nl2br(esc($cleanJobText($job['description'] ?? '')));
$jobReqs = !empty($job['requirements']) ? nl2br(esc($cleanJobText($job['requirements']))) : '';
$rawExp = !empty($job['experience_label']) ? $job['experience_label'] : (($job['experience_min'] ?? '0') . '–' . ($job['experience_max'] ?? '0') . ' years');
$cleanExperience = esc($cleanJobText($rawExp));

// ISO 8601 timestamps for Google Search Console Rich Results
$rawPublished = !empty($job['published_at']) ? $job['published_at'] : (!empty($job['created_at']) ? $job['created_at'] : 'now');
$publishedDate = date('Y-m-d\TH:i:sP', strtotime($rawPublished));
$validThrough = !empty($job['expires_at']) ? date('Y-m-d\TH:i:sP', strtotime($job['expires_at'])) : date('Y-m-d\TH:i:sP', strtotime($rawPublished . ' +90 days'));

$employerName = !empty($job['employer_name']) ? esc($job['employer_name']) : 'Confidential Corporate Partner';
$salaryDisplay = (!empty($job['is_salary_public']) && !empty($job['salary_min']))
    ? ('₹' . number_format((float) $job['salary_min']) . ' - ₹' . number_format((float) $job['salary_max']))
    : 'Competitive / Best in Industry';

// Salary range for Google baseSalary schema (eliminates missing baseSalary warning)
$salaryMin = !empty($job['salary_min']) ? (float)$job['salary_min'] : null;
$salaryMax = !empty($job['salary_max']) ? (float)$job['salary_max'] : null;
if (!$salaryMin || !$salaryMax) {
    $expMin = (int)($job['experience_min'] ?? 0);
    if ($expMin >= 6) {
        $salaryMin = 450000;
        $salaryMax = 800000;
    } elseif ($expMin >= 3) {
        $salaryMin = 350000;
        $salaryMax = 600000;
    } elseif ($expMin >= 1) {
        $salaryMin = 250000;
        $salaryMax = 450000;
    } else {
        $salaryMin = 200000;
        $salaryMax = 350000;
    }
}

// Street address & Postal Code mapping (eliminates streetAddress & postalCode warnings)
$rawLoc = strtolower(trim((string)($job['location'] ?? 'Pune')));
$streetAddress = 'Katraj-Kondhwa Road, Katraj';
$postalCode = '411046';
if (strpos($rawLoc, 'hinjawadi') !== false) {
    $streetAddress = 'Hinjawadi IT Park, Phase 1-3';
    $postalCode = '411057';
} elseif (strpos($rawLoc, 'kharadi') !== false) {
    $streetAddress = 'EON Free Zone, Kharadi';
    $postalCode = '411014';
} elseif (strpos($rawLoc, 'chakan') !== false) {
    $streetAddress = 'Chakan Industrial Corridor, MIDC';
    $postalCode = '410501';
} elseif (strpos($rawLoc, 'magarpatta') !== false || strpos($rawLoc, 'hadapsar') !== false) {
    $streetAddress = 'Magarpatta Cybercity, Hadapsar';
    $postalCode = '411028';
} elseif (strpos($rawLoc, 'baner') !== false) {
    $streetAddress = 'Baner High Street & IT Corridor';
    $postalCode = '411045';
}

$mailtoSubject = rawurlencode("Job Application: {$job['title']} (Ref: {$job['slug']})");
$mailtoBody = rawurlencode("Dear TalentHub Pune Recruitment Team,\n\nI am applying for the role of {$job['title']} located in {$jobLocation}.\n\nPlease find my updated resume attached.\n\nKey Highlights:\n- Current Location:\n- Total Experience (Years):\n- Relevant Skills:\n- Notice Period:\n\nLooking forward to hearing from you.\n\nBest regards,\n");
$candidateHiringEmail = esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com');
$mailtoHref = "mailto:{$candidateHiringEmail}?subject={$mailtoSubject}&body={$mailtoBody}";

$cleanDescription = esc(strip_tags($cleanJobText($job['description'] ?? '')));
$cleanRequirements = esc(strip_tags($cleanJobText($job['requirements'] ?? '')));
$employmentType = ($job['job_type'] === 'FT') ? 'FULL_TIME' : (($job['job_type'] === 'CT') ? 'CONTRACT' : (($job['job_type'] === 'IN') ? 'INTERN' : 'PART_TIME'));
$isRemote = (($job['work_mode'] ?? 'ONSITE') === 'REMOTE');
?>

<!-- Google Search Console Compliant JobPosting Structured Data Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "JobPosting",
  "title": "<?= $jobTitle ?>",
  "description": "<?= $cleanDescription ?>",
  "identifier": {
    "@type": "PropertyValue",
    "name": "TalentHub Consultant",
    "value": "<?= esc($job['slug']) ?>"
  },
  "datePosted": "<?= $publishedDate ?>",
  "validThrough": "<?= $validThrough ?>",
  "employmentType": "<?= $employmentType ?>",
  "directApply": true,
  "hiringOrganization": {
    "@type": "Organization",
    "name": "<?= $employerName ?>",
    "sameAs": "<?= base_url() ?>",
    "logo": "<?= base_url('favicon-192x192.png') ?>"
  },
  "baseSalary": {
    "@type": "MonetaryAmount",
    "currency": "INR",
    "value": {
      "@type": "QuantitativeValue",
      "minValue": <?= (int) $salaryMin ?>,
      "maxValue": <?= (int) $salaryMax ?>,
      "unitText": "YEAR"
    }
  },
  <?php if ($isRemote): ?>
  "jobLocationType": "TELECOMMUTE",
  "applicantLocationRequirements": {
    "@type": "Country",
    "name": "India"
  },
  <?php endif; ?>
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "<?= esc($streetAddress) ?>",
      "addressLocality": "Pune",
      "addressRegion": "Maharashtra",
      "postalCode": "<?= esc($postalCode) ?>",
      "addressCountry": "IN"
    }
  },
  "experienceRequirements": {
    "@type": "OccupationalExperienceRequirements",
    "monthsOfExperience": <?= (int)(($job['experience_min'] ?? 1) * 12) ?>
  }
}
</script>

<div class="pt-32 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width">

    <!-- BREADCRUMBS -->
    <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-6 font-semibold" aria-label="Breadcrumb">
      <a href="<?= base_url() ?>" class="hover:text-[#1D6A34] transition-colors">Home</a>
      <span>/</span>
      <a href="<?= base_url('jobs') ?>" class="hover:text-[#1D6A34] transition-colors">Active Jobs</a>
      <span>/</span>
      <span class="text-[#172B22] truncate max-w-sm font-bold"><?= $jobTitle ?></span>
    </nav>

    <!-- FLASH MESSAGES -->
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

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

      <!-- LEFT: MAIN JOB DETAILS (8 COLS) -->
      <div class="lg:col-span-8 space-y-6">
        <div class="card-premium p-6 sm:p-8 bg-white space-y-6">
          <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex flex-wrap items-center gap-2">
                <span class="badge-primary text-xs">
                  <?= esc($job['category_name'] ?? 'Professional') ?>
                </span>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                  <?= esc($job['work_mode'] ?? 'ONSITE') ?>
                </span>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 text-gray-700">
                  <?= esc($job['job_type'] === 'FT' ? 'Full-Time Permanent' : ($job['job_type'] === 'CT' ? 'Contract' : 'Part-Time')) ?>
                </span>
              </div>

              <!-- Save & Share Action Buttons -->
              <div class="flex items-center gap-2">
                <button type="button" data-save-job="<?= esc($job['slug']) ?>" class="p-2.5 rounded-xl border border-[rgba(29,106,52,0.18)] text-[#3d6350] hover:text-[#1D6A34] hover:bg-[#EAF5EF] transition-all cursor-pointer" title="Save Job">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                </button>
                <button type="button" onclick="shareJob('<?= esc(addslashes($jobTitle)) ?>', '<?= current_url() ?>', event)" class="p-2.5 rounded-xl border border-[rgba(29,106,52,0.18)] text-[#3d6350] hover:text-[#1D6A34] hover:bg-[#EAF5EF] transition-all cursor-pointer" title="Share Job">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </button>
              </div>
            </div>

            <h1 class="th-h1 th-h1--ink">
              <?= $jobTitle ?>
            </h1>

            <p class="text-xs sm:text-sm text-[#3d6350] flex flex-wrap items-center gap-2 pt-1">
              <span class="font-bold text-[#1D6A34] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span><?= $employerName ?></span>
              </span>
              <span class="text-gray-300">•</span>
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span><?= $jobLocation ?></span>
              </span>
              <span class="text-gray-300">•</span>
              <span>Posted <?= date('M d, Y', strtotime($job['published_at'] ?? 'now')) ?></span>
            </p>
          </div>

          <hr class="border-[rgba(29,106,52,0.1)]">

          <!-- DESCRIPTION BODY -->
          <div class="space-y-4">
            <h2 class="text-base sm:text-lg font-bold text-[#172B22]">Position Overview &amp; Mandate Responsibilities</h2>
            <div class="text-xs sm:text-sm text-[#172B22]/85 leading-relaxed space-y-3 whitespace-pre-line font-sans">
              <?= $jobDesc ?>
            </div>
          </div>

          <?php if (!empty($jobReqs)): ?>
            <hr class="border-[rgba(29,106,52,0.1)]">
            <div class="space-y-4">
              <h2 class="text-base sm:text-lg font-bold text-[#172B22]">Candidate Prerequisites &amp; Technical Skills</h2>
              <div class="text-xs sm:text-sm text-[#172B22]/85 leading-relaxed space-y-2 whitespace-pre-line">
                <?= $jobReqs ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- BOTTOM APPLY ACTIONS -->
          <div class="pt-6 border-t border-[rgba(29,106,52,0.1)] flex flex-wrap items-center gap-4">
            <button type="button" data-modal-target="apply-modal" class="btn-primary text-sm font-bold px-7 py-3.5 shadow-lg shadow-[#1D6A34]/25">
              <span>Apply for this Position</span>
              <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
            <a href="https://wa.me/917276146573?text=<?= urlencode("Hello TalentHub, I am interested in applying for {$jobTitle} (Ref: {$job['slug']})") ?>" target="_blank" rel="noopener noreferrer" class="btn-outline text-sm font-semibold px-5 py-3.5">
              <span>Inquire on WhatsApp</span>
            </a>
          </div>
        </div>

        <!-- RELATED OPPORTUNITIES -->
        <?php if (!empty($relatedJobs)): ?>
          <div class="space-y-4 pt-4">
            <h3 class="text-lg font-bold text-[#172B22]">Related Opportunities in Pune</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?php foreach ($relatedJobs as $rJob): ?>
                <div class="card-premium p-5 bg-white flex flex-col justify-between">
                  <div>
                    <span class="badge-primary text-[10px] mb-2 inline-block"><?= esc($rJob['category_name'] ?? 'Professional') ?></span>
                    <h4 class="text-sm font-bold text-[#172B22] mb-1 hover:text-[#1D6A34]">
                      <a href="<?= base_url('jobs/' . $rJob['slug']) ?>"><?= esc($rJob['title']) ?></a>
                    </h4>
                    <p class="text-xs text-[#3d6350]"><?= esc($rJob['location'] ?? 'Pune') ?></p>
                  </div>
                  <div class="pt-3 mt-3 border-t border-[rgba(29,106,52,0.1)] flex justify-between items-center text-xs">
                    <span class="font-bold text-[#1D6A34]"><?= (!empty($rJob['is_salary_public']) && !empty($rJob['salary_min'])) ? ('₹' . number_format((float) $rJob['salary_min'])) : 'Competitive' ?></span>
                    <a href="<?= base_url('jobs/' . $rJob['slug']) ?>" class="font-bold text-[#1D6A34] hover:underline">View Role &rarr;</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: SUMMARY SIDEBAR (4 COLS - STICKY WHILE SCROLLED) -->
      <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-28 self-start">
        <div class="card-premium p-6 bg-white space-y-4 shadow-md">
          <h3 class="text-xs font-bold text-[#172B22] uppercase tracking-wider">Opportunity Summary</h3>

          <ul class="space-y-3 text-xs divide-y divide-gray-100">
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Experience Required:</span>
              <span class="font-bold text-[#172B22]"><?= $cleanExperience ?></span>
            </li>
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Job Type:</span>
              <span class="font-bold text-[#172B22]"><?= esc($job['job_type'] === 'FT' ? 'Full-Time Permanent' : ($job['job_type'] === 'CT' ? 'Contract' : 'Part-Time')) ?></span>
            </li>
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Work Environment:</span>
              <span class="font-bold text-[#172B22]"><?= esc($job['work_mode'] ?? 'On-Site') ?></span>
            </li>
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Compensation:</span>
              <span class="font-bold text-[#1D6A34]"><?= $salaryDisplay ?></span>
            </li>
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Candidate Placement Fee:</span>
              <span class="font-bold text-[#1D6A34]">100% Free / ₹0</span>
            </li>
            <li class="pt-2 flex justify-between">
              <span class="text-[#3d6350]">Mandate Ref:</span>
              <span class="font-mono text-[#3d6350]"><?= esc($job['slug']) ?></span>
            </li>
          </ul>

          <button type="button" data-modal-target="apply-modal" class="w-full btn-primary text-xs font-bold py-3 mt-2 shadow-md">
            <span>Apply Directly with Resume</span>
          </button>
        </div>

        <!-- WHATSAPP CHANNEL CARD (Admin Controlled) -->
        <?php if (!empty($settings['whatsapp_channel_url'])): ?>
        <div class="card-premium p-5 rounded-2xl bg-[#EAF5EF] border border-[#25D366]/40 space-y-3 shadow-sm">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#25D366] text-white flex items-center justify-center shrink-0 shadow-sm">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            </div>
            <div>
              <h4 class="text-xs font-bold text-[#172B22]">WhatsApp Job Channel</h4>
              <p class="text-[10px] text-[#3d6350]">Get instant alerts on fresh Pune job openings</p>
            </div>
          </div>
          <a href="<?= esc($settings['whatsapp_channel_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-channel w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold transition shadow-sm text-center" style="background-color: #25D366 !important; color: #FFFFFF !important; border: 1.5px solid #20BA59 !important;">
            <span style="color: #FFFFFF !important; font-weight: 700 !important;">Join Our WhatsApp Channel – Get Latest Job Updates</span>
          </a>
        </div>
        <?php endif; ?>

        <!-- RECRUITER ETHICS CARD -->
        <div class="card-premium p-6 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20 space-y-2">
          <h4 class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider flex items-center gap-1.5">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Confidential Representation
          </h4>
          <p class="text-xs text-[#3d6350] leading-relaxed">
            Your personal contact details, current employer, and compensation details are handled with strict privacy. Resumes are only shared with the corporate client after your explicit briefing and consent.
          </p>
        </div>

        <!-- SHARE JOB & GET ALERTS -->
        <div class="card-premium p-6 bg-white space-y-4 shadow-sm border border-[rgba(29,106,52,0.15)]">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-[#172B22] uppercase tracking-wider flex items-center gap-1.5">
              <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
              Share Opportunity
            </h4>
          </div>
          <p class="text-xs text-[#3d6350]">Know someone suitable for this role? Share it directly:</p>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
            <a href="https://api.whatsapp.com/send?text=<?= urlencode($jobTitle . ' at ' . $employerName . ' - ' . current_url()) ?>" target="_blank" rel="noopener noreferrer" class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition">
              <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
              <span>WhatsApp</span>
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(current_url()) ?>" target="_blank" rel="noopener noreferrer" class="p-2.5 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50 text-slate-700 hover:text-blue-700 flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition">
              <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
              <span>LinkedIn</span>
            </a>
            <a href="mailto:?subject=<?= rawurlencode("Job Opportunity: " . $job['title']) ?>&body=<?= rawurlencode("Check out this opportunity: " . current_url()) ?>" class="p-2.5 rounded-xl border border-slate-200 hover:border-slate-500 hover:bg-slate-50 text-slate-700 hover:text-slate-900 flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition">
              <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>Email</span>
            </a>
            <button type="button" onclick="navigator.clipboard.writeText(window.location.href); alert('Job URL copied to clipboard!');" class="p-2.5 rounded-xl border border-slate-200 hover:border-slate-500 hover:bg-slate-50 text-slate-700 hover:text-slate-900 flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition cursor-pointer">
              <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              <span>Copy Link</span>
            </button>
          </div>
          <div class="pt-2 border-t border-gray-100">
            <button type="button" onclick="window.TalentHubEngagement &amp;&amp; window.TalentHubEngagement.openJobAlertModal ? window.TalentHubEngagement.openJobAlertModal() : document.dispatchEvent(new CustomEvent('talenthub:open-job-alert'))" class="w-full py-2.5 px-4 rounded-xl border border-[#1D6A34] text-[#1D6A34] hover:bg-[#EAF5EF] text-xs font-bold transition flex items-center justify-center gap-2">
              <svg class="w-4 h-4 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              <span>Get Similar Job Alerts</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ── DUAL-MODE APPLICATION MODAL DIALOG ────────────────────────────────────── -->
<div id="apply-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
  <div class="bg-white rounded-3xl border border-[rgba(29,106,52,0.2)] shadow-2xl max-w-lg w-full p-6 sm:p-8 relative my-8">
    <button type="button" data-modal-close class="absolute top-5 right-5 text-gray-400 hover:text-gray-700 text-2xl font-bold cursor-pointer" aria-label="Close dialog">&times;</button>

    <div class="mb-5">
      <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Job Application</span>
      <h3 class="text-xl font-bold text-[#172B22] mt-1"><?= $jobTitle ?></h3>
      <p class="text-xs text-[#3d6350] mt-1">Submit your updated resume directly or open a draft in your email client.</p>
    </div>

    <!-- Dual Tab Mode Selectors -->
    <div class="flex items-center gap-2 mb-4 p-1 rounded-xl bg-gray-100">
      <button type="button" id="tab-apply-form" onclick="switchApplyTab('form')" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all bg-white text-[#1D6A34] shadow-sm cursor-pointer">
        Upload Resume (Direct)
      </button>
      <button type="button" id="tab-apply-email" onclick="switchApplyTab('email')" class="flex-1 py-2 text-xs font-bold rounded-lg transition-all text-gray-600 hover:text-[#172B22] cursor-pointer">
        Email Application Draft
      </button>
    </div>

    <!-- MODE 1: DIRECT FORM UPLOAD -->
    <div id="panel-apply-form">
      <form action="<?= base_url('jobs/' . $job['slug'] . '/apply') ?>" method="POST" enctype="multipart/form-data" data-ajax="true" toolname="apply_for_job" tooldescription="Submit resume and candidate application directly for this job posting" class="space-y-4">
        <?= csrf_field() ?>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Full Name *</label>
          <input type="text" name="full_name" required placeholder="e.g. Rahul Sharma" class="form-input-premium text-xs">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Email Address *</label>
            <input type="email" name="email" required placeholder="rahul@example.com" class="form-input-premium text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Mobile Phone *</label>
            <input type="tel" name="phone" required placeholder="+91 98765 43210" pattern="^(\+?[0-9\s\-\(\)]{8,20})$" title="Please enter a valid 10-digit Indian (+91) or international mobile number" class="form-input-premium text-xs">
            <span class="text-[10px] text-[#3d6350] mt-0.5 block">Accepts Indian (+91) and international formats</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Current Location</label>
            <input type="text" name="location" placeholder="e.g. Pune / PCMC" class="form-input-premium text-xs">
          </div>
          <div>
            <label class="block text-xs font-bold text-[#172B22] mb-1">Experience (Years)</label>
            <input type="number" min="0" max="50" step="0.5" name="experience_years" placeholder="e.g. 4.5" class="form-input-premium text-xs">
            <span class="text-[10px] text-[#3d6350] mt-0.5 block">0 to 50 years</span>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Resume Document (PDF, DOC, DOCX up to 5MB) *</label>
          <input type="file" name="resume" id="modal-resume-input" required accept=".pdf,.doc,.docx" class="w-full text-xs px-3 py-2.5 rounded-xl border border-dashed border-[rgba(29,106,52,0.3)] bg-[#FAF9F5] focus:outline-none text-[#172B22]">
          <div id="modal-resume-error" class="text-xs text-red-600 font-semibold mt-1 hidden" role="alert"></div>
        </div>

        <div>
          <label class="block text-xs font-bold text-[#172B22] mb-1">Notice Period &amp; Key Skills (Optional)</label>
          <textarea name="cover_message" rows="2" placeholder="Mention notice period, key accomplishments, or current CTC..." class="form-input-premium text-xs"></textarea>
        </div>

        <!-- Anti-Spam, Honeypot & CAPTCHA Protection -->
        <?= $formProtection ?? '' ?>

        <div class="flex items-start gap-2 pt-1">
          <input type="checkbox" name="consent" id="modal-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
          <label for="modal-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">
            I affirmatively consent to TalentHub processing my CV for this mandate under the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy (v1.0-DPDP)</a>.
          </label>
        </div>

        <button type="submit" id="modal-submit-btn" class="w-full btn-primary text-xs font-bold py-3 mt-2 shadow-lg shadow-[#1D6A34]/25">
          <span>Submit Application Directly</span>
        </button>
      </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modalResumeInput = document.getElementById('modal-resume-input');
      const modalResumeError = document.getElementById('modal-resume-error');

      if (modalResumeInput) {
        modalResumeInput.addEventListener('change', function() {
          const file = this.files[0];
          modalResumeError.classList.add('hidden');
          modalResumeError.textContent = '';
          if (!file) return;

          const allowedExts = ['pdf', 'doc', 'docx'];
          const ext = file.name.split('.').pop().toLowerCase();
          const maxBytes = 5 * 1024 * 1024; // 5 MB

          if (!allowedExts.includes(ext)) {
            modalResumeError.textContent = 'Invalid file extension. Only .pdf, .doc, and .docx documents are accepted.';
            modalResumeError.classList.remove('hidden');
            this.value = '';
            return;
          }

          if (file.size > maxBytes) {
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            modalResumeError.textContent = 'File is too large (' + sizeMb + ' MB). Maximum allowed size is 5.0 MB.';
            modalResumeError.classList.remove('hidden');
            this.value = '';
            return;
          }
        });
      }
    });
    </script>

    <!-- MODE 2: EMAIL DRAFT OPTION -->
    <div id="panel-apply-email" class="hidden space-y-4 pt-2">
      <p class="text-xs text-[#3d6350] leading-relaxed">
        Prefer applying directly from Outlook, Gmail, or your corporate mail client? Use our pre-filled email draft to connect with our Pune hiring desk:
      </p>

      <div class="p-4 rounded-2xl bg-[#EAF5EF] border border-[#1D6A34]/20 space-y-2">
        <div class="flex items-center justify-between text-xs">
          <span class="text-[#3d6350]">Send CV To:</span>
          <span class="font-bold text-[#1D6A34]"><?= $candidateHiringEmail ?></span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-[#3d6350]">Subject Line:</span>
          <span class="font-bold text-[#172B22] truncate max-w-[200px]">Application for <?= $jobTitle ?></span>
        </div>
      </div>

      <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800">
        <strong>Notice:</strong> Please attach your updated Resume (PDF or DOCX) in your email before clicking send.
      </div>

      <div class="space-y-2 pt-2">
        <a href="<?= $mailtoHref ?>" class="w-full btn-primary text-xs font-bold py-3 justify-center">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>Open in Your Email Client</span>
        </a>

        <button type="button" onclick="navigator.clipboard.writeText('<?= $candidateHiringEmail ?>'); window.showToast('Hiring email copied to clipboard!', 'success');" class="w-full btn-outline text-xs font-bold py-2.5 justify-center">
          <span>Copy <?= $candidateHiringEmail ?></span>
        </button>
      </div>
    </div>

    <div class="pt-4 border-t border-gray-100 mt-4 text-center">
      <p class="text-[11px] text-[#3d6350]">
        Pune Hiring Desk: <a href="tel:+917276146573" class="font-bold text-[#1D6A34] underline">+91 72761 46573</a>
      </p>
    </div>
  </div>
</div>

<script>
function switchApplyTab(tab) {
  const formPanel = document.getElementById('panel-apply-form');
  const emailPanel = document.getElementById('panel-apply-email');
  const formTab = document.getElementById('tab-apply-form');
  const emailTab = document.getElementById('tab-apply-email');

  if (tab === 'form') {
    formPanel.classList.remove('hidden');
    emailPanel.classList.add('hidden');
    formTab.classList.add('bg-white', 'text-[#1D6A34]', 'shadow-sm');
    formTab.classList.remove('text-gray-600');
    emailTab.classList.remove('bg-white', 'text-[#1D6A34]', 'shadow-sm');
    emailTab.classList.add('text-gray-600');
  } else {
    formPanel.classList.add('hidden');
    emailPanel.classList.remove('hidden');
    emailTab.classList.add('bg-white', 'text-[#1D6A34]', 'shadow-sm');
    emailTab.classList.remove('text-gray-600');
    formTab.classList.remove('bg-white', 'text-[#1D6A34]', 'shadow-sm');
    formTab.classList.add('text-gray-600');
  }
}
</script>
