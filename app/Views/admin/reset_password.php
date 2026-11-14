<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Reset Password | <?= esc($settings['website_name'] ?? 'TalentHub Consultant') ?></title>
  <link rel="icon" type="image/png" href="/logo-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-[#072412] min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
  <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#1D6A34]/30 rounded-full blur-[120px] pointer-events-none"></div>
  <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#37CE97]/20 rounded-full blur-[140px] pointer-events-none"></div>

  <div class="w-full max-w-md relative z-10">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/10 shadow-lg mb-4">
        <img src="/logo-white.png" alt="TalentHub Logo" class="h-10 w-auto object-contain">
      </div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight">Set New Password</h1>
      <p class="text-xs text-white/60 mt-1">Create a strong, secure credentials for administrative access</p>
    </div>

    <div class="bg-white rounded-3xl p-8 shadow-2xl border border-white/20">
      <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5">
          <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="font-medium"><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('admin/reset-password') ?>" method="POST" class="space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

        <div>
          <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">New Password (min 8 chars)</label>
          <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••••••" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition">
        </div>

        <div>
          <label for="password_confirm" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Confirm New Password</label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="8" placeholder="••••••••••••" class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition">
        </div>

        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-[#1D6A34] hover:bg-[#165a2c] text-white font-bold text-sm shadow-lg shadow-[#1D6A34]/25 transition duration-200 flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Update Password &amp; Login</span>
        </button>
      </form>

      <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-center text-xs text-gray-500">
        <a href="<?= base_url('admin/login') ?>" class="text-[#1D6A34] font-semibold hover:underline">Return to Login</a>
      </div>
    </div>
  </div>
</body>
</html>

