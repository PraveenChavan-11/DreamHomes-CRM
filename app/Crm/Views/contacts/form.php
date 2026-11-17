<?php
/** @var string $mode @var array $contact @var list<array> $organisations */
$isEdit = ($mode === 'edit');
$roles = ['Owner', 'Director', 'Purchase Manager', 'Project Manager', 'Architect', 'Interior Designer', 'Finance / Accounts', 'Site Engineer', 'Procurement Executive', 'Admin', 'Other'];
$channels = ['phone' => 'Phone Call', 'email' => 'Email', 'whatsapp' => 'WhatsApp', 'in_person' => 'In-Person'];
$action = $isEdit ? site_url('crm/contacts/' . $contact['id'] . '/update') : site_url('crm/contacts/create');
?>
<div class="pagehead">
  <div>
    <h1><?= $isEdit ? 'Edit Contact Person' : 'New Contact Person' ?></h1>
    <div class="muted small"><?= $isEdit ? 'Update contact coordinates, designation, role, and decision-maker status.' : 'Register a contact person under a parent organisation.' ?></div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= ! empty($contact['organisation_id']) ? site_url('crm/organisations/' . $contact['organisation_id'] . '?tab=contacts') : site_url('crm/contacts') ?>">Cancel</a>
  </div>
</div>

<form class="card" method="post" action="<?= $action ?>" style="max-width:860px">
  <?= csrf_field() ?>
  <?php if (! empty($contact['organisation_id'])): ?>
    <input type="hidden" name="return_to_org" value="1">
  <?php endif; ?>
  <div class="hd">
    <h2>1. Organisation &amp; Identity</h2>
  </div>
  <div class="bd grid g2">
    <div style="grid-column:1/-1">
      <label for="organisation_id">Parent Business Organisation <span class="req">*</span></label>
      <select id="organisation_id" name="organisation_id" required>
        <option value="">— Select Organisation —</option>
        <?php foreach ($organisations as $o): ?>
          <option value="<?= (int) $o['id'] ?>"<?= crm_sel(crm_old('organisation_id', $contact['organisation_id'] ?? ''), $o['id']) ?>><?= esc($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?= crm_err('organisation_id') ?>
    </div>
    <div>
      <label for="first_name">First Name <span class="req">*</span></label>
      <input type="text" id="first_name" name="first_name" required value="<?= esc(crm_old('first_name', $contact['first_name'] ?? '')) ?>">
      <?= crm_err('first_name') ?>
    </div>
    <div>
      <label for="last_name">Last Name <span class="req">*</span></label>
      <input type="text" id="last_name" name="last_name" required value="<?= esc(crm_old('last_name', $contact['last_name'] ?? '')) ?>">
      <?= crm_err('last_name') ?>
    </div>
    <div>
      <label for="designation">Designation / Job Title</label>
      <input type="text" id="designation" name="designation" value="<?= esc(crm_old('designation', $contact['designation'] ?? '')) ?>" placeholder="e.g. Principal Architect / Head of Procurement">
    </div>
    <div>
      <label for="department">Department</label>
      <input type="text" id="department" name="department" value="<?= esc(crm_old('department', $contact['department'] ?? '')) ?>" placeholder="e.g. Commercial / Projects">
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>2. Contact Coordinates &amp; Preference</h2>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="mobile">Mobile Number <span class="req">*</span></label>
      <input type="text" id="mobile" name="mobile" required value="<?= esc(crm_old('mobile', $contact['mobile'] ?? '')) ?>" placeholder="+91 98765 43210">
      <?= crm_err('mobile') ?>
    </div>
    <div>
      <label for="alternate_number">Alternate Number</label>
      <input type="text" id="alternate_number" name="alternate_number" value="<?= esc(crm_old('alternate_number', $contact['alternate_number'] ?? '')) ?>">
    </div>
    <div>
      <label for="email">Work Email</label>
      <input type="email" id="email" name="email" value="<?= esc(crm_old('email', $contact['email'] ?? '')) ?>" placeholder="name@company.com">
      <?= crm_err('email') ?>
    </div>
    <div>
      <label for="whatsapp_no">WhatsApp Number</label>
      <input type="text" id="whatsapp_no" name="whatsapp_no" value="<?= esc(crm_old('whatsapp_no', $contact['whatsapp_no'] ?? '')) ?>" placeholder="+91 98765 43210">
    </div>
    <div>
      <label for="preferred_channel">Preferred Channel</label>
      <select id="preferred_channel" name="preferred_channel">
        <?php foreach ($channels as $k => $lbl): ?>
          <option value="<?= $k ?>"<?= crm_sel(crm_old('preferred_channel', $contact['preferred_channel'] ?? 'phone'), $k) ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="preferred_language">Preferred Language</label>
      <input type="text" id="preferred_language" name="preferred_language" value="<?= esc(crm_old('preferred_language', $contact['preferred_language'] ?? 'English')) ?>">
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>3. Role &amp; Decision Authority</h2>
  </div>
  <div class="bd grid g3">
    <div>
      <label for="contact_role">Contact Role</label>
      <select id="contact_role" name="contact_role">
        <?php foreach ($roles as $r): ?>
          <option value="<?= esc($r) ?>"<?= crm_sel(crm_old('contact_role', $contact['contact_role'] ?? 'Purchase Manager'), $r) ?>><?= esc($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="is_decision_maker">Decision Authority</label>
      <select id="is_decision_maker" name="is_decision_maker">
        <option value="1"<?= crm_sel(crm_old('is_decision_maker', $contact['is_decision_maker'] ?? '0'), '1') ?>>Key Decision Maker (Signs Off)</option>
        <option value="0"<?= crm_sel(crm_old('is_decision_maker', $contact['is_decision_maker'] ?? '0'), '0') ?>>Staff / Influencer / Recommender</option>
      </select>
    </div>
    <div>
      <label for="status">Contact Status</label>
      <select id="status" name="status">
        <option value="active"<?= crm_sel(crm_old('status', $contact['status'] ?? 'active'), 'active') ?>>Active</option>
        <option value="inactive"<?= crm_sel(crm_old('status', $contact['status'] ?? 'active'), 'inactive') ?>>Inactive</option>
      </select>
    </div>
    <div style="grid-column:1/-1">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600">
        <input type="checkbox" name="is_primary" value="1"<?= ! empty(crm_old('is_primary', $contact['is_primary'] ?? 0)) ? ' checked' : '' ?>>
        Set as Primary Contact Person for this Organisation
      </label>
    </div>
    <div style="grid-column:1/-1">
      <label for="remarks">Remarks / Operational Notes</label>
      <textarea id="remarks" name="remarks" rows="2" placeholder="Availability timings, personal preferences, project authority..."><?= esc(crm_old('remarks', $contact['remarks'] ?? '')) ?></textarea>
    </div>
  </div>

  <div class="ft" style="display:flex;justify-content:flex-end;gap:12px;padding:16px 22px">
    <a class="btn alt" href="<?= ! empty($contact['organisation_id']) ? site_url('crm/organisations/' . $contact['organisation_id'] . '?tab=contacts') : site_url('crm/contacts') ?>">Cancel</a>
    <button class="btn teal" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Contact' ?></button>
  </div>
</form>
