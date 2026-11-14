<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">Articles &amp; Industry Insights</h1>
      <p class="text-xs text-slate-500 mt-1">
        Manage Pune hiring salary trends, regional reports, and executive commentary published on <a href="<?= base_url('insights') ?>" target="_blank" class="text-brand-primary underline">/insights</a>.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/content/articles/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold shadow-md shadow-brand-primary/20 hover:bg-brand-hover transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Article</span>
      </a>
    </div>
  </div>

  <!-- Articles Table -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-100">
            <th class="py-3 px-6">Article Title &amp; Category</th>
            <th class="py-3 px-6">Status</th>
            <th class="py-3 px-6">Published Date</th>
            <th class="py-3 px-6 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $a): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="py-4 px-6">
                  <div class="font-bold text-slate-800 text-sm mb-0.5">
                    <a href="<?= base_url('insights/' . $a['slug']) ?>" target="_blank" class="hover:text-brand-primary transition">
                      <?= esc($a['title']) ?>
                    </a>
                  </div>
                  <div class="flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold">
                      <?= esc($a['category_name'] ?: 'General') ?>
                    </span>
                    <span>•</span>
                    <span class="truncate max-w-xs"><?= esc($a['summary'] ?: 'No summary') ?></span>
                  </div>
                </td>

                <td class="py-4 px-6">
                  <?php if (!empty($a['is_published'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                      Published
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                      Draft
                    </span>
                  <?php endif; ?>
                </td>

                <td class="py-4 px-6 text-slate-500">
                  <?= !empty($a['published_at']) ? date('d M Y, h:i A', strtotime($a['published_at'])) : '—' ?>
                </td>

                <td class="py-4 px-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <form action="<?= base_url('admin/content/articles/toggle/' . $a['id']) ?>" method="POST" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition text-[11px]">
                        <?= !empty($a['is_published']) ? 'Unpublish' : 'Publish' ?>
                      </button>
                    </form>

                    <a href="<?= base_url('admin/content/articles/edit/' . $a['id']) ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-800 font-bold hover:bg-slate-200 transition text-[11px]">
                      Edit
                    </a>

                    <form action="<?= base_url('admin/content/articles/delete/' . $a['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Permanently delete this article?')">
                      <?= csrf_field() ?>
                      <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 transition text-[11px] font-bold">
                        Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="py-12 text-center text-slate-400">
                No articles published yet. Click "New Article" to write your first market intelligence post.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
