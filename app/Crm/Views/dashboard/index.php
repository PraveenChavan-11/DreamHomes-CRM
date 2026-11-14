<?php
/** @var string $variant @var array $m @var list<array> $today @var list<array> $overdue @var list<array> $visits @var list<array> $approvals @var int $quotesPending @var array<string,int> $stages */
$kpi = static function (string $label, string $value, ?string $sub = null, ?string $url = null, string $tone = '') {
    $tag = $url ? 'a href="' . esc(site_url($url), 'attr') . '"' : 'div';
    $end = $url ? 'a' : 'div';

    return "<{$tag} class=\"card kpi {$tone}\"><div class=\"l\">" . esc($label) . '</div><div class="v">' . $value . '</div>' . ($sub ? '<div class="s">' . $sub . '</div>' : '') . "</{$end}>";
};
$n    = static fn ($v) => number_format((float) $v);
$conv = $m['conversion_pct'] === null ? '—' : $m['conversion_pct'] . '%';
$title = ['admin' => 'Business overview', 'manager' => 'Team pipeline', 'executive' => 'My workspace', 'management' => 'Management overview'][$variant];
$stageMax = max(1, max($stages));
$monthLabels = array_map(static fn ($r) => date('M y', strtotime($r['ym'] . '-01')), $m['monthly']);
?>
<div class="pagehead"><h1><?= esc($title) ?></h1>
  <?php if (crm_can('enquiries.create')): ?><a class="btn teal" href="<?= site_url('crm/enquiries/new') ?>">+ New enquiry</a><?php endif; ?>
  <?php if (crm_can('customers.create')): ?><a class="btn alt" href="<?= site_url('crm/customers/new') ?>">+ New customer</a><?php endif; ?>
</div>
<p class="muted small" style="margin-top:-8px">Live figures from your CRM records · refreshed <?= esc(crm_dt($m['generated_at'])) ?></p>

<div class="grid g4 mb">
<?php if ($variant === 'admin'): ?>
  <?= $kpi('Total enquiries', $n($m['enquiries_total']), $n($m['enquiries_active']) . ' active', 'crm/enquiries') ?>
  <?= $kpi('Customers', $n($m['customers_total']), null, 'crm/customers') ?>
  <?= $kpi('Quotations', $n($m['quotations_count']), crm_money0($m['quotations_value']) . ' total value', 'crm/quotations') ?>
  <?= $kpi('Negotiation pipeline', $n($m['negotiation_open_count']), crm_money0($m['negotiation_open_value']) . ' proposed', 'crm/negotiations') ?>
  <?= $kpi('Approvals pending', $n($m['approvals_pending']), 'discount requests awaiting a decision', 'crm/negotiations?status=pending', $m['approvals_pending'] ? 'warn' : '') ?>
  <?= $kpi('Follow-ups pending', $n($m['followups_pending']), $n($m['followups_today']) . ' due today', 'crm/followups') ?>
  <?= $kpi('Overdue follow-ups', $n($m['followups_overdue']), null, 'crm/followups?when=overdue', $m['followups_overdue'] ? 'warn' : 'good') ?>
  <?= $kpi('Site visits', $n($m['visits_upcoming']) . ' upcoming', $n($m['visits_completed']) . ' completed', 'crm/visits') ?>
  <?= $kpi('Won sales', $n($m['won_count']), crm_money0($m['won_value']), 'crm/enquiries?status=won', 'good') ?>
  <?= $kpi('Lost enquiries', $n($m['lost_count']), 'Conversion ' . $conv, 'crm/enquiries?status=lost') ?>
  <?= $kpi('Confirmed order value', crm_money0($m['orders_confirmed_value']), $n($m['orders_count']) . ' orders', 'crm/orders', 'good') ?>
  <?= $kpi('Unassigned enquiries', $n($m['unassigned']), null, 'crm/enquiries?status=new', $m['unassigned'] ? 'warn' : '') ?>
