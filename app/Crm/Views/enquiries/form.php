<?php
/** @var ?array $selected @var array $customers @var array $duplicates @var array $categories @var array $sources @var array $teams @var array $users */
$mode = old('customer_mode') ?: ($selected || old('customer_id') ? 'existing' : (crm_can('customers.create') ? 'new' : 'existing'));
$selId = (int) (old('customer_id') ?: ($selected['id'] ?? 0));
?>
<div class="pagehead"><h1>New enquiry</h1><a class="btn alt" href="<?= site_url('crm/enquiries') ?>">Cancel</a></div>

<?php if ($duplicates): ?>
  <div class="flash err" role="alert" style="display:block">
    <strong>Possible duplicates found.</strong> Review them before continuing:
    <ul style="margin:6px 0 0 18px">
      <?php foreach ($duplicates as $d): ?>
        <li><?php if (isset($d['enquiry_no'])): ?>
              Open enquiry <a href="<?= site_url('crm/enquiries/' . (int) $d['id']) ?>"><?= esc($d['enquiry_no']) ?></a> — <?= esc($d['project_name'] ?? '') ?> (<?= esc(crm_label($d['status'] ?? '')) ?>)
            <?php else: ?>
              Customer <a href="<?= site_url('crm/customers/' . (int) $d['id']) ?>"><?= esc(($d['customer_no'] ?? '') . ' ' . ($d['name'] ?? '')) ?></a> (<?= esc($d['mobile'] ?? '') ?>) —
              <a href="<?= site_url('crm/enquiries/new?customer_id=' . (int) $d['id']) ?>">use this customer instead</a>
            <?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
    <label style="display:block;margin-top:8px"><input type="checkbox" name="allow_duplicate" value="1" form="enq-form" style="width:auto;min-height:0"> I have checked — create it anyway</label>
  </div>
<?php endif; ?>

