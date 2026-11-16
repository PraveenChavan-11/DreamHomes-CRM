<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/deliveries') ?>" class="muted small" style="text-decoration:none">← All Deliveries</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <h2 style="margin:0">Delivery Challan <?= esc($d['challan_no'] ?: $d['delivery_no']) ?></h2>
      <?= crm_badge($d['status']) ?>
    </div>
    <p class="muted" style="margin:4px 0 0">
      Order: <b><a href="<?= site_url("crm/orders/{$d['order_id']}") ?>" style="color:inherit"><?= esc($d['order_no']) ?></a></b> · Client: <b><a href="<?= site_url("crm/customers/{$d['customer_id']}") ?>" style="color:inherit"><?= esc($d['customer_name']) ?></a></b>
    </p>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div>
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 16px;font-size:15px;color:var(--th-dark);border-bottom:1px solid var(--line);padding-bottom:10px">Logistics &amp; Transport Specifications</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px">
        <div>
          <span class="muted small">Vehicle Details</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($d['vehicle_details'] ?: 'Dedicated Company Van') ?></div>
        </div>
        <div>
          <span class="muted small">Driver / Carrier</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($d['driver_name'] ?: 'In-house logistics team') ?></div>
          <?php if (! empty($d['driver_phone'])): ?><div class="muted small">📞 <?= esc($d['driver_phone']) ?></div><?php endif; ?>
        </div>
        <div>
          <span class="muted small">Scheduled Dispatch</span>
          <div style="margin-top:2px"><?= crm_date($d['scheduled_date']) ?></div>
        </div>
        <div>
          <span class="muted small">Actual Delivery Date</span>
          <div style="font-weight:600;margin-top:2px"><?= $d['delivered_date'] ? crm_dt($d['delivered_date']) : 'Pending on-site confirmation' ?></div>
        </div>
      </div>

      <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
        <span class="muted small">Site Delivery Destination</span>
        <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($d['delivery_address'] ?: $d['billing_address'] ?: 'Pune Region Site')) ?></p>
      </div>

      <?php if (! empty($d['notes'])): ?>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
          <span class="muted small">Handling Instructions</span>
          <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($d['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Quick Status Update Card -->
    <div class="card" style="padding:20px">
      <h3 style="margin:0 0 12px;font-size:14px;color:var(--th-dark)">Update Challan Status</h3>
      <form method="post" action="<?= site_url("crm/deliveries/{$d['id']}/save") ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int) $d['order_id'] ?>">
        <div class="field">
          <label>Delivery Status</label>
          <select name="status">
            <option value="pending" <?= $d['status'] === 'pending' ? 'selected' : '' ?>>Pending Preparation</option>
            <option value="scheduled" <?= $d['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="ready" <?= $d['status'] === 'ready' ? 'selected' : '' ?>>Ready for Dispatch</option>
            <option value="dispatched" <?= $d['status'] === 'dispatched' ? 'selected' : '' ?>>Dispatched / In Transit</option>
            <option value="delivered" <?= $d['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
          </select>
        </div>
        <div class="field">
          <label>Recipient Receiver Name</label>
          <input type="text" name="recipient_name" value="<?= esc($d['recipient_name'] ?? '') ?>" placeholder="Person receiving material">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Update Delivery State</button>
      </form>
    </div>
  </div>
</div>
