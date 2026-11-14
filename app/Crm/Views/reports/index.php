<?php /** @var array<string,array<string,string>> $groups */ ?>
<div class="pagehead"><h1>Reports</h1></div>
<p class="muted">Every report is built from live CRM records, respects your access scope and can be filtered and exported.</p>
<?php if (! $groups): ?><div class="card"><div class="empty"><strong>No reports available for your role</strong></div></div><?php endif; ?>
<?php foreach ($groups as $g => $reports): ?>
  <h2 class="mt mb"><?= esc($g) ?></h2>
  <div class="grid g3">
  <?php foreach ($reports as $k => $title): ?>
    <a class="card kpi" href="<?= site_url('crm/reports/' . $k) ?>"><div class="l">Report</div><div class="v" style="font-size:18px"><?= esc($title) ?></div><div class="s">Open, filter &amp; export &rarr;</div></a>
  <?php endforeach; ?>
  </div>
<?php endforeach; ?>
