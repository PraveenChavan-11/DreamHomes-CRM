<?php
/** @var array{rows:list<array>,total:int} $res @var array $f @var int $page @var int $per @var list<array> $customers @var list<array> $users @var list<array> $teams @var list<array> $products */
$total = $res['total'];
$qs = http_build_query($f);
$sel = static fn (string $k, $v) => crm_sel($f[$k] ?? '', $v);
$user = static function (string $name, string $label) use ($users, $sel) {
    $o = '<div class="f"><label for="h_' . $name . '">' . esc($label) . '</label><select id="h_' . $name . '" name="' . $name . '"><option value="">Anyone</option>';
    foreach ($users as $u) { $o .= '<option value="' . (int) $u['id'] . '"' . $sel($name, $u['id']) . '>' . esc($u['name']) . ((int) $u['is_active'] ? '' : ' (inactive)') . '</option>'; }

    return $o . '</select></div>';
};
?>
<div class="pagehead"><h1>Sales history <span class="muted small">(<?= number_format($total) ?> events)</span></h1>
  <?php if (crm_can('reports.export') || crm_can('customers.export')): ?>
    <a class="btn alt" href="<?= site_url('crm/history/export?' . $qs) ?>">Export Excel</a>
    <a class="btn alt" href="<?= site_url('crm/history/export?' . $qs . ($qs ? '&' : '') . 'format=pdf') ?>">Export PDF</a>
  <?php endif; ?></div>
<form class="filters card" style="padding:14px" method="get" action="<?= site_url('crm/history') ?>">
  <div class="f"><label for="h_from">From</label><input id="h_from" type="date" name="from" value="<?= esc($f['from'] ?? '') ?>"></div>
  <div class="f"><label for="h_to">To</label><input id="h_to" type="date" name="to" value="<?= esc($f['to'] ?? '') ?>"></div>
  <div class="f"><label for="h_customer_id">Customer</label><select id="h_customer_id" name="customer_id" data-search><option value="">All customers</option><?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $sel('customer_id', $c['id']) ?>><?= esc($c['customer_no'] . ' · ' . $c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_enq">Enquiry no.</label><input id="h_enq" name="enquiry_no" value="<?= esc($f['enquiry_no'] ?? '') ?>" placeholder="e.g. ENQ-0001"></div>
  <?= $user('user_id', 'Acting salesperson') ?>
  <?= $user('prev_owner_id', 'Previous salesperson') ?>
  <?= $user('owner_id', 'Current salesperson') ?>
  <div class="f"><label for="h_team">Team</label><select id="h_team" name="team_id"><option value="">All</option><?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"<?= $sel('team_id', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_prod">Product</label><select id="h_prod" name="product_id" data-search><option value="">All products</option><?php foreach ($products as $p): ?><option value="<?= (int) $p['id'] ?>"<?= $sel('product_id', $p['id']) ?>><?= esc($p['sku'] . ' · ' . $p['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_group">Transaction type</label><select id="h_group" name="event_group"><option value="">All</option><?php foreach (['enquiry', 'activity', 'followup', 'visit', 'quotation', 'negotiation', 'discount', 'order'] as $g): ?><option value="<?= $g ?>"<?= $sel('event_group', $g) ?>><?= esc(crm_label($g)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_status">Enquiry status</label><select id="h_status" name="status"><option value="">All</option><?php foreach (['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation', 'won', 'lost', 'on_hold'] as $s): ?><option value="<?= $s ?>"<?= $sel('status', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_appr">Approval</label><select id="h_appr" name="approval"><option value="">Any</option><?php foreach (['requested', 'approved', 'rejected', 'returned', 'escalated', 'auto_approved'] as $s): ?><option value="<?= $s ?>"<?= $sel('approval', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="h_out">Won / lost</label><select id="h_out" name="outcome"><option value="">Any</option><?php foreach (['won', 'lost', 'on_hold'] as $s): ?><option value="<?= $s ?>"<?= $sel('outcome', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Apply</button></div><div class="f"><a class="btn alt" href="<?= site_url('crm/history') ?>">Reset</a></div>
</form>
<p class="small muted">Quotation, order and approval status filters are available via the transaction-type and approval filters; the enquiry status and won/lost filters use the enquiry's current state.</p>
<div class="card"><div class="bd flush">
<?php if (! $res['rows']): ?><div class="empty"><strong>No transactions match these filters</strong></div>
<?php else: ?>
<div class="tablewrap"><table class="t"><thead><tr><th>When</th><th>Event</th><th>Customer</th><th>Enquiry</th><th>User</th><th>Owner change</th><th>Status change</th><th>Remarks</th></tr></thead><tbody>
<?php foreach ($res['rows'] as $r): ?>
  <tr><td class="nowrap"><?= esc(crm_dt($r['created_at'])) ?></td>
    <td><strong><?= esc($r['label']) ?></strong><?php if ($r['entity_ref']): ?><div class="small"><?= $r['link'] ? '<a href="' . site_url($r['link']) . '">' . esc($r['entity_ref']) . ' &rarr;</a>' : esc($r['entity_ref']) ?></div><?php endif; ?></td>
    <td><?php if ($r['customer_id']): ?><a href="<?= site_url('crm/customers/' . (int) $r['customer_id']) ?>"><?= esc($r['customer_name']) ?></a><?php else: ?>—<?php endif; ?></td>
    <td><?php if ($r['enquiry_id']): ?><a href="<?= site_url('crm/history/enquiry/' . (int) $r['enquiry_id']) ?>"><?= esc($r['enquiry_no']) ?></a><?php else: ?>—<?php endif; ?></td>
    <td><?= esc($r['user_name']) ?></td>
    <td class="small"><?= ($r['prev_owner'] || $r['new_owner']) ? esc($r['prev_owner'] ?? '—') . ' &rarr; <strong>' . esc($r['new_owner'] ?? '—') . '</strong>' : '—' ?></td>
    <td class="small"><?= ($r['prev_status'] || $r['new_status']) ? esc(crm_label($r['prev_status'])) . ' &rarr; <strong>' . esc(crm_label($r['new_status'])) . '</strong>' : '—' ?></td>
    <td class="small muted"><?= esc($r['reason'] ?? '') ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?= view('Crm\Views\partials\wa_pager', compact('total', 'per', 'page')) ?>
<?php endif; ?></div></div>
