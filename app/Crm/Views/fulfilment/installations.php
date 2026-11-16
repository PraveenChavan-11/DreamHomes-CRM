<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Site Installations &amp; Handover</h2>
    <p class="muted" style="margin:0">Manage installation appointments, technician assignments, work in progress, and customer sign-off certificates.</p>
  </div>
  <button type="button" class="btn btn-primary" data-modal="m-add-installation">
    <?= crm_icon('M12 4v16m8-8H4', 16) ?>
    <span>Schedule Installation</span>
  </button>
</div>

<!-- Search Filter -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/installations') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search installation #, technician, customer, signoff..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="pending" <?= ($params['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Schedule</option>
        <option value="scheduled" <?= ($params['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
        <option value="in_progress" <?= ($params['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="completed" <?= ($params['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed &amp; Signed Off</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ! empty($params['status'])): ?>
      <a href="<?= site_url('crm/installations') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Installation #</th>
          <th>Sales Order</th>
          <th>Customer</th>
          <th>Technician</th>
          <th>Scheduled Date</th>
          <th>Sign-off Name</th>
          <th>Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:32px" class="muted">No installation jobs scheduled.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $ins): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/installations/{$ins['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($ins['installation_no']) ?></a>
              </td>
              <td>
                <a href="<?= site_url("crm/orders/{$ins['order_id']}") ?>" style="color:inherit;font-weight:600"><?= esc($ins['order_no']) ?></a>
              </td>
              <td>
                <a href="<?= site_url("crm/customers/{$ins['customer_id']}") ?>" style="color:inherit;font-weight:600"><?= esc($ins['customer_name']) ?></a><br>
                <span class="muted small"><?= esc($ins['customer_mobile']) ?></span>
              </td>
              <td>
                <b><?= esc($ins['technician_name'] ?: 'Unassigned') ?></b><br>
                <span class="muted small"><?= esc($ins['technician_phone'] ?: '') ?></span>
              </td>
              <td><?= crm_date($ins['scheduled_date']) ?></td>
              <td><?= esc($ins['customer_signoff_name'] ?: 'Pending Sign-off') ?></td>
              <td><?= crm_badge($ins['status']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/installations/{$ins['id']}") ?>" class="btn btn-xs btn-secondary">View Job</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Schedule Installation -->
<div class="modal" id="m-add-installation" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Schedule Site Installation</h3>
      <button type="button" class="modal-close" aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/installations/save') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field">
          <label>Sales Order <span style="color:var(--red)">*</span></label>
          <select name="order_id" required>
            <option value="">Select Confirmed Order...</option>
            <?php foreach ($orders as $o): ?>
              <option value="<?= (int) $o['id'] ?>"><?= esc($o['order_no']) ?> · <?= esc($o['customer_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Installation Scheduled Date</label>
            <input type="date" name="scheduled_date" value="<?= date('Y-m-d', strtotime('+5 days')) ?>">
          </div>
          <div class="field">
            <label>Initial Status</label>
            <select name="status">
              <option value="scheduled">Scheduled</option>
              <option value="assigned">Assigned to Technician</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Lead Technician Name</label>
            <input type="text" name="technician_name" placeholder="e.g. Ramesh Shinde">
          </div>
          <div class="field">
            <label>Technician Phone</label>
            <input type="tel" name="technician_phone" placeholder="e.g. 98220 12345">
          </div>
        </div>

        <div class="field">
          <label>Site Address &amp; Access Notes</label>
          <textarea name="site_address" rows="2" placeholder="Site address, floor level, lift availability..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary">Book Installation Appointment</button>
      </div>
    </form>
  </div>
</div>
