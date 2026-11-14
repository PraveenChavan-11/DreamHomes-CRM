<?php
  $adminUser  = $adminUser ?? session()->get('admin_user') ?? [];
  $counts     = $counts ?? [];
  $activeMenu = $activeMenu ?? 'dashboard';
  $settings   = $settings ?? [];
  $title      = $title ?? 'Admin Portal — TalentHub Operations Center';
  $isStaging  = \App\Services\EnvironmentSafetyService::isStaging();
  $stagingTelemetry = $isStaging ? \App\Services\EnvironmentSafetyService::getStagingHealthSummary() : null;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= esc($title ?? 'Admin Portal — TalentHub Consultant') ?></title>
  <link rel="icon" type="image/png" sizes="48x48" href="<?= base_url('favicon-48x48.png') ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= base_url('favicon-96x96.png') ?>">
  <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('favicon-192x192.png') ?>">
  <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">

  <!-- Progressive Web App (PWA) Meta & Manifest -->
  <link rel="manifest" href="<?= base_url($isStaging ? 'manifest-staging.json' : 'manifest-admin.json') ?>">
  <meta name="theme-color" content="#1D6A34">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="TalentHub">
  <link rel="apple-touch-icon" href="<?= base_url('icons/icon-192.png') ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS: pre-built (tools/tailwind) instead of the runtime CDN compiler -->
  <link rel="stylesheet" href="<?= base_url('assets/css/admin-tailwind.css?v=' . (file_exists(FCPATH . 'assets/css/admin-tailwind.css') ? filemtime(FCPATH . 'assets/css/admin-tailwind.css') : time())) ?>">

  <!-- Custom Styles & Design Tokens -->
  <link rel="stylesheet" href="<?= base_url('assets/css/custom.css?v=' . (file_exists(FCPATH . 'assets/css/custom.css') ? filemtime(FCPATH . 'assets/css/custom.css') : time())) ?>">

  <?php
    // Heavy third-party scripts load only on pages that use them. `$content` is the already-rendered page body; when it is not
    // available (section-style views) they load as before, so nothing can break.
    if (isset($content) && is_string($content) && $content !== '') {
        $__body = $content;
    } else {
        // renderSection() CONSUMES the section unless $saveData is true; peek with true so <main> can still print it.
        $__body = (string) $this->renderSection('content', true);
    }
    if ($__body === '') { $__body = null; }
    $__needsChart  = $__body === null || (bool) preg_match('/\bChart\b/', $__body);
    $__editor      = config('Editor');
    $__needsEditor = $__editor->isTinyMceEnabled() && ($__body === null || str_contains($__body, 'tinymce'));
  ?>
  <?php if ($__needsChart): ?>
  <!-- Chart.js Engine -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php endif; ?>

  <?php if ($__needsEditor): ?>
  <!-- TinyMCE Rich Text Editor (key comes from configuration, see app/Config/Editor.php) -->
  <script src="<?= esc($__editor->tinymceScriptUrl(), 'attr') ?>" referrerpolicy="origin"></script>
  <?php endif; ?>

  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
    [x-cloak] { display: none !important; }
  </style>
