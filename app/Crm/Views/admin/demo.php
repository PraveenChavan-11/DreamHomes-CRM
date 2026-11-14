<?php /** @var bool $envOn $settingOn $iAmDemo @var string $phrase @var array $registry $log */
$ready = $envOn && $settingOn; ?>
<div class="pagehead"><h1>Demo Data</h1></div>
<div class="grid g3 mb">
  <div class="card kpi <?= $envOn ? 'good' : 'warn' ?>"><div class="l">Environment flag DHIS_DEMO_ENV</div><div class="v"><?= $envOn ? 'Demo server' : 'Not demo' ?></div><div class="s">Set only on the designated demo server.</div></div>
  <div class="card kpi <?= $settingOn ? 'good' : 'warn' ?>"><div class="l">Setting demo.enabled</div><div class="v"><?= $settingOn ? 'Enabled' : 'Disabled' ?></div><div class="s">Explicit opt-in required.</div></div>
  <div class="card kpi"><div class="l">Demo-owned records</div><div class="v"><?= array_sum(array_column($registry, 'n')) ?></div><div class="s">Tracked in the demo registry.</div></div>
</div>
<div class="grid g-side">
<div class="card"><div class="hd"><h2>Reset demo data</h2></div><div class="bd">
<?php if (! $ready): ?><div class="flash err">Reset is <strong>blocked</strong> here: this is not a designated demo environment with demo mode enabled. Production data can never be reset from this screen.</div><?php endif; ?>
<p>Resetting takes a recoverable JSON snapshot, removes <strong>only demo-owned rows</strong> (no truncation), rebuilds the deterministic fictional dataset, then verifies referential integrity. Configuration and non-demo users are preserved.</p>
<?php if ($iAmDemo): ?><p class="flash info">You are signed in as a demo user: the reset recreates demo accounts, so you will need to sign in again afterwards.</p><?php endif; ?>
<form method="post" action="<?= site_url('crm/demo/reset') ?>" data-validate data-confirm="Reset all demo data now? A snapshot is taken first."><?= csrf_field() ?>
  <label class="req" for="cf">Type <code><?= esc($phrase) ?></code> to confirm</label>
  <input id="cf" name="confirm" required autocomplete="off" pattern="<?= esc($phrase) ?>" data-msg="Type exactly: <?= esc($phrase) ?>" style="max-width:280px"><?= crm_err('confirm') ?>
  <div class="mt"><button class="btn danger" type="submit"<?= $ready ? '' : ' disabled' ?>>Reset demo data</button></div></form></div></div>
<div class="card"><div class="hd"><h2>Registry</h2></div><div class="bd flush">
<?php if (! $registry): ?><div class="empty">No demo records registered.</div><?php else: ?><table class="t"><tbody><?php foreach ($registry as $r): ?><tr><td><?= esc($r['table_name']) ?></td><td class="num"><?= (int) $r['n'] ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
</div>
<div class="card mt"><div class="hd"><h2>Reset history</h2></div><div class="bd flush tablewrap">
<?php if (! $log): ?><div class="empty">No resets have been performed.</div><?php else: ?>
<table class="t"><thead><tr><th>When</th><th>By</th><th class="num">Removed</th><th class="num">Created</th><th>Integrity</th><th>Snapshot file</th></tr></thead><tbody>
<?php foreach ($log as $l): ?><tr><td class="nowrap"><?= crm_dt($l['created_at']) ?></td><td><?= esc($l['by_name'] ?: 'CLI / system') ?></td><td class="num"><?= (int) $l['rows_removed'] ?></td><td class="num"><?= (int) $l['rows_created'] ?></td>
<td><?= (int) $l['integrity_ok'] ? crm_badge('approved') : crm_badge('failed') . ' <span class="small">' . esc($l['notes']) . '</span>' ?></td><td class="small"><?= esc(basename((string) $l['snapshot_path'])) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
