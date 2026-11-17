<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $filters @var array $metrics @var list<array> $users @var list<array> $organisations @var string $viewMode */
$types = ['customer_visit', 'phone_call', 'architect_meeting', 'site_measurement', 'quotation_presentation', 'client_demo', 'follow_up', 'payment_collection'];
$statuses = ['scheduled', 'completed', 'cancelled', 'rescheduled', 'missed'];
?>
<div class="pagehead">
  <div>
    <h1>Sales Activities &amp; Interactions <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">Dedicated sales activity ledger: customer visits, architect meetings, phone calls &amp; measurement surveys.</div>
  </div>
  <div class="gap">
    <?php if (crm_can('followups.manage')): ?>
      <a class="btn teal" href="<?= site_url('crm/activities/new') ?>">+ Record Activity</a>
    <?php endif; ?>
  </div>
</div>

<!-- Operational Metric Cards -->
<div class="grid g4" style="margin-bottom:18px">
  <div class="card kpi good">
    <div class="l">Completed (This Month)</div>
    <div class="v"><?= (int) ($metrics['completed_this_month'] ?? 0) ?></div>
    <div class="s">Visits, calls &amp; meetings</div>
  </div>
  <div class="card kpi">
    <div class="l">Scheduled Today</div>
    <div class="v"><?= (int) ($metrics['today'] ?? 0) ?></div>
    <div class="s">Active appointments</div>
  </div>
  <div class="card kpi">
    <div class="l">Upcoming Activities</div>
    <div class="v"><?= (int) ($metrics['upcoming'] ?? 0) ?></div>
    <div class="s">Next 7 days</div>
  </div>
  <div class="card kpi <?= ! empty($metrics['overdue']) ? 'bad' : '' ?>">
    <div class="l">Overdue Action Items</div>
    <div class="v" style="<?= ! empty($metrics['overdue']) ? 'color:#B91C1C' : '' ?>"><?= (int) ($metrics['overdue'] ?? 0) ?></div>
    <div class="s"><?= ! empty($metrics['overdue']) ? 'Requires immediate action' : 'All up to date' ?></div>
  </div>
</div>

<form class="filters card" style="padding:14px;margin-bottom:18px" method="get" action="<?= site_url('crm/activities') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Subject, contact, notes..."></div>
  <div class="f"><label for="type">Activity Type</label><select id="type" name="activity_type"><option value="">All Types</option><?php foreach ($types as $t): ?><option value="<?= $t ?>"<?= crm_sel($filters['activity_type'] ?? '', $t) ?>><?= esc(crm_label($t)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="status">Status</label><select id="status" name="status"><option value="">All Statuses</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>"<?= crm_sel($filters['status'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="user">Assigned Rep</label><select id="user" name="user_id"><option value="">All Reps</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= crm_sel($filters['user_id'] ?? '', $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="org">Organisation</label><select id="org" name="organisation_id"><option value="">All Organisations</option><?php foreach ($organisations as $o): ?><option value="<?= (int) $o['id'] ?>"<?= crm_sel($filters['organisation_id'] ?? '', $o['id']) ?>><?= esc($o['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/activities') ?>">Reset</a></div>
</form>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No sales activities match</strong>Try clearing filters or schedule a new customer interaction.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Activity #</th>
          <th>Type</th>
          <th>Subject / Details</th>
          <th>Account / Contact</th>
          <th>Scheduled Date</th>
          <th>Assigned Rep</th>
          <th>Status</th>
          <th>Outcome / Lead Link</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <a href="<?= site_url('crm/activities/' . $r['id']) ?>"><strong><?= esc($r['activity_no']) ?></strong></a>
          </td>
          <td>
            <span class="badge badge-<?= match($r['activity_type']) { 'customer_visit', 'architect_meeting' => 'blue', 'quotation_presentation' => 'green', 'phone_call' => 'grey', default => 'amber' } ?>">
              <?= esc(crm_label($r['activity_type'])) ?>
            </span>
          </td>
          <td>
            <strong><?= esc($r['subject'] ?? ($r['discussion_summary'] ?: ($r['product_interest'] ?: 'Activity #' . $r['activity_no']))) ?></strong>
            <?php if (! empty($r['location'])): ?><div class="muted small">📍 <?= esc($r['location']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if (! empty($r['organisation_name'])): ?>
              <a href="<?= site_url('crm/organisations/' . $r['organisation_id']) ?>"><strong><?= esc($r['organisation_name']) ?></strong></a>
            <?php endif; ?>
            <div class="muted small"><?= esc($r['contact_name'] ?? '—') ?></div>
          </td>
          <td class="nowrap">
            <div><?= esc(crm_date($r['activity_date'])) ?></div>
            <?php if (! empty($r['scheduled_at'])): ?>
              <div class="muted small"><?= esc(crm_dt($r['scheduled_at'])) ?></div>
            <?php elseif (! empty($r['start_time'])): ?>
              <div class="muted small"><?= esc(date('h:i A', strtotime($r['start_time']))) ?></div>
            <?php endif; ?>
          </td>
          <td><?= esc($r['assigned_user_name'] ?? '—') ?></td>
          <td><?= crm_badge($r['status']) ?></td>
          <td>
            <?php $convId = $r['converted_to_enquiry_id'] ?? ($r['converted_to_lead_id'] ?? null); ?>
            <?php if (! empty($convId)): ?>
              <a href="<?= site_url('crm/enquiries/' . $convId) ?>" class="badge badge-green" title="View Opportunity">
                ✓ Converted (<?= esc($r['converted_enquiry_no'] ?? ($r['converted_lead_no'] ?? '#' . $convId)) ?>)
              </a>
            <?php elseif (! empty($r['outcome'])): ?>
              <span class="muted small">Outcome: <strong><?= esc(crm_label($r['outcome'])) ?></strong></span>
            <?php elseif ($r['status'] === 'scheduled'): ?>
              <span class="muted small">Pending Completion</span>
            <?php else: ?>
              <span class="muted small">—</span>
            <?php endif; ?>
          </td>
          <td class="nowrap">
            <a class="btn sm alt" href="<?= site_url('crm/activities/' . $r['id']) ?>">View</a>
            <?php if ($r['status'] === 'scheduled'): ?>
              <a class="btn sm teal" href="<?= site_url('crm/activities/' . $r['id'] . '#complete') ?>">Complete</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $perPage, 'page' => $page]) ?>
<?php endif; ?>
</div></div>
