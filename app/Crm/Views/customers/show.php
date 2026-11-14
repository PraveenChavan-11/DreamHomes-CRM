<?php
/** @var array $t tab data @var array $c customer+meta @var string $tab @var array<string,string> $tabs @var array<string,int|null> $counts */
$base = site_url('crm/customers/' . (int) $c['id']);
$tbl = static function (array $cols, array $rows, string $emptyMsg = 'Nothing recorded yet.') {
    if (! $rows) { return '<div class="empty"><strong>' . esc($emptyMsg) . '</strong></div>'; }
    $h = '<div class="tablewrap"><table class="t"><thead><tr>';
    foreach ($cols as $label => $_) { $h .= '<th>' . esc($label) . '</th>'; }
    $h .= '</tr></thead><tbody>';
    foreach ($rows as $r) {
        $h .= '<tr>';
        foreach ($cols as $fn) { $h .= '<td>' . $fn($r) . '</td>'; }
        $h .= '</tr>';
    }

    return $h . '</tbody></table></div>';
};
$e = static fn ($v) => esc((string) ($v ?? '—'));
$enqLink = static fn ($r) => ! empty($r['enquiry_id']) ? '<a href="' . site_url('crm/enquiries/' . (int) $r['enquiry_id']) . '">' . $e($r['enquiry_no'] ?? ('#' . $r['enquiry_id'])) . '</a>' : $e($r['enquiry_no'] ?? null);
?>
<div class="pagehead">
  <h1><?= esc($c['name']) ?> <?= crm_badge($c['status']) ?><div class="muted small" style="font-weight:400"><?= esc($c['customer_no']) ?> · <?= esc(crm_label($c['customer_type'])) ?><?= $c['company_name'] ? ' · ' . esc($c['company_name']) : '' ?></div></h1>
  <?php if (crm_can('enquiries.create') && $c['status'] === 'active'): ?><a class="btn teal" href="<?= site_url('crm/enquiries/new?customer_id=' . (int) $c['id']) ?>">+ New enquiry</a><?php endif; ?>
  <?php if (crm_can('customers.edit')): ?><a class="btn alt" href="<?= $base ?>/edit">Edit</a><?php endif; ?>
  <?php if (crm_can('customers.deactivate')): ?><button class="btn alt" type="button" data-modal="status-modal">Change status</button><?php endif; ?>
</div>

<nav class="tabs" aria-label="Customer sections">
<?php foreach ($tabs as $k => $label): ?>
  <a href="<?= $base ?>?tab=<?= $k ?>" class="<?= $tab === $k ? 'on' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($label) ?><?= $counts[$k] !== null ? ' <span class="muted small">(' . (int) $counts[$k] . ')</span>' : '' ?></a>
<?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'): ?>
<div class="grid g-side">
  <div class="grid">
    <div class="card"><div class="hd"><h2>Customer details</h2></div><div class="bd"><dl class="kv">
      <dt>Primary contact</dt><dd><?= $e($c['primary_contact']) ?></dd>
      <dt>Mobile</dt><dd><?= $e($c['mobile']) ?><?= $c['alt_mobile'] ? ' · ' . esc($c['alt_mobile']) : '' ?></dd>
      <dt>Email</dt><dd><?= $e($c['email']) ?></dd>
      <dt>City / State / PIN</dt><dd><?= $e(trim(($c['city'] ?? '') . ' ' . ($c['state'] ?? '') . ' ' . ($c['pin_code'] ?? '')) ?: null) ?></dd>
      <dt>Billing address</dt><dd><?= nl2br($e($c['billing_address'])) ?></dd>
      <dt>Site address</dt><dd><?= nl2br($e($c['site_address'])) ?></dd>
      <dt>GSTIN</dt><dd><?= $e($c['gstin']) ?></dd>
      <dt>Source</dt><dd><?= $e($c['source_name']) ?></dd>
      <dt>Relationship owner</dt><dd><?= $e($c['owner_name']) ?></dd>
      <dt>Remarks</dt><dd><?= nl2br($e($c['remarks'])) ?></dd>
      <dt>Created</dt><dd><?= esc(crm_dt($c['created_at'])) ?></dd>
      <dt>Last updated</dt><dd><?= esc(crm_dt($c['updated_at'])) ?></dd>
    </dl></div></div>
    <div class="card"><div class="hd"><h2>Sales timeline</h2><span class="muted small">oldest first · every event links to its record</span></div><div class="bd">
      <?= view('Crm\Views\partials\wa_timeline', ['events' => $t['workflow']]) ?></div></div>
  </div>
  <div class="grid" style="align-content:start">
    <?php $ordersVal = array_sum(array_map(static fn ($o) => $o['status'] === 'cancelled' ? 0 : (float) $o['grand_total'], $t['orders'])); ?>
    <div class="card kpi"><div class="l">Enquiries</div><div class="v"><?= count($t['enquiries']) ?></div><div class="s"><?= count(array_filter($t['enquiries'], static fn ($x) => $x['status'] === 'won')) ?> won</div></div>
    <div class="card kpi"><div class="l">Quotations</div><div class="v"><?= count($t['quotations']) ?></div></div>
    <div class="card kpi good"><div class="l">Order value</div><div class="v"><?= crm_money0($ordersVal) ?></div><div class="s"><?= count($t['orders']) ?> order(s)</div></div>
  </div>
