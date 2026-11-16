<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/masters') ?>" class="muted small" style="text-decoration:none">← All Masters</a>
      <span class="muted small"> / </span>
      <span class="small muted"><?= esc($meta['group_label']) ?></span>
    </div>
    <h2 style="margin:0 0 4px"><?= esc($meta['name']) ?></h2>
    <p class="muted" style="margin:0"><?= esc($meta['desc']) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="<?= site_url("crm/masters/{$meta['type_key']}/export") ?>" class="btn btn-secondary">
      <?= crm_icon('M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4', 16) ?>
      <span>Export CSV</span>
    </a>
    <?php if ($meta['mode'] === 'records' || $meta['mode'] === 'native'): ?>
      <button type="button" class="btn btn-primary" data-modal="m-add-master">
        <?= crm_icon('M12 4v16m8-8H4', 16) ?>
        <span>Add New <?= esc(rtrim($meta['name'], 's')) ?></span>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url("crm/masters/{$meta['type_key']}") ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search by name, code or description..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="all" <?= ($params['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
        <option value="active" <?= ($params['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active Only</option>
        <option value="inactive" <?= ($params['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ($params['status'] ?? '') !== 'all'): ?>
      <a href="<?= site_url("crm/masters/{$meta['type_key']}") ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th style="width:50px">#</th>
          <th>Name</th>
          <th>Code / Key</th>
          <th>Details / Description</th>
          <th style="text-align:center;width:100px">Status</th>
          <th style="text-align:right;width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:32px" class="muted">
              No master records found matching your filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $idx => $r): 
            $isActive = ! empty($r['is_active']);
            $id = $r['id'] ?? $idx + 1;
            $code = $r['code'] ?? $r['slug'] ?? $r['seq_key'] ?? ('#' . $id);
            $name = $r['name'] ?? $r['title'] ?? (isset($r['first_name']) ? trim($r['first_name'] . ' ' . ($r['last_name'] ?? '')) : $code);
          ?>
            <tr>
              <td><?= ($page - 1) * $limit + $idx + 1 ?></td>
              <td style="font-weight:600;color:var(--th-dark)"><?= esc($name) ?></td>
              <td><code><?= esc($code) ?></code></td>
              <td class="muted small"><?= esc($r['description'] ?? $r['value'] ?? '—') ?></td>
              <td style="text-align:center">
                <span class="badge badge-<?= $isActive ? 'green' : 'grey' ?>">
                  <?= $isActive ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td style="text-align:right">
                <?php if (isset($r['id'])): ?>
                  <form method="post" action="<?= site_url("crm/masters/{$meta['type_key']}/toggle/{$r['id']}") ?>" style="display:inline;margin:0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-xs <?= $isActive ? 'btn-ghost' : 'btn-secondary' ?>" title="<?= $isActive ? 'Deactivate record' : 'Activate record' ?>">
                      <?= $isActive ? 'Deactivate' : 'Activate' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="muted small">System Locked</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New Record -->
<?php if ($meta['mode'] === 'records' || $meta['mode'] === 'native'): ?>
<div class="modal" id="m-add-master" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Add New <?= esc(rtrim($meta['name'], 's')) ?></h3>
      <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url("crm/masters/{$meta['type_key']}/save") ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field">
          <label>Record Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" required placeholder="e.g. Aluminium Sliding Doors" autofocus>
        </div>
        <div class="field">
          <label>Code / Identifier (Optional, auto-generated if blank)</label>
          <input type="text" name="code" placeholder="e.g. ALUM_SLD">
        </div>
        <div class="field">
          <label>Description / Usage Guidelines</label>
          <textarea name="description" rows="3" placeholder="Brief explanation of this standard parameter"></textarea>
        </div>
        <div class="field">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="is_active" value="1" checked style="width:auto;margin:0">
            <span>Set record active immediately</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Save Master Record</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
