<?php
/** @var array $run @var array $catalogue @var list<string> $categories */
$score = (float) ($run['health_score'] ?? 100);
$tone = $score >= 90 ? 'good' : ($score >= 70 ? 'warn' : 'danger');
$statusBadge = match ($run['status'] ?? 'passed') {
    'passed' => 'badge-green',
    'warning' => 'badge-amber',
    default => 'badge-red',
};
?>
<div class="pagehead">
  <div>
    <h1>Auto QA Command Centre</h1>
    <p class="muted small">Intelligent Automated Quality Assurance &amp; Integrity Audit Engine for DHIS Sales Operations.</p>
  </div>
  <div class="gap">
    <form method="post" action="<?= site_url('crm/qa/run') ?>" style="display:inline"><?= csrf_field() ?>
      <button class="btn teal sm" type="submit" id="btn-run-all-qa">
        <?= crm_icon('M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 16) ?>
        Run All Checks
      </button>
    </form>
    <a class="btn alt sm" href="<?= site_url('crm/health') ?>">Server Health</a>
  </div>
</div>

<!-- Primary Health Dashboard -->
<div class="grid g4 mb">
  <div class="card kpi <?= $tone ?>">
    <div class="l">System Health Score</div>
    <div class="v"><?= number_format($score, 1) ?>%</div>
    <div class="s">Status: <span class="badge <?= $statusBadge ?>"><?= strtoupper(esc($run['status'] ?? 'passed')) ?></span></div>
  </div>
  <div class="card kpi good">
    <div class="l">Passed Checks</div>
    <div class="v"><?= (int) ($run['passed_checks'] ?? 0) ?></div>
    <div class="s">Out of <?= (int) ($run['total_checks'] ?? 0) ?> total audits</div>
  </div>
  <div class="card kpi <?= ! empty($run['failed_checks']) ? 'danger' : '' ?>">
    <div class="l">Critical / Failed Findings</div>
    <div class="v"><?= (int) ($run['failed_checks'] ?? 0) ?></div>
    <div class="s"><?= empty($run['failed_checks']) ? '0 integrity violations' : 'Requires immediate attention' ?></div>
  </div>
  <div class="card kpi <?= ! empty($run['warning_checks']) ? 'warn' : '' ?>">
    <div class="l">Warnings &amp; Operational Lead</div>
    <div class="v"><?= (int) ($run['warning_checks'] ?? 0) ?></div>
    <div class="s">Latency: <?= (int) ($run['duration_ms'] ?? 0) ?> ms</div>
  </div>
</div>

<!-- Category Trigger Hub -->
<div class="card mb">
  <div class="hd">
    <h2>Execute QA By Category</h2>
    <span class="muted small">Run focused verification on specific business subsystems.</span>
  </div>
  <div class="bd gap">
    <form method="post" action="<?= site_url('crm/qa/run') ?>" class="gap"><?= csrf_field() ?>
      <input type="hidden" name="category" value="">
      <button class="btn alt sm" type="submit">All Subsystems</button>
    </form>
    <?php foreach ($categories as $cat): ?>
      <form method="post" action="<?= site_url('crm/qa/run') ?>" class="gap" style="display:inline"><?= csrf_field() ?>
        <input type="hidden" name="category" value="<?= esc($cat, 'attr') ?>">
        <button class="btn alt sm" type="submit"><?= esc($cat) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<!-- QA Findings & Results Data Grid -->
<div class="card mb">
  <div class="hd">
    <h2>Audit Findings &amp; Evidence Log (Run: <?= esc($run['run_uuid'] ?? 'Initial') ?>)</h2>
    <span class="muted small">Refreshed: <?= esc(crm_dt($run['created_at'] ?? null)) ?></span>
  </div>
  <div class="bd flush tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Check ID</th>
          <th>Subsystem</th>
          <th>Verification Test</th>
          <th>Severity</th>
          <th>Status</th>
          <th class="num">Latency</th>
          <th>Actual Findings / Evidence</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (($run['findings'] ?? []) as $f):
          $sTone = match ($f['status']) {
              'passed' => 'badge-green',
              'warning' => 'badge-amber',
              'skipped' => 'badge-grey',
              default => 'badge-red',
          };
          $sevTone = match ($f['severity']) {
              'critical' => 'badge-red',
              'high' => 'badge-amber',
              default => 'badge-grey',
          };
      ?>
        <tr>
          <td class="nowrap"><code><?= esc($f['check_id']) ?></code></td>
          <td class="nowrap"><strong><?= esc($f['category']) ?></strong></td>
          <td>
            <strong><?= esc($f['check_name']) ?></strong>
            <div class="muted small">Expected: <?= esc($f['expected_result'] ?? 'Pass') ?></div>
          </td>
          <td><span class="badge <?= $sevTone ?>"><?= strtoupper(esc($f['severity'])) ?></span></td>
          <td><span class="badge <?= $sTone ?>"><?= strtoupper(esc($f['status'])) ?></span></td>
          <td class="num"><?= (int) $f['duration_ms'] ?> ms</td>
          <td>
            <div><?= esc($f['actual_result'] ?? '—') ?></div>
            <?php if (! empty($f['evidence'])): ?>
              <div class="muted small" style="font-family:monospace;margin-top:2px"><?= esc($f['evidence']) ?></div>
            <?php endif; ?>
            <?php if (! empty($f['remediation_plan'])): ?>
              <div style="color:var(--amber);font-size:11.5px;font-weight:600;margin-top:3px">Remediation: <?= esc($f['remediation_plan']) ?></div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

