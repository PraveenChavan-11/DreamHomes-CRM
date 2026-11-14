<?php
/** @var array $q @var array $cmp @var array $va @var array $vb @var list<array> $versions */
$qty = static fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
$labels = ['description' => 'Product / description', 'sku' => 'SKU', 'variant' => 'Variant', 'width' => 'Width', 'height' => 'Height', 'size_unit' => 'Size unit', 'nos' => 'Pieces', 'quantity' => 'Quantity', 'unit' => 'Unit',
    'unit_price' => 'Unit price', 'price_source' => 'Price source', 'discount_pct' => 'Discount %', 'discount_amount' => 'Discount ₹', 'tax_rate' => 'GST %', 'taxable_amount' => 'Taxable', 'tax_amount' => 'GST ₹', 'line_total' => 'Line total'];
$money = ['unit_price', 'line_total', 'taxable_amount', 'tax_amount', 'discount_amount'];
$tl = ['subtotal' => 'Subtotal', 'discount_total' => 'Discount', 'taxable_total' => 'Taxable value', 'charges_total' => 'Additional charges', 'tax_total' => 'GST', 'grand_total' => 'Grand total'];
$show = static fn (string $k, $v) => $v === null || $v === '' ? '—' : (in_array($k, ['unit_price', 'line_total', 'taxable_amount', 'tax_amount', 'discount_amount'], true) ? crm_money($v) : ($k === 'price_source' ? ($v === 'manual' ? 'Manual' : 'Catalogue') : (is_numeric($v) ? $qty($v) : esc((string) $v))));
?>
<div class="pagehead"><h1>Compare <?= esc($q['quotation_no']) ?></h1><a class="btn alt" href="<?= site_url('crm/quotations/' . (int) $q['id']) ?>">Back to quotation</a></div>

<form class="filters card" method="get" style="padding:14px 18px">
  <div class="f"><label for="ca">Earlier version</label><select id="ca" name="a"><?php foreach ($versions as $v): ?><option value="<?= (int) $v['version_no'] ?>"<?= crm_sel($cmp['a'], $v['version_no']) ?>>v<?= (int) $v['version_no'] ?> · <?= crm_money($v['grand_total']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="cb">Later version</label><select id="cb" name="b"><?php foreach ($versions as $v): ?><option value="<?= (int) $v['version_no'] ?>"<?= crm_sel($cmp['b'], $v['version_no']) ?>>v<?= (int) $v['version_no'] ?> · <?= crm_money($v['grand_total']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Compare</button></div>
</form>

<?php if ($cmp['reason']): ?><div class="flash info"><strong>Reason for v<?= (int) $cmp['b'] ?>:</strong> <?= esc($cmp['reason']) ?></div><?php endif; ?>

<div class="grid g-side mb">
  <div class="card"><div class="hd"><h2>What changed</h2></div><div class="bd">
    <?php if (! $cmp['items']): ?><div class="muted">No line-item differences between these versions.</div><?php endif; ?>
    <?php foreach ($cmp['items'] as $ch): $f = (array) $ch['fields']; ?>
      <div style="border:1px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:10px">
        <span class="badge <?= ['added' => 'badge-green', 'removed' => 'badge-red', 'modified' => 'badge-amber'][$ch['change']] ?? 'badge-grey' ?>"><?= $ch['change'] === 'modified' ? 'Changed' : crm_label($ch['change']) ?></span> <strong><?= esc($ch['item']) ?></strong>
        <?php if ($ch['change'] === 'modified'): ?>
          <table class="t" style="margin-top:6px"><thead><tr><th>Field</th><th>v<?= (int) $cmp['a'] ?></th><th>v<?= (int) $cmp['b'] ?></th></tr></thead><tbody>
          <?php foreach ($f as $k => $ft): ?><tr><td><?= esc($labels[$k] ?? crm_label($k)) ?></td><td><del class="muted"><?= $show($k, $ft['from']) ?></del></td><td><strong><?= $show($k, $ft['to']) ?></strong></td></tr><?php endforeach; ?></tbody></table>
        <?php elseif (! empty($f['replaces'])): ?>
          <div class="small muted" style="margin-top:4px">Replaces: <?= esc($f['replaces']['item']) ?></div>
          <table class="t" style="margin-top:6px"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>
          <?php foreach ((array) $f['replaces']['fields'] as $k => $ft): ?><tr><td><?= esc($labels[$k] ?? crm_label($k)) ?></td><td><del class="muted"><?= $show($k, $ft['from']) ?></del></td><td><strong><?= $show($k, $ft['to']) ?></strong></td></tr><?php endforeach; ?></tbody></table>
        <?php else: ?>
          <div class="small muted">qty <?= $qty($f['quantity'] ?? 0) ?> × <?= crm_money($f['unit_price'] ?? 0) ?> = <?= crm_money($f['line_total'] ?? 0) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div></div>
  <div class="card"><div class="hd"><h2>Totals</h2></div><div class="bd flush"><table class="t"><thead><tr><th></th><th class="num">v<?= (int) $cmp['a'] ?></th><th class="num">v<?= (int) $cmp['b'] ?></th><th class="num">Change</th></tr></thead><tbody>
    <?php foreach ($tl as $k => $lab): $t = $cmp['totals'][$k]; $d = (float) $t['delta']; ?>
      <tr<?= $k === 'grand_total' ? ' style="font-weight:700"' : '' ?>><td><?= $lab ?></td><td class="num"><?= crm_money($t['a']) ?></td><td class="num"><?= crm_money($t['b']) ?></td>
        <td class="num" style="color:<?= $d < 0 ? 'var(--green)' : ($d > 0 ? 'var(--red)' : 'inherit') ?>"><?= $d == 0 ? '—' : ($d > 0 ? '+' : '−') . crm_money(abs($d)) ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div>
</div>

<div class="grid g2">
<?php foreach ([['v' => $va, 'no' => $cmp['a']], ['v' => $vb, 'no' => $cmp['b']]] as $side): $v = $side['v']; ?>
  <div class="card"><div class="hd"><h3>v<?= (int) $side['no'] ?> — <?= crm_money($v['grand_total']) ?></h3><span class="small muted"><?= esc($v['author']) ?> · <?= crm_dt($v['created_at']) ?></span></div>
    <div class="bd flush tablewrap"><table class="t"><thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr></thead><tbody>
      <?php foreach ($v['items'] as $i): ?><tr><td><?= esc(\Crm\Services\QuotationService::itemLabel($i)) ?><?= $i['price_source'] === 'manual' ? ' <span class="badge badge-amber">Manual</span>' : '' ?></td><td class="num"><?= $qty($i['quantity']) ?></td><td class="num"><?= crm_money($i['unit_price']) ?></td><td class="num"><?= crm_money($i['line_total']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></div>
<?php endforeach; ?>
</div>
