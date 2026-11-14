<?php
/** @var array $e @var bool $active @var array $next @var bool $canWon @var string $tab @var ?string $openModal */
$id   = (int) $e['id'];
$base = site_url('crm/enquiries/' . $id);
$tabs = ['overview' => 'Overview', 'requirements' => 'Requirements', 'activity' => 'Follow-ups & activity', 'visits' => 'Site visits', 'quotations' => 'Quotations', 'negotiations' => 'Negotiations',
         'orders' => 'Orders', 'timeline' => 'Timeline', 'ownership' => 'Ownership', 'status' => 'Status history', 'documents' => 'Documents'];
$cnt = ['requirements' => count(array_filter($requirements, static fn ($r) => $r['status'] === 'active')), 'activity' => count($followups), 'visits' => count($visits), 'quotations' => count($quotes),
        'negotiations' => count($negotiations), 'orders' => count($orders), 'documents' => count($documents)];
$canEdit   = $active && crm_can('enquiries.edit');
$canAssign = $active && ($e['current_owner_id'] ? crm_can('enquiries.reassign') : crm_can('enquiries.assign'));
$canClose  = $active && crm_can('enquiries.close');
$isClosed  = in_array($e['status'], ['won', 'lost'], true);
$isHold    = $e['status'] === 'on_hold';
$fmtDiff   = static function ($a) {
    if (! is_array($a) || ! $a) { return '—'; }
    return implode('; ', array_map(static fn ($k, $v) => crm_label((string) $k) . ': ' . (is_scalar($v) || $v === null ? ($v === null || $v === '' ? '—' : $v) : json_encode($v)), array_keys($a), $a));
};
$size = static fn ($r) => ($r['width'] ?? '') !== '' && $r['width'] !== null
    ? rtrim(rtrim((string) $r['width'], '0'), '.') . (($r['height'] ?? '') !== '' && $r['height'] !== null ? ' × ' . rtrim(rtrim((string) $r['height'], '0'), '.') : '') . ' ' . ($r['size_unit'] ?? '') . (($r['nos'] ?? '') !== '' && $r['nos'] !== null ? ' × ' . rtrim(rtrim((string) $r['nos'], '0'), '.') . ' nos' : '')
    : '—';
$num = static fn ($v) => rtrim(rtrim((string) $v, '0'), '.');
$fill = static fn (array $r) => implode(',', array_map(static fn ($k) => $k . '=' . rawurlencode((string) ($r[$k] ?? '')), ['product_id', 'description', 'variant', 'width', 'height', 'size_unit', 'nos', 'quantity', 'unit', 'expected_price', 'notes']));
?>
<div class="pagehead">
  <div style="flex:1;min-width:240px">
    <h1><?= esc($e['enquiry_no']) ?> <span class="muted" style="font-weight:500">· <?= esc($e['project_name'] ?: 'No project name') ?></span></h1>
    <div class="gap" style="margin-top:6px">
      <?= crm_badge($e['status']) ?> <?= crm_badge($e['priority']) ?>
      <span class="muted small">Lead score <strong><?= (int) $e['lead_score'] ?></strong></span>
      <span class="muted small">Customer <a href="<?= site_url('crm/customers/' . (int) $e['customer_id']) ?>"><?= esc($e['customer_name']) ?> (<?= esc($e['customer_no']) ?>)</a></span>
      <span class="muted small">Owner <strong><?= esc($e['owner_name'] ?: 'Unassigned') ?></strong><?= $e['team_name'] ? ' · ' . esc($e['team_name']) : '' ?></span>
    </div>
  </div>
</div>

<?php
// Compute the 7-step visual sales journey based on current status
$journeySteps = [
    ['key' => 'new', 'no' => 'Step 1', 'name' => 'New Enquiry'],
    ['key' => 'contacted', 'no' => 'Step 2', 'name' => 'Contact'],
    ['key' => 'followup', 'no' => 'Step 3', 'name' => 'Follow-up'],
    ['key' => 'site_visit', 'no' => 'Step 4', 'name' => 'Site Visit'],
    ['key' => 'quotation', 'no' => 'Step 5', 'name' => 'Quotation'],
    ['key' => 'confirmation', 'no' => 'Step 6', 'name' => 'Confirmation'],
    ['key' => 'order', 'no' => 'Step 7', 'name' => 'Sales Order'],
];

