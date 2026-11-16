<?php
/** @var string $tab @var array $rows @var array $counts @var ?int $assignee @var array $users @var bool $showPicker @var string $pager @var string $now */
$labels = ['today' => "Today", 'upcoming' => 'Upcoming', 'overdue' => 'Overdue', 'completed' => 'Completed'];
$empty  = ['today' => 'Nothing due today.', 'upcoming' => 'No upcoming follow-ups scheduled.', 'overdue' => 'No overdue follow-ups — well done.', 'completed' => 'No completed follow-ups yet.'];
?>
<div class="pagehead">
  <h1>Follow-ups</h1>
  <?php if ($showPicker): ?>
  <div class="gap">
    <form method="get" class="gap"><input type="hidden" name="tab" value="<?= esc($tab) ?>"><label class="small muted" for="uid">Assigned to</label>
      <select id="uid" name="user_id" style="width:auto" onchange="this.form.submit()"><option value="">Everyone</option>
        <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= crm_sel($assignee, $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select>
      <noscript><button class="btn sm" type="submit">Apply</button></noscript></form>
  </div>
  <?php endif; ?>
</div>

<nav class="tabs" aria-label="Follow-up lists">
  <?php foreach ($labels as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= site_url('crm/followups?' . http_build_query(array_filter(['tab' => $k, 'user_id' => $assignee]))) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($l) ?>
    <span class="badge <?= $k === 'overdue' && $counts[$k] ? 'badge-red' : 'badge-grey' ?>"><?= (int) $counts[$k] ?></span></a><?php endforeach; ?>
</nav>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong><?= esc($empty[$tab]) ?></strong></div>
<?php else: ?><div class="tablewrap"><table class="t">
  <thead><tr><th>Due</th><th>Customer / enquiry</th><th>Type</th><th>Assigned to</th><th>Status</th><th><?= $tab === 'completed' ? 'Outcome' : 'Planned action' ?></th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $f): $late = in_array($f['status'], ['pending', 'missed'], true) && $f['due_at'] < $now; ?>
    <tr>
      <td class="nowrap"><?= crm_dt($f['due_at']) ?><?php if ($late): ?><div><span class="badge badge-red">Overdue</span></div><?php endif; ?><?php if ($f['escalated_at']): ?><div><span class="badge badge-red" title="Escalated to manager on <?= esc(crm_dt($f['escalated_at'])) ?>">Escalated</span></div><?php endif; ?></td>
      <td><strong><?= esc($f['customer_name']) ?></strong><div class="muted small"><?php if ($f['enquiry_id']): ?><a href="<?= site_url('crm/enquiries/' . (int) $f['enquiry_id']) ?>"><?= esc($f['enquiry_no']) ?></a> · <?= esc($f['project_name'] ?: '') ?><?php else: ?>Customer-level<?php endif; ?> · <?= esc($f['customer_mobile']) ?></div></td>
      <td><?= esc(crm_label($f['activity_type'])) ?></td><td><?= esc($f['user_name']) ?></td><td><?= crm_badge($f['status']) ?></td>
      <td><?php if ($tab === 'completed'): ?><?= esc($f['outcome'] ?: '—') ?><div class="muted small"><?= $f['completed_at'] ? 'Done ' . esc(crm_dt($f['completed_at'])) : '' ?></div>
          <?php else: ?><?= esc($f['next_action'] ?: '—') ?><div class="muted small"><?= esc(mb_strimwidth((string) $f['remarks'], 0, 90, '…')) ?></div><?php endif; ?></td>
      <td class="nowrap"><?php if (in_array($f['status'], ['pending', 'missed'], true)): ?>
        <button class="btn teal sm" type="button" data-modal="m-complete" data-action="<?= site_url('crm/followups/' . (int) $f['id'] . '/complete') ?>">Complete</button>
        <button class="btn alt sm" type="button" data-modal="m-resched" data-action="<?= site_url('crm/followups/' . (int) $f['id'] . '/reschedule') ?>">Reschedule</button>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; ?></tbody></table></div><?= $pager ?><?php endif; ?>
</div></div>

<div class="modal" id="m-complete" role="dialog" aria-modal="true" aria-label="Complete follow-up"><div class="box">
  <div class="hd"><h3>Complete follow-up</h3><button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button></div>
  <form method="post" action="" data-validate><?= csrf_field() ?>
    <div class="bd"><div class="form" style="grid-template-columns:1fr">
      <div><label class="req" for="c-out">Outcome</label><input id="c-out" name="outcome" required maxlength="255" placeholder="e.g. Customer will decide this week"></div>
      <div><label for="c-rem">Remarks</label><textarea id="c-rem" name="remarks"></textarea></div>
      <div><label for="c-next">Next follow-up (optional)</label><input id="c-next" type="datetime-local" name="next_due_at" min="<?= date('Y-m-d\TH:i') ?>"></div>
      <div><label for="c-act">Next action</label><input id="c-act" name="next_action" maxlength="255"></div>
      <p class="hint">The follow-up stays in the history as completed and an activity is logged.</p>
    </div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Mark completed</button></div>
  </form></div></div>

<div class="modal" id="m-resched" role="dialog" aria-modal="true" aria-label="Reschedule follow-up"><div class="box">
  <div class="hd"><h3>Reschedule follow-up</h3><button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button></div>
  <form method="post" action="" data-validate><?= csrf_field() ?>
    <div class="bd"><div class="form" style="grid-template-columns:1fr">
      <div><label class="req" for="r-due">New date &amp; time</label><input id="r-due" type="datetime-local" name="new_due_at" required min="<?= date('Y-m-d\TH:i') ?>"></div>
      <div><label class="req" for="r-why">Reason</label><input id="r-why" name="reason" required maxlength="255"></div>
    </div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Reschedule</button></div>
  </form></div></div>
