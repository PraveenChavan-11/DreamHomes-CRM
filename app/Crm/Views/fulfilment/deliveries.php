<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Deliveries &amp; Logistics</h2>
    <p class="muted" style="margin:0">Track order dispatches, delivery challans, vehicle assignments, and on-site material handovers.</p>
  </div>
  <button type="button" class="btn btn-primary" data-modal="m-add-delivery">
    <?= crm_icon('M12 4v16m8-8H4', 16) ?>
    <span>Schedule New Dispatch</span>
  </button>
</div>

<!-- Search Filter -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/deliveries') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search delivery #, challan #, client, vehicle, driver..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="pending" <?= ($params['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Preparation</option>
        <option value="scheduled" <?= ($params['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
        <option value="dispatched" <?= ($params['status'] ?? '') === 'dispatched' ? 'selected' : '' ?>>Dispatched / In Transit</option>
        <option value="delivered" <?= ($params['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ! empty($params['status'])): ?>
      <a href="<?= site_url('crm/deliveries') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Delivery #</th>
          <th>Challan #</th>
          <th>Sales Order</th>
          <th>Client</th>
          <th>Vehicle / Driver</th>
          <th>Scheduled Date</th>
          <th>Delivered Date</th>
          <th>Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:32px" class="muted">No delivery dispatches scheduled.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $d): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/deliveries/{$d['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($d['delivery_no']) ?></a>
              </td>
              <td><b><?= esc($d['challan_no'] ?: '—') ?></b></td>
              <td>
                <a href="<?= site_url("crm/orders/{$d['order_id']}") ?>" style="color:inherit;font-weight:600"><?= esc($d['order_no']) ?></a>
              </td>
              <td>
                <?php if (! empty($d['organisation_id'])): ?>
                  <a href="<?= site_url("crm/organisations/{$d['organisation_id']}") ?>" style="color:inherit;font-weight:700"><?= esc($d['organisation_name'] ?? $d['customer_name']) ?></a><br>
                <?php else: ?>
                  <strong style="color:inherit"><?= esc($d['organisation_name'] ?? $d['customer_name']) ?></strong><br>
                <?php endif; ?>
                <span class="muted small"><?= esc($d['contact_name'] ?? $d['customer_name']) ?><?= ! empty($d['customer_mobile']) ? ' · ' . esc($d['customer_mobile']) : '' ?></span>
              </td>
              <td>
                <?= esc($d['vehicle_details'] ?: 'Dedicated Transport') ?><br>
                <span class="muted small"><?= esc($d['driver_name'] ? $d['driver_name'] . ' (' . $d['driver_phone'] . ')' : '') ?></span>
              </td>
              <td><?= crm_date($d['scheduled_date']) ?></td>
              <td><?= $d['delivered_date'] ? crm_date($d['delivered_date']) : '—' ?></td>
              <td><?= crm_badge($d['status']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/deliveries/{$d['id']}") ?>" class="btn btn-xs btn-secondary">Challan Details</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Schedule Delivery -->
<div class="modal" id="m-add-delivery" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Schedule Material Dispatch &amp; Challan</h3>
      <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/deliveries/save') ?>">
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
            <label>Scheduled Delivery Date</label>
            <input type="date" name="scheduled_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
          </div>
          <div class="field">
            <label>Dispatch Status</label>
            <select name="status">
              <option value="pending">Pending Preparation</option>
              <option value="scheduled">Scheduled</option>
              <option value="ready">Ready for Dispatch</option>
              <option value="dispatched">Dispatched / In Transit</option>
              <option value="delivered">Delivered</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Vehicle Details (Plate / Model)</label>
            <input type="text" name="vehicle_details" placeholder="e.g. MH 12 AB 1234 (Tata Ace)">
          </div>
          <div class="field">
            <label>Tracking / LR Number</label>
            <input type="text" name="tracking_no" placeholder="e.g. LR-98745">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Driver Name</label>
            <input type="text" name="driver_name" placeholder="Driver Full Name">
          </div>
          <div class="field">
            <label>Driver Contact Number</label>
            <input type="tel" name="driver_phone" placeholder="Driver Phone">
          </div>
        </div>

        <div class="field">
          <label>Site Delivery Address</label>
          <textarea name="delivery_address" rows="2" placeholder="Full site address and unloading contact person..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Generate Delivery Challan</button>
      </div>
    </form>
  </div>
</div>
