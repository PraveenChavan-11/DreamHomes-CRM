<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Master Data Management</h2>
    <p class="muted" style="margin:0">Configure and govern standard parameters across Organisation, Customer, Product, Sales, and System masters.</p>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:24px;margin-top:20px">
  <?php foreach ($groups as $groupKey => $group): ?>
    <div class="card" style="padding:20px">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line)">
        <div style="width:36px;height:36px;border-radius:var(--r-sm);background:var(--th-light);color:var(--th-primary);display:flex;align-items:center;justify-content:center">
          <?= crm_icon($group['icon'], 22) ?>
        </div>
        <div>
          <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--th-dark)"><?= esc($group['label']) ?></h3>
          <span class="muted small"><?= count($group['items']) ?> master catalogs configured</span>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:16px">
        <?php foreach ($group['items'] as $itemKey => $item): ?>
          <a href="<?= site_url("crm/masters/{$itemKey}") ?>" class="card-item-link" style="display:block;padding:14px;border:1px solid var(--line);border-radius:var(--r);background:#FFFFFF;text-decoration:none;color:inherit;transition:all 0.15s ease">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
              <span style="font-weight:600;font-size:14px;color:var(--th-dark)"><?= esc($item['name']) ?></span>
              <span class="badge badge-grey" style="font-size:11px"><?= esc(ucfirst($item['mode'])) ?></span>
            </div>
            <p class="muted small" style="margin:0;line-height:1.4"><?= esc($item['desc']) ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
.card-item-link:hover {
  border-color: var(--th-primary) !important;
  box-shadow: var(--shadow) !important;
  transform: translateY(-2px);
}
</style>
