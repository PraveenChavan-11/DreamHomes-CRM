<?php
/** @var array $o @var array $c @var array $e @var array $q @var array $v @var list<array> $items @var list<array> $docs @var list<array> $events @var list<string> $flow @var string $owner @var string $creator */
$qty = static fn ($x) => rtrim(rtrim(number_format((float) $x, 3, '.', ''), '0'), '.');
$needsReason = ['on_hold', 'cancelled'];
$balance = \Crm\Services\Money::round(\Crm\Services\Money::sub($o['grand_total'], $o['advance_amount']));
?>
<div class="pagehead">
  <h1><?= esc($o['order_no']) ?> <?= crm_badge($o['status']) ?></h1>
  <div class="gap">
    <?php if (crm_can('enquiries.close') && $e['status'] !== 'won' && in_array($o['status'], ['confirmed', 'pending_confirmation'], true)): ?>
      <form method="post" action="<?= site_url('crm/enquiries/' . (int) $e['id'] . '/won') ?>" data-confirm="Mark enquiry <?= esc($e['enquiry_no'], 'attr') ?> as WON using this order?"><?= csrf_field() ?><button class="btn teal" type="submit">Mark enquiry as won</button></form>
    <?php endif; ?>
    <?php if (crm_can('orders.manage') && $flow): ?>
      <?php foreach ($flow as $to): ?>
        <?php if (in_array($to, $needsReason, true)): ?><button class="btn <?= $to === 'cancelled' ? 'danger' : 'alt' ?>" type="button" data-modal="m-status" data-fill="to=<?= $to ?>"><?= $to === 'on_hold' ? 'Put on hold' : 'Cancel order' ?></button>
        <?php else: ?><form method="post" action="<?= site_url('crm/orders/' . (int) $o['id'] . '/status') ?>"><?= csrf_field() ?><input type="hidden" name="to" value="<?= esc($to, 'attr') ?>"><button class="btn alt" type="submit"><?= $to === 'confirmed' ? 'Confirm order' : ($to === 'closed' ? 'Close order' : 'Move to ' . crm_label($to)) ?></button></form><?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="grid g4 mb">
  <div class="card kpi good"><div class="l">Order value</div><div class="v"><?= crm_money0($o['grand_total']) ?></div><div class="s">Fixed from accepted <?= esc($q['quotation_no']) ?> v<?= (int) $v['version_no'] ?></div></div>
  <div class="card kpi"><div class="l">Advance</div><div class="v"><?= crm_money0($o['advance_amount']) ?></div><div class="s"><?= esc((string) $o['advance_reference'] ?: 'No reference') ?></div></div>
  <div class="card kpi"><div class="l">Balance</div><div class="v"><?= crm_money0($balance) ?></div></div>
  <div class="card kpi"><div class="l">Expected delivery</div><div class="v" style="font-size:20px"><?= crm_date($o['expected_delivery']) ?></div><div class="s"><?= $o['confirmed_at'] ? 'Confirmed ' . crm_date($o['confirmed_at']) : 'Not confirmed yet' ?></div></div>
</div>

<div class="grid g-side mb">
  <div class="card"><div class="hd"><h2>Order details</h2></div><div class="bd"><dl class="kv">
    <dt>Organisation</dt><dd><?php if (! empty($org['id'])): ?><a href="<?= site_url('crm/organisations/' . (int) $org['id']) ?>"><strong><?= esc($org['name']) ?></strong> (<?= esc($org['org_no']) ?>)</a><?php else: ?><strong><?= esc($c['company_name'] ?: $c['name']) ?></strong><?php endif; ?><?php if (! empty($contact['first_name'])): ?> · Contact: <?= esc($contact['first_name'] . ' ' . $contact['last_name']) ?><?= ! empty($contact['designation']) ? ' (' . esc($contact['designation']) . ')' : '' ?><?php else: ?> · <?= esc($c['name']) ?><?php endif; ?> · <?= esc($contact['mobile_number'] ?? $c['mobile']) ?></dd>
    <dt>Enquiry</dt><dd><a href="<?= site_url('crm/enquiries/' . (int) $e['id']) ?>"><?= esc($e['enquiry_no']) ?></a> <?= crm_badge($e['status']) ?> <?= $e['final_value'] !== null ? '· final value ' . crm_money($e['final_value']) : '' ?></dd>
    <dt>Quotation</dt><dd><a href="<?= site_url('crm/quotations/' . (int) $q['id'] . '?v=' . (int) $v['version_no']) ?>"><?= esc($q['quotation_no']) ?> v<?= (int) $v['version_no'] ?></a> · accepted <?= crm_date($q['responded_at']) ?></dd>
    <dt>Salesperson</dt><dd><?= esc($owner) ?></dd><dt>Created by</dt><dd><?= esc($creator) ?> on <?= crm_dt($o['created_at']) ?></dd>
    <?php if ($o['remarks']): ?><dt>Remarks</dt><dd><?= nl2br(esc($o['remarks'])) ?></dd><?php endif; ?>
  </dl></div></div>
  <div class="card"><div class="hd"><h2>Documents</h2></div><div class="bd">
    <?php if (! $docs): ?><div class="muted">No signed confirmation uploaded.</div><?php endif; ?>
    <ul style="padding-left:18px;margin:0"><?php foreach ($docs as $d): ?><li><a href="<?= site_url('crm/documents/' . (int) $d['id']) ?>"><?= esc($d['original_name']) ?></a> <span class="small muted"><?= number_format($d['size_bytes'] / 1024, 0) ?> KB · <?= crm_date($d['created_at']) ?></span></li><?php endforeach; ?></ul>
  </div></div>
