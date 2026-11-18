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
    <h2 class="mb">Organisation &amp; Client Account</h2>
    <fieldset style="border:0;padding:0;margin:0 0 14px">
      <legend class="muted small">Which business organisation is this enquiry for?</legend>
      <label style="display:inline-flex;gap:6px;margin-right:18px"><input type="radio" name="org_mode" value="existing" style="width:auto;min-height:0"<?= $mode !== 'new' ? ' checked' : '' ?>> Existing organisation</label>
      <?php if (crm_can('customers.create')): ?><label style="display:inline-flex;gap:6px"><input type="radio" name="org_mode" value="new" style="width:auto;min-height:0"<?= $mode === 'new' ? ' checked' : '' ?>> + New organisation</label><?php endif; ?>
    </fieldset>

    <div class="form" id="org-existing">
      <div>
        <label class="req" for="organisation_id">Business Organisation</label>
        <select id="organisation_id" name="organisation_id" data-search required>
          <option value="">— choose organisation —</option>
          <?php foreach ($organisations ?? [] as $o): ?>
            <option value="<?= (int) $o['id'] ?>"<?= ($selected_org_id ?? 0) === (int) $o['id'] ? ' selected' : '' ?>><?= esc($o['org_no'] . ' · ' . $o['name'] . ($o['city'] ? ' (' . $o['city'] . ')' : '')) ?></option>
          <?php endforeach; ?>
        </select>
        <?= crm_err('organisation_id') ?>
      </div>
      <div>
        <label for="contact_id">Primary Contact Person</label>
        <select id="contact_id" name="contact_id" data-search>
          <option value="">— default primary contact —</option>
          <?php foreach ($contacts ?? [] as $cnt): ?>
            <option value="<?= (int) $cnt['id'] ?>" data-org="<?= (int) $cnt['organisation_id'] ?>"><?= esc($cnt['first_name'] . ' ' . $cnt['last_name'] . ($cnt['designation'] ? ' · ' . $cnt['designation'] : '') . ' (' . ($cnt['mobile'] ?? ($cnt['mobile_number'] ?? '')) . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (crm_can('customers.create')): ?>
    <div class="form" id="org-new" style="display:none">
      <div><label class="req" for="org_name">Organisation name</label><input id="org_name" name="org_name" value="<?= esc(old('org_name')) ?>" maxlength="150"><?= crm_err('org_name') ?></div>
      <div>
        <label for="org_type">Organisation type</label>
        <select id="org_type" name="org_type">
          <?php foreach (['Builder / Developer', 'Architect', 'Interior Designer', 'Corporate', 'Contractor', 'Individual', 'Institutional', 'Referral Partner'] as $ot): ?>
            <option value="<?= esc($ot) ?>"><?= esc($ot) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label class="req" for="contact_name">Contact person</label><input id="contact_name" name="contact_name" value="<?= esc(old('contact_name')) ?>" maxlength="100"></div>
      <div><label for="contact_designation">Designation</label><input id="contact_designation" name="contact_designation" value="<?= esc(old('contact_designation')) ?>" placeholder="e.g. Director, Purchase Manager"></div>
      <div><label class="req" for="contact_mobile">Mobile</label><input id="contact_mobile" name="contact_mobile" value="<?= esc(old('contact_mobile')) ?>" inputmode="tel" maxlength="15" placeholder="10-digit mobile"></div>
      <div><label for="contact_email">Email</label><input id="contact_email" type="email" name="contact_email" value="<?= esc(old('contact_email')) ?>"></div>
      <div><label for="org_city">City</label><input id="org_city" name="org_city" value="<?= esc(old('org_city', 'Pune')) ?>"></div>
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
(function () {
  var radios = document.querySelectorAll('input[name=org_mode]'), ex = document.getElementById('org-existing'), nw = document.getElementById('org-new');
  var orgSel = document.getElementById('organisation_id'), cntSel = document.getElementById('contact_id');
  
  function sync() {
    var checked = document.querySelector('input[name=org_mode]:checked');
    var m = checked ? checked.value : 'existing';
    if (ex) ex.style.display = m === 'existing' ? '' : 'none';
    if (nw) nw.style.display = m === 'new' ? '' : 'none';
    if (orgSel) orgSel.disabled = m !== 'existing';
    ['org_name', 'contact_name', 'contact_mobile'].forEach(function (i) {
      var el = document.getElementById(i);
      if (el) { el.required = (m === 'new'); el.disabled = (m !== 'new'); }
    });
  }

  function filterContacts() {
    if (!orgSel || !cntSel) return;
    var selectedOrg = orgSel.value;
    var opts = cntSel.querySelectorAll('option');
    opts.forEach(function (opt) {
      if (!opt.value) { opt.style.display = ''; return; }
      var org = opt.getAttribute('data-org');
      if (!selectedOrg || org === selectedOrg) {
        opt.style.display = '';
      } else {
        opt.style.display = 'none';
      }
    });
  }

  radios.forEach(function (r) { r.addEventListener('change', sync); });
  if (orgSel) orgSel.addEventListener('change', filterContacts);
  sync();
  filterContacts();
})();
</script>
