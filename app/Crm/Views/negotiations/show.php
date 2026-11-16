<?php
/**
 * @var array $n @var array $q @var array $c @var array $e @var array $orig @var array $cur @var ?array $result @var list<array> $approvals @var list<array> $rounds
 * @var ?array $editor @var bool $canRevise @var bool $canDecide @var string $requester @var string $limit @var string $estimate
 */
$qid = (int) $q['id'];
$isMine = (int) $n['requested_by'] === (int) ($user['id'] ?? 0);
?>
<div class="pagehead">
  <h1><?= esc($n['negotiation_no']) ?> <?= crm_badge($n['status']) ?></h1>
  <a class="btn alt" href="<?= site_url('crm/quotations/' . $qid) ?>">Quotation <?= esc($q['quotation_no']) ?></a>
  <a class="btn alt" href="<?= site_url('crm/negotiations') ?>">All negotiations</a>
</div>

<div class="grid g4 mb">
  <div class="card kpi"><div class="l">Original quotation</div><div class="v"><?= crm_money0($orig['grand_total']) ?></div><div class="s"><a href="<?= site_url('crm/quotations/' . $qid . '?v=' . (int) $orig['version_no']) ?>"><?= esc($q['quotation_no']) ?> v<?= (int) $orig['version_no'] ?></a> · <?= (int) $orig['is_locked'] ? 'sent ' . crm_date($orig['sent_at']) : 'draft' ?></div></div>
  <div class="card kpi"><div class="l">Customer expects</div><div class="v"><?= $n['customer_expected_amount'] !== null ? crm_money0($n['customer_expected_amount']) : '—' ?></div><div class="s">Reason: <?= esc((string) $n['reason']) ?></div></div>
  <div class="card kpi"><div class="l">Requested discount</div><div class="v"><?= esc(number_format((float) $n['requested_discount_pct'], 2)) ?>%</div><div class="s"><?= crm_money($n['requested_discount_amount']) ?> off the subtotal</div></div>
  <div class="card kpi good"><div class="l">Proposed price</div><div class="v"><?= crm_money0($n['proposed_price']) ?></div><div class="s">Authority needed: <strong><?= crm_label($n['required_level']) ?></strong> · by <?= esc($requester) ?></div></div>
</div>

