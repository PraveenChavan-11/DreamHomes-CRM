<?php
/** @var string $tab @var array $rows @var int $total @var string $pager */
?>
<div class="pagehead"><h1>Site visits</h1></div>
<nav class="tabs" aria-label="Visit lists">
  <?php foreach (['scheduled' => 'Scheduled', 'completed' => 'Completed', 'all' => 'All'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= site_url('crm/visits?tab=' . $k) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($l) ?></a><?php endforeach; ?>
</nav>
<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong>No site visits here</strong>Schedule visits from an enquiry's action bar.</div>
<?php else: ?><div class="tablewrap"><table class="t" style="min-width:1050px"><thead><tr><th style="min-width:100px">Visit</th><th style="min-width:140px">When</th><th style="min-width:210px">Customer / enquiry</th><th style="min-width:130px">Assigned to</th><th style="min-width:110px">Status</th><th style="min-width:180px">Address</th><th class="num" style="min-width:130px">Measurement versions</th><th style="min-width:100px;text-align:right">Action</th></tr></thead><tbody>
  <?php foreach ($rows as $v): $late = $v['status'] === 'scheduled' && $v['scheduled_at'] < date('Y-m-d H:i:s'); ?>
    <tr><td><a href="<?= site_url('crm/visits/' . (int) $v['id']) ?>"><strong><?= esc($v['visit_no']) ?></strong></a></td>
      <td class="nowrap"><?= crm_dt($v['scheduled_at']) ?><?= $late ? '<div style="margin-top:4px"><span class="badge badge-amber">Awaiting completion</span></div>' : '' ?></td>
      <td><strong><?= esc($v['customer_name']) ?></strong><div class="muted small"><a href="<?= site_url('crm/enquiries/' . (int) $v['enquiry_id']) ?>"><?= esc($v['enquiry_no']) ?></a> · <?= esc($v['project_name'] ?: 'Enquiry Site') ?></div></td>
      <td><?= esc($v['user_name']) ?></td><td><?= crm_badge($v['status']) ?></td><td><?= esc($v['address'] ?: '—') ?></td><td class="num" style="font-weight:600"><?= (int) $v['meas_versions'] ?></td>
      <td style="text-align:right"><a class="btn alt sm" href="<?= site_url('crm/visits/' . (int) $v['id']) ?>"><?= $v['status'] === 'scheduled' ? 'Record visit' : 'Open' ?></a></td></tr>
  <?php endforeach; ?></tbody></table></div><?= $pager ?><?php endif; ?>
</div></div>
