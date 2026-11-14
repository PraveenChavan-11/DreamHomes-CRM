<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header & Actions -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight"><?= esc($title) ?></h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Manage, review, reschedule, and monitor all social media content.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/social/calendar') ?>" class="px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
        Calendar View
      </a>
      <a href="<?= base_url('admin/social/posts/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-xs font-bold hover:bg-brand-hover transition shadow-sm shadow-brand-primary/20">
        <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Create Post</span>
      </a>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-2">
      <a href="<?= base_url('admin/social/posts') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= empty($filterStatus) ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">All</a>
      <a href="<?= base_url('admin/social/posts?status=published') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($filterStatus === 'published') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Published</a>
      <a href="<?= base_url('admin/social/posts?status=scheduled') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($filterStatus === 'scheduled') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Scheduled</a>
      <a href="<?= base_url('admin/social/posts?status=draft') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($filterStatus === 'draft') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Drafts</a>
      <a href="<?= base_url('admin/social/posts?status=failed') ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold transition <?= ($filterStatus === 'failed') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">Failed</a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="<?= base_url('admin/social/posts') ?>" class="flex items-center gap-2">
      <?php if (!empty($filterStatus)): ?>
        <input type="hidden" name="status" value="<?= esc($filterStatus) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= esc($searchQuery ?? '') ?>" placeholder="Search posts..." class="text-xs rounded-xl border border-slate-300 px-3 py-1.5 w-64 focus:ring-2 focus:ring-brand-accent">
      <button type="submit" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-200 transition">Search</button>
    </form>
  </div>

  <!-- Posts Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="px-5 py-3.5">Post Details</th>
            <th class="px-4 py-3.5">Platform</th>
            <th class="px-4 py-3.5">Scheduled / Published</th>
            <th class="px-4 py-3.5">Status</th>
            <th class="px-5 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($posts)): ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-400">
                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                <span class="block font-medium">No posts found in this view.</span>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($posts as $p): ?>
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-4 max-w-sm">
                  <a href="<?= base_url('admin/social/posts/' . $p['id']) ?>" class="font-bold text-slate-900 hover:text-brand-primary block text-sm leading-snug">
                    <?= esc($p['title']) ?>
                  </a>
                  <?php if (($p['origin'] ?? 'admin') === 'agent'): ?><span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-violet-50 text-violet-800 border border-violet-200">AI agent<?= ($p['review_decision'] ?? '') === 'approved' ? ' · approved' : ' · needs approval' ?></span><?php endif; ?>
                  <p class="text-slate-500 text-xs mt-1 line-clamp-2">
                    <?= esc($p['base_content']) ?>
                  </p>
                </td>
                <td class="px-4 py-4">
                  <?php if ($p['primary_platform'] === 'both'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Both (LI+IG)</span>
                  <?php elseif ($p['primary_platform'] === 'linkedin'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">LinkedIn</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700 border border-pink-200">Instagram</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 text-slate-600 font-medium whitespace-nowrap">
                  <?php if ($p['status'] === 'published' && !empty($p['published_at'])): ?>
                    <span class="block text-slate-800 font-semibold"><?= esc(date('M d, Y', strtotime($p['published_at']))) ?></span>
                    <span class="block text-[11px] text-slate-400"><?= esc(date('h:i A', strtotime($p['published_at']))) ?></span>
                  <?php elseif ($p['status'] === 'scheduled' && !empty($p['scheduled_at'])): ?>
                    <span class="block text-amber-700 font-semibold"><?= esc(date('M d, Y', strtotime($p['scheduled_at']))) ?></span>
                    <span class="block text-[11px] text-amber-500"><?= esc(date('h:i A', strtotime($p['scheduled_at']))) ?></span>
                  <?php else: ?>
                    <span class="text-slate-400">Created <?= esc(date('M d', strtotime($p['created_at']))) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <?php if ($p['status'] === 'published'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Published</span>
                  <?php elseif ($p['status'] === 'scheduled'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Scheduled</span>
                  <?php elseif ($p['status'] === 'processing'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 animate-pulse">Publishing...</span>
                  <?php elseif ($p['status'] === 'failed'): ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Failed</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Draft</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-right whitespace-nowrap space-x-2">
                  <a href="<?= base_url('admin/social/posts/' . $p['id']) ?>" class="text-slate-600 hover:text-brand-primary font-bold">Details</a>

                  <?php if ($p['status'] !== 'published'): ?>
                    <form method="POST" action="<?= base_url('admin/social/posts/' . $p['id'] . '/publish') ?>" class="inline" onsubmit="return confirm('Publish this post immediately across selected platforms?');">
                      <?= csrf_field() ?>
                      <button type="submit" class="text-xs font-bold text-brand-primary hover:underline">Publish Now</button>
                    </form>
                  <?php endif; ?>

                  <form method="POST" action="<?= base_url('admin/social/posts/' . $p['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('Permanently remove this post record?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

