<?php
/** @var string $variant @var array $m @var list<array> $today @var list<array> $overdue @var list<array> $visits @var list<array> $approvals @var int $quotesPending @var array<string,int> $stages */
$kpi = static function (string $label, string $value, ?string $sub = null, ?string $url = null, string $tone = '') {
    $tag = $url ? 'a href="' . esc(site_url($url), 'attr') . '"' : 'div';
    $end = $url ? 'a' : 'div';

    return "<{$tag} class=\"card kpi {$tone}\"><div class=\"l\">" . esc($label) . '</div><div class="v">' . $value . '</div>' . ($sub ? '<div class="s">' . $sub . '</div>' : '') . "</{$end}>";
};
$n    = static fn ($v) => number_format((float) $v);
$conv = $m['conversion_pct'] === null ? '—' : $m['conversion_pct'] . '%';
$title = ['admin' => 'Sales Overview', 'manager' => 'Team Pipeline', 'executive' => 'My Sales Workspace', 'management' => 'Executive Performance'][$variant];
$stageMax = max(1, max($stages));
$monthLabels = array_map(static fn ($r) => date('M y', strtotime($r['ym'] . '-01')), $m['monthly']);

// Collect Next Actions list items for immediate salesperson productivity
$nextActions = [];
if (! empty($overdue)) {
    foreach (array_slice($overdue, 0, 3) as $f) {
        $nextActions[] = [
            'type' => 'overdue',
            'title' => 'Overdue Follow-up: ' . $f['customer_name'],
            'sub' => ($f['enquiry_no'] ? $f['enquiry_no'] . ' · ' : '') . ($f['next_action'] ?: 'Follow up with customer'),
            'badge' => 'Overdue ' . crm_date($f['due_at']),
            'badge_tone' => 'red',
            'url' => $f['enquiry_id'] ? 'crm/enquiries/' . (int) $f['enquiry_id'] : 'crm/followups?when=overdue',
            'action_label' => 'Open',
        ];
    }
}
if (! empty($today)) {
    foreach (array_slice($today, 0, 3) as $f) {
        $nextActions[] = [
            'type' => 'today',
            'title' => 'Follow-up Due Today: ' . $f['customer_name'],
            'sub' => ($f['enquiry_no'] ? $f['enquiry_no'] . ' · ' : '') . ($f['next_action'] ?: 'Call customer'),
            'badge' => date('H:i', strtotime($f['due_at'])),
            'badge_tone' => 'blue',
            'url' => $f['enquiry_id'] ? 'crm/enquiries/' . (int) $f['enquiry_id'] : 'crm/followups',
            'action_label' => 'Contact',
        ];
    }
}
if (! empty($approvals)) {
    foreach (array_slice($approvals, 0, 2) as $a) {
        $nextActions[] = [
            'type' => 'approval',
            'title' => 'Discount Approval Pending: ' . $a['negotiation_no'],
            'sub' => $a['customer_name'] . ' · ' . number_format((float) $a['requested_discount_pct'], 1) . '% requested',
            'badge' => 'Requires Approval',
            'badge_tone' => 'amber',
            'url' => 'crm/negotiations/' . (int) $a['id'],
            'action_label' => 'Review',
        ];
    }
}
if (! empty($visits)) {
    foreach (array_slice($visits, 0, 2) as $v) {
        $nextActions[] = [
            'type' => 'visit',
            'title' => 'Site Visit Scheduled: ' . $v['customer_name'],
            'sub' => ($v['visit_no'] ?? 'Site measurement') . ' · ' . crm_dt($v['scheduled_at']),
            'badge' => 'Upcoming Visit',
            'badge_tone' => 'green',
            'url' => 'crm/visits/' . (int) $v['id'],
            'action_label' => 'View Visit',
        ];
    }
}
?>
<div class="pagehead">
  <div>
    <h1><?= esc($title) ?></h1>
    <p class="muted small">Live figures from DHIS database · refreshed <?= esc(crm_dt($m['generated_at'])) ?></p>
  </div>
  <div class="gap">
    <?php if (crm_can('enquiries.create')): ?><a class="btn teal" href="<?= site_url('crm/enquiries/new') ?>">+ New enquiry</a><?php endif; ?>
    <?php if (crm_can('customers.create')): ?><a class="btn alt" href="<?= site_url('crm/customers/new') ?>">+ New customer</a><?php endif; ?>
  </div>
</div>

