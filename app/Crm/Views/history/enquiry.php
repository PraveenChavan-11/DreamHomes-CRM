<?php
/** @var array $e @var list<array> $events @var list<array> $owners @var list<array> $approvals @var list<array> $quotes @var list<array> $emails */
?>
<div class="pagehead"><h1>Transaction <?= esc($e['enquiry_no']) ?> <?= crm_badge($e['status']) ?>
  <div class="muted small" style="font-weight:400"><a href="<?= site_url('crm/customers/' . (int) $e['customer_id']) ?>"><?= esc($e['customer_no'] . ' · ' . $e['customer_name']) ?></a><?= $e['project_name'] ? ' · ' . esc($e['project_name']) : '' ?> · current owner <?= esc($e['owner_name'] ?? 'unassigned') ?></div></h1>
  <a class="btn alt" href="<?= site_url('crm/enquiries/' . (int) $e['id']) ?>">Open enquiry</a>
  <a class="btn alt" href="<?= site_url('crm/history') ?>">&larr; All history</a></div>
<div class="grid g-side mb">
  <div class="card"><div class="hd"><h2>Chronological event history</h2></div><div class="bd"><?= view('Crm\Views\partials\wa_timeline', ['events' => $events]) ?></div></div>
  <div class="grid" style="align-content:start">
    <div class="card"><div class="hd"><h3>Ownership history</h3></div><div class="bd flush">
      <?php if (! $owners): ?><div class="empty">No assignments.</div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
      <?php foreach ($owners as $o): ?><tr><td class="small nowrap"><?= esc(crm_dt($o['created_at'])) ?></td><td><?= esc($o['from_name'] ?? '—') ?> &rarr; <strong><?= esc($o['to_name'] ?? '—') ?></strong><div class="muted small">by <?= esc($o['by_name']) ?><?= ! empty($o['reason']) ? ' · ' . esc($o['reason']) : '' ?></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
    <div class="card"><div class="hd"><h3>Approval history</h3></div><div class="bd flush">
      <?php if (! $approvals): ?><div class="empty">No discount approvals.</div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
      <?php foreach ($approvals as $a): ?><tr><td><a href="<?= site_url('crm/negotiations/' . (int) $a['negotiation_id']) ?>"><?= esc($a['negotiation_no']) ?></a><div class="muted small"><?= esc($a['quotation_no']) ?> · <?= esc(number_format((float) $a['requested_discount_pct'], 1)) ?>% · level <?= (int) $a['level'] ?></div></td>
        <td><?= crm_badge($a['decision']) ?><div class="muted small"><?= esc($a['approver_name'] ?? '') ?> <?= $a['decided_at'] ? esc(crm_dt($a['decided_at'])) : '' ?></div><?= $a['remarks'] ? '<div class="small">“' . esc($a['remarks']) . '”</div>' : '' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  </div>
</div>
<div class="grid g2">
  <div class="card"><div class="hd"><h2>Quotation versions</h2></div><div class="bd flush">
    <?php if (! $quotes): ?><div class="empty"><strong>No quotations</strong></div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Quotation</th><th>Version</th><th class="num">Total</th><th class="num">Discount</th><th>Created</th></tr></thead><tbody>
    <?php foreach ($quotes as $q): foreach ($q['versions'] as $i => $v): ?>
      <tr><td><?= $i === 0 ? '<a href="' . site_url('crm/quotations/' . (int) $q['id']) . '"><strong>' . esc($q['quotation_no']) . '</strong></a> ' . crm_badge($q['status']) : '' ?></td>
        <td>v<?= (int) $v['version_no'] ?><?= $v['revision_reason'] ? '<div class="muted small">' . esc($v['revision_reason']) . '</div>' : '' ?></td><td class="num"><?= crm_money($v['grand_total']) ?></td><td class="num"><?= crm_money($v['discount_total']) ?></td><td class="small nowrap"><?= esc(crm_dt($v['created_at'])) ?></td></tr>
    <?php endforeach; if (count($q['versions']) > 1): ?>
      <tr><td colspan="5" class="small"><a href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/compare?a=1&b=' . (int) end($q['versions'])['version_no']) ?>">Compare v1 with v<?= (int) end($q['versions'])['version_no'] ?> &rarr;</a></td></tr>
    <?php endif; endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <div class="card"><div class="hd"><h2>Customer communication</h2></div><div class="bd flush">
    <?php if (! $emails): ?><div class="empty"><strong>No emails for this enquiry</strong></div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
    <?php foreach ($emails as $m): ?><tr><td class="small nowrap"><?= esc(crm_dt($m['created_at'])) ?></td><td><?= crm_can('emails.view') ? '<a href="' . site_url('crm/emails/' . (int) $m['id']) . '">' . esc($m['subject']) . '</a>' : esc($m['subject']) ?><div class="muted small"><?= esc($m['to_email']) ?></div></td><td><?= crm_badge($m['status']) ?><?= $m['failure_reason'] ? '<div class="small muted">' . esc($m['failure_reason']) . '</div>' : '' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
</div>
