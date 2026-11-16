<?php
/**
 * Shared quotation editor — used by: new quotation, edit draft, revise, and the negotiation page ("prepare revised quotation").
 * Every figure typed here is only an ESTIMATE; the server recomputes and validates on preview and on save.
 *
 * @var string $mode create|edit|revise  @var string $action  @var string $submit  @var array $lines  @var array $chargeRows  @var array $meta
 * @var array $products category => products  @var array $charges master charges  @var array $hidden  @var ?array $quotation  @var ?array $negotiation
 * @var string $limit  @var ?string $approvedPct  @var string $defaultTax  @var string $formId
 */
$formId   = $formId ?? 'qform';
$oldLines = old('lines', null, false);
if (is_array($oldLines)) { $lines = $oldLines; }
$oldCh = old('charges', null, false);
$e = static fn ($v) => esc((string) ($v ?? ''), 'attr');
$all = [];
foreach ($products as $cat => $ps) { foreach ($ps as $p) { $all[(int) $p['id']] = $p; } }
$val = static fn (array $l, string $k) => $l[$k] ?? '';
$num = static fn ($v) => ($v === '' || $v === null) ? '' : rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
$sizeUnits = ['mm' => 'mm', 'cm' => 'cm', 'inch' => 'inch', 'ft' => 'ft'];

