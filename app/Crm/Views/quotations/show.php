<?php
/**
 * @var array $q @var array $c @var array $e @var list<array> $versions @var array $sel @var array $cur @var list<array> $negs @var ?array $openNeg @var ?array $order @var list<array> $mails @var bool $intra
 */
$isCur    = (int) $sel['id'] === (int) $cur['id'];
$closed   = in_array($q['status'], ['accepted', 'rejected', 'cancelled'], true);
$locked   = (int) $cur['is_locked'] === 1;
$qty      = static fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
$cgst = $igst = 0.0;
foreach (array_merge($sel['items'], $sel['charges']) as $i) { $igst += (float) $i['tax_amount']; }
$cgst = $igst / 2;
$canNeg = crm_can('negotiations.create') && $locked && in_array($q['status'], ['sent', 'negotiation'], true) && ! $openNeg;
?>
<div class="pagehead">
  <h1><?= esc($q['quotation_no']) ?> <?= crm_badge($q['status']) ?> <span class="muted small">v<?= (int) $sel['version_no'] ?> of <?= count($versions) ?></span></h1>
  <div class="gap">
    <?php if (crm_can('quotations.create') && ! $closed): ?>
      <?php if (! $locked): ?><a class="btn alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/edit') ?>">Edit draft</a>
      <?php else: ?><a class="btn alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/revise') ?>">Revise (new version)</a><?php endif; ?>
    <?php endif; ?>
    <a class="btn alt" target="_blank" rel="noopener" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/pdf/' . (int) $sel['version_no']) ?>">📄 View A4 PDF<?= $sel['pdf_path'] ? '' : ' (draft)' ?></a>
    <a class="btn alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/pdf/' . (int) $sel['version_no'] . '?download=1') ?>">⬇️ Download PDF</a>
    <a class="btn alt" target="_blank" rel="noopener" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/preview/' . (int) $sel['version_no']) ?>">Preview HTML</a>
    <?php if (count($versions) > 1): ?><a class="btn alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/compare') ?>">Compare versions</a><?php endif; ?>
    <?php if (crm_can('quotations.send') && ! $closed): ?>
      <?php if (! $locked): ?><button class="btn teal" type="button" data-modal="m-send">Send to customer</button><?php else: ?><button class="btn alt" type="button" data-modal="m-resend">Resend email</button><?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="grid g-side mb">
  <div class="card">
    <div class="hd"><h2>Quotation</h2>
      <?php if (count($versions) > 1): ?><nav class="gap small" aria-label="Versions"><?php foreach (array_reverse($versions) as $v): ?><a class="btn sm <?= (int) $v['version_no'] === (int) $sel['version_no'] ? '' : 'alt' ?>" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '?v=' . (int) $v['version_no']) ?>">v<?= (int) $v['version_no'] ?></a><?php endforeach; ?></nav><?php endif; ?></div>
    <div class="bd">
      <?php if (! $isCur): ?><div class="flash info">You are viewing an earlier version (v<?= (int) $sel['version_no'] ?>). It is read-only; the current version is v<?= (int) $cur['version_no'] ?>.</div><?php endif; ?>
      <dl class="kv">
        <dt>Customer</dt><dd><a href="<?= site_url('crm/customers/' . (int) $c['id']) ?>"><?= esc($c['name']) ?></a> · <?= esc($c['mobile']) ?><?= $c['email'] ? ' · ' . esc($c['email']) : '' ?></dd>
        <dt>Enquiry</dt><dd><a href="<?= site_url('crm/enquiries/' . (int) $e['id']) ?>"><?= esc($e['enquiry_no']) ?></a> · <?= esc((string) $e['project_name']) ?> · owner <?= esc((string) $e['owner_name'] ?: '—') ?></dd>
        <dt>This version</dt><dd>v<?= (int) $sel['version_no'] ?> by <?= esc($sel['author']) ?> on <?= crm_dt($sel['created_at']) ?> · <?= (int) $sel['is_locked'] ? '<span class="badge badge-green">Locked' . ($sel['sent_at'] ? ' · sent ' . crm_dt($sel['sent_at']) : '') . '</span>' : '<span class="badge badge-amber">Draft (editable)</span>' ?></dd>
        <?php if (! empty($sel['revision_reason'])): ?><dt>Revision reason</dt><dd><?= esc($sel['revision_reason']) ?></dd><?php endif; ?>
        <dt>Validity</dt><dd><?= crm_date($sel['valid_until']) ?><?= $sel['delivery_estimate'] ? ' · delivery ' . esc($sel['delivery_estimate']) : '' ?></dd>
        <?php if ($q['responded_at']): ?><dt>Customer response</dt><dd><?= crm_badge($q['customer_response']) ?> on <?= crm_dt($q['responded_at']) ?></dd><?php endif; ?>
      </dl>
    </div>
  </div>
  <div class="card">
    <div class="hd"><h2>Next steps</h2></div>
    <div class="bd">
      <?php if ($order): ?>
        <p>Converted to sales order <a href="<?= site_url('crm/orders/' . (int) $order['id']) ?>"><strong><?= esc($order['order_no']) ?></strong></a> (<?= crm_label($order['status']) ?>).</p>
      <?php elseif ($q['status'] === 'accepted'): ?>
        <p>The customer accepted this quotation.</p>
        <?php if (crm_can('orders.manage')): ?><button class="btn teal" type="button" data-modal="m-order">Convert to sales order</button><?php endif; ?>
      <?php elseif ($closed): ?><p class="muted">This quotation is closed (<?= crm_label($q['status']) ?>).</p>
      <?php elseif (! $locked): ?><p class="muted">This version is a draft. Send it to the customer to lock it and generate the PDF.</p>
      <?php else: ?>
        <div class="gap">
          <?php if (crm_can('quotations.create', 'quotations.send')): ?><button class="btn" type="button" data-modal="m-response">Record customer response</button><?php endif; ?>
          <?php if ($canNeg): ?><button class="btn alt" type="button" data-modal="m-neg">Request price reduction</button><?php endif; ?>
        </div>
        <?php if ($openNeg): ?><p class="mt">Open negotiation <a href="<?= site_url('crm/negotiations/' . (int) $openNeg['id']) ?>"><strong><?= esc($openNeg['negotiation_no']) ?></strong></a> — <?= crm_badge($openNeg['status']) ?><?= $openNeg['status'] === 'approved' ? ' · <a href="' . site_url('crm/negotiations/' . (int) $openNeg['id']) . '">prepare the revised quotation</a>' : '' ?></p><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card mb">
  <div class="hd"><h2>Items — v<?= (int) $sel['version_no'] ?></h2></div>
  <div class="bd flush tablewrap"><table class="t">
    <thead><tr><th>#</th><th>Item · variant · size</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Discount</th><th class="num">Taxable</th><th class="num">GST</th><th class="num">Line total</th></tr></thead>
    <tbody>
    <?php foreach ($sel['items'] as $n => $i): ?>
      <tr>
        <td><?= $n + 1 ?></td>
        <td><strong><?= esc($i['description']) ?></strong><?= $i['sku'] ? ' <span class="muted small">(' . esc($i['sku']) . ')</span>' : '' ?>
          <?php if ($i['variant']): ?><div class="small">Variant: <?= esc($i['variant']) ?></div><?php endif; ?>
          <?php if ($i['width'] !== null && $i['height'] !== null): ?><div class="small muted">Size: <?= $qty($i['width']) ?> × <?= $qty($i['height']) ?> <?= esc($i['size_unit']) ?><?= $i['nos'] ? ' × ' . $qty($i['nos']) . ' nos' : '' ?></div><?php endif; ?>
          <?php if ($i['specifications']): ?><div class="small muted"><?= esc($i['specifications']) ?></div><?php endif; ?></td>
        <td class="num"><?= $qty($i['quantity']) ?> <?= esc($i['unit']) ?></td>
        <td class="num"><?= crm_money($i['unit_price']) ?>
          <div class="small"><?= $i['price_source'] === 'manual' ? '<span class="badge badge-amber">Manual price</span>' : '<span class="badge badge-grey">Catalogue</span>' ?><?= $i['std_price'] !== null && $i['price_source'] === 'manual' ? '<div class="muted">std ' . crm_money($i['std_price']) . '</div>' : '' ?><?= $i['override_total'] !== null ? '<div class="muted">fixed total ' . crm_money($i['override_total']) . '</div>' : '' ?></div></td>
        <td class="num"><?= crm_money($i['discount_amount']) ?><?= (float) $i['discount_pct'] > 0 ? '<div class="small muted">' . $qty($i['discount_pct']) . '%</div>' : '' ?></td>
        <td class="num"><?= crm_money($i['taxable_amount']) ?></td>
        <td class="num"><?= crm_money($i['tax_amount']) ?><div class="small muted"><?= $qty($i['tax_rate']) ?>%</div></td>
        <td class="num"><strong><?= crm_money($i['line_total']) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="bd" style="padding-top:16px;padding-bottom:20px;background:#FAFCFB;border-top:1px solid var(--line-2)">
    <div class="totals">
      <div class="row-subtotal"><span>Subtotal</span><span><?= crm_money($sel['subtotal']) ?></span></div>
      <?php if ((float) $sel['discount_total'] > 0): ?>
        <div class="row-discount"><span>Discount Applied</span><span>− <?= crm_money($sel['discount_total']) ?></span></div>
      <?php endif; ?>
      <?php if ($sel['target_total'] !== null): ?>
        <div class="small muted" style="padding:4px 0 6px;border-bottom:1px dashed rgba(0,0,0,0.06);display:flex;justify-content:space-between">
          <span>↳ Agreed Final Price Adjustment (Target <?= crm_money($sel['target_total']) ?>)</span>
          <span><?= crm_money($sel['adjustment_amount']) ?></span>
        </div>
      <?php endif; ?>
      <div class="row-taxable"><span>Net Taxable Value</span><span><?= crm_money($sel['taxable_total']) ?></span></div>
      <?php foreach ($sel['charges'] as $ch): ?>
        <div class="row-charge"><span><?= esc($ch['name']) ?></span><span><?= crm_money($ch['amount']) ?></span></div>
      <?php endforeach; ?>
      <?php if ($intra): ?>
        <div class="row-tax"><span>Central GST (CGST @ 9%)</span><span><?= crm_money($cgst) ?></span></div>
        <div class="row-tax"><span>State GST (SGST @ 9%)</span><span><?= crm_money($cgst) ?></span></div>
      <?php else: ?>
        <div class="row-tax"><span>Integrated GST (IGST @ 18%)</span><span><?= crm_money($igst) ?></span></div>
      <?php endif; ?>
      <?php if ((float) $sel['round_off'] != 0.0): ?>
        <div class="row-roundoff"><span class="muted small">Round Off</span><span><?= ((float) $sel['round_off'] > 0 ? '+ ' : '') . crm_money($sel['round_off']) ?></span></div>
      <?php endif; ?>
      <div class="gt row-grand-total">
        <span>Grand Total (INR)</span>
        <span><?= crm_money($sel['grand_total']) ?></span>
      </div>
    </div>
  </div>