</div>

<?php elseif ($tab === 'enquiries'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Enquiry' => static fn ($r) => '<a href="' . site_url('crm/enquiries/' . (int) $r['id']) . '"><strong>' . esc($r['enquiry_no']) . '</strong></a><div class="muted small">' . esc($r['project_name'] ?? '') . '</div>',
    'Status' => static fn ($r) => crm_badge($r['status']), 'Priority' => static fn ($r) => crm_badge($r['priority']), 'Owner' => static fn ($r) => $e($r['owner_name']),
    'Budget' => static fn ($r) => $r['est_budget'] ? crm_money0($r['est_budget']) : '—', 'Created' => static fn ($r) => esc(crm_dt($r['created_at'])),
], $t['enquiries'], 'No enquiries visible to you.') ?></div></div>

<?php elseif ($tab === 'salespeople'): ?>
<div class="card"><div class="hd"><h2>Assignment &amp; ownership history</h2></div><div class="bd flush"><?= $tbl([
    'When' => static fn ($r) => esc(crm_dt($r['created_at'])), 'Enquiry' => $enqLink, 'From' => static fn ($r) => $e($r['from_name']), 'To' => static fn ($r) => '<strong>' . $e($r['to_name']) . '</strong>',
    'Method' => static fn ($r) => esc(crm_label($r['method'] ?? '')), 'Reason' => static fn ($r) => $e($r['reason'] ?? null),
], $t['salespeople'], 'No assignments yet.') ?></div></div>

<?php elseif ($tab === 'followups'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Due' => static fn ($r) => esc(crm_dt($r['due_at'])), 'Enquiry' => $enqLink, 'Type' => static fn ($r) => esc(crm_label($r['activity_type'])), 'Status' => static fn ($r) => crm_badge($r['status']),
    'Assigned to' => static fn ($r) => $e($r['user_name']), 'Outcome' => static fn ($r) => $e($r['outcome']), 'Next action' => static fn ($r) => $e($r['next_action']), 'Completed' => static fn ($r) => esc(crm_dt($r['completed_at'])),
], $t['followups']) ?></div></div>

<?php elseif ($tab === 'activities'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'When' => static fn ($r) => esc(crm_dt($r['occurred_at'])), 'Type' => static fn ($r) => esc(crm_label($r['activity_type'])), 'Summary' => static fn ($r) => esc($r['summary']) . ($r['details'] ? '<div class="muted small">' . esc($r['details']) . '</div>' : ''),
    'Outcome' => static fn ($r) => $e($r['outcome']), 'Enquiry' => $enqLink, 'By' => static fn ($r) => $e($r['user_name']),
], $t['activities']) ?></div></div>

<?php elseif ($tab === 'visits'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Visit' => static fn ($r) => crm_can('visits.manage') ? '<a href="' . site_url('crm/visits/' . (int) $r['id']) . '"><strong>' . esc($r['visit_no']) . '</strong></a>' : esc($r['visit_no']),
    'Scheduled' => static fn ($r) => esc(crm_dt($r['scheduled_at'])), 'Status' => static fn ($r) => crm_badge($r['status']), 'Enquiry' => $enqLink, 'Assigned to' => static fn ($r) => $e($r['user_name']),
    'Observations' => static fn ($r) => $e($r['observations']), 'Completed' => static fn ($r) => esc(crm_dt($r['completed_at'])),
], $t['visits']) ?></div></div>

