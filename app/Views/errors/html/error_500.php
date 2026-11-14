<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System Notice — TalentHub Consultant</title>
  <link rel="icon" type="image/png" href="/logo-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-[#FAF9F5] flex items-center justify-center px-4 py-24 text-[#172B22] relative overflow-hidden">
  <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-[#1D6A34]/10 rounded-full blur-[140px] pointer-events-none"></div>

  <div class="max-w-xl w-full bg-white border border-[#1D6A34]/15 rounded-3xl p-8 sm:p-12 text-center relative shadow-xl z-10">
    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 text-amber-800 text-xs font-bold mb-6 border border-amber-200">
      <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <span>Temporary Service Notice</span>
    </div>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] tracking-tight mb-3">Something Went Unexpectedly Wrong</h1>
    <p class="text-sm text-[#172B22]/75 leading-relaxed mb-6 max-w-md mx-auto">
      Our technical team has been automatically alerted to this event. No data or application details have been compromised. Please refresh or try again in a few moments.
    </p>

    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-[11px] text-gray-500 font-mono mb-8 inline-block">
      Incident Ref: <?= bin2hex(random_bytes(6)) ?> &bull; <?= date('Y-m-d H:i:s T') ?>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      <a href="/" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-[#1D6A34] text-white text-xs sm:text-sm font-bold shadow-md shadow-[#1D6A34]/20 hover:bg-[#165a2c] transition-all">
        <span>Return to Homepage</span>
      </a>
      <a href="/contact" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl border border-gray-200 bg-white text-[#172B22] text-xs sm:text-sm font-bold hover:bg-gray-50 transition-all">
        <span>Contact Technical Support</span>
      </a>
    </div>
  </div>
</body>
</html>