</div>

<div class="card mb">
  <div class="hd"><h2>Version history &amp; tracked changes</h2><span class="small muted">Every draft save and revision is recorded with who, when, why and a before/after of each change.</span></div>
  <div class="bd">
    <ul class="tl">
    <?php foreach ($versions as $k => $v): $prev = $versions[$k + 1] ?? null; ?>
      <li>
        <div class="when"><?= crm_dt($v['created_at']) ?></div>
        <div class="what">v<?= (int) $v['version_no'] ?> · <?= crm_money($v['grand_total']) ?> · <?= (int) $v['is_locked'] ? 'locked' : 'draft' ?><?= (int) $v['id'] === (int) $cur['id'] ? ' · current' : '' ?></div>
        <div class="meta">By <?= esc($v['author']) ?><?= $v['sent_at'] ? ' · sent ' . crm_dt($v['sent_at']) : '' ?><?= $v['revision_reason'] ? ' · reason: ' . esc($v['revision_reason']) : '' ?>
          <?php if ($v['negotiation']): ?> · from negotiation <a href="<?= site_url('crm/negotiations/' . (int) $v['negotiation']['id']) ?>"><?= esc($v['negotiation']['negotiation_no']) ?></a><?php endif; ?></div>
        <div class="gap small" style="margin-top:6px">
          <a class="btn sm alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '?v=' . (int) $v['version_no']) ?>">View v<?= (int) $v['version_no'] ?></a>
          <a class="btn sm alt" target="_blank" rel="noopener" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/pdf/' . (int) $v['version_no']) ?>">📄 View A4 PDF</a>
          <a class="btn sm alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/pdf/' . (int) $v['version_no'] . '?download=1') ?>">⬇️ Download PDF</a>
          <?php if ($prev): ?><a class="btn sm alt" href="<?= site_url('crm/quotations/' . (int) $q['id'] . '/compare?a=' . (int) $prev['version_no'] . '&b=' . (int) $v['version_no']) ?>">Compare with v<?= (int) $prev['version_no'] ?></a><?php endif; ?>
        </div>
        <?php if ($v['changes']): ?><details style="margin-top:6px"<?= (int) $v['version_no'] === (int) $sel['version_no'] ? ' open' : '' ?>><summary class="small">Tracked changes (<?= count($v['changes']) ?>)</summary><div style="margin-top:8px"><?= view('Crm\Views\quotations\_changes', ['changes' => $v['changes']]) ?></div></details><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="grid g2 mb">
  <div class="card"><div class="hd"><h2>Terms</h2></div><div class="bd">
    <div class="small muted">Payment terms</div><p><?= nl2br(esc((string) $sel['payment_terms'])) ?></p>
    <div class="small muted">Terms &amp; conditions</div><p><?= nl2br(esc((string) $sel['terms'])) ?></p>
    <?php if ($sel['customer_remarks']): ?><div class="small muted">Customer remarks</div><p><?= nl2br(esc($sel['customer_remarks'])) ?></p><?php endif; ?>
    <?php if ($sel['internal_remarks']): ?><div class="small muted">Internal remarks</div><p><?= nl2br(esc($sel['internal_remarks'])) ?></p><?php endif; ?>
  </div></div>
  <div class="card"><div class="hd"><h2>Negotiations &amp; emails</h2></div><div class="bd flush">
    <?php if ($negs): ?><table class="t"><thead><tr><th>No.</th><th>Status</th><th class="num">Ask</th><th>Versions</th></tr></thead><tbody>
      <?php foreach ($negs as $n): ?><tr><td><a href="<?= site_url('crm/negotiations/' . (int) $n['id']) ?>"><?= esc($n['negotiation_no']) ?></a></td><td><?= crm_badge($n['status']) ?></td><td class="num"><?= esc(number_format((float) $n['requested_discount_pct'], 2)) ?>%</td><td>v<?= (int) $n['from_version'] ?><?= $n['result_version'] ? ' → v' . (int) $n['result_version'] : '' ?></td></tr><?php endforeach; ?></tbody></table>
    <?php else: ?><div class="empty">No negotiations yet.</div><?php endif; ?>
    <?php if ($mails): ?><table class="t"><thead><tr><th>Email to</th><th>Status</th><th>When</th></tr></thead><tbody>
      <?php foreach ($mails as $m): ?><tr><td><a href="<?= site_url('crm/emails/' . (int) $m['id']) ?>"><?= esc($m['to_email']) ?></a></td><td><?= crm_badge($m['status']) ?></td><td class="nowrap"><?= crm_dt($m['created_at']) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
  </div></div>