<?php elseif ($variant === 'manager'): ?>
  <?= $kpi('Team active enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' in total', 'crm/enquiries') ?>
  <?= $kpi('Team follow-ups', $n($m['followups_pending']), $n($m['followups_today']) . ' due today', 'crm/followups') ?>
  <?= $kpi('Overdue activities', $n($m['followups_overdue']), null, 'crm/followups?when=overdue', $m['followups_overdue'] ? 'warn' : 'good') ?>
  <?= $kpi('Quotations pending', $n($quotesPending), 'draft or awaiting customer', 'crm/quotations') ?>
  <?= $kpi('Negotiations pending', $n($m['negotiation_open_count']), crm_money0($m['negotiation_open_value']) . ' proposed', 'crm/negotiations') ?>
  <?= $kpi('Awaiting my approval', $n($m['approvals_pending']), null, 'crm/negotiations?status=pending', $m['approvals_pending'] ? 'warn' : '') ?>
  <?= $kpi('Team conversion', $conv, $n($m['won_count']) . ' won · ' . $n($m['lost_count']) . ' lost', 'crm/reports/conversion') ?>
  <?= $kpi('Team sales won', crm_money0($m['won_value']), $n($m['orders_count']) . ' orders', 'crm/orders', 'good') ?>
<?php elseif ($variant === 'management'): ?>
  <?= $kpi('Won revenue', crm_money0($m['won_value']), $n($m['won_count']) . ' enquiries won', 'crm/history?outcome=won', 'good') ?>
  <?= $kpi('Confirmed orders', crm_money0($m['orders_confirmed_value']), $n($m['orders_count']) . ' orders', 'crm/orders', 'good') ?>
  <?= $kpi('Quotation pipeline', crm_money0($m['quotations_value']), $n($m['quotations_count']) . ' quotations', 'crm/quotations') ?>
  <?= $kpi('Win rate', $conv, $n($m['won_count']) . ' won · ' . $n($m['lost_count']) . ' lost', 'crm/reports/conversion') ?>
  <?= $kpi('Active enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' total', 'crm/enquiries') ?>
  <?= $kpi('Customers', $n($m['customers_total']), null, 'crm/customers') ?>
  <?= $kpi('Negotiation pipeline', crm_money0($m['negotiation_open_value']), $n($m['negotiation_open_count']) . ' open', 'crm/negotiations') ?>
  <?= $kpi('Overdue follow-ups', $n($m['followups_overdue']), null, null, $m['followups_overdue'] ? 'warn' : 'good') ?>
<?php else: ?>
  <?= $kpi('My enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' in total', 'crm/enquiries') ?>
  <?= $kpi('My customers', $n($m['customers_total']), null, 'crm/customers') ?>
  <?= $kpi("Today's follow-ups", $n($m['followups_today']), $n($m['followups_upcoming']) . ' upcoming', 'crm/followups') ?>
  <?= $kpi('Overdue follow-ups', $n($m['followups_overdue']), null, 'crm/followups?when=overdue', $m['followups_overdue'] ? 'warn' : 'good') ?>
  <?= $kpi('Upcoming site visits', $n($m['visits_upcoming']), $n($m['visits_completed']) . ' completed', 'crm/visits') ?>
  <?= $kpi('My quotations', $n($m['quotations_count']), crm_money0($m['quotations_value']), 'crm/quotations') ?>
  <?= $kpi('Pending negotiations', $n($m['negotiation_open_count']), crm_money0($m['negotiation_open_value']), 'crm/negotiations') ?>
  <?= $kpi('Won / lost', $n($m['won_count']) . ' / ' . $n($m['lost_count']), 'Won value ' . crm_money0($m['won_value']), 'crm/enquiries?status=won', 'good') ?>
<?php endif; ?>
</div>

<div class="grid g-side mb">
  <div class="card"><div class="hd"><h2>Pipeline by stage</h2></div><div class="bd">
    <div class="bars">
    <?php foreach ($stages as $st => $cnt): ?>
      <a class="bar" href="<?= site_url('crm/enquiries?status=' . $st) ?>" style="color:inherit"><span class="nm"><?= esc(crm_label($st)) ?></span><span class="tr"><span class="fl" style="width:<?= round(100 * $cnt / $stageMax) ?>%"></span></span><span class="right"><?= $n($cnt) ?></span></a>
    <?php endforeach; ?>
    </div></div></div>
  <div class="card"><div class="hd"><h2>Lead sources</h2></div><div class="bd">
    <script type="application/json" data-chart="bar"><?= json_encode(['title' => 'Enquiries by lead source', 'labels' => array_column($m['lead_sources'], 'source'), 'values' => array_map('intval', array_column($m['lead_sources'], 'n'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <noscript><table class="t"><?php foreach ($m['lead_sources'] as $s): ?><tr><td><?= esc($s['source']) ?></td><td class="num"><?= (int) $s['n'] ?></td></tr><?php endforeach; ?></table></noscript>
  </div></div>
</div>

<div class="grid g2 mb">
  <div class="card"><div class="hd"><h2>Enquiries · last 6 months</h2></div><div class="bd">
    <script type="application/json" data-chart="line"><?= json_encode(['title' => 'Enquiries per month', 'labels' => $monthLabels, 'values' => array_map('intval', array_column($m['monthly'], 'enquiries'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script></div></div>
  <div class="card"><div class="hd"><h2>Won value · last 6 months</h2></div><div class="bd">
    <script type="application/json" data-chart="bar"><?= json_encode(['title' => 'Won value per month', 'fmt' => 'money', 'labels' => $monthLabels, 'values' => array_map('floatval', array_column($m['monthly'], 'won_value'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script></div></div>
</div>

<div class="grid g2 mb">
  <div class="card"><div class="hd"><h2>Today's follow-ups</h2><a class="btn alt sm" href="<?= site_url('crm/followups') ?>">All</a></div><div class="bd flush">
    <?php if (! $today): ?><div class="empty"><strong>Nothing due today</strong></div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
    <?php foreach ($today as $f): ?><tr><td class="nowrap"><?= esc(date('H:i', strtotime($f['due_at']))) ?></td><td><a href="<?= site_url('crm/customers/' . $f['customer_id']) ?>"><?= esc($f['customer_name']) ?></a><div class="muted small"><?= esc(crm_label($f['activity_type'])) ?><?= $f['enquiry_no'] ? ' · ' . esc($f['enquiry_no']) : '' ?></div></td><td class="muted small"><?= esc($f['assignee']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <div class="card"><div class="hd"><h2>Overdue follow-ups</h2><a class="btn alt sm" href="<?= site_url('crm/followups?when=overdue') ?>">All</a></div><div class="bd flush">
    <?php if (! $overdue): ?><div class="empty"><strong>No overdue follow-ups</strong></div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
    <?php foreach ($overdue as $f): ?><tr><td class="nowrap"><span class="badge badge-red"><?= esc(crm_date($f['due_at'])) ?></span></td><td><a href="<?= site_url('crm/customers/' . $f['customer_id']) ?>"><?= esc($f['customer_name']) ?></a><div class="muted small"><?= esc(crm_label($f['activity_type'])) ?><?= $f['enquiry_no'] ? ' · ' . esc($f['enquiry_no']) : '' ?></div></td><td class="muted small"><?= esc($f['assignee']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
</div>

<div class="grid g2 mb">
  <div class="card"><div class="hd"><h2>Upcoming site visits</h2><a class="btn alt sm" href="<?= site_url('crm/calendar') ?>">Calendar</a></div><div class="bd flush">
    <?php if (! $visits): ?><div class="empty"><strong>No visits scheduled</strong></div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
    <?php foreach ($visits as $v): ?><tr><td class="nowrap"><?= esc(crm_dt($v['scheduled_at'])) ?></td><td><a href="<?= site_url('crm/visits/' . $v['id']) ?>"><?= esc($v['visit_no']) ?></a> · <?= esc($v['customer_name']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <?php if (crm_can('quotations.view')): ?>
  <div class="card"><div class="hd"><h2>Pending discount approvals</h2><a class="btn alt sm" href="<?= site_url('crm/negotiations') ?>">All</a></div><div class="bd flush">
    <?php if (! $approvals): ?><div class="empty"><strong>No approvals pending</strong></div><?php else: ?><div class="tablewrap"><table class="t"><tbody>
    <?php foreach ($approvals as $a): ?><tr><td><a href="<?= site_url('crm/negotiations/' . $a['id']) ?>"><?= esc($a['negotiation_no']) ?></a><div class="muted small"><?= esc($a['quotation_no']) ?> · <?= esc($a['customer_name']) ?></div></td><td class="num"><?= esc(number_format((float) $a['requested_discount_pct'], 1)) ?>%<div class="muted small"><?= crm_money0($a['proposed_price']) ?></div></td><td><?= crm_badge($a['status']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
  <?php endif; ?>
</div>

<?php if (in_array($variant, ['admin', 'manager', 'management'], true)): ?>
<div class="card mb"><div class="hd"><h2><?= $variant === 'manager' ? 'Executive-wise performance' : 'Salesperson performance' ?></h2></div><div class="bd flush">
  <?php if (! $m['team_performance']): ?><div class="empty"><strong>No assigned enquiries yet</strong></div><?php else: ?>
  <div class="tablewrap"><table class="t"><thead><tr><th>Salesperson</th><th class="num">Enquiries</th><th class="num">Active</th><th class="num">Won</th><th class="num">Lost</th><th class="num">Won value</th></tr></thead><tbody>
  <?php foreach ($m['team_performance'] as $p): ?><tr><td><?= esc($p['name']) ?></td><td class="num"><?= (int) $p['enquiries'] ?></td><td class="num"><?= (int) $p['active'] ?></td><td class="num"><?= (int) $p['won'] ?></td><td class="num"><?= (int) $p['lost'] ?></td><td class="num"><?= crm_money0($p['won_value']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php endif; ?>

<?php if (in_array($variant, ['admin', 'management'], true)): ?>
<div class="card mb"><div class="hd"><h2>Product category performance (orders)</h2></div><div class="bd flush">
  <?php if (! $m['product_performance']): ?><div class="empty"><strong>No orders yet</strong></div><?php else: ?>
  <div class="tablewrap"><table class="t"><thead><tr><th>Category</th><th class="num">Orders</th><th class="num">Value</th></tr></thead><tbody>
  <?php foreach ($m['product_performance'] as $p): ?><tr><td><?= esc($p['category']) ?></td><td class="num"><?= (int) $p['orders'] ?></td><td class="num"><?= crm_money0($p['value']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
<?php endif; ?>
