<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">After-Sales Service &amp; Support</h2>
    <p class="muted" style="margin:0">Manage client service requests, warranty claims, installation complaints, and technician maintenance visits.</p>
  </div>
  <button type="button" class="btn btn-primary" data-modal="m-add-ticket">
    <?= crm_icon('M12 4v16m8-8H4', 16) ?>
    <span>Log Service Ticket</span>
  </button>
</div>

<!-- Search Filter -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/service') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search ticket #, customer, issue description..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="open" <?= ($params['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="assigned" <?= ($params['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned</option>
        <option value="in_progress" <?= ($params['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="resolved" <?= ($params['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
        <option value="closed" <?= ($params['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
      </select>
    </div>
    <div>
      <select name="request_type" style="margin:0" onchange="this.form.submit()">
        <option value="">All Request Types</option>
        <option value="complaint" <?= ($params['request_type'] ?? '') === 'complaint' ? 'selected' : '' ?>>Complaint</option>
        <option value="service_request" <?= ($params['request_type'] ?? '') === 'service_request' ? 'selected' : '' ?>>Service Request</option>
        <option value="warranty_claim" <?= ($params['request_type'] ?? '') === 'warranty_claim' ? 'selected' : '' ?>>Warranty Claim</option>
        <option value="repair_request" <?= ($params['request_type'] ?? '') === 'repair_request' ? 'selected' : '' ?>>Repair Request</option>
        <option value="maintenance_visit" <?= ($params['request_type'] ?? '') === 'maintenance_visit' ? 'selected' : '' ?>>Maintenance Visit</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ! empty($params['status']) || ! empty($params['request_type'])): ?>
      <a href="<?= site_url('crm/service') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Ticket #</th>
          <th>Type</th>
          <th>Client</th>
          <th>Order Ref</th>
          <th>Issue Description</th>
          <th>Priority</th>
          <th>Assigned Representative</th>
          <th>Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:32px" class="muted">No after-sales service tickets recorded.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $t): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/service/{$t['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($t['ticket_no']) ?></a>
              </td>
              <td><span class="badge badge-grey"><?= esc(ucwords(str_replace('_', ' ', $t['request_type']))) ?></span></td>
              <td>
                <a href="<?= site_url("crm/customers/{$t['customer_id']}") ?>" style="color:inherit;font-weight:600"><?= esc($t['customer_name']) ?></a><br>
                <span class="muted small"><?= esc($t['customer_mobile']) ?></span>
              </td>
              <td><?= esc($t['order_no'] ?: '—') ?></td>
              <td style="max-width:280px" class="small">
                <?= esc(mb_strimwidth($t['issue_description'], 0, 80, '...')) ?>
              </td>
              <td><?= crm_badge($t['priority']) ?></td>
              <td><?= esc($t['assigned_name'] ?: 'Unassigned') ?></td>
              <td><?= crm_badge($t['status']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/service/{$t['id']}") ?>" class="btn btn-xs btn-secondary">View Ticket</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Log Service Ticket -->
<div class="modal" id="m-add-ticket" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Log After-Sales Ticket</h3>
      <button type="button" class="modal-close" aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/service/save') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field">
          <label>Customer <span style="color:var(--red)">*</span></label>
          <select name="customer_id" required>
            <option value="">Select Customer...</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?> (<?= esc($c['mobile']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Related Sales Order</label>
            <select name="order_id">
              <option value="">Select Order (Optional)...</option>
              <?php foreach ($orders as $o): ?>
                <option value="<?= (int) $o['id'] ?>"><?= esc($o['order_no']) ?> · <?= esc($o['customer_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Request Type <span style="color:var(--red)">*</span></label>
            <select name="request_type" required>
              <option value="service_request">Service Request</option>
              <option value="complaint">Complaint</option>
              <option value="warranty_claim">Warranty Claim</option>
              <option value="repair_request">Repair Request</option>
              <option value="installation_complaint">Installation Complaint</option>
              <option value="maintenance_visit">Maintenance Visit</option>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Priority</label>
            <select name="priority">
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
              <option value="low">Low</option>
            </select>
          </div>
          <div class="field">
            <label>Assign Technician / Rep</label>
            <select name="assigned_to">
              <option value="">Select Representative...</option>
              <?php foreach ($technicians as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= esc($t['first_name'] . ' ' . $t['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Issue Description &amp; Customer Symptoms <span style="color:var(--red)">*</span></label>
          <textarea name="issue_description" rows="3" required placeholder="Describe the defect, alignment issue, glass crack, water ingress, or hardware complaint..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Service Ticket</button>
      </div>
    </form>
  </div>
</div>
