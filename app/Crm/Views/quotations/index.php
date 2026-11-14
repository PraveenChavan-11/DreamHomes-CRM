<?php
/** @var list<array> $rows @var array $sum @var array $byStatus @var list<array> $owners @var array $g @var int $page @var int $per */
$statuses = ['draft', 'sent', 'negotiation', 'revised', 'accepted', 'rejected', 'expired', 'cancelled'];
?>
<div class="pagehead"><h1>Quotations</h1><?php if (crm_can('quotations.create')): ?><a class="btn teal" href="<?= site_url('crm/quotations/new') ?>">+ New quotation</a><?php endif; ?></div>

<div class="grid g4 mb">
  <div class="card kpi"><div class="l">Quotations (filtered)</div><div class="v"><?= number_format((int) $sum['n']) ?></div></div>
  <div class="card kpi"><div class="l">Total value</div><div class="v"><?= crm_money0($sum['total']) ?></div><div class="s">Current version of each quotation</div></div>
  <div class="card kpi good"><div class="l">Accepted</div><div class="v"><?= (int) ($byStatus['accepted'] ?? 0) ?></div></div>
  <div class="card kpi"><div class="l">Open (sent / negotiation / revised)</div><div class="v"><?= (int) ($byStatus['sent'] ?? 0) + (int) ($byStatus['negotiation'] ?? 0) + (int) ($byStatus['revised'] ?? 0) ?></div></div>
</div>

<form class="filters card" method="get" style="padding:14px 18px">
  <div class="f"><label for="fq">Search</label><input id="fq" type="search" name="q" value="<?= esc($g['q'] ?? '', 'attr') ?>" placeholder="QT no. or customer"></div>
  <div class="f"><label for="fs">Status</label><select id="fs" name="status"><option value="">All</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>"<?= crm_sel($g['status'] ?? '', $s) ?>><?= crm_label($s) ?></option><?php endforeach; ?></select></div>
  <?php if (count($owners) > 1): ?><div class="f"><label for="fo">Salesperson</label><select id="fo" name="owner_id"><option value="">All</option><?php foreach ($owners as $o): ?><option value="<?= (int) $o['id'] ?>"<?= crm_sel($g['owner_id'] ?? '', $o['id']) ?>><?= esc($o['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
  <div class="f"><label for="fe">Enquiry no.</label><input id="fe" name="enquiry" value="<?= esc($g['enquiry'] ?? '', 'attr') ?>" placeholder="ENQ-…"></div>
  <div class="f"><label for="ff">From</label><input id="ff" type="date" name="from" value="<?= esc($g['from'] ?? '', 'attr') ?>"></div>
  <div class="f"><label for="ft">To</label><input id="ft" type="date" name="to" value="<?= esc($g['to'] ?? '', 'attr') ?>"></div>
  <div class="f"><button class="btn" type="submit">Filter</button> <a class="btn alt" href="<?= site_url('crm/quotations') ?>">Reset</a></div>
</form>

<div class="card">
<?php if (! $rows): ?>
  <div class="empty"><strong>No quotations match</strong>Adjust the filters, or create a quotation from an enquiry.</div>
<?php else: ?>
  <div class="bd flush tablewrap"><table class="t">
    <thead><tr><th>Quotation</th><th>Customer · project</th><th>Enquiry</th><th>Salesperson</th><th>Status</th><th class="num">Version</th><th class="num">Discount</th><th class="num">Total</th><th>Valid until</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="<?= site_url('crm/quotations/' . (int) $r['id']) ?>"><strong><?= esc($r['quotation_no']) ?></strong></a></td>
        <td><a href="<?= site_url('crm/customers/' . (int) $r['customer_id']) ?>"><?= esc($r['customer_name']) ?></a><div class="small muted"><?= esc((string) $r['project_name']) ?></div></td>
        <td><a href="<?= site_url('crm/enquiries/' . (int) $r['enquiry_id']) ?>"><?= esc($r['enquiry_no']) ?></a></td>
        <td><?= esc((string) $r['owner_name'] ?: '—') ?></td>
        <td><?= crm_badge($r['status']) ?><?= (int) $r['is_locked'] ? '' : ' <span class="small muted">(editable)</span>' ?></td>
        <td class="num">v<?= (int) $r['version_no'] ?></td>
        <td class="num"><?= crm_money($r['discount_total']) ?></td>
        <td class="num"><strong><?= crm_money($r['grand_total']) ?></strong></td>
        <td class="nowrap"><?= crm_date($r['valid_until']) ?></td>
        <td class="nowrap"><?= crm_date($r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => (int) $sum['n'], 'per' => $per, 'page' => $page]) ?>
<?php endif; ?>
</div>
