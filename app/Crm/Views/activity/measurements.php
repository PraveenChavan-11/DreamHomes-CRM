<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Site Measurements &amp; Openings</h2>
    <p class="muted" style="margin:0">Architectural dimensions, room locations, versioned revisions, and technical notes captured during on-site surveys.</p>
  </div>
  <a href="<?= site_url('crm/visits') ?>" class="btn btn-secondary">
    <?= crm_icon('M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z', 16) ?>
    <span>View Scheduled Site Visits</span>
  </a>
</div>

<!-- Search Filter -->
<div class="card" style="margin-top:16px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/measurements') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($q ?? '') ?>" placeholder="Search visit #, enquiry #, client, project, revision notes..." style="width:100%;margin:0">
    </div>
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if (! empty($q)): ?>
      <a href="<?= site_url('crm/measurements') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Visit #</th>
          <th>Version</th>
          <th>Client &amp; Project</th>
          <th>Recorded Measurements (Width &times; Height)</th>
          <th>Recorded By</th>
          <th>Revision Reason</th>
          <th>Date</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:32px" class="muted">No site measurement records found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $m): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/visits/{$m['visit_id']}") ?>" style="font-weight:700;color:var(--th-primary)"><?= esc($m['visit_no']) ?></a>
              </td>
              <td>
                <span class="badge <?= (int) $m['is_current'] === 1 ? 'badge-green' : 'badge-grey' ?>">
                  V<?= (int) $m['version_no'] ?> <?= (int) $m['is_current'] === 1 ? '(Current)' : '' ?>
                </span>
              </td>
              <td>
                <b><?= esc($m['customer_name']) ?></b><br>
                <span class="muted small"><?= esc($m['project_name'] ?: 'Site Opening') ?></span>
              </td>
              <td style="max-width:320px">
                <?php if (! empty($m['items'])): ?>
                  <ul style="margin:0;padding-left:16px;font-size:12px;line-height:1.4">
                    <?php foreach (array_slice($m['items'], 0, 3) as $label => $val): ?>
                      <li><b><?= esc($label) ?>:</b> <?= esc($val) ?></li>
                    <?php endforeach; ?>
                    <?php if (count($m['items']) > 3): ?>
                      <li class="muted"><em>+ <?= count($m['items']) - 3 ?> more openings...</em></li>
                    <?php endif; ?>
                  </ul>
                <?php else: ?>
                  <span class="muted small">Standard dimensions</span>
                <?php endif; ?>
              </td>
              <td><?= esc($m['recorded_by_name'] ?: 'Technical Surveyor') ?></td>
              <td class="muted small"><?= esc(!empty($m['revision_note']) ? $m['revision_note'] : 'Initial site survey measurements') ?></td>
              <td><?= crm_date($m['created_at']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/visits/{$m['visit_id']}") ?>" class="btn btn-xs btn-secondary">Open Visit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
