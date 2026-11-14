<?php /** @var array $rows $f $users $entities @var int $total $page $pages */
$link = static function (array $r): string {
    $id = $r['entity_id'] ?? '';
    $map = ['customer' => 'customers', 'enquiry' => 'enquiries', 'quotation' => 'quotations', 'negotiation' => 'negotiations', 'sales_order' => 'orders', 'product' => 'products'];
    return isset($map[$r['entity_type']]) && ctype_digit((string) $id) ? '<a href="' . site_url('crm/' . $map[$r['entity_type']] . '/' . $id) . '">' . esc($r['entity_type'] . ' #' . $id) . '</a>' : esc($r['entity_type'] . ($id !== '' ? ' #' . $id : ''));
}; ?>
<div class="pagehead"><h1>Audit Trail</h1><span class="small muted">Append-only. Passwords and tokens are never stored in payloads.</span></div>
<div class="tabs"><a class="<?= $f['src'] === 'audit' ? 'on' : '' ?>" href="<?= site_url('crm/audit?src=audit') ?>">System audit events</a><a class="<?= $f['src'] === 'workflow' ? 'on' : '' ?>" href="<?= site_url('crm/audit?src=workflow') ?>">Business workflow events</a></div>
<form class="filters" method="get" action="<?= site_url('crm/audit') ?>"><input type="hidden" name="src" value="<?= esc($f['src']) ?>">
  <div class="f"><label for="en">Entity</label><select id="en" name="entity"><option value="">All</option><?php foreach ($entities as $e): ?><option value="<?= esc($e) ?>"<?= crm_sel($f['entity'], $e) ?>><?= esc($e) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="ev">Event starts with</label><input id="ev" name="event" value="<?= esc($f['event']) ?>" placeholder="e.g. quotation."></div>
  <div class="f"><label for="us">User</label><select id="us" name="user"><option value="">Anyone</option><?php foreach ($users as $id => $n): ?><option value="<?= (int) $id ?>"<?= crm_sel($f['user'], $id) ?>><?= esc($n) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="rf">Entity ID</label><input id="rf" name="ref" value="<?= esc($f['ref']) ?>" style="min-width:90px;width:90px"></div>
  <div class="f"><label for="fr">From</label><input id="fr" type="date" name="from" value="<?= esc($f['from']) ?>"></div>
  <div class="f"><label for="to">To</label><input id="to" type="date" name="to" value="<?= esc($f['to']) ?>"></div>
  <button class="btn" type="submit">Filter</button><a class="btn alt" href="<?= site_url('crm/audit?src=' . $f['src']) ?>">Reset</a></form>
<div class="card"><div class="bd flush tablewrap">
<?php if (! $rows): ?><div class="empty"><strong>No events</strong>Nothing matches these filters.</div><?php else: ?>
<table class="t"><thead><tr><th>#</th><th>When</th><th>Event</th><th>Entity</th><th>User</th><th>Source</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int) $r['id'] ?></td><td class="nowrap"><?= crm_dt($r['created_at']) ?></td><td><strong><?= esc($r['event_type']) ?></strong></td><td><?= $link($r) ?></td><td><?= esc($r['user_name']) ?></td><td class="small"><?= esc($r['source']) ?></td>
<td><?php if ($r['reason'] || $r['prev_state_pretty'] || $r['new_state_pretty'] || $r['metadata_pretty']): ?><details><summary class="small">View<?= $r['reason'] ? ' · ' . esc(mb_strimwidth($r['reason'], 0, 50, '…')) : '' ?></summary>
  <?php if ($r['reason']): ?><p class="small"><strong>Reason:</strong> <?= esc($r['reason']) ?></p><?php endif; ?>
  <?php foreach (['prev_state' => 'Previous', 'new_state' => 'New', 'metadata' => 'Metadata'] as $k => $l): if ($r[$k . '_pretty'] !== ''): ?><div class="small muted"><?= $l ?></div><pre style="margin:2px 0 8px;white-space:pre-wrap;font-size:12px;background:#f7f9fc;padding:8px;border-radius:6px;max-width:520px;overflow:auto"><?= esc($r[$k . '_pretty']) ?></pre><?php endif; endforeach; ?>
  <div class="small muted">Correlation <?= esc($r['correlation_id']) ?><?= ! empty($r['ip_address']) ? ' · IP ' . esc($r['ip_address']) : '' ?></div></details><?php else: ?>—<?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>
<?= view('Crm\Views\admin\_pager', ['page' => $page, 'pages' => $pages, 'total' => $total]) ?></div>
