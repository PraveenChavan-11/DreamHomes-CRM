<?php
/**
 * Professional A4 Commercial Quotation PDF Template
 * Designed for Dream Homes Interior Solutions (DHIS Group)
 * Precision styled for Dompdf with exact A4 portrait dimensions, repeated headers,
 * Indian numbering system amount in words, and complete architectural product specifications.
 *
 * @var array $q Quotation record
 * @var array $v Quotation version record
 * @var array $c Customer record
 * @var array $e Enquiry record
 * @var array $items Line items
 * @var array $charges Additional charges
 * @var array $company Company settings
 * @var bool $intra Whether customer is in same state (CGST+SGST vs IGST)
 * @var ?string $logo Base64 encoded logo image
 * @var string $salesperson Salesperson name
 */

$f = static fn ($n) => number_format((float) $n, 2, '.', ',');
$cgst = $sgst = $igst = 0.0;
foreach ($items as $i) {
    if ($intra) {
        $cgst += $i['tax_amount'] / 2;
        $sgst += $i['tax_amount'] / 2;
    } else {
        $igst += $i['tax_amount'];
    }
}
foreach ($charges as $ch) {
    if ($intra) {
        $cgst += $ch['tax_amount'] / 2;
        $sgst += $ch['tax_amount'] / 2;
    } else {
        $igst += $ch['tax_amount'];
    }
}