<!-- Primary KPI Cards: Exactly 4 Clean Focus Metrics at the Top -->
<div class="grid g4 mb">
<?php if ($variant === 'admin'): ?>
  <?= $kpi('Active Enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' total in pipeline', 'crm/enquiries', 'primary') ?>
  <?= $kpi('Confirmed Orders', crm_money0($m['orders_confirmed_value']), $n($m['orders_count']) . ' confirmed deals', 'crm/orders', 'good') ?>
  <?= $kpi('Quotation Pipeline', crm_money0($m['quotations_value']), $n($m['quotations_count']) . ' quotations prepared', 'crm/quotations') ?>
  <?= $kpi('Pending Follow-ups', $n($m['followups_pending']), ($m['followups_overdue'] ? $m['followups_overdue'] . ' overdue' : 'All up to date'), 'crm/followups', $m['followups_overdue'] ? 'warn' : 'good') ?>
<?php elseif ($variant === 'manager'): ?>
  <?= $kpi('Team Active Enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' total enquiries', 'crm/enquiries', 'primary') ?>
  <?= $kpi('Team Orders Won', crm_money0($m['won_value']), $n($m['orders_count']) . ' deals won', 'crm/orders', 'good') ?>
  <?= $kpi('Approvals Pending', $n($m['approvals_pending']), 'Awaiting price decision', 'crm/negotiations?status=pending', $m['approvals_pending'] ? 'warn' : '') ?>
  <?= $kpi('Team Win Rate', $conv, $n($m['won_count']) . ' won · ' . $n($m['lost_count']) . ' lost', 'crm/reports/conversion') ?>
<?php elseif ($variant === 'management'): ?>
  <?= $kpi('Confirmed Revenue', crm_money0($m['won_value']), $n($m['orders_count']) . ' confirmed orders', 'crm/orders', 'primary') ?>
  <?= $kpi('Quotation Value', crm_money0($m['quotations_value']), $n($m['quotations_count']) . ' active quotes', 'crm/quotations') ?>
  <?= $kpi('Conversion Rate', $conv, $n($m['won_count']) . ' won · ' . $n($m['lost_count']) . ' lost', 'crm/reports/conversion', 'good') ?>
  <?= $kpi('Total Customers', $n($m['customers_total']), $n($m['enquiries_active']) . ' active leads', 'crm/customers') ?>
<?php else: ?>
  <?= $kpi('My Active Enquiries', $n($m['enquiries_active']), $n($m['enquiries_total']) . ' assigned to me', 'crm/enquiries', 'primary') ?>
  <?= $kpi('Follow-ups Today', $n($m['followups_today']), ($m['followups_overdue'] ? $m['followups_overdue'] . ' overdue' : 'On schedule'), 'crm/followups', $m['followups_overdue'] ? 'warn' : 'good') ?>
  <?= $kpi('My Quotation Value', crm_money0($m['quotations_value']), $n($m['quotations_count']) . ' active quotes', 'crm/quotations') ?>
  <?= $kpi('My Won Sales', crm_money0($m['won_value']), $n($m['won_count']) . ' deals won', 'crm/orders', 'good') ?>
<?php endif; ?>
</div>

<!-- My Next Actions Priority List -->
<div class="card mb">
  <div class="hd">
    <h2>My Next Actions</h2>
    <a class="btn alt sm" href="<?= site_url('crm/followups') ?>">View all follow-ups &rarr;</a>
  </div>
  <div class="bd flush">
    <?php if (! $nextActions): ?>
      <div class="empty">
        <strong>You have no pending urgent actions!</strong>
        All due follow-ups, scheduled site visits and approvals are currently clear.
      </div>
    <?php else: ?>
      <?php foreach (array_slice($nextActions, 0, 6) as $act): ?>
        <div class="action-item">
          <div class="action-meta">
            <div class="action-title">
              <?= esc($act['title']) ?>
              <span class="badge badge-<?= esc($act['badge_tone']) ?>" style="margin-left:6px"><?= esc($act['badge']) ?></span>
            </div>
            <div class="action-sub"><?= esc($act['sub']) ?></div>
          </div>
          <a class="btn alt sm" href="<?= site_url($act['url']) ?>"><?= esc($act['action_label']) ?> &rarr;</a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Sales Pipeline & Conversion Flow -->
<div class="grid g-side mb">
  <div class="card">
    <div class="hd">
      <h2>Sales Pipeline by Stage</h2>
      <a class="btn alt sm" href="<?= site_url('crm/enquiries?view=kanban') ?>">Open pipeline board</a>
    </div>
    <div class="bd">
      <div class="bars">
      <?php foreach ($stages as $st => $cnt): ?>
        <a class="bar" href="<?= site_url('crm/enquiries?status=' . $st) ?>" style="color:inherit">
          <span class="nm"><strong><?= esc(crm_label($st)) ?></strong></span>
          <span class="tr"><span class="fl" style="width:<?= round(100 * $cnt / $stageMax) ?>%"></span></span>
          <span class="right"><?= $n($cnt) ?></span>
        </a>
      <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="hd"><h2>Enquiries by Lead Source</h2></div>
    <div class="bd">
      <script type="application/json" data-chart="bar"><?= json_encode(['title' => 'Enquiries by lead source', 'labels' => array_column($m['lead_sources'], 'source'), 'values' => array_map('intval', array_column($m['lead_sources'], 'n'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
      <noscript><table class="t"><?php foreach ($m['lead_sources'] as $s): ?><tr><td><?= esc($s['source']) ?></td><td class="num"><?= (int) $s['n'] ?></td></tr><?php endforeach; ?></table></noscript>
    </div>
  </div>
</div>

<!-- Sales Performance Trend (Enquiries vs Won Revenue) -->
<div class="grid g2 mb">
  <div class="card">
    <div class="hd"><h2>Enquiry Inflow · Last 6 Months</h2></div>
    <div class="bd">
      <script type="application/json" data-chart="line"><?= json_encode(['title' => 'Enquiries per month', 'labels' => $monthLabels, 'values' => array_map('intval', array_column($m['monthly'], 'enquiries'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
  </div>
  <div class="card">
    <div class="hd"><h2>Won Sales Revenue · Last 6 Months</h2></div>
    <div class="bd">
      <script type="application/json" data-chart="bar"><?= json_encode(['title' => 'Won value per month', 'fmt' => 'money', 'labels' => $monthLabels, 'values' => array_map('floatval', array_column($m['monthly'], 'won_value'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
  </div>
</div>

<!-- Operational Feeds (Follow-ups & Pending Site Visits) -->
<div class="grid g2 mb">
  <div class="card">
    <div class="hd">
      <h2>Today's Follow-ups</h2>
      <a class="btn alt sm" href="<?= site_url('crm/followups') ?>">All follow-ups</a>
    </div>
    <div class="bd flush">
      <?php if (! $today): ?>
        <div class="empty"><strong>No follow-ups due today</strong>Great job staying ahead of your calls.</div>
      <?php else: ?>
        <div class="tablewrap"><table class="t"><tbody>
        <?php foreach (array_slice($today, 0, 5) as $f): ?>
          <tr>
            <td class="nowrap"><strong><?= esc(date('H:i', strtotime($f['due_at']))) ?></strong></td>
            <td>
              <a href="<?= site_url('crm/customers/' . (int) $f['customer_id']) ?>"><strong><?= esc($f['customer_name']) ?></strong></a>
              <div class="muted small"><?= esc(crm_label($f['activity_type'])) ?><?= $f['enquiry_no'] ? ' · ' . esc($f['enquiry_no']) : '' ?></div>
            </td>
            <td class="muted small"><?= esc($f['assignee']) ?></td>
            <td class="right"><a class="btn alt sm" href="<?= site_url('crm/enquiries/' . (int) $f['enquiry_id']) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="hd">
      <h2>Upcoming Site Visits &amp; Measurements</h2>
      <a class="btn alt sm" href="<?= site_url('crm/calendar') ?>">Calendar</a>
    </div>
    <div class="bd flush">
      <?php if (! $visits): ?>
        <div class="empty"><strong>No site visits scheduled</strong>Schedule visits directly from customer enquiries.</div>
      <?php else: ?>
        <div class="tablewrap"><table class="t"><tbody>
        <?php foreach (array_slice($visits, 0, 5) as $v): ?>
          <tr>
            <td class="nowrap"><strong><?= esc(crm_dt($v['scheduled_at'])) ?></strong></td>
            <td>
              <a href="<?= site_url('crm/visits/' . (int) $v['id']) ?>"><strong><?= esc($v['visit_no']) ?></strong></a>
              <div class="muted small"><?= esc($v['customer_name']) ?></div>
            </td>
            <td class="right"><a class="btn alt sm" href="<?= site_url('crm/visits/' . (int) $v['id']) ?>">Details</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>
  </div>
</div>
