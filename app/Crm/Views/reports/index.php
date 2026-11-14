<?php /** @var array<string,array<string,string>> $groups */ ?>
<div class="pagehead">
  <div>
    <h1>Reports &amp; Business Intelligence</h1>
    <p class="muted small">Live analytics, sales conversion funnels, transaction history, and Auto QA Command Centre.</p>
  </div>
  <?php if (crm_can('settings.manage')): ?>
  <div class="gap">
    <a class="btn teal sm" href="<?= site_url('crm/qa') ?>">
      <?= crm_icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 16) ?>
      Auto QA Command Centre
    </a>
  </div>
  <?php endif; ?>
</div>

<?php if (crm_can('settings.manage')): ?>
<div class="card mb" style="background:var(--brand-primary);color:#FFFFFF;border-color:var(--brand-primary)">
  <div class="bd" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <h3 style="color:#FFFFFF;margin-bottom:4px">Intelligent Auto QA Engine</h3>
      <p style="color:var(--side-text);font-size:13px;margin:0">Run automated integrity tests across financial totals, sales workflow state machines, duplicate number prevention, and rate-limiting buckets.</p>
    </div>
    <a class="btn alt sm" href="<?= site_url('crm/qa') ?>" style="background:#FFFFFF;color:var(--brand-primary);font-weight:700">Open QA Command Centre &rarr;</a>
  </div>
</div>
<?php endif; ?>
<?php if (! $groups): ?><div class="card"><div class="empty"><strong>No reports available for your role</strong></div></div><?php endif; ?>
<?php foreach ($groups as $g => $reports): ?>
  <h2 class="mt mb"><?= esc($g) ?></h2>
  <div class="grid g3">
  <?php foreach ($reports as $k => $title): ?>
    <a class="card kpi" href="<?= site_url('crm/reports/' . $k) ?>"><div class="l">Report</div><div class="v" style="font-size:18px"><?= esc($title) ?></div><div class="s">Open, filter &amp; export &rarr;</div></a>
  <?php endforeach; ?>
  </div>
<?php endforeach; ?>