<?php elseif ($tab === 'measurements'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Visit' => static fn ($r) => esc($r['visit_no']), 'Version' => static fn ($r) => 'v' . (int) $r['version_no'] . ($r['is_current'] ? ' <span class="badge badge-green">current</span>' : ' <span class="badge badge-grey">superseded</span>'),
    'Recorded' => static fn ($r) => esc(crm_dt($r['created_at'])),
    'Measurements' => static function ($r) {
        $d = json_decode((string) ($r['data'] ?? ''), true);
        if (! is_array($d)) { return esc((string) ($r['data'] ?? '—')); }
        $o = '';
        foreach ($d as $k => $v) { $o .= '<div class="small"><span class="muted">' . esc(is_int($k) ? '#' . ($k + 1) : crm_label((string) $k)) . ':</span> ' . esc(is_array($v) ? implode(' × ', array_map('strval', $v)) : (string) $v) . '</div>'; }

        return $o;
    },
], $t['measurements'], 'No measurements recorded.') ?></div></div>

<?php elseif ($tab === 'quotations'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Quotation' => static fn ($r) => '<a href="' . site_url('crm/quotations/' . (int) $r['id']) . '"><strong>' . esc($r['quotation_no']) . '</strong></a>', 'Enquiry' => $enqLink,
    'Version' => static fn ($r) => 'v' . (int) $r['version_no'] . ' <span class="muted small">of ' . (int) $r['versions'] . '</span>', 'Status' => static fn ($r) => crm_badge($r['status']),
    'Grand total' => static fn ($r) => crm_money($r['grand_total']), 'Valid until' => static fn ($r) => esc(crm_date($r['valid_until'])), 'Created' => static fn ($r) => esc(crm_dt($r['created_at'])),
], $t['quotations']) ?></div></div>

<?php elseif ($tab === 'negotiations'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Negotiation' => static fn ($r) => '<a href="' . site_url('crm/negotiations/' . (int) $r['id']) . '"><strong>' . esc($r['negotiation_no']) . '</strong></a>', 'Quotation' => static fn ($r) => $e($r['quotation_no']),
    'Requested' => static fn ($r) => esc(number_format((float) $r['requested_discount_pct'], 2)) . '%', 'Proposed price' => static fn ($r) => crm_money($r['proposed_price']), 'Status' => static fn ($r) => crm_badge($r['status']),
    'Level' => static fn ($r) => esc(crm_label($r['required_level'] ?? '')), 'By' => static fn ($r) => $e($r['requester']), 'Created' => static fn ($r) => esc(crm_dt($r['created_at'])),
], $t['negotiations']) ?></div></div>

<?php elseif ($tab === 'approvals'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Negotiation' => static fn ($r) => '<a href="' . site_url('crm/negotiations/' . (int) $r['negotiation_id']) . '">' . esc($r['negotiation_no']) . '</a>', 'Level' => static fn ($r) => (int) $r['level'] . ' · ' . esc(crm_label($r['required_role'])),
    'Requested %' => static fn ($r) => esc(number_format((float) $r['requested_discount_pct'], 2)) . '%', 'Decision' => static fn ($r) => crm_badge($r['decision']), 'Approver' => static fn ($r) => $e($r['approver_name']),
    'Remarks' => static fn ($r) => $e($r['remarks']), 'Decided' => static fn ($r) => esc(crm_dt($r['decided_at'])),
], $t['approvals'], 'No discount approvals.') ?></div></div>

<?php elseif ($tab === 'emails'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'When' => static fn ($r) => esc(crm_dt($r['created_at'])), 'To' => static fn ($r) => esc($r['to_email']), 'Subject' => static fn ($r) => crm_can('emails.view') ? '<a href="' . site_url('crm/emails/' . (int) $r['id']) . '">' . esc($r['subject']) . '</a>' : esc($r['subject']),
    'Status' => static fn ($r) => crm_badge($r['status']), 'Provider' => static fn ($r) => $e($r['provider']), 'Attempts' => static fn ($r) => (int) $r['attempts'], 'Failure' => static fn ($r) => $e($r['failure_reason']),
], $t['emails'], 'No emails logged.') ?></div></div>