$row = static function (int $i, array $l) use ($products, $all, $e, $val, $num, $sizeUnits): string {
    $pid   = (int) $val($l, 'product_id');
    $p     = $all[$pid] ?? null;
    $model = $p['pricing_model'] ?? (in_array($val($l, 'unit'), ['sqft', 'rft'], true) ? $val($l, 'unit') : '');
    ob_start(); ?>
<tr class="ln" data-model="<?= $e($model) ?>">
  <td style="min-width:280px">
    <div style="display:flex;gap:10px;align-items:flex-start">
      <div data-f="imgpreview" style="width:44px;height:44px;border-radius:6px;border:1px solid var(--line-2,#cbd5e1);background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;margin-top:2px">
        <?php if (! empty($p['image'])): ?>
          <img src="<?= esc(base_url($p['image'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <span style="font-size:18px;color:#94a3b8">📐</span>
        <?php endif; ?>
      </div>
      <div style="flex:1;min-width:0">
        <select name="lines[<?= $i ?>][product_id]" data-f="product" aria-label="Product" style="width:100%">
          <option value="">— Custom / free-typed item —</option>
          <?php foreach ($products as $cat => $ps): ?><optgroup label="<?= $e($cat) ?>">
            <?php foreach ($ps as $pr): ?><option value="<?= (int) $pr['id'] ?>" data-price="<?= $e((float) $pr['standard_price']) ?>" data-tax="<?= $e((float) $pr['tax_rate']) ?>" data-unit="<?= $e($pr['unit']) ?>" data-name="<?= $e($pr['name']) ?>" data-min="<?= $e($pr['min_price'] !== null ? (float) $pr['min_price'] : '') ?>" data-model="<?= $e($pr['pricing_model']) ?>" data-image="<?= $e(! empty($pr['image']) ? base_url($pr['image']) : '') ?>"<?= crm_sel($pid, $pr['id']) ?>><?= esc($pr['sku'] . ' · ' . $pr['name']) ?></option><?php endforeach; ?>
          </optgroup><?php endforeach; ?>
        </select>
        <input type="text" name="lines[<?= $i ?>][description]" data-f="desc" value="<?= $e($val($l, 'description')) ?>" maxlength="500" placeholder="Description" aria-label="Description" style="margin-top:4px;width:100%">
        <input type="text" name="lines[<?= $i ?>][variant]" value="<?= $e($val($l, 'variant')) ?>" maxlength="150" placeholder="Variant (e.g. 3-track, bronze)" aria-label="Variant" style="margin-top:4px;width:100%">
        <input type="text" name="lines[<?= $i ?>][specifications]" value="<?= $e($val($l, 'specifications')) ?>" placeholder="Specification (optional)" aria-label="Specification" style="margin-top:4px;width:100%">
      </div>
    </div>
  </td>
  <td style="min-width:150px">
    <div class="gap" style="flex-wrap:nowrap"><input type="number" step="any" min="0" data-f="w" name="lines[<?= $i ?>][width]" value="<?= $e($num($val($l, 'width'))) ?>" placeholder="Width" aria-label="Width" style="min-width:64px"><span>×</span><input type="number" step="any" min="0" data-f="h" name="lines[<?= $i ?>][height]" value="<?= $e($num($val($l, 'height'))) ?>" placeholder="Height" aria-label="Height" style="min-width:64px"></div>
    <div class="gap" style="flex-wrap:nowrap;margin-top:4px"><select name="lines[<?= $i ?>][size_unit]" data-f="su" aria-label="Size unit" style="min-width:64px"><option value="">unit</option><?php foreach ($sizeUnits as $k => $lab): ?><option value="<?= $k ?>"<?= crm_sel($val($l, 'size_unit'), $k) ?>><?= $lab ?></option><?php endforeach; ?></select>
      <input type="number" step="any" min="0" data-f="nos" name="lines[<?= $i ?>][nos]" value="<?= $e($num($val($l, 'nos'))) ?>" placeholder="Nos" aria-label="Number of pieces" style="min-width:64px"></div>
    <div class="hint" data-f="sizehint">Quantity is derived from size for sq-ft / running-ft items.</div>
  </td>
  <td style="min-width:110px"><input type="number" step="0.001" min="0.001" data-f="qty" name="lines[<?= $i ?>][quantity]" value="<?= $e($num($val($l, 'quantity') === '' ? 1 : $val($l, 'quantity'))) ?>" aria-label="Quantity" required>
    <input type="text" data-f="unit" name="lines[<?= $i ?>][unit]" value="<?= $e($val($l, 'unit') ?: ($p['unit'] ?? 'nos')) ?>" maxlength="20" aria-label="Unit" style="margin-top:4px"></td>
  <td style="min-width:140px"><input type="number" step="0.01" min="0" data-f="price" name="lines[<?= $i ?>][unit_price]" value="<?= $e($val($l, 'unit_price') !== '' ? number_format((float) $val($l, 'unit_price'), 2, '.', '') : '') ?>" placeholder="Unit price" aria-label="Unit price">
    <input type="number" step="0.01" min="0" data-f="override" name="lines[<?= $i ?>][line_total]" value="<?= $e($val($l, 'line_total')) ?>" placeholder="Fix line total ₹" aria-label="Fix the line total manually" title="Type the line total you want; the unit price is solved for you" style="margin-top:4px">
    <div class="hint"><span data-f="min"><?= $p && $p['min_price'] !== null ? 'min ' . crm_money($p['min_price']) : '' ?></span> <span data-f="std"><?= $p ? 'std ' . crm_money($p['standard_price']) : '' ?></span></div></td>
  <td style="min-width:80px"><input type="number" step="0.001" min="0" max="100" data-f="disc" name="lines[<?= $i ?>][discount_pct]" value="<?= $e($num($val($l, 'discount_pct'))) ?>" placeholder="0" aria-label="Discount percent">
    <input type="number" step="0.001" min="0" data-f="tax" name="lines[<?= $i ?>][tax_rate]" value="<?= $e($num($val($l, 'tax_rate') !== '' ? $val($l, 'tax_rate') : ($p['tax_rate'] ?? ''))) ?>" placeholder="GST %" aria-label="GST percent" style="margin-top:4px"></td>
  <td class="num nowrap" style="min-width:120px"><strong data-f="total">—</strong><div data-f="src" class="small"><?= ($l['price_source'] ?? '') === 'manual' ? '<span class="badge badge-amber">Manual price</span>' : '' ?></div>
    <button type="button" class="btn danger sm" data-rm aria-label="Remove line" style="margin-top:6px">Remove</button></td>
</tr>
<?php return (string) ob_get_clean();
};

