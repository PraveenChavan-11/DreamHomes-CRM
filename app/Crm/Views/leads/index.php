<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Leads Pipeline Board</h2>
    <p class="muted" style="margin:0">Visual Kanban funnel tracking prospects from initial inquiry to qualified opportunities, quotations, and won orders.</p>
  </div>
  <div style="display:flex;gap:8px">
    <div class="btn-group" style="display:inline-flex;border:1px solid var(--line);border-radius:var(--r-sm);overflow:hidden">
      <a href="<?= site_url('crm/leads?view=kanban') ?>" class="btn btn-sm <?= ($viewMode ?? 'kanban') === 'kanban' ? 'btn-primary' : 'btn-ghost' ?>">
        <?= crm_icon('M4 5a1 1 0 011-1h4a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5z', 16) ?>
        <span>Kanban</span>
      </a>
      <a href="<?= site_url('crm/leads?view=list') ?>" class="btn btn-sm <?= ($viewMode ?? 'kanban') === 'list' ? 'btn-primary' : 'btn-ghost' ?>">
        <?= crm_icon('M4 6h16M4 12h16M4 18h16', 16) ?>
        <span>List</span>
      </a>
    </div>
    <button type="button" class="btn btn-primary" data-modal="m-quick-lead">
      <?= crm_icon('M12 4v16m8-8H4', 16) ?>
      <span>Capture New Lead</span>
    </button>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/leads') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <input type="hidden" name="view" value="<?= esc($viewMode ?? 'kanban') ?>">
    <div style="flex:1;min-width:220px">
      <input type="search" name="q" value="<?= esc($q ?? '') ?>" placeholder="Search lead number, client, project, location..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="priority" style="margin:0" onchange="this.form.submit()">
        <option value="">All Priorities</option>
        <option value="urgent" <?= ($priority ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
        <option value="high" <?= ($priority ?? '') === 'high' ? 'selected' : '' ?>>High</option>
        <option value="medium" <?= ($priority ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
        <option value="low" <?= ($priority ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
      </select>
    </div>
    <div>
      <select name="source_id" style="margin:0" onchange="this.form.submit()">
        <option value="">All Sources</option>
        <?php foreach ($sources as $s): ?>
          <option value="<?= (int) $s['id'] ?>" <?= ((int) ($sourceId ?? 0) === (int) $s['id']) ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <?php if (! empty($q) || ! empty($priority) || ! empty($sourceId)): ?>
      <a href="<?= site_url('crm/leads?view=' . esc($viewMode ?? 'kanban')) ?>" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
  </form>
</div>

<?php if (($viewMode ?? 'kanban') === 'kanban'): ?>
  <!-- Kanban Board -->
  <div class="kanban-wrapper" style="display:flex;gap:16px;overflow-x:auto;padding-bottom:16px;margin-top:20px;min-height:560px">
    <?php foreach ($columns as $stageKey => $col): ?>
      <div class="kanban-col" data-stage="<?= esc($stageKey) ?>" style="flex:0 0 300px;background:#F4F7F5;border-radius:var(--r);padding:12px;display:flex;flex-direction:column;border:1px solid #E1EBE4;transition:all 0.2s ease">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #D2E3D8">
          <div>
            <span style="font-weight:700;font-size:13px;color:var(--th-dark)"><?= esc($col['label']) ?></span>
            <span class="badge badge-grey" style="margin-left:6px;font-size:11px"><?= count($col['leads']) ?></span>
          </div>
          <?php if ($col['total_value'] > 0): ?>
            <span class="muted small bold">₹<?= number_format($col['total_value'] / 100000, 1) ?>L</span>
          <?php endif; ?>
        </div>

        <div class="kanban-cards" data-stage="<?= esc($stageKey) ?>" style="display:flex;flex-direction:column;gap:10px;flex:1;overflow-y:auto;min-height:100px">
          <?php if (empty($col['leads'])): ?>
            <div class="empty-placeholder" style="text-align:center;padding:24px 8px;border:1px dashed #D2E3D8;border-radius:var(--r-sm)" class="muted small">
              No leads in this stage
            </div>
          <?php else: ?>
            <?php foreach ($col['leads'] as $l): ?>
              <div class="card lead-card" draggable="true" data-id="<?= (int) $l['id'] ?>" data-stage="<?= esc($stageKey) ?>" style="padding:12px;background:#FFFFFF;border:1px solid var(--line);border-radius:var(--r-sm);box-shadow:var(--shadow-sm);cursor:grab;user-select:none" onclick="if(!this.dataset.dragging){location.href='<?= site_url("crm/enquiries/{$l['id']}") ?>'}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                  <span style="font-weight:700;font-size:13px;color:var(--th-primary)"><?= esc($l['enquiry_no']) ?></span>
                  <?= crm_badge($l['priority']) ?>
                </div>

                <div style="font-weight:600;font-size:13px;color:var(--th-dark);margin-bottom:2px">
                  <?= esc($l['customer_name'] ?: 'Prospect') ?>
                </div>
                <?php if (! empty($l['customer_mobile'])): ?>
                  <div class="muted small" style="margin-bottom:6px">📞 <?= esc($l['customer_mobile']) ?></div>
                <?php endif; ?>

                <?php if (! empty($l['project_name']) || ! empty($l['site_location'])): ?>
                  <div class="small" style="color:var(--th-dark-alt);background:#F3F8F5;padding:4px 6px;border-radius:4px;margin-bottom:6px">
                    📍 <?= esc($l['project_name'] ?: $l['site_location']) ?>
                  </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;margin-top:6px;padding-top:6px;border-top:1px solid var(--line-2)">
                  <span class="muted"><?= esc($l['owner_name'] ?: 'Unassigned') ?></span>
                  <?php if ((float) $l['est_budget'] > 0): ?>
                    <span style="font-weight:700;color:var(--green)">₹<?= number_format((float) $l['est_budget']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <!-- List View -->
  <div class="card" style="margin-top:20px;overflow:hidden">
    <div style="overflow-x:auto">
      <table class="table">
        <thead>
          <tr>
            <th>Lead #</th>
            <th>Customer</th>
            <th>Project / Location</th>
            <th>Category</th>
            <th>Source</th>
            <th>Priority</th>
            <th>Budget (₹)</th>
            <th>Assigned To</th>
            <th>Stage</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($allLeads)): ?>
            <tr>
              <td colspan="10" style="text-align:center;padding:32px" class="muted">No leads found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($allLeads as $l): ?>
              <tr>
                <td><a href="<?= site_url("crm/enquiries/{$l['id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($l['enquiry_no']) ?></a></td>
                <td>
                  <b><?= esc($l['customer_name']) ?></b><br>
                  <span class="muted small"><?= esc($l['customer_mobile']) ?></span>
                </td>
                <td><?= esc($l['project_name'] ?: $l['site_location'] ?: 'Pune') ?></td>
                <td><?= esc($l['category_name'] ?: 'Interior') ?></td>
                <td><span class="badge badge-grey"><?= esc($l['source_name'] ?: 'Direct') ?></span></td>
                <td><?= crm_badge($l['priority']) ?></td>
                <td><b><?= (float) $l['est_budget'] > 0 ? crm_money($l['est_budget']) : '—' ?></b></td>
                <td><?= esc($l['owner_name'] ?: 'Unassigned') ?></td>
                <td><?= crm_badge($l['status']) ?></td>
                <td style="text-align:right">
                  <a href="<?= site_url("crm/enquiries/{$l['id']}") ?>" class="btn btn-xs btn-secondary">Open</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Quick Lead Modal -->
<div class="modal" id="m-quick-lead" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Capture Inbound Lead</h3>
      <button type="button" class="modal-close" aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/leads/create') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Customer Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="customer_name" required placeholder="Full Name or Firm Name">
          </div>
          <div class="field">
            <label>Mobile Number <span style="color:var(--red)">*</span></label>
            <input type="tel" name="customer_mobile" required placeholder="10-digit Mobile">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Product Category</label>
            <select name="product_category_id">
              <option value="">Select Category...</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"><?= esc($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Lead Source</label>
            <select name="source_id">
              <?php foreach ($sources as $src): ?>
                <option value="<?= (int) $src['id'] ?>"><?= esc($src['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Project / Site Name</label>
            <input type="text" name="project_name" placeholder="e.g. Baner Villa, Wakad Flat">
          </div>
          <div class="field">
            <label>Site Location (City/Area)</label>
            <input type="text" name="site_location" placeholder="e.g. Kothrud, Pune">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Estimated Budget (₹)</label>
            <input type="number" name="est_budget" step="1000" placeholder="e.g. 500000">
          </div>
          <div class="field">
            <label>Priority</label>
            <select name="priority">
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
              <option value="low">Low</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label>Requirement Notes &amp; Scope</label>
          <textarea name="remarks" rows="3" placeholder="Enter customer requirements, approximate openings, finishes, or brand preference..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Lead Record</button>
      </div>
    </form>
  </div>
</div>

<style>
.lead-card {
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
}
.lead-card:hover {
  border-color: var(--th-primary) !important;
  transform: translateY(-2px);
  box-shadow: var(--shadow) !important;
}
.lead-card.is-dragging {
  opacity: 0.45 !important;
  transform: scale(0.97) !important;
  border-color: var(--th-primary) !important;
  box-shadow: var(--shadow-md) !important;
}
.kanban-col.drag-over {
  background: #E8F4EC !important;
  border-color: var(--th-primary) !important;
  outline: 2px dashed var(--th-primary);
  outline-offset: -2px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var draggedCard = null;
  var sourceCol = null;
  var isDraggingFlag = false;

  var cards = document.querySelectorAll('.lead-card[draggable="true"]');
  var cols = document.querySelectorAll('.kanban-col');

  cards.forEach(function (card) {
    card.addEventListener('dragstart', function (e) {
      isDraggingFlag = true;
      card.dataset.dragging = '1';
      draggedCard = card;
      sourceCol = card.closest('.kanban-col');
      card.classList.add('is-dragging');
      e.dataTransfer.setData('text/plain', card.dataset.id || '');
      e.dataTransfer.effectAllowed = 'move';
    });

    card.addEventListener('dragend', function () {
      card.classList.remove('is-dragging');
      setTimeout(function () {
        delete card.dataset.dragging;
        isDraggingFlag = false;
      }, 100);
      cols.forEach(function (c) { c.classList.remove('drag-over'); });
    });
  });

  cols.forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      if (!col.classList.contains('drag-over')) {
        col.classList.add('drag-over');
      }
    });

    col.addEventListener('dragleave', function (e) {
      if (!col.contains(e.relatedTarget)) {
        col.classList.remove('drag-over');
      }
    });

    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('drag-over');

      if (!draggedCard || !sourceCol) return;

      var targetStage = col.dataset.stage;
      var currentStage = draggedCard.dataset.stage;
      var leadId = draggedCard.dataset.id;

      if (!targetStage || targetStage === currentStage) return;

      var targetCardsContainer = col.querySelector('.kanban-cards');
      var sourceCardsContainer = sourceCol.querySelector('.kanban-cards');
      if (!targetCardsContainer || !sourceCardsContainer) return;

      // Check target empty placeholder
      var targetPlaceholder = targetCardsContainer.querySelector('.empty-placeholder');
      if (targetPlaceholder) targetPlaceholder.style.display = 'none';

      // Move card element optimistically
      targetCardsContainer.appendChild(draggedCard);

      // Update counters
      var sourceBadge = sourceCol.querySelector('.badge');
      var targetBadge = col.querySelector('.badge');
      var sourceCount = Math.max(0, parseInt(sourceBadge ? sourceBadge.textContent : '0', 10) - 1);
      var targetCount = parseInt(targetBadge ? targetBadge.textContent : '0', 10) + 1;
      if (sourceBadge) sourceBadge.textContent = sourceCount;
      if (targetBadge) targetBadge.textContent = targetCount;

      // If source column is now empty, ensure empty placeholder is visible
      var remainingCards = sourceCardsContainer.querySelectorAll('.lead-card');
      if (remainingCards.length === 0) {
        var sourcePlaceholder = sourceCardsContainer.querySelector('.empty-placeholder');
        if (sourcePlaceholder) {
          sourcePlaceholder.style.display = 'block';
        } else {
          var p = document.createElement('div');
          p.className = 'empty-placeholder muted small';
          p.style.cssText = 'text-align:center;padding:24px 8px;border:1px dashed #D2E3D8;border-radius:var(--r-sm)';
          p.textContent = 'No leads in this stage';
          sourceCardsContainer.appendChild(p);
        }
      }

      // CSRF token retrieval
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      var csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
      var csrfToken = csrfMeta ? csrfMeta.content : '';
      var csrfName = csrfNameMeta ? csrfNameMeta.content : 'csrf_test_name';

      var formData = new FormData();
      formData.append('stage', targetStage);
      formData.append('remarks', 'Stage updated via Leads Pipeline Board');
      formData.append('ajax', '1');
      formData.append(csrfName, csrfToken);

      fetch('<?= site_url('crm/leads') ?>/' + encodeURIComponent(leadId) + '/stage', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok) {
          throw new Error(result.data && result.data.message ? result.data.message : 'Stage update failed');
        }
        draggedCard.dataset.stage = targetStage;
        if (window.crmToast) {
          window.crmToast(result.data.message || 'Lead moved to ' + targetStage);
        }
      })
      .catch(function (err) {
        // Rollback on server error
        sourceCardsContainer.appendChild(draggedCard);
        if (sourceBadge) sourceBadge.textContent = sourceCount + 1;
        if (targetBadge) targetBadge.textContent = Math.max(0, targetCount - 1);
        if (targetPlaceholder && targetCardsContainer.querySelectorAll('.lead-card').length === 0) {
          targetPlaceholder.style.display = 'block';
        }
        var msg = err.message || 'Could not move lead stage.';
        if (window.crmToast) {
          window.crmToast(msg);
        } else {
          alert(msg);
        }
      });
    });
  });
});
</script>
