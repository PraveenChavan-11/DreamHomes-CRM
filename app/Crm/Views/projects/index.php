<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Customer Projects</h2>
    <p class="muted" style="margin:0">Manage residential villas, apartments, commercial sites, budgets and ongoing project status.</p>
  </div>
  <button type="button" class="btn btn-primary" data-modal="m-add-project">
    <?= crm_icon('M12 4v16m8-8H4', 16) ?>
    <span>Create New Project</span>
  </button>
</div>

<!-- Search Filter -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/projects') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search project name, number, client, site location..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="planning" <?= ($params['status'] ?? '') === 'planning' ? 'selected' : '' ?>>Planning</option>
        <option value="in_progress" <?= ($params['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="on_hold" <?= ($params['status'] ?? '') === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
        <option value="completed" <?= ($params['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ! empty($params['status'])): ?>
      <a href="<?= site_url('crm/projects') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Project #</th>
          <th>Project Name</th>
          <th>Client</th>
          <th>Site Location</th>
          <th>Budget (₹)</th>
          <th>Assigned Manager</th>
          <th>Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:32px" class="muted">No projects found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $p): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/projects/{$p['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($p['project_no']) ?></a>
              </td>
              <td><b><?= esc($p['name']) ?></b></td>
              <td>
                <a href="<?= site_url("crm/customers/{$p['customer_id']}") ?>" style="color:inherit;font-weight:600"><?= esc($p['customer_name']) ?></a><br>
                <span class="muted small"><?= esc($p['customer_mobile']) ?></span>
              </td>
              <td><?= esc($p['site_location'] ?: $p['city'] ?: 'Pune') ?></td>
              <td><b><?= $p['budget'] ? crm_money($p['budget']) : '—' ?></b></td>
              <td><?= esc($p['assigned_name'] ?: 'Unassigned') ?></td>
              <td><?= crm_badge($p['status']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/projects/{$p['id']}") ?>" class="btn btn-xs btn-secondary">View Project</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Create Project -->
<div class="modal" id="m-add-project" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Create Customer Project</h3>
      <button type="button" class="modal-close" aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/projects/save') ?>">
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

        <div class="field">
          <label>Project Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" required placeholder="e.g. 4BHK Luxury Villa Interiors - Baner">
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px">
          <div class="field">
            <label>Site Location / Landmark</label>
            <input type="text" name="site_location" placeholder="e.g. Plot 42, Supreme Amadore, Baner">
          </div>
          <div class="field">
            <label>City</label>
            <input type="text" name="city" value="Pune">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Estimated Project Budget (₹)</label>
            <input type="number" name="budget" step="1000" placeholder="e.g. 1500000">
          </div>
          <div class="field">
            <label>Assigned Project Lead</label>
            <select name="assigned_to">
              <option value="">Select Employee...</option>
              <?php foreach ($salesUsers as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= esc($u['first_name'] . ' ' . $u['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Project Scope &amp; Specifications</label>
          <textarea name="remarks" rows="3" placeholder="Enter key architectural scope: e.g. Aluminium sliding windows, designer vanities, Häfele kitchen..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Project</button>
      </div>
    </form>
  </div>
</div>
