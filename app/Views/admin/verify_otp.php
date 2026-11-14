<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Email OTP Verification | <?= esc($settings['website_name'] ?? 'TalentHub Consultant') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('favicon.png') ?>">

  <!-- Google Fonts: Inter & Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">

  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif; }
    .otp-letter-spacing { letter-spacing: 0.35em; }
  </style>
</head>
<body class="h-full antialiased text-slate-800 selection:bg-[#37CE97] selection:text-[#072412]">

  <div class="min-h-screen flex w-full">

    <!-- ── LEFT: BRAND SHOWCASE PANEL ─────────────────────────────────────────── -->
    <div class="hidden lg:flex lg:w-1/2 login-brand-panel relative overflow-hidden flex-col justify-between p-12 xl:p-16" style="background: linear-gradient(145deg, #072412 0%, #1D6A34 60%, #155227 100%) !important; color: #ffffff !important;">
      <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-[#37CE97]/15 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-[#37CE97]/20 blur-3xl pointer-events-none"></div>

      <!-- Top Header / Logo -->
      <div class="relative z-10">
        <a href="<?= base_url() ?>" class="inline-flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shadow-lg">
            <svg class="w-6 h-6 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <span class="text-xl font-extrabold tracking-tight text-white block">TalentHub</span>
            <span class="text-[10px] text-[#37CE97] font-bold tracking-widest uppercase block">Identity Shield</span>
          </div>
        </a>
      </div>

      <!-- Center Value Proposition -->
      <div class="relative z-10 max-w-lg space-y-6">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-[#37CE97]">
          <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
          <span>Zero-App Email OTP Verification</span>
        </div>

        <h1 class="text-3xl xl:text-4xl font-extrabold leading-tight text-white" style="color: #ffffff !important;">
          Secure Administrative Workstation Verification
        </h1>

        <p class="text-sm leading-relaxed font-normal" style="color: rgba(255,255,255,0.85) !important;">
          Two-factor authentication protects candidate vaults, confidential client mandates, and recruitment activity records with instant email-delivered passcodes.
        </p>

        <div class="p-4 rounded-2xl backdrop-blur-sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18);">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#37CE97]/20 text-[#37CE97] flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <p class="text-xs font-bold text-white">Dispatched to Official Email</p>
              <p class="text-[11px] text-white/70">Check your inbox or corporate spam folder</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Notice -->
      <div class="relative z-10 flex items-center justify-between text-xs pt-4 border-t border-white/15" style="color: rgba(255,255,255,0.75) !important;">
        <span>Single-use 10-minute code</span>
        <span>TalentHub Pune</span>
      </div>
    </div>

    <!-- ── RIGHT: OTP VERIFICATION FORM PANEL ──────────────────────────────────── -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative bg-[#FAF9F5] lg:bg-slate-50">
      <div class="w-full max-w-md bg-white p-8 sm:p-10 rounded-3xl shadow-xl shadow-slate-900/5 border border-slate-200/80">

        <!-- Mobile Header -->
        <div class="lg:hidden flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl bg-[#1D6A34] text-white flex items-center justify-center shadow-md">
            <svg class="w-5 h-5 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <span class="text-lg font-extrabold text-[#172B22] block">TalentHub</span>
            <span class="text-[10px] text-[#1D6A34] font-bold uppercase tracking-wider block">Security Check</span>
          </div>
        </div>

        <div class="mb-6">
          <div class="w-12 h-12 rounded-2xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <h2 class="text-2xl font-extrabold text-slate-900">Enter Verification Code</h2>
          <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            A 6-digit passcode has been sent to <strong class="text-slate-800"><?= esc($maskedEmail ?? 'your registered email') ?></strong>.
          </p>
        </div>

        <!-- Session Flash Messages -->
        <?php if (session()->getFlashdata('error')): ?>
          <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-start gap-2.5">
            <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="leading-relaxed"><?= esc(session()->getFlashdata('error')) ?></span>
          </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
          <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-start gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span class="leading-relaxed"><?= esc(session()->getFlashdata('success')) ?></span>
          </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/verify-otp') ?>" method="POST" class="space-y-5">
          <?= csrf_field() ?>

          <div>
            <label for="otp" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 text-center">6-Digit One-Time Code</label>
            <input type="text" id="otp" name="otp" required autofocus maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="••••••"
                   autocomplete="one-time-code"
                   class="w-full text-center text-2xl font-mono font-bold tracking-[0.4em] py-3.5 px-4 rounded-xl border border-slate-300 text-slate-900 placeholder:text-slate-300 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition bg-slate-50/50">
          </div>

          <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white font-bold text-xs sm:text-sm shadow-lg shadow-[#1D6A34]/25 transition duration-200 flex items-center justify-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <span>Verify &amp; Enter Dashboard</span>
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs">
          <form action="<?= base_url('admin/resend-otp') ?>" method="POST" class="inline">
            <?= csrf_field() ?>
            <button type="submit" class="font-bold text-[#1D6A34] hover:underline cursor-pointer">
              Resend Passcode
            </button>
          </form>

          <a href="<?= base_url('admin/cancel-otp') ?>" class="text-slate-400 hover:text-slate-600 transition">
            Cancel &amp; Sign In Again
          </a>
        </div>

      </div>
    </div>

  </div>

</body>
</html>

