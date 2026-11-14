<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($headline ?? 'System Maintenance Underway') ?> | TalentHub Consultant</title>
  <link rel="icon" type="image/png" href="/logo-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-[#072412] flex items-center justify-center px-4 py-24 text-white relative overflow-hidden">
  <!-- Glow Orbs -->
  <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#1D6A34]/30 rounded-full blur-[140px] pointer-events-none"></div>
  <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#37CE97]/20 rounded-full blur-[140px] pointer-events-none"></div>

  <div class="max-w-xl w-full bg-white rounded-3xl p-8 sm:p-12 text-center relative shadow-2xl z-10 text-[#172B22]">
    <!-- Brand Logo -->
    <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-[#EAF5EF] border border-[#1D6A34]/20 shadow-sm mb-6">
      <img src="/logo.png" alt="TalentHub Logo" class="h-9 w-auto object-contain">
    </div>

    <!-- Status Badge -->
    <div class="block mb-4">
      <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 text-amber-800 text-xs font-bold border border-amber-200">
        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
        Scheduled Platform Upgrade
      </span>
    </div>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] tracking-tight mb-3">
      <?= esc($headline ?? 'System Maintenance Underway') ?>
    </h1>

    <p class="text-sm sm:text-base text-slate-600 leading-relaxed mb-6 max-w-md mx-auto">
      <?= esc($message ?? 'TalentHub Consultant is currently undergoing scheduled platform upgrades to serve you better.') ?>
    </p>

    <!-- Return ETA Box -->
    <div class="mb-8 p-4 rounded-2xl bg-[#FAF9F5] border border-gray-200 text-xs text-slate-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-left">
      <div class="flex items-center gap-2.5">
        <svg class="w-5 h-5 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <span class="block font-bold text-gray-900">Estimated Reopening</span>
          <span class="text-gray-500"><?= esc($returnTime ?? 'Shortly (Within 1 Hour)') ?></span>
        </div>
      </div>
      <span class="px-2.5 py-1 rounded-md bg-[#1D6A34]/10 text-[#1D6A34] text-[11px] font-bold">HTTP 503 Active</span>
    </div>

    <!-- Contact Info -->
    <div class="pt-6 border-t border-gray-100 space-y-3">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Urgent Mandates &amp; Candidate Assistance</p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 text-xs font-medium">
        <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $supportPhone ?? '+917276146573')) ?>" class="inline-flex items-center gap-1.5 text-[#1D6A34] hover:underline font-bold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <?= esc($supportPhone ?? '+91 72761 46573') ?>
        </a>
        <span class="hidden sm:inline text-gray-300">&bull;</span>
        <a href="mailto:<?= esc($supportEmail ?? 'hr@talenthubconsultant.com') ?>" class="inline-flex items-center gap-1.5 text-[#1D6A34] hover:underline font-bold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <?= esc($supportEmail ?? 'hr@talenthubconsultant.com') ?>
        </a>
      </div>
    </div>
  </div>
</body>
</html>