</div>

<?php /* ───── modals ───── */ ?>
<?php if (crm_can('quotations.send') && ! $closed): ?>
<div class="modal" id="m-send"><div class="box"><div class="hd"><h3>Send quotation <?= esc($q['quotation_no']) ?> v<?= (int) $cur['version_no'] ?></h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" action="<?= site_url('crm/quotations/' . (int) $q['id'] . '/send') ?>" data-validate><?= csrf_field() ?>
    <div class="bd form"><div class="full flash info">Sending locks this version, generates the PDF and logs the email. A locked version can only be changed by creating a new version.</div>
      <div class="full"><label for="send-ch">Delivery</label><select id="send-ch" name="channel"><option value="email">Email the PDF to the customer</option><option value="manual">Lock only — I will hand it over myself</option></select></div>
      <div class="full"><label for="send-to">Recipient email</label><input id="send-to" type="email" name="to_email" value="<?= esc((string) $c['email'], 'attr') ?>" placeholder="customer email"><div class="hint">Demo records are never actually transmitted — the email log shows what happened.</div></div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Lock &amp; send</button></div></form></div></div>
<div class="modal" id="m-resend"><div class="box"><div class="hd"><h3>Resend current version</h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" action="<?= site_url('crm/quotations/' . (int) $q['id'] . '/resend') ?>" data-validate><?= csrf_field() ?>
    <div class="bd form"><div class="full"><label for="rs-to">Recipient email</label><input id="rs-to" type="email" name="to_email" value="<?= esc((string) $c['email'], 'attr') ?>"></div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Resend</button></div></form></div></div>
