<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Connected Social Accounts</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Manage OAuth authorizations, long-lived token validities, and platform access grants.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/social/settings') ?>" class="px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
        API App Settings
      </a>
    </div>
  </div>

  <!-- Accounts Cards Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- 1. LinkedIn Card -->
    <?php
      $li = null;
      $ig = null;
      foreach ($accounts as $a) {
          if ($a['platform'] === 'linkedin') $li = $a;
          if ($a['platform'] === 'instagram') $ig = $a;
      }
    ?>
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5 flex flex-col justify-between">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-[#0A66C2]">
              <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-base">LinkedIn Company Page</h3>
              <span class="text-xs text-slate-400">Official Organization Publishing</span>
            </div>
          </div>

          <div>
            <?php if ($li && $li['status'] === 'active'): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                Not Connected
              </span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($li): ?>
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Organization Name:</span>
              <span class="font-bold text-slate-800"><?= esc($li['account_name']) ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Organization URN ID:</span>
              <span class="font-mono text-slate-700"><?= esc($li['external_id']) ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Last Verified:</span>
              <span class="text-slate-700"><?= esc($li['last_verified_at'] ? date('M d, Y', strtotime($li['last_verified_at'])) : '—') ?></span>
            </div>
          </div>
        <?php else: ?>
          <p class="text-xs text-slate-500 leading-relaxed">
            Connect your registered LinkedIn Organization page using OAuth 2.0 to enable direct publishing, PDF document sharing, and organizational telemetry.
          </p>
        <?php endif; ?>
      </div>

      <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <?php if ($li && $li['status'] === 'active'): ?>
          <form method="POST" action="<?= base_url('admin/social/oauth/disconnect/' . $li['id']) ?>" onsubmit="return confirm('Disconnect this LinkedIn account? Stored tokens will be revoked.');">
            <?= csrf_field() ?>
            <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700">Disconnect</button>
          </form>
          <a href="<?= base_url('admin/social/oauth/connect/linkedin') ?>" class="px-4 py-2 bg-blue-50 text-[#0A66C2] rounded-xl text-xs font-bold hover:bg-blue-100 transition">
            Reconnect / Refresh
          </a>
        <?php else: ?>
          <span></span>
          <a href="<?= base_url('admin/social/oauth/connect/linkedin') ?>" class="px-5 py-2.5 bg-[#0A66C2] text-white rounded-xl text-xs font-extrabold hover:bg-blue-700 transition shadow-sm">
            Authorize LinkedIn &rarr;
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2. Instagram Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5 flex flex-col justify-between">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-pink-50 border border-pink-200 flex items-center justify-center text-[#E4405F]">
              <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-base">Instagram Professional</h3>
              <span class="text-xs text-slate-400">Content Publishing Graph API</span>
            </div>
          </div>

          <div>
            <?php if ($ig && $ig['status'] === 'active'): ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500">
                Not Connected
              </span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($ig): ?>
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Account:</span>
              <span class="font-bold text-slate-800"><?= esc($ig['account_name']) ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Instagram Account ID:</span>
              <span class="font-mono text-slate-700"><?= esc($ig['external_id']) ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500 font-medium">Linked Facebook Page ID:</span>
              <span class="font-mono text-slate-700"><?= esc($ig['secondary_external_id'] ?? '—') ?></span>
            </div>
          </div>
        <?php else: ?>
          <p class="text-xs text-slate-500 leading-relaxed">
            Connect your Instagram Business/Creator account via Meta Login to enable single image, carousel, and video publishing directly from TalentHub.
          </p>
        <?php endif; ?>
      </div>

      <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <?php if ($ig && $ig['status'] === 'active'): ?>
          <form method="POST" action="<?= base_url('admin/social/oauth/disconnect/' . $ig['id']) ?>" onsubmit="return confirm('Disconnect this Instagram account?');">
            <?= csrf_field() ?>
            <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700">Disconnect</button>
          </form>
          <a href="<?= base_url('admin/social/oauth/connect/instagram') ?>" class="px-4 py-2 bg-pink-50 text-[#E4405F] rounded-xl text-xs font-bold hover:bg-pink-100 transition">
            Reconnect / Refresh
          </a>
        <?php else: ?>
          <span></span>
          <a href="<?= base_url('admin/social/oauth/connect/instagram') ?>" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-pink-600 text-white rounded-xl text-xs font-extrabold hover:opacity-95 transition shadow-sm">
            Authorize Instagram &rarr;
          </a>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>
<?= $this->endSection() ?>

