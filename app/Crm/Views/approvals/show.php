<?php
/** @var array $app */
$canDecide = crm_can('discounts.approve_manager', 'discounts.approve_admin') && ($app['status'] === 'pending' || $app['status'] === 'clarification_requested');
$isOwn = ((int) ($app['salesperson_id'] ?? 0) === (int) (crm_user()['id'] ?? 0));
if ($isOwn && ! crm_can('discounts.approve_admin')) {
    $canDecide = false; // Self-approval prevention
}
?>
<div class="pagehead">
  <div>
    <h1>
      Commercial Approval Request <?= esc($app['approval_no']) ?>
      <?= crm_badge($app['status']) ?>
      <span class="badge badge-blue">Level <?= (int) $app['approval_level'] ?> of <?= (int) $app['max_approval_level'] ?></span>
    </h1>
    <div class="muted small" style="font-weight:400">
      Quotation: <a href="<?= site_url('crm/quotations/' . $app['quotation_id']) ?>"><strong><?= esc($app['quotation_no']) ?> (v<?= (int) $app['version_no'] ?>)</strong></a>
      <?php if (! empty($app['org_name'])): ?> · Account: <a href="<?= site_url('crm/organisations/' . $app['organisation_id']) ?>"><strong><?= esc($app['org_name']) ?></strong></a><?php endif; ?>
      · Requested by: <strong><?= esc($app['salesperson_name']) ?></strong> on <?= esc(crm_dt($app['created_at'])) ?>
    </div>
  </div>
  <div class="gap">
    <a class="btn sm alt" target="_blank" rel="noopener" href="<?= site_url('crm/quotations/' . $app['quotation_id'] . '/pdf/' . (int) $app['version_no']) ?>">📄 View A4 Proposal PDF</a>
    <a class="btn alt" href="<?= site_url('crm/approvals') ?>">Back to Approval Queue</a>
  </div>
</div>

<?php if ($isOwn && $app['status'] === 'pending'): ?>
  <div class="card" style="margin-bottom:18px;background:#FEF3C7;border-color:#FDE68A;padding:14px 18px">
    <strong style="color:#92400E">ℹ️ Commercial Governance Notice:</strong>
    <span style="font-size:13.5px;color:#78350F">You are the author of this quotation. Under enterprise governance policy, approval must be decided by an independent Sales Manager or Commercial Head.</span>
  </div>
<?php endif; ?>

<!-- Side-by-Side Commercial Comparison -->
<div class="card" style="margin-bottom:18px">
  <div class="hd">
    <h2>1. Side-by-Side Commercial Comparison &amp; Financial Impact</h2>
    <span class="muted small">Catalogue standard benchmark vs proposed discounted price</span>
  </div>
  <div class="bd">
    <div class="grid g4" style="margin-bottom:12px">
      <div style="background:#F8FAFC;border:1px solid var(--line);border-radius:var(--r-sm);padding:14px 16px">
        <div class="muted small" style="font-weight:600;text-transform:uppercase">Standard Catalogue Price</div>
        <div style="font-size:22px;font-weight:800;color:var(--th-dark);margin:4px 0"><?= crm_money($app['standard_selling_price']) ?></div>
        <div class="muted small">Benchmark selling value before discounts</div>
      </div>

      <div style="background:#FFF1F2;border:1px solid #FECDD3;border-radius:var(--r-sm);padding:14px 16px">
        <div class="muted small" style="font-weight:600;text-transform:uppercase;color:#9F1239">Proposed Concession</div>
        <div style="font-size:22px;font-weight:800;color:#BE123C;margin:4px 0">− <?= crm_money($app['requested_discount_amount']) ?></div>
        <div style="color:#9F1239;font-weight:700"><?= number_format((float) $app['requested_discount_pct'], 2) ?>% Commercial Discount</div>
      </div>

      <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:var(--r-sm);padding:14px 16px">
        <div class="muted small" style="font-weight:600;text-transform:uppercase;color:#166534">Proposed Selling Price (Net)</div>
        <div style="font-size:22px;font-weight:800;color:#15803D;margin:4px 0"><?= crm_money($app['proposed_selling_price']) ?></div>
        <div class="muted small">Taxable base for GST calculations</div>
      </div>

      <div style="background:#F8FAFC;border:1px solid var(--line);border-radius:var(--r-sm);padding:14px 16px">
        <div class="muted small" style="font-weight:600;text-transform:uppercase">Customer Grand Total</div>
        <div style="font-size:22px;font-weight:800;color:var(--th-primary);margin:4px 0"><?= crm_money($app['grand_total']) ?></div>
        <div class="muted small">Inclusive of GST, freight &amp; installation</div>
      </div>
    </div>
  </div>
