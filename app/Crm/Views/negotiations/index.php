<?php
/** @var list<array> $rows @var string $tab @var array $counts @var array $g @var int $total @var int $page @var int $per */
$tabs = ['pending' => 'Pending my approval', 'mine' => 'My requests', 'all' => 'All'];
$qs   = static fn (string $t) => '?' . http_build_query(['tab' => $t]);
?>
<div class="pagehead"><h1>Negotiations &amp; discount approvals</h1><a class="btn alt" href="<?= site_url('crm/quotations') ?>">Open quotations</a></div>
<div class="tabs" role="tablist">
  <?php foreach ($tabs as $k => $lab): ?><a role="tab" class="<?= $tab === $k ? 'on' : '' ?>" href="<?= esc($qs($k), 'attr') ?>"><?= $lab ?> <span class="badge badge-grey"><?= (int) $counts[$k] ?></span></a><?php endforeach; ?>
</div>
<form class="filters card" method="get" style="padding:14px 18px"><input type="hidden" name="tab" value="<?= esc($tab, 'attr') ?>">
  <div class="f"><label for="nq">Search</label><input id="nq" type="search" name="q" value="<?= esc($g['q'] ?? '', 'attr') ?>" placeholder="NEG / QT no. or customer"></div>
  <div class="f"><label for="ns">Status</label><select id="ns" name="status"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected', 'returned', 'escalated', 'applied', 'withdrawn'] as $s): ?><option value="<?= $s ?>"<?= crm_sel($g['status'] ?? '', $s) ?>><?= crm_label($s) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="nf">From</label><input id="nf" type="date" name="from" value="<?= esc($g['from'] ?? '', 'attr') ?>"></div>
  <div class="f"><label for="nt">To</label><input id="nt" type="date" name="to" value="<?= esc($g['to'] ?? '', 'attr') ?>"></div>
  <div class="f"><button class="btn" type="submit">Filter</button> <a class="btn alt" href="<?= esc($qs($tab), 'attr') ?>">Reset</a></div>
</form>
<div class="card">
<?php if (! $rows): ?>
  <div class="empty"><strong><?= $tab === 'pending' ? 'Nothing is waiting for your approval' : 'No negotiations found' ?></strong>Requests appear here when a customer asks for a lower price on a sent quotation.</div>
<?php else: ?>
  <div class="bd flush tablewrap"><table class="t">
    <thead><tr><th>Negotiation</th><th>Quotation · customer</th><th>Requested by</th><th class="num">Quoted total</th><th class="num">Customer expects</th><th class="num">Ask</th><th class="num">Proposed price</th><th>Level</th><th>Status</th><th>Raised</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= site_url('crm/negotiations/' . (int) $r['id']) ?>"><strong><?= esc($r['negotiation_no']) ?></strong></a></td>
        <td><a href="<?= site_url('crm/quotations/' . (int) $r['qid']) ?>"><?= esc($r['quotation_no']) ?></a> v<?= (int) $r['version_no'] ?><div class="small muted"><?= esc($r['customer_name']) ?></div></td>
        <td><?= esc($r['requester']) ?></td>
        <td class="num"><?= crm_money($r['original_total']) ?></td>
        <td class="num"><?= $r['customer_expected_amount'] !== null ? crm_money($r['customer_expected_amount']) : '—' ?></td>
        <td class="num"><?= esc(number_format((float) $r['requested_discount_pct'], 2)) ?>%<div class="small muted"><?= crm_money($r['requested_discount_amount']) ?></div></td>
        <td class="num"><strong><?= crm_money($r['proposed_price']) ?></strong></td>
        <td><?= crm_label($r['required_level']) ?></td>
        <td><?= crm_badge($r['status']) ?></td>
        <td class="nowrap"><?= crm_date($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $per, 'page' => $page]) ?>
<?php endif; ?>
</div>
