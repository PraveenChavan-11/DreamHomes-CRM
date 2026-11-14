<?php /** @var array $p $history @var int $usage */ ?>
<div class="pagehead"><h1><?= esc($p['name']) ?> <?= crm_badge((int) $p['is_active'] ? 'active' : 'inactive') ?></h1>
  <a class="btn alt" href="<?= site_url('crm/products') ?>">&laquo; All products</a>
  <?php if (crm_can('products.manage')): ?><a class="btn teal" href="<?= site_url('crm/products/' . $p['id'] . '/edit') ?>">Edit product</a><?php endif; ?></div>
<div class="grid g-side">
  <div class="card"><div class="hd"><h2>Details</h2></div><div class="bd"><dl class="kv">
    <dt>SKU</dt><dd><?= esc($p['sku']) ?></dd><dt>Brand</dt><dd><?= esc($p['brand'] ?: '—') ?></dd><dt>Category</dt><dd><?= esc($p['category'] ?? '—') ?></dd>
    <dt>Pricing model</dt><dd><?= esc(crm_label($p['pricing_model'])) ?> (per <?= esc($p['unit']) ?>)</dd>
    <dt>Tax</dt><dd><?= esc($p['tax_name'] ?? '—') ?> · <?= esc(rtrim(rtrim((string) $p['tax_rate'], '0'), '.')) ?>%</dd>
    <dt>Description</dt><dd><?= nl2br(esc($p['description'] ?: '—')) ?></dd><dt>Specifications</dt><dd><?= nl2br(esc($p['specifications'] ?: '—')) ?></dd>
    <dt>Used in</dt><dd><?= (int) $usage ?> quotation line<?= $usage === 1 ? '' : 's' ?></dd></dl></div></div>
  <div class="grid">
    <div class="card"><div class="hd"><h2>Price ladder</h2></div><div class="bd">
      <dl class="kv"><dt>Base price</dt><dd><?= crm_money($p['base_price']) ?></dd><dt>Minimum selling</dt><dd><strong><?= crm_money($p['min_price']) ?></strong></dd><dt>Standard selling</dt><dd><strong><?= crm_money($p['standard_price']) ?></strong></dd></dl>
      <p class="hint">Quotation lines priced below the minimum need an approved discount; this is enforced on the server.</p></div></div>
    <?php if (! empty($p['image'])): ?><div class="card"><div class="bd"><img src="<?= esc(base_url($p['image'])) ?>" alt="<?= esc($p['name']) ?>" style="max-width:100%;border-radius:8px"></div></div><?php endif; ?>
  </div>
</div>
<div class="card mt"><div class="hd"><h2>Price change history</h2></div><div class="bd flush tablewrap">
<?php if (! $history): ?><div class="empty">No price history yet.</div><?php else: ?>
<table class="t"><thead><tr><th>When</th><th>By</th><th class="num">Base</th><th class="num">Standard</th><th class="num">Minimum</th></tr></thead><tbody>
<?php foreach ($history as $h): $c = static fn ($o, $n) => $o === null ? crm_money($n) . ' <span class="muted small">(initial)</span>' : (crm_money($o) . ' → <strong>' . crm_money($n) . '</strong>'); ?>
<tr><td class="nowrap"><?= crm_dt($h['changed_at']) ?></td><td><?= esc($h['by_name'] ?: 'System') ?></td><td class="num"><?= $c($h['old_base'], $h['new_base']) ?></td><td class="num"><?= $c($h['old_standard'], $h['new_standard']) ?></td><td class="num"><?= $c($h['old_min'], $h['new_min']) ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div></div>