</head>
<body class="h-full flex overflow-hidden text-slate-800 antialiased selection:bg-brand-accent selection:text-brand-dark">

  <!-- Mobile Sidebar Backdrop -->
  <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/60 z-40 hidden md:hidden transition-opacity"></div>

  <!-- Main Sidebar -->
  <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-brand-dark text-white flex flex-col transition-transform duration-300 -translate-x-full md:translate-x-0 md:static md:inset-auto md:shrink-0 border-r border-white/10 shadow-xl">

    <!-- Sidebar Header / Brand Logo -->
    <div class="h-20 flex items-center justify-between px-6 border-b border-white/10 bg-black/20">
      <a href="<?= base_url('admin') ?>" class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 p-1.5 flex items-center justify-center shadow-lg shadow-brand-accent/20">
          <img src="<?= base_url('favicon.png') ?>" alt="TalentHub" class="w-7 h-7 object-contain">
        </div>
        <div class="nav-brand-text">
          <span class="text-base font-extrabold tracking-tight text-white block">TalentHub</span>
          <span class="text-[10px] text-brand-accent font-semibold tracking-wider uppercase block">Operations Center</span>
        </div>
      </a>
      <button id="close-mobile-sidebar" class="md:hidden p-2 rounded-lg text-white/70 hover:text-white hover:bg-white/10">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Navigation Links -->
    <?php /* permission helper */ ?>
    <?php
      $userRoles = $adminUser['roles'] ?? [];
      $userPerms = $adminUser['permissions'] ?? [];
      $isSuperAdmin = false;
      foreach ($userRoles as $r) {
          if (($r['slug'] ?? '') === 'super_admin' || ($r['name'] ?? '') === 'Super Administrator') {
              $isSuperAdmin = true;
              break;
          }
      }

      $canAccess = function (string ...$permissionCodes) use ($isSuperAdmin, $userPerms): bool {
          if ($isSuperAdmin) {
              return true;
          }
          foreach ($permissionCodes as $p) {
              if (in_array($p, $userPerms, true)) {
                  return true;
              }
          }
          return false;
      };
    ?>
    <?php include __DIR__ . '/_sidebar_nav.php'; ?>

    <!-- Sidebar Footer / Current Admin User -->
    <div class="p-4 border-t border-white/10 bg-black/20 flex items-center justify-between">
      <div class="flex items-center gap-3 overflow-hidden">
        <div class="w-9 h-9 rounded-full bg-brand-primary border border-brand-accent/40 flex items-center justify-center font-bold text-white text-xs shrink-0">
          <?= esc(strtoupper(substr($adminUser['name'] ?? 'Admin', 0, 1))) ?>
        </div>
        <div class="truncate nav-user-text">
          <span class="block text-xs font-bold text-white truncate"><?= esc($adminUser['name'] ?? 'Administrator') ?></span>
          <span class="block text-[10px] text-brand-accent truncate font-medium">
            <?php
              $roleTitles = [];
              if (!empty($adminUser['roles'])) {
                  foreach ($adminUser['roles'] as $r) {
                      $roleTitles[] = $r['name'] ?? $r['slug'] ?? 'Staff';
                  }
              }
              echo esc(!empty($roleTitles) ? implode(', ', $roleTitles) : 'Staff');
            ?>
          </span>
        </div>
      </div>
      <a href="<?= base_url('admin/logout') ?>" title="Sign Out" class="p-2 rounded-lg text-white/60 hover:text-red-400 hover:bg-white/5 transition shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>

  </aside>

  <!-- Main Content Wrapper -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

    <!-- Top Navigation Bar (Mobile-Hardened Responsive Header) -->
    <header class="h-16 bg-white border-b border-slate-200 px-3 sm:px-6 flex items-center justify-between shrink-0 shadow-sm relative z-[9999]">
      <div class="flex items-center gap-2 sm:gap-4 min-w-0">
        <button id="open-mobile-sidebar" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 shrink-0" aria-label="Open Navigation Menu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button id="sidebar-collapse-toggle" type="button" class="hidden md:inline-flex p-2 rounded-lg text-slate-500 hover:bg-slate-100 shrink-0" aria-label="Collapse or expand sidebar" aria-pressed="false" title="Collapse / expand sidebar"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg></button>
        <div class="flex items-center gap-1.5 sm:gap-2 text-xs font-semibold text-slate-500 truncate">
          <span class="hidden sm:inline text-brand-primary font-bold">Admin Portal</span>
          <span class="hidden sm:inline">/</span>
          <span class="text-slate-800 capitalize font-bold truncate max-w-[120px] sm:max-w-none"><?= esc(str_replace(['_', '-'], ' ', $activeMenu)) ?></span>
        </div>
      </div>

      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <?php if ($isStaging): ?>
        <!-- Staging Control Centre Indicator Button -->
        <button type="button" onclick="document.getElementById('staging-control-modal').classList.remove('hidden')" class="flex items-center gap-1.5 sm:gap-2 px-2.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 border border-amber-400/50 rounded-lg text-xs font-bold tracking-tight transition cursor-pointer shadow-sm">
          <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
          <span>STAGING ENVIRONMENT</span>
          <span class="hidden lg:inline text-[10px] text-amber-800 font-medium">| DB: <?= esc($stagingTelemetry['database']['name'] ?? 'staging') ?> | SANDBOX ACTIVE</span>
        </button>
        <?php endif; ?>
        <!-- PWA Install Admin App Button (Shown when browser supports installation) -->
        <button id="pwa-install-btn" type="button" class="hidden items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-brand-primary hover:bg-brand-hover shadow-sm transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span class="hidden sm:inline">Install TalentHub</span>
          <span class="sm:hidden">Install</span>
        </button>

        <!-- Live Website Link -->
        <a href="<?= base_url() ?>" target="_blank" class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition" title="View Live Site">
          <span class="hidden sm:inline">View Live Site</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>

        <!-- PWA / Recruitment Alert Notification Bell -->
        <div class="relative z-[10000]">
          <button id="admin-notify-bell-btn" type="button" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Recruitment Alerts">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span id="admin-bell-badge" class="hidden absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
          </button>

          <!-- Notification Dropdown Panel (Responsive Width for Mobile Viewport) -->
          <div id="admin-notify-dropdown" style="z-index: 100000 !important; background-color: #ffffff !important;" class="hidden fixed sm:absolute inset-x-2 sm:inset-x-auto right-auto sm:right-0 top-16 sm:top-auto sm:mt-2 w-auto sm:w-96 max-w-sm sm:max-w-none bg-white text-slate-800 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.35)] border border-slate-200 overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-xs text-slate-800 uppercase tracking-wider">Recruitment Alerts</span>
                <span id="admin-alert-count-pill" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-primary text-white">0</span>
              </div>
              <button id="btn-request-pwa-push" type="button" class="text-[10px] font-bold text-brand-primary hover:underline flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span id="pwa-push-btn-text">Enable Push Alerts</span>
              </button>
            </div>
            <div id="admin-alerts-list" class="max-h-72 overflow-y-auto divide-y divide-slate-100 p-2 text-xs">
              <div class="p-4 text-center text-slate-400">Loading alerts...</div>
            </div>
            <div class="p-2.5 border-t border-slate-100 bg-slate-50 text-center">
              <a href="<?= base_url('admin/engagement/leads') ?>" class="text-[11px] font-bold text-brand-primary hover:underline">
                View All Leads &amp; Datelines &rarr;
              </a>
            </div>
          </div>
        </div>

        <!-- Mail sync indicator: reflects the real mail:sync heartbeat, never a fixed "Active" -->
        <?php
          try {
              $__ms = (new \App\Services\SystemHealthService())->schedulerJob('mail:sync');
          } catch (\Throwable $e) {
              $__ms = ['status' => 'not_verified'];
          }
          $__msMap = [
              'healthy'  => ['Mail sync: running',      'bg-emerald-50 text-emerald-800 border-emerald-200/60', 'bg-emerald-500'],
              'warning'  => ['Mail sync: needs attention', 'bg-amber-50 text-amber-800 border-amber-200/60', 'bg-amber-500'],
              'failed'   => ['Mail sync: stopped',      'bg-red-50 text-red-800 border-red-200/60', 'bg-red-500'],
          ];
          [$__msTxt, $__msCls, $__msDot] = $__msMap[$__ms['status']] ?? ['Mail sync: not verified', 'bg-sky-50 text-sky-800 border-sky-200/60', 'bg-sky-500'];
          $__msCan = $canAccess('system.view');
        ?>
        <<?= $__msCan ? 'a href="' . base_url('admin/system/scheduler') . '"' : 'div' ?> class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs border <?= $__msCls ?>"<?= $__msCan ? ' title="Open Scheduler & Cron"' : '' ?>>
          <span class="w-2 h-2 rounded-full <?= $__msDot ?>"></span>
          <span class="font-semibold"><?= esc($__msTxt) ?></span>
        </<?= $__msCan ? 'a' : 'div' ?>>
      </div>
    </header>

    <!-- Page Body Container (Zero Horizontal Scroll Guarantee) -->
    <main class="flex-1 overflow-y-auto overflow-x-hidden p-3.5 sm:p-6 md:p-8 bg-slate-50/70 relative z-0">

      <!-- Session Flash Messages -->
      <?php if ($flashSuccess = session()->getFlashdata('success')): ?>
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="font-medium"><?= esc(is_array($flashSuccess) ? implode(', ', $flashSuccess) : $flashSuccess) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="p-1 rounded-lg text-emerald-600 hover:bg-emerald-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      <?php endif; ?>

      <?php if ($flashError = session()->getFlashdata('error')): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-red-500 text-white flex items-center justify-center shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <span class="font-medium"><?= esc(is_array($flashError) ? implode(', ', $flashError) : $flashError) ?></span>
          </div>
          <button onclick="this.parentElement.remove()" class="p-1 rounded-lg text-red-600 hover:bg-red-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      <?php endif; ?>

      <!-- Dynamic Server-Rendered Content -->
      <?= (isset($content) && is_string($content) && !empty($content)) ? $content : ($this->renderSection('content') ?? '') ?>

    </main>

  </div>

  <!-- Mobile Drawer Toggle Script -->
  <script>
    const mobileBackdrop = document.getElementById('mobile-sidebar-backdrop');
    const adminSidebar = document.getElementById('admin-sidebar');
    const openBtn = document.getElementById('open-mobile-sidebar');
    const closeBtn = document.getElementById('close-mobile-sidebar');

    function openSidebar() {
      adminSidebar.classList.remove('-translate-x-full');
      mobileBackdrop.classList.remove('hidden');
    }

    function closeSidebar() {
      adminSidebar.classList.add('-translate-x-full');
      mobileBackdrop.classList.add('hidden');
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeSidebar);
  </script>

  <!-- PWA Service Worker Registration & Desktop/Mobile Install Handler -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url('sw.js') ?>')
          .then(reg => console.debug('TalentHub Admin PWA ServiceWorker registered', reg.scope))
          .catch(err => console.debug('ServiceWorker notice:', err));
      });
    }

    let deferredInstallPrompt = null;
    const pwaInstallBtn = document.getElementById('pwa-install-btn');

    window.addEventListener('beforeinstallprompt', (e) => {
      // Prevent browser's default mini-infobar
      e.preventDefault();
      deferredInstallPrompt = e;
      if (pwaInstallBtn) {
        pwaInstallBtn.classList.remove('hidden');
        pwaInstallBtn.classList.add('inline-flex');
      }
    });

    if (pwaInstallBtn) {
      pwaInstallBtn.addEventListener('click', async () => {
        if (!deferredInstallPrompt) return;
        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;
        if (outcome === 'accepted') {
          pwaInstallBtn.classList.add('hidden');
          pwaInstallBtn.classList.remove('inline-flex');
        }
        deferredInstallPrompt = null;
      });
    }

    window.addEventListener('appinstalled', () => {
      console.log('TalentHub PWA was installed successfully');
      if (pwaInstallBtn) {
        pwaInstallBtn.classList.add('hidden');
        pwaInstallBtn.classList.remove('inline-flex');
      }
    });
  </script>
  <!-- Floating Real-Time PWA Notification Popup Banner (Mobile-Optimized Viewport Anchoring) -->
  <div id="admin-pwa-popup-toast" class="fixed bottom-4 right-4 left-4 sm:left-auto sm:bottom-6 sm:right-6 sm:w-96 max-w-sm bg-slate-900/95 backdrop-blur-md text-white p-4 rounded-2xl shadow-2xl border border-white/10 hidden transform transition-all duration-300 pointer-events-auto z-[99999]">
    <div class="flex items-start gap-3">
      <div id="pwa-toast-icon" class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
          <h4 id="pwa-toast-title" class="text-xs font-black text-white truncate">Recruitment Alert</h4>
          <button type="button" onclick="closeAdminToast()" class="text-white/40 hover:text-white p-0.5 text-xs" aria-label="Close Notification">&times;</button>
        </div>
        <p id="pwa-toast-msg" class="text-[11px] text-slate-300 mt-1 leading-snug">
          Alert message text...
        </p>
        <div class="mt-2.5 flex items-center justify-between pt-1 border-t border-white/10">
          <a id="pwa-toast-link" href="#" class="text-[10px] font-bold text-[#37CE97] hover:underline">
            Take Action &rarr;
          </a>
          <span class="text-[9px] text-white/40">TalentHub PWA</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Real-Time Notification Poller & Mobile ServiceWorker Push Script -->
  <script>
    (function() {
      const bellBtn = document.getElementById('admin-notify-bell-btn');
      const dropdown = document.getElementById('admin-notify-dropdown');
      const badge = document.getElementById('admin-bell-badge');
      const countPill = document.getElementById('admin-alert-count-pill');
      const alertsList = document.getElementById('admin-alerts-list');
      const reqPushBtn = document.getElementById('btn-request-pwa-push');

      // Dropdown toggle
      if (bellBtn && dropdown) {
        bellBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          dropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
          if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdown.classList.add('hidden');
          }
        });
      }

      // Native Push Permission Request (Mobile & Desktop)
      if (reqPushBtn) {
        if ('Notification' in window && Notification.permission === 'granted') {
          reqPushBtn.innerHTML = '<span class="text-emerald-600 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Push Active</span>';
        }

        reqPushBtn.addEventListener('click', async () => {
          if (!('Notification' in window)) {
            alert('This browser does not support Web Push notifications.');
            return;
          }
          try {
            const perm = await Notification.requestPermission();
            if (perm === 'granted') {
              reqPushBtn.innerHTML = '<span class="text-emerald-600 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Push Active</span>';
              showNativePush('TalentHub: Push Alerts Enabled', 'You will receive instant alerts whenever new employer enquiries, applications, or contact requests arrive.', '<?= base_url('admin') ?>', 'welcome');
            }
          } catch (e) {
            console.debug('Permission request error:', e);
          }
        });
      }

      // Universal Mobile & Desktop Push Notification Dispatcher
      async function showNativePush(title, body, url, tag) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
          return;
        }

        const options = {
          body: body,
          icon: '<?= base_url('favicon-192x192.png') ?>',
          badge: '<?= base_url('favicon-96x96.png') ?>',
          vibrate: [200, 100, 200],
          tag: tag || 'talenthub-' + Date.now(),
          renotify: true,
          data: { url: url || '<?= base_url('admin') ?>' }
        };

        // 1. Mobile Priority: Chrome on Android & iOS Safari 16.4+ (PWA Mode)
        // Mobile browsers require ServiceWorkerRegistration.showNotification()
        if ('serviceWorker' in navigator) {
          try {
            const reg = await navigator.serviceWorker.ready;
            if (reg && typeof reg.showNotification === 'function') {
              await reg.showNotification(title, options);
              return;
            }
          } catch (swErr) {
            console.debug('ServiceWorker showNotification failed:', swErr);
          }
        }

        // 2. Desktop Fallback: new Notification()
        try {
          const notif = new Notification(title, options);
          if (url) {
            notif.onclick = () => { window.focus(); window.location.href = url; };
          }
        } catch (notifErr) {
          console.debug('Notification constructor failed:', notifErr);
        }
      }

      window.closeAdminToast = function() {
        const toast = document.getElementById('admin-pwa-popup-toast');
        if (toast) toast.classList.add('hidden');
      };

      function triggerToast(title, msg, url, type) {
        const toast = document.getElementById('admin-pwa-popup-toast');
        if (!toast) return;

        document.getElementById('pwa-toast-title').innerText = title;
        document.getElementById('pwa-toast-msg').innerText = msg;
        const link = document.getElementById('pwa-toast-link');
        link.href = url || '#';

        toast.classList.remove('hidden');
        setTimeout(() => {
          toast.classList.add('hidden');
        }, 10000);
      }

      // Tracking state across real-time polling intervals
      let lastEmployerCount = -1;
      let lastContactCount = -1;
      let lastAppCount = -1;
      let lastOverdueCount = -1;

      async function pollAdminAlerts() {
        try {
          const res = await fetch('<?= base_url('admin/dashboard/notifications') ?>');
          const json = await res.json();

          if (json.status === 'success' && json.data) {
            const data = json.data;
            const alerts = data.alerts || [];

            if (countPill) countPill.innerText = alerts.length;
            if (badge) {
              if (alerts.length > 0) {
                badge.classList.remove('hidden');
              } else {
                badge.classList.add('hidden');
              }
            }

            if (alertsList) {
              if (alerts.length === 0) {
                alertsList.innerHTML = '<div class="p-4 text-center text-slate-400">All follow-ups and inbound applications up to date.</div>';
              } else {
                alertsList.innerHTML = alerts.map(a => `
                  <a href="${a.url}" class="block p-3 hover:bg-slate-50 transition rounded-xl">
                    <div class="flex items-center justify-between mb-1">
                      <span class="font-bold text-slate-800 text-[11px]">${a.title}</span>
                      <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold">${a.time}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">${a.message}</p>
                  </a>
                `).join('');
              }
            }

            // 1. Alert for New Employer Inquiries / Hiring Mandates
            if (lastEmployerCount !== -1 && data.new_employers_today > lastEmployerCount) {
              const diff = data.new_employers_today - lastEmployerCount;
              const title = diff > 1 ? `${diff} New Employer Mandates` : 'New Employer Mandate Received';
              const msg = `Corporate hiring requirement registered on TalentHub.`;
              triggerToast(title, msg, '<?= base_url('admin/enquiries/employers') ?>', 'success');
              showNativePush('TalentHub: ' + title, msg, '<?= base_url('admin/enquiries/employers') ?>', 'emp-mandate');
            }
            lastEmployerCount = data.new_employers_today;

            // 2. Alert for New Contact Desk Enquiries
            if (lastContactCount !== -1 && data.new_contacts_today > lastContactCount) {
              const diff = data.new_contacts_today - lastContactCount;
              const title = diff > 1 ? `${diff} New Contact Messages` : 'New Contact Inquiry Received';
              const msg = `New inbound message submitted via public contact desk.`;
              triggerToast(title, msg, '<?= base_url('admin/enquiries/contact') ?>', 'info');
              showNativePush('TalentHub: ' + title, msg, '<?= base_url('admin/enquiries/contact') ?>', 'contact-msg');
            }
            lastContactCount = data.new_contacts_today;

            // 3. Alert for New Candidate Job Applications
            if (lastAppCount !== -1 && data.new_apps_today > lastAppCount) {
              const diff = data.new_apps_today - lastAppCount;
              const title = diff > 1 ? `${diff} New Candidate Applications` : 'New Candidate Application';
              const msg = `New candidate applied to live vacancies on TalentHub.`;
              triggerToast(title, msg, '<?= base_url('admin/applications') ?>', 'info');
              showNativePush('TalentHub: ' + title, msg, '<?= base_url('admin/applications') ?>', 'cand-app');
            }
            lastAppCount = data.new_apps_today;

            // 4. Alert for Overdue Datelines
            if (lastOverdueCount !== -1 && data.overdue_count > lastOverdueCount && data.overdue_count > 0) {
              const title = 'Overdue Follow-up Alert';
              const msg = `You have ${data.overdue_count} enquiry follow-up dateline(s) requiring attention.`;
              triggerToast(title, msg, '<?= base_url('admin/engagement/leads') ?>', 'danger');
              showNativePush('TalentHub: ' + title, msg, '<?= base_url('admin/engagement/leads') ?>', 'overdue-alert');
            }
            lastOverdueCount = data.overdue_count;
          }
        } catch (e) {
          // silently keep going
        }
      }

      // Initial poll immediately
      pollAdminAlerts();
      // Recurring poll every 25s for prompt notifications
      setInterval(pollAdminAlerts, 25000);

      // Re-poll immediately when app or tab is brought to foreground (Mobile UX)
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          pollAdminAlerts();
        }
      });
    })();
  </script>
  <?php if ($isStaging && !empty($stagingTelemetry)): ?>
  <!-- Staging Control Centre & Isolation Diagnostics Modal -->
  <div id="staging-control-modal" class="fixed inset-0 z-[10000] hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
      <!-- Modal Header -->
      <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950">
        <div class="flex items-center gap-2.5">
          <span class="w-3 h-3 rounded-full bg-red-600 animate-pulse"></span>
          <div>
            <h3 class="text-sm font-extrabold tracking-wide uppercase">Staging Control Centre &amp; Telemetry</h3>
            <p class="text-[11px] text-slate-900/80 font-medium">Isolated Testing Environment &bull; Production Untouched</p>
          </div>
        </div>
        <button type="button" onclick="document.getElementById('staging-control-modal').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-950/70 hover:text-slate-950 hover:bg-black/10 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <!-- Modal Body -->
      <div class="p-6 space-y-4 text-xs text-slate-700 max-h-[75vh] overflow-y-auto">
        <!-- Environment Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Environment</div>
            <div class="text-sm font-bold text-amber-600 uppercase mt-0.5"><?= esc($stagingTelemetry['environment']) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Base URL: <?= esc($stagingTelemetry['app_url']) ?></div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Database Isolation</div>
            <div class="text-sm font-bold text-emerald-600 mt-0.5"><?= esc($stagingTelemetry['database']['name']) ?></div>
            <div class="text-[10px] text-slate-500 mt-1">Host: <?= esc($stagingTelemetry['database']['host']) ?> (<?= esc($stagingTelemetry['database']['status']) ?>)</div>
          </div>
        </div>
        <!-- Security & Sandbox Protection List -->
        <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80 space-y-2">
          <div class="font-bold text-amber-900 text-xs">Environment Isolation Guarantees:</div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>Email Sandbox:</strong> Intercepted (Simulated only)</span>
            </div>
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>SEO Shield:</strong> Noindex, nofollow, nosnippet</span>
            </div>
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>Robots:</strong> Disallow: / &bull; Sitemaps 404</span>
            </div>
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>PWA Cache:</strong> Partitioned staging scope</span>
            </div>
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>Analytics:</strong> Suppressed (No GA4 / GTM pollution)</span>
            </div>
            <div class="flex items-center gap-2 text-slate-800">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <span><strong>Sessions &amp; Cookies:</strong> Separate cookie prefix</span>
            </div>
          </div>
        </div>
        <!-- Active Staging Records Summary -->
        <div>
          <div class="font-bold text-slate-800 mb-2">Staging Database Record Counts:</div>
          <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 text-center">
            <?php foreach (($stagingTelemetry['database']['counts'] ?? []) as $tbl => $cnt): ?>
            <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
              <div class="text-base font-extrabold text-slate-800"><?= esc($cnt) ?></div>
              <div class="text-[10px] text-slate-400 capitalize truncate"><?= esc(str_replace('_', ' ', $tbl)) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- CLI Command Quick Reference -->
        <div class="p-3 rounded-xl bg-slate-900 text-slate-300 font-mono text-[11px] space-y-1">
          <div class="text-slate-400 font-sans text-[10px] uppercase font-bold tracking-wider">Spark CLI Staging Tools:</div>
          <div><span class="text-amber-400">php spark staging:status</span> &mdash; Full telemetry check</div>
          <div><span class="text-amber-400">php spark staging:seed-test-data</span> &mdash; Seed dummy records</div>
          <div><span class="text-amber-400">php spark staging:reset</span> &mdash; Reset staging test data</div>
          <div><span class="text-amber-400">php spark staging:provision-admin</span> &mdash; Rotate admin credentials</div>
        </div>
      </div>
      <!-- Modal Footer -->
      <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
        <span class="text-[11px] text-slate-400">Git Branch: <?= esc($stagingTelemetry['git']['branch']) ?></span>
        <button type="button" onclick="document.getElementById('staging-control-modal').classList.add('hidden')" class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 text-white hover:bg-slate-700 transition">Close Diagnostic Panel</button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</body>
</html>

