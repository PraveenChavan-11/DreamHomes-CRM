<?php
/** Original brand illustrations. @var string $kind 'recruitment'|'it'|'both' */
$kind = $kind ?? 'both';
?>
<?php if ($kind === 'recruitment'): ?>
<svg viewBox="0 0 320 200" role="img" aria-label="Illustration of candidates being matched to roles through a structured hiring pipeline" xmlns="http://www.w3.org/2000/svg">
  <rect width="320" height="200" rx="16" fill="#fff" opacity=".6"/>
  <g stroke="#37CE97" stroke-width="2" fill="none"><path class="th-draw" d="M70 100 H120 M200 100 H250 M160 60 V80 M160 120 V140"/></g>
  <g fill="#1D6A34"><circle cx="50" cy="100" r="22"/><circle cx="160" cy="100" r="30" fill="#1A532A"/><circle cx="270" cy="100" r="22"/><circle cx="160" cy="42" r="14" fill="#37CE97"/><circle cx="160" cy="158" r="14" fill="#37CE97"/></g>
  <g fill="#fff"><circle cx="50" cy="94" r="7"/><path d="M38 112c2-8 22-8 24 0z"/><circle cx="160" cy="92" r="9"/><path d="M144 118c3-11 29-11 32 0z"/><circle cx="270" cy="94" r="7"/><path d="M258 112c2-8 22-8 24 0z"/></g>
  <path d="M153 42l5 5 9-10" stroke="#0b2d18" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
<?php elseif ($kind === 'it'): ?>
<svg viewBox="0 0 320 200" role="img" aria-label="Illustration of an application interface, API and database layers" xmlns="http://www.w3.org/2000/svg">
  <rect width="320" height="200" rx="16" fill="#fff" opacity=".6"/>
  <rect x="40" y="28" width="170" height="112" rx="10" fill="#1A532A"/>
  <circle cx="56" cy="42" r="4" fill="#37CE97"/><circle cx="70" cy="42" r="4" fill="#fff" opacity=".5"/><circle cx="84" cy="42" r="4" fill="#fff" opacity=".5"/>
  <g stroke="#37CE97" stroke-width="3" stroke-linecap="round"><path class="th-draw" d="M58 66h60M70 84h82M58 102h46M84 120h58"/></g>
  <g class="th-float"><rect x="190" y="70" width="92" height="74" rx="10" fill="#1D6A34"/><rect x="204" y="84" width="64" height="10" rx="5" fill="#37CE97"/><rect x="204" y="102" width="48" height="10" rx="5" fill="#fff" opacity=".7"/><rect x="204" y="120" width="56" height="10" rx="5" fill="#fff" opacity=".5"/></g>
  <ellipse cx="256" cy="162" rx="30" ry="9" fill="#1A532A"/><path d="M226 162v14c0 5 13 9 30 9s30-4 30-9v-14" fill="#1D6A34"/>