<form id="enq-form" method="post" action="<?= site_url('crm/enquiries/create') ?>" data-validate class="card">
  <?= csrf_field() ?>
  <div class="bd">
    <h2 class="mb">Customer</h2>
    <fieldset style="border:0;padding:0;margin:0 0 14px">
      <legend class="muted small">Who is this enquiry for?</legend>
      <label style="display:inline-flex;gap:6px;margin-right:18px"><input type="radio" name="customer_mode" value="existing" style="width:auto;min-height:0"<?= $mode === 'existing' ? ' checked' : '' ?>> Existing customer</label>
      <?php if (crm_can('customers.create')): ?><label style="display:inline-flex;gap:6px"><input type="radio" name="customer_mode" value="new" style="width:auto;min-height:0"<?= $mode === 'new' ? ' checked' : '' ?>> New customer</label><?php endif; ?>
    </fieldset>

    <div class="form" id="cust-existing">
      <div class="full"><label for="customer_id">Select customer</label>
        <select id="customer_id" name="customer_id" data-search>
          <option value="">— choose —</option>
          <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $selId === (int) $c['id'] ? ' selected' : '' ?>><?= esc($c['customer_no'] . ' · ' . $c['name'] . ' · ' . $c['mobile'] . ($c['city'] ? ' · ' . $c['city'] : '')) ?></option><?php endforeach; ?>
          <?php if ($selected && ! in_array((int) $selected['id'], array_map('intval', array_column($customers, 'id')), true)): ?><option value="<?= (int) $selected['id'] ?>" selected><?= esc($selected['customer_no'] . ' · ' . $selected['name']) ?></option><?php endif; ?>
        </select><?= crm_err('customer_id') ?></div>
    </div>

    <?php if (crm_can('customers.create')): ?>
    <div class="form" id="cust-new">
      <div><label class="req" for="c_name">Customer name</label><input id="c_name" name="name" value="<?= esc(old('name')) ?>" maxlength="150"><?= crm_err('name') ?></div>
      <div><label class="req" for="c_mobile">Mobile</label><input id="c_mobile" name="mobile" value="<?= esc(old('mobile')) ?>" inputmode="tel" maxlength="15" placeholder="10-digit mobile"><?= crm_err('mobile') ?></div>
      <div><label for="c_email">Email</label><input id="c_email" type="email" name="email" value="<?= esc(old('email')) ?>"><?= crm_err('email') ?></div>
      <div><label for="c_city">City</label><input id="c_city" name="city" value="<?= esc(old('city')) ?>"></div>
    </div>
    <?php endif; ?>

    <h2 class="mt mb">Enquiry details</h2>
    <div class="form">
      <div><label for="project_name">Project name</label><input id="project_name" name="project_name" value="<?= esc(old('project_name')) ?>" maxlength="200"><?= crm_err('project_name') ?></div>
      <div><label for="product_category_id">Product category</label><select id="product_category_id" name="product_category_id"><option value="">—</option>
        <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= crm_sel(old('product_category_id'), $c['id']) ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="full"><label for="requirements">Requirements</label><textarea id="requirements" name="requirements" placeholder="What does the customer want? Variants and sizes can be added item-by-item on the enquiry page."><?= esc(old('requirements')) ?></textarea></div>
      <div><label for="site_location">Site location</label><input id="site_location" name="site_location" value="<?= esc(old('site_location')) ?>"></div>
      <div><label for="est_budget">Estimated budget (₹)</label><input id="est_budget" type="number" step="0.01" min="0" name="est_budget" value="<?= esc(old('est_budget')) ?>"><?= crm_err('est_budget') ?></div>
      <div><label for="expected_purchase_date">Expected purchase date</label><input id="expected_purchase_date" type="date" name="expected_purchase_date" value="<?= esc(old('expected_purchase_date')) ?>"><?= crm_err('expected_purchase_date') ?></div>
      <div><label for="source_id">Lead source</label><select id="source_id" name="source_id"><option value="">—</option>
        <?php foreach ($sources as $s): ?><option value="<?= (int) $s['id'] ?>" data-code="<?= esc($s['code']) ?>"<?= crm_sel(old('source_id'), $s['id']) ?>><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
      <div><label for="source_channel">Channel</label><select id="source_channel" name="source_channel">
        <?php foreach (\Crm\Services\ReferenceData::LEAD_SOURCES as $k => $v): ?><option value="<?= esc($k) ?>"<?= crm_sel(old('source_channel') ?: 'manual', $k) ?>><?= esc($v) ?></option><?php endforeach; ?></select></div>
      <div><label for="priority">Priority</label><select id="priority" name="priority"><?php foreach (['low', 'medium', 'high', 'urgent'] as $s): ?><option value="<?= $s ?>"<?= crm_sel(old('priority') ?: 'medium', $s) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><?= crm_err('priority') ?></div>
      <?php if (crm_can('enquiries.assign')): ?>
      <div><label for="current_owner_id">Assign to</label><select id="current_owner_id" name="current_owner_id"><option value="">Automatic (assignment rules)</option>
        <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= crm_sel(old('current_owner_id'), $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select>
        <div class="hint">Leave on Automatic to let the configured rules pick an eligible salesperson. An explicit choice is never overwritten.</div></div>
      <?php endif; ?>
      <div class="full"><label for="description">Notes</label><textarea id="description" name="description"><?= esc(old('description')) ?></textarea></div>
    </div>
  </div>
  <div class="bd gap" style="border-top:1px solid var(--line-2)"><button class="btn teal" type="submit">Create enquiry</button><a class="btn alt" href="<?= site_url('crm/enquiries') ?>">Cancel</a></div>
</form>

<script>
// Progressive enhancement: show only the relevant customer block. Without JS both blocks stay visible and the server uses the chosen customer_id when present.
(function () {
  var radios = document.querySelectorAll('input[name=customer_mode]'), ex = document.getElementById('cust-existing'), nw = document.getElementById('cust-new');
  if (!radios.length || !nw) return;
  function sync() {
    var m = document.querySelector('input[name=customer_mode]:checked').value;
    ex.style.display = m === 'existing' ? '' : 'none'; nw.style.display = m === 'new' ? '' : 'none';
    var sel = document.getElementById('customer_id'); if (sel) { sel.disabled = m !== 'existing'; }
    ['c_name', 'c_mobile'].forEach(function (i) { var n = document.getElementById(i); if (n) { n.required = m === 'new'; n.disabled = m !== 'new'; } });
    ['c_email', 'c_city'].forEach(function (i) { var n = document.getElementById(i); if (n) n.disabled = m !== 'new'; });
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); }); sync();
})();
</script>