</div>

<div class="card mb"><div class="hd"><h2>Items (snapshot of the accepted quotation)</h2></div><div class="bd flush tablewrap"><table class="t">
  <thead><tr><th>#</th><th>Item</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Discount</th><th class="num">GST</th><th class="num">Line total</th></tr></thead><tbody>
  <?php foreach ($items as $n => $i): ?><tr><td><?= $n + 1 ?></td><td><?= esc($i['description']) ?><?= $i['sku'] ? ' <span class="muted small">(' . esc($i['sku']) . ')</span>' : '' ?></td><td class="num"><?= $qty($i['quantity']) ?> <?= esc($i['unit']) ?></td><td class="num"><?= crm_money($i['unit_price']) ?></td><td class="num"><?= crm_money($i['discount_amount']) ?></td><td class="num"><?= crm_money($i['tax_amount']) ?></td><td class="num"><strong><?= crm_money($i['line_total']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="bd" style="padding:20px 24px;background:#FAFCFB;border-top:1px solid var(--line-2)">
    <div style="margin-left:auto;width:100%;max-width:460px;background:#FFFFFF;border:1px solid var(--line);border-radius:var(--r);padding:18px 22px;box-shadow:0 1px 4px rgba(0,0,0,0.04)">
      <table style="width:100%;border-collapse:collapse;font-size:13.5px">
        <tbody>
          <tr style="border-bottom:1px dashed rgba(0,0,0,0.08)">
            <td style="padding:8px 0;color:var(--text-muted);font-weight:500">Item Subtotal</td>
            <td style="padding:8px 0;text-align:right;font-weight:600;font-variant-numeric:tabular-nums"><?= crm_money($o['subtotal']) ?></td>
          </tr>
          <?php if ((float) $o['discount_total'] > 0): ?>
            <tr style="border-bottom:1px dashed rgba(0,0,0,0.08)">
              <td style="padding:8px 0;color:#B91C1C;font-weight:600">Discount Applied</td>
              <td style="padding:8px 0;text-align:right;color:#B91C1C;font-weight:700;font-variant-numeric:tabular-nums">− <?= crm_money($o['discount_total']) ?></td>
            </tr>
          <?php endif; ?>
          <?php if ((float) $o['charges_total'] > 0): ?>
            <tr style="border-bottom:1px dashed rgba(0,0,0,0.08)">
              <td style="padding:7px 0;color:var(--text-muted);font-weight:500">Additional Charges</td>
              <td style="padding:7px 0;text-align:right;font-weight:600;font-variant-numeric:tabular-nums"><?= crm_money($o['charges_total']) ?></td>
            </tr>
          <?php endif; ?>
          <tr style="border-bottom:1px dashed rgba(0,0,0,0.08)">
            <td style="padding:7px 0;color:var(--text-muted);font-size:12.5px">GST (Taxes)</td>
            <td style="padding:7px 0;text-align:right;font-weight:600;font-size:13px;font-variant-numeric:tabular-nums"><?= crm_money($o['tax_total']) ?></td>
          </tr>
          <tr style="border-top:2px solid var(--th-primary)">
            <td style="padding:14px 0 4px;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--th-dark)">Order Total (INR)</td>
            <td style="padding:14px 0 4px;text-align:right;font-size:22px;font-weight:800;color:var(--th-primary);font-variant-numeric:tabular-nums"><?= crm_money($o['grand_total']) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div></div>

<div class="card"><div class="hd"><h2>Order history</h2></div><div class="bd">
  <?php if (! $events): ?><div class="muted">No events recorded.</div><?php endif; ?>
  <ul class="tl"><?php foreach ($events as $ev): ?><li><div class="when"><?= crm_dt($ev['created_at']) ?></div><div class="what"><?= esc($ev['label']) ?></div><div class="meta">By <?= esc($ev['user_name']) ?><?= $ev['prev_status'] || $ev['new_status'] ? ' · ' . esc(crm_label($ev['prev_status'])) . ' → ' . esc(crm_label($ev['new_status'])) : '' ?><?= $ev['reason'] ?? null ? ' · ' . esc($ev['reason']) : '' ?></div></li><?php endforeach; ?></ul>
</div></div>

<?php if (crm_can('orders.manage')): ?>
<div class="modal" id="m-status"><div class="box"><div class="hd"><h3>Change order status</h3><button type="button" class="btn alt sm" data-close>×</button></div>
  <form method="post" action="<?= site_url('crm/orders/' . (int) $o['id'] . '/status') ?>" data-validate><?= csrf_field() ?><input type="hidden" name="to" value="">
    <div class="bd form"><div class="full"><label class="req" for="st-r">Reason</label><textarea id="st-r" name="reason" required data-msg="A reason is required."></textarea></div></div>
    <div class="ft"><button type="button" class="btn alt" data-close>Back</button><button class="btn teal" type="submit">Confirm</button></div></form></div></div>
<?php endif; ?>
