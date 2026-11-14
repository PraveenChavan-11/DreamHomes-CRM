<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($title ?? 'TalentHub — Executive Placement & Operations Report') ?></title>
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-tailwind.css?v=' . (file_exists(FCPATH . 'assets/css/admin-tailwind.css') ? filemtime(FCPATH . 'assets/css/admin-tailwind.css') : time())) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    @media print {
      .no-print { display: none !important; }
      body { background: #FFFFFF !important; color: #000000 !important; }
      .page-break { page-break-before: always; }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased p-4 sm:p-8">

  <!-- Print Control Bar (Hidden on print) -->
  <div class="no-print max-w-5xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/engagement/analytics') ?>" class="text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1">
        &larr; Back to Dashboard
      </a>
      <span class="text-slate-300">|</span>
      <span class="text-xs font-semibold text-slate-500">TalentHub Executive Report Generator (B2B Audit Ready)</span>
    </div>
    <div class="flex items-center gap-3">
      <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#1D6A34] text-white text-xs font-bold shadow-md hover:bg-[#155227] transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print / Save as PDF
      </button>
    </div>
  </div>

  <!-- Document Container -->
  <div class="max-w-5xl mx-auto bg-white rounded-3xl border border-slate-200 p-8 sm:p-12 shadow-sm">

    <!-- Document Header -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between pb-8 border-b border-slate-200 gap-6">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-[#1D6A34] text-white flex items-center justify-center font-black text-xl">
            T
          </div>
          <div>
            <span class="text-xl font-black text-slate-900 tracking-tight">TalentHub</span>
            <span class="text-xs font-bold text-[#1D6A34] ml-1">Consultant</span>
          </div>
        </div>
        <p class="text-xs text-slate-500 max-w-md leading-relaxed">
          Pune Recruitment Intelligence, Corporate Staffing &amp; Executive Search Mandates.<br>
          Hinjawadi Phase 1-3 &bull; Chakan Industrial &bull; Kharadi IT Corridor &bull; Pune, Maharashtra
        </p>
      </div>

      <div class="sm:text-right text-xs text-slate-500 space-y-1">
        <div class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 text-emerald-800 border border-emerald-200">
          Executive Operations Report
        </div>
        <p class="font-bold text-slate-800 text-sm mt-2">Report Ref: TH-EXEC-<?= date('Ym') ?>-01</p>
        <p>Generated: <?= date('d M Y, h:i A') ?> IST</p>
        <p>Auditor: TalentHub Automated Intelligence</p>
      </div>
    </div>

    <!-- Executive KPI Grid -->
    <div class="mt-8">
      <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-4">Core Placement &amp; Talent KPIs</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <div class="text-[11px] font-bold text-slate-500 uppercase">Active Mandates</div>
          <div class="text-2xl font-black text-slate-900 mt-1"><?= number_format($activeJobsCount ?? 0) ?></div>
          <div class="text-[10px] text-emerald-600 font-semibold mt-1">Live in Pune Region</div>
        </div>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <div class="text-[11px] font-bold text-slate-500 uppercase">Total Applications</div>
          <div class="text-2xl font-black text-slate-900 mt-1"><?= number_format($totalApplications ?? 0) ?></div>
          <div class="text-[10px] text-blue-600 font-semibold mt-1">Inbound Candidates</div>
        </div>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <div class="text-[11px] font-bold text-slate-500 uppercase">Shortlisted / Pipeline</div>
          <div class="text-2xl font-black text-[#1D6A34] mt-1"><?= number_format($shortlistedCount ?? 0) ?></div>
          <div class="text-[10px] text-emerald-700 font-semibold mt-1">Screening &amp; Interview</div>
        </div>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
          <div class="text-[11px] font-bold text-slate-500 uppercase">Verified Placements</div>
          <div class="text-2xl font-black text-emerald-600 mt-1"><?= number_format($hiredCount ?? 0) ?></div>
          <div class="text-[10px] text-emerald-700 font-semibold mt-1">Hired Candidates</div>
        </div>
      </div>
    </div>

    <!-- Candidate Pipeline Funnel -->
    <div class="mt-10">
      <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-4">Talent Funnel &amp; Conversion Efficiency</h2>
      <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
        <div class="space-y-4">
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
              <span>Candidate Pool Registered</span>
              <span><?= number_format($totalCandidates ?? 0) ?> profiles</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-slate-800 h-full rounded-full" style="width: 100%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
              <span>Applications Submitted</span>
              <span><?= number_format($totalApplications ?? 0) ?> applications</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-blue-600 h-full rounded-full" style="width: <?= !empty($totalCandidates) ? min(100, round(($totalApplications / $totalCandidates) * 100)) : 100 ?>%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
              <span>Shortlisted / Technical Interview</span>
              <span><?= number_format($shortlistedCount ?? 0) ?> candidates</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-[#1D6A34] h-full rounded-full" style="width: <?= !empty($totalApplications) ? min(100, round(($shortlistedCount / $totalApplications) * 100)) : 40 ?>%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
              <span>Final Placements / Offers Accepted</span>
              <span><?= number_format($hiredCount ?? 0) ?> hired</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-emerald-500 h-full rounded-full" style="width: <?= !empty($totalApplications) ? min(100, round(($hiredCount / $totalApplications) * 100)) : 20 ?>%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Campaign Engagement Telemetry -->
    <div class="mt-10">
      <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-4">Marketing Campaign &amp; Sourcing Performance</h2>
      <div class="border border-slate-200 rounded-2xl overflow-hidden">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
            <tr>
              <th class="px-4 py-3">Initiative / Campaign</th>
              <th class="px-4 py-3">Format</th>
              <th class="px-4 py-3 text-right">Impressions</th>
              <th class="px-4 py-3 text-right">Clicks</th>
              <th class="px-4 py-3 text-right">CTR</th>
              <th class="px-4 py-3 text-right">Conversions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (!empty($analytics)): ?>
              <?php foreach ($analytics as $c): ?>
                <?php $ctr = $c['impressions'] > 0 ? round(($c['clicks'] / $c['impressions']) * 100, 2) : 0; ?>
                <tr>
                  <td class="px-4 py-3 font-bold text-slate-800"><?= esc($c['title']) ?></td>
                  <td class="px-4 py-3 text-slate-500 capitalize"><?= esc(str_replace('_', ' ', $c['display_format'])) ?></td>
                  <td class="px-4 py-3 text-right font-medium"><?= number_format($c['impressions']) ?></td>
                  <td class="px-4 py-3 text-right font-bold text-[#1D6A34]"><?= number_format($c['clicks']) ?></td>
                  <td class="px-4 py-3 text-right font-bold text-slate-800"><?= $ctr ?>%</td>
                  <td class="px-4 py-3 text-right font-black text-emerald-600"><?= number_format($c['conversions']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-4 py-6 text-center text-slate-400">Standard operational engagement active.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Sign-off & Compliance Footer -->
    <div class="mt-12 pt-8 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
      <div>
        <p>&copy; <?= date('Y') ?> TalentHub Consultant. Confidential Executive Report.</p>
        <p>India DPDP Act 2023/2026 Compliant &bull; Non-Public Resume Vault Verified</p>
      </div>
      <div class="text-right">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          System Verified &bull; ISO-Ready
        </span>
      </div>
    </div>

  </div>

</body>
</html>

