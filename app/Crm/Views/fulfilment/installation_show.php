<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/installations') ?>" class="muted small" style="text-decoration:none">← All Installations</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <h2 style="margin:0">Installation Job <?= esc($ins['installation_no']) ?></h2>
      <?= crm_badge($ins['status']) ?>
    </div>
    <p class="muted" style="margin:4px 0 0">
      Order: <b><a href="<?= site_url("crm/orders/{$ins['order_id']}") ?>" style="color:inherit"><?= esc($ins['order_no']) ?></a></b> · Client: <b><a href="<?= site_url("crm/customers/{$ins['customer_id']}") ?>" style="color:inherit"><?= esc($ins['customer_name']) ?></a></b>
    </p>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div>
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 16px;font-size:15px;color:var(--th-dark);border-bottom:1px solid var(--line);padding-bottom:10px">Installation &amp; Handover Protocol</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px">
        <div>
          <span class="muted small">Lead Technician</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($ins['technician_name'] ?: 'Unassigned Technician') ?></div>
          <?php if (! empty($ins['technician_phone'])): ?><div class="muted small">📞 <?= esc($ins['technician_phone']) ?></div><?php endif; ?>
        </div>
        <div>
          <span class="muted small">Scheduled Date</span>
          <div style="margin-top:2px"><?= crm_date($ins['scheduled_date']) ?></div>
        </div>
        <div>
          <span class="muted small">Customer Sign-off</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($ins['customer_signoff_name'] ?: 'Pending client sign-off') ?></div>
          <?php if (! empty($ins['signoff_date'])): ?><div class="muted small">Signed on <?= crm_date($ins['signoff_date']) ?></div><?php endif; ?>
        </div>
        <div>
          <span class="muted small">Work Progress Timestamps</span>
          <div style="margin-top:2px">Completed: <?= $ins['completed_at'] ? crm_dt($ins['completed_at']) : 'In progress' ?></div>
        </div>
      </div>

      <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
        <span class="muted small">Site Installation Address</span>
        <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($ins['site_address'] ?: 'Pune Region Site')) ?></p>
      </div>

      <?php if (! empty($ins['remarks'])): ?>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
          <span class="muted small">Technician Observations &amp; Checklist</span>
          <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($ins['remarks'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Quick Status Update Card -->
    <div class="card" style="padding:20px">
      <h3 style="margin:0 0 12px;font-size:14px;color:var(--th-dark)">Update Job &amp; Handover</h3>
      <form method="post" action="<?= site_url("crm/installations/{$ins['id']}/save") ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int) $ins['order_id'] ?>">
        <div class="field">
          <label>Installation Status</label>
          <select name="status">
            <option value="pending" <?= $ins['status'] === 'pending' ? 'selected' : '' ?>>Pending Schedule</option>
            <option value="scheduled" <?= $ins['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="assigned" <?= $ins['status'] === 'assigned' ? 'selected' : '' ?>>Assigned</option>
            <option value="in_progress" <?= $ins['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= $ins['status'] === 'completed' ? 'selected' : '' ?>>Completed &amp; Signed Off</option>
          </select>
        </div>
        <div class="field">
          <label>Client Sign-off Name</label>
          <input type="text" name="customer_signoff_name" value="<?= esc($ins['customer_signoff_name'] ?? '') ?>" placeholder="Customer or Site Incharge">
        </div>
        <div class="field">
          <label>Sign-off Date</label>
          <input type="date" name="signoff_date" value="<?= esc($ins['signoff_date'] ?? date('Y-m-d')) ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Save Handover Sign-off</button>
      </form>
    </div>
  </div>
</div>
