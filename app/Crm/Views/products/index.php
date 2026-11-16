<?php /** @var array $rows $f $categories @var int $total $page $pages */ ?>
<div class="pagehead">
  <div>
    <h1>Products &amp; Pricing</h1>
    <p class="muted small">Architectural Catalogue, Brands &amp; Configurable Variants</p>
  </div>
  <div class="gap">
    <?php if (crm_can('products.manage')): ?><a class="btn teal" href="<?= site_url('crm/products/new') ?>">+ New product</a><?php endif; ?>
  </div>
</div>
<form class="filters" method="get" action="<?= site_url('crm/products') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($f['q']) ?>" placeholder="Name, SKU or brand"></div>
  <div class="f"><label for="cat">Category</label><select id="cat" name="category"><option value="">All</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= crm_sel($f['category'], $c['id']) ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="act">Status</label><select id="act" name="active"><option value="">All</option><option value="1"<?= crm_sel($f['active'], '1') ?>>Active</option><option value="0"<?= crm_sel($f['active'], '0') ?>>Inactive</option></select></div>
  <button class="btn" type="submit">Filter</button><a class="btn alt" href="<?= site_url('crm/products') ?>">Reset</a>
</form>
<div class="card"><div class="bd flush tablewrap">
<?php if (! $rows): ?><div class="empty"><strong>No products match</strong>Adjust the filters<?= crm_can('products.manage') ? ' or add a product.' : '.' ?></div><?php else: ?>
<table class="t"><thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Pricing</th><th class="num">Standard</th><th class="num">Minimum</th><th class="num">Tax</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td class="nowrap"><a href="<?= site_url('crm/products/' . $r['id']) ?>"><?= esc($r['sku']) ?></a></td>
<td>
  <div style="display:flex;align-items:center;gap:12px">
    <?php if (! empty($r['image'])): ?>
      <img src="<?= esc(base_url($r['image'])) ?>" alt="<?= esc($r['name']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:6px;border:1px solid var(--line-2,#e2e8f0);background:#fff;flex-shrink:0;">
    <?php else: ?>
      <div style="width:44px;height:44px;border-radius:6px;background:var(--bg-2,#f8fafc);border:1px solid var(--line-2,#e2e8f0);display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:18px;flex-shrink:0;">📐</div>
    <?php endif; ?>
    <div>
      <strong><?= esc($r['name']) ?></strong>
      <div class="small muted"><?= esc($r['brand'] ?? '') ?></div>
    </div>
  </div>
</td>
<td><?= esc($r['category'] ?? '—') ?></td><td><?= esc(crm_label($r['pricing_model'])) ?> <span class="muted small">/ <?= esc($r['unit']) ?></span></td>
<td class="num"><?= crm_money($r['standard_price']) ?></td><td class="num"><?= crm_money($r['min_price']) ?></td><td class="num"><?= esc(rtrim(rtrim((string) $r['tax_rate'], '0'), '.')) ?>%</td>
<td><?= crm_badge((int) $r['is_active'] ? 'active' : 'inactive') ?></td>
<td class="right nowrap"><a class="btn alt sm" href="<?= site_url('crm/products/' . $r['id']) ?>">View</a>
<?php if (crm_can('products.manage')): ?> <a class="btn alt sm" href="<?= site_url('crm/products/' . $r['id'] . '/edit') ?>">Edit</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>
<?= view('Crm\Views\admin\_pager', ['page' => $page, 'pages' => $pages, 'total' => $total]) ?></div>
