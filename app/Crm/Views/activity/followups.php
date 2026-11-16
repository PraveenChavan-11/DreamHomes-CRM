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
  <thead><tr><th>Due</th><th>Customer / enquiry</th><th>Quotation &amp; Price Approval</th><th>Type</th><th>Assigned to</th><th>Status</th><th><?= $tab === 'completed' ? 'Outcome' : 'Planned action' ?></th><th style="text-align:right">Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $f): $late = in_array($f['status'], ['pending', 'missed'], true) && $f['due_at'] < $now; ?>
    <tr>
      <td class="nowrap"><?= crm_dt($f['due_at']) ?><?php if ($late): ?><div><span class="badge badge-red">Overdue</span></div><?php endif; ?><?php if ($f['escalated_at']): ?><div><span class="badge badge-red" title="Escalated to manager on <?= esc(crm_dt($f['escalated_at'])) ?>">Escalated</span></div><?php endif; ?></td>
      <td>
        <strong><?= esc($f['customer_name']) ?></strong>
        <div class="muted small">
          <?php if ($f['enquiry_id']): ?><a href="<?= site_url('crm/enquiries/' . (int) $f['enquiry_id']) ?>"><?= esc($f['enquiry_no']) ?></a> · <?= esc($f['project_name'] ?: 'Enquiry') ?><?php else: ?>Customer-level<?php endif; ?> · <?= esc($f['customer_mobile']) ?>
        </div>
      </td>
      <td>
        <?php if (! empty($f['quotation_id'])): ?>
          <div style="margin-bottom:3px">
            <a href="<?= site_url('crm/quotations/' . (int) $f['quotation_id']) ?>" style="font-weight:700;color:var(--th-primary);font-size:12.5px">📄 <?= esc($f['quotation_no']) ?></a>
            <?= crm_badge($f['quotation_status']) ?>
          </div>
          <?php if (! empty($f['negotiation_id'])): ?>
            <div>
              <a href="<?= site_url('crm/negotiations/' . (int) $f['negotiation_id']) ?>" class="badge <?= $f['negotiation_status'] === 'approved' ? 'badge-green' : ($f['negotiation_status'] === 'rejected' ? 'badge-red' : 'badge-amber') ?>" style="text-decoration:none">
                ⚖️ <?= esc($f['negotiation_no']) ?>: <?= crm_label($f['negotiation_status']) ?><?= (float) $f['requested_discount_pct'] > 0 ? ' (' . number_format((float) $f['requested_discount_pct'], 1) . '%)' : '' ?>
              </a>
            </div>
          <?php else: ?>
            <span class="muted small">No active discount request</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="muted small">—</span>
        <?php endif; ?>
      </td>
      <td><?= esc(crm_label($f['activity_type'])) ?></td>
      <td><?= esc($f['user_name']) ?></td>
      <td><?= crm_badge($f['status']) ?></td>
      <td><?php if ($tab === 'completed'): ?><?= esc($f['outcome'] ?: '—') ?><div class="muted small"><?= $f['completed_at'] ? 'Done ' . esc(crm_dt($f['completed_at'])) : '' ?></div>
          <?php else: ?><?= esc($f['next_action'] ?: '—') ?><div class="muted small"><?= esc(mb_strimwidth((string) $f['remarks'], 0, 90, '…')) ?></div><?php endif; ?></td>
      <td class="nowrap" style="text-align:right">
        <?php if (in_array($f['status'], ['pending', 'missed'], true)): ?>
          <?php if (! empty($f['negotiation_id']) && $f['negotiation_status'] === 'approved'): ?>
            <a href="<?= site_url('crm/negotiations/' . (int) $f['negotiation_id']) ?>" class="btn sm" style="background:#E8F5E9;color:#2E7D32;border-color:#A5D6A7;font-weight:600" title="Manager approved discount! Prepare revised quotation version">⚡ Create Revised Quote</a>
          <?php elseif (! empty($f['negotiation_id']) && $f['negotiation_status'] === 'rejected'): ?>
            <a href="<?= site_url('crm/quotations/' . (int) $f['quotation_id'] . '/revise') ?>" class="btn sm" style="background:#FFEBEE;color:#C62828;border-color:#EF9A9A;font-weight:600" title="Discount rejected. Revise product pricing, sizes or specs">✏️ Revise Cost / Quote</a>
          <?php elseif (! empty($f['quotation_id']) && in_array($f['quotation_status'], ['sent', 'negotiation'], true)): ?>
            <a href="<?= site_url('crm/quotations/' . (int) $f['quotation_id']) ?>" class="btn sm alt" title="View quotation to request price reduction">Negotiate Price</a>
          <?php endif; ?>
          <button class="btn teal sm" type="button" data-modal="m-complete" data-action="<?= site_url('crm/followups/' . (int) $f['id'] . '/complete') ?>" data-fill="quotation_id=<?= (int) ($f['quotation_id'] ?? 0) ?>">Complete</button>
          <button class="btn alt sm" type="button" data-modal="m-resched" data-action="<?= site_url('crm/followups/' . (int) $f['id'] . '/reschedule') ?>">Reschedule</button>
        <?php elseif (! empty($f['quotation_id'])): ?>
          <a href="<?= site_url('crm/quotations/' . (int) $f['quotation_id']) ?>" class="btn sm alt">View Quote</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div><?= $pager ?><?php endif; ?>