$stOrder = [
    'new' => 1, 'assigned' => 1,
    'contacted' => 2,
    'qualified' => 3,
    'site_visit' => 4,
    'quotation_preparation' => 5, 'quotation_sent' => 5, 'revised' => 5,
    'negotiation' => 6, 'accepted' => 6,
    'won' => 7,
];
$curStep = $stOrder[$e['status']] ?? 1;
?>

<!-- Visual Sales Progress Journey Bar -->
<div class="sales-journey" aria-label="Sales progress">
  <?php foreach ($journeySteps as $idx => $step): 
      $stepNum = $idx + 1;
      $state = ($stepNum < $curStep || $e['status'] === 'won') ? 'done' : ($stepNum === $curStep ? 'current' : '');
  ?>
    <div class="journey-step <?= $state ?>">
      <div class="step-no"><?= esc($step['no']) ?></div>
      <div class="step-name"><?= esc($step['name']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb"><div class="bd gap">
  <!-- Contextual Primary Next Action based on Current Sales Stage -->
  <?php if ($active): ?>
    <?php if (in_array($e['status'], ['new', 'assigned'], true) && crm_can('followups.manage')): ?>
      <button class="btn teal sm" type="button" data-modal="m-contact">&#9654; Contact Customer</button>
    <?php elseif ($e['status'] === 'contacted' && crm_can('followups.manage')): ?>
      <button class="btn teal sm" type="button" data-modal="m-followup">&#9654; Schedule Follow-up</button>
    <?php elseif ($e['status'] === 'qualified' && crm_can('visits.manage')): ?>
      <button class="btn teal sm" type="button" data-modal="m-visit">&#9654; Schedule Site Visit</button>
    <?php elseif (in_array($e['status'], ['site_visit', 'quotation_preparation'], true) && crm_can('quotations.create')): ?>
      <a class="btn teal sm" href="<?= site_url('crm/quotations/new?enquiry=' . $id) ?>">&#9654; Prepare Quotation</a>
    <?php elseif ($e['status'] === 'quotation_sent' && $quotes && crm_can('negotiations.create')): ?>
      <a class="btn teal sm" href="<?= site_url('crm/quotations/' . (int) $quotes[0]['id']) ?>#negotiate">&#9654; Discuss Price &amp; Discounts</a>
    <?php elseif ($canClose && $canWon): ?>
      <button class="btn teal sm" type="button" data-modal="m-won">&#9654; Confirm Order (Mark Won)</button>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($canAssign): ?><button class="btn alt sm" type="button" data-modal="m-assign"><?= $e['current_owner_id'] ? 'Reassign' : 'Assign' ?></button><?php endif; ?>
  <?php if ($canEdit && $next): ?><button class="btn alt sm" type="button" data-modal="m-status">Change status</button><?php endif; ?>
  <?php if ($active && crm_can('followups.manage')): ?>
    <button class="btn alt sm" type="button" data-modal="m-contact">Record contact</button>
    <button class="btn alt sm" type="button" data-modal="m-followup">Schedule follow-up</button>
  <?php endif; ?>
  <?php if ($active && crm_can('visits.manage')): ?><button class="btn alt sm" type="button" data-modal="m-visit">Schedule site visit</button><?php endif; ?>
  <?php if ($active && crm_can('quotations.create')): ?><a class="btn alt sm" href="<?= site_url('crm/quotations/new?enquiry=' . $id) ?>">Create quotation</a><?php endif; ?>
  <?php if ($active && $quotes && crm_can('negotiations.create')): ?><a class="btn alt sm" href="<?= site_url('crm/quotations/' . (int) $quotes[0]['id']) ?>#negotiate">Request negotiation</a><?php endif; ?>
  <?php if ($canClose && $canWon): ?><button class="btn sm" type="button" data-modal="m-won">Mark won</button><?php endif; ?>
  <?php if ($canClose): ?><button class="btn danger sm" type="button" data-modal="m-lost">Mark lost</button><button class="btn alt sm" type="button" data-modal="m-hold">Put on hold</button><?php endif; ?>
  <?php if (($isClosed || $isHold) && crm_can('enquiries.reopen', 'enquiries.edit')): ?><button class="btn alt sm" type="button" data-modal="m-reopen"><?= $isHold ? 'Resume' : 'Reopen' ?></button><?php endif; ?>
  <?php if (! $active && ! $isClosed && ! $isHold): ?><span class="muted small">No actions available.</span><?php endif; ?>
</div></div>

<nav class="tabs" aria-label="Enquiry sections">
  <?php foreach ($tabs as $k => $label): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= $base ?>?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($label) ?><?= ! empty($cnt[$k]) ? ' <span class="muted small">(' . $cnt[$k] . ')</span>' : '' ?></a><?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'): ?>
<div class="grid g-side">
  <div class="card"><div class="hd"><h2>Enquiry details</h2></div><div class="bd"><dl class="kv">
    <dt>Customer</dt><dd><?= esc($e['customer_name']) ?> · <?= esc($e['customer_mobile']) ?><?= $e['customer_email'] ? ' · ' . esc($e['customer_email']) : '' ?></dd>
    <dt>Category</dt><dd><?= esc($e['category_name'] ?: '—') ?></dd>
    <dt>Site location</dt><dd><?= esc($e['site_location'] ?: '—') ?></dd>
    <dt>Estimated budget</dt><dd><?= $e['est_budget'] !== null ? crm_money($e['est_budget']) : '—' ?></dd>
    <dt>Expected purchase</dt><dd><?= crm_date($e['expected_purchase_date']) ?></dd>
    <dt>Source</dt><dd><?= esc($e['source_name'] ?: '—') ?> <span class="muted small">(<?= esc(crm_label($e['source_channel'])) ?>)</span></dd>
    <dt>Created</dt><dd><?= crm_dt($e['created_at']) ?></dd>
    <dt>Requirements</dt><dd><?= nl2br(esc($e['requirements'] ?: '—')) ?></dd>
    <dt>Notes</dt><dd><?= nl2br(esc($e['description'] ?: '—')) ?></dd>
  </dl></div></div>
  <div class="grid">
    <?php if ($e['outcome']): ?>
    <div class="card"><div class="hd"><h3>Outcome</h3><?= crm_badge($e['outcome']) ?></div><div class="bd"><dl class="kv">
      <dt>Closed</dt><dd><?= crm_dt($e['closed_at']) ?><?= $e['closed_by_name'] ? ' by ' . esc($e['closed_by_name']) : '' ?></dd>
      <?php if ($e['final_value'] !== null): ?><dt>Final value</dt><dd><?= crm_money($e['final_value']) ?></dd><?php endif; ?>
      <?php if ($e['lost_reason']): ?><dt>Lost reason</dt><dd><?= esc($e['lost_reason']) ?></dd><?php endif; ?>
      <?php if ($e['lost_competitor']): ?><dt>Competitor</dt><dd><?= esc($e['lost_competitor']) ?></dd><?php endif; ?>
      <?php if ($e['hold_review_date']): ?><dt>Review on</dt><dd><?= crm_date($e['hold_review_date']) ?></dd><?php endif; ?>
      <?php if ($e['close_remarks']): ?><dt>Remarks</dt><dd><?= nl2br(esc($e['close_remarks'])) ?></dd><?php endif; ?>
    </dl></div></div>
    <?php endif; ?>
    <div class="card"><div class="hd"><h3>At a glance</h3></div><div class="bd"><dl class="kv">
      <dt>Active requirements</dt><dd><?= (int) $cnt['requirements'] ?></dd>
      <dt>Open follow-ups</dt><dd><?= count(array_filter($followups, static fn ($f) => $f['status'] === 'pending')) ?></dd>
      <dt>Site visits</dt><dd><?= count($visits) ?></dd>
      <dt>Quotations</dt><dd><?= count($quotes) ?></dd>
      <dt>Negotiations</dt><dd><?= count($negotiations) ?></dd>
      <dt>Sales orders</dt><dd><?= count($orders) ?></dd>
    </dl></div></div>
  </div>
</div>

<?php elseif ($tab === 'requirements'): ?>
<div class="card">
  <div class="hd"><h2>What the customer wants now</h2><?php if ($canEdit): ?><button class="btn teal sm" type="button" data-modal="m-req-add">+ Add item</button><?php endif; ?></div>
  <div class="bd flush">
  <?php if (! $requirements): ?><div class="empty"><strong>No requirement items yet</strong>Add products, variants and sizes as the customer's needs become clear. The active items pre-fill the next quotation.</div>
  <?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Item</th><th>Variant</th><th>Size</th><th class="num">Qty</th><th class="num">Expected price</th><th>Notes</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($requirements as $r): ?>
      <tr<?= $r['status'] === 'dropped' ? ' style="opacity:.55"' : '' ?>>
        <td><strong><?= esc($r['description']) ?></strong></td><td><?= esc($r['variant'] ?: '—') ?></td><td class="nowrap"><?= esc($size($r)) ?></td>
        <td class="num"><?= esc($num($r['quantity'])) ?> <?= esc($r['unit']) ?></td><td class="num"><?= $r['expected_price'] !== null ? crm_money($r['expected_price']) : '—' ?></td>
        <td><?= esc($r['notes'] ?: '') ?></td><td><?= crm_badge($r['status']) ?></td>
        <td class="nowrap"><?php if ($canEdit): ?>
          <button class="btn alt sm" type="button" data-modal="m-req-edit" data-action="<?= site_url('crm/enquiries/requirements/' . (int) $r['id'] . '/update') ?>" data-fill="<?= esc($fill($r)) ?>"><?= $r['status'] === 'dropped' ? 'Restore' : 'Edit' ?></button>
          <?php if ($r['status'] === 'active'): ?><button class="btn danger sm" type="button" data-modal="m-req-drop" data-action="<?= site_url('crm/enquiries/requirements/' . (int) $r['id'] . '/drop') ?>">Drop</button><?php endif; ?>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
</div>
<div class="card mt"><div class="hd"><h3>Requirement change history</h3><span class="muted small">Every edit is kept — nothing is overwritten</span></div><div class="bd flush">
  <?php if (! $reqHistory): ?><div class="empty">No changes recorded yet.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>When</th><th>By</th><th>Action</th><th>Before</th><th>After</th><th>Reason</th></tr></thead><tbody>
    <?php foreach ($reqHistory as $h): ?><tr><td class="nowrap"><?= crm_dt($h['created_at']) ?></td><td><?= esc($h['user_name']) ?></td><td><?= crm_badge($h['action']) ?></td>
      <td class="small"><?= esc($fmtDiff($h['before'] ?? null)) ?></td><td class="small"><?= esc($fmtDiff($h['after'] ?? null)) ?></td><td><?= esc($h['reason'] ?: '—') ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'activity'): ?>
