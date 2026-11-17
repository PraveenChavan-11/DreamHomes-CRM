<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Payment Transactions &amp; Receipts</h2>
    <p class="muted" style="margin:0">Record advances, milestone settlements, full balances, and issue official PDF payment receipts.</p>
  </div>
  <button type="button" class="btn btn-primary" data-modal="m-record-payment">
    <?= crm_icon('M12 4v16m8-8H4', 16) ?>
    <span>Record New Payment</span>
  </button>
</div>

<!-- Financial Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin-top:20px">
  <div class="card" style="padding:16px">
    <span class="muted small bold" style="text-transform:uppercase;letter-spacing:0.5px">Total Collections (INR)</span>
    <div style="font-size:24px;font-weight:800;color:var(--green);margin-top:6px">
      ₹<?= number_format($total_received, 2) ?>
    </div>
    <span class="muted small">Settled commercial payments across all orders</span>
  </div>
  <div class="card" style="padding:16px">
    <span class="muted small bold" style="text-transform:uppercase;letter-spacing:0.5px">Total Transactions</span>
    <div style="font-size:24px;font-weight:800;color:var(--th-dark);margin-top:6px">
      <?= $total ?>
    </div>
    <span class="muted small">Recorded payment and refund entries</span>
  </div>
  <?php if ($total_refunded > 0): ?>
  <div class="card" style="padding:16px">
    <span class="muted small bold" style="text-transform:uppercase;letter-spacing:0.5px">Refunds Processed</span>
    <div style="font-size:24px;font-weight:800;color:var(--red);margin-top:6px">
      ₹<?= number_format($total_refunded, 2) ?>
    </div>
    <span class="muted small">Client cancellations and order adjustments</span>
  </div>
  <?php endif; ?>
</div>

<!-- Search & Filter Bar -->
<div class="card" style="margin-top:20px;padding:12px 16px">
  <form method="get" action="<?= site_url('crm/payments') ?>" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:0">
    <div style="flex:1;min-width:240px">
      <input type="search" name="q" value="<?= esc($params['q'] ?? '') ?>" placeholder="Search payment ref, receipt #, client, order #..." style="width:100%;margin:0">
    </div>
    <div>
      <select name="status" style="margin:0" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="paid" <?= ($params['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid / Settled</option>
        <option value="pending" <?= ($params['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Clearance</option>
        <option value="refunded" <?= ($params['status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if (! empty($params['q']) || ! empty($params['status'])): ?>
      <a href="<?= site_url('crm/payments') ?>" class="btn btn-ghost">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table Card -->
<div class="card" style="margin-top:16px;overflow:hidden">
  <div style="overflow-x:auto">
    <table class="table">
      <thead>
        <tr>
          <th>Receipt #</th>
          <th>Order Ref</th>
          <th>Customer</th>
          <th>Type</th>
          <th>Mode</th>
          <th>Txn Reference</th>
          <th>Date</th>
          <th style="text-align:right">Amount (₹)</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="10" style="text-align:center;padding:32px" class="muted">No payment transactions recorded.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $p): ?>
            <tr>
              <td>
                <a href="<?= site_url("crm/payments/{$p['id']}") ?>" style="font-weight:700;color:var(--th-primary)">
                  <?= esc($p['receipt_no'] ?: $p['payment_no']) ?>
                </a>
              </td>
              <td>
                <a href="<?= site_url("crm/orders/{$p['order_id']}") ?>" style="color:inherit;font-weight:600">
                  <?= esc($p['order_no']) ?>
                </a>
              </td>
              <td>
                <?php if (! empty($p['organisation_id'])): ?>
                  <a href="<?= site_url("crm/organisations/{$p['organisation_id']}") ?>" style="color:inherit;font-weight:700">
                    <?= esc($p['organisation_name'] ?? $p['customer_name']) ?>
                  </a><br>
                <?php else: ?>
                  <strong style="color:inherit"><?= esc($p['organisation_name'] ?? $p['customer_name']) ?></strong><br>
                <?php endif; ?>
                <span class="muted small"><?= esc($p['contact_name'] ?? $p['customer_name']) ?><?= ! empty($p['customer_mobile']) ? ' · ' . esc($p['customer_mobile']) : '' ?></span>
              </td>
              <td><?= esc(ucwords($p['payment_type'])) ?></td>
              <td><span class="badge badge-grey"><?= esc(strtoupper(str_replace('_', ' ', $p['payment_mode']))) ?></span></td>
              <td class="muted small"><?= esc($p['transaction_reference'] ?: 'Direct settlement') ?></td>
              <td><?= crm_date($p['payment_date']) ?></td>
              <td style="text-align:right;font-weight:800;color:<?= $p['payment_type'] === 'refund' ? 'var(--red)' : 'var(--green)' ?>">
                <?= $p['payment_type'] === 'refund' ? '-' : '' ?>₹<?= number_format((float) $p['amount'], 2) ?>
              </td>
              <td style="text-align:center"><?= crm_badge($p['status']) ?></td>
              <td style="text-align:right">
                <a href="<?= site_url("crm/payments/{$p['id']}/receipt") ?>" class="btn btn-xs btn-secondary" target="_blank" title="View PDF Receipt">
                  <?= crm_icon('M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z', 14) ?>
                  <span>PDF</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Record Payment -->
<div class="modal" id="m-record-payment" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3>Record Customer Payment</h3>
      <button type="button" class="modal-close" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/payments/record') ?>">
      <?= csrf_field() ?>
      <div class="modal-body">
        <div class="field">
          <label>Sales Order <span style="color:var(--red)">*</span></label>
          <select name="order_id" required>
            <option value="">Select Confirmed Order...</option>
            <?php foreach ($orders as $o): ?>
              <option value="<?= (int) $o['id'] ?>"><?= esc($o['order_no']) ?> · <?= esc($o['customer_name']) ?> (Total: ₹<?= number_format((float) $o['grand_total'], 2) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Payment Nature <span style="color:var(--red)">*</span></label>
            <select name="payment_type" required>
              <option value="advance">Advance Payment</option>
              <option value="partial">Partial / Milestone Payment</option>
              <option value="balance">Final Balance Payment</option>
              <option value="full">Full Settlement</option>
              <option value="refund">Refund</option>
            </select>
          </div>
          <div class="field">
            <label>Amount (INR ₹) <span style="color:var(--red)">*</span></label>
            <input type="number" name="amount" step="0.01" required placeholder="e.g. 50000.00">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="field">
            <label>Payment Mode <span style="color:var(--red)">*</span></label>
            <select name="payment_mode" required>
              <option value="bank_transfer">Bank Transfer (NEFT / RTGS / IMPS)</option>
              <option value="upi">UPI (GPay, PhonePe, Paytm)</option>
              <option value="cheque">Cheque</option>
              <option value="card">Credit / Debit Card</option>
              <option value="cash">Cash</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="field">
            <label>Payment Date</label>
            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <div class="field">
          <label>Transaction Reference / UTR / Cheque Number</label>
          <input type="text" name="transaction_reference" placeholder="e.g. UTR123456789 or Cheque #004521">
        </div>

        <div class="field">
          <label>Payment Notes &amp; Allocation</label>
          <textarea name="notes" rows="2" placeholder="e.g. 50% advance towards window order manufacturing..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close" data-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Record Payment &amp; Issue Receipt</button>
      </div>
    </form>
  </div>
</div>