</div>

<div class="grid g-side">
  <div class="grid">
    <!-- Salesperson Justification -->
    <div class="card">
      <div class="hd"><h2>2. Salesperson Commercial Justification &amp; Customer Context</h2></div>
      <div class="bd">
        <blockquote style="margin:0;padding:12px 16px;background:#F8FAFC;border-left:4px solid var(--th-primary);border-radius:var(--r-sm);font-size:14px;line-height:1.6">
          <?= nl2br(esc($app['salesperson_justification'] ?: 'Standard commercial review triggered by discount rule.')) ?>
        </blockquote>
      </div>
    </div>

    <!-- Line Item Breakdown Table -->
    <div class="card">
      <div class="hd"><h2>3. Line Item Commercial Audit</h2></div>
      <div class="bd flush">
        <div class="tablewrap"><table class="t">
          <thead>
            <tr>
              <th>Product / Opening</th>
              <th class="num">Qty</th>
              <th class="num">Standard Rate</th>
              <th class="num">Discount %</th>
              <th class="num">Unit Price</th>
              <th class="num">Line Total</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($app['items'] as $item): ?>
            <tr>
              <td>
                <strong><?= esc($item['product_name'] ?? 'Custom Profile') ?></strong>
                <div class="muted small"><?= esc($item['sku'] ?? 'SKU') ?><?= ! empty($item['finish']) ? ' · ' . esc($item['finish']) : '' ?></div>
              </td>
              <td class="num"><?= (float) $item['quantity'] ?></td>
              <td class="num"><?= crm_money($item['standard_price'] ?: $item['unit_price']) ?></td>
              <td class="num">
                <?php if ((float) $item['discount_pct'] > 0): ?>
                  <span style="color:#B91C1C;font-weight:600"><?= number_format((float) $item['discount_pct'], 1) ?>%</span>
                <?php else: ?>
                  <span class="muted">0%</span>
                <?php endif; ?>
              </td>
              <td class="num"><strong><?= crm_money($item['unit_price']) ?></strong></td>
              <td class="num"><strong><?= crm_money($item['line_total']) ?></strong></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
    </div>

    <!-- Clarifications Thread -->
    <div class="card">
      <div class="hd"><h2>4. Clarification Thread &amp; Management Notes</h2></div>
      <div class="bd">
        <?php if (! empty($app['clarifications'])): ?>
          <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px">
          <?php foreach ($app['clarifications'] as $c): ?>
            <div style="padding:10px 14px;background:#F8FAFC;border:1px solid var(--line);border-radius:var(--r-sm)">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                <strong><?= esc($c['sender_name']) ?></strong>
                <span class="muted small"><?= esc(crm_dt($c['created_at'])) ?></span>
              </div>
              <div style="font-size:13.5px"><?= nl2br(esc($c['message'])) ?></div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="muted small" style="margin-bottom:14px">No clarification messages exchanged yet.</p>
        <?php endif; ?>

        <form method="post" action="<?= site_url('crm/approvals/' . $app['id'] . '/clarify') ?>">
          <?= csrf_field() ?>
          <label for="message">Post a Question or Clarification</label>
          <textarea id="message" name="message" rows="2" required placeholder="Ask salesperson about competitor quotes, volume commitments, or margin trade-offs..."></textarea>
          <div style="margin-top:8px;text-align:right">
            <button class="btn sm alt" type="submit">Post Clarification Note</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Manager Decision Panel -->
  <div class="grid" style="align-content:start">
    <div class="card">
      <div class="hd"><h2>Manager Decision Panel</h2></div>
      <div class="bd">
        <?php if ($app['status'] === 'approved'): ?>
          <div style="padding:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:var(--r-sm);margin-bottom:14px">
            <strong style="color:#166534">✓ Approved by <?= esc($app['approver_name'] ?? 'Management') ?></strong>
            <div class="muted small" style="margin-top:4px">Decided on <?= esc(crm_dt($app['action_at'])) ?></div>
            <?php if (! empty($app['manager_remarks'])): ?>
              <div style="margin-top:8px;font-size:13.5px"><em>"<?= esc($app['manager_remarks']) ?>"</em></div>
            <?php endif; ?>
          </div>
          <p class="small muted">Quotation is unlocked and commercially verified. Salesperson can send official proposal to client.</p>

        <?php elseif ($app['status'] === 'rejected'): ?>
          <div style="padding:16px;background:#FEF2F2;border:1px solid #FECDD3;border-radius:var(--r-sm);margin-bottom:14px">
            <strong style="color:#991B1B">✗ Rejected by <?= esc($app['approver_name'] ?? 'Management') ?></strong>
            <div class="muted small" style="margin-top:4px">Decided on <?= esc(crm_dt($app['action_at'])) ?></div>
            <div style="margin-top:8px;font-size:13.5px;color:#991B1B"><strong>Mandatory Reason:</strong> <?= esc($app['rejection_reason'] ?: $app['manager_remarks']) ?></div>
          </div>
          <p class="small muted">Proposal cannot be sent to customer under these commercial terms. A revision with compliant margins is required.</p>

        <?php elseif ($app['status'] === 'returned_for_revision'): ?>
          <div style="padding:16px;background:#FEF3C7;border:1px solid #FDE68A;border-radius:var(--r-sm);margin-bottom:14px">
            <strong style="color:#92400E">↺ Returned for Revision</strong>
            <div class="muted small" style="margin-top:4px">By <?= esc($app['approver_name'] ?? 'Management') ?> on <?= esc(crm_dt($app['action_at'])) ?></div>
            <?php if (! empty($app['manager_remarks'])): ?>
              <div style="margin-top:8px;font-size:13.5px"><em>"<?= esc($app['manager_remarks']) ?>"</em></div>
            <?php endif; ?>
          </div>
          <p class="small muted">Salesperson must modify quantities, product specifications, or reduce discounts to re-submit.</p>

        <?php elseif ($canDecide): ?>
          <form method="post" action="<?= site_url('crm/approvals/' . $app['id'] . '/decide') ?>">
            <?= csrf_field() ?>
            <label for="remarks">Approval Remarks / Counter-Terms</label>
            <textarea id="remarks" name="remarks" rows="2" placeholder="e.g. Approved subject to 50% advance milestone payment..."></textarea>

            <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px">
              <button class="btn teal" type="submit" name="decision" value="approve" style="width:100%;justify-content:center">
                ✓ Approve Commercial Terms
              </button>
              <button class="btn alt" type="button" data-modal="modal-reject" style="width:100%;justify-content:center;color:#B91C1C">
                ✗ Decline / Reject Proposal
              </button>
              <button class="btn alt" type="submit" name="decision" value="return" style="width:100%;justify-content:center" onclick="return confirm('Return this quotation to salesperson for commercial revision?')">
                ↺ Return for Revision
              </button>
            </div>
          </form>
        <?php else: ?>
          <p class="muted">You do not have authorization to decide on this commercial tier, or self-approval restrictions apply.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Mandatory Rejection Reason -->
<div class="modal" id="modal-reject" role="dialog" aria-modal="true" aria-label="Reject Commercial Approval">
  <div class="box">
    <div class="hd">
      <h3>Decline / Reject Commercial Proposal</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/approvals/' . $app['id'] . '/decide') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="decision" value="reject">
      <div class="bd">
        <p style="font-size:13.5px;color:var(--text-muted);margin-bottom:12px">
          Under DHIS Sales Governance, declining a quotation requires a mandatory written reason so the sales executive can negotiate viable alternate specifications with the client.
        </p>

        <label for="rejection_reason">Mandatory Rejection Reason <span class="req">*</span></label>
        <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Explain why the margin is insufficient, what counter-offer or product substitution is recommended..."></textarea>
      </div>
      <div class="ft" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn alt" data-close>Cancel</button>
        <button type="submit" class="btn" style="background:#B91C1C;color:#fff">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>