<div class="grid g-side mb">
  <div class="card"><div class="hd"><h2>Approval trail</h2></div><div class="bd">
    <ul class="tl">
      <li><div class="when"><?= crm_dt($n['created_at']) ?></div><div class="what">Request raised by <?= esc($requester) ?></div><div class="meta"><?= esc(number_format((float) $n['requested_discount_pct'], 2)) ?>% ask → <?= crm_money($n['proposed_price']) ?> · <?= esc((string) $n['reason']) ?></div></li>
      <?php foreach ($approvals as $a): ?>
        <li><div class="when"><?= $a['decided_at'] ? crm_dt($a['decided_at']) : 'Awaiting decision' ?></div>
          <div class="what">Level <?= (int) $a['level'] ?> · <?= crm_label($a['required_role']) ?> — <?= crm_badge($a['decision']) ?></div>
          <div class="meta"><?= $a['approver_name'] ? 'By ' . esc($a['approver_name']) : 'Not decided yet' ?><?= $a['remarks'] ? ' · “' . esc($a['remarks']) . '”' : '' ?></div></li>
      <?php endforeach; ?>
      <?php if ($result): ?><li><div class="when"><?= crm_dt($result['created_at']) ?></div><div class="what">Revised quotation v<?= (int) $result['version_no'] ?> produced — <?= crm_money($result['grand_total']) ?></div>
        <div class="meta">Final discount <?= crm_money($n['final_discount_amount']) ?> · <a href="<?= site_url('crm/quotations/' . $qid . '?v=' . (int) $result['version_no']) ?>">open v<?= (int) $result['version_no'] ?></a> · <a href="<?= site_url('crm/quotations/' . $qid . '/compare?a=' . (int) $orig['version_no'] . '&b=' . (int) $result['version_no']) ?>">compare with v<?= (int) $orig['version_no'] ?></a></div></li><?php endif; ?>
    </ul>
  </div></div>

  <div class="card"><div class="hd"><h2>Decision</h2></div><div class="bd">
    <?php if ($canDecide): ?>
      <p class="small muted">Your approval limit is <strong><?= esc(number_format((float) $limit, 1)) ?>%</strong>. Requests above it must be escalated.</p>
      <form method="post" action="<?= site_url('crm/negotiations/' . (int) $n['id'] . '/decide') ?>" data-validate><?= csrf_field() ?>
        <label for="rem" style="font-weight:600;font-size:13px">Remarks <span class="muted">(required unless approving)</span></label>
        <textarea id="rem" name="remarks"><?= esc((string) old('remarks')) ?></textarea><?= crm_err('remarks') ?>
        <div class="gap mt">
          <button class="btn teal" name="decision" value="approve" type="submit">Approve</button>
          <button class="btn danger" name="decision" value="reject" type="submit" data-confirm="Reject this discount request?">Reject</button>
          <button class="btn alt" name="decision" value="return" type="submit">Return for revision</button>
          <?php if ($n['required_level'] !== 'admin'): ?><button class="btn alt" name="decision" value="escalate" type="submit" data-confirm="Escalate to an administrator?">Escalate</button><?php endif; ?>
        </div>
      </form>
    <?php elseif (in_array($n['status'], ['pending', 'escalated'], true)): ?>
      <p class="muted"><?= $isMine && \Crm\Services\Settings::get('discount.allow_self_approval') !== '1' ? 'You raised this request, so you cannot decide it. ' : '' ?>Waiting for a <?= esc($n['required_level']) ?>-level approver.</p>
    <?php elseif ($n['status'] === 'returned' && $isMine): ?>
      <div class="flash info" style="margin-bottom:12px">
        <strong>Returned for Revision by Senior:</strong>
        <p style="margin:4px 0 0">Remarks: <em>“<?= esc(end($approvals)['remarks'] ?? 'Please adjust figures.') ?>”</em></p>
      </div>
      <p>Resubmit with a revised ask:</p>
      <form method="post" action="<?= site_url('crm/negotiations/' . (int) $n['id'] . '/resubmit') ?>" data-validate><?= csrf_field() ?>
        <div class="form"><div><label class="req" for="rp">New discount %</label><input id="rp" type="number" step="0.001" min="0.001" max="100" name="requested_discount_pct" required></div>
        <div class="full"><label class="req" for="rr">Reason</label><textarea id="rr" name="reason" required></textarea></div></div>
        <button class="btn teal mt" type="submit">Resubmit</button></form>
    <?php elseif ($n['status'] === 'approved'): ?>
      <div class="flash ok" style="margin-bottom:14px">
        <strong style="color:var(--green);display:block;margin-bottom:4px;font-size:14px">✅ Price Concession Approved by Senior / Manager</strong>
        <span>Approved discount ceiling: <strong><?= esc(number_format((float) $n['requested_discount_pct'], 2)) ?>%</strong> (Proposed final price: <?= crm_money($n['proposed_price']) ?>).</span>
      </div>
      <p style="font-size:13px">Prepare the revised quotation below with updated sizes, quantities, or prices. Once saved, it creates a new official quotation version ready to send to the client with an A4 PDF.</p>
      <?php if ($result): ?>
        <div class="flash info" style="margin-top:12px">
          <strong>Revised Quotation v<?= (int) $result['version_no'] ?> Generated:</strong> <?= crm_money($result['grand_total']) ?>
          <div class="gap mt" style="margin-top:8px">
            <a href="<?= site_url('crm/quotations/' . $qid . '/pdf/' . (int) $result['version_no']) ?>" target="_blank" class="btn alt sm">📄 View A4 PDF</a>
            <a href="<?= site_url('crm/quotations/' . $qid . '/pdf/' . (int) $result['version_no'] . '?download=1') ?>" class="btn alt sm">⬇️ Download A4 PDF</a>
            <a href="<?= site_url('crm/quotations/' . $qid . '?v=' . (int) $result['version_no']) ?>" class="btn teal sm">🚀 Open Version &amp; Send to Client</a>
          </div>
        </div>
      <?php endif; ?>
    <?php elseif ($n['status'] === 'rejected'): ?>
      <?php $lastApproval = end($approvals) ?: null; ?>
      <div class="flash danger" style="margin-bottom:14px">
        <strong style="color:var(--red);display:block;margin-bottom:4px;font-size:14px">❌ Discount Request Rejected by Senior / Manager</strong>
        <p style="margin:4px 0 0">Approver Remarks: <em>“<?= esc($lastApproval['remarks'] ?? 'Requested discount not approved at this level.') ?>”</em></p>
      </div>
      <p style="font-size:13px">As per workflow, you can adjust product sizes, quantities, specifications, or explore alternative materials to re-cost the project and generate a new quotation version for the customer:</p>
      <div class="gap mt">
        <a href="<?= site_url('crm/quotations/' . $qid . '/revise') ?>" class="btn teal">✏️ Create Revised Cost / Quotation (New Version)</a>
        <a href="<?= site_url('crm/quotations/' . $qid) ?>" class="btn alt">Quotation Details</a>
        <a href="<?= site_url('crm/followups') ?>" class="btn alt">Daily Follow-ups</a>
      </div>
    <?php else: ?>
      <p class="muted">This request is <?= crm_label($n['status']) ?>.</p>
    <?php endif; ?>
    <p class="small muted mt">Customer <a href="<?= site_url('crm/customers/' . (int) $c['id']) ?>"><?= esc($c['name']) ?></a> · enquiry <a href="<?= site_url('crm/enquiries/' . (int) $e['id']) ?>"><?= esc($e['enquiry_no']) ?></a></p>
  </div></div>
