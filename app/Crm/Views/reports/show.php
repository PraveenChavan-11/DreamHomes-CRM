<?php
/** @var string $key @var string $title @var list<string> $columns @var list<array> $rows @var int $total @var int $per @var int $page @var array $f @var list<array> $users @var list<array> $teams @var list<array> $customers */
$qs  = http_build_query($f);
$sel = static fn (string $k, $v) => crm_sel($f[$k] ?? '', $v);
$statuses = ['new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'negotiation', 'won', 'lost', 'on_hold', 'active', 'inactive', 'archived', 'draft', 'sent', 'revised', 'accepted', 'rejected', 'expired', 'cancelled', 'pending', 'approved', 'returned', 'escalated', 'completed', 'missed', 'scheduled', 'confirmed', 'closed', 'queued', 'delivered', 'failed', 'bounced'];
$isDate = static fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $v);
$isMoney = static fn (string $col) => (bool) preg_match('/(total|value|price|budget|discount|subtotal|tax|advance)$/i', trim($col)) && stripos($col, '%') === false;
?>
<div class="pagehead"><h1><?= esc($title) ?> <span class="muted small">(<?= number_format($total) ?> rows)</span></h1>
  <a class="btn alt" href="<?= site_url('crm/reports') ?>">&larr; All reports</a>
  <?php if (crm_can('reports.export')): ?>
    <a class="btn alt" href="<?= site_url('crm/reports/' . $key . '/xlsx?' . $qs) ?>">Export Excel</a>
    <a class="btn alt" href="<?= site_url('crm/reports/' . $key . '/pdf?' . $qs) ?>">Export PDF</a>
  <?php endif; ?></div>
<form class="filters card" style="padding:14px" method="get" action="<?= site_url('crm/reports/' . $key) ?>">
  <div class="f"><label for="r_from">From</label><input id="r_from" type="date" name="from" value="<?= esc($f['from'] ?? '') ?>"></div>
  <div class="f"><label for="r_to">To</label><input id="r_to" type="date" name="to" value="<?= esc($f['to'] ?? '') ?>"></div>
  <div class="f"><label for="r_user">User</label><select id="r_user" name="user_id"><option value="">Anyone</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= $sel('user_id', $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="r_team">Team</label><select id="r_team" name="team_id"><option value="">All</option><?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"<?= $sel('team_id', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="r_status">Status</label><select id="r_status" name="status"><option value="">Any</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>"<?= $sel('status', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="r_cust">Customer</label><select id="r_cust" name="customer_id" data-search><option value="">All customers</option><?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $sel('customer_id', $c['id']) ?>><?= esc($c['customer_no'] . ' · ' . $c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Apply</button></div><div class="f"><a class="btn alt" href="<?= site_url('crm/reports/' . $key) ?>">Reset</a></div>
</form>
<p class="small muted">Filters apply where the report has that dimension (for example, "Team" has no effect on the login report).</p>
<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong>No data for these filters</strong>Widen the date range or clear a filter.</div>
<?php else: ?>
<div class="tablewrap"><table class="t"><thead><tr><?php foreach ($columns as $c): ?><th<?= $isMoney($c) ? ' class="num"' : '' ?>><?= esc($c) ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
  <?php foreach ($columns as $c): $v = $r[$c] ?? null; ?>
    <td<?= $isMoney($c) ? ' class="num"' : '' ?>><?php
      if ($v === null || $v === '') { echo '—'; }
      elseif ($isDate($v)) { echo esc(strlen($v) > 10 ? crm_dt($v) : crm_date($v)); }
      elseif ($isMoney($c) && is_numeric($v)) { echo esc(crm_money($v)); }
      elseif ($c === 'Status' || $c === 'Decision' || $c === 'Outcome' && in_array($v, ['won', 'lost', 'on_hold'], true)) { echo crm_badge((string) $v); }
      else { echo esc(mb_strimwidth((string) $v, 0, 140, '…')); }
    ?></td>
  <?php endforeach; ?></tr>
<?php endforeach; ?></tbody></table></div>
<?= view('Crm\Views\partials\wa_pager', compact('total', 'per', 'page')) ?>
<?php endif; ?></div></div>
