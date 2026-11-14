<?php
use App\Services\ProductService as PS;
/** @var array $products @var int $total @var int $page @var int $totalPages @var string $status @var string $search @var array $statusCounts @var bool $canManage */
$badge = ['draft' => 'bg-amber-100 text-amber-800', 'published' => 'bg-emerald-100 text-emerald-800', 'archived' => 'bg-slate-200 text-slate-700'];
$qs = static fn (array $extra = []) => http_build_query(array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null));
$btn = 'px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-semibold transition';
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Products</h1>
      <p class="text-xs text-slate-500 mt-1">The public product catalogue at <a class="underline" href="<?= base_url('products') ?>" target="_blank" rel="noopener">/products</a>. Only published products are visible to visitors.</p>
    </div>
    <?php if ($canManage): ?>
      <a href="<?= base_url('admin/products/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:opacity-90 transition">+ New product</a>
    <?php endif; ?>
  </div>

  <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
    <?php foreach (['' => 'All'] + PS::STATUSES as $k => $label): $n = $k === '' ? $statusCounts['all'] : ($statusCounts[$k] ?? 0); ?>
      <a href="<?= base_url('admin/products?' . $qs(['status' => $k, 'page' => null])) ?>" <?= $status === $k ? 'aria-current="true"' : '' ?>
         class="px-3.5 py-2 rounded-xl text-xs font-semibold border <?= $status === $k ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>"><?= esc($label) ?> (<?= (int) $n ?>)</a>
    <?php endforeach; ?>
  </nav>

  <form action="<?= base_url('admin/products') ?>" method="GET" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-3">
    <label for="prod-search" class="sr-only">Search products</label>
    <input id="prod-search" type="text" name="search" value="<?= esc($search) ?>" placeholder="Search name, slug, tagline, category..." class="w-full sm:w-80 px-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= esc($status) ?>"><?php endif; ?>
    <button class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">Search</button>
    <?php if ($search !== '' || $status !== ''): ?><a href="<?= base_url('admin/products') ?>" class="text-xs text-slate-500 hover:text-slate-700">Clear</a><?php endif; ?>
    <span class="ml-auto text-xs text-slate-500 font-medium"><?= (int) $total ?> product<?= $total === 1 ? '' : 's' ?></span>
  </form>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
        <tr><th class="text-left px-4 py-3">Product</th><th class="text-left px-4 py-3">Status</th><th class="text-left px-4 py-3">Flags</th><th class="text-left px-4 py-3">Order</th><th class="text-left px-4 py-3">Updated</th><th class="text-left px-4 py-3">Actions</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php if (!$products): ?>
        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No products match. <?php if ($canManage): ?><a class="underline" href="<?= base_url('admin/products/new') ?>">Create the first one</a>.<?php endif; ?></td></tr>
      <?php endif; ?>
      <?php foreach ($products as $p): $id = (int) $p['id']; ?>
        <tr class="hover:bg-slate-50/70 align-top">
          <td class="px-4 py-3">
            <a href="<?= base_url('admin/products/' . $id . '/edit') ?>" class="font-bold text-slate-900 hover:text-brand-primary"><?= esc($p['name']) ?></a>
            <div class="text-slate-500">/products/<?= esc($p['slug']) ?><?= $p['category'] ? ' · ' . esc($p['category']) : '' ?></div>
          </td>
          <td class="px-4 py-3"><span class="px-2 py-1 rounded-full font-bold <?= $badge[$p['status']] ?? 'bg-slate-100' ?>"><?= esc(PS::STATUSES[$p['status']] ?? $p['status']) ?></span></td>
          <td class="px-4 py-3 text-slate-600">
            <?= $p['is_flagship'] ? '<span class="font-bold">Flagship</span><br>' : '' ?><?= $p['is_featured'] ? 'Featured' : '' ?><?= (!$p['is_flagship'] && !$p['is_featured']) ? '—' : '' ?>
            <?= $p['no_index'] ? '<br>No-index' : '' ?>
          </td>
          <td class="px-4 py-3">
            <?php if ($canManage): ?>
              <div class="flex gap-1">
                <?php foreach (['up' => ['Move up', '&uarr;'], 'down' => ['Move down', '&darr;']] as $d => [$lbl, $ch]): ?>
                  <form action="<?= base_url('admin/products/' . $id . '/move') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="direction" value="<?= $d ?>">
                    <button class="<?= $btn ?>" aria-label="<?= $lbl ?>: <?= esc($p['name']) ?>"><?= $ch ?></button></form>
                <?php endforeach; ?>
              </div>
            <?php else: ?><?= (int) $p['display_order'] ?><?php endif; ?>
          </td>
          <td class="px-4 py-3 text-slate-500"><?= esc(date('d M Y, H:i', strtotime($p['updated_at'] ?? $p['created_at']))) ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-1.5">
              <a href="<?= base_url('admin/products/' . $id . '/edit') ?>" class="<?= $btn ?>"><?= $canManage ? 'Edit' : 'View' ?></a>
              <a href="<?= base_url('admin/products/' . $id . '/preview') ?>" target="_blank" rel="noopener" class="<?= $btn ?>">Preview</a>
              <?php if ($canManage): ?>
                <?php if ($p['status'] === 'published'): ?>
                  <form action="<?= base_url('admin/products/' . $id . '/status') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="status" value="draft"><button class="<?= $btn ?>">Unpublish</button></form>
                <?php else: ?>
                  <form action="<?= base_url('admin/products/' . $id . '/status') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="status" value="published"><button class="<?= $btn ?>">Publish</button></form>
                <?php endif; ?>
                <?php if ($p['status'] !== 'archived'): ?>
                  <form action="<?= base_url('admin/products/' . $id . '/status') ?>" method="POST" onsubmit="return confirm('Archive this product? It will no longer be visible to visitors.');"><?= csrf_field() ?><input type="hidden" name="status" value="archived"><button class="<?= $btn ?>">Archive</button></form>
                <?php endif; ?>
                <form action="<?= base_url('admin/products/' . $id . '/feature') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="featured" value="<?= $p['is_featured'] ? 0 : 1 ?>"><button class="<?= $btn ?>"><?= $p['is_featured'] ? 'Unfeature' : 'Feature' ?></button></form>
                <form action="<?= base_url('admin/products/' . $id . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this product? It is hidden everywhere (soft delete) and can only be restored from the database.');"><?= csrf_field() ?><button class="px-2.5 py-1.5 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 text-[11px] font-semibold transition">Delete</button></form>
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
      <?php if ($page > 1): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/products?' . $qs(['page' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
      <span class="text-slate-500">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
      <?php if ($page < $totalPages): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/products?' . $qs(['page' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