</div>

<div class="card mb"><div class="hd"><h2>Negotiation rounds on <?= esc($q['quotation_no']) ?></h2><span class="small muted">Each round links to the quotation version it started from and the version it produced.</span></div>
  <div class="bd flush tablewrap"><table class="t"><thead><tr><th>Round</th><th>Status</th><th class="num">Ask</th><th class="num">Proposed price</th><th>From version</th><th>Produced version</th><th>Raised</th></tr></thead><tbody>
  <?php foreach ($rounds as $i => $r): ?>
    <tr<?= (int) $r['id'] === (int) $n['id'] ? ' style="background:var(--teal-l)"' : '' ?>><td><a href="<?= site_url('crm/negotiations/' . (int) $r['id']) ?>"><?= esc($r['negotiation_no']) ?></a> <span class="muted small">#<?= $i + 1 ?><?= $r['resubmission_of_id'] ? ' (resubmission)' : '' ?></span></td>
      <td><?= crm_badge($r['status']) ?></td><td class="num"><?= esc(number_format((float) $r['requested_discount_pct'], 2)) ?>%</td><td class="num"><?= crm_money($r['proposed_price']) ?></td>
      <td><a href="<?= site_url('crm/quotations/' . $qid . '?v=' . (int) $r['from_no']) ?>">v<?= (int) $r['from_no'] ?></a></td>
      <td><?php if ($r['to_no']): ?><a href="<?= site_url('crm/quotations/' . $qid . '?v=' . (int) $r['to_no']) ?>">v<?= (int) $r['to_no'] ?></a> · <a href="<?= site_url('crm/quotations/' . $qid . '/compare?a=' . (int) $r['from_no'] . '&b=' . (int) $r['to_no']) ?>">compare</a><?php else: ?>—<?php endif; ?></td>
      <td class="nowrap"><?= crm_dt($r['created_at']) ?> · <?= esc($r['requester']) ?></td></tr>
  <?php endforeach; ?></tbody></table></div></div>

<?php if ($result && $result['changes']): ?>
<div class="card mb"><div class="hd"><h2>Tracked changes made in this round (v<?= (int) $orig['version_no'] ?> → v<?= (int) $result['version_no'] ?>)</h2></div><div class="bd"><?= view('Crm\Views\quotations\_changes', ['changes' => $result['changes']]) ?></div></div>
<?php endif; ?>

<?php if ($editor): ?>
<div class="card mb"><div class="hd"><h2>Prepare revised quotation</h2><span class="small muted">Edit products, variants, sizes, prices or the final total freely — the previous version stays as it is.</span></div></div>
<?= view('Crm\Views\quotations\_editor', $editor) ?>
<?php elseif ($canRevise && ! in_array($n['status'], ['applied', 'withdrawn'], true)): ?>
<div class="card mb"><div class="bd gap"><span>Need to change products, sizes or prices before this is decided?</span><a class="btn alt" href="<?= site_url('crm/quotations/' . $qid . '/revise') ?>">Revise within my own authority</a><span class="small muted">Changes beyond your discount limit are blocked until approved.</span></div></div>
<?php endif; ?>