<?php elseif ($tab === 'orders'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Order' => static fn ($r) => '<a href="' . site_url('crm/orders/' . (int) $r['id']) . '"><strong>' . esc($r['order_no']) . '</strong></a>', 'Quotation' => static fn ($r) => $e($r['quotation_no']), 'Status' => static fn ($r) => crm_badge($r['status']),
    'Value' => static fn ($r) => crm_money($r['grand_total']), 'Confirmed' => static fn ($r) => esc(crm_date($r['confirmed_at'] ?? null)), 'Expected delivery' => static fn ($r) => esc(crm_date($r['expected_delivery'] ?? null)),
], $t['orders'], 'No sales orders yet.') ?></div></div>

<?php elseif ($tab === 'documents'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'Document' => static fn ($r) => '<a href="' . site_url('crm/documents/' . (int) $r['id']) . '">' . esc($r['original_name']) . '</a>', 'Attached to' => static fn ($r) => esc(crm_label($r['entity_type'])) . ' #' . (int) $r['entity_id'],
    'Type' => static fn ($r) => esc($r['mime']), 'Size' => static fn ($r) => esc(number_format(((int) $r['size_bytes']) / 1024, 1)) . ' KB', 'Uploaded' => static fn ($r) => esc(crm_dt($r['created_at'])),
], $t['documents'], 'No documents uploaded.') ?></div></div>

<?php elseif ($tab === 'workflow'): ?>
<div class="card"><div class="bd flush"><?= $tbl([
    'When' => static fn ($r) => esc(crm_dt($r['created_at'])), 'Event' => static fn ($r) => '<strong>' . esc($r['label']) . '</strong>' . ($r['entity_ref'] ? '<div class="small">' . ($r['link'] ? '<a href="' . site_url($r['link']) . '">' . esc($r['entity_ref']) . '</a>' : esc($r['entity_ref'])) . '</div>' : ''),
    'User' => static fn ($r) => esc($r['user_name']), 'Prev owner' => static fn ($r) => $e($r['prev_owner']), 'New owner' => static fn ($r) => $e($r['new_owner']),
    'Prev status' => static fn ($r) => $r['prev_status'] ? crm_badge($r['prev_status']) : '—', 'New status' => static fn ($r) => $r['new_status'] ? crm_badge($r['new_status']) : '—', 'Remarks' => static fn ($r) => $e($r['reason']),
], array_reverse($t['workflow'])) ?></div></div>

<?php elseif ($tab === 'audit'): ?>
<?php if (! crm_can('audit.view')): ?><div class="card"><div class="empty"><strong>Audit history is restricted</strong>Ask an administrator if you need access.</div></div>
<?php else: ?><div class="card"><div class="bd flush"><?= $tbl([
    'When' => static fn ($r) => esc(crm_dt($r['created_at'])), 'Event' => static fn ($r) => esc($r['event_type']), 'User' => static fn ($r) => $e($r['user_id'] ? \Crm\Services\HistoryService::userNames()[(int) $r['user_id']] ?? ('User #' . $r['user_id']) : 'System'),
    'Source' => static fn ($r) => esc($r['source']), 'Reason' => static fn ($r) => $e($r['reason']), 'Change' => static fn ($r) => '<span class="small muted">' . esc(mb_strimwidth((string) ($r['new_state'] ?? ''), 0, 160, '…')) . '</span>',
], $t['audit'], 'No audit events.') ?></div></div><?php endif; ?>
<?php endif; ?>

<?php if (crm_can('customers.deactivate')): ?>
<div class="modal" id="status-modal" role="dialog" aria-modal="true" aria-labelledby="sm-t"><div class="box">
  <form method="post" action="<?= $base ?>/status" data-validate><?= csrf_field() ?>
    <div class="hd"><h3 id="sm-t">Change customer status</h3><button type="button" class="btn alt sm" data-close aria-label="Close">×</button></div>
    <div class="bd form">
      <div class="full"><label for="sm-s">New status</label><select id="sm-s" name="status"><?php foreach (['active', 'inactive', 'archived'] as $s): if ($s === $c['status']) { continue; } ?><option value="<?= $s ?>"><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select>
        <div class="hint">Customers with sales history are never deleted; they are deactivated or archived and stay visible in history.</div></div>
      <div class="full"><label for="sm-r">Reason (required unless reactivating)</label><textarea id="sm-r" name="reason"></textarea></div>
    </div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn" type="submit">Update status</button></div>
  </form></div></div>
<?php endif; ?>
