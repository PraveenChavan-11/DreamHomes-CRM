<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Password Recovery | <?= esc($settings['website_name'] ?? 'TalentHub Consultant') ?></title>
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
      <h1 class="text-2xl font-extrabold text-white tracking-tight">Password Recovery</h1>
      <p class="text-xs text-white/60 mt-1">Receive secure reset instructions via verified email</p>
    </div>

    <div class="bg-white rounded-3xl p-8 shadow-2xl border border-white/20">
      <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5">
          <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="font-medium"><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('admin/forgot-password') ?>" method="POST" class="space-y-5">
        <?= csrf_field() ?>

        <div>
          <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Registered Administrator Email</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
            </span>
            <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required autofocus placeholder="hr@talenthubconsultant.com" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-[#1D6A34] focus:ring-2 focus:ring-[#37CE97]/30 transition">
          </div>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-[#1D6A34] hover:bg-[#165a2c] text-white font-bold text-sm shadow-lg shadow-[#1D6A34]/25 transition duration-200 flex items-center justify-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>Send Recovery Email</span>
        </button>
      </form>

      <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
        <a href="<?= base_url('admin/login') ?>" class="text-[#1D6A34] font-semibold hover:underline flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Back to Login
        </a>
        <a href="/" class="text-gray-400 hover:text-gray-600">Home</a>
      </div>
    </div>
  </div>
</body>
</html>