<div class="card"><div class="hd"><h2>Follow-ups</h2></div><div class="bd flush">
  <?php if (! $followups): ?><div class="empty">No follow-ups scheduled.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Due</th><th>Type</th><th>Assigned to</th><th>Status</th><th>Outcome / remarks</th><th>Next action</th></tr></thead><tbody>
    <?php foreach ($followups as $f): $over = $f['status'] === 'pending' && strtotime($f['due_at']) < time(); ?>
      <tr><td class="nowrap"><?= crm_dt($f['due_at']) ?><?= $over ? ' <span class="badge badge-red">Overdue</span>' : '' ?></td><td><?= esc(crm_label($f['activity_type'])) ?></td><td><?= esc($f['user_name']) ?></td>
        <td><?= crm_badge($f['status']) ?><?= $f['escalated_at'] ? ' <span class="badge badge-red">Escalated</span>' : '' ?></td><td><?= esc($f['outcome'] ?: '') ?><div class="muted small"><?= esc($f['remarks'] ?: '') ?></div></td><td><?= esc($f['next_action'] ?: '—') ?></td></tr>
    <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div></div>
<div class="card mt"><div class="hd"><h2>Activity log</h2></div><div class="bd">
  <?php if (! $activities): ?><div class="empty">No interactions recorded yet.</div><?php else: ?><ul class="tl">
    <?php foreach ($activities as $a): ?><li><div class="when"><?= crm_dt($a['occurred_at']) ?> · <?= esc(crm_label($a['activity_type'])) ?></div><div class="what"><?= esc($a['summary']) ?></div>
      <div class="meta"><?= esc($a['user_name'] ?: 'System') ?><?= $a['outcome'] ? ' · Outcome: ' . esc($a['outcome']) : '' ?></div></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>

