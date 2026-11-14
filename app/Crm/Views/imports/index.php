<?php
/** @var array $jobs @var int $total @var string $pager @var float $maxMb @var int $maxRows */
?>
<div class="pagehead"><h1>Import enquiries</h1><a class="btn alt" href="<?= site_url('crm/imports/template') ?>">Download sample template</a></div>

<div class="grid g-side">
  <div class="card"><div class="hd"><h2>Upload a file</h2></div>
    <form class="bd" method="post" action="<?= site_url('crm/imports/upload') ?>" enctype="multipart/form-data" data-validate>
      <?= csrf_field() ?>
      <label for="file" style="font-weight:600;font-size:13px" class="req">Excel (.xlsx, .xls) or CSV file</label>
      <input id="file" type="file" name="file" required accept=".xlsx,.xls,.csv">
      <p class="hint">First row = column headings. Up to <?= (int) $maxMb ?> MB and <?= number_format($maxRows) ?> rows. Required: customer name, mobile, project name.
        The same file can only be imported once. You will preview, map columns and validate before anything is created.</p>
      <button class="btn teal" type="submit">Upload &amp; continue</button>
    </form></div>
  <div class="card"><div class="hd"><h3>How it works</h3></div><div class="bd"><ol style="margin:0 0 0 18px;padding:0;line-height:1.8">
    <li>Upload the file</li><li>Confirm the column mapping</li><li>Validate — invalid and duplicate rows are flagged</li><li>Approve — only valid rows are created</li><li>Download the error report for the rest</li></ol></div></div>
</div>

<div class="card mt"><div class="hd"><h2>Import history</h2></div><div class="bd flush">
<?php if (! $jobs): ?><div class="empty"><strong>No imports yet</strong>Uploaded files and their results appear here.</div>
<?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Job</th><th>File</th><th>Uploaded by</th><th>When</th><th>Status</th><th class="num">Rows</th><th class="num">Valid</th><th class="num">Invalid</th><th class="num">Duplicate</th><th class="num">Imported</th><th></th></tr></thead><tbody>
  <?php foreach ($jobs as $j): ?><tr><td>#<?= (int) $j['id'] ?></td><td><?= esc($j['original_name']) ?></td><td><?= esc($j['user_name'] ?: '—') ?></td><td class="nowrap"><?= crm_dt($j['created_at']) ?></td>
    <td><?= crm_badge($j['status']) ?></td><td class="num"><?= (int) $j['total_rows'] ?></td><td class="num"><?= (int) $j['valid_rows'] ?></td><td class="num"><?= (int) $j['invalid_rows'] ?></td>
    <td class="num"><?= (int) $j['duplicate_rows'] ?></td><td class="num"><?= (int) $j['imported_rows'] ?></td><td><a class="btn alt sm" href="<?= site_url('crm/imports/' . (int) $j['id']) ?>">Open</a></td></tr><?php endforeach; ?>
</tbody></table></div><?= $pager ?><?php endif; ?></div></div>
