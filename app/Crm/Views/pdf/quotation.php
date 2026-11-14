<?php
/** @var array $q @var array $v @var array $c @var array $items @var array $charges @var array $company @var bool $intra @var ?string $logo @var string $salesperson */
$f = static fn ($n) => number_format((float) $n, 2, '.', ',');
$cgst = $sgst = $igst = 0.0;
foreach ($items as $i) { if ($intra) { $cgst += $i['tax_amount'] / 2; $sgst += $i['tax_amount'] / 2; } else { $igst += $i['tax_amount']; } }
foreach ($charges as $ch) { if ($intra) { $cgst += $ch['tax_amount'] / 2; $sgst += $ch['tax_amount'] / 2; } else { $igst += $ch['tax_amount']; } }
?><!doctype html>
<html><head><meta charset="utf-8"><title><?= esc($q['quotation_no']) ?></title>
<style>
 body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1b2a41;margin:0}
 .hdr{border-bottom:3px solid #0f2b4c;padding-bottom:8px;margin-bottom:10px}
 .hdr td{vertical-align:top}
 h1{font-size:16px;color:#0f2b4c;margin:0 0 2px}
 .brand{font-size:18px;font-weight:bold;color:#0f2b4c}
 .muted{color:#5b6b82}
 table{width:100%;border-collapse:collapse}
 .grid td{padding:3px 4px}
 .items th{background:#0f2b4c;color:#fff;padding:5px 4px;text-align:left;font-size:9px}
 .items td{border-bottom:1px solid #d8e0ea;padding:5px 4px;vertical-align:top}
 .r{text-align:right}
 .tot td{padding:3px 4px}
 .tot .g td{font-size:12px;font-weight:bold;border-top:2px solid #0f2b4c;color:#0f2b4c}
 .box{background:#f3f7fa;padding:6px 8px;margin-top:8px}
 .sig{margin-top:40px}
 .ph{background:#fff7e0;border:1px solid #e8c766;padding:4px 6px;margin:6px 0;font-size:9px}
</style></head><body>
<table class="hdr"><tr>
 <td style="width:55%"><?php if ($logo): ?><img src="<?= $logo ?>" style="height:46px"><?php else: ?><div class="brand">DREAM HOMES</div><div class="muted">Interior Solutions</div><?php endif; ?>
  <div style="margin-top:4px"><b><?= esc($company['name']) ?></b><br><?= esc($company['address']) ?><br>Tel: <?= esc($company['phone']) ?> · <?= esc($company['email']) ?><br>
  GSTIN: <?= $company['gstin'] !== '' ? esc($company['gstin']) : '<i>not configured</i>' ?></div></td>
 <td class="r"><h1>QUOTATION</h1><b><?= esc($q['quotation_no']) ?></b> · Version <?= (int) $v['version_no'] ?><br>
  Date: <?= esc(date('d M Y', strtotime($v['created_at']))) ?><br>Valid until: <b><?= $v['valid_until'] ? esc(date('d M Y', strtotime($v['valid_until']))) : '—' ?></b><br>Prepared by: <?= esc($salesperson) ?></td>
</tr></table>

<table class="grid"><tr>
 <td style="width:50%"><b>Customer</b><br><?= esc($c['name']) ?><?= $c['company_name'] ? '<br>' . esc($c['company_name']) : '' ?><br><?= nl2br(esc((string) ($c['billing_address'] ?: $c['site_address']))) ?><br><?= esc(trim($c['city'] . ' ' . $c['pin_code'])) ?><br>Mobile: <?= esc($c['mobile']) ?><?= $c['gstin'] ? '<br>GSTIN: ' . esc($c['gstin']) : '' ?></td>
 <td><b>Project</b><br><?= esc((string) $e['project_name']) ?><br>Enquiry: <?= esc($e['enquiry_no']) ?><br>Site: <?= esc((string) $e['site_location']) ?></td>
</tr></table>

<table class="items" style="margin-top:8px"><thead><tr><th>#</th><th>Item / Specification</th><th class="r">Qty</th><th>Unit</th><th class="r">Rate</th><th class="r">Disc.</th><th class="r">Taxable</th><th class="r">GST %</th><th class="r">Amount</th></tr></thead><tbody>
<?php foreach ($items as $n => $i): ?>
<tr><td><?= $n + 1 ?></td><td><b><?= esc($i['description']) ?></b><?php if ($i['sku']): ?> <span class="muted">(<?= esc($i['sku']) ?>)</span><?php endif; ?><?php if (! empty($i['variant'])): ?><br><span>Variant: <?= esc($i['variant']) ?></span><?php endif; ?><?php if ($i['width'] !== null && $i['height'] !== null): ?><br><span class="muted">Size: <?= rtrim(rtrim(number_format((float) $i['width'], 2, '.', ''), '0'), '.') ?> x <?= rtrim(rtrim(number_format((float) $i['height'], 2, '.', ''), '0'), '.') ?> <?= esc($i['size_unit']) ?><?= $i['nos'] ? ' x ' . rtrim(rtrim(number_format((float) $i['nos'], 2, '.', ''), '0'), '.') . ' nos' : '' ?></span><?php endif; ?><?= $i['specifications'] ? '<br><span class="muted">' . nl2br(esc($i['specifications'])) . '</span>' : '' ?></td>
<td class="r"><?= rtrim(rtrim(number_format((float) $i['quantity'], 3, '.', ''), '0'), '.') ?></td><td><?= esc($i['unit']) ?></td><td class="r"><?= $f($i['unit_price']) ?></td>
<td class="r"><?= $f($i['discount_amount']) ?></td><td class="r"><?= $f($i['taxable_amount']) ?></td><td class="r"><?= rtrim(rtrim(number_format((float) $i['tax_rate'], 2, '.', ''), '0'), '.') ?></td><td class="r"><?= $f($i['line_total']) ?></td></tr>
<?php endforeach; ?></tbody></table>

<table style="margin-top:8px"><tr><td style="width:55%;vertical-align:top">
 <div class="box"><b>Payment terms</b><br><?= nl2br(esc((string) $v['payment_terms'])) ?><?php if ($v['delivery_estimate']): ?><br><b>Delivery:</b> <?= esc($v['delivery_estimate']) ?><?php endif; ?></div>
 <?php if ($v['customer_remarks']): ?><div class="box"><b>Remarks</b><br><?= nl2br(esc($v['customer_remarks'])) ?></div><?php endif; ?></td>
 <td style="vertical-align:top"><table class="tot">
  <tr><td>Subtotal</td><td class="r"><?= $f($v['subtotal']) ?></td></tr>
  <tr><td>Discount</td><td class="r">− <?= $f($v['discount_total']) ?></td></tr>
  <tr><td>Taxable value</td><td class="r"><?= $f($v['taxable_total']) ?></td></tr>
  <?php foreach ($charges as $ch): ?><tr><td><?= esc($ch['name']) ?></td><td class="r"><?= $f($ch['amount']) ?></td></tr><?php endforeach; ?>
  <?php if ($intra): ?><tr><td>CGST</td><td class="r"><?= $f($cgst) ?></td></tr><tr><td>SGST</td><td class="r"><?= $f($sgst) ?></td></tr>
  <?php else: ?><tr><td>IGST</td><td class="r"><?= $f($igst) ?></td></tr><?php endif; ?>
  <?php if ((float) $v['round_off'] != 0.0): ?><tr><td>Round off</td><td class="r"><?= $f($v['round_off']) ?></td></tr><?php endif; ?>
  <tr class="g"><td>GRAND TOTAL (INR)</td><td class="r"><?= $f($v['grand_total']) ?></td></tr></table></td></tr></table>

<div class="box"><b>Terms &amp; Conditions</b><br><?= nl2br(esc((string) $v['terms'])) ?></div>
<?php if ($company['gstin'] === ''): ?><div class="ph">Draft notice: company GSTIN has not been configured in CRM settings.</div><?php endif; ?>
<table class="sig"><tr><td>Customer acceptance<br><br><br>______________________</td><td class="r">For <?= esc($company['name']) ?><br><br><br>Authorized signatory ______________________</td></tr></table>
</body></html>