<?php endif; ?>
<?php if (crm_can('quotations.create', 'quotations.send') && $locked && in_array($q['status'], ['sent', 'negotiation'], true)): ?>
<div class="modal" id="m-response"><div class="box"><div class="hd"><h3>Customer response</h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" action="<?= site_url('crm/quotations/' . (int) $q['id'] . '/response') ?>" data-validate><?= csrf_field() ?>
    <div class="bd form"><div class="full"><label class="req" for="resp">Decision</label><select id="resp" name="response" required><option value="">Select…</option><option value="accepted">Customer accepted</option><option value="rejected">Customer rejected</option></select><div class="hint">Until you record a decision the quotation stays pending. Accepting closes any open negotiation.</div></div>
      <div class="full"><label for="resp-r">Remarks</label><textarea id="resp-r" name="remarks"></textarea></div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Save response</button></div></form></div></div>
<?php endif; ?>
<?php if ($canNeg): ?>
<div class="modal" id="m-neg"><div class="box"><div class="hd"><h3>Customer wants a lower price</h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" action="<?= site_url('crm/negotiations/request') ?>" data-validate><?= csrf_field() ?><input type="hidden" name="quotation_id" value="<?= (int) $q['id'] ?>">
    <div class="bd form">
      <div><label for="n-exp">Customer expected total ₹</label><input id="n-exp" type="number" step="0.01" min="0" name="customer_expected_amount" placeholder="e.g. <?= esc((string) (int) ($cur['grand_total'] * 0.93), 'attr') ?>"><div class="hint">Current total <?= crm_money($cur['grand_total']) ?>. Leave the % empty to have it worked out from this figure.</div></div>
      <div><label for="n-pct">Requested discount %</label><input id="n-pct" type="number" step="0.001" min="0.001" max="100" name="requested_discount_pct"></div>
      <div class="full"><label class="req" for="n-why">Reason</label><textarea id="n-why" name="reason" required data-msg="Give the reason for the request."></textarea></div>
      <div class="full hint">Within your limit (<?= esc(number_format((float) ($user['max_discount_pct'] ?? 0), 1)) ?>%) it is approved straight away; above it, it goes to your manager or an admin.</div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Submit request</button></div></form></div></div>
