<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-8 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">API Integration Settings &amp; Security</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Configure developer application credentials, OAuth redirect URIs, and verify system compliance.</p>
    </div>

    <!-- Health Badge -->
    <div class="flex items-center gap-2">
      <?php if ($healthCheck['overall_status'] === 'pass'): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Security Status: All Checks Passed
        </span>
      <?php elseif ($healthCheck['overall_status'] === 'warning'): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
          <span class="w-2 h-2 rounded-full bg-amber-500"></span> Security Status: Attention Recommended
        </span>
      <?php else: ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-800 border border-rose-200">
          <span class="w-2 h-2 rounded-full bg-rose-500"></span> Security Status: Action Required
        </span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Security & Health Checker Card -->
  <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-extrabold text-slate-900">System Integration Health Checker</h2>
        <p class="text-xs text-slate-500">Automated diagnostic verification of cryptographic keys, directories, and background schedulers.</p>
      </div>
      <span class="text-xs text-slate-400 font-mono"><?= esc($healthCheck['timestamp']) ?></span>
    </div>

    <div class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden">
      <?php foreach ($healthCheck['checks'] as $key => $check): ?>
        <div class="p-3.5 flex items-start justify-between gap-4 text-xs bg-slate-50/30">
          <div class="space-y-0.5">
            <span class="font-bold text-slate-900 block"><?= esc($check['name']) ?></span>
            <span class="text-slate-600 block"><?= esc($check['message']) ?></span>
            <?php if (!empty($check['remediation'])): ?>
              <span class="text-[11px] text-amber-600 font-medium block">Fix: <?= esc($check['remediation']) ?></span>
            <?php endif; ?>
          </div>

          <div>
            <?php if ($check['status'] === 'pass'): ?>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800">Pass</span>
            <?php elseif ($check['status'] === 'warning'): ?>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-100 text-amber-800">Warning</span>
            <?php elseif ($check['status'] === 'info'): ?>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-blue-100 text-blue-800">Info</span>
            <?php else: ?>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-100 text-rose-800">Fail</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Credentials Forms Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- 1. LinkedIn API Settings -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 flex items-center justify-center text-[#0A66C2]">
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
        </div>
        <div>
          <h3 class="font-extrabold text-slate-900 text-base">LinkedIn Developer Application</h3>
          <span class="text-xs text-slate-400">OAuth 2.0 Client Credentials</span>
        </div>
      </div>

      <form method="POST" action="<?= base_url('admin/social/settings') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="platform" value="linkedin">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Client ID</label>
          <input type="text" name="client_id" value="<?= esc($linkedInConfig['client_id']) ?>" placeholder="e.g. 78xxxxxxxxxxxx" class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Client Secret (Encrypted At Rest)</label>
          <input type="password" name="client_secret" value="<?= esc($linkedInConfig['client_secret_masked']) ?>" placeholder="Enter new secret to update..." class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
          <span class="text-[10px] text-slate-400 block mt-1">Secret is AES-256 encrypted. Never revealed in logs or API responses.</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Approved API Version</label>
          <input type="text" name="api_version" value="<?= esc($linkedInConfig['api_version']) ?>" class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
        </div>

        <!-- Redirect URI Display -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
          <span class="text-[11px] font-bold text-slate-600 block mb-1">Authorized Redirect URI (Copy to LinkedIn Console):</span>
          <span class="text-[11px] font-mono text-[#0A66C2] select-all block break-all"><?= esc($linkedInConfig['redirect_uri']) ?></span>
        </div>

        <button type="submit" class="w-full py-2.5 bg-brand-primary text-white text-xs font-extrabold rounded-xl hover:bg-brand-hover transition shadow-sm">
          Save LinkedIn Credentials
        </button>
      </form>
    </div>

    <!-- 2. Meta / Instagram API Settings -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-pink-50 border border-pink-200 flex items-center justify-center text-[#E4405F]">
          <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </div>
        <div>
          <h3 class="font-extrabold text-slate-900 text-base">Meta / Instagram App</h3>
          <span class="text-xs text-slate-400">Graph API Content Publishing</span>
        </div>
      </div>

      <form method="POST" action="<?= base_url('admin/social/settings') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="platform" value="instagram">

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta App ID</label>
          <input type="text" name="app_id" value="<?= esc($instagramConfig['app_id']) ?>" placeholder="e.g. 15xxxxxxxxxxxx" class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta App Secret (Encrypted At Rest)</label>
          <input type="password" name="app_secret" value="<?= esc($instagramConfig['app_secret_masked']) ?>" placeholder="Enter new secret to update..." class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
          <span class="text-[10px] text-slate-400 block mt-1">Secret is AES-256 encrypted at rest.</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Graph API Version</label>
          <input type="text" name="api_version" value="<?= esc($instagramConfig['api_version']) ?>" class="w-full text-xs font-mono rounded-xl border border-slate-300 px-3 py-2.5">
        </div>

        <!-- Redirect URI Display -->
        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
          <span class="text-[11px] font-bold text-slate-600 block mb-1">Valid OAuth Redirect URI (Copy to Meta Login):</span>
          <span class="text-[11px] font-mono text-[#E4405F] select-all block break-all"><?= esc($instagramConfig['redirect_uri']) ?></span>
        </div>

        <button type="submit" class="w-full py-2.5 bg-brand-primary text-white text-xs font-extrabold rounded-xl hover:bg-brand-hover transition shadow-sm">
          Save Meta Credentials
        </button>
      </form>
    </div>

  </div>

</div>
<?= $this->endSection() ?>

