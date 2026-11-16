<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/projects') ?>" class="muted small" style="text-decoration:none">← All Projects</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <h2 style="margin:0"><?= esc($project['name']) ?></h2>
      <?= crm_badge($project['status']) ?>
    </div>
    <p class="muted" style="margin:4px 0 0">
      Project Ref: <b><?= esc($project['project_no']) ?></b> · Client: <b><a href="<?= site_url("crm/customers/{$project['customer_id']}") ?>" style="color:inherit"><?= esc($project['customer_name']) ?></a></b>
    </p>
  </div>
  <div style="display:flex;gap:8px">
    <button type="button" class="btn btn-secondary" data-modal="m-edit-project">
      <?= crm_icon('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 16) ?>
      <span>Edit Project</span>
    </button>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div>
    <!-- Project Overview Card -->
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 16px;font-size:15px;color:var(--th-dark);border-bottom:1px solid var(--line);padding-bottom:10px">Site &amp; Execution Overview</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <span class="muted small">Site Location</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($project['site_location'] ?: 'Pune Region') ?></div>
          <div class="muted small"><?= esc($project['city']) ?>, <?= esc($project['state']) ?> <?= esc($project['pin_code']) ?></div>
        </div>
        <div>
          <span class="muted small">Budget Allocation</span>
          <div style="font-weight:700;font-size:16px;color:var(--green);margin-top:2px">
            <?= $project['budget'] ? crm_money($project['budget']) : 'Not specified' ?>
          </div>
        </div>
        <div>
          <span class="muted small">Assigned Project Lead</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($project['assigned_name'] ?: 'Unassigned') ?></div>
        </div>
        <div>
          <span class="muted small">Created Date</span>
          <div style="margin-top:2px"><?= crm_date($project['created_at']) ?></div>
        </div>
      </div>
      <?php if (! empty($project['remarks'])): ?>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--line-2)">
          <span class="muted small">Project Scope &amp; Remarks</span>
          <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($project['remarks'])) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Linked Enquiries & Opportunities -->
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 12px;font-size:15px;color:var(--th-dark)">Linked Enquiries &amp; Quotations</h3>
      <?php if (empty($project['enquiries'])): ?>
        <p class="muted small" style="margin:0">No enquiries explicitly linked to this project yet.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Enquiry #</th>
              <th>Status</th>
              <th>Estimated Value</th>
              <th>Created</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($project['enquiries'] as $e): ?>
              <tr>
                <td><a href="<?= site_url("crm/enquiries/{$e['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($e['enquiry_no']) ?></a></td>
                <td><?= crm_badge($e['status']) ?></td>
                <td><b><?= $e['final_value'] ? crm_money($e['final_value']) : ($e['est_budget'] ? crm_money($e['est_budget']) : '—') ?></b></td>
                <td><?= crm_date($e['created_at']) ?></td>
                <td style="text-align:right">
                  <a href="<?= site_url("crm/enquiries/{$e['id']}") ?>" class="btn btn-xs btn-secondary">Open</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Client Quick Contact Card -->
    <div class="card" style="padding:20px;margin-bottom:20px">
      <h3 style="margin:0 0 12px;font-size:14px;color:var(--th-dark)">Client Profile</h3>
      <div style="font-weight:700;font-size:15px;color:var(--th-dark)"><?= esc($project['customer_name']) ?></div>
      <?php if (! empty($project['company_name'])): ?>
        <div class="muted small"><?= esc($project['company_name']) ?></div>
      <?php endif; ?>
      <div style="margin-top:12px;font-size:13px">
        <div>📞 <?= esc($project['customer_mobile']) ?></div>
        <?php if (! empty($project['customer_email'])): ?>
          <div style="margin-top:4px">✉️ <?= esc($project['customer_email']) ?></div>
        <?php endif; ?>
      </div>
      <div style="margin-top:16px">
        <a href="<?= site_url("crm/customers/{$project['customer_id']}") ?>" class="btn btn-sm btn-secondary" style="width:100%;text-align:center">
          View Complete Customer 360
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Edit Project -->
<div class="modal" id="m-edit-project" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Edit Customer Project</h3>
      <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/projects/save/' . (int) $project['id']) ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field">
          <label>Project Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" required value="<?= esc($project['name']) ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Project Status</label>
            <select name="status">
              <?php foreach (['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $project['status'] === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>City</label>
            <input type="text" name="city" value="<?= esc($project['city'] ?? 'Pune') ?>">
          </div>
        </div>

        <div class="field">
          <label>Site Location / Landmark</label>
          <input type="text" name="site_location" value="<?= esc($project['site_location'] ?? '') ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Estimated Project Budget (₹)</label>
            <input type="number" name="budget" step="1000" value="<?= (float) ($project['budget'] ?? 0) ?>">
          </div>
          <div class="field">
            <label>Assigned Project Lead</label>
            <select name="assigned_to">
              <option value="">Select Employee...</option>
              <?php foreach ($salesUsers ?? [] as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= ((int) ($project['assigned_to'] ?? 0) === (int) $u['id']) ? 'selected' : '' ?>><?= esc($u['first_name'] . ' ' . $u['last_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Project Scope &amp; Specifications</label>
          <textarea name="remarks" rows="3"><?= esc($project['remarks'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Update Project</button>
      </div>
    </form>
  </div>
</div>
