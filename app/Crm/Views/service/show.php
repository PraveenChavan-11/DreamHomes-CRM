<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/service') ?>" class="muted small" style="text-decoration:none">← All Service Tickets</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <h2 style="margin:0">Ticket <?= esc($ticket['ticket_no']) ?></h2>
      <?= crm_badge($ticket['status']) ?>
      <?= crm_badge($ticket['priority']) ?>
    </div>
    <p class="muted" style="margin:4px 0 0">
      Type: <b><?= esc(ucwords(str_replace('_', ' ', $ticket['request_type']))) ?></b> · Client: <b><a href="<?= site_url("crm/customers/{$ticket['customer_id']}") ?>" style="color:inherit"><?= esc($ticket['customer_name']) ?></a></b>
    </p>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div>
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 16px;font-size:15px;color:var(--th-dark);border-bottom:1px solid var(--line);padding-bottom:10px">Ticket Details &amp; Diagnosis</h3>
      
      <div style="margin-bottom:16px">
        <span class="muted small">Reported Customer Issue</span>
        <div style="font-size:14px;line-height:1.5;margin-top:4px;background:#F9FBFA;border:1px solid var(--line);border-radius:4px;padding:12px">
          <?= nl2br(esc($ticket['issue_description'])) ?>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px">
        <div>
          <span class="muted small">Assigned Support Rep</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($ticket['assigned_name'] ?: 'Unassigned') ?></div>
        </div>
        <div>
          <span class="muted small">Sales Order Reference</span>
          <div style="font-weight:600;margin-top:2px">
            <?= $ticket['order_id'] ? '<a href="' . site_url("crm/orders/{$ticket['order_id']}") . '">' . esc($ticket['order_no']) . '</a>' : 'General Warranty Service' ?>
          </div>
        </div>
        <div>
          <span class="muted small">Date Logged</span>
          <div style="margin-top:2px"><?= crm_dt($ticket['created_at']) ?></div>
        </div>
        <div>
          <span class="muted small">Logged By</span>
          <div style="margin-top:2px"><?= esc($ticket['created_by_name'] ?: 'System') ?></div>
        </div>
      </div>

      <?php if (! empty($ticket['resolution_notes'])): ?>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-2)">
          <span class="muted small bold" style="color:var(--green)">Technician Resolution Notes</span>
          <div style="margin-top:4px;font-size:13px;line-height:1.4;background:var(--green-bg);border:1px solid var(--green-border);padding:10px;border-radius:4px">
            <?= nl2br(esc($ticket['resolution_notes'])) ?>
            <div class="muted small" style="margin-top:6px">Resolved at: <?= crm_dt($ticket['resolved_at']) ?></div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (! empty($ticket['customer_feedback'])): ?>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
          <span class="muted small bold">Customer Feedback &amp; Satisfaction</span>
          <p style="margin:4px 0 0;font-size:13px"><?= esc($ticket['customer_feedback']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Resolution & Status Update Card -->
    <div class="card" style="padding:20px">
      <h3 style="margin:0 0 12px;font-size:14px;color:var(--th-dark)">Update Ticket Status</h3>
      <form method="post" action="<?= site_url("crm/service/{$ticket['id']}/save") ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="customer_id" value="<?= (int) $ticket['customer_id'] ?>">
        <input type="hidden" name="issue_description" value="<?= esc($ticket['issue_description']) ?>">
        
        <div class="field">
          <label>Status</label>
          <select name="status">
            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="assigned" <?= $ticket['status'] === 'assigned' ? 'selected' : '' ?>>Assigned</option>
            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="waiting_for_parts" <?= $ticket['status'] === 'waiting_for_parts' ? 'selected' : '' ?>>Waiting for Parts / Glass</option>
            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
        </div>

        <div class="field">
          <label>Assign Technician</label>
          <select name="assigned_to">
            <option value="">Select Technician...</option>
            <?php foreach ($technicians as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= ((int) ($ticket['assigned_to'] ?? 0) === (int) $t['id']) ? 'selected' : '' ?>><?= esc($t['first_name'] . ' ' . $t['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>Resolution Notes</label>
          <textarea name="resolution_notes" rows="3" placeholder="Explain on-site repairs performed, glass replaced, alignment adjusted..."><?= esc($ticket['resolution_notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">Save Ticket Update</button>
      </form>
    </div>
  </div>
</div>
