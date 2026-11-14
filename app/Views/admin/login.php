<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Authentication | <?= esc($settings['website_name'] ?? 'TalentHub Consultant') ?></title>
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
  </style>
</head>
<body class="h-full antialiased text-slate-800 selection:bg-[#37CE97] selection:text-[#072412]">

  <div class="min-h-screen flex w-full">

    <!-- ── LEFT: BRAND SHOWCASE PANEL (DESKTOP 50%) ───────────────────────────── -->
    <div class="hidden lg:flex lg:w-1/2 login-brand-panel relative overflow-hidden flex-col justify-between p-12 xl:p-16" style="background: linear-gradient(145deg, #072412 0%, #1D6A34 60%, #155227 100%) !important; color: #ffffff !important;">
      <!-- Ambient Decorative Circles -->
      <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-[#37CE97]/15 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-[#37CE97]/20 blur-3xl pointer-events-none"></div>
      <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.03)_1px,transparent_1px)] [background-size:40px_40px] opacity-40 pointer-events-none"></div>

      <!-- Top Header / Logo -->
      <div class="relative z-10">
        <a href="<?= base_url() ?>" class="inline-flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center shadow-lg">
            <svg class="w-6 h-6 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <span class="text-xl font-extrabold tracking-tight text-white block">TalentHub</span>
            <span class="text-[10px] text-[#37CE97] font-bold tracking-widest uppercase block">Operations Platform</span>
          </div>
        </a>
      </div>

      <!-- Center Value Proposition -->
      <div class="relative z-10 max-w-lg space-y-6">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/15 text-xs font-semibold text-[#37CE97]">
          <span class="w-2 h-2 rounded-full bg-[#37CE97] animate-pulse"></span>
          <span>Enterprise Recruitment CRM &amp; Email Center</span>
        </div>

        <h1 class="text-3xl xl:text-4xl font-extrabold leading-tight text-white" style="color: #ffffff !important;">
          Empowering Talent Acquisition &amp; Executive Mandate Delivery
        </h1>

        <p class="text-sm leading-relaxed font-normal" style="color: rgba(255,255,255,0.85) !important;">
          Manage corporate employer mandates, review verified candidate applications, process recruitment workflows, and control official Hostinger Mail communications securely.
        </p>

        <div class="grid grid-cols-2 gap-4 pt-2">
          <div class="p-4 rounded-2xl backdrop-blur-sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18);">
            <p class="text-2xl font-extrabold font-heading" style="color: #37CE97 !important;">7 Days</p>
            <p class="text-xs font-medium" style="color: rgba(255,255,255,0.9) !important;">Turnaround Target</p>
          </div>
          <div class="p-4 rounded-2xl backdrop-blur-sm" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18);">
            <p class="text-2xl font-extrabold font-heading" style="color: #37CE97 !important;">100%</p>
            <p class="text-xs font-medium" style="color: rgba(255,255,255,0.9) !important;">Encrypted Vault</p>
          </div>
        </div>
      </div>

      <!-- Bottom Security Notice -->
      <div class="relative z-10 flex items-center justify-between text-xs pt-4 border-t border-white/15" style="color: rgba(255,255,255,0.75) !important;">
        <span>ISO-standard role-based authentication</span>
        <span>TalentHub Pune</span>
      </div>
    </div>

    <!-- ── RIGHT: LOGIN FORM PANEL (50% DESKTOP, 100% MOBILE) ────────────────── -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative bg-[#FAF9F5] lg:bg-slate-50">
      <div class="w-full max-w-md bg-white p-8 sm:p-10 rounded-3xl shadow-xl shadow-slate-900/5 border border-slate-200/80">

        <!-- Mobile Brand Header -->
        <div class="lg:hidden flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl bg-[#1D6A34] text-white flex items-center justify-center shadow-md">
            <svg class="w-5 h-5 text-[#37CE97]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <span class="text-lg font-extrabold text-[#172B22] block">TalentHub</span>
            <span class="text-[10px] text-[#1D6A34] font-bold uppercase tracking-wider block">Admin Center</span>
          </div>
        </div>

        <div class="mb-8">
          <h2 class="text-2xl font-extrabold text-slate-900">Sign In to Dashboard</h2>
          <p class="text-xs font-semibold text-slate-500 mt-1">Enter your administrative credentials to continue.</p>
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

        <form action="<?= base_url('admin/login') ?>" method="POST" class="space-y-5">
          <?= csrf_field() ?>

          <div>
            <label for="identifier" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Username or Email</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </span>
              <input type="text" id="identifier" name="email" value="<?= esc(old('email')) ?>" required autofocus autocomplete="username" placeholder="admin or hr@talenthubconsultant.com" class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition bg-white">
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
              <a href="<?= base_url('admin/forgot-password') ?>" class="text-xs font-bold text-[#1D6A34] hover:underline">Forgot password?</a>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </span>
              <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••••••" class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-300 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition bg-white">
              <button type="button" onclick="togglePasswordVisibility('password', 'eye-icon')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer" aria-label="Toggle password visibility">
                <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center gap-2 cursor-pointer font-medium text-slate-700">
              <input type="checkbox" name="remember" class="rounded border-slate-300 text-[#1D6A34] focus:ring-[#37CE97]">
              <span>Remember this workstation</span>
            </label>
          </div>

          <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white font-bold text-xs sm:text-sm shadow-lg shadow-[#1D6A34]/25 transition duration-200 flex items-center justify-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            <span>Authenticate to Dashboard</span>
          </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
          <p class="text-[11px] text-slate-400">
            TalentHub Consultant &bull; Secure Recruitment Platform &bull; Pune
          </p>
        </div>

      </div>
    </div>

  </div>

  <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