// charge rows: saved/posted ones first, then masters not yet present, then one blank "other"
$chargeList = [];
if (is_array($oldCh)) { foreach ($oldCh as $c) { if (is_array($c)) { $chargeList[] = ['name' => $c['name'] ?? '', 'amount' => $c['amount'] ?? '']; } } }
else { foreach ($chargeRows as $c) { $chargeList[] = ['name' => $c['name'], 'amount' => $c['amount']]; } }
$have = array_map('strtolower', array_column($chargeList, 'name'));
foreach ($charges as $m) { if (! in_array(strtolower($m['name']), $have, true)) { $chargeList[] = ['name' => $m['name'], 'amount' => '', 'hint' => $m['calc_type'] === 'fixed' ? 'default ' . crm_money($m['value']) : (float) $m['value'] . '% (enter the amount)']; } }
$chargeList[] = ['name' => '', 'amount' => ''];
?>
<form method="post" action="<?= esc($action, 'attr') ?>" id="<?= $e($formId) ?>" data-validate data-preview="<?= site_url('crm/quotations/preview') ?>">
  <?= csrf_field() ?>
  <?php foreach ($hidden as $k => $v): ?><input type="hidden" name="<?= $e($k) ?>" value="<?= $e($v) ?>"><?php endforeach; ?>
  <?php if (! empty($quotation['id'])): ?><input type="hidden" name="quotation_id" value="<?= (int) $quotation['id'] ?>"><?php endif; ?>

  <div class="card mb">
    <div class="hd"><h2>Line items</h2><span class="small muted">Change the product, variant, size, price or the line total on any line. All edits are saved as a tracked version.</span></div>
    <div class="bd" style="padding-bottom:0">
      <div class="gap mb"><input type="search" id="prod-filter" placeholder="Filter the product lists (type a name or SKU)…" aria-label="Filter products" style="max-width:340px">
        <span class="small muted">Your discount limit: <strong><?= esc(number_format((float) $limit, 1)) ?>%</strong><?= $approvedPct !== null ? ' · approved ceiling for this quotation: <strong>' . esc(number_format((float) $approvedPct, 1)) . '%</strong>' : '' ?>. Prices typed below the catalogue standard price count as discount.</span></div>
    </div>
    <div class="bd flush tablewrap" id="lines-wrap">
      <table class="t lines" id="lines">
        <thead><tr><th>Product · variant</th><th>Size (W × H) · pieces</th><th>Qty · unit</th><th>Unit price · fixed line total</th><th>Disc % · GST %</th><th class="num">Line total</th></tr></thead>
        <tbody>
          <?php foreach (array_values($lines) as $i => $l): echo $row($i, (array) $l); endforeach; ?>
        </tbody>
      </table>
      <template id="line-tpl"><?= $row(0, []) ?></template>
      <div class="bd gap"><button type="button" class="btn alt sm" id="add-line">+ Add line</button><span class="small muted">Leave the unit price empty to use the catalogue standard price.</span></div>

      <div class="bd" style="border-top:1px solid var(--line-2)">
        <h3 class="mb">Additional charges</h3>
        <?php foreach ($chargeList as $ci => $c): ?>
          <div class="chg gap" style="margin-bottom:6px">
            <input type="text" name="charges[<?= $ci ?>][name]" value="<?= $e($c['name']) ?>" placeholder="Charge name" aria-label="Charge name" style="max-width:240px" maxlength="100">
            <input type="number" step="0.01" min="0" name="charges[<?= $ci ?>][amount]" value="<?= $e($c['amount'] !== '' ? number_format((float) $c['amount'], 2, '.', '') : '') ?>" data-tax="<?= $e($defaultTax) ?>" placeholder="Amount ₹" aria-label="Charge amount" style="max-width:160px">
            <?php if (! empty($c['hint'])): ?><span class="small muted"><?= esc($c['hint']) ?></span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="grid g-side mb">
    <div class="card"><div class="hd"><h2>Terms &amp; remarks</h2></div><div class="bd form">
      <div><label for="valid_until">Valid until</label><input type="date" id="valid_until" name="valid_until" value="<?= $e(crm_old('valid_until', $meta['valid_until'] ?? '')) ?>"></div>
      <div><label for="delivery_estimate">Delivery estimate</label><input type="text" id="delivery_estimate" name="delivery_estimate" maxlength="150" value="<?= $e(crm_old('delivery_estimate', $meta['delivery_estimate'] ?? '')) ?>" placeholder="e.g. 4–6 weeks from order confirmation"></div>
      <div class="full"><label for="payment_terms">Payment terms</label><textarea id="payment_terms" name="payment_terms"><?= esc((string) crm_old('payment_terms', $meta['payment_terms'] ?? '')) ?></textarea></div>
      <div class="full"><label for="terms">Terms &amp; conditions</label><textarea id="terms" name="terms" rows="4"><?= esc((string) crm_old('terms', $meta['terms'] ?? '')) ?></textarea></div>
      <div><label for="customer_remarks">Remarks shown to customer</label><textarea id="customer_remarks" name="customer_remarks"><?= esc((string) crm_old('customer_remarks', $meta['customer_remarks'] ?? '')) ?></textarea></div>
      <div><label for="internal_remarks">Internal remarks (never printed)</label><textarea id="internal_remarks" name="internal_remarks"><?= esc((string) crm_old('internal_remarks', $meta['internal_remarks'] ?? '')) ?></textarea></div>
      <?php if ($mode === 'revise'): ?>
        <div class="full"><label class="req" for="reason">Why is this being revised? <span class="muted">(saved in the version history)</span></label><textarea id="reason" name="reason" required data-msg="State why the quotation is being revised."><?= esc((string) crm_old('reason', $negotiation ? 'Approved discount ' . $negotiation['negotiation_no'] : '')) ?></textarea><?= crm_err('reason') ?></div>
      <?php elseif ($mode === 'edit'): ?>
        <div class="full"><label for="change_note">What did you change? <span class="muted">(optional, saved in the history)</span></label><input type="text" id="change_note" name="change_note" maxlength="500" value="<?= $e(crm_old('change_note')) ?>"></div>
      <?php endif; ?>
    </div></div>

    <div class="card"><div class="hd"><h2>Totals</h2></div><div class="bd">
      <div class="totals" style="margin:0;max-width:none">
        <div class="row-subtotal"><span>Subtotal</span><span id="t-sub">—</span></div>
        <div class="row-discount"><span>Discount Applied</span><span id="t-disc">—</span></div>
        <div class="row-taxable"><span>Net Taxable Value</span><span id="t-taxable">—</span></div>
        <div class="row-charge"><span>Additional Charges</span><span id="t-chg">—</span></div>
        <div class="row-tax"><span>GST (Taxes)</span><span id="t-tax">—</span></div>
        <div class="gt row-grand-total"><span>Grand Total (INR)</span><span id="t-grand">—</span></div>
      </div>
      <div class="mt"><label for="target_total" style="font-weight:600;font-size:13px">Negotiated final price (optional)</label>
        <input type="number" step="0.01" min="0" id="target_total" name="target_total" value="<?= $e(crm_old('target_total', $meta['target_total'] ?? '')) ?>" placeholder="Agreed grand total ₹">
        <?= crm_err('target_total') ?>
        <div class="hint">Reduces the total to this figure by spreading an extra discount over the lines. It is recorded as an explicit adjustment, counts against your discount limit and is never allowed to raise the total.</div></div>
      <div id="srv-status" class="mt small muted" role="status" aria-live="polite">Totals above are estimates until the server verifies them.</div>
      <div class="mt gap"><button type="submit" class="btn teal"><?= esc($submit) ?></button><a class="btn alt" href="<?= esc($quotation ? site_url('crm/quotations/' . $quotation['id']) : site_url('crm/quotations'), 'attr') ?>">Cancel</a></div>
    </div></div>
  </div>
