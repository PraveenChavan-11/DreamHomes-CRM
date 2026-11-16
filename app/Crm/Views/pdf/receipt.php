<?php
/**
 * Professional Payment Receipt Template
 * @var array $p Payment record
 * @var array $company Company settings
 */
$f = static fn ($n) => number_format((float) $n, 2, '.', ',');
$amountWords = crm_inr_words((float) $p['amount']);
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt <?= esc($p['receipt_no'] ?: $p['payment_no']) ?></title>
<style>
  @page { size: 210mm 297mm portrait; margin: 12mm 15mm; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #172B22; line-height: 1.4; }
  table { width: 100%; border-collapse: collapse; }
  .r { text-align: right; }
  .c { text-align: center; }
  .hdr { border-bottom: 2pt solid #1D6A34; padding-bottom: 8px; margin-bottom: 12px; }
  .title { font-size: 16pt; font-weight: bold; color: #1D6A34; }
  .box { border: 1pt solid #DCE5E0; border-radius: 4px; padding: 10px; margin-bottom: 12px; }
  .field-table td { padding: 4px 6px; border-bottom: 0.5pt solid #EEF3F0; }
  .highlight-amount { font-size: 14pt; font-weight: bold; color: #1D6A34; background: #F3F8F5; padding: 8px; border: 1pt solid #D6E8DE; text-align: center; margin: 10px 0; }
  .sig-table { margin-top: 40px; }
</style>
</head>
<body>

<table class="hdr">
  <tr>
    <td>
      <div class="title">OFFICIAL PAYMENT RECEIPT</div>
      <b><?= esc($company['name']) ?></b><br>
      <?= esc($company['address']) ?><br>
      GSTIN: <?= esc($company['gstin']) ?> · Tel: <?= esc($company['phone']) ?>
    </td>
    <td class="r">
      <b>Receipt No:</b> <span style="font-size: 11pt; color: #1D6A34; font-weight: bold;"><?= esc($p['receipt_no'] ?: $p['payment_no']) ?></span><br>
      <b>Date:</b> <?= esc(date('d M Y', strtotime($p['payment_date']))) ?><br>
      <b>Order Ref:</b> <?= esc($p['order_no']) ?><br>
      <b>Payment Mode:</b> <?= esc(strtoupper(str_replace('_', ' ', $p['payment_mode']))) ?>
    </td>
  </tr>
</table>

<div class="box">
  <table class="field-table">
    <tr>
      <td style="width: 25%;"><b>Received From:</b></td>
      <td><b><?= esc($p['customer_name']) ?></b> <?= ! empty($p['company_name']) ? '(' . esc($p['company_name']) . ')' : '' ?></td>
    </tr>
    <tr>
      <td><b>Client Address:</b></td>
      <td><?= esc($p['billing_address'] ?: 'Pune, Maharashtra') ?></td>
    </tr>
    <tr>
      <td><b>Transaction Ref:</b></td>
      <td><?= esc($p['transaction_reference'] ?: 'N/A (Direct settlement)') ?></td>
    </tr>
    <tr>
      <td><b>Payment Nature:</b></td>
      <td><?= esc(ucwords($p['payment_type'])) ?> payment towards Sales Order <b><?= esc($p['order_no']) ?></b></td>
    </tr>
  </table>

  <div class="highlight-amount">
    AMOUNT RECEIVED: ₹ <?= $f($p['amount']) ?>
  </div>

  <p><b>Amount in Words:</b> Indian Rupees <?= esc($amountWords) ?></p>

  <table class="field-table" style="margin-top: 10px;">
    <tr>
      <td><b>Total Order Value:</b></td>
      <td class="r">₹ <?= $f($p['order_total']) ?></td>
    </tr>
    <tr>
      <td><b>Total Amount Received to Date:</b></td>
      <td class="r" style="color: #1D6A34; font-weight: bold;">₹ <?= $f($p['order_paid_total']) ?></td>
    </tr>
    <tr>
      <td><b>Remaining Balance Payable:</b></td>
      <td class="r" style="color: #9C2A2A; font-weight: bold;">₹ <?= $f($p['order_balance']) ?></td>
    </tr>
  </table>
</div>

<?php if (! empty($p['notes'])): ?>
  <div style="font-size: 8pt; color: #5B6B62; margin-bottom: 15px;">
    <b>Remarks:</b> <?= nl2br(esc($p['notes'])) ?>
  </div>
<?php endif; ?>

<table class="sig-table">
  <tr>
    <td style="width: 50%;">
      Client Signature: _______________________
    </td>
    <td class="r" style="width: 50%;">
      For <b><?= esc($company['name']) ?></b><br><br><br>
      Authorised Accounts Signatory
    </td>
  </tr>
</table>

</body>
</html>
