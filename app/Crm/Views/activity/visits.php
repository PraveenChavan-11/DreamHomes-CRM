<?php
/** @var string $tab @var array $rows @var int $total @var string $pager */
?>
<div class="pagehead"><h1>Site visits</h1></div>
<nav class="tabs" aria-label="Visit lists">
  <?php foreach (['scheduled' => 'Scheduled', 'completed' => 'Completed', 'all' => 'All'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= site_url('crm/visits?tab=' . $k) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($l) ?></a><?php endforeach; ?>
</nav>
<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong>No site visits here</strong>Schedule visits from an enquiry's action bar.</div>
<?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Visit</th><th>When</th><th>Customer / enquiry</th><th>Assigned to</th><th>Status</th><th>Address</th><th class="num">Measurement versions</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $v): $late = $v['status'] === 'scheduled' && $v['scheduled_at'] < date('Y-m-d H:i:s'); ?>
    <tr><td><a href="<?= site_url('crm/visits/' . (int) $v['id']) ?>"><strong><?= esc($v['visit_no']) ?></strong></a></td>
      <td class="nowrap"><?= crm_dt($v['scheduled_at']) ?><?= $late ? '<div><span class="badge badge-amber">Awaiting completion</span></div>' : '' ?></td>
      <td><?= esc($v['customer_name']) ?><div class="muted small"><a href="<?= site_url('crm/enquiries/' . (int) $v['enquiry_id']) ?>"><?= esc($v['enquiry_no']) ?></a> · <?= esc($v['project_name'] ?: '') ?></div></td>
      <td><?= esc($v['user_name']) ?></td><td><?= crm_badge($v['status']) ?></td><td><?= esc($v['address'] ?: '—') ?></td><td class="num"><?= (int) $v['meas_versions'] ?></td>
      <td><a class="btn alt sm" href="<?= site_url('crm/visits/' . (int) $v['id']) ?>"><?= $v['status'] === 'scheduled' ? 'Record visit' : 'Open' ?></a></td></tr>
  <?php endforeach; ?></tbody></table></div><?= $pager ?><?php endif; ?>
</div></div>
