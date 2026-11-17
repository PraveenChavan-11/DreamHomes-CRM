<?php
/** @var list<array> $rules @var list<array> $categories @var list<array> $brands @var list<array> $users */
?>
<div class="pagehead">
  <div>
    <h1>Sales Governance &amp; Commercial Approval Matrix</h1>
    <div class="muted small">Configure multi-level approval triggers, discount ceilings, and SLA escalation policies.</div>
  </div>
  <div class="gap">
    <button class="btn teal" type="button" data-modal="modal-rule">+ Add Governance Rule</button>
    <a class="btn alt" href="<?= site_url('crm/settings') ?>">Back to Settings</a>
  </div>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="hd"><h2>Active Approval Rules Matrix</h2></div>
  <div class="bd flush">
    <div class="tablewrap"><table class="t">
      <thead>
        <tr>
          <th>Priority</th>
          <th>Rule Code &amp; Name</th>
          <th>Discount Threshold</th>
          <th>Margin Floor</th>
          <th>Quotation Value</th>
          <th>Level Required</th>
          <th>Approver Role</th>
          <th>SLA (Hours)</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rules as $r): ?>
        <tr>
          <td><strong>#<?= (int) $r['priority'] ?></strong></td>
          <td>
            <strong><?= esc($r['rule_name']) ?></strong>
            <div class="muted small"><code><?= esc($r['rule_code']) ?></code></div>
          </td>
          <td>
            <span style="color:#B91C1C;font-weight:700">&gt; <?= number_format((float) $r['min_discount_pct'], 1) ?>%</span>
            <?php if (! empty($r['max_discount_pct'])): ?> to <?= number_format((float) $r['max_discount_pct'], 1) ?>%<?php endif; ?>
          </td>
          <td>
            <?= ! empty($r['min_margin_pct']) ? '&lt; ' . number_format((float) $r['min_margin_pct'], 1) . '%' : '—' ?>
          </td>
          <td>
            <?= ! empty($r['min_order_value']) ? crm_money0($r['min_order_value']) : 'Any Value' ?>
          </td>
          <td>
            <span class="badge badge-blue">Level <?= (int) $r['approval_level'] ?></span>
          </td>
          <td><code><?= esc($r['approver_role'] ?? 'sales_manager') ?></code></td>
          <td><?= (int) ($r['sla_hours'] ?? 24) ?>h</td>
          <td>
            <?= ! empty($r['is_active']) ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-grey">Inactive</span>' ?>
          </td>
          <td>
            <form method="post" action="<?= site_url('crm/settings/governance/rule/' . $r['id'] . '/toggle') ?>" style="display:inline">
              <?= csrf_field() ?>
              <button class="btn sm alt" type="submit"><?= ! empty($r['is_active']) ? 'Deactivate' : 'Activate' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>

<!-- Modal: Add Rule -->
<div class="modal" id="modal-rule" role="dialog" aria-modal="true" aria-label="Add Governance Rule">
  <div class="box" style="max-width:640px">
    <div class="hd">
      <h3>Add Commercial Approval Rule</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/settings/governance/rule') ?>">
      <?= csrf_field() ?>
      <div class="bd grid g2">
        <div style="grid-column:1/-1">
          <label for="rule_name">Rule Name <span class="req">*</span></label>
          <input type="text" id="rule_name" name="rule_name" required placeholder="e.g. High Value Commercial Discount Policy">
        </div>
        <div>
          <label for="min_discount_pct">Discount Trigger Threshold (%) <span class="req">*</span></label>
          <input type="number" step="0.5" id="min_discount_pct" name="min_discount_pct" required placeholder="e.g. 5.0">
        </div>
        <div>
          <label for="max_discount_pct">Discount Ceiling (%)</label>
          <input type="number" step="0.5" id="max_discount_pct" name="max_discount_pct" placeholder="e.g. 15.0">
        </div>
        <div>
          <label for="approval_level">Approval Level <span class="req">*</span></label>
          <select id="approval_level" name="approval_level" required>
            <option value="1">Level 1 (Sales Manager)</option>
            <option value="2">Level 2 (Senior Commercial Manager)</option>
            <option value="3">Level 3 (Business Head / Director)</option>
          </select>
        </div>
        <div>
          <label for="approver_role">Approver Role <span class="req">*</span></label>
          <select id="approver_role" name="approver_role" required>
            <option value="sales_manager">Sales Manager</option>
            <option value="commercial_head">Commercial Head</option>
            <option value="admin">Administrator / Director</option>
          </select>
        </div>
        <div>
          <label for="sla_hours">Resolution SLA (Hours)</label>
          <input type="number" id="sla_hours" name="sla_hours" value="24">
        </div>
        <div>
          <label for="priority">Evaluation Priority (1 = Highest)</label>
          <input type="number" id="priority" name="priority" value="10">
        </div>
        <div style="grid-column:1/-1">
          <label for="rule_description">Description / Governance Intent</label>
          <textarea id="rule_description" name="rule_description" rows="2" placeholder="Explain the rationale for this commercial rule..."></textarea>
        </div>
      </div>
      <div class="ft" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn alt" data-close>Cancel</button>
        <button type="submit" class="btn teal">Save Governance Rule</button>
      </div>
    </form>
  </div>
</div>
