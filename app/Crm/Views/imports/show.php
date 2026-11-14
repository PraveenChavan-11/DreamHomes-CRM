<?php
/** @var array $job @var array $headers @var array $map @var array $fields @var array $labels @var array $required @var array $rows @var string $filter @var array $counts @var string $owner */
$id = (int) $job['id'];
$st = $job['status'];
$step = match ($st) { 'uploaded' => 2, 'previewed', 'approved' => 4, 'completed' => 5, default => 2 };
$steps = ['Upload', 'Map columns', 'Validate', 'Approve', 'Result'];
$mapped = static fn ($f) => $map[$f] ?? '';
?>
<div class="pagehead"><h1>Import #<?= $id ?> <span class="muted" style="font-weight:500">· <?= esc($job['original_name']) ?></span></h1><?= crm_badge($st) ?><a class="btn alt" href="<?= site_url('crm/imports') ?>">All imports</a></div>

<ol class="gap" style="list-style:none;padding:0;margin:0 0 16px" aria-label="Progress">
  <?php foreach ($steps as $i => $s): $n = $i + 1; ?><li class="badge <?= $n < $step ? 'badge-green' : ($n === $step ? 'badge-blue' : 'badge-grey') ?>"<?= $n === $step ? ' aria-current="step"' : '' ?>><?= $n ?>. <?= esc($s) ?></li><?php endforeach; ?>
</ol>

<div class="grid g4 mb">
  <div class="card kpi"><div class="l">Rows in file</div><div class="v"><?= (int) $job['total_rows'] ?></div><div class="s">Uploaded by <?= esc($owner) ?>, <?= crm_dt($job['created_at']) ?></div></div>
  <div class="card kpi good"><div class="l">Valid</div><div class="v"><?= (int) ($st === 'completed' ? ($counts['imported'] ?? 0) : ($counts['valid'] ?? 0)) ?></div><div class="s"><?= $st === 'completed' ? 'Imported' : 'Ready to import' ?></div></div>
  <div class="card kpi <?= ! empty($counts['invalid']) ? 'warn' : '' ?>"><div class="l">Invalid</div><div class="v"><?= (int) ($counts['invalid'] ?? 0) ?></div><div class="s">Need correction</div></div>
  <div class="card kpi"><div class="l">Duplicate</div><div class="v"><?= (int) ($counts['duplicate'] ?? 0) ?></div><div class="s">Skipped</div></div>
</div>

<?php if ($st === 'completed'): ?>
  <div class="flash ok" style="display:block"><strong>Import complete.</strong> <?= (int) $job['imported_rows'] ?> enquiries were created on <?= crm_dt($job['completed_at']) ?>. The file can't be imported again.
    <a href="<?= site_url('crm/enquiries') ?>">View enquiries</a></div>
<?php elseif ($st === 'failed'): ?>
  <div class="flash err" style="display:block"><strong>This import failed and was rolled back.</strong> No enquiries were created. Fix the file and upload it again.</div>
<?php endif; ?>

<?php if (in_array($st, ['uploaded', 'previewed'], true)): ?>
<form class="card mb" method="post" action="<?= site_url("crm/imports/{$id}/mapping") ?>" data-validate>
  <?= csrf_field() ?><div class="hd"><h2>Column mapping</h2><span class="muted small">Matched automatically where possible — adjust if needed</span></div>
  <div class="bd"><div class="form">
    <?php foreach ($labels as $f => $label): $isReq = in_array($f, $required, true); ?>
      <div><label for="map-<?= $f ?>"<?= $isReq ? ' class="req"' : '' ?>><?= esc($label) ?></label>
        <select id="map-<?= $f ?>" name="map[<?= $f ?>]"<?= $isReq ? ' required' : '' ?>><option value="">— not in file —</option>
          <?php foreach ($headers as $h): ?><option value="<?= esc($h) ?>"<?= crm_sel($mapped($f), $h) ?>><?= esc($h === '' ? '(blank heading)' : $h) ?></option><?php endforeach; ?></select></div>
    <?php endforeach; ?>
  </div></div>
  <div class="bd gap" style="border-top:1px solid var(--line-2)"><button class="btn alt" type="submit">Save mapping</button><span class="muted small">Saving the mapping clears earlier validation results.</span></div>