$grandTotal = (float) $v['grand_total'];
$amountWords = crm_inr_words($grandTotal);
$website = \Crm\Services\Settings::get('company.website', 'https://dhisgroup.in');
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title><?= esc($q['quotation_no']) ?> · V<?= (int) $v['version_no'] ?></title>
<style>
  @page {
    size: 210mm 297mm portrait;
    margin: 10mm 12mm 14mm 12mm;
  }
  * { box-sizing: border-box; }
  body {
    font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 8.5pt;
    line-height: 1.35;
    color: #172B22;
    margin: 0;
    padding: 0;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    padding: 0;
  }
  .r { text-align: right; }
  .c { text-align: center; }
  .muted { color: #5B6B62; }
  .bold { font-weight: bold; }
  .small { font-size: 7.5pt; }

  /* Header banner */
  .header-table {
    border-bottom: 2.5pt solid #1D6A34;
    padding-bottom: 8px;
    margin-bottom: 10px;
  }
  .header-table td {
    vertical-align: top;
  }
  .brand-title {
    font-size: 15pt;
    font-weight: bold;
    color: #1D6A34;
    letter-spacing: -0.3px;
    margin: 0 0 2px 0;
  }
  .brand-sub {
    font-size: 8pt;
    font-weight: 600;
    color: #1B5F52;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 5px;
  }
  .company-meta {
    font-size: 7.5pt;
    line-height: 1.3;
    color: #33443C;
  }
  .doc-title {
    font-size: 18pt;
    font-weight: 800;
    color: #1D6A34;
    margin: 0 0 4px 0;
    letter-spacing: 0.5px;
  }
  .meta-box {
    background: #F3F8F5;
    border: 1pt solid #D6E8DE;
    padding: 6px 8px;
    border-radius: 4px;
    margin-top: 4px;
  }

  /* Customer & Project split cards */
  .parties-table {
    margin-bottom: 10px;
  }
  .party-card {
    background: #FFFFFF;
    border: 1pt solid #DCE5E0;
    border-radius: 4px;
    padding: 7px 9px;
    vertical-align: top;
    height: 100%;
  }
  .party-hdr {
    font-size: 8pt;
    font-weight: bold;
    color: #1D6A34;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1pt solid #E5EDE8;
    padding-bottom: 3px;
    margin-bottom: 4px;
  }

  /* Items Table with repeated headers across pages */
  .items-table {
    margin-bottom: 10px;
  }
  .items-table thead {
    display: table-header-group;
  }
  .items-table tr {
    page-break-inside: avoid;
  }
  .items-table th {
    background: #1D6A34;
    color: #FFFFFF;
    font-size: 7.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 5px 4px;
    border: 0.5pt solid #1A532A;
  }
  .items-table td {
    padding: 5px 4px;
    border: 0.5pt solid #DCE5E0;
    font-size: 8pt;
    vertical-align: top;
  }
  .items-table tbody tr:nth-child(even) {
    background: #FAFCFA;
  }

  /* Totals section */
  .summary-table td {
    vertical-align: top;
  }
  .tot-box {
    border: 1pt solid #DCE5E0;
    border-radius: 4px;
    background: #FFFFFF;
  }
  .tot-table td {
    padding: 3.5px 6px;
    font-size: 8pt;
  }
  .tot-table .grand-row td {
    background: #1D6A34;
    color: #FFFFFF;
    font-weight: bold;
    font-size: 9.5pt;
    padding: 6px 8px;
  }

  .words-box {
    background: #F7FAF8;
    border: 1pt solid #D8E6DE;
    border-left: 3pt solid #1D6A34;
    padding: 5px 8px;
    margin: 6px 0;
    font-size: 8pt;
  }

  /* Terms and specifications */
  .terms-box {
    background: #FAFCFA;
    border: 1pt solid #DCE5E0;
    border-radius: 4px;
    padding: 6px 8px;
    margin-top: 8px;
    font-size: 7.5pt;
    line-height: 1.35;
  }
  .terms-title {
    font-weight: bold;
    color: #1D6A34;
    text-transform: uppercase;
    font-size: 7.5pt;
    margin-bottom: 3px;
  }

  /* Signatures */
  .sig-table {
    margin-top: 25px;
    page-break-inside: avoid;
  }
  .sig-table td {
    vertical-align: top;
    width: 50%;
  }
  .sig-line {
    border-top: 1pt solid #6C8276;
    width: 80%;
    margin-top: 40px;
    padding-top: 4px;
    font-size: 7.5pt;
  }

  /* Page Footer */
  .footer {
    position: fixed;
    bottom: -8mm;
    left: 0;
    right: 0;
    border-top: 0.5pt solid #DCE5E0;
    padding-top: 3px;
    font-size: 7pt;
    color: #7B8F84;
    text-align: center;
  }
</style>
</head>
<body>

<!-- Header -->
<table class="header-table">
  <tr>
    <td style="width: 58%;">
      <?php if (! empty($logo)): ?>
        <img src="<?= $logo ?>" style="max-height: 48px; max-width: 220px; margin-bottom: 4px;" alt="Dream Homes Logo">
      <?php else: ?>
        <div class="brand-title">DREAM HOMES INTERIOR SOLUTIONS</div>
        <div class="brand-sub">Premium Windows · Bathrooms · Kitchens · Appliances</div>
      <?php endif; ?>
      <div class="company-meta">
        <b><?= esc($company['name'] ?: 'Dream Homes Interior Solutions') ?></b><br>
        <?= esc($company['address'] ?: 'Showroom: Ground Floor, Near Balewadi High Street, Pune, Maharashtra 411045') ?><br>
        Phone: <?= esc($company['phone'] ?: '+91 98765 43210') ?> · Email: <?= esc($company['email'] ?: 'sales@dhisgroup.in') ?><br>
        Website: <?= esc($website) ?> · State: <?= esc($company['state'] ?: 'Maharashtra') ?> (Code: 27)<br>
        <b>GSTIN:</b> <?= ! empty($company['gstin']) ? esc($company['gstin']) : '<i>27AABCD1234E1Z5 (Applied / Registered)</i>' ?>
      </div>
    </td>
    <td class="r" style="width: 42%;">
      <div class="doc-title">QUOTATION</div>
      <div class="meta-box r">
        <b>Quote Ref:</b> <span style="font-size: 10pt; color: #1D6A34; font-weight: bold;"><?= esc($q['quotation_no']) ?></span><br>
        <b>Version:</b> V<?= (int) $v['version_no'] ?> · <b>Date:</b> <?= esc(date('d M Y', strtotime($v['created_at']))) ?><br>
        <b>Valid Until:</b> <span class="bold" style="color: #9C2A2A;"><?= $v['valid_until'] ? esc(date('d M Y', strtotime($v['valid_until']))) : '30 Days from date' ?></span><br>
        <b>Consultant:</b> <?= esc($salesperson ?: 'Sales Operations Desk') ?>
      </div>
    </td>
  </tr>
</table>

<!-- Customer & Project Parties Table -->
<table class="parties-table">
  <tr>
    <td style="width: 50%; padding-right: 5px;">
      <div class="party-card">
        <div class="party-hdr">Client Information</div>
        <b><?= esc($c['name']) ?></b>
        <?php if (! empty($c['company_name'])): ?>
          <br><span class="muted"><?= esc($c['company_name']) ?></span>
        <?php endif; ?>
        <br><?= nl2br(esc((string) ($c['billing_address'] ?: $c['site_address'] ?: 'Pune, Maharashtra'))) ?>
        <?php if (! empty($c['city']) || ! empty($c['pin_code'])): ?>
          <br><?= esc(trim(($c['city'] ?? 'Pune') . ' ' . ($c['pin_code'] ?? ''))) ?>, <?= esc($c['state'] ?? 'Maharashtra') ?>
        <?php endif; ?>
        <br><b>Phone:</b> <?= esc($c['mobile']) ?>
        <?php if (! empty($c['email'])): ?> · <b>Email:</b> <?= esc($c['email']) ?><?php endif; ?>
        <?php if (! empty($c['gstin'])): ?><br><b>GSTIN:</b> <?= esc($c['gstin']) ?><?php endif; ?>
      </div>
    </td>
    <td style="width: 50%; padding-left: 5px;">
      <div class="party-card">
        <div class="party-hdr">Project &amp; Site Details</div>
        <b>Project:</b> <?= esc((string) ($e['project_name'] ?: 'Residential Villa / Apartment Interior')) ?><br>
        <b>Enquiry Ref:</b> <?= esc($e['enquiry_no'] ?: 'Direct Inbound') ?><br>
        <b>Site Location:</b> <?= esc((string) ($e['site_location'] ?: $c['site_address'] ?: 'Pune Region')) ?><br>
        <b>Scope of Work:</b> Architectural Openings &amp; Interior Fittings<br>
        <b>Pricing Terms:</b> Supply, Delivery &amp; Professional Installation
      </div>
    </td>
  </tr>
</table>

<!-- Quotation Items Table with Repeated Headers -->
<table class="items-table">
  <thead>
    <tr>
      <th style="width: 4%;" class="c">#</th>
      <th style="width: 38%;">Description &amp; Specifications</th>
      <th style="width: 12%;">Brand / Model</th>
      <th style="width: 10%;" class="c">Dimensions</th>
      <th style="width: 6%;" class="r">Qty</th>
      <th style="width: 5%;" class="c">Unit</th>
      <th style="width: 8%;" class="r">Rate (₹)</th>
      <th style="width: 7%;" class="r">Taxable (₹)</th>
      <th style="width: 5%;" class="c">GST</th>
      <th style="width: 9%;" class="r">Total (₹)</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $n => $i): ?>
      <tr>
        <td class="c bold"><?= $n + 1 ?></td>
        <td>
          <b><?= esc($i['description']) ?></b>
          <?php if (! empty($i['sku'])): ?>
            <span class="muted small">(SKU: <?= esc($i['sku']) ?>)</span>
          <?php endif; ?>
          <?php if (! empty($i['variant'])): ?>
            <br><span style="color: #1D6A34;">• Variant: <?= esc($i['variant']) ?></span>
          <?php endif; ?>
          <?php if (! empty($i['specifications'])): ?>
            <br><span class="muted small"><?= nl2br(esc($i['specifications'])) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?= esc(! empty($i['brand']) ? $i['brand'] : 'DHIS Standard') ?>
        </td>
        <td class="c small">
          <?php if ($i['width'] !== null && $i['height'] !== null): ?>
            <?= rtrim(rtrim(number_format((float) $i['width'], 2, '.', ''), '0'), '.') ?> &times; <?= rtrim(rtrim(number_format((float) $i['height'], 2, '.', ''), '0'), '.') ?> <?= esc($i['size_unit'] ?: 'mm') ?>
            <?php if (! empty($i['nos'])): ?>
              <br><?= (int) $i['nos'] ?> Nos
            <?php endif; ?>
          <?php else: ?>
            Standard
          <?php endif; ?>
        </td>
        <td class="r bold"><?= rtrim(rtrim(number_format((float) $i['quantity'], 2, '.', ''), '0'), '.') ?></td>
        <td class="c"><?= esc($i['unit'] ?: 'Nos') ?></td>
        <td class="r"><?= $f($i['unit_price']) ?></td>
        <td class="r"><?= $f($i['taxable_amount']) ?></td>
        <td class="c small"><?= rtrim(rtrim(number_format((float) $i['tax_rate'], 1, '.', ''), '0'), '.') ?>%</td>
        <td class="r bold"><?= $f($i['line_total']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Commercial Summary & Totals -->
<table class="summary-table">
  <tr>
    <td style="width: 55%; padding-right: 8px;">
      <div class="words-box">
        <b>Amount in Words:</b><br>
        <span style="font-weight: bold; color: #1D6A34; font-size: 8.5pt;">Indian Rupees <?= esc($amountWords) ?></span>
      </div>

      <div class="terms-box">
        <div class="terms-title">Standard Terms &amp; Commercial Conditions</div>
        • <b>Payment Schedule:</b> <?= nl2br(esc((string) ($v['payment_terms'] ?: '50% Advance with Purchase Order, 40% before dispatch, 10% upon installation sign-off.'))) ?><br>
        • <b>Delivery Schedule:</b> <?= esc($v['delivery_estimate'] ?: '3 to 4 Weeks from site measurement confirmation and advance clearance.') ?><br>
        • <b>Installation Scope:</b> Professional unloading, fitting, sealing, alignment and debris clean-up included.<br>
        • <b>Warranty Coverage:</b> 10 Years on profiles &amp; finishes, 2 Years on multi-point locking hardware.<br>
        • <b>Site Readiness:</b> Client to ensure civil openings plastered, electrical/water supply available on site.
      </div>

      <?php if (! empty($v['customer_remarks'])): ?>
        <div class="terms-box" style="margin-top: 4px; border-left: 2pt solid #1B5F52;">
          <b>Special Remarks:</b> <?= nl2br(esc($v['customer_remarks'])) ?>
        </div>
      <?php endif; ?>
    </td>

    <td style="width: 45%;">
      <div class="tot-box">
        <table class="tot-table">
          <tr>
            <td>Item Subtotal</td>
            <td class="r">₹ <?= $f($v['subtotal']) ?></td>
          </tr>
          <?php if ((float) $v['discount_total'] > 0): ?>
            <tr>
              <td style="color: #9C2A2A;">Discount Applied</td>
              <td class="r" style="color: #9C2A2A;">− ₹ <?= $f($v['discount_total']) ?></td>
            </tr>
          <?php endif; ?>
          <tr style="border-top: 0.5pt solid #E5EDE8;">
            <td><b>Net Taxable Amount</b></td>
            <td class="r bold">₹ <?= $f($v['taxable_total']) ?></td>
          </tr>

          <?php foreach ($charges as $ch): ?>
            <tr>
              <td><?= esc($ch['name']) ?></td>
              <td class="r">₹ <?= $f($ch['amount']) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if ($intra): ?>
            <tr>
              <td class="muted small">Central GST (CGST @ 9%)</td>
              <td class="r">₹ <?= $f($cgst) ?></td>
            </tr>
            <tr>
              <td class="muted small">State GST (SGST @ 9%)</td>
              <td class="r">₹ <?= $f($sgst) ?></td>
            </tr>
          <?php else: ?>
            <tr>
              <td class="muted small">Integrated GST (IGST @ 18%)</td>
              <td class="r">₹ <?= $f($igst) ?></td>
            </tr>
          <?php endif; ?>

          <?php if ((float) $v['round_off'] != 0.0): ?>
            <tr>
              <td>Round Off</td>
              <td class="r"><?= ((float) $v['round_off'] > 0 ? '+ ' : '') ?>₹ <?= $f($v['round_off']) ?></td>
            </tr>
          <?php endif; ?>

          <tr class="grand-row">
            <td>GRAND TOTAL (INR)</td>
            <td class="r">₹ <?= $f($v['grand_total']) ?></td>
          </tr>
        </table>
      </div>
    </td>
  </tr>
</table>

<!-- Signatures -->
<table class="sig-table">
  <tr>
    <td>
      <div class="sig-line">
        <b>Client Acceptance Signature</b><br>
        Name &amp; Designation: ____________________<br>
        Date: ____________________
      </div>
    </td>
    <td class="r">
      <div class="sig-line" style="margin-left: auto;">
        <b>For Dream Homes Interior Solutions</b><br>
        Authorised Commercial Signatory<br>
        Date: <?= esc(date('d M Y')) ?>
      </div>
    </td>
  </tr>
</table>

<!-- Fixed Footer -->
<div class="footer">
  This is a computer-generated commercial document issued by Dream Homes Interior Solutions (DHIS Group). All disputes subject to Pune jurisdiction.<br>
  Registered Office: Balewadi High Street, Pune 411045 · Web: dhisgroup.in · Support: sales@dhisgroup.in
</div>

</body>
</html>
