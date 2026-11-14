<?php
use App\Services\CaseStudyService as CS;
/** @var array $rows @var int $total @var int $page @var int $totalPages @var string $status @var string $search @var array $statusCounts @var bool $canManage */
$badge = ['draft' => 'bg-amber-100 text-amber-800', 'published' => 'bg-emerald-100 text-emerald-800', 'archived' => 'bg-slate-200 text-slate-700'];
$qs = static fn (array $extra = []) => http_build_query(array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null));
$btn = 'px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-semibold transition min-h-[36px]';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Case studies</h1>
      <p class="text-xs text-slate-500 mt-1">Real client or internal projects shown at <a class="underline" href="<?= base_url('case-studies') ?>" target="_blank" rel="noopener">/case-studies</a>. Only published entries are visible. Nothing is pre-filled: with none published, the public portfolio links stay hidden.</p>
    </div>
    <?php if ($canManage): ?>
      <a href="<?= base_url('admin/case-studies/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:opacity-90 transition">+ New case study</a>
    <?php endif; ?>
  </div>

  <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
    <?php foreach (['' => 'All'] + CS::STATUSES as $k => $label): $n = $k === '' ? $statusCounts['all'] : ($statusCounts[$k] ?? 0); ?>
      <a href="<?= base_url('admin/case-studies?' . $qs(['status' => $k, 'page' => null])) ?>" <?= $status === $k ? 'aria-current="true"' : '' ?>
         class="px-3.5 py-2 rounded-xl text-xs font-semibold border <?= $status === $k ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>"><?= esc($label) ?> (<?= (int) $n ?>)</a>
    <?php endforeach; ?>
  </nav>

  <form action="<?= base_url('admin/case-studies') ?>" method="GET" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-3">
    <label for="cs-search" class="sr-only">Search case studies</label>
    <input id="cs-search" type="text" name="search" value="<?= esc($search) ?>" placeholder="Search title, client, category..." class="w-full sm:w-80 px-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= esc($status) ?>"><?php endif; ?>
    <button class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">Search</button>
    <?php if ($search !== '' || $status !== ''): ?><a href="<?= base_url('admin/case-studies') ?>" class="text-xs text-slate-500 hover:text-slate-700">Clear</a><?php endif; ?>
    <span class="ml-auto text-xs text-slate-500 font-medium"><?= (int) $total ?> case stud<?= $total === 1 ? 'y' : 'ies' ?></span>
  </form>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
        <tr><th scope="col" class="text-left px-4 py-3">Case study</th><th scope="col" class="text-left px-4 py-3">Status</th><th scope="col" class="text-left px-4 py-3">Category</th><th scope="col" class="text-left px-4 py-3">Updated</th><th scope="col" class="text-left px-4 py-3">Actions</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No case studies yet. <?php if ($canManage): ?><a class="underline" href="<?= base_url('admin/case-studies/new') ?>">Add the first real project</a>.<?php endif; ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): $id = (int) $r['id']; ?>
        <tr class="hover:bg-slate-50/70 align-top">
          <td class="px-4 py-3">
            <a href="<?= base_url('admin/case-studies/' . $id . '/edit') ?>" class="font-bold text-slate-900 hover:text-brand-primary"><?= esc($r['title']) ?></a>
            <div class="text-slate-500">/case-studies/<?= esc($r['slug']) ?><?= $r['client_name'] ? ' · ' . esc($r['client_name']) : '' ?></div>
          </td>
          <td class="px-4 py-3"><span class="px-2 py-1 rounded-full font-bold <?= $badge[$r['status']] ?? 'bg-slate-100' ?>"><?= esc(CS::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
          <td class="px-4 py-3 text-slate-600"><?= esc($r['category'] ?: '—') ?></td>
          <td class="px-4 py-3 text-slate-500"><?= esc(date('d M Y, H:i', strtotime($r['updated_at'] ?? $r['created_at']))) ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1.5">
              <a href="<?= base_url('admin/case-studies/' . $id . '/edit') ?>" class="<?= $btn ?> inline-flex items-center"><?= $canManage ? 'Edit' : 'View' ?></a>
              <?php if ($r['status'] === 'published'): ?><a href="<?= base_url('case-studies/' . $r['slug']) ?>" target="_blank" rel="noopener" class="<?= $btn ?> inline-flex items-center">View live</a><?php endif; ?>
              <?php if ($canManage): ?>
                <form action="<?= base_url('admin/case-studies/' . $id . '/status') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $r['status'] === 'published' ? 'draft' : 'published' ?>"><button class="<?= $btn ?>"><?= $r['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button></form>
                <form action="<?= base_url('admin/case-studies/' . $id . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this case study? It is hidden everywhere (soft delete) and can only be restored from the database.');"><?= csrf_field() ?><button class="<?= $btn ?> text-rose-700">Delete</button></form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-2 text-xs">
      <?php if ($page > 1): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/case-studies?' . $qs(['page' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
      <span class="text-slate-500">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
      <?php if ($page < $totalPages): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/case-studies?' . $qs(['page' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
