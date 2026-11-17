<?php
/** @var string $mode @var array $target @var list<array> $users @var list<array> $teams */
$isEdit = ($mode === 'edit');
$frequencies = ['daily' => 'Daily Target', 'weekly' => 'Weekly Target', 'monthly' => 'Monthly Target', 'quarterly' => 'Quarterly Target', 'yearly' => 'Annual Quota'];
$action = $isEdit ? site_url('crm/targets/' . $target['id'] . '/update') : site_url('crm/targets/create');
?>
<div class="pagehead">
  <div>
    <h1><?= $isEdit ? 'Revise Target #' . esc($target['target_no']) : 'Assign Sales Target &amp; Quota' ?></h1>
    <div class="muted small"><?= $isEdit ? 'Update commercial expectations, visits and collection goals for this period.' : 'Set multi-dimensional sales targets across revenue, order counts, client visits, and collections.' ?></div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= site_url('crm/targets') ?>">Cancel</a>
  </div>
</div>

<form class="card" method="post" action="<?= $action ?>" style="max-width:860px">
  <?= csrf_field() ?>
  <div class="hd">
    <h2>1. Assignment Scope &amp; Period Cadence</h2>
  </div>
  <div class="bd grid g3">
    <div>
      <label for="user_id">Sales Executive</label>
      <select id="user_id" name="user_id">
        <option value="">— Entire Team / Company Pool —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"<?= crm_sel(crm_old('user_id', $target['user_id'] ?? ''), $u['id']) ?>><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="team_id">Sales Team</label>
      <select id="team_id" name="team_id">
        <option value="">— Select Team (Optional) —</option>
        <?php foreach ($teams as $t): ?>
          <option value="<?= (int) $t['id'] ?>"<?= crm_sel(crm_old('team_id', $target['team_id'] ?? ''), $t['id']) ?>><?= esc($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="frequency">Cadence / Frequency <span class="req">*</span></label>
      <select id="frequency" name="frequency" required>
        <?php foreach ($frequencies as $k => $lbl): ?>
          <option value="<?= $k ?>"<?= crm_sel(crm_old('frequency', $target['frequency'] ?? 'monthly'), $k) ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="period_label">Period Title / Label <span class="req">*</span></label>
      <input type="text" id="period_label" name="period_label" required value="<?= esc(crm_old('period_label', $target['period_label'] ?? date('F Y'))) ?>" placeholder="e.g. November 2026 / Q4 FY26">
      <?= crm_err('period_label') ?>
    </div>
    <div>
      <label for="period_start">Start Date <span class="req">*</span></label>
      <input type="date" id="period_start" name="period_start" required value="<?= esc(crm_old('period_start', $target['period_start'] ?? date('Y-m-01'))) ?>">
    </div>
    <div>
      <label for="period_end">End Date <span class="req">*</span></label>
      <input type="date" id="period_end" name="period_end" required value="<?= esc(crm_old('period_end', $target['period_end'] ?? date('Y-m-t'))) ?>">
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>2. Commercial &amp; Operational Milestones</h2>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="revenue_target">Net Confirmed Revenue Target (₹) <span class="req">*</span></label>
      <input type="number" step="1000" id="revenue_target" name="revenue_target" required value="<?= esc(crm_old('revenue_target', $target['revenue_target'] ?? 1000000)) ?>">
      <div class="muted small">Calculated from confirmed sales orders in this period.</div>
    </div>
    <div>
      <label for="orders_target">Sales Orders Count Goal</label>
      <input type="number" id="orders_target" name="orders_target" value="<?= esc(crm_old('orders_target', $target['orders_target'] ?? 5)) ?>">
    </div>
    <div>
      <label for="activities_target">Customer Visits &amp; Meetings Goal</label>
      <input type="number" id="activities_target" name="activities_target" value="<?= esc(crm_old('activities_target', $target['activities_target'] ?? 25)) ?>">
    </div>
    <div>
      <label for="quotations_target">Quotations Generated Goal</label>
      <input type="number" id="quotations_target" name="quotations_target" value="<?= esc(crm_old('quotations_target', $target['quotations_target'] ?? 12)) ?>">
    </div>
    <div>
      <label for="collections_target">Payment Collections Goal (₹)</label>
      <input type="number" step="1000" id="collections_target" name="collections_target" value="<?= esc(crm_old('collections_target', $target['collections_target'] ?? 750000)) ?>">
    </div>
  </div>

  <div class="ft" style="display:flex;justify-content:flex-end;gap:12px;padding:16px 22px">
    <a class="btn alt" href="<?= site_url('crm/targets') ?>">Cancel</a>
    <button class="btn teal" type="submit"><?= $isEdit ? 'Save Changes' : 'Assign Target' ?></button>
  </div>
</form>