</svg>
<?php elseif ($kind === 'worksphere'): ?>
<svg viewBox="0 0 420 280" role="img" aria-label="WorkSphere enterprise dashboard illustration showing workforce management, attendance, and payroll metrics" xmlns="http://www.w3.org/2000/svg">
  <rect width="420" height="280" rx="16" fill="#0D2E1A"/>
  <!-- Top bar -->
  <rect x="24" y="20" width="372" height="32" rx="8" fill="#1A532A"/>
  <circle cx="44" cy="36" r="5" fill="#37CE97"/>
  <circle cx="60" cy="36" r="5" fill="#37CE97" opacity=".5"/>
  <rect x="80" y="32" width="90" height="8" rx="4" fill="#fff" opacity=".4"/>
  <rect x="330" y="28" width="54" height="16" rx="8" fill="#37CE97"/>
  <!-- Metric card 1: Workforce -->
  <g class="th-float">
    <rect x="24" y="64" width="116" height="72" rx="10" fill="#144121" stroke="#37CE97" stroke-opacity=".25" stroke-width="1"/>
    <text x="36" y="86" fill="#37CE97" font-size="10" font-family="sans-serif" font-weight="700">WORKFORCE</text>
    <text x="36" y="112" fill="#fff" font-size="20" font-family="sans-serif" font-weight="800">100%</text>
    <rect x="36" y="122" width="92" height="4" rx="2" fill="#1A532A"/>
    <rect x="36" y="122" width="76" height="4" rx="2" fill="#37CE97"/>
  </g>
  <!-- Metric card 2: Attendance -->
  <g class="th-float" style="--d:.15s">
    <rect x="152" y="64" width="116" height="72" rx="10" fill="#144121" stroke="#37CE97" stroke-opacity=".25" stroke-width="1"/>
    <text x="164" y="86" fill="#37CE97" font-size="10" font-family="sans-serif" font-weight="700">ATTENDANCE</text>
    <text x="164" y="112" fill="#fff" font-size="20" font-family="sans-serif" font-weight="800">Biometric</text>
    <circle cx="250" cy="100" r="10" fill="#37CE97" opacity=".2"/>
    <path d="M246 100l3 3 6-6" stroke="#37CE97" stroke-width="2" fill="none" stroke-linecap="round"/>
  </g>
  <!-- Metric card 3: Payroll -->
  <g class="th-float" style="--d:.3s">
    <rect x="280" y="64" width="116" height="72" rx="10" fill="#144121" stroke="#37CE97" stroke-opacity=".25" stroke-width="1"/>
    <text x="292" y="86" fill="#37CE97" font-size="10" font-family="sans-serif" font-weight="700">PAYROLL &amp; STAT</text>
    <text x="292" y="112" fill="#fff" font-size="20" font-family="sans-serif" font-weight="800">One-Click</text>
    <path d="M292 124c12-6 24 2 36-3s24-8 36-2" stroke="#37CE97" stroke-width="2.5" fill="none" stroke-linecap="round"/>
  </g>
  <!-- Main content panel: Modules grid -->
  <rect x="24" y="148" width="372" height="112" rx="10" fill="#11381D" stroke="#1D6A34" stroke-width="1"/>
  <g fill="#1A532A">
    <rect x="36" y="160" width="108" height="28" rx="6"/>
    <rect x="156" y="160" width="108" height="28" rx="6"/>
    <rect x="276" y="160" width="108" height="28" rx="6"/>
    <rect x="36" y="196" width="108" height="28" rx="6"/>
    <rect x="156" y="196" width="108" height="28" rx="6"/>
    <rect x="276" y="196" width="108" height="28" rx="6"/>
  </g>
  <g fill="#fff" opacity=".85" font-size="9" font-family="sans-serif" font-weight="600">
    <text x="48" y="178">Employee Master</text>
    <text x="168" y="178">Shift Rosters</text>
    <text x="288" y="178">PF, ESIC &amp; TDS</text>
    <text x="48" y="214">Leave Approvals</text>
    <text x="168" y="214">Project Timesheets</text>
    <text x="288" y="214">Performance OKRs</text>
  </g>
  <circle cx="42" cy="174" r="2.5" fill="#37CE97"/>
  <circle cx="162" cy="174" r="2.5" fill="#37CE97"/>
  <circle cx="282" cy="174" r="2.5" fill="#37CE97"/>
  <circle cx="42" cy="210" r="2.5" fill="#37CE97"/>
  <circle cx="162" cy="210" r="2.5" fill="#37CE97"/>
  <circle cx="282" cy="210" r="2.5" fill="#37CE97"/>
  <!-- Status footer -->
  <rect x="36" y="234" width="348" height="16" rx="4" fill="#0A2414"/>
  <circle cx="46" cy="242" r="3" fill="#37CE97"/>
  <text x="56" y="245" fill="#37CE97" font-size="8.5" font-family="sans-serif" font-weight="700">LIVE WORKFORCE CLOUD &bull; HTTPS://WORKSPHERE.TALENTHUBCONSULTANT.COM</text>
</svg>
<?php else: ?>
<svg viewBox="0 0 400 400" role="img" aria-label="TalentHub brings people and technology together" xmlns="http://www.w3.org/2000/svg">
  <circle cx="200" cy="200" r="150" fill="none" stroke="#37CE97" stroke-opacity=".35" stroke-width="2" stroke-dasharray="4 8"/>
  <circle cx="200" cy="200" r="96" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="2"/>
  <g class="th-float"><circle cx="200" cy="200" r="58" fill="#37CE97"/><path d="M178 214c4-16 40-16 44 0M200 170a14 14 0 1 1 0 28 14 14 0 0 1 0-28z" fill="#0b2d18"/></g>
  <g fill="#fff"><circle cx="82" cy="150" r="30" opacity=".95"/><circle cx="318" cy="150" r="30" opacity=".95"/><circle cx="200" cy="344" r="30" opacity=".95"/></g>
  <g stroke="#1D6A34" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M70 150c0-9 24-9 24 0M82 134a7 7 0 1 1 0 .1"/><path d="M308 144l-10 6 10 6M328 144l10 6-10 6"/><path d="M188 338c0-9 24-9 24 0M200 322a7 7 0 1 1 0 .1"/></g>
  <g stroke="#37CE97" stroke-opacity=".6" stroke-width="2"><path class="th-draw" d="M108 160L150 184M292 160L250 184M200 258V314"/></g>
</svg>
<?php endif; ?>
