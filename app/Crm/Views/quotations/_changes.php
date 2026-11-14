<?php
/**
 * Renders tracked quotation changes (from QuotationService::changeLog / versions()[n]['changes']).
 * @var list<array<string,mixed>> $changes
 */
$moneyKeys = ['subtotal', 'discount_total', 'tax_total', 'charges_total', 'adjustment_amount', 'grand_total', 'target_total', 'unit_price', 'line_total', 'taxable_amount', 'tax_amount', 'discount_amount', 'amount'];
$labels = ['description' => 'Product / description', 'sku' => 'SKU', 'variant' => 'Variant', 'width' => 'Width', 'height' => 'Height', 'size_unit' => 'Size unit', 'nos' => 'Pieces', 'quantity' => 'Quantity', 'unit' => 'Unit',
    'unit_price' => 'Unit price', 'price_source' => 'Price source', 'discount_pct' => 'Discount %', 'discount_amount' => 'Discount ₹', 'tax_rate' => 'GST %', 'taxable_amount' => 'Taxable', 'tax_amount' => 'GST ₹', 'line_total' => 'Line total',
    'subtotal' => 'Subtotal', 'discount_total' => 'Discount', 'tax_total' => 'GST', 'charges_total' => 'Charges', 'adjustment_amount' => 'Negotiated adjustment', 'grand_total' => 'Grand total', 'target_total' => 'Negotiated final price',
    'terms' => 'Terms', 'payment_terms' => 'Payment terms', 'delivery_estimate' => 'Delivery', 'valid_until' => 'Valid until', 'customer_remarks' => 'Customer remarks', 'amount' => 'Amount'];
$fmt = static function (string $k, $v) use ($moneyKeys): string {
    if ($v === null || $v === '') { return '—'; }
    if ($k === 'price_source') { return $v === 'manual' ? 'Manual' : 'Catalogue'; }
    if (in_array($k, $moneyKeys, true)) { return crm_money($v); }
    if (is_numeric($v)) { return rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.'); }
    $s = (string) $v;

    return mb_strlen($s) > 90 ? mb_substr($s, 0, 90) . '…' : $s;
};
$fieldTable = static function (array $fields) use ($labels, $fmt): void {
    if (! $fields) { return; } ?>
  <table class="t" style="margin:4px 0 0"><thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead><tbody>
  <?php foreach ($fields as $k => $ft): if (! is_array($ft) || ! array_key_exists('from', $ft)) { continue; } ?>
    <tr><td><?= esc($labels[$k] ?? crm_label($k)) ?></td><td><del class="muted"><?= esc($fmt($k, $ft['from'])) ?></del></td><td><strong><?= esc($fmt($k, $ft['to'])) ?></strong></td></tr>
  <?php endforeach; ?></tbody></table>
<?php };
// group rows that belong to one save
$groups = [];
foreach ($changes as $c) { $groups[$c['to_version_no'] . '|' . $c['from_version_no'] . '|' . $c['created_at'] . '|' . $c['user_id']][] = $c; }
if (! $groups): ?><div class="muted small">No tracked changes recorded for this version.</div><?php endif;
foreach ($groups as $rows): $h = $rows[0]; $draft = (int) $h['from_version_no'] === (int) $h['to_version_no']; ?>
  <div style="border:1px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:10px">
    <div class="small"><strong><?= $draft ? 'Draft saved' : 'Revision v' . (int) $h['from_version_no'] . ' → v' . (int) $h['to_version_no'] ?></strong> · <?= esc($h['user_name']) ?> · <?= crm_dt($h['created_at']) ?>
      <?php if (! empty($h['reason'])): ?><br><span class="muted">Reason:</span> <?= esc($h['reason']) ?><?php endif; ?></div>
    <ul style="margin:8px 0 0;padding-left:18px">
    <?php foreach ($rows as $c): $d = (array) ($c['data'] ?? []); ?>
      <li style="margin-bottom:6px">
      <?php if ($c['change_type'] === 'added'): ?>
        <span class="badge badge-green">Added</span> <strong><?= esc($c['item_ref']) ?></strong>
        <?php if (isset($d['amount'])): ?> — <?= crm_money($d['amount']) ?><?php else: ?> — qty <?= esc($fmt('quantity', $d['quantity'] ?? '')) ?> × <?= crm_money($d['unit_price'] ?? 0) ?> = <?= crm_money($d['line_total'] ?? 0) ?><?php endif; ?>
        <?php if (! empty($d['replaces'])): ?><div class="small muted">Replaces: <?= esc($d['replaces']['item']) ?></div><?php $fieldTable((array) $d['replaces']['fields']); endif; ?>
      <?php elseif ($c['change_type'] === 'removed'): ?>
        <span class="badge badge-red"><?= ! empty($d['replaced']) ? 'Replaced' : 'Removed' ?></span> <strong><?= esc($c['item_ref']) ?></strong>
        <?php if (isset($d['amount'])): ?> — <?= crm_money($d['amount']) ?><?php else: ?> — qty <?= esc($fmt('quantity', $d['quantity'] ?? '')) ?> × <?= crm_money($d['unit_price'] ?? 0) ?><?php endif; ?>
      <?php elseif ($c['change_type'] === 'modified'): ?>
        <span class="badge badge-amber">Changed</span> <strong><?= esc($c['item_ref']) ?></strong><?php $fieldTable($d); ?>
      <?php elseif ($c['change_type'] === 'totals'): ?>
        <span class="badge badge-blue">Totals</span><?php $fieldTable($d); ?>
      <?php else: ?>
        <span class="badge badge-grey">Terms</span><?php $fieldTable($d); ?>
      <?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
