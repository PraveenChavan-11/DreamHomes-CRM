<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Marketing Campaigns</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Organize organic marketing initiatives, product launches, and recruitment drives.</p>
    </div>

    <a href="<?= base_url('admin/social/campaigns/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-xs font-bold hover:bg-brand-hover transition shadow-sm">
      <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>New Campaign</span>
    </a>
  </div>

  <!-- Campaigns Grid -->
  <?php if (empty($campaigns)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
      <h3 class="text-sm font-bold text-slate-800">No active marketing campaigns</h3>
      <p class="text-xs text-slate-400 mt-1">Create your first organic campaign to group posts and measure attribution.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($campaigns as $camp): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-brand-light text-brand-primary border border-brand-accent/30">
                <?= esc(str_replace('_', ' ', $camp['objective'])) ?>
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= ($camp['status'] === 'active') ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                <?= esc(ucfirst($camp['status'])) ?>
              </span>
            </div>

            <h3 class="font-bold text-slate-900 text-base"><?= esc($camp['name']) ?></h3>
            <p class="text-xs text-slate-500 mt-1 line-clamp-3"><?= esc($camp['description'] ?? 'No description.') ?></p>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-400">Target: <?= esc(ucfirst($camp['platform'])) ?></span>
            <a href="<?= base_url('admin/social/campaigns/' . $camp['id'] . '/edit') ?>" class="font-bold text-brand-primary hover:underline">Edit Campaign &rarr;</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
<?= $this->endSection() ?>

