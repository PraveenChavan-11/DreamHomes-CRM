<?php
/** @var list<array> $rows @var array $sum @var array $g @var int $page @var int $per @var list<array> $awaiting */
$statuses = ['draft', 'pending_confirmation', 'confirmed', 'on_hold', 'cancelled', 'closed'];
?>
<div class="pagehead"><h1>Sales orders</h1></div>

<div class="grid g4 mb">
  <div class="card kpi"><div class="l">Orders (filtered)</div><div class="v"><?= number_format((int) $sum['n']) ?></div></div>
  <div class="card kpi good"><div class="l">Order value</div><div class="v"><?= crm_money0($sum['total']) ?></div></div>
  <div class="card kpi"><div class="l">Advance received</div><div class="v"><?= crm_money0($sum['advance']) ?></div></div>
  <div class="card kpi"><div class="l">Awaiting conversion</div><div class="v"><?= count($awaiting) ?></div><div class="s">Accepted quotations without an order</div></div>
</div>

<?php if ($awaiting): ?>
<div class="card mb"><div class="hd"><h2>Accepted quotations ready to become orders</h2></div><div class="bd flush tablewrap"><table class="t"><thead><tr><th>Quotation</th><th>Customer</th><th class="num">Value</th><th></th></tr></thead><tbody>
  <?php foreach ($awaiting as $a): ?><tr><td><a href="<?= site_url('crm/quotations/' . (int) $a['id']) ?>"><?= esc($a['quotation_no']) ?></a> v<?= (int) $a['version_no'] ?></td><td><?= esc($a['customer_name']) ?></td><td class="num"><?= crm_money($a['grand_total']) ?></td>
    <td class="right"><a class="btn teal sm" href="<?= site_url('crm/quotations/' . (int) $a['id']) ?>">Open &amp; convert</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>

<form class="filters card" method="get" style="padding:14px 18px">
  <div class="f"><label for="oq">Search</label><input id="oq" type="search" name="q" value="<?= esc($g['q'] ?? '', 'attr') ?>" placeholder="SO / QT no. or customer"></div>
  <div class="f"><label for="os">Status</label><select id="os" name="status"><option value="">All</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>"<?= crm_sel($g['status'] ?? '', $s) ?>><?= crm_label($s) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="of">From</label><input id="of" type="date" name="from" value="<?= esc($g['from'] ?? '', 'attr') ?>"></div>
  <div class="f"><label for="ot">To</label><input id="ot" type="date" name="to" value="<?= esc($g['to'] ?? '', 'attr') ?>"></div>
  <div class="f"><button class="btn" type="submit">Filter</button> <a class="btn alt" href="<?= site_url('crm/orders') ?>">Reset</a></div>
</form>
<div class="card">
<?php if (! $rows): ?><div class="empty"><strong>No sales orders match</strong>Orders are created from accepted quotations.</div>
<?php else: ?>
  <div class="bd flush tablewrap"><table class="t"><thead><tr><th>Order</th><th>Organisation / Client</th><th>Quotation</th><th>Salesperson</th><th>Status</th><th class="num">Value</th><th class="num">Advance</th><th>Expected delivery</th><th>Created</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr>
    <td><a href="<?= site_url('crm/orders/' . (int) $r['id']) ?>"><strong><?= esc($r['order_no']) ?></strong></a></td>
    <td>
      <a href="<?= ! empty($r['organisation_id']) ? site_url('crm/organisations/' . (int) $r['organisation_id']) : site_url('crm/orders/' . (int) $r['id']) ?>">
        <strong><?= esc($r['organisation_name'] ?? $r['customer_name']) ?></strong>
      </a>
      <?php if (! empty($r['contact_name'])): ?><div class="muted small"><?= esc($r['contact_name']) ?></div><?php endif; ?>
    </td>
    <td><a href="<?= site_url('crm/quotations/' . (int) $r['quotation_id']) ?>"><?= esc($r['quotation_no']) ?></a></td>
    <td><?= esc((string) $r['salesperson'] ?: '—') ?></td><td><?= crm_badge($r['status']) ?></td>
    <td class="num"><strong><?= crm_money($r['grand_total']) ?></strong></td><td class="num"><?= crm_money($r['advance_amount']) ?></td>
    <td class="nowrap"><?= crm_date($r['expected_delivery']) ?></td><td class="nowrap"><?= crm_date($r['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => (int) $sum['n'], 'per' => $per, 'page' => $page]) ?>
<?php endif; ?>
</div>
