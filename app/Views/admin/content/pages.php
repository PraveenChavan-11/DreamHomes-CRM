<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>Google Search Engine Optimization (SEO) CMS</span>
      </div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Website Pages &amp; SEO Management</h1>
      <p class="text-xs text-slate-500 mt-1">Configure search meta titles, high-CTR descriptions, keywords, and Google SERP snippets across all 15 public pages.</p>
    </div>
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-sm">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>100% SEO Connected</span>
      </span>
    </div>
  </div>

  <!-- SEO Optimization KPI Summary -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Managed Pages</div>
      <div class="text-xl font-black text-slate-900 mt-1"><?= count($pages ?? []) ?> Pages</div>
      <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">All Routes Registered</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Title Standard</div>
      <div class="text-xl font-black text-brand-primary mt-1">50–60 Chars</div>
      <div class="text-[10px] text-slate-500 font-medium mt-0.5">Desktop &amp; Mobile Optimal</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Snippet Standard</div>
      <div class="text-xl font-black text-blue-600 mt-1">150–160 Chars</div>
      <div class="text-[10px] text-slate-500 font-medium mt-0.5">Maximum SERP Click-Through</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Indexing Directives</div>
      <div class="text-xl font-black text-emerald-600 mt-1">Index, Follow</div>
      <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">Clean Canonical Tagging</div>
    </div>
  </div>

  <!-- Pages & SEO Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Page Name &amp; Route</th>
            <th class="px-6 py-4">Google Search Meta Title (50-60)</th>
            <th class="px-6 py-4">Search Snippet Meta Description (150-160)</th>
            <th class="px-4 py-4 text-center">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($pages)): ?>
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-xs">No pages found in CMS database.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($pages as $p): ?>
              <?php
                $titleLen = mb_strlen($p['meta_title'] ?? '');
                $descLen = mb_strlen($p['meta_description'] ?? '');
                $titleOptimal = ($titleLen >= 45 && $titleLen <= 65);
                $descOptimal = ($descLen >= 135 && $descLen <= 165);
                $routeUrl = $p['slug'] === 'home' ? base_url() : base_url($p['slug']);
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($p['title']) ?></div>
                  <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-mono mt-0.5">
                    <span>/<?= esc($p['slug'] === 'home' ? '' : $p['slug']) ?></span>
                  </div>
                </td>

                <td class="px-6 py-4 max-w-xs">
                  <div class="font-semibold text-slate-800 line-clamp-2 leading-snug">
                    <?= esc($p['meta_title'] ?: 'Not configured') ?>
                  </div>
                  <div class="mt-1 flex items-center gap-1.5">
                    <?php if ($titleLen > 0): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $titleOptimal ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                        <?= $titleLen ?> chars <?= $titleOptimal ? '• Optimal' : ($titleLen < 45 ? '• Short' : '• Long') ?>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                        Missing Title
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <td class="px-6 py-4 max-w-sm">
                  <div class="text-slate-600 line-clamp-2 leading-relaxed text-[11px]">
                    <?= esc($p['meta_description'] ?: 'No meta description configured.') ?>
                  </div>
                  <div class="mt-1 flex items-center gap-1.5">
                    <?php if ($descLen > 0): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $descOptimal ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                        <?= $descLen ?> chars <?= $descOptimal ? '• Optimal' : ($descLen < 135 ? '• Short' : '• Long') ?>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                        Missing Description
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Published
                  </span>
                </td>

                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="<?= $routeUrl ?>" target="_blank" title="View Public Page" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition border border-transparent hover:border-slate-200">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="<?= base_url('admin/content/pages/edit/' . $p['id']) ?>" title="Edit Page SEO & Meta" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover transition shadow-sm">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      <span>Edit SEO</span>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
