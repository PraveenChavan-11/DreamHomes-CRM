<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $filters @var list<array> $users @var list<array> $teams */
$frequencies = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'];
?>
<div class="pagehead">
  <div>
    <h1>Sales Targets &amp; Performance Ledger <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">Operational quotas: Revenue targets, orders, client visits, quotations, and collection goals vs live actuals.</div>
  </div>
  <div class="gap">
    <?php if (crm_can('settings.manage', 'users.manage')): ?>
      <a class="btn teal" href="<?= site_url('crm/targets/new') ?>">+ Assign Target</a>
    <?php endif; ?>
  </div>
</div>

<form class="filters card" style="padding:14px;margin-bottom:18px" method="get" action="<?= site_url('crm/targets') ?>">
  <div class="f"><label for="user">Sales Executive</label><select id="user" name="user_id"><option value="">All Executives</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= crm_sel($filters['user_id'] ?? '', $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="team">Team</label><select id="team" name="team_id"><option value="">All Teams</option><?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"<?= crm_sel($filters['team_id'] ?? '', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="freq">Cadence</label><select id="freq" name="frequency"><option value="">All Cadences</option><?php foreach ($frequencies as $k => $v): ?><option value="<?= $k ?>"<?= crm_sel($filters['frequency'] ?? '', $k) ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/targets') ?>">Reset</a></div>
</form>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No sales targets match</strong>Try clearing filters or assign a new quota.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Target # / Period</th>
          <th>Sales Rep / Team</th>
          <th>Cadence</th>
          <th class="num">Revenue Target</th>
          <th class="num">Revenue Actual</th>
          <th>Revenue Progress</th>
          <th class="num">Orders Goal</th>
          <th class="num">Visits Goal</th>
          <th>Period Dates</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $revPct = (float) ($r['revenue_progress_pct'] ?? 0);
        $barColor = $revPct >= 100 ? '#10B981' : ($revPct >= 65 ? '#0284C7' : ($revPct >= 35 ? '#F59E0B' : '#EF4444'));
      ?>
        <tr>
          <td>
            <a href="<?= site_url('crm/targets/' . $r['id']) ?>"><strong><?= esc($r['target_no']) ?></strong></a>
            <div class="muted small"><?= esc($r['period_label']) ?></div>
          </td>
          <td>
            <strong><?= esc($r['salesperson_name'] ?: ($r['team_name'] ?: 'Company Fleet')) ?></strong>
            <div class="muted small"><?= $r['salesperson_name'] && $r['team_name'] ? esc($r['team_name']) : 'Sales Division' ?></div>
          </td>
          <td><span class="badge badge-grey"><?= esc(crm_label($r['frequency'])) ?></span></td>
          <td class="num"><strong><?= crm_money($r['revenue_target']) ?></strong></td>
          <td class="num"><strong style="color:var(--th-primary)"><?= crm_money($r['actual_revenue'] ?? ($r['achieved_revenue'] ?? 0)) ?></strong></td>
          <td style="min-width:140px">
            <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:3px">
              <span><?= number_format($revPct, 1) ?>%</span>
              <span class="muted small"><?= crm_money0($r['actual_revenue'] ?? ($r['achieved_revenue'] ?? 0)) ?> / <?= crm_money0($r['revenue_target']) ?></span>
            </div>
            <div style="height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden">
              <div style="height:100%;width:<?= min(100, $revPct) ?>%;background:<?= $barColor ?>"></div>
            </div>
          </td>
          <td class="num">
            <strong><?= (int) ($r['actual_orders'] ?? 0) ?></strong> / <?= (int) ($r['orders_target'] ?? 0) ?>
          </td>
          <td class="num">
            <strong><?= (int) ($r['actual_activities'] ?? 0) ?></strong> / <?= (int) ($r['activities_target'] ?? 0) ?>
          </td>
          <td class="nowrap muted small">
            <?= esc(crm_date($r['period_start'])) ?> – <?= esc(crm_date($r['period_end'])) ?>
          </td>
          <td class="nowrap">
            <a class="btn sm alt" href="<?= site_url('crm/targets/' . $r['id']) ?>">View</a>
            <?php if (crm_can('settings.manage', 'users.manage')): ?>
              <a class="btn sm alt" href="<?= site_url('crm/targets/' . $r['id'] . '/edit') ?>">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $perPage, 'page' => $page]) ?>
<?php endif; ?>
</div></div>
