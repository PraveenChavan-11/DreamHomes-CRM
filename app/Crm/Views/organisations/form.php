<?php
/** @var string $mode @var array $org @var list<array> $owners @var list<array> $territories @var list<array> $sources */
$isEdit = ($mode === 'edit');
$types = ['Individual', 'Corporate', 'Builder / Developer', 'Architect', 'Interior Designer', 'Contractor', 'Dealer / Distributor', 'Institutional', 'Referral Partner', 'Other'];
$action = $isEdit ? site_url('crm/organisations/' . $org['id'] . '/update') : site_url('crm/organisations/create');
?>
<div class="pagehead">
  <div>
    <h1><?= $isEdit ? 'Edit ' . esc($org['name']) : 'New Organisation Account' ?></h1>
    <div class="muted small"><?= $isEdit ? 'Update business account details, addresses, and commercial attributes.' : 'Register a new corporate, developer, architect, or contractor account.' ?></div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= $isEdit ? site_url('crm/organisations/' . $org['id']) : site_url('crm/organisations') ?>">Cancel</a>
  </div>
</div>

<form class="card" method="post" action="<?= $action ?>" style="max-width:960px">
  <?= csrf_field() ?>
  <div class="hd">
    <h2>1. General Organisation Profile</h2>
    <span class="muted small">Primary commercial identity and classification</span>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="name">Organisation Name <span class="req">*</span></label>
      <input type="text" id="name" name="name" required value="<?= esc(crm_old('name', $org['name'] ?? '')) ?>" placeholder="e.g. Godrej Properties Ltd / Skyline Architects">
      <?= crm_err('name') ?>
    </div>
    <div>
      <label for="type">Organisation Type <span class="req">*</span></label>
      <select id="type" name="type" required>
        <?php foreach ($types as $t): ?>
          <option value="<?= esc($t) ?>"<?= crm_sel(crm_old('type', $org['type'] ?? 'Corporate'), $t) ?>><?= esc($t) ?></option>
        <?php endforeach; ?>
      </select>
      <?= crm_err('type') ?>
    </div>
    <div>
      <label for="industry">Industry / Sector</label>
      <input type="text" id="industry" name="industry" value="<?= esc(crm_old('industry', $org['industry'] ?? 'Real Estate & Interior Solutions')) ?>" placeholder="e.g. Commercial Construction, Luxury Residential">
    </div>
    <div>
      <label for="business_category">Business Category</label>
      <input type="text" id="business_category" name="business_category" value="<?= esc(crm_old('business_category', $org['business_category'] ?? 'B2B Enterprise')) ?>">
    </div>
    <div>
      <label for="website">Website URL</label>
      <input type="url" id="website" name="website" value="<?= esc(crm_old('website', $org['website'] ?? '')) ?>" placeholder="https://example.com">
    </div>
    <div>
      <label for="sales_territory_id">Sales Territory</label>
      <select id="sales_territory_id" name="sales_territory_id">
        <option value="">— Select Territory —</option>
        <?php foreach ($territories as $t): ?>
          <option value="<?= (int) $t['id'] ?>"<?= crm_sel(crm_old('sales_territory_id', $org['sales_territory_id'] ?? ''), $t['id']) ?>><?= esc($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>2. Primary Communication &amp; Tax Compliance</h2>
    <span class="muted small">Contact coordinates and statutory GSTIN / PAN information</span>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="primary_email">Primary Email</label>
      <input type="email" id="primary_email" name="primary_email" value="<?= esc(crm_old('primary_email', $org['primary_email'] ?? '')) ?>" placeholder="info@company.com">
      <?= crm_err('primary_email') ?>
    </div>
    <div>
      <label for="primary_phone">Primary Phone</label>
      <input type="text" id="primary_phone" name="primary_phone" value="<?= esc(crm_old('primary_phone', $org['primary_phone'] ?? '')) ?>" placeholder="+91 98765 43210">
      <?= crm_err('primary_phone') ?>
    </div>
    <div>
      <label for="alternate_phone">Alternate Phone</label>
      <input type="text" id="alternate_phone" name="alternate_phone" value="<?= esc(crm_old('alternate_phone', $org['alternate_phone'] ?? '')) ?>">
    </div>
    <div>
      <label for="gstin">GSTIN (15 Digits)</label>
      <input type="text" id="gstin" name="gstin" maxlength="15" style="text-transform:uppercase" value="<?= esc(crm_old('gstin', $org['gstin'] ?? '')) ?>" placeholder="27AAAAA0000A1Z5">
    </div>
    <div>
      <label for="pan">PAN Number</label>
      <input type="text" id="pan" name="pan" maxlength="10" style="text-transform:uppercase" value="<?= esc(crm_old('pan', $org['pan'] ?? '')) ?>" placeholder="AAAAA0000A">
    </div>
    <div>
      <label for="source_id">Lead Source</label>
      <select id="source_id" name="source_id">
        <option value="">— Select Source —</option>
        <?php foreach ($sources as $s): ?>
          <option value="<?= (int) $s['id'] ?>"<?= crm_sel(crm_old('source_id', $org['source_id'] ?? ''), $s['id']) ?>><?= esc($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>3. Physical Locations &amp; Site Addresses</h2>
    <span class="muted small">Registered company headquarters, billing address, and default site installation address</span>
  </div>
  <div class="bd grid g2">
    <div style="grid-column:1/-1">
      <label for="registered_address">Registered / Corporate Office Address</label>
      <textarea id="registered_address" name="registered_address" rows="2" placeholder="Full registered legal office address"><?= esc(crm_old('registered_address', $org['registered_address'] ?? '')) ?></textarea>
    </div>
    <div>
      <label for="billing_address">Billing Address</label>
      <textarea id="billing_address" name="billing_address" rows="2" placeholder="Address printed on GST tax invoices"><?= esc(crm_old('billing_address', $org['billing_address'] ?? '')) ?></textarea>
    </div>
    <div>
      <label for="default_site_address">Default Site / Project Address</label>
      <textarea id="default_site_address" name="default_site_address" rows="2" placeholder="Default delivery and site survey location"><?= esc(crm_old('default_site_address', $org['default_site_address'] ?? '')) ?></textarea>
    </div>
    <div>
      <label for="city">City</label>
      <input type="text" id="city" name="city" value="<?= esc(crm_old('city', $org['city'] ?? 'Pune')) ?>">
    </div>
    <div>
      <label for="state">State</label>
      <input type="text" id="state" name="state" value="<?= esc(crm_old('state', $org['state'] ?? 'Maharashtra')) ?>">
    </div>
    <div>
      <label for="pin_code">PIN Code</label>
      <input type="text" id="pin_code" name="pin_code" maxlength="6" value="<?= esc(crm_old('pin_code', $org['pin_code'] ?? '')) ?>">
    </div>
    <div>
      <label for="country">Country</label>
      <input type="text" id="country" name="country" value="<?= esc(crm_old('country', $org['country'] ?? 'India')) ?>">
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>4. Sales Governance, Account Ownership &amp; Rating</h2>
  </div>
  <div class="bd grid g3">
    <div>
      <label for="owner_user_id">Account Owner <span class="req">*</span></label>
      <select id="owner_user_id" name="owner_user_id" required>
        <?php foreach ($owners as $o): ?>
          <option value="<?= (int) $o['id'] ?>"<?= crm_sel(crm_old('owner_user_id', $org['owner_user_id'] ?? ''), $o['id']) ?>><?= esc($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="account_rating">Account Rating</label>
      <select id="account_rating" name="account_rating">
        <?php foreach (['Hot' => '🔥 Hot (Immediate Deals)', 'Warm' => '⚡ Warm (Active Pipeline)', 'Cold' => '❄️ Cold (Long-term / Nurturing)'] as $val => $lbl): ?>
          <option value="<?= $val ?>"<?= crm_sel(crm_old('account_rating', $org['account_rating'] ?? 'Warm'), $val) ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="status">Account Status</label>
      <select id="status" name="status">
        <?php foreach (['active', 'inactive', 'on_hold', 'archived'] as $s): ?>
          <option value="<?= $s ?>"<?= crm_sel(crm_old('status', $org['status'] ?? 'active'), $s) ?>><?= esc(crm_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="grid-column:1/-1">
      <label for="notes">Strategic Notes / Commercial Context</label>
      <textarea id="notes" name="notes" rows="3" placeholder="Key decision makers, special commercial pricing terms, project scope..."><?= esc(crm_old('notes', $org['notes'] ?? '')) ?></textarea>
    </div>
  </div>

  <div class="ft" style="display:flex;justify-content:flex-end;gap:12px;padding:16px 22px">
    <a class="btn alt" href="<?= $isEdit ? site_url('crm/organisations/' . $org['id']) : site_url('crm/organisations') ?>">Cancel</a>
    <button class="btn teal" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Organisation Account' ?></button>
  </div>
</form>