</form>

<script>
(function () {
  'use strict';
  var form = document.getElementById(<?= json_encode($formId) ?>), lines = document.getElementById('lines');
  if (!form || !lines) return;
  var SIZE = { mm: 0.00328084, cm: 0.0328084, inch: 0.08333333, ft: 1 };
  var $ = function (s, c) { return (c || document).querySelector(s); }, $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var num = function (v) { v = parseFloat(String(v).replace(/,/g, '')); return isNaN(v) ? 0 : v; };
  var r2 = function (n) { return Math.round((n + Number.EPSILON) * 100) / 100; };
  var inr = function (n) { return '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var fire = function (el) { el.dispatchEvent(new Event('input', { bubbles: true })); };

  function modelOf(tr) {
    var sel = $('[data-f=product]', tr), o = sel && sel.options[sel.selectedIndex], u = $('[data-f=unit]', tr).value;
    return (o && o.dataset.model) || (u === 'sqft' || u === 'rft' ? u : '');
  }
  function deriveQty(tr) {
    var m = modelOf(tr), w = num($('[data-f=w]', tr).value), h = num($('[data-f=h]', tr).value), su = $('[data-f=su]', tr).value, n = num($('[data-f=nos]', tr).value), hint = $('[data-f=sizehint]', tr);
    if (!w || !SIZE[su] || (m !== 'sqft' && m !== 'rft')) { if (hint) hint.textContent = 'Quantity is derived from size for sq-ft / running-ft items.'; return; }
    var f = SIZE[su], pcs = n > 0 ? n : 1, q = null;
    if (m === 'sqft' && h) q = w * f * h * f * pcs; else if (m === 'rft') q = w * f * pcs;
    if (q === null) return;
    var qty = $('[data-f=qty]', tr); qty.value = (Math.round(q * 1000) / 1000).toString(); if (hint) hint.textContent = 'Quantity ' + qty.value + ' ' + m + ' derived from size.'; fire(qty);
  }
  function solveFromOverride(tr) {
    var ov = $('[data-f=override]', tr); if (!ov || ov.value === '') return;
    var q = num($('[data-f=qty]', tr).value), d = num($('[data-f=disc]', tr).value), t = num($('[data-f=tax]', tr).value), T = num(ov.value);
    var keep = 1 - d / 100, den = q * keep * (1 + t / 100); if (den <= 0) return;
    var price = $('[data-f=price]', tr); price.value = r2(T / den).toFixed(2); price.dataset.auto = '1'; fire(price);
  }
  function initRow(tr) {
    var sel = $('[data-f=product]', tr), d = $('[data-f=desc]', tr), o = sel && sel.options[sel.selectedIndex], img = $('[data-f=imgpreview]', tr);
    if (o && o.dataset.name && d.value === o.dataset.name) d.dataset.autoName = o.dataset.name;
    if (img && o && o.dataset.image) {
      img.innerHTML = '<img src="' + o.dataset.image + '" alt="" style="width:100%;height:100%;object-fit:cover">';
    }
  }
  $$('tr.ln', lines).forEach(initRow);

  // delegated: works for rows added later too
  lines.addEventListener('change', function (e) {
    var f = e.target.getAttribute('data-f'), tr = e.target.closest('tr.ln'); if (!tr) return;
    if (f === 'product') {
      var o = e.target.options[e.target.selectedIndex], d = $('[data-f=desc]', tr), img = $('[data-f=imgpreview]', tr);
      tr.dataset.model = (o && o.dataset.model) || '';
      if (img) {
        if (o && o.dataset.image) {
          img.innerHTML = '<img src="' + o.dataset.image + '" alt="" style="width:100%;height:100%;object-fit:cover">';
        } else {
          img.innerHTML = '<span style="font-size:18px;color:#94a3b8">📐</span>';
        }
      }
      if (o && o.dataset.name && (!d.value || d.value === d.dataset.autoName)) { d.value = o.dataset.name; d.dataset.autoName = o.dataset.name; }
      $('[data-f=override]', tr).value = '';
      $('[data-f=std]', tr).textContent = o && o.dataset.price ? 'std ' + inr(num(o.dataset.price)) : '';
      deriveQty(tr);
    }
  });
  lines.addEventListener('input', function (e) {
    var f = e.target.getAttribute('data-f'), tr = e.target.closest('tr.ln'); if (!tr) return;
    if (f === 'w' || f === 'h' || f === 'su' || f === 'nos' || f === 'unit') deriveQty(tr);
    if (f === 'override') solveFromOverride(tr);
    if ((f === 'qty' || f === 'disc' || f === 'tax') && $('[data-f=override]', tr).value !== '') solveFromOverride(tr);
    if (f === 'price' && e.isTrusted) { $('[data-f=override]', tr).value = ''; }   // user typed a price: drop the fixed total
  });
  var flt = document.getElementById('prod-filter');
  if (flt) flt.addEventListener('input', function () {
    var q = flt.value.toLowerCase();
    $$('select[data-f=product] option', form).forEach(function (o) { if (o.value) o.hidden = q && o.text.toLowerCase().indexOf(q) < 0 && !o.selected; });
  });

  // server-verified totals (debounced); falls back silently to the estimate
  var status = document.getElementById('srv-status'), timer = null, seq = 0;
  function setT(id, v) { var n = document.getElementById(id); if (n) n.textContent = inr(v); }
  function verify() {
    if (!('fetch' in window)) return; var my = ++seq;
    status.textContent = 'Verifying totals on the server…';
    fetch(form.getAttribute('data-preview'), { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (j) {
        if (my !== seq) return;
        $$('input[name=<?= json_encode(csrf_token()) ?>]', form).forEach(function (i) { i.value = j.csrf || i.value; });
        if (!j.ok) { status.innerHTML = ''; var s = document.createElement('span'); s.style.color = 'var(--red)'; s.textContent = '⚠ ' + j.message; status.appendChild(s); return; }
        var rows = $$('tr.ln', lines);
        j.lines.forEach(function (l, i) {
          var tr = rows[i]; if (!tr) return;
          $('[data-f=total]', tr).textContent = l.line_total_fmt;
          $('[data-f=src]', tr).innerHTML = l.price_source === 'manual' ? '<span class="badge badge-amber">Manual price</span>' : '';
          var ov = $('[data-f=override]', tr); if (ov && ov.value !== '' && Math.abs(num(ov.value) - num(l.line_total)) > 0.005) {
            $('[data-f=src]', tr).insertAdjacentHTML('beforeend', '<div class="hint">Closest achievable ' + l.line_total_fmt + ' (price has 2 decimals)</div>'); }
        });
        var t = j.totals; setT('t-sub', t.subtotal); setT('t-disc', -t.discount_total); setT('t-taxable', t.taxable_total); setT('t-chg', t.charges_total); setT('t-tax', t.tax_total); setT('t-grand', t.grand_total);
        var msg = '✔ Server-verified total ' + inr(t.grand_total) + ' · effective discount ' + j.effective_discount_pct + '%';
        if (j.target) msg += ' · negotiated total applied (extra reduction ' + inr(j.adjustment) + (j.variance && num(j.variance) > 0 ? ', closest achievable, off by ' + inr(j.variance) : '') + ')';
        status.textContent = msg;
      }).catch(function () { if (my === seq) status.textContent = 'Server check unavailable — figures above are estimates; the server re-verifies when you save.'; });
  }
  form.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(verify, 700); });
  form.addEventListener('change', function () { clearTimeout(timer); timer = setTimeout(verify, 300); });
  setTimeout(verify, 400);
})();
</script>