</form>

<?php $missingMap = array_diff($required, array_keys($map)); ?>
<form class="card mb" method="post" action="<?= site_url("crm/imports/{$id}/validate") ?>">
  <?= csrf_field() ?><div class="bd gap"><div style="flex:1"><strong>Validate rows</strong><div class="muted small">Checks mobile numbers, emails, categories, dates, budgets and duplicates (in the file and against open enquiries). Nothing is created yet.</div></div>
  <button class="btn teal" type="submit"<?= $missingMap ? ' disabled title="Save the required column mapping first"' : '' ?>>Validate</button></div>
</form>
<?php endif; ?>

<?php if ($st === 'previewed'): ?>
<form class="card mb" method="post" action="<?= site_url("crm/imports/{$id}/run") ?>" data-confirm="Create <?= (int) $job['valid_rows'] ?> enquiries from the valid rows? This cannot be repeated for the same file.">
  <?= csrf_field() ?><div class="bd gap"><div style="flex:1"><strong>Approve &amp; import</strong>
    <div class="muted small"><?= (int) $job['valid_rows'] ?> valid rows will be created (customers are matched by mobile or created). <?= (int) $job['invalid_rows'] + (int) $job['duplicate_rows'] ?> rows will be skipped.</div></div>
  <?php if ((int) $job['invalid_rows'] + (int) $job['duplicate_rows'] > 0): ?><a class="btn alt" href="<?= site_url("crm/imports/{$id}/errors") ?>">Download error report</a><?php endif; ?>
  <button class="btn" type="submit"<?= (int) $job['valid_rows'] === 0 ? ' disabled' : '' ?>>Approve &amp; import</button></div>
</form>
<?php elseif ($st === 'completed' && (int) $job['invalid_rows'] + (int) $job['duplicate_rows'] > 0): ?>
  <p><a class="btn alt" href="<?= site_url("crm/imports/{$id}/errors") ?>">Download error report (<?= (int) $job['invalid_rows'] + (int) $job['duplicate_rows'] ?> skipped rows)</a></p>
<?php endif; ?>

<div class="card"><div class="hd"><h2>Rows</h2>
  <nav class="gap" aria-label="Row filter"><?php foreach (['all' => 'All', 'valid' => 'Valid', 'invalid' => 'Invalid', 'duplicate' => 'Duplicate', 'imported' => 'Imported'] as $k => $l): ?>
    <a class="btn sm <?= $filter === $k ? '' : 'alt' ?>" href="<?= site_url("crm/imports/{$id}?rows={$k}") ?>"><?= esc($l) ?></a><?php endforeach; ?></nav></div>
  <div class="bd flush"><?php if (! $rows): ?><div class="empty">No rows to show.</div><?php else: ?><div class="tablewrap"><table class="t">
    <thead><tr><th>Row</th><th>Status</th><th>Message</th><?php foreach ($headers as $h): ?><th><?= esc($h) ?></th><?php endforeach; ?></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><?= (int) $r['row_no'] ?></td><td><?= crm_badge($r['status']) ?></td><td class="small"><?= esc($r['errors'] ?: '') ?><?php if ($r['entity_id']): ?> <a href="<?= site_url('crm/enquiries/' . (int) $r['entity_id']) ?>">View enquiry</a><?php endif; ?></td>
      <?php foreach ($headers as $h): ?><td><?= esc((string) ($r['data'][$h] ?? '')) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  </tbody></table></div><div class="pager"><span class="muted small">Showing up to 100 rows. The error report contains every skipped row.</span></div><?php endif; ?></div></div>
