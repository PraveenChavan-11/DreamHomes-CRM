<?php /** @var array $problems $jobs $dirs $ext $counts @var string $php $dbv $env $emailMode @var bool $https $demoEnv */ ?>
<div class="pagehead"><h1>System Health</h1><a class="btn alt" href="<?= site_url('crm/settings') ?>">&laquo; Settings</a></div>
<div class="grid g4 mb">
  <div class="card kpi <?= $problems ? 'warn' : 'good' ?>"><div class="l">Data integrity</div><div class="v"><?= $problems ? count($problems) . ' issue(s)' : 'Clean' ?></div><div class="s">Same checks as <code>php spark crm:verify</code></div></div>
  <div class="card kpi"><div class="l">PHP</div><div class="v" style="font-size:20px"><?= esc($php) ?></div><div class="s">Environment: <?= esc($env) ?></div></div>
  <div class="card kpi"><div class="l">Database</div><div class="v" style="font-size:20px"><?= esc($dbv) ?></div></div>
  <div class="card kpi <?= $https ? 'good' : '' ?>"><div class="l">HTTPS</div><div class="v" style="font-size:20px"><?= $https ? 'On' : 'Off' ?></div><div class="s">Email mode: <?= esc($emailMode) ?><?= $demoEnv ? ' · demo server' : '' ?></div></div>
</div>
<?php if ($problems): ?><div class="card mb"><div class="hd"><h2>Integrity issues</h2></div><div class="bd"><ul><?php foreach ($problems as $p): ?><li><?= esc($p) ?></li><?php endforeach; ?></ul></div></div><?php endif; ?>
<div class="grid g3 mb">
  <div class="card"><div class="hd"><h3>Folders</h3></div><div class="bd"><dl class="kv"><?php foreach ($dirs as $l => $s): ?><dt><?= esc($l) ?></dt><dd><?= crm_badge($s === 'writable' ? 'active' : 'failed') ?> <?= esc($s) ?></dd><?php endforeach; ?></dl></div></div>
  <div class="card"><div class="hd"><h3>PHP extensions</h3></div><div class="bd"><dl class="kv"><?php foreach ($ext as $x => $on): ?><dt><?= esc($x) ?></dt><dd><?= $on ? crm_badge('active') . ' loaded' : crm_badge('failed') . ' missing' ?></dd><?php endforeach; ?></dl></div></div>
  <div class="card"><div class="hd"><h3>Record counts</h3></div><div class="bd"><dl class="kv"><?php foreach ($counts as $l => $n): ?><dt><?= esc($l) ?></dt><dd><?= number_format((int) $n) ?></dd><?php endforeach; ?></dl></div></div>
</div>
<div class="card mb"><div class="hd"><h2>Scheduled jobs (cron)</h2></div><div class="bd">
  <p class="small muted">Add these to the hosting cron (adjust the PHP and project paths):</p>
  <pre style="background:#f7f9fc;padding:10px;border-radius:8px;overflow:auto;font-size:12.5px">*/10 * * * * cd /path/to/app &amp;&amp; php spark crm:reminders &gt;&gt; writable/logs/cron.log 2&gt;&amp;1
17 2 * * *   cd /path/to/app &amp;&amp; php spark crm:verify    &gt;&gt; writable/logs/cron.log 2&gt;&amp;1</pre></div>
<div class="bd flush tablewrap" style="border-top:1px solid var(--line-2)">
<?php if (! $jobs): ?><div class="empty"><strong>No job runs recorded yet</strong>The reminder job has not been executed by cron.</div><?php else: ?>
<table class="t"><thead><tr><th>Job</th><th>Status</th><th>Started</th><th class="num">Items</th><th class="num">Duration</th><th>Error</th></tr></thead><tbody>
<?php foreach ($jobs as $j): ?><tr><td><?= esc($j['job_name']) ?></td><td><?= crm_badge($j['status'] === 'SUCCESS' ? 'completed' : ($j['status'] === 'FAILED' ? 'failed' : 'pending')) ?> <span class="small"><?= esc(crm_label(strtolower($j['status']))) ?></span></td><td class="nowrap"><?= crm_dt($j['started_at']) ?></td><td class="num"><?= (int) $j['items_processed'] ?></td><td class="num"><?= (int) $j['duration_ms'] ?> ms</td><td class="small"><?= esc(mb_strimwidth((string) $j['error_details'], 0, 120, '…')) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