<?php elseif ($tab === 'visits'): ?>
<div class="card"><div class="hd"><h2>Site visits &amp; measurements</h2></div><div class="bd flush">
  <?php if (! $visits): ?><div class="empty"><strong>No site visits</strong>Schedule one from the action bar.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Visit</th><th>Scheduled</th><th>Assigned to</th><th>Status</th><th>Address</th><th class="num">Measurement versions</th><th></th></tr></thead><tbody>
    <?php foreach ($visits as $v): ?><tr><td><strong><?= esc($v['visit_no']) ?></strong></td><td class="nowrap"><?= crm_dt($v['scheduled_at']) ?></td><td><?= esc($v['user_name']) ?></td><td><?= crm_badge($v['status']) ?></td>
      <td><?= esc($v['address'] ?: '—') ?></td><td class="num"><?= (int) $v['meas_versions'] ?></td><td><?php if (crm_can('visits.manage')): ?><a class="btn alt sm" href="<?= site_url('crm/visits/' . (int) $v['id']) ?>">Open</a><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'quotations'): ?>
<div class="card"><div class="hd"><h2>Quotations</h2><?php if ($active && crm_can('quotations.create')): ?><a class="btn teal sm" href="<?= site_url('crm/quotations/new?enquiry=' . $id) ?>">+ New quotation</a><?php endif; ?></div><div class="bd flush">
  <?php if (! $quotes): ?><div class="empty"><strong>No quotations yet</strong>Active requirement items will pre-fill the quotation editor.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Quotation</th><th>Status</th><th>Current version</th><th class="num">Versions</th><th class="num">Total</th><th>Valid until</th><th>Customer response</th><th></th></tr></thead><tbody>
    <?php foreach ($quotes as $q): ?><tr><td><a href="<?= site_url('crm/quotations/' . (int) $q['id']) ?>"><strong><?= esc($q['quotation_no']) ?></strong></a></td><td><?= crm_badge($q['status']) ?></td><td>V<?= (int) $q['cur_version'] ?></td>
      <td class="num"><?= (int) $q['versions'] ?></td><td class="num"><?= crm_money($q['grand_total']) ?></td><td><?= crm_date($q['valid_until']) ?></td><td><?= $q['customer_response'] ? crm_badge($q['customer_response']) : '—' ?></td>
      <td class="nowrap"><a class="btn alt sm" href="<?= site_url('crm/quotations/' . (int) $q['id']) ?>">Open</a><?php if ((int) $q['versions'] > 1): ?> <a class="btn alt sm" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/compare') ?>">Compare versions</a><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'negotiations'): ?>
