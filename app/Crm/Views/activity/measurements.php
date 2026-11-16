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
<div class="card" style="margin-top:20px;overflow:hidden">
  <div class="tablewrap">
    <table class="table t" style="min-width:1150px">
      <thead>
        <tr>
          <th style="width:10%;min-width:110px">Visit #</th>
          <th style="width:11%;min-width:120px">Version</th>
          <th style="width:18%;min-width:190px">Client &amp; Project</th>
          <th style="width:25%;min-width:250px">Recorded Measurements (Width &times; Height)</th>
          <th style="width:14%;min-width:140px">Recorded By</th>
          <th style="width:18%;min-width:200px">Revision Reason</th>
          <th style="width:12%;min-width:120px">Date</th>
          <th style="width:8%;min-width:100px;text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="8" style="text-align:center;padding:36px" class="muted">No site measurement records found.</td>
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
                <div style="font-weight:700;color:var(--text);margin-bottom:2px"><?= esc($m['customer_name']) ?></div>
                <div class="muted small"><?= esc($m['project_name'] ?: 'Site Opening') ?></div>
              </td>
              <td>
                <?php if (! empty($m['items'])): ?>
                  <ul style="margin:0;padding-left:18px;font-size:12.5px;line-height:1.6">
                    <?php foreach (array_slice($m['items'], 0, 3) as $label => $val): ?>
                      <li><b style="color:var(--text)"><?= esc($label) ?>:</b> <span style="font-variant-numeric:tabular-nums"><?= esc($val) ?></span></li>
                    <?php endforeach; ?>
                    <?php if (count($m['items']) > 3): ?>
                      <li class="muted"><em>+ <?= count($m['items']) - 3 ?> more openings...</em></li>
                    <?php endif; ?>
                  </ul>
                <?php else: ?>
                  <span class="muted small">Standard dimensions</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:500;color:var(--text)"><?= esc($m['recorded_by_name'] ?: 'Technical Surveyor') ?></div>
              </td>
              <td>
                <div class="muted small" style="max-width:260px;line-height:1.45;word-break:break-word">
                  <?= esc(!empty($m['revision_note']) ? $m['revision_note'] : 'Initial site survey measurements') ?>
                </div>
              </td>
              <td>
                <div style="white-space:nowrap;font-size:13px;font-variant-numeric:tabular-nums;color:var(--text)"><?= crm_date($m['created_at']) ?></div>
              </td>
              <td style="text-align:right;white-space:nowrap">
                <a href="<?= site_url("crm/visits/{$m['visit_id']}") ?>" class="btn btn-xs btn-secondary" style="margin:0">Open Visit</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