</div></div>

<div class="modal" id="m-complete" role="dialog" aria-modal="true" aria-label="Complete follow-up"><div class="box" style="max-width:580px">
  <div class="hd"><h3>Complete follow-up &amp; Log Activity</h3><button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button></div>
  <form method="post" action="" data-validate><?= csrf_field() ?>
    <div class="bd"><div class="form" style="grid-template-columns:1fr">
      <div><label class="req" for="c-out">Discussion Outcome</label><input id="c-out" name="outcome" required maxlength="255" placeholder="e.g. Customer requested price concession, final decision this Friday"></div>
      <div><label for="c-rem">Detailed Remarks / Scope</label><textarea id="c-rem" name="remarks" placeholder="Key talking points, client reactions, competitors mentioned..."></textarea></div>

      <div style="background:#F8FAFC;border:1px solid var(--line);border-radius:var(--r-sm);padding:12px;margin-top:4px">
        <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;cursor:pointer;color:var(--th-dark)">
          <input type="checkbox" id="c-has-neg" style="width:auto;min-height:0" onchange="document.getElementById('c-neg-box').style.display=this.checked?'block':'none'">
          <span>⚡ Client Wants Price Concession (Request Senior / Manager Approval)</span>
        </label>
        <div id="c-neg-box" style="display:none;margin-top:10px;padding-top:10px;border-top:1px solid var(--line-2)">
          <input type="hidden" name="quotation_id" id="c-qid" value="">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px">
            <div>
              <label style="font-size:12px;font-weight:600" for="c-neg-pct">Requested Discount %</label>
              <input type="number" step="0.001" min="0" max="100" id="c-neg-pct" name="requested_discount_pct" placeholder="e.g. 7.5">
            </div>
            <div>
              <label style="font-size:12px;font-weight:600" for="c-neg-exp">Or Customer Expected Total ₹</label>
              <input type="number" step="100" min="0" id="c-neg-exp" name="customer_expected_amount" placeholder="e.g. 500000">
            </div>
          </div>
          <div>
            <label style="font-size:12px;font-weight:600" for="c-neg-why">Negotiation Justification / Competitor Note</label>
            <input type="text" id="c-neg-why" name="negotiation_reason" placeholder="e.g. Client comparing with competitor quote, agreed to close if discounted">
          </div>
          <p class="hint" style="margin:6px 0 0">Sends approval alert to senior/manager. Once approved, you can create a revised quotation version.</p>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px">
        <div><label for="c-next">Next follow-up date</label><input id="c-next" type="datetime-local" name="next_due_at" min="<?= date('Y-m-d\TH:i') ?>"></div>
        <div><label for="c-act">Next action</label><input id="c-act" name="next_action" maxlength="255" placeholder="e.g. Send revised A4 quote"></div>
      </div>
    </div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Complete &amp; Submit</button></div>
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