<?php endif; ?>
<?php if ($q['status'] === 'accepted' && ! $order && crm_can('orders.manage')): ?>
<div class="modal" id="m-order"><div class="box"><div class="hd"><h3>Create sales order</h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" enctype="multipart/form-data" action="<?= site_url('crm/orders/create') ?>" data-validate><?= csrf_field() ?><input type="hidden" name="quotation_id" value="<?= (int) $q['id'] ?>">
    <div class="bd form"><div class="full flash info">Order value is fixed from the accepted version v<?= (int) $cur['version_no'] ?>: <strong><?= crm_money($cur['grand_total']) ?></strong>.</div>
      <div><label for="o-adv">Advance received ₹</label><input id="o-adv" type="number" step="0.01" min="0" name="advance_amount"></div>
      <div><label for="o-ref">Advance reference</label><input id="o-ref" name="advance_reference" maxlength="100"></div>
      <div><label for="o-del">Expected delivery</label><input id="o-del" type="date" name="expected_delivery"></div>
      <div><label for="o-file">Signed confirmation (PDF/JPG/PNG, ≤ 5 MB)</label><input id="o-file" type="file" name="confirmation" accept=".pdf,.jpg,.jpeg,.png"></div>
      <div class="full"><label for="o-rem">Remarks</label><textarea id="o-rem" name="remarks"></textarea></div>
      <div class="full"><label><input type="checkbox" name="confirm" value="1" style="width:auto;min-height:0"> Customer has confirmed — create as <strong>Confirmed</strong></label></div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Create order</button></div></form></div></div>
<?php endif; ?>
