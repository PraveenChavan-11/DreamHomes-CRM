<div class="header-action">
  <div>
    <div style="margin-bottom:4px">
      <a href="<?= site_url('crm/payments') ?>" class="muted small" style="text-decoration:none">← All Payments</a>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <h2 style="margin:0">Receipt <?= esc($p['receipt_no'] ?: $p['payment_no']) ?></h2>
      <?= crm_badge($p['status']) ?>
    </div>
    <p class="muted" style="margin:4px 0 0">
      Order: <b><a href="<?= site_url("crm/orders/{$p['order_id']}") ?>" style="color:inherit"><?= esc($p['order_no']) ?></a></b> · Client: <b><a href="<?= site_url("crm/customers/{$p['customer_id']}") ?>" style="color:inherit"><?= esc($p['customer_name']) ?></a></b>
    </p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="<?= site_url("crm/payments/{$p['id']}/receipt") ?>" class="btn btn-primary" target="_blank">
      <?= crm_icon('M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z', 16) ?>
      <span>Download PDF Receipt</span>
    </a>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px">
  <div>
    <div class="card" style="padding:24px">
      <div style="display:flex;justify-content:space-between;border-bottom:2px solid var(--line);padding-bottom:16px;margin-bottom:20px">
        <div>
          <span class="muted small bold" style="text-transform:uppercase">Amount Settled</span>
          <div style="font-size:32px;font-weight:900;color:var(--green);margin-top:4px">
            ₹<?= number_format((float) $p['amount'], 2) ?>
          </div>
          <span class="small bold" style="color:var(--th-primary)">
            Indian Rupees <?= esc(crm_inr_words((float) $p['amount'])) ?>
          </span>
        </div>
        <div style="text-align:right">
          <span class="muted small">Payment Date</span>
          <div style="font-weight:700;font-size:15px;margin-top:2px"><?= crm_date($p['payment_date']) ?></div>
          <span class="badge badge-grey" style="margin-top:4px"><?= esc(strtoupper(str_replace('_', ' ', $p['payment_mode']))) ?></span>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px">
        <div>
          <span class="muted small">Transaction Reference / UTR</span>
          <div style="font-weight:600;margin-top:2px"><?= esc($p['transaction_reference'] ?: 'Direct settlement') ?></div>
        </div>
        <div>
          <span class="muted small">Payment Nature</span>
          <div style="font-weight:600;margin-top:2px"><?= esc(ucwords($p['payment_type'])) ?> Payment</div>
        </div>
        <div>
          <span class="muted small">Recorded By</span>
          <div style="margin-top:2px"><?= esc($p['recorded_by_name'] ?: 'System') ?></div>
        </div>
        <div>
          <span class="muted small">System Timestamp</span>
          <div style="margin-top:2px"><?= crm_dt($p['created_at']) ?></div>
        </div>
      </div>

      <?php if (! empty($p['notes'])): ?>
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line-2)">
          <span class="muted small">Remarks &amp; Purpose</span>
          <p style="margin:4px 0 0;font-size:13px;line-height:1.4"><?= nl2br(esc($p['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Order Balance Status Card -->
    <div class="card" style="padding:20px">
      <h3 style="margin:0 0 12px;font-size:14px;color:var(--th-dark)">Sales Order Financials</h3>
      <table class="table" style="font-size:13px">
        <tr>
          <td>Order Total:</td>
          <td style="text-align:right;font-weight:700">₹<?= number_format((float) $p['order_total'], 2) ?></td>
        </tr>
        <tr>
          <td>Total Received:</td>
          <td style="text-align:right;font-weight:700;color:var(--green)">₹<?= number_format((float) $p['order_paid_total'], 2) ?></td>
        </tr>
        <tr style="border-top:2px solid var(--line)">
          <td><b>Balance Payable:</b></td>
          <td style="text-align:right;font-weight:800;color:var(--red)">₹<?= number_format((float) $p['order_balance'], 2) ?></td>
        </tr>
      </table>

      <div style="margin-top:16px">
        <a href="<?= site_url("crm/orders/{$p['order_id']}") ?>" class="btn btn-sm btn-secondary" style="width:100%;text-align:center">
          Open Sales Order Details
        </a>
      </div>
    </div>
  </div>
</div>