<div class="card"><div class="hd"><h2>Negotiations &amp; discount requests</h2></div><div class="bd flush">
  <?php if (! $negotiations): ?><div class="empty">No negotiations recorded.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Negotiation</th><th>Quotation</th><th>Requested by</th><th class="num">Requested %</th><th class="num">Proposed price</th><th>Status</th><th>Raised</th></tr></thead><tbody>
    <?php foreach ($negotiations as $n): ?><tr><td><a href="<?= site_url('crm/negotiations/' . (int) $n['id']) ?>"><strong><?= esc($n['negotiation_no']) ?></strong></a></td><td><a href="<?= site_url('crm/quotations/' . (int) $n['quotation_id']) ?>"><?= esc($n['quotation_no']) ?></a></td>
      <td><?= esc($n['requester']) ?></td><td class="num"><?= esc($num($n['requested_discount_pct'])) ?>%</td><td class="num"><?= $n['proposed_price'] !== null ? crm_money($n['proposed_price']) : '—' ?></td><td><?= crm_badge($n['status']) ?></td><td class="nowrap"><?= crm_dt($n['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'orders'): ?>
<div class="card"><div class="hd"><h2>Sales orders</h2></div><div class="bd flush">
  <?php if (! $orders): ?><div class="empty">No sales order yet. An order is created once the customer accepts a quotation.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>Order</th><th>Quotation</th><th>Status</th><th class="num">Value</th><th>Confirmed</th><th>Expected delivery</th></tr></thead><tbody>
    <?php foreach ($orders as $o): ?><tr><td><a href="<?= site_url('crm/orders/' . (int) $o['id']) ?>"><strong><?= esc($o['order_no']) ?></strong></a></td><td><a href="<?= site_url('crm/quotations/' . (int) $o['quotation_id']) ?>"><?= esc($o['quotation_no']) ?></a></td>
      <td><?= crm_badge($o['status']) ?></td><td class="num"><?= crm_money($o['grand_total']) ?></td><td><?= crm_dt($o['confirmed_at']) ?></td><td><?= crm_date($o['expected_delivery']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'timeline'): ?>
<div class="card"><div class="hd"><h2>Workflow timeline</h2><span class="muted small">Newest first</span></div><div class="bd">
  <?php if (! $events): ?><div class="empty">No events yet.</div><?php else: ?><ul class="tl">
    <?php foreach ($events as $ev): ?><li><div class="when"><?= crm_dt($ev['created_at']) ?></div>
      <div class="what"><?php if ($ev['link']): ?><a href="<?= site_url($ev['link']) ?>"><?= esc($ev['label']) ?></a><?php else: ?><?= esc($ev['label']) ?><?php endif; ?></div>
      <div class="meta"><?= esc($ev['user_name']) ?>
        <?php if ($ev['prev_owner'] || $ev['new_owner']): ?> · Owner: <?= esc($ev['prev_owner'] ?: '—') ?> → <?= esc($ev['new_owner'] ?: '—') ?><?php endif; ?>
        <?php if ($ev['prev_status'] || $ev['new_status']): ?> · Status: <?= esc(crm_label($ev['prev_status'])) ?> → <?= esc(crm_label($ev['new_status'])) ?><?php endif; ?>
        <?= ! empty($ev['entity_ref']) ? ' · Ref ' . esc($ev['entity_ref']) : '' ?><?= ! empty($ev['reason']) ? ' · ' . esc($ev['reason']) : '' ?></div></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>

<?php elseif ($tab === 'ownership'): ?>
<div class="card"><div class="hd"><h2>Ownership history</h2></div><div class="bd flush">
  <?php if (! $ownership): ?><div class="empty">This enquiry has never been assigned.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>When</th><th>From</th><th>To</th><th>Method</th><th>By</th><th>Reason</th></tr></thead><tbody>
    <?php foreach ($ownership as $o): ?><tr><td class="nowrap"><?= crm_dt($o['created_at']) ?></td><td><?= esc($o['from_name'] ?: '—') ?></td><td><strong><?= esc($o['to_name'] ?: '—') ?></strong></td><td><?= esc(crm_label($o['method'])) ?></td><td><?= esc($o['by_name']) ?></td><td><?= esc($o['reason'] ?: '—') ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>

<?php elseif ($tab === 'status'): ?>
<div class="card"><div class="hd"><h2>Status history</h2></div><div class="bd flush"><div class="tablewrap"><table class="t"><thead><tr><th>When</th><th>From</th><th>To</th><th>By</th><th>Reason</th></tr></thead><tbody>
  <?php foreach ($statusHistory as $h): ?><tr><td class="nowrap"><?= crm_dt($h['created_at']) ?></td><td><?= $h['from_status'] ? crm_badge($h['from_status']) : '—' ?></td><td><?= crm_badge($h['to_status']) ?></td><td><?= esc($h['user_name'] ?: 'System') ?></td><td><?= esc($h['reason'] ?: '—') ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>

<?php elseif ($tab === 'documents'): ?>
<div class="card"><div class="hd"><h2>Documents</h2><span class="muted small">Uploaded against site visits</span></div><div class="bd flush">
  <?php if (! $documents): ?><div class="empty">No documents uploaded.</div><?php else: ?><div class="tablewrap"><table class="t"><thead><tr><th>File</th><th>Attached to</th><th>Type</th><th class="num">Size</th><th>Uploaded</th></tr></thead><tbody>
    <?php foreach ($documents as $d): ?><tr><td><a href="<?= site_url('crm/documents/' . (int) $d['id']) ?>"><?= esc($d['original_name']) ?></a></td><td><?= esc(crm_label($d['entity_type'])) ?> #<?= (int) $d['entity_id'] ?></td><td><?= esc($d['mime']) ?></td><td class="num"><?= number_format($d['size_bytes'] / 1024, 1) ?> KB</td><td class="nowrap"><?= crm_dt($d['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>
<?php endif; ?>

<?php
// ───────────── modals ─────────────
$m = static fn (string $id, string $title, string $action, string $body, string $submit = 'Save', bool $open = false) =>
    '<div class="modal' . ($open ? ' open' : '') . '" id="' . $id . '" role="dialog" aria-modal="true" aria-label="' . esc($title) . '"><div class="box"><div class="hd"><h3>' . esc($title) . '</h3><button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button></div>'
    . '<form method="post" action="' . $action . '" data-validate>' . csrf_field() . '<div class="bd"><div class="form" style="grid-template-columns:1fr">' . $body . '</div></div><div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">' . esc($submit) . '</button></div></form></div></div>';
$userOpts = static function (array $users, $sel = null) {
    $o = '';
    foreach ($users as $u) { $o .= '<option value="' . (int) $u['id'] . '"' . ((string) $sel === (string) $u['id'] ? ' selected' : '') . '>' . esc($u['name']) . '</option>'; }
    return $o;
};
$prodOpts = '<option value="">— custom item (no catalogue product) —</option>';
foreach ($products as $p) { $prodOpts .= '<option value="' . (int) $p['id'] . '">' . esc($p['sku'] . ' · ' . $p['name']) . '</option>'; }
$unitOpts = '<option value="mm">mm</option><option value="cm">cm</option><option value="inch">inch</option><option value="ft" selected>ft</option>';
$reqFields = '<div><label for="PFX-product_id">Catalogue product</label><select id="PFX-product_id" name="product_id" data-search>' . $prodOpts . '</select></div>'
    . '<div><label class="req" for="PFX-description">Item description</label><input id="PFX-description" name="description" maxlength="500" required></div>'
    . '<div><label for="PFX-variant">Variant (e.g. colour, finish, profile)</label><input id="PFX-variant" name="variant" maxlength="150"></div>'
    . '<div class="form" style="grid-template-columns:repeat(4,1fr);gap:8px"><div><label for="PFX-width">Width</label><input id="PFX-width" type="number" step="0.01" min="0" name="width"></div>'
    . '<div><label for="PFX-height">Height</label><input id="PFX-height" type="number" step="0.01" min="0" name="height"></div>'
    . '<div><label for="PFX-size_unit">Unit</label><select id="PFX-size_unit" name="size_unit"><option value="">—</option>' . $unitOpts . '</select></div>'
    . '<div><label for="PFX-nos">Nos</label><input id="PFX-nos" type="number" step="0.01" min="0" name="nos"></div></div>'
    . '<div class="form" style="grid-template-columns:repeat(3,1fr);gap:8px"><div><label for="PFX-quantity">Quantity</label><input id="PFX-quantity" type="number" step="0.001" min="0" name="quantity" value="1"></div>'
    . '<div><label for="PFX-unit">Billing unit</label><select id="PFX-unit" name="unit"><option value="nos">nos</option><option value="sqft">sqft</option><option value="rft">rft</option><option value="set">set</option></select></div>'
    . '<div><label for="PFX-expected_price">Expected unit price (₹)</label><input id="PFX-expected_price" type="number" step="0.01" min="0" name="expected_price"></div></div>'
    . '<div class="hint">For sq-ft / running-ft items the quantity is calculated from the size on the server.</div>'
    . '<div><label for="PFX-notes">Notes</label><input id="PFX-notes" name="notes" maxlength="500"></div>'
    . '<div><label for="PFX-reason">Reason for this change</label><input id="PFX-reason" name="reason" maxlength="500" placeholder="e.g. customer asked for a different finish at the follow-up call"></div>';

echo $m('m-assign', $e['current_owner_id'] ? 'Reassign enquiry' : 'Assign enquiry', $base . '/assign',
    '<div><label class="req" for="as-user">Salesperson</label><select id="as-user" name="user_id" required><option value="">— choose —</option>' . $userOpts($salesUsers) . '</select></div>'
    . '<div><label for="as-reason"' . ($e['current_owner_id'] ? ' class="req"' : '') . '>Reason</label><input id="as-reason" name="reason" maxlength="255"' . ($e['current_owner_id'] ? ' required' : '') . '></div>'
    . '<p class="hint">Current owner: ' . esc($e['owner_name'] ?: 'none') . '. The previous owner stays visible in the ownership history.</p>', 'Save assignment');

echo $m('m-status', 'Change status', $base . '/status',
    '<div><label class="req" for="st-to">New status</label><select id="st-to" name="to" required>' . implode('', array_map(static fn ($n) => '<option value="' . esc($n) . '">' . esc(crm_label($n)) . '</option>', $next)) . '</select></div>'
    . '<div><label for="st-reason">Reason / remarks</label><input id="st-reason" name="reason" maxlength="255"></div>', 'Update status');

echo $m('m-contact', 'Record customer contact', $base . '/contact',
    '<div><label for="ct-type">Type</label><select id="ct-type" name="type"><option value="call">Call</option><option value="meeting">Meeting</option><option value="whatsapp">WhatsApp</option><option value="email">Email</option><option value="note">Internal note</option></select></div>'
    . '<div><label class="req" for="ct-sum">What happened?</label><textarea id="ct-sum" name="summary" required maxlength="500"></textarea></div>'
    . '<div><label for="ct-out">Outcome / customer response</label><input id="ct-out" name="outcome" maxlength="255"></div>'
    . '<div><label for="ct-at">When (leave blank for now)</label><input id="ct-at" type="datetime-local" name="occurred_at" max="' . date('Y-m-d\TH:i') . '"></div>', 'Record contact');

echo $m('m-followup', 'Schedule follow-up', $base . '/followups',
    '<div><label class="req" for="fu-due">Due date &amp; time</label><input id="fu-due" type="datetime-local" name="due_at" required min="' . date('Y-m-d\TH:i') . '"></div>'
    . '<div><label for="fu-type">Activity</label><select id="fu-type" name="type"><option value="call">Call</option><option value="meeting">Meeting</option><option value="whatsapp">WhatsApp</option><option value="email">Email</option><option value="visit">Visit</option><option value="other">Other</option></select></div>'
    . '<div><label for="fu-to">Assign to</label><select id="fu-to" name="assign_to"><option value="">Enquiry owner</option>' . $userOpts($salesUsers) . '</select></div>'
    . '<div><label for="fu-next">Planned action</label><input id="fu-next" name="next_action" maxlength="255"></div>'
    . '<div><label for="fu-rem">Remarks</label><textarea id="fu-rem" name="remarks"></textarea></div>', 'Schedule');

echo $m('m-visit', 'Schedule site visit', $base . '/visits',
    '<div><label class="req" for="sv-at">Visit date &amp; time</label><input id="sv-at" type="datetime-local" name="scheduled_at" required min="' . date('Y-m-d\TH:i') . '"></div>'
    . '<div><label for="sv-to">Assigned to</label><select id="sv-to" name="assign_to"><option value="">Enquiry owner</option>' . $userOpts($salesUsers) . '</select></div>'
    . '<div><label for="sv-addr">Address</label><input id="sv-addr" name="address" maxlength="255" value="' . esc($e['site_location'] ?? '') . '"></div>'
    . '<div><label for="sv-rem">Remarks</label><textarea id="sv-rem" name="remarks"></textarea></div>', 'Schedule visit');

echo $m('m-won', 'Mark enquiry as won', $base . '/won',
    '<p>A won enquiry needs an accepted quotation converted to a sales order. ' . ($orders ? 'This enquiry has order ' . esc($orders[0]['order_no']) . '.' : 'No sales order exists yet — create one from the accepted quotation first, or (admin only) record an exception below.') . '</p>'
    . (crm_can('discounts.approve_admin') ? '<div><label for="won-ex">Admin exception reason (only if there is no order)</label><input id="won-ex" name="exception_reason" maxlength="255"></div>' : ''), 'Mark won', $openModal === 'won');

$lr = '';
foreach ($lostReasons as $r) { $lr .= '<option value="' . (int) $r['id'] . '">' . esc($r['name']) . '</option>'; }
echo $m('m-lost', 'Mark enquiry as lost', $base . '/lost',
    '<div><label class="req" for="ls-r">Lost reason</label><select id="ls-r" name="lost_reason_id" required><option value="">— choose —</option>' . $lr . '</select></div>'
    . '<div><label for="ls-c">Competitor / alternative chosen</label><input id="ls-c" name="competitor" maxlength="150"></div>'
    . '<div><label for="ls-rem">Remarks</label><textarea id="ls-rem" name="remarks"></textarea></div>'
    . '<p class="hint">Open quotations and negotiations on this enquiry will be cancelled/withdrawn. The enquiry can be reopened later with an audit trail.</p>', 'Mark lost', $openModal === 'lost');

echo $m('m-hold', 'Put enquiry on hold', $base . '/hold',
    '<div><label class="req" for="hd-r">Reason</label><input id="hd-r" name="reason" required maxlength="255"></div>'
    . '<div><label class="req" for="hd-d">Next review date</label><input id="hd-d" type="date" name="hold_review_date" required min="' . date('Y-m-d') . '"></div>', 'Put on hold', $openModal === 'hold');

echo $m('m-reopen', $isHold ? 'Resume enquiry' : 'Reopen enquiry', $isHold ? $base . '/status' : $base . '/reopen',
    ($isHold ? '<input type="hidden" name="to" value="contacted">' : '') . '<div><label class="req" for="ro-r">Reason</label><input id="ro-r" name="reason" required maxlength="255"></div>', $isHold ? 'Resume' : 'Reopen');

if ($canEdit) {
    echo $m('m-req-add', 'Add requirement item', $base . '/requirements', str_replace('PFX', 'ra', $reqFields), 'Add item');
    echo $m('m-req-edit', 'Edit requirement item', $base . '/requirements', str_replace('PFX', 're', $reqFields), 'Save change');
    echo $m('m-req-drop', 'Drop requirement item', $base . '/requirements', '<div><label class="req" for="rd-r">Reason</label><input id="rd-r" name="reason" required maxlength="500"></div><p class="hint">The item stays in the history and can be restored later.</p>', 'Drop item');
}
?>
