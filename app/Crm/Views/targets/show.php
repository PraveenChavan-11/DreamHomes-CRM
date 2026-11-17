<?php
/** @var array $target */
$t = $target;
$revPct = (float) ($t['revenue_progress_pct'] ?? 0);
$ordersPct = ! empty($t['orders_target']) ? round(((int) ($t['actual_orders'] ?? 0) / (int) $t['orders_target']) * 100, 1) : 0;
$actPct = ! empty($t['activities_target']) ? round(((int) ($t['actual_activities'] ?? 0) / (int) $t['activities_target']) * 100, 1) : 0;
$quotesPct = ! empty($t['quotations_target']) ? round(((int) ($t['actual_quotations'] ?? 0) / (int) $t['quotations_target']) * 100, 1) : 0;
$collPct = ! empty($t['collections_target']) ? round(((float) ($t['actual_collections'] ?? 0) / (float) $t['collections_target']) * 100, 1) : 0;
?>
<div class="pagehead">
  <div>
    <h1>
      Target Performance · <?= esc($t['period_label']) ?>
      <span class="badge badge-grey"><?= esc(crm_label($t['frequency'])) ?></span>
    </h1>
    <div class="muted small" style="font-weight:400">
      Target #<?= esc($t['target_no']) ?> · Assignee: <strong><?= esc($t['salesperson_name'] ?: ($t['team_name'] ?: 'Company Fleet')) ?></strong>
      · Period: <?= esc(crm_date($t['period_start'])) ?> to <?= esc(crm_date($t['period_end'])) ?>
    </div>
  </div>
  <div class="gap">
    <?php if (crm_can('settings.manage', 'users.manage')): ?>
      <a class="btn alt" href="<?= site_url('crm/targets/' . $t['id'] . '/edit') ?>">Revise Quota</a>
    <?php endif; ?>
    <a class="btn alt" href="<?= site_url('crm/targets') ?>">Back to Targets</a>
  </div>
</div>

<!-- Primary Revenue Achievement Bar -->
<div class="card" style="margin-bottom:18px">
  <div class="hd">
    <h2>Revenue Target vs Live Actuals</h2>
    <span class="muted small">Orders confirmed between <?= esc(crm_date($t['period_start'])) ?> and <?= esc(crm_date($t['period_end'])) ?></span>
  </div>
  <div class="bd">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px">
      <div>
        <div class="muted small" style="text-transform:uppercase;font-weight:600">Net Won Order Value</div>
        <div style="font-size:28px;font-weight:800;color:var(--th-primary)"><?= crm_money($t['actual_revenue'] ?? ($t['achieved_revenue'] ?? 0)) ?></div>
      </div>
      <div style="text-align:right">
        <div class="muted small" style="text-transform:uppercase;font-weight:600">Period Target</div>
        <div style="font-size:20px;font-weight:700"><?= crm_money($t['revenue_target'] ?? 0) ?></div>
      </div>
    </div>

    <div style="height:12px;background:#E2E8F0;border-radius:6px;overflow:hidden;margin-bottom:6px">
      <div style="height:100%;width:<?= min(100, $revPct) ?>%;background:<?= $revPct >= 100 ? '#10B981' : ($revPct >= 65 ? '#0284C7' : ($revPct >= 35 ? '#F59E0B' : '#EF4444')) ?>"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600">
      <span><?= number_format($revPct, 1) ?>% Achieved</span>
      <span class="muted"><?= $revPct >= 100 ? '🎉 Target exceeded!' : crm_money(($t['revenue_target'] ?? 0) - ($t['actual_revenue'] ?? ($t['achieved_revenue'] ?? 0))) . ' remaining to goal' ?></span>
    </div>
  </div>
</div>

<!-- Multi-Dimensional Performance Grid -->
<div class="grid g4" style="margin-bottom:18px">
  <div class="card">
    <div class="hd"><h3>Sales Orders</h3></div>
    <div class="bd">
      <div style="font-size:24px;font-weight:800;color:var(--th-dark);margin-bottom:4px">
        <?= (int) ($t['actual_orders'] ?? 0) ?> <span style="font-size:15px;color:var(--text-muted);font-weight:400">/ <?= (int) ($t['orders_target'] ?? 0) ?></span>
      </div>
      <div style="height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;margin-bottom:4px">
        <div style="height:100%;width:<?= min(100, $ordersPct) ?>%;background:#0284C7"></div>
      </div>
      <div class="small muted"><?= $ordersPct ?>% order goal completed</div>
    </div>
  </div>

  <div class="card">
    <div class="hd"><h3>Client Visits &amp; Meetings</h3></div>
    <div class="bd">
      <div style="font-size:24px;font-weight:800;color:var(--th-dark);margin-bottom:4px">
        <?= (int) ($t['actual_activities'] ?? 0) ?> <span style="font-size:15px;color:var(--text-muted);font-weight:400">/ <?= (int) ($t['activities_target'] ?? 0) ?></span>
      </div>
      <div style="height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;margin-bottom:4px">
        <div style="height:100%;width:<?= min(100, $actPct) ?>%;background:#0D9488"></div>
      </div>
      <div class="small muted"><?= $actPct ?>% customer touchpoints</div>
    </div>
  </div>

  <div class="card">
    <div class="hd"><h3>Quotations Generated</h3></div>
    <div class="bd">
      <div style="font-size:24px;font-weight:800;color:var(--th-dark);margin-bottom:4px">
        <?= (int) ($t['actual_quotations'] ?? 0) ?> <span style="font-size:15px;color:var(--text-muted);font-weight:400">/ <?= (int) ($t['quotations_target'] ?? 0) ?></span>
      </div>
      <div style="height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;margin-bottom:4px">
        <div style="height:100%;width:<?= min(100, $quotesPct) ?>%;background:#8B5CF6"></div>
      </div>
      <div class="small muted"><?= $quotesPct ?>% commercial proposals</div>
    </div>
  </div>

  <div class="card">
    <div class="hd"><h3>Collections Realized</h3></div>
    <div class="bd">
      <div style="font-size:24px;font-weight:800;color:var(--th-dark);margin-bottom:4px">
        <?= crm_money0($t['actual_collections'] ?? 0) ?>
      </div>
      <div style="height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;margin-bottom:4px">
        <div style="height:100%;width:<?= min(100, $collPct) ?>%;background:#10B981"></div>
      </div>
      <div class="small muted"><?= $collPct ?>% of <?= crm_money0($t['collections_target'] ?? 0) ?></div>
    </div>
  </div>
</div>
